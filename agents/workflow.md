# workflow.md
## Alur Kerja Sistem Informasi Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur

Dokumen ini menjelaskan alur kerja pengembangan dan penggunaan sistem agar konsisten dengan `prd.md`, `rules.md`, `ui-spec.md`, dan pembagian fase pada proposal.

## 1. Gambaran Umum Workflow
Workflow sistem disusun untuk mendukung tujuan proposal: mengintegrasikan data transmigran, rumah, lahan, kelompok tani, komoditas, hasil panen, alsintan, saprotan, infrastruktur, inventaris/fasilitas SP, dan pengaduan kawasan transmigrasi dalam satu platform web, lalu memanfaatkan data tersebut untuk monitoring, evaluasi, pelaporan, dan pengambilan keputusan berbasis bukti. Sistem dibangun berbasis Laravel, di-hosting daring, serta digunakan sesuai hak akses.

Workflow dibagi menjadi dua bagian besar:
1. **Workflow pengembangan proyek** — dari analisis kebutuhan sampai serah terima.
2. **Workflow operasional sistem** — cara data masuk, divalidasi, ditampilkan di dashboard, dan diekspor menjadi laporan.

Lokus kegiatan mencakup 6 desa pada 4 kecamatan: Kapitan Meo dan Tniumanu (Laen Manen), Harekakae (Malaka Tengah), Weoe/Uluk Lubuk (Wewiku), Naet/SP Tualaran dan Weain (Rinhat).

## 2. Workflow Pengembangan Proyek
Pengembangan menggunakan pendekatan **incremental-prototyping**: membangun fitur inti terlebih dahulu, lalu diuji dan disempurnakan berdasarkan umpan balik pengguna. Pelaksanaan dibagi menjadi empat fase mengikuti Tabel 5 proposal, dengan periode **Agustus minggu III sampai Desember minggu I**.

### Ringkasan fase

| Fase | Periode | Fokus utama | Keluaran |
|---|---|---|---|
| Fase 1 — Persiapan dan Analisis | Agustus III – September II (bulan 1) | Koordinasi awal, pembentukan tim, telaah dokumen, identifikasi stakeholder, penyusunan instrumen kebutuhan, validasi kebutuhan awal | Dokumen kebutuhan pengguna, daftar modul prioritas, rencana kerja teknis |
| Fase 2 — Desain dan Pengembangan Sistem | September III – Oktober IV (bulan 1–3) | Desain database dan UI, pembangunan seluruh antarmuka dengan data dummy, lalu backend modul data master/SP, transmigran, rumah, lahan, poktan, alsintan, saprotan, komoditas, panen, infrastruktur, pengaduan, dan dashboard | Prototype aplikasi dan dokumen desain sistem |
| Fase 3 — Uji Coba, Perbaikan, dan Deployment | Oktober V – November III (bulan 4) | FGD progres, refinement dashboard, uji fungsi, perbaikan bug, deployment hosting, konfigurasi domain/SSL, pengaturan akun, simulasi input data | Aplikasi online versi operasional awal |
| Fase 4 — Pendampingan dan Evaluasi | November IV – Desember I (bulan 4) | Beta testing, pelatihan pengguna, pendampingan input data, monitoring penggunaan, evaluasi indikator, penyusunan laporan akhir, serah terima | SOP, laporan evaluasi, rekomendasi pengembangan lanjutan |

### 2.1 Fase 1 — Persiapan dan Analisis (Agustus III – September II)
Langkah kerja:
1. Koordinasi internal tim, konfirmasi lokus, dan penyusunan daftar stakeholder.
2. Studi dokumen: laporan akhir TEP tahun sebelumnya, data kawasan, dan data komoditas unggulan.
3. Diskusi teknis dengan perangkat desa/SP, pendamping, dinas terkait, BUMDes/KopDes, dan perwakilan petani.
4. Survei awal kebutuhan bidang pertanian: petani, komoditas, lahan, hasil panen, harga, kendala produksi, kebutuhan sarana produksi.
5. Survei tata kelola data transmigrasi: data penghuni, status tinggal/pindah, kelembagaan lokal, infrastruktur kawasan, alur pelaporan berjalan.
6. Analisis kebutuhan pengguna per aktor dan penyusunan prioritas fitur.

