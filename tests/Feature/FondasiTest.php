<?php

/**
 * Uji fondasi Tahap 1: aturan validasi, middleware huruf kapital, dan
 * penyimpanan dokumen. Ketiganya dipakai hampir seluruh modul, sehingga
 * kesalahan di sini akan menular ke mana-mana.
 */

use App\Http\Middleware\UppercaseInput;
use App\Support\PenyimpananDokumen;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| ValidationRules
|--------------------------------------------------------------------------
*/

it('menerima NIK 16 digit dan menolak yang lain', function () {
    $aturan = ['nik' => ['required', 'digits:16']];

    expect(Validator::make(['nik' => '5321011505800001'], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['nik' => '532101150580'], $aturan)->passes())->toBeFalse();
    expect(Validator::make(['nik' => '53210115058000012'], $aturan)->passes())->toBeFalse();
    expect(Validator::make(['nik' => 'abcdefghijklmnop'], $aturan)->passes())->toBeFalse();
});

it('menolak nama yang mengandung angka', function () {
    $aturan = ['nama' => ValidationRules::nama()];

    expect(Validator::make(['nama' => 'Yohanes Bere'], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['nama' => "Maria D'Souza"], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['nama' => 'Yohanes 123'], $aturan)->passes())->toBeFalse();
    expect(Validator::make(['nama' => 'Yo'], $aturan)->passes())->toBeFalse();
});

it('menerima nomor telepon Indonesia yang sah', function () {
    $aturan = ['telepon' => ValidationRules::telepon()];

    expect(Validator::make(['telepon' => '081234567890'], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['telepon' => '+6281234567890'], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['telepon' => null], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['telepon' => '12345'], $aturan)->passes())->toBeFalse();
    expect(Validator::make(['telepon' => '0812'], $aturan)->passes())->toBeFalse();
});

it('membatasi volume panen sampai tiga angka desimal', function () {
    $aturan = ['volume' => ValidationRules::volume()];

    expect(Validator::make(['volume' => '12.5'], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['volume' => '0.001'], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['volume' => '0'], $aturan)->passes())->toBeFalse();
    expect(Validator::make(['volume' => '12.5001'], $aturan)->passes())->toBeFalse();
});

it('membatasi koordinat pada rentang yang masuk akal', function () {
    expect(Validator::make(['lintang' => '-9.512345'], ['lintang' => ValidationRules::lintang()])->passes())->toBeTrue();
    expect(Validator::make(['lintang' => '95'], ['lintang' => ValidationRules::lintang()])->passes())->toBeFalse();
    expect(Validator::make(['bujur' => '124.912345'], ['bujur' => ValidationRules::bujur()])->passes())->toBeTrue();
    expect(Validator::make(['bujur' => '200'], ['bujur' => ValidationRules::bujur()])->passes())->toBeFalse();
});

it('menuntut kata sandi memuat huruf dan angka', function () {
    $aturan = ['password' => ValidationRules::password()];

    expect(Validator::make(
        ['password' => 'rahasia123', 'password_confirmation' => 'rahasia123'],
        $aturan
    )->passes())->toBeTrue();

    // Hanya huruf, tanpa angka
    expect(Validator::make(
        ['password' => 'rahasiaku', 'password_confirmation' => 'rahasiaku'],
        $aturan
    )->passes())->toBeFalse();

    // Kurang dari 8 karakter
    expect(Validator::make(
        ['password' => 'abc123', 'password_confirmation' => 'abc123'],
        $aturan
    )->passes())->toBeFalse();
});

it('menolak username yang memuat huruf besar atau spasi', function () {
    $aturan = ['username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9._]+$/']];

    expect(Validator::make(['username' => 'operator.kapitanmeo'], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['username' => 'operator_sp1'], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['username' => 'Operator SP'], $aturan)->passes())->toBeFalse();
    expect(Validator::make(['username' => 'OPERATOR'], $aturan)->passes())->toBeFalse();
});

