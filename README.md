# Magacin

[![Tests](https://github.com/markosavicle/Magacin/actions/workflows/tests.yml/badge.svg)](https://github.com/markosavicle/Magacin/actions/workflows/tests.yml)

A web app for a small wholesaler or distributor to keep partners, incoming and outgoing invoices, and payments in one place. It replaces the Excel workbook where balances are calculated by hand: each partner's balance is always current, a partner statement (*kartica partnera*) is one click away as a PDF, and every change is logged with who made it.

**Stack:** PHP 8.4, Laravel 13, Blade + Tailwind (Breeze), dompdf, spatie/laravel-backup, PostgreSQL (SQLite for tests), Docker.

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

## Run it

```bash
cp .env.example .env               # set DB_* and ADMIN_EMAIL / ADMIN_PASSWORD
composer install && npm ci && npm run build
php artisan key:generate
php artisan migrate --seed         # creates the admin from ADMIN_EMAIL / ADMIN_PASSWORD
php artisan db:seed --class=DemoSeeder   # optional: fictional wholesaler data
php artisan serve
```

With Docker, the `Dockerfile` migrates and runs the seeders on every start. That is safe: the admin is created only if the account does not exist yet, and an existing password is never reset. The scheduler (backup and nightly check) needs its own process: `php artisan schedule:work`, or cron running `php artisan schedule:run` every minute.

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
```

---

**Ukratko (SR):** Interna web aplikacija za veleprodaju i distributere: partneri (kupci i dobavljači sa PIB-om), ulazne i izlazne fakture sa stavkama, uplate i isplate, automatski saldo po partneru, kartica partnera u PDF-u za bilo koji period, istorija svih izmena (ko, kada, staro i novo) i odobravanje novih korisnika. Svake noći pravi backup i proverava da li se saldo i iznosi faktura slažu sa izvornim podacima. Demo podaci su potpuno izmišljeni.

## License

MIT
