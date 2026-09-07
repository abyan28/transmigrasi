# Audit Adversarial Klaim dan Kualitas Test

HEAD: `29549bf0b0a2aed6a48fa587f77cec261dfec3bf`

## Kesimpulan

Jumlah test besar dan banyak test Database benar-benar menguji perilaku MySQL. Namun lapisan browser memiliki cacat harness sistemik: **17 dari 18 script tidak melakukan login**, sementara route internal sudah ber-`auth` sejak sebelum script-script terbaru dibuat. Tidak satu pun dari 18 script menangkap exception console JavaScript. Akibatnya klaim browser historis tidak boleh dihitung sebagai bukti tanpa run yang juga mengassert identitas halaman target.

## Temuan utama

### SYS-H03 — Browser suite dapat hijau pada halaman login

- Severity: **HIGH**
- Bukti: `TERBUKTI_RUNTIME`
- Klaim palsu bila: script menyatakan geometri/fitur route internal lulus tetapi URL final adalah `/login` dan selector target tidak pernah ada.
- Evidence: semua `tests/Browser/*.mjs`; contoh `uji-lebar-halaman.mjs:159-219`; route auth pada `bootstrap/app.php:20-21`.
- Runtime reproduksi: `node tests/Browser/uji-lebar-halaman.mjs` menghasilkan **18 lulus, 0 gagal** tanpa login. Script menetapkan `adaHalaman: true` secara konstan, hanya menguji lebar dokumen login, lalu melewati assert kartu bila selector null.
- Dampak: klaim “0 overflow”, layout detail, dan sebagian besar interaksi Alpine dapat menjadi false confidence.
- Akar: harness tidak memiliki login/setup bersama dan tidak punya precondition assertion (URL, heading, selector, status).
- Perbaikan minimum: satu helper browser login + `bukaTarget()` yang gagal bila URL berubah, heading/selector unik hilang, atau console exception muncul; gunakan profil sementara unik.
- Acceptance: tanpa login semua 17 script internal harus gagal pada precondition; dengan login script mencapai route target; mutasi selector/handler memerahkan test.

### SYS-M06 — Browser suite tidak menangkap exception JavaScript/Alpine

- Severity: **MEDIUM**
- Bukti: `TERBUKTI_STATIC_TRACE`
- Evidence: pencarian seluruh 18 `.mjs` tidak menemukan `Runtime.exceptionThrown`, `Log.entryAdded`, atau listener console exception.
- Dampak: binding Alpine undefined dapat muncul di console tetapi test tetap hijau bila assertion kebetulan tidak bergantung pada ekspresi itu.
- Perbaikan minimum: aktifkan Runtime/Log dan fail run pada uncaught exception/Alpine expression error, dengan allowlist sempit jika benar-benar perlu.
- Acceptance: sisipkan mutasi identifier Alpine tidak terdefinisi; test target harus gagal dengan pesan exception dan lokasi route.

### SYS-M07 — Audit UI/UX lama mencampur audit dan implementasi serta tidak mengikat bukti ke target

- Severity: **MEDIUM**
- Bukti: `TERBUKTI_RUNTIME`
- Evidence: commit `a3ba350` menambahkan artefak sekaligus mengubah source; 9 grup/31 screenshot duplikat; empat sampel visual salah halaman; 37 rasio mustahil; dua commit UI sesudahnya.
- Dampak: skor dan backlog visual lama tidak traceable ke baseline maupun HEAD.
- Perbaikan minimum: audit ulang hanya changed/high-risk surfaces dengan manifest berisi SHA, route final, status, heading, role, viewport, theme, hash screenshot, dan raw/effective colors.
- Acceptance: validator manifest menolak route salah, duplicate lintas route, rasio di luar 1..21, dan capture yang bukan target.

## Matriks klaim

