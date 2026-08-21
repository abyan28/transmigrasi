# ui-spec.md
## Spesifikasi Antarmuka — Sistem Informasi Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur

Dokumen ini adalah acuan tunggal untuk pengerjaan frontend. Konsisten dengan `prd.md`, `rules.md`, dan `workflow.md`. Nama field dan aturan validasi form mengacu pada `data-dictionary.md`.

> **Status:** palet warna sudah final (diambil dari logo resmi). Fondasi template sudah ditetapkan: **TailAdmin Laravel**. Referensi tata letak dari Figma masih menyusul; bagian yang menunggu ditandai `TODO`.

---

## 1. Stack dan Fondasi

| Aspek | Keputusan |
|---|---|
| PHP | **8.2.12** (bawaan XAMPP) |
| Framework | **Laravel 12.x** |
| Template engine | **Blade** |
| Interaktivitas | **Alpine.js 3** |
| Styling | **Tailwind CSS v4** |
| Grafik | **ApexCharts 5** |
| Pemilih tanggal | **Flatpickr** |
| Build tool | **Vite 7** |
| Fondasi komponen | **TailAdmin Laravel** — https://github.com/TailAdmin/tailadmin-laravel (MIT) |
| Basis data | MySQL/MariaDB (XAMPP) |
| Acuan visual | Template Figma pilihan tim (tata letak) |

**Strategi pengerjaan:** repositori TailAdmin di-clone sebagai titik awal, dibersihkan dari halaman contoh, lalu di-restyle memakai palet resmi Kementerian. Seluruh halaman dibangun sebagai Blade dengan **data dummy**. Ketika backend siap, sumber data ditukar tanpa mengubah tampilan.

### 1.1 Yang sudah disediakan TailAdmin

| Sudah ada | Keterangan |
|---|---|
| Layout sidebar + header + backdrop | Tinggal diganti isi menunya |
| Komponen UI | `alert`, `avatar`, `badge`, `button`, `modal` |
| Komponen form | input, select, checkbox, radio, textarea, toggle, dropzone, date picker |
| Komponen tabel | 5 varian tabel dasar |
| Breadcrumb, dropdown, preloader | Siap pakai |
| Mode gelap | Lewat `@custom-variant dark` |
| ApexCharts terpasang | Contoh grafik garis dan batang |

### 1.2 Yang harus dibuang saat pembersihan

Halaman contoh yang tidak relevan: dashboard e-commerce, kalender, dan seluruh halaman demo UI (alerts, avatars, badges, buttons, images, videos). Komponen `ecommerce/*` (6 berkas) dan `calender-area` ikut dihapus.

Dependensi npm yang dicabut karena tak terpakai: `@fullcalendar/*` (5 paket), `jsvectormap`, `swiper`, `prismjs`, `@popperjs/core`, `@floating-ui/dom`.

Yang dipertahankan: `alpinejs`, `apexcharts`, `flatpickr`, `axios`, `tailwindcss`, `vite`, `laravel-vite-plugin`, `concurrently`.

### 1.3 Yang harus dibangun sendiri

| Belum ada di TailAdmin | Catatan |
|---|---|
| **Autentikasi** | `signin.blade.php` hanya tampilan statis, tanpa controller, model, maupun sesi. Login nyata dikerjakan pada Tahap 3 |
| Komponen khusus domain | `x-data-table`, `x-wilayah-picker`, `x-koordinat-input`, `x-stat-card`, `x-file-upload`, `x-empty-state`, `x-toast`, `x-confirm-dialog` |
| Helper `hashTabs()` | Tab persisten lewat query string (`rules.md` §13.2 poin 1) |
| Menu per role | Sidebar bawaan berisi menu contoh, diganti struktur pada §4 |

**Catatan lisensi:** TailAdmin Laravel berlisensi MIT, sehingga bebas dipakai dan dimodifikasi untuk instansi pemerintah. Berkas `LICENSE` asli wajib dipertahankan di dalam repositori.

---

## 2. Arah Desain

Bagian ini memenuhi `ANTISLOP-ID.md` Bagian 3 dan R-37. Tanpa arah yang dinyatakan, hasil desain akan jatuh ke default netral yang steril, dan itu dihitung sebagai kegagalan, sama seperti slop.

### 2.1 Pembacaan Desain

> Membaca ini sebagai: **aplikasi pendataan pemerintahan** untuk **operator desa, pendamping lapangan, dan staf dinas**, dengan bahasa visual **institusional yang tenang dan padat data**, dial **ENERGI 1 / RITME 2 / GERAK 1**.

### 2.2 Tiga Dial

| Dial | Nilai | Alasan |
|---|---|---|
| **ENERGI** | **1** (Tenang) | Sistem dipakai untuk tugas berulang setiap hari, bukan untuk membujuk pengunjung. Antarmuka yang menyapa keras justru melelahkan operator yang mengisi puluhan baris data. Acuan rasa: GOV.UK |
| **RITME** | **2** (Seimbang) | Keseragaman penuh akan membuat dashboard, halaman daftar, dan halaman detail terasa sama dan membingungkan. Variasi komposisi antar-jenis halaman menjadi penanda orientasi: pengguna langsung tahu sedang berada di jenis halaman apa |
| **GERAK** | **1** (Hanya hover dan transisi state) | Sinyal di lokus tidak stabil dan perangkat lapangan terbatas. Animasi masuk, parallax, dan scroll-reveal menambah bobot tanpa membantu tugas pendataan |

**Konsekuensi RITME 2 yang wajib terlihat.** Empat jenis halaman berikut harus punya komposisi berbeda, bukan sekadar bertukar warna latar:

| Jenis halaman | Komposisi |
|---|---|
| Dashboard | Baris kartu statistik di atas, lalu grid grafik dua kolom yang tidak sama lebar |
| Halaman daftar | Lebar penuh, didominasi tabel, filter dalam laci yang dapat dilipat |
| Halaman detail | Dua kolom asimetris: ringkasan entitas menetap di kiri, tab konten di kanan |
| Halaman rekap | Tabel agregat dengan baris total yang ditegaskan, tanpa kartu statistik |

Bila keempatnya berakhir dengan pola yang sama (judul di tengah, lalu grid kartu seragam), berarti RITME 2 gagal dipenuhi dan harus diulang.

### 2.3 Motif Identitas

Satu motif yang diulang agar antarmuka menjadi milik produk ini, bukan template mana pun. Diturunkan dari **bentuk sudut miring pada logo Kementerian Transmigrasi**.

| Penerapan | Wujud |
|---|---|
| Penanda menu aktif di sidebar | Batang vertikal `gold-500` selebar 3px di tepi kiri item aktif |
| Judul kartu statistik | Garis pendek `gold-500` selebar 24px di atas label |
| Header halaman | Garis bawah tipis dengan gradasi berhenti di sepertiga lebar, bukan garis penuh |
| Baris total pada tabel rekap | Garis atas `navy-500` setebal 2px, bukan garis abu-abu biasa |

Motif ini **tidak** dipakai di tempat lain. Diulang di empat titik sudah cukup untuk membentuk identitas; lebih dari itu menjadi dekorasi.

### 2.4 Satu Aksen yang Disengaja

**Gold** (`#C09546`) adalah satu-satunya warna aksen, dipakai hemat dan hanya pada empat hal: motif identitas di atas, penanda prioritas Mendesak pada pengaduan, penanda komoditas unggulan, dan indikator dashboard yang melampaui target.

Gold **dilarang** dipakai untuk: tombol biasa, tautan, ikon menu, garis pemisah, dan latar kartu. Aksen yang muncul di mana-mana berhenti menjadi aksen.

Penanda komoditas unggulan **wajib tetap ditandai manusia**, tidak dihitung dari volume panen (`rules.md` 8.3a). Perhitungan otomatis membuat jumlah komoditas bergold berubah-ubah mengikuti musim: bisa nol bila tidak ada yang menonjol, bisa banyak sekaligus bila ambangnya longgar. Aksen yang jumlahnya tidak terkendali berhenti menjadi aksen.

### 2.5 Alasan Keputusan Desain (R-31)

Setiap keputusan utama disertai alasan satu baris. Keputusan yang alasannya tidak dapat ditulis satu baris berarti belum valid.

| Keputusan | Alasan |
|---|---|
| Palet navy, teal, sand, gold | Diambil dari logo resmi Kementerian Transmigrasi, sehingga identitas berasal dari instansi pemiliknya, bukan dari selera perancang |
| Navy sebagai warna utama | Porsi terbesar pada logo (24,5%) dan memberi kontras 11,75 dengan teks putih, aman untuk sidebar dan header |
| Gold sebagai aksen tunggal | Warna paling menonjol pada logo namun porsinya paling kecil (4,2%), sifat itu diteruskan ke antarmuka |
| Font Outfit | Mengikuti fondasi TailAdmin; angka bergaya tabular sehingga digit sejajar pada tabel yang padat data |
| Radius `rounded-2xl` untuk kartu | Mengikuti TailAdmin agar komponen bawaan tidak perlu disunting ulang satu per satu |
| Kartu memakai garis tepi, bukan bayangan besar | Halaman berisi banyak kartu sekaligus; bayangan pada semuanya membuat layar terasa melayang dan menyulitkan pemindaian |
| Tabel sebagai komponen utama, bukan kartu | Pekerjaan inti pengguna adalah membandingkan banyak baris data, dan tabel lebih padat serta lebih mudah dipindai daripada kartu |
| Paginasi 25 baris | Kompromi antara jumlah data terlihat dan waktu muat pada koneksi lambat di lokus |
| Filter dalam laci yang dapat dilipat | Filter jarang diubah setelah disetel, sehingga tidak layak menempati ruang tetap di atas tabel |
| Ikon SVG sebaris, bukan paket ikon | Menghindari unduhan berkas font ikon yang membebani kuota pengguna lapangan |
| Modal untuk form panjang | Mempertahankan konteks daftar di belakangnya, sehingga operator tidak kehilangan posisi setelah menyimpan |
| Angka memakai `tabular-nums` | Digit sejajar secara vertikal sehingga selisih nilai antar-baris langsung terlihat |

---

## 3. Design Token

### 3.1 Palet warna

Diekstrak langsung dari logo resmi Kementerian Transmigrasi (`docs/Logo_Kementerian_Transmigrasi_Republik_Indonesia_(2024).svg.webp`). Empat warna inti:

