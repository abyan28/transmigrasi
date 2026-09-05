<?php

namespace App\Support;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Config;

/**
 * Pengelolaan Konten Sistem (Task 9.6) -- pembaca terpusat tabel `pengaturan`.
 *
 * Satu-satunya jalur baca/tulis konten CMS. Menyediakan BAWAAN (dipakai bila
 * dinas belum mengisi), penafsiran `tipe`, dan pengingat selama satu permintaan
 * supaya satu halaman tidak menanyakan tabel berkali-kali.
 *
 * Nama aplikasi tetap jatuh ke `config('app.name')` bila CMS kosong (`tasklist`
 * Task 9.6), sehingga pemasangan baru tetap punya identitas tanpa mengisi CMS.
 *
 * Berkas (logo/favicon) BELUM dikelola di sini: berkas fisik wajib di cakram
 * privat (`rules.md` 14a) sedangkan logo publik butuh jalur serba-boleh
 * tersendiri -- ditunda, aset bundel tetap dipakai.
 */
class KontenSistem
{
    /**
     * Bawaan tiap kunci. Nilai awal = teks mockup Task 2.31 supaya tampilan
     * tidak berubah sebelum dinas menyuntingnya.
     *
     * @var array<string, string>
     */
    private const BAWAAN = [
        // Tab 1 -- Identitas & Visual. Bawaan `identitas.nama_app` di-override
        // `bawaan()` menjadi `config('app.name')` -- pemasangan baru tetap
        // memakai identitas dari env sampai CMS disunting.
        'identitas.nama_app' => 'DIGITRANS',
        'identitas.subjudul' => 'Kawasan Transmigrasi Kobalima Timur - Kabupaten Malaka, Provinsi Nusa Tenggara Timur',
        'identitas.instansi_pusat' => 'Kementerian Transmigrasi Republik Indonesia',
        'identitas.instansi_daerah' => 'Dinas Transmigrasi dan Tenaga Kerja Kabupaten Malaka',
        'identitas.email_bantuan' => 'helpdesk@transmigrasi.malakakab.go.id',
        'identitas.telepon_bantuan' => '(0389) 21004',
        'identitas.wa_bantuan' => '0812-3456-7890',
        'identitas.footer' => 'Kementerian Transmigrasi Republik Indonesia bersama Pemerintah Kabupaten Malaka. Dikembangkan bersama Institut Teknologi Sepuluh Nopember (ITS).',

        // Tab 2 -- Kop & Dokumen Laporan
        'kop.kementerian' => 'Kementerian Transmigrasi Republik Indonesia',
        'kop.pemerintah' => 'Pemerintah Kabupaten Malaka',
        'kop.dinas' => 'Dinas Transmigrasi dan Tenaga Kerja Kabupaten Malaka',
        'kop.alamat' => 'Jalan Raya Betun, Kompleks Perkantoran Pemerintah Daerah Kab. Malaka, Nusa Tenggara Timur',
        'kop.kontak' => 'Telepon (0389) 123456  |  Email distrans@malakakab.go.id',
        'kop.tampilkan_ttd' => '1',
        'kop.titimangsa_tempat' => 'Betun',
        'kop.ttd_jabatan' => 'Kepala Dinas Transmigrasi dan Tenaga Kerja Kabupaten Malaka',
        'kop.ttd_nama' => 'Drs. Agustinus Nahak, M.Si.',
        'kop.ttd_pangkat' => 'Pembina Utama Muda (IV/c)',
        'kop.ttd_nip' => '19750812 199903 1 004',

        // Tab 3 -- Konten Profil & FAQ
        'profil.latar_belakang' => 'Kawasan Transmigrasi Kobalima Timur memiliki potensi agroekologis yang strategis dengan komoditas unggulan utama tanaman jagung, padi, palawija, dan hortikultura. Sistem informasi ini dikembangkan sebagai basis data terpadu untuk mendigitalisasi pemantauan kondisi kependudukan, penguasaan lahan usaha dan pekarangan, sarana produksi, bantuan alat mesin pertanian, realisasi penanaman, serta hasil panen secara transparan dan akuntabel.',
        'profil.faq' => '[{"tanya":"Bagaimana jika terjadi pergantian pengurus atau suksesi Kepala Keluarga?","jawab":"Masuk ke menu Penduduk & Lahan lalu Transmigran, buka rincian transmigran bersangkutan, lalu pilih tombol suksesi. Sistem menyimpan riwayat perubahan pada audit log tanpa menghapus jejak data awal."},{"tanya":"Mengapa tombol Hapus tidak muncul pada akun Operator SP?","jawab":"Operator SP hanya berhak menambah dan memperbarui data lapangan demi mencegah kehilangan data. Penghapusan data master hanya dapat diproses oleh Administrator atau Dinas berwenang."},{"tanya":"Bagaimana alur penanganan pengaduan warga?","jawab":"Pengaduan masuk berstatus Menunggu Diterima, diverifikasi petugas menjadi Diterima, diproses tindak lanjut lapangannya, lalu ditandai Selesai setelah masalah terselesaikan."}]',

        // Tab 4 -- Portal Pengaduan Warga
        'portal.sambutan' => 'Sampaikan laporan, kendala pertanian, atau keluhan fasilitas di lingkungan satuan permukiman Anda. Laporan akan ditindaklanjuti langsung oleh dinas terkait.',
        'portal.disclaimer' => 'Identitas pelapor dilindungi dan hanya digunakan untuk keperluan verifikasi lapangan oleh petugas resmi kementerian dan dinas.',
        'portal.awalan_nomor' => 'PGD',
        'portal.hotline' => '0811-2345-6789',

        // Tab 5 -- Pengumuman Dinas
        'pengumuman.aktif' => '0',
        'pengumuman.judul' => '',
        'pengumuman.tipe' => 'info',
        'pengumuman.isi' => '',

        // Tab 6 -- Surel Sistem
        'surel.sapaan' => 'Yth.',
        'surel.penutup' => 'Hormat kami,',
        'surel.nama_pengirim' => 'Tim DIGITRANS Kobalima Timur',
        'surel.catatan_kaki' => 'Pesan ini dikirim otomatis oleh sistem. Mohon tidak membalas surel ini.',
    ];

