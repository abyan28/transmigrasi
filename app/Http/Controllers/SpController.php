<?php

namespace App\Http\Controllers;

use App\Enums\BentukWilayah;
use App\Enums\PolaPermukiman;
use App\Enums\StatusTinggal;
use App\Enums\TingkatKesuburanTanah;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\Desa;
use App\Models\FasilitasSp;
use App\Models\HasilPanen;
use App\Models\Infrastruktur;
use App\Models\InventarisSp;
use App\Models\KawasanTransmigrasi;
use App\Models\Lahan;
use App\Models\Pengaduan;
use App\Models\Poktan;
use App\Models\Rumah;
use App\Models\RuteAksesibilitasSp;
use App\Models\SatuanPermukiman;
use App\Models\Scopes\CakupanDataSp;
use App\Models\Transmigran;
use App\Support\Paginasi;
use App\Support\PenilaianKondisiSp;
use App\Support\PenyajianPanen;
use App\Support\PenyajianPoktan;
use App\Support\RekapDashboard;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Satuan permukiman -- INDUK inventaris, fasilitas, dan infrastruktur SP
 * (Task 4.2), sehingga dikerjakan sebelum ketiganya.
 *
 * `jumlah_kk_terisi` BUKAN kolom: ia diturunkan dari cacah transmigran aktif.
 */
class SpController extends Controller
{
    use MenyimpanBerkas;

    /**
     * Pasangan min/maks keadaan wilayah: awalan => [akhiran satuan, batas atas].
     *
     * Ditulis sekali di sini supaya aturan dan pesannya tidak dapat berselisih:
     * menambah pasangan baru cukup menyentuh satu tempat.
     *
     * @var array<string, array{0: string, 1: int|float}>
     */
    private const RENTANG = [
        'kemiringan' => ['_persen', 100],
        'curah_hujan_bulan' => ['_mm', 10000],
        'suhu' => ['_c', 60],
        'angin' => ['_knot', 200],
        'penyinaran' => ['_persen', 100],
    ];

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterKecamatan = $request->query('kecamatan');
        $perHalaman = Paginasi::perHalaman($request);
        $terisi = Transmigran::query()
            ->where('status_tinggal', StatusTinggal::Aktif->value)
            ->selectRaw('satuan_permukiman_id, count(*) as jumlah')
            ->groupBy('satuan_permukiman_id')
            ->pluck('jumlah', 'satuan_permukiman_id');

        $baris = SatuanPermukiman::query()
            ->terlihatOlehPengguna()
            ->with(['desa.kecamatan', 'kawasan', 'berkas'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nama', 'like', "%{$cari}%")
                ->orWhereHas('desa', fn ($d) => $d->where('nama', 'like', "%{$cari}%"))))
            ->when($filterKecamatan, fn ($q) => $q->whereHas('desa.kecamatan', fn ($k) => $k->where('nama', $filterKecamatan)))
            ->orderBy('kode_sp')
            ->paginate($perHalaman)
            ->withQueryString();

        $baris->through(fn (SatuanPermukiman $sp) => $this->barisSp($sp, (int) ($terisi[$sp->id_satuan_permukiman] ?? 0)));