| Peran | Hex | Porsi pada logo |
|---|---|---|
| **Navy** — warna utama | `#163B54` | 24,5% |
| **Teal** — aksen sekunder | `#33809C` | 7,3% |
| **Sand** — aksen terang | `#DFB87E` | 6,2% |
| **Gold** — aksen tegas | `#C09546` | 4,2% |

**Penting — Tailwind v4 tidak memakai `tailwind.config.js`.** TailAdmin memakai Tailwind v4, yang mendefinisikan token langsung di dalam CSS lewat blok `@theme` pada `resources/css/app.css`. Setiap variabel `--color-x-500` otomatis menghasilkan kelas `bg-x-500`, `text-x-500`, `border-x-500`, dan seterusnya.

Skala lengkap, ditulis pada `resources/css/app.css`:

```css
@theme {
  /* Navy — warna utama, dari logo Kementerian Transmigrasi */
  --color-navy-50:  #F3F5F6;
  --color-navy-100: #E3E7EA;
  --color-navy-200: #C2CCD3;
  --color-navy-300: #98A9B4;
  --color-navy-400: #5C7687;
  --color-navy-500: #163B54;
  --color-navy-600: #13344A;
  --color-navy-700: #102C3E;
  --color-navy-800: #0D2332;
  --color-navy-900: #0A1B27;
  --color-navy-950: #071219;

  /* Teal — aksen sekunder */
  --color-teal-50:  #F5F9FA;
  --color-teal-100: #E7F0F3;
  --color-teal-200: #CADEE5;
  --color-teal-300: #A5C7D3;
  --color-teal-400: #70A6BA;
  --color-teal-500: #33809C;
  --color-teal-600: #2D7189;
  --color-teal-700: #265F73;
  --color-teal-800: #1F4D5E;
  --color-teal-900: #173B48;
  --color-teal-950: #0F262F;

  /* Sand — aksen terang */
  --color-sand-50:  #FDFBF9;
  --color-sand-100: #FBF6F0;
  --color-sand-200: #F7EDDD;
  --color-sand-300: #F1E0C6;
  --color-sand-400: #E9CDA5;
  --color-sand-500: #DFB87E;
  --color-sand-600: #C4A26F;
  --color-sand-700: #A5885D;
  --color-sand-800: #866E4C;
  --color-sand-900: #67553A;
  --color-sand-950: #433726;

  /* Gold — aksen tegas. JANGAN pakai gold-500 untuk teks putih (kontras 2,75) */
  --color-gold-50:  #FCFAF6;
  --color-gold-100: #F7F2E9;
  --color-gold-200: #EFE3CF;
  --color-gold-300: #E3D0AE;
  --color-gold-400: #D3B57E;
  --color-gold-500: #C09546;
  --color-gold-600: #A9833E;
  --color-gold-700: #8E6E34;
  --color-gold-800: #73592A;
  --color-gold-900: #584520;
  --color-gold-950: #3A2D15;
}
```

**Penyesuaian warna bawaan TailAdmin:** template memakai `--color-brand-*` dengan nilai biru elektrik `#465fff`. Seluruh nilai `--color-brand-25` sampai `--color-brand-950` **wajib ditimpa** dengan skala navy di atas, agar komponen bawaan (tombol, badge, tautan aktif sidebar) langsung mengikuti identitas Kementerian tanpa perlu menyunting satu per satu.

Token bawaan TailAdmin yang **dipertahankan apa adanya**: `--color-gray-*`, `--color-success-*`, `--color-error-*`, `--color-warning-*`, seluruh `--text-title-*`, dan `--breakpoint-*`.

**Pemetaan permukaan pada kedua mode.** Tailwind v4 memakai varian `dark:` lewat `@custom-variant dark (&:is(.dark *))` yang sudah tersedia di TailAdmin.

| Peran permukaan | Mode terang | Mode gelap |
|---|---|---|
| Latar halaman | `white` | `navy-900` `#0A1B27` |
| Latar kartu | `white` | `navy-800` `#0D2332` |
| Garis tepi | `gray-200` | `navy-700` `#102C3E` |
| Sidebar | `navy-500` | `navy-950` `#071219` |
| Teks utama | `gray-900` | `white` |
| Teks isi | `gray-700` | `navy-100` |
| Teks keterangan | `gray-500` | `navy-300` |
| Aksen | `gold-700` | `gold-400` |

Sidebar di mode gelap memakai `navy-950`, satu tingkat lebih gelap dari latar halaman, agar tetap terbaca sebagai lapisan terpisah tanpa mengandalkan bayangan.

### 3.2 Aturan kontras (WCAG) — WAJIB dipatuhi

> **Angka pada tabel di bawah dijaga uji, bukan sekadar dicatat.** `tests/Feature/KontrasTest.php` menghitung ulang rasio dengan rumus WCAG 2.1 dan membaca nilai warna **langsung dari `resources/css/app.css`**, sehingga warna yang disunting tanpa memperbarui tabel ini akan memerahkan uji. Sebelum 2026-08-17 tabel ini tidak dijaga apa pun: laporan gate gelombang 1 mengklaim ada 11 uji kontras lewat Node, padahal berkasnya 0 byte dan tidak pernah berisi.

Aplikasi menyediakan **dua mode tema**, sehingga kontras wajib diuji pada keduanya (`ANTISLOP-ID.md` R-25 dan R-34). Rasio di bawah dihitung dengan rumus WCAG 2.1.

#### 3.2.1 Mode terang

Latar dasar putih `#FFFFFF`.

| Kombinasi | Rasio | Status | Penggunaan |
|---|---|---|---|
| `navy-500` + teks putih | 11,75 | AAA | Tombol utama, header, sidebar |
| `teal-700` + teks putih | 7,08 | AAA | Tombol sekunder, tautan |
| `teal-500` + teks putih | 4,46 | AA-large | **Hanya** untuk teks ≥18px atau ikon |
| `sand-500` + teks `navy-500` | 6,32 | AA | Badge, sorotan |
| `gold-700` + teks putih | 4,74 | AA | Badge peringatan |
| `gold-500` + teks putih | 2,75 | **GAGAL** | **Dilarang** untuk teks |

**Aturan turunan mode terang:**
1. `gold-500` hanya boleh dipakai sebagai latar dengan teks `navy-500`, atau sebagai garis/ikon dekoratif. Untuk teks putih gunakan `gold-700`.
2. `teal-500` tidak boleh untuk teks kecil dengan latar putih. Gunakan `teal-700`.
3. `sand-500` selalu dipasangkan dengan teks `navy-500`, tidak pernah putih.

#### 3.2.2 Mode gelap

Latar dasar `navy-900` `#0A1B27`, dipilih menggantikan `gray-900` bawaan TailAdmin agar mode gelap tetap membawa identitas navy Kementerian, bukan abu-abu netral.

| Kombinasi | Rasio | Status | Penggunaan |
|---|---|---|---|
| `navy-900` + teks putih | 17,51 | AAA | Teks utama |
| `navy-900` + `navy-100` | 14,08 | AAA | Teks isi |
| `navy-900` + `navy-200` | 10,74 | AAA | Teks sekunder |
| `navy-900` + `navy-300` | 7,23 | AAA | Teks keterangan, batas paling redup yang diizinkan |
| `navy-900` + `sand-400` | 11,46 | AAA | Sorotan, nilai penting |
| `navy-900` + `teal-300` | 9,77 | AAA | Tautan |
| `navy-900` + `gold-400` | 8,91 | AAA | Aksen, penanda prioritas |
| `navy-900` + `gold-500` | 6,36 | AA | Aksen alternatif |
| `navy-900` + `teal-400` | 6,55 | AA | Ikon, teks besar |

**Aturan turunan mode gelap:**
1. **Warna dibalik arahnya.** Di mode terang dipakai tingkat 500 sampai 700 sebagai latar; di mode gelap dipakai tingkat 300 sampai 400 sebagai teks di atas latar gelap.
2. `navy-300` adalah **batas paling redup** untuk teks. Tingkat di bawahnya (`navy-400` ke bawah) dilarang sebagai teks di mode gelap.
3. `gold-500` yang gagal di mode terang justru **lolos AA di mode gelap** (6,36). Ini satu-satunya konteks gold-500 boleh menjadi warna teks.
4. Kartu memakai latar `navy-800` `#0D2332` dengan garis tepi `navy-700`, memberi pemisahan tanpa bayangan.
5. Bayangan tidak berfungsi di mode gelap; pemisahan lapisan wajib memakai perbedaan warna latar dan garis tepi.

#### 3.2.3 Kewajiban pengujian dua mode

Sesuai R-34, mode yang dikirim wajib berfungsi penuh. Yang wajib diperiksa pada **kedua** mode sebelum menyatakan selesai:

- seluruh komponen bersama (§6),
- seluruh 17 visualisasi dashboard beserta legenda, sumbu, dan tooltip,
- **daftar dropdown dalam keadaan TERBUKA**, bukan hanya kotaknya. Select tertutup selalu tampak baik sebab yang dirender adalah kotaknya; isi daftarnya baru terlihat setelah diklik,
- **peta pemilih titik**, termasuk keadaan ketika ubin gagal dimuat. Peta bergantung pada sambungan, sedangkan isian koordinat manual tidak,
- **halaman yang dibuka lewat tab baru**, bukan hanya yang dimuat langsung, **beserta seluruh grafik di dalamnya sampai bagian paling bawah halaman**. Tab yang belum dilukis peramban melaporkan lebar nol, sehingga tata letak maupun kanvas grafik yang bergantung padanya sempat salah sampai halaman disegarkan. Grafik perlu diperiksa terpisah, sebab tata letak dapat sudah benar sementara kanvasnya masih memakai ukuran keliru,
- seluruh varian badge status (§6.6),
- state kosong, memuat, galat, dan tanpa kewenangan (§7),
- indikator fokus keyboard.

Mode gelap yang merusak keterbacaan mode terang, atau sebaliknya, dihitung sebagai cacat dan wajib diperbaiki sebelum penyerahan.

### 3.3 Warna semantik

Memakai token semantik bawaan TailAdmin agar komponen `alert` dan `badge` template langsung cocok tanpa modifikasi.

| Makna | Kelas | Nilai | Catatan |
|---|---|---|---|
| Sukses | `success-500` | `#12b76a` | Data tersimpan, pengaduan selesai |
| Peringatan | `warning-500` | `#f79009` | Data belum lengkap, rusak ringan |
| Bahaya | `error-500` | `#f04438` | Hapus, rusak berat, galat |
| Info | `teal-700` | `#265F73` | Notifikasi netral |
| Netral | `gray-*` | bawaan TailAdmin | Teks sekunder, garis, latar |

