# ANSHXOSINT MASTER — Render deployment

PHP 8.3 + Apache Docker deployment for Render. Includes SQLite-backed admin login, API key permissions/quotas, and public OSINT utility endpoints.

## Deploy on Render

1. Upload this folder to a GitHub repository (include `Dockerfile`, `docker-entrypoint.sh`, and `render.yaml` at repository root).
2. In Render, choose **New → Blueprint** and connect the repository, or create a Docker Web Service from the repository.
3. The included Blueprint config uses a **Starter web service and a 1 GB persistent disk** so the SQLite database survives restarts/redeploys. A persistent disk is a paid Render feature; check current pricing before deploying. Do not remove the disk unless you accept losing the SQLite database on deploy/restart.
4. Wait for the service to deploy and open the Render-provided `onrender.com` URL.
5. Open `/install.php`, use the `ADMIN_SETUP_TOKEN` value from the service's Environment page, and create your admin account with a strong unique password.
6. Immediately delete `install.php` from the GitHub repository and redeploy. Do not leave the setup page available in production.
7. Sign in at `/login.php`, create API keys, and select each key's permissions and limits.

If using **New → Web Service** instead of Blueprint, choose Docker, then add these environment variables:
- `DB_PATH=/var/data/app.sqlite`
- `ADMIN_SETUP_TOKEN` = a long random secret
and attach a persistent disk mounted at `/var/data` (paid feature). Without persistent storage, SQLite data can be lost when the service is replaced or redeployed.

## API examples

Create an API key in the admin panel. Send it as `Authorization: Bearer YOUR_KEY`:
- `GET /api/rdap.php?domain=example.com`
- `GET /api/dns.php?domain=example.com&type=MX`
- `GET /api/ip.php?ip=8.8.8.8`
- `GET /api/email.php?email=test%40example.com`
- `GET /api/url.php?url=https%3A%2F%2Fexample.com`
- `GET /api/phone.php?number=%2B919876543210`
- `GET /api/username.php?username=exampleuser`
- `GET /api/health.php` (public health check)

## Runtime requirements

Docker image installs PHP 8.3, PDO_SQLite, cURL and Apache headers. Render provides HTTPS for the service URL. External data providers may rate-limit requests or be unavailable.

## Security / scope notes

- Remove `install.php` after creating the first administrator.
- API keys are stored as SHA-256 hashes and displayed only once. Prefer the Bearer header; query-string keys can leak in logs/history.
- Phone endpoint returns basic format/country-code hints only—not subscriber identity or live location. Email checks syntax only; URL endpoint validates syntax but does not fetch or scan the URL. Username endpoint provides a public search link.
- This starter does not include advanced per-minute throttling. Use only data you are authorized to access and respect upstream provider terms.
