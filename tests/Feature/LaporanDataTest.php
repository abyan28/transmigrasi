<?php

use App\Enums\CakupanData;
use App\Enums\JenisKelamin;
use App\Enums\StatusAnggotaKeluarga;
use App\Models\AnggotaKeluarga;
use App\Models\Role;
use App\Models\SatuanPermukiman;
use App\Models\Transmigran;
use App\Models\User;
use App\Support\LaporanData;
use App\Support\RekapDashboard;
use Illuminate\Support\Carbon;

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

it('derives monograph age tables from recorded people', function () {
    $tahun = LaporanData::tahunDokumenBawaan();
    $akhirTahun = Carbon::create($tahun, 12, 31)->endOfDay();
    $monografi = collect(LaporanData::monografiSp()['monografi'])->first();
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
