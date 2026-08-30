# tasklist.md
## Daftar Tugas — Sistem Informasi Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur

**Progress: 84%**
*(Tahap 0 selesai 8 task. **Tahap 1 SELESAI** 12 task. **TAHAP 2 SELESAI SELURUHNYA.** Gelombang 1 dan 2 tuntas, 32 halaman berdiri. **Delivery Gate kedua gelombang sudah dijalankan** dan laporannya lengkap (`delivery-gate-gelombang-1.md` dan `-2.md`). Dua hal ditunda beralasan, bukan lolos diam-diam: keadaan memuat dan galat menunggu backend Tahap 3, dan pemeriksaan 360px pada perangkat nyata menunggu manusia. Siap masuk checkpoint validasi bersama tim dan dinas, lalu Tahap 3.)*

Acuan: `prd.md`, `rules.md`, `workflow.md`, `ui-spec.md`, `erd.md`, `data-dictionary.md`, `notes.md`.
Cara pakai: kerjakan satu task sampai tuntas, tandai `[✓]` + ✅, catat file yang dibuat/diubah, perbarui persentase progres, lalu beri ringkasan. Lihat `rules.md` §20.

**Strategi: frontend lebih dahulu.** Tahap 2 membangun seluruh antarmuka dengan data dummy, dipecah menjadi **dua gelombang** agar hasil gelombang pertama dapat divalidasi bersama pengguna sebelum 31 halaman sisanya dibangun. Backend menyusul pada Tahap 3–9 dan hanya menukar sumber data, tanpa mengubah tampilan.

**Keputusan teknis yang mengikat seluruh task:**

| Aspek | Keputusan |
|---|---|
| PHP | 8.2.12 (XAMPP), bukan 8.5 yang ada di PATH |
| Laravel | 12.x |
| Fondasi UI | TailAdmin Laravel (MIT), di-clone lalu dibersihkan |
| Tailwind | v4 — token di `resources/css/app.css` (`@theme`), **bukan** `tailwind.config.js` |
| Font | Outfit (bawaan TailAdmin) |
| Basis data | MySQL/MariaDB XAMPP, nama `sim_transmigrasi` |
| PK / FK | `id_transmigran` / `transmigran_id` |
| Koordinat | `lintang` + `bujur` DECIMAL(10,7), bukan GEOMETRY |

Keterangan tingkat kesulitan: `[Mudah]` `[Sedang]` `[Sulit]`

---

## Tahap 0 — Penyusunan Dokumen Acuan ✅ SELESAI

- [✓] ✅ Task 0.1 - Membaca dan mengekstrak isi proposal PDF `[Sedang]` (Selesai)
  * Sumber: `docs/Revisi_Proposal_Budi_TEP ITS 2026_Kobalima_Timur_Upload_10_6_2026_a.pdf`
  * Ekstraksi teks 40 content stream memakai PowerShell + `System.IO.Compression.DeflateStream`

- [✓] ✅ Task 0.2 - Membandingkan `prd.md`, `rules.md`, `workflow.md` dengan proposal `[Sedang]` (Selesai)
  * Ditemukan 12 gap dan 2 masalah struktural (pembagian fase bergeser, artefak sitasi ChatGPT)

- [✓] ✅ Task 0.3 - Sinkronisasi awal ketiga dokumen dengan proposal `[Sedang]` (Selesai)
  * Mengubah `agents/prd.md` — tambah lokus, modul penghuni, geotagging, satuan panen, indikator kuantitatif Tabel 7
  * Mengubah `agents/rules.md` — tambah §4a data master wilayah, §10a penghuni, §16 pengujian
  * Menulis ulang `agents/workflow.md` — hapus ±20 artefak `filecite`, luruskan Fase 1–4 sesuai Tabel 5 proposal

- [✓] ✅ Task 0.4 - Menyelaraskan `rules.md` dan `workflow.md` dengan PRD hasil revisi user `[Sulit]` (Selesai)
  * Mengubah `agents/prd.md` — role mengikuti SQL, modul Pengaduan (§7.13), infrastruktur jadi pendataan aset, entitas dan indikator diperbarui
  * Mengubah `agents/rules.md` — tambah §4b inventaris/fasilitas SP, §6a rumah, §7a poktan, §7b alsintan, §7c saprotan, §10b pengaduan, §13.2 konvensi UI/UX, §14a file upload, §19 penulisan kode, §20 tasklist AI
  * Mengubah `agents/workflow.md` — 13 workflow modul, 5 workflow role baru, §11 workflow pengerjaan AI

- [✓] ✅ Task 0.5 - Menyusun catatan teknis `[Sedang]` (Selesai)
  * Membuat file `agents/notes.md` — 9 koreksi FK, revisi tipe data, ENUM wilayah, duplikasi status pengaduan, field yang belum ada, catatan lembar pengesahan proposal

- [✓] ✅ Task 0.6 - Menyusun tasklist proyek `[Mudah]` (Selesai)
  * Membuat file `agents/tasklist.md`

- [✓] ✅ Task 0.7 - Menetapkan kardinalitas relasi dan aturan satuan panen `[Sedang]` (Selesai)
  * Mengubah `agents/prd.md` — §7.3 kardinalitas, §7.4 lahan jamak, §7.5 satuan baku komoditas, §7.6 konversi ton, §7.7a satu rumah satu KK, §9 entitas satuan
  * Mengubah `agents/rules.md` — §6.4 kardinalitas, §6a UNIQUE dua arah + riwayat penghunian, §7 lahan one-to-many, §8 satuan baku, §8a data master satuan, §9 konversi rekap
  * Mengubah `agents/workflow.md` — §4.4 pemeriksaan rumah kosong, §4.5 lahan jamak, §4.9 penetapan satuan, §4.10 konversi saat rekap
  * Mengubah `agents/notes.md` — koreksi FK lahan dan rumah, tipe `DECIMAL(12,3)`, bagian 1.4a tabel `satuan`, 3 keputusan baru

- [✓] ✅ Task 0.8 - Menyusun spesifikasi antarmuka dan identitas visual `[Sulit]` (Selesai)
  * Membuat file `agents/ui-spec.md` — stack, design token, 43 halaman + rute, menu 5 role, 8 komponen bersama, pola state, aturan responsif, spesifikasi 14 indikator dashboard, konvensi format
  * Ekstraksi palet dari logo resmi: navy `#163B54`, teal `#33809C`, sand `#DFB87E`, gold `#C09546` + skala Tailwind 11 tingkat
  * Uji kontras WCAG — temuan: `gold-500` gagal untuk teks putih (2,75), wajib pakai `gold-700`
  * Mengubah `agents/rules.md` — §5.1 matriks kewenangan 22 modul × 5 role, §13.2 ditulis ulang sebagai spesifikasi, §13.3 aturan tampilan
  * Mengubah `agents/prd.md` — §18 diselaraskan, §19 stack frontend dan identitas visual
  * Mengubah `agents/workflow.md` — Fase 2 disusun ulang jadi frontend-first (Langkah A/B/C)

---

## Tahap 1 — Fondasi Proyek ✅ SELESAI

- [✓] ✅ Task 1.1 - Revisi ERD berdasarkan `notes.md` bagian 1 `[Sulit]` (Selesai)
  * Membuat file `agents/erd.md` — skema final 27 tabel, 41 relasi, 10 aturan integritas, urutan migration, seeder awal
  * Memperbaiki 11 arah FK terbalik, menghapus tabel `pertanian`, menormalisasi `komoditas`
  * `lahan.transmigran_id` (one-to-many), `rumah.transmigran_id` UNIQUE nullable (one-to-one)
  * 11 temuan tambahan di luar `notes.md`, terdokumentasi pada §8.2
- [✓] ✅ Task 1.2 - Ganti ENUM wilayah menjadi tabel referensi bertingkat `[Sedang]` (Selesai)
  * Tercakup dalam `agents/erd.md` §3 — tabel `provinsi`, `kabupaten`, `kecamatan`, `desa`
  * UNIQUE gabungan berjenjang agar nama wilayah tak dobel
- [✓] ✅ Task 1.2b - Hierarki wilayah bercabang dua + tabel `kawasan_transmigrasi` `[Sedang]` (Selesai)
  * Hierarki final: `provinsi → kabupaten` lalu bercabang ke `kecamatan → desa` (administratif) dan `kawasan_transmigrasi` (program), bertemu di `satuan_permukiman`
  * `satuan_permukiman` menyimpan `desa_id` **dan** `kawasan_id`; `kecamatan_id` sengaja tidak disimpan karena terbaca lewat desa
  * Mengubah `erd.md` — 27→28 tabel, 41→43 relasi, diagram hierarki, urutan migration 25→29 langkah, temuan §8.2 nomor 26
  * Mengubah `data-dictionary.md` — §3.5 `kawasan_transmigrasi` baru, SP jadi §3.6
  * Mengubah `rules.md` §4a, `prd.md` §4a/§7.2/§9, `workflow.md` §4.1, `ui-spec.md` §6.5
- [✓] ✅ Task 1.3 - Susun data dictionary `[Sedang]` (Selesai)
  * Membuat file `agents/data-dictionary.md` — 27 tabel, 24 daftar enum terpusat, 13 aturan validasi bersama, 10 aturan lintas-field
- [✓] ✅ Task 1.4 - Clone TailAdmin Laravel + inisialisasi proyek `[Sedang]` (Selesai)
  * Clone `TailAdmin/tailadmin-laravel`, `.git` bawaan dihapus, `LICENSE` MIT dipertahankan
  * Mengubah `composer.json` — Pest diturunkan `^4.0` → `^3.0` karena Pest 4 menuntut PHP 8.3+, sedangkan proyek memakai PHP 8.2.12. `composer.lock` bawaan dihapus dan diselesaikan ulang
  * Membuat `.env` — `APP_NAME="SIM Transmigrasi"`, `APP_TIMEZONE=Asia/Makassar`, `APP_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`, `DB_DATABASE=sim_transmigrasi`
  * Mengubah `config/app.php` — `'timezone' => 'UTC'` diganti `env('APP_TIMEZONE', 'UTC')`; template menuliskan UTC secara harfiah sehingga `.env` diabaikan
  * Database `sim_transmigrasi` dibuat (utf8mb4_unicode_ci), `php artisan migrate` hijau: 9 tabel bawaan Laravel
  * Verifikasi: Laravel 12.65.0, PHP 8.2.12, timezone `Asia/Makassar`, locale `id`, `npm run build` hijau
  * Catatan: npm memblokir postinstall script; `@tailwindcss/oxide` dan `esbuild` disetujui lewat `npm approve-scripts`
- [✓] ✅ Task 1.4b - Bersihkan template dari halaman contoh `[Sedang]` (Selesai)
  * Menghapus halaman contoh: dashboard e-commerce, kalender, 6 demo UI, chart, tables, form-elements
  * Menghapus komponen `ecommerce/*` (6), `calender-area`, `youtube-embed`, `tables/BasicTables` (5), `form/FormElements` (10), beserta kelas PHP-nya
  * Menghapus `SidebarController.php` dan `DashboardController.php` (tak terpakai, menunjuk view yang sudah dihapus)
  * Menghapus `sidebar-widget.blade.php` (iklan TailAdmin Pro) beserta pemanggilnya di `sidebar.blade.php`
  * Menulis ulang `resources/js/app.js` — buang FullCalendar dan 6 grafik contoh, tambah locale Indonesia untuk Flatpickr
  * Menulis ulang `routes/web.php` — hanya menyisakan rute `/` sementara
  * Menulis ulang `app/Helpers/MenuHelper.php` — menu contoh diganti satu entri sementara, 15 ikon SVG dipertahankan, komentar Bahasa Indonesia ditambahkan
  * Mengubah `resources/css/app.css` — hapus `@import 'prismjs/themes/prism.min.css'` yang paketnya sudah dicabut
  * Mengubah judul halaman di `layouts/app.blade.php` dan `layouts/fullscreen-layout.blade.php` jadi `config('app.name')`
  * Mencabut 10 dependensi npm: `@fullcalendar/*` (5), `jsvectormap`, `swiper`, `prismjs`, `@popperjs/core`, `@floating-ui/dom`
  * Verifikasi: `npm run build` hijau, bundle JS turun **890 kB → 712 kB**; `php artisan view:cache` hijau; halaman `/` HTTP 200 dengan judul "Beranda | SIM Transmigrasi", tanpa sisa rujukan TailAdmin
- [✓] ✅ Task 1.4c - Pindahkan proyek Laravel ke root, rapikan berkas sumber `[Mudah]` (Selesai)
  * Isi folder `app/` dinaikkan satu tingkat menjadi root proyek; folder `app/` kini murni berisi kode Laravel (Helpers, Http, Models, Providers, View)
  * Pemindahan memakai nama sementara `_tmp_pindah` untuk menghindari tabrakan antara folder `app` lama dan `app/app` milik Laravel
  * Membuat folder `docs/` berisi 4 berkas sumber: proposal PDF, dump SQL, logo webp, foto heic
  * Mengubah `.gitignore` — tambah pola `/*.pdf`, `/*.heic`, `/*.sql`, `/*.webp` agar berkas sumber tidak tercecer di root
  * Memperbarui 8 rujukan berkas di `erd.md`, `notes.md`, `rules.md`, `tasklist.md`, `ui-spec.md` menjadi berawalan `docs/`
  * Verifikasi: 14.353 berkas / 130 MB utuh, 5 berkas tersembunyi ikut pindah, `artisan about` menunjukkan konfigurasi tak berubah, `migrate:status` hijau, `npm run build` hijau, halaman `/` HTTP 200
  * Tidak ada path absolut tertanam di `.env`, `vendor/composer/*`, maupun `bootstrap/cache/*`, sehingga pemindahan tidak memerlukan penyesuaian konfigurasi

- [✓] ✅ Task 1.5 - Verifikasi Alpine.js, ApexCharts, Flatpickr, dan Vite `[Mudah]` (Selesai)
  * Versi terpasang: Alpine 3.14.9, ApexCharts 5.3.5, Flatpickr 4.6.13, Tailwind 4.1.12, Vite 7.1.3
  * Uji eksekusi nyata memakai jsdom, **9 dari 9 lulus**: ketiga pustaka terekspos ke `window`, Alpine reaktif setelah klik (0 → 1), Flatpickr membentuk instance dan menghasilkan "11 Agustus 2026", nama hari Indonesia (Minggu, Senin, Selasa), ApexCharts merender SVG
  * Bundle bersih dari pustaka yang dicabut: FullCalendar dan jsvectormap tidak ada jejaknya
  * Temuan: `flatpickr.formatDate()` statis mengabaikan locale global sehingga menghasilkan Bahasa Inggris, sedangkan instance memakai locale dengan benar. Aplikasi memakai instance, jadi tidak terpengaruh
- [✓] ✅ Task 1.6 - Terapkan design token ke `resources/css/app.css` `[Sedang]` (Selesai)
  * Mengubah `resources/css/app.css` — menambah **44 variabel warna** (navy, teal, sand, gold masing-masing 11 tingkat) di dalam blok `@theme` Tailwind v4
  * Menimpa seluruh 12 nilai `--color-brand-*` dengan skala navy; biru bawaan `#465fff` hilang sepenuhnya
  * Menambah blok `:root` dan `.dark` berisi 8 variabel permukaan: latar halaman, latar kartu, sidebar, garis tepi, teks utama, teks isi, teks keterangan, aksen
  * Mempertahankan `--color-gray-*`, `--color-success/error/warning-*`, `--text-title-*`, `--breakpoint-*` bawaan TailAdmin
  * Memperbaiki 2 sisa warna biru yang di-hardcode pada gaya Flatpickr (baris 707 dan 713) menjadi `var(--color-brand-500)`; tanpa ini tanggal terpilih akan tampil biru, bukan navy
  * Verifikasi otomatis **30 dari 30 lulus**: skala lengkap, nilai warna inti sesuai logo, brand tertimpa navy, 13 variabel permukaan dua mode benar, 4 motif terdefinisi
  * Verifikasi kontras: **15 rasio WCAG cocok persis** dengan `ui-spec.md` §3.2, termasuk `gold-500` yang gagal di mode terang (2,75) tetapi lolos AA di mode gelap (6,36)

- [✓] ✅ Task 1.6b - Wujudkan motif identitas `[Sedang]` (Selesai)
  * Empat kelas motif dibuat di `resources/css/app.css` sesuai `ui-spec.md` §2.3:
    - `.motif-menu-aktif` — batang tegak gold 3px di tepi kiri item sidebar aktif
    - `.motif-judul-kartu` — garis pendek gold 24px di atas label kartu statistik
    - `.motif-header-halaman` — garis bawah bergradasi berhenti di sepertiga lebar
    - `.motif-baris-total` — garis atas navy 2px pada baris total tabel rekap, otomatis jadi `navy-300` di mode gelap
  * Gold dipakai sebagai aksen tunggal sesuai `ui-spec.md` §2.4, hanya pada empat titik ini
- [✓] ✅ Task 1.6d - Rancang hak akses dinamis, verifikasi, dan pengaduan publik `[Sulit]` (Selesai)
  * **Role menjadi dinamis.** Kolom enum `user.role` diganti 3 tabel: `role`, `permission`, `role_permission`. Admin dapat membuat role dan menyusun izinnya lewat antarmuka
  * **Cakupan data** ditambahkan sebagai dimensi terpisah dari izin: `Semua`, `Per SP`, `Milik Sendiri`, beserta tabel penugasan `user_satuan_permukiman`
  * **Tabel `verifikasi` terpusat** menjawab temuan bahwa hak V diberikan di 17 modul tetapi tidak ada satu pun kolom penyimpannya
  * **Role Transmigran dan Ketua Poktan dihapus.** Warga tidak lagi memiliki akun; kolom `user.transmigran_id` dan `user.nik` dicabut
  * **Pengaduan dibuka sebagai kanal publik tanpa login**, dengan `nama_pelapor`, `kontak_pelapor`, `sumber_laporan`, `ip_pelapor`, pembatasan 3 laporan per jam per IP, dan halaman lacak memakai nomor tiket
  * Login memakai **email atau username**; kolom `username` ditambahkan
  * Mengubah `erd.md` — 28 → **33 tabel**, 41 → **46 relasi**, urutan migration 29 → **33 langkah**, seeder 4 role bawaan, temuan §8.2 nomor 27 sampai 30
  * Mengubah `data-dictionary.md` — 5 tabel baru, ubah `user` dan `pengaduan`, 4 enum baru (§11.25 sampai §11.28), 11 aturan validasi baru, **§13 Daftar Izin** berisi ±120 permission
  * Mengubah `rules.md` — §5 ditulis ulang menjadi role dinamis, §5.1 matriks jadi konfigurasi awal 4 role, **§5.2 aturan verifikasi baru**, §10b kanal publik, §14b login
  * Mengubah `prd.md` §4 dan §7.1 dan §7.13, `ui-spec.md` **§4.1a halaman publik baru** dan §4.9 dan §5 menu dinamis, `workflow.md` §3.1 dan §4.13 dua jalur dan §5 empat role
  * Mengubah `signin.blade.php` — kolom jadi "Email atau Username", ditambah keterangan jalur pengaduan warga

- [✓] ✅ Task 1.6c - Rapikan halaman masuk, hapus pendaftaran mandiri `[Sedang]` (Selesai)
  * Menghapus `resources/views/pages/auth/signup.blade.php` — sistem tidak menyediakan pendaftaran mandiri, akun hanya dibuat Admin
  * Menulis ulang `resources/views/pages/auth/signin.blade.php` ke Bahasa Indonesia
  * Membuang **3 kontrol mati**: tautan `/signup`, tautan `/reset-password`, serta tombol "Sign in with Google" dan "Sign in with X" yang tidak relevan untuk sistem pemerintahan
  * Kolom kredensial tunggal berlabel "Email atau NIK" beserta keterangan penggunaannya per role
  * Keterangan lupa kata sandi ditulis sebagai **teks biasa, bukan tautan**, memenuhi `ANTISLOP-ID.md` R-24 dan R-26
  * Menambah label aksesibilitas pada tombol perlihatkan kata sandi dan tombol ganti tema, serta indikator fokus keyboard (R-32)
  * Panel kanan memuat nama sistem dan kawasan, menggantikan promosi template
  * Verifikasi: halaman `/login` HTTP 200, tidak ada sisa tautan hantu maupun tombol OAuth

- [✓] ✅ Task 1.7 - Siapkan aset logo `[Mudah]` (Selesai)
  * Konversi logo resmi memakai FFmpeg (sumber WebP 1280×1280 transparan) menjadi 6 varian di `public/images/logo/`: `logo-kementrans.png` (512), `-256`, `-128`, `apple-touch-icon.png` (180), `favicon-32.png`, `favicon-16.png`, plus salinan WebP asli
  * Membuat `public/favicon.ico` multi-ukuran (16 dan 32) dengan menyusun byte format ICO secara manual, terverifikasi terbaca `System.Drawing.Icon`
  * Menambahkan tautan favicon, apple-touch-icon, dan `theme-color` navy pada `layouts/app.blade.php` serta `layouts/fullscreen-layout.blade.php`
  * **Membersihkan 59 aset contoh TailAdmin** dari 12 folder (ai, brand, cards, carousel, chat, country, grid-image, logistics, product, support, task, video-thumb) beserta 4 logo TailAdmin
  * **Menghapus 37 foto pengguna fiktif** dan komponen yang memakainya: `notification-dropdown` diganti keadaan kosong yang jujur, `user-dropdown` ditulis ulang memakai avatar inisial, folder `profile` dihapus untuk dibangun ulang pada Task 2.4. Ini memenuhi `ANTISLOP-ID.md` R-18, R-23, dan R-38
  * Catatan: .NET tidak mendukung WebP, sehingga konversi memakai FFmpeg yang sudah tersedia di sistem

- [✓] ✅ Task 1.7b - Aset kesalahan dan ikon berkas `[Mudah]` (Selesai)
  * Mempertahankan 10 ilustrasi kesalahan (404, 500, 503, maintenance, success) dan 6 ikon jenis berkas, seluruhnya punya varian mode gelap
  * `public/images/` kini berisi 24 berkas, seluruhnya terpakai