        return view('pages.sp.index', [
            'title' => 'Satuan Permukiman',
            'baris' => $baris,
            'kondisi' => collect(PenilaianKondisiSp::nilaiSeluruhSp())->keyBy('satuan_permukiman_id'),
            'cari' => $cari,
            'filterKecamatan' => $filterKecamatan,
            'adaFilter' => $cari !== '' || $filterKecamatan,
            'jumlahSp' => SatuanPermukiman::query()->terlihatOlehPengguna()->count(),
            'daftarKecamatan' => SatuanPermukiman::query()
                ->terlihatOlehPengguna()
                ->join('desa', 'desa.id_desa', '=', 'satuan_permukiman.desa_id')
                ->join('kecamatan', 'kecamatan.id_kecamatan', '=', 'desa.kecamatan_id')
                ->distinct()->orderBy('kecamatan.nama')->pluck('kecamatan.nama')->all(),
            'totalLuas' => (float) SatuanPermukiman::query()->terlihatOlehPengguna()->sum('luas_lahan'),
            'totalRencana' => (int) SatuanPermukiman::query()->terlihatOlehPengguna()->sum('jumlah_kk_rencana'),
            'totalTerisi' => (int) $terisi->sum(),
        ]);
    }

    public function detail(int $sp): View
    {
        $model = SatuanPermukiman::query()
            ->terlihatOlehPengguna()
            ->with(['desa.kecamatan', 'kawasan', 'berkas'])
            ->findOrFail($sp);
        $id = (int) $model->id_satuan_permukiman;
        $transmigran = Transmigran::query()
            ->with('anggotaKeluarga')
            ->where('satuan_permukiman_id', $id)
            ->orderBy('id_transmigran')
            ->get();
        $rumah = Rumah::query()
            ->with(['penghuni', 'berkas'])
            ->where('satuan_permukiman_id', $id)
            ->orderBy('id_rumah')
            ->get();
        $lahan = Lahan::query()
            ->with(['transmigran.berkas'])
            ->where('satuan_permukiman_id', $id)
            ->orderBy('id_lahan')
            ->get();
        $poktan = Poktan::query()
            ->with(['satuanPermukiman', 'ketuaTransmigran', 'ketuaAnggotaKeluarga', 'berkas', 'anggota'])
            ->where('satuan_permukiman_id', $id)
            ->orderBy('id_poktan')
            ->get();
        $panen = HasilPanen::query()
            ->where('status', 'Aktif')
            ->with(['satuan', 'penanaman.poktan.satuanPermukiman', 'penanaman.komoditas', 'berkas'])
            ->whereHas('penanaman.poktan', fn ($q) => $q->where('satuan_permukiman_id', $id))
            ->orderByDesc('periode_panen')
            ->get();
        $pengaduan = Pengaduan::query()
            ->with(['satuanPermukiman', 'berkas'])
            ->where('satuan_permukiman_id', $id)
            ->orderByDesc('tanggal_pengaduan')
            ->get();
        $infrastruktur = Infrastruktur::query()
            ->with(['satuanPermukiman', 'cakupan', 'berkas'])
            ->where(fn ($q) => $q->where('satuan_permukiman_id', $id)
                ->orWhereHas('cakupan', fn ($cakupan) => $cakupan->where('satuan_permukiman.id_satuan_permukiman', $id)))
            ->orderBy('id_infrastruktur')
            ->get();
        $fasilitas = FasilitasSp::query()
            ->with(['satuanPermukiman', 'cakupan', 'berkas'])
            ->where(fn ($q) => $q->where('satuan_permukiman_id', $id)
                ->orWhereHas('cakupan', fn ($cakupan) => $cakupan->where('satuan_permukiman.id_satuan_permukiman', $id)))
            ->orderBy('id_fasilitas_sp')
            ->get();
        $inventaris = InventarisSp::query()
            ->with(['satuanPermukiman', 'berkas'])
            ->where('satuan_permukiman_id', $id)
            ->orderBy('id_inventaris_sp')
            ->get();
        $rute = RuteAksesibilitasSp::query()
            ->where('satuan_permukiman_id', $id)
            ->orderBy('id_rute_aksesibilitas_sp')
            ->get();
        $jumlahKk = $transmigran->where('status_tinggal', StatusTinggal::Aktif)->count();
        $ringkasan = RekapDashboard::ringkasan($id);
        $deret = RekapDashboard::deret($id);

        return view('pages.sp.detail', [
            'title' => $model->nama,
            'sp' => $this->barisSp($model, $jumlahKk),
            'rekap' => [
                'jumlah_kk' => $jumlahKk,
                'rumah_terhuni' => $ringkasan['rumah_terhuni'],
                'luas_lahan' => $ringkasan['luas_lahan_total'],
                'volume_panen' => $ringkasan['volume_panen_ton'],
                'pengaduan_terbuka' => $ringkasan['pengaduan_terbuka'],
            ],
            'deretSp' => [
                'tahun' => $deret['tahun'],
                'jumlah_kk' => $deret['jumlah_kk'],
                'volume_panen' => $deret['volume_panen'],
            ],
            'penilaian' => PenilaianKondisiSp::nilai(
                $id,
                $infrastruktur->map(fn (Infrastruktur $i) => $this->barisInfrastruktur($i))->all(),
                $fasilitas->map(fn (FasilitasSp $f) => $this->barisFasilitas($f))->all(),
            ),
            'transmigran' => $transmigran->map(fn (Transmigran $t) => $this->barisTransmigran($t))->all(),
            'rumah' => $rumah->map(fn (Rumah $r) => $this->barisRumah($r))->all(),
            'lahan' => $lahan->map(fn (Lahan $l) => $this->barisLahan($l))->all(),
            'poktan' => $poktan->map(fn (Poktan $p) => PenyajianPoktan::baris($p))->all(),
            'panen' => $panen->map(fn (HasilPanen $h) => PenyajianPanen::barisPanen($h))->all(),
            'pengaduan' => $pengaduan->map(fn (Pengaduan $p) => $this->barisPengaduan($p))->all(),
            'infrastruktur' => $infrastruktur->map(fn (Infrastruktur $i) => $this->barisInfrastruktur($i))->all(),
            'fasilitas' => $fasilitas->map(fn (FasilitasSp $f) => $this->barisFasilitas($f))->all(),
            'inventaris' => $inventaris->map(fn (InventarisSp $i) => $this->barisInventaris($i))->all(),
            'ruteAksesibilitas' => $rute->map(fn (RuteAksesibilitasSp $r) => [
                'id_rute_aksesibilitas_sp' => $r->id_rute_aksesibilitas_sp,
                'satuan_permukiman_id' => $r->satuan_permukiman_id,
                'rute' => $r->rute,
                'jarak_km' => $r->jarak_km === null ? null : (float) $r->jarak_km,
                'sarana_angkutan' => $r->sarana_angkutan,
                'tempat_pemberangkatan' => $r->tempat_pemberangkatan,
                'kondisi_jalan' => $r->kondisi_jalan,
                'waktu_tempuh' => $r->waktu_tempuh,
                'ongkos_rp' => $r->ongkos_rp === null ? null : (float) $r->ongkos_rp,
                'keterangan' => $r->keterangan,
            ])->all(),
            'persenHuni' => $jumlahKk > 0 ? round($ringkasan['rumah_terhuni'] / $jumlahKk * 100) : 0,
            'persenIsi' => $model->jumlah_kk_rencana > 0 ? round($jumlahKk / $model->jumlah_kk_rencana * 100) : 0,
            'dataGrafik' => [
                'tahun' => $deret['tahun'],
                'kk' => $deret['jumlah_kk'],
                'panen' => $deret['volume_panen'],
            ],
        ]);
    }

    private function barisSp(SatuanPermukiman $sp, int $terisi): array
    {
        return array_merge($sp->attributesToArray(), [
            'id_satuan_permukiman' => $sp->id_satuan_permukiman,
            'desa' => $sp->desa?->nama,
            'kecamatan' => $sp->desa?->kecamatan?->nama,
            'kawasan' => $sp->kawasan?->nama,
            'luas_lahan' => (float) ($sp->luas_lahan ?? 0),
            'jumlah_kk_rencana' => (int) ($sp->jumlah_kk_rencana ?? 0),
            'jumlah_kk_terisi' => $terisi,
            'lintang' => $sp->lintang === null ? null : (float) $sp->lintang,
            'bujur' => $sp->bujur === null ? null : (float) $sp->bujur,
            'tanggal_sk_pencadangan' => $sp->tanggal_sk_pencadangan?->format('Y-m-d'),
            'dokumen_pendukung' => $sp->berkas?->nama_file,
        ]);
    }

    private function barisTransmigran(Transmigran $transmigran): array
    {
        return [
            'id_transmigran' => $transmigran->id_transmigran,
            'nik' => $transmigran->nik,
            'nama_kepala_keluarga' => $transmigran->nama_kepala_keluarga,
            'pekerjaan_kepala_keluarga' => $transmigran->pekerjaan_kepala_keluarga,
        ];
    }

    private function barisRumah(Rumah $rumah): array
    {
        return [
            'id_rumah' => $rumah->id_rumah,
            'no_rumah' => $rumah->no_rumah,
            'penghuni' => $rumah->penghuni?->nama_kepala_keluarga,
            'kondisi' => $rumah->kondisi,
            'status_hunian' => $rumah->status_hunian,
            'alasan_tidak_dihuni' => $rumah->alasan_tidak_dihuni,
        ];
    }

    private function barisLahan(Lahan $lahan): array
    {
        return [
            'id_lahan' => $lahan->id_lahan,
            'kode_lahan' => $lahan->kode_lahan,
            'pemilik' => $lahan->transmigran?->nama_kepala_keluarga,
            'luas_pekarangan' => $lahan->luas_pekarangan === null ? null : (float) $lahan->luas_pekarangan,
            'luas_usaha' => $lahan->luas_usaha === null ? null : (float) $lahan->luas_usaha,
        ];
    }

    private function barisPengaduan(Pengaduan $pengaduan): array
    {
        return [
            'id_pengaduan' => $pengaduan->id_pengaduan,
            'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
            'judul' => $pengaduan->judul,
            'prioritas' => $pengaduan->prioritas,
            'status' => $pengaduan->status->value,
        ];
    }

    private function barisInfrastruktur(Infrastruktur $infrastruktur): array
    {
        return [
            'id_infrastruktur' => $infrastruktur->id_infrastruktur,
            'satuan_permukiman_id' => $infrastruktur->satuan_permukiman_id,
            'satuan_permukiman_ids' => $infrastruktur->cakupan->pluck('id_satuan_permukiman')->all()
                ?: [$infrastruktur->satuan_permukiman_id],
            'nama' => $infrastruktur->nama,
            'jenis' => $infrastruktur->jenis,
            'tahun_perolehan' => $infrastruktur->tahun_perolehan,
            'kondisi' => $infrastruktur->kondisi,
        ];
    }

    private function barisFasilitas(FasilitasSp $fasilitas): array
    {
        return [
            'id_fasilitas_sp' => $fasilitas->id_fasilitas_sp,
            'satuan_permukiman_id' => $fasilitas->satuan_permukiman_id,
            'satuan_permukiman_ids' => $fasilitas->cakupan->pluck('id_satuan_permukiman')->all()
                ?: [$fasilitas->satuan_permukiman_id],
            'nama_fasilitas' => $fasilitas->nama_fasilitas,
            'jenis_fasilitas' => $fasilitas->jenis_fasilitas?->value ?? $fasilitas->jenis_fasilitas,
            'kondisi' => $fasilitas->kondisi,
            'rincian_kondisi' => $fasilitas->rincian_kondisi,
        ];
    }

    private function barisInventaris(InventarisSp $inventaris): array
    {
        return [
            'id_inventaris_sp' => $inventaris->id_inventaris_sp,
            'nama_barang' => $inventaris->nama_barang,
            'jumlah' => $inventaris->jumlah,
            'kondisi' => $inventaris->kondisi,
            'status_penyerahan' => $inventaris->status_penyerahan,
        ];
    }

    public function simpan(Request $request): RedirectResponse
    {
        [$data, $rute] = $this->pisahkan($this->validasi($request));

        DB::transaction(function () use ($request, $data, $rute) {
            $sp = SatuanPermukiman::create($data);
            $sp->ruteAksesibilitas()->createMany($rute);
            $this->lampirkanDokumen($request, $sp);
        });

        return redirect()->route('sp.index')->with('sukses', 'Data satuan permukiman tersimpan.');
    }

    public function perbarui(Request $request, int $sp): RedirectResponse
    {
        $model = SatuanPermukiman::findOrFail($sp);
        CakupanDataSp::pastikanDapatDitulis($model);
        [$data, $rute, $ruteDisunting] = $this->pisahkan($this->validasi($request, $model));

        DB::transaction(function () use ($request, $model, $data, $rute, $ruteDisunting) {
            $model->update($data);

            if ($ruteDisunting) {
                $model->ruteAksesibilitas()->delete();
                $model->ruteAksesibilitas()->createMany($rute);
            }

            $this->lampirkanDokumen($request, $model);
        });

        return back()->with('sukses', 'Perubahan data satuan permukiman tersimpan.');
    }

    /**
     * Penghapusan DITOLAK bila SP masih menaungi data turunan.
     *
     * Diperiksa satu per satu supaya alasannya menyebut MODUL MANA yang
     * menahan; galat FK mentah hanya menyebut nama tabel.
     */
    public function hapus(int $id): RedirectResponse
    {
        $sp = SatuanPermukiman::findOrFail($id);
        CakupanDataSp::pastikanDapatDitulis($sp);

        $turunan = [
            'transmigran' => 'keluarga transmigran',
            'rumah' => 'rumah',
            'inventaris' => 'inventaris',
            'fasilitas' => 'fasilitas',
            'poktan' => 'kelompok tani',
            'lahan' => 'bidang lahan',
            'pengaduan' => 'pengaduan',
        ];

        foreach ($turunan as $relasi => $sebutan) {
            $jumlah = $sp->{$relasi}()->count();

            if ($jumlah > 0) {
                return back()->with('galat', 'SP ini masih menaungi '.$jumlah.' '.$sebutan.' sehingga tidak dapat dihapus.');
            }
        }

        $sp->delete();

        return redirect()->route('sp.index')->with('sukses', 'Data satuan permukiman dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?SatuanPermukiman $sp = null): array
    {
        $abaikan = $sp?->id_satuan_permukiman;

        $data = $request->validate([
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('satuan_permukiman', 'nama')->ignore($abaikan, 'id_satuan_permukiman'),
            ],
            'kode_sp' => [
                'nullable', 'string', 'max:20',
                Rule::unique('satuan_permukiman', 'kode_sp')->ignore($abaikan, 'id_satuan_permukiman'),
            ],
            'kawasan_id' => ['required', 'integer', Rule::exists('kawasan_transmigrasi', 'id_kawasan_transmigrasi')],
            'desa_id' => ['required', 'integer', Rule::exists('desa', 'id_desa')],
            'tahun_penempatan' => ValidationRules::tahun(),
            'luas_lahan' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'jumlah_kk_rencana' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'lintang' => ValidationRules::lintang(),
            'bujur' => ValidationRules::bujur(),
            'keterangan' => ['nullable', 'string', 'max:1000'],

            // Keadaan wilayah (Rombongan C). Seluruhnya opsional: SP lama
            // kerap belum punya datanya, dan mewajibkannya membuat
            // penyuntingan hal lain ikut tertahan.
            'lintang_utara' => ValidationRules::lintang(),
            'lintang_selatan' => ValidationRules::lintang(),
            'bujur_barat' => ValidationRules::bujur(),
            'bujur_timur' => ValidationRules::bujur(),
            'jarak_ke_kecamatan_km' => ['nullable', 'numeric', 'min:0', 'max:99999.9'],
            'jarak_ke_kabupaten_km' => ['nullable', 'numeric', 'min:0', 'max:99999.9'],
            'jarak_ke_provinsi_km' => ['nullable', 'numeric', 'min:0', 'max:99999.9'],
            'batas_utara' => ['nullable', 'string', 'max:150'],
            'batas_timur' => ['nullable', 'string', 'max:150'],
            'batas_selatan' => ['nullable', 'string', 'max:150'],
            'batas_barat' => ['nullable', 'string', 'max:150'],
            'nomor_sk_pencadangan' => ['nullable', 'string', 'max:100'],
            'tanggal_sk_pencadangan' => ['nullable', 'date', 'before_or_equal:today'],
            'pola_permukiman' => ['nullable', Rule::enum(PolaPermukiman::class)],
            'tingkat_kesuburan_tanah' => ['nullable', Rule::enum(TingkatKesuburanTanah::class)],
            'bentuk_wilayah' => ['nullable', Rule::enum(BentukWilayah::class)],
            'curah_hujan_tahunan_mm' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'suhu_rata_c' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'angin_rata_knot' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'penyinaran_rata_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sumber_air_bersih' => ['nullable', 'string', 'max:255'],
            'sumber_air_pertanian' => ['nullable', 'string', 'max:255'],
            'rute_aksesibilitas' => ['nullable', 'array'],
            'rute_aksesibilitas.*.rute' => ['required', 'string', 'max:255'],
            'rute_aksesibilitas.*.jarak_km' => ['nullable', 'numeric', 'min:0', 'max:999999.9'],
            'rute_aksesibilitas.*.sarana_angkutan' => ['nullable', 'string', 'max:150'],
            'rute_aksesibilitas.*.tempat_pemberangkatan' => ['nullable', 'string', 'max:150'],
            'rute_aksesibilitas.*.kondisi_jalan' => ['nullable', 'string', 'max:150'],
            'rute_aksesibilitas.*.waktu_tempuh' => ['nullable', 'string', 'max:80'],
            'rute_aksesibilitas.*.ongkos_rp' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'rute_aksesibilitas.*.keterangan' => ['nullable', 'string', 'max:255'],
            '_rute_disunting' => ['nullable', 'boolean'],
            'dokumen_pendukung' => ValidationRules::dokumen(),
        ] +
            $this->aturanRentang() + [
                'ph_tanah_min' => ['nullable', 'numeric', 'min:0', 'max:14'],
                'ph_tanah_maks' => ['nullable', 'numeric', 'min:0', 'max:14', 'gte:ph_tanah_min'],
            ], [
                'nama.required' => 'Nama satuan permukiman wajib diisi.',
                'nama.unique' => 'Nama SP ini sudah terdaftar.',
                'kode_sp.unique' => 'Kode SP ini sudah dipakai SP lain.',
                'kawasan_id.required' => 'Kawasan transmigrasi wajib dipilih.',
                'desa_id.required' => 'Desa wajib dipilih.',
                'ph_tanah_maks.gte' => 'pH maksimum tidak boleh lebih kecil dari pH minimum.',
            ] + $this->pesanRentang() + ValidationRules::pesan());

        $kabupatenDesa = Desa::query()
            ->join('kecamatan', 'kecamatan.id_kecamatan', '=', 'desa.kecamatan_id')
            ->where('desa.id_desa', $data['desa_id'])
            ->value('kecamatan.kabupaten_id');
        $kabupatenKawasan = KawasanTransmigrasi::query()
            ->whereKey($data['kawasan_id'])
            ->value('kabupaten_id');

        if ((int) $kabupatenDesa !== (int) $kabupatenKawasan) {
            throw ValidationException::withMessages([
                'desa_id' => 'Desa dan kawasan transmigrasi harus berada dalam kabupaten yang sama.',
            ]);
        }

        return $data;
    }

    private function pisahkan(array $data): array
    {
        $rute = $data['rute_aksesibilitas'] ?? [];
        $ruteDisunting = ($data['_rute_disunting'] ?? null) == '1';
        unset($data['rute_aksesibilitas'], $data['_rute_disunting'], $data['dokumen_pendukung']);

        return [$data, $rute, $ruteDisunting];
    }

    private function lampirkanDokumen(Request $request, SatuanPermukiman $sp): void
    {
        if (! $request->hasFile('dokumen_pendukung')) {
            return;
        }

        $berkas = $this->rekamBerkas(
            $request->file('dokumen_pendukung'),
            'sp',
            (int) $sp->id_satuan_permukiman,
            'sk',
            $sp->nama,
            1,
            'sk',
        );

        $sp->forceFill(['berkas_id' => $berkas->id_berkas])->save();
    }

    /**
     * Pasangan min/maks keadaan wilayah.
     *
     * Maks WAJIB `gte` minnya. Tanpa itu petugas dapat menyimpan rentang
     * terbalik (mis. curah hujan 3000-500) yang lolos diam-diam lalu terbaca
     * sebagai rentang kosong pada Laporan Monografi SP.
     *
     * @return array<string, array<int, string>>
     */
    private function aturanRentang(): array
    {
        $aturan = [];

        foreach (self::RENTANG as $awalan => [$akhiran, $maksNilai]) {
            $min = $awalan.'_min'.$akhiran;
            $maks = $awalan.'_maks'.$akhiran;

            $aturan[$min] = ['nullable', 'numeric', 'min:0', 'max:'.$maksNilai];
            $aturan[$maks] = ['nullable', 'numeric', 'min:0', 'max:'.$maksNilai, 'gte:'.$min];
        }

        return $aturan;
    }

    /**
     * @return array<string, string>
     */
    private function pesanRentang(): array
    {
        $pesan = [];

        foreach (self::RENTANG as $awalan => [$akhiran]) {
            $pesan[$awalan.'_maks'.$akhiran.'.gte'] = 'Nilai maksimum tidak boleh lebih kecil dari minimumnya.';
        }

        return $pesan;
    }
}
