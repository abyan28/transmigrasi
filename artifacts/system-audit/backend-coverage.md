# Coverage Backend Audit

HEAD: `29549bf0b0a2aed6a48fa587f77cec261dfec3bf`
Route snapshot: `artifacts/system-audit/routes-head.json` (161 route non-vendor).

## Coverage matrix

| Domain | Entry point | Middleware/permission | Scope & integrity | Side effect | Test/evidence | Status |
|---|---|---|---|---|---|---|
| Login/logout | `/login`, `/logout` | guest/auth; throttle manual login | session regenerate on login, invalidate on logout | audit login/logout | `AutentikasiTest` | Covered; inactive-session gap SYS-H01 |
| Wajib ganti sandi | `/ganti-kata-sandi*` | auth; middleware global internal | flag blocks internal routes | password+audit | `AutentikasiTest` | Covered; revoke gap SYS-H02 |
| Pemulihan sandi | `/lupa-kata-sandi`, `/atur-ulang-sandi` | guest | hashed 6-digit code, expiry/attempt/request bounds | queued mail, audit | `PemulihanSandiTest` | Covered; revoke/race gap SYS-H02 |
| Perubahan email | `/verifikasi-email/{token}` | public + limiter | token hash, expiry, lock, DB unique | revoke recovery/session/remember, audit | `PendingEmailChangeTest` | Strong |
| Pengguna/role | `/pengguna*`, `/pengaturan/role*` | auth + mapped RBAC | last active admin guard; role assignment | mail/notif/audit | dedicated Database tests | Covered; inactive-session gap |
| Per-SP/global scope | all operational models | auth + permission | global Eloquent scope + write guard | n/a | `CakupanDataTest`, route tests | Strong sampled coverage |
| Dokumen privat | `/dokumen/{modul}/{id}/{nama}` | auth; dynamic permission in controller | owner scope + pivot ownership + traversal rejection | stream private disk | `DokumenTest` and static trace | Strong access control; orphan gap |
| Public complaint | `/pengaduan-warga`, `/lacak-pengaduan*` | public throttles | server validation; public output reduced | upload, notifications, queued mail | `PengaduanPublikTest` | Covered; upload compensation gap |
| Internal complaint | `/pengaduan*` | auth + RBAC + scope | ordered state transition | history/upload/notif/mail | `PengaduanTest` | Covered |
| Import | `POST /impor/{entitas}` | auth + dynamic lihat/tambah (+ anggota poktan) | 1000 rows; per-row/group transactions; Per-SP checks | writes many domains | `ImporTest` | Strong parser checks; MIME boundary gap |
| Export/report | `/laporan/*`, client export | auth + per-report RBAC | scoped Eloquent data; client filter | XLSX download/print | browser export 17/17 | Covered; scale not load-tested |
| Dashboard/rekap | `/`, `/dashboard/sp/{sp}`, rekap routes | auth + RBAC | scoped queries and arithmetic tests | none | Feature + Database rekap tests | Covered |
| Notifications | `/notifikasi*` | auth | ownership on read; recipient permission/scope at creation | per-recipient rows | `NotifikasiTest` | Covered |
| Transactions | multi-table controllers/services | route guards + DB transaction | locks for inventory/plant/harvest/email | DB + files + mail | targeted DB tests/static trace | DB atomicity good; filesystem outside transaction |
| Migrations/schema | all migrations | CLI only | FK/index/unique/generated columns | schema | `sim:banding-skema` | Schema parity passes; fresh seed/test gap |
| Queue/email | account/recovery/complaint | caller permissions | encrypted credential/recovery mail | DB queue + failed_jobs | mail fake tests/static trace | Code covered; worker operational prerequisite |
| Configuration | session/filesystem/queue/logging | config | private local disk, DB sessions/queue | runtime infra | static trace/runtime | Proxy wildcard and deployment gaps |
| Deployment | `.github/workflows/deploy.yml` | manual GitHub action | static public-only build | Pages artifact | static trace | Preview only; PHP 8.2 lock incompatibility |

## Route permission audit

All named sensitive internal routes from `routes-head.json` carry `web`, `auth`, `pastikan.ganti.sandi`, an entry in `PetaIzinRute` where domain authorization is required, and read/write/big-file throttling. Dynamic endpoints (`dokumen`, `impor`) perform permission checks inside their controller because the module is a route parameter. Public exceptions are login/recovery/email-confirmation/public complaint tracking and submission.

## Not fully exercised

- Production reverse proxy/origin topology.
- Real queue worker retries and failed-job alerting.
- Backup and restore drill for DB plus `storage/app/private`.
- Large production-scale report memory/query benchmark.
- Concurrent password-reset race test.
- All browser scripts after authenticated harness repair.
