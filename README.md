# MoonPay Test

Simple PHP 8.3 + PostgreSQL application for local visual testing and later HestiaCP deployment.

## Local Docker preview

1. Copy `.env.example` to `.env`.
2. Add your Neon PostgreSQL credentials if you want to test login/dashboard data.
3. Run:

```bash
docker compose up --build
```

4. Open **http://localhost:8080**

The Docker container runs Apache + PHP 8.3 with the PostgreSQL PDO extension. The public web root is `/var/www/html/public`, so application source files remain outside the document root.

## HestiaCP deployment

Docker is only for local visual testing. For production on HestiaCP, use the same PHP application directly under the Hestia domain and point the domain document root to `public/`. Keep `.env`, `src/`, `database/`, and `scripts/` outside the public web root.

Then configure the production `.env` with the Neon connection details and run the database schema.

## Main pages

- `/login.php` — sign in
- `/dashboard.php` — user dashboard
- `/admin.php` — admin overview
- `/logout.php` — session logout
