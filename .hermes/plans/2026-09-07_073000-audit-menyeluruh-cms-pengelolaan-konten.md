# Audit Menyeluruh CMS / Pengelolaan Konten

**Aplikasi:** Sistem Informasi Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur  
**Tanggal audit:** 7 September 2026  
**Jenis pekerjaan:** Audit dan implementasi rekomendasi arsitektur  
**Mode:** Ponytail full  
**Status:** Implementasi Tahap A-E selesai pada 7 September 2026. Tidak ada tabel, page builder, media library, atau dependency baru.

## Status implementasi

- [x] Audit trail CMS, transaksi per tab, validasi tab, dan memoization per request.
- [x] Field identitas, kontak, footer, hotline, dan penandatangan terhubung ke consumer runtime.
- [x] Tentang dan Panduan memakai section tetap dari `KontenSistem`; daftar SP tetap berasal dari database.
- [x] Petunjuk, SLA, hotline, dan pelacakan pengaduan terpusat.
- [x] Catatan editorial per laporan tersedia tanpa memindahkan formula/struktur laporan ke CMS.
- [x] Copy purwarupa, versi alpha, PDF palsu, rentang tahun tetap, dan empty-state “data contoh” laporan dibersihkan.
- [x] Scope kawasan/SP, media, scheduling, page builder, WYSIWYG, revision, SEO, dan multibahasa tetap ditunda karena belum memiliki consumer nyata.

---

# 1. Executive Summary

## Kesimpulan utama

CMS yang sekarang tersedia adalah CMS nyata dan berfungsi, tetapi bentuknya masih berupa **pengaturan global berbasis key-value**, bukan CMS publikasi lengkap.

Alur aktual:

```text
/cms
  -> CmsController
  -> KontenSistem
  -> tabel pengaturan
  -> sebagian layout, halaman, email, laporan, dan dashboard
```

Referensi utama:

- Route CMS: `routes/internal.php:400-407`
- Controller: `app/Http/Controllers/CmsController.php:23-212`
- Service: `app/Support/KontenSistem.php:22-311`
- Model: `app/Models/Pengaturan.php:13-24`
- Schema: `database/migrations/2026_09_03_100190_create_pengaturan_table.php:17-24`
- UI enam tab: `resources/views/pages/cms/index.blade.php:36-541`
- Permission: `app/Support/PetaIzinRute.php:164-165`

CMS saat ini sudah mencakup beberapa kebutuhan editorial penting:

1. Identitas aplikasi.
2. Kop dokumen laporan.
3. Data pejabat penandatangan.
4. Narasi profil.
5. FAQ.
6. Portal pengaduan warga.
7. Pengumuman dashboard.
8. Format umum email sistem.

Namun, peluang CMS terbesar masih berada pada:

1. Halaman Panduan Penggunaan.
2. Halaman Tentang Sistem.
3. Footer publik dan internal.
4. Petunjuk layanan pengaduan dan pelacakan.
5. Catatan metodologi dan disclaimer laporan.
6. Identitas halaman login dan pemulihan akun.
7. Narasi tingkat tinggi dashboard.

## Temuan paling penting

### 1. Field CMS yang dapat disimpan tetapi tidak digunakan runtime

Field berikut memiliki form dan tersimpan ke database, tetapi tidak ditemukan consumer runtime yang menampilkannya:

- `identitas.subjudul`
- `identitas.instansi_pusat`
- `identitas.instansi_daerah`
- `identitas.email_bantuan`
- `identitas.telepon_bantuan`
- `identitas.wa_bantuan`
- `identitas.footer`
- `portal.hotline`
- `kop.tampilkan_ttd`
- `kop.titimangsa_tempat`
- `kop.ttd_jabatan`
- `kop.ttd_nama`
- `kop.ttd_pangkat`
- `kop.ttd_nip`

Akibatnya, admin dapat menekan tombol Simpan dan menerima pesan berhasil, tetapi perubahan tersebut tidak terlihat di aplikasi.

### 2. Konten editorial terbesar masih hard-coded

Halaman Tentang hanya memakai CMS untuk satu paragraf. Halaman Panduan hanya memakai CMS untuk FAQ. Selebihnya tetap ditulis langsung di Blade.

### 3. Perubahan CMS tidak masuk audit log

Model `Pengaturan` tidak termasuk daftar model yang dipantau `AuditLogObserver`:

- Daftar model audit: `app/Observers/AuditLogObserver.php:84-117`

Untuk aplikasi pemerintahan, ini merupakan gap penting karena perubahan pengumuman, FAQ, footer, identitas, kop surat, kontak, dan pejabat penandatangan tidak dapat ditelusuri pelakunya.

### 4. Arsitektur CMS belum mengenal scope

Semua konten CMS sekarang bersifat global. Tabel `pengaturan` tidak memiliki:

- `kawasan_id`
- `satuan_permukiman_id`
- target role
- status draft/publish
- tanggal mulai/akhir tayang
- versi/revisi
- editor/publisher
- ordering umum
- relasi media

### 5. Tidak ditemukan portal informasi publik umum

Halaman publik aktual hanya:

- Kirim pengaduan.
- Lacak pengaduan.
- Login dan pemulihan akun.

Tidak ditemukan homepage publik, berita, galeri publik, statistik publik, peta publik, halaman publik kawasan, atau halaman publik SP. Halaman `/tentang` dan `/panduan` berada di kelompok route internal yang memerlukan login.

Karena itu belum layak membangun:

- page builder;
- modul berita lengkap;
- galeri publik;
- hero carousel;
- SEO kompleks;
- CMS multibahasa.

Semua fitur itu belum mempunyai consumer aktual dan akan melanggar prinsip YAGNI.

## Jawaban singkat atas pertanyaan utama

Jika developer berhenti menyentuh source code, administrator seharusnya tetap dapat mengubah:

- identitas dan subjudul aplikasi;
- instansi dan kontak layanan;
- footer;
- halaman Tentang;
- Panduan/SOP;
- FAQ;
- petunjuk, privasi, SLA, dan hotline pengaduan;
- pengumuman dashboard;
- kop dan penandatangan laporan;
- catatan metodologi/disclaimer laporan;
- identitas umum email;
- profil kawasan dan SP hanya jika nanti benar-benar ada halaman yang mengonsumsinya.

Administrator tidak seharusnya mengubah melalui CMS:

- data transmigran, rumah, lahan, poktan, bantuan, panen, dan pengaduan;
- statistik dan hasil perhitungan;
- formula, validasi, permission, scope, route, dan security;
- skema/aturan impor;
- status transaksi dan aturan bisnis.

Kesimpulan Ponytail:

> Perluas CMS yang sudah ada. Jangan membangun CMS baru atau page builder. Prioritas pertama adalah menyambungkan field yang sudah tersedia, menambahkan audit trail, lalu memindahkan tiga kumpulan konten terbesar: Tentang, Panduan, dan informasi layanan pengaduan.

---

# 2. Ruang Lingkup dan Validasi Audit

Audit mencakup:

- 162 route runtime.
- 35 tujuan fitur/menu sidebar.
- 37 controller.
- 44 model.
- 155 Blade.
- 71 migration.
- Seluruh seeder.
- Route publik, internal, redirect kompatibilitas, endpoint file, impor, laporan, notifikasi, dan route development.
- Navigasi sidebar, header, footer, action button, modal, tab, detail page, notification link, dan quick action.
- CMS, RBAC, audit log, data scope SP, media/berkas, dashboard, laporan, peta, pengaduan, impor, halaman publik, dan email.

Validasi runtime:

- Laravel runtime: **Laravel 12.65.0**.
- `php artisan route:list --json`: berhasil, **162 route**.
- Seluruh migration runtime berstatus **Ran**.
- Tabel `pengaturan` ditemukan tetapi berisi **0 baris** saat audit. Seluruh konten CMS efektif masih berasal dari konstanta bawaan `KontenSistem::BAWAAN` sampai admin menyimpan perubahan.
- Pengujian terarah CMS, RBAC, dan penegakan izin berhasil dijalankan tanpa kegagalan.
- Audit read-only; working tree yang sudah memiliki perubahan sebelum audit dibiarkan utuh.

