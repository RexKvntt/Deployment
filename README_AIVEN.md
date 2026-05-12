# Aiven MySQL Migration

Use this when the production database will be Aiven for MySQL.

## 1. Create the Aiven database

1. Create an Aiven for MySQL service.
2. Copy the service URI from the service Overview page.
3. Create the target database if you do not want to use `defaultdb`.
4. Download the CA certificate from the service Overview page.

Aiven's PHP guidance uses PDO with the service URI, `sslmode=verify-ca`, and the downloaded CA certificate.

## 2. Import the schema

Import this file into Aiven:

```bash
mysql --host=AIVEN_HOST --port=AIVEN_PORT --user=avnadmin --password --ssl-mode=VERIFY_CA --ssl-ca=ca.pem defaultdb < database/aiven_schema.sql
```

Use the real host, port, user, database name, and `ca.pem` path from Aiven.

## 3. Render environment variables

Set `DATABASE_URL` to the Aiven service URI. If your URI does not already include SSL mode, append it:

```env
DATABASE_URL=mysql://avnadmin:password@host.aivencloud.com:12345/defaultdb?ssl-mode=verify-ca
MYSQL_SSL_MODE=verify-ca
```

Then provide the CA certificate using one of these options:

- Preferred: create a Render secret file containing Aiven's `ca.pem`, then set `MYSQL_SSL_CA` to that file path.
- Alternative: base64-encode `ca.pem` and set it as `AIVEN_CA_CERT_BASE64`.
- Alternative: paste the PEM text into `AIVEN_CA_CERT`.

Keep the rest of the app variables from `.env.example`, especially `HELIOS_SECRET_KEY`.

## 4. Create the first admin

After importing the schema and deploying the app, set:

```env
ADMIN_EMAIL=your-admin-email@example.com
ADMIN_PHONE=09XXXXXXXXX
ADMIN_PASSWORD=your-secure-password
```

Then run this one-off command in the Render shell:

```bash
php tools/create_admin.php
```
