# NextPixel Documentation

Welcome to the NextPixel technical documentation. NextPixel is a high-performance, full-stack image generation platform built with **Laravel 12**, **Vue 3**, **Inertia.js**, and **Tailwind CSS**, integrated with the **Pixelforge API**.

This directory contains comprehensive guides, architectural blueprints, data schemas, API contracts, and implementation standards for the NextPixel platform.

---

## Documentation Index

```
docs/
├── README.md                     # Documentation index & architecture overview
├── architecture/
│   └── README.md                 # System architecture, design patterns & conventions
├── authentication/
│   └── README.md                 # Web session & versioned Passport API authentication
├── pixelforge-api/
│   └── README.md                 # Pixelforge HTTP client, error handling & CLI checks
├── database-schema/
│   └── README.md                 # Schema migrations, Eloquent models & data integrity
├── pricing-engine/
│   └── README.md                 # Token estimation engine & bcmath financial calculations
├── frontend/
│   └── README.md                 # Vue 3, Inertia, shadcn-vue, dashboard & internal fonts
├── postman/
│   ├── collection.json           # Postman Collection (v2.1.0 schema)
│   ├── environment.json          # Postman Environment configuration
│   └── README.md                 # API testing & automated token guide
└── testing/
    └── README.md                 # Pest test suites, fixtures & Pint code style
```

---

## Section Summaries

### 1. [Architecture & Design Standards](file:///home/abdelrahman/me/next-pixel/docs/architecture/README.md)

Overview of the application layers, dependency inversion, Service-Repository pattern, Data Transfer Objects (DTOs), strict typing, and immutable date handling.

### 2. [Authentication & Authorization](file:///home/abdelrahman/me/next-pixel/docs/authentication/README.md)

Detailed guide on dual authentication:

- Web session authentication with Inertia.js.
- Versioned API (`/api/v1/auth/*`) powered by Laravel Passport with Bearer tokens, token refresh, and credential validation.

### 3. [Pixelforge API Client](file:///home/abdelrahman/me/next-pixel/docs/pixelforge-api/README.md)

The upstream integration layer with Pixelforge:

- `PixelforgeClient` implementation using Laravel HTTP Client.
- Exception hierarchy (`PixelforgeApiException`, `PixelforgeConnectionException`, etc.).
- Centralized configuration and the `php artisan pixelforge:check` connectivity command.

### 4. [Database Schema & Models](file:///home/abdelrahman/me/next-pixel/docs/database-schema/README.md)

Database persistence and lifecycle tracking:

- `generations` table: Token usage breakdown, nanodollar pricing (`DECIMAL(18, 9)`), image size/quality enums, and `nullOnDelete` account deletion strategy.
- `webhook_events` table: Idempotent provider webhook event ingestion.
- Query scopes, model relationships, and state machine transitions.

### 5. [Pricing Estimation Engine](file:///home/abdelrahman/me/next-pixel/docs/pricing-engine/README.md)

The algorithmic pricing core:

- Upstream rate caching (`ProviderPricingService`) with fail-safe error handling.
- Conservative token formulas for input text (+15% buffer), input images (765 tokens/img), exact output resolution lookup, and reasoning token upper bounds.
- Arbitrary-precision financial calculations using `bcmath` and 25% platform markup.

### 6. [Frontend & User Interface](file:///home/abdelrahman/me/next-pixel/docs/frontend/README.md)

Frontend design system and user workflows:

- Vue 3 + Inertia.js 3 SPA architecture.
- Clean dashboard layout with header user dropdown.
- Unified Account Settings modal (General & Security horizontal tabs).
- Offline self-hosted typography (Bricolage Grotesque & Inter).
- Dark/Light semantic theme tokens.

### 7. [Postman Collection & Environment](file:///home/abdelrahman/me/next-pixel/docs/postman/README.md)

Complete API testing package:

- Ready-to-import Postman Collection v2.1.0 (`collection.json`).
- Pre-configured local environment (`environment.json`).
- Automated token management scripts and Newman CLI runner.

### 8. [Testing & Quality Assurance](file:///home/abdelrahman/me/next-pixel/docs/testing/README.md)

Testing standards and execution:

- Pest PHP testing framework conventions.
- HTTP client fake fixtures and dynamic provider mocking.
- Precise financial test assertions.
- Laravel Pint formatting rules.

---

## Getting Started Quick Links

- [Root README](file:///home/abdelrahman/me/next-pixel/README.md)
- [Postman Collection](file:///home/abdelrahman/me/next-pixel/docs/postman/collection.json)
- [Postman Environment](file:///home/abdelrahman/me/next-pixel/docs/postman/environment.json)
- [Database Migrations](file:///home/abdelrahman/me/next-pixel/database/migrations/)
- [Environment Configuration Guide](file:///home/abdelrahman/me/next-pixel/.env.example)
