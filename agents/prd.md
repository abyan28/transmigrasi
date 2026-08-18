# PRD — Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur

## 1. Ringkasan Produk
Produk ini adalah sistem informasi berbasis web untuk mendukung digitalisasi monitoring pertanian dan tata kelola data kawasan transmigrasi di Kobalima Timur, Kabupaten Malaka, Nusa Tenggara Timur. Sistem dibangun agar data transmigran petani, lahan, komoditas, hasil panen, infrastruktur, penghuni/transmigran, dan aktivitas pendampingan dapat tersimpan dalam satu platform, mudah dimonitor, serta dapat digunakan untuk pengambilan keputusan berbasis data.

Sistem dikembangkan menggunakan Laravel dan di-hosting agar dapat diakses oleh pemangku kepentingan sesuai hak akses.

## 2. Latar Belakang
Berdasarkan proposal, kawasan Kobalima Timur memiliki potensi agroekologis dan komoditas unggulan, terutama jagung. Namun pengembangan kawasan masih menghadapi keterbatasan air, irigasi, listrik, jalan produksi, telekomunikasi, serta belum optimalnya sarana pascapanen dan distribusi. Selain itu, data transmigran petani, lahan, hasil panen, harga komoditas, kondisi infrastruktur, dan aktivitas kelembagaan masih tersebar dan belum terintegrasi.

Karena itu diperlukan sistem digital yang mampu:
- mengintegrasikan data pertanian dan data kawasan;
- mempercepat monitoring dan pelaporan kondisi lapangan;
- membantu prioritas program dan perbaikan infrastruktur;
- memperkuat kelembagaan lokal dan kapasitas operator desa/SP.

## 3. Tujuan Produk
### Tujuan umum
Membangun sistem informasi digital berbasis web untuk memperkuat monitoring pertanian dan tata kelola data kawasan transmigrasi Kobalima Timur secara terintegrasi, transparan, dan berkelanjutan.

### Tujuan khusus
1. Menyediakan basis data terpusat untuk transmigran petani, lahan, komoditas, hasil panen, infrastruktur, dan data kawasan.
2. Menyediakan dashboard monitoring yang ringkas untuk pemantauan indikator utama kawasan.
3. Memfasilitasi input, validasi, pemutakhiran, dan ekspor data oleh pengguna yang berwenang.
4. Mendukung pelaporan kondisi lapangan dengan dokumentasi foto dan riwayat perubahan data.

## 4. Sasaran Pengguna
### Pengguna utama
- Petani / masyarakat transmigran
- Operator desa / SP
- Pendamping lapangan
- Pemerintah desa dan kecamatan
- Pemerintah daerah / dinas terkait
- Kementerian Transmigrasi
- Tim pelaksana / admin sistem

### Peran umum pengguna

**Role bersifat dinamis.** Admin dapat membuat dan mengatur role beserta hak aksesnya lewat antarmuka, tanpa mengubah struktur database. Empat role di bawah dibuat sistem sebagai konfigurasi awal dan dapat disesuaikan, kecuali role Admin yang terkunci.

| Role | Cakupan data | Kewenangan utama |
|---|---|---|
| **Admin** | Semua | Mengelola pengguna, role dan hak akses, data master wilayah/kawasan/SP, konfigurasi sistem, serta memantau audit log. Akses penuh ke seluruh fitur. |
| **Dinas Transmigrasi** | Semua | Memantau dashboard dan laporan kawasan, mengelola data transmigran, rumah, lahan, inventaris, dan fasilitas SP, serta menangani pengaduan bidang ketransmigrasian. |
| **Dinas Pertanian** | Semua | Memantau dashboard dan laporan pertanian, mengelola data poktan, komoditas, hasil panen, alsintan, dan saprotan, serta menangani pengaduan bidang pertanian. |
| **Operator SP** | Per SP | Memasukkan dan memperbarui data transmigran, rumah, lahan, dan hasil panen, **terbatas pada SP yang ditugaskan padanya**. Tanpa kewenangan menghapus. |

**Seluruh pengguna sistem adalah petugas.** Warga transmigran tidak memiliki akun. Data mereka dikelola petugas, sedangkan pengaduan diajukan lewat halaman publik tanpa login (§7.13). Pilihan ini diambil karena melatih ratusan warga memakai sistem tidak sebanding manfaatnya, sementara jaringan di lokus juga tidak selalu mendukung.

Hak akses ditentukan oleh dua hal terpisah: **kewenangan** menjawab boleh melakukan apa, sedangkan **cakupan data** menjawab boleh melihat data siapa. Rincian pada `rules.md` §5.

