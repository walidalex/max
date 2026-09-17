# Contracting & Interior Design ERP

Arabic RTL modular-monolith ERP built with PHP 8.4, MySQLi, Tabler, and vanilla JavaScript.

The implemented scope covers core access and master data, contracts and BOQs, subcontract execution and payments, actual project costs, and Cost Plus client progress statements.

## Requirements

- PHP 8.4+ with `mysqli`
- MySQL 8+ or MariaDB 10.11+
- Composer 2
- Node.js 20+ and npm

## Setup

```bash
composer install
npm install
npm run assets:build
php database/migrate.php
php -S localhost:8000 -t public public/router.php
```

Check the configured database connection without changing data:

```bash
composer db:check
```

## First administrator

After migrations, create the first administrator without storing a default password in the repository:

```powershell
$env:INITIAL_ADMIN_USERNAME="admin"
$env:INITIAL_ADMIN_NAME="System Administrator"
$env:INITIAL_ADMIN_EMAIL="admin@example.com"
$env:INITIAL_ADMIN_PASSWORD="use-a-strong-password-here"
php database/seeders/create-super-admin.php
Remove-Item Env:INITIAL_ADMIN_PASSWORD
```

The password must contain at least 12 characters. Sign in at `/login` using the username or email.

Copy `.env.example` to `.env`, then set the database credentials and a random `APP_KEY` before running migrations.
On Windows, ensure `extension=mysqli` is enabled in the `php.ini` used by the CLI and web server.

## Quality checks

Tests are intentionally blocked unless `APP_ENV` is `testing` and the configured database name ends in `_test`. Create a dedicated empty test database, migrate it, and run the suite without reusing a development or production database.

PowerShell example:

```powershell
$env:APP_ENV="testing"
$env:DB_DATABASE="hycacmvp_contracting_erp_test"
php database/migrate.php
composer db:check
composer lint
composer test
npm run assets:build
```

Use `.env.testing.example` as a safe reference. If your local test database needs credentials, provide them through external environment variables or an ignored `.env.testing`; never commit them.

External environment variables take precedence over values in `.env`.

```bash
composer lint
composer test
```

See `docs/` for architecture and project decisions.
