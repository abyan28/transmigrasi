# Tahap 4 Task 4.8 - Penilaian Kondisi SP SELESAI -- TAHAP 4 TUNTAS (2026-09-03)

Task terakhir Tahap 4. Seluruh sembilan task selesai.

## Yang dikerjakan

- **`PenilaianKondisiController`** menggantikan 3 closure: daftar parameter
  (18 baris), sunting bobot, dan sunting ambang status.
- Parameter **dinonaktifkan, bukan dihapus**, agar riwayat penilaian yang
  memakainya tetap terbaca.

## Penjaga ambang menurun

Ambang WAJIB menurun menurut urutan status (80 -> 55 -> 0). Pembacaan status
berhenti pada ambang tertinggi yang cocok, sehingga ambang Mandiri yang lebih
KECIL daripada Berkembang membuat **Berkembang mustahil dicapai** -- seluruh SP
di rentang itu terbaca Mandiri.

Kegagalan senyap: tak ada galat, hanya satu status yang lenyap dari kawasan
tanpa ada yang menyadarinya. Diperiksa terhadap TETANGGA langsung (atas dan
bawah), dan diuji dua arah sekaligus satu perubahan sah.

## Temuan 1: bobot desimal dibulatkan diam-diam

Uji menyimpan bobot `12.5` lalu membaca **13**. Kolom `bobot` bertipe
**TINYINT UNSIGNED** di skema, sedangkan validasi saya menuliskannya
`numeric` -- desimal diterima lalu dibulatkan MySQL tanpa peringatan.

Bahayanya bukan pembulatannya, melainkan **senyapnya**: petugas menyusun bobot
agar berjumlah tepat 100, menyimpan, lalu totalnya meleset tanpa ada yang
memberi tahu. Validasi diperketat ke `integer` dan ditambahkan uji yang
mengunci penolakannya.

## Temuan 2: dua kunci tampilan hilang

Halaman membalas 500 dua kali berturut-turut:

1. `Undefined array key "nilai_jenis"` -- nama referensi yang ditunjuk
   parameter, ditambahkan lewat relasi `referensi` yang di-eager-load.
2. `Attempt to read property "value" on string` -- view memanggil
   `$p['tingkat']->value`, sedangkan kolom ENUM itu belum di-cast model
   sehingga Eloquent menyerahkannya sebagai string biasa. Dikembalikan sebagai
   `TingkatKebutuhan` di controller.

Keduanya jenis kesalahan yang sama: bentuk data dari Eloquent tidak persis
sama dengan bentuk dari `DummyData`, dan hanya ketahuan saat halamannya
benar-benar dirender.

## Verifikasi akhir Tahap 4

- `pest` **1.003 PASS / 8.252 assertions** (995 + 8 uji baru).
- `pint --test` **26** - `sim:tautan-statis` **14** -
  `sim:banding-skema --lengkap` **NOL SELISIH**.
- Manual: **14 dari 14** halaman Tahap 4 membalas 200 -- beranda, wilayah,
  kawasan, SP, inventaris, fasilitas, infrastruktur, satuan, referensi,
  penilaian kondisi, beserta empat halaman rincian.

## Tahap 4 tuntas -- 9 task

| Task | Isi |
|---|---|
| 4.1 | Wilayah bertingkat + seeder 38 provinsi / 514 kabupaten |
| 4.5 | Master satuan + faktor konversi ton |
| 4.7 | Daftar Pilihan (referensi) -- induk seluruh dropdown |
| 4.1b | Kawasan + unggahan berkas nyata pertama |
| 4.2 | Satuan permukiman + penjaga rentang min/maks |
| 4.3 | Inventaris SP |
| 4.4 | Fasilitas SP + cakupan lintas SP |
| 4.6 | Infrastruktur SP (pindahan dari 8.1) |
| 4.8 | Parameter bobot + ambang penilaian kondisi |

## Sisa `DummyData`

221 -> **189** pemanggilan (126 `routes/internal.php`, 59
`ViewServiceProvider`, 4 `routes/web.php`). Turun 32 sepanjang Tahap 4.

`ViewServiceProvider` belum tersentuh sama sekali: 59 pemanggilannya melayani
dropdown lintas modul yang baru dapat dicabut setelah modul pemakainya
beralih (Tahap 5-8).

---

# Tahap 4 Task 4.6 - Infrastruktur SP SELESAI (2026-09-03)

Dipindah dari Task 8.1 pada audit tasklist: di sidebar ia berada dalam grup
**Wilayah & SP**, dan `infrastruktur_sp` ber-FK ke `satuan_permukiman` yang
baru lahir di Tahap 4.

## Yang dikerjakan

- **`InfrastrukturSeeder`** -- 35 aset + 39 baris cakupan (4 di antaranya
  melayani lebih dari satu SP).
- **`InfrastrukturController`** menggantikan 5 closure, memakai trait
  `MenyimpanBerkas` dan pola cakupan yang sama dengan fasilitas SP.
- **`PenilaianKondisiSeeder`** (untuk Task 4.8) -- parameter bobot + ambang
  status. Keduanya keputusan KEBIJAKAN yang wajib divalidasi dinas
  (`rules.md` 10c poin 13), bukan angka teknis.

## `satuan_permukiman_id` adalah PANGKAL, bukan satu-satunya yang dilayani

Pivot `infrastruktur_sp` mencatat SP mana saja yang benar-benar DILAYANI.
Sebelum Putaran 7, kenyataan itu hanya tertulis pada `kapasitas` sebagai teks
("Melayani 3 SP sekitar") sebab satu FK tunggal tak menampungnya -- dan
penilaian kondisi SP karena itu tak dapat membacanya.

SP pangkal SELALU disertakan apa pun isian form, dijaga uji atas SELURUH baris.

## Temuan: `BerkasSeeder` menuntut urutan pemanggilan

Delapan uji `AsetSpTest` mendadak memerah `FOREIGN KEY constraint fails
(infrastruktur_berkas)` begitu `infrastruktur_berkas` masuk `PIVOT_SIAP`.

Sebabnya `BerkasSeeder` menanam pivot SELURUH modul yang siap sekaligus,
sehingga berkas uji yang memanggilnya WAJIB menanam seluruh induk lebih dulu.
Ditambahkan `InfrastrukturSeeder` pada `beforeEach`-nya beserta komentar yang
menyebutkan alasannya, supaya pemanggil berikutnya tidak terjebak sama.

## Verifikasi

- `pest` **995 PASS / 8.230 assertions** (991 + 4 uji baru).
- `pint --test` **26** - `sim:tautan-statis` **14** -
  `sim:banding-skema --lengkap` **NOL SELISIH**.
- Manual: `/sp/infrastruktur`, rincian, penyaring kondisi, dan
  `/master/penilaian-kondisi` seluruhnya 200.

## Sisa `DummyData`

199 -> **191** pemanggilan (128 `routes/internal.php`).

---

# Tahap 4 Task 4.3 + 4.4 - Inventaris & Fasilitas SP SELESAI (2026-09-03)

Dikerjakan berpasangan sebab strukturnya sama persis -- aset milik SP beserta
kondisi, sumber dana, dan status penyerahan. Yang membedakan hanya koordinat
dan cakupan lintas SP milik fasilitas.

## Yang dikerjakan

- **`AsetSpSeeder`** -- inventaris (5) + fasilitas (26) + pivot cakupan (29).
- **`InventarisSpController`** & **`FasilitasSpController`** menggantikan 10
  closure; keduanya memakai trait `MenyimpanBerkas` dari Task 4.1b.
- **`ValidationRules::referensi()`** (BARU) -- kolom REF divalidasi terhadap
  tabel `referensi` yang AKTIF, bukan daftar tetap di dalam kode. Nilai yang
  sudah dinonaktifkan tetap terbaca pada data lama tetapi ditolak pada data
  baru. Inilah yang menghidupkan Task 4.7 secara nyata.
- Cakupan fasilitas: **SP pangkal SELALU disertakan** apa pun isian form --
  fasilitas yang tak melayani SP tempatnya berdiri tidak masuk akal.

## Temuan 1: `status_penyerahan` NOT NULL, validasi menandainya opsional

Penyimpanan fasilitas gagal `Field 'status_penyerahan' doesn't have a default
value`. Skema menandainya NOT NULL tanpa default, sedangkan validasi saya
menuliskannya `nullable`.

Yang dibetulkan **validasinya**, bukan skemanya: status penyerahan memang
wajib diketahui: barang yang belum jelas diserahkan atau belum tidak dapat
dipertanggungjawabkan pada laporan aset.

## Temuan 2: `UppercaseInput` merusak `jenis_fasilitas` (pengulangan ketiga)

Setelah `tingkat` (4.1) dan `jenis` (4.7), kini `jenis_fasilitas` -- ENUM
berhuruf campur yang dikapitalkan lalu ditolak validasi. Ketiganya kini
sebaris di daftar kecuali.

**Pola yang perlu diingat:** setiap isian yang nilainya MEMILIH sesuatu
(tabel, daftar, enum) wajib masuk daftar kecuali sejak awal, bukan setelah
ujinya memerah.

## Temuan 3: satu peran berkas hilang dari tampilan

Uji `menyediakan cara membuka berkas` menuntut 3 tautan pada
`/sp/fasilitas/3`, terbit 2. Controller hanya menyuplai peran `foto`,
sedangkan view juga membaca `dokumen_pendukung`.

Penjaga ini bekerja persis sebagaimana dirancang Putaran 14: ia menghitung
tautan berkas nyata per halaman, sehingga peran yang lupa disuplai langsung
memerah.

## Verifikasi

- `pest` **991 PASS / 8.186 assertions** (983 + 8 uji baru).
- `pint --test` **26** (baseline; `PenyimpananDokumen.php` yang sempat ikut
  dirapikan pint DIKEMBALIKAN -- di luar cakupan).
- `sim:tautan-statis` **14** - `sim:banding-skema --lengkap` **NOL SELISIH**.
- Manual: `/sp`, `/sp/inventaris`, `/sp/fasilitas`, kedua halaman rincian, dan
  dua penyaring seluruhnya 200; `/sp/fasilitas/3` menerbitkan 3 tautan berkas.

## Sisa `DummyData`

210 -> **199** pemanggilan (136 `routes/internal.php`). Menembus bawah 200.

---

# Tahap 4 Task 4.2 - CRUD satuan permukiman SELESAI (2026-09-03)

SP adalah **induk** inventaris, fasilitas, dan infrastruktur, sehingga
dikerjakan sebelum ketiganya.

## Yang dikerjakan

- **`SpSeeder`** -- enam SP lokus. `desa_id` diturunkan dari NAMA desa pada
  data contoh (yang hanya menyimpan labelnya); nama yang tak ditemukan
  DILEWATI dengan peringatan, bukan ditanam ber-`desa_id` karangan yang akan
  menautkan SP ke wilayah keliru.
- **`SpController`** menggantikan 4 closure. `desa.kecamatan` dan `kawasan`
  di-eager-load supaya label wilayah tidak dikueri per baris.
- Penghapusan diperiksa terhadap TUJUH relasi turunan satu per satu, supaya
  alasannya menyebut modul mana yang menahan; galat FK mentah hanya menyebut
  nama tabel.

## Penjaga rentang min/maks

Keadaan wilayah punya lima pasangan min/maks (kemiringan, curah hujan, suhu,
angin, penyinaran) ditambah pH tanah. Maks WAJIB `gte` minnya.

Tanpa itu petugas dapat menyimpan rentang TERBALIK -- mis. curah hujan
3000-500 -- yang lolos diam-diam lalu terbaca sebagai rentang kosong pada
Laporan Monografi SP. Diuji dengan dua pasangan terbalik sekaligus satu
pasangan sah bernilai sama (min = maks), sebab rentang setitik itu sah.

Daftar pasangannya ditulis SEKALI pada konstanta `RENTANG`; aturan dan
pesannya diturunkan dari sana supaya keduanya tidak dapat berselisih.

## `jumlah_kk_terisi` BUKAN kolom

Ia turunan cacah transmigran. Selama Tahap 5 belum berjalan, angkanya masih
dibaca dari `DummyData::rekapPerSp()` -- dicatat di docblock controller supaya
tidak ada yang mengira nilainya sudah nyata.

## Verifikasi

- `pest` **983 PASS / 8.136 assertions** (975 + 8 uji baru). **Nol uji lama
  merah** -- peralihan ini tidak mengubah bentuk data yang dibaca tampilan.
- `pint --test` **26** - `sim:tautan-statis` **14** -
  `sim:banding-skema --lengkap` **NOL SELISIH**.
- Basis data: 6 SP, `desa_id` 1-6 benar, slug otomatis dari nama.

## Sisa `DummyData`

212 -> **210** pemanggilan (147 `routes/internal.php`).

---

# Tahap 4 Task 4.1b - CRUD kawasan + unggahan berkas nyata SELESAI (2026-09-03)

**Unggahan sungguhan PERTAMA di proyek ini.** Sepanjang Tahap 2-3 seluruh
isian berkas berhenti di data contoh; mulai task ini registry `berkas` +
`PenyimpananDokumen` dipakai nyata.

## Yang dikerjakan

- **`MenyimpanBerkas`** (trait baru, `app/Http/Controllers/Concerns/`) --
  menyimpan unggahan ke cakram PRIVAT lalu melekatkannya lewat pivot `*_berkas`
  milik modulnya. Ditulis sekali sebab empat modul melakukan hal sama persis
  (kawasan, inventaris SP, fasilitas SP, infrastruktur SP); menyalinnya ke tiap
  controller berarti empat tempat yang dapat berselisih diam-diam. `uuid`
  dibangkitkan di sini sebab model `Berkas` belum punya observer auto-generate.
- **`KawasanController`** menggantikan 4 closure. `berkas` dan
  `kabupaten.provinsi` di-eager-load, `satuanPermukiman` di-`withCount` --
  daftar berkas per kartu kawasan sebelumnya rawan N+1 (notes.md 1g.5).
- **`KawasanSeeder`** + **`BerkasSeeder`** -- keduanya membaca `DummyData`
  supaya id sama persis dengan yang masih dipakai modul lain selama Tahap 4.
- Penghapusan ditolak bila kawasan masih menaungi SP; pivot dilepas tetapi
  baris registry `berkas` TIDAK ikut hilang (Task 3.1 B4) sebab registry
  melayani banyak modul.

## Temuan 1: view menuntut label yang sempat hilang

Lima uji `HalamanTest` memerah `Undefined array key "kabupaten"`. Controller
semula hanya menyuplai `kabupaten_id`, padahal kartu kawasan menampilkan nama
kabupaten dan provinsinya, serta `jumlah_sp`.

Ditambahkan sebagai label tampilan yang dibaca lewat relasi ter-eager-load --
kebenarannya tetap `kabupaten_id` (`rules.md` 4a: pencocokan lewat nama putus
diam-diam begitu ejaan data master berubah).

## Temuan 2: `BerkasSeeder` melanggar FK di suite Feature

Lima belas uji `DummyDataTest` memerah `FOREIGN KEY constraint failed`. Data
contoh menyetel `berkas.user_id = 1`, sedangkan suite Feature tidak menanam
akun sama sekali -- ia memakai pengguna semu yang tak dipersist.

`user_id` karena itu SENGAJA dikosongkan seeder. Kolomnya memang nullable:
kanal publik mengunggah tanpa akun (Putaran 12 keputusan 4), sehingga
ketiadaan pengunggah bukan galat.

## Pivot ditanam BERTAHAP

`BerkasSeeder::PIVOT_SIAP` membatasi pivot yang ditanam pada modul yang
induknya sudah bertabel isi -- saat ini hanya `kawasan_transmigrasi_berkas`.
Menanam pivot bagi induk yang tabelnya masih kosong hanya akan melanggar FK.
Daftar ini bertambah seiring modulnya dikerjakan sepanjang Tahap 4-8.

## Utang yang dicatat, bukan ditebak

Form kawasan mengirim SATU isian multi-berkas `dokumen_kawasan[]`, sehingga
peran tiap berkas (`hpl`/`sk`/`peta`) belum dapat dibedakan dari sana.
Seluruhnya direkam berperan `sk` -- peran bawaan yang sama dipakai `DummyData`.
Penajaman peran per berkas menuntut isian pemilih di form (perubahan UI) dan
dicatat di docblock controllernya, bukan dikarang di peladen.

## Verifikasi

- `pest` **975 PASS / 8.110 assertions** (968 + 7 uji baru). Durasi 125 detik.
- Uji unggahan memakai `Storage::fake` dan memeriksa: berkas fisik ada di
  cakram, `uuid` terisi, `disk` = local, `peran` = sk, `urutan` berurutan, dan
  **path tidak menyentuh folder publik**. Batas 5 MB diuji PER BERKAS.
- `pint --test` **26** - `sim:tautan-statis` **14** -
  `sim:banding-skema --lengkap` **NOL SELISIH**.
- Manual: `/kawasan` 200, menampilkan Kobalima Timur + Kabupaten Malaka, dan
  **3 tautan berkas** (sk, hpl, peta) terbit.

## Sisa `DummyData`

216 -> **212** pemanggilan (149 `routes/internal.php`, 59
`ViewServiceProvider`, 4 `routes/web.php`).

---

# Tahap 4 Task 4.5 + 4.7 - Master Satuan & Daftar Pilihan SELESAI (2026-09-03)

Dua task dikerjakan berurutan atas permintaan pemilik proyek (kerjakan
beberapa task sekaligus selama tak ada konflik). Keduanya data master yang
DIANDAIKAN ADA oleh modul-modul Tahap 5-8.

## Task 4.5 - Data master satuan

- `SatuanSeeder` -- Ton (1), Kuintal (0,1), Kilogram (0,001). Idempoten lewat
  `updateOrCreate` pada `nama` yang UNIQUE.
- `MasterSatuanController` menggantikan 4 closure. `withCount(komoditas)`
  menyuplai `dipakai_komoditas` yang sudah dibaca tampilan.
- **`faktor_ke_ton` divalidasi `gt:0`**, bukan sekadar `numeric`. Faktor nol
  membuat volume panen LENYAP dari rekap dan faktor negatif membalik tandanya
  -- keduanya kegagalan senyap. Diuji dengan tiga nilai (0, -1, -0.5).
- Penghapusan ditolak dengan kalimat terbaca bila satuan masih dipakai
  komoditas; FK RESTRICT menahannya, tetapi galat SQL mentah tak dapat
  ditindaklanjuti petugas.

## Task 4.7 - Daftar Pilihan (referensi)

- `ReferensiSeeder` -- 76 baris, 14 jenis, dibaca dari `DummyData::referensi()`
  (pola sama `PermissionRoleSeeder`). **Dibaca, bukan disalin ulang, sebab
  id-nya sudah ditunjuk pihak lain:** `PenilaianKondisiSp::parameter()`
  merujuk `referensi_id` untuk jenis infrastruktur dan fasilitas. Menyusun
  ulang daftarnya akan menggeser id itu diam-diam dan membuat penilaian
  kondisi SP menunjuk jenis yang keliru. Dijaga uji yang membandingkan
  SELURUH 76 baris terhadap `DummyData`.
- `MasterReferensiController` menggantikan 4 closure, termasuk redirect 301
  alamat lama `?tab={jenis}`.
- **Keunikan ditegakkan DALAM jenis, bukan lintas jenis:** "Lainnya" sah
  muncul pada banyak daftar sekaligus.
- **Tanpa rute hapus**, disengaja: nilai dinonaktifkan lewat `is_aktif`.
  Menghapusnya membuat data lama menunjuk pilihan yang lenyap dan rekapnya
  kehilangan baris tanpa pesan apa pun.
- Kolom yang tak berlaku bagi jenisnya DIKOSONGKAN, bukan dibiarkan terbawa:
  skor pada daftar tak berskor tak pernah dibaca siapa pun dan hanya
  menyesatkan pembaca tabel.
- Self-FK `bidang_id` terjaga: 8 kategori berbidang, sisanya NULL sebab dapat
  jatuh ke dua dinas sekaligus (`rules.md` 10b poin 7b).

## Temuan 1: `UppercaseInput` merusak `jenis` (pengulangan Task 4.1)

Persis kasus `tingkat` kemarin: middleware mengubah `sumber_dana` menjadi
`SUMBER_DANA`, lalu validasi enum menolaknya. Ditambahkan ke daftar kecuali
bersama `tingkat`, dengan komentar yang menyatukan alasan keduanya: **penunjuk
sasaran, bukan isi data.**

Dua kali berturut-turut pada dua task berbeda. Pola yang perlu diingat saat
menambah isian pemilih baru sepanjang Tahap 4-8.

## Temuan 2: grup Database ikut kebagian seeder Feature

Lima uji Task 3.1 memerah `UniqueConstraintViolationException`.
`Tests\DatabaseTestCase` **mewarisi** `Tests\TestCase`, sehingga properti
`$seeder` yang dipasang Task 4.1 ikut terbawa ke grup Database.

Uji di sana menyusun barisnya SENDIRI untuk menguji constraint, sehingga
tabel yang sudah terisi justru membuatnya bertabrakan: `Domain2WilayahSpTest`
membuat `bidang_pengaduan` sendiri lalu kena `uq_referensi_jenis_nilai`
begitu `ReferensiSeeder` menanamnya lebih dulu.

Diperbaiki dengan `protected $seeder = null` eksplisit pada `DatabaseTestCase`
beserta alasannya. Uji yang memerlukan data master memanggil seedernya sendiri
lewat `$this->seed(...)` di `beforeEach` masing-masing.

## `DataMasterSeeder` (baru)

Membungkus seluruh seeder data master yang diandaikan tampilan
(`WilayahSeeder` + `SatuanSeeder` + `ReferensiSeeder`), dipakai
`Tests\TestCase::$seeder`. Menambah data master sepanjang Tahap 4 cukup
mendaftarkannya DI SINI; berkas uji tidak perlu disentuh lagi.

Sengaja TIDAK menanam role/izin/akun: suite Feature memakai pengguna semu
bertanda `semuaIzin`, sehingga menanamnya hanya memperlambat tanpa dipakai.

## Verifikasi

- `pest` **968 PASS / 8.076 assertions** (947 + 21 uji baru: 11 satuan, 10
  referensi). Durasi **114 detik**, praktis sama dengan baseline 111 detik.
- `pint --test` **26** (baseline). Tiga berkas pre-existing yang sempat ikut
  dirapikan pint (`FondasiTest`, `FormatNominalUangTest`, `BerkasBlade`)
  DIKEMBALIKAN -- di luar cakupan kedua task ini.
- `sim:tautan-statis` **14** - `sim:banding-skema --lengkap` **NOL SELISIH**.
- Manual: `/master/satuan`, `/master/referensi`, `/master/referensi/sumber_dana`,
  `/master/referensi/kategori_pengaduan` seluruhnya 200; bidang penanganan
  tampil; `?tab=sumber_dana` membalas **301** ke halaman daftarnya.
- Basis data: `referensi` 76 baris / 14 jenis / 8 berbidang / 1 nonaktif --
  identik dengan `DummyData`.

## Sisa `DummyData`

217 -> **216** pemanggilan (153 `routes/internal.php`, 59
`ViewServiceProvider`, 4 `routes/web.php`). Turunnya kecil sebab
`ReferensiSeeder` justru MEMBACA `DummyData` sebagai sumber kebenaran id;
pencabutannya menunggu seluruh pemakai referensi beralih.

---

# Tahap 4 Task 4.1 - CRUD wilayah bertingkat SELESAI (2026-09-03)

Task pertama Tahap 4, sekaligus **peralihan pertama tampilan dari `DummyData`
ke Eloquent**. Karena itu ia menentukan pola seluruh Tahap 4-8, bukan hanya
dirinya sendiri.

## Yang dikerjakan

- **`WilayahSeeder`** -- 38 provinsi + 514 kabupaten se-Indonesia dari
  `DataWilayah`; kecamatan (4) dan desa (6) HANYA wilayah lokus. **Id dipaksa
  sama dengan kode BPS** (Malaka 5321, NTT 53), bukan auto-increment, supaya id
  di basis data sama persis dengan yang dipakai `DataWilayah`/`DummyData` dan
  peralihan tak menggeser satu pun rujukan modul lain. Idempoten.
- **`WilayahController`** -- `index`/`simpan`/`perbarui`/`hapus` menggantikan 4
  closure. Empat tingkat dilayani satu controller lewat konstanta `TINGKAT`
  (kelas, kunci, kolom+relasi induk, daftar turunan): menambah tingkat cukup
  menyentuh satu tempat. Nama induk lewat eager loading -- daftarnya 500+ baris,
  membacanya per baris menghasilkan ratusan kueri untuk satu halaman.
- Penghapusan **ditolak dengan kalimat terbaca** bila wilayah masih menaungi
  turunan atau SP. FK RESTRICT sudah menahannya, tetapi galat SQL mentah tidak
  dapat ditindaklanjuti petugas.
- `/wilayah` beralih penuh ke Eloquent: penyaring tingkat, pencarian
  nama/induk/kode, dan paginasi kini kueri, bukan `array_filter`.

## Temuan 1: alamat `/wilayah/{id}` tidak pernah cukup

Kunci utama keempat tabel wilayah berdiri sendiri-sendiri, sehingga **id 1 sah
sebagai kecamatan (Laen Manen) MAUPUN desa (Kapitan Meo)**. Untuk Ubah masih
tertolong sebab form mengirim `tingkat` di body; **Hapus tidak** -- `DELETE
/wilayah/1` tak membawa body apa pun, dan peladen mustahil tahu yang dimaksud.

Menebaknya berarti membuang baris yang keliru tanpa memerahkan apa pun. Cacat
ini tak terlihat sepanjang Tahap 2 sebab datanya masih larik.

Atas keputusan pemilik proyek: tingkat disisipkan ke alamat menjadi
**`/wilayah/{tingkat}/{id}`**, dibatasi `where('tingkat', 'provinsi|kabupaten|
kecamatan|desa')`. Alamat menjadi jujur menyebut apa yang dituju.

Konsekuensi yang tidak diperkirakan: `x-sim.modal-form` hanya mengganti penanda
`:id`, sehingga `:tingkat` akan terkirim apa adanya. Diperbaiki di akarnya --
kini setiap penanda `:sesuatu` diganti properti bernama sama pada baris yang
dibuka. Penanda tanpa padanan **DIBIARKAN UTUH**, bukan dijadikan string
kosong: alamat cacat lebih baik gagal terang-terangan daripada menunjuk baris
yang keliru. Dua puluh empat pemanggil lain memakai `:id` dan tetap terlayani.

## Temuan 2: `UppercaseInput` merusak `tingkat`

Middleware mengubah `kecamatan` menjadi `KECAMATAN`, lalu validasi menolaknya
-- seluruh penyimpanan wilayah gagal dengan "Tingkat wilayah tidak sah."
Ditemukan uji, bukan mata.

`tingkat` ditambahkan ke daftar kecuali, sebaris dengan `cakupan_data` dan
`izin` yang dikecualikan atas alasan sama: **nilai sistem yang memilih tabel
sasaran, bukan isi data.**

## Temuan 3: strategi uji Tahap 2 tak menyanggupi Eloquent

Tujuh uji `HalamanTest` memerah serentak: `no such table: provinsi`. Suite
Feature memakai SQLite `:memory:` dengan `RefreshDatabase` **MATI** (keputusan
Task 3.1 B3) -- tabelnya tak pernah dibuat. Empat menguji `/wilayah` langsung,
tiga menyapu seluruh rute GET dan kena imbas.

Ketujuhnya menguji hal yang MASIH BERLAKU; yang berubah hanyalah dari mana
halaman mengambil data. Dilaporkan lebih dulu, tidak disesuaikan sendiri.

Keputusan pemilik proyek: **nyalakan `RefreshDatabase` + seed di suite Feature.**
Sekali kerja, berlaku untuk seluruh Tahap 4-8. Ketujuh uji pulih **tanpa satu
pun diubah isinya** -- bukti bahwa yang kurang memang datanya, bukan ujinya.

### Biaya kecepatan, dan cara membayarnya

Penempatan seeder ternyata menentukan segalanya:

| Cara | Feature | Keterangan |
|---|---|---|
| `$this->seed()` di `beforeEach` | **516 detik** | 552 baris ditulis ulang 732 kali |
| `RefreshDatabase::$seeder` di `Tests\TestCase` | **66 detik** | sekali per kelas, dipakai ulang lewat transaksi |

Suite penuh **111 detik**, sama persis dengan baseline sebelum Task 4.1 -- nol
biaya kecepatan. Bedanya satu baris, dan seluruhnya pada penempatan.

## Verifikasi

- `pest` **947 PASS / 7.946 assertions** (940 + 9 uji baru; 7 uji lama pulih
  utuh). Perlu `php -d memory_limit=1G`.
- `pint --test` **26** (kembali ke baseline setelah 4 berkas baru dirapikan) ·
  `sim:tautan-statis` **14** · `sim:banding-skema --lengkap` **NOL SELISIH**.
- Manual `migrate:fresh --seed` + HTTP: `/wilayah` 200 memuat **562 baris**
  (38+514+4+6); tambah kecamatan lewat form -> 302 dan tersimpan; penyaring
  tingkat, pencarian, paginasi berfungsi; alamat hapus terbit sebagai
  `/wilayah/desa/3`, bukan `/wilayah/3`.

## Sisa `DummyData`

221 -> **217** pemanggilan. Turun bertahap sepanjang Tahap 4-9; selama itu
sebagian halaman membaca basis data sementara sebagian masih data contoh. Itu
keadaan wajar, bukan pekerjaan setengah jadi.

---

# Audit celah backend: tasklist Tahap 4-10 dirapikan (2026-09-03)

Pemilik proyek bertanya sebelum Tahap 4 dibuka: menu Laporan, Data Master
(wilayah/satuan/daftar pilihan/penilaian kondisi SP), Pengelolaan Konten, dan
Bantuan & Info ada di bagian mana pada `tasklist.md`? Dan apakah SELURUH menu
frontend sudah punya task backend?

**Jawabannya: belum.** Menu disisir satu per satu dari `MenuHelper.php` terhadap
Tahap 4-11. Hasilnya empat kelompok temuan.

## A. Tujuh menu tanpa task backend sama sekali

| Menu | Path | Keadaan |
|---|---|---|
| Data Master > Wilayah | `/wilayah` | Task 4.1 hanya menyebut migration+seeder; CRUD-nya tak ada |
| Data Master > **Daftar Pilihan** | `/master/referensi` | **NOL task** -- padahal induk seluruh dropdown |
| Data Master > Penilaian Kondisi SP | `/master/penilaian-kondisi` | Task 9.5 hanya "bobot" pada konteks dashboard; CRUD parameter/ambang tak bertuan |
| **Pengelolaan Konten (CMS)** | `/cms` | **NOL task** -- 5 tab, seluruhnya nilai tetap di Alpine |
| Audit Log (halaman) | `/audit-log` | Task 3.6 hanya PENCATATAN; halaman+filter tak ada |
| Profil & ubah sandi | `/profil` | **NOL task** -- rute masih `return back()` kosong |
| Unduh berkas & template impor | `/dokumen/*`, `/template-impor/*` | Nol task |

`/panduan` dan `/tentang` (Bantuan & Info) memang tak perlu task tersendiri --
isinya statis dan diatur lewat CMS, jadi ikut Task 9.6.

## B. Lima task cakupannya USANG

Task **4.1, 5.1, 6.1, 7.1, 8.2** semuanya berbunyi "Migration dan model ...",
padahal **Task 3.1 sudah membuat 58 migration + 36 model** untuk seluruh 55
tabel bisnis, terverifikasi NOL SELISIH. Dibiarkan, pengerjaannya akan
membangun ulang yang sudah ada.

Kelimanya ditulis ulang menjadi "Peralihan ... ke Eloquent" beserta penunjuk
bahwa struktur DB-nya sudah lahir di Task 3.1 batch mana.

## C. Infrastruktur SP salah tahap -- DIPINDAH 8.1 -> 4.6

Di sidebar, Infrastruktur SP berada dalam grup **Wilayah & SP** bersama
Kawasan/SP/Inventaris/Fasilitas. Susunan lama menempatkannya di Tahap 8 bersama
Pengaduan -- warisan urutan sebelum menu dirombak. `infrastruktur_sp` pun ber-FK
ke `satuan_permukiman` yang baru lahir di Tahap 4.

Dipindah ke **Task 4.6**. Pengaduan TETAP di Tahap 8, dan judul tahapnya
disesuaikan menjadi "Backend Pengaduan".

## D. Tujuh laporan tanpa task pengisian data

`LaporanData::meta()` memuat 7 laporan (indikator-kawasan, monografi-sp,
transmigran, poktan, alsintan, saprotan, hasil-panen). Tahap 10 hanya membahas
EXPORT Excel/PDF; mengisi datanya dari Eloquent tidak pernah punya task.
Ditambahkan sebagai **Task 10.5**.

