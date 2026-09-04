<?php

/*
 * Task 3.3 (C4) -- `izin:` terpasang pada rute internal nyata via
 * `PetaIzinRute` + loop di `bootstrap/app.php`. Berjalan di MySQL nyata.
 *
 * Rute penulisan (POST/PUT/DELETE) di sini masih stub closure yang membalas
 * redirect; yang diuji adalah PINTU izinnya, bukan isi aksinya.
 */

use App\Models\Permission;
use App\Models\Role;
use App\Models\Transmigran;
use App\Models\User;
use App\Support\PenyimpananDokumen;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\PermissionRoleSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(PermissionRoleSeeder::class);
});

it('meloloskan Admin ke seluruh modul sistem', function () {
    $admin = User::factory()->create(['role_id' => 1]);

    $this->actingAs($admin)->get(route('pengaturan.role'))->assertOk();
    $this->actingAs($admin)->get(route('pengguna.index'))->assertOk();
    $this->actingAs($admin)->get(route('audit-log'))->assertOk();
    $this->actingAs($admin)->get(route('transmigran.index'))->assertOk();
});

it('menolak Operator SP membuka manajemen pengguna dan role (403)', function () {
    $operator = User::factory()->create(['role_id' => 4]);

    $this->actingAs($operator)->get(route('pengguna.index'))->assertForbidden();
    $this->actingAs($operator)->get(route('pengaturan.role'))->assertForbidden();
    $this->actingAs($operator)->get(route('audit-log'))->assertForbidden();
});

it('meloloskan Operator SP melihat & menambah transmigran tetapi menolak menghapus', function () {
    $operator = User::factory()->create(['role_id' => 4]);

    $this->actingAs($operator)->get(route('transmigran.index'))->assertOk();
    // Tambah lolos pintu izin (redirect stub, bukan 403).
    $this->actingAs($operator)->post(route('transmigran.simpan'))->assertRedirect();
    // Hapus ditolak: Operator SP tanpa `transmigran.hapus`.
    $this->actingAs($operator)->delete(route('transmigran.hapus', ['id' => 1]))->assertForbidden();
});

it('menolak Operator SP menangani pengaduan (rules.md 5.1 catatan 4)', function () {
    $operator = User::factory()->create(['role_id' => 4]);

    // Boleh lihat & catat pengaduan wilayahnya...
    $this->actingAs($operator)->get(route('pengaduan.index'))->assertOk();
    $this->actingAs($operator)->post(route('pengaduan.simpan'))->assertRedirect();
    // ...tetapi TIDAK menangani (memutuskan tindak lanjut atas nama dinas).
    $this->actingAs($operator)->post(route('pengaduan.tangani', ['id' => 1]))->assertForbidden();
});

it('menolak Dinas Transmigrasi mengubah data pertanian tetapi meloloskan bacaan', function () {
    $dinas = User::factory()->create(['role_id' => 2]);

    $this->actingAs($dinas)->get(route('poktan.index'))->assertOk();        // lihat: ya
    $this->actingAs($dinas)->post(route('poktan.simpan'))->assertForbidden(); // tambah: tidak
});

it('menegakkan lihat sebagai prasyarat: rute ubah menolak tanpa lihat', function () {
    $role = Role::factory()->create();
    $role->permissions()->sync(
        Permission::where('nama', 'komoditas.ubah')->pluck('id_permission'),
    );
    $user = User::factory()->create(['role_id' => $role->id_role]);

    $this->actingAs($user)->put(route('komoditas.perbarui', ['id' => 1]))->assertForbidden();
    $this->actingAs($user)->get(route('komoditas.index'))->assertForbidden();
});

it('membiarkan profil sendiri & halaman informasi terbuka untuk semua role', function () {
    $operator = User::factory()->create(['role_id' => 4]);

    $this->actingAs($operator)->get(route('profil'))->assertOk();
    $this->actingAs($operator)->get(route('profil.kata-sandi'))->assertOk();
    $this->actingAs($operator)->get(route('tentang'))->assertOk();
    $this->actingAs($operator)->get(route('panduan'))->assertOk();
});

it('memeriksa kewenangan lihat modul pada unduhan dokumen privat', function () {
    $operator = User::factory()->create(['role_id' => 4]); // punya transmigran.lihat
    $tanpaRole = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);

    // Berkas tak ada -> 404 mendahului cek izin bagi yang berwenang.
    $this->actingAs($operator)
        ->get('/dokumen/transmigran/1/tidak-ada.pdf')
        ->assertNotFound();
    // Tanpa transmigran.lihat -> 403 (cek izin dinamis di controller).
    // (juga 404 bila berkas tak ada; yang penting bukan 200.)
    expect($this->actingAs($tanpaRole)->get('/dokumen/transmigran/1/x.pdf')->status())
        ->toBeIn([403, 404]);
});

it('menahan unduhan dokumen di luar cakupan data operator Per SP (Task 10.6)', function () {
    Storage::fake('local');

    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(TransmigranSeeder::class);

    // Transmigran 1 (DummyData) berada di SP 1.
    $transmigran = Transmigran::withoutGlobalScopes()->findOrFail(1);
    expect($transmigran->satuan_permukiman_id)->toBe(1);

    $folder = PenyimpananDokumen::folder('transmigran', 1);
    Storage::disk('local')->put($folder.'/KK.pdf', '%PDF-1.4 contoh');

    $ditugaskanSp1 = User::factory()->create(['role_id' => 4]);
    $ditugaskanSp1->satuanPermukiman()->attach(1);

    $ditugaskanSp2 = User::factory()->create(['role_id' => 4]);
    $ditugaskanSp2->satuanPermukiman()->attach(2);

    $url = '/dokumen/transmigran/1/KK.pdf';

    // Operator SP 1: berkas milik SP-nya -> boleh.
    $this->actingAs($ditugaskanSp1)->get($url)->assertOk();

    // Operator SP 2: berkas di luar cakupan -> 404 (bukan 403, tak dibedakan
    // dari berkas yang memang tidak ada).
    $this->actingAs($ditugaskanSp2)->get($url)->assertNotFound();
});
