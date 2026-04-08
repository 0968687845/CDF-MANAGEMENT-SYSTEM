# CDF Management System — Full Technical Documentation
### Reference Document for Enterprise Rebuild

**Version:** 1.0 (School Project Analysis)
**Date:** 2026-04-08
**Purpose:** Complete system documentation to serve as the definitive reference for rebuilding this system at enterprise/government grade.

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Business Context](#2-business-context)
3. [User Roles & Permissions](#3-user-roles--permissions)
4. [Feature Inventory](#4-feature-inventory)
5. [Data Models](#5-data-models)
6. [Business Rules & Workflows](#6-business-rules--workflows)
7. [Current Architecture](#7-current-architecture)
8. [Known Weaknesses in the Current Build](#8-known-weaknesses-in-the-current-build)
9. [Enterprise Rebuild Recommendations](#9-enterprise-rebuild-recommendations)
10. [Recommended Technology Stack](#10-recommended-technology-stack)

---

## 1. System Overview

The **Constituency Development Fund (CDF) Management System** is a web-based platform for administering government development fund projects at the constituency level. It connects three stakeholder groups — administrators, field officers, and community beneficiaries — on a single platform that manages the full project lifecycle from application through completion and evaluation.

The system handles:
- Beneficiary registration and identity verification (NRC-based)
- Project application, approval, and assignment
- Financial tracking (budgets, disbursements, expenses)
- Field officer site visits and progress reporting
- Multi-dimensional evaluations and compliance assessments
- Internal communication and notification
- System-wide configuration and audit

---

## 2. Business Context

### What is the CDF?

The Constituency Development Fund is a Zambian government program that allocates funds to each of the country's 156 constituencies for community-driven development projects. Projects typically include infrastructure (roads, boreholes, school buildings), women empowerment programs, and youth empowerment initiatives.

### Administrative Chain

```
Central Government (Fund Allocation)
        ↓
Constituency Office (CDF Administrator)
        ↓
Field Officers (Project Oversight)
        ↓
Beneficiaries (Community Groups / Individuals)
```

### Key Operational Constraints

- Beneficiaries must be verified Zambian citizens with a valid NRC (National Registration Card)
- Projects require pre-approval with documented community consent, environmental compliance, and land ownership confirmation
- All financial expenditures must be tracked against approved budgets
- Field officers must conduct scheduled site visits and submit evaluations
- Reports must be available for government audit at any time

---

## 3. User Roles & Permissions

The system implements three fixed roles. There is no role hierarchy beyond this — admins cannot escalate to super-admin within the current design.

### 3.1 Admin

The constituency-level administrator. Has full system access.

| Capability | Details |
|---|---|
| User management | Create, edit, deactivate, delete all users |
| Project management | View, approve, reject, assign, delete all projects |
| Officer assignment | Assign field officers to specific projects |
| Financial oversight | View all expenses across all projects |
| Evaluation review | View all evaluations and compliance reports |
| Notifications | Send and receive system notifications |
| System settings | Configure all system-wide settings |
| Beneficiary approval | Approve or reject pending beneficiary registrations |

### 3.2 Officer (Field Officer)

A government-employed field officer assigned to monitor specific projects.

| Capability | Details |
|---|---|
| Project view | View only their assigned projects |
| Progress updates | Submit progress updates with photos and receipts |
| Site visits | Schedule and log site visits (with GPS coordinates) |
| Evaluations | Submit compliance checks, quality assessments, impact assessments, progress reviews |
| Expenses | Log project expenses with receipts |
| Communication | Send messages to admin and beneficiaries |
| Notifications | Receive project-related notifications |

### 3.3 Beneficiary

A community member or group receiving CDF-funded support.

| Capability | Details |
|---|---|
| Registration | Self-register; account requires admin approval before activation |
| Project submission | Submit new project applications |
| Project tracking | View status and progress of their own projects |
| Financial view | View budget and expense summaries for their projects |
| Communication | Receive notifications; view messages |
| Profile management | Update personal profile and preferences |

### 3.4 Registration & Account Lifecycle

```
Beneficiary registers (status: pending)
        ↓
Admin receives notification
        ↓
Admin approves or rejects
        ↓
If approved: status → active, user can log in
If rejected: account remains inactive

Officers and admins: register → immediately active (no approval gate — this is a gap)
```

---

## 4. Feature Inventory

### Module 1: Authentication & Security

- Username + password login with bcrypt hashing
- Session-based authentication
- CSRF protection on all forms (session token + `hash_equals()` comparison)
- Account lockout after 5 failed login attempts (locked for 15 minutes)
- Password reset via email token (SHA-256 hashed token in DB, 1-hour expiry)
- Rate limiting on password reset: max 3 requests per email per 15 minutes
- Role-based access control enforced on every page via `requireRole()`
- Session timeout configurable in system settings (default: 60 minutes)
- Two-factor auth setting exists in UI but is not implemented in code

### Module 2: User Management (Admin)

- List all users with role and status filters
- Create users of any role directly (admin-created accounts bypass approval)
- Edit user profiles: name, email, phone, department, position
- Activate / deactivate accounts
- Delete users (with self-deletion prevention)
- Auto-generate usernames from first + last name with collision handling
- View user activity logs

### Module 3: Project Management

#### Project Application (Beneficiary)
Beneficiaries submit a project application with the following fields:
- Title, description, category
- Location, constituency, ward
- Budget amount
- Start and end dates
- Funding source
- Budget breakdown (text)
- Required materials
- Human resources needed
- Stakeholders
- Pre-qualification checklist:
  - Community approval (boolean)
  - Environmental compliance (boolean)
  - Land ownership confirmed (boolean)
  - Technical feasibility confirmed (boolean)
  - Budget approval confirmed (boolean)
- Additional notes

Initial status on submission: `planning`

#### Project Lifecycle States

```
planning → in-progress → completed
                ↓
            cancelled (at any stage)
```

Approval status is tracked separately:
```
pending → approved / rejected
```

#### Project Fields (full list from schema)

| Field | Type | Description |
|---|---|---|
| title / name | VARCHAR | Project name (two columns exist — schema inconsistency) |
| description | TEXT | Full project description |
| beneficiary_id | INT | Owning beneficiary |
| officer_id / assigned_officer_id | INT | Assigned field officer (two columns — schema inconsistency) |
| constituency | VARCHAR | Geographical constituency |
| category | VARCHAR | Project category (infrastructure, empowerment, etc.) |
| funding_source | VARCHAR | Source of funding |
| budget | DECIMAL(15,2) | Approved budget in ZMW |
| total_expenses | DECIMAL(15,2) | Running sum of logged expenses |
| budget_utilization | DECIMAL(5,2) | Percentage of budget spent |
| progress | INT(3) | Completion percentage 0–100 |
| status | VARCHAR | planning / in-progress / completed / cancelled |
| approval_status | VARCHAR | pending / approved / rejected |
| start_date / end_date | DATE | Planned dates |
| actual_start_date / actual_end_date | DATE | Real dates |
| estimated_duration_days | INT | Duration estimate |
| estimated_completion_date | DATE | Projected completion |
| overall_compliance | DECIMAL(5,2) | Rolled-up compliance score |
| financial_compliance | DECIMAL(5,2) | Financial compliance sub-score |
| timeline_compliance | DECIMAL(5,2) | Timeline compliance sub-score |
| quality_compliance | DECIMAL(5,2) | Quality compliance sub-score |
| milestones | TEXT | Milestone definitions |
| community_approval | TINYINT | Pre-qualification: community sign-off |
| environmental_compliance | TINYINT | Pre-qualification: environmental sign-off |
| land_ownership | TINYINT | Pre-qualification: land confirmed |
| technical_feasibility | TINYINT | Pre-qualification: technical sign-off |
| budget_approval | TINYINT | Pre-qualification: budget sign-off |

### Module 4: Progress Tracking

Field officers submit progress updates against assigned projects.

**Progress update fields:**
- Progress percentage (0–100, replaces previous value on projects table)
- Description of work done
- Challenges encountered
- Next steps planned
- Achievements (JSON array)
- Photos (JSON array of file paths, max upload enforced)
- Receipt path (single file attachment)

Progress updates are append-only (historical log in `project_progress` table). The project's `progress` column is updated to reflect the latest percentage.

### Module 5: Financial Management

#### Expense Logging

Officers log individual expenses against projects:

| Field | Description |
|---|---|
| amount | Expense amount in ZMW |
| category | Labour / Materials / Equipment / Transport / Other |
| description | What the expense was for |
| expense_date | Date incurred |
| receipt_number | Physical receipt reference |
| vendor | Supplier name |
| payment_method | Cash / Bank transfer / Mobile money |
| notes | Additional notes |
| receipt_path | Scanned receipt file |
| resource_photos | Photos of purchased resources |

When an expense is saved, the project's `total_expenses` and `budget_utilization` are recalculated automatically.

#### Financial Dashboard (Admin)
- Total budget across all projects
- Total disbursed vs. total spent
- Budget utilization percentage per project
- Expense breakdown by category
- Over-budget alerts

### Module 6: Site Visits

Officers schedule and log physical site visits to project locations.

**Site visit fields:**
- Project (FK)
- Visit date and time
- Location (text description)
- GPS coordinates (latitude / longitude, from geocoding API)
- Purpose / agenda
- Status: scheduled / completed / cancelled

**Geocoding:** A dedicated `api/geocode.php` endpoint calls an external IP Geolocation API to convert address strings to coordinates. Results are cached server-side (outside webroot, `0700` permissions) to reduce API calls.

### Module 7: Evaluations & Compliance

This is the most complex module. Officers can submit five distinct assessment types against any project:

#### 7.1 General Evaluation (`evaluations` table)
Scored dimensions:
- Compliance score
- Budget compliance
- Timeline compliance
- Quality score
- Documentation score
- Community impact score
- Overall score (composite)

Plus: findings (text), recommendations (text), evaluation type, evaluation date, status.

#### 7.2 Compliance Check (`compliance_checks` table)
Eight scored dimensions (0–100):
- Budget compliance
- Timeline compliance
- Documentation compliance
- Quality standards
- Community engagement
- Environmental compliance
- Procurement compliance
- Safety standards
- Overall compliance (composite)

Plus: findings, recommendations, next audit date.

#### 7.3 Quality Assessment (`quality_assessments` table)
Five scored dimensions:
- Workmanship score
- Material quality
- Safety standards
- Completion quality
- Overall quality

Plus: strengths, improvement areas, recommendations.

#### 7.4 Quality Evaluation (`quality_evaluations` table)
Five scored dimensions:
- Quality score
- Workmanship score
- Materials score
- Safety score
- Compliance score
- Overall score

Plus: comments, recommendations, status, evaluation date.

*(Note: `quality_assessments` and `quality_evaluations` are redundant — both capture quality scoring. This should be consolidated in the enterprise version.)*

#### 7.5 Impact Assessment (`impact_assessments` table)
Seven scored dimensions:
- Community beneficiaries (count)
- Employment generated (count)
- Economic impact (score)
- Social impact (score)
- Environmental impact (score)
- Sustainability score
- Overall impact

Plus: success stories, challenges, recommendations.

#### 7.6 Progress Review (`progress_reviews` table)
Four scored dimensions:
- Progress score
- Timeline adherence
- Quality rating
- Resource utilization

Plus: challenges, recommendations, review date, next review date, status.

#### Evaluation Statistics (Officer Dashboard)
- Total evaluations submitted
- Evaluations completed this month
- Average compliance rate
- Pending reviews
- Filterable by: evaluation type, date range (today / week / month / quarter / year), status, project

### Module 8: Communication

#### In-App Notifications
- Created automatically by the system on key events (project created, progress updated, account approved, etc.)
- Per-user notification inbox with unread count badge
- Actions: mark as read, mark all as read, delete, clear all
- Notification type classification (urgent, success, warning, info) based on keyword matching in title/message

**Automatic notification triggers:**
- Beneficiary account approved/rejected
- Project created (notifies beneficiary)
- Project approved/rejected (notifies beneficiary)
- Progress update submitted (notifies admin)
- Officer assigned to project (notifies officer)

#### Direct Messages
- One-to-one messaging between users
- Subject line + message body
- Urgent flag
- Read/unread state
- Officers can message admin and their assigned beneficiaries
- Full conversation thread view

### Module 9: System Settings

Settings are stored as key-value pairs in `system_settings` table, grouped into four categories:

#### General Settings
| Key | Type | Default |
|---|---|---|
| system_name | string | CDF Management System |
| system_email | string | noreply@cdf.gov.zm |
| admin_email | string | admin@cdf.gov.zm |
| timezone | string | Africa/Lusaka |
| date_format | string | Y-m-d |
| items_per_page | integer | 10 |
| maintenance_mode | boolean | false |

#### Notification Settings
| Key | Type | Default |
|---|---|---|
| email_notifications | boolean | true |
| project_approvals | boolean | true |
| officer_assignments | boolean | true |
| budget_alerts | boolean | true |
| system_updates | boolean | true |

#### Security Settings
| Key | Type | Default |
|---|---|---|
| password_policy | string | medium |
| session_timeout | integer | 60 (minutes) |
| max_login_attempts | integer | 5 |
| two_factor_auth | boolean | false |
| ip_whitelist | text | (empty) |

#### Backup Settings
| Key | Type | Default |
|---|---|---|
| auto_backup | boolean | true |
| backup_frequency | string | daily |
| backup_retention | integer | 30 (days) |
| backup_email | string | backups@cdf.gov.zm |
| last_backup | string | Never |

### Module 10: User Preferences

Each user has a personal settings record in `user_settings`:
- Email notifications (on/off)
- SMS notifications (on/off)
- Push notifications (on/off)
- Project update alerts (on/off)
- Message alerts (on/off)
- Deadline reminders (on/off)
- Profile visibility (public / private)
- Location sharing (on/off)
- Data collection consent (on/off)

### Module 11: Analytics & Reporting

Role-specific dashboards with the following metrics:

**Admin Dashboard:**
- Total projects (all statuses)
- Total active beneficiaries
- Total active officers
- Total budget across all projects
- Recent activity feed
- Project status distribution

**Officer Dashboard:**
- Assigned projects count
- Completed projects count
- Site visits this month
- Average completion rate across assigned projects
- Recent evaluations list

**Beneficiary Dashboard:**
- Total projects submitted
- Completed projects
- Active (in-progress) projects
- Average progress percentage
- Total budget allocated
- Pending tasks count
- Completion rate percentage

---

## 5. Data Models

### Entity Relationship Overview

```
users (1) ──< projects (beneficiary_id)
users (1) ──< projects (officer_id)
projects (1) ──< project_progress
projects (1) ──< project_expenses
projects (1) ──< evaluations
projects (1) ──< compliance_checks
projects (1) ──< quality_assessments
projects (1) ──< quality_evaluations
projects (1) ──< impact_assessments
projects (1) ──< progress_reviews
projects (1) ──< site_visits
users (1) ──< notifications
users (1) ──< messages (sender_id)
users (1) ──< messages (recipient_id)
users (1) ──< activity_log
users (1) ──< beneficiary_groups (owner_user_id)
beneficiary_groups (1) ──< group_members
users (1) ──< user_settings (1)
users (1) ──< password_resets
```

### Table: `users`

| Column | Type | Notes |
|---|---|---|
| id | INT PK | Auto-increment |
| username | VARCHAR(50) UNIQUE | Auto-generated if not provided (first initial + last name) |
| password | VARCHAR(255) | bcrypt hash |
| email | VARCHAR(100) UNIQUE | |
| phone | VARCHAR(15) | |
| first_name | VARCHAR(100) | |
| last_name | VARCHAR(100) | |
| nrc | VARCHAR(20) | Zambian National Registration Card — format: `123456/78/9` |
| dob | DATE | Date of birth |
| gender | VARCHAR(20) | |
| role | ENUM | admin / officer / beneficiary |
| department | VARCHAR(100) | For admin/officer |
| employee_id | VARCHAR(50) | For admin/officer |
| position | VARCHAR(100) | Job title for admin/officer |
| constituency | VARCHAR(100) | For beneficiary |
| ward | VARCHAR(100) | Sub-constituency unit |
| village | VARCHAR(100) | |
| profile_picture | VARCHAR(500) | File path |
| street | VARCHAR(255) | Address |
| marital_status | VARCHAR(50) | |
| project_type | VARCHAR(100) | Intended project category at registration |
| project_description | TEXT | Brief description at registration |
| status | ENUM | active / inactive / pending |
| last_login | TIMESTAMP | |
| meta | JSON | Flexible metadata |
| preferences | JSON | User preferences (legacy; superseded by user_settings table) |
| login_attempts | INT | Incremented on failed login; reset on success |
| account_locked_until | TIMESTAMP | Set to NOW()+15min when attempts >= max |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### Table: `projects`

See Section 4, Module 3 for the full field list. Key design notes:

- **Dual officer columns:** Both `officer_id` and `assigned_officer_id` exist — these are redundant. The enterprise rebuild should use one.
- **Dual title columns:** Both `title` and `name` exist — these are redundant. Use `title`.
- **Compliance scores** are stored directly on the project row as rolled-up averages, updated when evaluations are submitted.
- **Budget utilization** is a computed column (total_expenses / budget × 100) that should be a computed/virtual column in the enterprise schema, not a stored float subject to sync issues.

### Table: `beneficiary_groups`

For group registrations (e.g., a women's cooperative applying as a single beneficiary entity).

| Column | Type | Notes |
|---|---|---|
| id | INT PK | |
| group_name | VARCHAR(255) | |
| owner_user_id | INT FK → users | The registered account representing the group |

### Table: `group_members`

Individual members of a beneficiary group.

| Column | Type | Notes |
|---|---|---|
| id | INT PK | |
| group_id | INT FK → beneficiary_groups | |
| member_name | VARCHAR(255) | Full name |
| member_phone | VARCHAR(20) | |
| member_nrc | VARCHAR(50) | NRC validated at registration |

### Table: `project_progress`

Append-only log of progress updates.

| Column | Type | Notes |
|---|---|---|
| id | INT PK | |
| project_id | INT FK → projects | |
| progress_percentage | INT | 0–100 |
| description | TEXT | Work description |
| challenges | TEXT | |
| next_steps | TEXT | |
| photos | TEXT | JSON array of file paths |
| receipt_path | VARCHAR(500) | Single receipt file |
| achievements | TEXT | JSON array of achievement strings |
| created_by | INT FK → users | Officer who submitted |
| created_at | TIMESTAMP | |

### Table: `project_expenses`

| Column | Type | Notes |
|---|---|---|
| id | INT PK | |
| project_id | INT FK → projects | |
| amount | DECIMAL(15,2) | ZMW |
| category | VARCHAR(100) | Labour / Materials / Equipment / Transport / Other |
| description | TEXT | |
| expense_date | DATE | |
| receipt_number | VARCHAR(100) | Physical receipt reference |
| vendor | VARCHAR(255) | |
| payment_method | VARCHAR(100) | Cash / Bank transfer / Mobile money |
| notes | TEXT | |
| created_by | INT FK → users | |
| receipt_path | VARCHAR(500) | Scanned receipt |
| resource_photos | TEXT | JSON array of photo paths |

### Table: `site_visits`

| Column | Type | Notes |
|---|---|---|
| id | INT PK | |
| project_id | INT FK → projects | |
| officer_id | INT FK → users | |
| visit_date | DATE | |
| visit_time | TIME | |
| location | VARCHAR(255) | Address string |
| latitude | DECIMAL(10,8) | From geocoding API |
| longitude | DECIMAL(11,8) | From geocoding API |
| purpose | TEXT | Agenda/reason |
| status | VARCHAR(20) | scheduled / completed / cancelled |

### Table: `evaluations`

General evaluation — see Section 4, Module 7.1 for scored dimensions.

### Table: `compliance_checks`

Eight-dimension compliance audit — see Section 4, Module 7.2.

### Table: `quality_assessments`

See Section 4, Module 7.3.

### Table: `quality_evaluations`

See Section 4, Module 7.4. Note: overlaps significantly with `quality_assessments`.

### Table: `impact_assessments`

See Section 4, Module 7.5.

### Table: `progress_reviews`

See Section 4, Module 7.6.

### Table: `notifications`

| Column | Type | Notes |
|---|---|---|
| id | INT PK | |
| user_id | INT FK → users | Recipient |
| title | VARCHAR(255) | |
| message | TEXT | |
| is_read | TINYINT(1) | 0 = unread, 1 = read |
| created_at | TIMESTAMP | |

### Table: `messages`

| Column | Type | Notes |
|---|---|---|
| id | INT PK | |
| sender_id | INT FK → users | |
| recipient_id | INT FK → users | |
| subject | VARCHAR(255) | |
| message | TEXT | |
| is_urgent | TINYINT(1) | |
| is_read | TINYINT(1) | |
| created_at | TIMESTAMP | |

### Table: `activity_log`

| Column | Type | Notes |
|---|---|---|
| id | INT PK | |
| user_id | INT FK → users | |
| action | VARCHAR(100) | Action code e.g. `project_creation` |
| description | TEXT | Human-readable detail |
| ip_address | VARCHAR(45) | IPv4 or IPv6 |
| created_at | TIMESTAMP | |

### Table: `password_resets`

| Column | Type | Notes |
|---|---|---|
| id | INT PK | |
| user_id | INT FK → users | |
| email | VARCHAR(255) | |
| token | VARCHAR(64) UNIQUE | SHA-256 hash of the raw token sent in the email link |
| created_at | TIMESTAMP | |
| expires_at | TIMESTAMP | 1 hour after creation |
| used_at | TIMESTAMP | Populated when consumed |
| is_used | BOOLEAN | Prevents token reuse |

### Table: `system_settings`

Key-value store. See Section 4, Module 9.

### Table: `user_settings`

Per-user preference flags. See Section 4, Module 10.

---

## 6. Business Rules & Workflows

### 6.1 Project Approval Workflow

```
1. Beneficiary submits project → status: planning, approval_status: pending
2. System notifies admin
3. Admin reviews pre-qualification checklist and project details
4. Admin approves → approval_status: approved, notification sent to beneficiary
   OR
   Admin rejects → approval_status: rejected, notification sent to beneficiary
5. Admin assigns officer to approved project → officer notified
6. Officer begins field work → progress updates move status to in-progress
7. Officer submits 100% progress update → admin marks completed
```

### 6.2 Beneficiary Registration Workflow

```
1. Beneficiary completes registration form (name, NRC, contact, constituency, ward, role)
2. Optional: register as group (provide group name + at least 1 member with NRC)
3. NRC validated: format must be 123456/78/9
4. Account created with status: pending
5. Admin receives notification
6. Admin approves → status: active, beneficiary can log in
   OR
   Admin rejects → account remains inactive
```

### 6.3 Login & Account Lockout

```
1. User submits username + password
2. System checks account_locked_until — if in future, return lockout message with remaining minutes
3. If not locked, verify password hash
4. On success: reset login_attempts = 0, account_locked_until = NULL, record last_login
5. On failure: increment login_attempts
   If login_attempts >= max_login_attempts (default 5):
     Set account_locked_until = NOW() + 15 minutes
6. Return appropriate error message
```

### 6.4 Password Reset Workflow

```
1. User submits email address
2. Rate limit check: if >= 3 resets requested for this email in last 15 min → reject
3. If email exists: generate 32-byte random token, hash with SHA-256, store hash in DB
4. Send raw token in reset link via email (link valid for 1 hour)
5. [Regardless of email existence: show generic "check your email" message]
6. User clicks link → system extracts raw token, hashes it, looks up in DB
7. Validate: token exists, not used, not expired, user account active
8. User submits new password
9. CSRF token validated
10. New password hashed and saved; token marked used_at + is_used = true
```

### 6.5 Budget & Expense Tracking

```
1. Admin approves project budget
2. Officer logs expenses as work progresses
3. On each expense save:
   - project.total_expenses = SUM(all expenses for project)
   - project.budget_utilization = (total_expenses / budget) * 100
4. Dashboard shows utilization % and remaining balance
5. Budget alerts trigger (notification) when utilization exceeds threshold (configurable)
```

### 6.6 NRC Validation Rule

Format: `NNNNNN/NN/N` where:
- First segment: exactly 6 digits
- Second segment: exactly 2 digits
- Third segment: exactly 1 digit

Regex: `/^\d{6}\/\d{2}\/\d$/`

---

## 7. Current Architecture

### Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.x |
| Database | MySQL 9.x |
| Web Server | Apache / Nginx (PHP built-in server for dev) |
| DB Access | PDO with prepared statements |
| Frontend | HTML + CSS (Bootstrap) + vanilla JavaScript |
| Maps/Geo | Google Maps API + IP Geolocation API |
| File Storage | Local filesystem (`uploads/` directory) |
| Session | PHP native sessions |
| Auth | Custom session-based auth |
| Email | Not implemented (stubbed) |

### Directory Structure

```
/
├── functions.php              # Entry point — bootstraps DB and loads modules
├── config.php                 # DB credentials, API keys, APP_ENV (gitignored)
├── config.example.php         # Template for config.php
├── db.php                     # Database PDO wrapper class
├── auth.php                   # Central POST handler for login + registration
├── login.php                  # Login page view
├── forgot_password.php        # Password reset request page
├── reset_password.php         # Password reset submission page
├── admin_register.php         # Admin registration form
├── officer_register.php       # Officer registration form
├── beneficiary_register.php   # Beneficiary registration form
│
├── includes/                  # Domain function modules (split from monolith)
│   ├── auth.functions.php     # Auth, CSRF, session, login, password reset
│   ├── user.functions.php     # User CRUD, registration
│   ├── project.functions.php  # Project lifecycle, progress, expenses
│   ├── communication.functions.php  # Notifications, messages
│   ├── evaluation.functions.php     # Evaluations, compliance, quality, analytics
│   ├── settings.functions.php       # System settings, backup, cache
│   ├── utils.functions.php          # Sanitize, validate, format, logging
│   └── misc.functions.php           # Remaining helpers
│
├── admin/                     # Admin-only pages
│   ├── dashboard.php
│   ├── users.php
│   ├── projects.php
│   ├── assignments.php
│   ├── project_expenses.php
│   ├── notifications.php
│   └── settings.php
│
├── officer/                   # Officer-only pages
│   └── dashboard.php
│
├── beneficiary/               # Beneficiary-only pages
│   └── dashboard.php (beneficiary_dashboard.php at root)
│
├── progress/                  # Progress update pages
│   └── updates.php
│
├── financial/                 # Financial pages
│   └── expenses.php
│
├── evaluation/                # Evaluation pages
│   └── compliance.php
│
├── site-visits/               # Site visit pages
│   └── schedule.php
│
├── communication/             # Messaging pages
│   └── send_message.php
│
├── settings/                  # User settings pages
│   ├── profile.php
│   └── settings.php
│
├── api/                       # API endpoints
│   └── geocode.php            # Geocoding proxy with server-side cache
│
├── database/
│   └── schema.sql             # Complete DB schema (all 19 tables)
│
├── uploads/                   # User-uploaded files (gitignored content)
│   ├── profiles/
│   ├── progress/
│   └── receipts/
│
├── logs/                      # Application logs
│   └── emails/
│
└── storage/                   # Outside-webroot storage (geocode cache)
    └── cache/geocoding/
```

### How a Page Request Works

```
Browser request → page.php
  → require_once 'functions.php'
      → require_once 'config.php'       (credentials, APP_ENV)
      → require_once 'db.php'           (creates $database, exposes $pdo globally)
      → require_once 'includes/*.php'   (loads all domain functions)
  → requireRole('admin')                (exits if session role doesn't match)
  → if POST: validate CSRF token
  → handle action (read GET/POST, call domain functions, redirect or render)
  → render HTML (PHP mixed with HTML template)
```

---

## 8. Known Weaknesses in the Current Build

These are documented for the enterprise rebuild team to understand what **not** to replicate.

### Critical (already patched in this version)
- ~~No CSRF protection~~ — fixed
- ~~No rate limiting on login~~ — fixed
- ~~Password reset tokens stored in plaintext~~ — fixed (now SHA-256 hashed)
- ~~`display_errors = 1` hardcoded~~ — fixed (now APP_ENV controlled)
- ~~Beneficiary accounts auto-activated on registration~~ — fixed (now pending)

### Still Present — Must Not Carry Forward

| Issue | Impact |
|---|---|
| No HTTP security headers (CSP, X-Frame-Options, HSTS, etc.) | XSS, clickjacking, MITM exposure |
| No HTTPS enforcement | Credentials transmitted in plaintext over HTTP |
| Session cookie has no `Secure` or `HttpOnly` flags explicitly set | Session hijacking risk |
| `geocode.php` redefines `sanitize()` which is already loaded — causes fatal error in production | Runtime crash |
| File uploads not validated by MIME type — only by extension | Malicious file upload |
| 2FA setting in UI is a placeholder — does nothing | False security assurance |
| Email is never actually sent — all email calls are stubbed | Password resets, notifications silently fail |
| Backup function hardcodes `root` with empty password, silently simulates success on failure | False assurance of backups |
| `clearSystemCache()` still points to webroot `../cache/` instead of `storage/` | Cache path mismatch |
| No automated tests | Regressions undetectable |
| No schema migrations system | Cannot safely evolve the database |
| `quality_assessments` and `quality_evaluations` are redundant tables | Data inconsistency |
| `officer_id` and `assigned_officer_id` both exist on projects | Ambiguity about which is authoritative |
| `title` and `name` both exist on projects | Redundancy |
| `preferences` JSON column on users overlaps with `user_settings` table | Two sources of truth |
| `budget_utilization` stored as a column instead of computed | Can drift out of sync with actual expenses |
| No dependency management (no Composer) | Security patches for libraries must be manual |
| Global `$pdo` passed via `global $pdo` in every function | Anti-pattern; impossible to unit test |
| Mixed HTML and PHP logic in all page files | No separation of concerns |

---

## 9. Enterprise Rebuild Recommendations

### Architecture Pattern: MVC + Service Layer

Do not mix SQL queries into page files. Use a layered architecture:

```
HTTP Request
    ↓
Router (maps URL to controller)
    ↓
Controller (validates input, calls service, returns response)
    ↓
Service Layer (business rules — project approval logic, budget calculations, etc.)
    ↓
Repository / ORM (database access only)
    ↓
Database
```

### Database Design Fixes

1. **Consolidate evaluation tables.** Merge `quality_assessments` and `quality_evaluations` into one. Create a single `evaluations` table with a `type` discriminator column and a flexible `scores` JSON column for type-specific dimensions.
2. **Remove redundant columns.** One title column on projects (`title`), one officer FK (`officer_id`), remove legacy `name` and `assigned_officer_id`.
3. **Make `budget_utilization` a computed/virtual column** — never a stored float.
4. **Add proper FK constraints** everywhere. The current schema has several tables with FK relationships commented out.
5. **Use UUIDs** for public-facing IDs. Integer IDs in URLs expose record counts and allow enumeration.
6. **Add a `deleted_at` soft-delete column** on users and projects. Government systems should never hard-delete records.
7. **Enforce the NRC uniqueness constraint** at the DB level (`UNIQUE` index on `nrc`).

### Security Baseline for Enterprise

Every response must include:
```
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000; includeSubDomains
Permissions-Policy: geolocation=(), camera=(), microphone=()
```

Session cookie must be:
```
Secure; HttpOnly; SameSite=Strict
```

Additional requirements:
- Enforce HTTPS — redirect all HTTP to HTTPS at the server/load balancer level
- Validate all file uploads by MIME type + file signature (magic bytes), not just extension
- Store uploaded files outside the webroot or behind a signed URL system (S3 / cloud storage)
- Implement real 2FA (TOTP via authenticator app — use a library like `pragmarx/google2fa`)
- Implement a real audit log: immutable, append-only, queryable. Every state-changing action (create/update/delete/approve/reject) must produce an audit entry with: `user_id`, `action`, `entity_type`, `entity_id`, `old_value` (JSON), `new_value` (JSON), `ip_address`, `timestamp`

### Functional Enhancements for Enterprise Version

| Feature | Notes |
|---|---|
| Real email delivery | Use SMTP with transactional email provider (SendGrid, Mailgun, AWS SES). Emails are required for password reset, account approval, and project notifications. |
| SMS notifications | The user_settings table already has an `sms_notifications` flag. Wire it to Africa's Talking API (dominant provider in Zambia) for project status updates. |
| PDF report generation | Evaluations, compliance reports, and project summaries should be exportable as signed PDF documents for government records. |
| Document management | Receipts and photos should be stored in an object store (S3-compatible) with access control, not a local `uploads/` folder. |
| Role hierarchy expansion | Add: Super Admin (national level), Constituency Admin, MP (read-only view), Auditor (read-only + report export) |
| API-first design | Build a REST or GraphQL API so the system can be consumed by a mobile app and third-party integrations |
| Real-time notifications | WebSocket or Server-Sent Events for live notification delivery instead of page-reload polling |
| Scheduled jobs | Budget threshold alerts, deadline reminders, and backup scheduling should run as cron jobs / task queues, not simulated on page load |
| Multi-constituency support | The current system is single-constituency. A national deployment needs a `constituency` tenant dimension on all records. |
| Data export | Admins should be able to export projects, evaluations, and financial records to CSV/Excel for government reporting |

---

## 10. Recommended Technology Stack

### Option A: Django + React (Recommended)

Best for: modern enterprise build, active development team, government agency approval.

| Layer | Technology | Reason |
|---|---|---|
| Backend framework | Django 5.x (Python) | Security by default, built-in admin, proven in government systems worldwide (UK GDS, etc.) |
| REST API | Django REST Framework | Well-documented, production-tested |
| Frontend | Next.js 14+ (React/TypeScript) | SSR for performance, type safety, large ecosystem |
| Database | PostgreSQL 16 | Superior constraint enforcement, JSON support, better compliance story than MySQL |
| ORM + Migrations | Django ORM | Best migrations system available — every schema change is versioned and reversible |
| Auth | `django-allauth` | 2FA, email verification, social auth all included |
| Permissions | `django-guardian` | Object-level permissions (e.g. officer can only access their assigned projects) |
| Audit log | `django-auditlog` | Automatic model-change logging — satisfies government audit requirement |
| File storage | `django-storages` + S3 | Files outside webroot, access-controlled, CDN-ready |
| Email | `django-anymail` + SendGrid | Real delivery with bounce/open tracking |
| SMS | Africa's Talking SDK | Standard Zambian telco integration |
| Task queue | Celery + Redis | Async notifications, scheduled backups, PDF generation |
| PDF generation | WeasyPrint or ReportLab | Evaluation and project report exports |
| Security headers | `django-csp` + middleware | CSP, HSTS, X-Frame-Options all configurable in one place |
| Testing | pytest-django | Unit + integration tests with real DB |
| CI/CD | GitHub Actions | Automated test + deploy pipeline |
| Deployment | Docker + Nginx + Gunicorn | Reproducible, portable, standard in government IT |
| Monitoring | Sentry | Error tracking and alerting |

### Option B: Laravel + Vue (PHP teams only)

Best for: teams with existing PHP expertise who cannot switch languages.

| Layer | Technology |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Vue 3 + Inertia.js (or Livewire for simpler UI) |
| Database | PostgreSQL |
| Auth | Laravel Breeze / Jetstream (includes 2FA) |
| Permissions | Spatie Laravel Permission |
| Audit log | Spatie Laravel Activitylog |
| Files | Laravel Storage + S3 driver |
| Email | Laravel Mail + Mailgun/SendGrid |
| Queue | Laravel Horizon + Redis |
| Testing | PHPUnit + Pest |

### What Not to Use

| Technology | Reason to Avoid |
|---|---|
| Raw PHP (current approach) | No migrations, no test framework culture, no DI, impossible to maintain at scale |
| CodeIgniter | Weak ecosystem, no first-class queue/jobs, harder to hire for |
| WordPress / plugins | Not appropriate for a data-management system of this complexity |
| MongoDB | Relational data with FK constraints — a relational DB is correct here |
| Microservices | Premature for this scale; a well-structured monolith is the right starting point |

---

*This document was generated from analysis of the complete source code of the CDF Management System school project. All table definitions, function signatures, business rules, and workflows are derived from the actual codebase. Use this as the authoritative specification for the enterprise rebuild.*
