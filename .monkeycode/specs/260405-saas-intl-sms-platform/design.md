# SaaS International SMS Platform - Technical Design Document

## 1. Technology Stack

### 1.1 Backend Stack
| Component | Technology |
|-----------|------------|
| Framework | ASP.NET Core 8.0 |
| ORM | Entity Framework Core 8.0 |
| Database | PostgreSQL 15 |
| Cache | Redis 7 |
| Message Queue | RabbitMQ |
| API Documentation | OpenAPI 3.0 / Swagger |
| Authentication | JWT Bearer Tokens |
| Background Jobs | Hangfire |
| Logging | Serilog + ELK Stack |

### 1.2 Frontend Stack
| Component | Technology |
|-----------|------------|
| Framework | React 18 + TypeScript |
| UI Library | Ant Design 5 |
| State Management | Zustand |
| HTTP Client | Axios |
| Build Tool | Vite |

### 1.3 Infrastructure
| Component | Technology |
|-----------|------------|
| Container | Docker + Docker Compose |
| Reverse Proxy | Nginx |
| SSL | Let's Encrypt |
| Monitoring | Prometheus + Grafana |

---

## 2. Project Structure

```
IntlSMS/
├── src/
│   ├── IntlSMS.Api/                    # API Layer
│   │   ├── Controllers/
│   │   │   ├── AuthController.cs
│   │   │   ├── SmsController.cs
│   │   │   ├── ApiKeyController.cs
│   │   │   ├── AnalyticsController.cs
│   │   │   └── AdminController.cs
│   │   ├── Middleware/
│   │   │   ├── RateLimitingMiddleware.cs
│   │   │   ├── TenantMiddleware.cs
│   │   │   └── ExceptionMiddleware.cs
│   │   ├── Filters/
│   │   │   └── ValidateModelAttribute.cs
│   │   ├── Program.cs
│   │   └── appsettings.json
│   │
│   ├── IntlSMS.Application/           # Application Layer
│   │   ├── Services/
│   │   │   ├── ISmsService.cs
│   │   │   ├── SmsService.cs
│   │   │   ├── IApiKeyService.cs
│   │   │   ├── ApiKeyService.cs
│   │   │   ├── IAuthService.cs
│   │   │   ├── AuthService.cs
│   │   │   ├── IAnalyticsService.cs
│   │   │   └── AnalyticsService.cs
│   │   ├── DTOs/
│   │   │   ├── SmsSendRequest.cs
│   │   │   ├── SmsSendResponse.cs
│   │   │   ├── BatchSmsRequest.cs
│   │   │   └── ApiKeyDto.cs
│   │   └── Validators/
│   │       ├── SmsSendRequestValidator.cs
│   │       └── BatchSmsRequestValidator.cs
│   │
│   ├── IntlSMS.Domain/                # Domain Layer
│   │   ├── Entities/
│   │   │   ├── Tenant.cs
│   │   │   ├── User.cs
│   │   │   ├── ApiKey.cs
│   │   │   ├── SmsMessage.cs
│   │   │   ├── SmsBatch.cs
│   │   │   ├── Country.cs
│   │   │   ├── Gateway.cs
│   │   │   ├── GatewayRoute.cs
│   │   │   ├── WebhookConfig.cs
│   │   │   ├── Transaction.cs
│   │   │   └── AuditLog.cs
│   │   ├── Enums/
│   │   │   ├── SmsStatus.cs
│   │   │   ├── GatewayType.cs
│   │   │   └── TransactionType.cs
│   │   └── Interfaces/
│   │       ├── ISmsGateway.cs
│   │       ├── ISmsRepository.cs
│   │       └── ITenantRepository.cs
│   │
│   ├── IntlSMS.Infrastructure/         # Infrastructure Layer
│   │   ├── Data/
│   │   │   ├── AppDbContext.cs
│   │   │   └── Migrations/
│   │   ├── Repositories/
│   │   │   ├── SmsRepository.cs
│   │   │   └── TenantRepository.cs
│   │   ├── Gateways/
│   │   │   ├── TwilioGateway.cs
│   │   │   ├── NexmoGateway.cs
│   │   │   ├── MessageBirdGateway.cs
│   │   │   └── GatewayFactory.cs
│   │   ├── External/
│   │   │   └── Ip2CountryService.cs
│   │   └── Services/
│   │       └── WebhookService.cs
│   │
│   └── IntlSMS.Worker/                # Background Worker
│       ├── SmsDeliveryWorker.cs
│       ├── WebhookRetryWorker.cs
│       └── Program.cs
│
├── tests/
│   ├── IntlSMS.UnitTests/
│   │   ├── Services/
│   │   │   ├── SmsServiceTests.cs
│   │   │   └── ApiKeyServiceTests.cs
│   │   └── Validators/
│   │       └── SmsSendRequestValidatorTests.cs
│   └── IntlSMS.IntegrationTests/
│
├── src/
│   └── IntlSMS.Web/                   # React Frontend
│       ├── src/
│       │   ├── pages/
│       │   │   ├── Login.tsx
│       │   │   ├── Dashboard.tsx
│       │   │   ├── SmsSend.tsx
│       │   │   ├── ApiKeys.tsx
│       │   │   └── Analytics.tsx
│       │   ├── components/
│       │   │   ├── Layout.tsx
│       │   │   └── SmsTable.tsx
│       │   ├── services/
│       │   │   └── api.ts
│       │   ├── stores/
│       │   │   └── authStore.ts
│       │   └── App.tsx
│       ├── package.json
│       └── vite.config.ts
│
├── docker-compose.yml
├── Dockerfile.api
├── Dockerfile.worker
├── Dockerfile.web
└── README.md
```

