# Laporan Delivery Gate ANTISLOP - Gelombang 1

**Tanggal:** 2026-08-11
**Revisi:** 2026-08-11 (kedua), setelah gate pertama terbukti keliru
**Cakupan:** 14 halaman Tahap 2 gelombang 1 (Task 2.1 sampai 2.12), 17 komponen bersama, 4 tata letak.
**Dasar:** `ANTISLOP-ID.md` Delivery Gate, `rules.md` §16.1.

Setiap PASS disertai bukti konkret. Laporan yang mengandung satu FAIL dilarang diserahkan.

**Perintah verifikasi yang menghasilkan bukti:**
`& "C:\xampp\php\php.exe" vendor\bin\pest` (195 lulus, 917 pernyataan), `npm run build`, `php artisan view:cache`, 12 uji `chart-config.js` dan 11 uji kontras WCAG lewat Node, ditambah **verifikasi visual lewat Edge headless** pada dua mode tema dan dua lebar layar.

---

## Koreksi atas gate pertama

Gate pertama dinyatakan PASS untuk keempat blok. **Pernyataan itu keliru dan ditarik.** Dua cacat lolos, keduanya jenis yang seharusnya justru ditangkap gate ini.

### Cacat 1: struktur HTML rusak, layout hancur

Penghapusan kolom pencarian pada Task 2.22 dilakukan lewat pemotongan indeks string. `IndexOf("        </div>")` berhenti di penutup pertama setelah blok, yang ternyata milik `<div class="relative">` **di dalam** `<form>`, bukan penutup blok pencarian. Akibatnya `</form>` dan dua `</div>` yatim tertinggal.

Peramban menanggapi dengan menutup `<header>` lebih awal, sehingga tombol tema, notifikasi, dan menu pengguna terlempar **keluar** header, dan seluruh area konten menjadi kosong.

**Mengapa 193 uji tetap hijau:** seluruhnya berbasis HTTP (`assertOk`, `assertSee`). PHP, Blade, dan `view:cache` tidak peduli HTML tidak seimbang. Hanya peramban yang terdampak saat membangun DOM, dan tidak satu pun uji membangun DOM.

**Klaim yang ditarik:**
- R-03 "tidak ada layout rusak di mobile" pada gate pertama tidak berdasar
- R-35 "sudah dijalankan dan diuji" tidak berdasar, karena hanya status HTTP yang diperiksa, bukan tampilan

### Cacat 2: nama sistem tidak terbaca di mode terang

Sidebar berlatar putih pada mode terang dan navy pada mode gelap, tetapi teks "SIM Transmigrasi" memakai `text-white` tanpa pasangan. Nama sistem tidak terbaca sama sekali di mode terang.

Cacat ini ada sejak Task 1.8 dan **luput dari gate pertama** karena verifikasi mode terang hanya dilakukan lewat pemindaian kelas `bg-*`, bukan dengan benar-benar membuka halaman pada mode terang. R-25 dan R-34 pada gate pertama karena itu juga tidak berdasar.

### Akar masalah yang sama

Kedua cacat berakar pada satu hal: **gate dijalankan tanpa pernah melihat hasilnya di peramban.** `rules.md` §13.2 poin 8 mewajibkan smoke test browser untuk setiap perubahan UI; kewajiban itu dilanggar.

Perbaikan pada revisi ini bukan hanya menambal dua cacat, melainkan menambahkan **uji regresi yang terbukti menangkap keduanya** dan menjadikan verifikasi visual bagian dari gate.

---

## Blok 1: Hard Gate

Seluruh jawaban harus **tidak**.

