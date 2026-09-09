<?php

use App\Enums\CakupanData;
use App\Enums\JenisDaftarPilihan;
use App\Enums\JenisKelamin;
use App\Enums\StatusAnggotaKeluarga;
use App\Models\AnggotaKeluarga;
use App\Models\DaftarPilihan;
use App\Models\Role;
use App\Models\SatuanPermukiman;
use App\Models\Transmigran;
use App\Models\User;
use App\Support\LaporanData;
use App\Support\RekapDashboard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

it('builds report data and filter options without DummyData', function () {
    expect(file_get_contents(app_path('Support/LaporanData.php')))->not->toContain('DummyData');

    $ids = SatuanPermukiman::withoutGlobalScopes()->orderBy('nama')->pluck('id_satuan_permukiman')->all();
    $filter = LaporanData::filterLaporan('transmigran');

    expect(array_column($filter['sp'], 'id'))->toBe($ids)
        ->and($filter['daftarTahun'])->toBe(
            Transmigran::withoutGlobalScopes()->distinct()->orderBy('tahun_kedatangan')->pluck('tahun_kedatangan')->map(fn ($tahun) => (int) $tahun)->all(),
        )
        ->and(LaporanData::filterLaporan('indikator-kawasan')['ringkasanTahun'])
        ->toBe(RekapDashboard::ringkasanTahun());
});

it('menjaga Monografi di bawah budget query pada enam SP demo', function () {
    DB::flushQueryLog();
    DB::enableQueryLog();

    $isi = LaporanData::monografiSp();
    $jumlahQuery = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($isi['monografi'])->toHaveCount(6)
        ->and($jumlahQuery)->toBeLessThanOrEqual(220);
});

it('menggunakan ulang payload Monografi saat menyusun filter laporan', function () {
    $isi = LaporanData::monografiSp();
    $query = 0;
    DB::listen(function () use (&$query): void {
        $query++;
    });

    $filter = LaporanData::filterLaporan('monografi-sp', $isi);

    expect($filter['iklimTahun'])->toBe($isi['iklimTahun'])
        ->and($filter['kependudukanTahun'])->toBe($isi['kependudukanTahun'])
        ->and($query)->toBeLessThanOrEqual(30);
});

it('tidak menganggap jenis kelamin kosong sebagai perempuan pada agregasi batch Monografi', function () {
    $tahun = LaporanData::tahunDokumenBawaan();
    $transmigran = Transmigran::query()->where('tahun_kedatangan', '<=', $tahun)->firstOrFail();
    $transmigran->forceFill(['jenis_kelamin' => null])->save();

    $aktual = LaporanData::monografiSp()['kependudukanTahun'][$transmigran->satuan_permukiman_id][$tahun];
    $harapan = LaporanData::keadaanPendudukTahun($transmigran->satuan_permukiman_id, $tahun);

    expect($aktual)->toBe($harapan);
});

it('memakai fallback dokumen tanah bila pilihan berperilaku dinonaktifkan', function () {
    $pilihan = DaftarPilihan::query()
        ->where('jenis', JenisDaftarPilihan::StatusSertifikat->value)
        ->where('kode_perilaku', 'sudah')
        ->firstOrFail();
    $pilihan->update(['label' => 'Label Nonaktif', 'is_aktif' => false]);

    $baris = collect(LaporanData::monografiSp()['monografi'])
        ->flatMap(fn (array $sp): array => $sp['sosial_ekonomi']['sertifikat']['baris'])
        ->firstWhere(0, 'Sudah');

    expect($baris)->not->toBeNull();
});

it('derives monograph age tables from recorded people', function () {
    $tahun = LaporanData::tahunDokumenBawaan();
    $akhirTahun = Carbon::create($tahun, 12, 31)->endOfDay();
    $monografi = collect(LaporanData::monografiSp()['monografi'])->first();
    expect($monografi['sosial_budaya']['fasilitasUmum']['baris'][0][0])->not->toBeNull();
    $id = $monografi['sp_id'];

    $kepala = Transmigran::withoutGlobalScopes()
        ->where('satuan_permukiman_id', $id)
        ->where('tahun_kedatangan', '<=', $tahun)
        ->where(fn ($q) => $q->whereNull('tahun_keluar')->orWhere('tahun_keluar', '>', $tahun))
        ->get();
    $anggota = AnggotaKeluarga::withoutGlobalScopes()
        ->whereIn('transmigran_id', $kepala->pluck('id_transmigran'))
        ->whereDate('tanggal_lahir', '<=', $akhirTahun)
        ->where(fn ($q) => $q
            ->where('status', StatusAnggotaKeluarga::Aktif->value)
            ->orWhereDate('tanggal_peristiwa', '>', $akhirTahun))
        ->get();
    $orang = $kepala->concat($anggota)->filter(fn ($orang) => $orang->tanggal_lahir !== null
        && in_array($orang->jenis_kelamin, [JenisKelamin::LakiLaki, JenisKelamin::Perempuan], true));
    $usiaSekolah = $orang->filter(fn ($orang) => ($usia = (int) $orang->tanggal_lahir->diffInYears($akhirTahun)) >= 4 && $usia <= 19)->count();

    expect($monografi['kependudukan']['strukturUmur']['total'][3])->toBe($orang->count())
        ->and(array_sum(array_column($monografi['kependudukan']['usiaSekolah']['baris'], 3)))->toBe($usiaSekolah)
        ->and($monografi['kependudukan']['mutasi']['catatan'])->toContain('tidak dapat diturunkan');
});

it('mengubah produktivitas baris hasil panen ke ton per hektare', function () {
    $baris = collect(LaporanData::hasilPanen()['kelompok'])
        ->flatMap(fn (array $grup): array => $grup['baris'])
        ->first(fn (array $item): bool => $item['produksi_ton'] === 0.32);

    expect($baris)->not->toBeNull()
        ->and($baris['produktivitas'])->toBe(1.282);
});

it('membatasi laporan transmigran dan opsi SP pada cakupan pengguna', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->semuaIzin = true;
    $pengguna->satuanPermukiman()->attach($sp[0]->id_satuan_permukiman);
    $this->actingAs($pengguna);

    $laporan = LaporanData::transmigran();
    $filter = LaporanData::filterLaporan('transmigran');

    expect(array_values(array_unique(array_column($laporan['transmigran'], 'satuan_permukiman_id'))))
        ->toBe([$sp[0]->id_satuan_permukiman])
        ->and(array_column($filter['sp'], 'id'))->toBe([$sp[0]->id_satuan_permukiman]);
});