- [✓] ✅ Task 1.8 - Sesuaikan layout, sidebar dinamis, dan helper `hashTabs()` `[Sulit]` (Selesai)
  * Menulis ulang `app/Helpers/MenuHelper.php` — **10 kelompok, 25 item menu** sesuai pemetaan `ui-spec.md` §5.1, setiap item membawa atribut `permission`
  * `getMenuGroups()` menyaring item menurut izin dan **membuang kelompok yang seluruh itemnya tersaring**, agar tidak ada judul kelompok kosong (`ui-spec.md` §5.2)
  * `bolehLihat()` disiapkan sebagai titik sambung RBAC; sementara selalu mengembalikan `true` sampai tabel `permission` dibuat pada Tahap 3, ditandai komentar `ponytail:`
  * Mengubah `layouts/sidebar.blade.php` — logo TailAdmin diganti logo Kementerian beserta nama sistem dan kawasan, item menu aktif memakai kelas `motif-menu-aktif` (batang gold di tepi kiri) dan atribut `aria-current="page"`
  * Mengubah `layouts/app-header.blade.php` — logo mobile diganti logo Kementerian
  * Menambahkan helper **`hashTabs()`** pada `layouts/app.blade.php`: menyimpan tab aktif di **query string**, bukan hash, agar posisi tab bertahan setelah `return back()`. Mendukung tab bertingkat lewat `setSubTab()`
  * Verifikasi `hashTabs()` memakai jsdom: **8 dari 8 lulus** — tab bawaan terbaca, query ditulis saat init, `setTab` dan `setSubTab` menulis query dengan benar, dan URL tidak pernah memakai hash
  * Verifikasi aset: `/favicon.ico`, logo, dan apple-touch-icon seluruhnya HTTP 200 dengan MIME type benar; halaman `/` merender 50 penanda item menu
- [✓] ✅ Task 1.9 - Buat `app/Support/ValidationRules.php` `[Mudah]` (Selesai)
  * 16 aturan siap pakai: `nik()`, `noKk()`, `nama()`, `teks()`, `telepon()`, `email()`, `username()`, `password()`, `tahun()`, `luas()`, `volume()`, `uang()`, `lintang()`, `bujur()`, `dokumen()`, `foto()`
  * `pesan()` berisi 40 pesan galat berbahasa Indonesia yang dapat dipahami operator lapangan, tanpa istilah teknis
  * `label()` menerjemahkan nama kolom agar pesan bawaan Laravel ikut berbahasa Indonesia
  * `aturanUnik()` menangani konvensi primary key `id_namatabel` yang berbeda dari asumsi bawaan Laravel

- [✓] ✅ Task 1.10 - Buat middleware `UppercaseInput` `[Mudah]` (Selesai)
  * Mengubah isian teks menjadi HURUF KAPITAL agar rekap per wilayah tidak terpecah oleh perbedaan penulisan
  * 24 kolom dikecualikan: kredensial, surel, username, dan teks naratif seperti deskripsi serta catatan
  * 6 akhiran dikecualikan: `_id`, `_at`, `_token`, `_password`, `_email`, `_url`
  * Memproses isian bersarang secara rekursif, membersihkan spasi berlebih, dan melewatkan permintaan GET
  * Didaftarkan pada `bootstrap/app.php` untuk seluruh rute web

- [✓] ✅ Task 1.11 - Konfigurasi penyimpanan file privat + aturan penamaan `[Sedang]` (Selesai)
  * Membuat `app/Support/PenyimpananDokumen.php`: `simpan()`, `ganti()`, `hapus()`, `hapusFolder()`, `ada()`, `ukuran()`, `folder()`, `susunNamaBerkas()`
  * Struktur folder `[modul]/[id]/`, pola nama `[NamaDokumen]_[nama-pemilik].[ekstensi]`
  * Membuat `app/Http/Controllers/DokumenController.php` yang mengalirkan berkas setelah pemeriksaan hak akses, lengkap dengan penolakan upaya menembus folder lewat `..` pada nama berkas
  * **Mematikan `serve` pada disk `local`** di `config/filesystems.php`. Nilai bawaan `true` mendaftarkan rute `/storage/{path}` yang melayani berkas privat TANPA pemeriksaan hak akses; ini lubang keamanan untuk dokumen kependudukan
  * Menambah rute `dokumen.tampilkan` dengan pembatasan pola pada parameter modul dan id

- [✓] ✅ Task 1.12 - Uji fondasi Tahap 1 `[Sedang]` (Selesai)
  * Membuat `tests/Feature/FondasiTest.php` berisi 22 uji, 150 pernyataan, seluruhnya lulus
  * Menemukan dan memperbaiki satu bug nyata: `Str::lower()` pada penyusunan nama berkas merusak huruf kapital di tengah kata, sehingga `KartuKeluarga` menjadi `Kartukeluarga` dan singkatan `HPL` menjadi `Hpl`

## Tahap 2 — Antarmuka dengan Data Dummy

Dikerjakan **dua gelombang**. Gelombang 1 membangun alur inti agar dapat divalidasi bersama tim dan dinas; gelombang 2 melanjutkan sisanya memakai pola yang sudah disetujui. Tujuannya agar revisi hasil FGD tidak membongkar 43 halaman sekaligus.

### Gelombang 1 — Alur inti (±12 halaman)

- [✓] ✅ Task 2.1 - PHP Enum di `app/Enums/` `[Sedang]` (Selesai)
  * Membuat **29 enum** di `app/Enums/` sesuai daftar nilai baku `data-dictionary.md` 11, lebih banyak dari rencana semula karena beberapa nilai enum pada kamus data belum terdaftar sebagai berkas tersendiri
  * Membuat 2 trait bersama di `app/Enums/Concerns/`: `PunyaLabel` (opsi dropdown, konversi teks ke enum, pemeriksaan keanggotaan) dan `PunyaWarnaBadge` (warna badge dan daftar berwarna)
  * 10 enum berbadge warna: `StatusVerifikasi`, `StatusPengaduan`, `PrioritasPengaduan`, `KondisiRumah`, `Kondisi`, `StatusHunian`, `StatusKeaktifanAnggota`, `StatusTinggal`, `StatusPenyerahan`, `KualitasPanen`
  * `StatusPengaduan` memuat logika alur: `berikutnya()`, `bolehPindahKe()`, dan `masihBerjalan()`, menolak perpindahan status yang melompat maupun mundur
  * `BidangPengaduan::dariKategori()` menyimpulkan dinas penanganan dari kategori, sehingga warga tidak perlu memilih bidang sendiri pada halaman pengaduan publik
  * Membuat `tests/Feature/EnumTest.php`: **31 uji, 92 pernyataan**, seluruhnya lulus
- [✓] ✅ Task 2.2 - Penyedia data dummy `app/Support/DummyData.php` `[Sedang]` (Selesai)
  * 15 metode penyedia data: kawasan, 6 SP, 8 transmigran, 6 rumah, 6 lahan, 5 hasil panen, 5 pengaduan, riwayat penanganan, 4 infrastruktur, ringkasan dashboard, deret 11 tahun, sebaran pekerjaan, sebaran komoditas, rekap per SP, status infrastruktur, dan mutu data
  * Struktur array mengikuti nama kolom pada `data-dictionary.md`, sehingga penggantian ke data nyata cukup menukar sumbernya tanpa menyentuh berkas Blade
  * Data mencerminkan lokus sebenarnya: 6 SP tersebar di 4 kecamatan di bawah Kawasan Kobalima Timur, dengan koordinat di sekitar Kabupaten Malaka
  * Konstanta `MEMAKAI_DATA_CONTOH` disiapkan sebagai penanda spanduk "Data contoh" (ANTISLOP-ID R-17 dan R-38)
  * Membuat `tests/Feature/DummyDataTest.php`: **24 uji, 212 pernyataan**, memeriksa kesesuaian nilai enum, aturan satu rumah satu KK, lahan usaha jamak, keunikan NIK, dan keselarasan rekap per SP terhadap ringkasan kawasan
- [✓] ✅ Task 2.3 - Komponen bersama `[Sulit]` (Selesai)
  * Membuat **11 komponen** di `resources/views/components/sim/`, memakai awalan `sim.` agar terpisah jelas dari komponen bawaan TailAdmin
  * `x-sim.status-badge` menerima objek enum langsung, sehingga warna tidak ditulis ulang di tiap halaman. Dilengkapi titik penanda agar tetap terbedakan bagi pengguna dengan buta warna
  * `x-sim.data-table` memuat pencarian, laci filter yang dapat dilipat, pilihan 10/25/50/100 baris, header lengket, keadaan kosong, serta **tata letak kartu untuk layar sempit** agar tidak ada gulir mendatar
  * `x-sim.modal-form` mengunci fokus di dalam modal (jebakan Tab), menutup dengan Esc dan klik latar, layar penuh pada mobile, dan menyediakan tombol **"Simpan dan Verifikasi"** yang hanya dirender bila pengguna berizin
  * `x-sim.confirm-dialog` mendukung ragam bahaya dan peringatan, dengan **alasan wajib** untuk penolakan verifikasi
  * `x-sim.file-upload` memeriksa ukuran dan tipe berkas **di sisi klien lebih dulu**, agar pengguna berjaringan lemah tidak menunggu unggahan yang pasti ditolak. Menampilkan pratinjau gambar dan contoh nama berkas akhir
  * `x-sim.wilayah-picker` punya dua mode: `operasional` (Kawasan → SP) dan `pendaftaran-sp` (kedua cabang hierarki). **Kecamatan tidak pernah diisi manual**, hanya dibaca dari desa terpilih
  * `x-sim.koordinat-input` memakai Geolocation API dengan tiga pesan galat berbahasa Indonesia yang selalu menawarkan pengisian manual sebagai jalan keluar
  * `x-sim.toast` menampilkan pesan sesi controller secara otomatis, dan dapat dipanggil dari mana pun lewat peristiwa Alpine
  * `x-sim.page-header` dan `x-sim.stat-card` menerapkan motif identitas (`motif-header-halaman` dan `motif-judul-kartu`)
  * Memasang **spanduk "Data contoh"** pada `layouts/app.blade.php`, tampil selama `DummyData::MEMAKAI_DATA_CONTOH` bernilai true (ANTISLOP-ID R-17 dan R-38)
  * Membuat halaman galeri `resources/views/pages/galeri-komponen.blade.php` beserta rutenya sebagai acuan pemakaian; dihapus sebelum penyerahan akhir
  * Temuan: pola `@if` yang menempel langsung setelah teks (contoh `Lintang@if ($wajib)`) membuat Blade gagal mengurai berkas. Diganti ekspresi `{!! $wajib ? ... !!}` pada 3 komponen
- [✓] ✅ Task 2.4 - Halaman autentikasi + profil `[Sedang]` (Selesai)
  * Membuat `resources/views/pages/profil/index.blade.php` — tata letak **dua kolom asimetris** sesuai pola halaman detail (`ui-spec.md` §2.2): kartu identitas menetap di kiri, tab Data Akun dan Kewenangan di kanan memakai helper `hashTabs()`
  * Membuat `resources/views/pages/profil/kata-sandi.blade.php` — ubah kata sandi atas keinginan sendiri, meminta kata sandi lama sebagai pemeriksaan pemilik akun
  * Membuat `resources/views/pages/auth/ganti-kata-sandi.blade.php` — halaman wajib ganti kata sandi memakai tata letak layar penuh **tanpa sidebar**. Alasannya: seluruh halaman lain diblokir middleware, sehingga merender menu di sini berarti mengirim 25 kontrol mati (R-24 dan R-26). **Kata sandi lama tidak diminta** karena pengguna baru menerimanya dari admin dan sudah membuktikan kepemilikan lewat proses masuk
  * Membuat komponen `resources/views/components/sim/input-kata-sandi.blade.php` — isian kata sandi beserta tombol perlihatkan, dipakai 5 kali di 3 halaman sehingga tidak disalin berulang. `aria-label` ikut berubah karena ikon saja tidak terbaca pembaca layar
  * Menulis ulang `resources/views/components/header/user-dropdown.blade.php` — keterangan "menu tersedia setelah autentikasi aktif" diganti tautan nyata ke Profil Saya, Ubah Kata Sandi, dan Keluar. Keluar memakai **POST**, bukan tautan, agar tidak terpicu prefetch peramban
  * Menambah `DummyData::penggunaSaatIni()` dan `DummyData::inisial()` — avatar memakai inisial nama, bukan foto orang karangan (R-18 dan R-23)
  * Mengubah `routes/web.php` — 8 rute autentikasi dan profil, seluruhnya membalas dengan pesan sesi sehingga `x-sim.toast` langsung berfungsi
  * **Nama, username, jabatan, dan role dirender sebagai teks baca-saja**, bukan input, karena hanya admin yang boleh mengubahnya (`rules.md` §14b poin 1). Merendernya sebagai isian akan menjanjikan kewenangan yang tidak dimiliki pengguna
  * Akun bercakupan `Per SP` tanpa penugasan diberi peringatan tegas di tab Kewenangan, sebab keadaan itu membuat seluruh daftar data kosong (`rules.md` §5.0b poin 7)
  * Tanpa halaman daftar akun dan tanpa halaman lupa kata sandi, sesuai `ui-spec.md` §4.1

- [✓] ✅ Task 2.5 - Dashboard + 15 visualisasi ApexCharts `[Sulit]` (Selesai)
  * Membuat `resources/js/chart-config.js` — konfigurasi bersama: 5 warna seri dari logo Kementerian, font Outfit, format angka Indonesia (`angka()`, `rupiah()`, `angkaSingkat()`), `opsiDasar()`, `gabung()` rekursif, `buatGrafik()`, dan `drilldownSp()`
  * **Temuan penting: mode gelap.** ApexCharts menghitung warna sumbu, legenda, dan tooltip sekali saat grafik dibuat, sehingga tidak ikut berubah ketika kelas `dark` dipasang. Diselesaikan dengan mendaftarkan setiap grafik lalu menggambarnya ulang lewat `MutationObserver` pada elemen html (`pantauTema()`). Tanpa ini seluruh 10 grafik tidak terbaca di salah satu mode, melanggar R-34
  * `gabung()` dibuat rekursif karena opsi ApexCharts bersarang; penggabungan dangkal akan menghapus seluruh isi `chart` dan `xaxis` bawaan
  * Membuat `resources/views/pages/dashboard/index.blade.php` — 15 indikator `ui-spec.md` §9: 8 kartu statistik, **10 grafik**, dan 1 tabel isu prioritas
  * Membuat komponen `resources/views/components/sim/chart-card.blade.php` — pembungkus grafik beserta pengalih **"Lihat tabel data"**, memenuhi kewajiban tabel alternatif (§9 poin 7). Tanpa itu isi grafik tidak terbaca sama sekali oleh pembaca layar
  * Komposisi mengikuti **RITME 2**: baris kartu statistik di atas, lalu grid grafik dua kolom yang sengaja **tidak sama lebar** (`xl:col-span-2` pada 4 grafik), agar dashboard terbaca berbeda dari halaman daftar
  * Animasi dan toolbar ApexCharts dimatikan mengikuti dial **GERAK 1**, karena perangkat lapangan terbatas
  * Filter wilayah dan periode dibuat sebagai kontrol nyata yang menulis query string dan bertahan setelah reload; penyaringan datanya menyusul pada Task 9.2. Bukan kontrol mati
  * Drill-down klik grafik saat itu belum dipasang karena halaman tujuannya belum ada; **sudah dipasang pada Task 2.6**
  * Menambah `DummyData::rekapPenghuni()` dan deret `harga_rata_rata`, melengkapi indikator 11 dan 14
  * Mengubah `routes/web.php` — rute `/` menunjuk dashboard sebenarnya, menggantikan halaman sementara
  * Menghapus `resources/views/pages/blank.blade.php` — sisa template TailAdmin berisi teks contoh Bahasa Inggris, sudah tidak dirujuk rute mana pun
  * **Temuan: direktif `@json` gagal mengurai array bersarang bertingkat** yang ditulis langsung di dalamnya, menghasilkan galat "Unclosed '['". Bahan grafik dipindahkan ke blok `@php` lebih dulu
  * Membuat `tests/Feature/HalamanTest.php`: **12 uji** meliputi perenderan 10 grafik, kelengkapan tabel alternatif, penanda data contoh, format angka Indonesia, dan pemeriksaan bahwa nama serta username tidak dirender sebagai isian
  * Menambah **7 uji** pada `tests/Feature/DummyDataTest.php` untuk data pengguna dan rekap penghuni
  * Verifikasi `chart-config.js` memakai Node: **12 dari 12 lulus** (format angka, penggabungan rekursif, kesesuaian palet)
- [✓] ✅ Task 2.6 - Halaman drill-down per SP `[Sedang]` (Selesai)
  * Membuat `resources/views/pages/dashboard/sp.blade.php` — halaman rincian satu satuan permukiman, tujuan penelusuran dari dashboard kawasan (`rules.md` §11 poin 5)
  * Komposisi **dua kolom asimetris** mengikuti pola halaman detail (`ui-spec.md` §2.2): profil SP menetap di kiri (kode, tahun penempatan, luas, keterisian, koordinat), indikator dan rincian di kanan. Sengaja dibedakan dari grid grafik dashboard kawasan agar pengguna tahu sedang berada di jenis halaman berbeda
  * **Enam tab rincian** memakai `hashTabs()`: transmigran, rumah, lahan, panen, pengaduan, infrastruktur. Setiap label memuat jumlah baris, dan tab tanpa data menampilkan keadaan kosong, bukan tabel kosong
  * Dua grafik ringkas khusus SP: pertumbuhan KK dan volume panen per tahun
  * Navigasi pindah antar SP di bagian atas, agar petugas tidak perlu kembali ke dashboard lebih dulu
  * Membuat komponen `resources/views/components/sim/tabel-ringkas.blade.php` — tabel tanpa pencarian dan paginasi untuk potongan data pendek di dalam tab, dipakai 6 kali. Dibedakan dari `x-sim.data-table` yang untuk halaman daftar utama
  * Menambah grafik **Perbandingan Antar Satuan Permukiman** pada dashboard kawasan. Inilah satu-satunya grafik bersumbu SP, sehingga di sinilah `drilldownSp()` dipasang. Grafik lain bersumbu tahun atau kategori, yang tidak dapat diterjemahkan menjadi satu SP tertentu
  * **Klik grafik dilengkapi tautan teks "Buka rincian"** pada tabel alternatifnya. Klik pada batang ApexCharts tidak dapat dijangkau tombol Tab, sehingga tanpa tautan ini penelusuran mustahil bagi pengguna keyboard (R-32)
  * Menambah 5 metode `DummyData`: `cariSp()`, `rekapSp()`, `saringPerSp()`, `deretTahunanSp()`, beserta kolom `satuan_permukiman_id` dan `data_total` pada `rekapPerSp()`
  * `deretTahunanSp()` menurunkan deret per SP secara proporsional dari deret kawasan, ditandai jelas sebagai perkiraan data contoh dan diganti query nyata pada Task 9.1
  * Mengubah `routes/web.php` — rute `dashboard.sp` dengan pembatasan pola angka; **SP tak dikenal membalas 404**, bukan halaman kosong yang membingungkan
  * Menambah **9 uji** pada `HalamanTest.php` dan **9 uji** pada `DummyDataTest.php`, termasuk pemeriksaan bahwa halaman SP hanya menampilkan data miliknya sendiri, dan bahwa id rekap sepadan dengan id daftar SP
  * Uji tabel alternatif diubah membandingkan **jumlah tabel terhadap jumlah grafik**, bukan angka tetap. Bentuk lama sempat gagal saat grafik ke-11 ditambahkan, dan itu justru membuktikan gunanya
- [✓] ✅ Task 2.7 - Halaman transmigran (daftar, detail, modal form) `[Sulit]` (Selesai)
  * **Halaman CRUD pertama; polanya menjadi acuan Task 2.8 sampai 2.11.**
  * Membuat `resources/views/pages/transmigran/index.blade.php` — daftar lebar penuh yang didominasi tabel, filter dalam laci terlipat, ditambah 4 kartu ringkasan mutu data. Komposisinya sengaja berbeda dari dashboard dan halaman detail, memenuhi dial **RITME 2**
  * Membuat `resources/views/pages/transmigran/detail.blade.php` — dua kolom asimetris, 5 tab: Biodata, Rumah, Lahan, Hasil Panen, Dokumen. Tab Lahan memakai **baris total** dengan motif identitas `motif-baris-total`
  * Membuat `resources/views/pages/transmigran/form.blade.php` — isian dipakai bersama modal tambah dan modal ubah, dibagi 4 bagian bertahap agar tidak padat (`rules.md` §13.1 poin 2). Ditulis sekali agar kedua modal tidak menyimpan salinan yang dapat berbeda diam-diam
  * **Atribut `awalan` pada form partial** membuat id isian tetap unik saat dua modal hadir di satu halaman. Tanpa itu, label `for` menunjuk isian yang salah dan klik label memfokuskan kolom keliru
  * **Pencarian dan filter bekerja nyata** atas data contoh: kata kunci mencocokkan nama, NIK, dan nomor KK; filter SP, status verifikasi, dan status tinggal. Seluruhnya lewat query string sehingga bertahan setelah dimuat ulang. Terverifikasi lewat uji: 8 baris tanpa filter, 1 baris untuk `cari=YOHANES`
  * Keadaan **pencarian nihil** dibedakan dari daftar yang memang kosong, masing-masing dengan jalan keluarnya sendiri (`ui-spec.md` §7)
  * **Alasan penolakan ditulis penuh** sebagai spanduk merah di atas halaman rincian, bukan sekadar tooltip pada badge. Alasannya: inilah satu-satunya petunjuk perbaikan bagi operator (`rules.md` §5.2 poin 7)
  * Tombol **"Simpan dan Verifikasi"** aktif pada kedua modal, dan tindakan verifikasi terpisah tersedia di kolom kiri halaman rincian
  * `pekerjaan_kepala_keluarga` memakai isian teks bebas ber-`datalist`, bukan dropdown, karena ragam pekerjaan di lapangan sulit dibatasi di muka (`data-dictionary.md` §6.1)
  * Menambah `tempat_lahir` pada 8 baris data contoh, melengkapi kolom kamus data yang belum terwakili
  * Mengubah `resources/views/pages/dashboard/sp.blade.php` — nama pada tab Transmigran kini menaut ke halaman rincian
  * Mengubah `routes/web.php` — 7 rute modul transmigran. Rute tulis memulangkan pesan sesi agar alur dapat dicoba tanpa tombol mati; penyimpanan sebenarnya menyusul pada Tahap 5. Id tak dikenal membalas **404**
  * Menambah **14 uji** pada `HalamanTest.php`, termasuk pemeriksaan bahwa **seluruh 18 nama isian cocok dengan kolom kamus data**, sehingga Form Request Tahap 5 dapat membacanya tanpa menyunting Blade
