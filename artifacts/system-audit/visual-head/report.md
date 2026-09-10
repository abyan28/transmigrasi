# Audit Visual Segar terhadap HEAD

- SHA aplikasi: `b545f50a02ff51e8ca3eb90c4bb9d18619be1a7b`
- Fixture: `DemoSeeder`, akun Admin khusus audit lokal
- Viewport: desktop `1440×900`, mobile `390×844`
- Cakupan: 8 permukaan berisiko × 2 viewport = 16 screenshot
- Manifest: `artifacts/system-audit/visual-head/manifest.json`
- Validator: `node tests/Browser/validasi-audit-visual.mjs` — PASS

## Integritas bukti

Setiap capture mengunci route dan URL akhir, heading unik, role, viewport, dimensi PNG, status overflow horizontal, path, dan SHA-256. Validator menolak file hilang/berubah, dimensi tidak cocok, URL/heading kosong, overflow horizontal, dan hash duplikat lintas capture.

Hasil validasi: 16/16 file ada, dimensi cocok, seluruh hash unik, URL akhir cocok route target, heading terisi, dan tidak ada overflow horizontal.

## Hasil inspeksi

Tidak ditemukan blocker visual berupa 404/login palsu, elemen tumpang tindih, komponen rusak, atau overflow horizontal pada permukaan yang dicapture. Inspeksi manual berbantuan visual menemukan hanya catatan kosmetik rendah: subjudul kawasan desktop dipotong dengan ellipsis, beberapa teks sekunder gelap perlu pengukuran kontras khusus, dan ikon mode kartu mobile terlihat kecil. Catatan ini tidak dinaikkan menjadi temuan audit tanpa pengukuran aksesibilitas yang sah.

## Batas cakupan

Audit ini membuktikan permukaan risiko berikut untuk role Admin, dark theme, dan dua viewport: dashboard, transmigran, pengaduan, penanaman, panen, master wilayah, master daftar pilihan, dan Monografi. Ini bukan klaim bahwa setiap kombinasi role/theme/route telah diperiksa. Pengukuran warna efektif dan rasio kontras matematis belum dilakukan, sehingga SYS-M07 berstatus `IMPROVED`, bukan `FIXED`.