| Item | Jawaban | Bukti |
|---|---|---|
| R-02 Ada em dash pada teks antarmuka? | **tidak** | Uji otomatis memindai seluruh berkas di `pages/`, `components/`, `layouts/`, `errors/`: 0 temuan. Satu em dash pada `ui/button.blade.php` ditemukan dan diperbaiki |
| R-03 Ada gulir mendatar atau layout rusak di mobile? | **tidak** | **Verifikasi visual Edge headless**, bukan sekadar pemindaian kelas: tangkapan layar 5 halaman pada dua lebar, diperiksa satu per satu. Ditambah uji otomatis 0 lebar tetap berlaku di layar sempit, seluruh tabel dibungkus, dan **0 dari 68 berkas Blade punya tag tidak seimbang** |
| R-17 Ada statistik tanpa sumber nyata? | **tidak** | Seluruh angka berasal dari `DummyData` dan setiap halaman petugas menampilkan spanduk "Data contoh", diuji pada 6 halaman |
| R-18 Ada testimonial fiktif? | **tidak** | Tidak ada bagian testimonial. 37 foto pengguna fiktif bawaan template sudah dihapus pada Task 1.7; avatar memakai inisial nama |
| R-23 Ada aset dibuat tanpa instruksi? | **tidak** | Logo berasal dari berkas resmi Kementerian. Ilustrasi galat adalah aset bawaan TailAdmin berlisensi MIT. Avatar memakai inisial, bukan wajah karangan |
| R-24 Ada tautan menuju halaman yang tidak ada? | **tidak** | 726 tautan diperiksa pada 18 halaman: **0 href kosong atau `#`**. Menu petugas tidak dirender pada halaman publik, diuji khusus |
| R-25 Ada kontras di bawah WCAG AA? | **tidak** | 11 pasangan warna diuji dengan rumus WCAG 2.1: seluruhnya lulus, terendah `gold-700` + putih pada 4,74:1. **Ditambah verifikasi visual mode terang** yang menemukan teks putih tak terbaca pada sidebar, kini diperbaiki dan dijaga uji regresi |
| R-26 Ada kontrol mati? | **tidak** | Kolom pencarian global bawaan TailAdmin **dihapus** karena tidak ada mesin pencari lintas modul. Tombol lanjut pengaduan tidak dirender saat status Selesai, bukan dibiarkan diam |
| R-27 UI tidak punya keadaan kosong, memuat, atau galat? | **tidak** | Kelima keadaan tersedia dan ditinjau di `/galeri-komponen`: kosong, pencarian nihil, memuat (`x-sim.skeleton`), galat (`x-sim.error-state`), tanpa izin (halaman 403) |
| R-28 FAQ generik? | **tidak** | Tidak ada bagian FAQ |
| R-32 Tidak dapat dinavigasi keyboard atau tanpa indikator fokus? | **tidak** | 70 penanda `focus:outline-2` pada satu halaman daftar, 0 `outline-none` tanpa pengganti, 207 `aria-label`. Modal mengunci fokus dan menutup dengan Esc |
| R-33 Ada fitur ditambahkan lewat patch script? | **tidak** | Seluruh perubahan ditulis langsung pada berkas sumber |
| R-34 Salah satu mode tema rusak? | **tidak** | **Kedua mode dibuka sungguhan di peramban**, bukan hanya dipindai kelasnya. Mode terang diverifikasi lewat `--blink-settings=preferredColorScheme=1`. Ditambah uji otomatis: 0 latar terang tanpa pasangan `dark:`, 0 teks putih di atas permukaan terang. Grafik digambar ulang lewat `MutationObserver`; kelima warna badge punya varian gelap |
| R-35 Belum dijalankan dan diuji sebelum diserahkan? | **tidak** | 195 uji lulus, `npm run build` dan `view:cache` hijau, 22 rute 200 dan 7 rute 404. **Ditambah pemeriksaan DOM hasil render** lewat `--dump-dom` untuk memastikan peramban tidak membuang tag, serta tangkapan layar yang diperiksa satu per satu |
| R-36 Ada klaim keamanan atau performa yang dikarang? | **tidak** | Tidak ada klaim semacam itu di antarmuka |
| R-37 Dibangun tanpa arah desain? | **tidak** | Arah dinyatakan pada `ui-spec.md` §2.1: dial ENERGI 1 / RITME 2 / GERAK 1 beserta alasannya |
| R-38 Ada konten realistis yang dikarang? | **tidak** | Data contoh mencerminkan lokus sebenarnya dan ditandai jelas. Nama pengguna sistem memakai penanda yang jujur |

**Hasil Blok 1: PASS**

---

## Blok 2: Purpose-Gate

Teknik diperbolehkan; FAIL bila muncul sebagai default tanpa tujuan tertulis.

