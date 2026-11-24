# Postman Collection & Environment Guide

NextPixel provides an official, pre-configured **Postman Collection** and **Environment** to test and interact with the REST API.

---

## Included Files

```
docs/postman/
├── collection.json     # Postman Collection (v2.1.0 schema) containing all versioned API routes
├── environment.json    # Local environment template with default variables
└── README.md           # Setup and usage instructions
```

---

## Quick Import

### 1. In Postman App

1. Open Postman.
2. Click **Import** in the upper-left corner.
3. Drag and drop both files:
    - [`docs/postman/collection.json`](file:///home/abdelrahman/me/next-pixel/docs/postman/collection.json)
    - [`docs/postman/environment.json`](file:///home/abdelrahman/me/next-pixel/docs/postman/environment.json)
4. In the top-right environment selector, select **NextPixel (Local)**.

### 2. In VS Code (Thunder Client or Postman Extension)

- Open Thunder Client / Postman extension.
- Select **Collections** $\rightarrow$ **Import** and choose `collection.json`.
- Select **Environments** $\rightarrow$ **Import** and choose `environment.json`.

---

## Environment Variables

| Variable        | Default Value           | Description                                                 |
| :-------------- | :---------------------- | :---------------------------------------------------------- |
| `base_url`      | `http://localhost:8000` | The root URL where your NextPixel Laravel server is running |
| `access_token`  | _(Dynamic)_             | Automatically updated by login and registration requests    |
| `user_name`     | `Jane Developer`        | Sample name used in test registration bodies                |
| `user_email`    | `jane@nextpixel.dev`    | Sample email used in test authentication                    |
| `user_password` | `Password123!`          | Sample password for authentication                          |
| `reset_token`   | _(Empty)_               | Password reset token received from reset flow               |

---

## Automated Token Lifecycle

The collection features built-in **Postman Test Scripts** that eliminate manual copying and pasting of Bearer tokens:

```
[ POST /api/v1/auth/register ] ───► Auto-saves response.token ───┐
                                                                 │
[ POST /api/v1/auth/login    ] ───► Auto-saves response.token ───┼──► {{access_token}}
                                                                 │       │
[ GET  /api/v1/auth/user     ] ◄─────────────────────────────────┴───────┘
                                    (Inherits Bearer {{access_token}})
                                                                 │
[ POST /api/v1/auth/logout   ] ───► Clears {{access_token}} ─────┘
```

### 1. Auto-Capture Token on Register / Login

When running either `Register User` or `Login`, the collection executes the following script:

```javascript
var jsonData = pm.response.json();
if (jsonData.token) {
    pm.environment.set('access_token', jsonData.token);
    console.log('Access token successfully saved to environment.');
}
```

### 2. Bearer Authentication Inheritance

All protected endpoints (such as `Get Authenticated User`) are configured to inherit collection-level Bearer authorization:

```
Authorization: Bearer {{access_token}}
```

### 3. Auto-Clear on Logout

When `POST /api/v1/auth/logout` succeeds, the script resets `{{access_token}}` to an empty string.

---

## CLI Automation with Newman

You can execute the entire collection from the command line or CI pipeline using [Newman](https://www.npmjs.com/package/newman):

```bash
# Install Newman globally (if not already installed)
npm install -g newman

# Run the collection against your local environment
newman run docs/postman/collection.json \
    -e docs/postman/environment.json \
    --reporters cli
```
