# rest-api-mvc-php

> PHP REST API — MVC Pattern, 2FA (TOTP/Google Authenticator), MySQL, Session-based Auth

[![PHP Version](https://img.shields.io/badge/PHP-8.x-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## Overview

**rest-api-mvc-php** is a PHP REST API built with the MVC architectural pattern. It provides user authentication with two-factor authentication (2FA/TOTP via Google Authenticator), password reset with token verification, login attempt limiting, and audit logging (login history, action history). The API serves as the backend for web applications that need secure user management.

### Key Features

- **MVC Architecture** — Clean separation: `api/` (routing), `app/mvc/` (controllers, models), `app/libraries/` (vendor logic)
- **2FA via Google Authenticator** — TOTP-based two-factor authentication
- **Session-based Authentication** — PHP sessions with cookie-based management
- **Password Reset** — Token-based password reset (10-minute expiry)
- **Login Security** — Failed attempt limiting (8 attempts → lock), login history tracking
- **Action Audit** — User action history (action + URL logging)
- **RESTful API** — JSON request/response via `api/index.php` router

### Tech Stack

| Layer | Technology |
|-------|------------|
| Language | PHP 8.x |
| Architecture | MVC (Custom) |
| Database | MySQL |
| 2FA | Google Authenticator (TOTP) |
| Auth | PHP Sessions + 2FA |
| Web Server | Nginx / Apache (via .htaccess) |
| Database Layer | Custom Database class (PDO) |

---

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Client Request                        │
└─────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────┐
│                    Nginx / Apache                        │
│              (Document root → /api/)                     │
└─────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────┐
│                   api/index.php                          │
│         (Bootstrap → Core Init → Route)                  │
└─────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────┐
│              app/mvc/controllers/                        │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐ │
│  │  Users.php   │ │ Dashboards.php│ │ Reports.php      │ │
│  │              │ │              │ │ SelectLists.php  │ │
│  │  register    │ │ index        │ │ index            │ │
│  │  login       │ │ index2       │ │                  │ │
│  │  resetPassword│ │ create       │ │                  │ │
│  │  checkReset  │ │ edit         │ │                  │ │
│  │  g2faCodeC   │ │ delete       │ │                  │ │
│  │  g2faCodeV   │ │              │ │                  │ │
│  │  logout      │ │              │ │                  │ │
│  │  createSession│ │             │ │                  │ │
│  └──────────────┘ └──────────────┘ └──────────────────┘ │
└─────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────┐
│              app/mvc/models/                             │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐ │
│  │  User.php    │ │ Dashboard.php│ │ Report.php       │ │
│  │              │ │              │ │ SelectList.php   │ │
│  │  registerUser│ │ getPosts     │ │ getReport        │ │
│  │  login       │ │ editPost     │ │                  │ │
│  │  login*Attmps│ │ deletePost   │ │                  │ │
│  │  resetPassword│ │             │ │                  │ │
│  │  checkResetToken│ │            │ │                  │ │
│  │  g2faCode*   │ │              │ │                  │ │
│  │  userActionHistory*│ │        │ │                  │ │
│  └──────────────┘ └──────────────┘ └──────────────────┘ │
└─────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────┐
│                     MySQL Database                       │
│  users, users_password_reset, users_password_failed,    │
│  users_login_history, users_action_history,             │
│  users_list_code, dashboards, reports, tasks,           │
│  select_lists                                           │
└─────────────────────────────────────────────────────────┘
```

### Directory Structure

```
rest-api-mvc-php/
├── api/
│   ├── index.php          # API entry point, bootstrap, core init
│   └── .htaccess          # URL rewriting, CORS headers
├── app/
│   ├── bootstrap.php      # Application bootstrap
│   ├── config/            # Configuration files
│   ├── helpers/           # Helper functions
│   ├── libraries/         # Vendor libraries
│   │   ├── GoogleAuthenticator/  # 2FA library
│   │   └── OtherClass/           # Other classes (QR code, etc.)
│   └── mvc/
│       ├── controllers/
│       │   ├── Users.php
│       │   ├── Dashboards.php
│       │   ├── Reports.php
│       │   ├── SelectLists.php
│       │   └── Tasks.php
│       ├── models/
│       │   ├── User.php
│       │   ├── Dashboard.php
│       │   ├── Report.php
│       │   ├── SelectList.php
│       │   └── Task.php
│       └── .htaccess
├── docs/
│   ├── architecture.md    (NEW — diagrams + patterns)
│   ├── api.md             (NEW — endpoint reference)
│   ├── openapi.yaml       (NEW — OpenAPI 3.0 spec)
│   └── installation.md    (NEW — setup guide)
├── important/
│   ├── file.php
│   ├── generate_uuid.php
│   ├── youtube.php
│   └── js/
├── other/OTP/
│   └── OTP_JS.html        # Frontend OTP interface
├── Documentation.txt      # Auth state machine (raw)
├── ToDo                   # Feature tasks
├── Url&Data.txt           # API endpoint examples
├── cmd                    # Frontend setup commands
├── CMD2                   # Framework comparison commands
├── LICENSE                # MIT License
└── README.md              # This file
```

---

## Authentication Flow

### Register → 2FA Setup → Login Flow

```
User Registration
       │
       ▼
┌─────────────────────┐
│ POST /users/register│
│ (SurName, Name,     │
│  MiddleName, Gender,│
│  UserName, Email,   │
│  Password,          │
│  UserStatus)        │
└─────────────────────┘
       │
       ▼
┌─────────────────────────┐
│ Validate input rules    │
│ Check email uniqueness  │
│ Hash password           │
│ Generate SecretKey      │
│ Register user in DB     │
│ Create 2FA secret       │
│ Generate QR code        │
└─────────────────────────┘
       │
       ▼
┌─────────────────────┐
│ 2FA Setup Complete  │
│ User scans QR with  │
│ Google Authenticator│
└─────────────────────┘
       │
       ▼
Login Attempt
       │
       ▼
┌─────────────────────┐
│ POST /users/login   │
│ (Email/UserName,    │
│  Password)          │
└─────────────────────┘
       │
       ▼
┌─────────────────────────┐
│ Verify credentials      │
│ Check UserStatus        │
│ (D=Disable,H=Hold,R=    │
│  Reset,L=Lock)          │
│ Check password_verify   │
│ Check Expiration_Date   │
└─────────────────────────┘
       │
       ▼
┌─────────────────────┐
│ Password correct?   │
└──────────┬──────────┘
           │
     ┌─────┴──────┐
     ▼             ▼
   YES            NO
   │              │
   ▼              ▼
Generate       Login Faild
2FA Data       Attempt +1
(2FA secret,   Check count
 md51Key,     >=8? → LOCK
 OtherKey,    → "Failed login
 jwt keys)    attempt limit"
   │
   ▼
SET SESSION
(user_id, user_email, user_fname, time)
   │
   ▼
Access Granted
```

### Password Reset Flow

```
POST /users/resetPassword
       │
       ▼
Verify email exists
       │
       ▼
Generate Token (base64(bin2hex(random_bytes(30))))
       │
       ▼
Store token in users_password_reset (10 min expiry)
       │
       ▼
Send reset link via email
       │
       ▼
User clicks link → POST /users/checkResetToken
       │
       ▼
Validate token (not expired, not used)
       │
       ▼
Set new password
```

---

## API Endpoints

All endpoints accept and return JSON. URL route: `/api/{endpoint}`

### User Authentication

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `POST` | `/users/register` | Register new user, create 2FA | No |
| `POST` | `/users/login` | User login, returns 2FA data | No |
| `POST` | `/users/resetPassword` | Request password reset | No |
| `POST` | `/users/checkResetToken` | Validate reset token | No |
| `POST` | `/users/g2faCodeC` | Generate 2FA code | Session |
| `POST` | `/users/g2faCodeV` | Verify 2FA code | Session |
| `GET/POST` | `/users/logout` | Logout (destroy session) | Session |
| `POST` | `/users/createUserSession` | Create user session | Session |

### Request/Response Examples

#### Register User
```bash
curl -X POST http://localhost/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "SurName": "Rasim",
    "Name": "Rasim",
    "MiddleName": "Shukur",
    "Gender": "M",
    "UserName": "afsdad",
    "Email": "raghayev@gmail.com",
    "Password": "Rasim123$",
    "UserStatus": "E",
    "Expiration_Date": "2027-01-01 00:00:00"
  }'
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Project has been created",
  "data": {
    "SurName": "Rasim",
    "Name": "Rasim",
    "MiddleName": "Shukur",
    "Gender": "M",
    "UserName": "afsdad",
    "Email": "raghayev@gmail.com",
    "Password": "Rasim123$",
    "UserStatus": "E"
  }
}
```

#### Login
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
  "success": true,
  "message": "User logged in successfully",
  "data": {
    "user_id": 1,
    "md51Key": "...",
    "OtherKey": "...",
    "redirect": "/TOTP"
  }
}
```

