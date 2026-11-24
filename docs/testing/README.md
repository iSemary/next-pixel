# Testing & Quality Assurance

NextPixel maintains a comprehensive automated testing suite written with **Pest PHP** and enforces strict code styling using **Laravel Pint**.

---

## Test Suite Structure

```
tests/
├── Feature/
│   ├── Api/
│   │   └── AuthApiTest.php             # REST API v1 authentication endpoints
│   ├── Auth/                           # Web session authentication flows
│   ├── Console/
│   │   └── PixelforgeCheckCommandTest.php # Artisan connectivity verification command
│   ├── Models/
│   │   ├── GenerationTest.php          # Generation model, enums, casts & scopes
│   │   └── WebhookEventTest.php        # Webhook event ingestion & idempotency
│   └── Settings/                       # Account profile and password update tests
├── Unit/
│   └── Services/
│       └── Pricing/
│           ├── ProviderPricingServiceTest.php   # Pricing retrieval, caching & error handling
│           └── PricingEstimationServiceTest.php # Token formulas & 9-decimal bcmath arithmetic
└── TestCase.php
```

---

## Testing Principles & Conventions

### 1. HTTP Client Faking (`Http::fake`)

Tests interacting with external APIs (such as Pixelforge) must never make outbound network calls. Always simulate upstream endpoints with `Http::fake()`:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*/v1/pricing' => Http::response([...], 200),
    '*/v1/generations*' => Http::response([...], 200),
]);
```

### 2. Exact Financial Precision Assertions

Do not use `assertEqualsWithDelta()` or float casting when asserting monetary amounts. Tests must assert exact numeric string equality to verify that 9-decimal precision is preserved:

```php
expect($result->totalProviderCost)->toBe('0.373875000');
expect($result->totalUserCost)->toBe('0.467343750');
expect($result->markupAmount)->toBe('0.093468750');
```

### 3. Date Immutability in Assertions

Because `AppServiceProvider` configures `Date::use(CarbonImmutable::class)`, model timestamps instantiate `Carbon\CarbonImmutable`. Tests asserting datetime types should verify against `Carbon\CarbonInterface`:

```php
expect($generation->submitted_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
```

### 4. Database Isolation

Feature tests that write to the database utilize the `Illuminate\Foundation\Testing\RefreshDatabase` trait, ensuring every test runs inside a pristine database transaction.

---

## Running Automated Tests

### Run Full Test Suite

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Run Pricing Estimation Engine unit tests
php artisan test --filter=PricingEstimationServiceTest

# Run Provider Pricing Service unit tests
php artisan test --filter=ProviderPricingServiceTest

# Run Generation and Webhook model tests
php artisan test --filter=GenerationTest
php artisan test --filter=WebhookEventTest

# Run API Authentication feature tests
php artisan test --filter=AuthApiTest

# Run Artisan CLI verification tests
php artisan test --filter=PixelforgeCheckCommandTest
```

### Parallel Test Execution

```bash
php artisan test --parallel
```

---

## Code Style & Formatting (Laravel Pint)

NextPixel adheres to Laravel's official opinionated coding style configured via `pint.json`.

### Check Code Style Without Modifying

```bash
./vendor/bin/pint --test
```

### Automatically Fix Code Style

```bash
./vendor/bin/pint
```

Or fix specific paths:

```bash
./vendor/bin/pint app/Services/Pricing tests/Unit/Services/Pricing
```