Output fase ini:
- daftar isu utama dan peta aktor,
- kebutuhan data awal dan dataset awal pertanian,
- dokumen kebutuhan pengguna (user requirement),
- kebutuhan fungsional dan nonfungsional,
- daftar modul prioritas dan rencana kerja teknis.

Risiko dan mitigasi: stakeholder belum lengkap → validasi ulang melalui pemerintah desa/kecamatan; data awal belum lengkap → gunakan format isian sederhana dan validasi bertahap; data kependudukan sensitif → gunakan hak akses dan agregasi data.

### 2.2 Fase 2 — Desain dan Pengembangan Sistem (September III – Oktober IV)
Langkah kerja:
Pengerjaan fase ini memakai pendekatan **frontend lebih dahulu**: seluruh antarmuka dibangun memakai data dummy agar dapat divalidasi bersama pengguna sedini mungkin, baru kemudian disambungkan ke backend. Dengan cara ini, revisi tampilan yang muncul saat FGD tidak memaksa pembongkaran logika basis data.

**Langkah A — Desain dan fondasi**
1. Desain proses bisnis dan database: alur pendataan wilayah/SP, transmigran, rumah, lahan, poktan, komoditas, panen, alsintan, saprotan, infrastruktur, pengaduan, validasi, dan laporan; penyusunan **ERD** (`erd.md`) dan **data dictionary** (`data-dictionary.md`). **Selesai 2026-08-11.**
2. Desain UI/UX dan mockup: login, dashboard, form input, tabel data, halaman detail, filter, dan laporan; form dibuat bertahap agar sederhana untuk operator lapangan.
3. Setup proyek: clone TailAdmin Laravel, pembersihan halaman contoh, konfigurasi Tailwind v4, Alpine, ApexCharts, Vite, struktur folder, layout dasar, design token, dan komponen bersama sesuai `ui-spec.md`.

**Langkah B — Pembangunan antarmuka dengan data dummy**

Dikerjakan dalam **dua gelombang** agar revisi hasil validasi tidak membongkar seluruh halaman sekaligus.

*Gelombang 1 — alur inti (±12 halaman):*
4. Membangun PHP Enum, penyedia data dummy, dan komponen bersama.
5. Membangun halaman autentikasi, dashboard beserta 16 visualisasi, drill-down per SP, transmigran, rumah, lahan, hasil panen, pengaduan, serta halaman 403 dan 404.
6. Melakukan validasi bersama tim dan dinas atas pola tata letak, alur form, dan penamaan field sebelum melanjutkan.

*Gelombang 2 — halaman sisanya (±31 halaman):*
7. Membangun halaman data master wilayah/SP/inventaris/fasilitas/satuan, rekap kependudukan, poktan dan anggota, alsintan, saprotan, komoditas, musim tanam, riwayat tanam, infrastruktur, laporan, template luring, pengguna, dan audit log.
8. Menyusun struktur menu untuk kelima role beserta status halaman kosong, memuat, galat, dan tanpa izin.
9. Memastikan tampilan berjalan baik pada ponsel dan desktop, diuji pada lebar 360px dan 1280px.

**Langkah C — Pembangunan backend dan penyambungan**
10. Migration dan model seluruh tabel mengikuti urutan pada `erd.md` §10, beserta seeder data wilayah dan satuan.
11. Autentikasi, hak akses dinamis (tabel `role`, `permission`, `role_permission`), seeder 4 role bawaan, dan pembatasan akses pada level query menurut izin serta cakupan data.
12. Implementasi modul data master kawasan: wilayah administratif provinsi-kabupaten-kecamatan-desa, kawasan transmigrasi, dan SP beserta koordinat dan batas SP, inventaris SP, fasilitas SP, serta data master satuan beserta faktor konversi ke ton.
13. Implementasi modul kependudukan kawasan: transmigran dan keluarga, rumah dan kondisi hunian, riwayat penghunian, status tinggal/pindah/aktif, serta rekap kependudukan.
14. Implementasi modul lahan: lahan pekarangan dan lahan usaha, kategori lahan, dokumen HPL/SHM, dan koordinat lahan.
15. Implementasi modul kelembagaan dan sarana pertanian: profil poktan, daftar anggota, alsintan, dan saprotan.
16. Implementasi modul produksi pertanian: komoditas, musim tanam, riwayat tanam, hasil panen, volume produksi, harga, dan kualitas panen.
17. Implementasi modul infrastruktur SP sebagai pendataan aset beserta dokumentasi dan koordinat.
18. Implementasi modul pengaduan: kategori pengaduan, alur status penanganan, riwayat penanganan, dan dokumen pendukung.
19. Penggantian data dummy dashboard dengan data nyata beserta filter wilayah dan periode.