## 4a. Lokus Kegiatan
Sistem dipakai pada **Kawasan Transmigrasi Kobalima Timur**, Kabupaten Malaka, Nusa Tenggara Timur, yang menaungi 6 Satuan Permukiman tersebar di 4 kecamatan:

| Satuan Permukiman / Lokus | Desa | Kecamatan |
|---|---|---|
| SP Kapitan Meo | Kapitan Meo | Laen Manen |
| SP Tniumanu | Tniumanu | Laen Manen |
| SP Harekakae | Harekakae | Malaka Tengah |
| SP Weoe / Uluk Lubuk | Weoe | Wewiku |
| SP Tualaran | Naet | Rinhat |
| SP Weain | Weain | Rinhat |

Data master wilayah mengikuti hierarki **bercabang dua** yang berpisah di tingkat kabupaten:

```
provinsi → kabupaten ─┬─ kecamatan → desa ─────┐
                      │  (cabang administratif) │
                      └─ kawasan transmigrasi ──┴─→ satuan permukiman (SP)
                         (cabang program)
```

Setiap SP menaut ke satu desa sekaligus satu kawasan. Kecamatan dibaca lewat desanya, tidak disimpan terpisah. Struktur ini dipilih karena kawasan transmigrasi adalah wilayah perencanaan yang memotong batas administratif: Kobalima Timur menaungi SP di empat kecamatan berbeda.

Seluruh rekap dan filter dashboard dipecah lewat SP, sehingga dapat dikelompokkan per kawasan, kecamatan, maupun desa. Struktur wilayah dan kawasan dapat ditambah agar sistem dapat direplikasi ke kawasan transmigrasi lain.

## 5. Ruang Lingkup Produk
### Dalam lingkup
- Login dan manajemen pengguna
- Manajemen hak akses berbasis role
- Data master wilayah/lokus (kecamatan, desa, kawasan transmigrasi, SP)
- Data inventaris SP
- Data fasilitas SP
- Data petani/transmigran, keluarga, dan kelompok tani
- Data alsintan
- Data saprotan
- Data lahan
- Data komoditas
- Data hasil panen
- Data infrastruktur SP dan kawasan
- Data penghuni/transmigran kawasan (status tinggal, pindah, aktif/tidak aktif)
- Data rumah dan kondisi hunian
- Data kelompok tani (poktan) dan daftar anggota
- Pengaduan dan penanganannya
- Dashboard monitoring kawasan
- Filter, pencarian, dan rekap data
- Export laporan (Excel/PDF)
- Dokumentasi foto, geotagging sederhana, dan bukti lapangan
- Upload dokumen dalam bentuk gambar/pdf
- Audit log perubahan data
- SOP dan panduan penggunaan sistem

### Di luar lingkup untuk versi awal
- Aplikasi mobile native
- Integrasi otomatis dengan sensor IoT
- Prediksi berbasis machine learning
- Integrasi pembayaran atau transaksi keuangan
- Marketplace komoditas
- GIS tingkat lanjut yang kompleks

## 6. Masalah yang Diselesaikan
Sistem ini dirancang untuk menyelesaikan masalah utama berikut:
1. Data pertanian dan data kawasan masih tersebar di banyak dokumen manual.
2. Monitoring kondisi lahan, hasil panen, dan infrastruktur tidak cepat dan tidak seragam.
3. Pelaporan kondisi lapangan belum terstruktur dan sulit diverifikasi.
4. Operator lokal membutuhkan alat bantu yang sederhana dan mudah digunakan.
5. Pemerintah desa, kecamatan, dinas, dan kementerian memerlukan dashboard yang ringkas untuk pengambilan keputusan.

