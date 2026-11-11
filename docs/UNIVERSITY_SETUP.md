# Kouhfar Dental — University Test Setup

This guide is for teammates who need to clone the repository and run the university/demo version locally.

## What this setup provides

- PostgreSQL local database.
- Synthetic seeded data only.
- A local-only demo OTP; no SMS provider account or token is required.
- A seeded public service, practitioner, resource, working hours, and appointment.
- Deterministic synthetic staff credentials.
- Production live-SMS activation remains separately gated.

## Requirements (Windows)

- Git
- PHP 8.3+
- Composer
- Node.js 22.x
- PostgreSQL 18.x or compatible PostgreSQL
- PowerShell

## 1. Clone

```powershell
git clone https://github.com/sepehrsamad1/kouhfar-dental.git
cd kouhfar-dental
git checkout task/29-university-setup
```

After this branch is merged into `main`, use `git checkout main; git pull` instead.

## 2. Create the database

Create a local PostgreSQL database named `kouhfar`:

```powershell
psql -U postgres -h 127.0.0.1 -c "CREATE DATABASE kouhfar;"
```

If it already exists, continue.

## 3. Create `.env`

```powershell
Copy-Item .env.example .env
```

Check the PostgreSQL password in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kouhfar
DB_USERNAME=postgres
DB_PASSWORD=YOUR_LOCAL_POSTGRES_PASSWORD
```

The example already enables:

```dotenv
SMS_OTP_DRIVER=demo
UNIVERSITY_DEMO_MODE=true
OTP_DEMO_CODE=123456
UNIVERSITY_DEMO_STAFF_PASSWORD=Demo1234!
```

Do not commit `.env`.

## 4. Install and initialize

```powershell
composer install
npm install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm run build
php artisan optimize:clear
```

The seeder is blocked in production and contains synthetic records only.

## 5. Start

```powershell
php artisan serve
```

Open:

```
http://127.0.0.1:8000
```

## 6. Test public booking

Use the public booking flow.

Seeded service:

- `خدمت آزمایشی`
- Practitioner: `پزشک آزمایشی`
- Resource: `یونیت آزمایشی`

When the booking page asks for the mobile verification code, enter:

```
123456
```

No SMS is sent.

## 7. Test staff login

Use one of these synthetic accounts:

| Role | Email |
|---|---|
| Admin | `seed-admin@example.invalid` |
| Reception | `seed-reception@example.invalid` |
| Practitioner | `seed-practitioner@example.invalid` |

Password:

```
Demo1234!
```

All seeded data is synthetic. Do not enter real patient information.

## 8. MeliPayamak is not required

The university setup does **not** require:

- MeliPayamak account/token
- Kavenegar credentials
- live SMS
- real patient data
- production secrets
- external AI credentials

The real MeliPayamak adapter remains available behind its explicit activation gates and is not used by the demo environment.

## 9. Quick verification

After the app starts, verify:

1. Homepage loads.
2. Public services show `خدمت آزمایشی`.
3. Availability shows slots.
4. Booking reaches OTP verification.
5. Entering `123456` completes verification.
6. Appointment is persisted.
7. Staff login works with the synthetic credentials.
8. Staff can see/manage the seeded appointment.
9. Logout works.

If a teammate only wants to run automated checks:

```powershell
php artisan test
npm run test
```
