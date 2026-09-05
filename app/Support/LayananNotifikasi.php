<?php

namespace App\Support;

use App\Enums\JenisNotifikasi;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusKondisiSp;
use App\Enums\StatusPengaduan;
use App\Models\Infrastruktur;
use App\Models\Notifikasi;
use App\Models\Pengaduan;
use App\Models\PenilaianSp;
use App\Models\SatuanPermukiman;
use App\Models\User;

class LayananNotifikasi
{
    public static function pengaduanBaru(Pengaduan $pengaduan): void
    {
        Notifikasi::kirim(
            JenisNotifikasi::PengaduanBaru,
            PenerimaNotifikasi::untuk(
                'pengaduan.lihat',
                $pengaduan->satuan_permukiman_id,
                $pengaduan->bidang,
            ),
            [
                'pengaduan_id' => $pengaduan->id_pengaduan,
                'satuan_permukiman_id' => $pengaduan->satuan_permukiman_id,
            ],
            'Pengaduan baru: '.$pengaduan->judul.'.',
        );

        self::pengaduanMendesak($pengaduan);
    }

    public static function pengaduanMendesak(Pengaduan $pengaduan): void
    {
        $subjek = ['pengaduan_id' => $pengaduan->id_pengaduan];
        $aktif = $pengaduan->prioritas === PrioritasPengaduan::Mendesak->value
            && $pengaduan->status !== StatusPengaduan::Selesai;

        if (! $aktif) {
            self::selesaikan(JenisNotifikasi::PengaduanMendesak, $subjek);

            return;
        }

        $penerima = PenerimaNotifikasi::untuk(
            'pengaduan.lihat',
            $pengaduan->satuan_permukiman_id,
            $pengaduan->bidang,
        );
        Notifikasi::query()
            ->where('jenis', JenisNotifikasi::PengaduanMendesak->value)
            ->where($subjek)
            ->whereNull('dibaca_at')
            ->whereNotIn('user_id', $penerima->pluck('id_user'))
            ->update(['dibaca_at' => now()]);
        Notifikasi::kirim(
            JenisNotifikasi::PengaduanMendesak,
            $penerima,
            $subjek + ['satuan_permukiman_id' => $pengaduan->satuan_permukiman_id],
            'Pengaduan mendesak belum selesai: '.$pengaduan->judul.'.',
        );
    }

    public static function infrastrukturRusakBerat(Infrastruktur $infrastruktur): void
    {
        $subjek = ['infrastruktur_id' => $infrastruktur->id_infrastruktur];

        if ($infrastruktur->kondisi !== 'Rusak Berat') {
            self::selesaikan(JenisNotifikasi::InfrastrukturRusakBerat, $subjek);

            return;
        }

        Notifikasi::kirim(
            JenisNotifikasi::InfrastrukturRusakBerat,
            PenerimaNotifikasi::untuk(
                'infrastruktur.lihat',
                $infrastruktur->satuan_permukiman_id,
            ),
            $subjek + ['satuan_permukiman_id' => $infrastruktur->satuan_permukiman_id],
            'Infrastruktur rusak berat: '.$infrastruktur->nama.'.',
        );
    }

    public static function hapusPengaduan(Pengaduan $pengaduan): void
    {
        Notifikasi::query()->where('pengaduan_id', $pengaduan->id_pengaduan)
            ->whereNull('dibaca_at')->update(['dibaca_at' => now()]);
    }

    public static function hapusInfrastruktur(Infrastruktur $infrastruktur): void
    {
        self::selesaikan(JenisNotifikasi::InfrastrukturRusakBerat, [
            'infrastruktur_id' => $infrastruktur->id_infrastruktur,
        ]);
    }

    public static function akun(User $subjek, string $pesan, ?int $pelakuId): void
    {
        self::selesaikan(JenisNotifikasi::AkunPengguna, ['subjek_user_id' => $subjek->id_user]);
        Notifikasi::kirim(
            JenisNotifikasi::AkunPengguna,
            PenerimaNotifikasi::admin($pelakuId),
            ['subjek_user_id' => $subjek->id_user],
            $pesan,
        );
    }

    public static function hitungUlangSp(array $spIds): void
    {
        foreach (array_unique($spIds) as $spId) {
            $hasil = PenilaianKondisiSp::nilai($spId);
            $terakhir = PenilaianSp::withoutGlobalScopes()
                ->where('satuan_permukiman_id', $spId)
                ->latest('id_penilaian_sp')->first();

            if ($terakhir?->status === $hasil['status']) {
                continue;
            }

            PenilaianSp::create([
                'satuan_permukiman_id' => $spId,
                'tanggal_penilaian' => today(),
                'skor' => $hasil['skor'],
                'status' => $hasil['status'],
                'ada_primer_nol' => $hasil['ada_primer_nol'],
                'rincian' => $hasil['rincian'],
                'user_id' => auth()->id(),
                'catatan' => 'Dihitung otomatis setelah perubahan aset.',
            ]);

            $subjek = ['satuan_permukiman_id' => $spId];

            if ($hasil['status'] !== StatusKondisiSp::PerluPenanganan) {
                self::selesaikan(JenisNotifikasi::SpPerluPenanganan, $subjek);

                continue;
            }

            $sp = SatuanPermukiman::find($spId);
            Notifikasi::kirim(
                JenisNotifikasi::SpPerluPenanganan,
                PenerimaNotifikasi::untuk('penilaian_kondisi.lihat', $spId),
                $subjek,
                'SP '.($sp?->nama ?? $spId).' perlu penanganan layanan dasar.',
            );
        }
    }

    private static function selesaikan(JenisNotifikasi $jenis, array $subjek): void
    {
        Notifikasi::query()->where('jenis', $jenis->value)->where($subjek)
            ->whereNull('dibaca_at')->update(['dibaca_at' => now()]);
    }
}