Output fase ini:
- ERD (`erd.md`), rancangan tabel, dan data dictionary (`data-dictionary.md`),
- mockup halaman utama dan rancangan navigasi,
- seluruh halaman antarmuka berjalan dengan data dummy,
- catatan hasil validasi gelombang 1 bersama tim dan dinas,
- prototype aplikasi dengan modul inti tersambung ke basis data,
- dokumen desain sistem.

Risiko dan mitigasi: relasi data berubah → gunakan desain modular dan field fleksibel; UI terlalu kompleks → form bertahap dan label sederhana; variasi satuan panen antar komoditas → tetapkan satuan baku per komoditas beserta faktor konversi ke ton, dan sediakan kolom keterangan untuk satuan lokal; unggahan foto besar → kompresi file dan batas ukuran unggahan.

### 2.3 Fase 3 — Uji Coba, Perbaikan, dan Deployment (Oktober V – November III)
Langkah kerja:
1. FGD progres dan validasi prototype bersama pengguna: mendemonstrasikan prototype, mengumpulkan masukan, memvalidasi field data, alur input, dashboard awal, dan kebutuhan laporan.
2. Refinement modul berdasarkan feedback dan implementasi dashboard indikator lengkap dengan filter wilayah dan periode.
3. Alpha testing internal: login, role, CRUD, validasi input, filter, upload, export, dashboard, audit log, dan keamanan akses.
4. Perbaikan bug dengan prioritas blocker terlebih dahulu.
5. Deployment ke hosting: konfigurasi domain/subdomain, SSL/HTTPS, database, storage, backup, akun awal, dan monitoring error log.
6. Simulasi input data awal oleh operator desa/SP dan pendamping.

Output fase ini:
- catatan feedback, daftar revisi, dan berita acara validasi,
- dashboard monitoring pertanian dan kawasan,
- dokumen alpha testing dan daftar bug,
- aplikasi online versi operasional awal,
- dokumentasi deployment.

Risiko dan mitigasi: banyak revisi baru → pisahkan revisi wajib dan pengembangan lanjutan; dashboard lambat → query terindeks, paginasi, dan agregasi; hosting tidak stabil → backup dan opsi VPS/cloud ringan.

### 2.4 Fase 4 — Pendampingan dan Evaluasi (November IV – Desember I)
Langkah kerja:
1. Beta testing bersama dinas, perangkat desa/SP, pendamping, dan operator.
2. Pelatihan operator dan pemangku kepentingan: login, input data petani dan lahan, pencatatan hasil panen, pelaporan infrastruktur, penggunaan dashboard, export laporan, dan prosedur backup sederhana.
3. Pelatihan masyarakat/petani dan simulasi input sederhana.
4. Pendampingan input data awal dan monitoring penggunaan sistem.
5. Finalisasi SOP, buku panduan, video panduan, dan dokumentasi teknis.
6. FGD akhir, evaluasi capaian indikator, penyusunan laporan akhir, dan serah terima akun, dokumentasi, database awal, SOP, serta rekomendasi pengembangan lanjutan.

Output fase ini:
- pengguna terlatih dan dokumentasi pelatihan,
- SOP penggunaan, buku panduan, dan video panduan,
- laporan evaluasi implementasi,
- berita acara serah terima (BAST),
- rekomendasi pengembangan lanjutan.

Risiko dan mitigasi: kemampuan digital bervariasi → SOP bergambar dan praktik langsung; adopsi rendah → libatkan operator lokal sebagai pendamping awal; tindak lanjut belum jelas → tetapkan PIC lokal dan rencana monitoring pascaprogram.

## 3. Workflow Operasional Sistem
Workflow operasional adalah alur saat data dikumpulkan, divalidasi, ditampilkan, dan dipakai untuk keputusan. Sistem inti mencakup data master wilayah/SP, inventaris dan fasilitas SP, transmigran, rumah, lahan, poktan, alsintan, saprotan, komoditas, hasil panen, infrastruktur SP, penghuni kawasan, pengaduan, dashboard monitoring, export laporan, dan pengaturan hak akses.