## Yang dikerjakan

- **8 task baru/pindah:** 4.6 (pindahan), 4.7, 4.8, 3.12, 3.13, 9.6, 10.5, 10.6
- **5 task ditulis ulang:** 4.1, 5.1, 6.1, 7.1, 8.2
- **Blok "BACA DULU"** di kepala Tahap 4 mencatat bahwa migration+model sudah
  ada, menyebut skala peralihan (**221 pemanggilan `DummyData`**: 158
  `routes/internal.php`, 59 `ViewServiceProvider`, 4 `routes/web.php`; **nol
  controller domain** -- seluruh rute masih closure), dan menetapkan urutan
  Tahap 4 mengikuti dependensi FK: **4.1 -> 4.5 -> 4.7 -> 4.1b -> 4.2 -> 4.3 ->
  4.4 -> 4.6 -> 4.8**.

`4.7` (Daftar Pilihan) sengaja didahulukan atas keputusan pemilik proyek: ia
induk seluruh dropdown, sehingga mengerjakannya belakangan berarti form Tahap
5-8 disentuh dua kali.

Task **3.12** dan **3.13** bernomor Tahap 3 sebab domainnya autentikasi/audit,
tetapi dikerjakan berbarengan Tahap 4 -- keduanya kecil dan menghapus pemakaian
terakhir `DummyData::penggunaSaatIni()`.

## Keputusan pemilik proyek yang mengikat

| # | Keputusan |
|---|---|
| 1 | Tasklist dirapikan LEBIH DULU sebelum satu baris kode Tahap 4 ditulis |
| 2 | Daftar Pilihan masuk Tahap 4, bukan ditunda |
| 3 | View dialihkan ke Eloquent **langsung per modul**, bukan menunggu seluruh Tahap 4 selesai |

Konsekuensi keputusan 3 yang diterima sadar: uji `HalamanTest` modul terkait
akan merah saat sumber datanya berpindah. Sesuai aturan repo, pengerjaan
**BERHENTI dan melapor** ketika itu terjadi, bukan menyesuaikan uji sendiri.
Tiap modul yang beralih WAJIB punya seeder data contoh supaya halaman tidak
mendadak kosong dan penjaga tampilan tetap bermakna.

## Catatan

Perubahan ini **nol kode** -- hanya `agents/tasklist.md`. Sengaja dicommit
terpisah dari pengerjaan Tahap 4 supaya perubahan rencana tidak bercampur
dengan perubahan perilaku.

---

# Nama sistem menjadi DIGITRANS + DB dev jadi `digitrans` (2026-09-03)

Pemilik proyek menetapkan nama sistem: **DIGITRANS** (Digitalisasi
Transmigrasi), menggantikan "SIM Transmigrasi".

## Temuan penyisiran: "SIM" punya DUA makna di repo ini

853 kemunculan `SIM`, dan hampir seluruhnya BUKAN nama produk:

| Bentuk | Jumlah | Diganti? |
|---|---|---|
| `x-sim.*` komponen Blade | ~800 | **TIDAK** |
| `sim:*` perintah artisan | 5 | **TIDAK** |
| `config/sim.php` | 8 | **TIDAK** |
| **"SIM Transmigrasi"** teks tampilan | **24** | **YA** |

Kelompok teknis sengaja dibiarkan: tak seorang pengguna pun melihat `x-sim.`
di layar, sehingga menggantinya berarti menyentuh ratusan Blade untuk nol
manfaat. Yang diganti hanya yang benar-benar terbaca manusia.

## Temuan kedua: sistem ini punya TIGA nama berbeda

- `cms/index.blade.php:63` -> "Sistem Informasi Monitoring Pertanian & Tata
  Kelola Kawasan"
- `README.md:1` -> "Sistem Informasi Kawasan Transmigrasi Kobalima Timur"
- `APP_NAME` -> "SIM Transmigrasi"

Ketiganya hidup berdampingan tanpa ada yang menyadari. DIGITRANS menyatukannya
-- perbaikan yang tidak diminta tetapi ikut didapat.

## Pembagian tiga bentuk nama

Prinsipnya: **makin jauh pembaca dari layar sistem, makin lengkap namanya.**

| Bentuk | Tempat | Alasan |
|---|---|---|
| `DIGITRANS` | `APP_NAME` -> judul tab, header, sidebar, footer internal, halaman galat | Dilihat petugas puluhan kali sehari; ruang sempit, nama panjang jadi bising |
| `DIGITRANS - Digitalisasi Transmigrasi` | `/login`, CMS "Nama Resmi Aplikasi", subjek surel, README | Titik temu PERTAMA; pembaca belum tentu tahu akronimnya |
| `DIGITRANS Kobalima Timur` | badan surel, footer publik | Keluar dari sistem menuju kotak masuk pribadi; penerima perlu tahu KAWASAN mana, sebab kelak ada DIGITRANS kawasan lain |

`signin.blade.php` sengaja memakai teks eksplisit, BUKAN `config('app.name')`,
supaya halaman masuk memuat kepanjangannya sementara header tetap ringkas.
`app-header` + `sidebar` justru sebaliknya: teks keras diganti
`config('app.name')` supaya penggantian berikutnya cukup di satu tempat.

## Yang TIDAK disentuh

- **Kop dokumen laporan.** Diperiksa lebih dulu, bukan diasumsikan:
  `components/sim/kop-laporan.blade.php` menyebut Kementerian dan Dinas dari
  `LaporanData::instansi()`, tidak pernah nama aplikasi. Nol perubahan.
  (`layouts/dokumen.blade.php:9` memakai `config('app.name')` hanya di
  `<title>` -- judul tab peramban, bukan bagian tercetak.)
- `x-sim.*`, `sim:*`, `config/sim.php` -- namespace teknis.

## Basis data: `sim_transmigrasi` -> `digitrans`

`.env`, `.env.example`, `config/database.php`, `DatabaseTestCase`,
`BandingSkema` (opsi `--skema-db`/`--migrasi-db`), README. DB uji ikut:
`digitrans_test`, `digitrans_skema_ref` -- keduanya terbentuk sendiri saat
`pest`/`sim:banding-skema` dijalankan.

`DB_TEST_DATABASE` ternyata belum ada di `.env` (hanya di `.env.example`),
sehingga ditambahkan -- tanpa itu grup uji Database tetap menunjuk DB lama.

Tiga DB lama (`sim_transmigrasi`, `sim_transmigrasi_test`,
`transmigrasi_skema_ref`, ~11,5 MB) **dihapus SETELAH pemilik proyek
mengonfirmasi sendiri dapat login dan logout di peramban** -- bukan setelah
verifikasi otomatis saja. Isinya hanya hasil seed; nol data lapangan.

## Efek samping yang disengaja

Nama cookie sesi diturunkan dari `APP_NAME` (`config/session.php:132`):
`sim-transmigrasi-session` -> `digitrans-session`. Seluruh sesi aktif terputus
sekali. Kredensial `admin`/`admin` tetap berlaku.

## Verifikasi

- Nol "SIM Transmigrasi" tersisa di seluruh kode di luar `agents/`.
- `pest` **938 PASS / 7.918 assertions** (tak berubah -- suite tak pernah
  menyebut nama produk). `pest tests/Database` 205 PASS diulang SESUDAH DB lama
  dihapus, memastikan tak ada yang diam-diam bergantung padanya.
- `sim:banding-skema --lengkap` **NOL SELISIH** terhadap DB baru.
- `pint --test` **26** - `sim:tautan-statis` **14** - `route:list` **151**.
- DB `digitrans`: 62 tabel, 58 migrasi, role 5, permission 97, admin 1.
- HTTP: `/login` 200 berjudul "Masuk | DIGITRANS" + nama panjang; login
  `admin`/`admin` -> 302 `/`; `/` berjudul "Dashboard | DIGITRANS", 5
  kemunculan DIGITRANS, **nol** sisa nama lama; `/pengaduan-warga` 200 dengan
  footer publik ber-DIGITRANS.
- Pemilik proyek mengonfirmasi login dan logout berjalan di peramban.

---

# Bypass masuk otomatis lokal DICABUT + bug keluar-sistem (2026-09-03)

Pemilik proyek menekan Keluar dan menerima **500**; sesudahnya `/login` selalu
dilempar ke dashboard sehingga halaman masuk mustahil dibuka. Keduanya berasal
dari satu akar: middleware `MasukOtomatisLokal` (Task 3.2b, C1).

## Akar

`MasukOtomatisLokal::penggunaPengembang()` membuat `new User([...])` yang
**tidak pernah di-`save()`**, lalu `Auth::login()`. Instance tanpa baris di
basis data berarti `id_user` bernilai **null**.

Rantainya:

1. Keluar -> `LoginController::keluar()` -> `catat()`
2. `catat()` mengisi `'record_id' => $pengguna->id_user` -> **null**
3. `audit_log.record_id` **NOT NULL** -> MySQL menolak -> **500**

Gejala kedua satu paket: middleware di-`prepend` ke grup `web`, sehingga ia
meng-`Auth::login()` SEBELUM `guest` dievaluasi. `guest` selalu melihat
pengguna sudah masuk, lalu melempar ke beranda. Selama flag menyala, halaman
login tidak akan pernah dapat dibuka.

## Mengapa baru muncul sekarang

Tabel `audit_log` belum ada di DB dev sampai migrasi hari ini. Sebelumnya
`catat()` gagal dengan "table doesn't exist" -- sama-sama galat, hanya belum
pernah ada yang mencoba keluar. Migrasi memindahkan kegagalannya ke constraint
kolom, dan di situlah ia terlihat.

Sebab kedua yang lebih penting: **suite Feature memakai SQLite**, yang tidak
menegakkan NOT NULL sekeras MySQL. Bug yang hidup di jalur autentikasi karena
itu tidak dapat ditangkap di sana.

## Keputusan: dicabut, bukan diperbaiki

Pemilik proyek memilih meniadakan bypass supaya `auth`, `guest`, dan
`pastikan.ganti.sandi` benar-benar teruji sehari-hari. Sejalan dengan itu,
**alasan lahirnya sudah gugur**: docblocknya berbunyi "basis data lokal belum
di-seed akun mana pun sampai Tahap 4", padahal `AdminAwalSeeder` kini menanam
akun Admin sungguhan ber-97-dari-97 izin. Mencabut lebih murah daripada
memperbaiki, dan menghapus satu jalan pintas yang dapat menutupi kegagalan
penegakan rute.

Dicabut: `app/Http/Middleware/MasukOtomatisLokal.php` (berkas), blok `prepend`
+ `use` di `bootstrap/app.php`, kunci `masuk_otomatis` di `config/sim.php`,
`SIM_MASUK_OTOMATIS` di `.env.example` dan `.env`.

**TIDAK disentuh:** `beforeEach` global `tests/Pest.php`. Itu mekanisme
terpisah (`actingAs()` di suite Feature, bukan lewat middleware); menyentuhnya
memerahkan ~340 panggilan HTTP tanpa alasan. Properti `User::$semuaIzin` tetap
hidup karena dipakai di sana -- hanya docblocknya dibetulkan.

## Penjaga baru

`AutentikasiTest` -- "mencatat keluar atas pengguna tersimpan, bukan pengguna
tanpa id_user". Berumah di grup **Database** (MySQL nyata), sebab justru
SQLite-lah yang membuat bug ini tak terlihat.

**Dibuktikan menangkap:** `$user` diganti sementara menjadi `new User([...])`
tak-tersimpan -> uji MERAH dengan galat identik yang dilaporkan pemilik proyek
(`Column 'record_id' cannot be null`, aksi Logout); dikembalikan -> HIJAU.

> **Aturan:** pengguna terautentikasi wajib punya baris di basis data. Instance
> `new User()` yang tak dipersist boleh hidup di uji yang tak menyentuh DB,
> tidak pernah di jalur permintaan sungguhan.

## Verifikasi

- `pest` **938 PASS / 7.918 assertions** (937 + 1 penjaga baru). Perlu
  `php -d memory_limit=1G`.
- `pint --test` **26** (tak berubah) - `sim:tautan-statis` **14** -
  `route:list` **151** (set rute identik; hanya middleware berkurang satu).
- Manual `php artisan serve`, DB dev, `audit_log` dikosongkan lebih dulu:

  | Langkah | Hasil |
  |---|---|
  | Tamu buka `/` | 302 -> `/login` |
  | Tamu buka `/login` | 200, tampil |
  | POST login `admin`/`admin` | 302 -> `/` |
  | `/`, `/transmigran`, `/audit-log` | 200 |
  | **POST `/logout`** | **302 -> `/login`** (dulu 500) |
  | Sesudah keluar, `/transmigran` | 302 -> `/login` |
  | Sesudah keluar, `/login` | 200 (dulu dilempar ke dashboard) |

- `audit_log` sesudahnya: 2 baris, Login + Logout, `record_id = 1` keduanya,
  **nol** baris ber-`record_id` null.

## Konsekuensi sehari-hari

Login tiap sesi habis (120 menit) dan tiap `migrate:fresh --seed`. Kredensial
`admin`/`admin` bertahan sebab `SIM_ADMIN_*` ada di `.env` lokal. Header masih
menampilkan "NARA WIJAYA" (`DummyData::penggunaSaatIni()`) sampai peralihan
Eloquent Tahap 4 -- kosmetik.

---

# DB dev tertinggal sejak Task 3.1 -- dimigrasikan + akun admin lokal (2026-09-03)

Pemilik proyek memeriksa phpMyAdmin dan bertanya mengapa Tahap 3 yang sudah
selesai belum tampak di basis data. Ternyata benar: **DB dev tidak pernah
di-migrate sejak Tahap 3 dimulai.**

## Temuan

| Basis data | Tabel | Keadaan saat ditemukan |
|---|---|---|
| `sim_transmigrasi` (DB dev) | **9** | tabel bawaan Laravel pra-Tahap-3: `users`, `password_reset_tokens`, `cache`, `jobs`, `sessions` |
| `sim_transmigrasi_test` | 62 | 58 migration Tahap 3 lengkap |
| `transmigrasi_skema_ref` | 61 | hasil impor `schema.sql` |

`sim_transmigrasi.migrations` hanya berisi **3 baris**, dan ketiganya menunjuk
berkas yang **sudah tidak ada lagi** -- `0001_01_01_000000_create_users_table`
di-`git mv` menjadi `create_sessions_table` pada Task 3.1 (B0). Jadi isinya
potret keadaan sebelum Tahap 3 dibuka.

## Mengapa tidak ada yang memerah

Dua sebab yang saling menutupi, dan ini yang perlu diingat:

1. **Seluruh penjaga skema memakai DB LAIN.** `tests/Database/` ber-`RefreshDatabase`
   ke `sim_transmigrasi_test`; `sim:banding-skema` `migrate:fresh` juga ke sana.
   Migration karena itu terus-menerus diverifikasi -- hanya tidak pernah di DB dev.
2. **Tidak ada satu pun query Eloquent di jalur tampilan.** Halaman masih membaca
   `app/Support/DummyData.php` seluruhnya; peralihan ke Eloquent baru Tahap 4.
   DB dev kosong pun web tetap tampil normal.

Akibatnya `php artisan migrate` biasa akan GAGAL di DB dev: tabel `sessions`
sudah ada tetapi namanya tak tercatat di `migrations`, sehingga Laravel mencoba
`CREATE TABLE sessions` dan menabrak "table already exists". Wajib `migrate:fresh`.

**Pelajarannya:** basis data uji yang selalu hijau tidak membuktikan apa pun
tentang basis data dev. Keduanya perlu disebut terpisah saat memverifikasi.

## Yang dikerjakan

- `migrate:fresh --seed` ke `sim_transmigrasi`. 9 tabel lama dibuang (nol data
  bisnis di dalamnya), 58 tabel Tahap 3 terbangun, `PermissionRoleSeeder` +
  `AdminAwalSeeder` jalan. 45 baris `sessions` ikut terbuang -- sesi login lokal
  yang sedang terbuka ter-logout, tidak ada konsekuensi lain.
- **`SIM_ADMIN_WAJIB_GANTI` (BARU)** pada `AdminAwalSeeder`. Default **`true`**:
  pemasangan di server dinas tetap patuh `rules.md` 14b poin 5 tanpa menyetel
  apa pun. `.env` lokal (gitignored) menyetelnya `false` + `SIM_ADMIN_USERNAME=admin`
  + `SIM_ADMIN_PASSWORD=admin`, atas permintaan pemilik proyek, supaya akun
  telusur tidak terlempar ke `/ganti-kata-sandi` tiap kali DB dibangun ulang.
- Pengecualian ini **hanya** menyentuh akun seed. Akun yang dibuat Admin lewat
  sistem tetap ditandai wajib-ganti oleh `PengaturanPenggunaController` (baris
  105 dan 167) -- tidak ada kode yang perlu diubah untuk itu.
- `.env.example` mendokumentasikan kelima kunci `SIM_ADMIN_*` sebagai komentar
  tanpa nilai aktif, beserta peringatan jangan diisi di server.

## Uji yang merah, dan mengapa itu benar

`tests/Database/AdminAwalSeederTest.php:25` mengunci `password_harus_diganti`
bernilai TRUE, lalu **merah** begitu flag ditambahkan -- sebab uji ikut membaca
`.env` mesin pengembang. Penjaganya bekerja sebagaimana mestinya.

Diperbaiki di ujinya, bukan dilonggarkan: `beforeEach` kini **membuang**
`SIM_ADMIN_WAJIB_GANTI` dari environment lebih dulu, sehingga yang dijaga adalah
perilaku BAWAAN -- apa yang terjadi di server yang tidak menyetel apa pun.
Ditambah satu uji baru yang mengunci arah sebaliknya (`=false` -> tidak wajib
ganti), supaya nilai bawaannya tidak dapat berubah diam-diam menjadi longgar.

> **Aturan:** uji tidak boleh mewarisi setelan `.env` mesin siapa pun. Nilai yang
> menentukan hasil wajib dinyatakan di dalam ujinya sendiri.

## Verifikasi

- `sim_transmigrasi`: **62 tabel** (58 bisnis/infra + `migrations`), `migrations`
  58 baris, `role` 5 (4 bawaan + 1 contoh non-bawaan, memang rancangan seeder),
  `permission` 97, `user` 1 (`admin`, `password_harus_diganti = 0`).
- `sim:banding-skema --lengkap` **NOL SELISIH**.
- **Login HTTP sungguhan** dengan `SIM_MASUK_OTOMATIS=false`: `admin`/`admin`
  -> 302 ke `/` (BUKAN ke `/ganti-kata-sandi`); `/`, `/transmigran`, `/pengguna`,
  `/audit-log` seluruhnya 200. Tanpa login: `/` dan `/transmigran` -> 302 ke
  `/login`, `/pengaduan-warga` tetap 200.
- `pest` **937 PASS / 7.910 assertions** (936 + 1 uji baru). Perlu
  `php -d memory_limit=1G`; batas 128 MB bawaan habis saat merender jejak galat.
- `pint --test` **26** (tak berubah; berkas tersunting bersih) ·
  `sim:tautan-statis` **14**.

## TIDAK disentuh

`app/Observers/`, `tests/Database/AuditLogOtomatisTest.php`, `app/Models/AuditLog.php`,
`app/Providers/AppServiceProvider.php` -- pekerjaan Task 3.6 yang belum di-commit.
Observernya ikut aktif saat seeding, jadi `audit_log` dapat terisi sendiri; itu
wajar, bukan bug. Tidak ada commit dibuat pada sesi ini.

---

# Tahap 3 · Sisa Tahap 3: Task 3.9 + 3.10 + 3.6 SELESAI (2026-09-03)

Pemilik proyek: "kerjakan semua sisa Tahap 3 selama tidak ada konflik".
Commit terpisah per task. Belum di-push. Ketiganya SELESAI -- Tahap 3 tuntas
(Task 3.7 dibatalkan, 3.8 dikerjakan berbarengan Model tiap Task 3.1).
Verifikasi akhir: Feature 733, Database 203, `sim:banding-skema` NOL SELISIH,
`sim:tautan-statis` 14, pint bersih.

## Task 3.9 -- Slug data master  [SELESAI]

- `app/Models/Concerns/BerslugOtomatis.php`: `creating` -> slug dari `nama`
  (`Str::slug`) bila kosong; unik (`-2`, `-3`, ...) diperiksa
  `withoutGlobalScopes()` (ikut lepas SoftDeletingScope -> baris terhapus lunak
  terhitung, dan cakupan data Poktan diabaikan); dipangkas 110 char (kolom
  `VARCHAR(120)`). `updating` -> `slug` dirty dikembalikan ke `getOriginal`
  (tak berubah selamanya, `rules.md` 4.0a poin 3). Slug yang sudah diisi
  pemanggil dihormati.
- Dipasang: `KawasanTransmigrasi`, `SatuanPermukiman`, `Komoditas`, `Poktan`
  (keempat tabel ber-kolom `slug`; semua `getRouteKeyName()='slug'` sejak
  Task 3.1).
- Catatan: keempat tabel juga UNIQUE `nama`, jadi tabrakan slug hanya dari
  nama berbeda yang meluruh sama ("Ubi Kayu" vs "Ubi-Kayu").
- 9 uji `tests/Database/SlugOtomatisTest.php`. Feature 733, Database 188.

## Task 3.10 -- Pembatasan laju per jenis akses  [SELESAI]

HASIL: `AppServiceProvider::daftarkanBatasLaju()` mendefinisikan 5 limiter
bernama; `config('sim.batas_laju.*')` (angka + flag `aktif`) dibaca DI DALAM
closure supaya uji bisa menyalakannya. `phpunit.xml` -> `SIM_BATAS_LAJU=false`
(uji penyapu rute tak terkena). `bootstrap/app.php` `then:` melampirkan
`throttle:` per rute: internal ber-`auth` -> `baca-internal` (GET, 120/mnt/akun)
atau `tulis-internal` (tulis, 40/mnt/akun); `template-impor`/`laporan.dokumen`/
`dokumen.tampilkan` -> `berkas-besar` (30/mnt). Publik di `routes/web.php`:
`lacak-pengaduan*` -> `lacak-publik` (10/mnt/IP), `pengaduan-warga.kirim` ->
`kirim-pengaduan` (3/jam/IP). Pesan 429 Indonesia menyebut jalan keluar.
PENTING: pakai `$rute->middleware()` bukan `gatherMiddleware()` di loop --
yang terakhir men-cache hasil sehingga throttle tak terbawa. 62 baca + 74
tulis + 3 besar + 3 publik. 7 uji `PembatasanLajuTest`. Login (5 gagal/mnt)
tetap di `LoginController` manual.

Rencana awal (arsip):
- `RateLimiter` di `AppServiceProvider::boot()` (atau `bootstrap/app.php`):
  - `bacaInternal` 120/mnt per `Auth::id()` (fallback IP).
  - `tulisInternal` 40/mnt per `Auth::id()`.
  - `lacakPublik` 10/mnt per IP.
  - `kirimPengaduan` 3/jam per IP (`rules.md` 10b 1d / 14c.2).
  - login sudah ditangani `LoginController` (RateLimiter manual) -- tidak
    disentuh.
- Terapkan di `bootstrap/app.php` `then:`: rute GET internal ->
  `throttle:bacaInternal`; rute tulis (POST/PUT/PATCH/DELETE) internal ->
  `throttle:tulisInternal`. KECUALIKAN rute ekspor massal + unggah template
  (`rules.md` 14c.3 poin 6) -> daftar nama rute dikecualikan / batas sendiri.
  Rute publik `lacak-pengaduan*` + `pengaduan-warga.kirim` di `routes/web.php`.
- Pesan 429 berbahasa Indonesia menyebut jalan keluar (`rules.md` 14c.3 poin 5)
  -> `RateLimiter::...->response(fn () => ...)`.
- Aset statis (CSS/JS/gambar) tak dihitung -- otomatis, di luar grup `web`.
- Uji `tests/Database/PembatasanLajuTest.php` (atau Feature): batas baca/tulis,
  publik, pengecualian ekspor, pesan Indonesia.

## Task 3.6 -- Audit log perubahan data otomatis  [SELESAI]

HASIL: `App\Observers\AuditLogObserver` (const `MODEL` = 32 kelas) dipasang
lewat perulangan `AppServiceProvider::daftarkanAuditOtomatis()` -- tanpa
menyunting model. `created`->Tambah, `updated`->Ubah (hanya kolom berubah,
`data_lama` = irisan `getOriginal()`), `deleted`->Hapus, `restored`->Pulihkan.
Dikecualikan: `password`, `remember_token`, `created_at`, `updated_at`,
`deleted_at`. `updated` tanpa kolom bermakna -> dilewati. `restore()` tak lagi
bikin baris "Ubah" hantu (deleted_at dikecualikan). TIDAK diobservasi:
`User`/`Role`/`Permission` (manual + konteks), `AuditLog`/`KodePemulihanSandi`/
`Berkas`/pivot. Feature 733, Database 203 (+8 `AuditLogOtomatisTest`),
`sim:banding-skema` NOL SELISIH. `AuditLog` model docstring diperbarui.

Rencana awal (arsip):
- `app/Observers/AuditLogObserver.php` -- observer terpusat, DIDAFTARKAN lewat
  loop di `AppServiceProvider::boot()` (tanpa menyunting 32 model -> kurangi
  permukaan konflik).
- Event: `created`->Tambah (`data_baru` = atribut bersih, `data_lama` null),
  `updated`->Ubah (`data_baru` = `getChanges()` bersih, `data_lama` =
  irisannya dari `getOriginal()`), `deleted`->Hapus (`data_lama` = original
  bersih, `data_baru` null; soft & force sama), `restored`->Pulihkan.
- DIKECUALIKAN dari catatan: `password`, `remember_token`, `created_at`,
  `updated_at`, `deleted_at` (`data-dictionary.md` 2.2 + supaya `restore()`
  yang memicu `updated` (deleted_at->null) TIDAK jadi baris "Ubah" hantu ->
  `restored` yang menanganinya).
- `updated` tanpa kolom berubah bermakna -> DILEWATI.
- `user_id` = `Auth::id()`; `ip_address`/`user_agent` dari `request()` bila ada.
- Model DIAUDIT (const `AuditLogObserver::MODEL`): seluruh data operasional +
  master wilayah/SP/pertanian + referensi. TIDAK: `User`/`Role`/`Permission`
  (sudah dicatat manual di controllernya dgn konteks -> hindari ganda),
  `AuditLog`, `KodePemulihanSandi`, `Berkas`, tabel pivot.
- `Login/Logout/Reset Kata Sandi/Nonaktifkan/Aktifkan/Ubah Izin Role` tetap
  manual di controller -- observer ini HANYA perubahan DATA.
- Uji `tests/Database/AuditLogOtomatisTest.php`: create->Tambah; update->Ubah
  hanya kolom berubah; `password` tak pernah masuk (uji lewat model lain yg
  tak diaudit? -> pakai model diaudit tanpa password, mis. Transmigran, +
  1 uji khusus User TIDAK memicu observer); soft delete->Hapus; restore->
  Pulihkan (tanpa "Ubah" hantu); pelaku terekam saat `actingAs`; model tak
  terdaftar (mis. `Berkas`) tak menghasilkan baris.

---

# Tahap 3 · Task 3.11 - Pemulihan kata sandi lewat kode verifikasi SELESAI (2026-09-03)

## HASIL (2026-09-03)

Commit tunggal. Belum di-push. Verifikasi: pest Feature 733 PASS, pest
tests/Database 177 PASS (+12 `PemulihanSandiTest`), pint bersih,
`sim:banding-skema` NOL SELISIH, `sim:tautan-statis` 14.

- **`app/Models/KodePemulihanSandi.php`** -- `const UPDATED_AT = null` (tabel
  tanpa `updated_at`), scope `masihBerlaku()` (belum dipakai, belum
  kedaluwarsa, `percobaan < 5`), relasi `pengguna()`.
- **`app/Mail/KodePemulihanSandiMail.php`** + `resources/views/emails/
  kode-pemulihan-sandi.blade.php` -- Mailable PERTAMA di proyek. Teks polos,
  tanpa gambar/tautan lacak. `$kode` + `$menitBerlaku` publik.
- **`app/Http/Controllers/Auth/PemulihanSandiController.php`**:
  `tampilPermintaan`/`kirimKode`/`tampilVerifikasi`/`aturUlang`. `kirimKode`:
  cari User AKTIF by email/username; user null -> tetap `Hash::make` (ratakan
  waktu) + redirect generik; batas 3/jam via hitung `created_at`; batalkan
  kode lama (`kedaluwarsa_pada = now()`); buat kode `random_int(0,999999)`
  6 digit, simpan `Hash::make`, kedaluwarsa +15 mnt; kirim mail (try/catch +
  `Log::error`); `session('pemulihan_user_id')`. `aturUlang`: validasi kode
  (`digits:6`) + `password_baru` (`ValidationRules::password(konfirmasi:false)`)
  + `password_baru_konfirmasi` (`same:`); ambil user dari sesi; kode
  `masihBerlaku()` terbaru; `Hash::check` gagal -> `increment('percobaan')` +
  galat generik; sukses -> `dipakai_pada=now()`, set password (TANPA
  `password_harus_diganti`), audit `ResetKataSandi` jalur `Kode verifikasi`,
  `session forget`, `Auth::logout`, redirect `login`.
- **`routes/web.php`** -- 4 closure dummy -> controller.
- **`app/Support/ValidationRules.php`** -- `password(bool $wajib = true,
  bool $konfirmasi = true)`; `confirmed` hanya bila `$konfirmasi`. (Pint ikut
  merapikan 5 spasi concat lama di berkas yang sama.)
- **`app/Http/Middleware/UppercaseInput.php`** -- `password_baru_konfirmasi`
  masuk `$kecualikan` (kalau tidak, term konfirmasi jadi HURUF BESAR sedang
  `password_baru` tidak -> `same:` selalu gagal).
- **`agents/rules.md` 14b poin 13** -- dipecah: jalur Admin/artisan menyetel
  `password_harus_diganti`, jalur kode verifikasi tidak.

### DITUNDA
- Ratakan waktu balas lebih ketat (sleep konstan) -- bcrypt cukup untuk kini.

### SUSULAN (2026-09-03, commit terpisah)
- **Kredensial akun ke surel (Task 3.5 poin 3a) SELESAI.** `KredensialAkunMail`
  (+ templat `emails/kredensial-akun`) dikirim `PengaturanPenggunaController::
  simpan` (`akunBaru=true`) dan `setelSandi` (`akunBaru=false`), dibungkus
  try/catch + `Log::error`. 2 uji `Mail::fake` di `PengaturanPenggunaTest`.
  `AdminAwalSeeder` tetap cetak-terminal saja (bootstrap, mail belum tentu
  siap saat deploy perdana).

---

# Tahap 3 · Task 3.11 (RENCANA LAMA) - lihat HASIL di atas

Rencana ditulis sebelum kode disentuh (`rules.md` 20b poin 12). Pemilik proyek
menyetel mail server Mailjet di `.env` (`MAIL_MAILER=smtp`, host
`in-v3.mailjet.com`) dan minta lanjut task Tahap 3.

## Keputusan pemilik proyek

- **`password_harus_diganti` TIDAK diset** pada reset lewat kode verifikasi
  (petugas sudah memilih sandi final di form). Hanya jalur Admin (sandi
  sementara) yang menyetelnya. `rules.md` 14b poin 13 diperbarui agar eksplisit
  membedakan kedua jalur.

## Penyisiran skenario (`rules.md` 20a)

- **Privasi:** `POST /lupa-kata-sandi` balas SAMA baik akun ada/tidak (poin 9) --
  redirect identik + kerja bcrypt tetap dijalankan walau akun tak ada (ratakan
  waktu). Kode disimpan sebagai SIDIK (`kode_hash`), bukan angkanya. Tabel tak
  simpan email tujuan. Kunci rate-limit = sidik kredensial, tak membocorkan.
- **Siklus hidup:** akun nonaktif -> kode tak diterbitkan; bila akun dinonaktif
  antara terbit & pakai -> `aturUlang` tolak (perlakuan = kode tak sah). Akun
  soft-deleted -> tak ditemukan (scope bawaan). Kode lama DIBATALKAN saat kode
  baru diminta (`kedaluwarsa_pada = now()`).
- **Kejujuran angka:** audit `Reset Kata Sandi` jalur `Kode verifikasi` --
  beda dari jalur `Admin`/`Artisan darurat`.
- **Alur kerja:** 4 closure dummy di `routes/web.php` -> controller. Petugas
  salah ketik kredensial -> minta lagi (maks 3/jam); kode lama hangus.