    /** Kunci ber-tipe boolean (disimpan '1'/'0'). */
    private const BOOLEAN = ['kop.tampilkan_ttd', 'pengumuman.aktif'];

    /** Kunci ber-tipe JSON. */
    private const JSON = ['profil.faq'];

    /**
     * Nilai satu kunci -- boolean/array untuk kunci ber-tipe khusus, string
     * selain itu. Jatuh ke BAWAAN bila belum diisi.
     */
    public static function ambil(string $kunci): mixed
    {
        $mentah = self::mentah()[$kunci] ?? self::bawaan($kunci);

        if (in_array($kunci, self::BOOLEAN, true)) {
            return (string) $mentah === '1';
        }

        if (in_array($kunci, self::JSON, true)) {
            $urai = json_decode((string) ($mentah ?? '[]'), true);

            return is_array($urai) ? $urai : [];
        }

        return (string) ($mentah ?? '');
    }

    public static function teks(string $kunci): string
    {
        $nilai = self::ambil($kunci);

        return is_string($nilai) ? $nilai : '';
    }

    /**
     * Bawaan satu kunci. Sama dengan `self::BAWAAN` kecuali `identitas.nama_app`
     * yang jatuh ke `config('app.name')`.
     */
    private static function bawaan(string $kunci): ?string
    {
        if ($kunci === 'identitas.nama_app') {
            return (string) Config::get('app.name', 'DIGITRANS');
        }

        return self::BAWAAN[$kunci] ?? null;
    }

    /**
     * Seluruh nilai mentah (string) tergabung bawaan -- untuk mengisi form CMS.
     *
     * @return array<string, string>
     */
    public static function semua(): array
    {
        $bawaan = self::BAWAAN;
        $bawaan['identitas.nama_app'] = self::bawaan('identitas.nama_app');

        return array_merge($bawaan, self::mentah());
    }

    /**
     * @param  array<string, mixed>  $data  kunci => nilai
     */
    public static function simpan(array $data): void
    {
        foreach ($data as $kunci => $nilai) {
            if (! array_key_exists($kunci, self::BAWAAN)) {
                continue;
            }

            $tipe = 'teks';
            $simpan = $nilai;

            if (in_array($kunci, self::BOOLEAN, true)) {
                $tipe = 'boolean';
                $simpan = $nilai ? '1' : '0';
            } elseif (in_array($kunci, self::JSON, true)) {
                $tipe = 'json';
                $simpan = json_encode(array_values((array) $nilai), JSON_UNESCAPED_UNICODE);
            } else {
                $simpan = (string) ($nilai ?? '');
            }

            Pengaturan::updateOrCreate(['kunci' => $kunci], ['nilai' => $simpan, 'tipe' => $tipe]);
        }
    }

