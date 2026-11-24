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

| Layer              | Technologies                                                                     |
| :----------------- | :------------------------------------------------------------------------------- |
| **Backend**        | Laravel 13, PHP 8.5, PostgreSQL / SQLite                                         |
| **Frontend**       | Vue 3 (Composition API, `<script setup>`), Inertia.js 3, Tailwind CSS 4, Reka UI |
| **Precision Math** | PHP `bcmath` extension (`DECIMAL(18, 9)`)                                        |
| **Testing**        | Pest PHP, PHPUnit, HTTP Client Mocking                                           |
| **Tooling**        | Vite Plus (`vp`), Biome, Vue-TSC, Laravel Pint                                   |

---

## Getting Started

### 1. Installation

```bash
# Clone the repository
git clone https://github.com/<your-username>/next-pixel.git
cd next-pixel

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run database migrations
php artisan migrate

# Build frontend assets
npm run build
```

### 2. Environment Configuration

Add your Pixelforge credentials to `.env`:

```ini
PIXELFORGE_BASE_URL=https://pixelforge.promptedit.dev
PIXELFORGE_API_KEY=pf_live_your_api_key_here
PIXELFORGE_WEBHOOK_SECRET=pf_whsec_your_secret_here
```

### 3. Development Services

Run all services concurrently:

```bash
composer run dev
```

Or run them individually:

```bash
# Laravel development server
php artisan serve

# Vite HMR dev server
npm run dev

# Queue worker (required for asynchronous generations)
php artisan queue:work
```

### 4. Webhook Tunneling (Local Development)

Pixelforge needs a public URL to deliver webhooks. Start a tunnel (e.g. Expose, ngrok, or Cloudflare Tunnel):

```bash
ngrok http 80 --host-header=next-pixel.test
```

Register the public URL in your [Pixelforge Dashboard](https://pixelforge.promptedit.dev/):

```
https://<your-tunnel-subdomain>.ngrok-free.app/webhooks/pixelforge
```

---

## Quality Assurance & Verification

Run the full automated test suite and static analysis:

```bash
# Run all Pest feature & unit tests (146 tests, 947 assertions)
php artisan test

# Run TypeScript typecheck
npm run types:check

# Run Biome code check & formatting
npm run check

# Run Laravel Pint code style check
./vendor/bin/pint --test
```

---

## Testing Special Provider Behaviors

You can test edge-case handling by including special keywords in your prompts:

- `[fail]` &mdash; Forces Pixelforge to reject the generation after acceptance. Confirms the generation is marked `failed` and user is charged `$0.00`.
- `[slow]` &mdash; Simulates a long generation process to test async polling and webhook arrival.
- `[duplicate]` &mdash; Triggers repeated webhook delivery to verify idempotent signature deduplication.

---

## Documentation Index

Comprehensive technical documentation is maintained in the [`docs/`](docs/README.md) directory:

- [Architecture & Standards](docs/architecture/README.md)
- [Authentication](docs/authentication/README.md)
- [Pixelforge API Client](docs/pixelforge-api/README.md)
- [Database Schema & Models](docs/database-schema/README.md)
- [Pricing Estimation Engine](docs/pricing-engine/README.md)
- [Frontend & UI Design System](docs/frontend/README.md)
- [Postman API Collection](docs/postman/README.md)
- [Testing & Quality Assurance](docs/testing/README.md)