- [✓] ✅ Task 2.8 - Halaman rumah dan riwayat penghunian `[Sedang]` (Selesai)
  * Membuat 3 berkas di `resources/views/pages/rumah/`: `index`, `detail`, `form`
  * **Dropdown penghuni hanya menawarkan KK yang belum menempati rumah lain**, memenuhi aturan satu KK satu rumah (`rules.md` 6a poin 8). Terverifikasi lewat uji: dari 8 KK, hanya 4 yang muncul sebagai pilihan. Pada modal ubah, penghuni rumah itu sendiri tetap disertakan agar tidak hilang dari pilihannya sendiri
  * **Riwayat penghunian disajikan sebagai garis waktu**, bukan tabel, karena yang perlu terbaca adalah urutan kejadian: siapa masuk, kapan keluar, dan mengapa. Titik hijau menandai penghuni yang masih menempati
  * Alasan wajib diisi saat status Tidak Dihuni, dijaga Alpine di sisi klien dan diulang server pada Tahap 5
  * Rumah kosong diberi spanduk kuning beserta alasannya di bagian atas halaman rincian
  * Menambah `DummyData::riwayatPenghunian()`, `rumahKosong()`, dan `transmigranTanpaRumah()`
- [✓] ✅ Task 2.9 - Halaman lahan dan dokumen lahan `[Sedang]` (Selesai)
  * Membuat 3 berkas di `resources/views/pages/lahan/`: `index`, `detail`, `form`
  * **Kategori, pola tanam, peralatan, dan kendala disembunyikan bila jenis lahan bukan Lahan Usaha**, karena keempatnya tidak berlaku untuk lahan pekarangan (`data-dictionary.md` 7.1). Tab Pengelolaan pada halaman rincian ikut hilang
  * **Dokumen HPL dan SHM dikelola lewat modal terpisah**, bukan di dalam form lahan, karena satu lahan dapat memiliki lebih dari satu dokumen (7.2)
  * Daftar memakai **baris total luas** dengan motif identitas, memenuhi aturan bahwa rekap luas wajib memakai penjumlahan seluruh lahan (`rules.md` 7.10)
  * Menambah `DummyData::dokumenLahan()`
  * **Temuan lewat uji:** placeholder pada form semula berbunyi Contoh LU-001, yang kebetulan sama dengan kode lahan asli sehingga uji penyaringan gagal. Diganti LU-025 yang tidak dipakai data mana pun
- [✓] ✅ Task 2.10 - Halaman hasil panen dan rekap panen `[Sedang]` (Selesai)
  * Membuat 4 berkas di `resources/views/pages/panen/`: `index`, `detail`, `rekap`, `form`
  * **Satuan volume mengikuti komoditas terpilih**, ditampilkan baca-saja dan berubah otomatis lewat Alpine, bukan dipilih bebas operator (`rules.md` 9 poin 3). Jagung selalu ton, cabai selalu kilogram
  * **Penjumlahan lintas komoditas memakai hasil konversi ke ton.** Ini bukan perkara gaya: menjumlahkan 4,250 ton dan 320,500 kilogram begitu saja menghasilkan 336,55 yang keliru, sedangkan angka benarnya 16,371 ton. Diuji khusus agar tidak tergeser diam-diam
  * Volume tetap disimpan apa adanya; setara tonnya ditampilkan sebagai keterangan agar operator melihat dua angka sekaligus, yang ia catat di lapangan dan yang dipakai sistem
  * Satuan lokal seperti karung dicatat pada kolom keterangan, bukan sebagai satuan baku (`rules.md` 8a poin 6)
  * **Halaman rekap adalah jenis komposisi KEEMPAT** pada dial RITME 2: tabel agregat dengan baris total ditegaskan, **tanpa kartu statistik** (`ui-spec.md` 2.2). Diuji bahwa `motif-judul-kartu` memang tidak muncul di sana
  * Rekap dapat dikelompokkan per SP, komoditas, atau petani lewat query string (kelompok musim dicabut 2026-08-22)
  * **Rute `/panen/rekap` didaftarkan sebelum `/panen/{id}`**, karena Laravel mencocokkan rute menurut urutan pendaftaran; tanpa itu rekap akan tertangkap sebagai id
  * Menambah `DummyData::faktorKonversiTon()` dan `keTon()`

**Lintas modul (Task 2.8 sampai 2.10):**
  * Menambah `satuan_permukiman_id` pada seluruh data contoh rumah, lahan, panen, pengaduan, dan infrastruktur, agar penyaringan per SP tidak bergantung pada pencocokan nama
  * Menautkan modul satu sama lain: rincian transmigran menaut ke rumah, lahan, dan panennya; rincian rumah dan lahan menaut balik ke pemiliknya; halaman rincian SP menaut ke keempat modul
  * Mengubah `routes/web.php` dengan 21 rute baru, seluruhnya membalas 404 untuk id tak dikenal
  * Menambah **21 uji** pada `HalamanTest.php`
- [✓] ✅ Task 2.11 - Halaman pengaduan + form + penanganan + rekap `[Sulit]` (Selesai)
  * Membuat 4 berkas di `resources/views/pages/pengaduan/`: `index`, `detail`, `rekap`, `form`
  * **Modul ini berbeda dari empat sebelumnya:** tidak memakai verifikasi data, melainkan **alur status berurutan** Menunggu Diterima, Diterima, Diproses, Selesai (`rules.md` 10b poin 4)
  * **Lompatan status dicegah sejak di antarmuka.** Halaman rincian merender **tepat satu** tombol tujuan, yaitu status berikutnya yang sah menurut `StatusPengaduan::berikutnya()`. Status lain tidak ditawarkan sama sekali, dan nilai tujuan dikirim sebagai isian tersembunyi yang tidak dapat diubah pengguna
  * Terverifikasi lewat uji: pengaduan berstatus Menunggu Diterima hanya menawarkan Diterima, yang Diterima hanya menawarkan Diproses, yang Diproses hanya menawarkan Selesai. Yang sudah Selesai **tidak punya tombol sama sekali**, hanya keterangan, karena tombol mati lebih menyesatkan daripada tidak ada tombol (R-26)
  * **Penanda kemajuan alur** menampilkan keempat tahap sekaligus di kolom kiri, sehingga petugas tahu posisi laporan dan apa tahap sesudahnya
  * **Bidang penanganan disimpulkan dari kategori** lewat `BidangPengaduan::dariKategori()`, bukan dipilih manual. Petugas pencatat tidak perlu hafal pembagian tugas antar-dinas, dan penerusan laporan jadi konsisten
  * Catatan tindakan **wajib** pada setiap perpindahan status, karena riwayat tanpa catatan tidak menjelaskan apa pun kepada pembacanya (10b poin 5)
  * Prioritas Mendesak ditegaskan memakai **aksen gold**, salah satu dari empat pemakaian sah menurut `ui-spec.md` 2.4
  * Daftar mengurutkan yang belum selesai lebih dulu, lalu menurut kemendesakan
  * Rekap dapat dikelompokkan per kategori, status, SP, prioritas, atau bidang; inilah sumber indikator isu prioritas dashboard (10b poin 8)
  * Menambah `DummyData::rekapPengaduan()` dan memperluas `penangananPengaduan()` menjadi 3 pengaduan berjejak, termasuk satu yang alurnya lengkap sampai Selesai
  * Menautkan isu prioritas dashboard dan tab pengaduan halaman SP ke rincian laporannya
  * Mengubah `routes/web.php` dengan 6 rute; `/pengaduan/rekap` didaftarkan sebelum `/pengaduan/{id}`
  * Menambah **15 uji** pada `HalamanTest.php`, termasuk pemeriksaan bahwa **status selain tujuan yang sah tidak pernah muncul** sebagai nilai kiriman
- [✓] ✅ Task 2.11b - Halaman publik: form pengaduan warga + lacak pengaduan `[Sedang]` (Selesai)
  * Membuat `resources/views/layouts/publik.blade.php` sebagai tata letak terpisah tanpa sidebar. Alasannya bukan sekadar gaya: seluruh tujuan menu petugas memerlukan login, sehingga merendernya bagi warga berarti mengirim 25 kontrol mati (R-24 dan R-26). Hanya dua tautan yang tersedia, kirim dan lacak
  * Membuat `resources/views/pages/publik/pengaduan.blade.php` dan `lacak.blade.php`
  * **Isian warga sengaja lebih pendek daripada form petugas.** Kolom bidang penanganan dan prioritas **tidak ditampilkan sama sekali**: pembagian tugas antar-dinas disimpulkan sistem, dan penilaian kegentingan adalah tugas petugas, bukan beban pelapor. Koordinat juga tidak diminta
  * Setelah kirim berhasil, **nomor pengaduan tampil sangat besar** beserta anjuran mencatat atau memotretnya, ditambah tautan langsung ke halaman lacak
  * Halaman lacak memakai **GET berparameter nomor**, sehingga hasilnya dapat ditandai atau dibuka ulang tanpa mengisi lagi
  * **Aturan privasi terpenting modul ini dijaga dan diuji:** halaman lacak hanya menampilkan status, tanggal, dan catatan penanganan. Nama pelapor, nomor telepon, **dan bahkan nama petugas penangan** tidak pernah dirender. Tanpa ini, siapa pun yang menebak nomor pengaduan dapat memanen data pribadi warga lain (`rules.md` 10b poin 1c)
  * Penanda tahap pada halaman warga memakai kalimat penjelas, bukan sekadar nama status, misalnya Diproses dijelaskan sebagai petugas sedang menangani masalah yang Anda laporkan
  * Mengubah `signin.blade.php` yang semula hanya menyebut kanal warga sebagai teks; kini menaut ke halaman yang benar-benar ada
  * Pembatasan 3 pengiriman per jam per IP dan penyimpanan `ip_pelapor` dipasang pada Tahap 8; **tanpa CAPTCHA** sesuai 10b poin 1g
- [✓] ✅ Task 2.12 - Halaman 403 dan 404 `[Mudah]` (Selesai)
  * Membuat `resources/views/errors/404.blade.php` dan `403.blade.php`. **Diletakkan di `resources/views/errors/`**, bukan `pages/errors/`, agar Laravel memakainya otomatis untuk setiap respons galat termasuk `abort(404)` pada rute modul
  * Membuat komponen `x-sim.halaman-galat` sebagai kerangka bersama, agar halaman galat berikutnya tidak menyalin markup dan tidak berbeda gaya tanpa alasan
  * Keduanya memakai tata letak layar penuh tanpa sidebar, karena pengunjung bisa jadi belum masuk
  * **Setiap halaman galat menyediakan jalan keluar.** Halaman 404 menawarkan kembali ke halaman sebelumnya dan buka dashboard; halaman 403 menjelaskan bahwa izin hanya dapat diubah admin, beserta anjuran menyebutkan halaman yang dituju agar izinnya tepat sasaran
  * Ilustrasi punya varian mode gelap, memenuhi kewajiban kedua mode berfungsi penuh (R-34)
  * Menghapus `resources/views/pages/errors/error-404.blade.php`, sisa template TailAdmin berbahasa Inggris yang tidak dirujuk rute mana pun
  * Terverifikasi: keenam rute modul beserta alamat karangan seluruhnya membalas 404 dengan halaman kustom, bukan tampilan bawaan Laravel

**Lintas Task 2.11b dan 2.12:**
  * Menambah **21 uji** pada `HalamanTest.php`
  * **Satu uji sempat gagal dan itu berguna:** pemeriksaan kebocoran navigasi semula mencocokkan potongan alamat, padahal `/pengaduan` adalah awalan dari `/pengaduan-warga` sehingga selalu cocok dan ujinya tidak bermakna. Diperbaiki menjadi pencocokan atribut `href` lengkap

**→ CHECKPOINT: validasi bersama tim dan dinas sebelum lanjut ke gelombang 2.**

### Gelombang 2 — Halaman sisanya (±31 halaman)

- [✓] ✅ Task 2.13 - Halaman data master wilayah, SP, inventaris, fasilitas, satuan `[Sedang]` (Selesai)
  * Membuat 6 halaman: `master/wilayah`, `master/satuan`, `sp/kawasan`, `sp/index`, `sp/inventaris`, `sp/fasilitas`
  * Halaman wilayah menjelaskan **hierarki bercabang dua** lewat diagram teks, karena percabangan di tingkat kabupaten tidak lazim dan mudah disalahpahami
  * Halaman satuan menjelaskan **mengapa faktor konversi diperlukan** beserta contoh perhitungan, sebab inilah tabel yang membuat rekap lintas komoditas sepadan
  * Halaman kawasan memperlihatkan sebaran 6 SP di 4 kecamatan, bukti nyata alasan kawasan dipisah dari hierarki administratif
  * Rute `/sp/inventaris` dan `/sp/fasilitas` didaftarkan sebelum `/sp` agar tidak tertukar
- [✓] ✅ Task 2.14 - Halaman rekap kependudukan `[Sedang]` (Selesai)
  * Memakai komposisi rekap: tabel agregat, baris total ditegaskan, tanpa kartu statistik
  * Empat dasar pengelompokan: tahun, satuan permukiman, status tinggal, dan pekerjaan
  * Menyajikan KK masuk dan keluar per tahun sesuai kewajiban `rules.md` 10a poin 4
- [✓] ✅ Task 2.15 - Halaman poktan dan anggota poktan `[Sedang]` (Selesai)
  * Membuat daftar dan halaman rincian bertab: Anggota, Alsintan, Saprotan
  * **Anggota yang berhenti ditandai Sudah Keluar, bukan dihapus**, agar riwayat keanggotaan utuh
  * Status keaktifan bukan sekadar penanda: penyaluran saprotan hanya untuk anggota aktif, sehingga kolom ini dibaca modul lain
  * **Diperbaiki 2026-08-17 atas revisi pemilik proyek (`notes.md` 6, empat poin).** Ditemukan bahwa **anggota poktan tidak dapat diubah sama sekali**: tidak ada tombol edit, modal, maupun rute PUT, sehingga status keaktifan dan tanggal keluar tidak pernah dapat diisi setelah tersimpan. Ditutup dengan kolom Aksi, modal berpola `:id`, dan rute `anggota-poktan.perbarui`.
  * **Ketua kini dapat berasal dari luar transmigran** lewat `is_ketua_transmigran`, sebab banyak poktan diketuai penduduk setempat. Nilai `Ketua` dicabut dari enum jabatan anggota agar ketua hanya ditetapkan di satu tempat.
  * Kontak poktan diseragamkan menjadi **kontak ketua** (`telepon_ketua`, `email_ketua`, `alamat_ketua`), menyusul kenyataan bahwa `DummyData` sejak awal memang memperlakukannya demikian.
  * Huruf **H** dicabut dari matriks kewenangan Anggota poktan karena bertentangan dengan larangan hapus; total kewenangan 96 menjadi **95**.
- [✓] ✅ Task 2.16 - Halaman alsintan dan saprotan `[Sedang]` (Selesai)
  * ~~Alsintan **membedakan milik pribadi dan bantuan lewat poktan**, karena berbeda pemilik dan berbeda jalur pertanggungjawaban~~ **DICABUT 2026-08-22:** pemilik alsintan selalu kelompok tani
  * ~~Kolom pemilik menaut ke poktan atau transmigran sesuai jenis kepemilikannya~~ **DICABUT 2026-08-22:** selalu menaut ke poktan
  * ~~Saprotan mencatat penerima berupa poktan maupun individu~~ **DICABUT 2026-08-22:** penerima saprotan selalu poktan
- [✓] ✅ Task 2.17 - Halaman komoditas, musim tanam, riwayat tanam `[Sedang]` (Selesai; 2026-08-22 halaman musim tanam dihapus dan riwayat tanam berganti nama menjadi **Penanaman**)
  * Komoditas menegaskan **satuan panen baku per komoditas**, yang dipakai form panen dan tidak dapat diubah operator
  * Komoditas unggulan ditandai **aksen gold**, salah satu dari empat pemakaian sah
  * ~~Musim tanam memisahkan nama dan tahun, bukan teks bebas, agar grafik per tahun dapat dihitung~~ **FITURNYA DICABUT 2026-08-22:** poktan menanam fleksibel, tidak mengikuti periode baku MT1/MT2. Grafik per tahun dihitung dari `tanggal_tanam` dan `tanggal_panen`
  * Penanaman menjadi jembatan lahan ke hasil panen; lokasi produksi terbaca lewat rantai penanaman, lahan, SP (bernama "riwayat tanam" sampai 2026-08-22)
- [✓] ✅ Task 2.18 - Halaman infrastruktur `[Sedang]` (Selesai)
  * **Modul pendataan aset, bukan pelaporan masalah.** Halaman sengaja tidak menyediakan tombol lapor kerusakan, melainkan menaut ke modul Pengaduan (`rules.md` 10 poin 1)
  * Dilengkapi rekap kondisi per jenis, sumber indikator ke-12 dashboard
- [✓] ✅ Task 2.19 - Halaman laporan dan template luring `[Sedang]` (Selesai)
  * Dua tab: 9 laporan data dan 3 template isian luring
  * **Tombol export diberi label jujur Segera hadir**, bukan dibiarkan tampak berfungsi. Pembangkitan Excel dan PDF dikerjakan Tahap 10 (R-26)
  * Menjelaskan alasan template luring: sinyal di lokus tidak selalu stabil
  * **DIBONGKAR 2026-08-17 atas keputusan pemilik proyek.** Halaman `/laporan` dihapus seluruhnya; ekspor dipindah menempel pada tabel data masing-masing lewat komponen `x-sim.tombol-ekspor`.
  * Alasannya halaman ini **menyalahi aturannya sendiri**: `rules.md` 12 poin 5 mewajibkan laporan dapat difilter sebelum diekspor, sedangkan halaman ini menawarkan sembilan unduhan tanpa satu pun kontrol filter. Halaman daftar sudah punya pencarian dan filter yang bekerja, jadi di sanalah ekspor seharusnya sejak awal.
  * Tab template luring **tidak dipindah** sebab ternyata duplikat: modal impor sudah memuat langkah "Unduh template" di 14 halaman. Indikator Kawasan pindah ke kepala dashboard, satu-satunya isi yang benar-benar tak punya tabel padanan.
  * Kewenangan `export` ikut dicabut dari RBAC pada kesempatan yang sama: 117 izin menjadi 96. Rinciannya pada `notes.md` bagian 5.
- [✓] ✅ Task 2.20 - Halaman pengguna dan audit log `[Sedang]` (Selesai)
  * Tiga halaman: manajemen pengguna, role dan hak akses, audit log
  * Halaman pengguna **menandai operator Per SP yang belum ditugaskan**, keadaan yang membuatnya tidak melihat data apa pun
  * Halaman role menjelaskan **dua dimensi hak akses yang terpisah**: izin menjawab boleh melakukan apa, cakupan data menjawab boleh melihat data siapa
  * Audit log memberi warna badge per jenis aksi agar tindakan berisiko langsung terlihat

**Lintas gelombang 2:**
  * Membuat komponen `x-sim.halaman-daftar` sebagai kerangka halaman daftar, agar 18 halaman tidak menyalin markup yang sama
  * Menambah **15 metode data contoh** pada `DummyData`
  * **Menutup pelanggaran R-24 yang ditemukan sebelum pekerjaan dimulai:** 18 dari 25 item menu sidebar menaut ke halaman yang membalas 404. Seluruhnya kini menaut ke halaman nyata
- [✓] ✅ Task 2.21 - Pola state kosong/memuat/galat/tanpa kewenangan di semua halaman `[Sedang]` (Sebagian: kosong dan tanpa kewenangan selesai kedua gelombang; memuat dan galat ditunda ke Tahap 3)
  * **Audit menemukan dua keadaan yang belum ada:** memuat dan galat. Keadaan kosong, pencarian nihil, dan tanpa izin sudah tersedia sejak task sebelumnya
  * Membuat `x-sim.skeleton` dengan 4 ragam bentuk: tabel, kartu, grafik, teks. **Memakai skeleton, bukan spinner layar penuh**, karena spinner menutupi seluruh halaman sehingga pengguna kehilangan konteks, sedangkan skeleton memberi tahu bentuk konten yang sedang datang (`ui-spec.md` 7)
  * Membuat `x-sim.error-state`. Pesannya menyebut penyebab yang benar-benar sering terjadi di lokus, yaitu jaringan tidak stabil, bukan istilah teknis seperti kode galat HTTP (`rules.md` 13.3 poin 7)
  * Kelima keadaan ditampilkan berdampingan pada `/galeri-komponen` agar dapat ditinjau sekaligus saat validasi
  * **Ditinjau ulang 2026-08-17 untuk gelombang 2.** Keadaan kosong, pencarian nihil, dan tanpa kewenangan **selesai** untuk kedua gelombang: 17 dari 23 halaman gelombang 2 mewarisinya lewat `x-sim.data-table` atau `x-sim.halaman-daftar`.
  * Enam halaman sisanya (`master/wilayah`, `master/satuan`, `sp/kawasan`, `kependudukan/rekap`, `laporan/index`, `pengguna/role`) **sengaja tanpa keadaan kosong**, sebab seluruhnya menampilkan data master yang di-seed bersama sistem dan tidak mungkin kosong. Bila benar-benar kosong, yang terjadi adalah kegagalan pemasangan, bukan keadaan wajar yang perlu ilustrasi ramah. Penelusuran menyeluruh menemukan hanya 3 perulangan `<tr>` tanpa `@forelse` di seluruh 70 berkas `pages/`, ketiganya agregat dashboard.
  * **Keadaan memuat dan galat DITUNDA ke Tahap 3, bukan selesai.** `x-sim.skeleton` dan `x-sim.error-state` sudah dibuat tetapi **dipakai 0 halaman kerja**, hanya tampil di `/galeri-komponen`. Seluruh halaman dirender di sisi peladen dari `DummyData` dalam satu balasan HTTP: tidak ada jeda pengambilan data dan tidak ada panggilan jaringan yang dapat gagal. Memasangnya sekarang berarti animasi memuat yang tak pernah terlihat dan jalan keluar bagi galat yang tak dapat terjadi. Keduanya bermakna sejak data diambil dari basis data.
  * Menambah rute `/uji-403` untuk meninjau tampilan tanpa izin; RBAC yang memicunya secara alami baru aktif pada Tahap 3
