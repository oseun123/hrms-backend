# Roles & Permissions API Documentation

This document covers the APIs for managing roles and assigning permissions within the HRMS.

## 1. Roles Management

### Get All Roles
**URL**: `GET /roles`
**Authentication**: Required (X-Tenant-Id header)
**Purpose**: Returns all roles for the current tenant.

### Create Role
**URL**: `POST /roles`
**Body**:
```json
{
  "name": "Department Supervisor",
  "description": "Manages a specific department",
  "permissions": [1, 2, 3]
}
```

### Update Role
**URL**: `PUT /roles/{role}`
**Body**: Similar to Create Role.

### Delete Role
**URL**: `DELETE /roles/{role}`
**Note**: Protected roles (Admin, Employee) cannot be deleted.

---

## 2. Permissions Management

### Get All Permissions
**URL**: `GET /permissions`
**Purpose**: Returns a list of all system-defined permissions.

---

## 3. User Role Assignment

### Sync User Roles
**URL**: `POST /users/{user}/sync-roles`
**Body**:
```json
{
  "roles": [1, 2]
}
```
**Purpose**: Sets the roles for a specific user. This will replace any existing roles assigned to the user.

---

## Default Roles
- **Admin**: Full access to all modules and settings.
- **Employee**: Default role for all staff members. Allows access to self-service features.