---

# 3. Inventarisasi Sistem

## 3.1 Menu dan fitur utama

| No | Menu/Fitur | Route | Controller | View | Data Source | Jenis |
|---:|---|---|---|---|---|---|
| 1 | Kirim Pengaduan Publik | `GET/POST /pengaduan-warga` | `PengaduanPublikController` | `pages.publik.pengaduan` | `Pengaduan`, `SatuanPermukiman`, `DaftarPilihan`, `KontenSistem` | Operasional + CMS |
| 2 | Lacak Pengaduan Publik | `/lacak-pengaduan[/{nomor}]` | `PengaduanPublikController` | `pages.publik.lacak` | `Pengaduan`, `PenangananPengaduan` | Operasional |
| 3 | Login/Logout | `/login`, `/logout` | `LoginController` | `pages.auth.signin` | `User`, session, audit | Teknis |
| 4 | Pemulihan Kata Sandi | `/lupa-kata-sandi`, `/verifikasi-kode`, `/atur-ulang-sandi` | `PemulihanSandiController` | `pages.auth.*` | `User`, `KodePemulihanSandi`, email | Teknis |
| 5 | Verifikasi Email Baru | `/verifikasi-email-baru/{token}` | `PendingEmailChangeController` | `pages.auth.confirm-email-change` | `PendingEmailChange`, `User` | Teknis |
| 6 | Ganti Kata Sandi Wajib | `/ganti-kata-sandi` | `GantiKataSandiController` | `pages.auth.ganti-kata-sandi` | `User`, audit | Teknis |
| 7 | Dashboard | `/` | Closure + support | `pages.dashboard.index` | `RekapDashboard`, `PenilaianKondisiSp`, `RekapPengaduan`, `KontenSistem` | Operasional + CMS |
| 8 | Profil Sendiri | `/profil` | `ProfilController` | `pages.profil.*` | `User`, role, SP assignment | Setting akun |
| 9 | Tentang Sistem | `/tentang` | Closure | `pages.tentang.index` | Sebagian `KontenSistem`, sebagian Blade | CMS parsial |
| 10 | Panduan Penggunaan | `/panduan` | Closure | `pages.panduan.index` | FAQ CMS + Blade hard-coded | CMS parsial |
| 11 | Kawasan Transmigrasi | `/kawasan` | `KawasanController` | `pages.sp.kawasan` | `KawasanTransmigrasi`, `Berkas`, SP | Master Data |
| 12 | Satuan Permukiman | `/sp`, `/sp/{sp}` | `SpController` | `pages.sp.index/detail` | `SatuanPermukiman` dan seluruh domain turunannya | Master + agregasi operasional |
| 13 | Inventaris SP | `/sp/inventaris` | `InventarisSpController` | `pages.sp.inventaris/detail-inventaris` | `InventarisSp`, `Berkas` | Operasional |
| 14 | Fasilitas SP | `/sp/fasilitas` | `FasilitasSpController` | `pages.sp.fasilitas/detail-fasilitas` | `FasilitasSp`, cakupan SP, `Berkas` | Operasional |
| 15 | Infrastruktur SP | `/sp/infrastruktur` | `InfrastrukturController` | `pages.infrastruktur.*` | `Infrastruktur`, cakupan SP, `Berkas` | Operasional |
| 16 | Transmigran | `/transmigran` | `TransmigranController` | `pages.transmigran.*` | Transmigran, keluarga, rumah, lahan, poktan, berkas | Operasional |
| 17 | Rumah dan Hunian | `/rumah` | `RumahController` | `pages.rumah.*` | `Rumah`, `RiwayatPenghunian`, `Berkas` | Operasional |
| 18 | Data Lahan | `/lahan` | `LahanController` | `pages.lahan.*` | `Lahan`, transmigran, SP, berkas | Operasional |
| 19 | Rekap Kependudukan | `/kependudukan/rekap[/{kelompok}]` | `KependudukanController` | `pages.kependudukan.rekap` | `RekapDashboard`, `Transmigran` | Operasional/Rekap |
| 20 | Kelompok Tani | `/poktan` | `PoktanController` | `pages.poktan.*` | Poktan, anggota, lahan, alsintan, saprotan, berkas | Operasional |
| 21 | Anggota Poktan | POST/PUT `/anggota-poktan` | `AnggotaPoktanController` | Form/modal Poktan | `AnggotaPoktan` | Operasional |
| 22 | Alsintan | `/alsintan` | `AlsintanController` | `pages.alsintan.*` | Alsintan, distribusi, poktan, berkas | Operasional |
| 23 | Saprotan | `/saprotan` | `SaprotanController` | `pages.saprotan.*` | Saprotan, distribusi, poktan, komoditas, berkas | Operasional |
| 24 | Komoditas | `/komoditas` | `KomoditasController` | `pages.komoditas.*` | `Komoditas`, satuan, penanaman | Master Data |
| 25 | Penanaman | `/penanaman` | `PenanamanController` | `pages.penanaman.*` | Penanaman, poktan, saprotan, komoditas | Operasional |
| 26 | Hasil Panen | `/panen` | `HasilPanenController` | `pages.panen.index/detail` | Hasil panen, penanaman, satuan, berkas | Operasional |
| 27 | Rekap Panen | `/panen/rekap[/{kelompok}]` | Closure | `pages.panen.rekap` | `RekapPanen` | Operasional/Rekap |
| 28 | Pengaduan Internal | `/pengaduan` | `PengaduanController` | `pages.pengaduan.*` | Pengaduan, penanganan, berkas | Operasional |
| 29 | Rekap Pengaduan | `/pengaduan/rekap[/{kelompok}]` | `PengaduanController` | `pages.pengaduan.rekap` | `RekapPengaduan` | Operasional/Rekap |
| 30 | Notifikasi | `/notifikasi` | `NotifikasiController` | `pages.notifikasi.index` | `Notifikasi` milik user | Teknis/Operasional |
| 31 | Tujuh Laporan | `/laporan/{slug}` | Closure + `LaporanData` | `pages.laporan.*` | Seluruh model terkait | Laporan |
| 32 | Dokumen Laporan | `/laporan/{slug}/dokumen` | Closure | `pages.laporan.dokumen` | Data laporan + CMS kop | Laporan |
| 33 | Data Master Wilayah | `/wilayah` | `WilayahController` | `pages.master.wilayah` | Provinsi, kabupaten, kecamatan, desa | Master Data |
| 34 | Data Master Satuan | `/master/satuan` | `MasterSatuanController` | `pages.master.satuan` | `Satuan` | Master Data |
| 35 | Daftar Pilihan | `/master/daftar-pilihan[/{jenis}]` | `MasterDaftarPilihanController` | `pages.master.*daftar-pilihan` | `DaftarPilihan` | Master Data |
| 36 | Penilaian Kondisi SP | `/master/penilaian-kondisi` | `PenilaianKondisiController` | `pages.master.penilaian-kondisi` | Parameter, ambang, status kondisi | Setting/Kebijakan |
| 37 | Pengelolaan Konten | `/cms` | `CmsController` | `pages.cms.index` | `Pengaturan` melalui `KontenSistem` | CMS |
| 38 | Pengguna | `/pengguna` | `PengaturanPenggunaController` | `pages.pengguna.index` | `User`, role, SP, audit | Setting |
| 39 | Role dan Hak Akses | `/pengaturan/role` | `PengaturanRoleController` | `pages.pengguna.role` | `Role`, `Permission` | Setting/Security |
| 40 | Audit Log | `/audit-log` | `AuditLogController` | `pages.pengguna.audit-log` | `AuditLog` | Teknis/Security |
| 41 | Template Impor | `/template-impor/{entitas}[ /xlsx]` | `TemplateImporController` | Unduhan file | `SkemaImpor`, daftar pilihan | Teknis |
| 42 | Impor Massal | `POST /impor/{entitas}` | `ImporController` | Modal per modul | `ImporEngine` + model domain | Operasional |
| 43 | Dokumen Privat | `/dokumen/{modul}/{id}/{nama}` | `DokumenController` | Stream file | `Berkas`, `PetaModulBerkas` | Teknis |
| 44 | Galeri Komponen | `/galeri-komponen` | Closure local/testing | `pages.galeri-komponen` | Data/komponen pengembangan | Teknis/dev-only |
| 45 | Uji 403 | `/uji-403` | Closure local/testing | `errors.403` | Tidak ada | Teknis/dev-only |
| 46 | Health Check | `/up` | Laravel | Bawaan framework | Tidak ada | Teknis |