- [✓] ✅ Task 2.22 - Penyesuaian responsif dan uji pada lebar 360px `[Sulit]` (Selesai kedua gelombang untuk audit otomatis; pemeriksaan perangkat nyata masih menunggu manusia)
  * Audit otomatis atas 15 halaman: **0 lebar tetap melebihi 360px**, seluruh tabel dibungkus `overflow-x-auto` atau menyediakan tata letak kartu lewat slot `kartu`
  * Seluruh grid memakai awalan titik henti (`sm:`, `lg:`, `xl:`) sehingga menumpuk satu kolom pada 360px
  * **Temuan dan perbaikan:** kolom pencarian global bawaan TailAdmin berlebar tetap `w-[430px]` **dihapus seluruhnya**. Bukan sekadar karena lebarnya, melainkan karena tidak ada mesin pencari lintas modul di sistem ini sehingga kolomnya adalah kontrol mati berlabel Bahasa Inggris (R-26 dan R-02). Pencarian tersedia pada masing-masing halaman daftar
  * Dua uji otomatis ditambahkan agar pelanggaran serupa tertangkap sendiri di kemudian hari
- [✓] ✅ Task 2.23 - Verifikasi mode terang dan gelap `[Sulit]` (Selesai kedua gelombang)
  * Audit otomatis: **0 latar terang tanpa pasangan `dark:`** pada seluruh berkas halaman, komponen, dan galat
  * ~~11 pasangan warna diuji dengan rumus WCAG 2.1 lewat Node~~ **Klaim dicabut 2026-08-17: uji itu tidak pernah ada** (`uji-chart-config.mjs` 0 byte sejak masuk repo, nihil di seluruh riwayat git). Digantikan `tests/Feature/KontrasTest.php`, 13 pasangan benar-benar dihitung dan seluruhnya lulus; terendah `teal-500` + putih 4,46:1 (aksen nonteks, ambang 3:1) dan `gold-700` + `gold-50` 4,55:1 (teks, ambang 4,5:1)
  * Grafik terverifikasi menggambar ulang saat tema berganti lewat `MutationObserver`; tanpa ini sumbu dan legenda ApexCharts tetap memakai warna mode sebelumnya
  * Kelima warna badge punya varian gelap; ilustrasi galat punya berkas `-dark`
  * **Temuan dan perbaikan:** halaman 403 dan 404 mewarisi tema tetapi **tidak punya tombol untuk mengubahnya**, padahal halaman galat kerap menjadi halaman pertama yang dibuka. Tombol ditambahkan pada komponen `x-sim.halaman-galat` agar tidak disalin dua kali
- [✓] ✅ Task 2.24 - Jalankan Delivery Gate ANTISLOP `[Sedang]` (Selesai kedua gelombang)
  * Membuat `agents/delivery-gate-gelombang-1.md` berisi laporan lengkap keempat blok
  * **Keempat blok PASS:** Hard Gate 17 item, Purpose-Gate 12 item, Liveliness 7 item, Craftsmanship dan Quality Locks 14 item
  * Bukti terkuat: **0 tautan mati dari 726 tautan** yang diperiksa pada 18 halaman, 207 `aria-label`, 0 `outline-none` tanpa pengganti
  * **RITME 2 dibuktikan, bukan diklaim:** empat jenis halaman berkomposisi berbeda, dan diuji otomatis bahwa halaman rekap memang tidak memakai kartu statistik
  * **Enam perbaikan dilakukan selama gate berjalan:** kolom pencarian mati dihapus, em dash pada `ui/button` diganti, label Learn more diganti, dua `aria-label` Inggris diterjemahkan, tombol tema ditambahkan ke halaman galat, dan dua komponen keadaan dibuat
  * **Dijalankan ulang untuk gelombang 2 pada 2026-08-17,** menghasilkan `agents/delivery-gate-gelombang-2.md`. Keempat blok PASS, dengan dua butir dicatat sebagai **ditunda beralasan, bukan lolos diam-diam**: keadaan memuat dan galat menunggu backend, dan pemeriksaan 360px pada perangkat nyata menunggu manusia.
  * Laporan mencatat **tiga hal yang tetap wajib diperiksa manusia** dan tidak dapat digantikan uji otomatis: membuka tiap halaman di peramban nyata pada 360px, menjalankan seluruh alur hanya dengan keyboard, dan menguji halaman warga kepada warga sungguhan

**Catatan cakupan:** keempat task di atas semula dikerjakan untuk **halaman gelombang 1** saja, dan wajib dijalankan ulang saat gelombang 2 selesai (`rules.md` 16.1 poin 4: gate dijalankan pada akhir setiap gelombang).

**Pengulangan itu SUDAH dikerjakan 2026-08-17,** hasilnya pada `agents/delivery-gate-gelombang-2.md`. Ringkasan bukti: 23 halaman gelombang 2 seluruhnya membalas 200, **0 tautan mati dari 30 tujuan unik**, 1.207 `aria-label`, 0 `outline-none`, 0 `href="#"`, 0 em dash, 0 lebar tetap melebihi 360px, 758 kelas `dark:`, dan **13 pasangan warna yang benar-benar dihitung** menurut WCAG 2.1.

**Temuan terpenting justru mengenai gelombang 1:** klaim "11 uji kontras WCAG lewat Node" pada laporan gate pertama **tidak pernah ada ujinya**. Berkas `uji-chart-config.mjs` berukuran 0 byte sejak masuk repositori dan nihil di seluruh riwayat git. Klaim dicabut, berkas kosong dihapus, dan kontras kini diuji sungguhan lewat `tests/Feature/KontrasTest.php` yang ikut `vendor\bin\pest`. Pelajarannya dicatat pada laporan gelombang 2: angka pada laporan gate wajib dapat direproduksi oleh pembacanya.

### Perbaikan pascagate (2026-08-11)

Gate pertama dinyatakan PASS, tetapi **pernyataan itu keliru dan sudah ditarik**. Dua cacat lolos dan baru ketahuan saat pengguna membuka aplikasi:

1. **Struktur HTML `app-header.blade.php` rusak, layout hancur.** Penghapusan kolom pencarian pada Task 2.22 dilakukan lewat pemotongan indeks string; `IndexOf` berhenti di penutup `</div>` yang salah, meninggalkan `</form>` dan dua `</div>` yatim. Peramban menutup `<header>` lebih awal sehingga seluruh konten terlempar keluar wadahnya.
2. **Nama sistem pada sidebar tidak terbaca di mode terang.** Memakai `text-white` tanpa pasangan, padahal sidebar berlatar putih pada mode terang. Cacat ini ada sejak Task 1.8.

**Akar keduanya sama:** gate dijalankan tanpa pernah membuka hasilnya di peramban. 193 uji tetap hijau karena seluruhnya berbasis HTTP; PHP dan Blade tidak peduli HTML tidak seimbang, dan pemindaian kelas tidak melihat warna yang benar-benar terbaca.

**Yang diperbaiki:**
  * Struktur `app-header.blade.php` dipulihkan, kedalaman DOM kembali 0
  * Teks sidebar jadi `text-navy-500 dark:text-white`
  * **Uji keseimbangan tag HTML** atas seluruh 68 berkas Blade memakai tumpukan tag, bukan sekadar menghitung buka dan tutup
  * **Uji teks putih di atas permukaan terang**
  * Keduanya **dibuktikan menangkap cacatnya** dengan menyisipkan ulang kerusakan, lalu memastikan ujinya merah
  * Cakupan seluruh uji berbasis berkas diseragamkan lewat `Tests\Support\BerkasBlade`, menggantikan `glob()` yang ternyata **tidak rekursif** sehingga 3 berkas luput
  * Dua uji yang aturannya keliru diperbaiki: uji lebar tetap sempat membaca koordinat SVG sebagai lebar kelas, uji teks Inggris sempat menganggap komentar yang melarang sebuah teks sebagai pelanggaran teks itu sendiri
  * **Verifikasi visual lewat Edge headless** kini menjadi bagian gate: pemeriksaan DOM hasil render, tangkapan layar dua mode tema, dan pemeriksaan layar sempit

**ATURAN BARU yang mengikat seluruh pekerjaan berikutnya:**

1. **Dilarang menyunting berkas lewat pemotongan indeks string** (`IndexOf`, `Substring`, `RemoveRange` pada isi berkas). Gunakan tool editor atau pencocokan blok utuh. Kerusakan hari ini lahir persis dari cara ini.
2. **Perubahan antarmuka wajib diverifikasi visual**, bukan sekadar status HTTP 200. Minimal: `--dump-dom` untuk memastikan peramban tidak membuang tag, dan tangkapan layar pada kedua mode tema. Ini menegaskan `rules.md` 13.2 poin 8 yang sempat dilanggar.
3. **Uji baru wajib dibuktikan menangkap cacatnya**: sisipkan kerusakan, pastikan uji merah, baru pulihkan. Uji yang tidak pernah merah belum tentu bekerja.

**Cacat gate keempat, ditemukan saat memulai gelombang 2:** laporan gate menyatakan `0 tautan mati dari 726`, padahal **18 dari 25 item menu sidebar membalas 404**. Skrip gate hanya memeriksa apakah `href` kosong atau bernilai `#`, sehingga tautan ke `/poktan` dianggap sah karena bentuknya benar, tanpa pernah menanyakan apakah tujuannya ada. Klaim R-24 pada gate kedua karena itu juga ditarik.

Ini **pola kesalahan yang sama untuk keempat kalinya**: memeriksa bentuk, bukan kenyataan.

| Uji | Yang diperiksa | Yang seharusnya |
|---|---|---|
| Tag HTML | status HTTP | struktur DOM |
| Kontras | kelas CSS | warna terbaca |
| Lebar tetap | seluruh angka `w-[...]` | kelas yang berlaku di layar sempit |
| Tautan menu | bentuk `href` | tujuan benar-benar ada |

Diperbaiki dengan uji yang **membuka setiap tujuan menu ke aplikasi sungguhan**. Uji itu merah selama 18 halaman belum ada, lalu hijau sendiri setelah seluruhnya dibangun pada gelombang 2.

**Catatan keterbatasan:** Edge headless memaksa viewport minimum sekitar 496px, sehingga `--window-size=360` menghasilkan render 496px yang dipangkas. **Lebar 360px sesungguhnya belum pernah diuji** dan tetap wajib diperiksa pada perangkat nyata.

- [✓] ✅ Task 2.25 - Penilaian kondisi satuan permukiman `[Sulit]` (Selesai)
  * **Fitur baru hasil diskusi 2026-08-12.** Menilai kesiapan layanan dasar tiap SP lalu menyimpulkannya jadi satu label
  * **Istilah sengaja dipilih Mandiri, Berkembang, Perlu Penanganan.** Sebutan seperti terbelakang melabeli warga, padahal yang dinilai jalan dan listrik, hal yang berada di luar kendali mereka
  * Memperluas `JenisInfrastruktur` dengan **Sanitasi, Jalan Penghubung, dan Pasar atau Kios Saprotan**. Jalan penghubung dibedakan dari jalan produksi: yang pertama menentukan akses masuk termasuk bagi kendaraan darurat, yang kedua pengangkutan hasil panen
  * Membuat 3 enum: `TingkatKebutuhan`, `StatusKondisiSp`, `JenisFasilitas`
  * Membuat `app/Support/PenilaianKondisiSp.php` dengan 13 parameter berbobot **5 primer, 3 sekunder, 1 tersier**
  * **Aturan primer nol:** satu parameter primer yang tidak tersedia membuat SP berstatus Perlu Penanganan berapa pun skornya. Tanpa ini, SP tanpa air bersih tetapi lengkap penunjangnya dapat mencapai skor tinggi, dan angka itu menyesatkan
  * **Ketiadaan dibedakan dari kerusakan.** Parameter tanpa aset dinilai Tidak Ada (0), bukan dikeluarkan dari perhitungan; mengeluarkannya justru menaikkan skor SP yang paling membutuhkan perhatian
  * Bila ada beberapa aset sejenis, dipakai **kondisi terbaik**, sebab satu sumur yang berfungsi sudah memenuhi kebutuhan meski ada yang rusak
  * Menambah `jenis_fasilitas` pada data contoh fasilitas, 20 aset infrastruktur, dan 8 fasilitas sosial agar keenam SP punya variasi status yang bermakna
  * Membuat `tests/Feature/PenilaianKondisiSpTest.php`: **19 uji, 186 pernyataan**, termasuk uji bahwa **istilah merendahkan tidak dipakai**
  * Hasil data contoh: 1 Mandiri, 1 Berkembang, 4 Perlu Penanganan, dua di antaranya terkena aturan primer nol
- [✓] ✅ Task 2.26 - Tampilan status kondisi SP `[Sedang]` (Selesai)
  * Membuat komponen `x-sim.rincian-kondisi-sp`, menampilkan skor, badge, peringatan primer nol, dan rincian per tingkat kebutuhan
  * **Label wajib disertai rincian penyebabnya.** Tanpa rincian, label berhenti sebagai stempel: petugas tahu sebuah SP bermasalah tetapi tidak tahu apa yang harus diperbaiki
  * Dashboard mendapat **indikator ke-16**: kartu jumlah SP per status ditambah tabel per SP beserta penyebab utamanya
  * Halaman rincian SP menampilkan rincian lengkap; daftar SP mendapat kolom Kondisi berbadge
  * Tanggal penilaian ditampilkan, sebab status dari data lama dapat menyesatkan
  * Terverifikasi visual pada mode gelap lewat Edge headless

**Catatan untuk FGD:** indikator ke-16 adalah **usulan**, di luar 15 indikator pada PRD. Bobot 5/3/1 dan ambang 80/55 adalah keputusan **kebijakan**, bukan teknis, sehingga wajib divalidasi dinas. Karena bobot disimpan sebagai data, penyesuaian nanti tidak memerlukan perubahan kode.

- [✓] ✅ Task 2.27 - Manajemen pengguna dan role `[Sulit]` (Selesai)
  * Empat modal pada `/pengguna` dan `/pengaturan/role`: form akun, rincian akun, setel ulang kata sandi, form role beserta matriks izin
  * Matriks izin **27 modul x 6 aksi**, dikelompokkan sesuai `data-dictionary.md` 13.2 agar urutannya sama dengan menu sidebar
  * Sel dibiarkan kosong untuk aksi yang tidak berlaku, misalnya Dashboard yang tidak mengenal tambah maupun hapus. Kotak centang yang mustahil bermakna membuat matriks tampak menawarkan kewenangan palsu
  * **Role terkunci dirender tanpa satu pun kotak centang**, hanya tanda centang baca beserta alasannya. Merender kontrol lalu menolaknya di server melanggar R-26
  * **Tombol nonaktifkan tidak dirender untuk Admin aktif terakhir**, diganti penanda beralasan (`rules.md` 14b poin 16)
  * **Kolom kata sandi tidak dirender sama sekali pada modal ubah**, bukan sekadar dikosongkan, sebab sistem hanya menyimpan sidik (poin 14)
  * Pilihan penugasan SP muncul hanya untuk role bercakupan Per SP, ikut berubah saat role diganti (poin 2)
  * Menambah `DummyData::daftarIzin()` dan `izinRole()`, disalin dari kamus data 13.1 dan `rules.md` 5.1
  * Enam rute closure: simpan, perbarui, setel sandi, nonaktifkan, simpan role, perbarui role

**Koreksi yang ditemukan saat pengerjaan:** `rules.md` 5.1 menggabungkan Inventaris dan Fasilitas SP menjadi satu baris, sementara kamus data, ERD, dan ui-spec sejak awal memisahkannya sebagai dua tabel, dua halaman, dan dua izin. `rules.md` diperbaiki, dan `jumlah_izin` pada data contoh dikoreksi menjadi 119 / 68 / 74 / 50.

- [✓] ✅ Task 2.28 - Pemulihan kata sandi mandiri `[Sedang]` (Selesai)
  * Dua halaman baru: `/lupa-kata-sandi` dan `/verifikasi-kode`, ditambah tautan dari halaman masuk
  * **Kode enam digit yang diketik, bukan tautan sekali klik.** Kode dapat dibaca di ponsel lalu diketik di komputer, sehingga tetap berguna ketika surel dan peramban berada di perangkat berbeda
  * **Halaman tidak pernah menyatakan apakah alamat terdaftar.** Pesannya sama untuk kedua keadaan, sebab pesan yang membedakan menjadikan halaman publik ini alat memeriksa siapa saja yang memiliki akun dinas
  * **Jalur Admin dipertahankan dan disebut sejajar** pada ketiga halaman, sebab jalur itulah satu-satunya yang bekerja tanpa sambungan surel di lokus bersinyal lemah
  * Dua rute closure: kirim kode dan atur ulang sandi. Pengiriman surel sungguhan dikerjakan pada Task 3.11

**Catatan untuk FGD:** pemulihan lewat surel mencabut sebagian keputusan 2026-08-11. Alasan lama bahwa transmigran tidak memiliki surel sudah gugur, sebab warga tidak memiliki akun sama sekali. Perlu dipastikan dinas memiliki SMTP dan seluruh petugas memiliki surel dinas aktif. Bila tidak, jalur Admin sudah menutupi seluruh kebutuhan.

- [✓] ✅ Task 2.29 - Halaman rincian alsintan, saprotan, komoditas, infrastruktur `[Sedang]` (Selesai)
  * Empat modul sebelumnya hanya punya halaman daftar, sehingga tidak ada tempat menaruh tombol Ubah
  * Mengikuti pola baku sejak Task 2.7: **Tambah di halaman daftar, Ubah di halaman rincian**
  * ~~Alsintan menampilkan kepemilikan bercabang; tautan pemilik menuju poktan atau transmigran sesuai jenisnya~~ **DICABUT 2026-08-22**
  * Saprotan menegaskan bahwa penyaluran hanya untuk anggota aktif, beserta alasannya
  * Komoditas menegaskan satuan panen baku beserta penanamannya
  * Infrastruktur menegaskan batas modul: pendataan aset, bukan pelaporan kerusakan, dengan tautan ke modul pengaduan

**Cacat yang ditemukan saat pengerjaan:** data contoh alsintan memakai `'Milik Pribadi'` sedangkan enum `KepemilikanAlsintan` bernilai `'Pribadi'`. Filter kepemilikan pada halaman daftar membandingkan keduanya, sehingga memilih Pribadi **selalu menghasilkan nol baris**. Data kini memakai nilai enum langsung.

- [✓] ✅ Task 2.30 - Empat belas modal form yang tertinggal `[Sulit]` (Selesai)
  * Tahap 2 membangun 51 halaman, tetapi form isian hanya dibuat untuk 5 modul. Task 2.13 sampai 2.18 hanya menulis Membuat halaman, sehingga form-nya tidak pernah masuk lingkup
  * Akibatnya 14 modul berhalaman daftar baca-saja, dan form-nya menyatu di task CRUD Tahap 4 sampai 8
  * Tahap 4: SP, inventaris SP, fasilitas SP, kawasan, satuan, wilayah
  * Tahap 6: poktan, anggota poktan, alsintan, saprotan
  * Tahap 7: komoditas, penanaman
  * Tahap 8: infrastruktur
  * **Form SP** meminta desa dan kawasan terpisah, sebab satu SP menempel pada dua hierarki sekaligus
  * **Form satuan** menampilkan pratinjau konversi 1/10/100 satuan ke ton, agar faktor keliru terlihat saat mengisi bukan berbulan kemudian
  * **Form anggota poktan** tidak menyediakan opsi hapus; yang berhenti ditandai Sudah Keluar beserta tanggal dan alasannya
  * **Form saprotan** menyaring penerima individu hanya anggota aktif, dengan keterangan mengapa nama tertentu tidak muncul
  * ~~**Form alsintan** menampilkan pemilik bergantian menurut jenis kepemilikan, tidak pernah keduanya sekaligus~~ **DICABUT 2026-08-22:** satu jalur pemilik, selalu poktan
  * **Form fasilitas SP** memakai enum `jenis_fasilitas` agar terbaca penilaian kondisi SP, sementara `nama_fasilitas` tetap teks bebas
  * **Form wilayah** satu form untuk empat tingkat; induk berubah mengikuti tingkat, dan provinsi tidak memilikinya
  * Menambah 20 rute closure `POST`/`PUT` mengikuti pola `transmigran.simpan`

**Catatan:** Task 3.3b (halaman pengaturan role dan izin) sudah tuntas lebih awal pada Task 2.27, sehingga ditandai selesai di Tahap 3. Task 4.2 sampai 8.1 kini tinggal menyambungkan form yang sudah ada ke database.

### Revisi lanjutan (2026-08-17)

Dikerjakan atas daftar revisi pemilik proyek pada `notes.md` bagian 6, dibagi tiga kelompok. Rinciannya beserta alasan tiap keputusan tercatat di sana.

**Kelompok 1 - Kelompok tani** (4 poin). Ditemukan bahwa **anggota poktan tidak dapat diubah sama sekali**: tanpa tombol, modal, maupun rute PUT, sehingga status keaktifan dan tanggal keluar tidak pernah dapat diisi setelah tersimpan. Ketua kini boleh berasal dari luar transmigran lewat `is_ketua_transmigran`, nilai `Ketua` dicabut dari enum jabatan, kontak poktan diseragamkan menjadi kontak ketua, dan keanggotaan ditetapkan dari sisi poktan saja. Huruf `H` pada matriks kewenangan Anggota poktan dicabut karena bertentangan dengan larangan hapus; total kewenangan 96 menjadi **95**.

