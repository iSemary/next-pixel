# NextPixel

An AI image generation platform with progress tracking and usage-based pricing, built with Laravel and Vue.js.

- [Technology Stack](#technology-stack)
    - [Backend](#backend)
    - [Frontend](#frontend)
- [Getting Started](#getting-started)
    - [1. Installation](#1-installation)
    - [2. Development](#2-development)
- [Documentation](#documentation)

## Technology Stack

### Backend

- **Framework:** Laravel 13
- **PHP:** 8.5
- **Database:** PostgreSQL
- **Testing:** Pest

### Frontend

- **Framework:** Vue 3
- **Full-Stack Bridge:** Inertia.js 3
- **Styling:** Tailwind CSS 4

---

## Getting Started

### 1. Installation

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Build frontend assets
npm run build
```

### 2. Development

```bash
# Run both Laravel server and Vite dev server concurrently
composer run dev
```

Or run separately:

```bash
# Laravel server
php artisan serve

# Vite dev server (HMR)
npm run dev

# Queue worker
php artisan queue:work
```

---

## Documentation

Comprehensive project documentation is organized in the [`docs/`](docs/README.md) directory:

- [Architecture & Standards](docs/architecture/README.md)
- [Authentication (Web & Passport API v1)](docs/authentication/README.md)
- [Pixelforge API Client & Integration](docs/pixelforge-api/README.md)
- [Database Schema, Models & Financial Integrity](docs/database-schema/README.md)
- [Pricing Estimation Engine](docs/pricing-engine/README.md)
- [Frontend, Layout & Settings](docs/frontend/README.md)
- [Postman Collection & Environment](docs/postman/README.md)
- [Testing & Quality Assurance](docs/testing/README.md)
