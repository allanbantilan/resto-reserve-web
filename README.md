# Restaurant Reservations

A WordPress restaurant website with an editable menu and a table reservation workflow. Guests can browse dishes, choose a date and time, and receive a booking reference. Staff manage reservations and menu items in WordPress Admin.

![Restaurant Reservations home page](docs/home-preview.webp)

## Features

- Responsive home, menu, about, and reservation pages
- Menu items with categories, prices, badges, and images
- Reservation validation for date, time, party size, and contact details
- Booking references, guest and administrator email notifications, and an admin booking list
- Local Docker environment with MariaDB, WordPress, Mailpit, and WP-CLI

## Local setup

Requires Docker Desktop with Compose and PowerShell 7. The default ports are `127.0.0.1:18087` for WordPress and `127.0.0.1:18088` for the local email inbox. Confirm those ports are free, then run:

```powershell
Get-NetTCPConnection -LocalPort 18087,18088 -State Listen -ErrorAction SilentlyContinue
$env:WP_ADMIN_PASSWORD = 'choose-a-long-unique-password'
.\setup.ps1
Remove-Item Env:WP_ADMIN_PASSWORD
```

On first run, the script creates a private `.env` with random database passwords, installs WordPress, activates the theme, configures Post name permalinks and the Manila timezone, and creates the site pages and initial menu items. Rerunning it preserves existing content, reservations, and administrator credentials.

Open [the local website](http://127.0.0.1:18087/) and sign in at `/wp-admin/` as `site_admin` with the password you supplied. An existing installation keeps its original administrator account. To use different ports, run `.\setup.ps1 -SitePort 18089 -MailpitPort 18090` with free port numbers.

The database and WordPress files persist in Docker volumes. `docker compose down` stops only this project and retains those volumes. `docker compose down -v` deletes them and all stored site data. Changing a password in `.env` does not rotate an existing database user's password.

## Site operations

| Task | WordPress location |
| --- | --- |
| Edit menu items, prices, categories, and images | **Menu dishes** |
| View reservations and guest details | **Reservations** |
| Release a booked slot | Move its reservation to Trash |

Reservations accept parties of 1–8, dates up to 60 days ahead, and bookings made at least two hours before the selected time. The current capacity setting allows ten reservations per 90-minute slot. Email sent by the local Docker environment is captured in [Mailpit](http://127.0.0.1:18088/).

## Verification

With Docker running, use Node.js and Python to run the included checks:

```powershell
node qa/reservation-smoke.mjs
python qa/chrome-mcp-check.py
```

The first check validates booking errors, confirmation, email capture, and cleanup. The Chrome DevTools MCP check covers navigation, the menu filter, mobile layout, and a browser booking. The local setup and deployment requirements are documented in the [setup guide](docs/setup-guide.pdf).

## Deployment requirements

The supplied Compose file is configured for local development: WordPress binds to `127.0.0.1`, and email goes to Mailpit. Before accepting reservations on a public server, configure a domain and HTTPS reverse proxy, real SMTP delivery, off-server backups, abuse protection, and table capacity rules that prevent concurrent overbooking. Replace the initial address, hours, menu content, and images, and publish a privacy notice and reservation contact policy.

The theme is in [`theme/`](theme/). Its generated hero image is stored locally; initial menu images and fonts use external services.
