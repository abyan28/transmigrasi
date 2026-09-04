<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusPengaduan;
use App\Enums\SumberLaporan;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\PenangananPengaduan;
use App\Models\Pengaduan;
use App\Support\DummyData;
use App\Support\NomorPengaduan;
use App\Support\Paginasi;
use App\Support\RekapPengaduan;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Pengaduan warga -- sisi internal petugas (Task 8.2-8.6).
 *
 * Alur status WAJIB berurutan Menunggu Diterima -> Diterima -> Diproses ->
 * Selesai, maju SATU langkah, tak boleh melompat/mundur (`rules.md` 10b.4);
 * ditegakkan lewat `StatusPengaduan::bolehPindahKe()`. Tiap perpindahan
 * menyimpan baris `penanganan_pengaduan` berisi petugas, tanggal, catatan,
 * dan dokumen tindak lanjut (10b.5).
 *
 * Bidang diturunkan dari kategori sebagai nilai AWAL (`DummyData::petaBidangKategori`,
 * data pada `daftar_pilihan.bidang_id` -- bukan `match`), selalu dapat ditimpa
 * petugas (10b.7c), dan WAJIB terisi sebelum status maju ke Diproses (10b.7b).
 * Penyaringan ke dinas ditangani global scope `CakupanDataSp`.
 */