#### Reset Password
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
  "success": true,
  "message": "Users has been reset",
  "data": {
    "Email": "raghayev@gmail.com"
  }
}
```

### Validation Rules (Register)

| Field | Rules |
|-------|-------|
| `SurName` | Required, min:3, max:16, alpha |
| `Name` | Required, min:3, max:16, alpha |
| `MiddleName` | Required, min:3, max:16, alpha |
| `Gender` | Required, alpha |
| `UserName` | Required, min:5 |
| `Email` | Required, valid email |
| `Password` | Required, min:6, strength: uppercase, lowercase, number, specialChars |
| `UserStatus` | Required, alpha |

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 201 | Created successfully |
| 202 | Login successful (requires 2FA) |
| 404 | Not found / Invalid credentials |
| 424 | Failed to create / update |
| 500 | Validation error |
| 503 | Access denied (wrong method) |
| 423 | User is locked/disabled/hold/reset |
| 417 | Exception / Server error |

---

## Security Features

| Feature | Implementation |
|---------|---------------|
| Password Hashing | `password_hash()` (bcrypt) |
| 2FA | Google Authenticator TOTP |
| Login Attempt Limit | 8 failed attempts → Lock |
| Session Management | PHP sessions with timeout |
| Input Validation | Custom `ValidField` class |
| SQL Injection Prevention | PDO prepared statements |
| XSS Protection | Input sanitization |
| Password Reset | 10-minute token expiry |
| Login History | Tracked in `users_login_history` |
| Action History | Tracked in `users_action_history` |

---

## Installation

Full installation guide with Composer dependencies, environment setup, and configuration: [docs/installation.md](docs/installation.md)

### Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/RasimAghayev/rest-api-mvc-php.git
cd rest-api-mvc-php

# 2. Configure database credentials in app/config/config.php

# 3. Import database schema
mysql -u your_user -p rest_api < database.sql

# 4. Install Composer dependencies
composer install

# 5. Start development server
cd api
php -S localhost:8000

# 6. Test the API
curl http://localhost:8000/users/login \
  -H "Content-Type: application/json" \
  -d '{"Email":"your@email.com","Password":"your-password"}'
```

