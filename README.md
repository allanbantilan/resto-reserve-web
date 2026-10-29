# Restaurant Reservations Demo

A WordPress theme for a fictional casual restaurant. Visitors explore the menu, read the story, and reserve a table. WordPress stores reservations in a private post type for staff to manage.

![Restaurant Demo home page](docs/home-preview.webp)

The deployable theme is in `theme/`; Docker configuration and QA files stay outside WordPress's public theme directory.

## Run locally with Docker

This project uses its own Compose name and volumes, with no exposed database port. By default WordPress runs on **127.0.0.1:18087** and the local email inbox on **127.0.0.1:18088**. Check that both ports are free, then set an admin password and run the setup script:

```powershell
Get-NetTCPConnection -LocalPort 18087 -State Listen -ErrorAction SilentlyContinue
Get-NetTCPConnection -LocalPort 18088 -State Listen -ErrorAction SilentlyContinue
$env:WP_ADMIN_PASSWORD = 'choose-a-long-unique-password'
.\setup.ps1
Remove-Item Env:WP_ADMIN_PASSWORD
```

The script creates a local `.env` with random database passwords on first run, installs WordPress, activates **Restaurant Reservations Demo**, sets Post name permalinks and the Manila timezone, and creates Home, Menu, Reservations, and About pages. Seven editable demo dishes are seeded automatically. It is safe to rerun against an installed project; it does not reset the database or admin password.

Open `http://127.0.0.1:18087/`. On a fresh installation, sign in at `/wp-admin/` with the admin user `portfolio_admin` and the password you set. An existing installation keeps its original admin account. To use different free ports, run `.\setup.ps1 -SitePort 18089 -MailpitPort 18090`. An existing database keeps its original credentials in `.env`; changing those values alone does not rotate its users. See [docs/setup-guide.pdf](docs/setup-guide.pdf) for the full setup and deployment checklist.

To stop only this project, run `docker compose down` from this directory. The database and WordPress volumes are retained. Do not run `down -v` if you want to keep bookings.

## Reservation flow

Visit `/reservations/`. A guest chooses a date within 60 days, a 90-minute time slot from 11:00 AM to 8:00 PM, a party size from 1–8, and contact details. The server checks every value, requires at least two hours' notice, and reserves up to ten parties in each slot. It saves the booking, shows a reference, and sends email to the guest and site administrator through `wp_mail`.

Staff can view and manage bookings in **WordPress Admin → Reservations**. Move a reservation to Trash to release its slot. In Docker, emails are captured at `http://127.0.0.1:18088`. Configure reliable SMTP delivery before using the site in a real restaurant; the booking still saves if email delivery is unavailable.

Menu dishes are editable in **WordPress Admin → Menu dishes**. Set the title, excerpt, category, price, badge, and featured image. The image URL field is a fallback for the seeded demo dishes.

## Before real use

- Replace the fictional Makati address, hours, prices, and demo food photos.
- Match the ten-party slot limit to the real dining room and define a cancellation policy.
- Configure SMTP and test notification delivery.
- Use HTTPS and confirm the site's privacy policy covers reservation contact details.

The generated hero image is stored locally as WebP. Demo dish images load from Unsplash, and Google Fonts load externally.

Run `node qa/reservation-smoke.mjs` while the Docker site is running to check invalid-date rejection, successful booking, confirmation access, guest and admin emails, and cleanup.
Run `python qa/chrome-mcp-check.py` to repeat the Chrome DevTools MCP home, menu, mobile, and browser booking checks.

## Case study

**Problem:** A restaurant website needs to turn menu interest into a clear, low-friction table booking.

**Solution:** A focused reservation form with date, time, party size, contact details, immediate confirmation, and an admin list for staff.

**Features:** Mobile menu filters, restaurant story and hours, server-validated bookings, slot capacity, booking references, staff notifications, and editable menu dishes.

**Tools:** WordPress, PHP, CSS, JavaScript, Docker Compose, and a generated food photograph.

**Result:** The reservation workflow is implemented. Speed and email reliability still need verification on the deployed host.
