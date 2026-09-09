# Coverage Terkini

Dokumen ini adalah ringkasan setelah batch remediasi. Baseline audit historis tetap tersedia pada bagian “Baseline audit asli” di `final-report.md`.

| Area | Bukti terkini | Status / gap |
|---|---|---|
| Route/auth/RBAC | seluruh named authenticated route dipetakan/dikecualikan; workflow menjalankan Pint, Unit, harness, audit dependency, dan action dipin SHA | IMPROVED; gate MySQL route-map masih lokal |
| Scope Per-SP/Per-Bidang dan dokumen | scope/write guards + focused MySQL tests | FIXED |
| Account lifecycle | sesi database/remember dicabut; akun nonaktif ditolak termasuk rute ganti sandi; create/update Admin dijaga; Admin terakhir di-lock | FIXED |
| Recovery | throttle IP+kredensial, transaksi, lock, konsumsi kode atomik | FIXED |
| Pending email | token, queue, revocation, dan rollback atomik | FIXED |
| Private documents/filesystem | owner scope dan kompensasi rollback file | FIXED |
| CRUD domain | dependency delete guards dan histogram kondisi | FIXED |
| Import CSV/XLSX | signature/content boundary, hostile parser, transaksi per baris/kelompok | FIXED |
| Notification | dedupe diserialisasi pada row pengguna; snapshot penilaian di-lock | FIXED untuk temuan audit |
| Complaint status/number | row lock transisi dan rentang nomor | FIXED |
| Dashboard/report | semantik/unit diperbaiki; graf panen dashboard dimuat sekali; query budget terkunci | SYS-M11 FIXED; Monografi SYS-M21 improved |
| Export browser | login, download, readback | Kuat |
| General browser/Alpine | helper 18/18 menangkap redirect/exception/console | FIXED; full runner masih menemukan race form Alsintan |
| Migration/schema | parity dan master bootstrap | FIXED |
| Queue/email | SMTP dapat dikonfigurasi dan queue database tersedia | Worker produksi/supervisor belum dibuktikan (SYS-M12) |
| PHP/dependencies | target CI PHP 8.3, build dan Pint lulus | FIXED |
| UI evidence | audit lama diverifikasi stale/tidak valid | Audit ulang HEAD masih terbuka (SYS-M07) |
| Deployment/backup/UAT | preview statis dibedakan dari aplikasi stateful | SYS-H11 dan SYS-M24 terbuka |

## Temuan yang masih terbuka

- SYS-H11 — strategi backup/restore database dan storage privat.
- SYS-M07 — audit UI/UX ulang terhadap HEAD.
- SYS-M12 — process supervisor queue worker production.
- SYS-M21 — residual N+1/full-load Monografi.
- SYS-M24 — deployment Laravel stateful.