**Kelompok 2 - Dokumen dan pilihan panjang** (2 poin).
* **Unggahan dokumen dipasang pada 7 form** (SP, inventaris, fasilitas, infrastruktur, poktan, alsintan, saprotan). Kedelapan kolomnya sudah lama ada di kamus data dan `x-sim.file-upload` sudah dipakai lima form lain; yang tidak ada hanya isiannya. Akibatnya SK pembentukan poktan dan berita acara penyaluran saprotan tidak dapat diunggah ke mana pun.
* **Dibuat `x-sim.pilih-cari`**, dipasang pada 7 isian bersumber tabel data. Isian sesungguhnya tetap `<select>` biasa sehingga backend tidak perlu tahu komponen ini ada, dan kotak pencarian hanya dirender bila daftarnya mencapai 8 opsi.

### Revisi lanjutan (2026-08-19) — Tautan objek pengaduan ✅

Butir terakhir `notes.md` bagian 6 yang belum dikerjakan. Rinciannya beserta alasan tiap keputusan tercatat di sana.

**Berkas baru:** `app/Enums/ObjekPengaduan.php`, `resources/views/components/sim/pengaduan-terkait.blade.php`, `resources/views/pages/pengaduan/isian-objek.blade.php`, `resources/views/pages/sp/detail-inventaris.blade.php`, `resources/views/pages/sp/detail-fasilitas.blade.php`.

* **Tabel `pengaduan_objek`**, bukan dua kolom, sebab satu kejadian kerap merusak beberapa hal sekaligus. Sembilan objek nyata ditambah dua pernyataan (`belum_terdata`, `tidak_ada`) yang ber-`objek_id` NULL.
* **Objek wajib dinyatakan sebelum status maju ke Diproses** (`rules.md` 10b.6g), dipenuhi salah satu dari tiga cara. Petugas tidak dipaksa memilih dari daftar agar tidak menaut ke aset yang sekadar mirip.
* **Tab Pengaduan Terkait pada 9 halaman rincian.** Tab lama pada rincian infrastruktur diperbaiki: sebelumnya menampilkan seluruh pengaduan se-SP, bukan keluhan atas aset yang dibuka.
* **Dua halaman rincian baru** untuk Inventaris SP dan Fasilitas SP, lengkap dengan rute, tombol Rincian, dan entri `DaftarTautanStatis`.
* **Tiga penjaga privasi:** objek tidak tampil di lacak publik; rumah, lahan, hasil panen, dan alsintan hanya sebagai angka gabungan di rekap; daftar objek wajib disaring cakupan data saat RBAC aktif.
* **Rekap aset dipecah dua tabel** berdampingan, disertai kolom jumlah unit tanpa rasio otomatis.
* Kategori `Peralatan dan Perlengkapan` dipecah menjadi `Inventaris SP` dan `Fasilitas SP`; jumlah kategori 9 menjadi **10**.
* **Aturan kerja baru** dari teguran pemilik proyek: `rules.md` 19a melarang data contoh dijadikan bukti tentang lapangan, dan 20a mewajibkan penyisiran skenario sendiri. Bukti pelanggaran tercatat pada `notes.md` bagian 1c — semula tiga, menjadi **lima** setelah audit menyeluruh.
* **Verifikasi:** 453 uji hijau, `pint` tidak bertambah dari 45 berkas, `npm run build` hijau, dan **152 halaman digilas seluruhnya membalas 200** (naik dari 122).

**Koreksi pada hari yang sama — isian objek ternyata tunggal.** Ditemukan pemilik proyek: halaman rincian hanya menampilkan daftar objek yang sudah tertaut, tanpa cara menautkannya. Empat cacat, seluruhnya lolos 449 uji yang hijau:

1. Isian hanya menerima **satu objek**, sehingga kejamakan yang menjadi alasan tabel `pengaduan_objek` tidak dapat dijalankan petugas.
2. Isian hanya ada di modal penanganan, sehingga pengaduan berstatus **Selesai tidak dapat ditaut sama sekali** — melanggar `rules.md` 10b.6h yang ditulis pada hari yang sama.
3. Form ubah pengaduan tanpa isian objek.
4. Tidak ada tombol mencabut tautan.

**Sebab lolosnya:** uji memeriksa keberadaan string (`toContain('name="objek_tipe"')`), bukan kemampuan menambah baris; dan uji membaca `/pengaduan/1` yang berstatus Diproses, padahal yang rusak adalah yang berstatus Selesai. Akarnya sama — yang diuji adalah apa yang dibangun, bukan apa yang dijanjikan.

**Diperbaiki:** isian menjadi daftar baris ber-`objek[i][tipe]` dengan tombol Tambah dan Cabut; kedua pernyataan dipindah ke dalam dropdown jenis sehingga satu laporan dapat memuat objek tertaut sekaligus pernyataan; modal **Kelola Objek** tersendiri dirender tanpa syarat status dan terisi tautan yang ada; rute `POST /pengaduan/{id}/objek`.

**Uji peramban dijadikan syarat.** `tests/Browser/uji-isian-objek.mjs` lewat Edge headless + protokol DevTools, tanpa dependensi baru. **6/6 lulus**, dan uji ini langsung memerah pada percobaan pertama — menangkap hal yang tidak dapat ditiru satu pun uji string. Berkas `uji-combobox.mjs` di akar ternyata kosong 0 byte dan dihapus.

**Aturan baru:** `rules.md` 10b.6h-1, 10b.6h-2, dan bagian **16.0a** (uji menyasar janji bukan kode; string bukan bukti; dilarang memilih satu baris contoh tanpa alasan; perilaku peramban wajib diuji di peramban). Rinciannya pada `notes.md` bagian 1d.

### Pencabutan tautan objek dan bidang berbasis kategori (2026-08-19) ✅

Ditetapkan pemilik proyek pada hari yang sama: **fitur tautan objek ditiadakan seluruhnya**, digantikan penentuan bidang dinas dan filter bidang pada halaman daftar. Alasannya bukan cacat pelaksanaan melainkan pergeseran dasar keputusan — setelah ditetapkan satu laporan ditangani satu dinas, mengelola daftar objek per laporan tidak lagi menjawab pertanyaan yang sebenarnya.

**Dihapus:** `ObjekPengaduan`, komponen `pengaduan-terkait`, partial `isian-objek`, `tests/Browser/uji-isian-objek.mjs`, 7 metode `DummyData`, tab pada 9 halaman rincian, rekap "Aset paling sering diadukan", rute `pengaduan.objek`, tab rekap `objek`, bagian 10.4 dan 11.30 kamus data, aturan `rules.md` 10b.6e–8g, serta 23 uji.

**Dipertahankan:** halaman rincian Inventaris SP & Fasilitas SP (sudah berdiri sendiri, dijaga 3 uji), pemecahan kategori Inventaris/Fasilitas SP, uji privasi lacak publik, dan aturan 16.0a.

**Bidang menggantikan objek:**
* `BidangPengaduan::dariKategori()` bertipe **`?self`** — empat kategori (lahan usaha, infrastruktur, bencana, lainnya) sengaja `null` sebab dapat ditangani dua dinas.
* Nilai turunan **dapat ditimpa** petugas; penanda `disentuh` mencegah pilihan manual tertimpa saat kategori disunting.
* Kolom `bidang` jadi **nullable**, wajib terisi sebelum status maju ke Diproses.
* Kategori **Saprotan** ditambahkan; jumlah kategori 10 menjadi **11**.

**Filter bidang** pada datatable pengaduan, termasuk pilihan **Belum ditentukan beserta jumlahnya**. Kolom bidang kosong ditulis sebagai keterangan bertanda gold, bukan sel hampa.

**Cakupan `Per Bidang`** ditambahkan; Dinas Pertanian memakainya, sedangkan Dinas Transmigrasi tetap `Semua` sebab sistem ini miliknya dan merekalah penyaring awal.

**Verifikasi:** 433 uji hijau (14 baru/diperbarui), `pint` tidak menambah berkas bermasalah, `npm run build` hijau, **151 halaman digilas seluruhnya membalas 200**.

**Catatan penting:** penyuntingan lewat `Set-Content` PowerShell sempat merusak 259 karakter non-ASCII pada `data-dictionary.md`. Dipulihkan lewat `git checkout` lalu disunting ulang dengan perkakas yang menjaga encoding. Aturannya dicatat pada `notes.md` 1e.7.

**Susulan: kategori Kelompok Tani** ✅ — ditemukan pemilik proyek bahwa poktan tidak punya kategori pengaduan, padahal modul penuh. Keluhannya terpaksa masuk `Lainnya` yang berbidang kosong sehingga menambah antrean penyaringan tanpa alasan. Terlewat karena hanya sebagian dari sederet nilai yang disebut pemilik proyek diperiksa terhadap keadaan sistem.

Penyisiran menyeluruh atas **26 fitur berkewenangan** menemukan **tepat satu** yang terlewat. Modul yang sengaja tidak berkategori (internal sistem, data referensi, data pribadi transmigran) kini tercatat beserta alasannya, kewajiban pemetaan lengkap dua arah masuk `rules.md` 10b.3a, dan ditambah uji penjaga yang mengadu daftar modul dengan daftar kategori.

Jumlah kategori 11 menjadi **12**; ditambah contoh `PGD-2026-0009`. Verifikasi: **435 uji hijau**, `pint` tidak menambah utang, build hijau, **153 halaman digilas seluruhnya 200**.

### Audit menyeluruh `rules.md` 19a (2026-08-19) ✅

Butir tindak lanjut 9 pada `notes.md` bagian 4, dikerjakan atas permintaan pemilik proyek. Seluruh **992 baris** `notes.md` disisir terhadap aturan larangan memakai data contoh sebagai bukti tentang lapangan.

**Hasil:** 36 keputusan menyebut data contoh sebagai alasan — **5 cacat menyangkut struktur data**, 4 ragu, 4 hanya tampilan, dan **23 sah** karena menjawab pertanyaan tentang kode.

**Dua pelanggaran baru ditemukan** di luar tiga yang sudah tercatat:
* **`PeruntukanLahan` I/II** — satu-satunya yang kerusakannya sudah nyata: enum dipasang lalu dicabut pada hari yang sama. Keterangan pemilik proyek sekaligus membatalkan keputusan 2026-08-10 yang juga tak berdasar lapangan, sehingga satu penalaran melingkar menutupi yang lain selama delapan hari.
* **Kontak poktan** — satu-satunya yang membatalkan alasan lapangan yang sudah benar demi menyesuaikan dokumen pada bentuk `DummyData`. Melahirkan **bentuk keempat** pada 1c.1 yang arahnya terbalik.

**Dua pertanyaan lapangan dijawab pemilik proyek, bukan disimpulkan:** poktan tidak punya kontak sendiri (keputusan bertahan, alasan diperbaiki), dan dinas perlu impor massal musim tanam (pengecualian dicabut, fitur ditambahkan).

**Perubahan fungsional:** impor musim tanam (fiturnya dicabut 2026-08-22). Modul berimpor 14 → **15**; daftar pengecualian 6 → **5**.

**Perubahan dokumen:** bagian 1c diperluas dari 3 jadi 5 pelanggaran plus bagian 1c.4 dan 1c.5; alasan cacat pada dokumen lahan dan ambang dropdown ditandai dicabut; `rules.md` 19a ditambah poin 13 dan 14; tiga butir tindak lanjut baru.

**Verifikasi:** seluruh uji hijau, `pint` tidak menambah utang, build hijau, halaman digilas seluruhnya 200.

### Audit field form tanpa tempat tampil (2026-08-25) ✅

Diminta pemilik proyek menyusul temuan catatan dan unggahan yang dapat diisi tetapi tidak pernah terbaca kembali. Seluruh **24 berkas form** disisir terhadap halaman rincian pasangannya.

**Hasil:** **8 field** tersimpan tanpa tempat tampil, tersebar di 7 modul. Foto pada saprotan dan alsintan, bukti dari pelapor pada pengaduan, alamat ketua pada poktan, telepon serta alasan keluar pada anggota poktan, catatan dan dokumen pada SP serta kawasan, dan `email_pelapor` yang bahkan tidak pernah tersimpan.

**Akar masalahnya** form dan rincian dikerjakan terpisah. Tambalan 2026-08-20 sudah membereskan `keterangan` dan `dokumen_pendukung` di enam modul, tetapi melewatkan kolom `foto` yang pemisahannya menyusul dan hanya diteruskan ke inventaris serta fasilitas.

**Mengapa 605 uji tidak menangkapnya:** uji penjaganya sudah ada dan namanya tepat, tetapi angka ekspektasinya ditulis dari hasil pengamatan. "1 tautan berkas" pada alsintan dan saprotan mengunci keadaan cacat sebagai kebenaran.

**Perubahan:** 6 berkas rincian dan 1 halaman daftar disunting; dua sisa refactor berupa judul tanpa isi dibersihkan di saprotan dan poktan; `DummyData` menerima 6 kunci baru agar setiap tempat tampil punya contoh isi.

**Kekeliruan diksi pada pekerjaan yang sama:** label ditulis "Surel", melanggar `ui-spec.md` 10.1. Lolos karena daftar uji anti-surel hanya memuat halaman auth dan publik, tanpa satu pun halaman rincian. Diperbaiki beserta cakupan ujinya.

**Verifikasi:** 607 uji hijau, dua mutasi memerah sebagaimana mestinya. Terverifikasi pula di halaman terender, bukan hanya pada berkas sumber.

### Audit menyeluruh antarmuka (2026-08-25) ✅

Diminta pemilik proyek: memeriksa seluruh pekerjaan sampai titik ini. Lingkupnya antarmuka saja, sebab Tahap 2 belum menyentuh backend. Disisir **128 Blade** (22K baris), **132 rute**, **609 uji**, dan `DummyData` 3.879 baris lewat empat penelusur paralel, lalu setiap temuan diverifikasi ulang sendiri.

**Yang sudah benar, dicatat lebih dulu:** tautan mati nol, `scope="col"` lengkap 100%, warna sebagai satu-satunya makna nol, tab kosong nol, `@csrf` lengkap, 57 rute GET seluruhnya tersentuh uji.

**Hasil:** **9 temuan**. Tiga kritis — form masuk tidak dapat dikirim, penelusuran 17 grafik dashboard 404 di situs terbit, dan fondasi tabel `user` bertabrakan dengan kamus data. Dua berdampak luas — empat tombol ikon tanpa nama di header yang muncul di semua halaman, dan focus trap hilang di dialog **hapus** yang dipakai 21 halaman. Empat sisanya kebersihan.

**Dikerjakan paket temuan 1, 2, 4, 5** atas persetujuan pemilik proyek. Form masuk dirangkai **tanpa autentikasi**, mengikuti pola ketiga form auth tetangganya; alamat dasar grafik dioper dari Blade; empat `aria-label` ditambah beserta penggantian "Notification" dan pencabutan dua `console.log`; focus trap disalin dari `modal-form` yang sudah terbukti.

**Ditunda dengan sengaja:** fondasi `user` ke Tahap 3, ditambah `<caption>` nol, 15 komponen yatim, dan 37 path absolut pada prop aksi. Seluruhnya beserta alasannya tercatat pada `notes.md` 1g.7, dan tiga butir tindak lanjut baru ditambahkan.

**Mengapa 609 uji tidak menangkapnya:** ketiganya lolos lewat sebab berbeda, dan hanya satu menyangkut kerumitan. Yang terpenting, uji lama justru **mengunci** kekeliruan grafik — ia memeriksa nama pemanggilan `drilldownSp(data.spId)`, bukan alamat yang dituju, sehingga akan tetap hijau selamanya. Larangan path absolutnya sendiri sudah tertulis di `notes.md` 1b.3 sejak 2026-08-17 tanpa satu pun penjaga.

**Perubahan:** 5 berkas antarmuka dan 1 rute disunting; **5 penjaga baru** (7 kasus uji) ditambahkan, seluruhnya diperiksa dari berkas sumber dan dibuktikan lewat mutasi.

**Verifikasi:** **616 uji hijau** (609 → 616), 3.728 asersi. `npm run build` sukses dan bundel terbukti sudah bersih dari alamat mutlak.

**Catatan:** sesi pengerjaannya terputus karena galat penyedia model tepat sebelum pencatatan. Dokumentasi disusun ulang 2026-08-27 dari riwayat sesi, lalu dicocokkan terhadap keadaan berkas yang sebenarnya. Lihat `notes.md` 1g.8.

### Pemindahan pengambilan data dari view ke rute (2026-08-27) ✅

Butir tindak lanjut 12 pada `notes.md`, yaitu ide C hasil audit 1g.7. Disetujui pemilik proyek, dikerjakan dalam **sembilan batch** yang masing-masing diuji dan dicommit terpisah.

**Hasil:** **212 pemanggilan `DummyData::` di 65 berkas Blade → nol**, dan kini dijaga uji.

**Mengapa sekarang:** selama view mengambil datanya sendiri, migrasi ke Eloquent pada Tahap 4 bukan pekerjaan controller melainkan penyuntingan 65 view, dan setiap pemanggilan di dalam perulangan berubah menjadi satu kueri per baris. Selagi sumbernya array, pemindahannya hanya mengubah `return view('x')` menjadi `return view('x', [...])`.

**Dua jalur.** Halaman berrute menerima data dari rutenya. Berkas form dan komponen bersama menerima rujukannya dari `ViewServiceProvider` — satu berkas form disisipkan tiga modal sekaligus, sehingga menyalurkannya lewat rute menuntut tiga rute mengoper isian yang sama persis, dan satu yang terlewat menghasilkan dropdown kosong tanpa galat apa pun.

**Temuan paling berharga: tujuh N+1**, tidak satu pun terlihat sebelum penyisiran, seluruhnya berbentuk sama yakni satu pemanggilan yang menelusuri seluruh tabel diletakkan di dalam `@foreach`. Yang terparah `poktan/form` dan `poktan/form-anggota`: keduanya memanggil `rekapLahanKeluarga()` untuk SETIAP keluarga lewat perulangan yang ditulis dua kali, dan kedua form dapat muncul pada halaman yang sama.

**Tiga rekap beserta lacak pengaduan** masing-masing dirender dua rute; sebelumnya kedua rute merender view yang menyusun datanya sendiri, sehingga tidak ada satu tempat pun yang dapat disebut sumbernya. Kini dipusatkan pada closure bersama, dan terbukti kedua jalur menghasilkan tabel identik.

**Dua uji penjaga diperbaiki**, keduanya memerah tanpa satu pun perilaku berubah. Yang satu mengunci dari mana data diambil; yang lain mengunci berkas mana yang menghitungnya. Pemeriksaan sumber pada uji kedua sengaja **dipertahankan**, sebab data contoh tidak dapat membedakan implementasi benar dari yang salah — hanya cakupannya yang diperluas.

**Penjaga baru:** view dilarang memanggil `DummyData` sama sekali, dibuktikan lewat mutasi. Wajib ada justru karena pelanggarannya tidak memerahkan apa pun.

**Verifikasi:** **617 uji hijau**; seluruh **55 rute GET** yang membalas 200 disisir dan tidak satu pun memuat variabel hilang, `<select>` kosong, maupun sisa `DummyData` pada keluaran; `pint` tidak menambah utang di seluruh batch.

### Temuan 6 audit: setiap tabel diberi nama (2026-08-27) ✅

Nol `<caption>` menjadi seluruh tabel bernama, dijaga uji. Dua tahap, dua commit.

**Taksiran audit meleset.** Audit menulis "akarnya cuma 2 komponen"; nyatanya **26 dari 46 tabel ditulis langsung di halaman**, tiga belas di antaranya pada dashboard kawasan saja. Tabel mentah justru lebih banyak daripada yang lewat komponen.

**Tahap 1:** `data-table` dan `tabel-ringkas` menerima prop `judul`. Penyalurannya murah pada yang pertama — `halaman-daftar` sudah memegang judul halaman, sehingga seluruh halaman daftar memperoleh caption tanpa satu pun disunting. Ke-19 pemanggil `tabel-ringkas` diberi judul satu per satu.

**Tahap 2:** 26 tabel mentah, ditambah penjaganya.

**Penjaganya memeriksa POSISI**, sebab `<caption>` wajib anak pertama `<table>`; yang diletakkan sesudah `<thead>` diabaikan sebagian pembaca layar, dan itu kegagalan yang tidak terlihat sama sekali. Dibuktikan lewat mutasi.

**Verifikasi:** **618 uji hijau**; lima halaman terender diperiksa dan jumlah `<table>` selalu sama dengan jumlah `<caption>`; `pint` tidak menambah utang.

### Temuan 8 audit: alamat aksi tidak lagi berakar domain (2026-08-27) ✅

Butir tindak lanjut 13. **37 pemanggil** mengoper alamat mentah semacam `/alsintan/3` pada `:hapus-url` dan `pola-aksi`; pada penyajian statis bersub-path seluruhnya mengirim ke akar domain dan tidak pernah sampai.

**Diperbaiki di akarnya, dua komponen saja.** `aksi-baris` dan `modal-form` membungkus alamatnya dengan `url()` mengikuti pola `stat-card`. Ketiga puluh tujuh pemanggilnya **tidak disentuh sama sekali**, dan yang menambah pemanggil baru tidak perlu mengingat aturannya.

Penanda `:id` pada pola aksi dibiarkan utuh dan itu diperiksa, bukan diasumsikan: `url()` tidak meng-encode kolonnya, sehingga Alpine tetap dapat menggantinya di sisi klien.

**Lima kemunculan lagi ditemukan penjaganya**, bukan oleh audit: dua tombol pada `pengguna/index`, satu pada `pengguna/role`, dan dua contoh pada `galeri-komponen`, seluruhnya memanggil `buka-konfirmasi` langsung tanpa lewat `aksi-baris`.