## 3.2 Menu publik aktual

```text
Pengaduan Warga
├── Kirim Pengaduan
└── Lacak Pengaduan
```

Referensi: `resources/views/layouts/publik.blade.php:66-99`.

Tidak ditemukan menu publik aktual untuk:

- Beranda publik.
- Profil kawasan publik.
- Satuan Permukiman publik.
- Statistik publik.
- Berita.
- Galeri publik.
- Peta publik.
- FAQ publik tersendiri.

## 3.3 Menu authenticated aktual

Sidebar dibangun dinamis oleh `app/Helpers/MenuHelper.php:37-309` dan difilter berdasarkan permission. Struktur ringkasnya:

```text
Menu
└── Dashboard

Transmigrasi
├── Wilayah & SP
│   ├── Kawasan Transmigrasi
│   ├── Satuan Permukiman
│   ├── Inventaris SP
│   ├── Fasilitas SP
│   └── Infrastruktur SP
└── Penduduk & Lahan
    ├── Transmigran
    ├── Rumah & Hunian
    ├── Data Lahan
    └── Rekap Kependudukan

Pertanian
├── Poktan & Sarana
│   ├── Kelompok Tani
│   ├── Alsintan
│   └── Saprotan
└── Produksi Pertanian
    ├── Komoditas
    ├── Penanaman
    ├── Hasil Panen
    └── Rekap Panen

Pengaduan
└── Pengaduan Warga
    ├── Daftar Pengaduan
    └── Rekap Pengaduan

Laporan
├── Rekap Indikator Kawasan
├── Laporan Monografi SP
├── Laporan Transmigran
├── Laporan Poktan
├── Laporan Alsintan
├── Laporan Saprotan
└── Laporan Hasil Panen

Administrasi Sistem
├── Data Master
│   ├── Wilayah
│   ├── Satuan
│   ├── Daftar Pilihan
│   └── Penilaian Kondisi SP
├── Pengelolaan Konten
├── Pengaturan Sistem
│   ├── Pengguna
│   ├── Role & Hak Akses
│   └── Audit Log
└── Bantuan & Info
    ├── Panduan Penggunaan
    └── Tentang Sistem
```

## 3.4 Fitur aktif yang tidak ada di sidebar

1. Login/logout dan pemulihan akun.
2. Profil dan ganti kata sandi.
3. Notifikasi.
4. Halaman rincian seluruh domain.
5. Penanganan pengaduan.
6. Pengelolaan anggota poktan.
7. Dokumen privat.
8. Template dan proses impor.
9. Dokumen laporan polos.
10. Redirect alamat lama:
    - `/dashboard/sp/{sp}`
    - `/infrastruktur`
    - `/master/referensi`
11. Route local/testing:
    - `/galeri-komponen`
    - `/uji-403`
12. Health endpoint `/up`.

---

# 4. Inventarisasi Konten Hard-Coded dan Dummy

## 4.1 Ringkasan inventaris konten

| No | Lokasi | Konten | Sumber Sekarang | Hard-coded? | Editable Sekarang? | Kandidat CMS |
|---:|---|---|---|---|---|---|
| 1 | Login | Nama DIGITRANS, slogan, lokus | Blade | Ya | Tidak | P1 |
| 2 | Pemulihan akun | Nama sistem dan Kabupaten Malaka | Blade | Ya | Tidak | P2 |
| 3 | Header publik | Pengaduan Warga dan Kobalima Timur | Blade | Ya | Tidak | P1 |
| 4 | Footer publik | Deskripsi, daftar enam SP, mitra, copyright | Blade | Ya | Tidak | P0 |
| 5 | Footer internal | Instansi, ITS, versi alpha/Tahap 2 | Blade | Ya | Tidak | P0/P1 |
| 6 | Tentang Sistem | Profil, SP, tim, mitra, teknologi, alamat | Blade kecuali satu paragraf | Ya | Sebagian | P0 |
| 7 | Panduan | Delapan bab, peran, fitur, aturan, daftar laporan | Blade kecuali FAQ | Ya | Sebagian | P0 |
| 8 | Dashboard | Judul lokus, narasi section, keterangan grafik/kartu | Blade | Ya | Tidak | P2 sebagian |
| 9 | Dashboard | Pengumuman | `KontenSistem` | Tidak | Ya | Sudah tepat |
| 10 | Kawasan | Keterangan per kawasan | Tabel kawasan | Tidak | Ya di modul kawasan | Bukan CMS sekarang |
| 11 | SP | Keterangan, kondisi wilayah, monografi | Tabel domain SP | Tidak | Ya di modul SP | Bukan CMS operasional |
| 12 | Komoditas | Deskripsi komoditas | Tabel komoditas | Tidak | Ya di modul komoditas | Tetap di modul |
| 13 | Pengaduan publik | Cara kerja, bantuan, privasi, submit | Blade + dua field CMS | Sebagian | Sebagian | P0/P1 |
| 14 | Pelacakan publik | Penjelasan status dan eskalasi | Blade | Ya | Tidak | P1 |
| 15 | Email | Isi keamanan dan operasional | Blade | Ya | Sebagian | P2 sebagian |
| 16 | Laporan | Judul, cakupan, dasar periode, sumber, metodologi | `LaporanData::meta()` | Ya | Tidak | P1 sebagian |
| 17 | Kop laporan | Identitas kop | CMS | Tidak | Ya | Sudah tepat |
| 18 | Penandatangan | Data tersimpan CMS | CMS | Tidak | Ya, tetapi tidak dirender | P0 gap |
| 19 | Import | Urutan, prasyarat, instruksi, kolom, contoh | `SkemaImpor` + Blade | Ya | Tidak | P2 untuk narasi saja |
| 20 | Peta | Cara pakai dan pesan jaringan | Blade/JS | Ya | Tidak | NO |
| 21 | Empty state | Belum ada/tidak ada dan jalan keluar | Blade/component | Ya | Tidak | NO |
| 22 | Notifikasi | Judul/pesan berdasarkan kejadian | Service + runtime data | Dinamis | Tidak | NO |
| 23 | Status kondisi | Nama, keterangan, ambang | Database setting | Tidak | Ya | Bukan CMS |
| 24 | Role/permission | Deskripsi role dan izin | DB/seeder/kode | Sebagian | Role editable | Bukan CMS |
| 25 | Label UI CRUD | Heading, label, validasi, tombol | Blade/controller | Ya | Tidak | NO |
| 26 | Penanda data contoh | Copy environment demo/local | Layout + environment | Ya | Tidak | Setting deployment |
| 27 | Status produk | Purwarupa/Tahap 2/v0.8.4-alpha | Blade | Ya | Tidak | Setting build |

## 4.2 Halaman Tentang Sistem

Hanya paragraf Latar Belakang dan Tujuan yang dinamis:

- `resources/views/pages/tentang/index.blade.php:60-64`

Konten berikut masih hard-coded:

- Identitas dan status produk: `:40-52`
- Daftar enam SP: `:68-98`
- Tim pengembang: `:103-177`
- Mitra kelembagaan: `:180-225`
- Teknologi dan lisensi: `:228-258`
- Narahubung: `:261-296`

Risiko:

- Daftar SP dapat berbeda dari database.
- Nama orang, instansi, program, tahun, dan kontak memerlukan developer untuk diubah.
- Status `Purwarupa Antarmuka (Tahap 2)` telah menjadi copy basi.
- Informasi teknis dan institusional tercampur pada satu halaman.

Klasifikasi:

- Tim, mitra, narahubung, profil: CMS.
- Stack teknologi, versi framework, lisensi dependency: teknis, sebaiknya kode/build metadata.
- Daftar SP: data master dari database, bukan CMS.

## 4.3 Halaman Panduan Penggunaan

Hampir seluruh isi masih hard-coded:

- Daftar isi: `resources/views/pages/panduan/index.blade.php:30-101`
- Peran dan hak akses: `:108-160`
- Dashboard: `:163-188`
- Wilayah/SP: `:190-220`
- Kependudukan/lahan: `:222-255`
- Pertanian: `:257-294`
- Pengaduan: `:296-343`
- Laporan: `:345-390`
- Modal PDF: `:425-457`

Hanya FAQ pada `:408-419` yang berasal dari CMS.

Copy yang berpotensi basi:

- “Terdapat 4 profil peran utama”, padahal role dinamis.
- Tren “10 tahun terakhir”.
- Periode tetap “2016 s.d. 2026”.
- Daftar tujuh laporan tidak seluruhnya sama dengan metadata laporan runtime.
- Tombol Unduh Panduan PDF tidak mengunduh file; hanya membuka modal yang mengatakan dokumen masih finalisasi.

Rekomendasi:

- Jadikan isi panduan section terstruktur yang dikelola admin.
- Jangan izinkan HTML bebas atau page builder.
- Pertahankan aturan keamanan, validasi, dan istilah teknis penting di kode bila harus selalu konsisten dengan perilaku aplikasi.
- Tombol PDF hanya boleh tampil jika dokumen benar-benar tersedia.

## 4.4 Footer publik dan internal

Footer internal masih hard-coded:

- `resources/views/components/sim/footer.blade.php:8-17`

Footer publik masih hard-coded dan menggandakan data SP:

- `resources/views/components/sim/footer-publik.blade.php:20-85`

Duplikasi meliputi:

- Kementerian Transmigrasi.
- Pemerintah Kabupaten Malaka.
- ITS Surabaya.
- Deskripsi sistem.
- Daftar enam SP.
- Copyright.

CMS sebenarnya sudah mempunyai field footer, instansi, subjudul, dan kontak, tetapi footer tidak membacanya.

Rekomendasi:

- Footer teks dari CMS.
- Daftar SP dari database.
- Versi aplikasi dari build/config, bukan CMS.
- Lisensi software tetap di kode atau metadata build.

## 4.5 Pengaduan publik

Yang sudah dinamis:

- Sambutan: `resources/views/pages/publik/pengaduan.blade.php:106-113`
- Disclaimer: `:315-320`

Yang masih hard-coded:

- Tahapan setelah submit: `:116-123`
- Bantuan identitas pelapor: `:131-185`
- Bantuan kategori: `:219-233`
- Bantuan foto/lokasi: `:271-301`
- Pernyataan submit: `:310-317`

Pelacakan publik memiliki penjelasan status hard-coded:

- `resources/views/pages/publik/lacak.blade.php:120-127`

Instruksi eskalasi juga hard-coded:

- `resources/views/pages/publik/lacak.blade.php:218-220`

Rekomendasi:

CMS dapat mengelola:

- intro layanan;
- SLA atau estimasi penanganan;
- privasi;
- hotline;
- cara melacak;
- jalur eskalasi.

Tetap di kode:

- urutan status;
- aturan privasi minimum;
- rate limit;
- validasi;
- pesan keamanan;
- aturan file.

## 4.6 Dashboard

Data dashboard sudah berasal dari Eloquent/support nyata, bukan angka CMS.

Referensi:

- `routes/internal.php:64-258`
- `app/Support/RekapDashboard.php`
- `resources/views/pages/dashboard/index.blade.php`

Yang sudah menjadi CMS:

- Banner pengumuman: `resources/views/pages/dashboard/index.blade.php:28-43`

Editorial hard-coded:

- Judul Dashboard Kawasan Kobalima Timur: `:45-47`
- Narasi Ringkasan Kawasan: `:123-124`
- Narasi Kependudukan: `:419-424`
- Narasi Pertanian dan Ekonomi: `:533-538`
- Narasi Infrastruktur dan Layanan: `:671-676`
- Keterangan grafik dan kartu lainnya.

Rekomendasi:

- Judul/lokus dan narasi tingkat tinggi boleh CMS.
- Nama indikator, satuan, definisi metrik, tooltip angka, dan formula tetap kode.

## 4.7 Laporan

Metadata editorial laporan saat ini berada di:

- `app/Support/LaporanData.php:131-210`

Termasuk:

- judul;
- cakupan;
- dasar periode;
- sumber;
- catatan metodologi;
- jumlah kolom.

Contoh catatan metodologi lain:

- `resources/views/pages/laporan/isi/indikator-kawasan.blade.php:53-55`
- `resources/views/pages/laporan/isi/monografi-sp.blade.php:21-24`

Klasifikasi:

Tetap kode:

- slug laporan;
- route;
- permission;
- query;
- struktur tabel;
- jumlah kolom;
- orientasi;
- formula;
- judul resmi jika merupakan nama dokumen baku.

Kandidat CMS:

- catatan metodologi;
- disclaimer;
- kata pengantar;
- catatan kaki editorial;
- pejabat penandatangan.

## 4.8 Import

Sumber teknis impor:

- `app/Support/SkemaImpor.php`
- `app/Support/ImporEngine.php`
- `app/Http/Controllers/TemplateImporController.php`
- `resources/views/components/sim/modal-impor.blade.php`

Skema teknis tidak boleh dijadikan CMS:

- nama kolom;
- field wajib;
- enum;
- batas baris;
- format tanggal;
- validasi;
- urutan dependensi yang berasal dari relasi model.

Kandidat CMS terbatas:

- catatan SOP lokal;
- nama petugas yang dapat dihubungi;
- tautan video/panduan;
- penjelasan tambahan non-normatif per entitas.

Catatan: semua 14 entitas yang terdaftar pada `ImporEngine::MODUL` aktif. Copy “fitur belum aktif” dalam modal hanya tampil jika entitas tidak aktif; tidak perlu dipindah ke CMS.

## 4.9 Peta

Komponen:

- `resources/views/components/sim/tautan-peta.blade.php`
- `resources/views/components/sim/koordinat-input.blade.php`
- `resources/js/peta.js`

Copy yang ditemukan adalah instruksi teknis dan fallback jaringan. Ini tidak perlu CMS karena harus tetap selaras dengan perilaku komponen.

Koordinat contoh Kobalima Timur hanya placeholder instruksional, bukan konten editorial.

## 4.10 Empty state, label, tooltip, dan modal

Sebagian besar merupakan product copy stabil dan bukan CMS:

- “Belum ada data...”
- “Tidak ada hasil...”
- “Isi field...”
- pesan validasi;
- bantuan keamanan;
- label tombol;
- dialog konfirmasi;
- tooltip satuan;
- penjelasan aturan bisnis.

Jika semua ini dimasukkan ke CMS, admin dapat membuat UI bertentangan dengan perilaku sistem. Tetap simpan di kode.

## 4.11 Dummy dan copy basi

Runtime modul utama telah memakai Eloquent nyata. `DummyData` masih digunakan terutama untuk:

- katalog permission/role awal;
- compatibility shape mapper;
- demo/seeder.

Pemakaian runtime langsung yang relevan:

- Katalog izin pada `app/Providers/ViewServiceProvider.php:273-275`

Itu adalah data teknis, bukan kandidat CMS.

Copy data contoh masih muncul berdasarkan environment:

- Penentu: `app/Providers/ViewServiceProvider.php:437-444`
- Layout aplikasi: `resources/views/layouts/app.blade.php:264-283`
- Layout dokumen: `resources/views/layouts/dokumen.blade.php:57-75`

