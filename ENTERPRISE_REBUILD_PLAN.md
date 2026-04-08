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