    /*
    |------------------------------------------------------------------
    | Pembaca semantik untuk pemakainya
    |------------------------------------------------------------------
    */

    public static function namaAplikasi(): string
    {
        $nama = self::teks('identitas.nama_app');

        return $nama !== '' ? $nama : (string) Config::get('app.name', 'DIGITRANS');
    }

    public static function subjudul(): string
    {
        return self::teks('identitas.subjudul');
    }

    public static function footer(): string
    {
        return self::teks('identitas.footer');
    }

    /**
     * @return array{email: string, telepon: string, wa: string}
     */
    public static function kontakBantuan(): array
    {
        return [
            'email' => self::teks('identitas.email_bantuan'),
            'telepon' => self::teks('identitas.telepon_bantuan'),
            'wa' => self::teks('identitas.wa_bantuan'),
        ];
    }

    /**
     * Identitas kop dokumen laporan (dipakai `LaporanData::instansi()`).
     *
     * @return array<string, string>
     */
    public static function kop(): array
    {
        return [
            'kementerian' => self::teks('kop.kementerian'),
            'pemerintah' => self::teks('kop.pemerintah'),
            'dinas' => self::teks('kop.dinas'),
            'alamat' => self::teks('kop.alamat'),
            'kontak' => self::teks('kop.kontak'),
        ];
    }

    /**
     * @return array{tampilkan: bool, tempat: string, jabatan: string, nama: string, pangkat: string, nip: string}
     */
    public static function ttd(): array
    {
        return [
            'tampilkan' => (bool) self::ambil('kop.tampilkan_ttd'),
            'tempat' => self::teks('kop.titimangsa_tempat'),
            'jabatan' => self::teks('kop.ttd_jabatan'),
            'nama' => self::teks('kop.ttd_nama'),
            'pangkat' => self::teks('kop.ttd_pangkat'),
            'nip' => self::teks('kop.ttd_nip'),
        ];
    }

    public static function tentang(): string
    {
        return self::teks('profil.latar_belakang');
    }

    /**
     * @return list<array{tanya: string, jawab: string}>
     */
    public static function faq(): array
    {
        $daftar = self::ambil('profil.faq');

        return collect(is_array($daftar) ? $daftar : [])
            ->map(fn ($f) => [
                'tanya' => (string) ($f['tanya'] ?? ''),
                'jawab' => (string) ($f['jawab'] ?? ''),
            ])
            ->filter(fn ($f) => $f['tanya'] !== '' || $f['jawab'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array{sambutan: string, disclaimer: string, hotline: string}
     */
    public static function portal(): array
    {
        return [
            'sambutan' => self::teks('portal.sambutan'),
            'disclaimer' => self::teks('portal.disclaimer'),
            'hotline' => self::teks('portal.hotline'),
        ];
    }

    /**
     * Awalan nomor pengaduan (bagian acak SELALU ditambahkan sistem, di luar
     * kendali CMS -- `rules.md` 4a). Huruf besar, hanya A-Z, 2-6 karakter.
     */
    public static function awalanNomorPengaduan(): string
    {
        $awalan = strtoupper(preg_replace('/[^A-Za-z]/', '', self::teks('portal.awalan_nomor')));

        return $awalan !== '' ? substr($awalan, 0, 6) : 'PGD';
    }

    /**
     * Banner pengumuman dasbor, atau null bila dinonaktifkan / kosong.
     *
     * @return array{judul: string, isi: string, tipe: string}|null
     */
    public static function pengumuman(): ?array
    {
        if (! self::ambil('pengumuman.aktif')) {
            return null;
        }

        $judul = self::teks('pengumuman.judul');
        $isi = self::teks('pengumuman.isi');

        if ($judul === '' && $isi === '') {
            return null;
        }

        $tipe = self::teks('pengumuman.tipe');

        return [
            'judul' => $judul,
            'isi' => $isi,
            'tipe' => in_array($tipe, ['info', 'success', 'warning', 'error'], true) ? $tipe : 'info',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function mentah(): array
    {
        return Pengaturan::query()->pluck('nilai', 'kunci')->all();
    }
}
