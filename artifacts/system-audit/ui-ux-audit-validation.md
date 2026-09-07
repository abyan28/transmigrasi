# Validasi Audit UI/UX Lama terhadap HEAD

- HEAD: `29549bf0b0a2aed6a48fa587f77cec261dfec3bf`
- Artefak asal: commit `a3ba350848bab42b52367899b4b3e1a25761d15c`
- Kesimpulan: **tidak dapat dipercaya sebagai bukti keadaan HEAD**. Sebagian observasi visual mungkin pernah berguna, tetapi cakupan, route, screenshot, dan metriknya tidak cukup sahih untuk mendukung klaim “53 titik”, skor 86,4, atau “siap produksi”.

## Bukti integritas

1. `report.md`, `coverage.md`, dan `measurements.json` tidak berubah sejak `a3ba350`, tetapi sesudah commit itu ada dua commit UI; `git diff a3ba350..HEAD -- resources/views resources/js` menunjukkan 21 view berubah (836 penambahan, 67 penghapusan). Bukti visual lama otomatis stale untuk area tersebut.
2. `measurements.json` memuat 172 rekaman dan 43 jalur, namun setidaknya enam jalur audit tidak ada pada route HEAD: `/dashboard`, `/laporan`, `/impor`, `/role`, `/daftar-pilihan`, dan route sengaja-404 `/halaman-pasti-tidak-ada-404`. Jalur publik lama `/pengaduan/baru` dan `/pengaduan/status` juga kini 404; route HEAD adalah `/pengaduan-warga` dan `/lacak-pengaduan`.
3. Pemeriksaan hash 153 PNG menemukan **9 grup duplikat yang mencakup 31 file**. Contoh: `DESKTOP-LIGHT-C1-dashboard-monitoring.png` identik byte-for-byte dengan `DESKTOP-LIGHT-I4-matriks-peran-izin.png`; keduanya secara visual adalah halaman “Galat 404”.
4. Sampel visual membuktikan filename tidak mewakili konten: screenshot dashboard, matriks role, status pengaduan publik, dan form pengaduan publik yang diperiksa semuanya menampilkan 404.
5. Sebanyak **37 nilai `rasio` berada di luar rentang matematis WCAG 1..21**, sampai miliaran. Warna berformat OKLab/alpha diperlakukan sebagai RGB efektif sehingga klaim kontras otomatis tidak sah.
6. Audit dan implementasi berada dalam commit yang sama (`a3ba350`), bertentangan dengan label “AUDIT ONLY” dan menghilangkan baseline sebelum/perubahan yang independen.

## Status temuan lama

| Kelompok | Status | Alasan |
|---|---|---|
| Route dan screenshot halaman publik/internal | `BUKTI_INVALID` | Banyak URL salah/404; hash lintas nama sama; konten screenshot tidak cocok label. |
| Nilai kontras dari `measurements.json` | `BUKTI_INVALID` | 37 rasio mustahil (>21); compositing OKLab/alpha tidak benar. |
| Temuan sidebar `text-gray-400` | `SUDAH_DIPERBAIKI` secara statis | HEAD memakai `text-gray-500 dark:text-gray-400` di `resources/views/layouts/sidebar.blade.php:134`. |
| Temuan visual sebelum `22d681e`/`29549bf` | `STALE` | Compact metric strip dan semantik/cakupan metrik mengubah 21 view sesudah screenshot dibuat. |
| Klaim 0 overflow / semua halaman responsif | `TIDAK_TERBUKTI` | Browser suite internal umumnya tidak autentikasi; contoh `uji-lebar-halaman.mjs` lulus 18/18 sambil sebenarnya berada pada `/login`. |
| Skor 86,4 dan “siap produksi” | `PALSU` sebagai kesimpulan terukur | Dibangun dari route/screenshot/metrik yang tidak sahih dan tidak mewakili HEAD. |

## Runtime terarah

- GET lokal tanpa login: `/pengaduan/baru`, `/pengaduan/status`, `/dashboard`, `/laporan`, `/impor`, `/role`, `/daftar-pilihan`, dan `/halaman-pasti-tidak-ada-404` membalas 404; `/` dan `/uji-403` mengalihkan ke login.
- `node tests/Browser/uji-master-daftar-pilihan.mjs`: **3 lulus, 7 gagal**, tujuan tetap `/login`.
- `node tests/Browser/uji-lebar-halaman.mjs`: **18 lulus, 0 gagal**, tetapi semua route internal mengalihkan ke login; hasil ini membuktikan false positive, bukan lebar halaman target.
- `node tests/Browser/uji-export-laporan.mjs`: **17 lulus, 0 gagal** karena satu-satunya script yang benar-benar melakukan login.

## Rekonsiliasi audit paralel

Audit paralel read-only mengonfirmasi putusan dan menambah statistik deterministik:

- 10 dari 43 ID halaman (40 rekaman) memakai route salah/404.
- JSON merujuk 172 screenshot unik, tetapi 19 file tidak ada; coverage menyebut 5 file yang tidak ada.
- 18 screenshot memiliki dimensi desktop/mobile yang terbalik terhadap label.
- 826 rasio diperiksa; 37 mustahil (>21), sedangkan 387 berada di bawah 4,5.
- Dua temuan source lama masih terindikasi: ringkasan SP tetap `lg:sticky` dan textarea penanganan belum autofocus. Keduanya memerlukan audit browser baru sebelum diprioritaskan.

## Acceptance criteria audit ulang

Audit visual baru harus dibangkitkan dari HEAD setelah build, login sesuai role, lalu pada setiap titik wajib mengassert URL final, status/route target, heading unik, dan selector konten utama sebelum mengukur atau mengambil screenshot. Capture console exception, gunakan profil browser sementara, hitung warna efektif setelah alpha compositing, dan gagal bila screenshot identik muncul untuk route berbeda tanpa alasan eksplisit.
