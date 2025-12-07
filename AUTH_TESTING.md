# Authentication Testing Script

## 🔐 Authentication System Status

**Authentication Method:** Laravel Sanctum (Token-based)  
**Status:** ✅ Fully Functional  
**Public Registration:** ❌ Disabled (Employees created via HRIS)

---

## Prerequisites

```bash
# Make sure server is running
php artisan serve

# Ensure Sanctum is installed and migrated
php artisan migrate
```

---

## Test 1: Login with Admin User ✅

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@hrms.local","password":"password","tenant_id":1}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@hrms.local",
      "tenant_id": 1,
      "email_verified_at": "2025-11-22T...",
      "created_at": "2025-11-22T...",
      "updated_at": "2025-11-22T...",
      "tenant": {
        "id": 1,
        "name": "Default Tenant",
        "slug": "default"
      }
    },
    "tenant": {
      "id": 1,
      "name": "Default Tenant",
      "slug": "default",
      "is_active": true
    },
    "token": "1|abc123xyz..."
  }
}
```

**Save the token for subsequent requests!**

---

## Test 2: Login with Employee User ✅

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john.doe@hrms.local","password":"password","tenant_id":1}'
```

---

## Test 3: Get Authenticated User (Me) ✅

**Replace `YOUR_TOKEN` with the token from login response**

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@hrms.local",
    ...
  }
}
```

---

## Test 4: Register New User ❌ DISABLED

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Public registration is disabled. Please contact your administrator to create an employee account."
}
```

**Note:** New users/employees should be created through the HRIS system at:
```bash
POST /api/hris/employees
```

---

## Test 5: Access Protected Endpoint (Test) ✅

```bash
curl -X GET http://localhost:8000/api/test \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Protected endpoint works!",
  "user": {...}
}
```

---

## Test 6: Access Protected Endpoint Without Token ✅

```bash
curl -X GET http://localhost:8000/api/test
```

**Expected Response:**
```json
{
  "message": "Unauthenticated."
}
```

---

## Test 7: Logout ✅

```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## Test 8: Try Using Token After Logout ✅

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected:** Should return "Unauthenticated" error

---

## Test 9: Invalid Credentials ✅

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@hrms.local","password":"wrongpassword","tenant_id":1}'
```

**Expected Response:**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

---

## Test 10: Password Reset Request ✅

```bash
curl -X POST http://localhost:8000/api/auth/password/reset-request \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@hrms.local"}'
```

---

## Quick Test Script (PowerShell)

Save this as `test-auth.ps1`:

```powershell
# Test Login
Write-Host "Testing Login..." -ForegroundColor Yellow
$response = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" `
    -Method Post `
    -ContentType "application/json" `
    -Body '{"email":"admin@hrms.local","password":"password","tenant_id":1}'

if ($response.success) {
    Write-Host "✓ Login successful!" -ForegroundColor Green
    $token = $response.data.token
    Write-Host "Token: $token" -ForegroundColor Cyan
    
    # Test Me Endpoint
    Write-Host "`nTesting /me endpoint..." -ForegroundColor Yellow
    $meResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/me" `
        -Method Get `
        -Headers @{"Authorization" = "Bearer $token"}
    
    if ($meResponse.success) {
        Write-Host "✓ Me endpoint works!" -ForegroundColor Green
        Write-Host "User: $($meResponse.data.name)" -ForegroundColor Cyan
    }
    
    # Test Protected Endpoint
    Write-Host "`nTesting protected endpoint..." -ForegroundColor Yellow
    try {
        $testResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/test" `
            -Method Get `
            -Headers @{"Authorization" = "Bearer $token"}
        Write-Host "✓ Protected endpoint accessible!" -ForegroundColor Green
    } catch {
        Write-Host "✗ Protected endpoint failed" -ForegroundColor Red
    }
    
    # Test Logout
    Write-Host "`nTesting logout..." -ForegroundColor Yellow
    $logoutResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/logout" `
        -Method Post `
        -Headers @{"Authorization" = "Bearer $token"}
    
    if ($logoutResponse.success) {
        Write-Host "✓ Logout successful!" -ForegroundColor Green
    }
} else {
    Write-Host "✗ Login failed!" -ForegroundColor Red
}
```

Run with: `.\test-auth.ps1`

---

## Using Postman

### 1. Import Collection

Create new collection "HRMS API" with environment variables:
- `base_url` = `http://localhost:8000`
- `token` (will be set automatically)

### 2. Login Request

- **Method:** POST
- **URL:** `{{base_url}}/api/auth/login`
- **Body (JSON):**
  ```json
  {
    "email": "admin@hrms.local",
    "password": "password",
    "tenant_id": 1
  }
  ```
- **Tests (to save token):**
  ```javascript
  pm.environment.set("token", pm.response.json().data.token);
  ```

### 3. Protected Requests

Add header: `Authorization: Bearer {{token}}`

---

## 📋 Available Auth Endpoints

| Method | Endpoint | Auth Required | Status |
|--------|----------|---------------|--------|
| POST | `/api/auth/login` | No | ✅ Working |
| POST | `/api/auth/register` | No | ❌ Disabled |
| POST | `/api/auth/logout` | Yes | ✅ Working |
| GET | `/api/auth/me` | Yes | ✅ Working |
| POST | `/api/auth/password/reset-request` | No | ✅ Working |
| POST | `/api/auth/password/reset` | No | ✅ Working |

---

## 🔒 Security Notes

### Public Registration Disabled
- ✅ Public registration endpoint returns 403 error
- ✅ New employees must be created through HRIS system
- ✅ Only administrators can create employee accounts
- ✅ Prevents unauthorized account creation

### How to Create New Employees

**Step 1: Create User Account (if needed)**
```bash
# Create user via database or tinker
php artisan tinker
User::create([
    'name' => 'New Employee',
    'email' => 'new.employee@company.com',
    'password' => Hash::make('password'),
]);
```

**Step 2: Create Employee Record**
```bash
curl -X POST http://localhost:8000/api/hris/employees \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "user_id": 3,
    "employee_number": "STAFF/2025/003",
    "first_name": "New",
    "last_name": "Employee",
    "is_active": true
  }'
```

---

## ✅ Verification Checklist

- [x] Login with admin user works
- [x] Login with employee user works
- [x] Invalid credentials return error
- [x] Token is generated on successful login
- [x] `/me` endpoint returns user data
- [x] Protected endpoints require token
- [x] Protected endpoints work with valid token
- [x] Logout revokes token
- [x] Token doesn't work after logout
- [x] Register endpoint is disabled ✨ **NEW**
- [x] Password reset request works

---

## 🎯 Summary

**Authentication Status:** ✅ Fully Functional

**Working Features:**
- ✅ Login with email/password
- ✅ Token-based authentication
- ✅ Protected routes
- ✅ Logout functionality
- ✅ Password reset
- ✅ Public registration disabled for security

**Security Improvements:**
- ✅ Registration disabled
- ✅ Employees created through HRIS
- ✅ Admin-controlled user creation
- ✅ CORS configured
- ✅ Sanctum middleware active

**Status:** Ready for production use! 🚀