**Penjaganya memeriksa keluaran terender, bukan sumber.** Sejak komponennya membereskan, alamat mentah di pemanggil tidak lagi keliru, sehingga uji berbasis sumber justru akan melarang kode yang benar. Ia menyisir seluruh rute GET yang membalas 200, dan punya penjaga terhadap dirinya sendiri berupa ambang jumlah halaman terperiksa. Dibuktikan lewat mutasi.

**Verifikasi:** **619 uji hijau**; terbukti pula `url()` mengikuti akar yang dipaksakan saat `ASSET_URL` terisi; `pint` tidak menambah utang.

### Temuan 7 audit: cabut komponen bawaan yang yatim (2026-08-27) ✅

Butir tindak lanjut 14. **26 berkas, 902 baris** dicabut: 13 komponen Blade beserta 13 kelas View Component-nya. Direktori `ui/` dan `form/` ikut habis.

**Jumlahnya 13, bukan 15** seperti tertulis audit. Penyisiran pertama bahkan sempat melaporkan **nol** yatim, dan itu keliru: setiap kelas View Component menyebut nama viewnya sendiri sehingga terhitung sebagai pemakai. Hasilnya bukan angka yang salah melainkan daftar yang sunyi.

**Mengapa bertahan lama:** polanya diserap ke `x-sim.*`, bukan dibungkus. `status-badge` mengambil pola `ui/badge` lalu menulis markupnya sendiri, tidak pernah memanggilnya. Basisnya karena itu mati sejak hari pertama pemakainya lahir, dan komponen mati tidak memerahkan apa pun.

**`ui-spec.md` ikut disunting di tiga tempat.** Satu klaimnya ternyata sudah keliru bahkan sebelum pencabutan ini: §6.6 menulis `status-badge` dibangun *di atas* `x-ui.badge`, padahal markupnya berdiri sendiri.

**Penjaganya** menolak komponen tanpa pemakai, tidak menghitung kelas View Component sebagai pemakai, dan mencatat bahwa `error-state` serta `skeleton` wajib ditimbang ulang saat `galeri-komponen` dihapus (butir tindak lanjut 15 baru). Dibuktikan lewat mutasi.

**Verifikasi:** **620 uji hijau**; utang `pint` justru **berkurang** dari 32 menjadi 31, sebab `ui/Modal.php` termasuk yang selama ini gagal.

### Ide B audit: angkat blok tombol menjadi komponen (2026-08-27) ✅

**Butir terakhir audit 2026-08-25. Seluruh auditnya kini tuntas.**

`x-sim.aksi-daftar` menggantikan blok Impor + Tambah pada 14 halaman; `x-sim.tombol-filter` menggantikan blok Terapkan + Bersihkan pada 16 halaman. **445 baris hilang dari halaman**, ditukar dua komponen berisi 80 baris.

**Yang dikejar satu sumber, bukan jumlah baris.** Duplikasi sepanjang dua ratus karakter kelas Tailwind tidak bertahan seragam: cukup satu halaman disunting sendiri dan sisanya menyimpang tanpa ada yang menyadari.

**Variasi ditampung, bukan dipaksa seragam.** Penjaga izin `@if ($bolehTambah)` berpindah menjadi ekspresi pada prop; tautan "Lihat Rekap Panen" yang menyela kedua tombol diterima lewat slot di posisi aslinya; `panen/rekap` dikecualikan beserta alasannya sebab wujudnya memang berbeda.

**Verifikasi terkuat:** seluruh **55 halaman terender dicuplik sebelum dan sesudah**, dan **tidak satu pun berbeda**. Penormalannya tidak langsung ketemu — percobaan pertama melaporkan 54 dari 55 berbeda akibat token CSRF, lalu tersisa 14 akibat id DOM `uniqid()` pada komponen peta. Kontrol dua cuplikan atas kode identik yang membuktikan selisih itu derau.

**Satu uji penjaga diperbaiki**, yang ketiga dalam sehari yang mengunci mekanisme alih-alih tujuan.

**Verifikasi:** **621 uji hijau**; penjaga baru menolak penulisan ulang kedua blok, dibuktikan lewat mutasi; `pint` tidak menambah utang.

### Revisi Putaran 1: fondasi pelaporan panen (2026-08-27) ✅

Rombongan A butir 1–2 dari `notes.md` bagian 6, ditambah butir mandiri D1 dan D2. Pemicunya pertemuan dengan Dinas Pertanian: laporan hasil panen dikelompokkan menurut **tahun anggaran bantuan**, bukan tahun panen.

**Fondasi datanya saja.** Halaman laporan dan penyaring rentang tahun ditunda ke putaran berikutnya.

**Yang dikerjakan:**
- `saprotan` dapat kolom `tahun_pengadaan` (YEAR wajib), `varietas` (wajib bila benih), `jadwal_tanam` (rencana, `YYYY-MM`)
- Penyimpangan nama field saprotan dibereskan: `tanggal_perolehan`→`tahun_pengadaan`, `sumber`→`sumber_dana`; `tahun_perolehan` dan `tanggal_penyaluran` dicabut dari kamus (tak pernah diimplementasikan)
- `alsintan` dapat kolom `penanda_terima_id` — penanda tangan serah terima, BUKAN pemilik; `rules.md` 7b.1 tidak disentuh
- `rules.md` §20b baru: rencana lengkap wajib ditulis ke `session-notes.md` sebelum eksekusi

**Rantai laporan tidak perlu kolom penghubung baru:** `hasil_panen → penanaman.saprotan_id → saprotan.tahun_pengadaan`, seluruhnya sudah ada.

**Basis tahun dipisah, bukan diganti:** rekap tetap tahun panen ("apa yang terjadi tahun ini"), laporan pakai tahun pengadaan ("apa hasil bantuan 2025").

**Verifikasi:** **623 uji hijau** (naik 2); dua penjaga baru — varietas bersyarat dan rantai laporan lintas tahun — dibuktikan lewat mutasi; render nyata 5 halaman diperiksa; `pint` tetap 31.

**Belum dibahas:** Rombongan B (anggota keluarga + usia/agama), Rombongan C (field SP dari Monografi), butir lain bagian 6.

### Revisi Putaran 2: menu Laporan + filter rentang tahun (2026-08-28) ✅

Rombongan A butir 3–5 dari `notes.md` bagian 6. Kerangka saja; isi kolom tiap laporan (Tahap 2c) menunggu format dari dinas.

**Yang dikerjakan:**
- `rules.md` §12 ditulis ulang (poin 5–14): keputusan 2026-08-17 dibalik — laporan adalah dokumen bernama, bukan tombol ekspor yang menempel di tiap tabel
- Komponen `tombol-ekspor` dihapus dari kerangka `halaman-daftar` + 9 halaman, lalu berkasnya dihapus (kontrol mati R-26)
- Menu "Laporan" baru (ikon `laporan`) berisi 8 tautan: ikhtisar `/laporan` + 7 halaman laporan
- 7 halaman laporan kerangka lewat `x-sim.kerangka-laporan`: judul, cakupan sebagai teks, penampung tabel, tombol unduh jujur "segera hadir". Laporan Alsintan dan Saprotan **terpisah**
- `x-sim.filter-rentang-tahun` (sepasang select dari–sampai) menggantikan penyaring tahun tunggal di `/panen` dan `/penanaman`; `/audit-log` dapat filter tahun untuk pertama kalinya
- Penyaringan dipusatkan di `DummyData::saringRentangTahun()` (batas kosong / terbalik / tahun hilang ditangani seragam)
- **Rekap agregat dikecualikan tegas** dari filter rentang (`/panen/rekap` tetap tahun tunggal): §9 poin 8b, luas terhitung ganda lintas tahun

**Verifikasi:** **635 uji hijau** (naik dari 623); penjaga baru untuk pencabutan tombol ekspor, menu Laporan, kerangka tiap laporan, dan penyaringan rentang; render nyata `/laporan` + 7 laporan + 3 halaman berfilter; `pint` tetap 31.

### Revisi Putaran 2c: isi kolom 7 halaman laporan (2026-08-28) ✅

Lima laporan mengikuti berkas rujukan di `refs/` (dibaca lewat `pdftotext`, baca gambar, `antiword`, unzip xlsx); dua dirancang dari kolom data yang ada.

**Yang dikerjakan:**
- `app/Support/LaporanData.php` baru: 1 metode per laporan; view tetap tak memanggil `DummyData`
- `x-sim.kerangka-laporan` merender tabel bila diisi lewat slot
- Laporan Hasil Panen (kolom Polri MT. I 2025), dikelompokkan per SP + subtotal + total kawasan; Belum Dipanen = tanam - panen - puso
- Laporan Alsintan (kolom berkas gambar), per SP + subtotal jumlah unit
- Laporan Saprotan (kolom berkas gambar), **dua bagian**: benih penuh + non-benih penyalurannya saja
- Laporan Monografi SP: satu baris indikator per SP (monografi penuh menunggu Rombongan C)
- Rekap Indikator Kawasan: identitas kawasan + blok indikator + rincian per SP
- Laporan Daftar Poktan (kolom xlsx): anggota per poktan + subtotal luas
- Laporan Daftar Transmigran: tiga bagian (transmigran, rumah, lahan)

**Ditemukan lalu DIBERESKAN 2026-08-28:** nama field alsintan (`tahun_perolehan` / `sumber_perolehan`) menyimpang dari saprotan (`tahun_pengadaan` / `sumber_dana`) dan dari kedua berkas rujukan. Diseragamkan: `alsintan` kini memakai `tahun_pengadaan` / `sumber_dana`; `inventaris_sp`/`fasilitas_sp`/`infrastruktur` tetap `tahun_perolehan`. Lihat `notes.md` §1o.4.

**Verifikasi:** **648 uji hijau** (naik dari 635); penjaga baru untuk isi tabel, kolom kunci, konsistensi subtotal-total, pemisahan benih/pupuk. `pint` tetap 31.

**Ditunda:**
- ~~Rombongan B: anggota keluarga + usia/agama~~ ✅ selesai (B1+B2+B3), lihat di bawah
- ~~Rombongan C: field SP Bab II Monografi~~ ✅ selesai (C1+C2+C3), lihat di bawah
- ~~Pintasan laporan dari halaman daftar (bawa filter aktif)~~ **GUGUR 2026-08-29** — digantikan filter per laporan (Putaran 3 D3). Pewarisan hanya perlu bila halaman laporan tak punya filter sendiri.
- ~~Pemilih periode untuk laporan lintas modul (Rekap Indikator Kawasan, Daftar Transmigran)~~ **GUGUR 2026-08-29** — alasan sama.
- ~~Penyeragaman nama field alsintan ke `tahun_pengadaan` / `sumber_dana`~~ ✅ selesai 2026-08-28
- Butir bagian 6 lain yang belum dibahas

### Revisi Rombongan B: pendataan anggota keluarga (2026-08-28) — bertahap

Membalik `erd.md` §7.4 ("sistem tidak mendata anggota keluarga satu per satu") atas permintaan pemilik proyek. Lingkup penuh: pendataan + jumlah turunan + rombak `anggota_poktan` + rombak suksesi KK.

- **Stage B1 ✅** — fondasi + modul transmigran: enum `Agama` / `HubunganAnggotaKeluarga` / `KegiatanAnggota`; tabel `anggota_keluarga` (29 baris contoh); `transmigran.agama`; `jumlah_anggota_keluarga` jadi turunan; usia dihitung; form repeater dinamis bersyarat; detail tab Anggota Keluarga. 654 uji hijau, pint 31. `erd.md` §7.4 direvisi berjejak. Lihat notes.md 1p.
- **Stage B2 ✅** — `anggota_poktan.anggota_keluarga_id` + `poktan.ketua_anggota_keluarga_id` (FK); `form-anggota` + `poktan.form` jalur "Anggota Keluarga" memilih orangnya dari daftar (`x-for`, menyempit per keluarga); `nama_wakil`/`nik_wakil`/`hubungan_dengan_kk`/`hubungan_ketua` dicabut, dibaca dari `anggota_keluarga`. `DummyData::poktan()` kini menyelesaikan identitas ketua (memperbaiki ketua kosong di daftar poktan berketua kepala keluarga). 656 uji hijau, pint 31.
- **Stage B3 ✅** — suksesi KK: `DummyData::calonPenggantiKk()` (pasangan lalu usia tertua); modal `<select pengganti_anggota_keluarga_id>` + isian tersembunyi dibaca dari pilihan; `riwayat_kepala_keluarga.hubungan_pengganti` beralih ke §11.39; rute Tahap 5 juga menghapus baris `anggota_keluarga` pengganti. `rules.md` §6.5d, `erd.md` §7.4a, `data-dictionary.md` §6.4/§11.35 direvisi berjejak. 657 uji hijau, pint 31.

**Rombongan B SELESAI seluruhnya (B1+B2+B3).** `erd.md` §7.4 dibalik penuh: sistem kini mendata anggota keluarga satu per satu.

### Revisi Rombongan C: field Keadaan Wilayah SP (2026-08-28) — bertahap

Field Bab II Laporan Monografi (Keadaan Wilayah) pada modul SP.

- **Stage C1 ✅** — 3 enum (`PolaPermukiman`/`TingkatKesuburanTanah`/`BentukWilayah`); ~35 kolom baru pada `satuan_permukiman` (letak astronomis kotak, jarak ekonomis, batas wilayah DIHIDUPKAN, SK pencadangan, pola, tanah, topografi, iklim min/maks/rata, sumber air); `keadaanWilayahSp()` di DummyData (Kapitan Meo dari berkas monografi); section "Keadaan Wilayah" pada form SP; blok tampil di `dashboard/sp`. 660 uji hijau, pint 31. `data-dictionary.md` §3.6/§3.6a/§11.41-43, `rules.md` §4a, `notes.md` bagian 6 batas + 1q.
- **Stage C2 ✅** — tabel `rute_aksesibilitas_sp` (17 baris; SP Kapitan Meo 5 baris dari Tabel 2.1 monografi); dynamic repeater "Rute Aksesibilitas" pada form SP; tabel tampil (dengan `<caption>`) di `dashboard/sp`. Label catatan repeater = "Catatan" (penjaga label). 660 uji hijau, pint 31. `notes.md` 1q.5.
- **Stage C3 ✅** — Laporan Monografi SP merender Bab II penuh per SP (Letak, Batas, Luas & Bentuk, Tanah, Topografi, Iklim, Sumberdaya Air, Aksesibilitas), didahului tabel ikhtisar indikator. `LaporanData::monografiSp()` kembalikan `baris` + `monografi`; helper `angka()`/`rentang()`/`bab2()`. Nilai kosong -> "belum dicatat". 661 uji hijau, pint 31. `notes.md` 1q.6. **Rombongan C selesai.**

### Putaran 3: Halaman Laporan diperbaiki (2026-08-28/29) — bertahap

Peninjauan pemilik proyek atas menu Laporan hasil Tahap 2c: "berantakan". Tiap laporan diberi filter sendiri; isinya disajikan sebagai dokumen berbingkai dengan "buka di tab baru". Rincian di `notes.md` §1r.

- **Stage D1 ✅** (commit `5bf52b0`) — fondasi data: `sp_id`/`poktan_id` dibawa keluar `LaporanData`; `kelompokkanPerSp()` per id SP (menutup cacat dua SP senama lebur); `LaporanData::angka()` publik + penjaga `$desimal > 0` (dulu 7 salinan, bug "1.200"→"1.2"); `jumlah_anggota` keluar dari subtotal hasilPanen; `laporan/transmigran` `@foreach`→`@forelse`. **662 uji hijau**, pint 31, tanpa perubahan uji.
- **Stage D2 ✅** (commit `9c4076c`) — kertas berbingkai: `kerangka-laporan` membungkus isi dalam `<article>` `.kertas-dokumen`; `LaporanData::meta($slug)` memusatkan metadata kepala; badan tiap laporan dipisah ke `pages/laporan/isi/{slug}`, di-`@include` halaman berbingkai + rute dokumen generik `pages/laporan/dokumen`; `layouts/dokumen.blade.php` baru (polos); rute `/laporan/{slug}/dokumen` (`laporan.dokumen`); tombol "Buka di tab baru"; `@media print` pertama + `.cetak-sembunyi`. Bug `{{-- --}}` bersarang di doc-comment `kerangka-laporan` ikut diperbaiki. **662 uji hijau**, pint 31.
- **Stage D2b ✅** (commit `a4421de`) — orientasi + garis: `LaporanData::KOLOM_LANDSCAPE = 9`, `meta()` + kunci `kolom`, `orientasi($slug)`. 6 laporan landscape, Indikator Kawasan potret. `@page` pertama (via `@push('gaya')` → `@stack('gaya')` di kedua layout). `.tabel-dokumen` (CSS telanjang, tak boleh ber-`>`) pada 12 tabel; `divide-y` dicabut. Cetak: garis digelapkan, `thead` diulang, tabel landscape `8pt`. Baris total: `border-t-2 border-gray-300` → `motif-baris-total` (`ui-spec.md` §2.3). Celah nol-cakupan rute dokumen ditutup. **678 uji hijau**, pint 31. `uji-lebar-dokumen.mjs` 28/0.
- **Stage D3 ✅ SELESAI** — filter per halaman laporan (Alpine sisi peramban, bukan query string). Ketujuh laporan berfilter. Catatan di `notes.md` §1t (§1t.8 = ringkasan pola per struktur). `pest` 690, `pint` 31, `uji-filter-laporan.mjs` 46/0.
  - **D3-1 ✅** — pola filter + Laporan Transmigran. Baru: `resources/js/filter-laporan.js` (`Alpine.data('filterLaporan')`), `x-sim.filter-laporan` (bilah selalu tampak, `.cetak-sembunyi`), `LaporanData::filterLaporan($slug)`. `x-data` pada `<article>` kerangka-laporan supaya kalimat cakupan kepala kertas ikut bereaksi (rules §12 poin 8). Nomor urut lewat penghitung CSS (baris `display:none` tak menaikkan penghitung). `uji-filter-laporan.mjs` 18/0, pest +5 (684).
  - **D3-2 ✅** — Laporan Poktan. Satu tabel per poktan (milik tepat satu SP) → penyaring SP menyembunyikan wadah tabel utuh, bukan baris. Helper JS `kosong()`. `uji-filter-laporan.mjs` 21/0.
  - **D3-3a ✅** — Laporan Alsintan (grup per SP). Baris data ber-`data-*`; grup-header + subtotal `x-show="!kosong(...,selSp(id))"`; sel subtotal & total `x-text="jumlahTampak(...)"`; baris total menyatakan cakupan (§8o). Helper JS: `_baris()` (elemen ATAU NodeList), `selSp()`, `rasioTampak()`. `uji-filter-laporan.mjs` 28/0.
  - **D3-3b ✅** — Saprotan. Ternyata dua tabel datar (benih + non-benih), TANPA subtotal → pola Transmigran, bukan Alsintan. Dua dimensi: Komoditas Benih (baris benih) + Jenis Sarana non-benih (baris non-benih), satu bilah melayani keduanya. `cocok()` diperketat: atribut data `''` = tidak ada. `uji-filter-laporan.mjs` 32/0.
  - **D3-3c ✅** — Hasil Panen (grup per SP, pola Alsintan). 7 kolom angka; produktivitas subtotal/total via `rasioTampak` (Σ produksi / Σ realisasi panen, bukan rata-rata). `data-tahun` = tahun anggaran bantuan §16a, label "Tahun Anggaran Bantuan". Closure Blade `$selHitung()`. `uji-filter-laporan.mjs` 37/0.
  - **D3-5 ✅** — Monografi SP. Potret per SP: pemilih SP menyembunyikan baris ikhtisar + `<section data-baris data-sp>` Bab II. Tanpa rentang tahun. `uji-filter-laporan.mjs` 41/0.
  - **D3-4 ✅** — Rekap Indikator Kawasan. `rekapPerSp()` sudah punya 5 indikator per SP yang berjumlah persis ke dashboard → tak perlu mengarang data. Tabel per SP menyempit (`x-show` + `<tfoot>` `x-text` + §8o); 4 blok ringkasan kawasan TIDAK menyempit (dari dashboard; catatan kejujuran `x-show="adaFilter"`). Penjaga Σ-SP = angka kawasan untuk 5 indikator jumlah. `uji-filter-laporan.mjs` 46/0.
- **Stage D4 ✅** (Putaran 4, sebagian di `03558ff`) — dokumen acuan: `rules.md` §12 poin 5-13, `ui-spec.md` §6.2/§6.9/§6.11/§4.9, `prd.md` §7.9. Dua butir tunggu GUGUR (lihat blok "Ditunda").

### Putaran 4: Submenu Laporan disatukan + Form Transmigran bertahap (2026-08-29)

Rencana lengkap di `agents/session-notes.md`; catatan tetap di `notes.md` 1s.

- **Stage E1 ✅** — nama & urutan laporan disatukan ke `LaporanData::meta()` (kunci `judul`+`izin`, urutan larik = urutan submenu); `MenuHelper` & `routes/web.php` menurunkan dari sana; `kerangka-laporan` baca judul dari `meta()` langsung. Submenu diurut ulang & dua laporan diganti nama (Laporan Transmigran, Laporan Poktan). Halaman `/laporan` **dibongkar** (butir "Semua Laporan", rute `laporan.index`, `pages/laporan/index.blade.php`, tombol "Kembali"). `sim:tautan-statis` 223→222. Penjaga baru: urutan submenu `toBe([...])`, satu sumber nama, `/laporan`→404.
- **Stage E2 ✅** — `x-sim.modal-form` prop opsional `langkah` (larik nama; tanpa prop tak berubah). Form transmigran 4 langkah: Identitas / Penempatan / Anggota Keluarga / Catatan dan Berkas. `required` tetap statis; Lanjut & Simpan memvalidasi per langkah dan **melompat** ke langkah bermasalah (bukan menolak diam-diam — cacat 1877/2197/2299). Prop dipasang pada 3 pemakaian transmigran saja. `uji-gulir-modal.mjs` kasus "form panjang" dipindah transmigran→SP. Uji peramban baru `uji-form-transmigran.mjs` (10/0).
- **Verifikasi:** pest 679 (dari 678), pint 31, tautan-statis 222, `uji-form-transmigran` 10/0, `uji-gulir-modal` 24/0, `uji-lebar-dokumen` 28/0.

