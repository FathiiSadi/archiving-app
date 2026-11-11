# أرشيف دال — DAL Archiving System

Unified archive and dashboards for the initiative's four teams — **Activities, Quran Memorization, Tajweed, Internal Affairs** — built from `DAL_Archiving_System_BRD.docx`.

| Layer | Choice |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Data entry & dashboards | Filament 5 (Arabic, RTL) at `/panel` |
| Public portal | Vue 3 via Inertia at `/` |
| Database | PostgreSQL on Neon |
| Files | **No uploads.** Invoices, photos and documents stay in Google Drive; the system stores the link and previews it inline. |

## Setup

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
# Neon (project dal_archive = small-dew-83811293, Frankfurt, branch production, database neondb).
# `npx neon@latest env pull` writes DATABASE_URL (pooled) and DATABASE_URL_UNPOOLED (direct), which the app
# reads automatically; DB_URL / DB_DIRECT_URL override them when set:
#   DB_URL         = pooled URL (host contains -pooler)  → app traffic
#   DB_DIRECT_URL  = same URL without -pooler            → migrations
#   DB_EMULATE_PREPARES=true  (required with the pooler, see below)
php artisan migrate --database=pgsql_direct
php artisan db:seed            # creates the first admin from ADMIN_EMAIL / ADMIN_PASSWORD only
```

**Why `DB_EMULATE_PREPARES`:** Neon's pooled endpoint runs PgBouncer in transaction mode. PHP's pgsql driver frees server-side prepared statements with an SQL `DEALLOCATE`, which the pooler rejects; that aborts any transaction with more than one statement (every Filament save). Client-side prepares avoid it. The direct connection (`pgsql_direct`) keeps normal prepares.

Tests always run on in-memory SQLite (`force="true"` in `phpunit.xml`), so `php artisan test` can never touch Neon.

Local preview with sample data (never in production — the seeder refuses to run there):

```bash
php artisan db:seed --class=DemoSeeder   # accounts: admin|management|activities|quran|tajweed|internal @dal.test / password
composer run dev
```

> ⚠️ Phone numbers are encrypted with `APP_KEY`. Back it up and never rotate it without re-encrypting, or stored phone numbers become unreadable.

## Roles (BR-16)

| Role | Team data | Dashboards | Settings | Users |
|---|---|---|---|---|
| Team leader (×4) | **Create/edit own team only** | Own team | View | — |
| Management | View all (read-only) | All + management overview | Manage | — |
| Admin | View all (read-only) | All + management overview | Manage | Manage |

All rules live in one place: `AppServiceProvider::registerAuthorization()`. Every team model implements `TeamOwned`; settings models implement `SettingsRecord`. Phone numbers are shown in full only to the owning team's leader and admins; everyone else sees `079•••••67`.

## Requirements → implementation

| BR | Where |
|---|---|
| BR-01 activities | `Activity` · الأنشطة |
| BR-02 purchases (supplier, qty, JOD cost, budget compliance, rating 1–5, invoice link) | `ActivityPurchase` · المشتريات والفواتير + tab inside each activity |
| BR-03 guests & invitation status (6 states) | `ActivityGuest` · الضيوف والمتحدثون |
| BR-04 Activities dashboard | `ActivitiesDashboard` |
| BR-05 teachers & circles (rating /10) | `QuranTeacher`, `QuranCircle` |
| BR-06 recitation sessions, pages = new + revision | `RecitationSession` (computed on save) |
| BR-07 circle rating on 5 criteria | `CircleEvaluation` |
| BR-08 Quran dashboard (pages, attendance, quality + strengths/weaknesses, stability, apology & compensation) | `QuranDashboard`, `QuranKpis` |
| BR-09 Tajweed staff, sections, levels, lessons, attendance, interaction & correction ratings | `TajweedStaff`, `TajweedLevel`, `TajweedSection`, `TajweedLesson` |
| BR-10 recitation scores, final exam, eligibility | `TajweedEnrollment` · النتائج والتأهيل |
| BR-11 Tajweed dashboard vs semester target (min 70%) | `TajweedDashboard`, `TajweedKpis` |
| BR-12 internal activities from approved list, surplus/deficit computed | `InternalActivity` (computed on save) |
| BR-13 vendors & venues (rating /10, invoice link, suitability) | `Vendor` |
| BR-14 Internal Affairs dashboard incl. whitelist / blacklist | `InternalAffairsDashboard` |
| BR-15 every dashboard filtered by year and semester | `BaseDashboard` filter + `SemesterScope` |
| BR-16 leader-only entry, protected phones & finance | see Roles |
| BR-17 Arabic, mobile + desktop, backups | RTL Arabic UI; daily `dal:backup` + tested `dal:restore` (see below) |
| Settings tab, approved activities per semester | الإعدادات: الفصول والميزانيات · الأنشطة المعتمدة · أنواع الأنشطة |
| 90% archive-completeness objective | archive status on every main record; tracked on every dashboard |

## Indicator definitions

All indicators are computed in `app/Support/Kpi/*` — never typed in.

- **Attendance rate (activities)** = Σ actual ÷ Σ expected, completed activities only.
- **Expenses vs cap** = Σ purchases ÷ the semester's activities budget cap.
- **Attendance commitment (Quran)** = students present ÷ students expected, sessions that took place.
- **Teacher stability** = (scheduled − apologised + make-up sessions) ÷ scheduled, capped at 100%. Apology rate = apologised ÷ scheduled; compensation rate = make-ups ÷ apologised.
- **Circle quality** = average of the five criteria (1–5); strengths/weaknesses = highest/lowest criteria.
- **Tajweed level completion** = lessons delivered ÷ lessons planned per section, vs the semester target.
- **Advancement rate** = students marked eligible ÷ students with a decision.
- **Net surplus/deficit** = revenue − actual cost. **Avg cost per person** = Σ actual cost ÷ Σ participants.
- **Archive completeness** = records marked "مؤرشف بالكامل" ÷ all records (target 90%).

## Backups (BR-17)

```bash
php artisan dal:backup            # write a backup now, keep the newest BACKUP_KEEP (default 30)
php artisan dal:restore --list    # list backups
php artisan dal:restore [file]    # replace ALL data with a backup (asks for confirmation)
```

- The scheduler runs `dal:backup` daily at `BACKUP_TIME` (default 02:30). The server needs the standard cron entry: `* * * * * cd /path/to/app && php artisan schedule:run`.
- A backup is one gzipped JSON file with every archive table (`ArchiveBackup::TABLES`). It needs no `pg_dump`. Phone numbers stay encrypted inside it, so `APP_KEY` is still required to read them. It contains financial data and password hashes: keep the disk private.
- Files go to the `backups` disk (`storage/app/backups`). On a host whose local disk is temporary, set `BACKUP_DISK` to an S3-compatible disk.
- Adding a table without listing it in `ArchiveBackup::TABLES` makes the backup fail loudly instead of silently skipping it.
- Neon's own history is a second line of defence only: the free plan keeps **6 hours** of point-in-time history and no snapshots.

## Security (BR-16)

- **Access rules** in one place (`AppServiceProvider::registerAuthorization()`); deactivated users are denied everything.
- **Widgets guard themselves** (`GuardsWidgetAccess`): a team's indicators load only for that team's leader, management and admins; management-wide widgets only for management and admins — also when the component is requested directly.
- **Phone numbers** encrypted at rest, masked for anyone but the owning team's leader and admins, and never serialised (`$hidden`).
- **Accounts**: passwords hashed (bcrypt), policy 10+ characters with letters and numbers, login throttled by Filament, sessions invalidated on password change. Users change their own password under الملف الشخصي; email and role are admin-only. An admin cannot demote, deactivate or delete their own account, so one admin always remains.
- **Links, not files**: only `https://` links are accepted; inline preview is limited to Google Drive/Docs hosts.
- **Browser headers** on every response (`SecurityHeaders`): no framing by other sites, no MIME sniffing, restricted referrer and permissions, HSTS over HTTPS.
- **Production**: set `APP_ENV=production`, `APP_DEBUG=false`, an `https://` `APP_URL` and `SESSION_ENCRYPT=true`. HTTPS URLs and Secure session cookies are then enforced automatically. Behind a load balancer, also configure trusted proxies.
- **Never** run `DemoSeeder` against the live database (it refuses in production) — its accounts share a public password.

## Tests

```bash
php artisan test
```
Covers every page for every role, all dashboard widgets across filter scopes, the main entry forms, and the calculations.
