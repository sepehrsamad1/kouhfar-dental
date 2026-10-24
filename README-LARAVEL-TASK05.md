# Kouhfar Dental Clinic — Laravel 13 Task 05

This is the corrected Task 05 package: the existing modularized frontend is preserved and integrated into a complete Laravel 13 application structure.

## Setup on Windows PowerShell

```powershell
composer install
npm.cmd install
Copy-Item .env.example .env
php artisan key:generate
npm.cmd run build
php artisan serve
```

Open `http://127.0.0.1:8000`.

The public booking, assistant and Smart Smile behaviors remain simulated as required by Task 05. Real backend/API work belongs to later tasks.

## Important

- `artisan`, `config/`, `database/`, `storage/`, and `public/index.php` are part of the Laravel application now.
- Vite is the frontend build tool; `npm.cmd run build` creates `public/build/` for Laravel's `@vite` directive.
- PostgreSQL remains the authoritative application-data target for later backend tasks; SQLite is only the local foundation default so the UI can boot without requiring a database server.
