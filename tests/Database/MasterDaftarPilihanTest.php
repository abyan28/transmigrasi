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
use App\Models\ParameterPenilaianSp;
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

it('menanam seluruh jenis daftar pilihan', function () {
    expect(DaftarPilihan::distinct('jenis')->count('jenis'))->toBe(count(JenisDaftarPilihan::cases()))
        ->and(DaftarPilihan::count())->toBe(count(DummyData::daftarPilihan()));
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

it('membuat parameter nonaktif untuk jenis fasilitas baru', function () {
    $this->post(route('daftar-pilihan.simpan'), [
        'jenis' => JenisDaftarPilihan::JenisFasilitas->value,
        'nilai' => 'Perpustakaan',
    ])->assertSessionHasNoErrors();

    $pilihan = DaftarPilihan::where('jenis', JenisDaftarPilihan::JenisFasilitas->value)
        ->where('nilai', 'Perpustakaan')->firstOrFail();
    $parameter = ParameterPenilaianSp::where('daftar_pilihan_id', $pilihan->id_daftar_pilihan)->firstOrFail();

    expect($parameter->sumber)->toBe('Fasilitas')
        ->and($parameter->is_dinilai)->toBeFalse();
});

it('mengizinkan nilai sama pada daftar yang berbeda', function () {
    // "Lainnya" sah muncul pada banyak daftar sekaligus, sehingga keunikan
    // ditegakkan DALAM jenis, bukan lintas jenis.
    $this->post(route('daftar-pilihan.simpan'), [
        'jenis' => JenisDaftarPilihan::JenisInventaris->value,
        'nilai' => 'APBN',
    ])->assertSessionHasNoErrors();

    expect(DaftarPilihan::where('nilai', 'APBN')->count())->toBeGreaterThan(1);
});

it('menolak nilai kembar dalam daftar yang sama', function () {
    $this->post(route('daftar-pilihan.simpan'), [
        'jenis' => JenisDaftarPilihan::StatusHunian->value,
        'nilai' => 'Dihuni',
    ])->assertSessionHasErrors('nilai');
});

it('menolak nilai baru pada daftar yang terikat perilaku sistem', function () {
    $this->post(route('daftar-pilihan.simpan'), [
        'jenis' => JenisDaftarPilihan::PrioritasPengaduan->value,
        'nilai' => 'Darurat',
    ])->assertUnprocessable();

    expect(DaftarPilihan::where('nilai', 'Darurat')->exists())->toBeFalse();
});

it('menonaktifkan pilihan tanpa menghapusnya', function () {
    // Tidak ada rute hapus, dan itu disengaja: menghapus membuat data lama
    // menunjuk pilihan yang lenyap, dan rekapnya kehilangan baris itu tanpa
    // pesan apa pun.
    $sumber = DaftarPilihan::where('jenis', JenisDaftarPilihan::SumberDana->value)
        ->where('nilai', 'APBN')->first();

    $this->put(route('daftar-pilihan.perbarui', $sumber->id_daftar_pilihan), [
        'jenis' => JenisDaftarPilihan::SumberDana->value,
        'nilai' => 'APBN',
        'urutan' => $sumber->urutan,
        'is_aktif' => '0',
    ])->assertRedirect(route('daftar-pilihan.jenis', ['jenis' => JenisDaftarPilihan::SumberDana->value]));

    expect(DaftarPilihan::find($sumber->id_daftar_pilihan))->not->toBeNull()
        ->and($sumber->fresh()->is_aktif)->toBeFalse();
});

it('mengunci nilai yang sudah tersimpan sebagai identitas referensi', function () {
    $sumber = DaftarPilihan::where('jenis', JenisDaftarPilihan::SumberDana->value)
        ->where('nilai', 'APBN')->firstOrFail();

    $this->put(route('daftar-pilihan.perbarui', $sumber->id_daftar_pilihan), [
        'jenis' => JenisDaftarPilihan::SumberDana->value,
        'nilai' => 'Anggaran Pendapatan dan Belanja Negara',
        'urutan' => $sumber->urutan,
        'is_aktif' => '1',
    ])->assertSessionHasErrors('nilai');

    expect($sumber->fresh()->nilai)->toBe('APBN');
});

it('mengunci jenis baris yang sudah tersimpan', function () {
    $sumber = DaftarPilihan::where('jenis', JenisDaftarPilihan::SumberDana->value)->firstOrFail();

    $this->put(route('daftar-pilihan.perbarui', $sumber->id_daftar_pilihan), [
        'jenis' => JenisDaftarPilihan::JenisInventaris->value,
        'nilai' => $sumber->nilai,
        'urutan' => $sumber->urutan,
        'is_aktif' => '1',
    ])->assertSessionHasErrors('jenis');
});

it('menolak bidang bawaan yang bukan berasal dari daftar bidang', function () {
    $kategori = DaftarPilihan::where('jenis', JenisDaftarPilihan::KategoriPengaduan->value)->firstOrFail();
    $prioritas = DaftarPilihan::where('jenis', JenisDaftarPilihan::PrioritasPengaduan->value)->firstOrFail();

    $this->put(route('daftar-pilihan.perbarui', $kategori->id_daftar_pilihan), [
        'jenis' => JenisDaftarPilihan::KategoriPengaduan->value,
        'nilai' => $kategori->nilai,
        'urutan' => $kategori->urutan,
        'bidang_id' => $prioritas->id_daftar_pilihan,
        'is_aktif' => '1',
    ])->assertSessionHasErrors('bidang_id');
});

it('mengosongkan skor pada daftar yang memang tak berskor', function () {
    // Skor hanya bermakna bagi jenis `kondisi`. Membiarkannya terbawa pada
    // daftar lain menaruh angka yang tak pernah dibaca siapa pun dan hanya
    // menyesatkan pembaca tabel.
    $this->post(route('daftar-pilihan.simpan'), [
        'jenis' => JenisDaftarPilihan::JenisInventaris->value,
        'nilai' => 'INVENTARIS UJI',
        'nilai_skor' => '0.5',
    ]);

    expect(DaftarPilihan::where('nilai', 'INVENTARIS UJI')->first()?->nilai_skor)->toBeNull();
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