**Catatan:** rencana awal memakai `emerald-600`, `amber-600`, `red-600`, dan `slate-*` bawaan Tailwind. Diganti ke token TailAdmin karena template sudah memakainya secara konsisten; memaksakan palet lain berarti menyunting ulang setiap komponen bawaan.

### 3.4 Tipografi

| Token | Nilai |
|---|---|
| Font | **Outfit** (fallback: system-ui, sans-serif) |
| Judul halaman | `text-title-sm font-semibold text-navy-500` |
| Judul kartu | `text-lg font-semibold` |
| Teks isi | `text-theme-sm` |
| Label form | `text-theme-sm font-medium` |
| Keterangan | `text-theme-xs text-gray-500` |
| Angka statistik | `text-title-sm font-bold tabular-nums` |

Font **Outfit** mengikuti bawaan TailAdmin, dimuat lewat Google Fonts pada baris pertama `app.css`. Keputusan ini menggantikan rencana awal memakai Inter, karena seluruh komponen TailAdmin sudah ditata dengan metrik Outfit sehingga penggantian font akan menggeser tinggi baris di banyak komponen tanpa manfaat sepadan.

Skala `text-title-*` dan `text-theme-*` adalah token bawaan TailAdmin dan dipakai menggantikan skala `text-2xl`/`text-sm` bawaan Tailwind, agar konsisten dengan komponen template.

Kelas `tabular-nums` wajib pada semua angka di tabel dan kartu statistik agar digit sejajar.

### 3.5 Spasi, radius, bayangan

| Token | Nilai |
|---|---|
| Skala spasi | Kelipatan 4px (skala bawaan Tailwind) |
| Padding kartu | `p-4` (mobile) / `p-6` (desktop) |
| Jarak antar kartu | `gap-4` / `gap-6` |
| Radius kartu & modal | `rounded-2xl` (mengikuti TailAdmin) |
| Radius input & tombol | `rounded-lg` |
| Radius badge | `rounded-full` |
| Pemisahan kartu | `border border-gray-200 bg-white` / gelap: `border-navy-700 bg-navy-800` |
| Bayangan modal | `shadow-xl` (mode terang saja; mode gelap memakai latar `navy-800` + garis tepi) |
| Lebar konten maks. | `max-w-7xl` |

**Catatan radius (R-11):** radius sengaja **dibedakan menurut peran**, bukan diseragamkan menjadi pil. Kartu `rounded-2xl`, kontrol `rounded-lg`, badge `rounded-full`. Perbedaan ini adalah alat hierarki: pengguna dapat membedakan wadah, kontrol, dan label hanya dari bentuk sudutnya.

### 3.6 Ikon

Set ikon: **SVG inline** mengikuti pola TailAdmin, yang menyimpan ikon sebagai markup SVG langsung di dalam Blade tanpa paket tambahan. Ukuran baku `w-5 h-5`, ikon menu `w-6 h-6`.

Bila dibutuhkan ikon di luar koleksi bawaan template, ambil dari **Heroicons** (MIT) dan salin markup SVG-nya. Dilarang memasang paket ikon berbasis font demi menjaga ukuran unduhan tetap kecil untuk pengguna lapangan.

### 3.7 Logo

- Berkas sumber: `docs/Logo_Kementerian_Transmigrasi_Republik_Indonesia_(2024).svg.webp`
- Simpan di `public/images/logo-kementrans.webp` beserta varian PNG untuk kompatibilitas.
- Sidebar: logo + teks "SIM Transmigrasi" pada latar `navy-500`.
- Halaman login: logo ukuran besar (`w-24`) di atas judul.
- Kop laporan PDF: logo + nama kementerian.
- **Larangan:** jangan mengubah warna, proporsi, atau memotong logo.

---

## 4. Inventaris Halaman dan Rute

Total ±47 halaman beserta 21 modal form. Kolom "Role" memakai singkatan: **A**=Admin, **DT**=Dinas Transmigrasi, **DP**=Dinas Pertanian, **OP**=Operator SP.

Singkatan **T** (Transmigran) dan **KP** (Ketua Poktan) tidak lagi dipakai. Sesuai `rules.md`, seluruh pengguna sistem adalah petugas dan warga tidak memiliki akun, sehingga halaman yang dahulu diperuntukkan bagi kedua role tersebut dihapus dari inventaris ini. Jalur bagi warga hanya dua, keduanya tanpa login: form pengaduan warga dan halaman lacak pengaduan.

### 4.1 Autentikasi

| Halaman | Rute | Role |
|---|---|---|
| Masuk | `GET /login` | publik |
| Proses masuk | `POST /login` | publik |
| Lupa kata sandi | `GET /lupa-kata-sandi` | publik |
| Masukkan kode verifikasi | `GET /verifikasi-kode` | publik |
| Keluar | `POST /logout` | semua |
| Profil saya | `GET /profil` | semua |
| Ubah kata sandi | `GET /profil/kata-sandi` | semua |
| Wajib ganti kata sandi | `GET /ganti-kata-sandi` | semua |

**Tidak ada halaman pendaftaran mandiri.** Akun hanya dibuat Admin lewat Manajemen Pengguna (`rules.md` §5.1). Berkas `signup.blade.php` bawaan template sudah dihapus.

**Dua jalur pemulihan kata sandi.** Sejak 2026-08-12 tersedia kode verifikasi lewat surel **beserta** penyetelan ulang oleh Admin. Jalur Admin tidak boleh dihapus, sebab itulah satu-satunya yang bekerja di lokus bersinyal lemah (`rules.md` §14b poin 11).

**Satu kolom isian untuk dua kredensial.** Halaman masuk menyediakan satu kolom berlabel "Email atau Username". Seluruh pengguna sistem adalah petugas; warga tidak memiliki akun.

**Halaman wajib ganti kata sandi** muncul otomatis ketika `user.password_harus_diganti` bernilai `TRUE`, baik setelah Admin menyetel ulang maupun saat petugas pertama kali masuk. Selama belum diselesaikan, pengguna tidak dapat mengakses halaman lain.

**Pembuatan akun (2026-08-14).** Admin mengisi surel yang **wajib**, sedangkan username dan kata sandi tidak diketiknya:

| Isian | Siapa yang menentukan | Kapan |
|---|---|---|
| Surel | Admin | saat akun dibuat, wajib |
| Kata sandi sementara | dibangkitkan sistem | tampil sekali di layar, dikirim juga ke surel |
| Username | petugas sendiri | saat pertama kali masuk |

Kata sandi sementara **tampil di layar sekaligus dikirim lewat surel**. Keduanya diperlukan: surel menolong petugas berjaringan memadai, tampilan layar menolong petugas di lokus yang sedang berdiri di depan Admin.

**Tidak ada kendali aktif/nonaktif pada formulir akun.** Akun baru selalu langsung aktif; penonaktifan dan pengaktifan kembali dilakukan lewat tombol ikon pada halaman daftar, agar seluruh perubahan keadaan akun melewati satu jalur yang sama dan tercatat rapi pada audit log.

### 4.1a Halaman Publik (tanpa login)

Dua halaman berikut dapat diakses siapa pun tanpa akun, sebagai kanal pengaduan warga.

| Halaman | Rute | Keterangan |
|---|---|---|
| Form pengaduan warga | `GET /pengaduan-warga` | Nama, kontak, SP, kategori, uraian, foto opsional |
| Kirim pengaduan | `POST /pengaduan-warga` | Dibatasi 3 pengiriman per jam per alamat IP |
| Lacak pengaduan | `GET /lacak-pengaduan` | Masukkan nomor pengaduan |
| Hasil pelacakan | `POST /lacak-pengaduan` | Menampilkan status dan riwayat penanganan |

**Aturan halaman publik:**
1. Memakai tata letak terpisah tanpa sidebar, karena pengunjung bukan pengguna sistem.
2. Bahasa dibuat sesederhana mungkin, karena penggunanya warga desa, bukan petugas.
3. Setelah pengiriman berhasil, **nomor pengaduan ditampilkan besar dan jelas** beserta anjuran mencatatnya. Nomor dapat disalin sekali ketuk, tetapi penyalinan adalah **pelengkap, bukan pengganti**: papan klip mudah tertimpa salinan berikutnya, sehingga nomornya tetap tampil besar dan ajakan mencatat tetap ada.
4. Halaman lacak hanya menampilkan status, tanggal, dan catatan penanganan. Data pribadi pelapor tidak pernah ditampilkan.
4a. **Dokumen tindak lanjut tidak dapat diunduh dari halaman lacak.** Warga cukup diberi tahu keberadaannya beserta cara memintanya. Halaman ini terbuka tanpa login dan hanya berbekal nomor pengaduan, sehingga siapa pun yang mengetahui nomornya akan ikut memperoleh berkasnya; dokumen tindak lanjut kerap memuat nama petugas, hasil peninjauan, dan kadang data warga lain.
5. Bila batas pengiriman terlampaui, tampilkan pesan ramah: "Anda sudah mengirim beberapa pengaduan. Silakan coba lagi satu jam lagi."
6. Tanpa CAPTCHA, agar tidak membebani pengguna berjaringan lemah.

### 4.2 Dashboard

| Halaman | Rute | Role |
|---|---|---|
| Dashboard utama | `GET /` | A, DT, DP |
| Drill-down per SP | `GET /dashboard/sp/{sp}` | A, DT, DP |

### 4.3 Data Master Wilayah

| Halaman | Rute | Role |
|---|---|---|
| Daftar wilayah | `GET /wilayah` | A |
| Form wilayah | modal | A |
| Daftar kawasan | `GET /kawasan` | A, DT |
| Form kawasan | modal | A |
| Daftar SP | `GET /sp` | A, DT |
| Detail SP | `GET /dashboard/sp/{sp}` | A, DT, DP |
| Form SP | modal | A |
| Inventaris SP | `GET /sp/inventaris` | A, DT |
| Form inventaris SP | modal | A, DT |
| Fasilitas SP | `GET /sp/fasilitas` | A, DT |
| Form fasilitas SP | modal | A, DT |
| Data master satuan | `GET /master/satuan` | A |
| Form data master satuan | modal | A |
| Indeks data master referensi | `GET /master/referensi` | A, DT |
| Satu daftar referensi | `GET /master/referensi/{jenis}` | A, DT |
| Form nilai referensi | modal | A, DT |

### 4.4 Kependudukan

