# MoonPay Test Dashboard

A deliberately lean fintech dashboard built for a hiring assessment.

## Stack

- PHP 8.3
- PostgreSQL / Neon
- PDO with native prepared statements
- Vanilla HTML/CSS
- PHP sessions
- Docker for Render deployment
- Apache

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

## Future VPS deployment

The project is intentionally container-friendly so it can move from Render to a VPS without changing the application architecture.

### Recommended VPS architecture

```text
Internet
   |
   v
Nginx / TLS
   |
   v
PHP application (Apache/PHP container or PHP-FPM)
   |
   +----> PostgreSQL / Neon
   |
   +----> persistent logs/backups
```

For the first VPS migration, the simplest approach is to keep the existing Docker image and run it behind Nginx.

### 1. Provision the VPS

Use a current Ubuntu LTS VPS with:

- 1–2 CPU cores for the prototype
- 1–2 GB RAM
- 20+ GB SSD
- a static public IPv4 address
- SSH access

Create a non-root deployment user and use SSH keys rather than password authentication.

### 2. Install the base software

On the VPS:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git nginx docker.io docker-compose-plugin ufw
sudo systemctl enable --now docker nginx
```

Do not expose PostgreSQL publicly if the database remains on Neon.

### 3. Configure the firewall

Only expose SSH, HTTP and HTTPS:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

If SSH is moved to a custom port later, update the firewall rule before changing SSH.

### 4. Deploy the repository

Clone the repository on the VPS:

```bash
sudo mkdir -p /opt/moonpay-test
sudo chown -R $USER:$USER /opt/moonpay-test
git clone https://github.com/Abdulsalam0001/Moonpay-test.git /opt/moonpay-test
cd /opt/moonpay-test
```

For production, pin deployments to reviewed commits rather than automatically running unreviewed code.

### 5. Create the production environment file

Create `/opt/moonpay-test/.env` and never commit it to Git:

```text
DB_HOST=your-neon-host
DB_PORT=5432
DB_NAME=your-database
DB_USER=your-user
DB_PASSWORD=your-password
DB_SSLMODE=require
APP_ENV=production
APP_URL=https://dashboard.example.com
SESSION_NAME=moonpay_test
SESSION_SECURE=true
```

Protect the file:

```bash
chmod 600 /opt/moonpay-test/.env
```

### 6. Build and run the application

The existing Dockerfile installs PHP's PostgreSQL extension and Apache configuration.

For a simple VPS deployment:

```bash
cd /opt/moonpay-test
docker build -t moonpay-test:latest .
docker run -d \
  --name moonpay-test \
  --restart unless-stopped \
  --env-file .env \
  -p 127.0.0.1:8080:80 \
  moonpay-test:latest
```

Binding the container to `127.0.0.1` keeps it inaccessible directly from the internet. Nginx becomes the public entry point.

Check it locally on the VPS:

```bash
curl -I http://127.0.0.1:8080/
```

### 7. Put Nginx in front

Create a site such as:

```text
/etc/nginx/sites-available/moonpay-test
```

Example:

```nginx
server {
    listen 80;
    server_name dashboard.example.com;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Enable it:

```bash
sudo ln -s /etc/nginx/sites-available/moonpay-test /etc/nginx/sites-enabled/moonpay-test
sudo nginx -t
sudo systemctl reload nginx
```

### 8. Enable HTTPS

Point the domain's DNS A record to the VPS IP, then install a TLS certificate using Certbot:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d dashboard.example.com
```

Choose the HTTPS redirect option. After this, keep `SESSION_SECURE=true`.

### 9. Initialize Neon

Run the database schema against the production Neon database once:

```psql "postgresql://USER:PASSWORD@HOST:5432/DATABASE?sslmode=require" -f database/schema.sql
```

Create the first administrator from the VPS:

```docker exec -it moonpay-test php scripts/create_admin.php "Admin Name" admin@example.com
```

The script requires a long password. Do not place production credentials in shell history; use the safest supported secret-entry method when creating the admin.

### 10. Updating the VPS

When a new version is approved:

```bash
cd /opt/moonpay-test
git fetch origin
git checkout main
git pull --ff-only origin main
docker build -t moonpay-test:latest .
docker stop moonpay-test
docker rm moonpay-test
docker run -d \
  --name moonpay-test \
  --restart unless-stopped \
  --env-file .env \
  -p 127.0.0.1:8080:80 \
  moonpay-test:latest
```

For a more mature production setup, replace this manual restart with Docker Compose and health checks so releases can be rolled out with less downtime.

### 11. Database migration strategy

The VPS should not own the database just because the application moves to a VPS.

The recommended first production arrangement is:

```text
VPS
  |
  +--> MoonPay PHP application
  |
  +--> Neon PostgreSQL over TLS
```

Keep Neon as the managed database until there is a specific reason to self-host PostgreSQL.

If PostgreSQL is eventually moved onto the VPS, add:

- private database binding
- encrypted off-server backups
- tested restore procedures
- database monitoring
- restricted database firewall rules
- separate database credentials
- scheduled backups

Never expose PostgreSQL port 5432 to the public internet unnecessarily.

### 12. Secrets and backups

Never put any of these in Git:

- `.env`
- database passwords
- SSH private keys
- TLS private keys
- admin passwords
- API keys

Back up the database separately from the VPS filesystem. Test restoring a backup before considering the migration complete.

### 13. Production hardening checklist

Before treating the VPS as production:

- [ ] SSH keys configured
- [ ] Root SSH login disabled
- [ ] Password SSH authentication disabled
- [ ] UFW enabled
- [ ] Only 22/80/443 exposed as required
- [ ] Automatic security updates configured
- [ ] Docker containers restart automatically
- [ ] Nginx terminates TLS
- [ ] HTTPS redirect enabled
- [ ] `SESSION_SECURE=true`
- [ ] Production `.env` is outside Git and mode 600
- [ ] Neon connection uses TLS
- [ ] Database backups enabled
- [ ] Restore procedure tested
- [ ] Admin account created with a strong unique password
- [ ] Application logs monitored
- [ ] No debug output or development credentials in production
- [ ] Deployment commits reviewed before release

### Render-to-VPS migration summary

The eventual migration should be:

```text
Current
GitHub -> Render Docker service -> Neon PostgreSQL

Future
GitHub -> VPS Docker service -> Neon PostgreSQL
                  |
                Nginx
                  |
                HTTPS
```

The application code remains largely the same. The main changes are infrastructure: VPS provisioning, Docker runtime management, Nginx, DNS, TLS, firewall rules, secrets management and deployment procedures.

> This project is a hiring-assessment prototype. It does not process real money or crypto transactions.