### Putaran 5: "Generate Laporan" — dokumen resmi + filter dibawa ke tab + filter tahun (2026-08-29)

Rencana `C:\Users\v28mt\.claude\plans\linked-sprouting-aho.md`; catatan `notes.md` §1u.

- **Part 1 ✅** (commit `cb6c311`) — filter dibawa ke rute dokumen lewat **fragmen hash** (`#sp=..`), bukan query string (mati di GitHub Pages). `filter-laporan.js` getter `hashFilter` + `dariHash()`. Tombol "Buka di tab baru" → **"Generate Laporan"** (primer); `x-data` pindah ke `<div>` pembungkus supaya tombol baca keadaan filter.
- **Part 2 ✅** (commit `cb6c311`) — rute dokumen = **dokumen resmi berkop**: `x-sim.kop-laporan` (dua lambang Kementerian+Malaka, flex tanpa tabel), blok judul + "TAHUN <2026>" + kalimat cakupan. TANPA bilah filter, TANPA blok "Cakupan laporan". `LaporanData::instansi()` + `tahunDokumenBawaan()`. Aset `lambang-malaka.png`. pest 692, pint 31, `uji-filter-laporan` 50/0, `uji-lebar-dokumen` 28/0.
- **Part 3 ✅** (commit `0c12b9f`) — pemilih **tahun tunggal** (bukan rentang) untuk Rekap Indikator Kawasan + Monografi SP. `DummyData::indikatorKawasanTahun()` / `rekapPerSpTahun()` / `iklimSpTahun()` (5 tahun 2022–2026; irisan 2026 == sumber lama). Blok kawasan `x-text="nilaiTahun()"`, tabel per SP 6×5 baris `data-tahun`, Bab II iklim `x-text="iklimTahun()"`. `rules.md` §12 poin 11 diperbarui (pemilih tahun tunggal diizinkan di laporan snapshot). pest 696, pint 31, `uji-filter-laporan` 53/0.

**Putaran 5 SELESAI. Sisa Tahap 2: NOL.**

**Belum diperiksa mata:** hasil cetak (Ctrl+P) tampilan dokumen resmi (kop dua lambang + tabel di A4).

### Putaran 6: Peristiwa penduduk + perluasan Laporan Monografi SP (2026-08-29)

Rencana `C:\Users\v28mt\.claude\plans\linked-sprouting-aho.md`; catatan `notes.md` §1v.

- **1/6 ✅** (commit `8c545f1`) — `App\Enums\StatusAnggotaKeluarga` {Aktif, Meninggal, Pindah}; `anggota_keluarga` +`status`/`tanggal_peristiwa`/`keterangan_peristiwa`; anggota non-Aktif keluar dari cacah jiwa / pengganti KK / rekap agama. Balik sebagian `rules.md` §9c.
- **2/6 ✅** — rute `POST /transmigran/{id}/anggota/{anggota}/catat-peristiwa`; tab keluarga kolom Status + tombol "Catat Peristiwa" + modal `formPeristiwaAnggota`; form multi-langkah repeater hanya anggota Aktif. Kepala keluarga tetap lewat "Ganti Kepala Keluarga".
- **3/6 ✅** — `DummyData::jiwaPerSp()` (Σ = `ringkasanDashboard`), `strukturUmurSp($id)` (14 kelompok, Σ = jiwa), `mutasiPendudukSp($id)` (kumulatif sejak penempatan, tanpa perkawinan). Angka contoh turunan deterministik (pengecualian sadar §19a).
- **4/6 ✅** — `LaporanData::bagianTambahanSp()` → Pendahuluan / Kependudukan / Sosial Ekonomi / Sosial Budaya per SP dari tabel yang sudah ada; `keadaanPendudukTahun()` + `kependudukanTahun` blob.
- **5/6 ✅** — `_tabel-dok.blade.php` partial; `monografi-sp.blade.php` judul tanpa "Bab X." + 4 blok baru; `filter-laporan.js` `nilaiKependudukan()`.
- **6/6 ✅** — dokumen: `rules.md` §9c (dibalik sebagian) + §12 poin 14; `data-dictionary.md` §6.1a + §11.44; `notes.md` §1v; `ui-spec.md` §6.12; blok ini.

pest 705, pint 31, `sim:tautan-statis` 222, `uji-lebar-dokumen` 28/0, `uji-filter-laporan` 53/0.

**Belum diperiksa mata:** Ctrl+P dokumen Monografi yang kini jauh lebih panjang (banyak sub-tabel per SP).

### Putaran 7: pola "induk + distribusi" — Alsintan, Saprotan, +3 temuan audit (2026-08-30)

Rencana `C:\Users\v28mt\.claude\plans\linked-sprouting-aho.md`; catatan `notes.md` §1w.

Cacat FATAL: `alsintan`/`saprotan` bawa satu `poktan_id` → satu batch bantuan ke banyak poktan diketik ulang per poktan. Audit menemukan 3 instans sama.

- **A ✅** data master `jenis_alsintan` (`JenisReferensi::JenisAlsintan`, deklarasi PALING AKHIR). `sim:tautan-statis` 222→223.
- **B+C ✅** `<x-sim.pilih-cari-banyak>` + alsintan `alsintan()` induk + `alsintanDistribusi()`. Form: repeater distribusi (jumlah bagi rata otomatis, kondisi/penanda per poktan). Index 1 baris/pengadaan.
- **D ✅** saprotan induk + `saprotanDistribusi()`. `penanaman.saprotan_id`→`saprotan_distribusi_id`. `sisaBenih()` grain turun ke distribusi (§7c.8 utuh). `satuan`→`satuan_id` dibetulkan.
- **E ✅** infrastruktur lintas SP: `+satuan_permukiman_ids` + `infrastrukturCakupan()`. `PenilaianKondisiSp` baca cakupan. **Memperbaiki skor SP yang salah** (primer nol).
- **F1 ✅** fasilitas_sp cakupan (pola sama E). **F2 (jumlah>1 satu kondisi) DITUNDA** — bug class berbeda (`rules.md` §7bc.5).
- **G ✅** `dokumenLahan()` induk + `dokumenLahanBidang()` (m2m). Satu HPL banyak bidang.
- **H ✅** `hasil_panen.poktan_id` dicabut, diturunkan dari penanaman.
- **I ✅** dokumen: `rules.md` §7b/§7c ditulis ulang + §7bc baru; `data-dictionary.md` §7.2/§8.3/§8.4/§10.1 + tabel batas #4/#5/#9/#33-35; `§11.37` betulkan `kualitas_panen` + `jenis_alsintan`; `ui-spec.md` §6.0a; `notes.md` §1w; blok ini.

pest 711, pint 31, `sim:tautan-statis` 223. Seluruh suite peramban hijau.

**Tertunda:** F2 (rincian kondisi per unit fasilitas/inventaris). **Belum diperiksa mata:** form repeater distribusi di layar, Ctrl+P laporan.

## Tahap 3 — Autentikasi dan Hak Akses

> **Peringatan penerbitan statis.** Begitu login aktif, halaman berpelindung membalas pengalihan ke `/login`, bukan 200, sehingga `.github/workflows/deploy.yml` **gagal** dan situs GitHub Pages berhenti diperbarui. Putuskan lebih dulu: batasi `sim:tautan-statis` hanya ke halaman publik, atau hentikan penerbitan statis sama sekali. Lihat `notes.md` bagian 1b.7.

- [ ] Task 3.1 - Migration dan model `user` beserta password dan timestamps `[Mudah]`
- [ ] Task 3.2 - Implementasi login, logout, dan rate limiting `[Sedang]`
  * Satu kolom kredensial menerima **email atau NIK**; sistem memilih kolom pencarian berdasarkan bentuk masukan (16 digit angka berarti NIK)
  * Tolak akun dengan `is_aktif = FALSE`
  * Middleware pemaksa ganti kata sandi bila `password_harus_diganti = TRUE`
  * **Tanpa rute pendaftaran dan tanpa rute pemulihan kata sandi** (`rules.md` §14b)
- [ ] Task 3.3 - Implementasi RBAC dinamis `[Sulit]`
  * Model `Role`, `Permission`, relasi many-to-many, seeder ±120 izin dan 4 role bawaan
  * Middleware pemeriksa izin, helper `can()` untuk Blade
  * Izin `lihat` sebagai prasyarat aksi lain pada modul yang sama
- [x] Task 3.3b - Halaman pengaturan role dan izin `[Sulit]` (Tampilan selesai pada Task 2.27)
  * Daftar role, form dengan matriks centang izin dikelompokkan per modul
  * Role terkunci ditampilkan hanya-baca; role bawaan tidak dapat dihapus
- [ ] Task 3.4 - Pembatasan akses pada level query (cakupan data) `[Sulit]`
  * Global scope Eloquent menyaring menurut `role.cakupan_data`
  * Cakupan `Per SP` membaca penugasan dari `user_satuan_permukiman`
  * Akun `Per SP` tanpa penugasan tidak melihat data apa pun, bukan melihat seluruhnya
- [ ] Task 3.4b - Sidebar dinamis berbasis izin `[Sedang]`
  * `MenuHelper` menyaring item menu menurut izin; kelompok kosong ikut hilang
- [~] ~~Task 3.7 - Implementasi verifikasi data~~ **DIBATALKAN 2026-08-14**
  * Fitur verifikasi dicabut seluruhnya atas kesepakatan tim, sehingga task ini tidak akan dikerjakan.
  * Yang ikut dihapus: enum `StatusVerifikasi`, tabel `verifikasi` pada ERD, aturan `rules.md` 5.2, delapan rute verifikasi/tolak, dan indikator 15 Mutu Data pada dashboard.
  * Rincian keputusan beserta dampaknya tercatat pada `notes.md` tabel keputusan bertanggal 2026-08-14.
- [ ] Task 3.5 - CRUD manajemen pengguna oleh Admin `[Sedang]`
  * Termasuk tindakan **setel ulang kata sandi**: membuat kata sandi sementara dan menandai `password_harus_diganti = TRUE`
  * Penonaktifan akun memakai `is_aktif = FALSE`, bukan penghapusan
  * Sistem menolak penonaktifan atau penghapusan akun Admin terakhir yang masih aktif
  * Seluruh tindakan tercatat di audit log: `Reset Kata Sandi`, `Nonaktifkan Akun`, `Aktifkan Akun`
- [ ] Task 3.5b - Perintah artisan pemulihan darurat kata sandi Admin `[Mudah]`
  * Jalur pemulihan lewat terminal server bila seluruh Admin kehilangan akses (`rules.md` §14b poin 13)
- [ ] Task 3.6 - Implementasi audit log perubahan data `[Sedang]`
- [ ] Task 3.8 - Pengenal UUID pada alamat URL `[Sedang]`
  * Diterapkan **bertahap**, dimulai dari modul berdata pribadi: transmigran, rumah, pengaduan
  * Primary key integer **tetap dipakai di dalam database** untuk relasi antar-tabel; UUID hanya pengenal publik
  * Alasan: id berurutan membocorkan perkiraan jumlah data (`rules.md` 4.0a)
- [ ] Task 3.9 - Slug pada data master `[Sedang]`
  * Diterapkan pada SP, kawasan, poktan, dan komoditas. Contoh `/dashboard/sp/kapitan-meo`
  * **Slug dilarang diturunkan dari data pribadi.** Nama orang pada URL tersimpan di riwayat peramban dan log server, sehingga justru menurunkan kerahasiaan dibanding id angka
  * Slug tidak berubah meski nama disunting, agar tautan yang sudah dibagikan tidak rusak
- [ ] Task 3.10 - Pembatasan laju per jenis akses `[Sedang]`
  * Halaman baca 120 per menit **per akun**, tulis 40 per menit, lacak publik 10 per menit per IP, kirim pengaduan 3 per jam per IP, login 5 kegagalan per menit
  * **Dihitung per akun untuk halaman internal**, bukan per IP: satu kantor dinas kerap memakai satu sambungan bersama, sehingga hitungan per IP membuat operator saling menghabiskan jatah
  * Rute export massal dan unggah template **wajib dikecualikan** dan diberi batas tersendiri (`rules.md` 14c)

- [ ] Task 3.11 - Pemulihan kata sandi lewat kode verifikasi `[Sedang]`
  * Tampilan sudah selesai pada Tahap 2: `/lupa-kata-sandi` dan `/verifikasi-kode`
  * Membuat tabel `kode_pemulihan_sandi` (`erd.md` bagian 9): sidik kode, kedaluwarsa, percobaan, dipakai_pada
  * **Yang disimpan adalah sidik kode, bukan angkanya.** Basis data yang bocor tidak boleh langsung memberi jalan masuk
  * Kode enam digit, berlaku 15 menit, sekali pakai, maksimal 5 percobaan, 3 permintaan per jam per akun
  * Kode lama **wajib dibatalkan** saat kode baru diminta, agar tidak ada dua kode sah beredar
  * Balasan `POST /lupa-kata-sandi` **wajib sama** baik akun ditemukan maupun tidak (`rules.md` 14b poin 9)
  * Waktu balasan sebaiknya diseragamkan, sebab selisih waktu proses juga membocorkan keberadaan akun
  * Jalur Admin pada Task 3.5 **tetap berlaku** dan tidak boleh dihapus

## Tahap 4 — Backend Data Master Kawasan

- [ ] Task 4.1 - Migration dan model wilayah bertingkat + seeder 6 desa/4 kecamatan `[Sedang]`
  * Tampilan form sudah selesai pada Task 2.30
- [ ] Task 4.2 - CRUD satuan permukiman (SP) beserta koordinat `[Sedang]`
  * Tampilan form sudah selesai pada Task 2.30; tersisa migration, model, dan penyimpanan
- [ ] Task 4.3 - CRUD inventaris SP (nama, tahun, sumber dana, status penyerahan, dokumen) `[Sedang]`
  * Tampilan form sudah selesai pada Task 2.30
- [ ] Task 4.4 - CRUD fasilitas SP `[Mudah]`
  * Tampilan form sudah selesai pada Task 2.30
- [ ] Task 4.5 - Data master satuan + faktor konversi ke ton `[Mudah]`
  * Tampilan form beserta pratinjau konversi sudah selesai pada Task 2.30
  * Seeder awal: Ton (1), Kuintal (0,1), Kilogram (0,001)

## Tahap 5 — Backend Kependudukan

- [ ] Task 5.1 - Migration dan model transmigran (termasuk no. KK dan jumlah anggota) `[Sedang]`
- [ ] Task 5.2 - CRUD transmigran + upload dokumen pendukung `[Sulit]`
- [ ] Task 5.3 - CRUD rumah dan kondisi hunian + foto dan koordinat `[Sedang]`
  * UNIQUE constraint dua arah rumah–KK; dropdown hanya menampilkan rumah kosong
- [ ] Task 5.4 - Riwayat penghunian rumah (masuk, keluar, alasan) `[Sedang]`
  * Pergantian penghuni tidak menimpa data lama
- [ ] Task 5.5 - Rekap kependudukan kawasan (KK masuk/keluar per tahun) `[Sedang]`

## Tahap 6 — Backend Lahan dan Kelembagaan

- [ ] Task 6.1 - Migration dan model lahan (pekarangan dan usaha) + kategori lahan `[Sedang]`
  * Relasi one-to-many: FK `id_transmigran` berada di tabel lahan
- [ ] Task 6.2 - CRUD lahan + upload dokumen HPL/SHM `[Sedang]`
- [ ] Task 6.3 - Pencatatan koordinat, pola tanam, peralatan, dan kendala lahan usaha `[Sedang]`
- [ ] Task 6.4 - CRUD profil poktan dan data ketua `[Sedang]`
  * Tampilan form sudah selesai pada Task 2.30
- [ ] Task 6.5 - CRUD daftar anggota poktan + status keaktifan `[Sedang]`
  * Tampilan form sudah selesai pada Task 2.30, termasuk aturan tandai keluar bukan hapus
- [ ] Task 6.6 - CRUD alsintan (selalu milik poktan) `[Sedang]`
  * Tampilan form dan halaman rincian sudah selesai pada Task 2.29 dan 2.30
- [ ] Task 6.7 - CRUD saprotan + penyaluran ke anggota aktif `[Sedang]`
  * Tampilan form dan halaman rincian sudah selesai pada Task 2.29 dan 2.30

## Tahap 7 — Backend Produksi Pertanian

- [ ] Task 7.1 - Migration dan model komoditas (dinormalisasi) `[Sedang]`
- [ ] Task 7.2 - CRUD komoditas + penanda unggulan + satuan baku per komoditas `[Sedang]`
  * Tampilan form dan halaman rincian sudah selesai pada Task 2.29 dan 2.30
- [ ] Task 7.3 - CRUD penanaman `[Sedang]`
  * Tampilan kedua form sudah selesai pada Task 2.30
- [ ] Task 7.4 - CRUD hasil panen (produksi, produktivitas, puso, harga) `[Sulit]`
  * Satuan mengikuti komoditas terpilih; `DECIMAL(12,3)`; kolom keterangan satuan lokal
  * **Status panen tidak ikut jadi kolom.** Ia diturunkan lewat `StatusPanen` beserta `statusPanen()`, dua nilai saja (`rules.md` 9.10)
  * **Validasi wajib: `realisasi_panen` + `puso` tepat sama dengan `penanaman.realisasi_tanam`**, dan satu penanaman hanya boleh punya satu baris panen (`rules.md` 9.9). Tampilannya sudah menegakkan ini sejak 2026-08-24; peladen wajib menegakkannya juga, sebab penjagaan di sisi peramban dapat dilewati
  * **Gagal total sah:** `realisasi_panen` 0 dengan `puso` menutup seluruh luas. Pada keadaan itu `produktivitas` tidak diwajibkan (`rules.md` 9.9b)
- [ ] Task 7.5 - Helper konversi volume panen ke ton `[Sedang]`
  * Dipakai seluruh rekap dan dashboard agar agregasi lintas komoditas konsisten
- [ ] Task 7.6 - Rekap panen per wilayah, poktan, komoditas, dan periode `[Sedang]`
  * Tampilannya sudah dirombak 2026-08-24: basis **penanaman** bukan hasil panen, terikat satu **tahun panen**
  * Saat backend masuk, `DummyData::rekapPanen()` diganti kueri agregat. Aturannya pada `rules.md` 9.8a-8i; yang paling mudah keliru adalah produktivitas tertimbang (8d) dan pembulatan (8e)
  * **Penggolongan tahunnya bukan kolom melainkan turunan** (`tahunRekapPanen()`): sudah dipanen ikut tahun panennya, belum dipanen ikut tahun berjalan. Yang kedua **berpindah sendiri** saat tahun berganti, sehingga kueri wajib menghitungnya tiap kali dijalankan, bukan menyimpannya (`rules.md` 9.8c-1 dan 8c-2)

## Tahap 8 — Backend Infrastruktur dan Pengaduan

- [ ] Task 8.1 - CRUD infrastruktur SP sebagai pendataan aset `[Sedang]`
  * Tampilan form dan halaman rincian sudah selesai pada Task 2.29 dan 2.30
- [ ] Task 8.2 - Migration dan model pengaduan + tabel riwayat penanganan `[Sedang]`
- [ ] Task 8.3 - Halaman pengaduan publik tanpa login `[Sulit]`
  * Form pengaduan warga di `/pengaduan-warga`, tata letak terpisah tanpa sidebar
  * Pembatasan 3 pengiriman per jam per alamat IP, tanpa CAPTCHA
  * Nomor pengaduan ditampilkan besar setelah berhasil kirim
  * Kolom **surel opsional**: bila diisi, nomor pengaduan dikirim juga ke sana sebagai salinan. Tidak diwajibkan, sebab jaringan lokus tidak selalu memadai dan sebagian warga tidak memiliki surel
  * Halaman lacak `/lacak-pengaduan` memakai nomor tiket, hanya menampilkan status dan riwayat penanganan
- [ ] Task 8.3b - Form pencatatan pengaduan oleh petugas `[Sedang]`
  * Petugas mencatatkan laporan lisan warga; `sumber_laporan` bernilai Petugas
- [ ] Task 8.4 - Alur status penanganan Menunggu Diterima → Diterima → Diproses → Selesai `[Sulit]`
- [ ] Task 8.5 - Routing pengaduan ke dinas sesuai bidang + penanda prioritas `[Sedang]`
- [ ] Task 8.6 - Rekap pengaduan per kategori, status, dan desa/SP `[Sedang]`
- [ ] Task 8.7 - Nomor pengaduan publik dengan bagian acak `[Mudah]`
  * Format `PGD-2026-0001-K7F2M9`, enam karakter terakhir acak
  * **Halaman lacak dapat dibuka tanpa login**, sehingga nomor berurutan dapat ditebak satu per satu untuk memanen judul dan catatan penanganan warga lain
  * Inilah permukaan serangan yang nyata, berbeda dari id petugas yang sudah terlindung login

## Tahap 9 — Dashboard dengan Data Nyata

- [ ] Task 9.1 - Ganti data dummy dashboard dengan query nyata `[Sulit]`
  * **Lima agregat produksi wajib ikut diganti**, ditambahkan 2026-08-24 sebagai indikator 17: `realisasi_tanam_ha`, `hasil_panen_ha`, `puso_ha`, `belum_dipanen_ha`, `produktivitas_ton_ha`
  * Kedua identitas pada `rules.md` 9.9 dan 9.11 wajib tetap berlaku setelah diganti kueri, dan sudah dijaga uji. Produktivitas **tertimbang** (9.8d), bukan rata-rata kolom
  * Angka agregat sekarang berskala kawasan, **bukan** penjumlahan `penanaman()` yang hanya beberapa baris contoh (`rules.md` 9.8g)
