# NextPixel

A clean, modern full-stack web application built with Laravel 13, Vue 3, Inertia.js 3, and Tailwind CSS 4.

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
