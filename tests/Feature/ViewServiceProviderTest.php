<?php

use App\Enums\AksiAuditLog;
use App\Enums\CakupanData;
use App\Enums\JenisDaftarPilihan;
use App\Enums\StatusAnggotaKeluarga;
use App\Models\AnggotaKeluarga;
use App\Models\AuditLog;
use App\Models\DaftarPilihan;
use App\Models\Role;
use App\Models\SatuanPermukiman;
use App\Models\Transmigran;
use App\Models\User;
use App\Providers\ViewServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;

uses(RefreshDatabase::class);

function rujukanForm(string $kunci): mixed
{
    return (new ReflectionMethod(ViewServiceProvider::class, 'nilaiRujukan'))->invoke(null, $kunci);
}

it('menyuplai rujukan form dari database dengan bentuk lama', function () {
    $sp = SatuanPermukiman::query()->firstOrFail();
    $sp->update(['nama' => 'SP DATA NYATA']);
    Transmigran::query()->firstOrFail()->update(['pekerjaan_kepala_keluarga' => 'PENGRAJIN']);

    $sumberDana = DaftarPilihan::query()
        ->where('jenis', JenisDaftarPilihan::SumberDana->value)
        ->firstOrFail();
    $sumberDana->update(['nilai' => 'Dana Nyata']);

    $anggota = AnggotaKeluarga::query()->where('status', StatusAnggotaKeluarga::Aktif->value)->firstOrFail();
    $anggota->update(['nama_lengkap' => 'ANGGOTA NYATA']);

    expect(rujukanForm('daftarSp'))->toContainEqual([
        'id_satuan_permukiman' => $sp->id_satuan_permukiman,
        'nama' => 'SP DATA NYATA',
        'kode_sp' => $sp->kode_sp,
        'desa' => $sp->desa->nama,
        'kecamatan' => $sp->desa->kecamatan->nama,
        'kawasan' => $sp->kawasan->nama,
        'kawasan_id' => $sp->kawasan_id,
        'tahun_penempatan' => $sp->tahun_penempatan,
        'luas_lahan' => (float) $sp->luas_lahan,
        'jumlah_kk_rencana' => $sp->jumlah_kk_rencana,
        'jumlah_kk_terisi' => $sp->transmigran()->count(),
        'lintang' => (float) $sp->lintang,
        'bujur' => (float) $sp->bujur,
        'keterangan' => $sp->keterangan,
        'berkas_id' => $sp->berkas_id,
    ])->and(rujukanForm('saranPekerjaan'))->toContain('PENGRAJIN')
        ->and(rujukanForm('opsiSumberDana'))->toHaveKey('Dana Nyata')
        ->and(collect(rujukanForm('anggotaKeluargaPerKeluarga'))->flatten(1)->pluck('nama')->all())
        ->toContain('ANGGOTA NYATA');
});

it('hanya menawarkan pilihan aktif pada form dan tetap memuat pilihan nonaktif untuk master', function () {
    $nonaktif = DaftarPilihan::query()
        ->where('jenis', JenisDaftarPilihan::BidangPengaduan->value)
        ->firstOrFail();
    $nonaktif->update(['nilai' => 'Bidang Lama', 'is_aktif' => false]);

    expect(rujukanForm('opsiBidang'))->not->toHaveKey('Bidang Lama')
        ->and(collect(rujukanForm('daftarBidang'))->pluck('nilai')->all())->toContain('Bidang Lama');
});

it('membatasi daftar satuan permukiman menurut penugasan pengguna per SP', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->satuanPermukiman()->attach($sp[0]->id_satuan_permukiman);
    $this->actingAs($pengguna);

    expect(collect(rujukanForm('daftarSp'))->pluck('id_satuan_permukiman')->all())
        ->toBe([$sp[0]->id_satuan_permukiman]);
});

it('menyuplai catatan log dan riwayat akun dari audit log', function () {
    $pelaku = User::factory()->create(['nama' => 'PETUGAS NYATA']);
    AuditLog::create([
        'user_id' => $pelaku->id_user,
        'aksi' => AksiAuditLog::Ubah,
        'nama_tabel' => 'transmigran',
        'record_id' => 991,
        'data_lama' => ['nama' => 'LAMA'],
        'data_baru' => ['nama' => 'BARU'],
        'ip_address' => '127.0.0.1',
    ]);
    AuditLog::create([
        'user_id' => $pelaku->id_user,
        'aksi' => AksiAuditLog::ResetKataSandi,
        'nama_tabel' => 'user',
        'record_id' => $pelaku->id_user,
        'data_baru' => ['jalur' => 'Admin'],
        'ip_address' => '127.0.0.1',
    ]);

    $catatan = View::make('components.sim.catatan-log', [
        'namaTabel' => 'transmigran',
        'recordId' => 991,
    ])->render();
    $detail = View::make('pages.pengguna.detail')->render();

    expect($catatan)->toContain('Mengubah 1 kolom: nama.')->toContain('PETUGAS NYATA')
        ->and($detail)->toContain('Reset Kata Sandi.')->toContain('PETUGAS NYATA');
});

it('menampilkan peringatan data contoh di demo dan pengujian tetapi tidak di produksi', function () {
    expect(View::make('layouts.dokumen')->render())->toContain('Data contoh.');

    app()->detectEnvironment(fn () => 'production');

    try {
        expect(View::make('layouts.dokumen')->render())->not->toContain('Data contoh.');
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});