Beberapa laporan juga masih menyebut “pada data contoh”, misalnya:

- `resources/views/pages/laporan/isi/indikator-kawasan.blade.php:145-150`
- `resources/views/pages/laporan/isi/monografi-sp.blade.php:73-77`
- `resources/views/pages/laporan/isi/hasil-panen.blade.php:130-134`

Ini bukan CMS. Copy harus diturunkan dari environment atau dibersihkan sebelum deployment.

---

# 5. Audit CMS yang Sudah Ada

## 5.1 Alur aktual

```text
Menu Pengelolaan Konten
  -> GET /cms [cms.lihat]
  -> CmsController@index
  -> KontenSistem::semua() + faq()
  -> pages.cms.index

Form per tab
  -> PUT /cms [cms.ubah]
  -> CmsController@simpan
  -> validasi berdasarkan tab
  -> KontenSistem::simpan
  -> Pengaturan::updateOrCreate
```

Rute internal wajib:

- authentication;
- melewati kewajiban ganti kata sandi;
- permission route;
- rate limiting internal.

Referensi:

- `bootstrap/app.php`
- `app/Support/PetaIzinRute.php:163-165`
- `app/Http/Middleware/EnsureIzin.php`

## 5.2 Permission CMS

CMS bawaan dapat diakses oleh:

- Admin.
- Dinas Transmigrasi.

Referensi:

- `app/Support/DummyData.php:5682-5716`

Dinas Pertanian dan Operator SP tidak memiliki permission CMS bawaan.

Ini masuk akal untuk CMS global. Jika konten per-SP kelak ditambahkan, permission `cms.ubah` global tidak boleh otomatis memberi Operator SP hak mengubah konten seluruh kawasan.

## 5.3 Struktur penyimpanan

Tabel `pengaturan` memiliki:

```text
kunci       string primary key
nilai       text nullable
tipe        string default teks
timestamps
```

Tidak tersedia:

- scope;
- status publikasi;
- jadwal;
- urutan umum;
- editor/publisher;
- revisi;
- soft delete;
- relasi media.

Jenis nilai yang digunakan service:

- teks;
- boolean;
- JSON.

Migration juga menyebut tipe `berkas`, tetapi service belum mengimplementasikan pengelolaan media CMS.

## 5.4 Pemetaan 33 key CMS

| Grup | Jumlah | Kondisi |
|---|---:|---|
| Identitas | 8 | Hanya `nama_app` yang benar-benar digunakan |
| Kop laporan | 11 | Lima identitas kop digunakan; enam field penandatangan belum digunakan |
| Profil dan FAQ | 2 | Keduanya digunakan |
| Portal pengaduan | 4 | Sambutan, disclaimer, prefix digunakan; hotline tidak |
| Pengumuman | 4 | Seluruhnya digunakan |
| Email | 4 | Seluruhnya digunakan pada wrapper email |

## 5.5 Yang editable dan terhubung

### Nama aplikasi

Digunakan oleh:

- title layout;
- header;
- sidebar;
- email subject;
- email body.

Referensi:

- `resources/views/layouts/app.blade.php:9`
- `resources/views/layouts/sidebar.blade.php:100-112`
- `resources/views/layouts/app-header.blade.php:52-55`

### Kop laporan dan email

- `app/Support/LaporanData.php:83-97`
- `resources/views/components/sim/kop-laporan.blade.php:28-59`
- `resources/views/emails/layout.blade.php:13-27`

### Narasi Tentang

- `resources/views/pages/tentang/index.blade.php:60-64`

### FAQ

- `resources/views/pages/panduan/index.blade.php:408-419`

### Portal warga

- `app/Http/Controllers/PengaduanPublikController.php:44-59`
- `resources/views/pages/publik/pengaduan.blade.php:106-113,315-320`

### Prefix nomor pengaduan

- `app/Support/KontenSistem.php:266-275`
- `app/Support/NomorPengaduan.php:31-34`

### Pengumuman

- `routes/internal.php:148-152`
- `resources/views/pages/dashboard/index.blade.php:28-43`

### Wrapper email

Sapaan, penutup, nama pengirim, dan footer email dipakai pada `resources/views/emails/*.blade.php`.

## 5.6 Editable tetapi belum terhubung

| Field | Gap |
|---|---|
| Subjudul/lokus | Header, sidebar, login masih menulis Kobalima Timur langsung |
| Instansi pusat/daerah | Tentang dan footer masih hard-coded |
| Email/telepon/WA bantuan | Tidak dirender |
| Footer | Footer internal dan publik tidak menggunakannya |
| Hotline | Tidak tampil di form maupun pelacakan |
| Tampilkan tanda tangan | Tidak dibaca dokumen laporan |
| Tempat/jabatan/nama/pangkat/NIP | Tidak dirender pada laporan |

## 5.7 Kelemahan teknis CMS

1. Tidak ada audit trail.
2. Tidak ada transaction ketika satu tab menyimpan banyak key.
3. Tidak ada scope kawasan/SP.
4. Tidak ada draft/publish.
5. Tidak ada scheduling.
6. Tidak ada revision/version.
7. Tidak ada media attachment.
8. Tidak ada editor/publisher.
9. Tidak ada ordering generik.
10. Tidak ada seeder CMS.
11. Semua default masih berada di source code.
12. Tabel runtime kosong saat audit.
13. Komentar service menyebut memoization, tetapi `mentah()` menjalankan query baru setiap pemanggilan: `app/Support/KontenSistem.php:307-310`.
14. Tab tidak dikenal jatuh ke penyimpanan Identitas: `app/Http/Controllers/CmsController.php:36-45`.
15. Satu halaman mencampur editorial, identitas, konfigurasi laporan, dan konfigurasi nomor tiket.

---

# 6. Klasifikasi Fitur

## Kategori A — Data Operasional

Tidak masuk CMS:

- Transmigran.
- Anggota keluarga.
- Rumah dan riwayat penghunian.
- Lahan.
- Poktan dan keanggotaan.
- Alsintan dan distribusi.
- Saprotan dan distribusi.
- Penanaman.
- Hasil panen.
- Infrastruktur.
- Inventaris.
- Fasilitas.
- Pengaduan.
- Penanganan pengaduan.
- Notifikasi.
- Penilaian per SP.
- Statistik dashboard.
- Nilai laporan.

## Kategori B — Master Data

Tetap dikelola di modul Data Master:

- Provinsi.
- Kabupaten.
- Kecamatan.
- Desa.
- Kawasan.
- Satuan Permukiman.
- Satuan dan faktor konversi.
- Daftar pilihan.
- Jenis aset.
- Sumber dana.
- Kondisi.
- Kategori dan bidang pengaduan.
- Tipe komoditas.
- Status hunian.
- Komoditas.

Master data memang editable, tetapi bukan konten editorial.

## Kategori C — Content/CMS

Layak CMS:

- identitas aplikasi;
- footer;
- kontak;
- profil/Tentang;
- panduan/SOP;
- FAQ;
- pengumuman;
- informasi layanan pengaduan;
- privasi/SLA/hotline;
- kop dan penandatangan laporan;
- catatan metodologi/disclaimer;
- identitas email;
- profil editorial kawasan/SP jika consumer-nya ada.

## Kategori D — Configuration/Setting

Bukan konten editorial biasa:

- bobot penilaian kondisi;
- ambang status;
- prefix nomor tiket;
- opsi tanda tangan;
- rate limit;
- batas upload;
- pagination;
- SMTP;
- environment;
- APP_URL/ASSET_URL.

## Kategori E — Tidak Boleh Diubah Admin melalui CMS

- formula produktivitas;
- agregasi dashboard;
- perhitungan kondisi SP;
- alur status pengaduan;
- validasi identitas;
- permission dan route map;
- global scope SP;
- query statistik;
- aturan satu rumah-satu keluarga;
- aturan hasil panen aktif;
- aturan distribusi;
- struktur template impor;
- format keamanan email;
- pesan anti-enumeration;
- label teknis grafik.

