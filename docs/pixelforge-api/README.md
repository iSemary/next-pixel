# Pixelforge API Client & Integration

NextPixel communicates with upstream AI image generation services using a dedicated HTTP client built on top of Laravel's `Illuminate\Support\Facades\Http` client.

---

## Configuration & Environment

The Pixelforge client is configured centrally via [`config/services.php`](file:///home/abdelrahman/me/next-pixel/config/services.php):

```php
'pixelforge' => [
    'base_url' => env('PIXELFORGE_BASE_URL', 'https://pixelforge.promptedit.dev'),
    'api_key' => env('PIXELFORGE_API_KEY'),
    'timeout' => (int) env('PIXELFORGE_TIMEOUT', 30),
    'pricing_cache_ttl' => (int) env('PIXELFORGE_PRICING_CACHE_TTL', 3600),
    'markup_rate' => env('PIXELFORGE_MARKUP_RATE', '0.2500'),
],
```

### Environment Variables (`.env`)

```ini
# Pixelforge API Configuration
PIXELFORGE_BASE_URL=https://pixelforge.promptedit.dev
PIXELFORGE_API_KEY=your_pixelforge_api_key_here
PIXELFORGE_TIMEOUT=30
PIXELFORGE_PRICING_CACHE_TTL=3600
PIXELFORGE_MARKUP_RATE=0.2500
```

> [!WARNING]
> Never commit real API keys into version control. Keep `.env.example` scrubbed of credentials.

---

## Client Contract (`PixelforgeClientInterface`)

All interactions with Pixelforge must go through [`PixelforgeClientInterface`](file:///home/abdelrahman/me/next-pixel/app/Services/Pixelforge/PixelforgeClientInterface.php):

```php
namespace App\Services\Pixelforge;

interface PixelforgeClientInterface
{
    /**
     * Retrieve the current token rates and output token lookup table.
     * Endpoint: GET /v1/pricing
     */
    public function getPricing(): array;

    /**
     * List image generations with cursor-based pagination.
     * Endpoint: GET /v1/generations
     */
    public function listGenerations(?int $limit = null, ?string $startingAfter = null): array;

    public function getBaseUrl(): string;

    public function hasApiKey(): bool;
}
```

Registered as a singleton in [`app/Providers/RepositoryServiceProvider.php`](file:///home/abdelrahman/me/next-pixel/app/Providers/RepositoryServiceProvider.php).

---

## Exception Hierarchy

All HTTP and network failures are translated into structured, domain-specific exceptions located in `app/Services/Pixelforge/Exceptions/`:

```
PixelforgeException (abstract base)
├── PixelforgeApiException
│   ├── getStatusCode(): int
│   ├── getErrorCode(): ?string
│   ├── getErrorType(): ?string
│   ├── getParam(): ?string
│   ├── getErrors(): ?array
│   ├── getRetryAfter(): ?int
│   ├── isAuthenticationError(): bool
│   ├── isRateLimited(): bool
│   ├── isOverloaded(): bool
│   └── isValidationError(): bool
├── PixelforgeConnectionException (network drops, DNS errors)
└── PixelforgeTimeoutException (request exceeded timeout limit)
```

### Error Handling Example

```php
try {
    $pricing = $client->getPricing();
} catch (PixelforgeApiException $e) {
    if ($e->isRateLimited()) {
        $retrySeconds = $e->getRetryAfter() ?? 60;
        Log::warning("Pixelforge rate limited, retry after {$retrySeconds}s");
    }
} catch (PixelforgeTimeoutException $e) {
    Log::error("Pixelforge API timed out after {$e->getTimeout()} seconds");
} catch (PixelforgeConnectionException $e) {
    Log::error("Pixelforge unreachable: " . $e->getMessage());
}
```

---

## Artisan Verification Command

To verify API credentials and connectivity without creating any generation or incurring costs, run:

```bash
php artisan pixelforge:check
```

### What it checks:

1. **Connectivity Check**: Calls `GET /v1/pricing` (unauthenticated endpoint) to confirm network reachability and logs live provider rates.
2. **Authentication Check**: Performs a read-only call to `GET /v1/generations?limit=1` using the configured Bearer token.
3. **Safe Operation**: Does **not** trigger image generation or modify state.

---

## Testing with Laravel HTTP Fake

When writing tests that interact with Pixelforge, use `Http::fake()`:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*/v1/pricing' => Http::response([
        'rates' => [
            'input_text' => '5.00',
            'input_image' => '10.00',
            'output_high' => '40.00',
            'output_medium' => '25.00',
            'output_low' => '15.00',
            'reasoning' => '12.50',
        ],
        'output_tokens' => [
            '1024x1024' => ['low' => 1024, 'medium' => 2560, 'high' => 4096],
            '1536x1024' => ['low' => 1536, 'medium' => 3840, 'high' => 6450],
            '1024x1536' => ['low' => 1536, 'medium' => 3840, 'high' => 6450],
        ],
    ], 200),
]);
```

Run tests via:

```bash
php artisan test --filter=PixelforgeClientTest
php artisan test --filter=PixelforgeCheckCommandTest
```
