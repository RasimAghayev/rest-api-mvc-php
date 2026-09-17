# Architecture Documentation

> rest-api-mvc-php — MVC Layers, Auth Flow, Database Schema

---

## MVC Layer Architecture

```mermaid
graph TB
    subgraph "Request Entry"
        Client[HTTP Client]
        WebServer[Nginx/Apache]
        APIRouter[api/index.php]
    end

    subgraph "Controller Layer (app/mvc/controllers/)"
        UsersCtrl[UsersController]
        DashCtrl[DashboardsController]
        RepCtrl[ReportsController]
        SLCtrl[SelectListsController]
        TaskCtrl[TasksController]
    end

    subgraph "Model Layer (app/mvc/models/)"
        UserModel[User Model]
        DashModel[Dashboard Model]
        RepModel[Report Model]
        SLModel[SelectList Model]
        TaskModel[Task Model]
    end

    subgraph "Library Layer (app/libraries/)"
        GAuth[GoogleAuthenticator]
        DB[Database Class]
        Core[Core Library]
    end

    subgraph "Database (MySQL)"
        MySQL[(MySQL)]
    end

    Client --> WebServer
    WebServer --> APIRouter
    APIRouter --> UsersCtrl
    APIRouter --> DashCtrl
    APIRouter --> RepCtrl
    APIRouter --> SLCtrl
    APIRouter --> TaskCtrl
    UsersCtrl --> UserModel
    DashCtrl --> DashModel
    RepCtrl --> RepModel
    SLCtrl --> SLModel
    TaskCtrl --> TaskModel
    UserModel --> DB
    DB --> MySQL
    UsersCtrl --> GAuth
    GAuth --> MySQL
    APIRouter --> Core
```

### Controller Responsibilities

| Controller | File | Actions |
|------------|------|---------|
| **Users** | `app/mvc/controllers/Users.php` | register, login, resetPassword, checkResetToken, g2faCodeC, g2faCodeV, logout, createUserSession |
| **Dashboards** | `app/mvc/controllers/Dashboards.php` | index, index2, create, edit, delete |
| **Reports** | `app/mvc/controllers/Reports.php` | index, create, edit, delete |
| **SelectLists** | `app/mvc/controllers/SelectLists.php` | index, create, edit, delete |
| **Tasks** | `app/mvc/controllers/Tasks.php` | index, create, edit, delete |

---

## Authentication Flow

### Session + 2FA Authentication

```mermaid
stateDiagram-v2
    [*] --> Unauthenticated

    Unauthenticated --> Registering: POST /users/register
    Registering --> OTPPending: User created, 2FA secret generated
    OTPPending --> Registered: User scans QR code

    Unauthenticated --> LoggingIn: POST /users/login
    LoggingIn --> CredentialsValid: Email/Password match
    CredentialsValid --> StatusCheck: Check UserStatus
    StatusCheck --> Authenticated2FA: Status OK (not D/H/R/L)
    StatusCheck --> Blocked: Status is D/H/R/L
    Blocked --> [*]: Access denied

    Authenticated2FA --> Requires2FA: Password verified
    Requires2FA --> Authenticated: 2FA code verified (g2faCodeV)
    Authenticated --> SessionActive: Session created

    SessionActive --> Authenticated: Valid session
    SessionActive --> LoggingOut: POST /users/logout
    LoggingOut --> Unauthenticated: Session destroyed
```

### 2FA Setup Flow

```mermaid
sequenceDiagram
    participant User
    participant API as UsersController
    participant Model as User Model
    participant GA as GoogleAuthenticator
    participant DB

    User->>API: POST /users/register
    API->>Model: Validate input
    Model->>DB: Check email uniqueness
    DB-->>Model: Email available
    Model->>Model: Hash password
    Model->>Model: Generate SecretKey
    Model->>DB: INSERT user
    DB-->>Model: User ID returned
    Model->>GA: createSecret()
    GA-->>Model: 2FA secret
    Model->>GA: getQRCodeGoogleUrl()
    GA-->>Model: QR code URL
    Model->>Model: Display QR to user
    Model->>GA: createSecret()
    Model->>DB: Store 2FA secret (g2faCodeU)
```

---

## Database Schema