| Halaman | Rute | Role |
|---|---|---|
| Daftar transmigran | `GET /transmigran` | A, DT, DP |
| Detail transmigran | `GET /transmigran/{id}` | A, DT, DP |
| Form transmigran | modal | A, DT |
| Daftar rumah | `GET /rumah` | A, DT |
| Detail rumah | `GET /rumah/{id}` | A, DT |
| Riwayat penghunian | tab pada detail rumah | A, DT |
| Rekap kependudukan | `GET /kependudukan/rekap` | A, DT |

### 4.5 Lahan

| Halaman | Rute | Role |
|---|---|---|
| Daftar lahan | `GET /lahan` | A, DT, DP |
| Detail lahan | `GET /lahan/{id}` | A, DT, DP |
| Form lahan | modal | A, DT |
| Dokumen lahan (HPL/SHM) | tab pada detail lahan | A, DT |

### 4.6 Kelembagaan dan Sarana

| Halaman | Rute | Role |
|---|---|---|
| Daftar poktan | `GET /poktan` | A, DP |
| Form poktan | modal | A, DP |
| Detail poktan | `GET /poktan/{id}` | A, DP |
| Anggota poktan | tab pada detail poktan | A, DP |
| Form anggota poktan | modal | A, DP |
| Daftar alsintan | `GET /alsintan` | A, DP |
| Detail alsintan | `GET /alsintan/{id}` | A, DP |
| Form alsintan | modal | A, DP |
| Daftar saprotan | `GET /saprotan` | A, DP |
| Detail saprotan | `GET /saprotan/{id}` | A, DP |
| Form saprotan | modal | A, DP |

### 4.7 Produksi Pertanian

| Halaman | Rute | Role |
|---|---|---|
| Daftar komoditas | `GET /komoditas` | A, DP |
| Detail komoditas | `GET /komoditas/{id}` | A, DP |
| Form komoditas | modal | A, DP |
| Musim tanam | `GET /musim-tanam` | A, DP |
| Form musim tanam | modal | A, DP |
| Riwayat tanam | `GET /riwayat-tanam` | A, DP |
| Form riwayat tanam | modal | A, DP |
| Daftar hasil panen | `GET /panen` | A, DP |
| Detail hasil panen | `GET /panen/{id}` | A, DP |
| Rekap panen | `GET /panen/rekap` | A, DP |

### 4.8 Infrastruktur dan Pengaduan

| Halaman | Rute | Role |
|---|---|---|
| Daftar infrastruktur | `GET /infrastruktur` | A, DT, DP |
| Detail infrastruktur | `GET /infrastruktur/{id}` | A, DT, DP |
| Form infrastruktur | modal | A, DT, DP |
| Daftar pengaduan | `GET /pengaduan` | A, DT, DP |
| Detail pengaduan | `GET /pengaduan/{id}` | A, DT, DP |
| Form pengaduan warga | `GET /pengaduan-warga` | publik, tanpa login |
| Lacak pengaduan | `GET /lacak-pengaduan` | publik, tanpa login |
| Penanganan pengaduan | modal | A, DT, DP |
| Rekap pengaduan | `GET /pengaduan/rekap` | A, DT, DP |

### 4.9 Laporan dan Sistem

Kolom "Kewenangan" menggantikan kolom "Role" pada tabel-tabel sebelumnya, karena akses kini ditentukan kewenangan, bukan nama role.

| Halaman | Rute | Kewenangan |
|---|---|---|
| Ekspor data tabel | tombol pada tiap halaman daftar | `[fitur].lihat` |
| Unduh template luring | langkah pertama modal impor | `[fitur].tambah` |
| Manajemen pengguna | `GET /pengguna` | `pengguna.lihat` |
| Detail pengguna | modal | `pengguna.lihat` |
| Form pengguna | modal | `pengguna.tambah` / `pengguna.ubah` |
| Setel ulang kata sandi | modal | `pengguna.ubah` |
| Nonaktifkan pengguna | konfirmasi | `pengguna.ubah` |
| Daftar role | `GET /pengaturan/role` | `role.lihat` |
| Form role dan kewenangan | modal | `role.tambah` / `role.ubah` |
| Audit log | `GET /audit-log` | `audit_log.lihat` |
| Halaman 403 | — | semua |
| Halaman 404 | — | semua |

**Halaman form role** memuat: nama role, deskripsi, pilihan cakupan data, dan matriks centang kewenangan yang dikelompokkan menurut fitur (`data-dictionary.md` §13.2). Role bertanda terkunci ditampilkan dalam keadaan hanya-baca.

**Catatan:** kolom "Role" pada tabel §4.1 sampai §4.8 kini dibaca sebagai **contoh role yang biasanya memiliki kewenangan tersebut**, bukan pembatasan yang dikunci di kode.

---

## 5. Struktur Menu Sidebar

Sejak role menjadi dinamis (`rules.md` bagian 5), menu **tidak lagi ditulis tetap per role**. Setiap item menu dikaitkan ke satu kewenangan, lalu dirender hanya bila pengguna memilikinya.

### 5.1 Pemetaan menu ke kewenangan

Sidebar memakai **submenu**: lima kelompok. Kelompok Transmigrasi dan Pertanian masing-masing memuat dua submenu, sehingga satu judul kelompok menaungi lebih dari satu daftar. Pembagiannya mengikuti pembagian urusan di dinas, bukan pembagian tabel.

Dua penempatan yang perlu diketahui, sebab tidak mengikuti struktur tabel:

- **Daftar Lahan** berada di submenu Penduduk & Lahan, bukan kelompok tersendiri, sebab lahan selalu melekat pada satu kepala keluarga dan ditelusuri lewat pemiliknya.
- **Infrastruktur SP** berada di submenu Wilayah & SP, bukan bersama alsintan dan saprotan. Alsintan milik poktan, sedangkan irigasi, listrik, dan jalan milik satuan permukiman.

| Kelompok | Item induk | Halaman | Rute | Kewenangan yang dibutuhkan |
|---|---|---|---|---|
| **Menu** | &mdash; | Dashboard | `/` | `dashboard.lihat` |
| **Transmigrasi** | Wilayah & SP | Kawasan Transmigrasi | `/kawasan` | `kawasan.lihat` |
| | | Satuan Permukiman | `/sp` | `sp.lihat` |
| | | Inventaris SP | `/sp/inventaris` | `inventaris_sp.lihat` |
| | | Fasilitas SP | `/sp/fasilitas` | `fasilitas_sp.lihat` |
| | | Infrastruktur SP | `/infrastruktur` | `infrastruktur.lihat` |
| | Penduduk & Lahan | Transmigran | `/transmigran` | `transmigran.lihat` |
| | | Rumah & Hunian | `/rumah` | `rumah.lihat` |
| | | Daftar Lahan | `/lahan` | `lahan.lihat` |
| | | Rekap Kependudukan | `/kependudukan/rekap` | `transmigran.lihat` |
| **Pertanian** | Poktan & Sarana | Kelompok Tani | `/poktan` | `poktan.lihat` |
| | | Alsintan | `/alsintan` | `alsintan.lihat` |
| | | Saprotan | `/saprotan` | `saprotan.lihat` |
| | Produksi Pertanian | Komoditas | `/komoditas` | `komoditas.lihat` |
| | | Musim Tanam | `/musim-tanam` | `musim_tanam.lihat` |
| | | Riwayat Tanam | `/riwayat-tanam` | `riwayat_tanam.lihat` |
| | | Hasil Panen | `/panen` | `hasil_panen.lihat` |
| | | Rekap Panen | `/panen/rekap` | `hasil_panen.lihat` |
| **Pengaduan** | Pengaduan Warga | Daftar Pengaduan | `/pengaduan` | `pengaduan.lihat` |
| | | Rekap Pengaduan | `/pengaduan/rekap` | `pengaduan.lihat` |
| **Administrasi Sistem** | Pengaturan Sistem | Data Master Wilayah | `/wilayah` | `wilayah.lihat` |
| | | Data Master Satuan | `/master/satuan` | `satuan.lihat` |
| | | Data Master Referensi | `/master/referensi` | `referensi.lihat` |
| | | Pengguna | `/pengguna` | `pengguna.lihat` |
| | | Role & Hak Akses | `/pengaturan/role` | `role.lihat` |
| | | Audit Log | `/audit-log` | `audit_log.lihat` |

### 5.1a Kolom aksi baku pada halaman daftar

Seluruh halaman daftar memakai komponen `x-sim.aksi-baris` dengan bentuk dan urutan yang sama, agar petugas tidak perlu menebak letak tindakan setiap kali berpindah fitur.

| Urutan | Tindakan | Bentuk |
|---|---|---|
| 1 | Rincian | ikon mata |
| 2 | Ubah | ikon pensil |
| 3 | Tindakan khusus fitur, contoh penanganan pengaduan | ikon sesuai maknanya |
| 4 | Hapus | ikon tempat sampah, **selalu paling kanan** |

Ketentuan:

1. **Bentuk ikon, bukan teks**, agar kolom aksi tetap sempit pada tabel yang sudah padat. Setiap ikon wajib membawa `aria-label` lengkap beserta nama barisnya, sebab ikon tanpa label tidak terbaca pembaca layar (§11.1, R-32).
2. **Hapus selalu paling kanan** dan berwarna merah saat disorot, agar tidak tertukar dengan tindakan yang tidak merusak.
3. Tindakan yang tidak berlaku pada sebuah fitur **tidak dirender sama sekali**, bukan dirender lalu ditolak (R-26).
4. Fitur yang seluruh datanya sudah tampil pada tabel, seperti data master satuan dan wilayah, **tidak memerlukan tombol Rincian**.
5. Satu modal ubah melayani seluruh baris; data baris dikirim lewat peristiwa saat tombol diklik. Merender satu modal per baris menggandakan formulir sebanyak baris pada satu halaman.

### 5.1b Impor data massal

Menjawab PRD §8.1: sinyal di lokus tidak selalu stabil, sehingga petugas mengunduh template, mengisinya luring di lapangan, lalu mengunggahnya kembali saat sambungan tersedia.

