<?php

/*
 * Task 4.7 -- Daftar Pilihan, induk seluruh dropdown sistem.
 *
 * Berumah di grup Database sebab yang dijaga menyentuh ENUM `jenis`, self-FK
 * `bidang_id`, dan keunikan nilai DALAM jenis -- ketiganya tidak ditegakkan
 * SQLite sekeras MySQL.
 */

use App\Enums\JenisDaftarPilihan;
use App\Models\DaftarPilihan;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\DaftarPilihanSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(DaftarPilihanSeeder::class);
});

it('menanam seluruh 14 jenis daftar pilihan', function () {
    expect(DaftarPilihan::distinct('jenis')->count('jenis'))->toBe(count(JenisDaftarPilihan::cases()))
        ->and(DaftarPilihan::count())->toBe(76);
});

it('mempertahankan id yang sudah ditunjuk penilaian kondisi SP', function () {
    // `PenilaianKondisiSp::parameter()` merujuk `daftar_pilihan_id` untuk jenis
    // infrastruktur dan fasilitas. Menyusun ulang daftarnya akan menggeser
    // id itu diam-diam dan membuat penilaian menunjuk jenis yang keliru.
    foreach (DummyData::daftarPilihan() as $b) {
        expect(DaftarPilihan::find($b['id_daftar_pilihan'])?->nilai)->toBe($b['nilai']);
    }
});

it('menautkan kategori pengaduan ke bidang penanganannya lewat self-FK', function () {
    $rumah = DaftarPilihan::where('jenis', JenisDaftarPilihan::KategoriPengaduan->value)
        ->where('nilai', 'Rumah')->first();

    expect($rumah->bidang?->nilai)->toBe('Ketransmigrasian');

    // Kategori yang dapat jatuh ke dua dinas sengaja berbidang null:
    // menebaknya membuat laporan masuk ke daftar dinas yang keliru.
    $bencana = DaftarPilihan::where('jenis', JenisDaftarPilihan::KategoriPengaduan->value)
        ->where('nilai', 'Bencana')->first();

    expect($bencana->bidang_id)->toBeNull();
});

it('menyimpan pilihan baru dan langsung menyediakannya', function () {
    $this->post(route('daftar-pilihan.simpan'), [
        'jenis' => JenisDaftarPilihan::SumberDana->value,
        'nilai' => 'DANA DESA',
    ])->assertRedirect(route('daftar-pilihan.jenis', ['jenis' => JenisDaftarPilihan::SumberDana->value]));

    $baru = DaftarPilihan::where('nilai', 'DANA DESA')->first();

    expect($baru)->not->toBeNull()
        ->and($baru->is_aktif)->toBeTrue()
        // Urutan diisi otomatis di ekor daftarnya bila tidak disebut.
        ->and($baru->urutan)->toBe(9);
});

it('mengizinkan nilai sama pada daftar yang berbeda', function () {
    // "Lainnya" sah muncul pada banyak daftar sekaligus, sehingga keunikan
    // ditegakkan DALAM jenis, bukan lintas jenis.
    $this->post(route('daftar-pilihan.simpan'), [
        'jenis' => JenisDaftarPilihan::StatusHunian->value,
        'nilai' => 'Lainnya',
    ])->assertSessionHasNoErrors();

    expect(DaftarPilihan::where('nilai', 'Lainnya')->count())->toBeGreaterThan(1);
});

it('menolak nilai kembar dalam daftar yang sama', function () {
    $this->post(route('daftar-pilihan.simpan'), [
        'jenis' => JenisDaftarPilihan::StatusHunian->value,
        'nilai' => 'Dihuni',
    ])->assertSessionHasErrors('nilai');
});

it('menonaktifkan pilihan tanpa menghapusnya', function () {
    // Tidak ada rute hapus, dan itu disengaja: menghapus membuat data lama
    // menunjuk pilihan yang lenyap, dan rekapnya kehilangan baris itu tanpa
    // pesan apa pun.
    $dihuni = DaftarPilihan::where('jenis', JenisDaftarPilihan::StatusHunian->value)
        ->where('nilai', 'Dihuni')->first();

    $this->put(route('daftar-pilihan.perbarui', $dihuni->id_daftar_pilihan), [
        'jenis' => JenisDaftarPilihan::StatusHunian->value,
        'nilai' => 'Dihuni',
        'urutan' => $dihuni->urutan,
        'is_aktif' => '0',
    ])->assertRedirect(route('daftar-pilihan.jenis', ['jenis' => JenisDaftarPilihan::StatusHunian->value]));

    expect(DaftarPilihan::find($dihuni->id_daftar_pilihan))->not->toBeNull()
        ->and($dihuni->fresh()->is_aktif)->toBeFalse();
});

it('mengosongkan skor pada daftar yang memang tak berskor', function () {
    // Skor hanya bermakna bagi jenis `kondisi`. Membiarkannya terbawa pada
    // daftar lain menaruh angka yang tak pernah dibaca siapa pun dan hanya
    // menyesatkan pembaca tabel.
    $this->post(route('daftar-pilihan.simpan'), [
        'jenis' => JenisDaftarPilihan::StatusHunian->value,
        'nilai' => 'DIHUNI SEBAGIAN',
        'nilai_skor' => '0.5',
    ]);

    expect(DaftarPilihan::where('nilai', 'DIHUNI SEBAGIAN')->first()?->nilai_skor)->toBeNull();
});

it('membalas 404 untuk jenis daftar yang tidak ada', function () {
    // Daftar yang tidak ada dan daftar yang kebetulan kosong adalah dua
    // keadaan berbeda; menyamakannya membuat salah ketik tampak seperti
    // data yang belum diisi.
    $this->get('/master/daftar-pilihan/jenis_karangan')->assertNotFound();
});

it('mengalihkan alamat lama bertab ke halaman daftarnya', function () {
    $this->get('/master/daftar-pilihan?tab='.JenisDaftarPilihan::SumberDana->value)
        ->assertRedirect(route('daftar-pilihan.jenis', ['jenis' => JenisDaftarPilihan::SumberDana->value]));
});
