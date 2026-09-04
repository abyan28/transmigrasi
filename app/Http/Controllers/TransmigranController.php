<?php

namespace App\Http\Controllers;

use App\Enums\Agama;
use App\Enums\AlasanPergantianKK;
use App\Enums\AsalWakilPoktan;
use App\Enums\HubunganAnggotaKeluarga;
use App\Enums\JenisKelamin;
use App\Enums\KegiatanAnggota;
use App\Enums\PendidikanTerakhir;
use App\Enums\StatusAnggotaKeluarga;
use App\Enums\StatusTinggal;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\AnggotaKeluarga;
use App\Models\Berkas;
use App\Models\RiwayatKepalaKeluarga;
use App\Models\Transmigran;
use App\Support\DummyData;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Modul transmigran (Task 5.1 + 5.2).
 *
 * 5.1 memindahkan jalur BACA (daftar + rincian) ke Eloquent. 5.2 memindahkan
 * jalur TULIS: tambah/ubah/hapus KK beserta anggota keluarganya, unggah KTP/KK/
 * SK sebagai tiga peran berkas terpisah, pencatatan peristiwa anggota, dan
 * suksesi kepala keluarga.
 *
 * Yang MASIH `DummyData` pada halaman rincian: rumah (Task 5.3), lahan (Task 6),
 * dan seluruh data poktan (Task 6). Karena poktan belum ber-Eloquent, mutasi
 * jabatan ketua saat suksesi (`nasib_ketua_poktan`) hanya DIVALIDASI di sini;
 * penerapannya menyusul di Task 6.
 */
class TransmigranController extends Controller
{
    use MenyimpanBerkas;

    /** Kolom `anggota_keluarga` yang boleh diisi lewat form KK. */
    private const KOLOM_ANGGOTA = [
        'hubungan', 'nama_lengkap', 'nik', 'jenis_kelamin', 'tempat_lahir',
        'tanggal_lahir', 'agama', 'kegiatan', 'pendidikan_terakhir', 'pekerjaan',
        'pendapatan_per_bulan', 'telepon', 'keterangan',
    ];

