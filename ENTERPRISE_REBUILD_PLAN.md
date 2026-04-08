# CDF Management System — Enterprise Rebuild Plan
## Django REST API + Flutter Multi-Platform

**Document Type:** Project Architecture & Implementation Plan
**Version:** 1.0
**Date:** 2026-04-08
**Classification:** Internal — Project Planning

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Project Objectives](#2-project-objectives)
3. [System Architecture Overview](#3-system-architecture-overview)
4. [Technology Stack](#4-technology-stack)
5. [Backend Architecture — Django](#5-backend-architecture--django)
6. [API Design](#6-api-design)
7. [Frontend Architecture — Flutter](#7-frontend-architecture--flutter)
8. [Database Architecture](#8-database-architecture)
9. [Security Architecture](#9-security-architecture)
10. [Infrastructure & Deployment](#10-infrastructure--deployment)
11. [Development Phases & Milestones](#11-development-phases--milestones)
12. [Testing Strategy](#12-testing-strategy)
13. [Compliance & Government Standards](#13-compliance--government-standards)
14. [Team Structure & Roles](#14-team-structure--roles)
15. [Risk Register](#15-risk-register)
16. [Definition of Done](#16-definition-of-done)
17. [UI/UX Design System](#17-uiux-design-system)

---

## 1. Executive Summary

This document defines the full architecture and implementation plan for rebuilding the Constituency Development Fund (CDF) Management System as an enterprise-grade, government-approved platform.

The rebuild replaces a PHP school project with a production system built on two industry-standard pillars:

- **Django REST Framework** — a Python backend serving a secure, versioned REST API
- **Flutter** — a single codebase delivering native mobile apps (Android, iOS) and a web application from one shared source

The result is a system that works on any device a government officer, administrator, or community beneficiary is likely to carry — phone, tablet, or desktop browser — without maintaining separate codebases for each platform.

The architecture is designed to meet:
- Zambia's Data Protection Act 2021
- OWASP API Security Top 10
- WCAG 2.1 AA accessibility standard
- Government ICT procurement standards
- ISO 27001-aligned security controls

---

## 2. Project Objectives

### Primary Objectives

| # | Objective |
|---|---|
| 1 | Replace the current PHP system with a maintainable, auditable, enterprise-grade platform |
| 2 | Deliver native mobile apps (Android + iOS) for field officers who work in the field |
| 3 | Deliver a web application for administrators and office-based staff |
| 4 | Implement a real audit trail — every state-changing action logged, immutable |
| 5 | Implement real email and SMS notifications (currently stubbed in the school project) |
| 6 | Enforce all security controls: 2FA, HTTPS, JWT, rate limiting, input validation |
| 7 | Support offline-capable data entry for officers in low-connectivity areas |
| 8 | Produce a system that can be handed to government IT for long-term maintenance |

### Non-Functional Requirements

| Requirement | Target |
|---|---|
| API response time (95th percentile) | < 300ms |
| Mobile app cold start | < 2 seconds |
| System availability | 99.5% uptime (measured monthly) |
| Concurrent users | 500 minimum without degradation |
| Data retention | 7 years (government financial record requirement) |
| Backup frequency | Daily automated, weekly offsite |
| Recovery Time Objective (RTO) | 4 hours |
| Recovery Point Objective (RPO) | 24 hours |
| Accessibility | WCAG 2.1 AA |
| Security audit | Pass OWASP Top 10 scan before go-live |

---

## 3. System Architecture Overview

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                             │
│                                                                 │
│   ┌─────────────┐   ┌─────────────┐   ┌─────────────────────┐  │
│   │ Flutter Web │   │ Flutter     │   │ Flutter             │  │
│   │ (Admin +    │   │ Android App │   │ iOS App             │  │
│   │  Officers)  │   │ (Officers + │   │ (Officers +         │  │
│   │             │   │  Benefic.)  │   │  Beneficiaries)     │  │
│   └──────┬──────┘   └──────┬──────┘   └──────────┬──────────┘  │
└──────────┼────────────────┼───────────────────────┼────────────┘
           │                │                       │
           └────────────────┴───────────────────────┘
                                    │
                              HTTPS / REST
                                    │
┌───────────────────────────────────▼────────────────────────────┐
│                         API GATEWAY                             │
│              Nginx (SSL termination, rate limiting)             │
└───────────────────────────────────┬────────────────────────────┘
                                    │
┌───────────────────────────────────▼────────────────────────────┐
│                      APPLICATION LAYER                          │
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐  │
│   │              Django REST Framework                       │  │
│   │                                                          │  │
│   │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────┐  │  │
│   │  │   Auth   │ │ Projects │ │   Eval   │ │   Users   │  │  │
│   │  │  Module  │ │  Module  │ │  Module  │ │  Module   │  │  │
│   │  └──────────┘ └──────────┘ └──────────┘ └───────────┘  │  │
│   │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────┐  │  │
│   │  │Financial │ │  Site    │ │  Comms   │ │ Settings  │  │  │
│   │  │  Module  │ │  Visits  │ │  Module  │ │  Module   │  │  │
│   │  └──────────┘ └──────────┘ └──────────┘ └───────────┘  │  │
│   └─────────────────────────────────────────────────────────┘  │
│                                                                  │
│   ┌──────────────────┐        ┌───────────────────────────────┐ │
│   │  Celery Worker   │        │       Django Admin            │ │
│   │  (async tasks:   │        │  (back-office for super admin │ │
│   │  email, SMS,     │        │   and IT management)          │ │
│   │  PDF, backups)   │        └───────────────────────────────┘ │
│   └──────────────────┘                                          │
└──────────────────────────────────────────────────────────────────┘
                          │              │
              ┌───────────┘              └──────────────┐
              │                                         │
┌─────────────▼──────────┐              ┌───────────────▼────────┐
│    DATA LAYER          │              │   SERVICES LAYER        │
│                        │              │                         │
│  ┌──────────────────┐  │              │  ┌───────────────────┐  │
│  │   PostgreSQL 16  │  │              │  │  Redis (cache +   │  │
│  │   (primary DB)   │  │              │  │   Celery broker)  │  │
│  └──────────────────┘  │              │  └───────────────────┘  │
│  ┌──────────────────┐  │              │  ┌───────────────────┐  │
│  │   S3-compatible  │  │              │  │  SendGrid (email) │  │
│  │   Object Store   │  │              │  └───────────────────┘  │
│  │ (files, receipts,│  │              │  ┌───────────────────┐  │
│  │  photos, exports)│  │              │  │  Africa's Talking │  │
│  └──────────────────┘  │              │  │  (SMS — Zambia)   │  │
└────────────────────────┘              │  └───────────────────┘  │
                                        │  ┌───────────────────┐  │
                                        │  │  Firebase FCM     │  │
                                        │  │  (push notif.)    │  │
                                        │  └───────────────────┘  │
                                        └─────────────────────────┘
```

### Request Flow

```
Flutter App
    │
    ├── Attaches JWT Bearer token to every request header
    │
    ▼
Nginx (SSL termination, rate limiting, static files)
    │
    ▼
Django (Gunicorn WSGI)
    │
    ├── JWT middleware validates token → extracts user identity
    ├── Permission class checks role (Admin / Officer / Beneficiary)
    ├── View/ViewSet executes business logic
    ├── Serializer validates and formats data
    ├── ORM queries PostgreSQL
    │
    ├── If async work needed (email, SMS, PDF):
    │       └── Enqueue Celery task → Redis broker → Worker processes async
    │
    └── Returns JSON response to Flutter
```

---

## 4. Technology Stack

### Backend

| Component | Technology | Version | Justification |
|---|---|---|---|
| Language | Python | 3.12+ | Readable, maintainable, large talent pool, government-friendly |
| Web framework | Django | 5.x | Security by default, ORM, migrations, admin — proven in government systems worldwide |
| API layer | Django REST Framework | 3.15+ | Industry standard for Django REST APIs |
| Authentication | djangorestframework-simplejwt | 5.x | JWT tokens for stateless auth — required for Flutter mobile |
| 2FA | django-otp + pyotp | latest | TOTP-based authenticator app 2FA |
| Permissions | django-guardian | 2.x | Object-level permissions (officer can only access assigned projects) |
| Audit log | django-auditlog | 3.x | Automatic model-change logging with old/new values |
| Async tasks | Celery | 5.x | Email, SMS, PDF generation, scheduled backups |
| Task broker | Redis | 7.x | Fast, reliable message broker for Celery |
| PDF generation | WeasyPrint | latest | Server-side PDF for evaluation/compliance reports |
| Email | django-anymail + SendGrid | latest | Transactional email with delivery tracking |
| SMS | Africa's Talking Python SDK | latest | Zambian carrier integration |
| Push notifications | firebase-admin | latest | FCM push for Flutter mobile apps |
| File storage | django-storages + boto3 | latest | S3-compatible object storage |
| Search | django-filter | latest | Queryable, filterable API endpoints |
| API docs | drf-spectacular | latest | Auto-generated OpenAPI 3.0 docs |
| CORS | django-cors-headers | latest | Controlled cross-origin for Flutter Web |
| Rate limiting | django-ratelimit | latest | Per-endpoint rate limiting |
| Environment | python-decouple | latest | 12-factor config — no secrets in code |

### Frontend (Flutter)

| Component | Technology | Version | Justification |
|---|---|---|---|
| Framework | Flutter | 3.x (stable) | Single codebase → Android, iOS, Web from one source |
| Language | Dart | 3.x | Null-safe, compiled, fast |
| State management | Riverpod | 2.x | Most maintainable Flutter state solution for complex apps |
| Navigation | GoRouter | latest | Declarative routing, deep link support, web URL support |
| HTTP client | Dio | 5.x | Interceptors for JWT injection, retry logic, error handling |
| Secure storage | flutter_secure_storage | latest | Keychain (iOS) / Keystore (Android) for JWT tokens |
| Local database | Drift (SQLite) | latest | Offline-first capability for field officers |
| Maps | google_maps_flutter | latest | Project location display, site visit coordinates |
| Charts | fl_chart | latest | Dashboard analytics and progress charts |
| Camera | image_picker + camera | latest | Photo capture for progress updates and receipts |
| File handling | file_picker | latest | Document attachment |
| Push notifications | firebase_messaging | latest | FCM push notification receipt on mobile |
| Form validation | reactive_forms | latest | Declarative, testable form validation |
| PDF viewer | flutter_pdfview | latest | View generated reports in-app |
| Connectivity | connectivity_plus | latest | Detect online/offline state for sync logic |
| Localization | flutter_localizations | built-in | i18n support for future language additions |
| UI components | Material 3 (built-in) | Flutter 3.x | Government-appropriate, accessible design system |

### Database & Infrastructure

| Component | Technology | Justification |
|---|---|---|
| Primary database | PostgreSQL 16 | Superior constraints, JSON support, better compliance story, row-level security |
| Cache | Redis 7 | Session cache, API response cache, Celery broker |
| Object storage | MinIO (self-hosted S3) or AWS S3 | Files, receipts, photos, PDF exports — outside webroot |
| Web server | Nginx | SSL termination, rate limiting, static asset serving |
| App server | Gunicorn | Production WSGI server for Django |
| Containers | Docker + Docker Compose | Reproducible, portable, standard in enterprise IT |
| Orchestration | Docker Swarm or Kubernetes | Production container management |
| CI/CD | GitHub Actions | Automated test, build, and deploy pipeline |
| Monitoring | Sentry | Error tracking and alerting |
| Logging | Django logging + structured JSON logs | Centralized log aggregation |
| SSL | Let's Encrypt or government-issued cert | HTTPS enforcement |

---

## 5. Backend Architecture — Django

### Project Structure

```
cdf_backend/
├── manage.py
├── requirements/
│   ├── base.txt           # Shared dependencies
│   ├── development.txt    # Dev-only (debug toolbar, etc.)
│   └── production.txt     # Prod-only (gunicorn, sentry, etc.)
├── config/
│   ├── settings/
│   │   ├── base.py        # Shared settings
│   │   ├── development.py # Dev overrides
│   │   └── production.py  # Production settings
│   ├── urls.py            # Root URL configuration
│   ├── wsgi.py
│   └── asgi.py
├── apps/
│   ├── accounts/          # User model, auth, registration, 2FA
│   ├── projects/          # Project lifecycle, progress, expenses
│   ├── evaluations/       # All evaluation types
│   ├── site_visits/       # Site visit scheduling and logging
│   ├── communication/     # Notifications, messages
│   ├── financials/        # Expenses, budget tracking
│   ├── settings_app/      # System settings
│   └── core/              # Shared utilities, base models, mixins
├── api/
│   └── v1/
│       ├── urls.py        # All API v1 URL patterns
│       └── router.py      # DRF router registration
├── tasks/                 # Celery task definitions
│   ├── email_tasks.py
│   ├── sms_tasks.py
│   ├── pdf_tasks.py
│   └── backup_tasks.py
├── tests/
│   ├── accounts/
│   ├── projects/
│   ├── evaluations/
│   └── ...
├── celery.py              # Celery application config
├── Dockerfile
└── docker-compose.yml
```

### App Structure (per app)

Each Django app follows the same internal structure:

```
apps/projects/
├── __init__.py
├── models.py          # ORM models
├── serializers.py     # DRF serializers (input validation + output formatting)
├── views.py           # ViewSets (API endpoint handlers)
├── permissions.py     # Custom permission classes for this app
├── filters.py         # django-filter FilterSets
├── signals.py         # Post-save signals (e.g., trigger notifications)
├── services.py        # Business logic layer (called by views, tested independently)
├── tasks.py           # Celery tasks specific to this app
├── admin.py           # Django admin registration
├── urls.py            # App-level URL patterns
├── migrations/        # Database migrations
└── tests/
    ├── test_models.py
    ├── test_serializers.py
    ├── test_views.py
    └── test_services.py
```

### Base Model

Every model in the system inherits from a shared base:

```python
# apps/core/models.py
import uuid
from django.db import models

class BaseModel(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    deleted_at = models.DateTimeField(null=True, blank=True)  # soft delete

    class Meta:
        abstract = True
```

**Why UUIDs:** Integer IDs in URLs expose record counts and allow enumeration attacks. UUIDs prevent this. They also allow client-side ID generation for offline-first sync.

**Why soft delete:** Government systems must never hard-delete financial or project records. `deleted_at` marks a record as deleted without removing it from the database.

### Service Layer Pattern

Business logic does not live in views or models. It lives in a service layer that is independently testable:

```python
# apps/projects/services.py

class ProjectService:

    @staticmethod
    def approve_project(project_id: uuid.UUID, approved_by: User) -> Project:
        project = Project.objects.get(id=project_id)

        if project.approval_status != 'pending':
            raise ValidationError("Only pending projects can be approved.")

        project.approval_status = 'approved'
        project.approved_by = approved_by
        project.approved_at = timezone.now()
        project.save()

        # Trigger async notification
        from tasks.email_tasks import send_project_approval_email
        send_project_approval_email.delay(project_id=str(project_id))

        # Audit log (automatic via django-auditlog)
        return project
```

Views call services. Services call models and tasks. This separation means business logic can be tested without HTTP requests.

---

## 6. API Design

### Versioning

All endpoints are versioned:

```
https://api.cdf.gov.zm/v1/
```

Version is in the URL path, not a header. This allows Flutter apps to target a specific version while the backend evolves.

### Authentication Flow

```
POST /v1/auth/login/
  Body: { "username": "...", "password": "..." }
  Response: { "access": "<JWT>", "refresh": "<JWT>", "user": {...} }

POST /v1/auth/token/refresh/
  Body: { "refresh": "<JWT>" }
  Response: { "access": "<new JWT>" }

POST /v1/auth/2fa/verify/
  Body: { "token": "123456" }  (TOTP code from authenticator app)
  Response: { "access": "<JWT with 2fa_verified claim>" }
```

Every subsequent request includes:
```
Authorization: Bearer <access_token>
```

**JWT access token lifetime:** 15 minutes
**JWT refresh token lifetime:** 7 days (stored securely in flutter_secure_storage)

### Core API Endpoints

#### Authentication & Accounts

```
POST   /v1/auth/login/                    Login
POST   /v1/auth/logout/                   Invalidate refresh token
POST   /v1/auth/token/refresh/            Refresh access token
POST   /v1/auth/password/reset/           Request password reset
POST   /v1/auth/password/reset/confirm/   Submit new password
POST   /v1/auth/2fa/setup/                Generate TOTP QR code
POST   /v1/auth/2fa/verify/               Verify TOTP and complete login
POST   /v1/auth/register/                 Beneficiary self-registration
```

#### Users

```
GET    /v1/users/                         List users (admin only, paginated)
POST   /v1/users/                         Create user (admin only)
GET    /v1/users/{id}/                    Get user detail
PATCH  /v1/users/{id}/                    Update user
DELETE /v1/users/{id}/                    Soft-delete user (admin only)
POST   /v1/users/{id}/activate/           Approve pending beneficiary
POST   /v1/users/{id}/deactivate/         Deactivate user
GET    /v1/users/me/                       Current user profile
PATCH  /v1/users/me/                       Update own profile
GET    /v1/users/me/settings/              User preferences
PATCH  /v1/users/me/settings/             Update preferences
```

#### Projects

```
GET    /v1/projects/                       List projects (filtered by role)
POST   /v1/projects/                       Submit new project (beneficiary)
GET    /v1/projects/{id}/                  Project detail
PATCH  /v1/projects/{id}/                  Update project
DELETE /v1/projects/{id}/                  Soft-delete (admin)
POST   /v1/projects/{id}/approve/          Approve project (admin)
POST   /v1/projects/{id}/reject/           Reject project (admin)
POST   /v1/projects/{id}/assign-officer/   Assign officer (admin)
GET    /v1/projects/{id}/progress/         List progress updates
POST   /v1/projects/{id}/progress/         Submit progress update
GET    /v1/projects/{id}/expenses/         List expenses
POST   /v1/projects/{id}/expenses/         Log expense
GET    /v1/projects/{id}/evaluations/      List evaluations
GET    /v1/projects/{id}/site-visits/      List site visits
GET    /v1/projects/{id}/timeline/         Full event timeline
GET    /v1/projects/stats/                 Dashboard stats (role-filtered)
```

#### Evaluations

```
GET    /v1/evaluations/                    List (officer's own, or all for admin)
POST   /v1/evaluations/                    Create evaluation
GET    /v1/evaluations/{id}/               Detail
PATCH  /v1/evaluations/{id}/               Update

POST   /v1/compliance-checks/              Submit compliance check
GET    /v1/compliance-checks/{id}/

POST   /v1/quality-assessments/            Submit quality assessment
GET    /v1/quality-assessments/{id}/

POST   /v1/impact-assessments/             Submit impact assessment
GET    /v1/impact-assessments/{id}/

POST   /v1/progress-reviews/               Submit progress review
GET    /v1/progress-reviews/{id}/
```

#### Site Visits

```
GET    /v1/site-visits/                    List (officer's own or all for admin)
POST   /v1/site-visits/                    Schedule visit
GET    /v1/site-visits/{id}/               Detail
PATCH  /v1/site-visits/{id}/               Update (mark completed, add findings)
DELETE /v1/site-visits/{id}/               Cancel
POST   /v1/geocode/                        Resolve address to coordinates
```

#### Communication

```
GET    /v1/notifications/                  User's notifications (paginated)
POST   /v1/notifications/{id}/read/        Mark as read
POST   /v1/notifications/read-all/         Mark all as read
DELETE /v1/notifications/{id}/             Delete

GET    /v1/messages/                       Inbox (conversations list)
POST   /v1/messages/                       Send message
GET    /v1/messages/{id}/                  Message thread
POST   /v1/messages/{id}/read/             Mark as read
```

#### System Settings (Admin)

```
GET    /v1/settings/                       All settings
PATCH  /v1/settings/                       Update settings
POST   /v1/settings/cache/clear/           Clear system cache
POST   /v1/settings/backup/run/            Trigger manual backup
POST   /v1/settings/email/test/            Send test email
```

### Standard Response Format

All API responses follow a consistent envelope:

**Success:**
```json
{
  "status": "success",
  "data": { ... },
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 143,
    "total_pages": 8
  }
}
```

**Error:**
```json
{
  "status": "error",
  "code": "VALIDATION_ERROR",
  "message": "The request contained invalid data.",
  "errors": {
    "budget": ["Ensure this value is greater than 0."],
    "end_date": ["End date must be after start date."]
  }
}
```

### API Documentation

`drf-spectacular` auto-generates an OpenAPI 3.0 specification, served at:

```
GET /api/schema/          → Raw OpenAPI YAML
GET /api/docs/            → Swagger UI (dev only)
GET /api/redoc/           → ReDoc (dev only)
```

---

## 7. Frontend Architecture — Flutter

### Repository Structure

```
cdf_flutter/
├── lib/
│   ├── main.dart                    # App entry point
│   ├── app.dart                     # MaterialApp + Router setup
│   │
│   ├── core/
│   │   ├── api/
│   │   │   ├── api_client.dart      # Dio HTTP client config
│   │   │   ├── api_interceptors.dart # JWT injection, 401 handling, retry
│   │   │   └── api_endpoints.dart   # Centralized endpoint constants
│   │   ├── auth/
│   │   │   ├── auth_service.dart    # Login, logout, token refresh logic
│   │   │   └── token_storage.dart   # flutter_secure_storage wrapper
│   │   ├── router/
│   │   │   ├── app_router.dart      # GoRouter config and route definitions
│   │   │   └── route_guards.dart    # Auth guard, role guard
│   │   ├── theme/
│   │   │   ├── app_theme.dart       # Material 3 theme (colors, typography)
│   │   │   └── color_scheme.dart    # Government brand colors
│   │   ├── models/                  # Shared data models / DTOs
│   │   ├── exceptions/              # Typed API exceptions
│   │   ├── utils/                   # Formatters, validators, helpers
│   │   └── widgets/                 # Shared reusable UI components
│   │       ├── app_bar.dart
│   │       ├── sidebar.dart
│   │       ├── data_table.dart
│   │       ├── status_badge.dart
│   │       ├── loading_indicator.dart
│   │       ├── error_view.dart
│   │       └── confirmation_dialog.dart
│   │
│   ├── features/
│   │   ├── auth/
│   │   │   ├── data/                # API calls for auth
│   │   │   ├── domain/              # Auth business models
│   │   │   ├── presentation/
│   │   │   │   ├── login_screen.dart
│   │   │   │   ├── forgot_password_screen.dart
│   │   │   │   ├── reset_password_screen.dart
│   │   │   │   └── two_factor_screen.dart
│   │   │   └── providers/           # Riverpod providers for auth state
│   │   │
│   │   ├── dashboard/
│   │   ├── users/
│   │   ├── projects/
│   │   ├── progress/
│   │   ├── financials/
│   │   ├── evaluations/
│   │   ├── site_visits/
│   │   ├── communication/
│   │   └── settings/
│   │
│   └── l10n/                        # Localization strings
│       └── app_en.arb
│
├── test/
│   ├── unit/
│   ├── widget/
│   └── integration/
├── android/
├── ios/
├── web/
├── pubspec.yaml
└── analysis_options.yaml            # Strict lint rules
```

### Feature Layer Structure (per feature)

Each feature follows the same three-layer pattern:

```
features/projects/
├── data/
│   ├── project_repository.dart      # Implements abstract interface
│   ├── project_api_service.dart     # Raw API calls (Dio)
│   └── project_local_service.dart   # Drift SQLite for offline
├── domain/
│   ├── project.dart                 # Domain model
│   ├── project_repository.dart      # Abstract interface
│   └── project_status.dart          # Enums, value objects
└── presentation/
    ├── project_list_screen.dart
    ├── project_detail_screen.dart
    ├── project_form_screen.dart
    ├── project_approval_screen.dart
    ├── widgets/
    │   ├── project_card.dart
    │   ├── project_status_badge.dart
    │   └── project_filter_bar.dart
    └── providers/
        ├── project_list_provider.dart
        ├── project_detail_provider.dart
        └── project_form_provider.dart
```

### State Management — Riverpod

Riverpod providers are the single source of truth for all UI state:

```dart
// features/projects/providers/project_list_provider.dart

@riverpod
class ProjectList extends _$ProjectList {
  @override
  Future<List<Project>> build() async {
    final repository = ref.watch(projectRepositoryProvider);
    return repository.getProjects();
  }

  Future<void> approveProject(String projectId) async {
    final repository = ref.read(projectRepositoryProvider);
    await repository.approveProject(projectId);
    ref.invalidateSelf(); // Refresh the list
  }
}
```

### JWT Token Management

```dart
// core/api/api_interceptors.dart

class AuthInterceptor extends Interceptor {

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await tokenStorage.getAccessToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (err.response?.statusCode == 401) {
      // Try to refresh the token
      final refreshed = await authService.refreshToken();
      if (refreshed) {
        // Retry original request with new token
        return handler.resolve(await dio.fetch(err.requestOptions));
      } else {
        // Refresh failed — send to login
        authService.logout();
      }
    }
    handler.next(err);
  }
}
```

### Offline-First for Field Officers

Field officers frequently work in areas with poor connectivity. The app handles this with a sync queue:

```dart
// core/sync/sync_service.dart

class SyncService {

  // Queue an action when offline
  Future<void> queueAction(SyncAction action) async {
    await localDb.syncQueue.insertOne(action);
  }

  // On reconnect, flush the queue
  Future<void> flushQueue() async {
    final pending = await localDb.syncQueue.getPending();
    for (final action in pending) {
      try {
        await executeAction(action);
        await localDb.syncQueue.markCompleted(action.id);
      } catch (e) {
        await localDb.syncQueue.markFailed(action.id, e.toString());
      }
    }
  }
}
```

**Offline-capable operations:**
- Submit progress updates (synced when online)
- Log expenses (synced when online)
- View previously loaded project data (from local cache)
- Draft site visit reports

### Navigation (GoRouter)

```dart
// core/router/app_router.dart

final appRouter = GoRouter(
  redirect: (context, state) {
    final isLoggedIn = ref.read(authProvider).isAuthenticated;
    final isLoginRoute = state.location == '/login';

    if (!isLoggedIn && !isLoginRoute) return '/login';
    if (isLoggedIn && isLoginRoute) return '/dashboard';
    return null;
  },
  routes: [
    GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
    GoRoute(path: '/2fa', builder: (_, __) => const TwoFactorScreen()),
    ShellRoute(
      builder: (_, __, child) => AppShell(child: child),
      routes: [
        GoRoute(path: '/dashboard', builder: (_, __) => const DashboardScreen()),
        GoRoute(
          path: '/projects',
          builder: (_, __) => const ProjectListScreen(),
          routes: [
            GoRoute(
              path: ':id',
              builder: (_, state) => ProjectDetailScreen(id: state.pathParameters['id']!),
            ),
          ],
        ),
        // ... other routes
      ],
    ),
  ],
);
```

### Adaptive Layout (Web vs Mobile)

The same Flutter code renders differently based on screen size:

```dart
// core/widgets/adaptive_layout.dart

class AdaptiveLayout extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.of(context).size.width;

    if (width >= 1200) {
      return DesktopLayout(child: child);   // Sidebar + content
    } else if (width >= 600) {
      return TabletLayout(child: child);    // Collapsible sidebar
    } else {
      return MobileLayout(child: child);    // Bottom nav bar
    }
  }
}
```

---

## 8. Database Architecture

### Schema Design Principles

1. **UUIDs as primary keys** — no integer enumeration in URLs
2. **Soft deletes** — `deleted_at` timestamp, never hard delete financial records
3. **Audit trail** — `django-auditlog` auto-logs every model change
4. **Computed fields as properties** — `budget_utilization` is computed, not stored
5. **One FK per relationship** — no duplicate `officer_id` / `assigned_officer_id`
6. **Consolidated evaluation model** — one `evaluations` table with a `type` field, not six separate tables
7. **Row-level security** — PostgreSQL RLS policies to enforce role-based data isolation at the DB level

### Core Models

```
users
  ├── id (UUID PK)
  ├── username (UNIQUE)
  ├── email (UNIQUE)
  ├── password (bcrypt via Django)
  ├── first_name, last_name
  ├── phone, nrc (UNIQUE for beneficiaries)
  ├── dob, gender, marital_status
  ├── role (ENUM: admin/officer/beneficiary)
  ├── department, employee_id, position (admin/officer)
  ├── constituency, ward, village (beneficiary)
  ├── status (ENUM: active/inactive/pending)
  ├── is_2fa_enabled
  ├── login_attempts, account_locked_until
  ├── last_login
  ├── deleted_at (soft delete)
  └── timestamps

projects
  ├── id (UUID PK)
  ├── title, description
  ├── beneficiary_id (FK → users)
  ├── officer_id (FK → users, nullable until assigned)
  ├── constituency, ward, province
  ├── category, funding_source
  ├── budget (DECIMAL 15,2)
  ├── status (ENUM: planning/in_progress/completed/cancelled)
  ├── approval_status (ENUM: pending/approved/rejected)
  ├── progress (INT 0-100)
  ├── start_date, end_date
  ├── actual_start_date, actual_end_date
  ├── pre_qualification (JSONB — checklist flags)
  ├── deleted_at
  └── timestamps

evaluations
  ├── id (UUID PK)
  ├── project_id (FK → projects)
  ├── officer_id (FK → users)
  ├── type (ENUM: general/compliance/quality/impact/progress_review)
  ├── status (ENUM: draft/submitted/reviewed)
  ├── evaluation_date
  ├── scores (JSONB — type-specific dimension scores)
  ├── overall_score (INT)
  ├── findings, recommendations (TEXT)
  ├── next_review_date (nullable)
  └── timestamps

project_progress
  ├── id (UUID PK)
  ├── project_id (FK → projects)
  ├── submitted_by (FK → users)
  ├── progress_percentage (INT)
  ├── description, challenges, next_steps
  ├── achievements (JSONB)
  ├── photo_urls (JSONB — S3 keys)
  ├── receipt_url (S3 key)
  └── timestamps

project_expenses
  ├── id (UUID PK)
  ├── project_id (FK → projects)
  ├── submitted_by (FK → users)
  ├── amount (DECIMAL 15,2)
  ├── category (ENUM)
  ├── description, vendor
  ├── expense_date
  ├── receipt_number
  ├── payment_method
  ├── receipt_url (S3 key)
  ├── resource_photo_urls (JSONB)
  └── timestamps

site_visits
  ├── id (UUID PK)
  ├── project_id (FK → projects)
  ├── officer_id (FK → users)
  ├── visit_date, visit_time
  ├── location (TEXT)
  ├── coordinates (PostGIS POINT or DECIMAL pair)
  ├── purpose (TEXT)
  ├── findings (TEXT — filled on completion)
  ├── status (ENUM: scheduled/completed/cancelled)
  └── timestamps

notifications
  ├── id (UUID PK)
  ├── recipient_id (FK → users)
  ├── title, message
  ├── type (ENUM: info/success/warning/urgent)
  ├── entity_type, entity_id (polymorphic — links to related object)
  ├── is_read
  └── timestamps

messages
  ├── id (UUID PK)
  ├── sender_id (FK → users)
  ├── recipient_id (FK → users)
  ├── subject, body
  ├── is_urgent
  ├── is_read
  └── timestamps

beneficiary_groups
  ├── id (UUID PK)
  ├── owner_id (FK → users)
  ├── name
  └── timestamps

group_members
  ├── id (UUID PK)
  ├── group_id (FK → beneficiary_groups)
  ├── name, nrc, phone
  └── timestamps

audit_log (managed by django-auditlog)
  ├── id
  ├── content_type (table name)
  ├── object_id (UUID of changed record)
  ├── actor_id (FK → users — who made the change)
  ├── action (ENUM: create/update/delete)
  ├── changes (JSONB — {"field": ["old", "new"]})
  ├── remote_addr (IP address)
  └── timestamp

password_resets
  ├── id (UUID PK)
  ├── user_id (FK → users)
  ├── token_hash (SHA-256 of raw token)
  ├── expires_at
  ├── used_at (nullable)
  └── created_at

system_settings
  ├── id
  ├── key (UNIQUE)
  ├── value
  ├── value_type
  ├── group
  └── updated_at

user_settings
  ├── id (UUID PK)
  ├── user_id (FK → users, UNIQUE)
  ├── email_notifications
  ├── sms_notifications
  ├── push_notifications
  ├── project_updates
  ├── message_alerts
  ├── deadline_reminders
  └── timestamps
```

### Migration Strategy

Django migrations provide a complete, versioned history of every schema change. The workflow:

```bash
# Developer makes model change
python manage.py makemigrations apps.projects

# Review the generated migration file before applying
# Each migration is code-reviewed like any other change

# Apply to dev
python manage.py migrate

# CI applies migrations to staging
# Deployment applies migrations to production before app restart
```

No schema changes are ever made directly in the database. Every change goes through a migration file committed to version control.

---

## 9. Security Architecture

### Authentication & Authorization

| Control | Implementation |
|---|---|
| Password hashing | Django's default PBKDF2 SHA-256 (or Argon2 with django[argon2]) |
| JWT access token | 15-minute lifetime, RS256 signed |
| JWT refresh token | 7-day lifetime, stored in flutter_secure_storage (Keychain/Keystore) |
| 2FA | TOTP (RFC 6238) — Google Authenticator, Authy compatible |
| Account lockout | 5 failed attempts → 15-minute lockout (server-side) |
| Session invalidation | Refresh token blacklist on logout (djangorestframework-simplejwt blacklist app) |
| Role enforcement | DRF permission classes on every endpoint — no role check is optional |
| Object-level permissions | django-guardian — officer can only access their assigned projects |

### API Security

| Control | Implementation |
|---|---|
| HTTPS | Enforced at Nginx level — HTTP 301 redirects to HTTPS |
| TLS version | TLS 1.2 minimum, TLS 1.3 preferred |
| Rate limiting | django-ratelimit — per-IP and per-user limits on all endpoints |
| Login endpoint | 10 requests per minute per IP |
| Password reset | 3 requests per email per 15 minutes |
| API endpoints | 100 requests per minute per authenticated user |
| CORS | django-cors-headers — whitelist only known Flutter Web origin |
| Input validation | DRF serializers validate all input — no raw request data reaches the DB |
| SQL injection | Django ORM parameterized queries — no raw SQL string building |
| File upload | MIME type + magic byte validation, virus scanning recommended |
| Secrets | python-decouple + environment variables — zero secrets in code or version control |

### HTTP Security Headers

Nginx is configured to add to every response:

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; connect-src 'self' https://api.cdf.gov.zm;" always;
add_header Permissions-Policy "geolocation=(), camera=(), microphone=()" always;
```

### Audit Trail

Every state-changing operation on every model is logged automatically by `django-auditlog`:

```json
{
  "timestamp": "2026-04-08T10:23:11Z",
  "actor": "admin_user_uuid",
  "action": "update",
  "model": "Project",
  "object_id": "project_uuid",
  "changes": {
    "approval_status": ["pending", "approved"],
    "approved_by_id": [null, "admin_user_uuid"]
  },
  "ip_address": "196.45.x.x"
}
```

This log is:
- Immutable — no update or delete permissions on the audit table
- Queryable — filterable by user, model, action, date range
- Exportable — admin panel can export audit logs to CSV for government inspection

### Data Protection (Zambia Data Protection Act 2021)

| Requirement | Implementation |
|---|---|
| Lawful basis for processing | Users consent at registration; government mandate documented |
| Data minimisation | Only collect fields required for CDF operations |
| Right to access | Users can export their own data via `/v1/users/me/export/` |
| Data retention | 7 years for financial records; configurable per data type |
| Breach notification | Sentry alerts + manual process documented in runbook |
| Data transfer | No personal data leaves Zambia (server hosted in-country or with ZPA-approved provider) |

---

## 10. Infrastructure & Deployment

### Environment Tiers

| Environment | Purpose | Update Trigger |
|---|---|---|
| Development | Local developer machines | Manual |
| Staging | Integration testing, client review | On merge to `develop` branch |
| Production | Live system | On merge to `main` branch (manual approval gate) |

### Docker Compose (Development)

```yaml
version: '3.9'
services:
  db:
    image: postgres:16
    environment:
      POSTGRES_DB: cdf_dev
      POSTGRES_USER: cdf
      POSTGRES_PASSWORD: devpassword

  redis:
    image: redis:7-alpine

  api:
    build: .
    command: python manage.py runserver 0.0.0.0:8000
    volumes:
      - .:/app
    ports:
      - "8000:8000"
    depends_on:
      - db
      - redis
    env_file: .env.development

  worker:
    build: .
    command: celery -A celery worker -l info
    depends_on:
      - redis

  beat:
    build: .
    command: celery -A celery beat -l info
    depends_on:
      - redis
```

### Production Deployment (Docker Swarm)

```
                    ┌─────────────────────┐
                    │    Load Balancer     │
                    │  (Nginx + SSL cert)  │
                    └──────────┬──────────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
    ┌─────────▼──────┐ ┌───────▼──────┐ ┌──────▼───────┐
    │  Django/Gunicorn│ │Django/Gunicorn│ │Django/Gunicorn│
    │   (replica 1)  │ │  (replica 2) │ │  (replica 3) │
    └────────────────┘ └──────────────┘ └──────────────┘
              │
    ┌─────────┼───────────┐
    │         │           │
┌───▼───┐ ┌───▼────┐ ┌────▼──────┐
│  PG   │ │ Redis  │ │  Celery   │
│Primary│ │Cluster │ │  Workers  │
└───────┘ └────────┘ └───────────┘
```

### CI/CD Pipeline (GitHub Actions)

```
Push to feature branch
    │
    ▼
[CI: test]
  ├── Run Python unit tests (pytest)
  ├── Run Flutter unit + widget tests
  ├── Lint (flake8, black, dart analyze)
  └── Security scan (bandit for Python, dependency audit)
    │
    ▼ (on merge to develop)
[CD: staging]
  ├── Build Docker image
  ├── Run database migrations on staging
  ├── Deploy to staging server
  └── Run smoke tests against staging API
    │
    ▼ (on merge to main — manual approval required)
[CD: production]
  ├── Build production Docker image
  ├── Tag release
  ├── Run migrations on production DB
  ├── Rolling deploy (zero downtime)
  └── Health check — rollback automatically if health check fails

[Flutter: mobile build]
  ├── Build Android APK/AAB (signed)
  ├── Build iOS IPA (signed)
  └── Upload to respective stores (manual review gate)
```

### Backup Strategy

| Backup Type | Frequency | Retention | Storage |
|---|---|---|---|
| PostgreSQL full dump | Daily | 30 days | S3 (encrypted) |
| PostgreSQL WAL streaming | Continuous | 7 days | S3 |
| Media files (S3) | S3 versioning | Indefinite | S3 |
| Application config | On change | Git history | Version control |

---

## 11. Development Phases & Milestones

### Phase 0 — Foundation (Weeks 1–2)

**Goal:** Skeleton is running locally end-to-end. No features yet.

Backend:
- [ ] Django project created with all apps scaffolded
- [ ] PostgreSQL connected, base models defined
- [ ] JWT authentication wired (login, refresh, logout)
- [ ] `BaseModel` (UUID, soft delete, timestamps) applied everywhere
- [ ] Docker Compose dev environment working
- [ ] GitHub Actions CI pipeline running (tests pass on every push)
- [ ] API documentation endpoint live
- [ ] Django Admin configured with all models registered

Flutter:
- [ ] Flutter project created (web + android + ios enabled)
- [ ] Riverpod, GoRouter, Dio dependencies configured
- [ ] Base theme (Material 3, government color palette) defined
- [ ] API client with JWT interceptor wired
- [ ] Login screen → JWT stored → Dashboard route
- [ ] Adaptive layout shell (desktop sidebar, mobile bottom nav)

**Milestone:** Developer can log in via Flutter Web, receive JWT, hit a protected API endpoint.

---

### Phase 1 — Core Auth & Users (Weeks 3–4)

Backend:
- [ ] Full user registration (beneficiary self-register → pending)
- [ ] Password reset flow (email token, rate limited)
- [ ] TOTP 2FA setup and verification
- [ ] Account lockout (5 attempts → 15 min)
- [ ] Admin: list, create, activate, deactivate, delete users
- [ ] Beneficiary: approval workflow with notification
- [ ] User profile GET/PATCH
- [ ] User preferences GET/PATCH

Flutter:
- [ ] Login screen with error handling and lockout message
- [ ] 2FA entry screen
- [ ] Forgot password + reset password screens
- [ ] Role-based route guards (admin sees admin routes, officer sees officer routes)
- [ ] User list screen (admin)
- [ ] User detail and edit screen
- [ ] Own profile screen
- [ ] Beneficiary registration form with NRC validation

**Milestone:** Full user lifecycle works on all platforms.

---

### Phase 2 — Projects (Weeks 5–7)

Backend:
- [ ] Project CRUD
- [ ] Project submission (beneficiary)
- [ ] Approval/rejection workflow + notifications
- [ ] Officer assignment + notification
- [ ] Project list filtered by role (admin sees all, officer sees assigned, beneficiary sees own)
- [ ] Project status transitions enforced
- [ ] Dashboard stats endpoint (role-aware)

Flutter:
- [ ] Project list screen with search, filter, pagination
- [ ] Project detail screen with status timeline
- [ ] Project submission form (beneficiary) — full pre-qualification checklist
- [ ] Approve/reject UI (admin)
- [ ] Assign officer UI (admin)
- [ ] Role-appropriate dashboard with stats cards and charts

**Milestone:** A beneficiary can submit a project on mobile. An admin can approve it on web. An officer can see it assigned to them.

---

### Phase 3 — Progress, Expenses & Site Visits (Weeks 8–10)

Backend:
- [ ] Progress update submission (officer, with photo upload to S3)
- [ ] Project progress percentage update on submission
- [ ] Expense logging (with receipt upload to S3)
- [ ] Budget utilization computed and returned
- [ ] Site visit schedule + complete/cancel
- [ ] Geocode API endpoint (proxied, server-side cached)

Flutter:
- [ ] Progress update form with camera integration (capture or upload photos)
- [ ] Progress history timeline view per project
- [ ] Expense logging form with receipt capture
- [ ] Expense list with totals and budget remaining bar
- [ ] Site visit scheduling form with map picker
- [ ] Site visit list screen
- [ ] Map view of project site visits

**Milestone:** Officers can do all field work from the mobile app — update progress, log expenses, and schedule visits.

---

### Phase 4 — Evaluations & Compliance (Weeks 11–13)

Backend:
- [ ] All evaluation types (general, compliance, quality, impact, progress review)
- [ ] Evaluation stats endpoint with filters
- [ ] Compliance scores written back to project record
- [ ] PDF generation for evaluation reports (WeasyPrint)

Flutter:
- [ ] Evaluation form (multi-step, scored dimensions, progress indicator)
- [ ] Evaluation list and detail screens
- [ ] Evaluation stats dashboard for officers
- [ ] In-app PDF viewer for generated reports
- [ ] Compliance summary on project detail screen

**Milestone:** Officers can complete the full evaluation workflow on mobile or web. Generated PDF is viewable in-app.

---

### Phase 5 — Communication (Weeks 14–15)

Backend:
- [ ] Notifications list, mark read, mark all read, delete
- [ ] Messages: send, inbox, thread view, mark read
- [ ] Automated notification triggers for all key events
- [ ] Firebase FCM push notification sending (via Celery task)
- [ ] SendGrid email notifications for key events
- [ ] Africa's Talking SMS for critical alerts

Flutter:
- [ ] Notification center with unread badge
- [ ] Real-time notification polling (Riverpod + periodic refresh)
- [ ] FCM push notification receipt and navigation
- [ ] Message inbox screen
- [ ] Message thread / conversation view
- [ ] Compose message screen with officer-to-admin / officer-to-beneficiary enforcement

**Milestone:** Notifications arrive on device without opening the app. Messages can be exchanged between roles.

---

### Phase 6 — Settings, Offline & Polish (Weeks 16–17)

Backend:
- [ ] System settings GET/PATCH (admin)
- [ ] Backup trigger endpoint
- [ ] Email test endpoint
- [ ] Cache clear endpoint
- [ ] Data export endpoint (user's own data)

Flutter:
- [ ] System settings screen (admin)
- [ ] User preferences screen
- [ ] Offline sync queue (Drift) — progress updates and expenses queue when offline
- [ ] Sync status indicator
- [ ] Connectivity-aware UI (banner when offline, disable submit buttons that require network)
- [ ] App-wide loading states, skeleton screens, error states
- [ ] Pull-to-refresh on all list screens

**Milestone:** Field officers can capture progress updates in the field with no signal. Data syncs when they reconnect.

---

### Phase 7 — Testing, Security Audit & Hardening (Weeks 18–20)

- [ ] Unit test coverage ≥ 80% on all Django service layer and serializers
- [ ] Flutter widget tests for all form screens and navigation flows
- [ ] Integration tests: full API flow tests (login → project → evaluate → report)
- [ ] OWASP API Security Top 10 scan and remediation
- [ ] Penetration test (or self-assessment using OWASP ZAP)
- [ ] WCAG 2.1 AA accessibility audit on Flutter Web
- [ ] Performance test: 500 concurrent users, all endpoints < 300ms p95
- [ ] Load test: 1000 concurrent users, identify bottlenecks
- [ ] Security headers verified (securityheaders.com score A+)
- [ ] All known vulnerabilities resolved

---

### Phase 8 — Staging, UAT & Deployment (Weeks 21–22)

- [ ] Full staging environment deployed
- [ ] Data migration from PHP system to new database
- [ ] User acceptance testing with real government staff
- [ ] Bug fix sprint from UAT feedback
- [ ] Production deployment checklist completed
- [ ] Monitoring and alerting configured (Sentry)
- [ ] Runbook written for IT operations team
- [ ] Mobile apps submitted to Google Play and Apple App Store
- [ ] Go-live

---

## 12. Testing Strategy

### Backend Testing (pytest-django)

| Test Type | What Is Tested | Target Coverage |
|---|---|---|
| Unit tests | Service layer functions, serializer validation, utility functions | 90% |
| Integration tests | Full API request-response cycles including DB | All endpoints |
| Permission tests | Every endpoint tested with wrong role — expect 403 | 100% of endpoints |
| Auth tests | JWT expiry, refresh, lockout, 2FA flow | 100% |
| Edge case tests | Invalid input, missing fields, boundary values | Per serializer |

### Flutter Testing

| Test Type | What Is Tested |
|---|---|
| Unit tests | Providers, services, utility functions, validators |
| Widget tests | Each screen renders correctly in all states (loading, error, empty, data) |
| Integration tests | Full user flows: login → view project → submit update |
| Golden tests | UI snapshot tests to catch unintended visual regressions |

### Pre-Go-Live Checklist

- [ ] All tests passing in CI
- [ ] Zero critical/high severity findings from OWASP scan
- [ ] WCAG 2.1 AA accessibility verified
- [ ] HTTPS enforced, TLS 1.2+, no mixed content
- [ ] All secrets in environment variables, none in code
- [ ] Rate limiting verified on login and password reset
- [ ] Account lockout verified
- [ ] 2FA working end-to-end
- [ ] Email delivery verified (not stubbed)
- [ ] SMS delivery verified (not stubbed)
- [ ] Push notifications working on real device
- [ ] Audit log capturing all key actions
- [ ] Backup and restore tested
- [ ] 500-user load test passed
- [ ] Monitoring and alerting live

---

## 13. Compliance & Government Standards

### Zambia Data Protection Act 2021

| Requirement | How Met |
|---|---|
| Consent at collection | Registration terms and conditions with explicit consent checkbox |
| Data minimisation | Only fields operationally required for CDF administration are collected |
| Right of access | `/v1/users/me/export/` provides a full JSON export of personal data |
| Right of erasure | Soft-delete masks personal data; financial records retained per law |
| Data breach notification | Sentry alert + 72-hour notification procedure documented in runbook |
| Data localisation | Server hosted in Zambia or ZPA-approved Zambian cloud provider |
| Privacy notice | In-app privacy notice linked at registration |

### OWASP API Security Top 10 (2023)

| Risk | Mitigation |
|---|---|
| API1: Broken Object Level Authorization | django-guardian object permissions on all endpoints |
| API2: Broken Authentication | JWT with 15min expiry, 2FA, account lockout |
| API3: Broken Object Property Level Authorization | Serializer fields explicitly declared — no `__all__` |
| API4: Unrestricted Resource Consumption | Rate limiting on all endpoints + pagination enforced |
| API5: Broken Function Level Authorization | Permission class required on every ViewSet |
| API6: Unrestricted Access to Sensitive Business Flows | Rate limiting + TOTP 2FA on admin actions |
| API7: Server Side Request Forgery | Geocode API proxied internally — no user-controlled URLs fetched |
| API8: Security Misconfiguration | Checklist verified pre-deploy; staging mirrors production config |
| API9: Improper Inventory Management | All endpoints documented in OpenAPI spec; `/v1/` versioning |
| API10: Unsafe Consumption of APIs | Geocoding API responses validated before use |

### WCAG 2.1 AA (Flutter Web)

| Criterion | Flutter Implementation |
|---|---|
| 1.1.1 Non-text Content | All images have `semanticLabel`; icons have `Tooltip` |
| 1.4.3 Contrast (minimum) | Material 3 theme validated against AA contrast ratios |
| 2.1.1 Keyboard | Flutter Web supports full keyboard navigation natively |
| 2.4.3 Focus Order | `FocusTraversalGroup` defines logical tab order in forms |
| 3.3.1 Error Identification | Form errors shown in text adjacent to field, not just color |
| 3.3.2 Labels or Instructions | All form fields use `InputDecoration.labelText` |
| 4.1.2 Name, Role, Value | Riverpod state exposed via Flutter semantics tree |

---

## 14. Team Structure & Roles

| Role | Responsibilities |
|---|---|
| **Project Lead / Architect** | System design, technical decisions, code review sign-off, government liaison |
| **Backend Developer (Django)** | API development, models, migrations, Celery tasks, unit tests |
| **Flutter Developer** | Mobile and web UI, state management, offline sync, platform testing |
| **DevOps Engineer** | Docker, CI/CD, Nginx, PostgreSQL administration, monitoring setup |
| **QA Engineer** | Test planning, integration testing, OWASP scan, UAT coordination |
| **UI/UX Designer** (part-time) | Government branding, WCAG audit, form UX, responsive layouts |

**Minimum viable team:** Backend Developer + Flutter Developer + Project Lead who handles DevOps and QA with the team.

---

## 15. Risk Register

| Risk | Probability | Impact | Mitigation |
|---|---|---|---|
| Flutter Web performance on low-end government computers | Medium | High | Test on representative hardware early; consider lighter web build if needed |
| Poor mobile network for field officers (offline sync fails) | High | High | Offline-first design from Phase 3; test in simulated poor-connectivity conditions |
| Africa's Talking SMS delivery failures | Medium | Medium | Fallback to email for critical notifications; retry queue in Celery |
| Apple App Store review delays for iOS | Medium | Low | Submit iOS build 4+ weeks before go-live; Android first for officers |
| Government IT infrastructure cannot host Docker | Medium | High | Provide bare-metal deployment guide as fallback; engage IT early |
| Data migration from PHP system loses records | Low | High | Migration scripts tested on full copy of production data before cutover |
| Key developer unavailability mid-project | Low | High | Document architecture decisions in ADRs; no tribal knowledge |
| OWASP scan finds critical issue late in project | Low | High | Run OWASP ZAP scan from Phase 5 onwards, not just Phase 7 |
| PostgreSQL hosting cost exceeds budget | Low | Medium | MySQL is a fallback; Django ORM abstracts the difference |
| Scope creep from government stakeholders during UAT | High | Medium | Formal change control process; new requests go to v2 backlog |

---

## 16. Definition of Done

A feature is not done until all of the following are true:

- [ ] **Code complete** — all acceptance criteria implemented
- [ ] **Unit tested** — service layer and serializers have passing unit tests
- [ ] **Integration tested** — API endpoint tested with correct role + wrong role
- [ ] **Flutter widget tested** — screen renders in loading, error, and data states
- [ ] **Code reviewed** — at least one other developer has approved the PR
- [ ] **Migrations reviewed** — schema changes are reviewed before merge
- [ ] **API documented** — OpenAPI spec updated or auto-generated correctly
- [ ] **Security checked** — no hardcoded secrets, input validated, correct permissions applied
- [ ] **Accessibility checked** — semantic labels present, contrast verified
- [ ] **Deployed to staging** — feature works end-to-end on staging environment
- [ ] **Tested on real device** — mobile features tested on actual Android or iOS device

A release is not ready until:

- [ ] All Phase 7 security and performance targets are met
- [ ] UAT sign-off from government stakeholder
- [ ] Runbook delivered to IT operations team
- [ ] Backup and restore tested on production database
- [ ] Mobile apps approved in respective app stores

---

*This plan is the authoritative technical reference for the CDF Management System enterprise rebuild. All architectural decisions documented here should be treated as the agreed baseline. Changes to the architecture require a written amendment to this document.*

---

## 17. UI/UX Design System

### Overview

This section is the authoritative design specification for the CDF Management System enterprise rebuild. It codifies the visual language established in the original system — the Zambian government color identity, branding assets, component behavior, and both light and dark themes — and elevates it to a consistent, accessible, production-grade design system ready for Flutter implementation.

The original developer made the right foundational choices: deep government blue as the authority color, Zambian copper-gold as the accent, full dark mode support, and prominent national branding. Those decisions are preserved here without compromise. What this specification adds is rigor: defined contrast ratios, a strict 8dp spacing grid, explicit component states, a Material 3–compliant token hierarchy, and clear rules for when each element is used.

**Design Principles**

| Principle | What it means in practice |
|---|---|
| **Authority** | Every screen must carry the Zambian government identity. The Coat of Arms, national colors, and official typography signal this is a state system, not a commercial app. |
| **Clarity** | Government users process high volumes of data. Hierarchy must be unambiguous. Labels, status indicators, and actions must be immediately readable without interpretation. |
| **Accessibility** | All text meets WCAG 2.1 AA contrast minimums. All interactive elements are reachable by keyboard and screen reader. Color is never the sole indicator of meaning. |
| **Consistency** | Every instance of every component looks and behaves identically across all screens. No one-off styles. |
| **Restraint** | Animation and decoration serve function, not flair. Transitions confirm state changes. Hover effects indicate interactivity. Nothing moves just to look impressive. |

---

---

### 17.1 Brand Identity & Government Assets

The system carries the full visual identity of the **Government of the Republic of Zambia**. These assets appear on every page and must be reproduced identically in Flutter.

| Asset | File | Usage |
|---|---|---|
| Zambia Coat of Arms | `coat-of-arms-of-zambia.jpg` | Navbar brand (45×45px), login card header (80×80px), all public-facing pages |
| Zambia National Flag | `Flag_of_Zambia.svg` | Footer, right-aligned, 30×20px |
| Favicon | `favicon.ico` | Browser tab icon |

**Navbar brand text:** `Government of Zambia - CDF System`
**Page title format:** `[Page Name] - CDF Management System | Government of Zambia`
**Footer copyright:** `© [Year] Government of the Republic of Zambia. All rights reserved.`
**Legal reference in footer:** `Developed in compliance with the Constituency Development Fund Act Cap 324 and 2022 CDF Guidelines.`

The Coat of Arms is displayed with these CSS effects that must be replicated in Flutter:
- Slight brightness boost (`brightness(1.05)`) and contrast lift (`contrast(1.1)`)
- Drop shadow applied for legibility against the primary gradient background
- Brief scale-up on press to confirm interaction
- Always displayed at consistent dimensions: **45×45dp** in the app bar, **80×80dp** on the login/splash screen

**Brand copy standards**

| Location | Text |
|---|---|
| App bar title | `Government of Zambia — CDF System` |
| Page/tab title | `[Screen Name] · CDF Management System` |
| Login screen subtitle | `Republic of Zambia · Constituency Development Fund` |
| Footer line 1 | `© [Year] Government of the Republic of Zambia. All rights reserved.` |
| Footer line 2 | `Administered under the Constituency Development Fund Act Cap 324 and the 2022 CDF Guidelines.` |

---

### 17.2 Color System

The palette is drawn directly from the existing system and the Zambian national identity. It is organised into four tiers: brand, semantic, neutral, and surface. No color is used outside its defined role.

#### Tier 1 — Brand Colors

These two colors define the system's identity. They are non-negotiable and must appear consistently on every screen.

| Token | Hex | RGB | Role |
|---|---|---|---|
| `color.brand.primary` | `#1A4E8A` | 26, 78, 138 | Authority color. Navbar, card headers, primary buttons, focus rings, active states, stat numbers. |
| `color.brand.primary.dark` | `#0D3A6C` | 13, 58, 108 | Gradient terminus for primary surfaces. Hover/pressed state of primary elements. |
| `color.brand.primary.light` | `#2C6CB0` | 44, 108, 176 | Gradient terminus for lighter surfaces. Used in progress bars and chart fills. |
| `color.brand.accent` | `#E9B949` | 233, 185, 73 | Zambian copper-gold. Navbar bottom border, profile avatar fill, CTAs on dark surfaces, notice box left-stripe. |
| `color.brand.accent.dark` | `#D4A337` | 212, 163, 55 | Gradient terminus for accent surfaces. Pressed state on gold buttons. |

**Primary gradient:** `LinearGradient(135°, #1A4E8A → #0D3A6C)`
**Accent gradient:** `LinearGradient(135°, #E9B949 → #D4A337)`

**Contrast verification (WCAG 2.1):**

| Foreground | Background | Ratio | Grade |
|---|---|---|---|
| White `#FFFFFF` | Primary `#1A4E8A` | **11.3 : 1** | AAA ✓ |
| White `#FFFFFF` | Primary Dark `#0D3A6C` | **14.2 : 1** | AAA ✓ |
| Dark `#212529` | Accent `#E9B949` | **7.6 : 1** | AAA ✓ |
| White `#FFFFFF` | Accent `#E9B949` | **1.98 : 1** | FAIL ✗ — never use white text on gold |
| Primary `#1A4E8A` | White `#FFFFFF` | **11.3 : 1** | AAA ✓ |
| Dark `#212529` | White `#FFFFFF` | **16.1 : 1** | AAA ✓ |

> **Rule:** Gold (`#E9B949`) always carries dark text (`#212529`). Never white.

#### Tier 2 — Semantic Colors

These colors communicate system state. Each has a foreground, background (tint), and dark-mode variant.

| State | Base | Tint (10%) | Dark mode base | Dark mode tint | Usage |
|---|---|---|---|---|---|
| Success | `#28A745` | `#D4EDDA` | `#2A5F3F` | `#1A3A2E` | Approved, completed, positive |
| Warning | `#FFC107` | `#FFF3CD` | `#6A5A2A` | `#3A3120` | Pending, alerts, budget nearing limit |
| Danger | `#DC3545` | `#F8D7DA` | `#5A2A3F` | `#3A1A20` | Rejected, errors, destructive actions |
| Info | `#17A2B8` | `#D1ECF1` | `#2A4D7F` | `#1A2D47` | In-progress, informational notices |

**Rule:** Semantic colors are never used as decorative fills. They are only used to communicate state. A border, icon, or badge that uses a semantic color must be accompanied by a text label — color is not the sole indicator of meaning.

#### Tier 3 — Neutral Scale

| Token | Hex | Usage |
|---|---|---|
| `color.neutral.900` | `#212529` | Primary body text, headings |
| `color.neutral.800` | `#343A40` | Secondary headings |
| `color.neutral.700` | `#495057` | Table cell text |
| `color.neutral.600` | `#6C757D` | Muted / secondary text, placeholder |
| `color.neutral.500` | `#ADB5BD` | Disabled text |
| `color.neutral.400` | `#CED4DA` | Disabled borders |
| `color.neutral.300` | `#DEE2E6` | Default input borders, dividers |
| `color.neutral.200` | `#E9ECEF` | Alternate table rows, card header gradient end |
| `color.neutral.100` | `#F8F9FA` | Light backgrounds, card header gradient start |
| `color.neutral.white` | `#FFFFFF` | Card surfaces, text on dark |
| `color.neutral.black` | `#000000` | Absolute black — used only in overlay opacity values |

#### Tier 4 — Surface Colors

Surfaces define the layered depth of the UI. Light and dark mode surfaces are distinct stacks.

**Light mode:**

| Layer | Token | Hex | What sits here |
|---|---|---|---|
| Page background | `surface.background` | Gradient `#F5F7FA → #E4E8F0` | The page canvas |
| Card | `surface.card` | `#FFFFFF` | All cards, modals, dropdowns |
| Card header | `surface.card.header` | Gradient `#F8F9FA → #E9ECEF` | Card title bars |
| Input | `surface.input` | `#FFFFFF` | Form fields |
| Overlay | `surface.overlay` | `rgba(0,0,0,0.5)` | Modal backdrops |

**Dark mode:**

| Layer | Token | Hex | What sits here |
|---|---|---|---|
| Page background | `surface.background` | Gradient `#1A1A2E → #0F3460` | The page canvas |
| Card | `surface.card` | `#2A2A3E` | All cards, modals |
| Card header | `surface.card.header` | `#3A3A4E` | Card title bars |
| Input | `surface.input` | `#3A3A4E` | Form fields at rest |
| Input focused | `surface.input.focused` | `#424455` | Form fields when focused |
| Divider / border | `surface.border` | `#404052` | All borders and dividers |
| Scrollbar track | `surface.scrollbar.track` | `#3A3A4E` | |
| Scrollbar thumb | `surface.scrollbar.thumb` | `#505062` | |

---

### 17.3 Dark Mode

Dark mode is a first-class feature of this system — not an afterthought. The original developer built complete coverage across every component. That coverage must be maintained.

**Toggle behavior:**
- Three modes: `light`, `dark`, `auto`
- `auto` follows the device `prefers-color-scheme` setting
- User's explicit choice is persisted in `SharedPreferences`
- Setting is applied immediately on app start, before first frame renders, to prevent a flash of the wrong theme
- A toggle is accessible from the user profile/settings screen

**Dark mode design rules:**
1. Primary brand blue `#1A4E8A` is retained in dark mode — it is the identity color and must remain recognisable
2. The accent gold `#E9B949` is retained — it is the national accent and must remain visible
3. Background surfaces shift from light grey to deep navy (`#1A1A2E → #0F3460`) — not pure black, which strains the eyes
4. Card surfaces are elevated slightly above the background (`#2A2A3E`) to maintain depth hierarchy without harsh borders
5. Text shifts to `#E0E0E0` — not pure white, which creates excessive glare
6. All semantic colors retain their hue but are darkened in their tint versions to prevent bloom on dark surfaces

---

### 17.4 Typography

**Typeface:** Inter (Google Fonts)
Inter is named explicitly in the original CSS font stack. It is clean, highly legible at small sizes, designed for screen readability, and carries the neutral authority appropriate for a government data system.

**Flutter package:** `google_fonts: ^6.x` — `GoogleFonts.inter()`

#### Type Scale

The scale follows Material 3 naming with values mapped directly from the original CSS.

| Role | Flutter style name | Size | Weight | Line height | Letter spacing | Usage |
|---|---|---|---|---|---|---|
| Display Large | `displayLarge` | 48sp | 900 | 1.15 | −0.25 | Stat numbers on dashboard |
| Display Medium | `displayMedium` | 36sp | 800 | 1.2 | −0.25 | Page hero headings |
| Headline Large | `headlineLarge` | 30sp | 800 | 1.3 | 0 | Dashboard welcome title |
| Headline Medium | `headlineMedium` | 24sp | 700 | 1.4 | 0 | Section headings |
| Headline Small | `headlineSmall` | 20sp | 700 | 1.4 | 0 | Card titles, section labels |
| Title Large | `titleLarge` | 18sp | 800 | 1.4 | 0 | Card header titles, navbar brand |
| Title Medium | `titleMedium` | 16sp | 600 | 1.5 | 0.15 | Form labels, table headers |
| Title Small | `titleSmall` | 14sp | 600 | 1.5 | 0.1 | Secondary labels, nav links |
| Body Large | `bodyLarge` | 16sp | 400 | 1.7 | 0 | Primary body text |
| Body Medium | `bodyMedium` | 14sp | 400 | 1.7 | 0 | Secondary body, table cells |
| Body Small | `bodySmall` | 12sp | 400 | 1.6 | 0 | Captions, fine print |
| Label Large | `labelLarge` | 14sp | 700 | 1.4 | 0.8 | Buttons (uppercase) |
| Label Medium | `labelMedium` | 12sp | 600 | 1.4 | 0.5 | Status badges |
| Label Small | `labelSmall` | 11sp | 600 | 1.4 | 0.5 | Tags, timestamps |

**Typography rules:**
- Buttons always use `labelLarge` — uppercase, letter-spaced, weight 700
- Stat numbers on dashboard cards use `displayLarge` in `color.brand.primary`
- Card header titles use `titleLarge` in `color.brand.primary`
- Body text line height is always 1.7 — the original system's choice and correct for dense government data
- Never use a weight below 400 in the app

---

### 17.5 Spacing — 8dp Grid

All spacing is derived from an 8dp base unit. This is the Material Design standard and produces consistent visual rhythm across all screen sizes.

| Token | Value | Common use |
|---|---|---|
| `space.1` | 4dp | Icon padding, tight gaps |
| `space.2` | 8dp | Inline element gaps, icon-to-label |
| `space.3` | 12dp | Compact padding |
| `space.4` | 16dp | Standard padding — default horizontal screen margin |
| `space.5` | 20dp | Form field internal padding |
| `space.6` | 24dp | Card padding, section gaps |
| `space.8` | 32dp | Large card padding, section separators |
| `space.10` | 40dp | Hero area padding |
| `space.12` | 48dp | Dashboard header padding |
| `space.16` | 64dp | Large section separators |
| `space.20` | 80dp | Top padding on login screen |

**Rule:** No hardcoded spacing value in any widget. Every `padding`, `margin`, and `SizedBox` references a `space.*` token.

---

### 17.6 Shape — Border Radius

| Token | Value | Usage |
|---|---|---|
| `shape.xs` | 4dp | Tags, small chips |
| `shape.sm` | 8dp | Input group prefixes, dropdown items, scrollbar |
| `shape.md` | 12dp | Buttons, form inputs, modals, tool cards |
| `shape.lg` | 16dp | Content cards, stat cards, login card |
| `shape.xl` | 20dp | Bottom sheets, large modals |
| `shape.pill` | 999dp | Status badges, notification chips |
| `shape.circle` | 50% | Profile avatar |

---

### 17.7 Elevation

Elevation is expressed as `BoxShadow` in Flutter and conveys the physical layer of a surface above the page canvas. The system uses five levels.

| Level | Token | Shadow | Usage |
|---|---|---|---|
| 0 | `elevation.none` | No shadow | Flat elements — dividers, chip outlines |
| 1 | `elevation.low` | `0 2px 8px rgba(0,0,0,0.06)` | Resting tool cards, project list cards |
| 2 | `elevation.medium` | `0 4px 12px rgba(0,0,0,0.08)` | Content cards, dropdowns, navbar |
| 3 | `elevation.high` | `0 8px 25px rgba(0,0,0,0.15)` | Hovered cards, footer, login card |
| 4 | `elevation.overlay` | `0 12px 40px rgba(0,0,0,0.22)` | Modals, focused buttons, active drawers |

**Profile avatar accent glow (gold only):** `0 12px 30px rgba(233,185,73,0.4)` — applied on press/hover. This is the only colored shadow in the system.

**Rule:** Elevation increases on interaction (hover → pressed), never decreases. A card at rest uses `elevation.low`; on hover it rises to `elevation.high`.

---

### 17.8 Motion

Motion confirms state changes and directs attention. It is never decorative.

**Easing curve:** `Curves.easeInOut` — equivalent to CSS `cubic-bezier(0.4, 0, 0.2, 1)` (Material standard). Used for all transitions.

| Duration token | Value | Usage |
|---|---|---|
| `motion.instant` | 100ms | Ripple feedback, checkbox toggle |
| `motion.fast` | 150ms | Button state change, icon swap |
| `motion.standard` | 300ms | Card hover elevation, drawer open, theme switch |
| `motion.slow` | 500ms | Page-level enter transitions, shimmer sweep |

#### Screen Entry Animation

Every screen enters with a combined fade + upward slide:
- `FadeTransition`: opacity 0.0 → 1.0
- `SlideTransition`: offset `(0, 0.04)` → `(0, 0)` (subtle, 4% upward)
- Duration: 300ms, `Curves.easeOut`

#### Interaction States

| Interaction | Visual response |
|---|---|
| Card hover (web) | `elevation.low → elevation.high` + `translateY(-6dp)` |
| Button hover (web) | `elevation.medium → elevation.overlay` + `translateY(-2dp)` |
| Button press (all) | Scale `1.0 → 0.97` + ripple |
| Tool card hover (web) | Background inverts to primary gradient; icon and text transition to white (300ms) |
| Coat of Arms press | Scale `1.0 → 1.08` |
| Nav item active | Golden underline `width 0 → 80%` from center (300ms) |

**Shimmer on primary buttons:** On hover (web only), a white highlight sweeps left-to-right across the button face in 500ms. This is the one deliberate premium touch retained from the original design. It is subtle — `rgba(255,255,255,0.25)` opacity — and must not be applied to any other component.

**Loading state (buttons):** On async submit, button label swaps to a `CircularProgressIndicator` (white, size 16dp) and the button is disabled. Original label restores on completion or error.

---

### 17.9 Icon System

**Library:** Font Awesome 6.4.0 Free
**Flutter package:** `font_awesome_flutter`

Icons are always paired with a text label except in contexts where space is severely constrained (mobile bottom navigation, action icon buttons). Icon-only elements must carry a `Tooltip`.

**Icon sizing:**

| Context | Size |
|---|---|
| App bar actions | 20dp |
| Navigation rail / bottom nav | 24dp |
| Inline with body text | 16dp |
| Form input prefix | 18dp |
| Card header title | 20dp |
| Tool card feature icon | 40dp |
| Dashboard stat card | 28dp |
| Empty state illustration | 64dp |

**Icons by feature area:**

| Domain | Icons used |
|---|---|
| Authentication | `faSignInAlt`, `faSignOutAlt`, `faLock`, `faUser`, `faKey`, `faEye`, `faEyeSlash`, `faUserPlus` |
| Dashboard & analytics | `faChartLine`, `faChartBar`, `faChartPie`, `faTachometerAlt` |
| Projects | `faProjectDiagram`, `faTasks`, `faCheckCircle`, `faTimesCircle`, `faFolder` |
| Users & profiles | `faUsers`, `faUserEdit`, `faUserCheck`, `faUserTimes`, `faIdCard` |
| Financial | `faMoneyBillWave`, `faReceipt`, `faCoins`, `faCalculator`, `faWallet` |
| Progress & media | `faChartLine`, `faPercentage`, `faCamera`, `faImage` |
| Site visits & maps | `faMapMarkerAlt`, `faMap`, `faCompass`, `faCalendarAlt`, `faClock` |
| Evaluations | `faClipboardCheck`, `faStar`, `faAward`, `faCertificate`, `faPoll` |
| Communication | `faBell`, `faEnvelope`, `faComment`, `faExclamationTriangle` |
| Settings | `faCog`, `faSlidersH`, `faShieldAlt`, `faDatabase`, `faPaintBrush` |
| System status | `faInfoCircle`, `faExclamationCircle`, `faCheck`, `faTimes`, `faSpinner` |
| CRUD actions | `faPlus`, `faEdit`, `faTrash`, `faEye`, `faDownload`, `faUpload`, `faSearch`, `faFilter` |
| Government / legal | `faLandmark`, `faFlag`, `faBalanceScale`, `faGavel` |

---

### 17.10 Component Library

Each component is defined by its resting state, interactive states, and variants. All measurements are in dp.

---

#### App Bar (Navbar)

```
Height:             64dp
Background:         LinearGradient(135°, #1A4E8A → #0D3A6C)
Bottom border:      3dp solid #E9B949                       ← the signature golden stripe
Elevation:          elevation.medium (shadow beneath)
Blur (web):         BackdropFilter blur 10dp

Leading:            Coat of Arms image (45×45dp)
                    + Brand text: "Government of Zambia — CDF System"
                    + Font: titleLarge, white
                    + Gap between image and text: 12dp

Actions:
  Nav link resting:   white, weight 600, 15sp, padding 12×16dp, border-radius 8dp
  Nav link hover:     rgba(255,255,255,0.12) background + translateY(-1dp)
  Nav link active:    rgba(255,255,255,0.15) background
                      + golden underline (3dp, width expands from center, 300ms)

Mobile:             Hamburger menu → drawer (primary gradient background)
```

---

#### Page Header Banner

```
Background:         LinearGradient(135°, #1A4E8A → #0D3A6C)
Padding:            48dp top, 40dp bottom
Decorative overlay: SVG diagonal polygon, rgba(255,255,255,0.05)

Profile avatar:
  Size:             100×100dp, circle
  Fill:             LinearGradient(135°, #E9B949 → #D4A337)
  Content:          User initials, 2 characters, displayMedium, #212529
  Border:           4dp solid rgba(255,255,255,0.3)
  Shadow:           elevation.high
  Hover/press glow: gold glow shadow

Welcome text:       headlineLarge, white, weight 800
Role subtitle:      bodyLarge, rgba(255,255,255,0.9)
```

---

#### Stat Card

```
Background:         surface.card (#FFFFFF / dark: #2A2A3E)
Border-radius:      shape.lg (16dp)
Elevation resting:  elevation.medium
Elevation hover:    elevation.high + translateY(-6dp)
Top accent bar:     5dp, LinearGradient(#1A4E8A → #2C6CB0)
Padding:            32dp vertical, 24dp horizontal, centered

Stat number:        displayLarge (48sp), weight 900, #1A4E8A
                    text shadow: 0 2dp 4dp rgba(26,78,138,0.15)
Stat title:         titleMedium (16sp), weight 700, neutral.900
Stat subtitle:      bodySmall (12sp), weight 500, neutral.600

Transition:         300ms, Curves.easeInOut
```

---

#### Content Card

```
Background:         surface.card
Border-radius:      shape.lg (16dp)
Border:             1dp solid rgba(0,0,0,0.03)
Elevation resting:  elevation.medium
Elevation hover:    elevation.high + translateY(-2dp)

Card header:
  Background:       surface.card.header (gradient #F8F9FA → #E9ECEF)
  Bottom border:    3dp solid #1A4E8A
  Padding:          24dp
  Title:            titleLarge, weight 800, #1A4E8A
                    displayed with icon (20dp) at 12dp gap
```

---

#### Tool / Quick-Action Card

```
Background resting: surface.card
Border-radius:      shape.md (12dp)
Left border:        5dp solid [role color — see variants below]
Padding:            32dp vertical, 24dp horizontal, centered column
Elevation resting:  elevation.low

Feature icon:       40dp, color matches left border
Title:              titleSmall, weight 700
Description:        bodySmall, neutral.600

Hover / press state:
  Background:       LinearGradient(135°, #1A4E8A → #0D3A6C)
  Text + icon:      white
  Elevation:        elevation.overlay + translateY(-6dp) + scale(1.01)
  Transition:       300ms standard

Color variants (left border + icon color):
  Default:          #1A4E8A  (primary)
  Success:          #28A745
  Warning:          #FFC107
  Danger:           #DC3545
  Info:             #17A2B8
```

---

#### Primary Button

```
Background:         LinearGradient(135°, #1A4E8A → #0D3A6C)
Foreground:         #FFFFFF
Border:             none
Height:             48dp minimum
Padding:            16dp vertical, 24dp horizontal
Border-radius:      shape.md (12dp)
Label:              labelLarge — uppercase, weight 700, letter-spacing 0.8
Elevation:          elevation.medium

Hover (web):
  Elevation:        elevation.overlay
  Transform:        translateY(-2dp)
  Shimmer:          white highlight sweeps left→right, 500ms, rgba(255,255,255,0.25)

Pressed:
  Scale:            0.97
  Elevation:        elevation.low

Disabled:
  Opacity:          0.5, no shadow

Loading:
  Label:            hidden
  Content:          CircularProgressIndicator (white, 16dp, strokeWidth 2dp)
  Disabled:         true
```

---

#### Accent CTA Button (on dark/primary backgrounds)

```
Background:         LinearGradient(135°, #E9B949 → #D4A337)
Foreground:         #212529  ← dark text on gold, never white
Border:             none
Height:             48dp minimum
Padding:            16dp vertical, 32dp horizontal
Border-radius:      shape.md (12dp)
Label:              labelLarge — uppercase, weight 700

Hover (web):
  Background:       LinearGradient(135°, #D4A337 → #C4952E)
  Transform:        translateY(-2dp) + elevation.high

Pressed:
  Scale:            0.97
```

---

#### Outline Button

```
Background:         transparent
Border:             2dp solid #1A4E8A
Foreground:         #1A4E8A
Height:             48dp minimum
Padding:            16dp vertical, 24dp horizontal
Border-radius:      shape.md (12dp)
Label:              labelLarge — uppercase, weight 700

Hover (web):
  Background:       fills with #1A4E8A
  Foreground:       #FFFFFF
  Transform:        translateY(-2dp)

On dark background variant:
  Border:           2dp solid rgba(255,255,255,0.7)
  Foreground:       #FFFFFF
  Hover bg:         #FFFFFF
  Hover foreground: #1A4E8A
```

---

#### Form Input

```
Fill:               surface.card (#FFFFFF / dark: #3A3A4E)
Border resting:     2dp solid neutral.300 (#DEE2E6 / dark: #505062)
Border focused:     2dp solid #1A4E8A
Border error:       2dp solid #DC3545
Border-radius:      shape.md (12dp)
Padding:            16dp vertical, 20dp horizontal
Font:               bodyLarge, 16sp

Focus ring:         box-shadow 0 0 0 3dp rgba(26,78,138,0.25)
Error ring:         box-shadow 0 0 0 3dp rgba(220,53,69,0.25)

Prefix icon group:
  Background:       #1A4E8A
  Foreground:       #FFFFFF
  Width:            48dp
  Border-radius:    shape.md left side only

Helper / error text:
  Font:             bodySmall
  Color:            neutral.600 (helper) / #DC3545 (error)
  Margin-top:       4dp
  Prefix:           icon (faInfoCircle / faExclamationCircle)
```

---

#### Status Badge / Chip

```
Shape:              shape.pill (full radius)
Padding:            4dp vertical, 12dp horizontal
Font:               labelMedium (12sp), weight 600, uppercase

States:
  planning:     bg #E9ECEF,  text #495057
  in-progress:  bg #D1ECF1,  text #138496
  pending:      bg #FFF3CD,  text #856404
  approved:     bg #D4EDDA,  text #155724
  completed:    bg #D4EDDA,  text #155724
  rejected:     bg #F8D7DA,  text #721C24
  cancelled:    bg #E9ECEF,  text #495057
  locked:       bg #F8D7DA,  text #721C24

Dark mode: backgrounds use their darker semantic tint variants from Section 17.2
```

---

#### Progress Bar

```
Track:          neutral.200 (#E9ECEF), border-radius shape.sm (8dp), height 8dp
Fill:           LinearGradient(135°, #1A4E8A → #2C6CB0)
Over-budget:    fill #DC3545
Animation:      width transition 600ms Curves.easeOut on initial render

Featured (project detail):
  Height:       10dp
  Label:        percentage shown above right, bodySmall, #1A4E8A
```

---

#### Government Notice Box

```
Background:     surface.card.header (light gradient)
Border:         2dp solid #1A4E8A
Border-radius:  shape.md (12dp)
Padding:        20dp
Left accent:    6dp wide strip, LinearGradient(#E9B949 → #D4A337) ← the gold stripe

Title:          titleSmall, weight 600, #1A4E8A
                prefix: faInfoCircle (18dp, #1A4E8A)
Body:           bodySmall, neutral.600
```

---

#### Data Table

```
Header row:
  Background:   surface.card.header
  Font:         titleSmall, weight 600, neutral.700
  Padding:      12dp vertical, 16dp horizontal
  Border-bottom: 2dp solid #1A4E8A

Body rows:
  Height:       52dp minimum
  Font:         bodyMedium, neutral.900
  Padding:      12dp vertical, 16dp horizontal
  Divider:      1dp solid neutral.200

Alternate rows (striped):
  Background:   neutral.100 (#F8F9FA / dark: #32323F)

Hover row:
  Background:   rgba(26,78,138,0.04)

Actions column:
  Align:        right
  Spacing:      8dp between action buttons
```

---

#### Notification Item

```
Icon:           Left-aligned, 24dp, semantic color
Title:          titleSmall, weight 600
Message:        bodySmall, neutral.600
Timestamp:      labelSmall, neutral.500
Unread dot:     8dp circle, #1A4E8A, top-right of icon

Urgent variant:
  Left border:  4dp solid #DC3545
  Background:   rgba(220,53,69,0.04)
```

---

#### Modal / Dialog

```
Background:     surface.card
Border-radius:  shape.xl (20dp)
Elevation:      elevation.overlay
Max-width:      560dp
Padding:        0 (header/body/footer have own padding)

Header:
  Background:   LinearGradient(135°, #1A4E8A → #0D3A6C)
  Padding:      24dp
  Title:        titleLarge, white, weight 800
  Close button: faTimesCircle, white, top-right

Body:
  Padding:      24dp
  Font:         bodyLarge

Footer:
  Padding:      16dp 24dp
  Border-top:   1dp solid neutral.200
  Button layout: right-aligned, 8dp gap
```

---

### 17.11 Screen Layouts

#### Authenticated Shell — Web (≥ 1200dp)

```
┌──────────────────────────────────────────────────────────────┐
│  APP BAR  [Coat of Arms + Brand]          [Nav Links] [User] │  64dp, primary gradient + gold bottom border
├──────────────────────────────────────────────────────────────┤
│  PAGE HEADER BANNER                                          │  Primary gradient, profile avatar + greeting
│  [Avatar]  [Welcome, Name]  [Role]  [Action Buttons]        │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  CONTENT AREA  (light gradient background, padding 32dp)    │
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │  Stat cards — 4 columns
│  │ Stat     │  │ Stat     │  │ Stat     │  │ Stat     │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
│                                                              │
│  ┌───────────────────────────┐  ┌──────────────────────┐   │  2-column content
│  │  Content Card             │  │  Content Card        │   │
│  │  (data table / list)      │  │  (chart / activity)  │   │
│  └───────────────────────────┘  └──────────────────────┘   │
│                                                              │
│  QUICK ACTION GRID  (auto-fill, min 240dp per card)         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │  Tool cards
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
│                                                              │
├──────────────────────────────────────────────────────────────┤
│  FOOTER  [Copyright] [Official System]        [Zambia Flag] │  Primary gradient, gold disclaimer stripe
└──────────────────────────────────────────────────────────────┘
```

#### Authenticated Shell — Tablet (600–1199dp)

- App bar: same, nav links collapse to icon rail on left
- Stat cards: 2 columns
- Content: single column
- Tool grid: 2 columns
- Footer: stacked

#### Authenticated Shell — Mobile (< 600dp)

- App bar: hamburger → modal drawer (primary gradient)
- Header banner: compact (avatar 64dp, greeting 2 lines)
- Stat cards: 2 columns, compact padding
- Content: single column, full width
- Tool grid: 2 columns
- Bottom navigation bar replaces left rail
- Footer: hidden (content accessible via app bar menu)

---

### 17.12 Flutter Theme Implementation

Complete, production-ready `AppTheme` class. Place at `lib/core/theme/app_theme.dart`.

```dart
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// CDF Management System — Design System
/// Government of the Republic of Zambia
///
/// All values sourced from the original system's CSS design tokens.
/// Do not modify color values without design review.
class AppTheme {

  // ─────────────────────────────────────────────────────────
  // Brand Colors
  // ─────────────────────────────────────────────────────────

  static const Color primary      = Color(0xFF1A4E8A);
  static const Color primaryDark  = Color(0xFF0D3A6C);
  static const Color primaryLight = Color(0xFF2C6CB0);
  static const Color accent       = Color(0xFFE9B949);
  static const Color accentDark   = Color(0xFFD4A337);

  // ─────────────────────────────────────────────────────────
  // Semantic Colors
  // ─────────────────────────────────────────────────────────

  static const Color success      = Color(0xFF28A745);
  static const Color successTint  = Color(0xFFD4EDDA);
  static const Color warning      = Color(0xFFFFC107);
  static const Color warningTint  = Color(0xFFFFF3CD);
  static const Color danger       = Color(0xFFDC3545);
  static const Color dangerTint   = Color(0xFFF8D7DA);
  static const Color info         = Color(0xFF17A2B8);
  static const Color infoTint     = Color(0xFFD1ECF1);

  // ─────────────────────────────────────────────────────────
  // Neutral Scale
  // ─────────────────────────────────────────────────────────

  static const Color neutral900 = Color(0xFF212529);
  static const Color neutral700 = Color(0xFF495057);
  static const Color neutral600 = Color(0xFF6C757D);
  static const Color neutral300 = Color(0xFFDEE2E6);
  static const Color neutral200 = Color(0xFFE9ECEF);
  static const Color neutral100 = Color(0xFFF8F9FA);

  // ─────────────────────────────────────────────────────────
  // Dark Mode Surfaces
  // ─────────────────────────────────────────────────────────

  static const Color darkBg1     = Color(0xFF1A1A2E);
  static const Color darkBg2     = Color(0xFF0F3460);
  static const Color darkSurface = Color(0xFF2A2A3E);
  static const Color darkHeader  = Color(0xFF3A3A4E);
  static const Color darkInput   = Color(0xFF3A3A4E);
  static const Color darkText    = Color(0xFFE0E0E0);
  static const Color darkMuted   = Color(0xFFA0A0B0);
  static const Color darkBorder  = Color(0xFF404052);

  // ─────────────────────────────────────────────────────────
  // Gradients
  // ─────────────────────────────────────────────────────────

  static const LinearGradient primaryGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [primary, primaryDark],
  );

  static const LinearGradient accentGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [accent, accentDark],
  );

  static const LinearGradient lightPageGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFFF5F7FA), Color(0xFFE4E8F0)],
  );

  static const LinearGradient darkPageGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [darkBg1, darkBg2],
  );

  static const LinearGradient cardHeaderGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFFF8F9FA), Color(0xFFE9ECEF)],
  );

  // ─────────────────────────────────────────────────────────
  // Elevation (BoxShadow lists)
  // ─────────────────────────────────────────────────────────

  static const List<BoxShadow> elevationLow = [
    BoxShadow(color: Color(0x0F000000), blurRadius: 8, offset: Offset(0, 2)),
  ];

  static const List<BoxShadow> elevationMedium = [
    BoxShadow(color: Color(0x14000000), blurRadius: 12, offset: Offset(0, 4)),
  ];

  static const List<BoxShadow> elevationHigh = [
    BoxShadow(color: Color(0x26000000), blurRadius: 25, offset: Offset(0, 8)),
  ];

  static const List<BoxShadow> elevationOverlay = [
    BoxShadow(color: Color(0x38000000), blurRadius: 40, offset: Offset(0, 12)),
  ];

  static const List<BoxShadow> accentGlow = [
    BoxShadow(color: Color(0x66E9B949), blurRadius: 30, offset: Offset(0, 12)),
  ];

  // ─────────────────────────────────────────────────────────
  // Typography
  // ─────────────────────────────────────────────────────────

  static TextTheme _buildTextTheme(Color bodyColor) {
    return GoogleFonts.interTextTheme().copyWith(
      displayLarge: GoogleFonts.inter(
        fontSize: 48, fontWeight: FontWeight.w900,
        color: primary, letterSpacing: -0.25, height: 1.15,
      ),
      displayMedium: GoogleFonts.inter(
        fontSize: 36, fontWeight: FontWeight.w800,
        color: bodyColor, letterSpacing: -0.25, height: 1.2,
      ),
      headlineLarge: GoogleFonts.inter(
        fontSize: 30, fontWeight: FontWeight.w800,
        color: bodyColor, height: 1.3,
      ),
      headlineMedium: GoogleFonts.inter(
        fontSize: 24, fontWeight: FontWeight.w700,
        color: bodyColor, height: 1.4,
      ),
      headlineSmall: GoogleFonts.inter(
        fontSize: 20, fontWeight: FontWeight.w700,
        color: primary, height: 1.4,
      ),
      titleLarge: GoogleFonts.inter(
        fontSize: 18, fontWeight: FontWeight.w800,
        color: primary, height: 1.4,
      ),
      titleMedium: GoogleFonts.inter(
        fontSize: 16, fontWeight: FontWeight.w600,
        color: bodyColor, letterSpacing: 0.15, height: 1.5,
      ),
      titleSmall: GoogleFonts.inter(
        fontSize: 14, fontWeight: FontWeight.w600,
        color: bodyColor, letterSpacing: 0.1, height: 1.5,
      ),
      bodyLarge: GoogleFonts.inter(
        fontSize: 16, fontWeight: FontWeight.w400,
        color: bodyColor, height: 1.7,
      ),
      bodyMedium: GoogleFonts.inter(
        fontSize: 14, fontWeight: FontWeight.w400,
        color: bodyColor, height: 1.7,
      ),
      bodySmall: GoogleFonts.inter(
        fontSize: 12, fontWeight: FontWeight.w400,
        color: neutral600, height: 1.6,
      ),
      labelLarge: GoogleFonts.inter(
        fontSize: 14, fontWeight: FontWeight.w700,
        letterSpacing: 0.8,
      ),
      labelMedium: GoogleFonts.inter(
        fontSize: 12, fontWeight: FontWeight.w600,
        letterSpacing: 0.5,
      ),
      labelSmall: GoogleFonts.inter(
        fontSize: 11, fontWeight: FontWeight.w600,
        letterSpacing: 0.5,
      ),
    );
  }

  // ─────────────────────────────────────────────────────────
  // Light Theme
  // ─────────────────────────────────────────────────────────

  static ThemeData get light => ThemeData(
    useMaterial3: true,
    colorScheme: const ColorScheme.light(
      primary: primary,
      onPrimary: Colors.white,
      primaryContainer: Color(0xFFDCE8F8),
      onPrimaryContainer: primaryDark,
      secondary: accent,
      onSecondary: neutral900,
      secondaryContainer: Color(0xFFFDF3D7),
      onSecondaryContainer: Color(0xFF6B4C00),
      error: danger,
      onError: Colors.white,
      surface: Colors.white,
      onSurface: neutral900,
      surfaceContainerHighest: neutral100,
      outline: neutral300,
    ),
    scaffoldBackgroundColor: const Color(0xFFF5F7FA),
    textTheme: _buildTextTheme(neutral900),
    appBarTheme: AppBarTheme(
      backgroundColor: primary,
      foregroundColor: Colors.white,
      elevation: 8,
      shadowColor: Colors.black26,
      titleTextStyle: GoogleFonts.inter(
        fontSize: 18, fontWeight: FontWeight.w800, color: Colors.white,
      ),
      iconTheme: const IconThemeData(color: Colors.white, size: 24),
    ),
    cardTheme: CardThemeData(
      color: Colors.white,
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      margin: const EdgeInsets.all(0),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: primary,
        foregroundColor: Colors.white,
        elevation: 4,
        shadowColor: Colors.black26,
        minimumSize: const Size(0, 48),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: GoogleFonts.inter(
          fontSize: 14, fontWeight: FontWeight.w700, letterSpacing: 0.8,
        ),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: primary,
        side: const BorderSide(color: primary, width: 2),
        minimumSize: const Size(0, 48),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: GoogleFonts.inter(
          fontSize: 14, fontWeight: FontWeight.w700, letterSpacing: 0.8,
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: neutral300, width: 2),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: neutral300, width: 2),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: primary, width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: danger, width: 2),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: danger, width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      labelStyle: GoogleFonts.inter(fontWeight: FontWeight.w600, color: neutral600),
      hintStyle: GoogleFonts.inter(color: neutral600),
    ),
    dividerTheme: const DividerThemeData(
      color: neutral200, thickness: 1, space: 1,
    ),
    chipTheme: ChipThemeData(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      labelStyle: GoogleFonts.inter(fontSize: 12, fontWeight: FontWeight.w600),
    ),
    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      backgroundColor: neutral900,
      contentTextStyle: GoogleFonts.inter(color: Colors.white),
    ),
    tooltipTheme: TooltipThemeData(
      decoration: BoxDecoration(
        color: neutral900,
        borderRadius: BorderRadius.circular(8),
      ),
      textStyle: GoogleFonts.inter(fontSize: 12, color: Colors.white),
    ),
    pageTransitionsTheme: const PageTransitionsTheme(
      builders: {
        TargetPlatform.android: FadeUpwardsPageTransitionsBuilder(),
        TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
        TargetPlatform.macOS: CupertinoPageTransitionsBuilder(),
        TargetPlatform.windows: FadeUpwardsPageTransitionsBuilder(),
        TargetPlatform.linux: FadeUpwardsPageTransitionsBuilder(),
      },
    ),
  );

  // ─────────────────────────────────────────────────────────
  // Dark Theme
  // ─────────────────────────────────────────────────────────

  static ThemeData get dark => ThemeData(
    useMaterial3: true,
    colorScheme: const ColorScheme.dark(
      primary: primary,
      onPrimary: Colors.white,
      primaryContainer: primaryDark,
      onPrimaryContainer: Colors.white,
      secondary: accent,
      onSecondary: neutral900,
      error: danger,
      onError: Colors.white,
      surface: darkSurface,
      onSurface: darkText,
      surfaceContainerHighest: darkHeader,
      outline: darkBorder,
    ),
    scaffoldBackgroundColor: darkBg1,
    textTheme: _buildTextTheme(darkText).copyWith(
      headlineSmall: GoogleFonts.inter(
        fontSize: 20, fontWeight: FontWeight.w700, color: darkText,
      ),
      titleLarge: GoogleFonts.inter(
        fontSize: 18, fontWeight: FontWeight.w800, color: darkText,
      ),
      bodySmall: GoogleFonts.inter(
        fontSize: 12, fontWeight: FontWeight.w400, color: darkMuted,
      ),
    ),
    appBarTheme: AppBarTheme(
      backgroundColor: darkBg2,
      foregroundColor: darkText,
      elevation: 8,
      titleTextStyle: GoogleFonts.inter(
        fontSize: 18, fontWeight: FontWeight.w800, color: darkText,
      ),
    ),
    cardTheme: CardThemeData(
      color: darkSurface,
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: primary,
        foregroundColor: Colors.white,
        elevation: 4,
        minimumSize: const Size(0, 48),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: GoogleFonts.inter(
          fontSize: 14, fontWeight: FontWeight.w700, letterSpacing: 0.8,
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: darkInput,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: darkBorder, width: 2),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: darkBorder, width: 2),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: primary, width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      labelStyle: GoogleFonts.inter(color: darkMuted, fontWeight: FontWeight.w600),
      hintStyle: GoogleFonts.inter(color: darkMuted),
    ),
    dividerTheme: const DividerThemeData(color: darkBorder, thickness: 1, space: 1),
  );
}
```

---

### 17.13 Design Rules Reference

A concise reference for any developer working on this system. These rules are non-negotiable.

| # | Rule |
|---|---|
| 1 | **Never use white text on gold** (`#E9B949`). Contrast ratio is 1.98:1 — a WCAG fail. Always use `#212529` on gold. |
| 2 | **The Coat of Arms appears on every authenticated screen** in the app bar. It is the legal identity signal of the system. |
| 3 | **The navbar always carries the 3dp gold bottom border** (`#E9B949`). It is the visual signature of this system. |
| 4 | **The profile avatar is always a gold circle with initials** — not a generic person icon. |
| 5 | **Color is never the sole indicator of state.** Every badge, alert, and status indicator must carry a text label alongside its color. |
| 6 | **Dark mode is mandatory.** The user's preference is persisted and applied before the first frame renders. |
| 7 | **No spacing value is hardcoded.** All padding and margins reference the 8dp spacing tokens. |
| 8 | **Buttons are uppercase with letter-spacing 0.8.** This is the typographic convention of this system — do not change. |
| 9 | **The shimmer effect on primary buttons is applied on hover/web only** — not on mobile press. It is subtle (25% white opacity) and must not be applied to other components. |
| 10 | **Animation serves function.** The only permitted animations are: state transitions (elevation, color), screen entry (fade + slide), loading indicators, and the button shimmer. No looping decorative animations. |
| 11 | **The footer on web layout mirrors the navbar gradient** — `#1A4E8A → #0D3A6C`. The golden left-border disclaimer box within the footer is required on all web pages. |
| 12 | **All WCAG 2.1 AA contrast requirements must pass** before any screen is considered complete. Use a contrast checker before submitting a PR. |
| 13 | **Inter is the only typeface.** No system fonts, no mixed families. All text in the application uses `GoogleFonts.inter()`. |

---

*End of UI/UX Design System. All color values, measurements, and component specifications are sourced directly from `login.php`, `admin_dashboard.php`, and `includes/global_theme.php` of the original school project, then elevated to enterprise-grade specification.*

#### Primary Colors

| Token | Hex | Usage |
|---|---|---|
| `--primary` | `#1a4e8a` | Navbar, card headers, borders, buttons, links, stat numbers, focus rings |
| `--primary-dark` | `#0d3a6c` | Gradient end, hover states, dark navbar |
| `--primary-light` | `#2c6cb0` | Gradient end on lighter elements, hover states |

**Primary gradient:** `linear-gradient(135deg, #1a4e8a 0%, #0d3a6c 100%)`

The primary blue `#1a4e8a` is the dominant brand color. It represents government authority and is derived from the deep blue present in the Zambian flag and Coat of Arms.

#### Secondary / Accent Colors

| Token | Hex | Usage |
|---|---|---|
| `--secondary` | `#e9b949` | CTA buttons on dark backgrounds, profile avatar fill, golden accent borders, shimmer effects |
| `--secondary-dark` | `#d4a337` | Secondary gradient end, hover on secondary buttons |

**Secondary gradient:** `linear-gradient(135deg, #e9b949 0%, #d4a337 100%)`

The secondary gold `#e9b949` is derived from the Zambian national colors (green, black, orange/copper) and creates the government gold accent seen throughout. It is used as the bottom border on the navbar, the profile avatar background, and the highlight on government notice components.

#### Semantic Colors

| Token | Hex | Light Variant | Dark Variant | Usage |
|---|---|---|---|---|
| `--success` | `#28a745` | `#d4edda` | `#1e7e34` | Approved status, completed projects, positive metrics |
| `--warning` | `#ffc107` | `#fff3cd` | `#e0a800` | Pending items, alerts, budget warnings |
| `--danger` | `#dc3545` | `#f8d7da` | `#c82333` | Rejected status, errors, delete actions |
| `--info` | `#17a2b8` | `#d1ecf1` | `#138496` | Informational notices, in-progress indicators |

#### Neutral Colors

| Token | Hex | Usage |
|---|---|---|
| `--white` | `#ffffff` | Card backgrounds, text on dark, button text |
| `--light` | `#f8f9fa` | Page background base, alternate rows |
| `--gray-100` | `#f8f9fa` | Light backgrounds |
| `--gray-200` | `#e9ecef` | Borders, dividers, stripe rows |
| `--gray-300` | `#dee2e6` | Input borders (default) |
| `--gray-400` | `#ced4da` | Disabled states |
| `--gray-500` | `#adb5bd` | Placeholder text |
| `--gray-600` | `#6c757d` | Muted/secondary text |
| `--gray-700` | `#495057` | Body text secondary |
| `--gray-800` | `#343a40` | Headings secondary |
| `--gray-900` / `--dark` | `#212529` | Primary body text |

#### Page Background

**Light mode:** `linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%)` fixed attachment
**Subtle overlay (login page):**
```
radial-gradient(circle at 20% 80%, rgba(26,78,138,0.05) 0%, transparent 50%),
radial-gradient(circle at 80% 20%, rgba(233,185,73,0.05) 0%, transparent 50%)
```

---

### 17.3 Dark Mode

The system has a fully implemented dark theme toggled via `localStorage` and applied via `body.dark-theme` class. The `applyTheme()` function supports three modes: `light`, `dark`, and `auto` (follows system preference).

The Flutter rebuild must implement equivalent dark/light theme switching using `ThemeMode` with a persistent preference in `SharedPreferences` or `flutter_secure_storage`.

#### Dark Mode Color Overrides

| Element | Light | Dark |
|---|---|---|
| Body background | `linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%)` | `linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%)` |
| Navbar background | `linear-gradient(135deg, #1a4e8a 0%, #0d3a6c 100%)` | `linear-gradient(135deg, #0f3460 0%, #162a47 100%)` |
| Card / form background | `#ffffff` | `#2a2a3e` |
| Card header background | `linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)` | `#3a3a4e` |
| Input background | — | `#3a3a4e` |
| Input focused | — | `#424455` |
| Body text | `#212529` | `#e0e0e0` |
| Muted text | `#6c757d` | `#a0a0b0` |
| Borders / dividers | `#dee2e6` | `#404052` |
| Table header | `#f8f9fa` | `#3a3a4e` |
| Striped row | — | `#32323f` |
| Scrollbar track | `#e9ecef` | `#3a3a4e` |
| Scrollbar thumb | `#1a4e8a` | `#505062` |
| Scrollbar thumb hover | `#0d3a6c` | `#606072` |
| Modal background | `#ffffff` | `#2a2a3e` |
| Modal header | — | `#3a3a4e` |
| Dropdown background | `#ffffff` | `#2a2a3e` |

---

### 17.4 Typography

**Font stack:** `'Segoe UI', 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif`

For Flutter, use **Inter** (via `google_fonts` package) as the primary typeface. It is already named in the CSS stack and is the closest cross-platform equivalent to Segoe UI.

#### Type Scale

| Token | Size | Usage |
|---|---|---|
| `--text-xs` | `0.75rem` / 12sp | Fine print, disclaimers |
| `--text-sm` | `0.875rem` / 14sp | Secondary text, table cells, labels |
| `--text-base` | `1rem` / 16sp | Body text, form inputs, nav links |
| `--text-lg` | `1.125rem` / 18sp | Navbar brand, section subtitles |
| `--text-xl` | `1.25rem` / 20sp | Section titles, card headings |
| `--text-2xl` | `1.5rem` / 24sp | Page sub-headings |
| `--text-3xl` | `1.875rem` / 30sp | Page headings |
| `--text-4xl` | `2.25rem` / 36sp | Hero headings |
| `--text-5xl` | `3rem` / 48sp | Large display numbers |

#### Font Weights
- Body: `400`
- Semi-bold (labels, nav): `600`
- Bold (headings, card titles): `700`
- Extra bold (stat numbers, brand): `800–900`

#### Additional Typography Rules
- Line height: `1.7` on body
- Letter spacing on buttons: `0.05em` (uppercase)
- Stat numbers: `font-size: 2.75rem`, `font-weight: 900`, `color: #1a4e8a`
- Dashboard title: `font-size: 2.25rem`, `font-weight: 800`
- Card header titles: `font-size: 1.25rem`, `font-weight: 800`, `color: #1a4e8a`

---

### 17.5 Spacing System

| Token | Value | Approx Flutter |
|---|---|---|
| `--space-1` | `0.25rem` | 4dp |
| `--space-2` | `0.5rem` | 8dp |
| `--space-3` | `0.75rem` | 12dp |
| `--space-4` | `1rem` | 16dp |
| `--space-5` | `1.25rem` | 20dp |
| `--space-6` | `1.5rem` | 24dp |
| `--space-8` | `2rem` | 32dp |
| `--space-10` | `2.5rem` | 40dp |
| `--space-12` | `3rem` | 48dp |
| `--space-16` | `4rem` | 64dp |
| `--space-20` | `5rem` | 80dp |

---

### 17.6 Border Radius

| Token | Value | Usage |
|---|---|---|
| `--border-radius-sm` | `8px` | Input groups, small tags, dropdown items |
| `--border-radius` | `12px` | Cards, buttons, form controls, modals |
| `--border-radius-lg` | `16px` | Stat cards, content cards, login card |
| `--border-radius-xl` | `20px` | Large modals, hero cards |

---

### 17.7 Elevation & Shadows

| Token | Value | Usage |
|---|---|---|
| `--shadow-sm` | `0 2px 8px rgba(0,0,0,0.06)` | Tool cards, project cards at rest |
| `--shadow` | `0 4px 12px rgba(0,0,0,0.08)` | Cards, dropdowns, navbar |
| `--shadow-md` | `0 6px 20px rgba(0,0,0,0.15)` | Modals |
| `--shadow-lg` | `0 8px 25px rgba(0,0,0,0.15)` | Hovered cards, footer, login card |
| `--shadow-hover` | `0 12px 40px rgba(0,0,0,0.22)` | Buttons on hover, elevated cards |

Profile avatar gold glow on hover: `0 12px 30px rgba(233,185,73,0.4)`

---

### 17.8 Animation & Transitions

**Standard transition:** `all 0.3s cubic-bezier(0.4, 0, 0.2, 1)` — Material Design easing
**Slow transition:** `all 0.5s cubic-bezier(0.4, 0, 0.2, 1)` — Button shimmer sweep
**Fast transition:** `all 0.15s cubic-bezier(0.4, 0, 0.2, 1)` — Micro-interactions

#### Hover Interactions (to replicate in Flutter)

| Component | Hover Effect |
|---|---|
| Stat cards | `translateY(-8px)` + shadow elevation |
| Tool cards | `translateY(-8px) scale(1.02)` + invert to primary blue fill |
| Project cards | `translateY(-5px)` + shadow elevation |
| Buttons (primary) | `translateY(-3px)` + shadow-hover + shimmer sweep left-to-right |
| Nav links | `translateY(-2px)` + semi-transparent white background + golden underline grows from center |
| Coat of Arms | `scale(1.1)` + increased brightness + golden border |
| Dropdown items | `translateX(5px)` + primary blue background + white text |

#### Page Load Animation
Cards and login form animate in with `fadeInUp`:
```
from: { opacity: 0, transform: translateY(30px) }
to:   { opacity: 1, transform: translateY(0) }
duration: 0.6s ease-out
```
Flutter equivalent: `SlideTransition` + `FadeTransition` on screen entry.

#### Loading States
Submit buttons transition to a spinner state on press:
`<i class="fas fa-spinner fa-spin me-2"></i>Authenticating...` + `disabled = true`
Flutter equivalent: `CircularProgressIndicator` replaces button label while awaiting API.

---

### 17.9 Icon Library

**Library:** Font Awesome 6.4.0 Free (CDN)
Flutter equivalent: **`font_awesome_flutter`** package (same icon set, same names)

#### Icons Used Per Feature Area

| Feature | Icons |
|---|---|
| Navigation / Auth | `fa-home`, `fa-sign-in-alt`, `fa-sign-out-alt`, `fa-user-plus`, `fa-lock`, `fa-user`, `fa-key`, `fa-eye`, `fa-eye-slash`, `fa-spinner` |
| Dashboard | `fa-tachometer-alt`, `fa-chart-line`, `fa-chart-bar`, `fa-chart-pie` |
| Projects | `fa-project-diagram`, `fa-folder`, `fa-folder-open`, `fa-tasks`, `fa-check-circle`, `fa-times-circle` |
| Users | `fa-users`, `fa-user`, `fa-user-edit`, `fa-user-check`, `fa-user-times`, `fa-id-card` |
| Financial | `fa-money-bill-wave`, `fa-coins`, `fa-receipt`, `fa-calculator`, `fa-wallet` |
| Progress | `fa-chart-line`, `fa-percentage`, `fa-camera`, `fa-image` |
| Site Visits | `fa-map-marker-alt`, `fa-map`, `fa-compass`, `fa-calendar-alt`, `fa-clock` |
| Evaluations | `fa-clipboard-check`, `fa-star`, `fa-award`, `fa-certificate`, `fa-poll` |
| Communication | `fa-bell`, `fa-envelope`, `fa-comment`, `fa-exclamation-triangle`, `fa-check-circle` |
| Settings | `fa-cog`, `fa-sliders-h`, `fa-shield-alt`, `fa-database`, `fa-paint-brush` |
| Status | `fa-info-circle`, `fa-exclamation-circle`, `fa-check`, `fa-times`, `fa-question-circle` |
| Actions | `fa-plus`, `fa-edit`, `fa-trash`, `fa-eye`, `fa-download`, `fa-upload`, `fa-search`, `fa-filter` |
| Government | `fa-landmark`, `fa-flag`, `fa-balance-scale`, `fa-gavel` |
| Misc | `fa-arrow-left`, `fa-arrow-right`, `fa-external-link-alt`, `fa-sync`, `fa-save` |

---

### 17.10 Component Specifications

#### Navbar

```
Background:    linear-gradient(135deg, #1a4e8a → #0d3a6c)
Bottom border: 3px solid #e9b949
Height:        ~72px (fixed-top)
Shadow:        0 8px 30px rgba(0,0,0,0.18)
Backdrop:      blur(10px)

Brand:
  - Coat of Arms image (45×45px) with semi-transparent border
  - Text: "Government of Zambia - CDF System"
  - Font: 1.25rem, weight 800, white, text-shadow

Nav links:
  - Color: white, weight 600
  - Padding: 12px 16px, border-radius 8px
  - Hover: semi-transparent white bg + translateY(-1px)
  - Active/hover underline: golden line (3px, grows from center)
  - Text shadow: rgba(0,0,0,0.6) for legibility
```

#### Dashboard Header Banner

```
Background:    linear-gradient(135deg, #1a4e8a → #0d3a6c)
Padding:       3rem top, 2.5rem bottom
Margin-top:    76px (clears fixed navbar)
Overlay:       SVG polygon (white 5% opacity) — decorative diagonal fill

Profile avatar:
  - 100×100px circle
  - Fill: linear-gradient(135deg, #e9b949 → #d4a337)
  - Shows user initials (2 letters)
  - Font: 2.5rem, weight 800, dark text
  - Border: 4px solid rgba(255,255,255,0.3)
  - Shadow: 0 8px 25px rgba(0,0,0,0.15)
  - Hover glow: 0 12px 30px rgba(233,185,73,0.4)

User greeting: font-size 2.25rem, weight 800, white
Role/subtitle: font-size 1.25rem, opacity 0.95, white
```

#### Stat Cards

```
Background:    #ffffff
Border-radius: 16px
Shadow:        0 4px 12px rgba(0,0,0,0.08)
Top accent:    5px gradient bar (#1a4e8a → #2c6cb0)
Padding:       2rem 1.5rem, centered

Stat number:   2.75rem, weight 900, color #1a4e8a
               text-shadow: 0 2px 4px rgba(26,78,138,0.15)
Stat title:    1.1rem, weight 700, #333333
Stat subtitle: 0.9rem, weight 500, #666666

Hover: translateY(-8px) + shadow-lg
```

#### Content Cards

```
Background:    #ffffff
Border-radius: 16px
Shadow:        0 4px 12px rgba(0,0,0,0.08)
Border:        1px solid rgba(0,0,0,0.03)

Card header:
  Background:  linear-gradient(135deg, #f8f9fa → #e9ecef)
  Border-bottom: 3px solid #1a4e8a
  Padding:     1.5rem
  Title:       1.25rem, weight 800, color #1a4e8a, flex with icon

Hover: translateY(-2px) + shadow-lg
```

#### Tool Cards (Quick Action Grid)

```
Background:    #ffffff
Border-radius: 12px
Shadow:        0 2px 8px rgba(0,0,0,0.06)
Left border:   5px solid [role color]
Padding:       2rem 1.5rem, centered column

Icon:          2.5rem Font Awesome, color matches border
Title:         1.1rem, weight 700
Body text:     0.9rem, muted

Color variants (left border + icon color):
  Default → #1a4e8a (primary blue)
  .success → #28a745
  .warning → #ffc107
  .info    → #17a2b8
  .danger  → #dc3545

Hover: translateY(-8px) + scale(1.02) + shadow-lg
       background inverts to primary gradient (#1a4e8a → #0d3a6c)
       icon + text → white
       shimmer sweep (left-to-right)
```

#### Primary Button (`.btn-custom`)

```
Background:    linear-gradient(135deg, #1a4e8a → #0d3a6c)
Color:         #ffffff
Border:        none
Padding:       1rem 1.5rem
Font:          1rem, weight 700, UPPERCASE, letter-spacing 0.05em
Border-radius: 12px
Shadow:        0 4px 12px rgba(0,0,0,0.08)
Text-shadow:   0 1px 2px rgba(0,0,0,0.2)

Hover: translateY(-3px) + shadow-hover + shimmer sweep
```

#### CTA Button on Dark Background (`.btn-primary-custom`)

```
Background:    linear-gradient(135deg, #e9b949 → #d4a337)
Color:         #212529 (dark text on gold)
Border:        none
Padding:       1rem 2rem
Font:          weight 700
Border-radius: 12px

Hover: translateY(-3px) + shadow-lg + shimmer
       background shifts to #d4a337 → #c4952e
```

#### Outline Button (`.btn-outline-custom`)

```
On light background:
  Background:  transparent
  Color:       #1a4e8a
  Border:      2px solid #1a4e8a
  Hover: background fills with #1a4e8a, text → white, translateY(-2px)

On dark background:
  Color:       #ffffff
  Border:      2px solid rgba(255,255,255,0.7)
  Hover: background → white, text → #1a4e8a
```

#### Form Controls

```
Input / Select:
  Border:       2px solid #dee2e6
  Border-radius: 12px
  Padding:      1rem 1.25rem
  Font-size:    1rem
  Focus:        border-color #1a4e8a, box-shadow 0 0 0 0.2rem rgba(26,78,138,0.25)

Input group icon (left):
  Background:  #1a4e8a
  Border:      2px solid #1a4e8a
  Color:       white
  Border-radius: 12px 0 0 12px
```

#### Government Notice Box

```
Background:   linear-gradient(135deg, #f8f9fa → #e9ecef)
Border:       2px solid #1a4e8a
Border-radius: 12px
Padding:      1.25rem

Left accent bar: 5px wide, linear-gradient(#e9b949 → #d4a337) — the gold stripe
```

#### Status Badges

```
Completed / Active:  background #d4edda, color #1e7e34 (green)
In Progress:         background #d1ecf1, color #138496 (info blue)
Pending:             background #fff3cd, color #e0a800 (amber)
Rejected / Danger:   background #f8d7da, color #c82333 (red)
Planning:            background #e9ecef, color #495057 (gray)

Border-radius: 20px (pill shape)
Font: 0.75–0.8rem, weight 600, uppercase
Padding: 0.25rem 0.75rem
```

#### Progress Bars

```
Track:       #e9ecef, border-radius 4px
Fill:        linear-gradient(135deg, #1a4e8a → #2c6cb0)
Over budget warning fill: #dc3545
Height:      8px (standard), 10px (featured)
Border-radius: 4px on fill
```

#### Scrollbar

```
Width:   8px
Track:   #e9ecef (light) / #3a3a4e (dark)
Thumb:   #1a4e8a (light) / #505062 (dark), border-radius 4px
Hover:   #0d3a6c (light) / #606072 (dark)
```

#### Footer

```
Background:    linear-gradient(135deg, #1a4e8a → #0d3a6c)
Shadow:        0 8px 30px rgba(0,0,0,0.18)
Padding:       2rem top, 1rem bottom
Overlay:       SVG polygon (white 5% opacity) — mirrors header decoration

Bottom divider: border-top 2px solid rgba(255,255,255,0.3)

Disclaimer box:
  Background:  rgba(0,0,0,0.3)
  Border:      1px solid rgba(255,255,255,0.1)
  Border-left: 4px solid #e9b949
  Border-radius: 12px
  Backdrop:    blur(10px)
  Font:        0.875rem, white

Zambia Flag: 30×20px, positioned right
```

---

### 17.11 Page Layout Patterns

#### Authenticated Page Structure

```
┌─────────────────────────────────────────┐
│  NAVBAR (fixed, 72px, primary gradient) │
│  [Coat of Arms] [Brand Text]  [Nav Links]│
└─────────────────────────────────────────┘
│                                          │
│  DASHBOARD HEADER (primary gradient)    │
│  [Avatar] [Name] [Role]                 │
│  [Action Buttons]                       │
│                                          │
├─────────────────────────────────────────┤
│                                          │
│  CONTENT AREA (light gradient bg)       │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐  │
│  │ Stat │ │ Stat │ │ Stat │ │ Stat │  │
│  │ Card │ │ Card │ │ Card │ │ Card │  │
│  └──────┘ └──────┘ └──────┘ └──────┘  │
│                                          │
│  ┌──────────────────┐ ┌───────────────┐ │
│  │  Content Card    │ │ Content Card  │ │
│  │  (table/list)    │ │ (chart/info)  │ │
│  └──────────────────┘ └───────────────┘ │
│                                          │
│  TOOL GRID (quick actions)              │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐  │
│  │ Tool │ │ Tool │ │ Tool │ │ Tool │  │
│  └──────┘ └──────┘ └──────┘ └──────┘  │
│                                          │
├─────────────────────────────────────────┤
│  FOOTER (primary gradient)              │
│  [Copyright] [Flag] [Disclaimer]        │
└─────────────────────────────────────────┘
```

#### Mobile Layout (< 768px)
- Navbar collapses to hamburger toggle
- Stat cards stack 1-column
- Tool grid stacks 1–2 columns
- Buttons become full-width
- Login card has reduced padding

#### Flutter Adaptive Layout Mapping

| Screen width | Flutter layout |
|---|---|
| ≥ 1200px (web desktop) | Side navbar + content area |
| 600–1199px (tablet / narrow web) | Collapsible rail navigation |
| < 600px (mobile) | Bottom navigation bar |

---

### 17.12 Flutter Theme Implementation

The following is the direct Flutter theme configuration derived from the design system above. This is the starting point for `app_theme.dart`:

```dart
// lib/core/theme/app_theme.dart

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  // ── Primary palette ──────────────────────────────────────
  static const Color primary      = Color(0xFF1A4E8A);
  static const Color primaryDark  = Color(0xFF0D3A6C);
  static const Color primaryLight = Color(0xFF2C6CB0);

  // ── Secondary / accent (Zambian gold) ────────────────────
  static const Color secondary     = Color(0xFFE9B949);
  static const Color secondaryDark = Color(0xFFD4A337);

  // ── Semantic ──────────────────────────────────────────────
  static const Color success = Color(0xFF28A745);
  static const Color warning = Color(0xFFFFC107);
  static const Color danger  = Color(0xFFDC3545);
  static const Color info    = Color(0xFF17A2B8);

  // ── Neutrals ──────────────────────────────────────────────
  static const Color dark    = Color(0xFF212529);
  static const Color gray600 = Color(0xFF6C757D);
  static const Color gray200 = Color(0xFFE9ECEF);
  static const Color light   = Color(0xFFF8F9FA);

  // ── Dark mode surfaces ────────────────────────────────────
  static const Color darkSurface     = Color(0xFF2A2A3E);
  static const Color darkCardHeader  = Color(0xFF3A3A4E);
  static const Color darkBackground1 = Color(0xFF1A1A2E);
  static const Color darkBackground2 = Color(0xFF0F3460);
  static const Color darkText        = Color(0xFFE0E0E0);
  static const Color darkMutedText   = Color(0xFFA0A0B0);
  static const Color darkBorder      = Color(0xFF404052);

  // ── Gradients ─────────────────────────────────────────────
  static const LinearGradient primaryGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [primary, primaryDark],
  );

  static const LinearGradient secondaryGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [secondary, secondaryDark],
  );

  static const LinearGradient lightBackgroundGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFFF5F7FA), Color(0xFFE4E8F0)],
  );

  static const LinearGradient darkBackgroundGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [darkBackground1, darkBackground2],
  );

  // ── Shadows ───────────────────────────────────────────────
  static List<BoxShadow> shadowSm = [
    BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 8, offset: Offset(0, 2)),
  ];
  static List<BoxShadow> shadow = [
    BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 12, offset: Offset(0, 4)),
  ];
  static List<BoxShadow> shadowLg = [
    BoxShadow(color: Colors.black.withOpacity(0.15), blurRadius: 25, offset: Offset(0, 8)),
  ];
  static List<BoxShadow> shadowHover = [
    BoxShadow(color: Colors.black.withOpacity(0.22), blurRadius: 40, offset: Offset(0, 12)),
  ];
  static List<BoxShadow> goldGlow = [
    BoxShadow(color: Color(0xFFE9B949).withOpacity(0.4), blurRadius: 30, offset: Offset(0, 12)),
  ];

  // ── Light ThemeData ───────────────────────────────────────
  static ThemeData lightTheme = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.light(
      primary: primary,
      onPrimary: Colors.white,
      secondary: secondary,
      onSecondary: dark,
      error: danger,
      surface: Colors.white,
      onSurface: dark,
      surfaceContainerHighest: light,
    ),
    textTheme: GoogleFonts.interTextTheme().copyWith(
      displayLarge: GoogleFonts.inter(fontSize: 48, fontWeight: FontWeight.w900, color: primary),
      displayMedium: GoogleFonts.inter(fontSize: 36, fontWeight: FontWeight.w800, color: dark),
      headlineLarge: GoogleFonts.inter(fontSize: 30, fontWeight: FontWeight.w800, color: dark),
      headlineMedium: GoogleFonts.inter(fontSize: 24, fontWeight: FontWeight.w700, color: dark),
      headlineSmall: GoogleFonts.inter(fontSize: 20, fontWeight: FontWeight.w700, color: primary),
      titleLarge: GoogleFonts.inter(fontSize: 18, fontWeight: FontWeight.w800, color: primary),
      titleMedium: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.w600, color: dark),
      bodyLarge: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.w400, color: dark, height: 1.7),
      bodyMedium: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.w400, color: dark, height: 1.7),
      labelLarge: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.w700, letterSpacing: 0.8),
    ),
    cardTheme: CardThemeData(
      color: Colors.white,
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    ),
    inputDecorationTheme: InputDecorationTheme(
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Color(0xFFDEE2E6), width: 2),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: primary, width: 2),
      ),
      contentPadding: EdgeInsets.symmetric(horizontal: 20, vertical: 16),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: primary,
        foregroundColor: Colors.white,
        elevation: 4,
        padding: EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.w700, letterSpacing: 0.8),
      ),
    ),
    appBarTheme: AppBarTheme(
      backgroundColor: primary,
      foregroundColor: Colors.white,
      elevation: 8,
      titleTextStyle: GoogleFonts.inter(fontSize: 20, fontWeight: FontWeight.w800, color: Colors.white),
    ),
  );

  // ── Dark ThemeData ────────────────────────────────────────
  static ThemeData darkTheme = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.dark(
      primary: primary,
      onPrimary: Colors.white,
      secondary: secondary,
      onSecondary: dark,
      error: danger,
      surface: darkSurface,
      onSurface: darkText,
      surfaceContainerHighest: darkCardHeader,
    ),
    scaffoldBackgroundColor: darkBackground1,
    textTheme: GoogleFonts.interTextTheme(ThemeData.dark().textTheme).copyWith(
      displayLarge: GoogleFonts.inter(fontSize: 48, fontWeight: FontWeight.w900, color: primary),
      headlineSmall: GoogleFonts.inter(fontSize: 20, fontWeight: FontWeight.w700, color: darkText),
      titleLarge: GoogleFonts.inter(fontSize: 18, fontWeight: FontWeight.w800, color: darkText),
      bodyLarge: GoogleFonts.inter(fontSize: 16, color: darkText, height: 1.7),
      bodyMedium: GoogleFonts.inter(fontSize: 14, color: darkText, height: 1.7),
    ),
    cardTheme: CardThemeData(
      color: darkSurface,
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: darkCardHeader,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: darkBorder, width: 2),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: primary, width: 2),
      ),
    ),
    appBarTheme: AppBarTheme(
      backgroundColor: darkBackground2,
      foregroundColor: darkText,
      elevation: 8,
    ),
  );
}
```

---

### 17.13 Design Decisions to Preserve

The following are deliberate design choices made by the original developer that must be carried forward without modification:

1. **Government blue as dominant color** — `#1a4e8a` is the visual authority signal. It appears on every header, navbar, card accent, and interactive element. Do not substitute with a different blue.

2. **Zambian gold as the accent** — `#e9b949` appears as the bottom border on the navbar, the profile avatar fill, and CTAs on dark surfaces. It references the copper/gold in Zambia's national symbolism. It must not be replaced with orange, yellow, or any other accent.

3. **Coat of Arms on every page header** — This is not decorative. It is the legal signal that this is an official government system. It must appear in the Flutter app bar on all authenticated screens.

4. **Dark mode is a first-class feature** — The student built a complete dark mode with full coverage. The enterprise rebuild must ship with both themes and respect `prefers-color-scheme` as the default, with a manual override stored in user preferences.

5. **Golden bottom border on the navbar** — The `3px solid #e9b949` separator between navbar and page content is a consistent design signature. In Flutter, implement it as a `bottom` border on the `AppBar` decoration.

6. **Profile avatar uses initials in a gold circle** — Not a profile photo default. The gold avatar with initials (`#e9b949` gradient, dark text) is a deliberate government-appropriate choice over a generic avatar icon.

7. **Cards have a colored top/left accent strip** — Every card carries a 4–5px accent border (top or left) in the relevant semantic color. This is the primary visual indicator of card type and must be replicated in Flutter `Container` decoration.

8. **Uppercase letter-spaced button labels** — Buttons use `text-transform: uppercase` with `letter-spacing: 0.05em`. In Flutter, apply `TextStyle(letterSpacing: 0.8)` and uppercase the string.

9. **Shimmer sweep on button hover** — The left-to-right white shimmer on button hover/press is a deliberate premium interaction. Implement using an `AnimationController` + `LinearGradient` overlay in Flutter.

10. **Footer repeats the navbar gradient** — Header and footer use the same `#1a4e8a → #0d3a6c` gradient with SVG polygon overlay. This creates visual bookends. The Flutter scaffold should have a matching bottom footer on web layout.

---

*End of Design System documentation. All values above are extracted directly from `login.php`, `admin_dashboard.php`, and `includes/global_theme.php` of the school project source code.*