- **Teknis:** rute `guest` GET (`/lupa-kata-sandi`, `/verifikasi-kode`) tetap
  terbit statis. Uji Feature "membuat rute tulis pemulihan" tetap lolos (gagal
  validasi -> redirect, sebelum sentuh DB). Perilaku nyata -> tests/Database.
  Mailable pertama di proyek; kirim dibungkus try/catch + `Log::error` supaya
  gangguan SMTP tak jadi 500 yang membocorkan.

## Rencana

### C1 -- Model + Mailable + templat
- `app/Models/KodePemulihanSandi.php`: `$table='kode_pemulihan_sandi'`,
  `$primaryKey='id_kode_pemulihan'`, `$fillable`, casts (`kedaluwarsa_pada`,
  `dipakai_pada` -> datetime; `percobaan` -> int). `CREATED_AT='created_at'`,
  `UPDATED_AT=null` (tabel tanpa `updated_at`). Relasi `pengguna()` belongsTo
  User. Scope `masihBerlaku()` (`dipakai_pada` null, `kedaluwarsa_pada` > now,
  `percobaan` < 5).
- `app/Mail/KodePemulihanSandiMail.php` + `resources/views/emails/
  kode-pemulihan-sandi.blade.php`: kode 6 digit, berlaku 15 menit, "abaikan
  bila bukan Anda", sebut jalur Admin. Teks Indonesia.

### C2 -- Controller + rute
- `app/Http/Controllers/Auth/PemulihanSandiController.php`:
  - `tampilPermintaan()` -> view `lupa-kata-sandi`.
  - `kirimKode(Request)`: validasi `kredensial` (required). Cari User aktif by
    email/username. Rate-limit 3/jam: hitung baris `kode_pemulihan_sandi`
    `created_at > now()->subHour()` utk user itu; >= 3 -> lewati pembuatan
    (tetap redirect generik). Bila boleh: batalkan kode lama
    (`kedaluwarsa_pada = now()` utk yang belum dipakai), buat kode
    `str_pad(random_int(0, 999999), 6)`, simpan `Hash::make($kode)`,
    `kedaluwarsa_pada = now()->addMinutes(15)`. Kirim `KodePemulihanSandiMail`
    (try/catch). Simpan `session('pemulihan_user_id')`. Bila user null: tetap
    `Hash::make(kode buang)` (ratakan waktu). SELALU
    `redirect()->route('verifikasi-kode')`.
  - `tampilVerifikasi()` -> view `verifikasi-kode`.
  - `aturUlang(Request)`: validasi `kode` (`digits:6`), `password_baru`
    (`ValidationRules::password(konfirmasi:false)`), `password_baru_konfirmasi`
    (`same:password_baru`). Ambil `session('pemulihan_user_id')`; null ->
    galat generik. Ambil kode `masihBerlaku()` terbaru user; tak ada -> galat
    generik "Kode salah atau sudah kedaluwarsa". `Hash::check` -> gagal:
    `increment('percobaan')`, galat generik. Sukses: `dipakai_pada = now()`,
    `User::update(['password' => $baru])` (TANPA `password_harus_diganti`),
    audit `ResetKataSandi` `user_id` = pemilik, `data_baru['jalur'] =
    'Kode verifikasi'`, `session()->forget`, `Auth::logout` (jaga2),
    redirect `login` + sukses.
- `routes/web.php`: 4 closure -> `[PemulihanSandiController::class, ...]`.
- `ValidationRules::password(bool $wajib = true, bool $konfirmasi = true)` --
  param baru; `confirmed` hanya bila `$konfirmasi`.

### C3 -- Uji `tests/Database/PemulihanSandiTest.php` (~14)
- balasan identik akun ada/tidak; kode tersimpan sbg hash bukan angka;
  reset sukses -> sandi berubah, `password_harus_diganti` TETAP false, audit
  jalur "Kode verifikasi"; kode salah -> `percobaan++`, sandi tak berubah;
  kode hangus stlh 5 percobaan; kode kedaluwarsa ditolak; kode sekali pakai
  (`dipakai_pada`); minta kode baru -> kode lama batal; batas 3/jam;
  akun nonaktif -> tak ada kode terbit; `Mail::fake` -> mailable terkirim ke
  email user dgn kode benar.

### C4 -- docs: `rules.md` 14b poin 13 (bedakan jalur), `tasklist` 3.11 [✓],
  `session-notes` HASIL, `data-dictionary` bila perlu.

### DITUNDA
- Kirim kredensial akun baru / reset Admin ke surel (Task 3.5 tunda) -- Mailable
  kedua, bisa menyusul karena infra mail sudah tegak di C1.
- Ratakan waktu balas lebih ketat (konstanta sleep) -- cukup bcrypt utk kini.

---

# Tahap 3 · Task 3.4b + 3.5 + 3.5b - Manajemen pengguna SELESAI (2026-09-03)

Rencana ditulis sebelum kode disentuh (`rules.md` 20b poin 12). Tiga task
dikerjakan sekaligus (izin pemilik proyek: "beberapa task sekaligus selama
tidak ada konflik"). Commit terpisah per task. Belum di-push.

## HASIL (2026-09-03)

Verifikasi: pest Feature 733 PASS, pest tests/Database 165 PASS, pint bersih
(berkas ketiga task saja), `sim:banding-skema` NOL SELISIH, `sim:tautan-statis`
14.

- **3.4b** commit `71f8e89`. `MenuHelper::bolehLihat()` ->
  `Auth::user()?->punyaIzin($izin) ?? false`. 2 uji Feature (tanpa role -> menu
  cuma Panduan/Tentang; `semuaIzin` -> semua kelompok).
- **3.5** `PengaturanPenggunaController` (6 aksi; `index()` baca DummyData +
  filter, tulisan ke `user`). `simpan`: `Str::password(14, symbols:false)`,
  hash, `password_harus_diganti=true`, `is_aktif=true`, username SEMENTARA
  `petugas.xxxxxxxx` (skema `user.username` NOT NULL -> tak bisa null),
  flash `kredensial_baru` sekali. Role `Per SP` -> `satuan_permukiman[]` wajib
  (`min:1`, `exists`) -> `sync`. `perbarui`: tak sentuh password; role bukan
  `Per SP` -> lepas semua penugasan SP. `setelSandi`: audit `ResetKataSandi`
  `data_baru['jalur']='Admin'`. `nonaktifkan`: `abort_if` Admin aktif terakhir
  (role `is_terkunci`, hitung di server) -> 422. TANPA `hapus` (modul
  `pengguna` = L T U saja, `DummyData::daftarIzin`). 6 closure `pengguna.*` di
  `routes/internal.php` -> controller. `AdminAwalSeeder` (akun Admin pertama,
  idempoten; kredensial dari `SIM_ADMIN_*` env atau acak+cetak) dipanggil
  `DatabaseSeeder` sesudah `PermissionRoleSeeder`. Uji Feature "membuat rute
  tulis pengguna" DIHAPUS (jadi controller DB). 14 uji Database.
- **3.5b** `app/Console/Commands/PulihkanAdmin.php` sig
  `sim:pulihkan-admin {identitas?}`. Cari akun role `is_terkunci`; tanpa arg &
  Admin tunggal -> akun itu; > 1 -> minta arg (username/email) + tabel;
  0 -> gagal. Reset `Str::password(16)`, `password_harus_diganti=true`, cetak
  sandi ke terminal, audit `ResetKataSandi` `user_id=null`
  `data_baru['jalur']='Artisan darurat'`. 5 uji Database.

### DITUNDA (dicatat, bukan lupa)
- **Username self-service saat masuk pertama** (`rules.md` 14b poin 5): form
  `ganti-kata-sandi` belum punya kolom username; `GantiKataSandiController`
  belum memprosesnya; pemeriksaan ketersediaan saat diketik (poin 5a) belum
  ada. Sampai itu, username sementara `petugas.xxxxxxxx` mengisi kolom NOT
  NULL. Perlu: kolom di view (bersyarat: hanya bila `username` masih pola
  sementara), validasi `ValidationRules::username()` di controller, endpoint
  cek ketersediaan. Cocok digabung Task 3.11 atau task UI tersendiri.
- **Kirim kredensial ke surel** petugas / Admin awal (`rules.md` 14b poin 3a):
  butuh Mailable + templat + `Mail::to()`. `MAIL_MAILER=log` sudah siap.
- **Peralihan `pengguna.index` ke Eloquent**: Tahap 4 (seluruh modul).

## Rencana

### Task 3.4b -- Sidebar dinamis berbasis izin
- `MenuHelper::bolehLihat($izin)`: `null` -> true; selain itu
  `Auth::user()?->punyaIzin($izin) ?? false`. Ganti ponytail lama.
- Machinery `getMenuGroups()` (saring submenu, buang kelompok kosong) SUDAH ada
  sejak Tahap 2 -- hanya `bolehLihat()` yang perlu disambungkan.
- Uji `tests/Feature`: `actingAs(new User([]))` (tanpa role -> `punyaIzin` false
  untuk semua) -> menu menyusut jadi item ber-`permission = null` saja
  (Panduan, Tentang); pengguna semu `semuaIzin` -> banyak kelompok. Tanpa DB.

### Task 3.5 -- CRUD manajemen pengguna (backend)
- `app/Http/Controllers/PengaturanPenggunaController.php`: `index()` masih baca
  `DummyData` (peralihan tampilan -> Tahap 4, pola sama `PengaturanRoleController`);
  `simpan/perbarui/setelSandi/nonaktifkan/aktifkan` menulis tabel `user` nyata.
  - `simpan`: validasi `nama` (`ValidationRules::nama`), `email`
    (`ValidationRules::email`), `role_id` (`exists:role,id_role`), `jabatan`
    nullable, `telepon` (`ValidationRules::telepon`). TANPA username/password
    (`rules.md` 14b poin 3/5). Bangkitkan `Str::password(14)`. Simpan hash,
    `password_harus_diganti = true`, `is_aktif = true`, `username = null`. Role
    `Per SP` -> wajib `satuan_permukiman[]` (`exists`) -> `attach`. Audit
    `Tambah`. Flash `kredensial_baru` (tampil sekali).
  - `perbarui`: `nama/email/role_id/jabatan/telepon`; TAK PERNAH password
    (poin 14). Kelola penugasan SP bila role Per SP. Audit `Ubah` + diff.
  - `setelSandi`: bangkitkan sandi sementara, timpa hash,
    `password_harus_diganti = true`, audit `ResetKataSandi`
    `data_baru['jalur'] = 'Admin'` (poin 13/15). Flash sandi sekali.
  - `nonaktifkan`/`aktifkan`: `is_aktif` false/true, audit `NonaktifkanAkun`/
    `AktifkanAkun`. `nonaktifkan` -> `abort_if` sasaran Admin aktif terakhir
    (poin 16); pemeriksaan DI SERVER.
  - TANPA hapus: `rules.md` 5.1 "Manajemen pengguna | L T U" -- tak ada Hapus.
    Rute `pengguna.hapus` tetap `abort(405)`.
- Rewire 6 closure `pengguna.*` di `routes/internal.php` -> controller.
- Seeder `AdminAwalSeeder`: buat 1 akun Admin bila belum ada Admin mana pun
  (`password_harus_diganti = true`, kredensial dari `config`/tetap
  terdokumentasi). `DatabaseSeeder` memanggilnya sesudah `PermissionRoleSeeder`.
- Uji `tests/Database/PengaturanPenggunaTest.php` (~12): sandi sementara +
  flag; Per SP wajib SP; reset sandi; nonaktif/aktif; lindungan Admin terakhir;
  audit tercatat. Hapus uji Feature "membuat rute tulis pengguna" (jadi
  controller DB, seperti role Task 3.3 C5).
- **DITUNDA:** kirim kredensial ke surel (`rules.md` 14b poin 3a) -- butuh
  Mailable + template, task tersendiri. TODO ditandai di controller.

### Task 3.5b -- Perintah artisan pemulihan darurat Admin
- `app/Console/Commands/PulihkanAdmin.php` sig `sim:pulihkan-admin {identitas?}`
  (`rules.md` 14b poin 17). Cari akun Admin (arg username/email; bila satu Admin
  dan tanpa arg -> akun itu; bila banyak dan tanpa arg -> minta arg). Bangkitkan
  sandi sementara, timpa hash, `password_harus_diganti = true`, cetak ke
  terminal. Audit `ResetKataSandi` `user_id = null` (sistem)
  `data_baru['jalur'] = 'Artisan darurat'`.
- Uji `tests/Database/PulihkanAdminTest.php`: reset + flag + audit; tolak bila
  bukan Admin; minta arg bila Admin > 1.

---

# Tahap 3 · Task 3.4 - Cakupan data (global scope) SELESAI (2026-09-03)

Rencana ditulis sebelum kode disentuh (`rules.md` 20b poin 12). Rancangan
penegakan MENGIKAT: `rules.md` 5.0b-1 (ditetapkan 2026-09-02).

## Fakta

- `CakupanData` enum: `Semua` / `Per SP` / `Per Bidang`. Role menyimpannya
  (`Role::$casts`). `User::role` + `User::satuanPermukiman()` (belongsToMany
  `user_satuan_permukiman`) sudah ada (Task 3.1).
- **10 model ber-`satuan_permukiman_id` langsung:** `Transmigran`, `Rumah`,
  `Lahan`, `Poktan`, `Infrastruktur`, `Pengaduan`, `InventarisSp`, `FasilitasSp`,
  `PenilaianSp`, `RuteAksesibilitasSp`. (`rules.md` 5.0b-1 poin 9 sebut 13 tabel
  -- 3 sisanya pivot tanpa model: `fasilitas_sp_cakupan`, `infrastruktur_sp`,
  `user_satuan_permukiman`.)
- **8 model mewarisi SP lewat induk:** `AnggotaKeluarga`/`RiwayatKepalaKeluarga`
  (via `transmigran`), `RiwayatPenghunian` (via `rumah`), `AnggotaPoktan`/
  `Penanaman` (via `poktan`), `HasilPanen` (via `penanaman`->`poktan`),
  `AlsintanDistribusi`/`SaprotanDistribusi` (via `poktan`).
- **TIDAK disaring:** `alsintan`/`saprotan` induk (deskripsi benda),
  data referensi (wilayah, kawasan, satuan, komoditas, `Referensi`,
  `ParameterPenilaianSp`, `StatusKondisiSp`, `Role`, `Permission`),
  `SatuanPermukiman` sendiri (poin 9b).
- Tampilan masih `DummyData` -> global scope **belum berefek di layar** sampai
  Tahap 4. Task 3.4 = pasang mesin + uji Eloquent.
- Belum ada global scope mana pun di `app/`.

## Rencana

### C1 -- `CakupanDataSp` scope + 10 model pemilik langsung
- `app/Models/Scopes/CakupanDataSp.php` implements `Scope`. `apply()`:
  - `Auth::user()` null (artisan/seeder/job) -> **tak menyaring** TAPI hanya
    lewat jalur eksplisit; default null-user = NOL baris? `rules.md` 5.0b-1
    poin 15: tanpa user "wajib LENGKAP tetapi hanya lewat pemanggilan yang
    menyatakannya sendiri". Praktik: null user -> scope tak menyaring (data
    lengkap) sebab konteks non-HTTP; permintaan HTTP selalu ber-user (`auth`).
    Uji tanpa `actingAs` = konteks non-HTTP -> lengkap.
  - `cakupan_data === Semua` -> tak menyaring.
  - `cakupan_data === PerSp` -> `whereIn('<tabel>.satuan_permukiman_id',
    $user->satuanPermukiman->pluck('id'))`. **Daftar kosong -> `whereRaw('1=0')`**
    (NOL baris, BUKAN bypass -- poin 10).
  - `cakupan_data === PerBidang` -> tak menyaring di scope umum (hanya
    `pengaduan`, ditangani C2).
- Pasang lewat atribut `#[ScopedBy([CakupanDataSp::class])]` pada 10 model.
- Helper `Model::tanpaCakupan()` / macro `withoutGlobalScope` untuk jalur
  eksplisit (laporan lintas-SP, ekspor -- poin 15).

### C2 -- model turunan + `Per Bidang` pengaduan
- 8 model turunan: scope tipis `whereHas('<induk>')` (delegasi -- poin 9,
  "tidak diulang"). `HasilPanen` -> `whereHas('penanaman')`.
- `Pengaduan`: scope terpisah/tambahan -> `PerBidang` = `where('bidang',
  BidangPengaduan::dariDinas($user))` ATAU union "bidang null" (poin 6b:
  filter sediakan "Belum ditentukan"). Cek `BidangPengaduan` enum + peta dinas.

### C3 -- uji `tests/Database/CakupanDataTest.php`
- `Semua` -> lihat semua; `Per SP` + 1 penugasan -> hanya SP itu; `Per SP`
  tanpa penugasan -> 0 baris; turunan ikut tersaring; referensi tak tersaring;
  `alsintan`/`saprotan` induk tak tersaring; `tanpaCakupan()` -> lengkap;
  paginasi menghitung setelah saring; null user (non-HTTP) -> lengkap.

### C4 -- docs (`tasklist` 3.4 [✓], `session-notes` HASIL)

### DITUNDA
- 404 untuk baris tak berhak di rute detail -> butuh rute pakai Eloquent
  (Tahap 4). Sekarang scope cukup bikin `find()` mengembalikan null -> caller
  `findOrFail` -> 404 otomatis saat Tahap 4.
- `MenuHelper` (3.4b), akun `Per SP` seeder (3.5).

## HASIL Task 3.4 (2026-09-03)

Commit tunggal `Tahap 3 Task 3.4: global scope cakupan data pada 19 model`.
Belum di-push. Verifikasi: pest Feature 732 PASS, pest tests/Database 146 PASS
(+10 `CakupanDataTest`), pint bersih (berkas Task 3.4 saja), `sim:banding-skema`
NOL SELISIH, `sim:tautan-statis` 14.

- **`app/Models/Scopes/CakupanDataSp.php`** -- `implements Scope`. `apply()`:
  `penggunaWajibDisaring()` null (tamu/artisan/seeder/job, tanpa role, atau
  role `Semua`) -> tak menyaring. `PerSp` -> `whereIn('<tabel>.
  satuan_permukiman_id', spDitugaskan())`; daftar kosong -> `whereRaw('1 = 0')`
  (NOL baris, poin 10). `PerBidang` + tabel `pengaduan` -> `where('bidang',
  'Pertanian')` (poin 14, `rules.md` 5.0b poin 6a); model lain berjangkauan
  penuh. Helper statis `penggunaWajibDisaring()` / `spDitugaskan()` /
  `bidangDinas()` dipakai bersama trait.
- **`app/Models/Concerns/DisaringLewatInduk.php`** -- trait; `bootX()` daftar
  global scope `cakupanViaInduk` = `whereHas(static::$indukCakupan)` bila
  `penggunaWajibDisaring() !== null`. Delegasi murni -- scope induk yang
  menyaring SP.
- **10 model pemilik langsung** dapat `#[ScopedBy([CakupanDataSp::class])]`:
  `Transmigran`, `Rumah`, `Lahan`, `Poktan`, `Infrastruktur`, `Pengaduan`,
  `InventarisSp`, `FasilitasSp`, `PenilaianSp`, `RuteAksesibilitasSp`.
- **9 model turunan** dapat `use DisaringLewatInduk` + `$indukCakupan`:
  `AnggotaKeluarga`/`RiwayatKepalaKeluarga` (`transmigran`), `RiwayatPenghunian`
  (`rumah`), `AnggotaPoktan`/`Penanaman`/`AlsintanDistribusi`/`SaprotanDistribusi`
  (`poktan`), `HasilPanen` (`penanaman`), `PenangananPengaduan` (`pengaduan`).
  `PenangananPengaduan` di luar rencana awal -- Domain 9 masuk lewat kerja
  paralel setelah rencana ditulis; anak `pengaduan` jadi ikut disaring.
- **`tests/Database/CakupanDataTest.php`** (10 uji): tamu non-HTTP tak
  menyaring; `Semua` lihat semua; `Per SP` 1 penugasan -> hanya SP itu;
  `Per SP` tanpa penugasan -> 0 baris; turunan ikut tersaring lewat induk;
  referensi (`SatuanPermukiman`) tak tersaring; `withoutGlobalScope` melewati;
  `count()`/`paginate()->total()` menghitung setelah saring; `Per Bidang` hanya
  pengaduan `Pertanian`; `Per Bidang` penuh pada model non-pengaduan.

### Tertunda sesudah 3.4
- 404 baris tak berhak di rute detail -> Tahap 4 (rute pakai Eloquent
  `findOrFail`).
- `MenuHelper` filter menu per izin -> Task 3.4b.
- Seeder akun `Per SP` + penugasan SP awal -> Task 3.5.
- `UppercaseInput` tak berjalan di uji (hanya middleware HTTP) -> assertion
  data uji pakai teks apa adanya.

---

# Tahap 3 · Task 3.3 - RBAC dinamis SELESAI (2026-09-03)

Rencana ditulis sebelum kode disentuh (`rules.md` 20b poin 12).

## HASIL Task 3.3 (2026-09-03)

Commit `1f2c024`(C1) `665b09f`(C2) `0592738`(C3) `ab05534`(C4) `a7b02d5`(C5).
Pemilik proyek pilih **"Penuh sekarang"** -- penegakan izin pada seluruh rute.

- **`PermissionRoleSeeder`** (C1): 95 `permission` + 5 `role` + pivot dari
  `DummyData::daftarIzin()/izinRole()/role()`. Idempoten. `DatabaseSeeder`
  memanggilnya. Pivot: Admin 95, Dinas Trans 47, Dinas Tani 44, Operator SP 49
  (tanpa `penanganan_pengaduan`), Pendamping 16.
- **`User::punyaIzin()`/`punyaAksi()`** (C2): role aktif memegang izin ->
  true; role non-aktif mencabut semua; properti publik `semuaIzin` (default
  false) menjawab true lebih dulu -- dipakai bypass `MasukOtomatisLokal` +
  `beforeEach` uji Feature (pengguna semu tak dipersist, tanpa role).
  `AppServiceProvider` `Gate::before` -> `@can('x.ubah')`, `$user->can(...)`.
- **`EnsureIzin` (alias `izin`)** (C3): `izin:transmigran,ubah` menuntut BAIK
  `transmigran.lihat` MAUPUN `transmigran.ubah` (prasyarat `lihat`,
  data-dictionary 13.3.4). Tolak **403** (kewenangan aksi; cakupan data 404 =
  Task 3.4).
- **`PetaIzinRute`** (C4): peta terpusat nama-rute -> "modul,aksi" (125 rute)
  + daftar pengecualian sengaja (profil sendiri, ganti-kata-sandi, tentang/
  panduan, template-impor [stub], dokumen.tampilkan [cek dinamis di
  controller], dev). `bootstrap/app.php`
  `then:` melampirkan `izin:` dgn iterasi objek Route (bukan `getByName` --
  `->name()` dipanggil setelah rute terdaftar). `DokumenController` cek
  `punyaAksi({modul}, 'lihat')` 403. Setiap rute ber-`auth` kini punya `izin:`
  ATAU ada di pengecualian (2 redirect 301 tak bernama -> rute ber-izin -> 403).
- **`PengaturanRoleController`** (C5): `role.simpan/perbarui/hapus` menulis
  `role`+`role_permission` nyata (`index()` masih DummyData -> Tahap 4). Tolak
  nama duplikat/pendek, aksi-tanpa-lihat, sunting role terkunci (403), hapus
  bawaan (403)/dipakai-akun (422)/tanpa-alasan. Audit `Ubah Izin Role`/`Hapus`.
- **FIX `UppercaseInput`**: kecualikan `cakupan_data` + subpohon `izin`
  (middleware meng-uppercase `'lihat'`->`'LIHAT'`, `'Per SP'`->`'PER SP'`).
  `ubahRekursif` kini hormati pengecualian pada kunci array.

**Verifikasi:** `pest` Feature **732 PASS** · `pest tests/Database` **136 PASS**
(RbacSeeder 6, PunyaIzin 6, IzinRute 5, IzinPenegakanRute 8, PengaturanRole 10,
+ AutentikasiTest disesuaikan) · `pint --test` **28** (turun dari 30) ·
`sim:tautan-statis` 14 · `sim:banding-skema --lengkap` NOL SELISIH.

**C7 (2026-09-03):** CMS dapat kewenangan sendiri (`cms.lihat`/`cms.ubah`),
dipegang Admin + Dinas Transmigrasi. Katalog izin 95 -> **97** (28 fitur);
`data-dictionary.md` 13.1/13.2 + `rules.md` 5.1 disesuaikan. Semua rute
internal kini benar-benar ber-`izin`.

**DITUNDA:** `MenuHelper` filter izin -> Task 3.4b · cakupan data (global
scope, 404) -> Task 3.4 · peralihan view non-role ke Eloquent -> Tahap 4 ·
`migrate:fresh`+`db:seed` ke `sim_transmigrasi` dev -> saat pemilik siap
(bypass `semuaIzin` bikin RBAC jalan lokal tanpa itu).

---

## Konteks & fakta eksplorasi

- **Model siap:** `Role` (pivot `permissions()`), `Permission` (`nama` unik e.g.
  `transmigran.lihat`, `modul`, `aksi` enum lihat/tambah/ubah/hapus, `label`,
  `urutan`), pivot `role_permission`. Semua dari Task 3.1.
- **Data sumber ada di `DummyData`** (dari Task 2.27): `daftarIzin()` ->
  **95 permission** (8 modul-kelompok, aksi per modul bervariasi -- `export`
  dicabut 2026-08-17), `izinRole(int)` -> peta izin per role (id 1-5),
  `role()` -> 4 bawaan (Admin id1 terkunci, Dinas Transmigrasi id2, Dinas
  Pertanian id3 `PerBidang`, Operator SP id4 `PerSp`) + 1 non-bawaan
  (Pendamping Lapangan id5). Catatan: `rules.md` 5.0a tabel bilang Dinas
  Pertanian `Semua` tapi 5.0b.6a + `DummyData` bilang `PerBidang` -- ikut
  `DummyData` (sudah direkonsiliasi Task 2.27).
- `DatabaseSeeder::run()` kosong, menunggu Task 3.3 (role+izin) & 3.5 (admin).
- Rute stub: `pengaturan.role` (GET, baca `DummyData::role()`), `role.simpan`/
  `role.perbarui`/`role.hapus` (closure kosong di `routes/internal.php`).
- TODO penanda: `MenuHelper.php:380` (`// Ganti dengan auth()->user()->punyaIzin`),
  `DokumenController.php:46` (`// Ganti dengan Gate::authorize`).
- **Pengguna semu** (bypass `MasukOtomatisLokal` + `beforeEach` uji Feature) =
  `new User(...)` tak dipersist, tanpa `role`. `punyaIzin()` WAJIB menangani
  ini -> flag `semuaIzin` (konstruksi dev/uji, bukan role nyata).

## Rencana (menunggu keputusan cakupan penegakan)

### A. Seeder `PermissionRoleSeeder` (dipanggil `DatabaseSeeder`)
- Tanam **95 `permission`** dari `DummyData::daftarIzin()` (nama = `<modul>.<aksi>`,
  `label`, `urutan` dari urutan daftar).
- Tanam **5 `role`** dari `DummyData::role()` (`is_bawaan`/`is_terkunci`/`cakupan_data`).
- Pasang pivot `role_permission` dari `DummyData::izinRole($id)`.
- Idempoten (`updateOrCreate` / `sync`) -- boleh dijalankan ulang.

### B. `User::punyaIzin(string $izin): bool` + `punyaAksi(string $modul, string $aksi)`
- `$this->semuaIzin === true` -> true (pengguna semu dev/uji).
- else `$this->role?->permissions->contains('nama', $izin) ?? false`.
- Deklarasi `public bool $semuaIzin = false;` pada model `User`.
- Eager-load `role.permissions` sekali per request (hindari N+1).

### C. Blade `@can` / Gate
- Daftarkan Gate dinamis di `AppServiceProvider::boot()`:
  `Gate::before(fn (User $u, string $izin) => $u->punyaIzin($izin) ?: null)`.
- `@can('transmigran.ubah')` langsung jalan di Blade.

### D. Middleware `izin` (`EnsureIzin`)
- Alias `izin` di `bootstrap/app.php`. Param: `izin:transmigran,ubah`.
- **`lihat` sebagai prasyarat** (`tasklist` 3.3): `izin:transmigran,ubah`
  otomatis juga menuntut `transmigran.lihat`.
- Tamu izin ditolak -> **403** (kewenangan aksi, `rules.md` 5.0b-1 poin 11 --
  beda dari cakupan data yang 404).

### E. Backend role CRUD (`role.simpan`/`perbarui`/`hapus`)
- Controller `PengaturanRoleController`. `simpan`/`perbarui`: validasi +
  `sync` pivot izin; tolak `is_terkunci` (`rules.md` 5.0a); audit `Ubah Izin Role`.
- `hapus`: tolak `is_bawaan` ATAU masih dipakai akun (`rules.md` 5.0c 8-9);
  alasan ke audit log. **Modul ini beralih baca Eloquent** (pengecualian
  terarah dari "Tahap 4 yang mengalihkan") -- `pengaturan.role` view baca
  `Role::with('permissions')` bukan `DummyData::role()`.

### F. Bypass dev/uji
- `MasukOtomatisLokal`: `$dev->semuaIzin = true`.
- `tests/Pest.php` `beforeEach`: `$user->semuaIzin = true`.

### G. Uji (`tests/Database/`, MySQL nyata)
- `RbacSeederTest`: 95 permission, 5 role, pivot Admin=95/Operator SP sesuai
  `izinRole(4)`, idempoten.
- `PunyaIzinTest`: role dgn izin -> true; tanpa -> false; `semuaIzin` -> true;
  tanpa role -> false.
- `IzinMiddlewareTest` / route: user tanpa `x.hapus` -> DELETE 403; dgn -> lolos;
  `lihat` prasyarat.
- `PengaturanRoleTest`: simpan/sync izin; tolak role terkunci; tolak hapus bawaan.

### CAKUPAN PENEGAKAN RUTE -- keputusan pemilik proyek
- **Opsi A (mekanik dulu):** lampirkan `izin:...` HANYA ke rute `role.*` +
  `pengguna.*` (Task 3.5 butuh). ~130 rute lain menyusul Task 3.3c.
- **Opsi B (penuh):** lampirkan `izin:modul,aksi` ke seluruh ~137 rute internal
  di `routes/internal.php` sekaligus + sesuaikan uji.

### DITUNDA (bukan 3.3)
- `MenuHelper` filter izin -> Task 3.4b. Cakupan data (global scope) -> Task 3.4.
- Peralihan view non-role ke Eloquent -> Tahap 4.
- `migrate:fresh` + `db:seed` ke `sim_transmigrasi` dev -> saat pemilik siap
  (bypass `semuaIzin` bikin RBAC jalan lokal tanpa itu).

---

# Tahap 3 · Task 3.2b - Penegakan `auth` + migrasi uji + bypass lokal SELESAI (2026-09-03)

Rencana ditulis sebelum kode disentuh (`rules.md` 20b poin 12). Rencana lengkap:
`.claude/plans/logical-whistling-salamander.md`.

## HASIL Task 3.2b (2026-09-03)

Commit `e5c0fc0` (C1), `40e487a` (C2-C4). **Rute internal kini WAJIB login.**
Pemilik proyek tetap bisa menelusuri lokal lewat bypass auto-login.

- **`routes/web.php`** menyusut ke rute publik saja (152 baris): `guest` group
  (login, login.kirim, lupa-kata-sandi x2, verifikasi-kode, atur-ulang-sandi),
  `logout` bare, `auth` group (ganti-kata-sandi x2), lalu kanal pengaduan warga.
- **`routes/internal.php`** (BARU) = sisa rute, dibungkus
  `['web','auth','pastikan.ganti.sandi']` lewat closure `then:` di
  `bootstrap/app.php`. `route:list` tetap **151 rute**, set identik -- hanya
  middleware berubah (137 internal, 6 guest, 2 auth, 5 web-only, 1 health).
- **`MasukOtomatisLokal`** (C1): auto-login pengguna semu (tak dipersist) bila
  `APP_ENV=local` DAN `config('sim.masuk_otomatis')`. Prepend ke grup `web`.
  `config/sim.php` default nyala di `local`. `redirectUsersTo('/')` sebab
  bawaan `/dashboard` tak ada.
- **`tests/Pest.php`**: `beforeEach` global `actingAs(new User(['nama'=>'DEV',
  'password_harus_diganti'=>false]))` untuk grup Feature -- tak dipersist, tanpa
  DB, tak mengubah HTML.
- **`HalamanTest`**: 10 titik disesuaikan (`auth()->logout()` untuk halaman
  guest; `withoutMiddleware(RedirectIfAuthenticated::class)` untuk uji campuran;
  `$lewati` + `login`/`lupa-kata-sandi`/`verifikasi-kode` pada smoke rute; uji
  "komoditas utama" kini baca `routes/internal.php` juga).