it('membatasi ukuran unggahan sampai 5 MB', function () {
    expect(ValidationRules::MAKS_UKURAN_BERKAS_KB)->toBe(5120);

    $aturan = ['dokumen' => ValidationRules::dokumen()];

    $kecil = UploadedFile::fake()->create('kk.pdf', 1000, 'application/pdf');
    $besar = UploadedFile::fake()->create('kk.pdf', 6000, 'application/pdf');

    expect(Validator::make(['dokumen' => $kecil], $aturan)->passes())->toBeTrue();
    expect(Validator::make(['dokumen' => $besar], $aturan)->passes())->toBeFalse();
});

it('menyediakan pesan galat berbahasa Indonesia', function () {
    $pesan = ValidationRules::pesan();

    expect($pesan)->toHaveKey('nik.digits')
        ->and($pesan['nik.digits'])->toBe('NIK harus 16 digit angka.')
        ->and($pesan['volume.gt'])->toContain('Volume panen')
        ->and($pesan['dokumen.max'])->toContain('5 MB');

    // Seluruh pesan wajib berbahasa Indonesia, tanpa sisa teks bawaan Laravel
    foreach ($pesan as $kunci => $isi) {
        expect($isi)->not->toContain('The ')
            ->and($isi)->not->toContain(' field ');
    }
});

/*
|--------------------------------------------------------------------------
| UppercaseInput
|--------------------------------------------------------------------------
*/

/**
 * Menjalankan middleware terhadap sekumpulan isian, lalu mengembalikan hasilnya.
 *
 * @param  array<string, mixed>  $data  Isian yang diuji
 * @return array<string, mixed> Isian setelah diproses middleware
 */
function jalankanMiddleware(array $data): array
{
    $request = Request::create('/uji', 'POST', $data);
    $hasil = [];

    (new UppercaseInput())->handle($request, function (Request $r) use (&$hasil) {
        $hasil = $r->all();

        return response('');
    });

    return $hasil;
}

it('mengubah isian teks biasa menjadi huruf kapital', function () {
    $hasil = jalankanMiddleware([
        'nama_kepala_keluarga' => 'yohanes bere',
        'pekerjaan_kepala_keluarga' => 'petani',
    ]);

    expect($hasil['nama_kepala_keluarga'])->toBe('YOHANES BERE')
        ->and($hasil['pekerjaan_kepala_keluarga'])->toBe('PETANI');
});

it('tidak mengubah kata sandi, surel, dan username', function () {
    $hasil = jalankanMiddleware([
        'password' => 'RahasiaKu123',
        'email' => 'operator@malaka.go.id',
        'username' => 'operator.kapitanmeo',
        'kredensial' => 'operator@malaka.go.id',
    ]);

    expect($hasil['password'])->toBe('RahasiaKu123')
        ->and($hasil['email'])->toBe('operator@malaka.go.id')
        ->and($hasil['username'])->toBe('operator.kapitanmeo')
        ->and($hasil['kredensial'])->toBe('operator@malaka.go.id');
});

it('tidak mengubah teks naratif agar tetap mudah dibaca', function () {
    $hasil = jalankanMiddleware([
        'deskripsi' => 'Saluran irigasi tersumbat sejak musim hujan.',
        'catatan' => 'Perlu ditinjau ulang.',
        'keterangan' => 'Data dari kunjungan lapangan.',
    ]);

    expect($hasil['deskripsi'])->toBe('Saluran irigasi tersumbat sejak musim hujan.')
        ->and($hasil['catatan'])->toBe('Perlu ditinjau ulang.')
        ->and($hasil['keterangan'])->toBe('Data dari kunjungan lapangan.');
});

it('tidak mengubah kolom berakhiran _id dan _at', function () {
    $hasil = jalankanMiddleware([
        'satuan_permukiman_id' => 'abc123',
        'created_at' => '2026-08-11 10:00:00',
    ]);

    expect($hasil['satuan_permukiman_id'])->toBe('abc123')
        ->and($hasil['created_at'])->toBe('2026-08-11 10:00:00');
});

it('memproses isian bersarang sampai ke dalam', function () {
    $hasil = jalankanMiddleware([
        'anggota' => [
            ['nama' => 'maria bere', 'email' => 'maria@desa.id'],
        ],
    ]);

    expect($hasil['anggota'][0]['nama'])->toBe('MARIA BERE')
        ->and($hasil['anggota'][0]['email'])->toBe('maria@desa.id');
});