    public function index(Request $request): View
    {
        $semua = $this->daftar();

        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterTinggal = $request->query('status_tinggal');

        $baris = array_values(array_filter($semua, function (array $t) use ($cari, $filterSp, $filterTinggal) {
            if ($cari !== '') {
                $cocok = str_contains(mb_strtolower($t['nama_kepala_keluarga']), mb_strtolower($cari))
                    || str_contains($t['nik'], $cari)
                    || str_contains($t['no_kk'], $cari);

                if (! $cocok) {
                    return false;
                }
            }

            if ($filterSp && (string) $t['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }

            return ! ($filterTinggal && $t['status_tinggal'] !== $filterTinggal);
        }));

        return view('pages.transmigran.index', [
            'title' => 'Data Transmigran',
            'semua' => $semua,
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterTinggal' => $filterTinggal,
            'adaFilter' => $cari !== '' || $filterSp || $filterTinggal,
            'daftarSp' => DummyData::satuanPermukiman(),
        ]);
    }

    public function detail(int $id): View
    {
        $transmigran = Transmigran::with(['satuanPermukiman', 'anggotaKeluarga', 'riwayatKepalaKeluarga', 'berkas'])
            ->findOrFail($id);

        $data = $this->baris($transmigran);

        $anggotaPoktan = DummyData::anggotaPoktan();

        // Lahan dan rumah masih data contoh (Task 6 / Task 5.3).
        $lahan = array_values(array_filter(
            DummyData::lahan(),
            fn ($l) => $l['transmigran_id'] === $id,
        ));

        $berkas = $transmigran->berkas->sortBy(fn ($b) => $b->pivot->urutan)->values();

        return view('pages.transmigran.detail', [
            'title' => $data['nama_kepala_keluarga'],
            'data' => $data,

            'rumah' => collect(DummyData::rumah())->firstWhere('transmigran_id', $id),

            'lahan' => $lahan,
            'totalLuas' => array_sum(array_map(
                fn ($l) => (float) ($l['luas_pekarangan'] ?? 0) + (float) ($l['luas_usaha'] ?? 0),
                $lahan,
            )),

            'berkasKtp' => $this->berkasPeran($berkas, 'ktp'),
            'berkasKk' => $this->berkasPeran($berkas, 'kk'),
            'berkasSk' => $this->berkasPeran($berkas, 'sk'),
            'berkasKeluarga' => $berkas->map(fn ($b) => [
                'nama_file' => $b->nama_file,
                'keterangan' => $b->keterangan,
                'peran' => $b->pivot->peran,
                'ukuran' => $b->ukuran,
            ])->all(),

            'anggotaKeluarga' => $transmigran->anggotaKeluarga
                ->sortBy('id_anggota_keluarga')
                ->map(fn (AnggotaKeluarga $a) => $this->barisAnggota($a))
                ->values()
                ->all(),

            'poktanBernaung' => array_values(array_filter(
                $anggotaPoktan,
                fn ($a) => $a['transmigran_id'] === $id && $a['status'] === 'Aktif',
            )),

            'spPoktan' => collect(DummyData::poktan())->pluck('satuan_permukiman', 'id_poktan')->all(),

            'riwayatKk' => $transmigran->riwayatKepalaKeluarga
                ->sortByDesc('tanggal_pergantian')
                ->map(fn (RiwayatKepalaKeluarga $r) => $this->barisRiwayatKk($r))
                ->values()
                ->all(),

            'calonPengganti' => $this->calonPengganti($transmigran),
            'poktanDiketuai' => DummyData::poktanDiketuaiKeluarga($id),

            'keanggotaanIkut' => array_values(array_filter(
                $anggotaPoktan,
                fn ($a) => $a['transmigran_id'] === $id
                    && $a['asal_wakil'] === AsalWakilPoktan::KepalaKeluarga->value
                    && $a['status'] !== 'Sudah Keluar',
            )),

            'inisial' => DummyData::inisial($data['nama_kepala_keluarga']),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $anggota = $data['anggota_keluarga'] ?? [];
        unset($data['anggota_keluarga'], $data['ktp'], $data['kk'], $data['sk'], $data['_anggota_disunting']);

        DB::transaction(function () use ($request, $data, $anggota) {
            // `uuid` adalah pengenal publik URL; model belum punya hook otomatis
            // (menyusul sebagai trait bersama saat Rumah/Lahan/Pengaduan ikut).
            $transmigran = Transmigran::create($data + ['uuid' => (string) Str::uuid()]);

            $this->sinkronAnggota($transmigran, $anggota, hapusYangHilang: false);
            $this->lampirkanDokumen($request, $transmigran);
        });

        return redirect()->route('transmigran.index')->with('sukses', 'Data transmigran tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $transmigran = Transmigran::findOrFail($id);

        $data = $this->validasi($request, $transmigran);
        $anggota = $data['anggota_keluarga'] ?? [];
        $anggotaDisunting = ($data['_anggota_disunting'] ?? null) == '1';
        unset($data['anggota_keluarga'], $data['ktp'], $data['kk'], $data['sk'], $data['_anggota_disunting']);

        DB::transaction(function () use ($request, $transmigran, $data, $anggota, $anggotaDisunting) {
            $transmigran->update($data);

            // Modal "Ubah" per baris pada halaman daftar tidak memuat anggota
            // keluarga sama sekali; hanya form tambah dan form ubah di halaman
            // rincian yang menyunting daftarnya. Tanpa penanda ini, menyimpan
            // dari modal baris akan menghapus seluruh anggota (kiriman kosong).
            if ($anggotaDisunting) {
                $this->sinkronAnggota($transmigran, $anggota, hapusYangHilang: true);
            }

            $this->lampirkanDokumen($request, $transmigran);
        });

        return redirect()->route('transmigran.detail', $id)
            ->with('sukses', 'Perubahan data transmigran tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        Transmigran::findOrFail($id)->delete();

        return redirect()->route('transmigran.index')->with('sukses', 'Data transmigran dihapus.');
    }

    /**
     * Menandai satu peristiwa pada anggota keluarga SELAIN kepala keluarga
     * (`rules.md` 6 poin 9c). Barisnya tidak dihapus, hanya ditandai.
     */
    public function catatPeristiwa(Request $request, int $id, int $anggota): RedirectResponse
    {
        $baris = AnggotaKeluarga::where('id_anggota_keluarga', $anggota)
            ->where('transmigran_id', $id)
            ->where('status', StatusAnggotaKeluarga::Aktif->value)
            ->firstOrFail();

        $data = $request->validate([
            'status' => ['required', Rule::in([
                StatusAnggotaKeluarga::Meninggal->value,
                StatusAnggotaKeluarga::Pindah->value,
            ])],
            'tanggal_peristiwa' => ['required', 'date', 'before_or_equal:today'],
            'keterangan_peristiwa' => ['nullable', 'string', 'max:500'],
        ], [
            'status.required' => 'Pilih peristiwa yang terjadi.',
            'status.in' => 'Peristiwa hanya dapat berupa meninggal atau pindah.',
            'tanggal_peristiwa.required' => 'Tanggal peristiwa wajib diisi.',
            'tanggal_peristiwa.before_or_equal' => 'Tanggal peristiwa tidak boleh di masa depan.',
        ]);

        $baris->update([
            'status' => $data['status'],
            'tanggal_peristiwa' => $data['tanggal_peristiwa'],
            'keterangan_peristiwa' => $data['keterangan_peristiwa'] ?? null,
        ]);

        return redirect()->route('transmigran.detail', ['id' => $id, 'tab' => 'keluarga'])
            ->with('sukses', 'Peristiwa anggota keluarga tercatat.');
    }

    /**
     * Suksesi kepala keluarga (`rules.md` 6 poin 5, 5a-5f).
     *
     * Satu transaksi: rekam `riwayat_kepala_keluarga` (kedua sisi identitas),
     * sunting baris `transmigran` dengan data pengganti, lalu hapus baris
     * `anggota_keluarga` pengganti (ia kini kepala keluarga).
     */
    public function gantiKepalaKeluarga(Request $request, int $id): RedirectResponse
    {
        $transmigran = Transmigran::with('anggotaKeluarga')->findOrFail($id);

        $mengetuaiPoktan = DummyData::poktanDiketuaiKeluarga($id) !== [];

        $data = $request->validate([
            'pengganti_anggota_keluarga_id' => ['required', 'integer'],
            'no_kk_baru' => ['required', 'digits:16'],
            'tanggal_pergantian' => ['required', 'date', 'before_or_equal:today'],
            'alasan' => ['required', Rule::enum(AlasanPergantianKK::class)],
            'keterangan' => ['nullable', 'string', 'max:500'],
            // Jabatan ketua poktan tidak diwariskan: bila keluarga ini menjabat
            // ketua lewat jalur Kepala Keluarga, petugas WAJIB memutuskan.
            'nasib_ketua_poktan' => [
                $mengetuaiPoktan ? 'required' : 'nullable',
                Rule::in(['kosongkan', 'teruskan']),
            ],
        ], [
            'pengganti_anggota_keluarga_id.required' => 'Pilih pengganti dari daftar anggota keluarga.',
            'no_kk_baru.required' => 'Nomor KK baru wajib diisi (isi sama bila belum terbit).',
            'no_kk_baru.digits' => 'Nomor KK harus 16 digit angka.',
            'tanggal_pergantian.required' => 'Tanggal pergantian wajib diisi.',
            'tanggal_pergantian.before_or_equal' => 'Tanggal pergantian tidak boleh di masa depan.',
            'alasan.required' => 'Sebab pergantian wajib dipilih.',
            'nasib_ketua_poktan.required' => 'Tetapkan nasib jabatan ketua kelompok tani.',
        ]);

        $pengganti = $transmigran->anggotaKeluarga
            ->firstWhere('id_anggota_keluarga', (int) $data['pengganti_anggota_keluarga_id']);

        if ($pengganti === null || $pengganti->status !== StatusAnggotaKeluarga::Aktif) {
            return back()->withErrors([
                'pengganti_anggota_keluarga_id' => 'Pengganti harus anggota keluarga yang masih aktif.',
            ]);
        }

        DB::transaction(function () use ($transmigran, $pengganti, $data) {
            RiwayatKepalaKeluarga::create([
                'transmigran_id' => $transmigran->id_transmigran,
                'nik_lama' => $transmigran->nik,
                'nama_lama' => $transmigran->nama_kepala_keluarga,
                'nik_baru' => (string) $pengganti->nik,
                'nama_baru' => $pengganti->nama_lengkap,
                'no_kk_lama' => $transmigran->no_kk,
                'no_kk_baru' => $data['no_kk_baru'],
                'tanggal_pergantian' => $data['tanggal_pergantian'],
                'alasan' => $data['alasan'],
                'hubungan_pengganti' => $pengganti->hubungan->value,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            // Identitas pengganti "naik" menimpa baris transmigran; biodatanya
            // ikut agar profil tidak menampilkan nama baru dengan tanggal lahir
            // kepala keluarga lama. Pekerjaan & pendapatan tetap (kolom NOT NULL,
            // pengganti belum tentu Bekerja) -- petugas melengkapi lewat Ubah.
            $transmigran->update([
                'nama_kepala_keluarga' => $pengganti->nama_lengkap,
                'nik' => (string) $pengganti->nik,
                'no_kk' => $data['no_kk_baru'],
                'jenis_kelamin' => $pengganti->jenis_kelamin?->value,
                'tempat_lahir' => $pengganti->tempat_lahir,
                'tanggal_lahir' => $pengganti->tanggal_lahir?->toDateString(),
                'agama' => $pengganti->agama?->value,
                'pendidikan_terakhir' => $pengganti->pendidikan_terakhir?->value,
                'telepon' => $pengganti->telepon,
            ]);

            $pengganti->delete();

            // Task 6: penerapan `nasib_ketua_poktan` (kosongkan / teruskan) ke
            // tabel `poktan` menyusul saat modul poktan beralih ke Eloquent.
            // Keanggotaan poktan yang melekat pada keluarga otomatis ikut sebab
            // tautannya `transmigran_id`, bukan identitas orang.
        });

        return redirect()->route('transmigran.detail', ['id' => $id, 'tab' => 'riwayat-kk'])
            ->with('sukses', 'Pergantian kepala keluarga tercatat pada riwayat.');
    }

    /**
     * @param  Collection<int, Berkas>  $berkas
     * @return array<int, array<string, mixed>>
     */
    private function berkasPeran(Collection $berkas, string $peran): array
    {
        return $berkas
            ->filter(fn ($b) => $b->pivot->peran === $peran)
            ->map(fn ($b) => [
                'nama_file' => $b->nama_file,
                'keterangan' => $b->keterangan,
                'peran' => $b->pivot->peran,
                'ukuran' => $b->ukuran,
            ])
            ->values()
            ->all();
    }

    private function lampirkanDokumen(Request $request, Transmigran $transmigran): void
    {
        foreach (['ktp', 'kk', 'sk'] as $peran) {
            $this->lekatkanBerkas($transmigran, (array) $request->file($peran, []), 'transmigran', $peran);
        }
    }

    /**
     * Menyamakan daftar anggota keluarga Aktif dengan kiriman form.
     *
     * Baris ber-`id` diperbarui, baris tanpa `id` dibuat. Bila
     * `$hapusYangHilang`, anggota Aktif yang tak lagi ada di kiriman disoftdelete
     * (koreksi salah entri, bukan pencatatan peristiwa).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function sinkronAnggota(Transmigran $transmigran, array $rows, bool $hapusYangHilang): void
    {
        $idDikirim = [];

        foreach ($rows as $row) {
            $atribut = collect($row)->only(self::KOLOM_ANGGOTA)->all();

            if (! empty($row['id'])) {
                $baris = $transmigran->anggotaKeluarga()
                    ->where('id_anggota_keluarga', (int) $row['id'])
                    ->where('status', StatusAnggotaKeluarga::Aktif->value)
                    ->first();

                if ($baris !== null) {
                    $baris->update($atribut);
                    $idDikirim[] = $baris->id_anggota_keluarga;

                    continue;
                }
            }

            $baru = $transmigran->anggotaKeluarga()->create($atribut + ['status' => StatusAnggotaKeluarga::Aktif->value]);
            $idDikirim[] = $baru->id_anggota_keluarga;
        }

        if ($hapusYangHilang) {
            $transmigran->anggotaKeluarga()
                ->where('status', StatusAnggotaKeluarga::Aktif->value)
                ->when($idDikirim !== [], fn ($q) => $q->whereNotIn('id_anggota_keluarga', $idDikirim))
                ->get()
                ->each
                ->delete();
        }
    }

    /**
     * Calon pengganti kepala keluarga: anggota Aktif, pasangan lebih dulu lalu
     * usia menurun (penunjuk, bukan aturan -- `rules.md` 6.5d).
     *
     * @return array<int, array<string, mixed>>
     */
    private function calonPengganti(Transmigran $transmigran): array
    {
        $pasangan = array_map(fn ($h) => $h->value, HubunganAnggotaKeluarga::pasangan());

        return $transmigran->anggotaKeluarga
            ->filter(fn (AnggotaKeluarga $a) => $a->status === StatusAnggotaKeluarga::Aktif)
            ->sort(function (AnggotaKeluarga $x, AnggotaKeluarga $y) use ($pasangan) {
                $px = in_array($x->hubungan->value, $pasangan, true);
                $py = in_array($y->hubungan->value, $pasangan, true);

                if ($px !== $py) {
                    return $px ? -1 : 1;
                }

                return ($this->usia($y->tanggal_lahir) ?? 0) <=> ($this->usia($x->tanggal_lahir) ?? 0);
            })
            ->map(fn (AnggotaKeluarga $a) => [
                'id' => $a->id_anggota_keluarga,
                'nama' => $a->nama_lengkap,
                'nik' => $a->nik,
                'hubungan' => $a->hubungan->value,
                'jenis_kelamin' => $a->jenis_kelamin?->value,
                'usia' => $this->usia($a->tanggal_lahir),
            ])
            ->values()
            ->all();
    }

    private function usia(?Carbon $tanggal): ?int
    {
        return $tanggal?->age;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function daftar(): array
    {
        return Transmigran::query()
            ->with(['satuanPermukiman', 'anggotaKeluarga'])
            ->orderBy('id_transmigran')
            ->get()
            ->map(fn (Transmigran $t) => $this->baris($t))
            ->all();
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::transmigran()`.
     *
     * @return array<string, mixed>
     */
    private function baris(Transmigran $t): array
    {
        $jiwaAnggota = $t->anggotaKeluarga
            ->filter(fn (AnggotaKeluarga $a) => $a->status === StatusAnggotaKeluarga::Aktif)
            ->count();

        return [
            'id_transmigran' => $t->id_transmigran,
            'nik' => $t->nik,
            'tempat_lahir' => $t->tempat_lahir,
            'no_kk' => $t->no_kk,
            'nama_kepala_keluarga' => $t->nama_kepala_keluarga,
            'jenis_kelamin' => $t->jenis_kelamin?->value,
            'agama' => $t->agama?->value,
            'tanggal_lahir' => $t->tanggal_lahir?->toDateString(),
            'pendidikan_terakhir' => $t->pendidikan_terakhir?->value,
            'pekerjaan_kepala_keluarga' => $t->pekerjaan_kepala_keluarga,
            'pendapatan_per_bulan' => $t->pendapatan_per_bulan === null ? null : (int) $t->pendapatan_per_bulan,
            'daerah_asal_kabupaten_id' => $t->daerah_asal_kabupaten_id,
            'tahun_kedatangan' => (int) $t->tahun_kedatangan,
            'status_tinggal' => $t->status_tinggal->value,
            'status_anggota_poktan' => $t->status_anggota_poktan->value,
            'status_sertifikat' => $t->status_sertifikat->value,
            'telepon' => $t->telepon,
            'keterangan' => $t->keterangan,
            'satuan_permukiman' => $t->satuanPermukiman?->nama,
            'satuan_permukiman_id' => $t->satuan_permukiman_id,
            'jumlah_anggota_keluarga' => 1 + $jiwaAnggota,
        ];
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::anggotaKeluarga()`.
     *
     * @return array<string, mixed>
     */
    private function barisAnggota(AnggotaKeluarga $a): array
    {
        return [
            'id_anggota_keluarga' => $a->id_anggota_keluarga,
            'transmigran_id' => $a->transmigran_id,
            'hubungan' => $a->hubungan->value,
            'nama_lengkap' => $a->nama_lengkap,
            'nik' => $a->nik,
            'jenis_kelamin' => $a->jenis_kelamin?->value,
            'tempat_lahir' => $a->tempat_lahir,
            'tanggal_lahir' => $a->tanggal_lahir?->toDateString(),
            'agama' => $a->agama?->value,
            'kegiatan' => $a->kegiatan?->value,
            'pendidikan_terakhir' => $a->pendidikan_terakhir?->value,
            'pekerjaan' => $a->pekerjaan,
            'pendapatan_per_bulan' => $a->pendapatan_per_bulan === null ? null : (int) $a->pendapatan_per_bulan,
            'telepon' => $a->telepon,
            'keterangan' => $a->keterangan,
            'status' => $a->status->value,
            'tanggal_peristiwa' => $a->tanggal_peristiwa?->toDateString(),
            'keterangan_peristiwa' => $a->keterangan_peristiwa,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function barisRiwayatKk(RiwayatKepalaKeluarga $r): array
    {
        return [
            'id_riwayat_kepala_keluarga' => $r->id_riwayat_kepala_keluarga,
            'transmigran_id' => $r->transmigran_id,
            'nik_lama' => $r->nik_lama,
            'nama_lama' => $r->nama_lama,
            'nik_baru' => $r->nik_baru,
            'nama_baru' => $r->nama_baru,
            'no_kk_lama' => $r->no_kk_lama,
            'no_kk_baru' => $r->no_kk_baru,
            'tanggal_pergantian' => $r->tanggal_pergantian?->toDateString(),
            'alasan' => $r->alasan->value,
            'hubungan_pengganti' => $r->hubungan_pengganti->value,
            'keterangan' => $r->keterangan,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Transmigran $transmigran = null): array
    {
        $id = $transmigran?->id_transmigran;

        $data = $request->validate([
            'nama_kepala_keluarga' => ValidationRules::nama(),
            'nik' => ValidationRules::nik(abaikanId: $id),
            'no_kk' => ValidationRules::noKk($id),
            'jenis_kelamin' => ['nullable', Rule::enum(JenisKelamin::class)],
            'agama' => ['nullable', Rule::enum(Agama::class)],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date', 'before_or_equal:today'],
            'pendidikan_terakhir' => ['nullable', Rule::enum(PendidikanTerakhir::class)],
            'pekerjaan_kepala_keluarga' => ['required', 'string', 'max:100'],
            'pendapatan_per_bulan' => ValidationRules::uang(),
            'satuan_permukiman_id' => ['required', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'tahun_kedatangan' => ValidationRules::tahun(wajib: true),
            'daerah_asal_kabupaten_id' => ['nullable', 'integer', Rule::exists('kabupaten', 'id_kabupaten')],
            'status_tinggal' => ['required', Rule::enum(StatusTinggal::class)],
            'telepon' => ValidationRules::telepon(),
            'keterangan' => ['nullable', 'string', 'max:1000'],

            '_anggota_disunting' => ['nullable', 'string'],
            'anggota_keluarga' => ['nullable', 'array'],
            'anggota_keluarga.*.id' => ['nullable', 'integer'],
            'anggota_keluarga.*.hubungan' => ['required', Rule::enum(HubunganAnggotaKeluarga::class)],
            'anggota_keluarga.*.nama_lengkap' => ['required', 'string', 'min:3', 'max:255', "regex:/^[\pL\s.']+$/u"],
            'anggota_keluarga.*.nik' => ['nullable', 'digits:16', 'distinct:ignore_case'],
            'anggota_keluarga.*.jenis_kelamin' => ['nullable', Rule::enum(JenisKelamin::class)],
            'anggota_keluarga.*.tempat_lahir' => ['nullable', 'string', 'max:100'],
            'anggota_keluarga.*.tanggal_lahir' => ['nullable', 'date', 'before_or_equal:today'],
            'anggota_keluarga.*.agama' => ['nullable', Rule::enum(Agama::class)],
            'anggota_keluarga.*.kegiatan' => ['nullable', Rule::enum(KegiatanAnggota::class)],
            'anggota_keluarga.*.pendidikan_terakhir' => ['nullable', Rule::enum(PendidikanTerakhir::class)],
            'anggota_keluarga.*.pekerjaan' => ['nullable', 'string', 'max:100'],
            'anggota_keluarga.*.pendapatan_per_bulan' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'anggota_keluarga.*.telepon' => ['nullable', 'string', 'regex:/^(08|\+62)[0-9]{8,13}$/'],
            'anggota_keluarga.*.keterangan' => ['nullable', 'string', 'max:1000'],

            'ktp' => ['nullable', 'array'],
            'ktp.*' => ValidationRules::dokumen(),
            'kk' => ['nullable', 'array'],
            'kk.*' => ValidationRules::dokumen(),
            'sk' => ['nullable', 'array'],
            'sk.*' => ValidationRules::dokumen(),
        ], [
            'nama_kepala_keluarga.required' => 'Nama kepala keluarga wajib diisi.',
            'nama_kepala_keluarga.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, dan tanda petik.',
            'pekerjaan_kepala_keluarga.required' => 'Pekerjaan kepala keluarga wajib diisi.',
            'satuan_permukiman_id.required' => 'Satuan permukiman wajib dipilih.',
            'tahun_kedatangan.required' => 'Tahun kedatangan wajib diisi.',
            'status_tinggal.required' => 'Status tinggal wajib dipilih.',
            'anggota_keluarga.*.hubungan.required' => 'Hubungan anggota keluarga wajib dipilih.',
            'anggota_keluarga.*.nama_lengkap.required' => 'Nama anggota keluarga wajib diisi.',
            'anggota_keluarga.*.nama_lengkap.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, dan tanda petik.',
            'anggota_keluarga.*.nik.digits' => 'NIK anggota keluarga harus 16 digit angka.',
            'anggota_keluarga.*.nik.distinct' => 'Ada NIK anggota keluarga yang sama diisi dua kali.',
        ] + ValidationRules::pesan());

        return $data;
    }
}