### 3.1 Login dan Akses
1. Pengguna masuk memakai akun yang diberikan Admin. Sistem tidak menyediakan pendaftaran mandiri.
2. Kredensial diisi pada satu kolom yang sama, berupa **email atau username**. Seluruh pengguna sistem adalah petugas.
3. Sistem menolak akun yang berstatus tidak aktif (`is_aktif = FALSE`).
4. Bila `password_harus_diganti` bernilai `TRUE`, pengguna langsung diarahkan ke halaman ganti kata sandi dan belum dapat membuka halaman lain.
5. Sistem membaca **role** pengguna beserta daftar izin dan cakupan datanya.
6. Menu sidebar dirender hanya untuk modul yang izin `lihat`-nya dimiliki. Menu yang tidak berhak tidak dirender sama sekali.
7. Data yang tampil disaring menurut cakupan role: `Semua` tanpa penyaring, `Per SP` dibatasi SP yang ditugaskan.
8. Pembatasan wajib diberlakukan pada level query dan controller, bukan sekadar menyembunyikan menu.
9. Data pribadi transmigran dan penghuni hanya tampil penuh untuk role berwenang; role lain menerima tampilan agregat.

### 3.1a Lupa Kata Sandi

Tersedia dua jalur. Keduanya berakhir sama: kata sandi sementara yang wajib diganti saat masuk.

**Jalur A, kode verifikasi lewat surel dinas.** Dipakai bila petugas punya surel aktif dan jaringan memadai.

1. Pengguna membuka halaman lupa kata sandi dari tautan pada halaman masuk, lalu memasukkan email atau username.
2. Sistem mengirim **kode enam digit** ke surel dinas terdaftar. Halaman berikutnya menampilkan pesan yang sama persis, baik akun ditemukan maupun tidak, agar halaman ini tidak dapat dipakai memeriksa siapa yang memiliki akun.
3. Pengguna mengetik kode tersebut beserta kata sandi barunya. Kode berlaku 15 menit, sekali pakai, dan hangus setelah 5 kali salah.
4. Bila kode telanjur kedaluwarsa, halaman menawarkan permintaan kode baru sekaligus mengingatkan jalur Admin.

**Jalur B, setel ulang oleh Admin.** Selalu tersedia, dan menjadi satu-satunya jalur di lokus bersinyal lemah.

5. Pengguna menghubungi Admin desa atau SP.
6. Admin membuka Manajemen Pengguna, mencari akun bersangkutan, lalu memilih tindakan setel ulang kata sandi.
7. Sistem menyimpan kata sandi sementara dan menandai `password_harus_diganti = TRUE`.
8. Admin menyerahkan kata sandi sementara kepada pengguna **secara langsung**, bukan lewat surel maupun pesan singkat.

**Keduanya:**

9. Pengguna masuk memakai kata sandi sementara, lalu wajib menggantinya sebelum dapat memakai sistem.
10. Seluruh langkah tercatat pada audit log dengan aksi `Reset Kata Sandi`, beserta jalur yang dipakai. Pemulihan lewat jalur A tercatat atas nama pemilik akun itu sendiri.

### 3.2 Input Data Awal
1. Admin menyiapkan data master wilayah dan SP beserta koordinat, batas wilayah, inventaris, dan fasilitas, serta data master satuan beserta faktor konversinya.
2. Data transmigran dan keluarga diinput, lalu ditautkan ke satu rumah kosong dan ke lahan miliknya.
3. Data lahan pekarangan dan lahan usaha diisi beserta dokumen status lahan (HPL/SHM).
4. Profil poktan, daftar anggota, alsintan, dan saprotan dicatat.
5. Data komoditas, musim tanam, hasil panen, dan infrastruktur SP dilengkapi.
6. Data awal dari desa/SP prioritas masuk ke sistem sebagai baseline monitoring.
7. Dokumentasi lapangan berupa foto dan koordinat lokasi dilampirkan bila tersedia, mengikuti batas ukuran dan aturan penamaan file.

### 3.3 Jalur Input Luring
Untuk lokasi dengan sinyal lemah:
1. Operator mengunduh template isian luring dari sistem.
2. Data diisi di lapangan tanpa koneksi.
3. Saat koneksi tersedia, data diinput atau diunggah kembali ke sistem.
4. Data hasil unggahan tetap melewati proses validasi yang sama.

