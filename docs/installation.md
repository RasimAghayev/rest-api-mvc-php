# Installation Guide

> rest-api-mvc-php — Setup and configuration

---

## Prerequisites

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | >= 8.0 | Required for scalar type hints and `int` return types |
| MySQL | 5.7+ / MariaDB | Database backend |
| Composer | >= 2.0 | Dependency management (optional — no install required) |
| Web Server | Nginx or Apache | Or PHP built-in server for development |

## PHP Extensions

The following PHP extensions must be enabled:

| Extension | Purpose |
|-----------|---------|
| `pdo` | Database connectivity (MySQL) |
| `pdo_mysql` | MySQL driver for PDO |
| `json` | JSON encoding/decoding |
| `openssl` | JWT signing (HS512) |

## Composer Dependencies

This project uses Composer for dependency management and autoloading.

### Install

```bash
composer install
```

### What Composer Provides

| Dependency | Purpose |
|------------|---------|
| `phpunit/phpunit ^9.6` | Test framework (dev dependency) |
| `php >= 8.0` | Minimum PHP version constraint |
| `ext-pdo` | Database extension requirement |
| `ext-json` | JSON extension requirement |
| `ext-openssl` | SSL/JWT extension requirement |

### Autoloading

Composer registers the autoloader via `app/bootstrap.php`:
- `App\` namespace → maps to `app/` directory
- `app/bootstrap.php` registered as a global functions file

### Run Tests

```bash
composer test
```

## Environment Setup

### Step 1: Configure Database

Edit `app/config/config.php`:

```php
define("DB_HOST", "localhost");
define("DB_USER", "your_db_user");      // Change from "root"
define("DB_PASS", "your_db_password");   // Change from ""
define("DB_NAME", "rest-api-mvc-php");
```

**Security note:** Do not use `root` with an empty password in production. Create a dedicated database user with limited privileges.

### Step 2: Create Database

```sql
CREATE DATABASE IF NOT EXISTS rest_api CHARACTER SET utf8 COLLATE utf8_general_ci;
```

### Step 3: Import Schema

Import the database schema (SQL file) into the `rest_api` database:

```bash
mysql -u your_db_user -p rest_api < database.sql
```

The database should contain the following tables:

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

### Step 4: Configure JWT Secret

In `app/config/config.php`, change the JWT signing key to a secure random value:

```php
define('JWT_SECCRET_KEY', 'your-secure-random-key-here');
```

Generate a secure key:
```php
<?php echo bin2hex(random_bytes(64));
```

### Step 5: Configure Web Server

#### Nginx

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/rest-api-mvc-php/api;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

#### Apache (.htaccess already included)

The project includes `api/.htaccess` with mod_rewrite rules. Ensure `mod_rewrite` is enabled:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /api/
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
</IfModule>
```

## Development Server

For local development, use PHP's built-in server:

```bash
cd rest-api-mvc-php/api
php -S localhost:8000
```

The API will be available at `http://localhost:8000/`.

## Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/RasimAghayev/rest-api-mvc-php.git
cd rest-api-mvc-php

# 2. Configure database credentials
# Edit app/config/config.php

# 3. Import database schema
mysql -u your_user -p rest_api < database.sql

# 4. Start the server
cd api
php -S localhost:8000

# 5. Test the API
curl http://localhost:8000/users/login \
  -H "Content-Type: application/json" \
  -d '{"Email":"your@email.com","Password":"your-password"}'
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| `Class 'PDO' not found` | Enable `php-pdo` and `php-pdo_mysql` extensions |
| `JWT decode failed` | Verify `JWT_SECCRET_KEY` matches the encoding key |
| `Access denied` | Check web server document root points to `/api/` |
| `Database connection error` | Verify credentials in `app/config/config.php` |
| `500 Internal Server Error` | Check PHP error log; ensure `display_errors` is On in development |