| Item | Jawaban | Alasan tertulis |
|---|---|---|
| R-01 Gradient tanpa tujuan? | **tidak** | Satu-satunya gradasi adalah `.motif-header-halaman`, garis bawah berhenti di sepertiga lebar sebagai motif identitas (`ui-spec.md` §2.3) |
| R-04 Ikon generik tak relevan? | **tidak** | Ikon menyebut isi kontennya: mata untuk lihat, tong sampah untuk hapus, pensil untuk ubah, kunci untuk kata sandi. Tanpa sparkle, robot, atau orb |
| R-06 Font monospace besar atau typeface tanpa alasan? | **tidak** | Outfit dipilih karena angkanya tabular sehingga digit sejajar pada tabel padat data (`ui-spec.md` §2.5). Monospace hanya untuk contoh nama berkas |
| R-07 Background grid tanpa tujuan? | **tidak** | `common-grid-shape` hanya pada halaman masuk sebagai latar panel identitas, tidak diulang di halaman kerja |
| R-08 Arrow dekoratif di hampir semua tombol? | **tidak** | Panah hanya pada 3 tempat berfungsi: kembali ke dashboard, penanda perpindahan status, dan tautan keluar aplikasi |
| R-09 Badge kapsul tanpa fungsi? | **tidak** | Seluruh badge menyatakan status nyata dari PHP Enum: verifikasi, hunian, prioritas, kondisi. Tanpa badge "Beta" atau "New" |
| R-10 Glassmorphism di banyak elemen? | **tidak** | Tidak dipakai sama sekali |
| R-12 Shadow besar di semua komponen? | **tidak** | Kartu memakai garis tepi, bukan bayangan, karena halaman memuat banyak kartu sekaligus (`ui-spec.md` §2.5). Bayangan hanya pada modal dan tombol tema |
| R-13 Glow di mana-mana? | **tidak** | Tidak dipakai |
| R-14 Semua feature card identik tanpa alasan? | **tidak** | Kartu statistik memang seragam **karena** fungsinya membandingkan angka sejenis; keseragaman itu yang membuatnya dapat dipindai. Kartu grafik justru berbeda lebar (`xl:col-span-2` pada 4 dari 11) |
| R-19 Animasi template atau bertentangan dengan dial GERAK? | **tidak** | Dial GERAK 1 dipenuhi: animasi ApexCharts **dimatikan**, transisi hanya pada hover, dropdown, dan modal. Satu-satunya animasi berulang adalah `animate-pulse` pada skeleton, yang memang menandakan proses berjalan |
| R-22 Ilustrasi generik tanpa hubungan? | **tidak** | Ilustrasi hanya pada halaman 404, menggambarkan keadaan galat itu sendiri |

**Hasil Blok 2: PASS**

---

## Blok 3: Liveliness

Seluruh jawaban harus **ya**.

| Item | Jawaban | Bukti |
|---|---|---|
| Dial ditetapkan eksplisit? | **ya** | ENERGI 1 / RITME 2 / GERAK 1, dinyatakan pada `ui-spec.md` §2.2 beserta alasan tiap nilainya |
| Output konsisten dengan dial? | **ya** | **RITME 2 terbukti:** empat jenis halaman berkomposisi berbeda, bukan sekadar bertukar warna. Dashboard memakai kartu lalu grid grafik tak sama lebar; daftar memakai lebar penuh didominasi tabel; detail memakai dua kolom asimetris dengan ringkasan menetap; rekap memakai tabel agregat **tanpa kartu statistik**, diuji otomatis bahwa `motif-judul-kartu` tidak muncul di sana. **GERAK 1 terbukti:** animasi grafik dimatikan |
| Ada titik fokus jelas per layar? | **ya** | Dashboard menuju kartu indikator utama; daftar menuju tabel; detail menuju ringkasan entitas di kiri; halaman warga menuju satu tombol kirim selebar isian |
| Whitespace bersifat struktural? | **ya** | Jarak memisahkan bagian form bertahap dan kelompok kartu, bukan ruang sisa. Bagian form dipisah garis tepi beserta judul bagian |
| Ada satu aksen yang disengaja? | **ya** | Gold dipakai hemat pada empat hal saja: motif identitas, prioritas Mendesak, komoditas unggulan, indikator melampaui target. Dilarang untuk tombol biasa dan tautan (`ui-spec.md` §2.4) |
| Ada motif identitas yang diulang? | **ya** | Empat penerapan bentuk sudut dari logo Kementerian: `.motif-menu-aktif`, `.motif-judul-kartu`, `.motif-header-halaman`, `.motif-baris-total`. Terpakai nyata di sidebar, kartu statistik, kepala halaman, dan baris total tabel rekap |
| Pembacaan Desain dinyatakan? | **ya** | `ui-spec.md` §2.1: aplikasi pendataan pemerintahan untuk operator desa, pendamping lapangan, dan staf dinas, dengan bahasa visual institusional yang tenang dan padat data |