---

# 7. Kandidat CMS dan Prioritas

## 7.1 Daftar kandidat

| Prioritas | Konten | Lokasi | Alasan | Scope | Bentuk Edit |
|---|---|---|---|---|---|
| P0 | Halaman Tentang Sistem | `/tentang` | Mayoritas informasi institusi/tim masih hard-coded | Global | Section text + daftar terstruktur |
| P0 | Panduan/SOP | `/panduan` | Besar, sering berubah mengikuti fitur, dan sudah ada klaim basi | Global | Section terstruktur |
| P0 | Identitas/footer/kontak | Semua layout | Field sudah ada tetapi belum terhubung | Global | Teks pendek + kontak |
| P0 | Penandatangan laporan | Dokumen laporan | Form CMS ada tetapi output tidak memakai nilainya | Global | Field terstruktur |
| P0 | Informasi layanan pengaduan | Form/lacak publik | Admin perlu mengubah SOP, SLA, hotline, privasi | Global | Teks terstruktur |
| P0 | Pengumuman dashboard | Dashboard | Sudah tepat sebagai konten dinamis | Global | Judul, isi, tipe, aktif |
| P1 | FAQ | `/panduan` | Sudah editable dan bermanfaat | Global | Repeater |
| P1 | Profil kawasan editorial | Tentang/kawasan | Identitas teknis ada, narasi belum terstruktur | Per kawasan | Judul, ringkasan, deskripsi |
| P1 | Kontak dan mitra | Tentang/footer/publik | Duplikasi hard-coded | Global/per kawasan | Daftar terstruktur |
| P1 | Catatan metodologi laporan | Tujuh laporan | Bisa berubah tanpa mengubah query | Per tipe laporan | Textarea |
| P1 | Disclaimer laporan | Laporan | Berpotensi berubah menurut kebijakan | Per tipe laporan | Textarea |
| P1 | Branding halaman autentikasi | Login/pemulihan | Tidak mengikuti identitas CMS | Global | Nama, slogan, subjudul |
| P2 | Narasi dashboard | Dashboard | Berguna untuk menjelaskan indikator | Global | Teks pendek per section |
| P2 | Profil/deskripsi editorial SP | Detail SP | Relevan bila nanti ada profil publik | Per SP | Ringkasan/deskripsi |
| P2 | Bantuan tambahan impor | Modal/template | SOP lokal dapat berubah, aturan teknis tidak | Per entitas | Catatan tambahan |
| P2 | Email non-keamanan | Email | Bisa menambah pengantar layanan | Global/per jenis | Teks pendek |
| P2 | Logo/lambang/visual | Layout/laporan | Jarang berubah dan sensitif | Global | Media terbatas |
| NO | Label form/validasi/empty state | CRUD | Bagian produk, bukan editorial | - | Tetap kode |
| NO | Petunjuk teknis peta | Peta | Harus selaras dengan perilaku komponen | - | Tetap kode |
| NO | Definisi indikator | Dashboard/laporan | Harus konsisten dengan formula | - | Tetap kode |
| NO | Hero/berita/galeri | Tidak ada consumer | Fitur belum ada | - | Jangan dibangun |

## 7.2 Skor kandidat

Kriteria:

- Sering berubah.
- Perlu diedit admin.
- Saat ini hard-coded.
- Bersifat editorial.
- Risiko jika tetap hard-coded.
- Cocok untuk CMS.

Maksimum: 30.

| Kandidat | Sering berubah | Perlu admin | Hard-coded | Editorial | Risiko | Cocok CMS | Total |
|---|---:|---:|---:|---:|---:|---:|---:|
| Panduan/SOP operasional | 4 | 5 | 5 | 5 | 5 | 5 | **29** |
| Halaman Tentang Sistem | 3 | 5 | 5 | 5 | 5 | 5 | **28** |
| Informasi layanan pengaduan | 3 | 5 | 5 | 5 | 5 | 5 | **28** |
| Identitas/footer/kontak | 3 | 5 | 4 | 5 | 5 | 5 | **27** |
| Pengumuman dashboard | 5 | 5 | 1 | 5 | 5 | 5 | **26** |
| Penandatangan laporan | 4 | 5 | 1 | 5 | 5 | 5 | **25** |
| Profil kawasan editorial | 3 | 5 | 3 | 5 | 4 | 5 | **25** |
| Metodologi/disclaimer laporan | 2 | 4 | 5 | 5 | 3 | 4 | **23** |
| Kontak/mitra kelembagaan | 3 | 4 | 5 | 5 | 3 | 3 | **23** |
| FAQ | 4 | 5 | 1 | 5 | 2 | 5 | **22** |
| Branding autentikasi | 2 | 3 | 5 | 4 | 3 | 4 | **21** |
| Profil/deskripsi SP | 2 | 4 | 2 | 5 | 3 | 4 | **20** |
| Narasi dashboard | 2 | 3 | 5 | 4 | 2 | 3 | **19** |
| Template email non-keamanan | 2 | 3 | 5 | 4 | 2 | 3 | **19** |
| Bantuan tambahan impor | 2 | 3 | 4 | 3 | 3 | 3 | **18** |
| Logo/lambang/visual | 1 | 2 | 5 | 3 | 3 | 3 | **17** |
| Petunjuk peta | 1 | 1 | 5 | 1 | 1 | 1 | **10** |
| Empty state umum | 1 | 1 | 5 | 1 | 1 | 1 | **10** |

Skor tinggi tidak otomatis berarti harus membuat tabel baru. Profil kawasan misalnya tetap P1 karena belum ditemukan halaman publik yang benar-benar memerlukan narasi tersebut.

---

# 8. Yang Sebaiknya Tidak Masuk CMS

## 8.1 Data operasional

- Data transmigran.
- Anggota keluarga.
- Rumah.
- Riwayat hunian.
- Lahan.
- Poktan dan keanggotaan.
- Pengadaan dan distribusi alsintan.
- Pengadaan dan distribusi saprotan.
- Penanaman.
- Hasil panen.
- Infrastruktur.
- Inventaris.
- Fasilitas.
- Pengaduan dan penanganan.
- Notifikasi.
- Audit log.
- Penilaian aktual per SP.

## 8.2 Angka dan hasil perhitungan

- Jumlah KK/jiwa/petani.
- Luas lahan.
- Volume panen.
- Produktivitas.
- Harga rata-rata.
- Persentase hunian.
- Status kondisi SP.
- Jumlah pengaduan.
- Isu prioritas.
- Semua seri grafik.

Angka harus berasal dari database dan formula sistem.

## 8.3 Master data

- Wilayah administratif.
- Kawasan dan SP.
- Satuan dan faktor konversi.
- Komoditas.
- Daftar pilihan.
- Jenis aset.
- Sumber dana.
- Status/kondisi.
- Kategori/bidang pengaduan.

## 8.4 Configuration dan security

- Prefix nomor tiket sebaiknya diklasifikasikan sebagai setting, meski sekarang berada di CMS.
- Rate limit.
- SMTP.
- batas upload.
- permission.
- route map.
- scope SP.
- password/security copy wajib.
- anti-enumeration.
- audit retention.

## 8.5 Aturan bisnis dan impor

- Formula produksi.
- Konversi satuan.
- Urutan status pengaduan.
- Aturan rumah dan penghuni.
- Aturan hasil panen.
- Batas dan constraint distribusi.
- Nama kolom impor.
- Validasi XLSX/CSV.
- Maksimum baris.
- Relasi prasyarat impor.

## 8.6 Field editorial pada record operasional

Field berikut tetap berada pada modul record-nya:

- `komoditas.deskripsi`
- `kawasan_transmigrasi.keterangan`
- `satuan_permukiman.keterangan`
- catatan aset;
- catatan pengaduan;
- catatan penanaman/panen;
- keterangan dokumen.

