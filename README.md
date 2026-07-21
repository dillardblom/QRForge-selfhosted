<p align="center"><img src="src/dist/img/brand/logo.svg" alt="QRForge" width="320"></p>

<p align="center"><strong>Self-hosted, open-source QR code generator.</strong></p>

**QRForge** creates and manages static and dynamic QR codes from a clean,
responsive control panel. It's a security-hardened, actively maintained fork
of the great but abandoned original [PHP Dynamic Qr code](https://github.com/giandonatoinverso/PHP-Dynamic-Qr-code)
project by Giandonato Inverso, built on [AdminLTE](https://adminlte.io/).

- **Try it free:** [qr.ensembia.com](https://qr.ensembia.com) - fully functional OSS test
  instance. [Register your own free account](https://qr.ensembia.com/register.php)
  (email + a self-hosted CAPTCHA, no third-party service) - no shared demo login needed.
- **Commercial VIP edition:** the ability to give sub-users the ability to create
  QR codes as well, from their own (sub)account. If you have a bigger organisation,
  having more users being able to create new QR codes delegates your workload. To
  fund our open-source project, a small fee (€49/year subscription per organisation
  ("tenant")) is requested for this. [www.qrforge.eu](https://www.qrforge.eu).
- **Self-host it yourself:** this repository, MIT-licensed.

# Features

- Dynamic QR codes with a database-backed URL shortener
    - Create, edit, delete, enable/disable the redirect
    - Download any time, bulk download/delete
    - Batch-generate from a CSV file
- 16 static QR code types: Text, Email, Phone, SMS, WhatsApp, Skype, Location,
  vCard, Event/calendar, Bookmark, WiFi (incl. WPA3), PayPal, Bitcoin, 2FA,
  App Link (Android intent / universal links), Bluetooth
- QR code styling: 6 export formats, foreground/background color, 4 precision
  levels, 10 sizes, optional label text below the code (custom font + size),
  optional icon shown above the code, save/load your own style presets
- Address search (OpenStreetMap Nominatim) for Location QR codes
- Built-in QR scanner (camera or image upload, decodes entirely client-side)
- Installable as a PWA
- Role-based access: `super` (full access + user management), `admin`
  (scoped to their own codes and sub-users), `user` (read-only, with
  optional view toggles set by an admin; per-account create rights are
  a VIP-edition feature, not available in this OSS version)
- Dashboard with QR/scan statistics and a 7-day activity chart
- CSRF protection, login rate limiting, session hardening, audit log
- Docker Compose setup, both a dev image and a production Nginx + PHP-FPM image

# What is included

- PHP 8.4 application source
- Database schema + migrations (applied automatically on every container start,
  so `git pull` + restart is enough to bring an existing install up to date)
- Docker Compose files (dev and production)
- CSS/JS assets

# Setup with Docker Compose

1. Clone this repository.
2. Copy `.env.example` to `.env` and set a real `DATABASE_PASSWORD` /
   `MYSQL_ROOT_PASSWORD`.
3. Start the stack:
   ```bash
   docker compose up -d --build
   ```
4. Open `http://localhost` and log in with `superadmin` / `superadmin`. You'll
   be required to set a new password on first login.

For a production deployment behind a reverse proxy, use
`docker-compose.prod.yml` (Nginx + PHP-FPM) instead of the dev stack.

# Credits

- Originally forked from [PHP Dynamic Qr code](https://github.com/giandonatoinverso/PHP-Dynamic-Qr-code)
  by Giandonato Inverso.
- QR code rendering powered by [chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode).
- Admin panel UI built on [AdminLTE](https://adminlte.io/).

# License

MIT - see [LICENSE](LICENSE).