```mermaid
erDiagram
    USERS ||--o{ users_password_reset : reset_token
    USERS ||--o{ users_password_failed : failed_attempts
    USERS ||--o{ users_login_history : login_events
    USERS ||--o{ users_action_history : actions
    USERS ||--o{ users_list_code : t2fa_secrets
    USERS ||--o{ DASHBOARDS : owns
    USERS ||--o{ REPORTS : owns
    USERS ||--o{ TASKS : owns

    USERS {
        int id PK
        string SurName
        string Name
        string MiddleName
        string Gender
        string UserName
        string Email UK
        string Password
        string SecretKey
        string UserStatus
        datetime Expiration_Date
        string user_ip
        datetime create_date
    }
    users_password_reset {
        int id PK
        int user_id FK
        string Token
        string UserStatus
        int Expired
        string user_ip
        datetime create_date
    }
    users_password_failed {
        int id PK
        int user_id FK
        string user_ip
        int count
    }
    users_login_history {
        int id PK
        int user_id FK
        string status
        string user_ip
        datetime create_date
    }
    users_action_history {
        int id PK
        int user_id FK
        string action
        string get_url
        string user_ip
        datetime create_date
    }
    users_list_code {
        int id PK
        int user_id FK
        string SecretKey
        string g2fa
        string user_ip
    }
    DASHBOARDS {
        int id PK
        int user_id FK
        string title
        text content
        datetime create_date
    }
```

---

## Request Lifecycle

```mermaid
sequenceDiagram
    participant Client
    participant Nginx
    participant index as api/index.php
    participant Core
    participant Controller
    participant Model
    participant DB

    Client->>Nginx: HTTP Request (POST /api/users/login)
    Nginx->>index: Rewrite to index.php?url=users/login
    index->>Core: new Core()
    Core->>Controller: Instantiate Users controller
    Controller->>Model: $this->model('User')
    Model->>DB: SELECT * FROM users WHERE...
    DB-->>Model: User data
    Model-->>Controller: User data
    Controller->>Controller: password_verify()
    Controller->>Model: loginUserStatusC('PT')
    Model->>DB: INSERT login history
    Controller->>Controller: Session create
    Controller-->>Client: JSON Response (202)
```

---

## Security Architecture

```mermaid
graph TB
    subgraph "Input Security"
        IVS[Input Validation<br/>ValidField class]
        SQLI[SQL Injection<br/>PDO prepared statements]
        XSS[XSS Protection<br/>Input sanitization]
    end

    subgraph "Auth Security"
        PH[Password Hashing<br/>bcrypt password_hash]
        TOTP[2FA TOTP<br/>GoogleAuthenticator]
        SL[Session Security<br/>PHP sessions + timeout]
        LL[Login Limit<br/>8 attempts → Lock]
    end

    subgraph "Data Security"
        RT[Reset Token<br/>10 min expiry]
        EH[Expiration Date<br/>Password expiry check]
        AH[Action Audit<br/>users_action_history]
        LH[Login History<br/>users_login_history]
    end

    IVS --> PH
    SQLI --> PH
    XSS --> PH
    PH --> TOTP
    TOTP --> SL
    SL --> LL
    RT --> AH
    EH --> LH
```

---

## UserStatus States

| Code | Meaning | Effect |
|------|---------|--------|
| `E` | Enable | User can login |
| `D` | Disable | Login blocked |
| `H` | Hold | Login blocked |
| `R` | Reset | Password reset required |
| `L` | Lock | Account locked (8 failed attempts) |

---

## Roadmap / Known Gaps

Planned work that is not yet implemented, consolidated here from retired
scratch notes (`ToDo`, `Url&Data.txt`) during the 2026-09-17 doc cleanup:

- **Role-Based Access Control (ACL).** A `UserID` / `RoleID` / `PermissionID`
  model was sketched but never built — there is no `roles` or `permissions`
  table in the [current schema](schema.md); all endpoints are gated by
  session auth + 2FA only, with no per-role permission checks.
- **Outbound email.** A `sendMail()` / `save_mail()` PHPMailer+IMAP helper
  exists in `app/helpers/index.php` (SMTP credentials now read from
  `SMTP_USERNAME`/`SMTP_PASSWORD`/`SMTP_FROM_EMAIL`/`SMTP_TEST_RECIPIENT` env
  vars, see `.env.example`) but it is **not called from any controller**.
  Registration and `resetPassword` currently return the reset token/QR data
  in the API response instead of emailing it — wiring `sendMail()` into
  those flows is open work.

---

## Reference

- [README.md](../README.md) — Project overview and API reference
- [API Documentation](../docs/api.md) — Complete endpoint details