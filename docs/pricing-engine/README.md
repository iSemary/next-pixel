# Pricing Estimation Engine

NextPixel features an estimation and financial billing engine designed to calculate token usage and costs prior to image generation submission.

---

## Architecture Overview

```
                           ┌───────────────────────────────┐
                           │      User Generation Form     │
                           └───────────────┬───────────────┘
                                           │
                                           ▼
                           ┌───────────────────────────────┐
                           │        EstimationInput        │
                           │ (DTO: Validates length/count) │
                           └───────────────┬───────────────┘
                                           │
                                           ▼
┌───────────────────────────────┐  pricing ┌───────────────────────────────┐
│    ProviderPricingService     ├─────────►│   PricingEstimationService    │
│  - GET /v1/pricing            │ snapshot │  - Conservative Token Formulas│
│  - Cache::remember (1 hour)   │          │  - bcmath nanodollar precision│
│  - Never defaults to zero     │          │  - 25% Platform Markup Rate   │
└───────────────────────────────┘          └───────────────┬───────────────┘
                                                           │
                                                           ▼
                                           ┌───────────────────────────────┐
                                           │    PricingEstimationResult    │
                                           │  - toDatabaseAttributes()     │
                                           │  - toPublicArray()            │
                                           └───────────────────────────────┘
```

---

## Token Estimation Formulas

All formulas are derived directly from the official [Pixelforge API documentation](https://pixelforge.promptedit.dev/docs):

### 1. Input Text Tokens

$$\text{Base Tokens} = \left\lceil \frac{\text{mb\_strlen}(\text{prompt})}{4} \right\rceil$$
$$\text{Estimated Input Text Tokens} = \lceil \text{Base Tokens} \times 1.15 \rceil$$

- Handles multi-byte UTF-8 characters cleanly.
- Applies a conservative 15% safety buffer to account for upstream BPE tokenizer variance.

### 2. Input Image Tokens

$$\text{Estimated Input Image Tokens} = \text{count}(\text{referenceImages}) \times 765$$

- Up to 4 reference image URLs permitted.

### 3. Output Tokens

Determined by an exact table lookup based on target resolution and quality tier:

| Resolution                  | Low Quality | Medium Quality | High Quality |
| :-------------------------- | :---------: | :------------: | :----------: |
| **`1024x1024`** (Square)    |    1,024    |     2,560      |    4,096     |
| **`1536x1024`** (Landscape) |    1,536    |     3,840      |    6,450     |
| **`1024x1536`** (Portrait)  |    1,536    |     3,840      |    6,450     |

### 4. Reasoning Tokens

Pixelforge models execute variable chain-of-thought reasoning steps depending on complexity. Before generation, the exact reasoning token count cannot be known. NextPixel utilizes the **upper bound** of the documented tier range for conservative estimation:

| Quality Tier | Documented Range | Conservative Upper Bound |
| :----------- | :--------------: | :----------------------: |
| **`low`**    |     0 – 500      |         **500**          |
| **`medium`** |   200 – 2,000    |        **2,000**         |
| **`high`**   |  1,000 – 8,000   |        **8,000**         |

---

## Financial Calculation Precision (`bcmath`)

To prevent cumulative floating-point rounding errors (e.g. `0.1 + 0.2 != 0.3`), all monetary computations are performed using PHP's native `bcmath` extension with an explicit scale of 9 decimal places.

### 1. Category Cost Formula

$$\text{Category Cost} = \frac{\text{Tokens} \times \text{Rate per 1,000,000}}{1,000,000}$$

```php
$tokenProduct = bcmul((string) $tokens, $ratePer1M, 9);
$categoryCost = bcdiv($tokenProduct, '1000000', 9);
```

### 2. Platform Markup Formula

NextPixel applies a centralized 25% platform markup:
$$\text{Markup Amount} = \text{Provider Cost} \times 0.2500$$
$$\text{Estimated User Cost} = \text{Provider Cost} + \text{Markup Amount}$$

```php
$markupAmount = bcmul($providerTotalCost, $markupRate, 9);
$userTotalCost = bcadd($providerTotalCost, $markupAmount, 9);
```

---

## Worked Example Verification

Official example from Pixelforge documentation:

- **Prompt:** 400 characters
- **Images:** 2 reference images
- **Size & Quality:** `1536x1024`, `high`
- **Rates:**
    - Input Text: \$5.00 / 1M
    - Input Image: \$10.00 / 1M
    - Output High: \$40.00 / 1M
    - Reasoning: \$12.50 / 1M

| Category                  |   Tokens   | Rate / 1M |      Cost ($)      |
| :------------------------ | :--------: | :-------: | :----------------: |
| Input Text                |    115     |  \$5.00   |   `$0.000575000`   |
| Input Image               |   1,530    |  \$10.00  |   `$0.015300000`   |
| Output                    |   6,450    |  \$40.00  |   `$0.258000000`   |
| Reasoning                 |   8,000    |  \$12.50  |   `$0.100000000`   |
| **Total Provider Cost**   | **16,095** |     —     | **`$0.373875000`** |
| **Platform Markup (25%)** |     —      |     —     | **`$0.093468750`** |
| **Total User Price**      |     —      |     —     | **`$0.467343750`** |

_Verified with 100% precision in `PricingEstimationServiceTest`._

---

## Data Transfer Objects (DTOs)

### 1. `EstimationInput`

Validates request integrity:

```php
$input = new EstimationInput(
    prompt: 'A cinematic sunset over a neon city',
    size: ImageSize::Landscape,
    quality: ImageQuality::High,
    referenceImages: ['https://example.com/ref1.png'],
);
```

### 2. `PricingEstimationResult`

Encapsulates token and price breakdowns. Supports two primary projection methods:

- **`toDatabaseAttributes()`**: Returns an associative array directly matching the columns of the `generations` table for seamless Eloquent storage.
- **`toPublicArray()`**: Returns a customer-facing breakdown (tokens and user price) while keeping upstream provider profit margins and raw provider rates confidential.

---

## Provider Rate Caching & Safety

- Managed by [`ProviderPricingService`](file:///home/abdelrahman/me/next-pixel/app/Services/Pricing/ProviderPricingService.php).
- Cached for 1 hour (`config('services.pixelforge.pricing_cache_ttl')`).
- If the Pixelforge API is unavailable, the service throws [`ProviderPricingUnavailableException`](file:///home/abdelrahman/me/next-pixel/app/Services/Pricing/Exceptions/ProviderPricingUnavailableException.php). **Zero pricing is never assumed or returned.**

---

## Testing

```bash
php artisan test --filter=ProviderPricingServiceTest
php artisan test --filter=PricingEstimationServiceTest
```
