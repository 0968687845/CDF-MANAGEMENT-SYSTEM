# CDF Management System

A web-based management system for Constituency Development Fund (CDF) operations in Zambia. Built with PHP and MySQL, it supports multi-role access for administrators, field officers, and beneficiaries.

## Features

- **Role-based access** — Admin, Field Officer, and Beneficiary dashboards
- **Project management** — Create, track, and review CDF-funded projects
- **Financial tracking** — Expense logging and receipt uploads
- **Site visits** — Schedule, record, and map field visits with geolocation
- **Progress reporting** — Photo-based progress updates with ML-assisted analysis
- **Evaluation tools** — Compliance checks, quality assessments, and impact reports
- **Communication** — Internal messaging and notifications
- **Analytics dashboard** — System-wide reporting and data visualisation

## Requirements

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- A web server (Apache or Nginx) with `mod_rewrite` enabled

## Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/0968687845/CDF-MANAGEMENT-SYSTEM.git
   cd CDF-MANAGEMENT-SYSTEM
   ```

2. **Configure the application**
   ```bash
   cp config.example.php config.php
   ```
   Edit `config.php` and fill in your database credentials and API keys.

3. **Import the database**
   ```bash
   mysql -u root -p < database/cdf_management.sql
   ```

4. **Set upload permissions**
   ```bash
   chmod -R 755 uploads/
   ```

5. **Run database migrations** (if upgrading)

   Open these in your browser or run via CLI:
   - `database/migrations/migration_add_completed_at.php`
   - `database/migrations/migration_add_password_resets.php`

6. **Point your web server** document root to this directory and open the app in your browser.

## Directory Structure

```
├── admin/              Admin-only pages
├── analytics/          Reporting and analytics dashboard
├── api/                JSON API endpoints (geocoding, geolocation)
├── communication/      Messaging and notifications
├── database/           SQL schema, backups, and migrations
├── evaluation/         Compliance, quality, and impact evaluation
├── financial/          Expense management
├── includes/           Shared UI components
├── ml/                 ML-assisted progress and sentiment analysis
├── progress/           Progress update tracking
├── projects/           Project listing and request flow
├── settings/           System and profile settings
├── site-visits/        Field visit scheduling and mapping
├── support/            Help and support pages
├── uploads/            User-uploaded files (gitignored)
└── logs/               Application logs (gitignored)
```

## API Keys Required

- **Google Maps API** — for site-visit mapping (`GOOGLE_MAPS_API_KEY` in `config.php`)
- **IP Geolocation API** — for officer geolocation (`IP_GEOLOCATION_API_KEY` in `config.php`)

## License

See [LICENSE](LICENSE).