### 3.4 Validasi Data
1. Data yang baru masuk diperiksa kelengkapannya oleh sistem.
2. Dinas terkait atau admin memeriksa kelengkapan data sesuai bidangnya.
3. Data yang belum lengkap dikembalikan untuk perbaikan.
4. Data yang lolos validasi dipakai sebagai data resmi sistem.
5. Setiap perubahan data penting tercatat dalam audit log.

### 3.5 Monitoring dan Dashboard
1. Data yang sudah valid ditampilkan pada dashboard.
2. Dashboard menampilkan indikator utama kawasan: jumlah transmigran, jumlah KK, jumlah petani, pendapatan keluarga, KK masuk dan keluar, rumah terhuni, pekerjaan kepala keluarga, luas lahan, komoditas utama, volume panen dalam ton, harga rata-rata, status infrastruktur, isu prioritas dari pengaduan, dan rekap penghuni.
3. Indikator yang bersifat tahunan ditampilkan sebagai grafik per tahun; pekerjaan kepala keluarga ditampilkan sebagai histogram.
4. Pengguna dapat memfilter data per kawasan, kecamatan, desa/SP, periode, atau jenis data.
5. Grafik yang menampilkan rekap gabungan seluruh SP dapat diklik untuk **drill-down** ke rincian per SP.
6. Dashboard digunakan untuk melihat kondisi kependudukan, pertanian, hasil panen, infrastruktur, dan isu prioritas kawasan.

### 3.6 Export Laporan
1. Pengguna memilih data atau periode yang ingin direkap.
2. Sistem membentuk rekap data.
3. Hasil rekap dapat diekspor ke Excel atau PDF.
4. Laporan digunakan untuk desa, dinas, pendamping, dan Kementerian Transmigrasi.

## 4. Workflow Per Modul
### 4.1 Modul Wilayah dan Satuan Permukiman (SP)
1. Admin membuat data wilayah administratif mengikuti hierarki provinsi → kabupaten → kecamatan → desa.
2. Admin mendaftarkan **kawasan transmigrasi** di bawah kabupaten, beserta tahun penetapan, nomor SK, dan luas total.
3. Admin mendaftarkan **SP** di bawah kawasan tersebut, sekaligus memilih desa tempat SP berdiri. Kecamatan terisi otomatis mengikuti desa yang dipilih, tidak diinput ulang.
4. Isi titik koordinat SP beserta batas wilayah Utara, Timur, Selatan, dan Barat.
5. Isi luas lahan SP, tetapkan penanggung jawab data, dan unggah dokumen pendukung.
6. Data SP menjadi acuan seluruh modul lain dan dasar filter dashboard. Rekap dapat dipecah per kawasan, kecamatan, desa, maupun SP, seluruhnya dihitung lewat SP.

### 4.2 Modul Inventaris dan Fasilitas SP
1. Pilih SP yang akan didata.
2. Input nama barang atau fasilitas, tahun perolehan, dan sumber dana.
3. Catat status penyerahan dan lampirkan dokumen pendukung.
4. Tampilkan rekap aset per SP pada laporan kawasan.

### 4.3 Modul Transmigran
1. Input data transmigran: nama kepala keluarga, NIK, nomor KK, jumlah anggota keluarga, pekerjaan, pendapatan per bulan, dan status keanggotaan poktan.
2. Validasi format NIK dan nomor KK.
3. Unggah dokumen pendukung.
4. Hubungkan dengan desa/SP, rumah, lahan, komoditas, dan panen.
5. Tampilkan pada rekap dan dashboard.

### 4.4 Modul Rumah dan Hunian
1. Input data rumah beserta titik koordinat lokasi.
2. Catat kondisi rumah (Tidak Rusak, Rusak Ringan, Rusak Berat) dan status hunian (Dihuni, Tidak Dihuni).
3. Saat menautkan KK ke rumah, sistem hanya menampilkan rumah yang masih kosong; rumah yang sudah dihuni tidak muncul pada pilihan.
4. Sistem menolak penautan bila rumah sudah dihuni atau bila KK tersebut sudah menempati rumah lain, karena relasinya satu-ke-satu.
5. Bila tidak dihuni, isi alasan dan catatan hunian.
6. Saat terjadi pergantian penghuni, catat tanggal keluar penghuni lama pada riwayat penghunian, lalu tautkan penghuni baru.
7. Lampirkan foto rumah dan simpan riwayat kepemilikan/penghunian.
8. Rekap jumlah rumah terhuni ditampilkan pada dashboard.

