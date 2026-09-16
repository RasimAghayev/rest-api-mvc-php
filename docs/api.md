# API Reference

> rest-api-mvc-php — Complete endpoint documentation based on actual codebase

---

## Base URL

| Environment | URL |
|-------------|-----|
| Local (PHP dev server) | `http://localhost:8000/api` |
| Local (Nginx) | `http://localhost/api` |
| Production | `https://api.yourdomain.com/api` |

All endpoints are routed through `api/index.php`. JSON request/response format.

---

## Error Responses

| Code | Meaning |
|------|---------|
| 201 | Created successfully |
| 202 | Login successful (2FA required) |
| 404 | Not found / Invalid credentials |
| 424 | Failed to create / update |
| 500 | Validation error |
| 503 | Access denied (wrong HTTP method) |
| 423 | User is locked/disabled/hold/reset |
| 417 | Exception / Server error |

Error format:
```json
{
  "success": 0,
  "status": 404,
  "url": "/",
  "message": "Error message",
  "data": null
}
```

---

## Endpoints

### User Registration

Register a new user account with 2FA setup.

**Endpoint:** `POST /users/register`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `SurName` | string | Yes | min:3, max:16, alpha |
| `Name` | string | Yes | min:3, max:16, alpha |
| `MiddleName` | string | Yes | min:3, max:16, alpha |
| `Gender` | string | Yes | alpha (M/F/O) |
| `UserName` | string | Yes | min:5 |
| `Email` | string | Yes | valid email |
| `Password` | string | Yes | min:6, uppercase+lowercase+number+specialChars |
| `UserStatus` | string | Yes | alpha |
| `Expiration_Date` | string | No | datetime |

**Example Request:**
```bash
curl -X POST http://localhost/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "SurName": "Rasim",
    "Name": "Rasim",
    "MiddleName": "Shukur",
    "Gender": "M",
    "UserName": "raghayev",
    "Email": "raghayev@gmail.com",
    "Password": "Rasim123$",
    "UserStatus": "E",
    "Expiration_Date": "2027-01-01 00:00:00"
  }'
```

**Success Response (201):**
```json
{
  "success": 1,
  "status": 201,
  "url": "/users",
  "message": "Project has been created",
  "data": { ...user data... }
}
```

**Error Responses:**
- `500` — Validation failed (see error details)
- `424` — User already exists ("User already exists, try another email address")
- `417` — Exception (database error, etc.)
- `503` — Wrong HTTP method

---

### User Login

Authenticate user. Returns 2FA data for second factor verification.

**Endpoint:** `POST /users/login`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `Email` | string | Yes | Email or UserName |
| `Password` | string | Yes | User password |

**Example Request:**
```bash
curl -X POST http://localhost/api/users/login \
  -H "Content-Type: application/json" \
  -d '{
    "Email": "raghayev@gmail.com",
    "Password": "Rasim123$"
  }'
```

**Success Response (202):**
```json
{
  "success": 1,
  "status": 202,
  "url": "/TOTP",
  "message": "User logged in successfully",
  "data": {
    "user_id": 1,
    "md51Key": "...",
    "OtherKey": "...",
    "imgKey": "data:image/png;base64,..."
  }
}
```

**Error Responses:**
- `404` — Invalid credentials
- `404` — User not found
- `404` — Password expired (`Your password Expiration Date`)
- `423` — User is Disable/Hold/Reset/Lock
- `423` — Failed login attempt limit (8+ attempts)
- `417` — Invalid credentials (2FA)
- `503` — Wrong HTTP method

---

### Password Reset Request

Initiate password reset. Generates a 10-minute expiry token.

**Endpoint:** `POST /users/resetPassword`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `Email` | string | Yes | User email |

**Example Request:**
```bash
curl -X POST http://localhost/api/users/resetPassword \
  -H "Content-Type: application/json" \
  -d '{
    "Email": "raghayev@gmail.com"
  }'
```

**Success Response (201):**
```json
{
  "success": 1,
  "status": 201,
  "url": "/",
  "message": "Users has been reset",
  "data": { "Email": "raghayev@gmail.com" }
}
```

**Error Responses:**
- `404` — Email not found
- `404` — Wrong HTTP method
- `503` — Wrong HTTP method

---

### Check Reset Token

Validate a password reset token.

**Endpoint:** `POST /users/checkResetToken`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `Token` | string | Yes | Reset token from email |

**Example Request:**
```bash
curl -X POST http://localhost/api/users/checkResetToken \
  -H "Content-Type: application/json" \
  -d '{
    "Token": "base64-encoded-token"
  }'
```

