# Traceability Klaim Terkini

| ID | Klaim | Bukti terkini | Status | Finding tersisa |
|---|---|---|---|---|
| CL-01 | Route internal ber-auth + izin | route-map seluruh named authenticated route; CI MariaDB wajib dan SHA-pinned | TERBUKTI | — |
| CL-02 | Scope Per-SP mencegah IDOR | scope/write/document tests | TERBUKTI | — |
| CL-03 | Penonaktifan menghentikan akses | revocation helper + middleware internal dan ganti sandi | TERBUKTI | — |
| CL-04 | Perubahan kredensial/status/role mencabut sesi | mutation-path tests | TERBUKTI | — |
| CL-05 | Pending email aman | lock, rollback queue, revocation tests | TERBUKTI | — |
| CL-06 | Recovery dibatasi dan atomik | throttle + transaction/row-lock tests | TERBUKTI | — |
| CL-07 | Import aman | signature/content detection + hostile parser tests | TERBUKTI | — |
| CL-08 | Browser tests mencapai halaman internal dan menangkap JS error | shared login/error harness 18/18; race distribusi focused 21/21 | TERBUKTI SEBAGIAN | canonical full runner masih memuat kontrak suksesi/wakil-poktan lama |
| CL-09 | Export Excel/browser bekerja | login + workbook readback | TERBUKTI_RUNTIME | — |
| CL-10 | Audit visual terkini membuktikan permukaan risiko HEAD | 16 screenshot dark-theme + manifest URL/heading/role/viewport/dimensi/SHA-256 tervalidasi | TERBUKTI SEBAGIAN | SYS-M07: kontras efektif dan theme/role tambahan |
| CL-11 | Migration cocok schema SQL | schema parity | TERBUKTI_RUNTIME | — |
| CL-12 | Fresh master bootstrap sehat | focused SpTest/master tests | TERBUKTI | — |
| CL-13 | Build frontend berhasil | `npm run build` | TERBUKTI_RUNTIME | warning chunk saja |
| CL-14 | Dependency tanpa advisory saat audit | Composer/npm audit | TERBUKTI_SAAT_AUDIT | — |
| CL-15 | Runtime memenuhi lock | workflow PHP 8.3 | TERBUKTI_STATIC | deployment smoke belum |
| CL-16 | Soft-delete tidak menyembunyikan rantai aktif | dependency guards + tests | TERBUKTI | — |
| CL-17 | Rincian kondisi tersimpan konsisten | validation/persistence tests | TERBUKTI | — |
| CL-18 | Riwayat status linear | transaction + row lock + stale-state test | TERBUKTI | — |
| CL-19 | Notifikasi aktif terdeduplikasi | transaction + lock penerima yang selalu ada | TERBUKTI_IMPLEMENTASI | — |
| CL-20 | Nomor pengaduan terserialisasi | locked yearly range + test query lock | TERBUKTI_IMPLEMENTASI | — |
| CL-21 | Surel queued diproses produksi | konfigurasi queue tersedia | TIDAK_TERBUKTI | SYS-M12 |
| CL-22 | User manager tidak dapat memberi role Admin | create/update guards + tests | TERBUKTI | — |
| CL-23 | Produktivitas laporan seragam ton/ha | conversion regression | TERBUKTI | — |
| CL-24 | Export menyimpan kuantitas sebagai number | metadata cell + build/browser checks | TERBUKTI | — |
| CL-25 | Monografi layak pada data demo | fixture enam SP dijaga ≤220 query; dataset/filter/master dipakai ulang | TERBUKTI | — |
| CL-26 | Aplikasi dapat dideploy dan dipulihkan | preview statis saja; belum ada restore drill | TIDAK_TERBUKTI | SYS-H11, SYS-M24 |
