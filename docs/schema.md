# Database Schema

Project: rest-api-mvc-php
Generated: 2026-09-16

## Tables

### users

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | INT AUTO_INCREMENT | NO | Primary Key |
| SurName | VARCHAR(16) | NO | Surname |
| Name | VARCHAR(16) | NO | First name |
| MiddleName | VARCHAR(16) | NO | Middle name |
| Gender | VARCHAR(1) | NO | Gender code |
| UserName | VARCHAR(16) | NO | Username (unique) |
| Email | VARCHAR(255) | NO | Email (unique) |
| Password | VARCHAR(255) | NO | BCRYPT hash |
| SecretKey | VARCHAR(32) | NO | User secret key |
| UserStatus | VARCHAR(1) | NO | Status: D=Disable, H=Hold, R=Reset, L=Lock |
| Token | TEXT | YES | Password reset token |
| Expiration_Date | DATETIME | NO | Password expiration |
| user_ip | VARCHAR(45) | NO | Registration IP |
| created_at | DATETIME | YES | Creation timestamp |

**Indexes:**
- UNIQUE(Email)
- UNIQUE(UserName)

### users_password_reset

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | INT AUTO_INCREMENT | NO | Primary Key |
| user_id | INT | NO | FK → users.id |
| Token | TEXT | NO | Base64-encoded reset token |
| UserStatus | VARCHAR(1) | NO | Status code (E=Expired) |
| Expired | INT | NO | Unix timestamp expiry |
| user_ip | VARCHAR(45) | NO | Request IP |

**Indexes:**
- INDEX(user_id)

**Relationships:**
- user_id → users.id (Many-to-One)

### users_password_failed

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | INT AUTO_INCREMENT | NO | Primary Key |
| user_id | INT | NO | FK → users.id |
| user_ip | VARCHAR(45) | NO | Failed attempt IP |
| count | INT | YES | Failed attempt count |
| created_at | DATETIME | YES | First attempt timestamp |

**Indexes:**
- UNIQUE(user_id)

**Relationships:**
- user_id → users.id (Many-to-One)

### users_login_history

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | INT AUTO_INCREMENT | NO | Primary Key |
| user_id | INT | NO | FK → users.id |
| status | VARCHAR(2) | NO | Login status code |
| user_ip | VARCHAR(45) | NO | Login IP |
| create_date | DATETIME | YES | Timestamp |

**Indexes:**
- INDEX(user_id)
- INDEX(create_date)

**Relationships:**
- user_id → users.id (Many-to-One)

### users_action_history

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | INT AUTO_INCREMENT | NO | Primary Key |
| user_id | INT | NO | FK → users.id |
| action | VARCHAR(255) | NO | Action type |
| get_url | VARCHAR(500) | NO | Request URL |
| user_ip | VARCHAR(45) | NO | Request IP |
| create_date | DATETIME | YES | Timestamp |

**Indexes:**
- INDEX(user_id)
- INDEX(create_date)

**Relationships:**
- user_id → users.id (Many-to-One)

### users_list_code (OTP/TOTP)

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | INT AUTO_INCREMENT | NO | Primary Key |
| user_id | INT | NO | FK → users.id |
| SecretKey | VARCHAR(32) | NO | TOTP secret key |
| g2fa | VARCHAR(16) | YES | Google Authenticator secret |
| user_ip | VARCHAR(45) | NO | Request IP |

**Indexes:**
- UNIQUE(user_id)

**Relationships:**
- user_id → users.id (Many-to-One)

### tasks

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | INT AUTO_INCREMENT | NO | Primary Key |
| UserID | INT | NO | FK → users.id |
| CompanyName | VARCHAR(255) | NO | Company name |
| WorkName | VARCHAR(255) | NO | Work item name |
| SegmentName | VARCHAR(255) | NO | Segment name |
| WorkCount | INT | YES | Work count |
| TaskStartTime | DATETIME | YES | Start time |
| TaskEndTime | DATETIME | YES | End time |
| FailureTask | TEXT | YES | Failure notes |
| Note | TEXT | YES | Notes |
| created_at | DATETIME | YES | Creation timestamp |

**Indexes:**
- INDEX(UserID)
- INDEX(created_at)

**Relationships:**
- UserID → users.id (Many-to-One)

### posts (legacy/placeholder)

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | INT AUTO_INCREMENT | NO | Primary Key |
| title | VARCHAR(255) | NO | Post title |
| body | TEXT | NO | Post content |

**Notes:** Referenced by Task.php update/delete methods — may be legacy artifact.

## Entity-Relationship Summary

```
users ──┬──< users_password_reset
        ├──< users_password_failed
        ├──< users_login_history
        ├──< users_action_history
        ├──< users_list_code
        └──< tasks
```

## Notes

- All timestamps use DATETIME type (MySQL)
- IP addresses stored as VARCHAR(45) (IPv6 compatible)
- Passwords stored as BCRYPT hashes (PASSWORD_DEFAULT)
- TOTP secrets stored in users_list_code.g2fa (Google Authenticator format)
- Foreign key constraints may not be enforced at DB level (application-level relationships)