## 7. Kebutuhan Fungsional
### 7.1 Autentikasi dan hak akses
- Pengguna dapat login menggunakan akun yang diberikan.
- Sistem membatasi akses berdasarkan role beserta kewenangan dan cakupan datanya. Empat role bawaan: Admin, Dinas Transmigrasi, Dinas Pertanian, dan Operator SP.
- Admin dapat menambah, mengubah, menonaktifkan, dan mengatur hak akses pengguna.
- Akun bercakupan Per SP, seperti Operator SP, hanya dapat mengakses data pada SP yang ditugaskan padanya.
- **Role bersifat dinamis.** Admin dapat membuat role baru, memilih kewenangan per fitur, dan menetapkan cakupan datanya lewat antarmuka, tanpa perlu mengubah kode program.
- **Tidak ada pendaftaran mandiri.** Sistem tidak menyediakan halaman daftar akun; seluruh akun dibuat oleh Admin. Ini mencegah pihak tak berwenang membuat akun ke sistem data kependudukan.
- **Kredensial berupa email atau username.** Seluruh pengguna adalah petugas, sehingga tidak ada kredensial berbasis NIK.
- **Pemulihan kata sandi tersedia lewat dua jalur.** Petugas dapat meminta kode verifikasi enam digit ke surel dinasnya, atau menghubungi Admin untuk disetel ulang secara langsung. Jalur Admin dipertahankan karena jaringan di lokus tidak selalu memungkinkan penerimaan surel tepat waktu, sehingga sistem tidak pernah bergantung pada satu-satunya cara. Kode dikirim sebagai angka yang diketik, bukan tautan yang diklik, agar tetap dapat dipakai ketika surel hanya dapat dibuka di perangkat lain.
- Setiap tindakan Admin terhadap akun orang lain, termasuk menyetel ulang kata sandi, wajib tercatat pada audit log.

### 7.2 Data wilayah transmigrasi
- mengelola data wilayah administratif: provinsi, kabupaten, kecamatan, dan desa;
- menambah data **kawasan transmigrasi** di bawah kabupaten, beserta tahun penetapan, nomor SK, dan luas total;
- menambah data **SP (Satuan Permukiman)** di bawah kawasan, dengan menautkan SP ke desa tempatnya berdiri;
- menyimpan pada tiap SP: nama SP, kecamatan dan desa, titik koordinat, luas lahan, dan penanggung jawab data;
- menyimpan data inventaris yg dimiliki SP beserta status penyerahan dan perolehannya;
- menyimpan data fasilitas SP;
- Data master menjadi acuan untuk fitur lain;
- melakukan pencarian, filter, edit, dan hapus data sesuai kewenangan.

Informasi kecamatan pada SP dibaca otomatis dari desa yang dipilih, sehingga operator cukup memilih desa tanpa mengisi kecamatan dua kali.

### 7.3 Data transmigran
Sistem harus dapat:
- menambah data transmigran;
- menyimpan nama kepala keluarga, NIK, No. KK, jumlah anggota keluarga, pekerjaan kepala keluarga, pendapatan keluarga perbulan, dan status keanggotaan kelompok tani;
- mengunggah dokumen pendukung;
- menautkan transmigran dengan desa/SP, lahan, dan data rumah, dengan ketentuan satu transmigran dapat memiliki **beberapa lahan usaha** tetapi **tepat satu rumah**;
- melakukan pencarian, filter, edit, dan hapus data sesuai kewenangan.

### 7.4 Data lahan
Sistem harus dapat:
- mengunggah dokumen status lahan (HPL/SHM)
- data lahan terdiri dari lahan perkarangan dan lahan usaha
- mendata lahan per transmigran petani atau per unit pengelolaan;
- menyimpan luas, status, lokasi, dan jenis pemanfaatan lahan;
- menampung **lebih dari satu lahan usaha** untuk satu transmigran, sedangkan lahan pekarangan umumnya satu per KK;
- menjumlahkan luas seluruh lahan milik satu transmigran untuk kebutuhan rekap;
- menautkan lahan dengan komoditas yang ditanam.

### 7.5 Data komoditas
Sistem harus dapat:
- mencatat komoditas utama dan komoditas pendukung;
- menandai komoditas unggulan kawasan;
- menetapkan **satuan panen baku untuk setiap komoditas** (misalnya jagung dalam ton, cabai dalam kilogram);
- menghubungkan komoditas dengan transmigran petani, lahan, dan hasil panen.

### 7.6 Hasil panen
Sistem harus dapat:
- mencatat volume panen, periode/musim tanam, jenis komoditas, kualitas panen, harga jual, dan lokasi produksi;
- mencatat volume panen menggunakan **satuan baku milik komoditas** yang bersangkutan, dengan kolom catatan tambahan untuk satuan lokal (karung, ikat, dan sejenisnya);
- mengonversi seluruh volume panen ke satuan **ton** saat melakukan rekap dan agregasi lintas komoditas, memakai faktor konversi yang tersimpan pada data master satuan;
- menyimpan riwayat panen per transmigran petani, lahan, atau desa/SP;
- menampilkan rekap hasil panen per periode, per komoditas, dan per wilayah.

### 7.7 Infrastruktur SP
Fitur ini berisi **pendataan aset** infrastruktur satuan permukiman. Pelaporan masalah/kerusakan ditangani oleh fitur Pengaduan (§7.13).

