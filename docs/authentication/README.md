# Authentication & Authorization

NextPixel implements a unified, dual-layer authentication architecture:

1. **Web Session Authentication**: State-driven session auth for the Inertia.js Vue frontend.
2. **Versioned REST API Authentication**: Stateless Bearer token authentication powered by **Laravel Passport** under `/api/v1/auth/*`.

---

## Authentication Architecture

```
                    ┌─────────────────────────────────────────┐
                    │              Client Request             │
                    └───────────────────┬─────────────────────┘
                                        │
                         ┌──────────────┴──────────────┐
                         ▼                             ▼
              [Web / Inertia Request]       [API Client Request]
                         │                             │
             ┌───────────▼───────────┐     ┌───────────▼───────────┐
             │ Session / Cookie Auth │     │ Bearer Token (Passport│
             │ Web Middleware Group  │     │ auth:api Guard        │
             └───────────┬───────────┘     └───────────┬───────────┘
                         │                             │
                         └──────────────┬──────────────┘
                                        ▼
                         ┌─────────────────────────────┐
                         │    AuthServiceInterface     │
                         │    (App\Services\Auth)      │
                         └──────────────┬──────────────┘
                                        ▼
                         ┌─────────────────────────────┐
                         │       App\Models\User       │
                         │  (HasApiTokens trait)       │
                         └─────────────────────────────┘
```

---

## API Endpoints (`/api/v1/auth/*`)

All REST endpoints accept and return JSON (`Accept: application/json`, `Content-Type: application/json`).

### 1. Register User

- **Route:** `POST /api/v1/auth/register` (also aliased at `/api/v1/register`)
- **Headers:** `Accept: application/json`
- **Request Body:**
    ```json
    {
        "name": "Jane Doe",
        "email": "jane@example.com",
        "password": "Password123!",
        "password_confirmation": "Password123!"
    }
    ```
- **Success Response (`201 Created`):**
    ```json
    {
        "message": "User registered successfully",
        "user": {
            "id": 1,
            "name": "Jane Doe",
            "email": "jane@example.com",
            "created_at": "2026-11-24T04:00:00.000000Z",
            "updated_at": "2026-11-24T04:00:00.000000Z"
        },
        "token": "def50200...",
        "token_type": "Bearer"
    }
    ```

---

### 2. Login User

- **Route:** `POST /api/v1/auth/login` (also aliased at `/api/v1/login`)
- **Headers:** `Accept: application/json`
- **Request Body:**
    ```json
    {
        "email": "jane@example.com",
        "password": "Password123!"
    }
    ```
- **Success Response (`200 OK`):**
    ```json
    {
        "message": "Login successful",
        "user": {
            "id": 1,
            "name": "Jane Doe",
            "email": "jane@example.com"
        },
        "token": "def50200...",
        "token_type": "Bearer"
    }
    ```
- **Failure Response (`422 Unprocessable Entity`):**
    ```json
    {
        "message": "These credentials do not match our records.",
        "errors": {
            "email": ["These credentials do not match our records."]
        }
    }
    ```

---

### 3. Get Authenticated User

- **Route:** `GET /api/v1/auth/user` (also aliased at `/api/v1/user`)
- **Headers:**
    - `Accept: application/json`
    - `Authorization: Bearer <access_token>`
- **Success Response (`200 OK`):**
    ```json
    {
        "user": {
            "id": 1,
            "name": "Jane Doe",
            "email": "jane@example.com",
            "email_verified_at": null,
            "created_at": "2026-11-24T04:00:00.000000Z",
            "updated_at": "2026-11-24T04:00:00.000000Z"
        }
    }
    ```
- **Unauthorized Response (`401 Unauthorized`):**
    ```json
    {
        "message": "Unauthenticated."
    }
    ```

---

### 4. Logout (Revoke Token)

- **Route:** `POST /api/v1/auth/logout` (also aliased at `/api/v1/logout`)
- **Headers:**
    - `Accept: application/json`
    - `Authorization: Bearer <access_token>`
- **Behavior:** The active access token is revoked immediately.
- **Success Response (`200 OK`):**
    ```json
    {
        "message": "Logged out successfully"
    }
    ```

---

### 5. Forgot Password

- **Route:** `POST /api/v1/auth/forgot-password` (also aliased at `/api/v1/forgot-password`)
- **Request Body:**
    ```json
    {
        "email": "jane@example.com"
    }
    ```
- **Success Response (`200 OK`):**
    ```json
    {
        "message": "We have emailed your password reset link."
    }
    ```

---

### 6. Reset Password

- **Route:** `POST /api/v1/auth/reset-password` (also aliased at `/api/v1/reset-password`)
- **Request Body:**
    ```json
    {
        "token": "token_from_email",
        "email": "jane@example.com",
        "password": "NewPassword123!",
        "password_confirmation": "NewPassword123!"
    }
    ```
- **Success Response (`200 OK`):**
    ```json
    {
        "message": "Your password has been reset."
    }
    ```

---

## Service Contracts & Implementation

Authentication logic is encapsulated behind [`AuthServiceInterface`](file:///home/abdelrahman/me/next-pixel/app/Services/Auth/AuthServiceInterface.php):

```php
interface AuthServiceInterface
{
    public function registerWeb(array $data): User;
    public function registerApi(array $data): array; // returns ['user' => ..., 'token' => ..., 'token_type' => 'Bearer']
    public function loginWeb(array $credentials, bool $remember = false): bool;
    public function loginApi(array $credentials): ?array;
    public function logoutWeb(): void;
    public function logoutApi(User $user): void;
}
```

Bound to [`AuthService`](file:///home/abdelrahman/me/next-pixel/app/Services/Auth/AuthService.php) inside [`RepositoryServiceProvider`](file:///home/abdelrahman/me/next-pixel/app/Providers/RepositoryServiceProvider.php).

---

## Token Lifecycle & Security

1. **Token Generation**: On successful `register` or `login`, Passport creates an access token via `$user->createToken('auth-token')->accessToken`.
2. **Token Revocation**: On `logout`, `$user->token()->revoke()` marks the token as revoked in `oauth_access_tokens`.
3. **Password Hashing**: Bcrypt with minimum 8 characters and mixed case/symbol recommendations.
4. **Rate Limiting**: API routes are protected by Laravel's standard throttle middleware to safeguard against brute-force attacks.

---

## Testing

Run the automated API authentication test suite:

```bash
php artisan test --filter=AuthApiTest
```