---

## 3. Database Schema

### 3.1 Entity Relationship Diagram (Simplified)

```
Tenant (1) ─── (N) User
    │
    ├── (N) ApiKey
    ├── (N) SmsMessage
    ├── (N) SmsBatch
    ├── (N) Transaction
    ├── (N) WebhookConfig
    │
    └── (1) ─── (N) GatewayRoute (via Country)

Country (1) ─── (N) GatewayRoute
                  │
                  └── (N) Gateway
```

### 3.2 Table Definitions

#### Tenants
| Column | Type | Constraints |
|--------|------|-------------|
| id | UUID | PK |
| name | VARCHAR(200) | NOT NULL |
| email | VARCHAR(255) | UNIQUE, NOT NULL |
| password_hash | VARCHAR(500) | NOT NULL |
| status | INT | NOT NULL (0=Pending, 1=Active, 2=Suspended) |
| tier | INT | NOT NULL (0=PayAsYouGo, 1=Standard, 2=Enterprise) |
| balance | DECIMAL(18,6) | DEFAULT 0 |
| created_at | TIMESTAMP | NOT NULL |
| updated_at | TIMESTAMP | NOT NULL |

#### Users
| Column | Type | Constraints |
|--------|------|-------------|
| id | UUID | PK |
| tenant_id | UUID | FK → Tenants |
| email | VARCHAR(255) | NOT NULL |
| password_hash | VARCHAR(500) | NOT NULL |
| role | INT | NOT NULL (0=Admin, 1=Developer, 2=Viewer) |
| is_verified | BOOLEAN | DEFAULT FALSE |
| created_at | TIMESTAMP | NOT NULL |

#### ApiKeys
| Column | Type | Constraints |
|--------|------|-------------|
| id | UUID | PK |
| tenant_id | UUID | FK → Tenants |
| key_hash | VARCHAR(500) | NOT NULL |
| key_secret_hash | VARCHAR(500) | NOT NULL |
| name | VARCHAR(100) | NOT NULL |
| is_active | BOOLEAN | DEFAULT TRUE |
| rate_limit | INT | DEFAULT 1000/min |
| last_used_at | TIMESTAMP | NULL |
| created_at | TIMESTAMP | NOT NULL |

#### SmsMessages
| Column | Type | Constraints |
|--------|------|-------------|
| id | UUID | PK |
| tenant_id | UUID | FK → Tenants |
| api_key_id | UUID | FK → ApiKeys |
| batch_id | UUID | FK → SmsBatches (NULL if single) |
| message_id | VARCHAR(100) | External gateway ID |
| from_number | VARCHAR(20) | Sender ID |
| to_number | VARCHAR(20) | E.164 format |
| to_number_hash | VARCHAR(64) | For deduplication |
| country_code | VARCHAR(3) | ISO 3166-1 |
| message_content | TEXT | Encrypted |
| segments | INT | Number of SMS parts |
| status | INT | NOT NULL |
| gateway_id | UUID | FK → Gateways |
| error_code | VARCHAR(50) | NULL |
| error_message | VARCHAR(500) | NULL |
| scheduled_at | TIMESTAMP | NULL |
| sent_at | TIMESTAMP | NULL |
| delivered_at | TIMESTAMP | NULL |
| created_at | TIMESTAMP | NOT NULL |
| updated_at | TIMESTAMP | NOT NULL |

