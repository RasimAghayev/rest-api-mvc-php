# Auth Flow — Mermaid Flowchart

> Converted from `Documentation.txt` — Auth state machine flow

## State Definitions

| Code | Meaning |
|------|---------|
| `TN` | True Next — proceed to next step |
| `FD` | False Die — abort / fail |
| `RP` | Response |
| `CH` | Check |
| `IP` | Input |
| `EP` | Empty |
| `FR` | Forward |
| `E` | Enable |
| `R` | Reset |
| `D` | Disable |
| `H` | Hold |
| `L` | Lock |

## 0. Register

```mermaid
flowchart TD
    A[POST /users/register] --> B{POST OK?}
    B -->|TN| C[JSON_DECODE data]
    B -->|FD| Z[Abort]
    C --> D[Response]
    Z
```

## 1. Login

```mermaid
flowchart TD
    A[POST /users/login] --> B{POST OK?}
    B -->|TN| C[JSON_DECODE data]
    B -->|FD| V[Abort]
    C --> D[Response]

    subgraph InputValidation [Step 3: Input Validation]
        E[Email, UserName, Password Input] --> F{Check Empty?}
        F -->|EP| B
        F -->|!EP| G{Check Valid?}
        G -->|TN| H[Proceed]
        G -->|FD| B
    end

    subgraph LoginProcess [Step 4: Login]
        H --> I[userModel.login Email, UserName]
        I --> J{Check Email, UserName}
        J -->|TN| K[Proceed]
        J -->|FD| V
    end

    subgraph ExpirationCheck [Step 5: Expiration Date Check]
        K --> L[Check Expiration_Date]
        L --> M{Status?}
        M -->|E| N[True Next]
        M -->|R| O[Forward to Reset]
        N --> P[Continue]
        O --> Q[Password Reset Check History]
    end

    subgraph StatusCheck [Step 6: User Status Check]
        P --> R{Check UserStatus}
        R -->|TN| S[Password Verify]
        R -->|FD| V
    end

    subgraph TwoFA [Step 7-8: Password Verify + 2FA]
        S --> T[password_verify]
        T --> U{Password Match?}
        U -->|TN| V2[Forward to T_OTP]
        U -->|FD| V
        V2 --> W[jwtEncode Create Token]
    end

    subgraph FailedAttempts [Step 9: Failed Attempts]
        X{Failed Attempts < 8?}
        X -->|Yes| Y[userModel.loginFaildAttempsC Create]
        Y --> Z1[userModel.loginFaildAttempsU Update]
        X -->|No| L2[Lock Account]
    end

    subgraph ResetFlow [Reset->Password]
        Q --> AA[ReNew Password Check Password History]
    end

    Z -.-> V
    L2 -.-> V
    AA -.-> Q
```

## 2. Reset Password

```mermaid
flowchart TD
    A[POST /users/resetPassword] --> B{POST OK?}
    B -->|TN| C[JSON_DECODE data]
    B -->|FD| Z[Abort]
    C --> D[Response]

    subgraph ResetInput [Input Validation]
        E[Email, UserName Input] --> F{Check Empty?}
        F -->|EP| B
        F -->|!EP| G{Check Valid?}
        G -->|TN| H[userModel.login]
        G -->|FD| B
    end

    H --> I{Check Email, UserName}
    I -->|TN| J[Proceed]
    I -->|FD| Z
```

## 3. Check Reset Token

```mermaid
flowchart TD
    A[POST /users/checkResetToken] --> B{POST OK?}
    B -->|TN| C[JSON_DECODE data]
    B -->|FD| Z[Abort]
    C --> D[Response]
```

## 4-6. Remaining Endpoints

Each endpoint follows the same pattern:

```mermaid
flowchart TD
    A[POST Endpoint] --> B{POST OK?}
    B -->|TN| C[JSON_DECODE data]
    B -->|FD| Z[Abort]
    C --> D[Response]
```

## Reference

> The raw `Documentation.txt` state-machine notation this page was converted
> from has been retired (2026-09-17 doc cleanup) — this file is now the
> single source of truth for the auth flow.

- [Architecture](../architecture.md) — MVC layers, auth flow state diagram, DB schema
- [API Reference](../docs/api.md) — Endpoint details
