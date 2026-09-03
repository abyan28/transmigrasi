<?php

/*
 * Task 4.1 -- CRUD wilayah bertingkat + seeder provinsi/kabupaten.
 *
 * Berumah di grup Database (MySQL nyata) sebab yang dijaga menyentuh FK
 * RESTRICT dan keunikan kolom -- keduanya tidak ditegakkan SQLite sekeras
 * MySQL, sehingga penjaga di suite Feature akan menjadi fiksi.
 */

use App\Models\Desa;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Provinsi;
use App\Models\User;
use Database\Seeders\WilayahSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $this->petugas = User::factory()->create();
    $this->petugas->semuaIzin = true;
    $this->actingAs($this->petugas);
});

it('menanam 38 provinsi dan 514 kabupaten se-Indonesia', function () {
    $this->seed(WilayahSeeder::class);

    expect(Provinsi::count())->toBe(38)
        ->and(Kabupaten::count())->toBe(514);
});

it('memakai kode BPS sebagai kunci utama, bukan auto-increment', function () {
    // Id di basis data WAJIB sama dengan id yang dipakai DataWilayah dan
    // DummyData, supaya peralihan tampilan ke Eloquent tidak menggeser
    // satu pun rujukan yang sudah ada di modul lain.
    $this->seed(WilayahSeeder::class);

    expect(Kabupaten::find(5321)?->nama)->toBe('Kabupaten Malaka')
        ->and(Provinsi::find(53)?->nama)->toBe('Nusa Tenggara Timur');
});

it('menanam kecamatan dan desa HANYA wilayah lokus', function () {
    // Berkas sumber nasional memuat ~7.000 kecamatan dan ~83.000 desa.
    // Menanamnya penuh membuat dropdown desa pada form SP berisi puluhan
    // ribu pilihan yang seluruhnya keliru kecuali enam.
    $this->seed(WilayahSeeder::class);

    expect(Kecamatan::count())->toBe(4)
        ->and(Desa::count())->toBe(6)
        ->and(Kecamatan::pluck('kabupaten_id')->unique()->all())->toBe([5321]);
});

it('idempoten: dijalankan ulang tidak menggandakan baris', function () {
    $this->seed(WilayahSeeder::class);
    $this->seed(WilayahSeeder::class);

    expect(Provinsi::count())->toBe(38)
        ->and(Kabupaten::count())->toBe(514)
        ->and(Desa::count())->toBe(6);
});

it('menyimpan kecamatan baru lewat form', function () {
    $this->seed(WilayahSeeder::class);

    $this->post(route('wilayah.simpan'), [
        'tingkat' => 'kecamatan',
        'nama' => 'KOBALIMA',
        'kabupaten_id' => 5321,
    ])->assertRedirect(route('wilayah'));

    expect(Kecamatan::where('nama', 'KOBALIMA')->first()?->kabupaten_id)->toBe(5321);
});

it('membedakan kecamatan dan desa ber-id sama lewat tingkat di alamat', function () {
    // Regresi yang ditemukan saat menulis Task 4.1: kunci utama keempat tabel
    // berdiri sendiri-sendiri, sehingga id 1 sah sebagai kecamatan (Laen Manen)
    // MAUPUN desa (Kapitan Meo). Alamat lama `/wilayah/{id}` tidak pernah cukup
    // menunjuk satu baris, dan penghapusan yang menebak tingkatnya akan
    // membuang baris yang keliru tanpa memerahkan apa pun.
    $this->seed(WilayahSeeder::class);

    expect(Kecamatan::find(1)?->nama)->toBe('Laen Manen')
        ->and(Desa::find(1)?->nama)->toBe('Kapitan Meo');

    $this->put(route('wilayah.perbarui', ['tingkat' => 'desa', 'id' => 1]), [
        'nama' => 'KAPITAN MEO BARU',
        'kecamatan_id' => 1,
    ])->assertRedirect(route('wilayah'));

    // Desa berubah, kecamatan ber-id sama TIDAK ikut tersentuh.
    expect(Desa::find(1)?->nama)->toBe('KAPITAN MEO BARU')
        ->and(Kecamatan::find(1)?->nama)->toBe('Laen Manen');
});

it('menolak menghapus wilayah yang masih menaungi turunan', function () {
    // FK RESTRICT sudah menahannya di basis data, tetapi galat SQL mentah tak
    // terbaca petugas. Yang dijaga di sini: alasannya tersampaikan sebagai
    // kalimat yang dapat ditindaklanjuti, bukan 500.
    $this->seed(WilayahSeeder::class);

    $this->from(route('wilayah'))
        ->delete(route('wilayah.hapus', ['tingkat' => 'kecamatan', 'id' => 1]))
        ->assertRedirect(route('wilayah'))
        ->assertSessionHas('galat');

    expect(Kecamatan::find(1))->not->toBeNull();
});

it('menghapus desa yang tidak menaungi SP mana pun', function () {
    $this->seed(WilayahSeeder::class);

    $this->delete(route('wilayah.hapus', ['tingkat' => 'desa', 'id' => 6]))
        ->assertRedirect(route('wilayah'));

    expect(Desa::find(6))->toBeNull()
        ->and(Desa::count())->toBe(5);
});

it('menolak tingkat yang tidak dikenal', function () {
    $this->seed(WilayahSeeder::class);

    $this->delete('/wilayah/dusun/1')->assertNotFound();
});