class PengaduanController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterStatus = $request->query('status');
        $filterKategori = $request->query('kategori');
        $filterPrioritas = $request->query('prioritas');
        $filterBidang = $request->query('bidang');
        $perHalaman = Paginasi::perHalaman($request);

        $baris = Pengaduan::query()
            ->with(['satuanPermukiman', 'berkas'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('judul', 'like', "%{$cari}%")
                ->orWhere('nomor_pengaduan', 'like', "%{$cari}%")
                ->orWhere('nama_pelapor', 'like', "%{$cari}%")))
            ->when($filterSp, fn ($q) => $q->where('satuan_permukiman_id', $filterSp))
            ->when($filterStatus, fn ($q) => $q->where('status', $filterStatus))
            ->when($filterKategori, fn ($q) => $q->where('kategori', $filterKategori))
            ->when($filterPrioritas, fn ($q) => $q->where('prioritas', $filterPrioritas))
            ->when($filterBidang === 'belum', fn ($q) => $q->whereNull('bidang'))
            ->when($filterBidang && $filterBidang !== 'belum', fn ($q) => $q->where('bidang', $filterBidang))
            // Belum selesai didahulukan, lalu menurut kemendesakan.
            //
            // SEBELUMNYA memakai FIELD(), fungsi MariaDB yang tak ada di
            // SQLite (Fase 1, 2026-09-05 -- ternyata `tests/Feature/HalamanTest.php`
            // memang memuat uji `/pengaduan` di SQLite, bukan cuma tests/Database
            // seperti catatan lama di sini mengira). `CASE WHEN` portabel di
            // kedua mesin, jadi dipakai untuk KEDUA pengurutan sekaligus.
            ->orderByRaw('CASE WHEN status = ? THEN 1 ELSE 0 END', [StatusPengaduan::Selesai->value])
            ->orderByRaw("CASE prioritas
                WHEN 'Mendesak' THEN 1 WHEN 'Tinggi' THEN 2
                WHEN 'Sedang' THEN 3 WHEN 'Rendah' THEN 4 ELSE 5 END")
            ->paginate($perHalaman)
            ->withQueryString();

        $baris->through(fn (Pengaduan $p) => $this->baris($p));

        return view('pages.pengaduan.index', [
            'title' => 'Pengaduan',
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterStatus' => $filterStatus,
            'filterKategori' => $filterKategori,
            'filterPrioritas' => $filterPrioritas,
            'filterBidang' => $filterBidang,
            'adaFilter' => $cari !== '' || $filterSp || $filterStatus || $filterKategori || $filterPrioritas || $filterBidang,
            // Kartu ringkasan: agregat kawasan-penuh (atau cakupan operator),
            // bukan halaman ini saja -- sama seperti $baris, CakupanDataSp
            // berlaku otomatis pada tiap query di bawah.
            'total' => Pengaduan::query()->count(),
            'belumBerbidang' => Pengaduan::query()->whereNull('bidang')->count(),
            'belumSelesai' => Pengaduan::query()->whereNot('status', StatusPengaduan::Selesai->value)->count(),
            'menungguDiterima' => Pengaduan::query()->where('status', StatusPengaduan::MenungguDiterima->value)->count(),
            'mendesak' => Pengaduan::query()->where('prioritas', PrioritasPengaduan::Mendesak->value)
                ->whereNot('status', StatusPengaduan::Selesai->value)->count(),
            'daftarSp' => DummyData::satuanPermukiman(),
            'opsiFilterBidang' => DummyData::opsiFilterDaftarPilihan(JenisDaftarPilihan::BidangPengaduan),
            'opsiFilterKategori' => DummyData::opsiFilterDaftarPilihan(JenisDaftarPilihan::KategoriPengaduan),
            'opsiFilterPrioritas' => DummyData::opsiFilterDaftarPilihan(JenisDaftarPilihan::PrioritasPengaduan),
        ]);
    }

    public function detail(int $id): View
    {
        $pengaduan = Pengaduan::with([
            'satuanPermukiman', 'berkas',
            'penanganan.petugas', 'penanganan.berkas',
        ])->findOrFail($id);

        return view('pages.pengaduan.detail', [
            'title' => $pengaduan->nomor_pengaduan,
            'data' => $this->baris($pengaduan),
            'riwayat' => $pengaduan->penanganan
                ->sortBy('id_penanganan_pengaduan')
                ->map(fn ($j) => $this->barisPenanganan($j))
                ->values()
                ->all(),
            'opsiBidang' => DummyData::opsiDaftarPilihan(JenisDaftarPilihan::BidangPengaduan),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        // Bidang terisi dari kategori sebagai NILAI AWAL bila petugas tak
        // menetapkannya sendiri (`rules.md` 10b.7a). Empat kategori netral
        // tetap kosong sampai ditinjau.
        $data['bidang'] = ($data['bidang'] ?? null) ?: (DummyData::petaBidangKategori()[$data['kategori']] ?? null) ?: null;

        DB::transaction(function () use ($request, $data) {
            $pengaduan = Pengaduan::create($this->kolom($data) + [
                'uuid' => (string) Str::uuid(),
                'user_id' => Auth::id(),
                'sumber_laporan' => SumberLaporan::Petugas->value,
                'nomor_pengaduan' => NomorPengaduan::buat(),
                'status' => StatusPengaduan::MenungguDiterima->value,
            ]);

            if ($request->hasFile('bukti')) {
                $this->lekatkanBerkas($pengaduan, (array) $request->file('bukti'), 'pengaduan', 'bukti');
            }
        });

        return redirect()->route('pengaduan.index')->with('sukses', 'Pengaduan tercatat dan menunggu diterima petugas.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $pengaduan = Pengaduan::findOrFail($id);
        $data = $this->validasi($request);

        DB::transaction(function () use ($request, $pengaduan, $data) {
            $pengaduan->update($this->kolom($data));

            if ($request->hasFile('bukti')) {
                $this->lekatkanBerkas($pengaduan, (array) $request->file('bukti'), 'pengaduan', 'bukti');
            }
        });

        return redirect()->route('pengaduan.detail', $id)->with('sukses', 'Perubahan data pengaduan tersimpan.');
    }

    /**
     * Memajukan status penanganan satu langkah (Task 8.4).
     */
    public function tangani(Request $request, int $id): RedirectResponse
    {
        $pengaduan = Pengaduan::findOrFail($id);

        $data = $request->validate([
            'status_sesudah' => ['required', Rule::enum(StatusPengaduan::class)],
            'tanggal_penanganan' => ['required', 'date', 'before_or_equal:today'],
            'catatan' => ['required', 'string', 'max:500'],
            'bidang' => ValidationRules::daftarPilihan(JenisDaftarPilihan::BidangPengaduan),
            'dokumen_tindak_lanjut' => ValidationRules::dokumen(),
        ], [
            'status_sesudah.required' => 'Status tujuan wajib ada.',
            'tanggal_penanganan.required' => 'Tanggal penanganan wajib diisi.',
            'catatan.required' => 'Catatan tindakan wajib diisi.',
        ] + ValidationRules::pesan());

        $sekarang = $pengaduan->status;
        $tujuan = StatusPengaduan::from($data['status_sesudah']);

        if (! $sekarang->bolehPindahKe($tujuan)) {
            throw ValidationException::withMessages([
                'status_sesudah' => 'Status hanya dapat maju satu langkah, dari '.$sekarang->label().' ke '.($sekarang->berikutnya()?->label() ?? '-').'.',
            ]);
        }

        $bidangBaru = $data['bidang'] ?? $pengaduan->bidang;

        // Rule 10b.7b: bidang wajib terisi sebelum status maju ke Diproses.
        if ($tujuan === StatusPengaduan::Diproses && empty($bidangBaru)) {
            throw ValidationException::withMessages([
                'bidang' => 'Bidang penanganan wajib ditetapkan sebelum pengaduan berstatus Diproses.',
            ]);
        }

        DB::transaction(function () use ($request, $pengaduan, $sekarang, $tujuan, $data, $bidangBaru) {
            $penanganan = $pengaduan->penanganan()->create([
                'user_id' => Auth::id(),
                'status_sebelum' => $sekarang->value,
                'status_sesudah' => $tujuan->value,
                'tanggal_penanganan' => $data['tanggal_penanganan'],
                'catatan' => $data['catatan'],
            ]);

            if ($request->hasFile('dokumen_tindak_lanjut')) {
                $this->lekatkanBerkas($penanganan, [$request->file('dokumen_tindak_lanjut')], 'pengaduan', 'tindak_lanjut');
            }

            $pengaduan->forceFill([
                'status' => $tujuan->value,
                'bidang' => $bidangBaru ?: null,
            ])->save();
        });

        return redirect()->route('pengaduan.detail', ['id' => $id, 'tab' => 'riwayat'])
            ->with('sukses', 'Penanganan tercatat dan status pengaduan diperbarui.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $pengaduan = Pengaduan::findOrFail($id);
        $pengaduan->berkas()->detach();
        $pengaduan->delete();

        return redirect()->route('pengaduan.index')->with('sukses', 'Pengaduan dihapus.');
    }

    /**
     * Rekap pengaduan per kategori/status/sp/prioritas/bidang (Task 8.6).
     */
    public function rekap(?string $kelompok = null): View
    {
        $kelompok = $kelompok ?? request('kelompok', 'kategori');

        return view('pages.pengaduan.rekap', [
            'title' => 'Rekap Pengaduan',
            'kelompok' => $kelompok,
            'rekap' => RekapPengaduan::rekap($kelompok),
        ]);
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::pengaduan()`.
     *
     * @return array<string, mixed>
     */
    private function baris(Pengaduan $p): array
    {
        return [
            'id_pengaduan' => $p->id_pengaduan,
            'nomor_pengaduan' => $p->nomor_pengaduan,
            'tanggal_pengaduan' => $p->tanggal_pengaduan?->toDateString(),
            'nama_pelapor' => $p->nama_pelapor,
            'kontak_pelapor' => $p->kontak_pelapor,
            'email_pelapor' => $p->email_pelapor,
            'sumber_laporan' => $p->sumber_laporan?->value,
            'satuan_permukiman' => $p->satuanPermukiman?->nama,
            'satuan_permukiman_id' => $p->satuan_permukiman_id,
            'kategori' => $p->kategori,
            'bidang' => $p->bidang,
            'judul' => $p->judul,
            'deskripsi' => $p->deskripsi,
            'status' => $p->status?->value,
            'prioritas' => $p->prioritas,
            'lintang' => $p->lintang === null ? null : (float) $p->lintang,
            'bujur' => $p->bujur === null ? null : (float) $p->bujur,
            'dokumen_pendukung' => $p->berkas->firstWhere('pivot.peran', 'bukti')?->nama_file
                ?? $p->berkas->first()?->nama_file,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function barisPenanganan(PenangananPengaduan $j): array
    {
        return [
            'tanggal_penanganan' => $j->tanggal_penanganan?->toDateString(),
            'petugas' => $j->petugas?->nama,
            'status_sebelum' => $j->status_sebelum?->value,
            'status_sesudah' => $j->status_sesudah?->value,
            'catatan' => $j->catatan,
            'dokumen_tindak_lanjut' => $j->berkas->first()?->nama_file,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function kolom(array $data): array
    {
        return [
            'nama_pelapor' => $data['nama_pelapor'],
            'kontak_pelapor' => $data['kontak_pelapor'],
            'satuan_permukiman_id' => $data['satuan_permukiman_id'],
            'tanggal_pengaduan' => $data['tanggal_pengaduan'],
            'kategori' => $data['kategori'],
            'bidang' => ($data['bidang'] ?? null) ?: null,
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'],
            'prioritas' => $data['prioritas'],
            'lintang' => $data['lintang'] ?? null,
            'bujur' => $data['bujur'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama_pelapor' => ['required', 'string', 'max:255'],
            'kontak_pelapor' => ['required', 'string', 'max:20'],
            'satuan_permukiman_id' => ['required', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'tanggal_pengaduan' => ['required', 'date', 'before_or_equal:today'],
            'kategori' => ValidationRules::daftarPilihan(JenisDaftarPilihan::KategoriPengaduan, wajib: true),
            'bidang' => ValidationRules::daftarPilihan(JenisDaftarPilihan::BidangPengaduan),
            'prioritas' => ValidationRules::daftarPilihan(JenisDaftarPilihan::PrioritasPengaduan, wajib: true),
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:5000'],
            'lintang' => ValidationRules::lintang(),
            'bujur' => ValidationRules::bujur(),
            'bukti' => ['nullable', 'array'],
            'bukti.*' => ValidationRules::dokumen(),
        ], [
            'nama_pelapor.required' => 'Nama pelapor wajib diisi.',
            'kontak_pelapor.required' => 'Nomor kontak pelapor wajib diisi.',
            'satuan_permukiman_id.required' => 'Satuan permukiman wajib dipilih.',
            'kategori.required' => 'Kategori pengaduan wajib dipilih.',
            'prioritas.required' => 'Prioritas wajib dipilih.',
            'judul.required' => 'Perihal pengaduan wajib diisi.',
            'deskripsi.required' => 'Uraian masalah wajib diisi.',
        ] + ValidationRules::pesan());
    }
}
