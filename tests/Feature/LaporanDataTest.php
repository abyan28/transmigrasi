<?php

use App\Enums\JenisKelamin;
use App\Enums\StatusAnggotaKeluarga;
use App\Models\AnggotaKeluarga;
use App\Models\SatuanPermukiman;
use App\Models\Transmigran;
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