Sistem harus dapat:
- mencatat infrastruktur SP seperti air, irigasi, listrik, jalan produksi, telekomunikasi, dan gudang;
- menyimpan nama infrastruktur, tahun perolehan, sumber dana, dan kondisi terkini;
- menyimpan foto/dokumentasi beserta geotagging sederhana (koordinat lokasi) sebagai dasar verifikasi;
- menautkan infrastruktur dengan desa/SP dan kelompok tani bila relevan;
- mengunggah dokumen pendukung.

### 7.7a Data penghuni kawasan
Sistem harus dapat:
- mencatat data penghuni/transmigran/petani kawasan beserta status tinggal, pindah, dan aktif/tidak aktif;
- menyimpan data kondisi rumah, foto rumah, koordinat lokasi, riwayat kepemilikan, dan catatan tambahan.
- memastikan **satu rumah hanya dihuni oleh satu KK** dan **satu KK hanya menempati satu rumah**; sistem menolak penautan ke rumah yang sudah dihuni dan hanya menampilkan rumah kosong pada pilihan;
- menautkan penghuni dengan desa/SP;
- menampilkan rekap kependudukan kawasan per desa/SP;
- membatasi tampilan data pribadi sesuai hak akses dan menyajikannya dalam bentuk agregat bagi pengguna terbatas.

### 7.8 Dashboard monitoring
Dashboard harus menampilkan ringkasan minimal:
- jumlah transmigran --> grafik jumlah transmigran tiap tahun
- jumlah KK --> grafik jumlah KK tiap tahun;
- jumlah petani --> grafik jumlah petani tiap tahun;
- jumlah pendapatan keluarga --> grafik jumlah pendapatan KK tiap tahun
- visualisasi KK masuk & keluar tiap tahun
- jumlah rumah yg terhuni
- visualisasi data pekerjaan kepala keluarga --> histogram
- luas lahan;
- komoditas utama (terbanyak);
- total volume panen tiap tahun, dinyatakan dalam ton hasil konversi lintas komoditas;
- harga rata-rata;
- status infrastruktur;
- isu prioritas per desa/SP (bersumber dari fitur Pengaduan: jumlah pengaduan per kategori dan per status penanganan);
- rekap data penghuni kawasan;
- status kondisi tiap SP (Mandiri, Berkembang, Perlu Penanganan), dihitung dari ketersediaan dan kondisi layanan dasar.

Dashboard harus dapat difilter berdasarkan wilayah (kawasan/kecamatan/desa/SP) dan periode. Nanti pada grafik atau visualisasi yg menampilkan rekap data dari semua SP, dapat diklik dan menampilkan data tiap SP.

### 7.9 Laporan dan export
Sistem harus:
- menyediakan filter data;
- menghasilkan laporan rekap;
- mendukung export Excel dan PDF untuk data utama.

Ekspor **menempel pada tabel data masing-masing**, bukan pada satu halaman laporan tersendiri (ditetapkan 2026-08-17, `rules.md` 12 poin 6). Alasannya kebutuhan "menyediakan filter data" di atas: halaman daftar sudah memiliki pencarian dan filter, sedangkan halaman laporan terpusat tidak pernah memilikinya. Filter yang sedang aktif ikut terbawa ke berkas hasil unduhan.

Rekap indikator kawasan untuk kementerian diekspor dari dashboard, sebab tidak memiliki tabel padanan di modul mana pun.

### 7.10 Data Alsintan (Alat dan Mesin Pertanian)
Sistem harus dapat:
- mencatat alsintan yg dimiliki oleh petani pribadi (contoh: traktor, sprayer, cultivator, dll).
- mencatat alsintan yg berasal dari bantuan pemerintah yg disalurkan melalui kelompok tani (poktan) beserta tahun perolehannya;
- upload dokumen pendukung;
- menautkan ke kelompok tani (poktan) dan individu transmigran petani.

### 7.11 Data Saprotan (Sarana Produksi Pertanian)
Sistem harus dapat:
- mencatat saprotan yg dibagikan ke kelompok tani dan individu transmigran petani (contoh: benih, pupuk, pestisida, mulsa, dll) beserta kapan perolehannya.
- upload dokumen pendukung;
- menautkan ke kelompok tani (poktan) dan individu transmigran petani.

### 7.12 Data Poktan (Kelompok Tani)
Sistem harus dapat:
- menambah profil kelompok tani (poktan).
- mencatat nama poktan;
- mencatat nama, NIK, telepon, dan email dari ketua poktan;
- mencatat jumlah anggota poktan dari transmigran petani;
- upload dokumen pendukung;
- menautkan ke individu transmigran petani, lahan, komoditas, alsintan, saprotan.