**Hasil Blok 3: PASS**

---

## Blok 4: Craftsmanship dan Quality Locks

Seluruh jawaban harus **tidak**.

| Item | Jawaban | Bukti |
|---|---|---|
| C-1 Ada keputusan yang alasannya hanya "default AI"? | **tidak** | 13 keputusan desain utama punya alasan satu baris pada `ui-spec.md` §2.5 |
| C-2 Ada elemen interaktif tanpa perilaku? | **tidak** | 0 tautan mati dari 726. Rute tulis membalas pesan sesi sehingga alur dapat dicoba utuh |
| C-3 Ada section pengisi template? | **tidak** | Setiap bagian melayani data nyata modulnya. Tidak ada hero, trusted-by, maupun pricing |
| C-4 UI rusak di suatu keadaan, tema, atau tanpa mouse? | **tidak** | Lima keadaan tersedia, dua mode tema diuji, navigasi keyboard terjaga |
| C-5 Ada klaim atau statistik karangan? | **tidak** | Seluruh angka dari data contoh bertanda jelas |
| R-05 Layout mengikuti template AI atau irama seragam? | **tidak** | Empat komposisi berbeda sesuai RITME 2, diuji otomatis |
| R-11 Semua elemen dibuat pil? | **tidak** | Radius dibedakan menurut peran: kartu `rounded-2xl`, kontrol `rounded-lg`, badge `rounded-full`. Perbedaan ini alat hierarki (`ui-spec.md` §3.5) |
| R-15 CTA masih generik? | **tidak** | Diuji otomatis: "Simpan Data Transmigran", "Simpan Hasil Panen", "Tambah Data Rumah", "Tolak Data Lahan". Tanpa "Simpan" telanjang |
| R-16 Ada buzzword marketing? | **tidak** | Kalimat menyebut kejadian nyata: "3 pengaduan menunggu ditindaklanjuti", "890 dari 1.200 data terverifikasi" |
| R-20 Desain masih generik bila logo diganti? | **tidak** | Palet diambil dari logo resmi Kementerian dan motif identitas diturunkan dari bentuk sudutnya. Mengganti logo akan memutus hubungan warna yang saat ini terlihat menyatu |
| R-21 Dark mode dipaksa atau ditunda? | **tidak** | Toggle dua mode berfungsi penuh pada keempat jenis halaman |
| R-29 Palet melebihi 2-3 warna inti + 1 aksen? | **tidak** | Navy sebagai inti, teal sekunder, gold aksen tunggal, sand pelengkap terbatas. Warna semantik hijau, kuning, merah hanya untuk status |
| R-30 Terlihat seperti clone produk populer? | **tidak** | TailAdmin dipakai sebagai fondasi MIT dengan seluruh `--color-brand-*` ditimpa navy, halaman contoh dihapus, dan motif identitas sendiri ditambahkan |
| R-31 Ada keputusan yang alasannya tak dapat ditulis satu baris? | **tidak** | Seluruhnya tercatat pada `ui-spec.md` §2.5 dan komentar berkas |

**Hasil Blok 4: PASS**

---

## Verifikasi visual

Dijalankan memakai Edge headless, dan menjadi bagian tetap gate mulai revisi ini.