- **`TautanStatisTest`**: `auth()->logout()` -- cerminkan crawl `deploy.yml`
  tanpa login.
- **`AutentikasiTest`**: +4 uji integrasi penegakan rute.
- **`DaftarTautanStatis`**: saring rute ber-`auth` lewat `gatherMiddleware()`;
  `rincianDariDataContoh()` dipangkas ke `/lacak-pengaduan/{nomor}` saja.
  `sim:tautan-statis` **224 -> 14** URL publik. (Keputusan `notes.md` §1b.7 / A1.)

**Verifikasi:** `pest` Feature **732 PASS** (7202 asersi -- turun sebab
`TautanStatisTest` crawl 14 bukan 224) · `pest tests/Database` **102 PASS**
(98 + 4) · `pint --test` **29** (turun dari 30) · `sim:banding-skema --lengkap`
NOL SELISIH · kernel manual: bypass ON -> `/` = 200; `SIM_MASUK_OTOMATIS=false`
-> `/` = 302 `/login`, `/login` = 200.

**DITUNDA:** username saat ganti-sandi pertama (`rules.md` 14b.5) -> Task 3.5;
`penggunaSaatIni()` -> `Auth::user()` -> Tahap 4 (dev lihat "NARA WIJAYA" di
header walau login sebagai "PENGEMBANG LOKAL" -- kosmetik); halaman landing
publik (`/` kini terkunci, situs statis hanya 14 URL auth/pengaduan) ->
keputusan pemilik; CI job pest+MySQL -> nanti.

---

## Konteks

Task 3.2 "mekanik" selesai (`4e252a0`): mesin auth jalan penuh, tapi **belum ada
rute yang mewajibkan login**. Task 3.2b menutup itu + menambah bypass auto-login
env `local` supaya pemilik proyek tetap bisa telusur bebas.

## Fakta eksplorasi (3 agen, 2026-09-03)

- `routes/web.php` (2931 baris) **100% flat** -- nol `Route::group`/`middleware`.
- **Layout aman:** publik/auth/error pakai `layouts.publik`/`fullscreen-layout` --
  nol sidebar/`user-dropdown`/`penggunaSaatIni()`. **Nol `@auth`/`Auth::` di
  seluruh `resources/views/`** -> mengautentikasi user uji tak mengubah HTML.
- `penggunaSaatIni()` dipakai 2 tempat: `routes/web.php:271` + `ViewServiceProvider.php:103`.
- `guest` bawaan L12 arahkan ke `/dashboard` yang **tak ada** -> butuh
  `$middleware->redirectUsersTo('/')`. `auth` bawaan arahkan tamu ke `login` (ada).
- `new User(['password_harus_diganti'=>false])` (tak dipersist) cukup lolos
  `auth`+`pastikan.ganti.sandi` -> **`RefreshDatabase` TIDAK perlu** untuk Feature.
- `DaftarTautanStatis` tak sadar middleware (emit 224). `TautanStatisTest` tak
  kunci angka. `deploy.yml` crawl tanpa login -> 302 mematikan deploy.
- **Bypass auto-login belum ada di mana pun** -- desain baru.
- `/` (`beranda`) = dashboard (internal). Tak ada landing publik terpisah.

## Cakupan

MASUK: (1) `auth`+`pastikan.ganti.sandi` pada rute internal, `guest` pada 6 rute
auth; (2) `redirectUsersTo('/')`; (3) middleware `MasukOtomatisLokal` +
`config/sim.php` (bypass `local`); (4) `tests/Pest.php` `beforeEach` global
`actingAs(new User(...))` grup Feature; (5) ~11 edit body uji (10 `HalamanTest`,
1 `TautanStatisTest`); (6) `DaftarTautanStatis` disaring ke publik; (7) uji
integrasi baru `AutentikasiTest`; (8) update docs + `notes.md` §1b.7/§6.

DITUNDA: username saat ganti-sandi pertama (`rules.md` 14b.5) -> Task 3.5;
`penggunaSaatIni()`->`Auth::user()` -> Tahap 4; halaman landing publik ->
keputusan pemilik; CI job pest+MySQL -> nanti.

## Urutan commit (tiap Cn = checkpoint bersih)

| C | Isi | Risiko |
|---|---|---|
| C0 | Rencana -> `session-notes.md` (blok ini) | nihil |
| C1 | `MasukOtomatisLokal` + `config/sim.php` + `bootstrap/app.php` | rendah |
| C2 | `routes/web.php` bungkus grup + `guest` | **TINGGI konflik agen paralel** -- commit atomik |
| C3 | `tests/Pest.php` `beforeEach` + 11 edit body + uji integrasi | sedang |
| C4 | `DaftarTautanStatis` saring publik | rendah |
| C5 | Docs (`tasklist`/`notes`/`session-notes` HASIL) | nihil |

Verifikasi penuh setelah C3 & C4: `pest` Feature **732 PASS**, `pest tests/Database`
98+ PASS, `pint` 31, `sim:tautan-statis` ~6 URL, `sim:banding-skema --lengkap`
NOL SELISIH. Manual: `SIM_MASUK_OTOMATIS=true` -> `/` tanpa login; `=false` -> redirect `/login`.

---

# Tahap 3 · Task 3.2 - Login, Logout, Throttle Masuk SELESAI (mekanik) (2026-09-03)

Rencana ditulis sebelum kode disentuh (`rules.md` 20b poin 12).

## HASIL Task 3.2 (2026-09-03)

Seluruh mesin autentikasi berjalan; rute internal **belum** dibungkus `auth`
(opsi "mekanik dulu" -- penegakan = Task 3.2b, baris tasklist baru).

- **`app/Http/Controllers/Auth/LoginController.php`** -- `tampil`/`masuk`/`keluar`.
  `masuk`: validasi `kredensial`+`password`; cari user `where('email')->orWhere('username')`;
  tolak `is_aktif=FALSE` dengan pesan KHUSUS sebelum cek sandi; `Hash::check` +
  `Auth::login` (bukan `Auth::attempt` -- hindari tebak kolom); `session()->regenerate`;
  tulis `last_login_at`; audit `Login`; `password_harus_diganti` -> `ganti-kata-sandi`,
  selain itu `redirect()->intended(beranda)`. `keluar`: audit `Logout` lalu
  `Auth::logout` + `session()->invalidate` + `regenerateToken`.
- **Throttle** (`rules.md` 14c.2): `RateLimiter`, kunci `lower(kredensial)|ip`,
  `hit(...,60)` hanya saat gagal, `clear` saat sukses, blokir di percobaan ke-6
  dengan pesan Indonesia + detik tersisa. Matriks rate-limit lain (120/40/10/3
  per menit/jam) tetap Task 3.10.
- **`GantiKataSandiController`** -- `simpan`: cek `Auth::check` sendiri (middleware
  belum dilampirkan); validasi `ValidationRules::password()`; `update(['password'
  => ..., 'password_harus_diganti' => false])`; audit `Reset Kata Sandi` atas
  nama pemilik.
- **`app/Http/Middleware/PastikanGantiKataSandi.php`** -- alias `pastikan.ganti.sandi`
  di `bootstrap/app.php`. **BELUM dilampirkan ke grup rute** (Task 3.2b).
- **`app/Models/AuditLog.php`** (B1 hanya buat migrasi) -- model minimal, cast
  `aksi=>AksiAuditLog`, `data_lama/baru=>array`, relasi `pelaku()`. Dipakai
  `AuditLog::create()` langsung; observer/diffing otomatis = Task 3.6.
- **`ValidationRules::kredensialMasuk()`** -- `required|string|max:255` (format
  tak diperiksa; pesan galat tak membeda-bedakan, `rules.md` 14b poin 9).
- **`routes/web.php`** -- 5 closure auth -> controller. Path & nama rute sama
  persis, jadi `sim:tautan-statis` tetap 224.

**Verifikasi:** `pest tests/Database` **98 PASS** (82 + 16 `AutentikasiTest`) ·
`pest` (SQLite) tetap **732 PASS** · `pint --test` tetap 31 (berkas baru bersih) ·
`sim:tautan-statis` 224 · `sim:banding-skema --lengkap` NOL SELISIH.

**GAP tercatat:** `rules.md` 14b poin 5 minta username dibuat saat ganti sandi
pertama; view `ganti-kata-sandi.blade.php` belum punya kolomnya -> Task 3.2b/3.5.
`config/auth.php` `passwords.users.table` masih `password_reset_tokens` (tak
dibuat) -> broker sandi Task 3.11.

---

## Keputusan pemilik proyek (2026-09-03)

**Opsi "mekanik dulu, penegakan menyusul".** Bangun seluruh mesin autentikasi +
uji terhadap MySQL nyata, TANPA membungkus rute internal dengan `auth`. Pemilik
proyek ingin tetap bisa membuka halaman utama/dll tanpa login saat mengecek,
walau fungsi login sudah berjalan. Pembungkusan middleware + migrasi ~350 uji
HTTP (`HalamanTest` 343 panggilan, `TautanStatis` ~224 URL) = **Task 3.2b**,
dikerjakan terpisah nanti.

- 732 uji lama **tetap SQLite, tetap hijau, tidak disentuh**. Itu penjaganya:
  satu berubah = ada yang tersentuh di luar cakupan.
- Uji autentikasi baru di grup `tests/Database/` (MySQL nyata, `DatabaseTestCase`).

## Cakupan Task 3.2

| # | Bagian | Berkas |
|---|---|---|
| A | `POST /login` sungguhan | `app/Http/Controllers/Auth/LoginController.php` (dari closure) |
| B | Throttle masuk 5 kegagalan/menit per IP+akun (`rules.md` 14c.2) | di LoginController, `RateLimiter` |
| C | `POST /logout` -- `Auth::logout` + invalidasi sesi | LoginController |
| D | `POST /ganti-kata-sandi.simpan` -- simpan hash + kosongkan flag | `GantiKataSandiController` |
| E | Middleware `PastikanGantiKataSandi` (dibangun + alias, **belum dilampirkan**) | `app/Http/Middleware/` |
| F | Model `AuditLog` (B1 hanya buat migrasi) + catat aksi Login/Logout/Reset Kata Sandi | `app/Models/AuditLog.php` |
| H | `tests/Database/AutentikasiTest.php` (~13 uji, MySQL nyata) | + helper `buatUser()` di `DatabaseHelpers.php` |

### A. Login
- Validasi `kredensial`+`password`+`ingat_saya` (opsional). Method baru
  `ValidationRules::kredensialMasuk()`.
- Kredensial memuat `@` -> cari `email`, selain itu -> `username`. Aman:
  `where('email',$k)->orWhere('username',$k)`.