### 7.13 Data Pengaduan

**Pengaduan diajukan lewat halaman publik tanpa login.** Warga transmigran tidak memiliki akun sistem, sehingga kanal pengaduan dibuka bagi siapa pun. Warga cukup mengisi nama, kontak, lokasi SP, kategori, dan uraian masalah, lalu menerima nomor pengaduan untuk melacak perkembangannya.

Sistem harus dapat:
- menerima pengaduan dari warga lewat halaman publik tanpa login, maupun dari petugas yang mencatatkan laporan lisan;
- memberikan nomor pengaduan kepada pelapor dan menyediakan halaman lacak publik berdasarkan nomor tersebut;
- membatasi pengiriman 3 pengaduan per jam untuk setiap alamat IP guna mencegah penyalahgunaan;
- mencatat tanggal pengaduan, nama dan kontak pelapor, lokasi/SP, dan deskripsi pengaduan;
- mengelompokkan pengaduan berdasarkan kategori: lahan usaha, lahan pekarangan, rumah, infrastruktur, peralatan dan perlengkapan, alsintan, produksi panen, bencana, dan lainnya;
- mengunggah dokumen/foto pendukung pengaduan;
- mengelola alur status penanganan: **Menunggu Diterima → Diterima → Diproses → Selesai**;
- mencatat riwayat penanganan berisi petugas penangan, tanggal penanganan, catatan, dan dokumen tindak lanjut;
- memberi penanda prioritas agar dapat diurutkan sebagai isu prioritas kawasan;
- menampilkan rekap pengaduan per kategori, per status, dan per desa/SP.

## 8. Kebutuhan Nonfungsional
### 8.1 Aksesibilitas dan perangkat
- Aplikasi berbasis web responsif.
- Dapat digunakan di desktop dan ponsel (mobile freindly).
- Tampilannya sederhana, ringkas, dan mudah dipelajari operator lapangan.
- Kondisi sinyal di lokus tidak selalu stabil, sehingga sistem harus menyediakan template isian luring (Excel/PDF) yang dapat diunduh, diisi di lapangan, lalu diinput/diunggah kembali saat koneksi tersedia.

### 8.2 Keamanan
- Password harus di-hash.
- Akses dibatasi berdasarkan role.
- Input divalidasi di sisi server dan di sisi client/UI.
- Perubahan data penting tercatat dalam audit log.
- Sistem menggunakan HTTPS/SSL.
- Login dibatasi dari percobaan berulang (rate limiting).
- Data pribadi penghuni/transmigran petani hanya ditampilkan penuh kepada role berwenang; role terbatas menerima data agregat.
- Unggahan foto dibatasi ukurannya dan dikompresi agar hemat penyimpanan dan kuota.

### 8.3 Ketersediaan dan pemeliharaan
- Sistem harus dapat di-host secara daring.
- Harus ada mekanisme backup data.
- Harus ada log error dan pemantauan dasar.
- Sistem harus memungkinkan pengembangan bertahap.

### 8.4 Kinerja
- Dashboard dan halaman data utama harus memuat dengan cepat pada koneksi standar.
- Tabel harus mendukung filter agar mudah digunakan untuk data yang besar.

## 9. Data Utama yang Dikelola
Minimal entitas data yang dikelola sistem:
- pengguna
- role/hak akses
- wilayah administratif (provinsi, kabupaten, kecamatan, desa)
- kawasan transmigrasi
- satuan permukiman (SP)
- koordinat lokasi SP
- inventaris SP
- fasilitas SP
- petani/transmigran
- keluarga transmigran petani
- rumah dan kondisi hunian
- kelompok tani (poktan) dan daftar anggota
- lahan (lahan pekarangan dan lahan usaha)
- kategori lahan
- satuan dan faktor konversi
- alsintan
- saprotan
- komoditas
- musim tanam dan riwayat tanam
- hasil panen
- infrastruktur SP
- penghuni kawasan
- pengaduan dan status penanganan
- laporan/pelaporan
- dokumen/dokumentasi
- log aktivitas

Struktur entitas ini dituangkan dalam ERD dan **data dictionary** sebagai dokumen deliverable pada tahap desain.