### 4.5 Modul Lahan
1. Input identitas lahan: jenis (pekarangan atau usaha), luas, lokasi, koordinat, status, dan tujuan pemanfaatan.
2. Unggah dokumen status lahan (HPL/SHM).
3. Untuk lahan usaha, catat kategori lahan (basah/kering), pola tanam, musim tanam, peralatan, dan kendala.
4. Hubungkan lahan dengan transmigran, poktan, dan komoditas. Satu transmigran dapat mendaftarkan **beberapa lahan usaha**, sehingga penambahan lahan baru tidak menimpa lahan yang sudah ada.
5. Rekap luas lahan dihitung dengan menjumlahkan seluruh lahan milik transmigran atau wilayah terkait.
6. Gunakan data lahan untuk analisis produksi dan perencanaan.

### 4.6 Modul Kelompok Tani (Poktan)
1. Buat profil poktan: nama poktan dan desa/SP asal.
2. Isi data ketua poktan: nama, NIK, telepon, dan email.
3. Tambahkan daftar anggota beserta tanggal masuk dan status keaktifan.
4. Tautkan poktan ke lahan, komoditas, alsintan, dan saprotan.
5. Unggah dokumen pendukung dan tampilkan rekap per desa/SP.

### 4.7 Modul Alsintan
1. Pilih jenis kepemilikan: milik pribadi transmigran atau bantuan pemerintah melalui poktan.
2. Input nama alat, jumlah, tahun perolehan, sumber perolehan, dan kondisi.
3. Tautkan ke poktan penerima atau transmigran pemilik.
4. Unggah dokumen pendukung.
5. Rekap alsintan ditampilkan per desa/SP, per poktan, dan per jenis alat.

### 4.8 Modul Saprotan
1. Input jenis saprotan (benih, pupuk, pestisida, mulsa, dan sejenisnya), jumlah, satuan, dan waktu perolehan.
2. Tentukan penerima: kelompok tani atau individu transmigran.
3. Untuk penyaluran ke anggota poktan, sistem hanya menampilkan anggota berstatus aktif.
4. Unggah dokumen pendukung.
5. Rekap penyaluran ditampilkan per periode, per poktan, dan per desa/SP.

### 4.9 Modul Komoditas
1. Pilih atau buat data komoditas beserta tipenya (pangan, palawija, hortikultura).
2. Tetapkan **satuan panen baku** untuk komoditas tersebut, diambil dari data master satuan.
3. Tandai komoditas unggulan kawasan bila diperlukan.
4. Hubungkan dengan transmigran, lahan, poktan, dan hasil panen.
5. Gunakan pada dashboard dan laporan, dianalisis per desa/SP atau per periode.

### 4.10 Modul Hasil Panen
1. Pilih komoditas; sistem otomatis menampilkan satuan baku komoditas tersebut.
2. Input volume panen sesuai satuan baku, kualitas panen, harga jual, musim tanam, dan lokasi produksi.
3. Isi keterangan tambahan bila di lapangan memakai satuan lokal seperti karung atau ikat.
4. Validasi data panen.
5. Simpan riwayat tanam dan riwayat panen apa adanya, tanpa konversi saat penyimpanan.
6. Saat merekap, sistem mengonversi volume ke ton memakai faktor konversi pada data master satuan.
7. Rekap berdasarkan wilayah, transmigran, poktan, komoditas, atau periode.

### 4.11 Modul Infrastruktur SP
1. Input data aset infrastruktur: air, irigasi, listrik, jalan produksi, telekomunikasi, atau gudang.
2. Catat nama, tahun perolehan, sumber dana, dan kondisi terkini.
3. Lampirkan dokumentasi foto dan koordinat lokasi.
4. Tautkan ke desa/SP dan poktan bila relevan.
5. Tampilkan sebagai peta aset dan bahan perencanaan perbaikan.

### 4.12 Modul Penghuni Kawasan
1. Input data penghuni/transmigran per desa/SP.
2. Catat status tinggal, pindah, dan aktif/tidak aktif.
3. Tautkan ke data rumah beserta kondisi, foto, dan koordinatnya.
4. Validasi oleh dinas terkait atau admin.
5. Tampilkan rekap kependudukan kawasan, termasuk KK masuk dan keluar per tahun, dalam bentuk agregat pada dashboard.

