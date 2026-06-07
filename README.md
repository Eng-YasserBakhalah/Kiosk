# Bank Self-Service API

Laravel API backend for a bank self-service kiosk channel. The current scope covers terminal heartbeat, customer enrollment with OTP, password setup, JWT login, and JWT logout.

## Stack

- PHP 8.2+
- Laravel 12
- MySQL or SQLite
- JWT authentication via `php-open-source-saver/jwt-auth`
- Swagger UI via `darkaonline/l5-swagger`
- Laravel Pint and PHPUnit

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

Run tests:

```bash
php artisan test
```

Check formatting:

```bash
vendor/bin/pint --test
```

## Seed Data

The default seeder creates:

- Branch: `BR-001`
- Terminal device: `KIOSK-001`
- Digital service user: `USR10001`
- Password: `Password1`

## API Routes

Base prefix: `/api/v1`

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/device/heartbeat` | No | Update active kiosk heartbeat |
| POST | `/enrollment/start` | No | Start enrollment and create OTP request |
| POST | `/enrollment/verify-otp` | No | Verify pending OTP |
| POST | `/enrollment/set-password` | No | Activate account after verified OTP |
| POST | `/auth/login` | No | Login with device, username, and password |
| POST | `/auth/logout` | JWT | Invalidate current JWT |
| GET | `/user` | JWT | Return the authenticated digital service user |

## Security Notes

- OTP values are hashed before storage and are not logged.
- In local/testing environments, `/api/v1/enrollment/start` can return `debug_otp` for temporary manual testing. Disable it with `OTP_DEBUG_RESPONSE=false`.
- OTP requests expire after five minutes.
- OTP verification fails permanently after five invalid attempts.
- Verified OTP requests can only be consumed once.
- Login requires an active terminal device and an active user.
- Accounts lock for 15 minutes after five failed password attempts.
- Password setup requires at least eight characters, mixed case, and a number.

## Remaining Integration Point

Enrollment currently contains a placeholder for core-banking customer verification. Replace it with the real bank customer lookup before production use.