it('membersihkan spasi berlebih di awal dan akhir', function () {
    $hasil = jalankanMiddleware(['nama' => '   yohanes bere   ']);

    expect($hasil['nama'])->toBe('YOHANES BERE');
});

it('melewatkan permintaan GET tanpa perubahan', function () {
    $request = Request::create('/uji?nama=yohanes', 'GET');
    $hasil = [];

    (new UppercaseInput())->handle($request, function (Request $r) use (&$hasil) {
        $hasil = $r->all();

        return response('');
    });

    expect($hasil['nama'])->toBe('yohanes');
});

/*
|--------------------------------------------------------------------------
| PenyimpananDokumen
|--------------------------------------------------------------------------
*/

it('menyusun nama berkas sesuai pola yang disepakati', function () {
    $berkas = UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf');

    expect(PenyimpananDokumen::susunNamaBerkas($berkas, 'Kartu Keluarga', 'Yohanes Bere'))
        ->toBe('KartuKeluarga_yohanes-bere.pdf');

    // Singkatan seperti HPL wajib tetap kapital, tidak boleh jadi "Hpl"
    expect(PenyimpananDokumen::susunNamaBerkas($berkas, 'SertifikatHPL', 'Maria Da Costa'))
        ->toBe('SertifikatHPL_maria-da-costa.pdf');

    // Tanpa nama pemilik, hanya jenis dokumennya
    expect(PenyimpananDokumen::susunNamaBerkas($berkas, 'SK Penetapan'))
        ->toBe('SKPenetapan.pdf');
});

it('menyusun folder penyimpanan per modul dan id', function () {
    expect(PenyimpananDokumen::folder('transmigran', 12))->toBe('transmigran/12');
    expect(PenyimpananDokumen::folder('kawasan_transmigrasi', 1))->toBe('kawasan-transmigrasi/1');
});

it('memakai disk privat dan batas 5 MB', function () {
    expect(PenyimpananDokumen::DISK)->toBe('local');
    expect(PenyimpananDokumen::MAKS_UKURAN_BYTE)->toBe(5 * 1024 * 1024);
});

it('menyimpan berkas ke disk privat lalu menghapusnya', function () {
    Illuminate\Support\Facades\Storage::fake('local');

    $berkas = UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf');

    $path = PenyimpananDokumen::simpan($berkas, 'transmigran', 12, 'KartuKeluarga', 'Yohanes Bere');

    expect($path)->toBe('transmigran/12/KartuKeluarga_yohanes-bere.pdf')
        ->and(PenyimpananDokumen::ada($path))->toBeTrue();

    PenyimpananDokumen::hapus($path);

    expect(PenyimpananDokumen::ada($path))->toBeFalse();
});

it('menghapus berkas lama saat diganti', function () {
    Illuminate\Support\Facades\Storage::fake('local');

    $lama = UploadedFile::fake()->create('lama.pdf', 100, 'application/pdf');
    $pathLama = PenyimpananDokumen::simpan($lama, 'transmigran', 5, 'KartuKeluarga', 'Maria Bere');

    $baru = UploadedFile::fake()->image('baru.jpg');
    $pathBaru = PenyimpananDokumen::ganti($baru, $pathLama, 'transmigran', 5, 'KartuKeluarga', 'Maria Bere');

    expect($pathBaru)->toBe('transmigran/5/KartuKeluarga_maria-bere.jpg')
        ->and(PenyimpananDokumen::ada($pathBaru))->toBeTrue()
        ->and(PenyimpananDokumen::ada($pathLama))->toBeFalse();
});

it('menganggap path kosong sebagai tidak ada berkas', function () {
    Illuminate\Support\Facades\Storage::fake('local');

    expect(PenyimpananDokumen::ada(null))->toBeFalse()
        ->and(PenyimpananDokumen::ada(''))->toBeFalse()
        ->and(PenyimpananDokumen::hapus(null))->toBeTrue();
});