- User ada tapi `is_aktif = FALSE` -> tolak pesan KHUSUS ("Akun Anda dinonaktifkan.
  Hubungi Admin.") -- dibedakan dari kredensial salah (`rules.md` 14b, tasklist).
- `Auth::attempt`. Gagal -> `back()->withErrors(['kredensial' => ...])->onlyInput('kredensial')`.
- Sukses: `session()->regenerate()`, tulis `last_login_at`, `RateLimiter::clear`,
  audit `Login`. `password_harus_diganti` -> `route('ganti-kata-sandi')`, selain
  itu `redirect()->intended(route('beranda'))`.

### B. Throttle (`rules.md` 14c.2: 5 kegagalan/menit, per IP+akun, hanya kegagalan)
- Kunci: `Str::lower($kredensial).'|'.$request->ip()`.
- `tooManyAttempts($key,5)` -> pesan Indonesia dgn `availableIn` detik.
- `RateLimiter::hit($key,60)` HANYA saat gagal; `clear` saat sukses.

### C. Logout
- `Auth::logout()` + `session()->invalidate()` + `session()->regenerateToken()`,
  audit `Logout`, redirect `login` + flash sukses.

### D. Ganti kata sandi wajib
- Handler cek `Auth::check()` sendiri (middleware E belum dilampirkan).
- Validasi `ValidationRules::password()` (min 8, huruf+angka, `confirmed`).
- `$user->update(['password' => $baru, 'password_harus_diganti' => false])`
  (cast `hashed`). Audit `Reset Kata Sandi` atas nama pemilik (`rules.md` 14b.15).
- **GAP dicatat:** `rules.md` 14b.5 minta username dibuat di sini juga; view
  `ganti-kata-sandi.blade.php` BELUM punya kolomnya. Task 3.2 TIDAK menambah
  (perubahan UI) -> Task 3.2b/3.5.

### E. Middleware `PastikanGantiKataSandi`
- `Auth::check() && user->password_harus_diganti` & rute bukan `ganti-kata-sandi*`/
  `logout` -> `redirect()->route('ganti-kata-sandi')`. Alias di `bootstrap/app.php`.
- **TIDAK dilampirkan ke grup rute mana pun di Task 3.2.** Task 3.2b yang lampirkan.

### F. AuditLog
- Model minimal: `$table='audit_log'`, `$primaryKey='id_audit_log'`, cast
  `aksi=>AksiAuditLog::class`, `data_lama/data_baru=>'array'`, relasi `pelaku()`
  belongsTo User. Untuk Login: `nama_tabel='user'`, `record_id=user id`.
- **Bukan** observer/diffing otomatis -- itu Task 3.6. Cukup `AuditLog::create()`
  langsung di controller auth.

### G. `config/auth.php`
- `passwords.users.table` masih `password_reset_tokens` (tabel tak dibuat).
  Broker sandi = Task 3.11. BIARKAN + catat di sini.

## Verifikasi
- `pest tests/Database` hijau (82 + ~13 baru).
- `pest` (SQLite) tetap **732 PASS** (rute GET tak berubah perilaku; hanya
  `login.kirim`/`logout`/`ganti-kata-sandi.simpan` POST yang berisi -- tak
  dipanggil 732 uji; `HalamanTest:7600` hanya memeriksa markup form + `Route::has`).
- `pint --test` tetap 31 · `sim:tautan-statis` tetap 224 ·
  `sim:banding-skema --lengkap` masih NOL SELISIH (tak sentuh migrasi).

## DITUNDA ke Task 3.2b
- Bungkus rute internal dgn `auth` + `pastikan.ganti.sandi`; `guest` di rute login.
- Migrasi ~350 uji HTTP: `RefreshDatabase` + `beforeEach` global `actingAs(User)`
  di `tests/Pest.php`, lalu edit ~30 uji perilaku-tamu (login page saat login,
  `merender setiap rute GET`, `route profil/kata-sandi/ganti-kata-sandi/login`).
- Kolom username di `ganti-kata-sandi` (`rules.md` 14b.5) + cek ketersediaan.
- Peralihan `DummyData::penggunaSaatIni()` -> `Auth::user()`.

---

# Tahap 3 · Task 3.1 - Migration + Model Eloquent SELESAI (2026-09-03)

Menerjemahkan `database/data/schema.sql` (55 tabel bisnis) menjadi migration +
model. **Menerjemahkan, bukan menyusun ulang.** Mengikuti rencana Putaran 13
(bagian di bawah, baris ~373-519) dengan penyesuaian pasca-Putaran-15.
Rencana lengkap: `.claude/plans/logical-whistling-salamander.md`.

## Keputusan B3 (strategi uji DB) - DITERAPKAN

- 732 uji lama **tetap** SQLite `:memory:` (`phpunit.xml`), `RefreshDatabase` MATI.
- Grup baru **`tests/Database/`** (BUKAN `tests/Feature/Database/` - Pest tak
  mengizinkan dua base class bertumpuk pada satu pohon). Base class
  `Tests\DatabaseTestCase`: koneksi `mysql_testing` (DB `sim_transmigrasi_test`)
  + `RefreshDatabase`. Di sini ENUM/UNSIGNED/FK/UNIQUE ditegakkan sungguhan.
  Auto-SKIP (bukan gagal) bila MySQL tak tersedia.
- `phpunit.xml`: testsuite ke-3 `Database`. `pest` default menjalankan ketiganya.
- **Penjaga terpenting:** `php artisan sim:banding-skema` (`app/Console/Commands/
  BandingSkema.php`) - impor `schema.sql` ke `transmigrasi_skema_ref`,
  `migrate:fresh` ke `sim_transmigrasi_test`, bandingkan `information_schema`
  (kolom+tipe+null, indeks, FK+aksi; nama ikut dibandingkan). Wajib NOL SELISIH
  tiap batch. `--hanya=<tabel>` per batch, `--lengkap` untuk verifikasi akhir.
- Tabel bawaan Laravel (`cache`/`jobs`/dll.) dikecualikan dari `sim:banding-skema`
  (nama indeksnya mengikuti bawaan, strukturnya identik). `sessions` DIBANDINGKAN.
- CI job MySQL: BELUM (CI belum punya job pest sama sekali) - ditambahkan nanti.

## Jebakan bawaan Laravel yang dibereskan (B0)

- `0001_01_01_000000_create_users_table.php` **di-`git mv` jadi
  `create_sessions_table.php`** dan ditulis ulang: hanya `sessions` (cocok
  `schema.sql`). `users` + `password_reset_tokens` bawaan TIDAK dibuat.
- `app/Models/User.php` ditulis ulang: `$table='user'`, `$primaryKey='id_user'`,
  `SoftDeletes`, relasi `role()` eksplisit, cast `password=>hashed` dll.
- `UserFactory` disesuaikan (`nama`/`username`/`role_id`, tanpa `email_verified_at`).
  `RoleFactory` + `PermissionFactory` baru. `DatabaseSeeder` dikosongkan
  (seeder role/izin/wilayah/admin = Task 3.3 & 4.1).
- `ValidationRules::email()/username()` (baris 137/155) DIBIARKAN - benar untuk
  skema target, hidup otomatis begitu tabel `user` ada.

## Progres batch

| Batch | Status | Isi |
|---|---|---|
| B0 fondasi | **SELESAI** | rename+tulis ulang `create_sessions_table`, `DatabaseTestCase`, `sim:banding-skema`, factory/seeder |
| B1 Domain 1 | **SELESAI** | role, permission, role_permission, user, kode_pemulihan_sandi, audit_log + model `Role`/`Permission`/`User` + `tests/Database/Domain1PenggunaSistemTest` (10 uji) |
| B2 Domain 2 | **SELESAI** | provinsi, kabupaten, kecamatan, desa, kawasan_transmigrasi, `referensi`, `berkas` (ditarik maju - topo-sort), satuan_permukiman, user_satuan_permukiman, rute_aksesibilitas_sp + 9 model + `tests/Database/Domain2WilayahSpTest` (10 uji) |
| B3 Domain 3+4 | **SELESAI** | satuan, komoditas, status_kondisi_sp, parameter_penilaian_sp, penilaian_sp, inventaris_sp, fasilitas_sp, fasilitas_sp_cakupan (pivot) + 7 model + `tests/Database/Domain3Domain4AsetPenilaianTest` (14 uji) |
| B4 Domain 4b | **SELESAI** | user_berkas, kawasan_transmigrasi_berkas, inventaris_sp_berkas, fasilitas_sp_berkas (4 dari 12 pivot `*_berkas` -- yang induknya sudah ada) + relasi `belongsToMany` pada 4 model induk + `tests/Database/Domain4bBerkasPivotTest` (5 uji). 8 pivot sisa menyusul di B5-B9 |
| B5 Domain 5 | **SELESAI** | transmigran, anggota_keluarga, rumah, riwayat_penghunian, riwayat_kepala_keluarga + pivot transmigran_berkas, rumah_berkas + 5 model + `tests/Database/Domain5KependudukanTest` (11 uji) |
| B6 Domain 6 | **SELESAI** | poktan, anggota_poktan, alsintan, alsintan_distribusi, saprotan, saprotan_distribusi + pivot alsintan_berkas + 6 model + `tests/Database/Domain6KelembagaanTest` (8 uji). Helper uji dipusatkan ke `tests/Database/DatabaseHelpers.php` (`require_once`) |
| B7 Domain 7 | **SELESAI** | lahan (satu tabel) + model `Lahan` + `tests/Database/Domain7LahanTest` (7 uji) |
| B8 Domain 8 | **SELESAI** | komoditas_poktan (pivot), penanaman, hasil_panen + pivot penanaman_berkas, hasil_panen_berkas + 2 model + `tests/Database/Domain8ProduksiTest` (8 uji) |
| B9 Domain 9 | **SELESAI** | infrastruktur, infrastruktur_sp (pivot), pengaduan, penanganan_pengaduan + pivot infrastruktur_berkas, pengaduan_berkas, penanganan_pengaduan_berkas + 3 model + `tests/Database/Domain9InfrastrukturPengaduanTest` (9 uji) |

**Task 3.1 SELESAI (2026-09-03).** 58 migration (55 tabel bisnis + 3 berkas
infra Laravel: `sessions`, `cache`+`cache_locks`, `jobs`+`job_batches`+
`failed_jobs`), 36 model Eloquent di `app/Models/` (sisanya pivot murni tanpa
model), 9 berkas uji `tests/Database/` (82 uji). Lihat blok HASIL di bawah.

**Verifikasi B0+B1:** `sim:banding-skema --hanya=<Domain 1>` NOL SELISIH ·
`pest` **742 PASS** (732 lama + 10 Database) · `pint --test` 31 (turun dari 33) ·
`sim:tautan-statis` 224 · `migrate:fresh` MariaDB 10.4.32 bersih.

**Verifikasi B2:** `sim:banding-skema --hanya=<10 tabel B2>` NOL SELISIH ·
`pest tests/Database` **20 PASS** · `pest` (SQLite) tetap **732 PASS** ·
`pint --test` tetap 31 (berkas B2 lolos). `referensi`+`berkas` masuk B2 (bukan
B4) sebab `satuan_permukiman.berkas_id` -> `berkas` -> `referensi`. Model B2
tanpa `HasFactory` - uji Database pakai `::create()` langsung + helper `buatSp()`;
factory ditunda ke Tahap 4 (CRUD).

**Verifikasi B3:** `sim:banding-skema --hanya=<8 tabel B3>` NOL SELISIH ·
`pest tests/Database` **34 PASS** · `pest` (SQLite) tetap **732 PASS** ·
`pint --test` tetap 31. Dialek: `komoditas.tipe` + kolom REF `inventaris_sp`/
`fasilitas_sp` (sumber_dana, status_penyerahan, kondisi, jenis_inventaris)
disimpan TEKS, TIDAK di-cast ke PHP Enum -- Admin boleh tambah nilai lewat
master. `fasilitas_sp.jenis_fasilitas` (ENUM sungguhan) -> `JenisFasilitas`;
`penilaian_sp.status` -> `App\Enums\StatusKondisiSp` (enum tetap 3 nilai).
`parameter_penilaian_sp.tingkat`/`sumber` ENUM tanpa PHP Enum -> string.
Model `App\Models\StatusKondisiSp` (data ambang) hidup berdampingan dengan
enum `App\Enums\StatusKondisiSp` (perilaku).

**Verifikasi B4:** `sim:banding-skema --hanya=<4 pivot>` NOL SELISIH ·
`pest tests/Database` **39 PASS** · `pest` (SQLite) tetap **732 PASS** ·
`pint --test` tetap 31. Pivot `*_berkas` = pivot murni tanpa model; relasi
`belongsToMany(...)->withPivot('peran','urutan')` pada induk. `user_berkas`
UNIQUE `user_id` saja (satu foto/pengguna) -> `User::fotoProfil()`. CASCADE dua
arah: pivot ikut hilang saat induk domain ATAU baris `berkas` registry hilang,
tapi `berkas` registry TIDAK ikut saat induk domain hilang.

**Verifikasi B5:** `sim:banding-skema --hanya=<7 tabel B5>` NOL SELISIH ·
`pest tests/Database` **50 PASS** · `pest` (SQLite) tetap **732 PASS** ·
`pint --test` tetap 31. `transmigran`/`rumah` uuid route key; `rumah.transmigran_id`
UNIQUE nullable (1:1 dua arah) -> `Transmigran::rumah()` hasOne + FK SET NULL.
`rumah.kondisi`/`status_hunian` TEKS REF (bukan enum). Tabel riwayat
(riwayat_penghunian, riwayat_kepala_keluarga) tanpa soft delete, FK transmigran
RESTRICT. Model belum auto-generate `uuid` (sama seperti `Berkas`) -- observer/
trait ditunda ke Tahap 4.

**Verifikasi B6:** `sim:banding-skema --hanya=<7 tabel B6>` NOL SELISIH ·
`pest tests/Database` **58 PASS** · `pest` (SQLite) tetap **732 PASS** ·
`pint --test` tetap 31. `poktan.asal_ketua` & `anggota_poktan.asal_wakil` pakai
`AsalWakilPoktan` (satu tipe, tapi 'Bukan Transmigran' tak berlaku di anggota --
ditegakkan aplikasi). `anggota_poktan.status` -> `StatusKeaktifanAnggota` (BUKAN
`StatusAnggotaPoktan` yg 'Ya'/'Tidak'). `alsintan`/`saprotan` = induk pengadaan
(pola 1 pengadaan -> N distribusi); tabel `*_distribusi` tanpa soft delete.
`jabatan`/`jenis_alsintan`/`kondisi` = teks REF. Helper uji Database dipindah
dari tiap berkas ke `DatabaseHelpers.php` bersama (buatSp/buatBerkas/
buatSatuanTon/buatTransmigran/buatPoktan) supaya `pest <satu-berkas>` jalan sendiri.

**Verifikasi B7:** `sim:banding-skema --hanya=lahan` NOL SELISIH ·
`pest tests/Database` **65 PASS** · `pest` (SQLite) tetap **732 PASS** ·
`pint --test` tetap 31. `lahan` = SATU BARIS per KK (Putaran 15): `UNIQUE
transmigran_id` (bukan komposit dengan peruntukan), dua pasang koordinat
`*_pekarangan`/`*_usaha`, `luas_*` NULL = belum menerima. FK transmigran
CASCADE, SP RESTRICT, poktan SET NULL. uuid route key. `Transmigran::lahan()`
hasOne. UNIQUE `transmigran_id` tak melihat `deleted_at` -> KK dengan lahan
ter-soft-delete belum bisa dapat baris baru tanpa restore/forceDelete.

**Verifikasi B8:** `sim:banding-skema --hanya=<5 tabel B8>` NOL SELISIH ·
`pest tests/Database` **73 PASS** · `pest` (SQLite) tetap **732 PASS** ·
`pint --test` tetap 31. `penanaman` berpusat poktan (bukan lahan); FK
saprotan_distribusi RESTRICT (jatah benih). `hasil_panen` uuid route key,
`satuan_id` snapshot dari komoditas (FK RESTRICT), 1:1 dengan penanaman
(ditegakkan aplikasi, bukan UNIQUE). Tak ada kolom ENUM di domain ini -- semua
DECIMAL/CHAR(7). `Poktan::komoditas()` + `Komoditas::poktan()` M:N.

**Verifikasi B9:** `sim:banding-skema --hanya=<7 tabel B9>` NOL SELISIH ·
`pest tests/Database` **82 PASS** · `pest` (SQLite) tetap **732 PASS** ·
`pint --test` tetap 31. `pengaduan.sumber_laporan` -> `SumberLaporan`,
`pengaduan.status` + `penanganan_pengaduan.status_sebelum`/`status_sesudah` ->
`StatusPengaduan`. `kategori`/`bidang`/`prioritas` = teks REF. `pengaduan` FK
user + SP RESTRICT (jejak & lokus tak boleh hilang); `penanganan_pengaduan`
tabel riwayat tanpa soft delete, FK pengaduan CASCADE, user RESTRICT.
`infrastruktur_sp` pivot cakupan lintas SP (WAJIB memuat SP pangkal).

## HASIL Task 3.1 (2026-09-03)

- **58 migration**, `sim:banding-skema --lengkap` **NOL SELISIH** untuk seluruh
  55 tabel bisnis + `sessions`. `migrate:fresh` ke MariaDB 10.4.32 bersih.
- **36 model** `app/Models/`; tiap relasi menyebut kunci eksplisit (`rules.md`
  4.0). 20 tabel ber-`SoftDeletes` sesuai daftar rencana. `getRouteKeyName()`:
  `uuid` (transmigran, rumah, pengaduan, lahan, hasil_panen), `slug`
  (satuan_permukiman, kawasan_transmigrasi, poktan, komoditas).
- Pivot murni tanpa model: role_permission, komoditas_poktan,
  user_satuan_permukiman, fasilitas_sp_cakupan, infrastruktur_sp, 12 `*_berkas`.
- **9 berkas uji `tests/Database/`, 82 uji** (MySQL nyata, `Tests\DatabaseTestCase`).
  732 uji lama tetap SQLite, tetap hijau. `pint` tetap 31 pre-existing.
- **Beda dari rencana Putaran 13:** (1) B3 test-DB memakai `tests/Database/`
  top-level (Pest tak izinkan base-class bertumpuk); (2) model TIDAK diberi
  `HasFactory` (factory = Tahap 4) - uji Database pakai `::create()` + helper
  `DatabaseHelpers.php`; (3) kolom REF (`komoditas.tipe`, `*.kondisi`,
  `*.sumber_dana`, `jabatan`, `jenis_alsintan`, `pengaduan.kategori/bidang/
  prioritas`, dll.) disimpan TEKS, tidak di-cast enum - Admin boleh tambah nilai
  lewat master; hanya ENUM sungguhan di schema yang di-cast; (4) `referensi` +
  `berkas` ditarik ke B2 (topo-sort); (5) model belum auto-generate `uuid`
  (observer/trait = Tahap 4).
- **BELUM (batch/tahap terpisah):** login/RBAC/global scope/penyesuaian ±330 uji
  (Task 3.2+), CI job pest+MySQL, seeder role/izin/wilayah/admin (Task 3.3 & 4.1),
  peralihan view dari `DummyData` ke Eloquent (Tahap 4), pembatas rute
  `->where('id','[0-9]+')` -> uuid/slug (tahap frontend).

**Urutan migration = topological sort dependensi FK, BUKAN urutan file
`schema.sql`.** Deviasi: `referensi`+`berkas` naik sebelum `satuan_permukiman`;
12 pivot `*_berkas` turun ke setelah induk domainnya.

**TIDAK disentuh:** `DummyData`, view, rute, 732 uji lama, autentikasi/RBAC/
cakupan data (Task 3.2+).

---

# Putaran 15 - Utang Putaran 12, Lahan Satu Baris per KK, dan Penjaga Isian Yatim BERJALAN (2026-09-02)

Rencana ditulis sebelum kode disentuh (`rules.md` 20b poin 12).

## Pemicu

Pemilik proyek menemukan dua hal yang saya lewatkan, dan keduanya benar:

1. **Form Lahan tidak berubah** meski Putaran 12 mencabut `dokumen_lahan`.
2. **Ada keputusan Putaran 12 yang tidak dikerjakan**, hilang karena tidak tercatat.

Saya juga keliru menyajikan Form Lahan sebagai "keputusan skema dengan 3 pilihan",
padahal `session-notes.md` baris 369 sudah memutuskannya (`Lahan TIDAK dapat pivot`),
dan `rules.md` 7.6a melarang unggahan di sana dengan alasan yang saya tulis sendiri.
Dua dari tiga pilihan yang saya tawarkan MELANGGAR aturan yang sudah ada.

## Hasil audit ulang Putaran 12 (15 keputusan disisir)

| # | Keputusan | Keadaan |
|---|---|---|
| 1-8, 12-15 | registry, pivot, HPL ke kawasan, SHM ke transmigran, UNIQUE lahan, pencabutan pola_tanam dll | SELESAI benar |
| 9 | `transmigran.status_sertifikat` | **CACAT**: kolom + data + tampil di 3 tempat, tetapi TIDAK ADA isian form |
| 11 | `alsintan` induk dapat foto | **TIDAK DIKERJAKAN**: `tasklist.md` 981 mengklaim selesai, nyatanya nol |
| sisa | modul `dokumen_lahan` | **BANGKAI**: izin, enum, opsi referensi, dan 3 isian form masih hidup |

Akar masalahnya satu: Putaran 12 memverifikasi SKEMA dengan impor MariaDB nyata,
tetapi tidak punya penjaga yang membandingkan ISIAN FORM terhadap skema. Isian yatim
karena itu tidak memerahkan apa pun - sama persis dengan empat kontrol mati Putaran 14.

## Keputusan pemilik proyek (mengikat)

| # | Keputusan |
|---|---|
| 1 | Bagian A, B, dan C dikerjakan berurutan tanpa henti selama aman |
| 2 | Lahan menjadi **SATU BARIS per KK** (opsi i), bukan dua baris per peruntukan |
| 3 | Form lahan **tetap multistep** seperti sekarang |
| 4 | Koordinat memakai **opsi (a)**: dua pasang, pekarangan dan usaha terpisah |

## Bagian A - Bereskan utang Putaran 12

A1. Langkah 3 form lahan: cabut isian `file_dokumen` dan `jenis_dokumen`, ganti
    menjadi BACAAN SHM/HPL beserta tautan, sepola tab Legalitas yang sudah benar.
A2. Cabut bangkai `dokumen_lahan`: `opsiJenisDokumenLahan` (ViewServiceProvider 58
    dan 186), `JenisReferensi::JenisDokumenLahan`, nilai referensi `[HPL, SHM]`,
    dan izin `dokumen_lahan` pada matriks izin beserta 4 pemakaian di data role.
A3. Alsintan induk mendapat foto, sejajar saprotan (keputusan 11 Putaran 12).
A4. `status_sertifikat` mendapat isian pada form transmigran (keputusan 9).

## Bagian B - Lahan satu baris per KK

Struktur baru tabel `lahan`:

| Kolom | Catatan |
|---|---|
| `transmigran_id` | **UNIQUE**, menggantikan `UNIQUE (transmigran_id, peruntukan_lahan)` |
| `luas_pekarangan` | NULL bila keluarga belum menerima pekarangan |
| `lintang_pekarangan`, `bujur_pekarangan` | koordinat bidang pekarangan |
| `luas_usaha` | NULL bila belum menerima lahan usaha |
| `luas_kering`, `luas_basah` | komposisi lahan USAHA saja; jumlahnya = `luas_usaha` |
| `lintang_usaha`, `bujur_usaha` | koordinat bidang usaha |
| `kode_lahan` | satu kode per keluarga |

Yang DICABUT: `peruntukan_lahan`, `luas`, `lintang`, `bujur`, dan enum
`PeruntukanLahan` beserta seluruh pemakaiannya.

Data contoh berubah dari 6 baris menjadi 4 baris:

```
tm=1 : LP-001 (0,25 ha) + LU-001 (1,5 ha; k=1,5 b=0)   -> 1 baris
tm=2 : LP-002 (0,25 ha) + LU-003 (2 ha; k=1,25 b=0,75) -> 1 baris
tm=3 : LU-004 (1,25 ha; k=1,25 b=0) SAJA               -> 1 baris, pekarangan NULL
tm=8 : LU-002 (0,75 ha; k=0 b=0,75) SAJA               -> 1 baris, pekarangan NULL
```

**Dua dari empat keluarga hanya punya lahan usaha.** Karena itu kolom pekarangan
WAJIB nullable, dan tampilan wajib membedakan "belum menerima" dari "nol hektare".

## Penyisiran lima sudut (rules.md 20a poin 10)

**Privasi.** Tidak ada data baru yang terbuka. Menggabungkan dua baris menjadi satu
tidak menambah bidang informasi, hanya memindahkannya. Koordinat tetap dua pasang,
sehingga ketelitian lokasi tidak berkurang maupun bertambah.

**Siklus hidup.** Ini yang paling menuntut perhatian. Keluarga yang baru menerima
pekarangan menyunting BARIS YANG SUDAH ADA, bukan menambah baris. Akibatnya alur
"tambah lahan usaha" untuk keluarga yang sudah punya pekarangan berubah dari
penambahan menjadi penyuntingan; bila form tetap menyediakan "Tambah", ia akan
ditolak UNIQUE. Halaman daftar wajib mengarahkan ke Ubah bila keluarganya sudah ada.

**Kejujuran angka.** Paling berisiko pada putaran ini. `luas_pekarangan` NULL
berarti BELUM MENERIMA, sedangkan 0 berarti menerima seluas nol - dua keadaan
berbeda yang tidak boleh dicampur, sepola `status_sertifikat` 7.6c dan 10a.4c.
Rekap luas (7.10) yang dahulu MENJUMLAH BARIS kini wajib MENJUMLAH KOLOM;
melewatkan satu tempat membuat total luas mengecil tanpa memerahkan apa pun.
Jumlah bidang juga tidak lagi sama dengan jumlah baris.

**Alur kerja.** Petugas kehilangan kemampuan mendata dua bidang sebagai dua entri
terpisah. Konsekuensi sadar dari keputusan pemilik proyek, sejalan dengan 7.9a yang
sudah menerima konsekuensi serupa. Form tetap multistep sehingga kebiasaan mengisi
tidak berubah bentuknya.

**Teknis.** Menyentuh ~230 pemakaian kolom lahan, 6 rute, dan 139 baris uji di
`HalamanTest`. `peruntukan_lahan` dipakai 37 kali termasuk `LaporanData` dan
Monografi SP. Enum `PeruntukanLahan` dicabut, sehingga `EnumTest` ikut berubah.
Tidak menyentuh rute baru, sehingga `sim:tautan-statis` diharapkan tetap 227.

## Bagian C - Penjaga isian yatim

Penjaga baru pada `HalamanTest`: setiap atribut `name=` pada berkas `pages/**/form*`
wajib punya padanan di `schema.sql`, entah sebagai kolom tabel atau sebagai peran
pivot berkas. Inilah yang absen sehingga tiga bangkai `dokumen_lahan` lolos berhari-hari.

Daftar kecuali ditulis eksplisit beserta alasannya (penyaring, token, isian bantu
yang memang bukan kolom), sebab penjaga tanpa daftar kecuali akan dimatikan orang
berikutnya begitu ia memerah sekali secara keliru.

## Urutan pengerjaan

```
A1+A2 (saling terkait: keduanya bangkai dokumen_lahan) -> test
A3 alsintan foto                                       -> test
A4 status_sertifikat                                   -> test
B1 schema.sql + DummyData                              -> test
B2 form + detail + index lahan                         -> test
B3 pemakai lain: LaporanData, Monografi, transmigran   -> test
C  penjaga isian yatim                                 -> test
D  dokumentasi + commit
```

Baseline: **730 PASS / 7.421 assertions**, `sim:tautan-statis` 227, `pint` 33 pre-existing.

## Cara verifikasi

1. `php artisan test` hijau. Uji yang DIPERKIRAKAN merah: yang menghitung baris lahan,
   yang menyebut `peruntukan_lahan`, dan `EnumTest` untuk `PeruntukanLahan`.
   Bila ada yang lain, diperiksa lebih dulu sebelum disentuh.
2. Total luas sebelum dan sesudah penggabungan WAJIB SAMA: 6,0 ha.
3. `/lahan`, `/lahan/1`, `/transmigran/2`, `/laporan/transmigran` membalas 200.
4. Penjaga Bagian C memerah bila isian yatim sengaja ditambahkan (diuji dengan
   menambahkan satu isian palsu, memastikan merah, lalu mencabutnya).
5. `sim:tautan-statis` tetap 227; `pint --test` tetap 33; seluruh berkas bebas BOM.

## Yang TIDAK dikerjakan

Migration Laravel dan model Eloquent tetap Tahap 3. Putaran ini hanya menyunting
`schema.sql` sebagai acuan DDL, sejalan dengan cara Putaran 12.

## HASIL (2026-09-03, diselesaikan oleh Claude setelah takeover dari opencode)

**Selesai.** `pest` **731 PASS / 7.409 assertions** (baseline 730/7.421; +1 uji =
penjaga isian yatim, beberapa uji lahan ditulis ulang sehingga assertion sedikit
berkurang). `pint --test` **33 pre-existing** (tidak bertambah). Seluruh berkas
bebas BOM. Total luas lahan **6,0 ha** (pekarangan 0,5 + usaha 5,5), 4 baris.

### Kondisi saat takeover (audit repository, bukan TODO)

opencode sudah menerapkan dengan benar: pencabutan enum `PeruntukanLahan` &
`JenisDokumenLahan`, `schema.sql` tabel `lahan` struktur baru + `UNIQUE
(transmigran_id)`, `DummyData::lahan()` 4 baris, `rekapLahanKeluarga()`,
`referensi()`/`daftarIzin()`/`izinRole()` pencabutan `dokumen_lahan`,
`LaporanData::bagianTambahanSp()`, `routes/web.php` `/lahan` index, form &
index lahan. **Berhenti sebelum:** A3, A4, `lahan/detail`, B3 pemakai lain
(sp/detail, transmigran/detail, laporan/isi/transmigran, routes web.php:954),
Bagian C, dan seluruh perbaikan uji (61 merah saat takeover).

### Yang dikerjakan pada sesi penyelesaian

| Bagian | Hasil |
|---|---|
| A3 | `alsintan` induk dapat foto: berkas 37 + pivot `alsintan_berkas` peran `foto`, `lekatkanBerkas` peta `foto`, form dua kolom (foto + dokumen) sepola saprotan, detail tab dokumen. `/alsintan/1` kini 3 tautan berkas |
| A4 | Enum `StatusSertifikat` (Sudah/Belum/Belum Didata) + isian `<select name="status_sertifikat">` pada langkah Penempatan form transmigran |
| B2 | `lahan/detail` ditulis ulang (dua blok bidang, dua pasang koordinat, "belum menerima" vs 0); `transmigranTanpaLahan()` + form Tambah hanya menawarkan KK tanpa baris lahan (UNIQUE); label langkah modal |
| B3 | `transmigran/detail` tab Lahan, `sp/detail` Bidang Lahan (cacah bidang ≠ cacah baris), `laporan/isi/transmigran` mode Gabungan + mode Data Lahan (satu keluarga bisa jadi dua baris laporan menurut peruntukan), `routes/web.php` totalLuas transmigran.detail, `daftarLahanLain` dicabut |
| B (uji) | 3 `DummyDataTest`, `FormatNominalUangTest`, ~10 blok `HalamanTest` lahan, matriks izin (`dokumen_lahan` dicabut → `role()` jumlah_izin 95/47/44/49), `ReferensiTest`, uji koordinat |
| C | `it tidak menyisakan isian form yatim...` di `HalamanTest`: setiap `name=`/`nama=` pada `pages/*/form*` wajib berpadanan di `schema.sql` (kolom mana pun) atau daftar kecuali eksplisit (peran berkas, pembungkus repeater, penyaring/token, pivot penugasan). **Diuji: `name="kolom_palsu_xyz"` → MERAH; dicabut → HIJAU.** Penjaga ini menemukan `satuan_permukiman_ids_lain` (pivot infrastruktur_sp/fasilitas_sp_cakupan) yang sah dan ditambahkan ke daftar kecuali |
| D | `rules.md` §5.1 & §7.2/7.8/7.9, `data-dictionary.md` §6.1/§7.1/§7.2/§11.11/§11.14/§13, `erd.md` relasi #14 + indeks, `tasklist.md` |

### Yang berbeda dari rencana

1. **`peruntukan` pada Mode Data Lahan laporan transmigran.** Penyaring Alpine
   `data-peruntukan` mencocokkan satu nilai persis (line 198 `filter-laporan.js`),
   sedangkan satu baris KK kini memuat dua bidang. Diselesaikan dengan memecah
   tampilan menjadi dua baris laporan per KK (satu pekarangan, satu usaha) -
   sesuai judul laporan "menurut peruntukannya" - tanpa menyentuh JS.

2. **`sim:tautan-statis` 227 → 224.** Ketiganya diwajibkan langsung Putaran 15:
   `/lahan/5` dan `/lahan/6` (6 baris bidang → 4 baris KK), dan
   `/master/referensi/jenis_dokumen_lahan` (referensi dicabut A2). Tidak ada uji
   yang mengunci angka 227.

3. **`kode_lahan` LP-001/LU-001 → LH-001..LH-004.** Satu kode per keluarga
   (struktur mengikat menyebut `kode_lahan` "satu kode per keluarga"); data
   contoh handover memakai kode lama untuk menunjukkan komposisi baris gabungan.

4. ~~SHM belum punya isian unggah.~~ **Diselesaikan pada "Putaran 15 lanjutan" di bawah.**

## Putaran 15 lanjutan - Unggahan SHM di Form Lahan + Rapikan Kartu (2026-09-03)

Pemilik proyek meninjau hasil Putaran 15 dan meminta dua hal:

1. **Unggahan SHM ada di FORM DATA LAHAN.** Putaran 12 menjadikan legalitas form
   lahan "bacaan saja" dengan alasan unggahan per-bidang melahirkan salinan
   sertifikat ganda. Alasan itu GUGUR sejak Putaran 15: satu keluarga tepat satu
   baris lahan. Pemilik menetapkan form lahan sebagai tempat kanonis SHM
   **dan** status sertifikat.
   - `status_sertifikat` DIPINDAH dari form transmigran ke form lahan langkah 3
     (atas pilihan pemilik saat ditanya - agar sebelahan dengan SHM).
   - Berkas SHM tetap tersimpan pada `transmigran_berkas` peran `shm`, status
     tetap kolom `transmigran.status_sertifikat`. Form lahan hanya permukaan
     entrinya. `DummyData::lahan()` menempelkan `shm`/`status_sertifikat` per
     baris dari keluarganya.
   - HPL tetap bacaan (alas hak kawasan, diunggah dari Data Kawasan).
   - `rules.md` 7.6a dibalik; `data-dictionary.md` §6.1/§7.2 disesuaikan.

2. **Kartu `/lahan` 6 -> 4** (muat satu baris): Total Bidang, Luas Pekarangan,
   Luas Lahan Usaha, Total Luas. Kartu "Lahan Usaha Kering/Basah" dibuang -
   rinciannya sudah tampil per baris di kolom tabel. `luasKering`/`luasBasah`
   dicabut dari `routes/web.php` `lahan.index`.

**Hasil:** pest **732 PASS** (+1 dari dataset `['/lahan','shm']`), pint 33,
`sim:tautan-statis` 224, total luas 6,0 ha, bebas BOM. Penjaga isian yatim
menambah `shm` ke daftar kecuali (peran berkas `transmigran_berkas`). Uji
`menjadikan langkah legalitas ... bacaan` ditulis ulang jadi `menyediakan
unggahan SHM dan status sertifikat pada form lahan, HPL tetap bacaan`. Satu
regresi tertangkap & dibetulkan: `x-sim.file-upload` SHM sempat mendahului
`name="keterangan"` di langkah 3 (melanggar ui-spec 6.4a poin 5) - Catatan
dipindah ke atas unggahan.

## Audit pra-Tahap-3 + utang dokumentasi + keputusan A1/A2 (2026-09-03)

Pemilik proyek meminta pemeriksaan apa yang tersisa sebelum Tahap 3. **Tidak ada
blocker kode** - Tahap 2 selesai seluruhnya, `schema.sql` final & terverifikasi
impor MariaDB, pengambilan data sudah pindah dari view ke rute. Yang ada:

- **Utang dokumentasi (DIBERESKAN):** `erd.md` §2 (hitungan 55 bisnis + 6 Laravel,
  `schema.sql` sebagai sumber kebenaran), §10 (pointer ke urutan `CREATE TABLE`),
  §12 poin 1 (lahan one-to-many -> one-to-one). `data-dictionary.md`: dicek
  konsisten dengan `schema.sql` (§5.7 `status_kondisi_sp` sudah ada dari agen
  paralel, `uuid` terpusat di §1.3a, kolom usang sudah dicoret). `tasklist.md`
  Task 4.1b (kawasan/HPL - sebelumnya tak bernomor), Task 5.1/5.2 & 6.1/6.2/6.3
  diperbarui agar penulis migration Tahap
  4-6 tidak membangun dari deskripsi usang (one-to-one lahan, bidang jadi kolom,
  SHM lewat form lahan, HPL di kawasan, `dokumen_pendukung` -> peran ktp/kk/sk,
  `jumlah_anggota_keluarga` diturunkan). `notes.md` §6 daftar tindak-lanjut DDL
  ditandai selesai. §5.7 `status_kondisi_sp` ternyata sudah ada (agen paralel).
- **A1 (DIPUTUSKAN):** penerbitan statis begitu login aktif -> **batasi ke halaman
  publik saja** (opsi a). Implementasi konkret masuk Task 3.2: saring
  `DaftarTautanStatis` ke rute tanpa `auth`, `TautanStatisTest` menyesuaikan.
- **A2 (MENUNGGU DINAS):** spesifikasi hosting - 6 hal (versi PHP 8.2.x, MySQL/
  MariaDB + versi, storage privat + S3/GCS, cron untuk backup, SSL + domain,
  reverse proxy). Diangkat jadi item eksplisit di `notes.md` §4 poin 7 + peringatan
  di kepala Tahap 3 `tasklist.md`. Tahap 3 boleh mulai di XAMPP lokal sambil
  menunggu; Task 3.10 & 11.3 menahan diri.

Baseline tak berubah: **732 PASS**, pint 33, `sim:tautan-statis` 224. Perubahan
audit ini hanya menyentuh `agents/*.md`, nol kode.

**Lanjutan 2026-09-03 — evaluasi analisis AI lain + konsolidasi.** Pemilik proyek
membagikan analisis AI lain soal "yang belum dikerjakan sebelum Tahap 3". Dicek
baris demi baris ke kode: **seluruh klaim teknisnya benar** (SQLite `:memory:`,
`RefreshDatabase` mati, 33 ENUM/185 UNSIGNED, migration & `Model User` masih
bawaan, `ValidationRules` menunjuk tabel `user`). Namun **sebagian besar
menemukan ulang** hal yang sudah tercatat: B1/B4 di rencana Putaran 13 (389–390,
495–500), B3 (SQLite tak kenal ENUM) di 474–489, urutan B-A & Pages publik-saja
di 418–419. B2 ("bangkai") keliru kategori — `ValidationRules::email()` benar
untuk skema target, cuma belum terpakai. **Tidak ada yang perlu dikerjakan
sekarang:** B1/B2/B4 = isi Task 3.1; B3 = keputusan kickoff Task 3.1 (arah sudah
ada). Yang dikerjakan: blok **"⚑ BACA SEBELUM TASK 3.1"** di kepala bagian Tahap 3
`tasklist.md` mengonsolidasikan semua keputusan/jebakan yang tersebar, plus
strategi uji DB (SQLite cepat untuk 732 uji lama + grup `tests/Feature/Database/`
ber-MySQL untuk uji constraint Tahap 3+), plus Task 3.1 dinaikkan dari `[Mudah]`
ke `[Sulit]` (cakupan sebenarnya ~55 migration + ~55 model), dan "44 tabel
bisnis" usang di kepala Tahap 3 dibetulkan jadi 55.

---

# Putaran 14 - UI Multi-Unggah Berkas BERJALAN (2026-09-02)

Rencana ditulis sebelum kode disentuh (`rules.md` 20b poin 12).

## Pemicu

Putaran 12 menyiapkan struktur multi-berkas (12 pivot, `berkasMilik()` yang mengembalikan
larik, kolom `peran` dan `urutan`), tetapi antarmukanya tetap single-file. Kemampuan itu
karena itu belum terpakai sama sekali: `file-upload.blade.php` baris 46 masih membaca
`files[0]`, dan tidak ada satu pun tombol tambah berkas di seluruh halaman.

Pemilik proyek menegur ketika pengerjaan melompat ke Tahap 3. Teguran itu benar: ada
pekerjaan tertunda yang lebih dekat, dan melewatinya membiarkan struktur yang sudah
dibayar menganggur.

## Keputusan pemilik proyek

| # | Keputusan |
|---|---|
| 1 | **Komponen dulu + 3 domain percontohan**, bukan 12 sekaligus. Pola dibuktikan di layar sebelum disebar |
| 2 | Tiga domainnya: **infrastruktur** (beberapa titik kerusakan), **pengaduan** (beberapa foto bukti), **transmigran** (KTP/KK/SK/SHM terpisah) |
| 3 | Rencana Task 3.1 ditandai DITUNDA, tidak dicabut, agar penyisirannya tidak disusun ulang |

## Cakupan

| Titik | Berkas | Perubahan |
|---|---|---|
| A | `components/sim/berkas-unggah.blade.php` (BARU) | Komponen multi-berkas: daftar berkas tersimpan, unggah beberapa sekaligus, hapus per berkas, penanda peran |
| B | `components/sim/file-upload.blade.php` | TETAP, tidak disentuh. Dipakai 22 titik single-file yang memang hanya butuh satu |
| C | `pages/infrastruktur/form` + `detail` | Foto beberapa titik kerusakan |
| D | `pages/pengaduan/form` + `detail` | Beberapa foto bukti dari pelapor |
| E | `pages/transmigran/form` + `detail` | KTP, KK, SK penempatan, SHM terpisah |
| F | `DummyData` | Tambah beberapa berkas contoh agar keadaan multi benar-benar terlihat |
| G | `ViewServiceProvider` / rute | Suplai daftar berkas per domain |

**Komponen lama TIDAK dihapus.** Dua puluh dua titik unggah lain memang single-file
(foto profil, SK kawasan, berita acara), dan memaksanya memakai komponen multi hanya
menambah kontrol yang tidak pernah dipakai. Keduanya berdampingan sesuai sifat domainnya.

## Penyisiran lima sudut (rules.md 20a poin 10)

**Privasi.** Berkas pengaduan berasal dari kanal publik tanpa login. Menampilkan daftar
berkas pada halaman lacak berarti warga lain yang mengetahui nomornya ikut melihatnya;
penjaga yang sudah ada (`memberitahu warga adanya dokumen tanpa membuka berkasnya`)
wajib tetap hijau, dan daftar multi-berkas TIDAK boleh melonggarkannya.

**Siklus hidup.** Menghapus satu berkas dari daftar berarti menghapus baris pivot, bukan
baris `berkas` itu sendiri; berkas fisik dibersihkan terjadwal lewat `deleted_at`
(14a.8). Pada tahap frontend penghapusan belum benar-benar terjadi, sehingga tombolnya
wajib jujur menyatakan itu, bukan tampak berfungsi lalu tidak mengubah apa pun (R-26).

**Kejujuran angka.** Kosong. Tidak menyentuh rekap mana pun. Yang perlu dijaga: cacah
berkas pada label tab wajib membaca daftar sebenarnya, bukan angka tetap.

**Alur kerja.** Petugas mengunggah beberapa berkas sekaligus, dan tiap berkas boleh
diberi keterangan sendiri (mis. tampak samping). Urutannya menentukan mana yang tampil
sebagai gambar utama pada halaman daftar. Batas 5 MB berlaku PER BERKAS, bukan total,
sehingga pesan galatnya wajib menyebut berkas yang mana.

**Teknis.** Nama isian menjadi larik (`berkas[]`), yang mengubah bentuk kiriman form.
Uji penjaga `memakai nama kolom kamus data pada isian form` memeriksa `name=` pada HTML,
sehingga perlu diperiksa apakah pola lariknya masih cocok. Uji
`menyediakan cara membuka berkas dari halaman rincian modulnya` menghitung tautan
`/dokumen/...` per halaman; menambah berkas contoh pada domain yang diuji akan
mengubah cacahnya, jadi angka harapannya ikut disesuaikan.

## Cara verifikasi

1. `php artisan test` kembali hijau. Kegagalan yang DIPERKIRAKAN hanya pada uji yang
   menghitung tautan berkas; bila ada yang lain, diperiksa lebih dulu sebelum disentuh.
2. Halaman `/infrastruktur/1`, `/pengaduan/1`, `/transmigran/1` membalas 200 dan
   menampilkan LEBIH DARI SATU berkas.
3. Penjaga privasi lacak publik tetap hijau.
4. `sim:tautan-statis` tetap 227; `pint --test` tetap 33 pre-existing.
5. Seluruh berkas tersunting bebas BOM.

## Yang TIDAK dikerjakan

Sembilan domain berpivot lainnya (rumah, kawasan, inventaris SP, fasilitas SP, alsintan,
penanaman, hasil panen, penanganan pengaduan, pengguna) menyusul setelah pola terbukti
di layar. Struktur basis datanya sudah siap, sehingga penerapannya tidak menuntut
perubahan skema lagi.

Penyimpanan sungguhan juga belum: unggahan masih berhenti di `DummyData` seperti seluruh
modul lain pada tahap ini.

## HASIL (diisi setelah pengerjaan)

**Selesai.** pest 727 PASS (7.415 assertions), `sim:tautan-statis` 227, pint 33 pre-existing.

Yang berbeda dari rencana, beserta alasannya:

1. **Prop `modul`/`idPemilik` dibuat lalu DICABUT lagi.** Rencana mengira komponen perlu
   memasang tautan buka berkas. Ternyata halaman rincian meng-include formnya sendiri,
   sehingga tautannya kembar: uji menghitung 5 tautan pada `/sp/infrastruktur/1`, bukan 2.
   Diputuskan tautan adalah milik panel rincian, form cukup menampilkan nama. Ditetapkan
   sebagai `rules.md` 14a.11b.

2. **Dua kontrol mati (R-26) ditemukan, yang tidak diperkirakan sama sekali.** Keduanya
   tidak memerahkan apa pun sebelum ini:
   - Panel Dokumen rincian transmigran masih membaca `dokumen_pendukung` yang sudah
     dicabut Putaran 12, sehingga menampilkan "Belum ada dokumen" padahal empat berkas
     nyata ada di registry.
   - Rincian infrastruktur menautkan `$data['foto']` yang hanya memuat satu nama,
     sehingga dua foto titik kerusakan lain tidak dapat dibuka.
   Keduanya adalah utang Putaran 12 yang baru terlihat ketika datanya benar-benar jamak.

3. **Tiga penjaga uji diperbarui, atas persetujuan pemilik proyek lebih dulu.** Sesuai
   aturan, pengerjaan BERHENTI dan melapor ketika uji merah, bukan menyesuaikan sendiri.
   Ketiganya menguji hal yang masih berlaku, tetapi mengasumsikan satu berkas.

4. **`berkasBukti` pada rute `pengaduan.detail` dicabut sebagai kode mati.** Rincian
   pengaduan tidak pernah meng-include formnya; form pengaduan hanya hidup di halaman
   indeks. Ketahuan saat memeriksa mengapa `bukti[]` tidak muncul di `/pengaduan/1`.

5. **Prop `wajib` ditambahkan** yang tidak ada di rencana, sebab isian KK sebelumnya
   `required` dan sifat itu akan hilang diam-diam bila tidak dibawa serta.

6. **Rencana "9 domain sisanya" pada bagian di atas TERNYATA SALAH.** Ditulis dari ingatan
   atas 14a.8b, tanpa memeriksa `schema.sql`. Setelah diperiksa: poktan, saprotan, dan SP
   memakai FK langsung (14a.8c), `user_berkas` sengaja dibatasi satu (14a.8d), dan
   `penanganan_pengaduan` tidak punya kolom id untuk dicocokkan. Yang layak jamak hanya
   EMPAT. Ini penegasan bahwa daftar kerja pun wajib diperiksa ke sumbernya, bukan
   diteruskan begitu saja.

7. **Dua kontrol mati tambahan ditemukan** di luar dua yang sudah dicatat: kartu kawasan
   (HPL dan peta tidak dapat dibuka dari mana pun) dan panel dokumentasi rumah (penjaga
   kosongnya membaca kunci lama). Totalnya EMPAT kontrol mati pada satu putaran, seluruhnya
   utang Putaran 12 yang baru terlihat ketika datanya benar-benar jamak.

8. **Dua Blade sempat rusak** oleh penghapusan rentang berbasis nomor baris yang meleset
   satu baris, mencabut `<div>` pembuka pada form fasilitas dan form kawasan. Ditangkap
   penjaga `menyeimbangkan tag HTML`, bukan oleh mata. Penyuntingan berbasis nomor baris
   pada berkas yang baru saja disunting menuntut pembacaan ulang lebih dulu.

9. **Satu temuan DILAPORKAN, tidak dikerjakan:** isian unggah pada Form Lahan tidak punya
   kolom, pivot, maupun kunci `DummyData` - berkasnya tidak akan tersimpan ke mana pun.
   Sisa pencabutan `dokumen_lahan` pada Putaran 12. Menyangkut keputusan skema, sehingga
   dicatat pada "Masih menunggu" di `tasklist.md` untuk diputuskan pemilik proyek.

---

# Putaran 13 - Migration dan Model Eloquent (Task 3.1) DITUNDA (2026-09-02)

> **DITUNDA sebelum satu baris kode pun ditulis.** Pemilik proyek menegur bahwa UI
> multi-unggah untuk 12 domain berpivot BELUM dikerjakan, padahal strukturnya sudah
> siap sejak Putaran 12. Melompat ke Tahap 3 adalah kekeliruan saya: pesan `lanjutkan
> tahap berikutnya` saya baca sebagai butir berikutnya pada `tasklist.md`, tanpa memeriksa
> lebih dulu adakah pekerjaan tertunda yang lebih dekat. Rencana di bawah beserta
> penyisiran lima sudutnya DIPERTAHANKAN agar tidak perlu disusun ulang, dan dipakai
> apa adanya ketika Task 3.1 benar-benar dikerjakan.

Rencana ditulis sebelum kode disentuh (`rules.md` 20b poin 12).

## Keputusan pemilik proyek

| # | Keputusan |
|---|---|
| 1 | **Urutan B lalu A**: migration + model dikerjakan LEBIH DULU tanpa menyentuh autentikasi; login, RBAC, dan penyesuaian uji menyusul pada putaran terpisah |
| 2 | Penerbitan GitHub Pages **dibatasi ke halaman publik** saat login aktif, bukan dihentikan. Diputuskan sekarang, diterapkan pada putaran berikutnya |

## Mengapa dipisah dari login

Mengaktifkan autentikasi mematikan sekitar 330 titik uji sekaligus: `TautanStatisTest`
menuntut 227 URL membalas 200, dan `HalamanTest` memuat 327 pemanggilan `\->get()`
tanpa login. Bila 44 model dibangun pada saat yang sama, kegagalan model tidak dapat
dibedakan dari kegagalan yang sekadar menuntut `actingAs()`.

Task 3.1 sendiri TIDAK menyentuh autentikasi, sehingga 727 uji wajib tetap hijau
sepanjang pengerjaan. Itu sekaligus penjaganya: satu saja uji berubah berarti ada yang
tersentuh di luar cakupan.

## Cakupan

Menerjemahkan `database/data/schema.sql` menjadi migration Laravel beserta Model
Eloquentnya. **Menerjemahkan, bukan menyusun ulang** (`tasklist.md` Tahap 3).

| Bagian | Isi |
|---|---|
| Migration | 61 tabel, urutan mengikuti dependensi pada `schema.sql` |
| Model | Satu per tabel bisnis, tanpa model bagi pivot murni yang cukup `belongsToMany` |
| Relasi | WAJIB menyebut kunci asing dan kunci lokal secara eksplisit (`rules.md` 4.0) |
| Soft delete | Hanya pada 22 tabel yang memang memilikinya; tabel riwayat dan referensi TIDAK |

## Penjaga yang wajib dipatuhi

1. **PK `id_<tabel>`, FK `<tabel>_id`** (`rules.md` 4.0). Berbeda dari asumsi bawaan Eloquent,
   sehingga tiap model wajib mendeklarasikan `\` dan tiap relasi wajib
   menyebut kuncinya. Melewatkannya menghasilkan query yang mencari `id` dan gagal senyap.
2. **Koordinat `DECIMAL(10,7)`**, bukan `GEOMETRY`.
3. **Uang `DECIMAL(15,2)`, luas `DECIMAL(12,2)`, volume panen `DECIMAL(12,3)`.**
4. **Berkas berupa path `VARCHAR(255)`**, tidak pernah BLOB.
5. **Nama tabel bentuk TUNGGAL**, sehingga `\` wajib disetel; Eloquent menjamakkannya.
6. Migration TIDAK boleh menambah atau mengurangi kolom terhadap `schema.sql`.

## Penyisiran lima sudut (rules.md 20a poin 10)

**Privasi.** Kosong pada putaran ini. Model belum dipakai rute mana pun, dan pembatasan
cakupan data baru dipasang bersama RBAC (5.0b-1). Yang perlu diingat: global scope
cakupan akan menempel pada model-model inilah, sehingga penamaan relasinya sekarang
menentukan kemudahan penegakannya nanti.

**Siklus hidup.** Referential action pada `schema.sql` sudah dipikirkan per relasi
(RESTRICT untuk master, CASCADE untuk anak sejati, SET NULL untuk opsional). Migration
wajib menyalinnya apa adanya; menerjemahkannya menjadi `cascadeOnDelete()` di mana-mana
akan menghapus data yang seharusnya menahan penghapusan induknya.

**Kejujuran angka.** Kosong. Tidak menyentuh satu pun rekap; `DummyData` tetap menjadi
sumber tampilan sampai putaran berikutnya.

**Alur kerja.** Model berdiri berdampingan dengan `DummyData` tanpa menggantikannya.
Peralihan tampilan dari data contoh ke Eloquent adalah pekerjaan terpisah, sebab ia
menuntut seeder terlebih dahulu agar halaman tidak mendadak kosong.

**Teknis.** Uji memakai SQLite `:memory:` dengan `RefreshDatabase` MATI, sehingga uji yang
ada tidak menyentuh basis data sama sekali. Menambahkan migration karena itu TIDAK
mengubah hasilnya, dan itu memang yang diharapkan. Konsekuensinya: kebenaran migration
tidak terjaga uji yang ada, sehingga wajib diverifikasi lewat `migrate:fresh` nyata ke
MariaDB dan dibandingkan terhadap `schema.sql`.

Perbedaan dialek juga perlu diperhatikan: `schema.sql` memakai ENUM MySQL, sedangkan
SQLite tidak mengenalnya. Bila kelak uji memakai basis data, ENUM diterjemahkan menjadi
`string` berpenjaga aplikasi.

## Cara verifikasi

1. `php artisan migrate:fresh` ke MariaDB nyata berhasil tanpa galat.
2. **Bandingkan hasilnya terhadap `schema.sql`**: jumlah tabel, jumlah kolom per tabel,
   dan jumlah foreign key wajib sama. Ini penjaga terpenting putaran ini, sebab uji
   yang ada tidak menyentuh basis data.
3. `php artisan test` wajib tetap **727 PASS**. Satu saja berubah berarti ada yang
   tersentuh di luar cakupan.
4. `sim:tautan-statis` tetap 227; `pint --test` tetap 33 pre-existing.
5. Model dimuat dan relasinya dipanggil tanpa galat kunci.

## Berkas yang akan disunting

- `database/migrations/` (baru, banyak berkas)
- `app/Models/` (baru, satu per tabel bisnis)
- `app/Models/User.php` (disesuaikan: PK `id_user`, tabel `user`, kolom mengikuti skema)
- `database/migrations/0001_01_01_000000_create_users_table.php` (dicabut, digantikan)

TIDAK disentuh: `DummyData`, view, rute, dan seluruh berkas uji.

---

# Putaran 12 - Registry Berkas Terpusat + Pembetulan Penempatan HPL/SHM SELESAI (2026-09-02)

Rencana ditulis sebelum kode disentuh (`rules.md` 20b poin 12).
Pemicu: audit khusus upload foto/dokumen/lampiran, dilanjutkan diskusi panjang yang
menyingkap kesalahpahaman mendasar tentang HPL dan SHM.

## Temuan yang mengubah segalanya

Audit semula menyimpulkan desain berkas `MEMADAI DENGAN PERBAIKAN KECIL` dan
`dokumen_lahan` adalah pengecualian yang tepat. Diskusi dengan pemilik proyek
membalik kesimpulan itu, dan pembaliknya benar:

**HPL adalah dokumen KAWASAN, bukan dokumen bidang.** `rules.md` 7.4a sebenarnya sudah
menuliskannya (`HPL adalah Hak Pengelolaan milik instansi atas tanah kawasan sehingga
tidak pernah menjadi hak seorang transmigran`), tetapi tabel `dokumen_lahan`
menempelkannya ke tiap bidang. Dari situlah lahir pivot M:N `dokumen_lahan_bidang`:
ia menambal akibat, bukan sebab.

**SHM meliputi seluruh lahan satu KK**, yaitu pekarangan DAN lahan usaha sekaligus.
Menempelkannya ke `lahan_id` memaksa satu sertifikat diunggah dua kali per KK.

Setelah keduanya ditempatkan benar, `dokumen_lahan` dan pivotnya hilang TANPA
kehilangan kemampuan apa pun. Bukan dikorbankan, melainkan memang salah tempat.

## Keputusan pemilik proyek (mengikat)

| # | Keputusan |
|---|---|
| 1 | Registry `dokumen` terpusat, Opsi Y: PK BIGINT + kolom `uuid`, bukan UUID sebagai PK |
| 2 | Buang `public_link` (lawan 14a.6), `updater` (kalah dari `audit_log`), `deskripsi` |
| 3 | `is_gcs` diganti `disk VARCHAR` agar sejalan konsep disk Laravel |
| 4 | `user_id` NULLABLE: kanal publik mengunggah tanpa akun (10b.1) |
| 5 | Multifile lewat PIVOT per domain, bukan polymorphic; single-file lewat FK langsung |
| 6 | `dokumen_lahan` + `dokumen_lahan_bidang` DIHAPUS |
| 7 | HPL pindah ke kawasan, dan kawasan jadi MULTIFILE (HPL + SK + peta) |
| 8 | SHM pindah ke `transmigran`, diunggah sekali, meliputi kedua bidang |
| 9 | `transmigran.status_sertifikat` enum Sudah/Belum/Belum Didata |
| 10 | Surat keterangan pembagian tanah TIDAK didata |
| 11 | `alsintan` induk dapat foto, sejajar `saprotan` |
| 12 | Hapus `pola_tanam`, `peralatan_pertanian`, `kendala` dari lahan |
| 13 | **Lahan: TEPAT 1 pekarangan + 1 usaha**, tidak boleh lebih |
| 14 | Seluruh dokumen terkait diperbarui |
| 15 | Dikerjakan BERTAHAP; beberapa tahap boleh digabung bila tidak konflik |

## Aturan yang dicabut atau diubah

| Aturan | Tindakan |
|---|---|
| 7.2 | Ubah - dokumen tidak lagi menempel pada bidang |
| 7.6, 7.6a | **Cabut** - dokumen lahan jadi berkas biasa; nomor/tanggal tidak didata |
| 7.7 | **Cabut** - pola tanam, peralatan, kendala dihapus |
| 7.8, 7.9 | Ubah - jumlah bidang kini BATAS yang ditegakkan, bukan sekadar kewajaran |
| 7bc.3 | **Cabut** - satu dokumen banyak bidang tidak lagi ada |
| 7.4a | Ubah - rantai HPL/SHM ditegaskan beserta tempat penyimpanannya |
| 14a | Perluas - registry, `mime`/`ukuran` wajib, `disk` |

## Penyisiran lima sudut (rules.md 20a poin 10)

**Privasi.** Registry menyatukan metadata seluruh berkas, termasuk dokumen kependudukan.
Ia menjadi satu tempat yang, bila bocor, memetakan seluruh berkas sistem. Peredamnya:
registry hanya menyimpan METADATA, bukan isi; dan aksesnya tetap lewat
`DokumenController` yang wajib memeriksa izin serta cakupan SP (5.0b-1 poin 11).
Cakupan SP tetap dapat ditegakkan justru KARENA memakai pivot, bukan polymorphic:
tiap pivot punya FK tetap ke induknya, sehingga penyaring induk ikut menyaring berkasnya.

**Siklus hidup.** Paling menuntut perhatian. Menghapus baris domain tidak lagi otomatis
membuang path berkasnya, sebab berkasnya kini hidup di tabel lain. Aturannya:
pivot `ON DELETE CASCADE` terhadap induk domain, sehingga tautannya hilang; baris
`dokumen` sendiri memakai soft delete agar berkas fisiknya dapat dibersihkan
terjadwal, bukan seketika. FK langsung memakai `ON DELETE SET NULL`: menghapus
berkas tidak boleh menghapus barisnya.

**Kejujuran angka.** `status_sertifikat` lahir justru dari sudut ini. Menghitung
`belum bersertifikat` dari ketiadaan unggahan mencampur dua keadaan yang berbeda:
belum punya sertifikat, dan punya tetapi belum diunggah petugas. Nilai `Belum Didata`
memisahkan keduanya, sepola dengan 10a.4c yang menolak baris hilang sebab `pembaca
tidak dapat membedakan tidak ada dari belum didata`.

**Alur kerja.** Data lahan yang sudah terlanjur punya dua bidang berperuntukan sama
akan DITOLAK UNIQUE baru. Data contoh sudah diperiksa: 0 pelanggaran, sehingga tidak
ada yang perlu dibereskan lebih dulu. Pada data nyata kelak, impor wajib memeriksanya
sebelum dijalankan. Petugas juga kehilangan kemampuan mencatat lahan usaha di dua
petak terpisah; ini konsekuensi sadar dari keputusan 13.

**Teknis.** Menyentuh 17 tabel, ~25 titik frontend, `PenyimpananDokumen`,
`LaporanData`, dan penjaga uji pada `HalamanTest.php` yang berukuran 343 KB.
Kolom `pola_tanam` ikut terpakai Laporan Transmigran, sehingga satu kolom laporan
hilang dari cetakan. Tidak menyentuh rute, sehingga `sim:tautan-statis` diharapkan
tetap 227.

## Urutan pengerjaan

```
T1+T2 (digabung: saling terkait) : schema.sql + DummyData  -> test
T3                               : domain lahan            -> test
T4                               : SHM/HPL/status_sertifikat -> test
T5                               : frontend + PenyimpananDokumen -> test
T6                               : dokumentasi lengkap
```

Baseline: 729 PASS / 7.410 assertions, `sim:tautan-statis` 227, `pint` 33 pre-existing.

## Struktur tabel dokumen

| Kolom | Tipe | Catatan |
|---|---|---|
| `id_dokumen` | BIGINT UNSIGNED | PK; integer lebih ringan sebagai indeks (4.0a.1) |
| `uuid` | CHAR(36) UNIQUE | pengenal publik |
| `jenis_dokumen_id` | BIGINT NULL | FK `referensi`; NULL = tanpa penggolongan |
| `nama_file` | VARCHAR(255) | nama tersimpan |
| `nama_asli` | VARCHAR(255) NULL | nama dari pengunggah, dipakai saat unduh |
| `path` | VARCHAR(255) | relatif terhadap disk |
| `mime` | VARCHAR(127) | hasil sniffing, bukan klaim klien (14a.2) |
| `ekstensi` | VARCHAR(10) | |
| `ukuran` | BIGINT UNSIGNED | byte; menegakkan batas 5 MB (14a.1) |
| `disk` | VARCHAR(20) | `local` / `s3` / `gcs`; menyiapkan 2.2.6 |
| `keterangan` | VARCHAR(500) NULL | mis. tampak samping; menggantikan kolom per-sisi |
| `user_id` | BIGINT NULL | NULL = unggahan kanal publik |
| timestamps | | + `deleted_at` |

**12 pivot multifile:** transmigran, rumah, lahan(-), infrastruktur, fasilitas_sp,
inventaris_sp, pengaduan, penanganan_pengaduan, penanaman, hasil_panen, alsintan,
kawasan_transmigrasi. Lahan TIDAK dapat pivot sebab tidak ada dokumen tingkat bidang.

**FK langsung (single-file):** user, satuan_permukiman, poktan, saprotan(2),
alsintan_distribusi.

---

## Hasil Putaran 12

| Bagian | Hasil |
|---|---|
| T1 skema | Registry `berkas` + 12 pivot + 5 FK langsung; `dokumen_lahan` dan pivotnya dicabut; 3 kolom lahan dicabut; UNIQUE lahan; `status_sertifikat`. **60 jadi 61 tabel** |
| T2 data | `berkas()` 23 baris + `berkasPemilik()` 19 tautan + helper `berkasMilik/berkasSatu/cariBerkas`; **0 orphan, 0 tanpa mime/ukuran** |
| T5 frontend | `lekatkanBerkas()` menempelkan nama berkas ke kunci lama, sehingga 25 titik view tidak disunting satu per satu |
| T3+T4 domain | SHM ke transmigran, HPL ke kawasan, tab Legalitas jadi bacaan + tautan, tab Pengelolaan dihapus |
| T6 dokumen | `rules.md` 7.6-7.9 + 7bc.3 + 14a.8-10, `data-dictionary.md` bagian 4b, `erd.md`, `notes.md`, `tasklist.md` |

### Verifikasi

| Pemeriksaan | Hasil |
|---|---|
| Impor `schema.sql` ke MariaDB 10.4 | **tanpa galat**; 61 tabel, 94 FK dibuat engine, 17 menunjuk `berkas` |
| UNIQUE lahan | bidang ketiga DITOLAK: `Duplicate entry '1-Lahan Usaha'` |
| Multi-berkas via pivot (FK aktif) | satu KK memegang SHM + KTP sekaligus |
| `php artisan test` | **727 PASS / 7.409 assertions** |
| `sim:tautan-statis` | 227, tidak berubah |
| `pint --test` | 33 pre-existing |
| BOM | seluruh berkas tersunting bersih |

### Yang berubah dari rencana

**Penamaan `dokumen` jadi `berkas`** atas usulan pemilik proyek di tengah T1, sebab tabelnya
menampung foto juga. Berbasis data: `berkas` 302 kemunculan di kode, `file` hanya 45.
Diangkat tepat waktu, sebelum 25 titik frontend memakainya.

**Siklus FK `user` dan `berkas`** tidak terlihat saat perencanaan. Ditemukan ketika
memeriksa urutan `CREATE TABLE`, dan diputus dengan pivot `user_berkas`.

**T5 digabung ke T2** atas persetujuan pemilik proyek. Rencana semula menaruh verifikasi
uji di akhir T2, dan itu tidak realistis: data dan view harus berpindah bersama, sehingga
memisahkannya meninggalkan suite merah di antara keduanya.

### Kekeliruan saya pada putaran ini

**Penghapusan kolom multi-baris menyisakan koma menggantung** pada `DummyData`, yang
menghasilkan `Cannot use empty array elements`. Terdeteksi `php -l` sebelum uji dijalankan.

**Menghapus tab Pengelolaan menyisakan `@endif` yatim**, sehingga `/lahan/1` berbalas 500.
Terdeteksi lewat pemeriksaan halaman, bukan lolos ke uji.

### Belum dikerjakan

UI multi-unggah untuk 12 domain berpivot. Struktur sudah siap menampungnya; komponen
`x-sim.file-upload` masih single-file dan dinaikkan bertahap per modul tanpa mengubah
skema lagi. Keputusan pemilik proyek pada awal putaran.

---

# Putaran 11 - Perbaikan Pra-Backend (audit menyeluruh) BERJALAN (2026-09-02)

Rencana ini ditulis sebelum kode disentuh, sesuai `rules.md` bagian 20b poin 12.
Pemicu: audit menyeluruh pra-backend atas permintaan pemilik proyek.
Catatan hasil (setelah selesai): `agents/notes.md`, `agents/tasklist.md`.

## Urutan bagian (disepakati pemilik proyek)

```
F'1 SELESAI -> A + C2 SELESAI -> C1 + C3 (BERJALAN) -> D1 + D3 + F'4
  -> E + F'2 + F'3 -> B + D2 + F'5
```

Baseline verifikasi tiap bagian: `php artisan test` = 729 PASS / 6.377 assertions.

## Keputusan pemilik proyek yang mengikat

| # | Keputusan |
|---|---|
| 1 | Rumah dinaut `transmigran_id`, pemetaan mengikuti data contoh yang ada |
| 2 | `daerah_asal` jadi FK ke `kabupaten`; `pekerjaan_kepala_keluarga` TETAP teks bebas + datalist |
| 3 | Dataset wilayah: 38 provinsi + 552 kab/kota dari `database/data/wilayah_indonesia.sql`; nama apa adanya (Title Case + awalan Kabupaten/Kota) |
| 4 | Kecamatan + desa penuh DITUNDA ke Tahap 3; sementara tetap wilayah lokus (4 kecamatan, 6 desa) |
| 5 | Halaman Master Wilayah: 4 tab DIHAPUS, ganti satu datatable + filter Tingkat bercantum jumlah |
| 6 | Form SP: blok Penempatan Wilayah pindah ke Section 1; Kawasan -> (kabupaten) -> Desa |
| 7 | UUID pada URL: TIDAK diubah sekarang, dicatat sebagai keputusan mengikat tahap Model |
| 8 | Nomor pengaduan berbagian acak dikerjakan sekarang di DummyData |
| 9 | Dead code (`dashboard/sp.blade.php`, `/galeri-komponen`, `/uji-403`) DITUNDA ke pra-deploy |
| 10 | Test gagal -> BERHENTI dan lapor, tidak menyesuaikan sendiri |

## Bagian yang SUDAH selesai pada sesi ini

### F'1 - Betulkan rujukan berkas skema (dokumen saja)
8 titik `database/transmigrasi.sql` -> `database/data/schema.sql`:
`tasklist.md` 920/928, `erd.md` 57/232/519, `data-dictionary.md` 564, `notes.md` 3427/3438.
Berkas dipindah pemilik proyek ke `database/data/` agar berkumpul dengan `wilayah_indonesia.sql`.
KOREKSI TEMUAN AUDIT: angka `44 tabel bisnis` pada dokumen ternyata BENAR
(50 CREATE TABLE - 6 tabel infrastruktur Laravel). Temuan audit yang menyebut 51 tabel keliru.

### A - Penautan rumah dan KK lewat id (blocker B-1)
| Titik | Berkas | Perubahan |
|---|---|---|
| A1 | `DummyData::rumah()` | +`transmigran_id` 6 baris (1->1, 2->2, 3->null, 4->3, 5->4, 6->null) |
| A2 | `DummyData::rumahKosong()` | saring `transmigran_id === null` |
| A3 | `DummyData::transmigranTanpaRumah()` | cocokkan `id_transmigran` |
| A4 | `routes/web.php` transmigran.detail | `firstWhere('transmigran_id', ...)` |
| A5 | `pages/rumah/form.blade.php` | `firstWhere('id_transmigran', ...)` |
| C2 | `DummyData::transmigran()` | hapus 7 duplikasi kunci `satuan_permukiman_id` |

Alasan A: penautan lewat nama PUTUS DIAM-DIAM saat suksesi mengganti nama kepala keluarga
(`rules.md` 6.5), dan dua KK senama menautkan rumah ke keluarga keliru. Skema sudah benar
lebih dulu (`uq_rumah_transmigran`), hanya data contoh dan frontend yang tertinggal.
Bukti perbaikan: `transmigranTanpaRumah()` semula mengembalikan 8 dari 8 KK (selalu gagal cocok),
kini `[5,6,7,8]`. `rumahKosong()` = `[3,6]`.
Verifikasi: 729 PASS / 6.377 assertions, sama dengan baseline.

## Bagian SELESAI: D3 (nomor pengaduan berbagian acak) (2026-09-02)

### Masalah

`rules.md` 4.0a poin 4 mewajibkan nomor pengaduan publik memuat BAGIAN ACAK, contoh
`PGD-2026-0001-K7F2M9`. Data contoh masih memakai `PGD-2026-0001` yang berurutan.

Ini bukan soal kerapian. Halaman lacak dapat diakses TANPA LOGIN, sehingga nomor
berurutan berarti siapa pun dapat menyusuri `PGD-2026-0001` sampai `PGD-2026-9999`
dan memanen status seluruh pengaduan warga. Uji privasi yang sudah ada
(`tidak pernah menampilkan data pribadi pelapor`) menjaga ISI halaman lacak, tetapi
tidak menjaga siapa yang dapat sampai ke sana.

### Konflik CMS yang ikut diselesaikan

`pages/cms/index.blade.php` menyediakan isian `Format Nomor Registrasi Tiket`
bernilai `PGD-{TAHUN}-{NOMOR}`, dan contohnya `PGD-2026-0042` tanpa bagian acak.
Artinya CMS menjanjikan dinas dapat menetapkan format yang justru melanggar 4.0a poin 4.

Keputusan pemilik proyek: **bagian acak WAJIB dan di luar kendali CMS.** Dinas mengatur
awalan dan pola nomor urut; suffix acak selalu ditambahkan sistem dan tidak dapat
dimatikan. Alasannya keamanan, bukan gaya penulisan.

### Bentuk nomor yang dipakai

`PGD-2026-0001-K7F2M9`: awalan, tahun, nomor urut empat digit, lalu **enam karakter**
acak. Alfabetnya sengaja TANPA huruf dan angka yang mudah tertukar (`0`/`O`,
`1`/`I`/`L`), sebab warga membacanya dari layar ponsel lalu menyalinnya ke
halaman lacak, dan salah baca satu karakter membuat laporannya seolah tidak ada.

Nomor urutnya DIPERTAHANKAN, tidak diganti acak seluruhnya: ia tetap berguna bagi
petugas untuk mengurutkan dan menyebut laporan, sedangkan bagian acaknya yang
menutup penyusuran.

### Berkas yang akan disunting

| Titik | Berkas | Perubahan |
|---|---|---|
| D3a | `DummyData::pengaduan()` | 9 nomor mendapat suffix acak |
| D3b | `DummyData::penangananPengaduan()` | 6 kunci peta riwayat ikut menyesuaikan |
| D3c | `routes/web.php` | nomor contoh pada balasan kirim pengaduan |
| D3d | `pages/cms/index.blade.php` | template + contoh + keterangan bahwa suffix acak di luar kendali dinas |
| D3e | `tests/Feature/HalamanTest.php` | 21 titik bernomor hardcoded |
| D3f | `rules.md` 4.0a poin 4 | ditegaskan: bagian acak di luar kendali CMS |

### Cara menyentuh berkas uji

Sebagian besar dari 21 titik itu SEHARUSNYA tidak pernah menuliskan nomor sendiri.
Uji yang mengetik `PGD-2026-0005` mengunci nilai data contoh, sehingga ia pecah tiap
kali datanya disunting sedikit pun. Bentuk yang benar adalah membacanya dari
`DummyData`, dan itulah yang dikerjakan di sini.

Contoh: `assertSee('PGD-2026-0005')` pada uji penyaring status menjadi membaca nomor
baris berstatus Selesai dari `DummyData::pengaduan()`. Ujinya menjadi LEBIH kuat,
sebab kini menegakkan hubungan status dengan barisnya, bukan sekadar mencocokkan teks.

Ini bukan melonggarkan uji. Yang dilonggarkan adalah keterikatannya pada nilai harfiah
yang memang bukan bagian dari perilaku yang dijaga.

### Penyisiran lima sudut (rules.md 20a poin 10)

**Privasi.** Inti bagian ini. Nomor tertebak membuat halaman lacak tanpa login dapat
disusuri berurutan. Uji privasi yang ada menjaga ISI halaman; bagian ini menjaga
AKSESNYA. Keduanya diperlukan, dan tidak saling menggantikan.

**Siklus hidup.** Nomor pengaduan TIDAK PERNAH berubah setelah terbit; warga sudah
mencatat atau memotretnya. Suffix acak karena itu dibangkitkan sekali saat penerbitan
dan ikut tersimpan, bukan dihitung ulang tiap kali dibaca. Pada data contoh nilainya
tetap, sebab data contoh memang mewakili nomor yang sudah terbit.

**Kejujuran angka.** Kosong. Tidak menyentuh satu pun rekap, agregat, maupun grafik;
rekap pengaduan mengelompokkan menurut kategori, status, SP, prioritas, dan bidang,
tidak satu pun memakai nomornya.

**Alur kerja.** Nomor menjadi lebih panjang dan lebih sulit disalin ulang dengan tangan.
Peredamnya sudah ada dan wajib dipertahankan: halaman kirim menampilkan nomor SANGAT
BESAR beserta anjuran mencatat atau memotretnya, ditambah tautan langsung ke halaman
lacak (Task 2.11b). Alfabet acaknya juga sengaja membuang karakter yang mudah tertukar.
Pencarian nomor pada halaman daftar petugas tetap bekerja sebab ia mencocokkan sebagian
teks, sehingga mengetik `PGD-2026-0001` tanpa suffix tetap menemukan barisnya.

**Teknis.** Rute lacak sudah menerima `[A-Za-z0-9\-]+` sehingga suffix lolos tanpa
perubahan. `DaftarTautanStatis` membangkitkan tautannya DARI DATA, sehingga ikut
menyesuaikan sendiri dan jumlah 227 seharusnya tidak berubah. Yang wajib diperiksa:
uji `TautanStatisTest` yang menuntut seluruh tautan membalas 200.

### Cara verifikasi

1. `php artisan test` wajib kembali 729 PASS. Kegagalan yang diperkirakan hanya pada
   21 titik bernomor; bila ada yang lain, berarti ada yang tidak terduga dan diperiksa
   lebih dulu sebelum disesuaikan.
2. `sim:tautan-statis` tetap 227, dan seluruhnya tetap membalas 200.
3. Halaman `/pengaduan`, `/pengaduan/1`, `/lacak-pengaduan`, dan lacak bernomor
   penuh seluruhnya 200.
4. Tiap nomor pada `penangananPengaduan()` wajib ada padanannya di `pengaduan()`,
   sebab keduanya dipetakan lewat nomor. Diperiksa langsung, bukan diandaikan.
5. `pint --test` tetap 33 berkas; seluruh berkas tersunting bebas BOM.

---

### Hasil D3

Sembilan nomor pengaduan mendapat suffix enam karakter. Alfabetnya membuang `0`, `O`,
`1`, `I`, dan `L` sebab warga menyalinnya dari layar ponsel.

`PGD-2026-0001-PMTUXK`, `0002-3EKHZA`, `0003-3NYVEN`, `0004-TGMZ79`,
`0005-96RY4X`, `0006-KCJSY6`, `0007-3YDKAW`, `0008-2QZY3Q`, `0009-669C3Z`.

| Pemeriksaan data | Hasil |
|---|---|
| sembilan nomor unik | YA |
| sesuai pola `PGD-YYYY-NNNN-XXXXXX` | 9 dari 9 |
| mengandung karakter mudah tertukar | 0 |
| kunci `penangananPengaduan()` punya padanan | 6 dari 6 |

**Konflik CMS diselesaikan.** Template `formatNomor` menjadi `PGD-{TAHUN}-{NOMOR}-{ACAK}`,
contohnya diperbarui, dan ditambahkan keterangan tampil bahwa bagian `{ACAK}` selalu
ditambahkan sistem dan tidak dapat dihilangkan, beserta alasannya. Sebelumnya CMS
menjanjikan dinas dapat menetapkan format yang justru melanggar `rules.md` 4.0a poin 4.

`rules.md` 4.0a mendapat poin 4a sampai 4d: bagian acak di luar kendali CMS, bentuk
enam karakter beralfabet aman, nomor urut dipertahankan, dan nomor tidak pernah berubah
setelah terbit.

### Uji: 21 titik berkurang menjadi satu komentar riwayat

Rencana memperkirakan 21 titik uji perlu disentuh. Yang benar-benar GAGAL hanya **5**,
sebab sisanya lolos lewat pencocokan sebagian: `assertSee('PGD-2026-0005')` tetap
cocok terhadap `PGD-2026-0005-96RY4X`.

Kelima yang gagal dibetulkan, dan **sembilan titik lain yang sebenarnya lolos ikut
dibereskan**. Alasannya: titik yang lolos hanya karena kebetulan awalannya sama akan
pecah diam-diam pada perubahan data berikutnya, dan ia menguji teks harfiah alih-alih
perilaku.

Bentuk yang dipakai adalah membaca nomornya dari `DummyData`, dan pada dua uji
penyaring hasilnya menjadi LEBIH kuat daripada sebelumnya:

| Uji | Sebelum | Sesudah |
|---|---|---|
| penyaring status/kategori/prioritas | mengetik nomor | membaca nomor LEWAT penyaring yang sama, sehingga menegakkan hubungan status dengan barisnya |
| penyaring bidang | mengetik nomor | membaca nomor lewat bidangnya sendiri |

Yang tersisa hanya satu kemunculan `PGD-2026` di berkas uji, yaitu komentar riwayat
tentang nomor yang dahulu tidak pernah ada. Itu memang catatan, bukan nilai yang diuji.

Jumlah uji tetap 729 dan assertions tetap 7.410.

### Dua hal yang ditemukan di luar rencana

**Placeholder halaman lacak terlewat dari daftar.** `publik/lacak.blade.php` memuat
`Contoh: PGD-2026-0001` tanpa suffix. Bila dibiarkan, contoh yang ditunjukkan kepada
warga justru mengajarkan bentuk yang salah, dan nomor yang diketik menurut contoh itu
tidak akan pernah ditemukan. Ikut dibetulkan.

**Nama kolom yang saya duga keliru.** Uji penyaring bidang sempat saya tulis membaca
`bidang_penanganan`, padahal kolomnya bernama `bidang`. Tertangkap uji, bukan
lolos, dan dibetulkan setelah memeriksa kunci sebenarnya pada data.

### Yang TIDAK diubah, beserta alasannya

Nama berkas dokumen tindak lanjut (`BeritaAcaraPeninjauan_pgd-2026-0001.pdf`) TETAP
memakai bentuk lama. Ia path berkas yang sudah tersimpan di disk, bukan nomor yang
ditampilkan atau dicari; mengubahnya berarti menunjuk berkas yang tidak ada.

### Verifikasi D3

| Pemeriksaan | Hasil |
|---|---|
| `php artisan test` | 729 PASS / 7.410 assertions |
| `sim:tautan-statis` | 227, tidak berubah; tautannya terbangkit dari data sehingga ikut menyesuaikan |
| `/lacak-pengaduan/PGD-2026-0001-PMTUXK` | 200 |
| `/pengaduan`, `/pengaduan/1`, `/pengaduan-warga`, `/cms` | 200 |
| `pint --test` | 33 pre-existing setelah CRLF dirapikan |
| BOM | seluruh berkas tersunting bersih |

---

## Bagian SELESAI: B + D2 + F'5 (2026-09-02)

Ketiganya DOKUMEN. Tidak ada kode aplikasi yang disunting, sehingga 729 uji wajib tetap
hijau tanpa satu pun penyesuaian.

### Koreksi terhadap temuan audit sendiri

Laporan audit menyatakan seluruh pemanggilan `DummyData` terpusat di
`ViewServiceProvider`, dan menjadikannya alasan bahwa seam cakupan data sudah
setengah tersedia. Itu KELIRU dan dikoreksi di sini.

| Tempat | Panggilan `DummyData::` |
|---|---|
| `routes/web.php` | **167** panggilan, 65 metode berbeda |
| `ViewServiceProvider` | 59 |
| view Blade | 0 |

Yang benar: VIEW tidak mengambil datanya sendiri, dan itu memang penjaga yang berlaku
(Ide C). Tetapi pengambilan datanya tersebar di 167 titik pada closure rute, bukan
terpusat. Angka inilah yang menentukan bentuk rancangan seam, sehingga kekeliruannya
berdampak nyata, bukan sekadar salah hitung.

### Fakta yang dikumpulkan sebelum merancang

**13 tabel membawa `satuan_permukiman_id` langsung:** `user_satuan_permukiman`,
`rute_aksesibilitas_sp`, `inventaris_sp`, `fasilitas_sp`, `fasilitas_sp_cakupan`,
`penilaian_sp`, `transmigran`, `rumah`, `poktan`, `lahan`, `infrastruktur`,
`infrastruktur_sp`, `pengaduan`.

**Sisanya mewarisi SP lewat induknya,** dan inilah bagian yang menentukan:

| Tabel | Mewarisi lewat |
|---|---|
| `anggota_keluarga`, `riwayat_kepala_keluarga` | `transmigran` |
| `riwayat_penghunian` | `rumah` dan `transmigran` |
| `anggota_poktan`, `komoditas_poktan`, `penanaman` | `poktan` |
| `hasil_panen` | `penanaman` lalu `poktan` |
| `alsintan_distribusi`, `saprotan_distribusi` | `poktan` |
| `dokumen_lahan_bidang` | `lahan` lalu `transmigran` |

`alsintan` dan `saprotan` INDUK sengaja tanpa SP: sejak Putaran 7 barisnya
mendeskripsikan bendanya, dan SP baru muncul pada baris distribusinya. Pengadaan yang
belum disalurkan tidak berada di SP mana pun, sehingga menyaringnya per SP akan
menyembunyikan barang gudang UPT dari semua orang.

`user_satuan_permukiman` sudah ada lengkap dengan UNIQUE `(user_id, satuan_permukiman_id)`.

### Penyisiran lima sudut (rules.md 20a poin 10)

**Privasi.** Inti bagian ini. Cakupan data adalah pembatas yang menentukan apakah
Operator SP dapat membaca data kependudukan SP lain. Aturan yang paling mudah terlewat:
akun `Per SP` TANPA penugasan wajib melihat NOL data, bukan seluruhnya (5.0b poin 7).
Fail-closed, bukan fail-open.

**Siklus hidup.** Menghapus penugasan SP seorang operator langsung menyempitkan apa yang
ia lihat, dan itu memang maksudnya. Yang perlu diperhatikan: baris yang mewarisi SP
lewat induk ikut hilang begitu induknya tersaring, sehingga penyaringan wajib dipasang
pada induknya, bukan diulang pada tiap anak.

**Kejujuran angka.** Paling berbahaya dan wajib ditulis sebagai aturan. Bila cakupan
menyaring rekap dan dashboard, Operator SP akan melihat angka kawasan yang sebenarnya
hanya angka SP-nya. Tanpa keterangan cakupan pada judulnya, angka itu dapat disalin ke
laporan sebagai total kawasan. Ini persoalan yang sama dengan kewajiban menulis periode
pada rekap panen (9 poin 8b dan 8o).

**Alur kerja.** Operator SP yang membuka tautan ke data SP lain harus mendapat 404,
bukan 403: 403 menyatakan datanya ADA tetapi tidak boleh dilihat, dan itu sendiri
kebocoran. Halaman `/uji-403` yang ada sekarang tetap berguna untuk galat kewenangan
aksi, bukan untuk cakupan data.

**Teknis.** Penyaringan wajib terjadi di tingkat query, bukan koleksi hasil: menyaring
setelah mengambil membuat paginasi menghitung baris yang tidak boleh dilihat, sehingga
jumlah halaman membocorkan banyaknya data SP lain.

### Berkas yang akan disunting

| Titik | Berkas | Perubahan |
|---|---|---|
| B | `agents/rules.md` bagian 5.0b | rancangan seam ditulis sebagai aturan yang mengikat tahap backend |
| D2 | `agents/rules.md` bagian 4.0a | keputusan mengikat: Model lahir dengan `getRouteKeyName()` = `uuid` |
| D2 | `agents/tasklist.md` Task 3.8 | urutan penerapan UUID ditegaskan |
| F'5 | `agents/tasklist.md` Task 3.2 | teks `email atau NIK` dibetulkan menjadi email atau username |

TIDAK ada kode aplikasi yang disentuh. Rute tetap memakai id integer; mengubahnya
sekarang hanya menambah kerumitan di atas `DummyData` tanpa manfaat, sebab belum ada
Model yang dapat menerjemahkan pengenal publik menjadi kunci internal.

### Cara verifikasi

1. `php artisan test` tetap 729 PASS. Bila ada satu saja yang berubah, berarti ada
   kode yang tersentuh dan itu di luar cakupan bagian ini.
2. `sim:tautan-statis` tetap 227; `pint --test` tetap 33 berkas.
3. Berkas dokumen bebas BOM.
4. Tidak ada rujukan silang yang menggantung: tiap nomor aturan yang disebut wajib ada.

---

### Hasil B + D2 + F'5

Seluruhnya dokumen. `git diff` atas `app`, `resources`, `routes`, dan `tests`
tidak bertambah satu baris pun pada bagian ini, dan 729 uji tetap hijau tanpa penyesuaian.

**B.** `rules.md` bagian **5.0b-1** baru, sembilan poin (8 sampai 16) yang mengikat
Tahap 3. Isi pokoknya:

| Poin | Ketetapan |
|---|---|
| 8 | Titik penegakan TUNGGAL berupa Eloquent global scope, melekat pada model bukan pemanggilnya |
| 8a | Penyaringan di controller dan di view DITOLAK, beserta alasannya masing-masing |
| 9 | Penyaring dipasang pada pemilik SP; 13 tabel membawa SP langsung, sisanya mewarisi lewat induk |
| 9a | `alsintan`/`saprotan` induk TIDAK disaring; yang disaring distribusinya |
| 9b | Data referensi tidak pernah disaring |
| 10 | Akun `Per SP` tanpa penugasan menerima NOL baris, bukan seluruhnya |
| 11 | Data tak berhak membalas 404, bukan 403 |
| 12 | Angka rekap yang menyempit wajib menyatakan cakupannya |
| 13 | Penyaringan mendahului paginasi |
| 14 | `Per Bidang` berdiri sendiri, hanya pada `pengaduan` |
| 15 | Satu jalan memintas untuk artisan/seeder, dan wajib eksplisit |
| 16 | Wajib disertai uji penjaga |

**D2.** `rules.md` 4.0a mendapat poin 5a, 5b, dan 5c. Intinya: Model yang tabelnya
berkolom `uuid` wajib lahir dengan `getRouteKeyName()` bernilai `uuid` sejak commit
pertamanya, sebab pada saat itu biayanya satu method per model sedangkan sesudahnya
menuntut penyisiran tiap `route()`. Rute tahap frontend sengaja TIDAK diubah sekarang.
Task 3.8 pada `tasklist.md` ditegaskan sebagai pekerjaan yang menempel pada pembuatan
Model, bukan task tersendiri di belakang antrean.

**F'5.** Butir Task 3.2 masih berbunyi `email atau NIK`. Catatan di atasnya sudah
menandainya usang sejak 2026-09-01, tetapi teks butirnya sendiri tidak pernah
dibetulkan, sehingga pembaca yang melompat langsung ke butir itu tetap membaca
ketentuan yang keliru. Kini dibetulkan menjadi email atau username, dengan ketentuan
lamanya dicoret beserta alasan pencabutannya.

### Koreksi terhadap laporan audit sendiri

Laporan audit menyatakan seluruh pemanggilan `DummyData` terpusat di
`ViewServiceProvider` dan menjadikannya modal bahwa seam cakupan data sudah setengah
tersedia. Pencacahan ulang menunjukkan sebaliknya:

| Tempat | Panggilan | Metode unik |
|---|---|---|
| `routes/web.php` | **167** | 65 |
| `ViewServiceProvider` | 59 | - |
| view Blade | 0 | - |

Yang benar hanyalah bahwa VIEW tidak mengambil datanya sendiri. Pengambilan datanya
justru tersebar di 167 titik pada closure rute. Kekeliruan ini berdampak nyata sebab
angka itulah yang menentukan bentuk rancangan: dengan 167 titik, penegakan per
pemanggilan berarti 167 peluang terlewat, dan yang terlewat gagal secara senyap.
Itulah alasan poin 8 memilih global scope, bukan penyaringan di pemanggil.

---

## Bagian SELESAI: E (Master Wilayah + Form SP) (2026-09-02)

### Keputusan pemilik proyek yang menambah rencana

| # | Keputusan |
|---|---|
| 15 | `DummyData::kawasan()` mendapat `kabupaten_id`. Rantai E3 menuntut id, dan teks `'kabupaten' => 'Malaka'` adalah cacat yang sama dengan B-1: penautan lewat nama. Skema sudah benar lebih dulu (`kawasan_transmigrasi.kabupaten_id` FK). Teks dipertahankan sebagai label. |
| 16 | Dua uji penjaga tab (`:5537` dan `:5548`) DIGANTI penjaga baru untuk datatable, bukan sekadar dihapus. Yang dijaga berpindah mengikuti fiturnya. |
| 17 | Dropdown induk pada `form-wilayah` memakai `x-sim.pilih-cari`, sebab setelah E2 ia memuat 514 kabupaten. |

### Masalah yang diperbaiki

**E1.** Halaman `/wilayah` memakai 4 tab (Provinsi, Kabupaten, Kecamatan, Desa) dengan
`x-sim.tabel-ringkas` yang merender SELURUH baris tanpa pencarian maupun paginasi.
Dengan 514 kabupaten setelah E2, tab itu tidak dapat dipakai. Mencari satu nama juga
menuntut petugas menebak lebih dulu ia ada di tab mana.

Riwayat menunjukkan tab ini sudah dua kali melahirkan cacat: tab bawaan yang keliru
(`wilayah.blade.php` 43-46) dan tingkat form yang tidak mengikuti tab (`form-wilayah`
13-14). Menghapus tab menghapus kelas cacat itu, bukan memperbaikinya untuk ketiga kali.

**E3.** `form-kawasan` SUDAH memakai pola dua tingkat yang teruji (`provinsiId` ->
`kabupatenTersaring` -> `gantiProvinsi()`), sedangkan form SP menampilkan seluruh desa
sekaligus. E3 meniru pola yang sudah ada, bukan mengarang baru.

### Berkas yang akan disunting

| Titik | Berkas | Perubahan |
|---|---|---|
| E2a | `DummyData::wilayah()` | provinsi + kabupaten dibaca dari `DataWilayah`; kecamatan + desa TETAP lokus |
| E2b | `DummyData::kawasan()` | tambah `kabupaten_id`; teks `kabupaten` tetap sebagai label |
| E1a | `pages/master/wilayah.blade.php` | 4 tab DIHAPUS; satu `x-sim.data-table` berkolom Nama, Tingkat, Induk, Kode, Aksi |
| E1b | `pages/master/form-wilayah.blade.php` | dropdown induk provinsi/kabupaten jadi `x-sim.pilih-cari` |
| E1c | `routes/web.php` | rute `/wilayah` menyusun baris rata (flat) lintas tingkat |
| E3a | `pages/sp/form.blade.php` | Kawasan sebelum Desa; desa disaring menurut kabupaten kawasan |
| E3b | `ViewServiceProvider` | suplai peta desa beserta `kabupaten_id` turunannya |
| E1d | `tests/Feature/HalamanTest.php` | 2 uji tab diganti penjaga datatable |
| E4 | `agents/` | catat E1 sampai E3 + rencana muat kecamatan/desa penuh di Tahap 3 |

### Yang TIDAK diubah

Kecamatan dan desa tetap wilayah LOKUS (4 dan 6 baris). Berkas sumber memuat 7.000
kecamatan dan 83.000 kelurahan, sedangkan `pilih-cari` menyematkan seluruh opsi ke
dalam HTML. Keduanya menunggu Tahap 3 ketika pemuatan bertahap lewat endpoint tersedia.
Keputusan pemilik proyek, tercatat sebagai butir 4 di atas.

Relasi kawasan TETAP ke kabupaten, BUKAN ke desa. Kawasan dan desa adalah dua cabang
terpisah yang bertemu di SP (`rules.md` 4a.2); penyaringan desa memakai kabupaten
milik kawasan, sehingga tidak ada relasi baru yang dikarang.

### Penyisiran lima sudut (rules.md 20a poin 10)

**Privasi.** Kosong. Wilayah administratif adalah data publik, tidak menunjuk orang,
dan tidak tunduk cakupan data.

**Siklus hidup.** Menghapus baris kabupaten yang masih ditunjuk transmigran ditolak FK
RESTRICT (dipasang pada D1), dan itu benar. Yang perlu diperhatikan: kawasan kini
menunjuk kabupaten lewat id, sehingga merapikan data master dapat memutus kaitannya.
Skema sudah memasang RESTRICT pada `fk_kawasan_kabupaten`.

**Kejujuran angka.** Tab lama menampilkan jumlah per tingkat pada judulnya, dan angka
itu HILANG bila tab dihapus begitu saja. Karena itu filter Tingkat wajib mencantumkan
jumlahnya, bukan sekadar nama tingkat. Tanpa itu pembaca kehilangan informasi yang
sebelumnya ada, dan penghapusan tab berubah menjadi kemunduran.

**Alur kerja.** Petugas yang terbiasa dengan tab akan mencari tab. Kolom Tingkat dan
filter Tingkat menggantikannya, dan blok penjelas hierarki di atas tabel DIPERTAHANKAN
sebab hierarki bercabang dua memang tidak lazim (`rules.md` 4a.2). Tautan lama
`?tab=` tidak lagi bermakna; ia diabaikan, bukan menghasilkan galat.

**Teknis.** Menyentuh berkas uji, sehingga dua penjaga tab diganti penjaga datatable.
Tidak menyentuh rute mana pun kecuali penyusunan datanya, sehingga `sim:tautan-statis`
seharusnya tetap 227. `form-wilayah` memakai `pilih-cari` yang menyematkan 514 opsi
ke HTML; ukurannya wajar, tetapi menjadi alasan tambahan mengapa kecamatan dan desa
penuh ditunda.

### Cara verifikasi

1. `php artisan test` wajib 729 PASS. Dua uji tab DIHARAPKAN gagal lebih dulu, lalu
   diganti penjaga datatable dengan jumlah uji yang sama.
2. `/wilayah` membalas 200 dan merender keempat tingkat dalam satu tabel.
3. Filter Tingkat memuat 4 pilihan beserta jumlahnya (38, 514, 4, 6).
4. `/kawasan` dan `/sp` tetap 200; form SP menyaring desa menurut kawasan terpilih.
5. `sim:tautan-statis` tetap 227; `pint --test` tetap 33 berkas.
6. Seluruh berkas tersunting bebas BOM.

---

### Hasil E

**E2.** `DummyData::wilayah()` membaca provinsi (38) dan kabupaten (514) dari
`DataWilayah`, menggantikan dua baris tulis tangan yang hanya memuat NTT dan Malaka.
Idnya berpindah ke kode BPS, sehingga Malaka menjadi 5321 dan bukan 1; kecamatan lokus
menyesuaikan diri. `kawasan()` mendapat `kabupaten_id`, dan `desaBerkabupaten()`
menurunkan kabupaten desa lewat kecamatannya, tidak menyimpannya.

**E1.** Empat tab pada `/wilayah` DICABUT, diganti satu `x-sim.data-table` berkolom
Nama, Tingkat, Induk, Kode, Aksi, beserta pencarian, paginasi, dan filter Tingkat yang
mencantumkan jumlah tiap tingkat. Dropdown induk pada `form-wilayah` memakai
`x-sim.pilih-cari`; kabupaten membawa keterangan provinsi sebagai pembeda nama kembar.

**E3.** Form SP menaruh Kawasan SEBELUM Desa, dan memilih kawasan menyaring daftar desa
menjadi desa pada kabupaten kawasan itu. Penyaringannya menempuh KABUPATEN, bukan relasi
kawasan-ke-desa yang memang sengaja tidak dimodelkan (`rules.md` 4a.2).

| Pemeriksaan | Hasil |
|---|---|
| `php artisan test` | 729 PASS, assertions 6.377 -> **7.410** (penjaga baru menguji lebih banyak) |
| kabupaten/kecamatan/desa yatim | 0 pada ketiga tingkat |
| `kawasan.kabupaten_id` 5321 ada di master | YA |
| `/wilayah` tanpa filter | 200, terpotong 25 baris per halaman |
| `?tingkat=desa`, `?cari=malaka`, `?per_halaman=100` | seluruhnya menyempitkan dengan benar |
| `/sp`, `/sp/1`, `/kawasan`, `/transmigran` | 200 |
| Kawasan dirender sebelum Desa | YA |
| `sim:tautan-statis` | 227, tidak berubah |
| `pint --test` | 33 (pre-existing) setelah CRLF berkas tersunting dirapikan |

### Tiga uji penjaga diarahkan ulang, bukan dilonggarkan

Ketiganya gagal persis seperti yang diperkirakan rencana, dan tidak satu pun dihapus
tanpa pengganti.

| Uji lama | Nasib |
|---|---|
| `membuka master wilayah dari tingkat teratas` | Diganti `menyatukan keempat tingkat wilayah dalam satu tabel`. Kini justru menegakkan bahwa `hashTabs` dan `role=tablist` TIDAK ADA lagi. |
| `menyesuaikan tingkat bawaan form wilayah dengan tab` | Diganti `menyaring daftar wilayah menurut tingkat beserta jumlahnya`. Menjaga hal yang menggantikan tab: jumlah per tingkat tetap terbaca, penyaring benar-benar menyempitkan, dan pencarian mencakup induk. |
| `menandai wajib isian induk wilayah secara bersyarat` | DIPERTAHANKAN, pemeriksaannya dipindah dari teks sumber ke HTML hasil render. |

Yang ketiga perlu dicatat alasannya. Ia memeriksa `name=` lewat `file_get_contents`,
sedangkan `x-sim.pilih-cari` merender atribut itu di dalam komponennya. Halamannya
sendiri BENAR: HTML hasil render tetap memuat ketiga `name=`, dan halamannya 200.
Jadi ujinya mengunci CARA implementasi, bukan perilakunya. Pola ini sudah dikenal repo
dan tercatat pada komentar `pilih-cari.blade.php` yang merujuk `notes.md` 1d.2.

Pemeriksaan pasangan bersyarat tetap berbasis sumber, sebab ekspresi Alpine memang
hidup di sana. Polanya dipersempit memakai `preg_match_all` yang menuntut awalan
`required=` atau `disabled=`; hitungan `substr_count` polos ikut menangkap
`x-show` sehingga menghasilkan 7, bukan 3.

### Kekeliruan saya pada bagian ini

**Ekspresi Alpine sempat ditulis berawalan titik dua.** `:required=` membuat Blade
menilainya sebagai PHP, sehingga muncul `Undefined constant tingkat` dan halaman
berbalas 500. Komponen membaca `\->get('required')`, sehingga ekspresinya
harus diteruskan sebagai atribut biasa tanpa titik dua.

**Suplai view baru sempat tidak didaftarkan.** `petaKawasanKabupaten` dipakai form SP
sebelum ditambahkan ke daftar kunci `pages.sp.form`, dan `/sp` berbalas 500.
Kekeliruan yang sejenis dengan tabrakan nama pada D1, dan sama-sama tertangkap
pemeriksaan halaman sebelum uji dijalankan.

### Yang TIDAK dikerjakan, beserta alasannya

Kecamatan dan desa penuh (7.000 dan 83.000 baris) TETAP DITUNDA ke Tahap 3 sesuai
keputusan pemilik proyek. Alasannya menguat setelah E1: `pilih-cari` menyematkan
seluruh opsi ke dalam HTML, dan itu masih wajar untuk 514 kabupaten tetapi tidak untuk
puluhan ribu desa. Pemuatan bertahap lewat endpoint baru tersedia setelah backend ada.

---

## Bagian SELESAI: D1 + F'4 (2026-09-02)

### Keputusan pemilik proyek yang menambah/mengubah rencana

| # | Keputusan |
|---|---|
| 11 | **D3 DITUNDA** ke bagian tersendiri. Alasan: menyentuh 21 titik uji bernomor hardcoded di `HalamanTest.php` + konflik format di CMS. Tidak dicampur dengan D1 agar diff tetap dapat ditinjau. |
| 12 | **Bagian acak nomor pengaduan WAJIB dan di luar kendali CMS.** Dinas boleh mengatur awalan dan pola nomor urut, tetapi suffix acak selalu ditambahkan sistem dan tidak dapat dimatikan lewat CMS. Alasannya keamanan (`rules.md` 4.0a poin 4), bukan gaya penulisan: halaman lacak dapat diakses tanpa login. Template CMS `PGD-{TAHUN}-{NOMOR}` sekarang BERTENTANGAN dengan aturan itu; diselesaikan saat D3. |
| 13 | `HalamanTest` penjaga nama kolom + `data-dictionary.md` diperbarui BERSAMA, bukan salah satu. |
| 14 | `sebaranDaerahAsal()` diubah menjadi berbasis `kabupaten_id`; label dibaca dari master saat render. |

### Masalah yang diperbaiki

`transmigran.daerah_asal` adalah `VARCHAR(255)` teks bebas TANPA indeks, tetapi menjadi
salah satu dari enam dasar rekap kependudukan (`rules.md` 10a poin 4a). Pada data nyata
ejaannya beragam: `KUPANG`, `Kab. Kupang`, dan `KABUPATEN KUPANG` menjadi tiga baris
berbeda meski menunjuk tempat yang sama. `UppercaseInput` menyeragamkan huruf besarnya,
tetapi tidak ejaannya. Kegagalannya SENYAP: angkanya tampak wajar, tidak ada yang memerah.
Cacat ini sudah diakui pada komentar `DummyData::sebaranDaerahAsal()` tetapi belum ditangani.

Nama kabupaten juga TIDAK unik secara nasional. Contoh nyata pada dataset: `Kabupaten Kupang`
(5301) berbeda dari `Kota Kupang` (5371), sedangkan data contoh hanya menulis `KUPANG`.
Karena itu isian memakai searchable dropdown yang menampilkan nama provinsi di baris kedua.

### Mengapa memakai tabel `kabupaten`, bukan `referensi`

Tabel `provinsi` dan `kabupaten` SUDAH ADA pada skema dengan UNIQUE komposit
`(provinsi_id, nama)`, yaitu justru penjaga yang dibutuhkan untuk nama kabupaten kembar.
Menaruh daerah asal pada tabel `referensi` berarti membuat daftar wilayah KEDUA yang
terpisah dari hierarki wilayah yang sudah ada, dan itu dua sumber kebenaran.
`referensi` adalah daftar datar untuk nilai enum-like; wilayah administratif punya hierarki.

### Berkas yang akan disunting

| Titik | Berkas | Perubahan |
|---|---|---|
| D1a | `app/Support/DataWilayah.php` (BARU) | 38 provinsi + 552 kab/kota dari `database/data/wilayah_indonesia.sql`. Berkas terpisah sebab `DummyData.php` sudah 291 KB. |
| D1b | `DummyData::transmigran()` | 8 baris: `daerah_asal` teks jadi `daerah_asal_kabupaten_id` |
| D1c | `DummyData::sebaranDaerahAsal()` | kunci jadi `kabupaten_id`; label dibaca master saat render |
| D1d | `database/data/schema.sql` | kolom jadi `daerah_asal_kabupaten_id BIGINT UNSIGNED NULL` + FK RESTRICT + indeks |
| D1e | `pages/transmigran/form.blade.php` | `<input text>` jadi `<x-sim.pilih-cari keterangan-opsi=provinsi>` |
| D1f | `ViewServiceProvider` | suplai `daftarKabupaten` ke form transmigran |
| D1g | `pages/transmigran/detail.blade.php` | baca label lewat master |
| D1h | `LaporanData.php` baris 709-710 | pengelompokan laporan ikut memakai label master |
| D1i | `pages/laporan/isi/transmigran.blade.php` | 4 titik pemakaian `daerah_asal` |
| D1j | `pages/kependudukan/rekap.blade.php` | rekap membaca label master |
| D1k | `data-dictionary.md` baris 603 | kolom + tipe diperbarui |
| D1l | `tests/Feature/HalamanTest.php` baris 302 | daftar nama kolom penjaga diperbarui |
| F'4 | `tasklist.md` Task 2.14 | empat dasar jadi ENAM (tahun, SP, status, pekerjaan, daerah asal, pendidikan) |

`pekerjaan_kepala_keluarga` TIDAK diubah. Keputusan `schema.sql` baris 617 (teks bebas
+ datalist) sudah benar: pekerjaan adalah himpunan TERBUKA dengan ekor panjang yang sah,
sedangkan daerah asal himpunan TERTUTUP. Memperlakukan keduanya sama adalah kekeliruan
analisis audit yang dikoreksi di sini.

### Penyisiran lima sudut (rules.md 20a poin 10)

**Privasi.** Daerah asal tampil pada Laporan Transmigran dan rekap kependudukan.
Pada rekap ia sudah berupa agregat, tidak menunjuk orang. Pada laporan ia per baris KK,
tetapi laporan memang untuk dinas dan sudah tunduk cakupan data saat RBAC aktif.
Tidak ada perubahan tingkat keterbukaan: yang berubah hanya cara nilainya disimpan.

**Siklus hidup.** Ini yang paling menuntut perhatian. Bila baris kabupaten dihapus
sedangkan masih ada transmigran yang menunjuknya, FK RESTRICT menolak penghapusan,
dan itu perilaku yang benar: daerah asal seorang transmigran tidak boleh lenyap
karena admin merapikan data master. Kolomnya NULL-able sebab data lama boleh belum terisi.

**Kejujuran angka.** Justru inilah yang diperbaiki: sebelum ini satu kabupaten dapat
terpecah menjadi beberapa baris rekap karena beda ejaan, dan totalnya tetap benar
sehingga tidak ada yang menyadari pembagiannya bocor. Penjaga yang sudah ada tetap
berlaku: keenam dasar rekap wajib menghasilkan total KK yang sama (rules.md 10a poin 4b).

**Alur kerja.** Data lama bertuliskan teks bebas tidak dapat dipetakan otomatis ke id
tanpa menebak. Pada tahap ini belum ada data nyata sehingga tidak ada migrasi yang
perlu dijalankan, TETAPI ini wajib diingat saat impor data produksi: perlu langkah
pemetaan manual, bukan konversi diam-diam. Dicatat sebagai kewajiban tahap backend.

**Teknis.** Dropdown memuat 552 opsi yang disematkan ke HTML lewat komponen pilih-cari.
Ukurannya wajar (ratusan baris, bukan puluhan ribu), sehingga tidak menuntut endpoint
bertahap. Kecamatan dan desa penuh (7.000 dan 83.000 baris) TETAP DITUNDA ke Tahap 3
justru karena alasan ini. Tidak menyentuh rute mana pun, sehingga sim:tautan-statis
seharusnya tetap 227.

### Cara verifikasi

1. `php artisan test` wajib 729 PASS. Satu uji DIHARAPKAN gagal lebih dulu
   (`HalamanTest` penjaga nama kolom), lalu dibetulkan bersama kamus data.
2. Muat `DataWilayah` lalu periksa: 38 provinsi, 552 kab/kota, tidak ada baris provinsi
   yang nyasar ke daftar kabupaten, dan setiap `provinsi_id` punya induk.
3. Periksa `Kabupaten Kupang` (5301) dan `Kota Kupang` (5371) sama-sama ada dan terpisah.
4. `sebaranDaerahAsal()` totalnya tetap sama dengan `ringkasanDashboard()['jumlah_kk']`.
5. Seluruh `daerah_asal_kabupaten_id` pada `transmigran()` wajib punya induk (tanpa orphan).
6. `sim:tautan-statis` tetap 227; `pint --test` tetap 33 berkas (pre-existing).

---

### Hasil D1 + F'4

`DataWilayah.php` baru: 38 provinsi + **514** kabupaten/kota.

KOREKSI RENCANA: rencana menulis 552 kab/kota. Angka itu keliru. Berkas sumber memuat 552
baris pada tabel `t_kota`, tetapi **38 di antaranya adalah baris PROVINSI yang nyasar**
(berkode 2 digit, mis. `('53', 'Nusa Tenggara Timur')`). Setelah disaring menurut panjang
kode, hasilnya 514 kabupaten/kota, dan itu memang jumlah resmi Indonesia. Rencana semula
menduga hanya ada 1 baris nyasar; dugaan itu salah dan ditemukan saat pemeriksaan data.

| Pemeriksaan data | Hasil |
|---|---|
| provinsi | 38 |
| kabupaten/kota | 514 (416 Kabupaten + 98 Kota) |
| kabupaten yatim (provinsi_id tanpa induk) | 0 |
| id ganda | 0 provinsi, 0 kabupaten |
| provinsi_id tidak cocok dua digit awal kodenya | 0 |
| Kabupaten Kupang vs Kota Kupang | 5301 dan 5371, terpisah benar |
| `daerah_asal_kabupaten_id` pada 8 transmigran | 0 orphan |
| total `sebaranDaerahAsal()` | 1.140 = `ringkasanDashboard()['jumlah_kk']` |

### Dua kekeliruan saya yang tertangkap uji

**1. Tabrakan nama variabel view.** Saya menamai suplai baru `daftarKabupaten`, padahal
nama itu SUDAH dipakai `pages.sp.form-kawasan` dengan bentuk berbeda (berkunci
`id_kabupaten`, terbatas wilayah lokus). Deklarasi saya menimpanya, dan `/kawasan`
berbalas HTTP 500. Diperiksa lewat `git stash`: 200 sebelum, 500 sesudah - jadi memang
kesalahan saya, bukan cacat lama. Diganti `opsiDaerahAsal`, dan alasannya ditulis di
tempatnya agar tidak terulang.

**2. Uji rekap kependudukan.** `HalamanTest` membandingkan kunci `sebaranDaerahAsal()`
dengan isi halaman. Setelah kuncinya menjadi id, ia mencari `5321` pada halaman yang
merender `Kabupaten Malaka`. Uji ini BENAR dan tidak dilonggarkan; yang dibetulkan
adalah sumbernya, menjadi `sebaranDaerahAsalBerlabel()` sesuai yang benar-benar dirender.

Keduanya membenarkan urutan kerja yang disepakati: uji dijalankan sebagai penjaga, dan
kegagalannya diperiksa lebih dulu alih-alih langsung disesuaikan.

### Temuan sampingan: satu lagi penautan lewat nama

`pages/laporan/isi/transmigran.blade.php` menyusun `petaRumah` berkunci NAMA penghuni,
yaitu cacat yang sama dengan blocker B-1 tetapi di tempat berbeda dan tidak ikut terbaca
saat audit awal. Ikut dibetulkan menjadi `transmigran_id` pada kesempatan ini, sebab
berkas yang sama memang sedang disunting.

### Verifikasi D1

| Pemeriksaan | Hasil |
|---|---|
| `php artisan test` | 729 PASS / 6.377 assertions, sama dengan baseline |
| `sim:tautan-statis` | 227, tidak berubah |
| `pint --test` | sempat 34 (berkas baru ber-CRLF), dibetulkan `pint` sehingga kembali 33 seperti sebelumnya |
| Halaman terdampak | `/kawasan`, `/transmigran`, `/transmigran/1`, `/kependudukan/rekap`, `/laporan/transmigran` seluruhnya 200 |
| BOM | seluruh berkas tersunting bersih |

### F'4

Task 2.14 pada `tasklist.md` menulis `Empat dasar pengelompokan`, sedangkan
implementasi dan `rules.md` 10a.4a memuat ENAM sejak daerah asal dan pendidikan
ditambahkan 2026-08-25. Butirnya dibetulkan beserta catatan bahwa daerah asal kini FK.

---

## Bagian SELESAI pada sesi ini: C1 + C3 (2026-09-02)

### C1 - Whitelist ekstensi berkas unggahan

Berkas: `app/Support/PenyimpananDokumen.php` baris 190 (`susunNamaBerkas()`).

Keadaan sekarang:
```php
$ekstensi = strtolower($berkas->getClientOriginalExtension() ?: $berkas->extension());
```
`getClientOriginalExtension()` membaca nama berkas yang DIKIRIM KLIEN, bukan isi berkasnya,
sehingga penyerang dapat menentukan ekstensi apa pun, termasuk `.php`. Belum dapat dieksekusi
sebab disk bersifat privat dan `DokumenController` mengalirkan isinya, tetapi ia menjadi
celah nyata begitu ada satu saja `Storage::disk('public')` atau symlink ke folder itu.

Rencana perubahan: dahulukan `extension()` (menebak dari MIME hasil pemeriksaan isi berkas),
jatuh ke ekstensi klien hanya bila tebakan kosong, lalu SARING lewat daftar putih.
Ekstensi di luar daftar ditolak dengan `InvalidArgumentException`, bukan didiamkan.

PENJAGA: daftar putih WAJIB memakai `ValidationRules::JENIS_BERKAS` yang sudah ada
(`jpg,jpeg,png,webp,pdf`), BUKAN daftar baru. Nilai itu sudah dipakai aturan validasi dan
atribut `accept` pada `components/sim/file-upload.blade.php`; membuat daftar kedua berarti
dua sumber kebenaran yang dapat berselisih diam-diam. Sejalan `rules.md` 14a poin 2
(gambar dan PDF, wajib divalidasi tipenya di sisi server) dan 13.2 poin 6 (validasi terpusat).

### C3 - Nama rute `sp.perbarui` terdaftar dua kali

Berkas: `routes/web.php` baris 201-204 dan 2706-2709. Keduanya `PUT /sp/{id}` dengan
pembatas `[0-9]+`; hanya nama parameter dan balikannya berbeda:

| Baris | Parameter | Balikan |
|---|---|---|
| 204 | `{sp}` | `back()` - tetap di halaman rincian SP |
| 2709 | `{id}` | `redirect()->route('sp.index')` - lompat ke daftar SP |

Laravel memakai deklarasi TERAKHIR, sehingga perilaku yang berlaku sekarang adalah 2709.
Akibatnya modal Ubah Data SP pada halaman rincian melempar pengguna ke daftar SP dan
membuang posisi tab, padahal `rules.md` 4c poin 5 justru menyatakan tab dijaga lewat
`?tab=` supaya posisinya bertahan saat form modal tersimpan.

Rencana: PERTAHANKAN baris 204 (`back()`), CABUT baris 2706-2709.
Alasan memilih yang bertahan: `back()` memenuhi 4c poin 5, dan halaman daftar SP tetap
benar sebab `back()` mengembalikan ke halaman asal mana pun, termasuk `/sp`.
Kebalikannya tidak berlaku: `redirect()->route('sp.index')` SELALU melempar ke daftar,
juga ketika penyuntingan dilakukan dari halaman rincian.
`sp.hapus` pada baris 2711-2714 TIDAK disentuh; ia tidak berganda.

Tidak ada view yang perlu diubah: pemanggilan `route('sp.perbarui', ...)` tidak ditemukan
di `resources` maupun `tests` (hanya dua deklarasinya sendiri), sebab alamat aksi modal
disusun `x-sim.aksi-baris`.

### Penyisiran lima sudut (`rules.md` 20a poin 10)

| Sudut | Temuan |
|---|---|
| Privasi | C1 memperkuat: berkas dokumen kependudukan tidak lagi dapat diberi ekstensi karangan penyerang. Pemeriksaan hak akses pada `DokumenController` TETAP belum ada (ditandai `ponytail:` baris 45-47) dan itu memang pekerjaan Tahap 3, di luar cakupan putaran ini. |
| Siklus hidup | C1: berkas LAMA yang terlanjur tersimpan berekstensi aneh tidak ikut terbetulkan; penyaringan hanya berlaku saat simpan/ganti. Pada tahap data contoh belum ada berkas nyata, sehingga tidak ada migrasi yang perlu dijalankan. Perlu diingat saat data produksi masuk. |
| Kejujuran angka | Kosong. Kedua perubahan tidak menyentuh satu pun rekap, agregat, maupun grafik. |
| Alur kerja | C1: berkas sah yang ditolak harus memberi pesan yang menyebut daftar jenis yang diterima, bukan gagal diam-diam. C3: memilih `back()` justru MEMPERBAIKI alur yang sekarang salah (terlempar ke daftar saat menyunting dari halaman rincian). |
| Teknis | C3 menyentuh daftar rute, sehingga `TautanStatisTest` dan `sim:tautan-statis` wajib diperiksa ulang. Rute yang dicabut adalah PUT, tidak masuk daftar tautan statis (hanya GET), jadi jumlah 223 seharusnya tidak berubah. C1 tidak menyentuh rute mana pun. |

### Cara verifikasi

1. `php artisan test` wajib tetap 729 PASS / 6.377 assertions.
2. `php artisan route:list` - pastikan `sp.perbarui` tinggal SATU baris.
3. `php artisan sim:tautan-statis` - jumlah tautan tetap 223.
4. C1 diperiksa lewat pemanggilan langsung `susunNamaBerkas()` dengan berkas beruji:
   nama `x.php` wajib DITOLAK, `x.pdf` dan `x.jpg` wajib diterima.
5. `vendor/bin/pint --test` diketahui GAGAL pada 33 berkas SEBELUM putaran ini
   (diverifikasi lewat `git stash`); yang dijaga adalah jumlahnya tidak bertambah.

### Berkas yang akan disunting pada C1 + C3

- `app/Support/PenyimpananDokumen.php`
- `routes/web.php`

---

### Hasil C1 + C3

C1: ditambahkan `PenyimpananDokumen::ekstensiDiterima()` dan `ekstensiAman()`.
`susunNamaBerkas()` kini memanggil `ekstensiAman()`, bukan `getClientOriginalExtension()`.
Daftar putih dibaca dari `ValidationRules::JENIS_BERKAS` sehingga tetap satu sumber kebenaran.
Ekstensi di luar daftar melempar `InvalidArgumentException` beserta daftar jenis yang diterima.

Diuji langsung dengan empat berkas nyata:

| Berkas uji | Hasil |
|---|---|
| PHP murni bernama `.php` | DITOLAK |
| PNG asli bernama `.php` | DITERIMA, tersimpan `.png` (bukan `.php`) |
| PNG bernama `.png` | DITERIMA `.png` |
| PDF bernama `.pdf` | DITERIMA `.pdf` |

Baris kedua adalah intinya: nama kiriman klien tidak lagi menentukan ekstensi tersimpan.

C3: deklarasi kedua `sp.perbarui` (`routes/web.php` blok Rute tulis tambahan) DICABUT,
diganti komentar yang menyebut alasannya. `sp.hapus` di sebelahnya tidak disentuh.

### Verifikasi C1 + C3

| Pemeriksaan | Hasil |
|---|---|
| `php artisan test` | 729 PASS / 6.377 assertions, sama dengan baseline |
| `route:list` nama `sp.perbarui` | 1 baris (semula 2): `PUT sp/{sp}` |
| Total rute | 152 -> 151, tepat satu yang hilang |
| Nama rute ganda lain | NIHIL di seluruh 151 rute |
| `pint --test` | 33 berkas gagal, sama persis dengan sebelum putaran ini |
| `sim:tautan-statis` | 227 tautan, sama sebelum dan sesudah |

KOREKSI RENCANA: rencana di atas menulis harapan 223 tautan statis. Angka sebenarnya 227
baik SEBELUM maupun SESUDAH perubahan (diperiksa lewat `git stash`), jadi tidak ada
yang berubah karenanya. Angka 223 berasal dari catatan Putaran 7 yang sudah usang.

CATATAN BOM: `Set-Content` PowerShell menyisipkan BOM UTF-8 yang sempat merusak
`PenyimpananDokumen.php` (galat `Namespace declaration statement has to be the very
first statement`). Sudah dibersihkan; berkas yang disunting sesi ini diperiksa bebas BOM.

---



# Putaran 10 — Audit Frontend, Penamaan Daftar Pilihan, Konsistensi Field SP Dilayani, & Modul Pengelolaan Konten (CMS) 5 Tab SELESAI (2026-09-01)

Rencana: `plan_penyempurnaan_frontend_dan_cms.md`, `plan_penambahan_upload_logo_favicon_cms.md`, `plan_audit_dan_konfigurasi_cms_laporan.md` & `walkthrough.md`.
Catatan hasil: `agents/notes.md` `## 6. Revisi`. Ringkasan: `agents/tasklist.md`, `agents/rules.md`, `agents/ui-spec.md`.

## 3 Pekerjaan yang Telah Diselesaikan:

### 1. Penamaan Ulang Submenu Data Master `Referensi` $\rightarrow$ `Daftar Pilihan`
- Mengubah submenu `Referensi` menjadi `Daftar Pilihan` pada `app/Helpers/MenuHelper.php` (`/master/referensi`).
- Mengubah header judul halaman menjadi `"Data Master Daftar Pilihan"` pada `resources/views/pages/master/referensi.blade.php`.
- Remah roti (breadcrumb) otomatis menyesuaikan: `Beranda > Data Master > Daftar Pilihan > [Nama Pilihan]`.

### 2. Konsistensi Relasi SP Lain yang Dilayani pada Detail Fasilitas & Infrastruktur
- Menambahkan blok informasi **"SP lain yang dilayani"** pada `resources/views/pages/sp/detail-fasilitas.blade.php` dan `resources/views/pages/infrastruktur/detail.blade.php`.
- Menyalurkan variabel data satuan permukiman dari closure route di `routes/web.php` untuk mematuhi aturan lint Ide C (*"view melarang mengambil datanya sendiri"*).

### 3. Pembangunan Antarmuka Frontend Pengelolaan Konten (CMS) 5 Tab (`/cms`)
- Mendaftarkan rute `GET /cms` dan `PUT /cms` di `routes/web.php` dengan izin `cms.lihat`.
- Menempatkan menu `Pengelolaan Konten` pada grup **Administrasi Sistem** (di antara `Data Master` dan `Pengaturan Sistem`) pada `MenuHelper.php` dan `sidebar.blade.php`.
- Membangun antarmuka interaktif 5 Tab berbasis Alpine.js di `resources/views/pages/cms/index.blade.php`:
  1. **Tab Identitas & Visual:** Nama sistem, subjudul kawasan, instansi pusat & daerah, kontak helpdesk, footer, serta form upload gambar lokal (Logo Utama PNG transparan, Logo Daerah, Favicon 32x32, Hero Banner) dengan live preview card & mock browser tab.
  2. **Tab Kop & Dokumen Laporan:** Pengaturan teks Kop Surat Dinas resmi (Kementerian, Pemda, Dinas, Alamat, Kontak, Logo Kiri & Kanan) serta Pejabat Penandatangan (Kota Titimangsa, Jabatan, Nama Pejabat, Pangkat/Golongan, NIP, Saklar Tampilkan Tanda Tangan) lengkap dengan live paper preview cetak A4.
  3. **Tab Konten Profil & FAQ:** Editor narasi Latar Belakang Halaman Tentang (`/tentang`) dan FAQ Panduan (`/panduan`).
  4. **Tab Portal Pengaduan Warga:** Sambutan, format nomor tiket `PGD-YYYY-XXXX`, jaminan kerahasiaan identitas, dan hotline darurat.
  5. **Tab Pengumuman Dinas:** Broadcast banner dasbor dengan saklar on/off, 4 level kegentingan (*Info, Sukses, Perhatian, Darurat*), dan live preview banner.

## Verifikasi
- **Pest PHP:** 728 test (6.149 assertions) lulus 100% hijau.
- **Vite Build:** `npm run build` terkompilasi bersih tanpa galat.

---

# Putaran 9 — Formatter Nominal Rupiah, Scope Poktan Transmigran, Peniadaan Filter Tab Tahun Rekap, & Urutan Field Form Lahan/Rumah SELESAI (2026-08-31)

Rencana: `C:\Users\v28mt\.gemini\antigravity-cli\brain\0fc4a1ba-ef33-4a8c-b8ea-138571694790\plan_dokumentasi_seluruh_revisi.md` & `walkthrough.md`.
Catatan hasil: `agents/notes.md` `## 6. Revisi` (butir 6). Ringkasan: `agents/tasklist.md`, `agents/rules.md`, `agents/ui-spec.md`.

## 4 Pekerjaan yang Telah Diselesaikan:

### 1. Reusable Currency Formatter Nominal Rupiah (`x-uang`)
- **Modul Sentral:** Membangun `resources/js/format-uang.js` dengan fungsi `bersihkanUang`, `formatUang`, `hitungPosisiKursor`, `pasangFormatUang(Alpine)`, serta mengekspos ke `window.formatUang`.
- **Interaksi & Sanitasi:** Pemisah ribuan titik murni (`1.000.000`), pengetikan karakter ilegal (huruf/minus/notasi) ditolak, paste teks dinormalisasi, navigasi kursor stabil, dan dukungan keyboard numerik mobile (`type="text" inputmode="numeric"`).
- **Programmatic & Submit Handling:** Intersepsi prototype descriptor `HTMLInputElement.value` untuk mencegah rekursi ganda pada modal ubah / Alpine `x-model`, serta global form `submit` capture listener yang menormalkan nilai `input[data-uang]` menjadi string integer murni (`1000000`) sebelum dikirim ke backend Laravel `ValidationRules::uang()`.
- **Implementasi Lapangan:**
  - Form Transmigran (`pendapatan_per_bulan` KK & repeater anggota keluarga).
  - Form Panen (`harga_jual`).
  - Form SP (`rute_aksesibilitas[*][ongkos_rp]`).

### 2. Peniadaan Card Filter Tahun Data pada Tab "Per Tahun" (Rekap Kependudukan)
- Menghilangkan card formulir filter Tahun Data khusus ketika tab "Per Tahun" aktif pada `/kependudukan/rekap` (`@if ($kelompok !== 'tahun') ... @endif`).
- Tab "Per Tahun" kini bersih langsung menyajikan tabel agregat deret waktu historis (2016–2026) tanpa kontrol dropdown yang tidak fungsional/mati, sementara filter tahun tetap aktif di 5 tab demografis lainnya (`sp`, `status`, `pekerjaan`, `asal`, `pendidikan`).

### 3. Penegasan Ruang Lingkup Anggota Poktan Khusus Transmigran
- Menegaskan batasan domain SIM Transmigrasi bahwa sistem **hanya mencatat anggota poktan yang merupakan warga/keluarga transmigran**, sedangkan anggota non-transmigran (penduduk lokal) tidak dicatat di sistem.
- **Titik Penegasan di Rincian Poktan (`pages/poktan/detail.blade.php`):**
  - Subjudul header: `"Kelompok tani di [SP], berdiri sejak [Tahun]. Pencatatan anggota khusus warga transmigran."`
  - Sidebar Profil: `Anggota transmigran aktif` dengan catatan kaki `Khusus warga transmigran`.
  - Tab Rincian: `Anggota Transmigran (n)`.
  - Banner Edukatif: Callout di atas tabel anggota menerangkan batasan ruang lingkup data.
  - Judul Tabel: `Anggota Kelompok Tani (Khusus Warga Transmigran)`.
- **Form Anggota (`pages/poktan/form-anggota.blade.php`):** Bantuan isian menegaskan bahwa anggota non-transmigran tidak didata pada SIM Transmigrasi.

### 4. Penyesuaian Urutan Field Transmigran & Auto-Fill Satuan Permukiman
- **Form Lahan (`pages/lahan/form.blade.php`):** Field `Pemilik` (transmigran) dipindahkan ke urutan pertama Section 1 sebelum `Satuan Permukiman`. Memilih Pemilik otomatis mengisi dan memilih dropdown Satuan Permukiman via Alpine reactive event.
- **Form Rumah (`pages/rumah/form.blade.php`):** Section 1 disusun menjadi `Penghunian & Wilayah` sebelum Section 2 `Spesifikasi Bangunan`. Saat status `Dihuni`, memilih KK Penghuni otomatis mengisi Satuan Permukiman. Saat status `Tidak Dihuni`, isian KK dinonaktifkan (`disabled`) dan pemilihan SP menjadi aktif manual.
- **Perbaikan Komponen (`resources/views/components/sim/pilih-cari.blade.php`):** Binding `:disabled` dan `:required` dirender dengan `{!! !!}` agar ekspresi JavaScript Alpine berkarakter petik tidak ter-escape menjadi HTML entity `&#039;`.

## Verifikasi
- **Pest PHP:** 523 test (3.363 assertions) lulus 100% hijau.
- **Browser Tests:** `uji-autofill-sp.mjs` (5/5 PASS), `uji-format-uang.mjs` (11/11 PASS).
- **Vite Build:** `npm run build` terkompilasi bersih tanpa galat.

---

# Putaran 8 — Visualisasi Dashboard, Optimasi Interaksi & Audit Warna SELESAI (2026-08-31)

Rencana: `C:\Users\v28mt\.gemini\antigravity-cli\brain\9333f50b-2b4d-449a-ab28-563179b31500\implementation_plan.md` & `walkthrough.md`.
Catatan hasil: `agents/notes.md` `## 6. Revisi`. Ringkasan: `agents/tasklist.md` & `agents/ui-spec.md` §9.

## 3 Pekerjaan Dashboard yang Telah Diselesaikan:

### 1. Perombakan Visualisasi "Ringkasan Kawasan" Dashboard
- Mengubah 12 kartu KPI datar menjadi **3 Pilar Domain Tematik Terstruktur**:
  1. **Pilar 1: Kependudukan & Hunian** (Navy `#163B54` / Blue-light) — 4.560 Jiwa, 1.140 KK, 2.280 Petani, Kapasitas Hunian 95% (1.140 / 1.200 unit) dengan bar visual.
  2. **Pilar 2: Lahan & Siklus Tanam** (Teal `#33809C` / Emerald) — 3.250 ha Total Kawasan, 1.140 ha Lahan Tergarap (35,08%), Siklus Tanam (630 ha Tanam $\rightarrow$ 24,60 ha Puso [3,9%] $\rightarrow$ 605,40 ha Panen [96,1%]) dengan stacked progress bar.
  3. **Pilar 3: Produksi & Nilai Pasar** (Gold `#C09546` / Sand `#DFB87E`) — 1.781 ton Produksi, 2,94 ton/ha Produktivitas Tertimbang, Rp 7,12 Miliar Estimasi Nilai Pasar, Jagung 65% Komoditas Unggulan.
- **Dual Y-Axis pada `#grafikPerSp`**: Memisahkan sumbu Jiwa (kiri) dan Luas Lahan ha (kanan) agar garis luas lahan tidak lagi tenggelam di dasar kanvas.
- **Smooth Area Chart pada `#grafikPendapatan`**: Visualisasi kurva pendapatan keluarga transmigran bernuansa area gradien lembut.

### 2. Optimasi Interaksi Scrolling & Touch Chart (Opsi C: Hybrid Responsive Gesture Model)
- **Prinsip UX:** `SCROLLING HALAMAN > INTERAKSI CHART`.
- **Desktop (`hover: hover`):** Hover mouse instan melihat tooltip (*zero-click friction*), mematikan internal zoom & selection listener di `opsiDasar()` (`chart-config.js`), `tooltip.followCursor: false` untuk mencegah lonjakan komputasi CPU saat scrolling cepat.
- **Mobile (`pointer: coarse` / Touchscreen):** Penegakan CSS native `touch-action: pan-y !important;` pada `.apexcharts-canvas`, `.apexcharts-svg`, `.apexcharts-inner`, `.apexcharts-grid-rect` di `app.css` dan utility `touch-pan-y` di `chart-card.blade.php`. Gestur *swipe* vertikal 100% diproses browser untuk menggulir halaman tanpa tersendat; *tap* singkat untuk mengunci tooltip titik data.
- **Konsistensi:** Diterapkan terpusat pada shared helper, mengamankan `#grafikPenduduk`, `#grafikPendapatan`, `#grafikHarga`, dan seluruh grafik lainnya.

### 3. Audit & Optimalisasi Warna Visualisasi Dashboard (Palet Kategorikal & Semantik)
- **Palet Komoditas Pertanian Khusus (`warnaKomoditas`)**: Memecahkan masalah irisan biru kembar pada Donut `#grafikKomoditas` (*Jagung* `#C09546` Gold, *Padi* `#2E7D32` Green, *Kacang Tanah* `#8E6E34` Bronze, *Ubi Kayu* `#DFB87E` Sand, *Cabai* `#D94841` Terracotta Red).
- **Palet Semantik Pengaduan (`warnaStatusPengaduan`)**: Menyelaraskan donat `#grafikStatusPengaduan` dengan status-badge sistem (*Diterima* Amber `#F79009`, *Diproses* Sky Blue `#0BA5EC`, *Selesai* Emerald `#12B76A`).
- **Pemisahan Kontras Dual-Bar Perbandingan SP (`#grafikPerSp`)**: Batang KK (Navy `#163B54`) vs Batang Panen (Gold `#C09546`).
- **Kontras Multi-Series Line Penduduk (`#grafikPenduduk`)**: Jiwa (Navy `#163B54`), KK (Sky Blue `#0BA5EC`), Petani (Gold `#C09546`).

## Verifikasi
- **Pest PHP:** 14 test Dashboard (75 assertions), 77 test DummyData (1.626 assertions) lulus 100% hijau.
- **Vite Build:** `npm run build` terkompilasi bersih tanpa error (`exit code 0`).

---

# Putaran 7 & 7 Poin Revisi Frontend SELESAI (2026-08-30)

Pola "induk + distribusi" untuk Alsintan, Saprotan, +3 temuan audit, +F2, dan 7 Poin Revisi Frontend.
Rencana: `C:\Users\v28mt\.claude\plans\linked-sprouting-aho.md` & Implementation Plan.
Catatan hasil: `agents/notes.md` §1w & `## 6. Revisi`. Ringkasan: `agents/tasklist.md`.

## 7 Poin Revisi Frontend & Perbaikan UI Scrollbar (Selesai):
1. **Revisi 1 (Detail Transmigran Scrollbar & Tab Cleanliness)**:
   - Mengubah CSS Grid kolom kanan menjadi `lg:grid-cols-[20rem_minmax(0,1fr)]` dan menyematkan `min-w-0 overflow-hidden` pada wadah kartu tab (`div.rounded-2xl`) serta panel tabel untuk mengisolasi pelebaran tabel 11 kolom dan mencegah bocornya *body horizontal scrollbar*.
   - Menghilangkan horizontal scrollbar native pada deretan Tab Header dengan menyematkan utility class `no-scrollbar` (`scrollbar-width: none` / `::-webkit-scrollbar: display: none`) di Detail Transmigran serta menstandarkannya di seluruh halaman bertab lainnya.
   - Mempertahankan 100% font size, caption, thead/tbody, dan styling asli tabel Anggota Keluarga, di mana scrollbar horizontal hanya terjadi secara lokal di dalam kartu tabel.
2. **Revisi 2 (Multi-Step Form)**:
   - Form Data Lahan: 3 langkah (*Identitas & Pemilik*, *Penggunaan & Lokasi*, *Legalitas & Berkas*).
   - Form SP: 4 langkah (*Identitas & Wilayah*, *Lokasi & Batas*, *Keadaan Alam & Iklim*, *Aksesibilitas & Berkas*).
   - Form Poktan: 3 langkah (*Identitas Kelompok*, *Pengurus & Legalitas*, *Anggota Kelompok Tani* + Dynamic Repeater).
3. **Revisi 3 (Master Data Jenis Inventaris)**: Penambahan `JenisReferensi::JenisInventaris` dengan opsi baku (*Peralatan Kantor*, *Elektronik & Mesin*, *Perabotan*, *Kendaraan Operasional*, *Peralatan Lainnya*), suplai view provider, dan dropdown wajib.
4. **Revisi 4 (Urutan Parent Form & Rename Data Lahan)**:
   - Parent SP diletakkan di urutan teratas pada `form-inventaris`, `form-fasilitas`, `infrastruktur/form`, `rumah/form`, `lahan/form`, `poktan/form`.
   - Rename menu & breadcrumb "Daftar Lahan" $\rightarrow$ "Data Lahan".
5. **Revisi 5 (Wording Form Transmigran)**: Label "Status Tinggal" $\rightarrow$ "Status Tinggal Keluarga".
6. **Revisi 6 (Upload KK Wajib)**: Upload Kartu Keluarga dijadikan mandatory (`:wajib="true"`) dengan label "Kartu Keluarga (KK)".
7. **Revisi 7 (Wording Subjudul Poktan)**: Subjudul halaman Poktan $\rightarrow$ "...beserta ketua dan jumlah anggota transmigrannya."

---

# Restrukturisasi Halaman Detail Satuan Permukiman (SP) & Rute RESTful (2026-08-31)

### 1. Standardisasi Rute RESTful
- Mengubah rute rincian SP dari `/dashboard/sp/{id}` menjadi `Route::get('/sp/{sp}', ...)->where('sp', '[0-9]+')->name('sp.detail')`.
- Memasang redirect 301 dari `/dashboard/sp/{id}` ke `/sp/{id}` untuk menjamin tautan lama dan penelusuran tetap bekerja.
- Menambahkan rute pembaruan data SP `Route::put('/sp/{sp}', ...)->name('sp.perbarui')`.
- Memperbarui 16 berkas view yang memanggil `route('dashboard.sp', ...)` menjadi `route('sp.detail', ...)`.

### 2. Restrukturisasi UI & Sistem 6 Tab Domain
- Memindahkan view ke `resources/views/pages/sp/detail.blade.php` dengan grid 2-kolom asimetris `lg:grid-cols-[20rem_minmax(0,1fr)]`.
- **Kolom Kiri (Sticky Sidebar):** Profil SP, kode SP, kecamatan/desa, tahun penempatan, luas lahan, status kondisi SP & skor kelayakan, kapasitas/keterisian KK, dokumen SK penetapan, catatan/keterangan wilayah, dan peta mini titik koordinat Leaflet OSM.
- **Kolom Kanan (6 Tab Domain Terpadu via `hashTabs()`):**
  1. `ringkasan`: 4 Stat Cards KPI (Skor Kelayakan, Jumlah KK, Realisasi Lahan Usaha, Total Produksi Panen), 2 ApexCharts (Tren Kependudukan KK & Volume Panen per Tahun), dan rincian 16 Parameter Layanan Dasar SP.
  2. `warga`: Tabel Warga Transmigran / KK dan Tabel Rumah & Hunian.
  3. `pertanian`: Tabel Bidang Lahan, Kelompok Tani (Poktan), dan Catatan Hasil Panen.
  4. `aset`: Tabel Infrastruktur Kawasan, Fasilitas Umum SP, dan Inventaris Operasional SP.
  5. `pengaduan`: Tabel Pengaduan Masuk SP beserta status & prioritasnya.
  6. `monografi`: Profil Geografis, Topografi, Tanah, Iklim Bab II Monografi SP & Tabel Rute Aksesibilitas.
- **Eliminasi Bar Switcher SP:** Bilah navigasi switcher 6 SP di atas halaman dihapus atas arahan user; navigasi antar-SP dilakukan melalui `/sp` atau breadcrumb.
- **Header Action:** Menambahkan tombol primer *"Ubah Data SP"* (membuka modal `formUbahSp`) dan tombol sekunder *"Kembali ke Daftar SP"* (`route('sp.index')`).

### 3. Verifikasi Mutu
- **Pest PHP:** 726 pengujian (6.110 assertions) 100% PASS (Hijau).
- **Vite Build:** `npm run build` terkompilasi bersih tanpa galat.
- **HTTP Endpoint:** `/sp/1` membalas 200 OK, `/dashboard/sp/1` membalas 301 Redirect ke `/sp/1`.


# Audit Menyeluruh Frontend, Phase A (Quick Wins UX/a11y), & Standardisasi Rute `/sp/infrastruktur` (2026-08-31)

### 1. Audit Menyeluruh Frontend
- Melakukan audit 24 tahap pada seluruh arsitektur antarmuka, 96 berkas tampilan Blade, 35 komponen sim, 8 berkas JavaScript, dan integrasi backend-readiness.
- Menyusun laporan audit komprehensif pada artefak `audit_komprehensif_seluruh_frontend.md`.

### 2. Standardisasi Rute RESTful `/sp/infrastruktur`
- Mengubah rute utama menjadi `/sp/infrastruktur` dan `/sp/infrastruktur/{id}` (`name: infrastruktur.index`, `infrastruktur.detail`, `infrastruktur.simpan`, `infrastruktur.perbarui`, `infrastruktur.hapus`).
- Memasang pengalihan permanen (HTTP 301) dari `/infrastruktur` dan `/infrastruktur/{id}` ke `/sp/infrastruktur`.
- Memperbarui `MenuHelper.php`, `RemahHelper.php`, dan view `pages/infrastruktur/*` agar seluruh aset wilayah (`/sp/inventaris`, `/sp/fasilitas`, `/sp/infrastruktur`) memiliki struktur rute simetris.

### 3. Eksekusi Phase A (Quick Wins UX & a11y)
- **A11y Live Regions (`aria-live="polite"`, `aria-atomic="true"`):** Dipasang pada field kalkulasi dinamis (Total Lahan Usaha di `pages/lahan/form.blade.php`, Puso & Produksi di `pages/panen/form.blade.php`, Usia KK di `pages/transmigran/form.blade.php`, dan Sisa Belum Ditanam di `pages/penanaman/form.blade.php`).
- **Indikator Visual Filter Aktif:** Menambahkan aksen latar & border (`border-brand-500 bg-brand-50 text-brand-700`) beserta dot badge pada komponen `x-sim.data-table` ketika ada filter aktif (`adaFilterAktif`).
- **Pembatalan Banner Panduan Rekap:** Banner panduan dokumen resmi pada `/panen/rekap` dan `/kependudukan/rekap` dibatalkan/dihapus sesuai arahan user demi menjaga kelapangan ruang vertikal layar dan kemurnian tata letak tabel agregat (ui-spec.md §2.2).

### 4. Verifikasi Mutu
- **Pest PHP:** 728 pengujian (6.120 assertions) **100% PASS (Hijau)**.
- **Vite Build:** `npm run build` sukses bersih.
- **HTTP Endpoint:** `/sp/infrastruktur` membalas 200 OK, `/infrastruktur` membalas 301 Redirect.


# Revisi Komprehensif UX/UI Menu Laporan Transmigran (2026-09-01)

### 1. Implementasi 4 Mode Tampilan Interaktif
- **Mode Gabungan (Terpadu / Alternatif 1):** Menggabungkan data Transmigran + Rumah + Lahan ke dalam satu tabel komprehensif berorientasi Kepala Keluarga dengan Multi-Level Grouped Header dan Sub-cell Stack untuk multi-bidang lahan.
- **Mode Data Transmigran:** Menampilkan rincian demografi 14 kolom lengkap Kepala Keluarga transmigran.
- **Mode Data Rumah:** Menampilkan inventarisasi fisik rumah, nomor rumah, penghuni, kondisi bangunan, status hunian, tahun bangun, dan luas bangunan.
- **Mode Data Lahan:** Menampilkan inventarisasi seluruh bidang lahan pekarangan dan usaha dengan komposisi luas kering/basah dan pola tanam.
- **Pill Tab Selector:** Disediakan bilah navigasi mode di bagian atas tabel dengan deskripsi peran masing-masing mode.

### 2. Penyaring Cerdas & Pencarian Kata Kunci Sisi Klien
- **Pencarian Kata Kunci Instan (`cari` / `q`):** Menambahkan input pencarian teks bebas di bilah filter laporan yang mencocokkan `data-cari` (Nama KK, NIK, No KK, no rumah, kode lahan).
- **Filter Kondisional Per Mode:** Dimensi filter ditampilkan secara kontekstual sesuai mode yang aktif (`statusHunian` & `kondisi` di Mode Rumah, `peruntukan` di Mode Lahan, `status` & `tahun` di Mode Gabungan dan Transmigran).
- **Dimensi Baru Laporan Data:** Menambahkan opsi dimensi `statusHunian`, `kondisi`, dan `peruntukan` pada `LaporanData::filterLaporan('transmigran')`.

### 3. Hierarki Visual & Kontainer Scroll Responsif
- Membungkus setiap tabel dalam `overflow-x-auto rounded-2xl border` agar scrollbar horizontal hanya berada pada container tabel saat layar menyempit.
- Tipografi terstruktur, penggunaan `tabular-nums` untuk angka/NIK/luas, dan badge status semantik (Aktif, Pindah, Kondisi Rumah, Status Hunian).

### 4. Pembersihan Dokumen Resmi Sesuai Mode Terpilih
- Pada rute dokumen resmi (`/laporan/transmigran/dokumen`), navigasi *pill tab switcher* dan subjudul pengantar informal disembunyikan seluruhnya (`$isDokumen`).
- Dokumen yang digenerate murni menyajikan Kop Surat Dinas + Tabel Data Mode Terpilih (Gabungan, Transmigran, Rumah, atau Lahan) beserta parameter filternya yang dibawa melalui URL Hash.

### 5. Peniadaan Scrollbar Horizontal & Vertikal di Dalam Tabel Dokumen
- Menyesuaikan ukuran font ke `text-theme-xs` (11–12px), memadatkan padding sel (`0.25rem 0.375rem`), dan mengoptimalkan sel lebar (TTL 2 baris kompak, NIK/KK/Pendapatan tabular-nums whitespace-nowrap).
- Menegakkan `overflow-y: hidden` pada `.kertas-dokumen .overflow-x-auto` di `app.css`.
- Total lebar tabel 14 kolom menyusut hingga ~1.180px, muat presisi di dalam kontainer dokumen 1.200px tanpa memicu slider horizontal maupun slider vertikal di dalam tabel.

### 6. Dukungan Pemilihan Ukuran Kertas Cetak A4 / F4 (Opsi 2)
- Menambahkan switcher ukuran kertas (`Kertas: [ A4 | F4 ]`) di bilah header laporan (`components/sim/kerangka-laporan.blade.php`).
- Keadaan pilihan ukuran kertas disinkronkan ke dokumen lewat URL Hash (`#kertas=f4&...`) dan ditangani secara reaktif di `resources/js/filter-laporan.js`.
- Menyesuaikan lebar kontainer layar (`max-w-[1320px]` untuk F4 landscape) dan menginjeksi aturan cetak `@page { size: 330mm 215mm; margin: 10mm; }` untuk dokumen F4.

### 7. Penyesuaian Teks & Pembersihan Card Informasi Monografi SP
- Redaksi panduan pemilih tahun disesuaikan menjadi: *"Laporan menampilkan data kependudukan, produksi, dan iklim sesuai tahun yang dipilih. Informasi kondisi fisik wilayah, meliputi letak, batas, luas, tanah, sumber daya air, dan aksesibilitas, merupakan informasi wilayah yang bersifat tetap."*
- Card panduan tersebut disembunyikan pada dokumen resmi yang digenerate (`@unless ($isDokumen)`).

### 8. Penyempurnaan Kolom & Judul Laporan Hasil Panen
- Menambahkan kolom **Luas Lahan (ha)** (total alokasi luas garapan poktan) lengkap dengan subtotal per SP dan total kawasan.
- Menambahkan kolom **Keterangan** pada sisi kanan tabel untuk mencatat catatan dan kendala lapangan (banjir, serangan hama, dll).
- Mengeluarkan kolom `Tahun Pengadaan` dari dalam tabel dan memindahkannya ke kop judul resmi: **LAPORAN HASIL PANEN BENIH {KOMODITAS} \n TAHUN ANGGARAN {TAHUN}** (responsif mengikuti filter aktif).

### 9. Penambahan Kolom Belum Ditanam (ha) pada Neraca Lahan
- Menambahkan kolom **Belum Ditanam (ha)** (`Luas Lahan - Realisasi Tanam`) di antara `Realisasi Tanam` dan `Realisasi Panen`.
- Rantai neraca lahan menjadi lengkap dan simetris (17 kolom): *Luas Lahan $\rightarrow$ Volume Benih $\rightarrow$ Realisasi Tanam $\rightarrow$ Belum Ditanam $\rightarrow$ Realisasi Panen $\rightarrow$ Puso $\rightarrow$ Belum Dipanen $\rightarrow$ Produktivitas $\rightarrow$ Produksi $\rightarrow$ Keterangan*.

### 10. Verifikasi Mutu
- **Uji Peramban Lebar Dokumen (`node tests/Browser/uji-lebar-dokumen.mjs`):** 28 lulus, 0 gagal (100% muat tanpa gulir mendatar di seluruh 7 laporan resmi).
- **Pest PHP:** 728 pengujian (6.142 assertions) **100% PASS (Hijau)**.
- **Vite Build:** `npm run build` sukses bersih dalam 5.17 detik.
- **A11y & Visual Hierarchy:** Memenuhi WCAG 2.1 AA (caption, th scope, tabular-nums).






