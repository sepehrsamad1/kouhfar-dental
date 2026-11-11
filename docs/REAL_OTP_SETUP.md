# Real OTP teammate setup

This repository keeps the real MeliPayamak OTP integration. There is no demo OTP provider and no fixed test OTP.

## What a teammate needs

After the machine prerequisites are installed, the repository contains the application code, migrations, seed data, OTP provider, tests, and frontend assets.

The only provider secret that must be entered manually is the teammate's own MeliPayamak OTP token.

Machine-local values such as the PostgreSQL password are not committed because they belong to the developer's machine.

## 1. Clone

```powershell
git clone https://github.com/sepehrsamad1/kouhfar-dental.git
cd kouhfar-dental
```

Use the branch/commit that contains this setup change, or continue from `main` after the PR is merged.

## 2. Prerequisites

Install:

- PHP 8.3+ (the project is currently tested locally with PHP 8.5)
- Composer 2.x
- PostgreSQL 18.x (or a compatible supported PostgreSQL version)
- Node.js 24.x and npm
- Git

PHP must have at least `curl`, `openssl`, `mbstring`, `pdo_pgsql`, and `pgsql` enabled.

## 3. PostgreSQL

Create a local database named `kouhfar`.

The tracked example assumes:

```text
host: 127.0.0.1
port: 5432
database: kouhfar
username: postgres
```

If the local PostgreSQL installation uses a password, put that machine-local password in `.env`. Never commit it.

## 4. Environment

Copy the example:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

The example is already configured for the real OTP driver. Set only your own provider token:

```env
MELIPAYAMAK_OTP_TOKEN=YOUR_OWN_TOKEN
```

Do not paste the token into GitHub, commits, issues, screenshots, or chat.

Then clear cached configuration:

```powershell
php artisan optimize:clear
```

## 5. Install and initialize

```powershell
composer install
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
```

The seed creates the synthetic booking data and makes the synthetic service publicly bookable, so a teammate does not need a manual Tinker change.

## 6. Run

```powershell
php artisan serve
```

Open the local application at:

```text
http://127.0.0.1:8000
```

## 7. Test real OTP

Use a real mobile number that you own or are explicitly authorized to test.

Start a public booking and request the OTP. The application will call the real MeliPayamak adapter. Enter the SMS code received on the phone.

There is intentionally no fallback OTP and no hard-coded code.

## 8. If MeliPayamak rejects the request

The provider account/token must be enabled and approved for OTP sending by MeliPayamak.

A token can be syntactically valid and recognized by the API while the provider still returns an account-level configuration/manager-approval error. That is a provider-side prerequisite, not a reason to replace the real integration with a demo provider.

## Security rules

- Never commit `.env`.
- Never commit the MeliPayamak token.
- Never disable TLS certificate verification.
- Do not use real patient data in development.
- Do not test against production or another person's phone without authorization.
- CI must continue using non-live OTP configuration; real SMS credentials must never be stored in CI.