## 10. Alur Penggunaan Tingkat Tinggi
1. Admin membuat akun dan menetapkan role.
2. Admin memasukkan data master wilayah/SP, inventaris, dan fasilitas.
3. Data awal transmigran, rumah, lahan, poktan, komoditas, panen, alsintan, saprotan, dan infrastruktur diinput.
4. Warga mengajukan pengaduan lewat halaman publik tanpa login bila ada kendala di lapangan, atau menyampaikannya lisan kepada operator SP untuk dicatatkan.
5. Dinas Transmigrasi dan Dinas Pertanian mengelola data serta menangani pengaduan sesuai bidangnya.
6. Dinas melihat dashboard dan laporan.
7. Data digunakan untuk monitoring, evaluasi, dan perencanaan tindak lanjut.
8. Sistem didokumentasikan dan diserahkan kepada pengelola lokal untuk keberlanjutan.

## 11. Indikator Keberhasilan Produk
Indikator mengikuti Tabel 7 proposal, dengan target kuantitatif dan kualitatif berikut:

| No | Aspek | Target kuantitatif | Target kualitatif |
|---|---|---|---|
| 1 | Pengembangan aplikasi web | Minimal 1 aplikasi web berhasil dikembangkan dan dapat diakses daring | Mudah digunakan, alur kerja jelas, sesuai kebutuhan lapangan |
| 2 | Fitur sistem | Minimal 5 fitur inti berjalan: petani/transmigran, lahan, komoditas, hasil panen, infrastruktur | Mendukung pendataan, pemantauan, dan pelaporan secara terstruktur |
| 3 | Dashboard monitoring | Minimal 1 dashboard aktif menampilkan indikator utama kawasan | Ringkas, mudah dibaca, membantu pengambilan keputusan |
| 4 | Database kawasan | Tersedia database transmigran petani, lahan, komoditas, hasil panen, infrastruktur, dan penghuni kawasan | Data tertib, terintegrasi, mudah dicari, dapat diperbarui berkala |
| 5 | Input dan validasi data awal | Data awal dari desa/SP prioritas berhasil diinput | Data menjadi baseline monitoring |
| 6 | Pencatatan hasil panen | Tersedia fitur pencatatan volume, jenis komoditas, harga, periode, dan lokasi | Riwayat panen mudah dipantau untuk melihat potensi produksi |
| 7 | Pendataan dan pengaduan infrastruktur | Tersedia pendataan aset infrastruktur serta fitur pengaduan multi-kategori dengan alur status penanganan | Masalah terdokumentasi, dapat dilacak, dan dapat diprioritaskan |
| 8 | Hak akses pengguna | Role dapat dibuat dan diatur Admin; minimal tersedia 4 role bawaan: Admin, Dinas Transmigrasi, Dinas Pertanian, Operator SP | Akses tertib, aman, sesuai kewenangan, dan dapat disesuaikan tanpa mengubah kode |
| 9 | Pelatihan pengguna | Minimal 1 pelatihan operator/pemangku kepentingan dan 1 pelatihan masyarakat/pengguna | Pengguna memahami login, input data, dashboard, dan pembuatan laporan |
| 10 | SOP dan dokumentasi | Minimal 1 SOP penggunaan, 1 buku panduan, dan dokumentasi teknis sistem | Sistem tetap dapat digunakan meskipun operator berganti |
| 11 | Export laporan | Minimal tersedia fitur export Excel/PDF untuk data utama | Laporan siap dipakai desa, dinas, pendamping, dan Kementerian |
| 12 | Monitoring dan evaluasi | Minimal 1 laporan evaluasi implementasi sistem | Tersedia masukan pengguna, catatan kendala, dan rekomendasi lanjutan |

## 12. Prinsip Desain Produk
- Sederhana dan mudah dipakai operator lapangan
- Fokus pada kebutuhan data yang benar-benar dipakai
- Terintegrasi, bukan terpecah dalam banyak file manual
- Mendukung penelusuran perubahan data lewat audit log
- Siap dipakai bertahap dan dapat dikembangkan lanjutan

## 13. Catatan Implementasi Awal
- Teknologi utama: Laravel 12 (pola MVC) di atas PHP 8.2 + database relasional MySQL/MariaDB
- Fondasi antarmuka memakai template TailAdmin Laravel berlisensi MIT
- Aplikasi web responsif dan mobile friendly
- Deployment melalui hosting/server daring dengan SSL, backup terjadwal, dan penyimpanan foto/dokumen
- Fokus awal pada fitur inti dan dashboard monitoring
- Pelatihan dan pendampingan menjadi bagian penting dari implementasi
- Skema awal dapat memakai shared hosting premium atau cloud VPS ringan; ditingkatkan ke VPS/cloud dengan queue, object storage, dan caching bila beban data bertambah
- Versi PHP pada hosting target wajib dikonfirmasi sebelum tahap deployment, minimal 8.2

