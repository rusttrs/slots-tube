# Phase 0/1 — Infrastructure runbook (slots.tube) — DigitalOcean

## Live

| Item | Value |
|------|--------|
| Droplet | `slots-tube-prod` · 4 vCPU / 16 GB · AMS3 · `188.166.21.66` |
| App path | `/var/www/slots.tube` |
| Staging | https://staging.slots.tube |
| Admin | https://staging.slots.tube/admin |
| Production domain | `slots.tube` → GitHub Pages Coming Soon (until Phase 5) |
| SSH | `ssh -i ~/.ssh/slots_tube root@188.166.21.66` |
| Stack | Laravel 13 · Filament 5 · PHP 8.4 · PostgreSQL 16 · Redis · Nginx |
| Media | Cloudflare R2 `slots-tube-media` |
| Mail | Resend (`noreply@slots.tube`) |

## Secrets on server

- `/root/slots-tube-credentials.txt` — Postgres
- `/root/slots-tube-secrets/r2.env`
- `/root/slots-tube-secrets/resend.env`
- App `.env` at `/var/www/slots.tube/.env`

## Admin (change password ASAP)

- URL: https://staging.slots.tube/admin
- Email: `admin@slots.tube`
- Temp password: set at install — change immediately

## Phase status

- [x] Phase 0 infra
- [x] Laravel 13 + Filament 5 on staging
- [ ] i18n en/de/fr + entity migrations
- [ ] Layout from `prototype/`
- [ ] Public pages (Phase 2)
