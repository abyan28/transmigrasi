# Traceability Klaim

| ID | Klaim | Kondisi falsifikasi | Bukti | Status | Finding |
|---|---|---|---|---|---|
| CL-01 | Route internal ber-auth + izin | route sensitif tanpa guard | route snapshot + security tests | TERBUKTI | — |
| CL-02 | Scope Per-SP mencegah IDOR | data lintas SP terlihat | scope/write tests | SEBAGIAN_TERBUKTI | SYS-M01 dokumen SP |
| CL-03 | Penonaktifan menghentikan akses | sesi lama tetap hidup | lifecycle trace | PALSU | SYS-H01 |
| CL-04 | Perubahan kredensial/status mengamankan akun | sesi/remember lama tetap hidup | mutation matrix | PALSU | SYS-H02 |
| CL-05 | Pending email aman | replay/session lama lolos | locks+revocation tests | TERBUKTI | — |
| CL-06 | Recovery maksimal lima percobaan | race melewati limit | serial-only implementation/tests | SEBAGIAN_TERBUKTI | SYS-M08 |
| CL-07 | Import aman dan parity manual | mismatch type/histogram hilang | parser trace | SEBAGIAN_TERBUKTI | SYS-M02, SYS-H05 |
| CL-08 | Browser tests membuktikan halaman internal | hijau pada login | runtime 18/18 false positive | PALSU | SYS-H03, SYS-M06 |
| CL-09 | Export Excel/browser bekerja | file/filter/print salah | login + workbook readback | TERBUKTI_RUNTIME | — |
| CL-10 | Audit UI 53 titik valid | route/PNG/metrik salah | route/hash/dimension/contrast | PALSU | SYS-M07 |
| CL-11 | Migration cocok schema SQL | struktur berbeda | schema parity | TERBUKTI_RUNTIME | — |
| CL-12 | Fresh install/test DB sehat | seed dependency/suite gagal | 633 pass, 3 fail | PALSU | SYS-M04 |
| CL-13 | Build frontend berhasil | Vite gagal | build pass | TERBUKTI_RUNTIME | — |
| CL-14 | Dependencies tanpa advisory | audit menemukan advisory | Composer/npm audit | TERBUKTI saat audit | — |
| CL-15 | PHP 8.2 didukung lock | clean install/platform gagal | platform check | PALSU | SYS-M05 |
| CL-16 | Soft-delete menjaga riwayat produksi | anak hilang dari query/report | parent/child scope trace | PALSU | SYS-H04 |
| CL-17 | Rincian kondisi tersimpan | request dibuang backend | form/controller/import trace | PALSU | SYS-H05 |
| CL-18 | Riwayat status linear | dua transisi dari state sama | transaction trace | TIDAK_TERBUKTI | SYS-H06 |
| CL-19 | Notifikasi aktif terdeduplikasi | race membuat duplicate | exists-create trace | TIDAK_TERBUKTI | SYS-M09 |
| CL-20 | Nomor urut benar-benar unik/urut | race berbagi komponen urut | max+1 trace | TIDAK_TERBUKTI | SYS-M10 |
| CL-21 | Surel queued terkirim produksi | tidak ada worker | config/deploy trace | TIDAK_TERBUKTI | SYS-M12 |
| CL-22 | User manager tidak dapat mengangkat diri ke Admin | role terkunci dapat dipilih lewat request | route/controller/view trace | PALSU | SYS-H09 |
| CL-23 | Produktivitas laporan seragam ton/ha | nilai satuan sumber dilabel ton/ha | runtime kilogram probe | PALSU | SYS-H10 |
| CL-24 | Export Excel menyimpan kuantitas sebagai number | integer polos menjadi string | JS probe | SEBAGIAN_TERBUKTI | SYS-M20 |
| CL-25 | Monografi layak pada data demo | ribuan query dan >8 detik | runtime DB::listen | PALSU | SYS-M21 |
| CL-26 | Aplikasi siap dideploy stateful dan dipulihkan | hanya preview statis/tanpa backup | workflow/repo trace | PALSU | SYS-H11, SYS-M24 |

## Ringkasan

- TERBUKTI / TERBUKTI_RUNTIME: 7
- SEBAGIAN_TERBUKTI: 4
- TIDAK_TERBUKTI: 4
- PALSU: 11
- STALE: bukti UI sesudah dua commit perubahan
- TERBLOKIR: deployment production nyata, backup/restore, benchmark, dan UAT berada di luar lingkungan audit