- [ ] Task 9.2 - Filter wilayah dan periode terhubung ke seluruh visualisasi `[Sedang]`
- [ ] Task 9.3 - Drill-down klik grafik menuju rincian per SP `[Sulit]`
- [ ] Task 9.4 - Optimasi query dashboard (indeks, agregasi, eager loading) `[Sulit]`
- [ ] Task 9.5 - Halaman pengaturan bobot penilaian kondisi SP `[Sedang]`
  * Admin dapat menyesuaikan bobot tiap parameter dan menonaktifkan parameter tanpa mengubah kode
  * Parameter **dinonaktifkan, bukan dihapus**, agar riwayat penilaian yang memakainya tetap terbaca
  * Perubahan bobot **tidak mengubah penilaian lama**, sebab tiap penilaian menyimpan salinan bobot yang berlaku saat itu
  * Menampilkan pratinjau dampak perubahan bobot sebelum disimpan
  * Luas lahan memakai penjumlahan seluruh lahan; volume panen dikonversi ke ton

## Tahap 10 — Laporan dan Export

- [ ] Task 10.1 - Export Excel untuk data utama `[Sedang]`
- [ ] Task 10.2 - Export PDF untuk data utama + kop logo `[Sedang]`
- [ ] Task 10.3 - Filter laporan sebelum export `[Sedang]`
- [ ] Task 10.4 - Template isian luring yang dapat diunduh dan diunggah kembali `[Sulit]`

## Tahap 11 — Pengujian, Deployment, dan Serah Terima

- [ ] Task 11.1 - Alpha testing internal (login, role, CRUD, filter, upload, export, audit log) `[Sulit]`
- [ ] Task 11.2 - Perbaikan bug blocker `[Sedang]`
- [ ] Task 11.3 - Deployment ke hosting + domain, SSL, storage, backup terjadwal `[Sulit]`
  * **Prasyarat:** hentikan atau batasi penerbitan statis GitHub Pages lebih dulu. Rinciannya pada `notes.md` bagian 1b.7
  * Penyeragaman `asset()`/`url()`/`route()` (1b.3) dan kepercayaan `X-Forwarded-*` di `bootstrap/app.php` **tetap diperlukan**, sebab keduanya syarat hosting di belakang reverse proxy
- [ ] Task 11.4 - Simulasi input data awal per desa/SP prioritas `[Sedang]`
- [ ] Task 11.5 - Beta testing bersama dinas dan pengguna lapangan `[Sedang]`
- [ ] Task 11.6 - Penyusunan SOP, buku panduan, dan video panduan `[Sedang]`
- [ ] Task 11.7 - Pelatihan pengguna (operator dan masyarakat) `[Sedang]`
- [ ] Task 11.8 - Laporan evaluasi implementasi dan BAST `[Sedang]`

---

## Catatan Checkpoint

**Checkpoint terakhir:** 2026-08-11 — Tahap 0 selesai (8 task) dan Tahap 1 sebagian (Task 1.1, 1.2, 1.2b, 1.3, 1.4, 1.4b). Delapan dokumen acuan sudah selaras, dan **fondasi proyek Laravel sudah berdiri di root proyek**.

**Struktur folder:**

```
sistem informasi transmigrasi/     <- root Laravel sekaligus root proyek
├── agents/     9 dokumen acuan
├── docs/       4 berkas sumber (proposal, dump SQL, logo, foto)
├── app/        kode aplikasi Laravel (Helpers, Http, Models, Providers, View)
├── bootstrap/  config/  database/  public/  resources/  routes/
├── storage/  tests/  vendor/  node_modules/
└── artisan  composer.json  package.json  .env  vite.config.js
```

**Kondisi proyek saat ini:**
- Laravel 12.65.0 di atas PHP 8.2.12 (XAMPP), database `sim_transmigrasi` dengan 9 tabel bawaan Laravel
- Timezone `Asia/Makassar`, locale `id`, terverifikasi lewat `translatedFormat` yang mengeluarkan "Selasa, 11 Agustus 2026"
- Template TailAdmin sudah dibersihkan; halaman `/` merender HTTP 200
- Menjalankan aplikasi dari root proyek: `& "C:\xampp\php\php.exe" artisan serve`
- Perintah build dari root proyek: `npm run build`

**Komponen TailAdmin yang dipertahankan sebagai basis:** `ui/` (alert, avatar, badge, button, modal), `common/` (component-card, page-breadcrumb, dropdown-menu, table-dropdown, theme-toggle, preloader, grid-shape), `form/` (date-picker, input/radio, select/multiple-select), `header/` (notification-dropdown, user-dropdown), `profile/` (3 kartu).

**Identitas visual sudah terpasang di `resources/css/app.css`:**
- 44 variabel warna Kementerian (navy, teal, sand, gold) di dalam blok `@theme`
- `--color-brand-*` tertimpa navy, sehingga seluruh komponen bawaan TailAdmin otomatis mengikuti palet Kementerian
- 8 variabel permukaan untuk mode terang (`:root`) dan gelap (`.dark`)
- 4 kelas motif identitas: `.motif-menu-aktif`, `.motif-judul-kartu`, `.motif-header-halaman`, `.motif-baris-total`

**Keputusan autentikasi dan hak akses (2026-08-11):**
- **Tanpa pendaftaran mandiri.** Akun hanya dibuat Admin lewat Manajemen Pengguna; `signup.blade.php` sudah dihapus.
- **Tanpa pemulihan lewat surel.** Pengguna yang lupa kata sandi menghubungi Admin. Tabel `password_reset_tokens` bawaan Laravel tidak dipakai.
- **Kredensial berupa email atau username** pada satu kolom isian. Kolom `nik` dan `transmigran_id` dicabut.
- **Role bersifat dinamis:** disimpan pada tabel `role`, dibuat dan diatur Admin lewat antarmuka. Empat role bawaan: Admin (terkunci), Dinas Transmigrasi, Dinas Pertanian, Operator SP.
- **Hak akses ditentukan dua hal terpisah:** izin (boleh melakukan apa) dan cakupan data (boleh melihat data siapa, bernilai Semua / Per SP / Milik Sendiri).
- **Role Transmigran dan Ketua Poktan dihapus.** Seluruh pengguna sistem adalah petugas.
- **Pengaduan warga lewat kanal publik tanpa login,** cukup nama dan kontak, dengan pelacakan memakai nomor tiket.
- **Verifikasi data dicabut 2026-08-14**, beserta tabel `verifikasi` dan indikator mutu data (`notes.md` tabel keputusan).
- Rincian lengkap pada `rules.md` §5, §5.2, §10b, dan §14b.

**Aset dan layout sudah terpasang:**
- `public/images/logo/` berisi 7 varian logo Kementerian, plus `public/favicon.ico` multi-ukuran
- `public/images/` sudah dibersihkan dari 59 aset contoh TailAdmin dan 37 foto pengguna fiktif; kini 24 berkas, seluruhnya terpakai
- `MenuHelper` memuat 10 kelompok dan 25 item menu, masing-masing terikat satu izin
- Helper `hashTabs()` tersedia di `layouts/app.blade.php`, terverifikasi 8 dari 8 uji

**Fondasi kode yang siap dipakai seluruh modul:**

| Berkas | Kegunaan |
|---|---|
| `app/Support/ValidationRules.php` | 16 aturan validasi, 40 pesan galat Bahasa Indonesia, label kolom |
| `app/Support/PenyimpananDokumen.php` | Simpan, ganti, hapus dokumen di disk privat beserta pola penamaan |
| `app/Http/Middleware/UppercaseInput.php` | Menyeragamkan isian teks jadi huruf kapital, 24 kolom dikecualikan |
| `app/Http/Controllers/DokumenController.php` | Melayani unduhan berkas privat setelah pemeriksaan hak akses |
| `app/Helpers/MenuHelper.php` | Menu sidebar dinamis berbasis izin |
| `tests/Feature/FondasiTest.php` | 22 uji, 150 pernyataan, seluruhnya lulus |

**Perintah verifikasi:** `& "C:\xampp\php\php.exe" vendor\bin\pest` dan `npm run build`, keduanya hijau. **Hanya dua perintah ini**; tidak ada runner uji ketiga. `package.json` sengaja tidak punya skrip uji, dan berkas `uji-chart-config.mjs` yang dulu disebut beberapa catatan sudah dihapus 2026-08-17 sebab isinya tidak pernah ada.

**Bahan siap pakai untuk membangun halaman (Tahap 2):**

| Berkas | Isi |
|---|---|
| `app/Enums/` | 29 enum + 2 trait di `Concerns/`. Pakai `Enum::opsi()` untuk dropdown, `$enum->warna()` untuk badge |
| `app/Support/DummyData.php` | 45 metode data contoh, struktur mengikuti kamus data |
| `resources/views/components/sim/` | 18 komponen bersama berawalan `x-sim.` |
| `resources/js/chart-config.js` | Konfigurasi ApexCharts bersama, diekspos sebagai `window.grafikSim` |
| `resources/views/pages/galeri-komponen.blade.php` | Acuan pemakaian seluruh komponen, buka di `/galeri-komponen` |

**Halaman yang sudah berdiri (Tahap 2):**

| Rute | Berkas | Isi |
|---|---|---|
| `/` | `pages/dashboard/index.blade.php` | 8 kartu statistik, 11 grafik, 1 tabel isu prioritas |
| `/dashboard/sp/{id}` | `pages/dashboard/sp.blade.php` | Rincian satu SP, 6 tab, 2 grafik; id tak dikenal membalas 404 |
| `/transmigran` | `pages/transmigran/index.blade.php` | Daftar, pencarian, 3 filter, modal tambah |
| `/transmigran/{id}` | `pages/transmigran/detail.blade.php` | Dua kolom asimetris, 5 tab, modal ubah |
| `/rumah`, `/rumah/{id}` | `pages/rumah/` | Daftar, rincian, riwayat penghunian sbg garis waktu |
| `/lahan`, `/lahan/{id}` | `pages/lahan/` | Daftar, rincian, tab dokumen HPL dan SHM |
| `/panen`, `/panen/{id}` | `pages/panen/` | Daftar, rincian, satuan mengikuti komoditas |
| `/panen/rekap` | `pages/panen/rekap.blade.php` | Tabel agregat, 4 dasar pengelompokan, tanpa kartu |
| `/pengaduan`, `/pengaduan/{id}` | `pages/pengaduan/` | Daftar, rincian, alur status berurutan |
| `/pengaduan/rekap` | `pages/pengaduan/rekap.blade.php` | Tabel agregat, 5 dasar pengelompokan |
| `/pengaduan-warga` | `pages/publik/pengaduan.blade.php` | **Publik tanpa login**, tata letak tanpa sidebar |
| `/lacak-pengaduan` | `pages/publik/lacak.blade.php` | **Publik tanpa login**, tanpa data pribadi pelapor |
| galat 403 dan 404 | `resources/views/errors/` | Dipakai Laravel otomatis, kedua mode tema |
| `/profil` | `pages/profil/index.blade.php` | Dua kolom asimetris, tab Data Akun dan Kewenangan |
| `/profil/kata-sandi` | `pages/profil/kata-sandi.blade.php` | Ubah kata sandi sendiri |
| `/ganti-kata-sandi` | `pages/auth/ganti-kata-sandi.blade.php` | Wajib ganti, layar penuh tanpa sidebar |
| `/login` | `pages/auth/signin.blade.php` | Email atau username |

**Cara memakai grafik pada halaman baru:**

```blade
<x-sim.chart-card id="grafikPanen" judul="Volume Panen" tinggi="320">
    <x-slot:tabel>...tabel alternatif wajib...</x-slot:tabel>
</x-sim.chart-card>

@push('scripts')
<script type="module">
    const { buatGrafik, angka } = window.grafikSim;
    buatGrafik('grafikPanen', { chart: { type: 'bar' }, series: [...] });
</script>
@endpush
```

Mode gelap ditangani otomatis: `pantauTema()` menggambar ulang seluruh grafik saat tema berganti.

**Total uji: 357 lulus, 1.562 pernyataan** (PHP), seluruhnya lewat `vendor\bin\pest`, ditambah verifikasi visual lewat Edge headless. Tidak ada runner kedua: klaim uji Node pada catatan lama sudah dicabut 2026-08-17 sebab ujinya tidak pernah ada.

**GELOMBANG 1 SELESAI DAN SUDAH LOLOS DELIVERY GATE. Berikutnya CHECKPOINT, bukan task baru.**

Task 2.1 sampai 2.12 tuntas, ditambah Task 2.21 sampai 2.24 yang dikerjakan lebih awal agar yang dipresentasikan sudah bersih. Laporan gate lengkap ada pada **`agents/delivery-gate-gelombang-1.md`**: keempat blok PASS, dengan bukti antara lain 0 tautan mati dari 726 tautan dan 11 pasangan warna lolos WCAG AA di kedua mode.

Sebelum melanjutkan ke gelombang 2 yang memuat 31 halaman sisanya, hasil ini **wajib divalidasi bersama tim dan dinas** (`workflow.md` §2.2 Langkah B poin 6). Tujuannya agar revisi yang muncul saat FGD tidak membongkar 31 halaman sekaligus.

**Yang perlu diminta pendapatnya saat validasi:**

1. **Pola tata letak.** Empat komposisi halaman sudah punya contohnya masing-masing (dashboard, daftar, detail, rekap). Apakah pembedaan ini membantu orientasi, atau justru membingungkan?
2. **Alur form.** Form dibagi bertahap per bagian. Apakah urutannya sesuai cara petugas mengumpulkan data di lapangan?
3. **Penamaan field.** Label saat ini mengikuti kamus data. Apakah istilahnya sama dengan yang dipakai sehari-hari di dinas dan SP?
4. **Halaman warga.** Bahasa pada `/pengaduan-warga` dan `/lacak-pengaduan` sudah disederhanakan. Perlu diuji langsung ke warga, bukan hanya dinilai petugas.
5. **Aturan yang dijaga antarmuka.** Beberapa keputusan sengaja membatasi pengguna: dropdown penghuni hanya rumah kosong, satuan panen mengikuti komoditas, status pengaduan hanya maju satu tahap. Perlu dipastikan tidak ada kasus lapangan yang sah tetapi terhalang aturan ini.

**Tiga hal yang wajib diperiksa manusia sebelum FGD,** karena tidak dapat digantikan uji otomatis (`delivery-gate-gelombang-1.md` bagian penutup):

1. Membuka setiap halaman pada peramban nyata di lebar 360px. Uji otomatis memeriksa penyebab tersering gulir mendatar, tetapi tidak menggantikan pengamatan mata.
2. Menjalankan seluruh alur hanya dengan keyboard, dari Tab pertama sampai modal tertutup.
3. Menguji halaman warga kepada warga sungguhan, bukan menilainya lewat kacamata petugas.

Setelah checkpoint, lanjut Task 2.13 dan seterusnya memakai pola yang sudah baku di bawah ini. **Task 2.21 sampai 2.24 wajib dijalankan ulang** setelah gelombang 2 selesai, untuk 31 halaman tambahan.

**Pola CRUD yang sudah baku (ikuti untuk gelombang 2):**

Modul transmigran adalah acuannya. Tiap modul terdiri atas tiga berkas di `resources/views/pages/[modul]/`:

| Berkas | Isi |
|---|---|
| `index.blade.php` | Kartu ringkasan, `x-sim.data-table` dibungkus satu `<form method="GET">` agar pencarian dan filter terkirim bersama, modal tambah, dialog hapus |
| `detail.blade.php` | Dua kolom asimetris, tab `hashTabs()`, ringkasan entitas di kolom kiri, modal ubah |
| `form.blade.php` | Isian bersama kedua modal, wajib menerima atribut `awalan` agar id tetap unik |

Aturan yang mudah terlewat:
1. Nama isian **wajib** sama dengan kolom pada `data-dictionary.md`, karena diuji otomatis.
2. Filter dan pencarian lewat **query string**, bukan state Alpine, agar bertahan setelah `return back()`.
3. Keadaan kosong dan pencarian nihil **dibedakan**, masing-masing dengan jalan keluarnya.
4. Alasan penolakan ditulis **penuh** di halaman rincian, bukan hanya tooltip.
5. Id tak dikenal membalas **404**, bukan halaman kosong.

Empat komposisi halaman pada dial RITME 2 kini seluruhnya sudah punya contohnya:

| Jenis | Contoh | Ciri |
|---|---|---|
| Dashboard | `pages/dashboard/index` | Kartu statistik lalu grid grafik dua kolom tak sama lebar |
| Daftar | `pages/transmigran/index` | Lebar penuh didominasi tabel, filter dalam laci |
| Detail | `pages/transmigran/detail` | Dua kolom asimetris, ringkasan menetap di kiri |
| Rekap | `pages/panen/rekap` | Tabel agregat, baris total ditegaskan, **tanpa kartu statistik** |

Aturan modul yang mudah terlewat, tercatat agar tidak terulang:
- **Rumah:** dropdown penghuni hanya menawarkan KK yang belum punya rumah; pergantian penghuni jadi riwayat baru.
- **Lahan:** empat kolom pengelolaan hanya berlaku bagi lahan usaha; dokumen dikelola terpisah karena bisa lebih dari satu.
- **Panen:** satuan mengikuti komoditas; penjumlahan lintas komoditas **wajib** lewat konversi ke ton.

**Keputusan yang sudah ditetapkan:**
1. Satu transmigran boleh memiliki **lebih dari satu lahan usaha** → relasi one-to-many, FK di tabel lahan.
2. Satu rumah dihuni **tepat satu KK**, dan satu KK menempati **tepat satu rumah** → relasi one-to-one, UNIQUE dua arah, pergantian penghuni dicatat sebagai riwayat.
3. Satuan panen ditetapkan **per komoditas**; **ton** dipakai sebagai satuan agregasi (ton 1; kuintal 0,1; kilogram 0,001).
4. Role mengikuti skema SQL: Admin, Dinas Transmigrasi, Dinas Pertanian, Transmigran, Ketua Poktan.
5. Pengaduan menjadi modul tersendiri; modul infrastruktur difokuskan sebagai pendataan aset.
6. Stack frontend: **Blade + Alpine.js + Tailwind + ApexCharts**, dikerjakan **frontend lebih dahulu** dengan data dummy.
7. Palet resmi dari logo Kementerian Transmigrasi: navy `#163B54`, teal `#33809C`, sand `#DFB87E`, gold `#C09546`.

**Keputusan tambahan 2026-08-11:**
8. Fondasi UI memakai **TailAdmin Laravel** (MIT), di-clone lalu dibersihkan dari halaman contoh.
9. **Tailwind v4** — design token ditulis di `resources/css/app.css` lewat blok `@theme`, bukan `tailwind.config.js`.
10. Font antarmuka **Outfit** mengikuti TailAdmin, menggantikan rencana awal Inter.
11. Laravel **12.x** di atas **PHP 8.2.12** milik XAMPP, bukan PHP 8.5 yang ada di PATH.
12. Basis data **MySQL/MariaDB XAMPP** sejak awal, bukan SQLite sementara.
13. Konvensi kunci: **PK `id_transmigran`**, **FK `transmigran_id`**. Setiap model wajib menyetel `$primaryKey`, setiap relasi wajib menyebut kunci eksplisit.
14. Koordinat memakai dua kolom **`lintang` dan `bujur` DECIMAL(10,7)**, bukan tipe GEOMETRY.
15. Tahap 2 dipecah **dua gelombang**: alur inti divalidasi lebih dulu, sisanya menyusul.
16. Data panen dipindah ke tabel **`hasil_panen`** tersendiri, agar riwayat panen per periode dimungkinkan.
17. Hierarki wilayah **bercabang dua** di tingkat kabupaten: cabang administratif (`kecamatan → desa`) dan cabang program (`kawasan_transmigrasi`), bertemu di `satuan_permukiman`.
18. SP menyimpan `desa_id` dan `kawasan_id`; **`kecamatan_id` tidak disimpan** karena terbaca lewat desa.
19. `ANTISLOP-ID.md` berlaku sebagai filter desain. **R-02 (larangan em dash) hanya berlaku untuk teks yang tampil di antarmuka**, tidak untuk dokumen di folder `agents/`.
20. Dial desain ditetapkan: **ENERGI 1 / RITME 2 / GERAK 1**, dengan motif identitas diturunkan dari logo Kementerian.
21. **Mode gelap dipertahankan** sebagai toggle dua mode; konsekuensinya seluruh komponen dan grafik wajib diuji di kedua mode (R-34).

**Masih menunggu:**
- Referensi tata letak Figma (tidak memblokir; sementara memakai tata letak bawaan TailAdmin).
- Daftar satuan final per komoditas dari konfirmasi lapangan (sementara: Ton, Kuintal, Kilogram).
- ~~Konfirmasi apakah lahan pekarangan bisa lebih dari satu per KK~~ **TERJAWAB 2026-08-18: tidak.** Satu transmigran menerima satu lahan pekarangan dan satu lahan usaha (`rules.md` 7.8). Relasi tetap one-to-many sebab satu KK memegang dua bidang berbeda peruntukan.
- **Apakah petugas benar-benar akan mengisi tautan objek pengaduan?** Isian wajib sebelum Diproses sudah ditegakkan, tetapi bila mayoritas ditandai "belum terdata" maka rekap aset paling sering diadukan tidak mewakili keadaan. Tabel kedua pada rekap sengaja dibuat untuk memperlihatkan hal itu secara terbuka, bukan menyembunyikannya. Perlu ditinjau setelah data nyata masuk.
- **Perlukah deteksi pengaduan berulang atas satu kejadian?** Sepuluh warga yang melaporkan longsor yang sama kini terhitung sepuluh laporan atas satu aset (`rules.md` 10b.8g). Angkanya benar sebagai jumlah laporan, tetapi tidak dapat dibaca sebagai ukuran keparahan. Menuntut konsep "pengaduan induk" yang lingkupnya besar.
- **Perlukah inventaris didata per unit?** Saat ini per jenis, sehingga sistem hanya sanggup menunjuk "meja kantor" bukan meja yang mana. Bila dinas memerlukannya, jalannya adalah tabel `unit_inventaris` di bawah `inventaris_sp`, tanpa membongkar `pengaduan_objek`. Menuntut penomoran dan pelabelan fisik di lapangan.
- Perlakuan rumah yang ditinggalkan sementara (sementara: tetap Dihuni, dicatat pada `catatan_hunian`).
- Konfirmasi apakah satu transmigran bisa masuk lebih dari satu poktan (sementara: tidak, sesuai `rules.md` §6.4).
- Keputusan apakah mode gelap bawaan TailAdmin dipakai atau dimatikan.