Field tersebut melekat pada satu record dan paling tepat disunting dalam konteks record tersebut.

Jika nanti diperlukan profil publik kawasan/SP, buat konten editorial terpisah hanya setelah consumer-nya ada. Jangan memakai `keterangan` operasional sebagai profil publik.

---

# 9. Redundansi dan Duplikasi Konten

Duplikasi yang ditemukan:

1. Nama/lokus Kobalima Timur muncul di sidebar, login, dashboard, footer, Tentang, Panduan, dan laporan.
2. Daftar enam SP ditulis manual di Tentang dan footer publik, padahal tersedia di database.
3. Nama Kementerian, Pemkab Malaka, Dinas, dan ITS muncul di default CMS, footer, Tentang, laporan, dan email.
4. Kontak instansi tersebar antara default CMS dan kop laporan.
5. Informasi layanan pengaduan tersebar di form publik, halaman lacak, Panduan, dan email.
6. Catatan metodologi laporan tersebar di `LaporanData::meta()` dan partial isi laporan.
7. Copy “data contoh” berulang di layout dan empty state laporan.
8. Copy versi/status Tahap 2 muncul di footer dan Tentang.

Rekomendasi sumber tunggal:

- Identitas global: `KontenSistem`.
- Daftar SP: query `SatuanPermukiman`.
- Nama laporan dan struktur teknis: `LaporanData::meta()`.
- Catatan editorial laporan: CMS per slug.
- Aturan/label teknis: kode.
- Versi aplikasi: config/build metadata.

---

# 10. Audit Media dan Berkas

Sistem sudah memiliki:

- registry `berkas`;
- metadata file;
- penyimpanan privat;
- ownership melalui pivot/FK domain;
- route download berizin;
- pemetaan modul berkas.

Referensi:

- `app/Models/Berkas.php:9-57`
- `app/Support/PenyimpananDokumen.php`
- `app/Http/Controllers/DokumenController.php`
- `app/Support/PetaModulBerkas.php`

CMS sendiri mengakui bahwa upload logo/favicon belum tersedia:

- `resources/views/pages/cms/index.blade.php:136-142`

Rekomendasi:

> Jangan membuat sistem upload kedua untuk CMS.

Jika media CMS benar-benar dibutuhkan:

1. Reuse model dan metadata `Berkas`.
2. Tambahkan kebijakan visibilitas publik eksplisit.
3. Batasi MIME dan ukuran per tipe.
4. Jangan membuka seluruh disk privat.
5. Catat ownership konten dan pengunggah.
6. Sajikan melalui controller/route yang aman.

Media belum perlu diimplementasikan sebelum ada consumer nyata seperti logo yang dapat diganti atau profil kawasan publik.

---

# 11. Rekomendasi Struktur Menu CMS

Struktur paling sederhana berdasarkan kebutuhan aktual:

```text
Pengelolaan Konten
├── Identitas & Kontak
├── Halaman Informasi
│   ├── Tentang Sistem
│   └── Panduan Penggunaan
├── Layanan Pengaduan
│   ├── Petunjuk & Privasi
│   └── FAQ
├── Pengumuman
└── Dokumen & Email
    ├── Kop Laporan
    ├── Penandatangan
    └── Identitas Email
```

Tetap gunakan satu route `/cms` dan satu halaman bertab. Tidak perlu membuat submenu sidebar untuk setiap tab.

Belum perlu dibuat:

```text
Berita
Galeri
Hero/Banner carousel
SEO
Media Library
Page Builder
Bahasa
Revision Manager
Approval Workflow
Template Builder
```

Jika profil kawasan/SP nanti disetujui dan mempunyai consumer nyata:

```text
Pengelolaan Konten
└── Profil Wilayah
    ├── Profil Kawasan
    └── Profil SP
```

---

# 12. Rekomendasi Model Data CMS

## 12.1 Pertahankan tabel `pengaturan`

Tabel sekarang masih cukup untuk singleton global:

- nama aplikasi;
- footer;
- kontak;
- teks portal;
- email wrapper;
- kop laporan;
- satu pengumuman global;
- satu halaman Tentang;
- FAQ sederhana;
- section Panduan.

Tidak perlu mengganti arsitektur sekarang karena:

- daftar halaman tetap;
- jumlah field terbatas;
- tidak ada kebutuhan membuat halaman bebas;
- tidak ada multibahasa;
- tidak ada multi-tenant konten;
- tidak ada portal publik umum.

## 12.2 Gunakan whitelist key dari kode

Jangan membuat `content_type` bebas buatan admin.

Contoh key yang dapat diperluas:

```text
halaman.tentang.*
halaman.panduan.*
layanan_pengaduan.*
laporan.{slug}.catatan
```

Admin boleh mengubah isi, tetapi tidak membuat tipe yang aplikasi tidak tahu cara menampilkannya.

## 12.3 Slug

Belum dibutuhkan karena route halaman sudah tetap:

- `/tentang`
- `/panduan`
- `/pengaduan-warga`
- `/lacak-pengaduan`

Slug baru hanya diperlukan jika admin dapat membuat halaman baru. Kebutuhan tersebut belum ditemukan.

## 12.4 Rich text

Tahap awal cukup menggunakan:

- plain text;
- multiline textarea;
- repeater;
- section terstruktur;
- bullet/numbered list terbatas.

Jangan langsung menerima HTML bebas karena meningkatkan risiko XSS dan membutuhkan sanitasi/editor tambahan.

## 12.5 FAQ

JSON array sekarang cukup karena:

- jumlah kecil;
- hanya satu consumer;
- urutan mengikuti array.

Pisahkan ke tabel FAQ hanya jika dibutuhkan:

- kategori;
- publish per item;
- pencarian;
- scheduling;
- banyak halaman;
- jumlah besar;
- analytics.

## 12.6 Pengumuman

Model sekarang mendukung satu banner global. Ini cukup jika kebutuhan aktual tetap satu pengumuman aktif.

Tabel pengumuman terpisah baru layak jika dibutuhkan:

- banyak pengumuman;
- riwayat;
- schedule;
- target role;
- target kawasan/SP;
- arsip;
- ordering.

## 12.7 Konten per kawasan/SP

Jangan menambahkan kolom `deskripsi` ke tabel utama secara spekulatif.

Urutan keputusan:

1. Pastikan ada halaman yang mengonsumsinya.
2. Pastikan berbeda dari `keterangan` operasional.
3. Baru tambahkan penyimpanan scoped.

Jika disetujui, struktur minimum yang dapat dipertimbangkan:

```text
konten_entitas
- id
- subject_type: kawasan | sp
- subject_id
- key
- value
- type
- updated_by
- timestamps
- unique(subject_type, subject_id, key)
```

Batasi hanya kawasan dan SP. Jangan menjadikannya polymorphic CMS untuk seluruh model.

## 12.8 Publish, version, scheduling, dan ordering

| Fitur | Rekomendasi |
|---|---|
| Draft/publish | Belum perlu untuk singleton global |
| Scheduling | Pertimbangkan hanya untuk pengumuman |
| Versioning penuh | Belum perlu |
| Audit trail | Wajib |
| Ordering | FAQ dan section saja |
| Attachment | P2 |
| SEO | Tidak perlu untuk halaman authenticated |
| Multibahasa | Tidak perlu |
| Role-specific content | Belum ada kebutuhan |
| Per-SP content | P2, menunggu consumer |
| Reusable block | Identitas, kontak, disclaimer saja |

## 12.9 Caching/query

Tidak perlu Redis atau cache kompleks.

Perbaikan minimum:

1. Memoize seluruh nilai `pengaturan` selama satu request.
2. Reset memo setelah `simpan()`.
3. Gunakan cache Laravel hanya jika traffic publik meningkat.
4. Invalidasi seluruh cache CMS setelah perubahan karena jumlah key kecil.

---

# 13. Potensi Masalah Arsitektur

## P0

### Field CMS tanpa consumer

Admin menerima kesan perubahan berlaku, padahal tidak tampil.

