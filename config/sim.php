<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pembatasan laju (rate limiting) -- Task 3.10
    |--------------------------------------------------------------------------
    |
    | Batas BERBEDA menurut jenis akses (`rules.md` 14c). Halaman internal
    | dihitung PER AKUN (satu kantor dinas kerap berbagi satu IP); kanal publik
    | per alamat IP. Angka boleh disetel lewat env untuk penyetelan lapangan
    | tanpa ganti kode.
    |
    | `aktif` dimatikan otomatis di lingkungan `testing` (phpunit.xml menyetel
    | SIM_BATAS_LAJU=false) supaya uji penjaga yang menyapu ratusan rute tidak
    | ikut terkena; uji khusus pembatasan laju menyalakannya sendiri lewat
    | config().
    |
    */

    'batas_laju' => [
        'aktif' => (bool) env('SIM_BATAS_LAJU', true),

        // per menit, per akun
        'baca_internal' => (int) env('SIM_BATAS_BACA_INTERNAL', 120),
        'tulis_internal' => (int) env('SIM_BATAS_TULIS_INTERNAL', 40),

        // rute berkas besar (unduh template, dokumen resmi, berkas unggahan):
        // dikecualikan dari batas halaman biasa, diberi batas sendiri
        // (`rules.md` 14c.3 poin 6). per menit, per akun.
        'berkas_besar' => (int) env('SIM_BATAS_BERKAS_BESAR', 30),

        // kanal publik, per alamat IP
        'lacak_publik' => (int) env('SIM_BATAS_LACAK_PUBLIK', 10),   // per menit
        'verifikasi_email' => (int) env('SIM_BATAS_VERIFIKASI_EMAIL', 10), // per menit
        'kirim_pengaduan' => (int) env('SIM_BATAS_KIRIM_PENGADUAN', 3), // per jam
        'pemulihan_sandi' => (int) env('SIM_BATAS_PEMULIHAN_SANDI', 5), // per menit
    ],

    'admin_awal' => [
        'email' => env('SIM_ADMIN_EMAIL', 'admin@malakakab.go.id'),
        'nama' => env('SIM_ADMIN_NAMA', 'ADMINISTRATOR SISTEM'),
        'username' => env('SIM_ADMIN_USERNAME'),
        'password' => env('SIM_ADMIN_PASSWORD'),
        'wajib_ganti' => filter_var(env('SIM_ADMIN_WAJIB_GANTI', true), FILTER_VALIDATE_BOOL),
    ],

];
