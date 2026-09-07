<?php

namespace App\Support;

use App\Enums\AksiAuditLog;
use App\Models\AuditLog;
use App\Models\Pengaturan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

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
        'profil.tim' => "Sistem dikembangkan bersama tim peneliti dan pengembang Institut Teknologi Sepuluh Nopember (ITS) Surabaya:\nDr. Budi Setiyono, S.Si., M.T. — Ketua Tim / Peneliti Utama\nLeonardi Paris Hasugian — Anggota Tim Pengembang\nMuhammad Abyan Dzaka — Anggota Tim Pengembang\nReyner Marvi Leiwakabessy — Anggota Tim Pengembang\nMuhammad Rias Ramadan — Anggota Tim Pengembang\nHeaven Happyna Putra Febriyono — Anggota Tim Pengembang",
        'profil.mitra' => "Kementerian Transmigrasi Republik Indonesia — pembina kebijakan nasional ketransmigrasian.\nPemerintah Kabupaten Malaka dan Dinas Transmigrasi — pengelola data kawasan, warga, perumahan, lahan, dan infrastruktur.\nDinas Pertanian Kabupaten Malaka — mitra pembinaan kelompok tani, alsintan, saprotan, dan hasil panen.\nInstitut Teknologi Sepuluh Nopember — mitra riset dan pengembangan sistem.",
        'profil.narahubung' => "Sekretariat Pelaksana: Kantor Dinas Transmigrasi Kabupaten Malaka, Betun, Nusa Tenggara Timur.\nTim Riset dan Pengembang: Institut Teknologi Sepuluh Nopember, Kampus ITS Sukolilo, Surabaya, Jawa Timur.\nSaluran bantuan resmi tercantum pada catatan kaki aplikasi.",
        'profil.faq' => '[{"tanya":"Bagaimana jika terjadi pergantian pengurus atau suksesi Kepala Keluarga?","jawab":"Masuk ke menu Penduduk & Lahan lalu Transmigran, buka rincian transmigran bersangkutan, lalu pilih tombol suksesi. Sistem menyimpan riwayat perubahan pada audit log tanpa menghapus jejak data awal."},{"tanya":"Mengapa tombol Hapus tidak muncul pada akun Operator SP?","jawab":"Operator SP hanya berhak menambah dan memperbarui data lapangan demi mencegah kehilangan data. Penghapusan data master hanya dapat diproses oleh Administrator atau Dinas berwenang."},{"tanya":"Bagaimana alur penanganan pengaduan warga?","jawab":"Pengaduan masuk berstatus Menunggu Diterima, diverifikasi petugas menjadi Diterima, diproses tindak lanjut lapangannya, lalu ditandai Selesai setelah masalah terselesaikan."}]',

        // Panduan tetap: isi dapat disunting, perilaku dan validasi tetap di kode.
        'panduan.peran' => 'Hak akses mengikuti peran dan penugasan pengguna. Menu yang tersedia dapat berbeda untuk setiap petugas; hubungi Administrator bila kewenangan belum sesuai.',
        'panduan.dashboard' => 'Dashboard merangkum data kawasan, kependudukan, pertanian, kondisi SP, dan pengaduan. Gunakan penyaring wilayah serta periode untuk mempersempit analisis.',
        'panduan.wilayah' => 'Kelola kawasan, satuan permukiman, inventaris, fasilitas, dan infrastruktur melalui kelompok menu Wilayah & SP.',
        'panduan.kependudukan' => 'Data transmigran menjadi sumber hubungan keluarga, rumah, dan lahan. Perbarui data dari halaman rincian agar keterkaitannya tetap terjaga.',
        'panduan.pertanian' => 'Catat kelompok tani, bantuan alsintan dan saprotan, penanaman, lalu hasil panen sesuai urutan kejadian lapangan.',
        'panduan.pengaduan' => 'Warga dapat mengirim dan melacak pengaduan tanpa akun. Petugas menindaklanjuti pengaduan melalui menu internal sesuai kewenangannya.',
        'panduan.laporan' => 'Laporan memakai data operasional yang tersimpan. Gunakan filter yang tersedia sebelum membuka dokumen atau mengekspor hasil.',

        // Tab 4 -- Portal Pengaduan Warga
        'portal.sambutan' => 'Sampaikan laporan, kendala pertanian, atau keluhan fasilitas di lingkungan satuan permukiman Anda. Laporan akan ditindaklanjuti langsung oleh dinas terkait.',
        'portal.disclaimer' => 'Identitas pelapor dilindungi dan hanya digunakan untuk keperluan verifikasi lapangan oleh petugas resmi kementerian dan dinas.',
        'portal.awalan_nomor' => 'PGD',
        'portal.hotline' => '0811-2345-6789',
        'portal.alur' => 'Catat atau foto nomor pengaduan setelah laporan dikirim. Petugas akan memeriksa dan menindaklanjutinya, lalu perkembangan dapat dilihat pada halaman pelacakan.',
        'portal.sla' => 'Waktu penanganan mengikuti jenis masalah dan kebutuhan verifikasi lapangan.',
        'portal.pelacakan' => 'Masukkan nomor pengaduan persis seperti yang diterima saat pengiriman. Bila perlu bantuan, sampaikan nomor tersebut melalui hotline layanan.',

        // Tab 5 -- Pengumuman Dinas
        'pengumuman.aktif' => '0',
        'pengumuman.judul' => '',
        'pengumuman.tipe' => 'info',
        'pengumuman.isi' => '',

        // Tab 6 -- Surel Sistem
        'surel.sapaan' => 'Yth.',
        'surel.penutup' => 'Hormat kami,',
        'surel.nama_pengirim' => 'Tim DIGITRANS Kobalima Timur',
        'surel.catatan_kaki' => 'Pesan ini dikirim otomatis oleh sistem. Mohon tidak membalas email ini.',

        // Catatan editorial tambahan; struktur dan formula laporan tetap di kode.
        'laporan.indikator-kawasan.catatan' => '',
        'laporan.monografi-sp.catatan' => '',
        'laporan.transmigran.catatan' => '',
        'laporan.poktan.catatan' => '',
        'laporan.alsintan.catatan' => '',
        'laporan.saprotan.catatan' => '',
        'laporan.hasil-panen.catatan' => '',
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
        $sebelum = self::semua();
        $baru = [];

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
            $baru[$kunci] = $simpan;
        }

        if ($baru !== []) {
            $baru = array_filter($baru, fn ($nilai, $kunci) => ($sebelum[$kunci] ?? null) !== $nilai, ARRAY_FILTER_USE_BOTH);
        }

        if ($baru !== []) {
            $permintaan = request();

            AuditLog::create([
                'user_id' => Auth::id(),
                'aksi' => AksiAuditLog::Ubah,
                'nama_tabel' => 'pengaturan',
                // Satu audit mewakili satu tab, bukan satu row `pengaturan`.
                // ID 0 adalah sentinel agregat; key yang berubah ada di JSON.
                'record_id' => 0,
                'data_lama' => array_intersect_key($sebelum, $baru),
                'data_baru' => $baru,
                'ip_address' => $permintaan->ip(),
                'user_agent' => Str::limit((string) $permintaan->userAgent(), 255, ''),
            ]);
        }

        request()->attributes->remove(self::class);
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

    /** @return array{pusat: string, daerah: string} */
    public static function instansi(): array
    {
        return [
            'pusat' => self::teks('identitas.instansi_pusat'),
            'daerah' => self::teks('identitas.instansi_daerah'),
        ];
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

    /** @return array{latar_belakang: string, tim: string, mitra: string, narahubung: string} */
    public static function halamanTentang(): array
    {
        return [
            'latar_belakang' => self::teks('profil.latar_belakang'),
            'tim' => self::teks('profil.tim'),
            'mitra' => self::teks('profil.mitra'),
            'narahubung' => self::teks('profil.narahubung'),
        ];
    }

    /** @return array<string, string> */
    public static function panduan(): array
    {
        return collect(['peran', 'dashboard', 'wilayah', 'kependudukan', 'pertanian', 'pengaduan', 'laporan'])
            ->mapWithKeys(fn (string $bagian) => [$bagian => self::teks('panduan.'.$bagian)])
            ->all();
    }

    public static function catatanLaporan(string $slug): string
    {
        return self::teks('laporan.'.$slug.'.catatan');
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
     * @return array{sambutan: string, disclaimer: string, hotline: string, alur: string, sla: string, pelacakan: string}
     */
    public static function portal(): array
    {
        return [
            'sambutan' => self::teks('portal.sambutan'),
            'disclaimer' => self::teks('portal.disclaimer'),
            'hotline' => self::teks('portal.hotline'),
            'alur' => self::teks('portal.alur'),
            'sla' => self::teks('portal.sla'),
            'pelacakan' => self::teks('portal.pelacakan'),
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
        $permintaan = request();

        if (! $permintaan->attributes->has(self::class)) {
            $permintaan->attributes->set(self::class, Pengaturan::query()->pluck('nilai', 'kunci')->all());
        }

        return $permintaan->attributes->get(self::class);
    }
}
