# SaaS International SMS Platform - Requirements Document

## 1. Project Overview

### Project Name
**IntlSMS** - SaaS International SMS Platform

### Project Type
Multi-tenant SaaS Platform (B2B)

### Core Functionality Summary
A multi-tenant cloud platform enabling businesses to send international SMS messages to users worldwide, supporting multiple SMS gateways, real-time delivery tracking, and comprehensive analytics.

### Target Users
- E-commerce platforms needing global order notifications
- Financial institutions requiring OTP and transaction alerts
- Marketing teams running international SMS campaigns
- SaaS providers implementing multi-factor authentication (MFA)

---

## 2. Functionality Specification

### 2.1 Multi-Tenant Architecture

| Feature | Description |
|---------|-------------|
| Tenant Isolation | Complete data isolation per tenant (users, SMS history, API keys, balance) |
| Tenant Registration | Email-based registration with email verification |
| Tenant Login | JWT-based authentication with refresh tokens |
| Password Reset | Email-based password reset flow |
| Role-Based Access | Admin, Developer, Viewer roles within tenant |

### 2.2 API Key Management

| Feature | Description |
|---------|-------------|
| Create API Key | Generate unique API key pairs (key + secret) |
| List API Keys | View all active API keys with creation date |
| Revoke API Key | Permanently disable an API key |
| Rate Limiting | Per-key rate limiting (configurable per tier) |

### 2.3 SMS Sending

| Feature | Description |
|---------|-------------|
| Send Single SMS | POST /sms/send with phone, message, country code |
| Send Batch SMS | POST /sms/send-batch with array of recipients |
| Scheduled SMS | Schedule SMS delivery for future timestamp |
| Sender ID | Custom sender ID (alphabetic, 3-11 chars) |
| Message Template | Pre-approved message templates for verification codes |
| Unicode Support | Support for international character sets |
| Concatenation | Auto-split long messages into multiple segments |

### 2.4 Country & Operator Support

| Feature | Description |
|---------|-------------|
| Country Database | Pre-loaded list of 200+ countries with codes |
| Operator Detection | Auto-detect mobile operator based on number prefix |
| Pricing by Country | Per-country SMS pricing configuration |
| Blocked Countries | Admin ability to block specific countries |

### 2.5 SMS Gateway Integration

| Feature | Description |
|---------|-------------|
| Multi-Gateway Support | Twilio, Nexmo (Vonage), MessageBird, ClickSend |
| Gateway Fallback | Automatic failover when primary gateway fails |
| Gateway Selection | Route-based on destination country/operator |
| Gateway Health Check | Monitor gateway availability |

### 2.6 Delivery Tracking

| Feature | Description |
|---------|-------------|
| Real-time Status | Webhook callbacks for delivery status |
| Status Types | Queued, Sent, Delivered, Failed, Undelivered |
| Delivery Receipt | DLR (Delivery Receipt) processing |
| Retry Logic | Automatic retry on temporary failures |
| Failure Reason | Detailed failure reason codes |

### 2.7 Webhooks

| Feature | Description |
|---------|-------------|
| Configure Webhook | Set URL for delivery status callbacks |
| Webhook Events | SMS Sent, Delivered, Failed, Reply Received |
| Retry on Failure | 3 retry attempts with exponential backoff |
| Webhook Signature | HMAC signature for verification |

### 2.8 Analytics Dashboard

| Feature | Description |
|---------|-------------|
| SMS Volume | Daily/weekly/monthly SMS sent counts |
| Delivery Rate | Success rate percentage |
| Response Time | Average API response time |
| Cost Summary | Total spend by country/operator |
| Export Reports | CSV export of SMS history |

### 2.9 Account & Billing

| Feature | Description |
|---------|-------------|
| Balance Top-up | Manual credit purchase via payment gateway |
| Auto Recharge | Configurable auto top-up threshold |
| Transaction History | Detailed credit transaction log |
| Invoice Generation | Monthly invoices for billing |
| Pricing Tiers | Pay-as-you-go, Standard, Enterprise tiers |

### 2.10 Admin Panel

| Feature | Description |
|---------|-------------|
| Tenant Management | View, suspend, delete tenants |
| Gateway Management | Configure gateway credentials |
| Pricing Management | Set per-country prices |
| System Health | Gateway status, queue depth, error rates |
| Audit Log | All admin actions logged |

---

## 3. User Interactions and Flows

### 3.1 Registration Flow
```
1. User submits registration (email, password, company name)
2. System sends verification email
3. User clicks verification link
4. Account activated, redirected to dashboard
```

### 3.2 First SMS Flow
```
1. Developer creates API key
2. Developer calls /sms/send with API key
3. System validates key, checks balance
4. System routes to appropriate gateway
5. Gateway sends SMS
6. Webhook updates delivery status
7. Dashboard reflects status change
```

### 3.3 Batch SMS Flow
```
1. User uploads CSV or enters comma-separated numbers
2. User enters/selects message template
3. System validates all numbers (format + country)
4. System shows pricing preview
5. User confirms and submits
6. Batch queued for processing
7. Real-time progress shown in dashboard
```

---

## 4. Data Handling

### 4.1 Core Entities

| Entity | Description |
|--------|-------------|
| Tenant | Company/organization using the platform |
| User | Individual user within a tenant |
| ApiKey | API authentication credentials |
| SmsMessage | Individual SMS record |
| SmsBatch | Batch SMS job |
| Country | Country with pricing info |
| Gateway | SMS gateway configuration |
| WebhookConfig | Tenant webhook settings |
| Transaction | Credit transaction record |
| AuditLog | System audit entries |

### 4.2 Data Retention
- SMS messages: 90 days (configurable)
- API logs: 1 year
- Audit logs: 3 years
- Transaction records: 7 years (compliance)

### 4.3 PII Handling
- Phone numbers encrypted at rest (AES-256)
- SMS content encrypted at rest
- PII export restricted by role
- GDPR compliance features (right to deletion)

---

## 5. Edge Cases

| Scenario | Handling |
|----------|----------|
| Invalid phone number format | Return 400 with validation error |
| Insufficient balance | Reject with clear message, suggest top-up |
| Gateway timeout | Retry with fallback gateway, max 3 retries |
| Duplicate SMS detection | Hash-based deduplication within 5-minute window |
| Rate limit exceeded | Return 429 with retry-after header |
| Webhook URL unreachable | Queue and retry with backoff |
| Unsupported country | Return 400 with supported countries list |
| Message too long | Auto-split with concatenation (max 5 segments) |

---

## 6. Acceptance Criteria

### 6.1 Functional Criteria
- [ ] Tenant can register and verify email
- [ ] Tenant can create and manage API keys
- [ ] API can send single SMS to any supported country
- [ ] API can send batch SMS (up to 10,000 per batch)
- [ ] Delivery status webhook received within 60 seconds
- [ ] Dashboard shows accurate analytics
- [ ] Balance deducted only on successful send
- [ ] Gateway failover works within 10 seconds

### 6.2 Performance Criteria
- [ ] API response time < 200ms (p95)
- [ ] Support 1,000 concurrent SMS sends
- [ ] Dashboard loads < 2 seconds

### 6.3 Security Criteria
- [ ] All API calls require authentication
- [ ] API keys transmitted only via HTTPS
- [ ] Webhook payloads signed with HMAC
- [ ] No PII in logs (phone numbers masked)
- [ ] Rate limiting enforced per API key

### 6.4 Reliability Criteria
- [ ] 99.9% uptime SLA
- [ ] No SMS loss during gateway failover
- [ ] All failed SMS retried at least once