#### Countries
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PK |
| name | VARCHAR(100) | NOT NULL |
| code | VARCHAR(3) | UNIQUE, NOT NULL |
| iso_code | VARCHAR(2) | UNIQUE, NOT NULL |
| phone_code | VARCHAR(10) | NOT NULL |
| is_supported | BOOLEAN | DEFAULT TRUE |
| price_per_sms | DECIMAL(10,6) | NOT NULL |
| currency | VARCHAR(3) | DEFAULT 'USD' |

---

## 4. API Specification

### 4.1 Authentication

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/auth/register | POST | Register new tenant |
| /api/auth/login | POST | Login and get JWT |
| /api/auth/refresh | POST | Refresh JWT token |
| /api/auth/verify-email | POST | Verify email token |
| /api/auth/forgot-password | POST | Request password reset |
| /api/auth/reset-password | POST | Reset password with token |

### 4.2 SMS Operations

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/sms/send | POST | API Key | Send single SMS |
| /api/sms/send-batch | POST | API Key | Send batch SMS |
| /api/sms/status/{id} | GET | API Key | Get SMS status |
| /api/sms/history | GET | API Key | Get SMS history |

### 4.3 API Key Management

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/keys | GET | JWT | List API keys |
| /api/keys | POST | JWT | Create new API key |
| /api/keys/{id} | DELETE | JWT | Revoke API key |

### 4.4 Analytics

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/analytics/summary | GET | JWT | Get analytics summary |
| /api/analytics/by-country | GET | JWT | Get breakdown by country |
| /api/analytics/by-date | GET | JWT | Get daily/weekly stats |

### 4.5 Webhooks

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/webhooks/config | GET | JWT | Get webhook config |
| /api/webhooks/config | PUT | JWT | Update webhook URL |
| /api/webhooks/test | POST | JWT | Send test webhook |

### 4.6 Admin (Protected)

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/admin/tenants | GET | Admin JWT | List all tenants |
| /api/admin/gateways | GET | Admin JWT | List gateway configs |
| /api/admin/gateways | PUT | Admin JWT | Update gateway config |
| /api/admin/pricing | GET | Admin JWT | Get country pricing |
| /api/admin/pricing | PUT | Admin JWT | Update pricing |

---

## 5. Core Service Implementation

### 5.1 SMS Send Flow

```
1. API receives POST /api/sms/send
2. RateLimitingMiddleware checks key's rate limit (Redis)
3. SmsController validates request
4. SmsService.SendSms():
   a. Validate phone number format
   b. Lookup country pricing
   c. Check tenant balance
   d. Check deduplication (Redis SET)
   e. Create SmsMessage record (status=Queued)
   f. Select gateway based on country (GatewayFactory)
   g. Publish to RabbitMQ queue
5. Return response with message_id
6. SmsDeliveryWorker consumes queue:
   a. Call gateway.SendSms()
   b. Update message status to Sent
   c. Publish webhook event
7. Gateway delivers SMS
8. Gateway calls webhook /api/webhooks/dlr
9. WebhookService updates message status to Delivered/Failed
```

### 5.2 Gateway Selection Strategy

```csharp
public class GatewayFactory : IGatewayFactory
{
    public ISmsGateway GetGateway(Country country)
    {
        var route = _routeRepository.GetActiveRoute(country.Code);
        
        if (route.Gateway.IsHealthy && route.Gateway.IsAvailable)
            return _gateways[route.Gateway.Type];
            
        var fallback = _routeRepository.GetFallbackRoute(country.Code);
        return _gateways[fallback.Gateway.Type];
    }
}
```

### 5.3 Rate Limiting Implementation

```csharp
public class RateLimitingMiddleware
{
    public async Task InvokeAsync(HttpContext context)
    {
        var apiKey = context.Request.Headers["X-API-Key"].FirstOrDefault();
        if (apiKey != null)
        {
            var key = $"ratelimit:{apiKey}";
            var count = await _redis.StringIncrementAsync(key);
            
            if (count == 1)
                await _redis.KeyExpireAsync(key, TimeSpan.FromMinutes(1));
                
            if (count > _rateLimitService.GetLimit(apiKey))
            {
                context.Response.StatusCode = 429;
                context.Response.Headers["Retry-After"] = "60";
                return;
            }
        }
    }
}
```

---

## 6. Security Implementation

### 6.1 JWT Token Structure

```json
{
  "sub": "user_id",
  "tenant_id": "tenant_uuid",
  "role": "Admin",
  "exp": 1234567890,
  "iss": "IntlSMS",
  "aud": "IntlSMS.Api"
}
```

### 6.2 API Key Authentication

```
Header: X-API-Key: <key>

OR

Header: Authorization: Bearer <jwt_token>

For signed requests (webhooks):
Header: X-Signature: <hmac_sha256_signature>
Header: X-Timestamp: <unix_timestamp>
```

