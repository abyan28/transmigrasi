<?php

/*
 * Task 3.1 -- DOMAIN 2 (Master Wilayah & SP) + `daftar_pilihan` + `berkas`.
 *
 * `daftar_pilihan` dan `berkas` ikut di batch ini sebab `satuan_permukiman.berkas_id`
 * -> `berkas` -> `daftar_pilihan` (topological sort, bukan urutan file schema.sql).
 *
 * Berjalan di MySQL/MariaDB nyata. Kecocokan kolom/indeks/FK dijaga terpisah
 * oleh `php artisan sim:banding-skema`. Uji ini menjaga model & relasinya.
 */

use App\Enums\JenisDaftarPilihan;
use App\Enums\PolaPermukiman;
use App\Models\Berkas;
use App\Models\DaftarPilihan;
use App\Models\Desa;
use App\Models\Kabupaten;
use App\Models\KawasanTransmigrasi;
use App\Models\Provinsi;
use App\Models\Role;
use App\Models\RuteAksesibilitasSp;
use App\Models\SatuanPermukiman;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require_once __DIR__.'/DatabaseHelpers.php';

it('membuat kesepuluh tabel batch ini', function () {
    foreach ([
        'provinsi', 'kabupaten', 'kecamatan', 'desa', 'kawasan_transmigrasi',
        'daftar_pilihan', 'berkas', 'satuan_permukiman', 'user_satuan_permukiman',
        'rute_aksesibilitas_sp',
    ] as $tabel) {
        expect(Schema::hasTable($tabel))->toBeTrue("tabel {$tabel} tidak dibuat");
    }
});

it('memakai PK dan nama tabel bentuk tunggal', function () {
    expect((new SatuanPermukiman)->getTable())->toBe('satuan_permukiman')
        ->and((new SatuanPermukiman)->getKeyName())->toBe('id_satuan_permukiman')
        ->and((new KawasanTransmigrasi)->getKeyName())->toBe('id_kawasan_transmigrasi')
        ->and((new RuteAksesibilitasSp)->getKeyName())->toBe('id_rute_aksesibilitas_sp')
        ->and((new DaftarPilihan)->getKeyName())->toBe('id_daftar_pilihan')
        ->and((new Berkas)->getKeyName())->toBe('id_berkas');
});

it('merangkai hierarki wilayah bercabang dua lewat kunci eksplisit', function () {
    $sp = buatSp();

    expect($sp->kawasan)->toBeInstanceOf(KawasanTransmigrasi::class)
        ->and($sp->desa)->toBeInstanceOf(Desa::class)
        ->and($sp->desa->kecamatan->kabupaten->provinsi)->toBeInstanceOf(Provinsi::class)
        ->and($sp->kawasan->kabupaten->id_kabupaten)->toBe($sp->desa->kecamatan->kabupaten->id_kabupaten)
        ->and($sp->desa->satuanPermukiman->pluck('id_satuan_permukiman'))->toContain($sp->id_satuan_permukiman);
});

it('memakai slug sebagai kunci rute untuk kawasan dan SP, uuid untuk berkas', function () {
    expect((new KawasanTransmigrasi)->getRouteKeyName())->toBe('slug')
        ->and((new SatuanPermukiman)->getRouteKeyName())->toBe('slug')
        ->and((new Berkas)->getRouteKeyName())->toBe('uuid');
});

it('menautkan penugasan SP dua arah lewat pivot user_satuan_permukiman', function () {
    $sp = buatSp();
    $user = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);

    $user->satuanPermukiman()->attach($sp->id_satuan_permukiman);

    expect($user->satuanPermukiman()->count())->toBe(1)
        ->and($sp->petugas->pluck('id_user'))->toContain($user->id_user);
});

it('meng-cast ENUM keadaan wilayah SP dan jenis daftar pilihan', function () {
    $sp = buatSp(['pola_permukiman' => PolaPermukiman::Linear->value, 'luas_lahan' => '12.50']);
    $ref = DaftarPilihan::create(['jenis' => JenisDaftarPilihan::SumberDana->value, 'nilai' => 'APBN']);

    expect($sp->pola_permukiman)->toBe(PolaPermukiman::Linear)
        ->and($sp->luas_lahan)->toBe('12.50')
        ->and($ref->jenis)->toBe(JenisDaftarPilihan::SumberDana)
        ->and($ref->fresh()->is_aktif)->toBeTrue();
});

it('menegakkan self-FK daftar_pilihan.bidang_id dan FK berkas ke daftar_pilihan/user', function () {
    $bidang = DaftarPilihan::create(['jenis' => JenisDaftarPilihan::BidangPengaduan->value, 'nilai' => 'Pertanian']);
    $kategori = DaftarPilihan::create([
        'jenis' => JenisDaftarPilihan::KategoriPengaduan->value, 'nilai' => 'Hama', 'bidang_id' => $bidang->id_daftar_pilihan,
    ]);
    expect($kategori->bidang->id_daftar_pilihan)->toBe($bidang->id_daftar_pilihan);

    $user = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);
    $berkas = Berkas::create([
        'uuid' => (string) Str::uuid(), 'nama_file' => 'sk.pdf', 'path' => 'sp/1/sk.pdf',
        'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 12345, 'user_id' => $user->id_user,
        'jenis_berkas_id' => null,
    ]);
    expect($berkas->pengunggah->id_user)->toBe($user->id_user);

    // bidang_id menunjuk daftar pilihan yang tak ada -> ditolak FK.
    expect(fn () => DaftarPilihan::create([
        'jenis' => JenisDaftarPilihan::KategoriPengaduan->value, 'nilai' => 'Palsu', 'bidang_id' => 999999,
    ]))->toThrow(QueryException::class);
});

it('menegakkan FK RESTRICT wilayah dan UNIQUE komposit', function () {
    $prov = Provinsi::create(['nama' => 'Bali']);
    Kabupaten::create(['provinsi_id' => $prov->id_provinsi, 'nama' => 'Badung']);

    // provinsi dengan kabupaten tidak dapat dihapus (RESTRICT).
    expect(fn () => $prov->delete())->toThrow(QueryException::class);

    // Nama kabupaten unik DALAM provinsinya.
    expect(fn () => Kabupaten::create(['provinsi_id' => $prov->id_provinsi, 'nama' => 'Badung']))
        ->toThrow(QueryException::class);
});

it('mengaktifkan soft delete pada kawasan & SP, tidak pada daftar pilihan & rute', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(SatuanPermukiman::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(KawasanTransmigrasi::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(DaftarPilihan::class), true))->toBeFalse()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(RuteAksesibilitasSp::class), true))->toBeFalse();

    $sp = buatSp();
    $id = $sp->id_satuan_permukiman;
    $sp->delete();

    expect(SatuanPermukiman::find($id))->toBeNull()
        ->and(SatuanPermukiman::withTrashed()->find($id))->not->toBeNull();
});

it('menghapus rute aksesibilitas saat SP dihapus permanen (CASCADE)', function () {
    $sp = buatSp();
    RuteAksesibilitasSp::create(['satuan_permukiman_id' => $sp->id_satuan_permukiman, 'rute' => 'Betun - Kapitan Meo']);

    expect($sp->ruteAksesibilitas()->count())->toBe(1);

    $sp->forceDelete();

    expect(RuteAksesibilitasSp::where('satuan_permukiman_id', $sp->id_satuan_permukiman)->count())->toBe(0);
});
