# MoonPay Test Dashboard

A deliberately lean fintech dashboard built for a hiring assessment.

## Stack

- PHP 8.3
- PostgreSQL / Neon
- PDO with native prepared statements
- Vanilla HTML/CSS
- PHP sessions
- Docker for Render deployment

## Security foundation

- Password hashing with `password_hash()`
- Password verification with `password_verify()`
- Session ID rotation after authentication
- CSRF protection
- Prepared SQL statements
- Server-side authorization for admin routes
- Login-attempt throttling
- Secure session cookie settings
- Security headers and restrictive CSP
- Public web root isolated from application/database/scripts directories
- Generic authentication failure messages

## Local setup

1. Copy `.env.example` to `.env`.
2. Fill in the PostgreSQL/Neon connection values.
3. Apply `database/schema.sql`.
4. Create an administrator:

```bash
php scripts/create_admin.php "Admin Name" admin@example.com "use-a-long-password"
```

5. Run the PHP server:

```bash
php -S localhost:8080 -t public
```

## Render

The repository includes `Dockerfile` and `render.yaml`.

Create a Render Web Service from this GitHub repository. Render will build the PHP/Apache container and provide the public `.onrender.com` URL.

Set these environment variables in Render:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
DB_SSLMODE=require
APP_ENV=production
APP_URL=https://your-service.onrender.com
SESSION_NAME=moonpay_test
SESSION_SECURE=true
```

Run the schema against the Neon database before testing authentication.

> This project is a hiring-assessment prototype. It does not process real money or crypto transactions.
