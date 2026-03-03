# LeadIntel — B2B Lead Intelligence System

A full-stack B2B sales intelligence and outreach automation platform built with Laravel 12, Vue 3, and Inertia.js. The system discovers local businesses via Google Places, scores them with Claude AI, and automates email and SMS outreach — all from a clean admin dashboard.

---

## Features

### Lead Collection
- Discovers businesses automatically via the **Google Places API** based on configurable search categories and locations
- Stores business name, address, phone, website, rating, review count, and category
- Scheduled daily collection (1:00 AM) with a configurable per-day limit to control API costs
- Skips duplicate entries; updates existing lead data on re-discovery

### AI Lead Scoring
- Each lead is scored 1–10 by **Claude AI** (Anthropic) based on Google review count, rating, and business category
- Scores above a configurable threshold are flagged as high-priority
- Scoring runs automatically after collection

### Email Outreach
- Approved leads receive a personalised HTML outreach email
- Emails are dispatched via a **queue job** (`SendOutreachEmailJob`) with rate limiting to respect daily send limits
- Configurable sender signature (name, position, company, email, phone, WhatsApp)
- Email delivery scheduled daily (9:00 AM) for all approved and uncontacted leads
- Tracks per-lead outreach history in the `outreach_logs` table

### SMS Outreach
- One-click SMS to leads with valid mobile numbers (Ugandan prefixes: 077, 078, 076, 070, 075, 074)
- Powered by **EgoSMS HTTP API**
- Phone numbers are normalised to international format (`256XXXXXXXXX`) automatically
- SMS history logged alongside email history

### Follow-Up Reminders
- Configurable follow-up delay (days after outreach)
- Daily digest reminder email sent to a configured admin address listing all leads overdue for follow-up

### User Roles & Permissions
Powered by **Spatie Laravel Permission**:

| Role | Access |
|------|--------|
| **Super Admin** | Full access: manage users, settings, approve leads, send email/SMS, delete leads |
| **Manager** | Approve leads, send email and SMS, view logs |
| **Viewer** | Read-only: view leads and logs |

### User Management
- Super Admins can create, edit, and remove users from the `/users` page
- Role assignment at user creation or any time via edit
- Self-deletion is prevented

### API Token Management
- Every user can generate named personal access tokens from their Profile page
- Tokens are used for mobile app and external API integrations
- Individual token revocation; token value shown once on creation

### Admin Dashboard
- KPI cards: total leads, approved leads, contacted leads, average AI score
- Leads table with search, filters (high score, approved, contacted, has mobile, category), and sorting
- Outreach log with delivery status per lead
- All mutations (approve, email, SMS, delete) via **axios + SweetAlert2** — no page reloads

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Vue 3 (Composition API), Inertia.js v2 |
| Styling | Bootstrap 5.3.3, FontAwesome Free 5 |
| Database | MySQL |
| Queue / Cache / Session | Database driver |
| Auth | Laravel Breeze + Sanctum (SPA cookies + Bearer tokens) |
| Roles | Spatie Laravel Permission |
| AI Scoring | Anthropic Claude API |
| Lead Discovery | Google Places API |
| SMS | EgoSMS HTTP API |
| Alerts | SweetAlert2 (Bootstrap-styled) |
| Build | Vite 6 |

---

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- MySQL 8+
- A running queue worker (for email dispatch)
- API keys: Google Places, Anthropic (Claude), EgoSMS

---

## Installation

### 1. Clone and install dependencies

```bash
git clone <repo-url> lead-intelligence-system
cd lead-intelligence-system

composer install
npm install --legacy-peer-deps
```

### 2. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and fill in:

```env
APP_URL=http://127.0.0.1:8087

DB_DATABASE=lead_intelligence_system
DB_USERNAME=root
DB_PASSWORD=your_password

GOOGLE_PLACES_API_KEY=your_key
ANTHROPIC_API_KEY=your_key

MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=no-reply@yourcompany.com
MAIL_FROM_NAME="LeadIntel"

EGOSMS_API_URL=https://www.egosms.co/api/v1/plain/
EGOSMS_SENDER=YourSender
EGOSMS_USERNAME=your_username
EGOSMS_PASSWORD=your_password

SANCTUM_STATEFUL_DOMAINS=127.0.0.1:8087,localhost
```

### 3. Database setup

```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=RolesAndPermissionsSeeder
```

### 4. Build assets

