# Preferences API Testing Guide

This document provides comprehensive testing instructions for the Preferences API endpoints.

## Table of Contents
- [Base URL](#base-url)
- [Authentication](#authentication)
- [Endpoints](#endpoints)
  - [1. Get All Preferences](#1-get-all-preferences)
  - [2. Get Preferences by Category](#2-get-preferences-by-category)
  - [3. Sync Preferences](#3-sync-preferences)
  - [4. Delete Preference](#4-delete-preference)
  - [5. Get My Activity History](#5-get-my-activity-history)
  - [6. Change Password](#6-change-password)
  - [7. Active Sessions Management](#7-active-sessions-management)
  - [8. Security & Authentication (2FA)](#8-security--authentication-2fa)
  - [9. Tenant Logo Management](#9-tenant-logo-management)
- [Common Preference Categories](#common-preference-categories)
- [Public Holidays Management](#public-holidays-management)
- [Tenant Theme Color](#tenant-theme-color)
- [Testing Workflow](#testing-workflow)

## Base URL
```
http://localhost:8000/api
```

## Authentication
All endpoints require authentication via Bearer token (Sanctum).

```
Authorization: Bearer {your_token_here}
```

---

## Endpoints

### 1. Get All Preferences

**Endpoint:** `GET /preferences`

**Description:** Retrieves all preferences for the current authenticated user and their tenant. User-specific preferences override tenant-wide preferences.

**Request:**
```http
GET /api/preferences HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "user_id": null,
      "category": "display",
      "key": "theme_color",
      "value": "geekblue",
      "created_at": "2025-12-24T10:00:00.000000Z",
      "updated_at": "2025-12-24T10:00:00.000000Z"
    },
    {
      "id": 2,
      "tenant_id": 1,
      "user_id": 5,
      "category": "language",
      "key": "date_format",
      "value": "DD/MM/YYYY",
      "created_at": "2025-12-24T10:05:00.000000Z",
      "updated_at": "2025-12-24T10:05:00.000000Z"
    }
  ]
}
```

---

### 2. Get Preferences by Category

**Endpoint:** `GET /preferences/category/{category}`

**Description:** Retrieves preferences filtered by a specific category (e.g., 'display', 'language', 'organization').

**Request:**
```http
GET /api/preferences/category/display HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "user_id": null,
      "category": "display",
      "key": "theme_color",
      "value": "geekblue",
      "created_at": "2025-12-24T10:00:00.000000Z",
      "updated_at": "2025-12-24T10:00:00.000000Z"
    }
  ]
}
```

---

### 3. Sync Preferences

**Endpoint:** `POST /preferences/sync`

**Description:** Creates or updates multiple preferences in a single request. Supports both tenant-wide and user-specific preferences.

**Request (Tenant-wide):**
```http
POST /api/preferences/sync HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
Content-Type: application/json

{
  "scope": "tenant",
  "preferences": [
    {
      "category": "display",
      "key": "theme_color",
      "value": "purple"
    },
    {
      "category": "language",
      "key": "date_format",
      "value": "YYYY-MM-DD"
    }
  ]
}
```

**Request (User-specific):**
```http
POST /api/preferences/sync HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
Content-Type: application/json

{
  "scope": "user",
  "preferences": [
    {
      "category": "display",
      "key": "theme_color",
      "value": "cyan"
    },
    {
      "category": "language",
      "key": "time_format",
      "value": "12"
    }
  ]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Preferences synced successfully",
  "data": [
    {
      "id": 3,
      "tenant_id": 1,
      "user_id": 5,
      "category": "display",
      "key": "theme_color",
      "value": "cyan",
      "created_at": "2025-12-24T11:00:00.000000Z",
      "updated_at": "2025-12-24T11:00:00.000000Z"
    },
    {
      "id": 4,
      "tenant_id": 1,
      "user_id": 5,
      "category": "language",
      "key": "time_format",
      "value": "12",
      "created_at": "2025-12-24T11:00:00.000000Z",
      "updated_at": "2025-12-24T11:00:00.000000Z"
    }
  ]
}
```

---

### 4. Delete Preference

**Endpoint:** `DELETE /preferences/{category}/{key}?scope={user|tenant}`

**Description:** Deletes a specific preference for the user or tenant.

**Request:**
```http
DELETE /api/preferences/organization/holiday_2025_12_25?scope=tenant HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Preference deleted successfully",
  "data": null
}
```

---

### 5. Search Available Admins

**Endpoint:** `GET /preferences/available-admins?search={query}`

**Description:** Searches for employees within the tenant who are not yet added as HR admins.

**Request:**
```http
GET /api/preferences/available-admins?search=John HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 101,
      "name": "John Doe",
      "email": "john.doe@example.com"
    }
  ]
}
```

---

### 6. Get My Activity History

**Endpoint:** `GET /preferences/my-activity-history`

**Description:** Retrieves the activity history (audit logs) for the current authenticated user.

**Request:**
```http
GET /api/preferences/my-activity-history HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": null,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 5,
        "auditable_type": "App\\Models\\Preference",
        "auditable_id": 10,
        "event": "updated",
        "old_values": { "value": "old_val" },
        "new_values": { "value": "new_val" },
        "ip_address": "127.0.0.1",
        "user_agent": "Mozilla/5.0...",
        "created_at": "2025-12-24T12:00:00.000000Z"
      }
    ],
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

**Validation Errors (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "scope": ["The scope field is required."],
    "preferences.0.category": ["The preferences.0.category field is required."]
  }
}
```

---

### 6. Change Password

**Endpoint:** `POST /preferences/security/change-password`

**Description:** Allows the authenticated user to change their password.

**Request:**
```http
POST /api/preferences/security/change-password HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
Content-Type: application/json

{
  "current_password": "OldPassword123!",
  "new_password": "NewSecurePassword456!",
  "confirm_password": "NewSecurePassword456!"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Password changed successfully",
  "data": null
}
```

---

### 7. Active Sessions Management

#### 7.1 Get Active Sessions

**Endpoint:** `GET /preferences/security/sessions`

**Description:** Retrieves all active sessions (tokens) for the current user, including device info and IP.

**Request:**
```http
GET /api/preferences/security/sessions HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "device": "auth-token",
      "ip_address": "127.0.0.1",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...",
      "last_active": "2025-12-25 09:15:00",
      "is_current": true
    }
  ]
}
```

#### 7.2 Revoke Session

**Endpoint:** `DELETE /preferences/security/sessions/{id}`

**Description:** Revokes a specific session by its token ID.

**Request:**
```http
DELETE /api/preferences/security/sessions/13 HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Session revoked successfully",
  "data": null
}
```

---

### 8. Security & Authentication (2FA)

#### 6.1 User Login (Updated with 2FA support)

**Endpoint:** `POST /auth/login`

**Description:** Standard login endpoint. If 2FA is enabled for the user or enforced by the tenant, it returns a challenge instead of an auth token.

**Response (2FA Required - 200 OK):**
```json
{
  "success": true,
  "message": "Verification code sent to your email",
  "data": {
    "two_factor_required": true,
    "email": "user@example.com"
  }
}
```

#### 6.2 Verify 2FA Code

**Endpoint:** `POST /auth/login/verify-2fa`

**Description:** Verifies the 6-digit code sent to the user's email.

**Request:**
```http
POST /api/auth/login/verify-2fa HTTP/1.1
Host: localhost:8000
Content-Type: application/json

{
  "email": "user@example.com",
  "code": "123456",
  "tenant_id": 1
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "2FA verification successful",
  "data": {
    "user": { ... },
    "tenant": { ... },
    "token": "..."
  }
}
```

#### 6.3 Update Security Settings

**Endpoint:** `PUT /auth/security-settings`

**Description:** Allows the authenticated user to toggle their own 2FA status.

**Request:**
```http
PUT /api/auth/security-settings HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
Content-Type: application/json

{
  "two_factor_enabled": true
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Security settings updated successfully",
  "data": {
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com",
    "two_factor_enabled": true
  }
}
```

#### 6.4 Get Current User (Updated)

**Endpoint:** `GET /auth/me`

**Description:** Returns the current user's profile, including their 2FA status.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com",
    "two_factor_enabled": true,
    "employee": { ... }
  }
}
```

---

### 9. Tenant Logo Management

#### 9.1 Upload Tenant Logo

**Endpoint:** `POST /tenant/logo`

**Description:** Uploads a company logo file and updates the `logo_url` organization preference.

**Payload:** `multipart/form-data`
- `logo`: File (image, max 2MB)

**Request:**
```http
POST /api/tenant/logo HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
Content-Type: multipart/form-data

--boundary
Content-Disposition: form-data; name="logo"; filename="logo.png"
Content-Type: image/png

<file content>
--boundary--
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logo uploaded successfully",
  "data": {
    "url": "http://localhost:8000/storage/tenant-logos/abcdef.png",
    "path": "tenant-logos/abcdef.png"
  }
}
```

#### 9.2 Delete Tenant Logo

**Endpoint:** `DELETE /tenant/logo`

**Description:** Deletes the tenant logo file and clears the `logo_url` preference.

**Request:**
```http
DELETE /api/tenant/logo HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logo deleted successfully",
  "data": null
}
```

---

## Common Preference Categories

### Display
- `theme_color` - Application theme color (e.g., 'geekblue', 'purple', 'cyan')

### Language
- `date_format` - Date display format (e.g., 'DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD')
- `time_format` - Time display format (e.g., '12', '24')
- `timezone` - User timezone (e.g., 'Africa/Lagos', 'America/New_York')
- `currency` - Preferred currency (e.g., 'NGN', 'USD', 'EUR')

### Organization
- `registered_address` - Company registered address
- `phone` - Company phone number
- `email` - Company email
- `website` - Company website
- `hr_email` - HR department email
- `support_email` - Employee support email
- `holiday_YYYY_MM_DD` - Public holiday data (JSON with name, date, type)

### Privacy (Scope: user)
- `show_mobile_phone` - (boolean)
- `show_personal_email` - (boolean)
- `show_birthday` - (boolean)

### Security Policy (Scope: tenant)
- `enforce_2fa` - (boolean) Force all employees to use 2FA
- `session_timeout` - (number, minutes) Auto-logout after inactivity
- `password_expiry_days` - (number, days) Force password change after X days (0 = disabled)
- `min_password_length` - (number) Minimum password length requirement

**Testing Security Policies:**

1. **Set Security Policies:**
```http
POST /api/preferences/sync HTTP/1.1
Host: localhost:8000
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "scope": "tenant",
  "preferences": [
    {
      "category": "security_policy",
      "key": "enforce_2fa",
      "value": true
    },
    {
      "category": "security_policy",
      "key": "session_timeout",
      "value": 30
    },
    {
      "category": "security_policy",
      "key": "password_expiry_days",
      "value": 90
    },
    {
      "category": "security_policy",
      "key": "min_password_length",
      "value": 12
    }
  ]
}
```

2. **Test Password Expiry:**
   - Set `password_expiry_days` to 1
   - Manually update a user's `password_changed_at` to 2 days ago in database
   - Try to login - should receive password expired response:
```json
{
  "success": false,
  "message": "Password expired",
  "data": {
    "password_expired": true,
    "email": "user@example.com",
    "tenant_id": 1,
    "days_overdue": 1
  }
}
```

3. **Test Session Timeout:**
   - Set `session_timeout` to 1 minute
   - Login and remain inactive for 1 minute
   - Frontend should show warning modal and auto-logout

4. **Test 2FA Enforcement:**
   - Set `enforce_2fa` to `true`
   - User's 2FA toggle in Privacy & Security should be:
     - Checked (forced on)
     - Disabled (cannot be toggled off)
     - Show lock icon with tooltip "Required by organization policy"
   - Login should require 2FA code even if user hasn't enabled it

---

## Public Holidays Management

Public holidays are stored as preferences with:
- **Category:** `organization`
- **Key:** `holiday_{date}` (e.g., `holiday_2025_01_01`)
- **Value:** JSON object with `name`, `date`, and `type`

**Example Holiday Preference:**
```json
{
  "category": "organization",
  "key": "holiday_2025_12_25",
  "value": {
    "name": "Christmas Day",
    "date": "2025-12-25",
    "type": "National"
  }
}
```

---

## Tenant Theme Color

The tenant's theme color is automatically included in the public tenant lookup endpoint:

**Endpoint:** `GET /tenants/{slug}`

**Response:**
```json
{
  "success": true,
  "message": null,
  "data": {
    "id": 1,
    "name": "Acme Corporation",
    "slug": "acme",
    "domain": "acme.example.com",
    "theme_color": "geekblue"
  }
}
```

Default theme color is **geekblue** if not explicitly set.

---

## Approval Settings API

### Get Approval Settings
**URL**: `GET /api/preferences/approval-settings`

**Description**: Get approval requirements for all sections (HR only)

**Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "section": "contact_details",
      "requires_approval": true
    },
    {
      "section": "addresses",
      "requires_approval": false
    }
  ]
}
```

### Update Approval Settings
**URL**: `PUT /api/preferences/approval-settings`

**Description**: Update approval requirements (HR only)

**Request Body**:
```json
{
  "settings": [
    {
      "section": "contact_details",
      "requires_approval": true
    },
    {
      "section": "financial",
      "requires_approval": true
    }
  ]
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Approval settings updated successfully"
}
```

---

## HR Approval Queue API

### Get Approval Queue
**URL**: `GET /api/hris/hr/approval-queue?section=contact_details&page=1&per_page=15`

**Description**: Get all pending approval requests (HR only)

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "employee": {
          "id": 5,
          "employee_number": "EMP001",
          "full_name": "John Doe",
          "photo": "https://..."
        },
        "section": "contact_details",
        "submitted_at": "2025-12-27T14:30:00Z",
        "notes": "Changed phone number"
      }
    ],
    "total": 1
  }
}
```

### Get Request Details
**URL**: `GET /api/hris/hr/approval-queue/{id}`

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "employee": {
      "id": 5,
      "employee_number": "EMP001",
      "full_name": "John Doe"
    },
    "section": "contact_details",
    "current_data": {
      "mobile_phone": "+234 801 234 5678",
      "personal_email": "old@email.com"
    },
    "proposed_data": {
      "mobile_phone": "+234 802 345 6789",
      "personal_email": "new@email.com"
    },
    "notes": "Changed phone number due to new SIM",
    "submitted_at": "2025-12-27T14:30:00Z"
  }
}
```

### Approve Request
**URL**: `POST /api/hris/hr/approval-queue/{id}/approve`

**Response (200)**:
```json
{
  "success": true,
  "message": "Request approved and changes applied"
}
```

### Decline Request
**URL**: `POST /api/hris/hr/approval-queue/{id}/decline`

**Request Body**:
```json
{
  "decline_reason": "Insufficient documentation provided"
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Request declined"
}
```

### Get Incorrect Detail Reports
**URL**: `GET /api/hris/hr/incorrect-detail-reports?status=pending`

**Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee": {
        "id": 5,
        "full_name": "John Doe"
      },
      "section": "personal",
      "field_name": "date_of_birth",
      "current_value": "1990-01-01",
      "reported_correct_value": "1990-01-15",
      "description": "Actual birth date is January 15th",
      "status": "pending",
      "created_at": "2025-12-27T14:00:00Z"
    }
  ]
}
```

### Resolve Report
**URL**: `PATCH /api/hris/hr/incorrect-detail-reports/{id}/resolve`

**Request Body**:
```json
{
  "resolution_notes": "Updated date of birth in system"
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Report marked as resolved"
}
```

---

## Testing Workflow

1. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

2. **Seed Default Preferences:**
   ```bash
   php artisan db:seed --class=DefaultPreferencesSeeder
   php artisan db:seed --class=PublicHolidaySeeder
   php artisan db:seed --class=DefaultSecurityPoliciesSeeder
   ```

3. **Test Endpoints:**
   - Get all preferences
   - Filter by category
   - Sync tenant-wide preferences
   - Sync user-specific preferences
   - Verify user preferences override tenant preferences

4. **Verify Theme Color:**
   - Check tenant endpoint returns theme color
   - Update theme via preferences sync
   - Confirm change reflects in tenant endpoint

5. **Verify Security Policies:**
   - Check default policies are seeded (session_timeout: 15, password_expiry_days: 0)
   - Update policies via preferences sync
   - Test password expiry, session timeout, and 2FA enforcement