1. Tombol **Impor Data** diletakkan **mendahului tombol Tambah** namun bergaya sekunder (bergaris tepi, bukan solid), sebab menambah satu data tetap tindakan yang paling sering dipakai.
2. Alurnya **tiga langkah** di dalam satu modal `x-sim.modal-impor`: unduh template → unggah berkas → pratinjau hasil.
3. **Hasil impor wajib merinci kegagalan per baris** beserta nomor baris dan alasannya. Berkas berisi ratusan baris tidak mungkin diperiksa manual, sehingga pesan "impor gagal" tanpa rincian memaksa petugas mengulang seluruh pekerjaan.
4. Baris bermasalah **dilewati, sisanya tetap disimpan.** Menolak seluruh berkas karena tiga baris salah membuang pekerjaan yang sudah benar.
5. Kolom wajib ditampilkan pada langkah pertama, agar petugas mengetahui isian yang diperlukan sebelum berangkat ke lapangan.
6. **Fitur berikut tidak diberi impor:** Pengaduan (datang satu per satu dari kanal publik, nomornya wajib memuat bagian acak), Pengguna (kata sandi awal diserahkan langsung kepada orangnya, `rules.md` §14b poin 3), serta Role, Kawasan, SP, dan Musim Tanam yang jumlah barisnya sedikit.
7. Selama penyimpanannya belum tersambung, modal **wajib memuat spanduk** yang menyatakan fitur belum aktif, sebab tampilannya sudah terlihat berfungsi penuh.

### 5.1c Tab Catatan Log pada halaman rincian

**Seluruh halaman rincian entitas memakai tab**, mengikuti komposisi pada §2.2: ringkasan entitas menetap di kiri, tab konten di kanan. Tab **Catatan Log** selalu menjadi tab paling kanan, memakai komponen `x-sim.catatan-log`.

> **Perubahan 2026-08-14.** Lima halaman rincian sebelumnya memakai kartu bersusun tanpa tab, yaitu Alsintan, Saprotan, Infrastruktur, Komoditas, dan Panen. Akibatnya letak Catatan Log berbeda-beda antarmodul dan petugas harus menebaknya tiap berpindah. Kelimanya diseragamkan meski sebagian hanya menghasilkan dua tab.

1. Isinya adalah riwayat perubahan **baris data yang sedang dibuka saja**, disaring memakai pasangan `nama_tabel` dan `record_id`. Menyaring nama tabel saja membuat setiap baris menampilkan riwayat baris lain pada tabel yang sama.
2. Entri **terbaru diletakkan paling atas.** Yang pertama dicari pembaca biasanya perubahan terakhir, bukan asal-usul datanya.
3. Tiap entri memuat jenis aksi berbadge, waktu, ringkasan, pelaku, dan alamat IP. Warna badge disamakan dengan halaman Audit Log agar petugas tidak perlu belajar dua sandi warna.
4. **Tab ini tidak menggantikan halaman Audit Log.** Keduanya menjawab pertanyaan berbeda: audit log menjawab *apa saja yang terjadi di seluruh sistem*, tab ini menjawab *apa yang terjadi pada data ini*. Karena itu tab menautkan ke halaman audit log.
5. Riwayat kosong memakai state kosong yang menegaskan bahwa datanya **belum pernah diubah sejak dimasukkan**, bukan bahwa pencatatannya gagal.
6. **Berlaku bagi setiap halaman rincian tanpa kecuali.** Halaman rincian baru wajib menyediakannya sejak awal; kelengkapan ini dijaga uji yang membaca daftar rute, bukan daftar tetap.

Terpasang pada sepuluh halaman: Transmigran, Rumah, Lahan, Poktan, Pengaduan, Alsintan, Saprotan, Infrastruktur, Komoditas, dan Panen.

### 5.2 Aturan perenderan menu

1. **Item menu dirender hanya bila pengguna memiliki kewenangan yang tercantum.** Menu yang tidak berhak **tidak dirender sama sekali**, bukan disembunyikan lewat CSS.
2. **Kelompok menu ikut hilang** bila seluruh item di dalamnya tidak berhak diakses. Tidak boleh ada judul kelompok kosong.
3. Menyembunyikan menu **tidak menggantikan** pemeriksaan kewenangan di controller dan query. Tanpa itu, pengguna masih dapat membuka halaman dengan mengetik alamat URL langsung (`rules.md` bagian 5).
4. Tombol aksi di dalam halaman (Tambah, Ubah, Hapus, Export) mengikuti aturan yang sama: dirender hanya bila kewenangannya dimiliki.
5. Susunan menu identik untuk seluruh role. Yang membedakan hanyalah item mana yang tampil.

### 5.3 Contoh hasil untuk role bawaan

Susunan berikut adalah **hasil** dari pemetaan di atas, bukan aturan tersendiri.

**Admin** melihat seluruh item menu, termasuk kelompok Pengaturan.

**Dinas Transmigrasi**
```
Dashboard
Wilayah & SP    (Kawasan, Daftar SP, Inventaris, Fasilitas)
Kependudukan    (Transmigran, Rumah & Hunian, Rekap)
Lahan
Kelembagaan     (lihat saja)
Pertanian       (lihat saja)
Infrastruktur
Pengaduan
Laporan
```

**Dinas Pertanian**
```
Dashboard
Wilayah & SP    (lihat saja)
Kependudukan    (lihat saja)
Lahan           (lihat saja)
Kelembagaan     (Kelompok Tani, Alsintan, Saprotan)
Pertanian       (Komoditas, Musim Tanam, Riwayat Tanam, Hasil Panen)
Infrastruktur
Pengaduan
Laporan
```

**Operator SP** (seluruhnya terbatas pada SP yang ditugaskan)
```
Dashboard
Wilayah & SP    (Inventaris, Fasilitas)
Kependudukan    (Transmigran, Rumah & Hunian)
Lahan
Kelembagaan     (Kelompok Tani, Alsintan, Saprotan)
Pertanian       (Riwayat Tanam, Hasil Panen)
Infrastruktur
Pengaduan
```

Kelompok **Pengaturan** tidak muncul bagi ketiga role di atas, karena tidak satu pun kewenangannya dimiliki.

---

## 6. Komponen Bersama

Seluruh komponen dibuat sebagai Blade component di `resources/views/components/`. Kolom "Basis" menunjukkan komponen TailAdmin yang dijadikan fondasi.

| Komponen | Basis TailAdmin |
|---|---|
| `<x-data-table>` | `tables/basic-tables-one` |
| `<x-modal-form>` | `ui/modal` |
| `<x-stat-card>` | `ecommerce/ecommerce-metrics` (diambil polanya, isinya diganti) |
| `<x-file-upload>` | `form/form-elements/dropzone` |
| `<x-status-badge>` | `ui/badge` |
| `<x-confirm-dialog>` | `ui/modal` |
| `<x-toast>` | `ui/alert` |
| `<x-breadcrumb>` | `common/page-breadcrumb` |
| `<x-page-header>` | `common/component-card` |
| `<x-wilayah-picker>` | dibangun sendiri di atas `form/select` |
| `<x-koordinat-input>` | dibangun sendiri, peta memakai Leaflet + ubin OpenStreetMap |
| `<x-tautan-peta>` | dibangun sendiri, peta baca-saja untuk halaman rincian |
| `<x-empty-state>` | dibangun sendiri |
| `<x-pilih-cari>` | dibangun sendiri di atas `form/select` |

### 5.1d Batas jumlah tab pada satu halaman

Tab hanya bekerja selama **seluruh judulnya muat dalam satu baris tanpa gulir mendatar**. Melewati batas itu, tab berhenti menjadi navigasi dan berubah menjadi tempat bersembunyi.

> **Ditemukan 2026-08-21.** Halaman `/master/referensi` dibangun dengan sembilan tab dan bekerja baik. Setelah daftarnya bertambah menjadi empat belas, pengukuran di peramban menunjukkan bar tab mencapai **2309px pada ruang 705px**: hanya **empat tab yang terlihat, sepuluh tersembunyi**. Tidak ada yang tampak rusak, sebab keempat belas tab tetap ada di HTML dan `overflow-x-auto` bekerja persis seperti seharusnya.

1. **Ambangnya bukan angka, melainkan lebar.** Empat tab berjudul panjang dapat melewati batas lebih dulu daripada delapan tab berjudul pendek. Yang menentukan adalah jumlah lebar judulnya terhadap lebar wadahnya, bukan cacahnya.
2. **Gulir mendatar bukan penyelamat.** Ia menyembunyikan gejala, bukan menyelesaikan masalah: bar gulir mendatar termasuk hal yang paling sering tidak disadari orang, dan pengguna yang tidak menyadarinya menyimpulkan daftar itu memang tidak ada.
3. **Setelah melewati batas, ganti menjadi halaman indeks berkartu**, bukan tab bertingkat. Tab di dalam tab menuntut pengguna mengingat dua kedudukan sekaligus, dan tetap menyisakan satu alamat untuk seluruh isinya.
4. **Bukan pula memecahnya menjadi butir menu.** Menu bilah sisi punya batas yang sama; memindahkan empat belas judul dari bar tab ke bilah sisi hanya memindahkan baris panjang yang sama ke tempat lain.
5. **Kartu dikelompokkan menurut modul yang memakainya**, bukan menurut kemiripan bentuk daftarnya. Petugas mencari `jenis_fasilitas` karena sedang mengurus aset satuan permukiman, bukan karena ingat isinya sembilan baris.
6. **Setiap daftar mendapat alamatnya sendiri**, sehingga dapat ditandai, muncul pada remah, dan dituju langsung dari tempat lain. Alamat lama berbentuk `?tab={jenis}` **dialihkan**, tidak dibiarkan mati.
7. **Keterangan tentang akibat menyunting ditaruh pada halaman daftarnya**, bukan pada indeks. Peringatan bahwa skor menentukan penilaian SP tidak berguna bagi orang yang sedang memilih daftar mana yang hendak dibuka.
8. **Penanda perilaku khusus tetap tampil di indeks** berupa lencana singkat, agar petugas mengetahui daftar mana yang berdampak lebih jauh daripada sekadar teks pada dropdown sebelum ia membukanya.
9. **Isian yang menentukan daftar dikunci ke halamannya**, dikirim sebagai isian tersembunyi. Membiarkannya berupa dropdown membuat nilai baru dapat mendarat di daftar lain tanpa petugas menyadarinya.
10. **Batas ini wajib dijaga uji peramban, bukan uji string.** Keempat belas judul tetap ada di HTML meski hanya empat yang terlihat; hanya pengukuran di peramban yang dapat membedakannya. Uji wajib memeriksa elemen benar-benar berada di dalam batas layar, bukan sekadar memiliki lebar dan tinggi.
### 6.0a Pilihan berdaftar panjang