| Yang diperiksa | Cara | Hasil |
|---|---|---|
| Struktur DOM hasil render | `--dump-dom` | Tidak ada tag dibuang peramban; `</header>` berada di tempat yang benar |
| Mode gelap, 1280px | `--screenshot` | Sidebar, header, tabel, badge terbaca |
| Mode terang, 1280px | `--blink-settings=preferredColorScheme=1` | Terbaca setelah perbaikan sidebar |
| Layar sempit | `--window-size` | Sidebar tersembunyi, kartu menumpuk satu kolom, tanpa gulir mendatar |
| Halaman diperiksa | dashboard, daftar, detail, rekap, publik | Seluruhnya wajar |

**Catatan teknis penting untuk pemeriksaan berikutnya:** Edge headless memaksa **viewport minimum sekitar 496px**. Perintah `--window-size=360` menghasilkan render 496px yang lalu dipangkas menjadi gambar 360px, sehingga teks tampak terpotong padahal tidak meluber. Ini sempat disalahartikan sebagai cacat. Untuk menguji 360px sesungguhnya, gunakan perangkat nyata atau DevTools dengan emulasi perangkat.

## Kesimpulan

**Keempat blok PASS, kali ini disertai verifikasi visual.** Gelombang 1 layak dipresentasikan pada FGD.

Perbaikan selama gate pertama:

1. **Kolom pencarian global dihapus** dari header. Tidak ada mesin pencari lintas modul, sehingga kolomnya adalah kontrol mati berlabel Inggris (R-26, R-02).
2. **Em dash pada `ui/button.blade.php`** diganti (R-02).
3. **Label "Learn more"** pada `ui/alert.blade.php` diganti "Lihat selengkapnya" (R-15).
4. **`aria-label` berbahasa Inggris** pada dua tombol header diterjemahkan (R-32).
5. **Tombol ganti tema ditambahkan pada halaman galat** (R-34).
6. **Komponen `x-sim.skeleton` dan `x-sim.error-state` dibuat** (R-27).

Perbaikan pada revisi kedua, setelah cacat ditemukan:

7. **Struktur `app-header.blade.php` diperbaiki**: `</form>` dan dua `</div>` yatim dibuang. Kedalaman DOM kembali 0.
8. **Teks sidebar diberi pasangan mode terang**: `text-navy-500 dark:text-white`, sehingga nama sistem terbaca di kedua mode.
9. **Uji keseimbangan tag HTML ditambahkan**, memindai seluruh 68 berkas Blade dengan tumpukan tag. Terbukti menangkap cacat pertama saat disisipkan ulang.
10. **Uji teks putih di atas permukaan terang ditambahkan.** Terbukti menangkap cacat kedua saat disisipkan ulang.
11. **Cakupan seluruh uji berbasis berkas diseragamkan** lewat `Tests\Support\BerkasBlade`, menggantikan `glob()` yang ternyata **tidak rekursif**. Sebelumnya 3 berkas luput diperiksa, termasuk `galeri-komponen.blade.php`.
12. **Dua uji yang aturannya keliru diperbaiki:** uji lebar tetap sempat membaca koordinat SVG sebagai lebar kelas dan menganggap `sm:w-[361px]` melanggar padahal hanya aktif di layar lebar; uji teks Inggris sempat menganggap komentar yang melarang sebuah teks sebagai pelanggaran teks itu sendiri.

## Yang masih perlu dilakukan manusia

Gate ini memverifikasi hal yang dapat diperiksa otomatis dan lewat peramban headless. Tiga hal berikut **tetap wajib** diperiksa manusia:

1. **Membuka setiap halaman pada perangkat nyata berlebar 360px.** Edge headless tidak dapat merender viewport di bawah 496px, sehingga lebar 360px sesungguhnya belum pernah diuji.
2. **Menjalankan seluruh alur hanya dengan keyboard**, dari Tab pertama sampai modal tertutup.
3. **Menguji halaman warga kepada warga sungguhan**, bukan menilainya lewat kacamata petugas.

## Pelajaran yang dicatat

Gate yang dijalankan tanpa melihat hasilnya di peramban dapat menghasilkan laporan PASS yang seluruhnya keliru. Uji berbasis HTTP memeriksa apakah server membalas, bukan apakah manusia dapat memakainya. Keduanya berbeda, dan perbedaan itu persis yang membuat dua cacat ini lolos.