### 6.3 Webhook Signature Verification

```csharp
public bool VerifyWebhookSignature(string payload, string signature, string secret)
{
    var expectedSignature = HMACSHA256(timestamp + payload, secret);
    return signature == expectedSignature;
}
```

---

## 7. Message Queue Configuration

### 7.1 RabbitMQ Exchanges and Queues

| Exchange | Type | Queue | Routing Key |
|----------|------|-------|-------------|
| sms.direct | direct | sms.send | sms.send |
| sms.direct | direct | sms.dlr | sms.dlr |
| sms.direct | direct | sms.webhook | sms.webhook |
| sms.fallback | direct | sms.retry | sms.retry |

### 7.2 Message Formats

#### SMS Send Message
```json
{
  "messageId": "uuid",
  "tenantId": "uuid",
  "toNumber": "+1234567890",
  "countryCode": "US",
  "content": "encrypted_content",
  "from": "MySenderID",
  "gatewayType": "Twilio",
  "scheduledAt": "2024-01-15T10:00:00Z"
}
```

#### DLR Webhook Message
```json
{
  "messageId": "uuid",
  "status": "Delivered",
  "errorCode": null,
  "errorMessage": null,
  "timestamp": "2024-01-15T10:00:05Z"
}
```

---

## 8. Caching Strategy

### 8.1 Redis Cache Keys

| Key Pattern | TTL | Description |
|-------------|-----|-------------|
| ratelimit:{apiKey} | 1 min | Rate limit counter |
| dedup:{hash} | 5 min | Deduplication set |
| country:{code} | 24 hr | Country info cache |
| gateway:health | 1 min | Gateway health status |
| tenant:balance:{id} | 5 min | Tenant balance cache |
| pricing:all | 1 hr | All country pricing |

---

## 9. Configuration

### 9.1 config.yaml Structure

```yaml
database:
  host: "localhost"
  port: 5432
  username: "intlsms"
  password: "${DB_PASSWORD}"
  name: "intlsms_db"

redis:
  host: "localhost"
  port: 6379
  password: "${REDIS_PASSWORD}"

rabbitmq:
  host: "localhost"
  port: 5672
  username: "intlsms"
  password: "${RABBITMQ_PASSWORD}"
  virtual_host: "/"

jwt:
  secret: "${JWT_SECRET}"
  issuer: "IntlSMS"
  audience: "IntlSMS.Api"
  expiry_minutes: 60
  refresh_expiry_days: 30

gateways:
  twilio:
    account_sid: "${TWILIO_ACCOUNT_SID}"
    auth_token: "${TWILIO_AUTH_TOKEN}"
    from_number: "${TWILIO_FROM_NUMBER}"
  nexmo:
    api_key: "${NEXMO_API_KEY}"
    api_secret: "${NEXMO_API_SECRET}"
  messagebird:
    api_key: "${MESSAGEBIRD_API_KEY}"

app:
  host: "0.0.0.0"
  port: 5000
  environment: "Development"
```

---

## 10. Deployment Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Nginx (SSL)                          │
│                   (Reverse Proxy + Load Balancer)           │
└─────────────────────────┬───────────────────────────────────┘
                          │
         ┌───────────────┼───────────────┐
         │               │               │
    ┌────▼────┐    ┌─────▼─────┐   ┌─────▼─────┐
    │ API Pod │    │ API Pod 2 │   │ Worker Pod│
    │ (ASP.NET│    │(ASP.NET) │   │(Hangfire) │
    │  Core)  │    │           │   │           │
    └────┬────┘    └─────┬─────┘   └─────┬─────┘
         │               │               │
         └───────────────┼───────────────┘
                         │
    ┌────────────────────┼────────────────────┐
    │                    │                    │
┌───▼───┐          ┌─────▼─────┐       ┌─────▼─────┐
│ Postgres│          │   Redis   │       │ RabbitMQ  │
│  (Data) │          │  (Cache)  │       │  (Queue)  │
└─────────┘          └───────────┘       └───────────┘
```

---

## 11. Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| INVALID_PHONE | 400 | Phone number format invalid |
| UNSUPPORTED_COUNTRY | 400 | Country not supported |
| INSUFFICIENT_BALANCE | 402 | Not enough credit |
| RATE_LIMITED | 429 | Rate limit exceeded |
| INVALID_API_KEY | 401 | API key invalid or revoked |
| GATEWAY_ERROR | 502 | Gateway returned error |
| GATEWAY_TIMEOUT | 504 | Gateway timed out |
| INTERNAL_ERROR | 500 | Internal server error |