Isian yang sumbernya **tabel data**, bukan enum, memakai `x-sim.pilih-cari`. Contohnya daftar transmigran, lahan, dan sejenisnya; enum seperti kondisi atau jenis fasilitas tetap memakai `<select>` biasa sebab jumlahnya tidak pernah bertambah.

> **Koreksi 2026-08-17.** Rancangan pertama komponen ini menaruh kotak pencarian **di atas** `<select>` sebagai dua kontrol berjajar, dan butir 1 di bawah semula berbunyi "isian sesungguhnya tetap `<select>` biasa". Itu keliru: pengguna melihat dua kotak dan harus menebak sendiri bahwa yang satu menyaring yang lain, sementara keduanya tampak sama-sama dapat diisi. Bentuknya kini combobox berpanel, dan aturan di bawah sudah disesuaikan.

1. **Satu tombol, satu panel.** Tombol menampilkan pilihan yang sedang aktif; kotak pencarian berada **di dalam** panel bersama daftarnya, sehingga hubungan keduanya tidak perlu ditebak. Satu pekerjaan tidak boleh memerlukan dua kontrol yang kaitannya tidak terlihat.
2. **Nilai disimpan pada isian ber-`name` sesuai kolomnya**, bukan pada panel. Isian itu memakai kelas `sr-only`, **bukan `type="hidden"`**: peramban mengabaikan `required` pada isian tersembunyi, sehingga form akan terkirim tanpa peringatan apa pun meski isian wajib masih kosong.
3. **Nilai yang berubah wajib diumumkan lewat event `change` sungguhan** pada isian tersebut. Tanpa itu `isiFormulir()` milik `x-sim.modal-form` tidak dapat mengisi form saat modal ubah dibuka, dan pemanggil yang memasang `@change` sendiri ikut diam.
4. **Cadangan tanpa JavaScript wajib ada** berupa `<select>` di dalam `<noscript>`. Sinyal di lokus tidak selalu stabil, dan form yang mustahil diisi karena satu berkas gagal diunduh adalah kegagalan yang tidak perlu.
5. **Kotak pencarian selalu dirender, tanpa ambang jumlah opsi** (ambang 8 dicabut 2026-08-20). Yang menentukan bukan jumlah opsi hari ini, melainkan **apakah daftarnya bertambah ketika petugas menambah data**. Bila ya, pencariannya memang diperlukan, dan menyembunyikannya sampai melewati ambang hanya membuat satu komponen berperilaku dua macam tanpa dapat diduga pemakainya.
5a. **Ambang itu dicabut karena dasarnya keliru, bukan karena tidak nyaman.** Perbandingannya dilakukan terhadap jumlah baris `DummyData`, yaitu data yang dikarang AI sendiri, sehingga kalimat "poktan baru 4 baris jadi wajar belum muncul" adalah penalaran melingkar yang dilarang `rules.md` 19a. Kekeliruan yang sama terulang **tiga kali** pada butir ini: ditetapkan 2026-08-17, ditandai perlu tinjau ulang 2026-08-19, lalu dipakai lagi sebagai pembenaran 2026-08-20. Lihat `notes.md` 1c.2 pelanggaran keenam.
5b. Alasan lama juga sudah tidak berlaku sejak komponen dibangun ulang. Kotak pencarian kini berada **di dalam panel** yang harus dibuka lebih dulu, bukan berjajar di luar sebagai kontrol kedua, sehingga yang hendak mengklik tetap mengklik tanpa melewati apa pun.
5c. **Tabel referensi kecil dikecualikan.** Isian `satuan_id` tetap `<select>` biasa: ia memang dapat ditambah Admin lewat data master, tetapi satuan takaran tidak akan pernah menuntut pencarian. Pengecualian ini disebut satu per satu, bukan dinyatakan sebagai ambang, agar tidak ada lagi yang perlu ditebak.
6. Pencarian mencocokkan **teks utama maupun keterangannya**, sebab petugas kerap mengingat asal SP lebih dulu daripada nama lengkapnya.
7. **Escape berlapis.** Panel wajib memakai `@keydown.escape.stop`: tekanan pertama menutup panel, tekanan kedua barulah menutup modal. Tanpa `.stop`, satu tekanan menutup keduanya sekaligus dan pengguna kehilangan seluruh isian yang sedang diketik.
8. **Tinggi panel wajib dibatasi beserta gulirnya sendiri.** Badan `x-sim.modal-form` memakai `overflow-y-auto`, sehingga panel yang lebih tinggi daripada sisa ruang akan terpotong, bukan mengambang keluar.
9. **Pemicu berupa `<button>`**, bukan `<div>` yang dapat diklik, agar ikut terjaring focus trap modal yang hanya mengumpulkan `a`, `button`, `input`, `select`, dan `textarea`.
10. **Peran ARIA ditulis eksplisit**: `role="combobox"` beserta `aria-expanded` pada pemicu, `role="listbox"` pada daftar, dan `role="option"` beserta `aria-selected` pada tiap opsi. Tanpa itu pembaca layar hanya mengumumkan sebuah tombol tanpa memberi tahu ada daftar yang dapat dibuka.
11. **Keyboard wajib berfungsi penuh** (R-32): `Enter`, `Space`, atau panah bawah membuka panel; panah atas dan bawah menyorot; `Enter` memilih; `Escape` menutup. Opsi yang tersorot wajib ikut tergulir agar terlihat, bukan sekadar tertandai.
12. **Pilihan aktif ditandai lebih dari sekadar warna.** Dipakai tanda centang, sebab pengguna yang tidak membedakan warna akan melihat seluruh baris tampak sama.
13. **Dilarang memakai `x-model` pada `<select>` yang opsinya dirender lewat `x-for`.** Alpine menyetel ulang nilainya setiap daftar opsi berubah, sehingga pilihan pengguna hilang begitu ia mengetik di kotak pencarian. Pakai `@change` untuk menyalin nilainya.
14. Keadaan pencarian nihil **wajib dikatakan**; daftar yang mendadak kosong tanpa penjelasan terbaca sebagai kerusakan.

### 6.0 Penandaan isian wajib

Isian wajib ditandai **dua hal yang selalu berpasangan**. Salah satu tanpa yang lain adalah cacat:

| Penanda | Wujud | Perannya |
|---|---|---|
| Bintang merah | `<span class="text-error-500">*</span>` pada label | memberi tahu pengguna sebelum ia mengisi |
| Atribut `required` | pada `<input>`, `<select>`, `<textarea>` | menegakkannya saat dikirim |

**Aturan:**

1. **Setiap kolom bertanda `Null = TIDAK` pada `data-dictionary.md` wajib ditandai** bila diminta lewat formulir. Kamus data adalah rujukannya, bukan penilaian per kasus.
2. Bintang tanpa `required` **dilarang**: ia menjanjikan sesuatu yang tidak ditegakkan, dan pengguna baru mengetahuinya setelah data tersimpan setengah jadi.
3. **Isian wajib bersyarat** memakai bintang statis beserta `:required` Alpine, contoh `:required="statusHunian === 'Tidak Dihuni'"`. Bintang tetap tampil sebab isiannya hanya muncul ketika syaratnya berlaku, sedangkan `required` menyala mengikuti syarat agar isian tersembunyi tidak menghalangi pengiriman.
4. **Larik kotak centang tidak memakai `required`**, sebab di sana atribut itu menuntut *setiap* kotak dicentang, bukan minimal satu. Pengiriman dicegah lewat Alpine beserta pesan galat yang menjelaskan akibatnya.
5. Dua bentuk berikut **tidak memerlukan** `required` dan bukan cacat: isian tersembunyi yang nilainya diisi sistem, serta `<select>` tanpa `<option value="">` yang pilihan pertamanya sudah menjadi nilai bawaan.
6. Komponen `x-sim.input-kata-sandi`, `x-sim.file-upload`, `x-sim.wilayah-picker`, dan `x-sim.koordinat-input` memancarkan kedua penanda sekaligus lewat prop `wajib`.

> **Audit 2026-08-17.** Ditemukan 45 cacat: 43 isian berkolom `Null = TIDAK` tanpa penanda apa pun, dan 2 bintang tanpa `required` pada halaman masuk. Cacatnya **mengelompok**, bukan tersebar: seluruh form master dan aset belum pernah dilewati penandaan, sedangkan form kependudukan sudah benar sejak awal. Kelengkapannya kini dijaga uji yang membaca kamus data.

### 6.1 `<x-data-table>`
Tabel dengan pencarian, filter, urutan, paginasi, dan tombol export.
- Pencarian di kanan atas, filter dalam laci yang bisa dilipat
- Paginasi default **25 baris**, pilihan 10/25/50/100
- Kolom aksi selalu di kanan, lebar tetap
- Baris diklik menuju halaman detail
- Header lengket (`sticky`) saat digulir
- Wajib punya state kosong

### 6.2 `<x-modal-form>`
Modal floating untuk form isian (`rules.md` §13.2 poin 3).
- Ukuran: `sm` / `md` / `lg` / `xl`
- Header judul + tombol tutup, footer tombol aksi rata kanan
- Tutup dengan `Esc` dan klik latar
- Fokus terkunci di dalam modal
- Tombol simpan nonaktif + spinner selama proses kirim
- Layar penuh pada perangkat mobile

### 6.3 `<x-stat-card>`
Kartu indikator dashboard: label, angka besar, ikon, tren, dan tautan drill-down opsional.

### 6.4 `<x-file-upload>`
- Batas **5 MB**, tipe: gambar dan PDF (`rules.md` §14a)
- Pratinjau gambar, ikon untuk PDF
- Progress bar, tombol hapus
- Menampilkan aturan penamaan berkas
- Validasi tipe dan ukuran di sisi klien sebelum unggah
- **Satu instansi menampung satu berkas.** Modul yang perlu menyimpan foto sekaligus dokumen memasang **dua instansi terpisah**, bukan satu slot berlabel ganda. Keduanya menjawab hal berbeda: foto merekam kondisi saat pendataan, dokumen menyimpan berkas administratifnya. Satu slot untuk keduanya membuat yang satu tertimpa yang lain, dan kehilangannya berlangsung diam-diam sebab form tetap tersimpan. Berlaku pada `infrastruktur`, `inventaris_sp`, `fasilitas_sp` (ditetapkan 2026-08-20).
- **Berkas yang dapat diunggah wajib dapat dibuka kembali** dari halaman rincian modulnya, memakai `x-sim.tautan-dokumen`. Unggahan yang tidak punya jalan dibuka adalah kontrol mati (R-26): petugas mengunggah berita acara lalu tidak menemukan cara membacanya.

### 6.4a Isian catatan

