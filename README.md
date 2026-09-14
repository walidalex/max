# Contracting & Interior Design ERP

Clean Arabic RTL modular-monolith foundation built with PHP 8.4, MySQLi, Tabler, and vanilla JavaScript.

Current business foundations include permission-based RBAC and a singleton company profile available at `/settings/company`.

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

```bash
composer lint
composer test
```

See `docs/` for architecture and project decisions.