```bash
npm run build
```

### 5. Start the application

```bash
# Web server
php artisan serve --port=8087

# Queue worker (separate terminal — required for email dispatch)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600

# Scheduler (separate terminal, or configure as a cron job)
php artisan schedule:work
```

**Default admin credentials:**
- Email: `admin@leadintel.local`
- Password: `password`

> Change these immediately after first login via **My Profile → Change Password**.

---

## Cron Job (Production)

Add this single entry to your server's crontab:

```cron
* * * * * cd /path/to/lead-intelligence-system && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler handles:
- `1:00 AM` — collect new leads (`leads:collect`)
- `9:00 AM` — send approved outreach emails (`leads:send-approved`)
- `8:00 AM` — send follow-up reminder digest

---

## Route Architecture

| File | Purpose |
|------|---------|
| `routes/web.php` | View rendering only — Inertia page responses and Breeze auth forms |
| `routes/api.php` | All JSON endpoints — lead actions, settings, user CRUD, auth tokens |

API routes are protected by `auth:sanctum`. The web SPA authenticates via session cookies (Sanctum stateful). Mobile clients authenticate via Bearer tokens issued from `POST /api/auth/login`.

### Key API Endpoints

```
POST   /api/auth/login                 Issue Bearer token (mobile login)
POST   /api/auth/logout                Revoke current token
GET    /api/auth/me                    Current user info
GET    /api/auth/tokens                List personal access tokens
POST   /api/auth/tokens                Create named token
DELETE /api/auth/tokens/{id}           Revoke token

PATCH  /api/leads/{lead}/approve       Toggle lead approval
POST   /api/leads/{lead}/send-email    Queue outreach email
POST   /api/leads/{lead}/send-sms      Send SMS
DELETE /api/leads/{lead}               Delete lead (super_admin only)

POST   /api/settings                   Save system settings (super_admin only)
POST   /api/users                      Create user (super_admin only)
PUT    /api/users/{user}               Update user (super_admin only)
DELETE /api/users/{user}               Remove user (super_admin only)
```

---

## Project Structure

```
app/
├── Console/Commands/
│   ├── CollectLeads.php            # Google Places scraper
│   └── SendFollowUpReminders.php   # Follow-up digest emails
├── Http/Controllers/
│   ├── Api/                        # JSON API controllers
│   │   ├── AuthController.php      # Token auth (mobile)
│   │   ├── LeadController.php      # Lead mutations
│   │   ├── SettingController.php   # Settings update
│   │   └── UserController.php      # User CRUD
│   ├── DashboardController.php
│   ├── LeadController.php          # Leads index page
│   ├── LogController.php
│   ├── SettingController.php       # Settings page
│   └── UserController.php          # Users page
├── Helpers/PhoneHelper.php         # Mobile number normalisation (Uganda)
├── Jobs/SendOutreachEmailJob.php   # Queued, rate-limited email dispatch
├── Models/
│   ├── Lead.php
│   ├── OutreachLog.php
│   ├── Setting.php
│   └── User.php
└── Services/
    ├── GooglePlacesService.php     # Places API wrapper
    ├── LeadScoringService.php      # Claude AI scoring
    ├── OutreachService.php         # Email HTML builder
    └── SmsService.php              # EgoSMS API wrapper

resources/js/
├── Layouts/AdminLayout.vue         # Sidebar + top navbar
├── Pages/
│   ├── Dashboard/Index.vue
│   ├── Leads/Index.vue             # Main leads table
│   ├── Logs/Index.vue
│   ├── Profile/Edit.vue            # Profile + token management
│   ├── Settings/Index.vue
│   └── Users/Index.vue             # User management (super_admin)
└── app.js                          # Vue app bootstrap + global directives
```

---

## Configuration

System settings are managed from the **Settings** page (super_admin only) and stored in the `settings` table:

| Setting | Description |
|---------|-------------|
| `daily_leads_limit` | Max new leads to collect per day (controls API cost) |
| `daily_email_limit` | Max outreach emails to send per day |
| `min_ai_score` | Minimum AI score to be eligible for outreach |
| `search_categories` | Business categories to search (e.g. "pharmacies Kampala") |
| `follow_up_days` | Days after outreach before follow-up reminder triggers |
| `follow_up_notification_email` | Email address for follow-up digest |
| Signature fields | Sender name, position, company details used in email footer |

---

## License

Proprietary. All rights reserved.