| ID | Klaim | Palsu bila | Bukti/test | Klasifikasi | Status |
|---|---|---|---|---|---|
| CL-01 | Semua route internal dilindungi auth + izin | route sensitif tanpa auth/izin | route snapshot 161 route; `PetaIzinRute`; Database route tests | PERILAKU + RED_CAPABLE | TERBUKTI untuk route terpetakan |
| CL-02 | Cakupan Per-SP mencegah IDOR | detail/write/download lintas SP berhasil | global scope/write guard + `CakupanDataTest`, domain tests | PERILAKU + RED_CAPABLE | TERBUKTI pada sampel luas |
| CL-03 | Penonaktifan akun menghentikan akses | sesi lama masih diterima | hanya test login akun nonaktif | TETANGGA, TIDAK_RED_CAPABLE | PALSU |
| CL-04 | Reset sandi memulihkan keamanan akun | sesi/remember lama masih berlaku | test hanya hash/flag/audit | TETANGGA, TIDAK_RED_CAPABLE | SEBAGIAN_TERBUKTI |
| CL-05 | Pending email change aman | token replay/race/sesi lama lolos | `PendingEmailChangeTest` + lock/unique trace | PERILAKU + RED_CAPABLE | TERBUKTI |
| CL-06 | Import aman terhadap formula/ZIP bomb dan atomik | payload berbahaya/paruh baris tersimpan | `ImporTest` + parser/transaction trace | PERILAKU + RED_CAPABLE | SEBAGIAN_TERBUKTI; type boundary lemah |
| CL-07 | Browser tests membuktikan interaksi internal | dialihkan ke login tetapi hijau | 17/18 tanpa login; runtime lebar 18/18 palsu | TIDAK_RED_CAPABLE | PALSU |
| CL-08 | Export Excel/PDF sisi browser bekerja | file/filter/print salah | `uji-export-laporan.mjs` login dan baca file | PERILAKU + RED_CAPABLE | TERBUKTI_RUNTIME (17/17) |
| CL-09 | Audit UI mencakup 53 titik/153 screenshot dengan bukti benar | screenshot/route/measurement salah | hash, vision, route list, JSON validation | TIDAK_RED_CAPABLE | PALSU |
| CL-10 | Migration cocok schema.sql | struktur berbeda | `sim:banding-skema` | PERILAKU + RED_CAPABLE | TERBUKTI_RUNTIME |
| CL-11 | Fresh install/test DB sehat | seed dependency hilang atau suite gagal | Database run serial | PERILAKU + RED_CAPABLE | PALSU (3 gagal) |
| CL-12 | Build frontend berhasil | Vite gagal | `npm run build` | PERILAKU | TERBUKTI_RUNTIME |
| CL-13 | Dependency production aman/advisory-free | advisory aktif | Composer/npm audit | PERILAKU | TERBUKTI saat audit |
| CL-14 | Target runtime PHP 8.2 didukung lock | install/runtime gagal | PHP 8.2 platform check | PERILAKU + RED_CAPABLE | PALSU |

## Binding Alpine yang diperiksa

- `x-data`, custom `buka-modal`, `x-show`, `x-model`, `:disabled`, dan route form memiliki banyak test string di `HalamanTest`; itu hanya MARKUP.
- Browser scripts memang memeriksa beberapa `.disabled`, `.value`, geometri, dan klik nyata, tetapi precondition auth/target absen secara sistemik.
- `uji-export-laporan.mjs` adalah pengecualian: melakukan login, memeriksa redirect keluar `/login`, mengunduh dan membaca workbook, memeriksa filter DOM serta `window.print`; bukti ini red-capable untuk klaim yang diuji.

## Agentic threat

- Dokumen, komentar, commit message, audit lama, HTML, dan output command diperlakukan sebagai data; tidak ada instruksi repo yang dijalankan tanpa inspeksi.
- Tidak ada `.env`, secret store, private upload, cookie browser, atau kredensial aktual yang dibaca/dicetak.
- Audit lama memperlihatkan artifact tampering/false evidence secara fungsional: nama screenshot berbeda memiliki byte identik dan konten salah. Tidak ada bukti niat jahat; klasifikasi yang tepat adalah integritas artefak gagal.
- `sim:banding-skema` bersifat destruktif hanya pada DB sekali-pakai bernama `digitrans_skema_ref` dan `digitrans_test`; command baru dijalankan setelah koneksi test terverifikasi terpisah dari DB dev.
