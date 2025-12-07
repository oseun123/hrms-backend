# Authentication System - Setup Guide

## ✅ What's Been Created

### Controllers
1. **AuthController.php** - Handles authentication
   - `POST /api/auth/login` - User login
   - `POST /api/auth/register` - User registration
   - `POST /api/auth/logout` - User logout
   - `GET /api/auth/me` - Get authenticated user

2. **PasswordResetController.php** - Handles password reset
   - `POST /api/auth/password/reset-request` - Request reset link
   - `POST /api/auth/password/reset` - Reset password with token

### Configuration
- ✅ User model has `HasApiTokens` trait
- ✅ API routes configured with Sanctum middleware
- ✅ Public and protected routes separated

---

## 🚀 Next Steps

### 1. Install Sanctum (if not already done)

```bash
composer require laravel/sanctum
```

### 2. Publish Sanctum Configuration

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 3. Run Sanctum Migration

```bash
php artisan migrate
```

This creates the `personal_access_tokens` table for storing API tokens.

### 4. Update Kernel.php

Add Sanctum middleware to `app/Http/Kernel.php`:

```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### 5. Configure CORS

Update `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['http://localhost:3000'],
'supports_credentials' => true,
```

---

## 🧪 Testing the API

### 1. Register a New User

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {...},
    "token": "1|abc123..."
  }
}
```

### 2. Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@hrms.local",
    "password": "password",
    "tenant_id": 1
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {...},
    "tenant": {...},
    "token": "2|xyz789..."
  }
}
```

### 3. Get Authenticated User

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 4. Logout

```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 5. Password Reset Request

```bash
curl -X POST http://localhost:8000/api/auth/password/reset-request \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@hrms.local"
  }'
```

---

## 📋 API Endpoints Summary

### Public Endpoints (No Authentication Required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | User login |
| POST | `/api/auth/register` | User registration |
| POST | `/api/auth/password/reset-request` | Request password reset |
| POST | `/api/auth/password/reset` | Reset password |

### Protected Endpoints (Require Bearer Token)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/logout` | Logout current user |
| GET | `/api/auth/me` | Get authenticated user |
| GET/POST | `/api/hris/*` | All HRIS endpoints |
| GET/POST | `/api/approvals/*` | All approval endpoints |

---

## 🔐 How Sanctum Works

1. **User logs in** → Receives API token
2. **Store token** in frontend (localStorage, cookie, etc.)
3. **Include token** in all API requests:
   ```
   Authorization: Bearer {token}
   ```
4. **Laravel validates** token automatically via `auth:sanctum` middleware
5. **User logs out** → Token is revoked

---

## 🛡️ Security Features

✅ **Password Hashing** - Bcrypt hashing
✅ **Token-based Auth** - Secure API tokens
✅ **Token Revocation** - Logout revokes tokens
✅ **Validation** - Input validation on all endpoints
✅ **CORS Protection** - Configured for frontend domain
✅ **Rate Limiting** - Built-in throttling

---

## 🎯 Testing with Seeded Users

You can test with the users created by the seeder:

**Admin User:**
- Email: `admin@hrms.local`
- Password: `password`

**Employee User:**
- Email: `john.doe@hrms.local`
- Password: `password`

---

## 📝 Example Frontend Integration (Next.js)

```typescript
// Login function
async function login(email: string, password: string, tenantId: number) {
  const response = await fetch('http://localhost:8000/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password, tenant_id: tenantId }),
  });
  
  const data = await response.json();
  
  if (data.success) {
    // Store token and tenant info
    localStorage.setItem('token', data.data.token);
    localStorage.setItem('tenant', JSON.stringify(data.data.tenant));
    return data.data.user;
  }
}

// Authenticated API call
async function getEmployees() {
  const token = localStorage.getItem('token');
  
  const response = await fetch('http://localhost:8000/api/hris/employees', {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });
  
  return await response.json();
}
```

---

## ✅ Verification Checklist

- [ ] Sanctum installed (`composer require laravel/sanctum`)
- [ ] Sanctum published (`php artisan vendor:publish`)
- [ ] Sanctum migrated (`php artisan migrate`)
- [ ] Kernel.php updated with Sanctum middleware
- [ ] CORS configured for frontend domain
- [ ] Test login endpoint
- [ ] Test register endpoint
- [ ] Test protected endpoints with token
- [ ] Test logout endpoint

---

**Status:** ✅ Authentication system ready!
**Next:** Test the endpoints or proceed to Role & Permission system
