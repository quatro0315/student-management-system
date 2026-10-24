# Student Management System — Render + Aiven Deployment

This is a PHP 8.3 / Apache application packaged for Render's Docker runtime and configured to connect to an external Aiven MySQL database.

## 1. Create the Aiven MySQL database

1. Create a MySQL service in Aiven.
2. Open the service's **Connection information** and note the host, port, database name, username, and password.
3. Download the Aiven CA certificate if your service requires certificate verification. Keep the certificate private to your deployment setup; do not paste database passwords into source files.
4. Import `sql/student_db.sql` into the Aiven database using Aiven's SQL console or a MySQL client. The uploaded `student_db (4).sql` dump supplied with this project was copied to this path. If your Aiven database already has the tables and real data, back it up first and do not import the dump over it.

The SQL dump contains sample accounts and sample student data. Treat those as demo data and change/remove them before using real information.

## 2. Deploy to Render

1. Push this project folder to a GitHub repository.
2. In Render, choose **New + → Web Service**, connect the repository, and select **Docker** as the runtime.
3. Set the Dockerfile path to `./Dockerfile` if Render does not detect it automatically.
4. Add the environment variables below in Render's Environment settings.

| Variable | Value |
|---|---|
| `DB_HOST` | Aiven MySQL hostname |
| `DB_PORT` | Aiven MySQL port |
| `DB_NAME` | Aiven database name, usually `student_db` if you created it that way |
| `DB_USER` | Aiven database username |
| `DB_PASS` | Aiven database password |
| `DB_SSL_CA` | Optional path to the Aiven CA certificate inside the container, if you have added/mounted it |
| `ALLOW_ROLE_SELECTION_ON_SIGNUP` | `false` for production |

Use the exact values shown in Aiven. Do not include `https://` in `DB_HOST`.

### Aiven TLS/SSL

Aiven commonly requires TLS. Download the CA certificate from the Aiven service and make it available to the container (for example, include a public CA certificate file in the repository at `certs/ca.pem`, then set `DB_SSL_CA=/var/www/html/certs/ca.pem`). Only commit the CA certificate, never credentials or private keys. If Aiven provides a different connection method for your plan, follow the service's connection instructions. The app enables CA verification when `DB_SSL_CA` points to a readable file.

5. Deploy. After the service is live, open `/health.php`; it should return `ok`. Then open `/` or `/login.php` to use the application.

## 3. Important production notes

- **Disable public role selection.** `ALLOW_ROLE_SELECTION_ON_SIGNUP=false` prevents users from choosing privileged roles on registration. Review `register.php` and test that new registrations receive the Student role.
- **Create/verify an administrator account securely.** Existing sample password hashes may not have known passwords. Use a controlled account-creation process and remove demo accounts. Do not publish passwords in source control.
- **Uploads on Render are ephemeral by default.** Profile photos and instructor photos may disappear after redeploy/restart. For persistent uploads, attach a Render persistent disk and mount it to `/var/www/html/images` and `/var/www/html/uploads`, or move uploads to object storage. Ensure the mounted paths are writable by Apache.
- **Database network access:** ensure the Aiven service is running and its access/network settings permit connections from the Render service. If Aiven uses an IP allowlist, account for Render's outbound IP behavior for your plan.
- **Backups:** configure Aiven backups and test restore procedures before storing real records.
- **Privacy:** use demo data until access control, account setup, TLS, backups, and upload persistence have been verified.
- `DB_SSL_CA` is optional in code but should be configured when required by Aiven's connection settings. Never disable TLS verification merely to bypass a connection error.

## Local development (XAMPP)

The same project works locally. Set `DB_HOST=127.0.0.1`, `DB_PORT=3306` (or your local MySQL port), `DB_NAME=student_db`, `DB_USER=root`, and `DB_PASS` in your local environment. If environment variables are not set, the app uses these local-development defaults.