### CMS tidak diaudit

Tidak ada bukti siapa mengubah FAQ, pengumuman, footer, nomor tiket, kop laporan, kontak, atau penandatangan.

### Penandatangan laporan tidak dirender

UI dan service menyediakan data, tetapi dokumen tidak menggunakannya.

### Default CMS masih source-controlled

Karena tabel `pengaturan` kosong, seluruh isi efektif berasal dari `KontenSistem::BAWAAN`.

## P1

### CMS dan setting teknis tercampur

`portal.awalan_nomor` adalah konfigurasi identitas transaksi, bukan editorial.

### Penyimpanan tab tidak transaksional

Satu tab menyimpan beberapa key secara berurutan. Kegagalan di tengah dapat menghasilkan state parsial.

### Tidak ada scope

Pengumuman hanya global; FAQ dan profil hanya global.

### Konten terduplikasi

Identitas, daftar SP, instansi, mitra, dan layanan pengaduan memiliki banyak sumber.

### Halaman “publik” tidak selalu publik

Tombol CMS “Lihat Halaman Publik” menuju `/tentang`, tetapi route `/tentang` berada pada route internal authenticated:

- Tombol: `resources/views/pages/cms/index.blade.php:29-32`
- Route: `routes/internal.php:278-284`

### Tidak ada memoization aktual

`KontenSistem::mentah()` melakukan query baru setiap pemanggilan.

## P2

### Tidak ada draft/review

Setiap simpan langsung aktif.

### Pengumuman tidak punya schedule atau audience

Masih cukup untuk satu banner global, tetapi ada ceiling.

### Berkas privat belum cocok untuk aset publik

Tidak boleh diselesaikan dengan membuka seluruh storage privat.

### Copy status produk basi

- Purwarupa Tahap 2.
- `v0.8.4-alpha`.
- Data contoh.
- PDF sedang finalisasi.

Copy ini bukan CMS. Sumber yang tepat adalah build/config atau penghapusan sebelum deployment.

---

# 14. Prioritas Implementasi

## P0 — Wajib

### Tahap 1: Benahi CMS yang sudah ada

1. Hubungkan field yang sudah tersedia:
   - subjudul;
   - instansi;
   - kontak;
   - footer;
   - hotline;
   - penandatangan laporan.
2. Catat perubahan CMS ke audit log.
3. Bungkus penyimpanan satu tab dalam transaction.
4. Tolak tab CMS yang tidak dikenal.
5. Tambahkan memoization per request.
6. Tambahkan test untuk field yang sekarang tidak terhubung.
7. Hilangkan pesan sukses palsu untuk field tanpa consumer.

Tahap ini tidak membutuhkan page builder atau tabel baru.

### Tahap 2: Dinamisasi halaman informasi aktual

1. Tentang Sistem.
2. Panduan Penggunaan.
3. Informasi pengaduan.
4. Kontak dan footer.
5. FAQ tetap memakai pola sekarang.

Gunakan `pengaturan` dan section tetap terlebih dahulu.

### Tahap 3: Bersihkan copy basi

1. Purwarupa Tahap 2.
2. `v0.8.4-alpha`.
3. Empty state “data contoh”.
4. Modal PDF “sedang finalisasi”.
5. Klaim lama pada Panduan.

Sebagian besar harus dihapus atau diturunkan dari sistem, bukan dipindah ke CMS.

## P1 — Penting

1. Catatan metodologi dan disclaimer per laporan.
2. Daftar mitra/instansi terpusat.
3. Branding login dan pemulihan akun.
4. Profil kawasan editorial jika benar-benar akan ditampilkan.
5. Daftar SP dari database, bukan hard-coded.
6. Riwayat pengumuman hanya bila satu banner tidak cukup.

## P2 — Nice to have

1. Narasi section dashboard.
2. Profil editorial per-SP.
3. SOP tambahan per entitas impor.
4. Konten email tambahan non-keamanan.
5. Logo/lambang yang dapat diganti terbatas.
6. Scheduling pengumuman.
7. Media halaman Tentang/profil kawasan.

## NO — Jangan dimasukkan

1. Statistik.
2. Formula.
3. Query.
4. Validasi.
5. Permission.
6. Scope SP.
7. Status transaksi.
8. Struktur route.
9. Kolom impor.
10. Faktor konversi.
11. Label teknis grafik.
12. Empty state rutin.
13. Pesan error keamanan.
14. Alur status.
15. Data operasional.

---

# 15. Rekomendasi Urutan Implementasi Berikutnya

Urutan paling aman dan minimum:

```text
Tahap A
Audit trail CMS + transaction + validasi tab + memoization

Tahap B
Hubungkan field CMS yang sudah ada ke footer, header, kontak,
portal, dan dokumen laporan

Tahap C
Pindahkan isi Tentang dan Panduan ke section tetap pada
arsitektur KontenSistem saat ini

Tahap D
Pusatkan informasi layanan pengaduan, SLA, hotline,
privasi, dan bantuan pelacakan

Tahap E
Pindahkan hanya catatan editorial laporan,
bukan judul, izin, formula, atau struktur kolom

Tahap F
Evaluasi kebutuhan halaman publik profil kawasan/SP

Tahap G
Hanya jika Tahap F disetujui:
tambahkan scope konten kawasan/SP dan integrasi media
```

Yang sengaja tidak direkomendasikan:

- mengganti total tabel `pengaturan`;
- membuat page builder;
- generic polymorphic CMS untuk semua model;
- WYSIWYG HTML bebas;
- media library kedua;
- workflow publishing kompleks;
- SEO;
- multibahasa;
- berita/galeri/hero tanpa consumer.

---

# 16. Kesimpulan Final

CMS saat ini **bukan kosong dan bukan gagal**, tetapi baru memenuhi sebagian kebutuhan. Fondasi yang ada sudah cukup untuk pengembangan tahap berikutnya.

Keputusan arsitektur yang paling tepat:

1. **Reuse `KontenSistem` dan tabel `pengaturan`.**
2. **Jangan membangun arsitektur CMS baru.**
3. **Selesaikan gap field yang sudah ada sebelum menambah fitur.**
4. **Audit trail adalah kebutuhan wajib.**
5. **Tentang, Panduan, dan layanan pengaduan adalah sasaran editorial terbesar.**
6. **Data operasional, master data, setting teknis, dan aturan bisnis harus tetap terpisah dari CMS.**
7. **Konten per kawasan/SP hanya dibangun jika ada halaman consumer yang nyata.**
8. **Media harus menggunakan registry `berkas` yang sudah ada, bukan sistem upload kedua.**
9. **Page builder, SEO, berita, galeri, multibahasa, versioning penuh, dan approval workflow belum dibutuhkan.**

Jawaban final:

> Jika developer berhenti menyentuh source code, administrator seharusnya tetap dapat mengubah seluruh narasi, identitas, kontak, panduan, FAQ, pengumuman, informasi layanan, kop, penandatangan, dan disclaimer. Administrator tidak boleh menggunakan CMS untuk mengubah fakta operasional, statistik, formula, validasi, permission, security, route, atau aturan bisnis.

---

# 17. Catatan Verifikasi

- Audit awal menggunakan kode aktual sebagai source of truth.
- Implementasi mempertahankan tabel `pengaturan` dan whitelist key yang sudah ada; tidak ada migration baru.
- Focused CMS suite lulus: 19 test / 93 assertion.
- Seluruh Feature suite lulus: 768 test / 7.600 assertion.
- Kompilasi Blade (`php artisan view:cache`) dan build aset (`npm run build`) lulus.
- Seluruh migration runtime berstatus `Ran`.
- Full-suite gabungan sempat dibatasi memori/waktu oleh proses XLSX dan durasi suite Database; domain yang diubah diverifikasi terarah dan Feature suite dijalankan penuh.
- Working tree awal memiliki dua plan untracked; plan audit ini kini ikut diperbarui sebagai catatan implementasi, sedangkan plan daftar pilihan tidak disentuh.