### Periode pelaksanaan
Kegiatan berjalan Agustus minggu III sampai Desember minggu I, dibagi menjadi empat fase:

| Fase | Periode | Fokus |
|---|---|---|
| Fase 1 — Persiapan dan Analisis | Agustus III – September II | Koordinasi, telaah dokumen, survei, analisis kebutuhan pengguna |
| Fase 2 — Desain dan Pengembangan Sistem | September III – Oktober IV | Desain database/UI dan pengembangan fitur inti |
| Fase 3 — Uji Coba, Perbaikan, dan Deployment | Oktober V – November III | FGD progres, dashboard, testing internal, deployment |
| Fase 4 — Pendampingan dan Evaluasi | November IV – Desember I | Beta testing, pelatihan, evaluasi akhir, serah terima |

Rincian langkah tiap fase dijabarkan pada `workflow.md`.

## 14. Referensi Ruang Lingkup dari Proposal
Dokumen ini diturunkan dari bagian proposal yang menjelaskan tujuan, ruang lingkup, deskripsi output, metodologi, indikator keberhasilan, serta integrasi program. Fokus utama proposal adalah aplikasi web untuk pendataan transmigran petani, lahan, komoditas, hasil panen, kondisi infrastruktur, data penghuni kawasan, dan dashboard monitoring kawasan transmigrasi.

## 15. Pedoman Penulisan Coding
- Berikan komentar dengan bahasa Indonesia untuk setiap fungsi kodingan yg dibuat, sehingga memudahkan programmer untuk memahami kodingannya.
- Struktur basis data mengikuti `erd.md` dan `data-dictionary.md`, dengan konvensi PK `id_transmigran` dan FK `transmigran_id` (`rules.md` §4.0).
- Seluruh teks yang tampil di antarmuka mengikuti aturan `ANTISLOP-ID.md`, termasuk larangan em dash dan buzzword (`rules.md` §13.0).

## 16. Ketentuan File & Upload
1. **Ukuran Maksimum**: Batas ukuran setiap file dokumen yang diunggah adalah **5 MB**.
2. **Penyimpanan**: File disimpan di folder `storage/app/private/[transmigran]/[id-transmigran]/`.
3. **Format Penamaan File**:
   * Dokumen: `[Nama Dokumen berdasarkan tabel pada database]_[nama-transmigran].[ekstensi]`\
   *(Spasi pada nama transmigran diganti dengan tanda hubung `-`)*

## 17. Pedoman Pengerjaan AI (Tasklist Rules)
Setiap kali selesai mengerjakan satu tugas/fitur, AI wajib memperbarui file `agents/tasklist.md` sebelum melaporkan hasil pengerjaan kepada user dengan ketentuan:
1. Tandai task yang selesai dengan centang `[✓]`.
2. Tambahkan emoji ✅ di depan task.
3. Update progress keseluruhan proyek (misal: `Progress: 35%`).
4. Tambahkan catatan singkat di bawah task mengenai file apa saja yang dibuat/diubah.
   *Contoh:*
   ```markdown
   - [✓] ✅ Task 2.3 - Membuat Room Migration `[Mudah]` (Selesai)
     * Membuat file `database/migrations/xxxx_create_students_table.php`
   ```
5. update tasklist.md setiap selesai 1 task.
6. kasih summary jelas di akhir setiap task.
7. kalau mulai limit, berhenti di check point yg rapi
8. jadi nanti next agent tinggal baca task list dan tahu tepat mana yang dilanjut.

## 18. Konvensi UI/UX (WAJIB diterapkan otomatis di fitur baru & perubahan)

Pola berikut adalah standar yang **harus dibangun dan dipatuhi** sejak awal proyek. Berkas dan helper yang disebut belum ada dan menjadi bagian pekerjaan Tahap 1; nama berkas dipertahankan sebagai target. Rincian teknis lengkap ada di `rules.md` §13.2 dan `ui-spec.md`.

**Arah desain dan filter kualitas.** Arah desain ditetapkan pada `ui-spec.md` §2 dengan dial **ENERGI 1 / RITME 2 / GERAK 1**, motif identitas dari logo Kementerian, dan gold sebagai aksen tunggal. Seluruh hasil antarmuka disaring memakai `ANTISLOP-ID.md`, dengan Delivery Gate wajib dijalankan pada akhir setiap gelombang pengerjaan (`rules.md` §16.1). Aplikasi menyediakan mode terang dan gelap yang keduanya wajib berfungsi penuh.

