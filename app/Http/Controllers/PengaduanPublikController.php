<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusPengaduan;
use App\Enums\SumberLaporan;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\Pengaduan;
use App\Support\DummyData;
use App\Support\KontenSistem;
use App\Support\LayananNotifikasi;
use App\Support\NomorPengaduan;
use App\Support\SurelPengaduan;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Kanal pengaduan PUBLIK tanpa login (Task 8.3).
 *
 * Warga transmigran tidak punya akun (`rules.md` 10b.1). Pengiriman dibatasi
 * 3 per jam per IP lewat middleware `throttle:kirim-pengaduan` (10b.1d); IP
 * pelapor disimpan untuk telusur penyalahgunaan (10b.1f). Seluruh pengaduan
 * publik masuk berstatus Menunggu Diterima (10b.1e).
 *
 * Bidang diisi sebagai nilai awal dari kategori (peta `daftar_pilihan.bidang_id`);
 * empat kategori netral tetap kosong sampai petugas menetapkannya.
 *
 * Halaman lacak (`/lacak-pengaduan`) hanya menampilkan status, tanggal, dan
 * catatan penanganan -- tidak pernah data pribadi pelapor (10b.1c).
 */
class PengaduanPublikController extends Controller
{
    use MenyimpanBerkas;

    public function formWarga(): View
    {
        return view('pages.publik.pengaduan', [
            'title' => 'Kirim Pengaduan',
            'daftarSp' => DummyData::satuanPermukiman(),
            'opsiKategoriPengaduan' => DummyData::opsiDaftarPilihan(JenisDaftarPilihan::KategoriPengaduan),
            'portal' => KontenSistem::portal(),
        ]);
    }

    public function kirim(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_pelapor' => ['required', 'string', 'max:255'],
            'kontak_pelapor' => ['required', 'string', 'max:20'],
            'email_pelapor' => ['nullable', 'email:rfc', 'max:100'],
            'satuan_permukiman_id' => ['required', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'kategori' => ValidationRules::daftarPilihan(JenisDaftarPilihan::KategoriPengaduan, wajib: true),
            'tanggal_pengaduan' => ['required', 'date', 'before_or_equal:today'],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:5000'],
            'lintang' => ValidationRules::lintang(),
            'bujur' => ValidationRules::bujur(),
            'dokumen_pendukung' => ValidationRules::foto(),
        ], [
            'nama_pelapor.required' => 'Nama wajib diisi.',
            'kontak_pelapor.required' => 'Nomor HP wajib diisi.',
            'satuan_permukiman_id.required' => 'Tempat tinggal wajib dipilih.',
            'kategori.required' => 'Pilih dulu masalahnya tentang apa.',
            'tanggal_pengaduan.required' => 'Tanggal kejadian wajib diisi.',
            'judul.required' => 'Judul singkat wajib diisi.',
            'deskripsi.required' => 'Ceritakan dulu masalahnya.',
        ] + ValidationRules::pesan());

        $bidangAwal = DummyData::petaBidangKategori()[$data['kategori']] ?? '';

        $pengaduan = DB::transaction(function () use ($request, $data, $bidangAwal) {
            $pengaduan = Pengaduan::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => null,
                'nama_pelapor' => $data['nama_pelapor'],
                'kontak_pelapor' => $data['kontak_pelapor'],
                'email_pelapor' => $data['email_pelapor'] ?? null,
                'sumber_laporan' => SumberLaporan::Publik->value,
                'ip_pelapor' => $request->ip(),
                'satuan_permukiman_id' => $data['satuan_permukiman_id'],
                'nomor_pengaduan' => NomorPengaduan::buat(),
                'tanggal_pengaduan' => $data['tanggal_pengaduan'],
                'kategori' => $data['kategori'],
                'bidang' => $bidangAwal ?: null,
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'],
                'status' => StatusPengaduan::MenungguDiterima->value,
                'prioritas' => PrioritasPengaduan::Sedang->value,
                'lintang' => $data['lintang'] ?? null,
                'bujur' => $data['bujur'] ?? null,
            ]);

            if ($request->hasFile('dokumen_pendukung')) {
                $this->lekatkanBerkas($pengaduan, [$request->file('dokumen_pendukung')], 'pengaduan', 'bukti');
            }

            return $pengaduan;
        });

        LayananNotifikasi::pengaduanBaru($pengaduan);
        $emailTerkirim = SurelPengaduan::kirim($pengaduan, baru: true);

        return back()
            ->with('nomor_pengaduan', $pengaduan->nomor_pengaduan)
            ->with('email_pelapor', $data['email_pelapor'] ?? null)
            ->with('email_terkirim', $emailTerkirim);
    }

    public function lacak(?string $nomorRute = null): View
    {
        $nomor = trim((string) ($nomorRute ?? request('nomor', '')));

        $pengaduan = null;
        $riwayat = [];

        if ($nomor !== '') {
            $model = Pengaduan::withoutGlobalScopes()
                ->with(['penanganan' => fn ($q) => $q->orderBy('id_penanganan_pengaduan'), 'penanganan.berkas'])
                ->where('nomor_pengaduan', Str::upper($nomor))
                ->first();

            if ($model !== null) {
                $pengaduan = [
                    'nomor_pengaduan' => $model->nomor_pengaduan,
                    'judul' => $model->judul,
                    'tanggal_pengaduan' => $model->tanggal_pengaduan?->toDateString(),
                    'status' => $model->status?->value,
                ];

                $riwayat = $model->penanganan->map(fn ($j) => [
                    'status_sesudah' => $j->status_sesudah?->value,
                    'tanggal_penanganan' => $j->tanggal_penanganan?->toDateString(),
                    'catatan' => $j->catatan,
                    'dokumen_tindak_lanjut' => $j->berkas->first()?->nama_file,
                ])->all();
            }
        }

        return view('pages.publik.lacak', [
            'title' => 'Lacak Pengaduan',
            'nomor' => $nomor,
            'pengaduan' => $pengaduan,
            'riwayat' => $riwayat,
        ]);
    }
}
