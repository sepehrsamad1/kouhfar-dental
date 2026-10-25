# Task 06 — Testing, Quality Gates, CI, and Browser Smoke Tests

## Purpose

Task 06 establishes the automated validation foundation for the Kouhfar Dental Clinic Laravel application.

The goal is to make the project reproducibly testable locally and in GitHub Actions without introducing new business behavior or backend domain/schema decisions.

The existing Task 05 UI remains unchanged.

---

## 1. PHP Testing

The project uses Pest on top of PHPUnit.

Files added:

- `tests/Pest.php`
- `tests/TestCase.php`
- `tests/Feature/HomepageTest.php`
- `tests/Feature/PostgresConnectionTest.php`
- `phpunit.xml`

Run:

```powershell
php artisan test