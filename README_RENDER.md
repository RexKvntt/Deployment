# Render Deployment

This project is a PHP/MySQL app. Render does not provide a native PHP runtime, so this repo includes a Dockerfile for the PHP web service. The database must be a MySQL-compatible database from an external provider.

## 1. Prepare the database

1. Create an empty MySQL or MariaDB database.
2. Import `database/render_schema.sql`.
3. Copy the provider's MySQL connection URL into Render as `DATABASE_URL`.

Do not import `helios_db.sql` into production unless you intentionally want the local sample users and class data.

## 2. Deploy on Render

1. Push this repository to GitHub.
2. In Render, create a new Blueprint from the repository. Render will use `render.yaml` and the included Dockerfile.
3. Set `APP_URL` to the final Render URL, for example `https://helios-academic-hub.onrender.com`.

For a manual Render service, choose `Docker` as the runtime and keep the default Dockerfile path `./Dockerfile`.

## 3. Create the first admin

After importing `database/render_schema.sql`, run a one-off shell command in Render with these environment variables set:

- `ADMIN_EMAIL`
- `ADMIN_PHONE`
- `ADMIN_PASSWORD`
- Optional: `ADMIN_USERNAME`, `ADMIN_FIRSTNAME`, `ADMIN_LASTNAME`

Command:

```bash
php tools/create_admin.php
```

## 4. Required environment variables

Copy `.env.example` into Render's environment settings and fill in real values for:

- `DATABASE_URL`
- `MYSQL_SSL_CA` or `AIVEN_CA_CERT_BASE64` if your database provider requires TLS, such as Aiven
- `HELIOS_SECRET_KEY`
- `SMTP_USERNAME`
- `SMTP_PASSWORD`
- `SMTP_FROM_EMAIL`
- `TWILIO_ACCOUNT_SID`
- `TWILIO_AUTH_TOKEN`
- `TWILIO_VERIFY_SERVICE_SID`

Keep `HELIOS_SECRET_KEY` unchanged after accounts exist, because it is used to encrypt stored email and phone values.

## 5. Uploads

User uploads are written under `uploads/`. Render web service filesystems are ephemeral unless you add a persistent disk. For production class submissions, attach a Render persistent disk at the project `uploads` path or move uploads to object storage.