### 4.13 Modul Pengaduan

**Jalur A: warga melapor sendiri lewat halaman publik**
1. Warga membuka halaman pengaduan publik, tanpa perlu akun maupun masuk sistem.
2. Mengisi nama, kontak, lokasi/SP, kategori, dan uraian masalah, serta melampirkan foto bila ada.
3. Sistem memeriksa batas pengiriman, yaitu maksimal 3 pengaduan per jam untuk setiap alamat IP.
4. Laporan tersimpan dengan sumber `Publik` dan status **Menunggu Diterima**.
5. Warga menerima **nomor pengaduan**, misalnya `PGD-2026-0001`, untuk melacak perkembangan laporannya.

**Jalur B: warga melapor lisan kepada petugas**
1. Warga menyampaikan keluhan langsung kepada operator SP atau petugas dinas.
2. Petugas mencatatkan laporan ke sistem atas nama warga, mengisi nama dan kontak pelapor.
3. Laporan tersimpan dengan sumber `Petugas` dan status **Menunggu Diterima**.
4. Petugas menyampaikan nomor pengaduan kepada warga.

**Penanganan (berlaku untuk kedua jalur)**
5. Petugas menyaring laporan yang masuk, memilah laporan sungguhan dari laporan iseng.
6. Dinas terkait menerima pengaduan sesuai bidangnya, status berubah menjadi **Diterima**.
7. Penanganan dijalankan dan status berubah menjadi **Diproses**, disertai catatan penanganan.
8. Setelah tuntas, status menjadi **Selesai** beserta dokumen tindak lanjut.
9. Setiap perubahan status tersimpan pada riwayat penanganan.
10. Warga dapat memantau seluruh perkembangan lewat halaman lacak publik memakai nomor pengaduannya.
11. Rekap pengaduan per kategori, status, dan desa/SP menjadi sumber indikator isu prioritas pada dashboard.

## 5. Workflow Per Role

Role bersifat dinamis (`rules.md` bagian 5), sehingga alur di bawah menggambarkan **empat role bawaan**. Admin dapat menyusun role lain sesuai kebutuhan lapangan.

### 5.1 Admin
1. Membuat akun pengguna, menetapkan role, dan bila role bercakupan Per SP, menugaskan SP yang menjadi tanggung jawabnya.
2. Menyusun role baru bila dibutuhkan: memberi nama, memilih izin per modul, dan menetapkan cakupan datanya.
3. Menyetel ulang kata sandi pengguna yang lupa, lalu menyerahkan kata sandi sementara secara langsung.
4. Menonaktifkan akun yang tidak lagi dipakai, tanpa menghapusnya, agar jejak audit tetap utuh.
5. Mengelola data master wilayah, kawasan transmigrasi, SP, inventaris, fasilitas, dan parameter referensi.
6. Memantau audit log perubahan data, termasuk jejak penyetelan ulang kata sandi dan perubahan susunan izin role.
7. Membantu validasi data dan operasional deployment.
8. Memiliki akses penuh ke seluruh modul.

### 5.2 Dinas Transmigrasi
1. Membuka dashboard dan memantau indikator kawasan.
2. Mengelola data transmigran, rumah, lahan, inventaris, dan fasilitas SP.
3. Menerima dan menangani pengaduan bidang ketransmigrasian.
4. Mengunduh laporan untuk perencanaan program dan tindak lanjut.

### 5.3 Dinas Pertanian
1. Membuka dashboard dan memantau indikator pertanian.
2. Mengelola data poktan, komoditas, hasil panen, alsintan, dan saprotan.
3. Menerima dan menangani pengaduan bidang pertanian.
4. Mengunduh laporan produksi dan sarana pertanian.

### 5.4 Operator SP
1. Masuk dengan cakupan data terbatas pada SP yang ditugaskan kepadanya.
2. Memasukkan dan memperbarui data transmigran, rumah, lahan, riwayat tanam, dan hasil panen di SP tersebut.
3. Mencatatkan pengaduan warga yang disampaikan lisan, atas nama warga bersangkutan.
4. Memperbaiki data yang ditolak dinas sesuai alasan penolakan yang tertulis.
5. Tidak berwenang menghapus data maupun mengakses manajemen pengguna dan audit log.