**Success Response (200):**
```json
{
  "success": 1,
  "status": 200,
  "url": "/",
  "message": "Reset password Token in successfully",
  "data": null
}
```

**Error Responses:**
- `404` — Token expired (`Reset password Token in expired`)
- `503` — Wrong HTTP method

---

### Generate 2FA Code

Generate or retrieve 2FA (Google Authenticator) code.

**Endpoint:** `POST /users/g2faCodeC`

**Headers:**
```
Content-Type: application/json
```

**Requires:** Active session

**Success Response (200):**
```json
{
  "success": 1,
  "status": 200,
  "url": "/",
  "message": null,
  "data": {
    "md52Key": "...",
    "jstKey": "...",
    "jetKey": "...",
    "imgKey": "data:image/png;base64,..."
  }
}
```

**Error Responses:**
- `404` — Invalid 2FA credentials

---

### Verify 2FA Code

Verify Google Authenticator TOTP code.

**Endpoint:** `POST /users/g2faCodeV`

**Headers:**
```
Content-Type: application/json
```

**Requires:** Active session

**Success Response (200):**
```json
{
  "success": 1,
  "status": 200,
  "url": "/",
  "message": null,
  "data": null
}
```

---

### Logout

Destroy user session.

**Endpoint:** `POST /users/logout`

**Requires:** Active session

**Success Response:**
```json
{
  "success": 1,
  "status": 200,
  "url": "/",
  "message": "Logged out",
  "data": null
}
```

---

### Create User Session

Create a new session for authenticated user.

**Endpoint:** `POST /users/createUserSession`

**Requires:** User ID in request body

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | int | Yes | User ID |

**Sets Session Variables:**
- `$_SESSION['user_id']`
- `$_SESSION['user_email']`
- `$_SESSION['user_fname']`
- `$_SESSION['time']`

---

## Validation Rules (Register)

| Field | Rules |
|-------|-------|
| `SurName` | Required, min:3, max:16, alpha only |
| `Name` | Required, min:3, max:16, alpha only |
| `MiddleName` | Required, min:3, max:16, alpha only |
| `Gender` | Required, alpha (M/F/O) |
| `UserName` | Required, min:5 characters |
| `Email` | Required, valid email format |
| `Password` | Required, min:6 chars, must contain uppercase, lowercase, number, specialChars |
| `UserStatus` | Required, alpha |

---

## 2FA (Two-Factor Authentication)

### Setup
1. User registers → 2FA secret generated
2. QR code displayed (Google Authenticator URL)
3. User scans QR code
4. 2FA secret stored in `users_list_code` table

### Login Flow
1. User enters email + password
2. If credentials valid → returns `md51Key`, `OtherKey`, QR image (if no 2FA set)
3. User enters TOTP code from authenticator app
4. User calls `POST /users/g2faCodeV` to verify
5. Session created on success

---

## Security Features

| Feature | Implementation |
|---------|---------------|
| Password Hashing | `password_hash(PASSWORD_DEFAULT)` — bcrypt |
| 2FA | Google Authenticator TOTP |
| Session Auth | PHP sessions |
| Login Attempt Limit | 8 failed attempts → Account locked |
| Password Reset | 10-minute token expiry |
| Password Expiry | `Expiration_Date` check on login |
| User Status | Enable/Disable/Hold/Reset/Lock states |
| SQL Injection | PDO prepared statements |
| Input Validation | Custom `ValidField` class |
| Login History | `users_login_history` table |
| Action History | `users_action_history` table |
| IP Tracking | All auth events include `user_ip` |

---
## Testing with Postman

The [OpenAPI spec](openapi.yaml) provides a machine-readable API contract
that can be imported directly into Postman, Insomnia, or any OpenAPI-compatible tool.

### Import into Postman

1. Open Postman → File → Import → Link
2. Paste: `http://localhost/api/docs/openapi.yaml` (or the raw file URL)
3. All 8 endpoints will appear as collection requests

### Environment Variables

| Variable | Value |
|----------|-------|
| `base_url` | `http://localhost/api` |

### Test Sequence

1. `POST /users/register` — Create account
2. `POST /users/login` — Get 2FA data
3. `POST /users/g2faCodeV` — Verify 2FA
4. `POST /users/resetPassword` — Request reset
5. `POST /users/checkResetToken` — Validate token
6. `POST /users/logout` — End session
---

## Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-09-15 | Initial API documentation |