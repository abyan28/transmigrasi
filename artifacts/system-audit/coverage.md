# Coverage Final

| Area | Static trace | Runtime | Status / gap |
|---|---:|---:|---|
| Route/auth/RBAC | 161 route + permission map | 193 security tests | Kuat; GET sweep belum menyeluruh |
| Scope Per-SP/Per-Bidang | global/local scopes + write guards | Dedicated MySQL tests | Kuat; dokumen SP local-scope gap |
| Account lifecycle | seluruh mutation path | hash/flag/audit tests | Session revocation gap |
| Recovery | code hash/expiry/serial attempts | Database tests | Race/HTTP throttle gap |
| Pending email | token/lock/revocation | Database tests | Kuat |
| Private documents | upload→registry→download | Dedicated tests | Kuat kecuali owner SP |
| CRUD domain | all controllers sampled | 633/636 DB suite + 105 focused | Soft-delete/rincian/transisi gaps |
| Filesystem lifecycle | upload/replace path | success tests | Rollback cleanup gap |
| Import CSV/XLSX | parser+mapping+transactions | hostile parser/domain tests | Kuat; MIME boundary + rincian parity gap |
| Notification | recipient/read paths | ownership tests | Concurrency dedupe gap |
| Complaint numbering | generator/schema | format tests | Parallel sequence gap |
| Dashboard/report | query/helper trace | Feature+Database | Semantik diuji; scale belum |
| Export browser | auth/download/readback | 17/17 | Kuat |
| General browser/Alpine | all 18 scripts | targeted runs | Harness invalid untuk 17 script |
| Migration/schema | migrations/schema SQL | parity pass | Fresh master bootstrap gap |
| Queue/email | callers/config | fake queued tests | Worker production belum |
| PHP/dependencies | manifest+lock+workflow | audit/platform checks | PHP 8.2 mismatch |
| UI evidence | routes/JSON/PNG/history | hash/dimension/vision checks | Legacy audit invalid/stale |
| Deployment/backup/UAT | repo/workflow | tidak ada production drill | Belum tercakup |

## Temuan vs regression check minimum

| Finding | Check yang harus memerah sebelum fix |
|---|---|
| SYS-H01/H02 | dua sesi + remember cookie tetap akses setelah nonaktif/reset/role change |
| SYS-H03/M06 | target dialihkan ke login atau Alpine exception tetapi script hijau |
| SYS-H04 | hapus poktan/saprotan dengan rantai aktif menghilangkan anak dari query/report |
| SYS-H05 | POST histogram valid tidak tersimpan; sum mismatch diterima |
| SYS-H06 | dua koneksi menulis transisi dari status awal sama |
| SYS-H07 | direct client spoof `X-Forwarded-For` mengubah IP/bucket |
| SYS-M01 | Operator SP A mengunduh dokumen SP B |
| SYS-M02 | konten teks bernama `.xlsx` mencapai parser XLSX |
| SYS-M03 | injected attach failure meninggalkan file |
| SYS-M04 | `SpTest` tiga nilai master gagal |
| SYS-M05 | clean install/platform check PHP target gagal |
| SYS-M08 | request paralel melewati lima percobaan efektif |
| SYS-M09 | dua sender paralel membuat unread duplicate |
| SYS-M10 | dua generator paralel mendapat urut sama |
| SYS-M11 | volume target melewati budget query/time/memory |
| SYS-M12 | queued job tidak pernah diproses pada deployment smoke |
| SYS-M13 | cookie production tidak memiliki Secure |
| SYS-H09 | delegated user manager dapat memberi role Admin terkunci |
| SYS-H10 | produktivitas kg/ha ditampilkan sebagai ton/ha tanpa konversi |
| SYS-H11 | restore fresh environment tidak dapat dilakukan karena backup/runbook tidak ada |
| SYS-M19 | flood recovery akun tidak dikenal memicu bcrypt tanpa 429 |
| SYS-M20 | kuantitas polos `3` diekspor sebagai string, sementara identifier tetap perlu string |
| SYS-M21 | route Monografi enam SP melampaui baseline 3.137 query/8,74 detik |
| SYS-M22 | config-cached seeder mengabaikan `SIM_ADMIN_*` |
| SYS-M23/M24 | operator mengira preview manual sebagai deployment aplikasi stateful |