### 5.5 Warga transmigran (tanpa akun)
1. Warga **tidak memiliki akun sistem**. Data mereka dikelola petugas.
2. Untuk melapor, warga membuka halaman pengaduan publik tanpa perlu masuk.
3. Mengisi nama, kontak, SP, kategori, uraian masalah, dan foto bila ada.
4. Menerima nomor pengaduan, lalu memakainya untuk melacak perkembangan laporan pada halaman lacak publik.
5. Bila tidak memiliki akses internet, warga dapat melapor lisan kepada operator SP untuk dicatatkan ke sistem.

## 6. Workflow Validasi dan Persetujuan Data
1. Data diinput oleh petugas sesuai izin dan cakupan data role-nya.
2. Sistem melakukan validasi format dan kelengkapan di sisi client dan sisi server.
3. Dinas terkait atau admin meninjau data sesuai bidangnya.
4. Jika salah atau kurang lengkap, data dikembalikan untuk revisi.
5. Jika valid, data disetujui.
6. Data masuk ke dashboard dan laporan.
7. Seluruh proses tercatat pada audit log.

## 7. Workflow Pengujian
1. Alpha testing internal oleh tim teknis.
2. Perbaikan bug dengan prioritas blocker.
3. Beta testing bersama dinas, perangkat desa/SP, pendamping, dan operator.
4. Pencatatan masukan pengguna, dipisahkan menjadi revisi wajib dan pengembangan lanjutan.
5. Validasi akhir bersama calon pengguna sebelum serah terima.

## 8. Workflow Deployment dan Serah Terima
1. Aplikasi dipasang pada hosting/server.
2. Domain/subdomain dikonfigurasi.
3. SSL/HTTPS diaktifkan.
4. Database dan storage diatur.
5. Backup dijadwalkan dan error log dipantau.
6. Aplikasi diuji pada kondisi operasional.
7. Pengguna dilatih.
8. Sistem diserahterimakan melalui BAST bersama akun, database awal, SOP, panduan, dan dokumentasi teknis.
9. PIC lokal ditetapkan sebagai penanggung jawab keberlanjutan.

## 9. Workflow Pemeliharaan
1. Admin atau penanggung jawab lokal memantau penggunaan sistem.
2. Data diperbarui secara berkala.
3. Bug dan kendala dicatat.
4. Perbaikan dilakukan bertahap.
5. Backup dan log error dipantau.
6. Sistem dikembangkan lanjutan bila kebutuhan bertambah, tetap selaras dengan tujuan monitoring pertanian dan tata kelola data kawasan.

## 10. Ringkasan Alur End-to-End
1. Pengguna login.
2. Operator menginput data, luring maupun daring.
3. Data divalidasi.
4. Data tampil di dashboard.
5. Pengguna melihat indikator dan isu prioritas.
6. Laporan diekspor.
7. Data ditindaklanjuti dalam monitoring dan perencanaan.
8. Sistem dipelihara dan dikembangkan bertahap.

## 11. Workflow Pengerjaan AI
Alur ini dipakai agar pengerjaan oleh AI dapat dilanjutkan lintas sesi tanpa kehilangan konteks.

1. Baca `agents/prd.md`, `agents/rules.md`, `agents/workflow.md`, dan `agents/ui-spec.md` sebagai acuan utama; `agents/notes.md` untuk catatan teknis dan keputusan yang sudah diambil.
2. Buka `agents/tasklist.md` dan tentukan task berikutnya yang belum selesai.
3. Kerjakan **satu task** sampai tuntas, mengikuti konvensi UI/UX dan aturan penulisan kode pada `rules.md`.
4. Jalankan verifikasi: `php artisan test`, `npm run build`, dan `php artisan view:cache`; lakukan smoke test browser pada lebar 360px dan 1280px untuk perubahan UI.
5. Perbarui `agents/tasklist.md`: tandai `[✓]`, tambahkan ✅, perbarui persentase progres, dan catat file yang dibuat/diubah.
6. Sampaikan ringkasan singkat hasil task kepada user.
7. Bila mendekati batas konteks, berhenti pada checkpoint yang rapi agar agent berikutnya dapat langsung melanjutkan.

## 12. Penutup
Workflow ini disusun untuk memastikan sistem tidak berhenti sebagai aplikasi administrasi, tetapi menjadi instrumen bersama untuk monitoring kawasan, penguatan kelembagaan, perbaikan layanan, dan pengembangan ekonomi masyarakat transmigran.