1. **Tab-halaman persisten setelah reload** — halaman dengan tab (bukan sidebar utama kiri) yang punya
   aksi submit (`back()` reload) WAJIB menyimpan tab aktif di URL **query string** (`?tab=`) agar tak balik
   ke tab default. (JANGAN pakai hash `#tab` — fragment hilang saat form POST & tak masuk Referer, sia-sia.
   Query string ikut Referer → controller `back()` redirect balik dgn query → tab tetap.)
   - 1 level tab: bangun helper global `hashTabs('defaultTab')` di `resources/views/layouts/dashboard.blade.php`. Tombol pakai `setTab('x')`.
   - Multi-level (tab + sub-tab): `?tab=&sub=` lewat fungsi `syncUrl()`.
   - `init()` tulis query saat load agar submit modal (tanpa klik tab dulu) tetap bawa posisi via Referer.
   - Syarat: controller pakai `return back()` (Referer-based). TIDAK perlu untuk switch tanpa submit-reload, modal, dropdown.

2. **Sub-tab (slidebar dalam halaman) bila konten menumpuk** — jika satu halaman menampung banyak card/section
   sehingga scroll panjang, pecah jadi sub-tab (hanya 1 section tampil via `x-show`).

3. **Form leluasa = modal floating** — form isian panjang/sempit pakai modal floating,
   bukan form inline sempit. Tombol pemicu (Tambah/Ubah) di header card, tak overflow.

4. **Input teks user = HURUF KAPITAL** otomatis via middleware `UppercaseInput` di `app/Http/Middleware/UppercaseInput.php` (kecualikan kredensial/enum/prose/`*_id`).

5. **Tanggal tampil = Bahasa Indonesia** (`translatedFormat`), uang = `Rp x.xxx.xxx` (`number_format(...,0,',','.')`).

6. **Validasi terpusat & DRY** — aturan nama/no_hp/NIK dst di `app/Support/ValidationRules.php`; Jangan tulis ulang regex/rule per form.

7. **Eager loading** — query yg dipakai di loop view WAJIB `with([...])` (cegah N+1).

8. **Verifikasi sebelum selesai** — `php artisan test` + `npm run build` + `php artisan view:cache` hijau; smoke browser untuk perubahan UI pada lebar 360px dan 1280px.

## 19. Stack Frontend dan Identitas Visual

| Aspek | Keputusan |
|---|---|
| PHP | 8.2.12 (bawaan XAMPP) |
| Framework | Laravel 12.x |
| Template engine | Blade |
| Interaktivitas | Alpine.js 3 |
| Styling | Tailwind CSS **v4** — token di `resources/css/app.css` (`@theme`), bukan `tailwind.config.js` |
| Grafik | ApexCharts 5 (mendukung drill-down klik) |
| Pemilih tanggal | Flatpickr |
| Build tool | Vite 7 |
| Fondasi komponen | **TailAdmin Laravel** (MIT) — https://github.com/TailAdmin/tailadmin-laravel |
| Font | Outfit (bawaan TailAdmin) |
| Basis data | MySQL/MariaDB (XAMPP), nama `sim_transmigrasi` |
| Acuan visual | Template Figma pilihan tim (tata letak) |

**Strategi pengerjaan:** repositori TailAdmin di-clone sebagai titik awal lalu dibersihkan dari halaman contoh, seluruh halaman dibangun sebagai Blade dengan **data dummy** dalam **dua gelombang** — alur inti divalidasi lebih dulu, sisanya menyusul. Backend menyusul setelahnya dan sumber data ditukar tanpa mengubah tampilan.

**Identitas visual:** palet warna diambil dari logo resmi Kementerian Transmigrasi — navy `#163B54`, teal `#33809C`, sand `#DFB87E`, dan gold `#C09546`. Warna bawaan TailAdmin (`--color-brand-*`, biru `#465fff`) ditimpa seluruhnya dengan skala navy. Skala warna lengkap, aturan kontras WCAG, tipografi, komponen bersama, inventaris halaman, struktur menu per role, serta spesifikasi dashboard dijabarkan pada `ui-spec.md`.

**Struktur basis data:** skema final beserta relasi ada pada `erd.md`, rincian kolom dan aturan validasi pada `data-dictionary.md`. Konvensi kunci memakai PK `id_transmigran` dan FK `transmigran_id`.

**Zona waktu dan locale:** aplikasi memakai WITA (`Asia/Makassar`) dan locale `id`, mengikuti lokasi Kabupaten Malaka, Nusa Tenggara Timur.
