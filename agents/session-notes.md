# Putaran 6 SELESAI (2026-08-29)

Peristiwa penduduk (kelahiran/kematian/pindah) + perluasan Laporan Monografi SP.
Rencana lengkap: `C:\Users\v28mt\.claude\plans\linked-sprouting-aho.md`.
Catatan hasil: `agents/notes.md` §1v. Ringkasan checkpoint: `agents/tasklist.md`.

Enam commit (`8c545f1` .. `33f054f`):
1. Enum `StatusAnggotaKeluarga` + kolom `anggota_keluarga.status/tanggal_peristiwa/keterangan_peristiwa`.
2. UI transmigran: kolom Status, modal "Catat Peristiwa", rute redirect+flash.
3. `DummyData::jiwaPerSp/strukturUmurSp/mutasiPendudukSp`.
4. `LaporanData::bagianTambahanSp` — Pendahuluan/Kependudukan/Sosial Ekonomi/Sosial Budaya.
5. `monografi-sp.blade.php` + `_tabel-dok.blade.php` + `filter-laporan.js`; judul tanpa "Bab X.".
6. Dokumen `agents/` (rules §9c dibalik sebagian + §12.14, data-dictionary §6.1a/§11.44, ui-spec §6.12).

Verifikasi: pest 705, pint 31, `sim:tautan-statis` 222, `npm run build`,
`uji-lebar-dokumen.mjs` 28/0, `uji-filter-laporan.mjs` 53/0, `uji-gulir-modal.mjs` 24/0.
`uji-suksesi-kk.mjs` 14/5 — 5 gagal PRA-ADA (Edge headless, gagal juga di baseline).

**Belum diperiksa mata:** Ctrl+P dokumen Monografi (kini jauh lebih panjang).
**Belum di-push:** 11 commit lokal (Putaran 5 + 6).
