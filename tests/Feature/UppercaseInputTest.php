<?php

/*
 * Penjaga `UppercaseInput` terhadap isian PEMILIH (2026-09-03).
 *
 * Middleware ini mengapitalkan seluruh isian teks kecuali yang terdaftar
 * (`rules.md` 13.2 poin 4). Default itu benar untuk isi data -- nama orang,
 * alamat, nama barang -- tetapi SELALU salah untuk isian yang MEMILIH
 * sesuatu: tingkat wilayah, jenis daftar pilihan, nilai enum. Dikapitalkan,
 * nilainya tak lagi cocok dengan daftar yang sah dan seluruh penyimpanan
 * ditolak validasi.
 *
 * Sudah terjadi TIGA KALI berturut-turut: `tingkat` (Task 4.1), `jenis`
 * (Task 4.7), dan `jenis_fasilitas` (Task 4.4). Ketiganya ditemukan setelah
 * ujinya merah, bukan dicegah.
 *
 * Uji ini membalik arahnya: ia menyisir SELURUH controller, mengumpulkan nama
 * isian yang divalidasi terhadap daftar tertutup, lalu menuntut tiap nama itu
 * ada di daftar kecuali. Merah saat controller ditulis, bukan saat data gagal
 * tersimpan di tangan petugas.
 *
 * Titik buta yang DISENGAJA (perlu penambahan manual ke `$kecualikan`):
 * - `Rule::exists('daftar_pilihan', 'nilai')` yang ditulis inline, bukan lewat
 *   `ValidationRules::daftarPilihan()`;
 * - kunci bertitik seperti `izin.*.*` (regexnya hanya `[a-z_]+`);
 * - aturan `Rule::enum`/`Rule::in` yang jatuh ke baris berikutnya setelah
 *   isi kurung siku lain;
 * - pemilih yang untuk sementara divalidasi sebagai string bebas (mis.
 *   `pola_permukiman`), yang enum-nya belum dipakai.
 */

use App\Http\Middleware\UppercaseInput;

/**
 * Nama isian yang divalidasi terhadap DAFTAR TERTUTUP pada seluruh controller.
 *
 * Dibaca dari sumbernya langsung, bukan ditulis tangan: isian pemilih baru
 * otomatis ikut terperiksa tanpa siapa pun perlu mengingat menambahkannya.
 *
 * @return array<string, string> nama isian => berkas tempat ia ditemukan
 */
function isianPemilih(): array
{
    $hasil = [];

    $berkas = array_merge(
        glob(app_path('Http/Controllers/*.php')) ?: [],
        glob(app_path('Http/Controllers/*/*.php')) ?: [],
    );

    foreach ($berkas as $satu) {
        $isi = file_get_contents($satu);

        // `nama_isian` => [ ... Rule::enum(...) / Rule::in(...) /
        // ValidationRules::daftarPilihan(...) ... ] pada larik aturan validasi.
        $pola = "/'([a-z_]+)'\s*=>\s*(?:\[[^\]]*?)?(?:Rule::(?:enum|in)\(|ValidationRules::daftarPilihan\()/s";

        preg_match_all($pola, $isi, $cocok);

        foreach ($cocok[1] as $nama) {
            $hasil[$nama] ??= basename($satu);
        }
    }

    return $hasil;
}

it('mengecualikan setiap isian pemilih dari kapitalisasi otomatis', function () {
    $bawaan = (new ReflectionClass(UppercaseInput::class))->getDefaultProperties();
    $kecuali = $bawaan['kecualikan'];
    $akhiran = $bawaan['kecualikanAkhiran'];

    $bocor = [];

    foreach (isianPemilih() as $nama => $berkas) {
        if (in_array($nama, $kecuali, true)) {
            continue;
        }

        // Kolom berakhiran `_id` sudah aman lewat daftar akhiran.
        foreach ($akhiran as $akhir) {
            if (str_ends_with($nama, $akhir)) {
                continue 2;
            }
        }

        $bocor[] = $nama.' ('.$berkas.')';
    }

    $pesan = 'Isian berikut divalidasi terhadap daftar tertutup tetapi TIDAK '
        .'dikecualikan dari UppercaseInput, sehingga nilainya akan dikapitalkan '
        .'lalu tersimpan dengan kapitalisasi berbeda dari daftar pilihannya. '
        .'Tambahkan ke UppercaseInput::$kecualikan: '.implode(', ', $bocor);

    $this->assertSame([], $bocor, $pesan);
});

it('menemukan isian pemilih yang memang ada, bukan larik kosong', function () {
    // Penjaga bagi penjaganya sendiri: bila regexnya berhenti cocok karena
    // gaya penulisan controller berubah, uji di atas akan HIJAU selamanya
    // tanpa memeriksa apa pun.
    $ditemukan = isianPemilih();

    expect($ditemukan)->not->toBeEmpty()
        ->and(array_keys($ditemukan))->toContain('jenis_fasilitas')
        ->and(array_keys($ditemukan))->toContain('kondisi');
});