1. **Namanya "Catatan", bukan "Keterangan".** Sebelum 2026-08-20 empat penamaan dipakai bergantian pada satu maksud yang sama, dan tiga di antaranya bahkan pada modul yang berdampingan. Kolom databasenya tetap `keterangan` mengikuti kamus data; yang diseragamkan adalah teks yang dibaca petugas.
2. **Setiap modul yang kolomnya ada di kamus data wajib punya isiannya.** Empat modul sempat memiliki kolom `keterangan` tanpa satu pun isian, sehingga hal yang tidak tertampung kolom baku tidak dapat dicatat ke mana pun.
3. **Nilainya wajib ditampilkan kembali pada halaman rincian.** Catatan yang hanya dapat diketik tetapi tidak pernah terbaca sama saja dengan tidak dicatat. Keadaan kosong dinyatakan apa adanya, bukan disembunyikan.
4. **Tiga pengecualian, sebab maknanya memang berbeda** dan menyamakan namanya justru menyesatkan:
   - `rumah.catatan_hunian` berlabel "Catatan Hunian" — kolomnya memang bernama demikian dan isinya khusus keadaan hunian.
   - `hasil_panen.keterangan_satuan_lokal` berlabel "Keterangan Satuan Lokal" — kolom tersendiri di samping `keterangan`, isinya padanan satuan setempat.
   - `pengaduan.deskripsi` berlabel "Uraian Masalah" — isi laporan yang wajib diisi, bukan catatan tambahan.

### 6.5 `<x-wilayah-picker>`

Hierarki wilayah bercabang dua (`rules.md` §4a), sehingga komponen ini punya **dua mode pemakaian** yang dipilih lewat atribut `mode`.

**Mode `operasional`** (bawaan) untuk seluruh form data operasional: transmigran, rumah, lahan, poktan, infrastruktur, pengaduan.

```
Kawasan Transmigrasi → SP
```

Cukup dua tingkat, karena seluruh data operasional menaut ke SP. Bila hanya ada satu kawasan aktif, tingkat kawasan terisi otomatis dan disembunyikan agar operator tidak memilih hal yang sama berulang kali.

**Mode `pendaftaran-sp`** dipakai **hanya** pada form pendaftaran SP baru, karena di sinilah SP ditautkan ke kedua cabang hierarki.

```
Kawasan Transmigrasi                        (cabang program)
Provinsi → Kabupaten → Kecamatan → Desa     (cabang administratif)
```

**Aturan perilaku:**
1. Setiap tingkat memuat opsi tingkat berikutnya; tingkat di bawahnya dikosongkan saat tingkat atas berubah.
2. **Kecamatan tidak pernah diisi manual pada data SP.** Setelah desa dipilih, kecamatan tampil sebagai teks baca-saja hasil pembacaan dari desa tersebut.
3. Pada mode `operasional`, daftar SP disaring menurut kawasan terpilih.
4. Untuk role bercakupan `Per SP`, pilihan SP dibatasi hanya pada SP yang ditugaskan kepada pengguna tersebut. Bila hanya satu SP, pilihan terisi otomatis dan disembunyikan.

**Filter dashboard** memakai tingkatan Kawasan → Kecamatan → Desa → SP, seluruhnya opsional. Kosong berarti seluruh kawasan.

### 6.6 `<x-status-badge>`
Dibangun di atas `<x-ui.badge>` bawaan TailAdmin. Nilai teks wajib diambil dari PHP Enum di `app/Enums/`, bukan ditulis langsung di view (§11.7).

Setiap warna badge wajib punya varian mode gelap: latar memakai tingkat gelap dengan opasitas rendah, teks memakai tingkat 300 sampai 400 agar memenuhi kontras pada §3.2.2.

| Konteks | Nilai dan warna |
|---|---|
| Pengaduan | Menunggu Diterima `gray` · Diterima `teal` · Diproses `warning` · Selesai `success` |
| Prioritas pengaduan | Rendah `gray` · Sedang `teal` · Tinggi `warning` · Mendesak `error` |
| Kondisi rumah | Tidak Rusak `success` · Rusak Ringan `warning` · Rusak Berat `error` |
| Kondisi aset | Baik `success` · Rusak Ringan `warning` · Rusak Berat `error` |
| Status hunian | Dihuni `teal` · Tidak Dihuni `gray` |
| Keanggotaan | Aktif `success` · Tidak Aktif `gray` · Sudah Keluar `error` |
| Status tinggal | Aktif `success` · Pindah `warning` · Tidak Aktif `gray` · Meninggal `gray` |
| Status penyerahan | Sudah Diserahkan `success` · Dalam Proses `warning` · Belum Diserahkan `gray` |

| **Kondisi SP** | Mandiri `success` · Berkembang `warning` · Perlu Penanganan `error` |

### 6.7 `<x-koordinat-input>`
Input lintang dan bujur, tombol "Ambil lokasi saat ini" (Geolocation API), serta pratinjau peta statis. Tetap dapat diisi manual bila GPS tidak tersedia.

### 6.8 Komponen pelengkap
`<x-breadcrumb>`, `<x-page-header>`, `<x-confirm-dialog>` (konfirmasi hapus), `<x-empty-state>`, `<x-toast>` (notifikasi hasil aksi).

---

## 7. Pola State

Setiap halaman daftar dan detail **wajib** menangani lima keadaan berikut:

| State | Tampilan |
|---|---|
| **Kosong** | Ikon, judul "Belum ada data", satu kalimat penjelasan, tombol aksi utama |
| **Memuat** | Skeleton menyerupai bentuk konten. Dilarang memakai spinner layar penuh. Komponen `preloader` bawaan TailAdmin hanya dipakai saat pemuatan awal aplikasi, bukan per bagian |
| **Galat** | Ikon, pesan ramah berbahasa Indonesia, tombol "Coba lagi" |
| **Tanpa kewenangan** | Halaman 403 dengan tombol kembali ke dashboard |
| **Pencarian nihil** | "Tidak ditemukan hasil untuk ..." + tombol bersihkan filter |

Pesan galat wajib memakai bahasa yang dimengerti operator lapangan, bukan istilah teknis.

---

## 8. Aturan Responsif

Titik henti mengikuti bawaan Tailwind: `sm` 640 · `md` 768 · `lg` 1024 · `xl` 1280.

| Elemen | Mobile (<768px) | Desktop (≥1024px) |
|---|---|---|
| Sidebar | Laci geser dari kiri, tertutup secara bawaan | Menetap, lebar 264px, bisa diciutkan |
| Tabel | Berubah menjadi daftar kartu | Tabel penuh |
| Modal | Layar penuh | Melayang di tengah |
| Form | Satu kolom | Dua kolom |
| Kartu statistik | 1 kolom | 4 kolom |
| Filter | Dalam laci bawah | Sebaris di atas tabel |
| Aksi tabel | Menu titik tiga | Tombol ikon sejajar |

**Wajib:**
- Sasaran sentuh minimal **44×44px**
- Ukuran font input minimal **16px** agar iOS tidak melakukan zoom otomatis
- Dilarang menggulir horizontal pada mobile, kecuali tabel yang memang dibungkus wadah bergulir
- Diuji pada lebar layar 360px (ponsel umum di lapangan)

---

## 9. Spesifikasi Dashboard

Indikator PRD §7.8 dipetakan ke jenis visualisasi:

| # | Indikator | Jenis | Drill-down |
|---|---|---|---|
| 1 | Jumlah transmigran per tahun | Garis | Ya → per SP |
| 2 | Jumlah KK per tahun | Garis | Ya → per SP |
| 3 | Jumlah petani per tahun | Garis | Ya → per SP |
| 4 | Pendapatan keluarga per tahun | Batang | Ya → per SP |
| 5 | KK masuk dan keluar per tahun | Batang berkelompok | Ya → per SP |
| 6 | Rumah terhuni | Kartu statistik + donat | Ya → per SP |
| 7 | Pekerjaan kepala keluarga | Histogram | Ya → per SP |
| 8 | Luas lahan | Kartu statistik | Ya → per SP |
| 9 | Komoditas utama | Donat | Ya → per SP |
| 10 | Total volume panen per tahun (ton) | Batang | Ya → per SP |
| 11 | Harga rata-rata | Garis | Ya → per komoditas |
| 12 | Status infrastruktur | Batang bertumpuk | Ya → per SP |
| 13 | Isu prioritas (dari pengaduan) | Tabel + badge | Ya → detail pengaduan |
| 14 | Rekap penghuni kawasan | Kartu statistik | Ya → per SP |
| 15 | Status kondisi SP | Kartu statistik + tabel berbadge | Ya → per SP |
| 16 | Pengaduan per status | Donat | Ya &mdash; daftar pengaduan |

**Aturan dashboard:**
1. Filter global wilayah dan periode di bagian atas, memengaruhi seluruh visualisasi. Tingkatan filter: Kawasan → Kecamatan → Desa → SP, seluruhnya opsional.
2. Drill-down memakai event `dataPointSelection` ApexCharts menuju `/dashboard/sp/{sp}`.
3. Volume panen **selalu** dikonversi ke ton sebelum diagregasi (`rules.md` §8a).
4. Kartu statistik dimuat lebih dulu, grafik menyusul secara asinkron.
5. Setiap grafik punya state kosong sendiri bila data belum tersedia.
6. Warna seri grafik mengambil urutan: `#163B54` (navy-500) → `#33809C` (teal-500) → `#C09546` (gold-500) → `#DFB87E` (sand-500) → `#265F73` (teal-700). ApexCharts dikonfigurasi memakai nilai heksadesimal, bukan nama kelas Tailwind.
7. Grafik wajib menyediakan tabel data alternatif demi aksesibilitas.
8. Konfigurasi ApexCharts bersama (warna, font Outfit, locale Indonesia, format angka) diletakkan di satu berkas `resources/js/chart-config.js`, tidak diulang di tiap grafik.
9. **Visualisasi dikelompokkan menurut topik, bukan menurut nomor indikator.** Dashboard memuat lebih dari dua puluh blok; mengurutkannya menurut nomor indikator membuat pembaca dilempar antartopik dan satu pokok bahasan terpecah di beberapa tempat berjauhan. Urutan bagiannya: Ringkasan Kawasan, Kependudukan, Pertanian dan Ekonomi, Infrastruktur dan Layanan, lalu Perbandingan Antar SP.
10. Tiap bagian diawali `x-sim.judul-bagian` yang memakai `<h2>`, sehingga hierarki tajuk tidak melompat dari `<h1>` halaman ke `<h3>` kartu grafik.
11. **Tiap baris grid wajib genap.** Lebar kartu disetel agar tidak menyisakan kolom menganggur di ujung baris; kartu yang berdiri sendiri diletakkan selebar halaman, di luar grid.

