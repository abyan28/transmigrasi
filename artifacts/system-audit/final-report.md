# Laporan Final Audit Sistem — Task 11.1

## Status Perbaikan (dimulai 2026-09-08)

- [FIXED] **SYS-H01, SYS-H02, SYS-H08, SYS-H09, SYS-M14** — boundary lifecycle akun diperketat: middleware menolak sesi akun nonaktif termasuk seluruh rute ganti kata sandi; helper shared merotasi remember token dan mencabut sesi database; reset Admin, nonaktif, serta perubahan role memakai revocation; mutasi user/pivot/audit dibungkus transaksi; role nonaktif ditolak; hanya Admin terkunci dapat membuat/memberi/mencabut role Admin. Penonaktifan dan demosi Admin mengunci target serta seluruh Admin aktif sebelum memeriksa invariant Admin terakhir.
  - Regression: `PengaturanPenggunaTest` **20 passed, 64 assertions**; guard rute ganti sandi akun nonaktif **3 passed, 12 assertions**.
  - Files: `app/Support/SesiPengguna.php`, `app/Http/Middleware/PastikanPenggunaAktif.php`, `bootstrap/app.php`, `app/Http/Controllers/PengaturanPenggunaController.php`, `tests/Database/PengaturanPenggunaTest.php`.