### Requirements

| Requirement | Version |
|-------------|---------|
| PHP | >= 8.0 |
| MySQL | 5.7+ / MariaDB |
| Composer | >= 2.0 |
| Web Server | Nginx or Apache (or PHP built-in server) |

### Database Tables

| Table | Description |
|-------|-------------|
| `users` | User accounts |
| `users_password_reset` | Password reset tokens |
| `users_password_failed` | Failed login attempts |
| `users_login_history` | Login events |
| `users_action_history` | User actions |
| `users_list_code` | 2FA secret codes |
| `dashboards` | Dashboard data |
| `reports` | Report data |
| `tasks` | Task data |
| `select_lists` | Select list data |

---

## Project Status

| Feature | Status |
|---------|--------|
| User Registration | ✅ Active |
| User Login | ✅ Active |
| 2FA (TOTP) | ✅ Active |
| Password Reset | ✅ Active |
| Login Attempt Limit | ✅ Active |
| Session Auth | ✅ Active |
| JWT Auth Middleware | ✅ Active |
| Docker Support | ❌ Missing |
| API Documentation | ❌ Missing |
| PHPUnit Tests | ✅ Scaffolded |
| Composer Setup | ✅ Complete |

---

## Related Projects

| Project | Relationship |
|---------|--------------|
| `react-with-laravel` | Frontend consumer of this API |
| `web-scraping-php` | PHP ecosystem companion |
| `csv2json_spatie` | Data transformation companion |
| `nanoservice-laravel` | Monolithic vs microservices comparison |

---

## License

MIT License — See [LICENSE](LICENSE) for details.