**Indikator 15, status kondisi SP.** Menampilkan kesiapan layanan dasar tiap satuan permukiman sebagai satu label yang mudah dibaca pemangku kepentingan (`rules.md` §10c).

- Kartu statistik memuat jumlah SP per status, contoh: **2 Mandiri, 3 Berkembang, 1 Perlu Penanganan**.
- Tabel di bawahnya memuat satu baris per SP beserta badge status dan skornya.
- **Label wajib disertai rincian pembentuknya.** Halaman rincian SP menampilkan nilai tiap parameter beserta bobotnya, sehingga petugas langsung tahu penyebab sebuah SP berstatus Perlu Penanganan, misalnya "jalan penghubung tidak ada, telekomunikasi rusak berat". Label tanpa rincian berhenti sebagai stempel dan tidak membantu perencanaan.
- SP yang memiliki parameter primer bernilai nol ditandai tegas, karena statusnya ditentukan aturan primer nol, bukan oleh skor (`rules.md` §10c.4 poin 11).
- Warna badge: Mandiri `success` · Berkembang `warning` · Perlu Penanganan `error`.
- Tanggal penilaian **wajib ditampilkan**, sebab status yang dihitung dari data lama dapat menyesatkan.

**Istilah yang dilarang.** Antarmuka tidak boleh memakai kata "terbelakang", "tertinggal", atau sebutan lain yang merendahkan, sebab yang dinilai adalah jalan dan listrik, hal yang berada di luar kendali warga. Ketiga label pada §11.30 kamus data adalah satu-satunya yang boleh dipakai.

---

## 10. Konvensi Format Tampilan

| Jenis | Aturan | Contoh |
|---|---|---|
| Zona waktu | **WITA (UTC+8)** — Kabupaten Malaka, NTT | — |
| Tanggal | `translatedFormat('d F Y')` | 10 Agustus 2026 |
| Tanggal + jam | `translatedFormat('d F Y, H:i')` + " WITA" | 10 Agustus 2026, 14:30 WITA |
| Tanggal ringkas (tabel) | `d/m/Y` | 10/08/2026 |
| Uang | `Rp ` + `number_format(x, 0, ',', '.')` | Rp 2.500.000 |
| Desimal | Koma sebagai pemisah desimal, titik sebagai pemisah ribuan | 1.234,567 |
| Volume panen | 3 angka desimal + satuan | 12,500 ton |
| Luas lahan | 2 angka desimal + " ha" | 1,25 ha |
| NIK / No. KK | Berkelompok 4 digit | 5321 0101 0101 0001 |
| Telepon | `+62 812-3456-7890` | — |
| Koordinat | 6 angka desimal | -9.512345, 124.912345 |
| Data kosong | Tanda hubung `—`, bukan teks "null" atau kosong | — |

**Locale:** `config/app.php` → `'locale' => 'id'`, `'timezone' => 'Asia/Makassar'`.

### 10.1 Istilah pada teks antarmuka

| Dipakai | Bukan | Berlaku pada |
|---|---|---|
| **email** | surel | seluruh teks yang dilihat pengguna |
| **Total** | Total kawasan | baris total pada tabel agregat |
| **fitur** | modul | penyebutan bagian sistem pada teks |
| **kewenangan** | izin | daftar tindakan yang boleh dilakukan role |

**Ketentuan:**

1. Aturan ini mengikat **teks yang tampil di layar** saja. Komentar kode dan dokumen acuan bebas memakai istilah mana pun, sebab pembacanya pengembang, bukan petugas lapangan.
2. **"email" dipilih daripada "surel"** karena lebih dikenal petugas dan warga di lokus, meski "surel" adalah padanan baku.
3. **Baris total cukup ditulis "Total"** tanpa keterangan cakupan. Judul halaman dan filter yang sedang aktif sudah menyatakan cakupannya, sehingga "Total kawasan" mengulang informasi yang ada di atasnya.
4. Baris total yang menjelaskan **apa** yang dijumlahkan tetap menuliskannya, contoh "Total luas lahan" atau "Total volume yang ditampilkan". Yang dihapus hanyalah penanda cakupan.
5. Uji dilarang mengunci kalimat penuh sebagai penanda keberadaan elemen. Pakai penanda struktural seperti kelas `motif-baris-total`, agar penyuntingan wording tidak memerahkan uji padahal tidak ada yang rusak.
6. **"modul" diganti "fitur"** sebab yang pertama adalah istilah pengembang, sedangkan petugas dinas mengenali sistem ini lewat menu dan fiturnya.
7. **"izin" diganti "kewenangan"**, bukan "hak akses". Menu induknya sendiri bernama Role dan Hak Akses, sehingga memakai istilah itu untuk salah satu isinya membuat pembaca mengira keduanya hal yang sama. Sistem ini memisahkan **kewenangan** (boleh melakukan apa) dari **cakupan data** (boleh melihat data siapa).
8. **Nama di dalam kode tidak wajib mengikuti aturan ini.** Tabel `permission`, kolom `permission.modul`, dan parameter rute `{modul}` mengikuti konvensi Laravel serta menyentuh skema dan URL. Istilah peramban seperti "izin lokasi" pada Geolocation API juga tetap, sebab menggantinya membuat pesan tidak cocok dengan dialog yang dilihat pengguna.

---

## 11. Aturan Tambahan Frontend

1. **Data dummy** ditempatkan di `app/Support/DummyData.php`, **bukan** ditulis langsung di dalam Blade, agar mudah ditukar ke data nyata. Struktur array wajib mengikuti nama kolom pada `data-dictionary.md`, sehingga saat backend siap penggantiannya cukup menukar sumber data tanpa menyentuh view.
2. **Semua teks antarmuka berbahasa Indonesia**, termasuk pesan validasi dan galat.
3. **Tab persisten** memakai query string `?tab=` sesuai `rules.md` §13.2 poin 1.
4. **Input teks otomatis huruf kapital** melalui middleware `UppercaseInput`, kecuali kredensial, enum, teks naratif, dan field `*_id`.
5. **Eager loading wajib** pada query yang dipakai di dalam perulangan view.
6. **Tanpa CSS sebaris**, seluruh gaya memakai kelas utilitas Tailwind.
7. **Tanpa teks berkode keras** untuk label status; gunakan PHP Enum di `app/Enums/` sesuai daftar pada `data-dictionary.md` §11.
8. **Utamakan komponen TailAdmin yang sudah ada** sebelum membuat komponen baru. Komponen khusus domain dibangun sebagai pembungkus komponen bawaan, bukan tulisan ulang dari nol.
9. **Verifikasi sebelum selesai:** `npm run build` dan `php artisan view:cache` harus hijau, ditambah smoke test browser pada **dua mode tema × dua lebar layar** (360px dan 1280px).

### 11.1 Aturan turunan ANTISLOP

Berlaku untuk seluruh teks dan elemen yang **tampil di antarmuka**. Dokumen internal di folder `agents/` dikecualikan.

| Aturan | Penerapan pada proyek ini |
|---|---|
| **R-02** dilarang em dash | Label, tombol, pesan validasi, pesan galat, dan judul halaman memakai koma, titik dua, atau tanda kurung. Untuk data kosong dipakai tanda hubung `-` (§10) |
| **R-15** CTA spesifik | Tombol menyebut objeknya: "Simpan Data Transmigran", "Unduh Rekap Panen", "Ajukan Pengaduan". Dilarang "Simpan", "Kirim", "Lihat" tanpa objek |
| **R-16** tanpa buzzword | Dilarang "canggih", "terintegrasi penuh", "solusi menyeluruh". Pakai kalimat yang menyebut apa yang terjadi: "Data tersimpan", "3 pengaduan menunggu ditindaklanjuti" |
| **R-17, R-38** angka jujur | Selama tahap data dummy, setiap halaman menampilkan penanda **"Data contoh"** yang terlihat jelas. Angka dummy tidak boleh disajikan seolah data nyata |
| **R-26** tanpa kontrol mati | Tombol yang belum berfungsi dihapus, bukan dibiarkan diam. Bila terpaksa ada, wajib berlabel "Segera hadir" dan diberi komentar `// TODO` di kode |
| **R-24** navigasi jujur | Menu sidebar hanya memuat halaman yang benar-benar ada. Menu di luar kewenangan role tidak dirender sama sekali |
| **R-30** bukan kloning | TailAdmin dipakai sebagai fondasi berlisensi MIT dengan seluruh `--color-brand-*` ditimpa palet Kementerian, halaman contoh dihapus, dan motif identitas sendiri ditambahkan (§2.3). Ini adopsi template, bukan peniruan identitas produk lain |
| **R-32** keyboard | Seluruh alur wajib dapat dioperasikan dengan Tab, Enter, dan Escape, dengan indikator fokus yang terlihat di kedua mode tema |
| **R-34** dua mode | Mode terang dan gelap sama-sama wajib berfungsi penuh, termasuk seluruh grafik dan badge (§3.2.3) |

---

## 12. Yang Masih Menunggu

| Item | Status |
|---|---|
| Pemilihan template fondasi | **SELESAI** — TailAdmin Laravel (MIT) |
| Font antarmuka | **SELESAI** — Outfit, mengikuti TailAdmin |
| Palet warna | **SELESAI** — navy/teal/sand/gold dari logo resmi |
| Arah desain dan dial | **SELESAI** — ENERGI 1 / RITME 2 / GERAK 1 (§2.2) |
| Mode gelap | **SELESAI** — toggle dua mode dipertahankan, palet dan kontras ditetapkan (§3.2.2) |
| Referensi tata letak Figma | `TODO` — menyusul dari tim. Tidak memblokir pekerjaan: tata letak memakai bawaan TailAdmin, penyesuaian dilakukan saat Figma diterima |
| Varian logo (PNG, favicon, versi mono) | `TODO` — diturunkan dari berkas webp pada Task 1.7 |
| Daftar satuan final per komoditas | `TODO` — menunggu konfirmasi lapangan (`notes.md` bagian 4). Nilai awal: Ton, Kuintal, Kilogram |
| Bentuk pasti motif identitas | `TODO` — arah sudah ditetapkan (§2.3); wujud akhirnya difinalkan saat Task 1.6 setelah logo diproses |