- [FIXED] **SYS-M08, SYS-M19** — pemulihan sandi kini memiliki throttle per IP+kredensial, dan verifikasi kode berjalan dalam transaksi dengan row lock; konsumsi kode, perubahan password, pencabutan sesi, rotasi remember token, dan audit menjadi satu unit atomik.
  - Regression: `PemulihanSandiTest` — **14 passed, 94 assertions**.
  - Files: `config/sim.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, `app/Http/Controllers/Auth/PemulihanSandiController.php`, `tests/Database/PemulihanSandiTest.php`.

- [FIXED] **SYS-H04, SYS-H05** — penghapusan poktan/saprotan kini ditolak selama masih memiliki anggota/distribusi/riwayat tanam; histogram `rincian_kondisi` inventaris/fasilitas sekarang divalidasi terhadap master kondisi, wajib berjumlah sama dengan unit, disimpan, dan otomatis diisi dari kondisi umum untuk kompatibilitas form lama.
  - Regression focused: **4 passed, 11 assertions**.
  - Files: `app/Support/ValidationRules.php`, `InventarisSpController.php`, `FasilitasSpController.php`, `PoktanController.php`, `SaprotanController.php`, serta tiga test Database terkait.

- [FIXED] **SYS-H10, SYS-M01** — produktivitas tiap baris laporan sekarang dikonversi dengan faktor satuan yang sama seperti produksi sebelum dilabel `ton/ha`; dokumen SP memakai local scope `terlihatOlehPengguna()` sehingga operator luar penugasan mendapat 404.
  - Regression: `LaporanDataTest` **4 passed/12 assertions**; dokumen SP focused **1 passed/1 assertion**.
  - Files: `app/Support/LaporanData.php`, `app/Support/PetaModulBerkas.php`, dan test terkait.

- [FIXED] **SYS-M20** — kuantitas integer polos kini dikonversi ke numeric cell hanya pada sel yang ditandai `data-export-number`; NIK, telepon, tahun, dan kode tetap teks.
  - Verification: `node --check resources/js/export-laporan.js` dan `npm run build` lulus.
  - Files: `resources/js/export-laporan.js`, `resources/views/pages/laporan/isi/alsintan.blade.php`.

- [FIXED] **SYS-M18** dan [IMPROVED] **SYS-M21** — static cache user-scoped pada laporan dihapus; filter Monografi sekarang membangun dataset sekali, bukan dua kali. Probe filter enam SP turun dari **2.164 query/5,95 dtk** menjadi **1.227 query/3,41 dtk**. Sisa N+1 lintas helper masih terbuka dan memerlukan refactor terukur berikutnya.
  - Files: `app/Support/LaporanData.php`.

- [FIXED] **SYS-M05, SYS-M13, SYS-M22, SYS-M23** — workflow preview diselaraskan ke PHP 8.3 yang memenuhi lock; cookie Secure production dan limiter recovery didokumentasikan; seeder Admin membaca `config()` sehingga aman setelah config cache; README kini jelas menyebut preview Pages manual 14 URL, bukan deployment stateful.
  - Regression: `AdminAwalSeederTest` **5 passed/10 assertions**; workflow YAML lint dan build lulus.
  - Files: `.github/workflows/deploy.yml`, `.env.example`, `config/sim.php`, `AdminAwalSeeder.php`, `AdminAwalSeederTest.php`, `README.md`.

- [FIXED] **SYS-M04** — setup `SpTest` sekarang menanam master pilihan resmi sebelum `SpSeeder`; tiga kegagalan audit hilang.
  - Regression: `SpTest` **11 passed, 343 assertions**.

- [FIXED] cakupan **SYS-H02** yang tersisa — ganti sandi mandiri, ganti wajib/masuk pertama, recovery kode, reset Admin, dan Artisan darurat seluruhnya memakai helper revocation sesi/remember token.

- [FIXED] **SYS-H06** — status pengaduan sekarang dimuat ulang dengan `lockForUpdate()` di dalam transaksi sebelum transisi divalidasi; request dengan status basi ditolak sehingga tidak dapat membuat riwayat bercabang. Efek samping notifikasi/surel tetap dijalankan hanya setelah commit berhasil.
  - Regression: `PengaduanTest` — **14 passed, 64 assertions**; test interleaving khusus terbukti merah sebelum perbaikan dan hijau sesudahnya.
  - Files: `app/Http/Controllers/PengaduanController.php`, `tests/Database/PengaduanTest.php`.

- [FIXED] **SYS-H07** — aplikasi tidak lagi mempercayai seluruh proxy. Default sekarang tidak mempercayai forwarded headers dan deployment dapat mengisi daftar IP/CIDR eksplisit melalui `SIM_TRUSTED_PROXIES`; contoh konfigurasi aman ditambahkan ke `.env.example`.
  - Regression: `PengaduanPublikTest` — **9 passed, 34 assertions**; forwarded IP dari klien langsung terbukti diabaikan.
  - Files: `bootstrap/app.php`, `.env.example`, `tests/Database/PengaduanPublikTest.php`.

- [FIXED] **SYS-M03** — helper unggahan shared kini mendaftarkan kompensasi rollback: bila transaksi database gagal setelah berkas fisik ditulis, berkas tersebut otomatis dihapus. Perbaikan berlaku pada seluruh controller yang memakai `MenyimpanBerkas`, tanpa scheduler cleanup baru.
  - Regression: `PengaduanTest` — **15 passed, 67 assertions**; forced rollback meninggalkan nol file dan nol registry row.
  - Files: `app/Http/Controllers/Concerns/MenyimpanBerkas.php`, `tests/Database/PengaduanTest.php`.

- [FIXED] **SYS-M15** — pembuatan pending email dan enqueue token kini berada dalam transaksi yang sama, dan kredensial sementara baru dicabut setelah enqueue berhasil. Kegagalan queue tidak lagi ditelan: transaksi rollback sehingga sandi/remember token tetap dapat dipakai dan pending token palsu tidak tertinggal.
  - Regression: `PendingEmailChangeTest` — **15 passed, 116 assertions**; queue failure terpaksa terbukti me-rollback kredensial dan pending row.
  - Files: `app/Support/PendingEmailChangeService.php`, `tests/Database/PendingEmailChangeTest.php`.

- [FIXED] **SYS-M16, SYS-M17** — deduplikasi riwayat penilaian SP sekarang membandingkan seluruh snapshot (`skor`, `status`, `ada_primer_nol`, dan `rincian`), bukan status saja. Snapshot dan notifikasi kondisi SP juga dijalankan dalam satu transaksi dengan row lock, sehingga kegagalan notifikasi tidak meninggalkan mutasi yang tampak gagal dan writer paralel terserialisasi.
  - Regression: `NotifikasiTest` — **16 passed, 37 assertions**; test perubahan snapshot, forced notification failure, dan row lock SP terbukti hijau.
  - Files: `app/Support/LayananNotifikasi.php`, `tests/Database/NotifikasiTest.php`.

- [FIXED] **SYS-M02** — boundary impor sekarang mengenali XLSX hanya dari signature ZIP dan CSV dari isi teks/header; ekstensi/nama klien tidak lagi dipakai sebagai fallback. Nama salah tidak menolak isi sah, sedangkan teks yang sekadar bernama `.xlsx` ditolak sebelum parser.
  - Regression penuh sebelumnya: `ImporTest` **65 passed, 222 assertions**; verifikasi boundary terakhir **6 passed, 15 assertions**.
  - Files: `app/Http/Controllers/ImporController.php`, `tests/Database/ImporTest.php`.

- [FIXED] **SYS-M09** — pemeriksaan dan pembuatan notifikasi belum dibaca berada dalam transaksi per penerima dan diserialisasi dengan row lock pada baris pengguna yang selalu ada; ini menghindari ketergantungan pada gap lock hasil query notifikasi kosong.
  - Regression: dedupe focused lulus **1 test, 3 assertions**; `NotifikasiTest` sebelumnya **16 passed, 37 assertions**.
  - Files: `app/Models/Notifikasi.php`, `tests/Database/NotifikasiTest.php`.

- [FIXED] **SYS-M10** — pembacaan urut tertinggi nomor pengaduan per awalan+tahun kini memakai `lockForUpdate()` di dalam transaksi create yang sudah ada, sehingga pembuat nomor paralel terserialisasi sebelum memilih urutan berikutnya.
  - Regression: `PengaduanTest` — **16 passed, 68 assertions**, termasuk bukti query rentang memakai row lock.
  - Files: `app/Support/NomorPengaduan.php`, `tests/Database/PengaduanTest.php`.

- [FIXED] **SYS-H03, SYS-M06, SYS-L03** — seluruh 18 script browser memakai helper shared yang wajib login sebelum mengakses rute internal, menangkap `Runtime.exceptionThrown`/`console.error`, menolak redirect kembali ke `/login`, dan gagal keras bila WebSocket/Edge/server/kredensial tidak tersedia. Runner resmi `npm run test:browser` sekarang terdaftar.
  - Verification: `npm run test:browser:harness` PASS; syntax seluruh script lulus; adopsi helper **18/18**. Script terfokus yang diperbarui lulus: export **16/16**, filter laporan **54/54**, form panen **23/23**, form penanaman **15/15** (jalur stok kosong), komposisi lahan **8/8**, master daftar pilihan **10/10**, master wilayah **19/19**, penilaian kondisi **12/12**, lebar halaman **36/36**. Full runner masih menemukan exception lama di form distribusi Alsintan (`distribusi[pid]` saat pilihan berubah), sehingga suite keseluruhan belum diklaim hijau.
  - Files: `tests/Browser/browser-harness.mjs`, `tests/Browser/uji-harness.mjs`, `tests/Browser/jalankan-semua.mjs`, 18 script browser domain, `package.json`; defect runtime tambahan diperbaiki di form Rumah, Penanaman, dan Status Kondisi.

- [FIXED sebagian] **SYS-L02** — penjaga route-map kini menyapu seluruh named authenticated route (GET dan write), bukan hanya POST/PUT/PATCH/DELETE. Pengecualian `ganti-kata-sandi.cek-username` dicatat eksplisit karena endpoint itu milik setiap pengguna terautentikasi. Quality gate CI umum dan pin action SHA masih terbuka.
  - Regression: `IzinPenegakanRuteTest` — **17 passed, 38 assertions**.
  - Files: `app/Support/PetaIzinRute.php`, `tests/Database/IzinPenegakanRuteTest.php`.

- [FIXED] **SYS-L01** — 12 pelanggaran format yang dilaporkan audit dibersihkan menggunakan Pint pada file yang tepat; tidak ada perubahan perilaku yang disengaja.
  - Verification: `vendor/bin/pint --test` — **358 files PASS**.

## Baseline audit asli (historis, sebelum remediasi)

Bagian berikut mempertahankan temuan awal pada SHA audit agar jejak pemeriksaan tidak hilang. Status terkini dan bukti perbaikan berada di bagian **Status Perbaikan** di atas serta `final-findings.json`; teks “terbuka/gagal” di bawah bukan status HEAD setelah remediasi.

## Identitas dan putusan

- SHA baseline audit: `29549bf0b0a2aed6a48fa587f77cec261dfec3bf`
- Branch baseline: `main`
- Mode baseline: audit-only
- Putusan: **belum layak dinyatakan siap produksi**.
- Temuan final setelah rekonsiliasi seluruh audit paralel: **0 Critical, 11 High, 24 Medium, 3 Low**.

## Temuan High

1. **SYS-H01 — akun nonaktif tetap dapat memakai sesi aktif.** Login baru ditolak, tetapi tidak ada middleware aktif-akun atau pencabutan sesi saat Admin menonaktifkan user.
2. **SYS-H02 — perubahan status/kredensial/role/cakupan tidak mencabut seluruh sesi.** Reset Admin, ganti sandi mandiri, wajib-ganti, recovery, Artisan, dan mutasi role/SP tidak memakai revocation shared yang sudah ada pada konfirmasi email.
3. **SYS-H03 — browser suite dapat hijau pada halaman login.** Sebanyak 17/18 script internal tidak login, tidak mengassert URL/heading target, dan tidak menangkap exception JS; reproduksi `uji-lebar-halaman` lulus 18/18 pada `/login`.
4. **SYS-H04 — soft-delete poktan/saprotan dapat menyembunyikan alur produksi aktif.** Penghapusan induk tidak menolak anggota/distribusi/penanaman/panen hidup, sementara anak disaring melalui induknya.
5. **SYS-H05 — `rincian_kondisi` inventaris/fasilitas hilang diam-diam.** Form mengirim histogram, model/schema menyediakan JSON, tetapi controller dan importer tidak memvalidasi/menyimpan invariant `Σ rincian = jumlah`.
6. **SYS-H06 — transisi pengaduan rentan lost update.** Status dibaca dan divalidasi sebelum transaksi/row lock; request paralel dapat menulis dua riwayat dari status awal yang sama dan mengirim efek samping ganda.
7. **SYS-H07 — semua proxy dipercaya.** Severity High bersyarat deployment: bila origin terekspos atau proxy tidak membersihkan forwarded headers, throttle dan IP audit dapat dipalsukan.
8. **SYS-H08 — race dapat menonaktifkan seluruh Admin.** Guard “Admin aktif terakhir” menghitung akun di luar transaksi/lock; dua Admin dapat saling menonaktifkan setelah keduanya membaca jumlah dua. Jalur update juga dapat mendemote Admin terakhir.
9. **SYS-H09 — `pengguna.ubah` memungkinkan privilege escalation ke Admin terkunci.** Role Admin aktif ikut opsi form dan controller menerima setiap `role_id` yang ada; user manager non-Admin dengan izin ubah pengguna dapat mempromosikan diri/orang lain.
10. **SYS-H10 — produktivitas laporan panen salah satuan.** Produksi dikonversi ke ton tetapi produktivitas dibiarkan dalam satuan sumber lalu dilabel `ton/ha`; probe Kilogram menampilkan 1282 ton/ha alih-alih 1,282 ton/ha.
11. **SYS-H11 — backup/restore tidak tersedia.** DB dan `storage/app/private` diwajibkan masuk strategi backup, tetapi repo tidak memiliki job/runbook maupun restore drill.

## Temuan Medium

- **SYS-M01:** dokumen `satuan_permukiman` memakai query model biasa, bukan `terlihatOlehPengguna()`; Operator Per-SP berpotensi mengunduh dokumen SP lain bila mengetahui ID/nama.
- **SYS-M02:** boundary impor memilih ekstensi nama klien sebelum deteksi isi; parser internal tetap memiliki hardening ZIP/formula kuat.
- **SYS-M03:** file ditulis sebelum registry/pivot DB; rollback tidak menghapus file yatim dan scheduler cleanup yang dijanjikan tidak ada.
- **SYS-M04:** suite Database serial gagal tiga `SpTest` karena master pilihan P1/P2 tidak ter-seed pada setup/fresh bootstrap tersebut.
- **SYS-M05:** workflow memakai PHP 8.2 tetapi lock memuat `maennchen/zipstream-php 3.2.2` yang memerlukan PHP 64-bit ≥8.3.
- **SYS-M06:** browser suite tidak menangkap uncaught JavaScript/Alpine exception.
- **SYS-M07:** audit UI/UX lama tidak memiliki integritas bukti memadai dan stale terhadap HEAD.
- **SYS-M08:** batas lima percobaan recovery dibaca/increment tanpa lock atau atomic conditional update; endpoint verifikasi tidak memiliki throttle HTTP khusus.
- **SYS-M09:** deduplikasi notifikasi menggunakan `exists()` lalu `create()` tanpa unique gate, sehingga race menghasilkan unread duplicate.
- **SYS-M10:** nomor urut pengaduan dihitung `max()+1` tanpa lock/counter unik; dua nomor paralel dapat berbagi komponen urut.
- **SYS-M11:** dashboard/laporan mengulang full-load per tahun dan dapat tumbuh mendekati `tahun × seluruh penanaman`; belum ada load guard.
- **SYS-M12:** queue default database dan mail di-queue, tetapi repo tidak menyediakan worker produksi/supervisor; email dapat tertahan di `jobs`.
- **SYS-M13:** `SESSION_SECURE_COOKIE` tidak didokumentasikan/default aman untuk production; perlu deployment smoke test HTTPS-cookie.
- **SYS-M14:** update user/role, sync pivot, dan audit tidak berada dalam satu transaksi; kegagalan tengah jalan dapat meninggalkan authorization state separuh berubah.
- **SYS-M15:** pending email untuk akun berkredensial sementara mencabut password/sesi sebelum queue token dijamin; queue exception ditelan sehingga akun dapat terkunci tanpa token.
- **SYS-M16:** beberapa notifikasi/penilaian dibuat sesudah transaksi bisnis commit; kegagalan menghasilkan 500 atas mutasi yang sebenarnya berhasil dan mendorong retry duplikat.
- **SYS-M17:** riwayat penilaian SP berhenti hanya karena kategori status sama, sehingga perubahan skor/rincian dalam kategori yang sama tidak tercatat; race juga dapat membuat snapshot ganda.
- **SYS-M18:** `LaporanData::anggotaAktifPerPoktan()` memakai static cache tanpa user/scope key; pada Octane/RoadRunner/worker persisten agregat dapat terbawa lintas pengguna.
- **SYS-M19:** endpoint kirim recovery tidak memiliki throttle; kredensial tidak dikenal tetap menjalankan bcrypt untuk timing parity, sehingga request anonim tak terbatas dapat menguras CPU.
- **SYS-M20:** exporter Excel hanya mengonversi angka bertitik/koma; kuantitas polos seperti `3`, `999`, dan `0` tetap teks. Identitas digit memang perlu teks, jadi perbaikan harus berbasis kolom/metadata, bukan regex global.
- **SYS-M21:** runtime `/laporan/monografi-sp` pada enam SP menjalankan 3.137 query/8,74 detik; `filterLaporan()` membangun monografi dua kali dan helper melakukan repeated full-load.
- **SYS-M22:** `AdminAwalSeeder` memanggil `env()` langsung; setelah `config:cache`, nilai bootstrap Admin dapat diabaikan.
- **SYS-M23:** README menyatakan deployment otomatis/full-site, sedangkan workflow manual dan hanya preview 14 halaman publik.
- **SYS-M24:** tidak ada deployment path stateful untuk Laravel; GitHub Pages tidak dapat menyediakan auth, CRUD, DB persisten, private upload, atau queue.

## Risiko test/adversarial tambahan (belum dinaikkan sebagai defect runtime mandiri)

- Semua laporan pertanian belum memiliki matriks Per-SP langsung; scope source terlihat benar, tetapi evidence test end-to-end belum lengkap.
- Panen dibatalkan disaring di banyak query, tetapi belum ada satu test ekstrem yang membuktikan seluruh rekap/laporan mengabaikannya.
- Komponen `wilayah-picker` memakai ID DOM tetap; halaman yang merender modal tambah+ubah bersamaan berpotensi duplicate IDs. Perlu browser reproduction sebelum severity produk ditetapkan.
- Test metric terbaru bercampur: denominator behavioral, tetapi sebagian klaim enum/label/hardcode hanya source-string/markup checks.

## Temuan Low dan keputusan tertunda

- **SYS-L01:** `vendor/bin/pint --test` menemukan 12 style issue.
- **SYS-L02:** test route-map hanya menyapu route tulis; workflow tidak menjalankan test/audit/schema parity dan actions memakai mutable tags.
- **SYS-L03:** seluruh browser script memiliki jalur `LEWAT` yang keluar sukses bila WebSocket tidak tersedia dan tidak ada runner wajib di `package.json`; angka PASS historis dapat stale.
- **DEC-01:** rules menghendaki identifier publik UUID, tetapi route/controller/view saat ini tetap memakai PK integer. Ini material namun merupakan migrasi kompatibilitas lintas aplikasi; masukkan batch tersendiri, jangan dicampur dengan patch keamanan kecil.
- Respons login akun nonaktif berbeda dari kredensial salah adalah keputusan produk eksplisit dan sudah ditest; residual enumeration diterima kecuali kebijakan berubah.

## Audit UI/UX lama

Laporan lama **tidak layak menjadi bukti HEAD**:

- 172 rekaman = 43 halaman × 4 konfigurasi, bukan bukti 53 titik independen.
- 10/43 halaman atau 40 rekaman memakai route salah/404.
- JSON merujuk 172 screenshot unik, tetapi 19 file tidak ada; coverage menyebut 5 file yang tidak ada.
- 18 PNG salah dimensi terhadap label viewport.
- 153 file fisik memuat 9 grup hash duplikat yang melibatkan 31 file.
- 37/826 rasio kontras berada di luar rentang matematis WCAG 1..21.
- Artefak dibuat sekaligus dengan implementasi meski mengklaim audit-only; 21–22 view berubah lagi sesudahnya tanpa refresh artefak.
- Banyak temuan lama sudah diperbaiki pada commit audit yang sama; dua temuan source masih terindikasi (`lg:sticky` ringkasan SP dan textarea penanganan tanpa autofocus), sedangkan temuan subjektif lain perlu capture browser baru.

## Verifikasi nyata

- Build frontend: lulus; warning chunk >500 kB.
- Unit: 1 test / 1 assertion lulus.
- Feature: 788 test / 7.780 assertion lulus.
- Database serial utama: 633 lulus / 3 gagal / 2.819 assertion.
- Audit keamanan paralel: 193 test / 716 assertion lulus.
- Audit domain terfokus: 105 test / 392 assertion lulus; laporan Feature: 3 test / 10 assertion lulus.
- Schema parity: `NOL SELISIH` migration vs `schema.sql`.
- Browser export: 17/17 lulus dengan login dan workbook readback.
- Composer/npm production audit: 0 advisory saat audit.
- PHP 8.2 platform check: gagal terhadap lock saat ini.

## Kontrol yang kuat

- Route internal terpusat pada `auth`, wajib ganti sandi, RBAC, dan limiter.
- Global scope/write guard Per-SP menutup banyak IDOR dan gagal tertutup bila penugasan kosong.
- Pending email change memakai token hash, expiry, transaction/lock, uniqueness, revocation sesi/remember, dan audit tanpa secret.
- Import membatasi baris/ukuran/ZIP ratio, menolak formula/makro/external link, dan memakai transaksi per baris/kelompok.
- Notifikasi hanya dapat ditandai baca lewat relasi pemilik; penerima memperhitungkan izin, status role/user, Per-SP, dan Per-Bidang.

## Kesenjangan operasional

Workflow yang ada adalah preview statis GitHub Pages, bukan deployment Laravel produksi. Queue worker, scheduler, backup DB+private files, restore drill, log monitoring, production smoke test, benchmark volume nyata, dan UAT/beta belum dibuktikan.

Artefak lengkap tersedia di `artifacts/system-audit/`. Catatan pada paragraf ini adalah kondisi saat baseline audit; source kemudian berubah melalui batch remediasi yang dirinci pada bagian teratas.
