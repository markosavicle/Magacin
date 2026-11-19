# Magacin

[![Tests](https://github.com/markosavicle/Magacin/actions/workflows/tests.yml/badge.svg)](https://github.com/markosavicle/Magacin/actions/workflows/tests.yml)

A web app for a small wholesaler or distributor to keep partners, incoming and outgoing invoices, and payments in one place. It replaces the Excel workbook where balances are calculated by hand: each partner's balance is always current, a partner statement (*kartica partnera*) is one click away as a PDF, and every change is logged with who made it.

**Stack:** PHP 8.4, Laravel 13, Blade + Tailwind (Breeze), dompdf, spatie/laravel-backup, PostgreSQL (SQLite for tests), Docker.

**Scope.** Magacin is the wholesale back office: partners, invoices, payments, balances and PDFs. It does not track items or stock. The separate [`erp-sync-engine/`](erp-sync-engine/) folder shows the other half of a typical integration: syncing stock and prices from an ERP export to a webshop. It is a base to adapt per client, not a plug-and-play ERP module.

## At a glance

**Problem.** A distributor tracks customers, suppliers, invoices and payments in Excel. Balances are worked out by hand, statements are assembled before every call with a customer, and nobody can tell who changed a number or when.

**Solution.**
- **Partners:** customers (*kupac*) and suppliers (*dobavljač*), with PIB, contact person, city and notes. Search across name, type, e-mail, phone and city.
- **Invoices:** outgoing and incoming, with line items. The invoice total is calculated from its items.
- **Payments:** money received (*uplata*) and paid out (*isplata*), per partner and date.
- **Balance per partner:** outgoing invoices + payouts − incoming invoices − payments received. Positive means the partner owes us.
- **PDF:** a single invoice, or a partner statement for any date range with every invoice and payment as a debit or credit line.
- **Audit log:** every create, update and delete of a partner, invoice or payment, with old and new values, user and IP.
- **Accounts:** new sign-ups wait for an admin's approval before they can log in.
- **Nightly checks:** a 02:00 backup of the database and files, and a 02:30 consistency check (below).

**Results.** No production numbers yet: this is published as a working demo with fictional data. Numbers to add once it runs at a business: time to produce a partner statement before and after, number of partners and invoices, and balance errors caught.

## Consistency check

```bash
php artisan partners:verify-balances            # all partners
php artisan partners:verify-balances --partner=3
```

It recalculates every partner's balance from the raw invoices and payments, and checks that every invoice's amount equals the sum of its line items, so a total edited outside the app is caught. It exits with status 1 on any mismatch, so it can gate a deploy or alert from cron. It runs nightly through the scheduler.

## Run it with Docker (one command)

Needs Docker Desktop (Windows/macOS) or Docker Engine with Compose v2 (Linux).

```bash
make demo          # or ./setup.sh demo (Git Bash/WSL on Windows), or docker compose up --build -d
```

This builds the images, starts Postgres 17, PHP-FPM 8.4, Nginx and the scheduler, runs the migrations, creates the admin and loads the fictional demo data. It prints the URL (http://localhost:8080) and the admin login. No password is hardcoded anywhere: `setup.sh` writes a `.env` with a random DB and admin password, and without a `.env` the app generates the APP_KEY and admin password on first start and keeps them in the storage volume (`make admin` prints them).

| Command | What it does |
|---|---|
| `make demo` | build, start, seed, print URL and login |
| `make test` | run the test suite inside the test image (same as CI) |
| `make admin` / `make logs` | print the login / follow the logs |
| `make verify` / `make backup` | balance check / backup now (database dump + files, in the storage volume) |
| `make down` / `make reset` | stop (data kept) / delete all data (asks first) |

Containers: `app` (PHP-FPM; migrates and seeds on start, safe to repeat), `web` (Nginx, the only published port), `scheduler` (`schedule:work`: backup 02:00, balance check 02:30), `db` (Postgres, not published). Redis and a queue worker are not included because the app does not use them. Files are in `docker/` and `docker-compose.yml`; the `Docker` GitHub Actions workflow runs `make demo`, checks the login page, the demo data and the balances, and runs the tests in the container on every pull request.

The root `Dockerfile` is the older single-container image (`php artisan serve` on port 10000), kept as it was for existing deployments.

## Run it without Docker

```bash
cp .env.example .env               # set DB_* and ADMIN_EMAIL / ADMIN_PASSWORD
composer install && npm ci && npm run build
php artisan key:generate
php artisan migrate --seed         # creates the admin from ADMIN_EMAIL / ADMIN_PASSWORD
php artisan db:seed --class=DemoSeeder   # optional: fictional wholesaler data
php artisan serve
```

With the single-container root `Dockerfile`, the container migrates and runs the seeders on every start. That is safe: the admin is created only if the account does not exist yet, and an existing password is never reset. The scheduler (backup and nightly check) needs its own process: `php artisan schedule:work`, or cron running `php artisan schedule:run` every minute.

### Demo data

`DemoSeeder` creates a food and drinks wholesaler around Niš: 8 customers (shops, a café, a restaurant, a hotel), 4 suppliers (dairy, winery, brewery, water), about six months of invoices with line items, and payments, some still outstanding. **All companies, people, PIBs, phone numbers and addresses are fictional.** PIBs are in the 100000101–100000112 range, and e-mails use the reserved `.example` domain. It runs once and does nothing if the demo partners already exist.

## Tests

```bash
php artisan test
```

Besides Breeze's authentication tests, the feature tests cover the balance formula, the consistency check (passing, a tampered invoice, an unknown partner), PDF export of statements and invoices, guest access, the admin seeder (created from config, skipped without credentials, never resetting a password) and the demo data (fictional ranges, consistent balances, runs once). CI runs them on every push and pull request.

## Project layout

```
app/Models/                Partner (balance), Invoice, InvoiceItem, Transaction, History (audit log), User
app/Http/Controllers/      Partners (+ statement PDF), invoices (+ PDF), transactions, history, user approval
app/Console/Commands/      partners:verify-balances
routes/console.php         Schedule: backup 02:00, consistency check 02:30
database/seeders/          AdminUserSeeder (from .env), DemoSeeder (fictional data)
resources/views/           Blade views, including invoices/pdf and partners/statement-pdf
tests/Feature/             Feature tests
docker/                    Multi-stage Dockerfile (app, web, test), Nginx config, entrypoint
erp-sync-engine/           Separate Python CLI: ERP CSV/XML export -> WooCommerce/Magento stock and prices
```

---

**Ukratko (SR):** Interna web aplikacija za veleprodaju i distributere: partneri (kupci i dobavljači sa PIB-om), ulazne i izlazne fakture sa stavkama, uplate i isplate, automatski saldo po partneru, kartica partnera u PDF-u za bilo koji period, istorija svih izmena (ko, kada, staro i novo) i odobravanje novih korisnika. Svake noći pravi backup i proverava da li se saldo i iznosi faktura slažu sa izvornim podacima. Demo podaci su potpuno izmišljeni.

## License

MIT
