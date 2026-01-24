# HRIS API - Complete Testing Reference

> [!IMPORTANT]
> **Multi-Tenant Authentication Update**: As of December 2025, `tenant_id` is NO LONGER required in request bodies. It's automatically injected from your authentication token. Remove `"tenant_id": 1,` from all examples below. See `api_changes.md` for details.

Base URL: `http://localhost:8000/api/hris`

**Authentication**: All requests require Bearer token in header:
```
Authorization: Bearer {your-token-here}
```

---

## Table of Contents
0. [Tenant Lookup (Public)](#tenant-lookup-public) 🆕
1. [Getting Started](#getting-started)
2. [Dashboard API](#dashboard-api) 🆕
    - [Analytics API](#analytics-api) 🆕
3. [Departments API](#departments-api)
4. [Levels API](#levels-api)
5. [Grades API](#grades-api)
6. [Positions API](#positions-api)
23. [Reports API](#reports-api) 🆕
7. [Employees API](#employees-api)
8. [Bulk Employee Upload API](#bulk-employee-upload-api) 🆕
9. [Financial Details API](#financial-details-api)
9. [Medical Details API](#medical-details-api)
10. [Addresses API](#addresses-api)
11. [Emergency Contacts API](#emergency-contacts-api)
12. [Education API](#education-api)
13. [Dependents API](#dependents-api)
14. [Skills API](#skills-api)
15. [Documents API](#documents-api)
16. [Work Experience API](#work-experience-api)
17. [Certifications API](#certifications-api)
18. [Employee History API](#employee-history-api)
19. [Profile Completeness API](#profile-completeness-api)
20. [Audit Logs API](#audit-logs-api)
21. [Notifications API](#notifications-api)
22. [Testing Checklist](#testing-checklist)
23. [Common Test Scenarios](#common-test-scenarios)

---

## Tenant Lookup (Public)

> [!NOTE]
> This endpoint is **public** (no authentication required) and is used to lookup tenant information before logging in.

### Get Tenant by Slug
**URL**: `GET /tenants/{slug}`

**Authentication**: None required ✅

**Purpose**: Lookup tenant ID by slug for use in login flow

**Example Request**:
```bash
curl http://localhost:8000/api/tenants/default
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Default Tenant",
    "slug": "default",
    "domain": null
  }
}
```

**Error Response** (404):
```json
{
  "success": false,
  "message": "Tenant not found or inactive"
}
```

**Usage in Login Flow**:
```bash
# Step 1: Get tenant ID by slug
TENANT_RESPONSE=$(curl http://localhost:8000/api/tenants/acme-corp)
TENANT_ID=$(echo $TENANT_RESPONSE | jq -r '.data.id')

# Step 2: Login with tenant ID
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"user@example.com\",\"password\":\"password\",\"tenant_id\":$TENANT_ID}"
```

---

## Getting Started

### Prerequisites
```bash
# 1. Start the server
php artisan serve

# 2. Get authentication token (with tenant_id)
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@hrms.local","password":"password","tenant_id":1}'

# 3. Save the token
TOKEN="your_token_here"
```

### Test Data Available
After running `php artisan db:seed`, you'll have:
- **Users**: 2 (Admin, Employee)
- **Departments**: 4 (IT, HR, Finance, Development)
- **Levels**: 4 (Junior, Mid, Senior, Lead)
- **Grades**: 3 (G1, G2, G3)
- **Positions**: 2 (Software Developer, HR Manager)
- **Employees**: 1 (John Doe)

---

---

## Dashboard API 🆕

### 1. Get Dashboard Summary
**URL**: `GET /dashboard/summary`

**Authentication**: Required ✅

**Purpose**: Get consolidated data for dashboard cards (Welcome, My Profile, Profile Completeness, Stats)

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/dashboard/summary" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "employee": {
      "id": 1,
      "employee_number": "STAFF/2024/001",
      "full_name": "John Doe",
      "employment_details": {
        "department": { "name": "Information Technology" },
        "position": { "title": "Software Developer" }
      }
    },
    "last_login": "2024-12-29T10:00:00.000000Z",
    "profile_completeness": 85.5,
    "stats": {
      "team_members": 12,
      "direct_reports": 5
    }
  }
}
```

### 2. Get Team Members
**URL**: `GET /dashboard/team`

**Purpose**: Get all employees in the same department as the logged-in user (excluding self).

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/dashboard/team" \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Get Direct Reports
**URL**: `GET /dashboard/downlines`

**Purpose**: Get all employees who report directly to the logged-in user.

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/dashboard/downlines" \
  -H "Authorization: Bearer $TOKEN"
```

---

### 4. Get Notifications
**URL**: `GET /dashboard/notifications`

**Purpose**: Get user's 7 most recent notifications.

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/dashboard/notifications" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": "9a5e3c8d-...",
      "type": "welcome",
      "title": "Welcome to HRMS",
      "message": "Your account has been created...",
      "timestamp": "2024-12-29T10:00:00.000000Z",
      "read": false,
      "action_url": "/dashboard",
      "action_text": "Go to Dashboard"
    }
  ]
}
```

### 5. Get Unread Count
**URL**: `GET /dashboard/notifications/unread-count`

**Purpose**: Get the total number of unread notifications for the user.

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/dashboard/notifications/unread-count" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "count": 5
  }
}
```

### 6. Mark Notification as Read
**URL**: `PATCH /dashboard/notifications/{id}/read`

**Purpose**: Mark a specific notification as read.

**Example Request**:
```bash
curl -X PATCH "http://localhost:8000/api/hris/dashboard/notifications/9a5e3c8d-.../read" \
  -H "Authorization: Bearer $TOKEN"
```

### 7. Mark All Notifications as Read
**URL**: `PATCH /dashboard/notifications/read-all`

**Purpose**: Mark all unread notifications as read.

**Example Request**:
```bash
curl -X PATCH "http://localhost:8000/api/hris/dashboard/notifications/read-all" \
  -H "Authorization: Bearer $TOKEN"
```

---

### 8. Get Employees on Probation
**URL**: `GET /dashboard/employees/on-probation`

**Purpose**: Get list of employees whose probation period ends in the future or today.

**Data Includes**:
- `days_remaining`: Number of days until probation ends
- `probation_end_date`: Exact date probation ends
- `is_today`: Boolean flag if probation ends today (for animations)

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/dashboard/employees/on-probation" \
  -H "Authorization: Bearer $TOKEN"
```

### 9. Get Birthdays This Month
**URL**: `GET /dashboard/employees/birthdays-this-month`

**Purpose**: Get employees celebrating birthdays in the current month.

**Data Includes**:
- `age`: Age the employee is turning
- `birth_date`: Employee's birth date
- `is_today`: Boolean flag if birthday is today (for animations)

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/dashboard/employees/birthdays-this-month" \
  -H "Authorization: Bearer $TOKEN"
```

### 10. Get Work Anniversaries This Month
**URL**: `GET /dashboard/employees/anniversaries-this-month`

**Purpose**: Get employees celebrating work anniversaries in the current month.

**Data Includes**:
- `years_of_service`: Total years worked
- `hire_date`: Original hire date
- `is_today`: Boolean flag if anniversary is today (for animations)

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/dashboard/employees/anniversaries-this-month" \
  -H "Authorization: Bearer $TOKEN"
```
### 11. Get Dashboard Analytics 🆕
**URL**: `GET /dashboard/analytics`

**Purpose**: Get consolidated demographic and distribution data for the dashboard. Note: All data is filtered by `is_active: true` by default for demographics.

**Data Includes (Phase 1 - Integrated)**:
- `department_distribution`: Count of active employees per department (includes `id`).
- `gender_ratio`: Breakdown of active employees by gender.
- `age_demographics`: Age group distribution and average age.
- `total_active_employees`: Total count of active staff.
- `diversity`: Nationality and Education (highest degree) distribution.
- `headcount_trend`: 12-month historical growth.
- `retention_rate`: Attrition/Retention performance metrics.
- `terminated_ytd`: Total terminations in the current year.
- `average_tenure`: Average years of service.
- `skills_distribution`: Organization-wide skills mapping (includes skill `id`).

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/dashboard/analytics" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "department_distribution": [
      { "id": 1, "name": "IT", "value": 15 },
      { "id": 2, "name": "HR", "value": 4 }
    ],
    "gender_ratio": [
      { "name": "Male", "value": 10 },
      { "name": "Female", "value": 9 }
    ],
    "age_demographics": [
      { "range": "18-25", "count": 2 },
      { "range": "26-35", "count": 8 }
    ],
    "average_age": 32,
    "total_active_employees": 19,
    "total_inactive_employees": 2,
    "diversity": {
      "nationality": [
        { "label": "Nigerian", "count": 15 },
        { "label": "Others", "count": 4 }
      ],
      "education": [
        { "label": "B.Sc", "count": 12 },
        { "label": "M.Sc", "count": 7 }
      ]
    },
    "headcount_trend": [
      { "month": "Jan", "count": 15 },
      { "month": "Feb", "count": 17 }
    ],
    "average_tenure": 2.5,
    "retention_rate": 95,
    "terminated_ytd": 1,
    "skills_distribution": [
      { "id": 1, "subject": "React", "A": 4.5, "fullMark": 5 },
      { "id": 2, "subject": "PHP", "A": 3.8, "fullMark": 5 }
    ]
  }
}
```
---

### 12. Get Daily Motivational Quote 🆕
**URL**: `GET /dashboard/daily-quote`

**Purpose**: Get a motivational quote for the current day of the month.

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/dashboard/daily-quote" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "quote": "Success is not final; failure is not fatal: it is the courage to continue that counts.",
    "author": "Winston Churchill"
  }
}
```

---


---

## Departments API

### 1. Get All Departments
**URL**: `GET /departments`

**Query Parameters**:
- `search` (optional): Search in code, name, description
- `is_active` (optional): Filter by active status (true/false)
- `parent_id` (optional): Filter by parent department
- `per_page` (optional): Items per page (default: 15)

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/departments" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "parent_id": null,
      "code": "IT",
      "name": "Information Technology",
      "description": "IT Department",
      "manager_id": null,
      "cost_center": null,
      "location": null,
      "email": null,
      "phone": null,
      "is_active": true,
      "created_at": "2025-11-22T15:43:08.000000Z",
      "parent": null,
      "children": [
        {
          "id": 4,
          "code": "IT-DEV",
          "name": "Development"
        }
      ],
      "manager": null
    }
  ]
}
```

**Example Response (Paginated)**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Information Technology",
        "code": "IT",
        "employees_count": 12
      }
    ],
    "per_page": 15,
    "total": 45
  }
}
```

### 2. Get Single Department
**URL**: `GET /departments/{id}`

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/departments/1" \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Create Department
**URL**: `POST /departments`

**Body**:
```json
{
  "tenant_id": 1,
  "code": "SALES",
  "name": "Sales Department",
  "description": "Sales and Marketing Team",
  "parent_id": null,
  "manager_id": null,
  "cost_center": "CC-SALES-001",
  "location": "Building A, Floor 2",
  "email": "sales@company.com",
  "phone": "+1234567890",
  "is_active": true
}
```

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/hris/departments" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "code": "SALES",
    "name": "Sales Department",
    "description": "Sales and Marketing",
    "is_active": true
  }'
```

### 4. Update Department
**URL**: `PUT /departments/{id}`

**Example Request**:
```bash
curl -X PUT "http://localhost:8000/api/hris/departments/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "IT",
    "name": "Information Technology - Updated",
    "is_active": true
  }'
```

### 5. Delete Department
**URL**: `DELETE /departments/{id}`

**Note**: Cannot delete if department has:
- Child departments
- Employees assigned

**Example Request**:
```bash
curl -X DELETE "http://localhost:8000/api/hris/departments/5" \
  -H "Authorization: Bearer $TOKEN"
```

---

## Levels API

### 1. Get All Levels
**URL**: `GET /levels`

**Query Parameters**:
- `search` (optional): Search in code, name, description
- `is_active` (optional): Filter by active status
- `per_page` (optional): Items per page (default: 15)

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/levels" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response (Paginated)**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Executive",
        "code": "EXEC",
        "rank": 5
      }
    ],
    "per_page": 15,
    "total": 8
  }
}
```

### 2. Create Level
**URL**: `POST /levels`

**Body**:
```json
{
  "tenant_id": 1,
  "code": "EXEC",
  "name": "Executive",
  "description": "Executive Level",
  "rank": 5,
  "is_active": true
}
```

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/hris/levels" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "code": "EXEC",
    "name": "Executive",
    "description": "Executive Level",
    "rank": 5,
    "is_active": true
  }'
```

### 3. Update Level
**URL**: `PUT /levels/{id}`

### 4. Delete Level
**URL**: `DELETE /levels/{id}`

**Note**: Cannot delete if level is assigned to positions

---

## Grades API

### 1. Get All Grades
**URL**: `GET /grades`

**Query Parameters**:
- `search` (optional): Search in code, name, description
- `is_active` (optional): Filter by active status
- `per_page` (optional): Items per page (default: 15)

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/grades" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response (Paginated)**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Grade 4",
        "code": "G4",
        "min_salary": 120000,
        "max_salary": 200000
      }
    ],
    "per_page": 15,
    "total": 12
  }
}
```

### 2. Create Grade
**URL**: `POST /grades`

**Body**:
```json
{
  "tenant_id": 1,
  "code": "G4",
  "name": "Grade 4",
  "description": "Executive Grade",
  "min_salary": 120000,
  "max_salary": 200000,
  "rank": 4,
  "is_active": true
}
```

**Validation Rules**:
- `max_salary` must be greater than or equal to `min_salary`

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/hris/grades" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "code": "G4",
    "name": "Grade 4",
    "description": "Executive Grade",
    "min_salary": 120000,
    "max_salary": 200000,
    "rank": 4,
    "is_active": true
  }'
```

### 3. Update Grade
**URL**: `PUT /grades/{id}`

### 4. Delete Grade
**URL**: `DELETE /grades/{id}`

**Note**: Cannot delete if grade is assigned to positions

---

## Positions API

### 1. Get All Positions
**URL**: `GET /positions`

**Query Parameters**:
- `search` (optional): Search in code, title, description
- `department_id` (optional): Filter by department
- `level_id` (optional): Filter by level
- `grade_id` (optional): Filter by grade
- `is_active` (optional): Filter by active status
- `per_page` (optional): Items per page (default: 15)

**Example Request**:
```bash
# Get all positions
curl -X GET "http://localhost:8000/api/hris/positions" \
  -H "Authorization: Bearer $TOKEN"

# Get positions by department
curl -X GET "http://localhost:8000/api/hris/positions?department_id=1" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response (Paginated)**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Software Developer",
        "code": "DEV-001",
        "department": { "name": "Development" }
      }
    ],
    "per_page": 15,
    "total": 25
  }
}
```

### 2. Get Single Position
**URL**: `GET /positions/{id}`

**Example Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tenant_id": 1,
    "department_id": 4,
    "level_id": 2,
    "grade_id": 2,
    "code": "DEV-001",
    "title": "Software Developer",
    "description": "Full Stack Developer",
    "min_salary": null,
    "max_salary": null,
    "reports_to": null,
    "required_qualifications": null,
    "responsibilities": null,
    "is_active": true,
    "department": {
      "id": 4,
      "code": "IT-DEV",
      "name": "Development"
    },
    "level": {
      "id": 2,
      "code": "MID",
      "name": "Mid Level"
    },
    "grade": {
      "id": 2,
      "code": "G2",
      "name": "Grade 2"
    }
  }
}
```

### 3. Create Position
**URL**: `POST /positions`

**Body**:
```json
{
  "tenant_id": 1,
  "department_id": 1,
  "level_id": 2,
  "grade_id": 2,
  "code": "POS-001",
  "title": "Senior Developer",
  "description": "Senior Software Developer",
  "min_salary": 80000,
  "max_salary": 120000,
  "reports_to": null,
  "required_qualifications": "Bachelor's degree in Computer Science, 5+ years experience",
  "responsibilities": "Lead development team, code reviews, mentoring",
  "is_active": true
}
```

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/hris/positions" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "department_id": 1,
    "level_id": 2,
    "grade_id": 2,
    "code": "POS-001",
    "title": "Senior Developer",
    "description": "Senior Software Developer",
    "is_active": true
  }'
```

### 4. Update Position
**URL**: `PUT /positions/{id}`

### 5. Delete Position
**URL**: `DELETE /positions/{id}`

**Note**: Cannot delete if position has:
- Employees assigned
- Subordinate positions (positions reporting to it)

---

## Employees API

### 1. Get All Employees (Paginated)
**URL**: `GET /employees`

**Query Parameters**:
- `search` (optional): Search in employee_number, first_name, last_name, email
- `department_id` (optional): Filter by department ID
- `gender` (optional): Filter by gender (e.g., `male`, `female`)
- `nationality` (optional): Filter by nationality (e.g., `Nigerian`)
- `education_degree` (optional): Filter by education degree (e.g., `B.Sc`)
- `age_min` (optional): Minimum age filter
- `age_max` (optional): Maximum age filter
- `skill_id` (optional): Filter by skill ID
- `is_active` (optional): Filter by active status (1 for active, 0 for inactive)
- `per_page` (optional): Items per page (default: 15)

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/employees" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "tenant_id": 1,
        "user_id": 2,
        "employee_number": "STAFF/2025/001",
        "first_name": "John",
        "middle_name": "Michael",
        "last_name": "Doe",
        "date_of_birth": "1990-05-15T00:00:00.000000Z",
        "gender": "male",
        "marital_status": "single",
        "nationality": "Nigerian",
        "is_active": true,
        "full_name": "John Michael Doe",
        "user": {
          "id": 2,
          "name": "John Doe",
          "email": "john.doe@hrms.local"
        },
        "employment_details": {
          "work_email": "john.doe@hrms.local",
          "department_id": 4,
          "position_id": 1,
          "employment_type": "full-time",
          "employment_status": "active",
          "hire_date": "2025-01-01T00:00:00.000000Z"
        }
      }
    ],
    "per_page": 15,
    "total": 1
  }
}
```

### 2. Get Single Employee
**URL**: `GET /employees/{id}`

**Returns**: Employee with all relationships:
- User account
- Employment details (department, position, manager)
- Contact details
- Financial details
- Medical details
- Addresses
- Emergency contacts
- Dependents
- Education

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/employees/1" \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Create Employee
**URL**: `POST /employees`

> [!IMPORTANT]
> **Automatic User Account Creation**: When you create an employee, a user account is automatically created with a temporary password. The employee can use this to log in to the system.

> [!TIP]
> **Combined Endpoint**: You can now include `employment_details` in the employee creation request to create both the employee and their employment details in a single API call. This represents the minimum required employee information.

**Required Fields**:
- `employee_number` - Unique employee number
- `first_name` - Employee's first name
- `last_name` - Employee's last name
- **`email`** - Employee's email (must be unique, used for login)

**Optional Employment Details**:
You can include an `employment_details` object with any of the following fields:
- `work_email` - Work email address
- `department_id` - Department ID
- `position_id` - Position ID
- `manager_id` - Manager's employee ID
- `employment_type` - Type (e.g., full-time, part-time, contract)
- `employment_status` - Status (e.g., active, on-leave)
- `hire_date` - Hire date
- `probation_end_date` - Probation end date
- `probation_status` - One of: pending, passed, failed, extended
- `confirmation_date` - Confirmation date
- `contract_start_date` - Contract start date
- `contract_end_date` - Contract end date
- `notice_period_days` - Notice period in days
- `work_location` - Work location
- `work_schedule` - Work schedule
- `shift` - Work shift
- `remote_work_eligible` - Boolean

**Body (Basic)**:
```json
{
  "employee_number": "STAFF/2025/002",
  "first_name": "Jane",
  "middle_name": "Marie",
  "last_name": "Smith",
  "email": "jane.smith@company.com",
  "date_of_birth": "1992-03-20",
  "gender": "female",
  "marital_status": "single",
  "nationality": "American",
  "national_id": "123456789",
  "passport_number": "P1234567",
  "is_active": true
}
```

**Body (With Employment Details)**:
```json
{
  "employee_number": "STAFF/2025/002",
  "first_name": "Jane",
  "middle_name": "Marie",
  "last_name": "Smith",
  "email": "jane.smith@company.com",
  "date_of_birth": "1992-03-20",
  "gender": "female",
  "marital_status": "single",
  "nationality": "American",
  "national_id": "123456789",
  "passport_number": "P1234567",
  "is_active": true,
  "employment_details": {
    "work_email": "jane.smith@company.com",
    "department_id": 4,
    "position_id": 1,
    "manager_id": 2,
    "employment_type": "full-time",
    "employment_status": "active",
    "hire_date": "2025-01-01",
    "probation_end_date": "2025-04-01",
    "probation_status": "pending",
    "notice_period_days": 30,
    "work_location": "Lagos Office",
    "work_schedule": "Monday-Friday, 9AM-5PM",
    "shift": "day",
    "remote_work_eligible": true
  }
}
```

**Example Request (Basic)**:
```bash
curl -X POST "http://localhost:8000/api/hris/employees" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_number": "STAFF/2025/002",
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane.smith@company.com",
    "date_of_birth": "1992-03-20",
    "gender": "female",
    "marital_status": "single",
    "is_active": true
  }'
```

**Example Request (With Employment Details)**:
```bash
curl -X POST "http://localhost:8000/api/hris/employees" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_number": "STAFF/2025/003",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@company.com",
    "date_of_birth": "1990-05-15",
    "gender": "male",
    "marital_status": "single",
    "is_active": true,
    "employment_details": {
      "work_email": "john.doe@company.com",
      "department_id": 4,
      "position_id": 1,
      "employment_type": "full-time",
      "employment_status": "active",
      "hire_date": "2025-01-01",
      "probation_end_date": "2025-04-01",
      "probation_status": "pending",
      "notice_period_days": 30,
      "work_location": "Lagos Office",
      "remote_work_eligible": true
    }
  }'
```

**Example Response (With Employment Details)**:
```json
{
  "success": true,
  "message": "Employee and user account created successfully. A welcome email has been sent with instructions to set their password.",
  "data": {
    "employee": {
      "id": 2,
      "tenant_id": 1,
      "user_id": 3,
      "employee_number": "STAFF/2025/002",
      "first_name": "Jane",
      "last_name": "Smith",
      "user": {
        "id": 3,
        "name": "Jane Marie Smith",
        "email": "jane.smith@company.com"
      },
      "employment_details": {
        "id": 1,
        "employee_id": 2,
        "work_email": "jane.smith@company.com",
        "department_id": 4,
        "position_id": 1,
        "employment_type": "full-time",
        "employment_status": "active",
        "hire_date": "2025-01-01",
        "department": {
          "id": 4,
          "name": "Development"
        },
        "position": {
          "id": 1,
          "title": "Software Developer"
        },
        "manager": {
          "id": 2,
          "full_name": "Manager Name"
        }
      }
    }
  }
}
```

> [!NOTE]
> The temporary password is returned in the response. Make sure to securely share it with the employee. They will need to change it on first login.


### 4. Update Employee
**URL**: `PUT /employees/{id}`

### 5. Update Employee Photo
**URL**: `POST /employees/{id}/photo`

**Description**: Upload or update an employee's profile photo. Requires `multipart/form-data`.

**Request Body**:
- `photo`: File (image, max 2MB)

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/hris/employees/1/photo" \
  -H "Authorization: Bearer $TOKEN" \
  -F "photo=@/path/to/your/image.jpg"
```

**Example Response**:
```json
{
  "success": true,
  "message": "Profile photo updated successfully",
  "data": {
    "photo_url": "http://localhost:8000/storage/employee-photos/image_1735050000.jpg"
  }
}
```

### 6. Delete Employee
**URL**: `DELETE /employees/{id}`

### 7. Employment Details API

#### Create Employment Details
**URL**: `POST /employees/{id}/employment-details`

**Description**: Create employment details for an employee. This must be done before you can update employment details.

**Request Body**:
```json
{
  "work_email": "john.doe@company.com",
  "department_id": 4,
  "position_id": 1,
  "manager_id": 2,
  "employment_type": "full-time",
  "employment_status": "active",
  "hire_date": "2025-01-01",
  "probation_end_date": "2025-04-01",
  "probation_status": "pending",
  "confirmation_date": null,
  "contract_start_date": "2025-01-01",
  "contract_end_date": null,
  "termination_date": null,
  "termination_type": null,
  "termination_reason": null,
  "notice_period_days": 30,
  "work_location": "Lagos Office",
  "work_schedule": "Monday-Friday, 9AM-5PM",
  "shift": "day",
  "remote_work_eligible": true
}
```

**Validation Rules**:
- All fields are optional
- `department_id`: Must exist in departments table
- `position_id`: Must exist in positions table
- `manager_id`: Must exist in employees table
- `work_email`: Must be valid email format
- `probation_status`: Must be one of: `pending`, `passed`, `failed`, `extended`
- `remote_work_eligible`: Boolean (true/false)
- All date fields: Must be valid date format (YYYY-MM-DD)

**Example Response** (201 Created):
```json
{
  "success": true,
  "message": "Employment details created successfully",
  "data": {
    "id": 1,
    "employee_id": 7,
    "work_email": "john.doe@company.com",
    "department_id": 4,
    "position_id": 1,
    "manager_id": 2,
    "employment_type": "full-time",
    "employment_status": "active",
    "hire_date": "2025-01-01",
    "notice_period_days": 30,
    "work_location": "Lagos Office",
    "remote_work_eligible": true,
    "department": {
      "id": 4,
      "name": "Development"
    },
    "position": {
      "id": 1,
      "title": "Software Developer"
    },
    "manager": {
      "id": 2,
      "full_name": "Jane Smith"
    },
    "created_at": "2025-12-04T04:20:00.000000Z"
  }
}
```

**Error Response** (409 Conflict - if already exists):
```json
{
  "success": false,
  "message": "Employment details already exist. Use PUT to update."
}
```

#### Get Employee Employment Details
**URL**: `GET /employees/{id}/employment-details`

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/employees/1/employment-details" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "employee_id": 1,
    "work_email": "john.doe@company.com",
    "department_id": 4,
    "position_id": 1,
    "manager_id": 2,
    "employment_type": "full-time",
    "employment_status": "active",
    "hire_date": "2025-01-01",
    "probation_end_date": "2025-04-01",
    "probation_status": "pending",
    "confirmation_date": null,
    "contract_start_date": "2025-01-01",
    "contract_end_date": null,
    "termination_date": null,
    "termination_type": null,
    "termination_reason": null,
    "notice_period_days": 30,
    "work_location": "Lagos Office",
    "work_schedule": "Monday-Friday, 9AM-5PM",
    "shift": "day",
    "remote_work_eligible": true,
    "department": {
      "id": 4,
      "name": "Development"
    },
    "position": {
      "id": 1,
      "title": "Software Developer"
    },
    "manager": {
      "id": 2,
      "full_name": "Jane Smith"
    }
  }
}
```

#### Update Employee Employment Details
**URL**: `PUT /employees/{id}/employment-details`

**Request Body**:
```json
{
  "work_email": "john.doe@company.com",
  "department_id": 4,
  "position_id": 1,
  "manager_id": 2,
  "employment_type": "full-time",
  "employment_status": "active",
  "hire_date": "2025-01-01",
  "probation_end_date": "2025-04-01",
  "probation_status": "pending",
  "confirmation_date": null,
  "contract_start_date": "2025-01-01",
  "contract_end_date": null,
  "termination_date": null,
  "termination_type": null,
  "termination_reason": null,
  "notice_period_days": 30,
  "work_location": "Lagos Office",
  "work_schedule": "Monday-Friday, 9AM-5PM",
  "shift": "day",
  "remote_work_eligible": true
}
```

**Field Descriptions**:
- `work_email`: Employee's work email address
- `department_id`: ID of the department (must exist in departments table)
- `position_id`: ID of the position (must exist in positions table)
- `manager_id`: ID of the manager (must be another employee)
- `employment_type`: Type of employment (e.g., full-time, part-time, contract)
- `employment_status`: Current status (e.g., active, on-leave, terminated)
- `hire_date`: Date employee was hired
- `probation_end_date`: End date of probation period
- `probation_status`: Status of probation - one of: `pending`, `passed`, `failed`, `extended`
- `confirmation_date`: Date employee was confirmed
- `contract_start_date`: Contract start date (for contract employees)
- `contract_end_date`: Contract end date (for contract employees)
- `termination_date`: Date of termination (if applicable)
- `termination_type`: Type of termination (e.g., voluntary, involuntary)
- `termination_reason`: Reason for termination
- `notice_period_days`: Notice period in days (default: 30)
- `work_location`: Physical work location
- `work_schedule`: Work schedule description
- `shift`: Work shift (e.g., day, night, rotating)
- `remote_work_eligible`: Boolean - whether employee can work remotely

**Example Response**:
```json
{
  "success": true,
  "message": "Employment details updated successfully",
  "data": {
    "id": 1,
    "employee_id": 1,
    "work_email": "john.doe@company.com",
    "department_id": 4,
    "position_id": 1,
    "employment_type": "full-time",
    "employment_status": "active",
    "updated_at": "2025-01-15T10:45:00.000000Z"
  }
}
```

---

### 7. Contact Details API

#### Create Contact Details
**URL**: `POST /employees/{id}/contact-details`

**Description**: Create contact details for an employee. This must be done before you can update contact details.

**Request Body**:
```json
{
  "personal_email": "john.doe@gmail.com",
  "work_phone": "+234-802-345-6789",
  "mobile_phone": "+234-801-234-5678",
  "home_phone": "+234-1-234-5678",
  "whatsapp_number": "+234-801-234-5678",
  "linkedin_url": "https://linkedin.com/in/johndoe",
  "skype_id": "john.doe.skype",
  "other_contact": "Telegram: @johndoe",
  "preferred_contact_method": "mobile_phone"
}
```

**Validation Rules**:
- All fields are optional
- `personal_email`: Must be valid email format
- `linkedin_url`: Must be valid URL format
- `preferred_contact_method`: Can be any string (typically one of the contact field names)

**Example Response** (201 Created):
```json
{
  "success": true,
  "message": "Contact details created successfully",
  "data": {
    "id": 1,
    "employee_id": 7,
    "personal_email": "john.doe@gmail.com",
    "work_phone": "+234-802-345-6789",
    "mobile_phone": "+234-801-234-5678",
    "home_phone": "+234-1-234-5678",
    "whatsapp_number": "+234-801-234-5678",
    "linkedin_url": "https://linkedin.com/in/johndoe",
    "skype_id": "john.doe.skype",
    "other_contact": "Telegram: @johndoe",
    "preferred_contact_method": "mobile_phone",
    "created_at": "2025-12-04T04:20:00.000000Z"
  }
}
```

**Error Response** (409 Conflict - if already exists):
```json
{
  "success": false,
  "message": "Contact details already exist. Use PUT to update."
}
```

#### Get Employee Contact Details
**URL**: `GET /employees/{id}/contact-details`

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/employees/1/contact-details" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "employee_id": 1,
    "personal_email": "john.doe@gmail.com",
    "work_phone": "+234-802-345-6789",
    "mobile_phone": "+234-801-234-5678",
    "home_phone": "+234-1-234-5678",
    "whatsapp_number": "+234-801-234-5678",
    "linkedin_url": "https://linkedin.com/in/johndoe",
    "skype_id": "john.doe.skype",
    "other_contact": "Telegram: @johndoe",
    "preferred_contact_method": "mobile_phone"
  }
}
```

#### Update Employee Contact Details
**URL**: `PUT /employees/{id}/contact-details`

**Request Body**:
```json
{
  "personal_email": "john.doe@gmail.com",
  "work_phone": "+234-802-345-6789",
  "mobile_phone": "+234-801-234-5678",
  "home_phone": "+234-1-234-5678",
  "whatsapp_number": "+234-801-234-5678",
  "linkedin_url": "https://linkedin.com/in/johndoe",
  "skype_id": "john.doe.skype",
  "other_contact": "Telegram: @johndoe",
  "preferred_contact_method": "mobile_phone"
}
```

**Validation Rules**:
- All fields are optional
- `preferred_contact_method`: Can be any of the contact field names

---

### 8. Financial Details API

#### Create Financial Details
**URL**: `POST /employees/{employee}/financial-details`

**Request Body**:
```json
{
  "bank_name": "First Bank of Nigeria",
  "bank_branch": "Victoria Island",
  "account_number": "1234567890",
  "account_name": "John Michael Doe",
  "account_type": "savings",
  "swift_code": "FBNINGLA",
  "iban": "NG12FBNI00000001234567890",
  "tax_id": "12345678-0001",
  "tax_status": "PAYE",
  "social_security_number": "SSN123456789",
  "pension_number": "PEN123456789",
  "insurance_number": "INS987654321",
  "current_salary": 500000.00,
  "salary_currency": "NGN",
  "payment_frequency": "monthly",
  "payment_method": "bank-transfer"
}
```

**Validation Rules**:
- `bank_name`: Required, string, max 255
- `bank_branch`: Optional, string, max 255
- `account_number`: Required, string, max 50
- `account_name`: Required, string, max 255
- `account_type`: Optional, one of: `savings`, `current`, `checking`
- `swift_code`: Optional, string, max 20
- `iban`: Optional, string, max 50
- `tax_id`: Optional, string, max 50
- `tax_status`: Optional, string, max 100
- `social_security_number`: Optional, string, max 50
- `pension_number`: Optional, string, max 50
- `insurance_number`: Optional, string, max 50
- `current_salary`: Required, numeric, min 0
- `salary_currency`: Required, string, exactly 3 characters (e.g., NGN, USD, GBP)
- `payment_frequency`: Required, one of: `monthly`, `bi-weekly`, `weekly`, `daily`
- `payment_method`: Optional, one of: `bank-transfer`, `cash`, `cheque`,`mobile-money`

**Example Response**:
```json
{
  "success": true,
  "message": "Financial details created successfully",
  "data": {
    "id": 1,
    "employee_id": 1,
    "bank_name": "First Bank of Nigeria",
    "bank_branch": "Victoria Island",
    "account_number": "1234567890",
    "account_name": "John Michael Doe",
    "account_type": "savings",
    "current_salary": "500000.00",
    "salary_currency": "NGN",
    "payment_frequency": "monthly",
    "created_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

#### Update Financial Details
**URL**: `PUT /employees/{employee}/financial-details`

**Request Body**: Same as create, all fields optional

#### Get Financial Details
**URL**: `GET /employees/{employee}/financial-details`

**Example Response**: Same structure as create response

> [!WARNING]
> Financial details are highly sensitive. Ensure proper access control and audit logging.

---

### 9. Medical Details API

#### Create Medical Details
**URL**: `POST /employees/{employee}/medical-details`

**Request Body**:
```json
{
  "blood_group": "O+",
  "genotype": "AA",
  "height": 175.5,
  "weight": 75.0,
  "allergies": "Penicillin, Peanuts",
  "chronic_conditions": "None",
  "medications": "None",
  "disabilities": null,
  "health_insurance_provider": "Hygeia HMO",
  "health_insurance_number": "HYG123456789",
  "health_insurance_expiry": "2025-12-31",
  "emergency_medical_info": "Allergic to penicillin",
  "last_medical_checkup": "2024-06-15",
  "next_medical_checkup": "2025-06-15",
  "doctor_name": "Dr. Smith Johnson",
  "doctor_phone": "+234-805-678-9012",
  "hospital_preference": "Lagos University Teaching Hospital"
}
```

**Validation Rules**:
- `blood_group`: Optional, one of: `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-`
- `genotype`: Optional, one of: `AA`, `AS`, `SS`, `AC`, `SC`
- `height`: Optional, numeric, min 0 (in cm)
- `weight`: Optional, numeric, min 0 (in kg)
- `allergies`: Optional, string
- `chronic_conditions`: Optional, string
- `medications`: Optional, string
- `disabilities`: Optional, string
- `health_insurance_provider`: Optional, string, max 255
- `health_insurance_number`: Optional, string, max 50
- `health_insurance_expiry`: Optional, date
- `emergency_medical_info`: Optional, string
- `last_medical_checkup`: Optional, date
- `next_medical_checkup`: Optional, date, must be after today
- `doctor_name`: Optional, string, max 255
- `doctor_phone`: Optional, string, max 20
- `hospital_preference`: Optional, string, max 255

**Example Response**:
```json
{
  "success": true,
  "message": "Medical details created successfully",
  "data": {
    "id": 1,
    "employee_id": 1,
    "blood_group": "O+",
    "genotype": "AA",
    "height": "175.50",
    "weight": "75.00",
    "allergies": "Penicillin, Peanuts",
    "health_insurance_provider": "Hygeia HMO",
    "health_insurance_number": "HYG123456789",
    "health_insurance_expiry": "2025-12-31",
    "created_at": "2025-01-15T10:35:00.000000Z"
  }
}
```

#### Update Medical Details
**URL**: `PUT /employees/{employee}/medical-details`

**Request Body**: Same as create, all fields optional

#### Get Medical Details
**URL**: `GET /employees/{employee}/medical-details`

**Example Response**: Same structure as create response

> [!IMPORTANT]
> Medical information is highly sensitive and subject to privacy regulations (HIPAA, GDPR, etc.). Implement strict access controls, encryption, and comprehensive audit logging.


### 10. Get Employee Profile Completeness
**URL**: `GET /employees/{id}/profile-completeness`

**Example Response**:
```json
{
  "success": true,
  "data": {
    "employee_id": 1,
    "overall_percentage": 75.50,
    "basic_info_percentage": 100.00,
    "employment_info_percentage": 100.00,
    "contact_info_percentage": 50.00,
    "financial_info_percentage": 80.00,
    "medical_info_percentage": 60.00,
    "education_percentage": 70.00,
    "documents_percentage": 40.00,
    "last_calculated_at": "2025-11-22T15:43:08.000000Z"
  }
}
```

### 11. Get Employee History
**URL**: `GET /employees/{id}/history`

**Example Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee_id": 1,
      "change_type": "promotion",
      "effective_date": "2024-01-01",
      "previous_value": {
        "position_id": 1,
        "grade_id": 1
      },
      "new_value": {
        "position_id": 2,
        "grade_id": 2
      },
      "reason": "Performance review",
      "approved_by": 1,
      "created_at": "2024-01-01T10:00:00.000000Z"
    }
  ]
}
```

---

## Skills API

### 1. Get All Skills
**URL**: `GET /skills`

**Query Parameters**:
- `search` (optional): Search in name, description
- `category` (optional): Filter by category
- `is_active` (optional): Filter by active status

**Example Request**:
```bash
# Get all skills
curl -X GET "http://localhost:8000/api/hris/skills" \
  -H "Authorization: Bearer $TOKEN"

# Get skills by category
curl -X GET "http://localhost:8000/api/hris/skills?category=Technical" \
  -H "Authorization: Bearer $TOKEN"
```

### 2. Get Single Skill
**URL**: `GET /skills/{id}`

**Example Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tenant_id": 1,
    "name": "Laravel Development",
    "category": "Technical",
    "description": "Laravel PHP Framework",
    "is_active": true,
    "employees": [
      {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "pivot": {
          "proficiency_level": "advanced",
          "years_of_experience": 3.5,
          "is_certified": true
        }
      }
    ]
  }
}
```

### 3. Create Skill
**URL**: `POST /skills`

**Body**:
```json
{
  "tenant_id": 1,
  "name": "Laravel Development",
  "category": "Technical",
  "description": "Laravel PHP Framework",
  "is_active": true
}
```

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/hris/skills" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "name": "Laravel Development",
    "category": "Technical",
    "description": "Laravel PHP Framework",
    "is_active": true
  }'
```

### 4. Update Skill
**URL**: `PUT /skills/{id}`

### 5. Delete Skill
**URL**: `DELETE /skills/{id}`

**Note**: Cannot delete if skill is assigned to employees

---

## Documents API

### 1. Get Employee Documents
**URL**: `GET /employees/{employee}/documents`

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/employees/1/documents" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee_id": 1,
      "document_type_id": 1,
      "document_name": "Passport",
      "file_path": "employee-documents/abc123.pdf",
      "file_size": 1024000,
      "mime_type": "application/pdf",
      "issue_date": "2020-01-01",
      "expiry_date": "2030-01-01",
      "notes": "Valid passport",
      "uploaded_by": 1,
      "document_type": {
        "id": 1,
        "name": "Passport",
        "code": "PASSPORT"
      }
    }
  ]
}
```

### 2. Upload Employee Document
**URL**: `POST /employees/{employee}/documents`

**Body** (multipart/form-data):
```
tenant_id: 1
document_type_id: 1
document_name: Passport
file: [file upload]
issue_date: 2020-01-01
expiry_date: 2030-01-01
notes: Valid passport
```

**Supported file types**:
- **Images**: jpg, jpeg, png, gif, webp
- **Documents**: pdf, doc, docx, xls, xlsx
- **Max file size**: 10MB

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/hris/employees/1/documents" \
  -H "Authorization: Bearer $TOKEN" \
  -F "tenant_id=1" \
  -F "document_type_id=1" \
  -F "document_name=Passport" \
  -F "file=@/path/to/document.pdf" \
  -F "issue_date=2020-01-01" \
  -F "expiry_date=2030-01-01" \
  -F "notes=Valid passport"
```

### 3. Get Single Document
**URL**: `GET /employees/{employee}/documents/{document}`

### 4. Delete Document
**URL**: `DELETE /employees/{employee}/documents/{document}`

**Note**: Deletes both database record and file from storage

---

## Work Experience API

### List All Work Experience
```bash
GET /api/hris/employees/{employee}/work-experience
```

### Create Work Experience
```bash
POST /api/hris/employees/{employee}/work-experience
```

**Request Body**:
```json
{
  "company": "Tech Solutions Ltd",
  "position": "Senior Developer",
  "start_date": "2020-01-01",
  "end_date": "2023-12-31",
  "responsibilities": "Leading the backend team, architecture design.",
  "reason_for_leaving": "Better opportunity"
}
```

### Update Work Experience
```bash
PUT /api/hris/employees/{employee}/work-experience/{experience}
```

### Delete Work Experience (Soft Delete)
```bash
DELETE /api/hris/employees/{employee}/work-experience/{experience}
```

---

## Certifications API

### List All Certifications
```bash
GET /api/hris/employees/{employee}/certifications
```

### Create Certification
```bash
POST /api/hris/employees/{employee}/certifications
```

**Request Body**:
```json
{
  "certification_name": "AWS Certified Solutions Architect",
  "issuing_organization": "Amazon Web Services",
  "issue_date": "2023-05-15",
  "expiry_date": "2026-05-15",
  "credential_id": "AWS-123456",
  "credential_url": "https://aws.amazon.com/verification"
}
```

### Update Certification
```bash
PUT /api/hris/employees/{employee}/certifications/{certification}
```

### Delete Certification (Soft Delete)
```bash
DELETE /api/hris/employees/{employee}/certifications/{certification}
```

---

## Employee Skills API

### List Assigned Skills
```bash
GET /api/hris/employees/{employee}/skills
```

### Assign Skill
```bash
POST /api/hris/employees/{employee}/skills
```
**Payload**:
```json
{
  "skill_id": 1,
  "proficiency_level": "Advanced",
  "years_of_experience": 2.5,
  "last_used": "2023-12-01",
  "is_certified": true,
  "certification_name": "AWS Certified Developer",
  "certification_date": "2023-01-15"
}
```

### Update Skill Assignment
```bash
PUT /api/hris/employees/{employee}/skills/{id}
```

### Delete Skill Assignment
```bash
DELETE /api/hris/employees/{employee}/skills/{id}
```

---

## Employee History API

### Get Employee History
**URL**: `GET /employees/{id}/history`

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/employees/1/history" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee_id": 1,
      "change_type": "promotion",
      "effective_date": "2024-01-01",
      "previous_value": {
        "position_id": 1
      },
      "new_value": {
        "position_id": 2
      },
      "reason": "Excellent performance review",
      "approved_by": 1,
      "notes": "Promoted after 2 years of outstanding service",
      "created_at": "2024-01-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "employee_id": 1,
      "change_type": "salary_change",
      "effective_date": "2024-01-01",
      "previous_value": {
        "current_salary": 50000
      },
      "new_value": {
        "current_salary": 75000
      },
      "reason": "Promotion salary adjustment",
      "approved_by": 1,
      "notes": "50% increase due to promotion",
      "created_at": "2024-01-01T10:00:00.000000Z"
    }
  ]
}
```

**Change Types**:
- `promotion` - Employee promoted to higher position
- `demotion` - Employee moved to lower position
- `transfer` - Department or location transfer
- `salary_change` - Salary adjustment
- `status_change` - Employment status change
- `other` - Other types of changes

**Note**: Employee history is **automatically** tracked for both **initial setup (creation)** and **updates** to major employment/financial details. This provides a complete audit trail of professional lifecycle events.

---

## Profile Completeness API

### Get Employee Profile Completeness
**URL**: `GET /employees/{id}/profile-completeness`

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/employees/1/profile-completeness" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "employee_id": 1,
    "overall_percentage": 75.50,
    "basic_info_percentage": 100.00,
    "employment_info_percentage": 100.00,
    "contact_info_percentage": 50.00,
    "financial_info_percentage": 80.00,
    "medical_info_percentage": 60.00,
    "education_percentage": 70.00,
    "documents_percentage": 40.00,
    "last_calculated_at": "2025-11-29T13:00:00.000000Z",
    "created_at": "2025-11-22T15:43:08.000000Z",
    "updated_at": "2025-11-29T13:00:00.000000Z"
  }
}
```

**Completeness Breakdown**:
- **overall_percentage**: Overall profile completion (0-100%)
- **basic_info_percentage**: Personal information (name, DOB, gender, etc.)
- **employment_info_percentage**: Employment details (department, position, etc.)
- **contact_info_percentage**: Contact information and addresses
- **financial_info_percentage**: Bank details and salary information
- **medical_info_percentage**: Medical and health information
- **education_percentage**: Educational qualifications
- **documents_percentage**: Required documents uploaded

**Note**: Profile completeness is calculated automatically based on filled fields. This helps HR track onboarding progress and ensure all required information is collected.

---

## Testing Checklist

### Basic CRUD Tests
- [ ] Test Department CRUD operations
- [ ] Test Level CRUD operations
- [ ] Test Grade CRUD operations
- [ ] Test Position CRUD operations
- [ ] Test Employee CRUD operations
- [ ] Test Skill CRUD operations

### Employee Detail Tests
- [ ] Test employment details endpoint
- [ ] Test contact details endpoint
- [ ] Test financial details endpoint
- [ ] Test medical details endpoint
- [ ] Test profile completeness endpoint
- [ ] Test history endpoint

### Document Tests
- [ ] Test document upload
- [ ] Test document listing
- [ ] Test document retrieval
- [ ] Test document deletion
- [ ] Test file size limits
- [ ] Test file type validation

### New CRUD API Tests
- [x] Test Work Experience CRUD operations (individual endpoints)
- [x] Test Certifications CRUD operations (individual endpoints)
- [ ] Test work experience relationship loading via Employee show
- [ ] Test certifications relationship loading via Employee show
- [ ] Test skills relationship loading via Employee show
- [ ] Test documents relationship loading via Employee show
- [ ] Test employee history tracking for all major changes (Initial Setup, Promotion, Salary/Currency Change)
- [x] Test employee history tracking for salary and currency modifications
- [ ] Test profile completeness auto-calculation after CRUD
- [ ] Verify all relationships load correctly in employee show endpoint

### Relationship Tests
- [ ] Test employee with all relationships
- [ ] Test department hierarchy (parent/child)
- [ ] Test position reporting structure
- [ ] Test skill assignment to employees
- [ ] Test department employees (hasManyThrough)
- [ ] Test position employees (hasManyThrough)
- [ ] Test work experience cascade delete
- [ ] Test certifications cascade delete
- [ ] Test documents cascade delete with file cleanup

### Validation Tests
- [ ] Test deleting department with children (should fail)
- [ ] Test deleting department with employees (should fail)
- [ ] Test deleting position with employees (should fail)
- [ ] Test deleting position with subordinates (should fail)
- [ ] Test salary range validation (max >= min)
- [ ] Test deleting skill assigned to employees (should fail)
- [ ] Test file upload size limits
- [ ] Test required field validation
- [ ] Test document type foreign key constraint

### Search & Filter Tests
- [ ] Test department search
- [ ] Test position filtering by department
- [ ] Test employee search
- [ ] Test employee filtering by department
- [ ] Test skill filtering by category
- [ ] Test pagination

---

## Common Test Scenarios

### Scenario 1: Complete Employee Onboarding
```bash
# 1. Create employee
curl -X POST "http://localhost:8000/api/hris/employees" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "employee_number": "STAFF/2025/003",
    "first_name": "Alice",
    "last_name": "Johnson",
    "date_of_birth": "1995-07-10",
    "gender": "female",
    "marital_status": "single",
    "is_active": true
  }'

# 2. Add employment details (via update)
# 3. Add contact details
# 4. Add work experience
# 5. Add certifications
# 6. Upload documents
# 7. Check profile completeness recalculation
```

### Scenario 2: Department Hierarchy
```bash
# 1. Create parent department
curl -X POST "http://localhost:8000/api/hris/departments" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "code": "ENG",
    "name": "Engineering",
    "is_active": true
  }'

# 2. Create child department
curl -X POST "http://localhost:8000/api/hris/departments" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "parent_id": 5,
    "code": "ENG-FE",
    "name": "Frontend Engineering",
    "is_active": true
  }'

# 3. Verify hierarchy
curl -X GET "http://localhost:8000/api/hris/departments/5" \
  -H "Authorization: Bearer $TOKEN"
```

### Scenario 3: Position with Reporting Structure
```bash
# 1. Create manager position
curl -X POST "http://localhost:8000/api/hris/positions" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "department_id": 1,
    "level_id": 3,
    "grade_id": 3,
    "code": "MGR-001",
    "title": "Engineering Manager",
    "is_active": true
  }'

# 2. Create subordinate position
curl -X POST "http://localhost:8000/api/hris/positions" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "department_id": 1,
    "level_id": 2,
    "grade_id": 2,
    "code": "DEV-002",
    "title": "Senior Developer",
    "reports_to": 3,
    "is_active": true
  }'
```

### Scenario 4: Employee Skills Management
```bash
# 1. Create skill
curl -X POST "http://localhost:8000/api/hris/skills" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "name": "React Development",
    "category": "Technical",
    "description": "React JavaScript Framework",
    "is_active": true
  }'

# 2. View skill with employees
curl -X GET "http://localhost:8000/api/hris/skills/1" \
  -H "Authorization: Bearer $TOKEN"

### Scenario 5: Fetch Skill Categories
```bash
# Get all unique skill categories
curl -X GET "http://localhost:8000/api/hris/skills/categories" \
  -H "Authorization: Bearer $TOKEN"
```
```

---

## API Response Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Missing or invalid token |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Server Error | Internal server error |

---

## Quick Reference

### Available Endpoints Summary

| Resource | Endpoints | Count |
|----------|-----------|-------|
| Departments | GET, POST, GET/{id}, PUT/{id}, DELETE/{id} | 5 |
| Levels | GET, POST, GET/{id}, PUT/{id}, DELETE/{id} | 5 |
| Grades | GET, POST, GET/{id}, PUT/{id}, DELETE/{id} | 5 |
| Positions | GET, POST, GET/{id}, PUT/{id}, DELETE/{id} | 5 |
| Employees | GET, POST, GET/{id}, PUT/{id}, DELETE/{id} + 6 detail endpoints | 11 |
| Skills | GET, POST, GET/{id}, PUT/{id}, DELETE/{id} | 5 |
| Documents | GET, POST, GET/{id}, DELETE/{id} (per employee) | 4 |
| Bulk Employee Upload | GET /template, POST /import | 2 |

**Total Endpoints**: 40+

---
 
 ## Bulk Employee Upload API
 
 ### 1. Download Upload Template
 **URL**: `GET /hris/employees-bulk/template`
 
 **Purpose**: Download a pre-formatted Excel template for bulk employee creation.
 **Smart Features**: The template includes dynamic dropdowns for Departments, Positions, Genders, etc.
 
 **Example Request**:
 ```bash
 curl -X GET "http://localhost:8000/api/hris/employees-bulk/template" \
   -H "Authorization: Bearer $TOKEN" \
   --output template.xlsx
 ```
 
 ### 2. Bulk Import Employees
 **URL**: `POST /hris/employees-bulk/import`
 
 **Description**: Upload an Excel file to create multiple employees at once.
 **Smart Processing**:
 - **Partial Success**: Valid rows are processed; invalid rows are skipped and reported.
 - **Auto-Creation**: Missing Departments and Positions are automatically created.
 - **Manager Resolution**: Resolved by work email.
 - **Welcome Emails**: Automatically sent for every successful creation.
 
 **Request Body**: `multipart/form-data`
 - `file`: Excel file (.xlsx, .xls)
 
 **Example Request**:
 ```bash
 curl -X POST "http://localhost:8000/api/hris/employees-bulk/import" \
   -H "Authorization: Bearer $TOKEN" \
   -F "file=@employees.xlsx"
 ```
 
 **Example Success Response (Partial Success)**:
 ```json
 {
   "success": true,
   "message": "Import processed successfully",
   "data": {
     "summary": {
       "total": 5,
       "success": 3,
       "failed": 2
     },
     "errors": [
       {
         "row": 4,
         "email": "invalid-email",
         "error": "The email must be a valid email address."
       },
       {
         "row": 6,
         "email": "existing@example.com",
         "error": "User with email existing@example.com already exists."
       }
     ]
   }
 }
 ```
 
 ---
 
 ## Financial Details API

### Create Financial Details
```bash
POST /api/hris/employees/{employee}/financial-details
```

**Request Body**:
```json
{
  "bank_name": "First Bank",
  "bank_branch": "Victoria Island",
  "account_number": "1234567890",
  "account_name": "John Doe",
  "account_type": "savings",
  "swift_code": "FBNINGLA",
  "iban": "NG12FBNL1234567890",
  "tax_id": "TAX123456",
  "tax_status": "PAYE",
  "social_security_number": "SSN123456",
  "pension_number": "PEN123456",
  "insurance_number": "INS123456",
  "current_salary": 150000.00,
  "salary_currency": "NGN",
  "payment_frequency": "monthly",
  "payment_method": "bank_transfer"
}
```

### Update Financial Details
```bash
PUT /api/hris/employees/{employee}/financial-details
```

**Request Body** (partial updates allowed):
```json
{
  "current_salary": 175000.00,
  "bank_name": "Access Bank"
}
```

### Get Financial Details
```bash
GET /api/hris/employees/{employee}/financial-details
```

---

## Medical Details API

### Create Medical Details
```bash
POST /api/hris/employees/{employee}/medical-details
```

**Request Body**:
```json
{
  "blood_group": "O+",
  "genotype": "AA",
  "height": 175.5,
  "weight": 75.0,
  "allergies": "Peanuts, Penicillin",
  "chronic_conditions": "Asthma",
  "medications": "Inhaler as needed",
  "disabilities": null,
  "health_insurance_provider": "Hygeia HMO",
  "health_insurance_number": "HYG123456",
  "health_insurance_expiry": "2025-12-31",
  "emergency_medical_info": "Asthmatic - keep inhaler nearby",
  "last_medical_checkup": "2024-06-15",
  "next_medical_checkup": "2025-06-15",
  "doctor_name": "Dr. Smith",
  "doctor_phone": "+234-800-000-0000",
  "hospital_preference": "Lagos University Teaching Hospital"
}
```

### Update Medical Details
```bash
PUT /api/hris/employees/{employee}/medical-details
```

### Get Medical Details
```bash
GET /api/hris/employees/{employee}/medical-details
```

---

## Addresses API

### List All Addresses
```bash
GET /api/hris/employees/{employee}/addresses
```

### Create Address
```bash
POST /api/hris/employees/{employee}/addresses
```

**Request Body**:
```json
{
  "address_type": "home",
  "address_line1": "123 Main Street",
  "address_line2": "Apartment 4B",
  "city": "Lagos",
  "state": "Lagos",
  "postal_code": "100001",
  "country": "Nigeria",
  "is_primary": true
}
```

**Address Types**: `home`, `work`, `current`, `permanent`, `mailing`

### Update Address
```bash
PUT /api/hris/employees/{employee}/addresses/{address}
```

### Delete Address (Soft Delete)
```bash
DELETE /api/hris/employees/{employee}/addresses/{address}
```

**Note**: Address is soft-deleted and can be restored from audit logs.

---

## Emergency Contacts API

### List All Emergency Contacts
```bash
GET /api/hris/employees/{employee}/emergency-contacts
```

### Create Emergency Contact
```bash
POST /api/hris/employees/{employee}/emergency-contacts
```

**Request Body**:
```json
{
  "name": "Jane Doe",
  "relationship": "Spouse",
  "phone": "+234-800-000-0000",
  "alternate_phone": "+234-800-000-0001",
  "email": "jane.doe@example.com",
  "address": "123 Main Street, Lagos",
  "is_primary": true
}
```

### Update Emergency Contact
```bash
PUT /api/hris/employees/{employee}/emergency-contacts/{contact}
```

### Delete Emergency Contact (Soft Delete)
```bash
DELETE /api/hris/employees/{employee}/emergency-contacts/{contact}
```

---

## Education API

### List All Education Records
```bash
GET /api/hris/employees/{employee}/education
```

### Create Education Record
```bash
POST /api/hris/employees/{employee}/education
```

**Request Body**:
```json
{
  "institution": "University of Lagos",
  "degree": "Bachelor of Science",
  "field_of_study": "Computer Science",
  "start_date": "2015-09-01",
  "end_date": "2019-06-30",
  "grade": "First Class",
  "is_highest": true
}
```

### Update Education Record
```bash
PUT /api/hris/employees/{employee}/education/{education}
```

### Delete Education Record (Soft Delete)
```bash
DELETE /api/hris/employees/{employee}/education/{education}
```

---

## Dependents API

### List All Dependents
```bash
GET /api/hris/employees/{employee}/dependents
```

### Create Dependent
```bash
POST /api/hris/employees/{employee}/dependents
```

**Request Body**:
```json
{
  "name": "John Doe Jr.",
  "relationship": "child",
  "date_of_birth": "2015-03-20",
  "gender": "male",
  "national_id": "NIN123456789",
  "is_beneficiary": true,
  "beneficiary_percentage": 50.00
}
```

**Relationship Types**: `spouse`, `child`, `parent`, `sibling`, `other`

### Update Dependent
```bash
PUT /api/hris/employees/{employee}/dependents/{dependent}
```

### Delete Dependent (Soft Delete)
```bash
DELETE /api/hris/employees/{employee}/dependents/{dependent}
```

---

## Skills API

### 1. Get All Skills
**URL**: `GET /skills`

**Query Parameters**:
- `search` (optional): Search in name, description
- `category` (optional): Filter by category
- `is_active` (optional): Filter by active status
- `per_page` (optional): Items per page (default: 15)

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/skills" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response (Paginated)**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "JavaScript",
        "category": "Technical",
        "is_active": true,
        "employees_count": 5
      }
    ],
    "per_page": 15,
    "total": 50
  }
}
```

### 2. Create Skill
**URL**: `POST /skills`

**Body**:
```json
{
  "name": "JavaScript",
  "category": "Technical",
  "description": "JavaScript programming language",
  "is_active": true
}
```

### 3. Update Skill
**URL**: `PUT /skills/{id}`

### 4. Delete Skill
**URL**: `DELETE /skills/{id}`

**Note**: Cannot delete if skill is assigned to employees


## Audit Logs API

### Get Employee Audit Logs
```bash
GET /api/hris/employees/{employee}/audit-logs
```

**Response Example**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "auditable_type": "App\\Models\\EmployeeFinancialDetail",
      "auditable_id": 1,
      "event": "updated",
      "old_values": {
        "current_salary": "150000.00"
      },
      "new_values": {
        "current_salary": "175000.00"
      },
      "ip_address": "127.0.0.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2024-11-29T15:30:00.000000Z"
    }
  ]
}
```

**Event Types**: `created`, `updated`, `deleted`, `restored`

---

## Notifications API

### Get All Notifications
```bash
GET /api/notifications
```

**Query Parameters**:
- `unread_only` (optional): Set to `true` to get only unread notifications
- `per_page` (optional): Items per page (default: 15)

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/notifications" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "9a5e3c8d-1234-5678-90ab-cdef12345678",
        "type": "App\\Notifications\\WelcomeEmployee",
        "notifiable_type": "App\\Models\\User",
        "notifiable_id": 3,
        "data": {
          "title": "Welcome to HRMS",
          "message": "Your employee account has been created. Employee Number: STAFF/2025/002",
          "type": "welcome",
          "action_url": "http://localhost:8000/login",
          "action_text": "Login to Portal",
          "employee_number": "STAFF/2025/002",
          "has_temporary_password": true
        },
        "read_at": null,
        "created_at": "2024-11-30T07:45:00.000000Z"
      }
    ],
    "per_page": 15,
    "total": 1
  }
}
```

### Get Unread Count
```bash
GET /api/notifications/unread-count
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "unread_count": 3
  }
}
```

### Mark Notification as Read
```bash
POST /api/notifications/{id}/mark-as-read
```

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/notifications/9a5e3c8d-1234-5678-90ab-cdef12345678/mark-as-read" \
  -H "Authorization: Bearer $TOKEN"
```

### Mark All Notifications as Read
```bash
POST /api/notifications/mark-all-as-read
```

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/notifications/mark-all-as-read" \
  -H "Authorization: Bearer $TOKEN"
```

### Delete Notification
```bash
DELETE /api/notifications/{id}
```

**Example Request**:
```bash
curl -X DELETE "http://localhost:8000/api/notifications/9a5e3c8d-1234-5678-90ab-cdef12345678" \
  -H "Authorization: Bearer $TOKEN"
```

---

## Profile Change Requests API

### Submit Change Request
**URL**: `POST /api/hris/profile/change-requests`

**Description**: Submit a profile change request for approval

**Request Body**:
```json
{
  "section": "contact_details",
  "proposed_data": {
    "mobile_phone": "+234 802 345 6789",
    "personal_email": "new@email.com"
  },
  "notes": "Changed phone number due to new SIM card"
}
```

**Response (201)**:
```json
{
  "success": true,
  "message": "Change request submitted for approval",
  "data": {
    "id": 1,
    "section": "contact_details",
    "status": "pending",
    "submitted_at": "2025-12-27T14:30:00Z"
  }
}
```

**Error (409)** - Pending request exists:
```json
{
  "success": false,
  "message": "You have a pending approval request for this section"
}
```

### Get My Requests
**URL**: `GET /api/hris/profile/my-requests?status=pending&page=1&per_page=15`

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "section": "contact_details",
        "status": "pending",
        "submitted_at": "2025-12-27T14:30:00Z",
        "reviewed_at": null,
        "reviewed_by": null,
        "decline_reason": null,
        "notes": "Changed phone number"
      }
    ],
    "total": 1
  }
}
```

### Cancel Request
**URL**: `DELETE /api/hris/profile/change-requests/{id}`

**Response (200)**:
```json
{
  "success": true,
  "message": "Request cancelled successfully"
}
```

### Report Incorrect Detail
**URL**: `POST /api/hris/profile/report-incorrect-detail`

**Request Body**:
```json
{
  "section": "personal",
  "field_name": "date_of_birth",
  "current_value": "1990-01-01",
  "reported_correct_value": "1990-01-15",
  "description": "My actual birth date is January 15th, not January 1st"
}
```

**Response (201)**:
```json
{
  "success": true,
  "message": "Report submitted successfully. HR will review and update.",
  "data": {
    "id": 1,
    "status": "pending"
  }
}
```

---

## Status

✅ **All endpoints tested and working!**

**Features Available**:
- Complete CRUD for all resources
- Hierarchical departments
- Position reporting structure
- Employee profile management
- **Financial details management**
- **Medical details management**
- **Address management (multiple per employee)**
- **Emergency contacts management**
- **Education records management**
- **Dependents management**
- Skills management
- Document upload and management
- Profile completeness tracking (auto-recalculates)
- Employee history tracking
- **Comprehensive audit trail (all changes logged)**
- **Soft deletes (data protection)**

**Next Steps**:
1. ~~Create migrations for work experience, certifications~~ ✅
2. ~~Enable relationships in EmployeeController~~ ✅
3. ~~Implement employee details CRUD~~ ✅
4. ~~Add audit trail system~~ ✅
5. Build frontend interface
6. Implement role & permissions
7. ~~Add reporting features (All Phases 1-4)~~ ✅
8. Implement advanced filtering (Date ranges)

---

## Reports API

### 1. Headcount Summary
**URL**: `GET /api/hris/reports/headcount-summary`
**Description**: Get overall headcount statistics.

### 2. Department Headcount
**URL**: `GET /api/hris/reports/department-headcount`
**Description**: Headcount breakdown by department.

### 3. Demographics
**URL**: `GET /api/hris/reports/demographics`
**Description**: Employee breakdown by age and gender.

### 4. Employment Report
**URL**: `GET /api/hris/reports/employment`
**Description**: Detailed list of employees with employment info.

### 5. New Hires
**URL**: `GET /api/hris/reports/new-hires?days=30`
**Description**: List of employees hired in the last N days with onboarding status.

### 6. Attrition & Turnover
**URL**: `GET /api/hris/reports/attrition`
**Description**: Monthly separation data and attrition rates.

### 7. Document Expiry
**URL**: `GET /api/hris/reports/document-expiry`
**Description**: Documents sorted by approaching expiry date.

### 8. Profile Completeness
**URL**: `GET /api/hris/reports/profile-completeness`
**Description**: Profile fill rate per employee.

### 9. Financials
**URL**: `GET /api/hris/reports/financials`
**Description**: Bank and tax details for all active employees.

### 10. Medical
**URL**: `GET /api/hris/reports/medical`
**Description**: Health info, blood groups, and allergies.

### 11. Contact
**URL**: `GET /api/hris/reports/contact`
**Description**: Personal contact details and primary emergency contacts.

### 12. Skills Inventory
**URL**: `GET /api/hris/reports/skills-inventory`
**Description**: Database of employee skills, proficiency, and experience levels.

### 13. Birthday & Anniversary
**URL**: `GET /api/hris/reports/birthday-anniversary`
**Description**: List of employee birthdays and work anniversaries for the current month.

### 14. Audit Trail
**URL**: `GET /api/hris/reports/audit-trail`
**Description**: Detailed log of all changes made to employee records.

### 15. Export Report
**URL**: `GET /api/hris/reports/{type}/export?format=csv`
**Description**: Export report data. Supported types: `headcount-summary`, `department-headcount`, `demographics`, `employment`, `new-hires`, `attrition`, `document-expiry`, `profile-completeness`, `financials`, `medical`, `contact`, `skills-inventory`, `birthday-anniversary`, `audit-trail`.

---


---

## Employee Number Format API

These endpoints allow administrators to configure how employee numbers are auto-generated.

### 1. Get Current Format
**URL**: `GET /employee-number-format`

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/employee-number-format" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "exists": true,
  "format": {
    "id": 1,
    "tenant_id": 1,
    "prefix": "STAFF",
    "include_year": true,
    "year_format": "YYYY",
    "include_month": false,
    "month_format": null,
    "separator": "/",
    "sequence_length": 3,
    "current_sequence": 5,
    "reset_sequence": "yearly",
    "sample_format": "STAFF/2026/001",
    "is_active": true
  },
  "preview": "STAFF/2026/006"
}
```

### 2. Update Format Configuration
**URL**: `PUT /employee-number-format`

**Description**: Updates the format for **NEW** employees created after this change. Existing employee numbers are NOT changed.

**Body**:
```json
{
  "format_name": "Standard Format",
  "prefix": "EMP",
  "include_year": true,
  "year_format": "YYYY",
  "include_month": true,
  "month_format": "MM",
  "separator": "-",
  "sequence_length": 4,
  "reset_sequence": "yearly"
}
```

**Example Response**:
```json
{
  "data": {
    "format": {
      "id": 1,
      "tenant_id": 1,
      "prefix": "EMP",
      "include_year": true,
      "year_format": "YYYY",
      "include_month": true,
      "month_format": "MM",
      "separator": "-",
      "sequence_length": 4,
      "reset_sequence": "yearly",
      "sample_format": "EMP-YYYY-MM-0000"
    },
    "preview": "EMP-2026-01-0001"
  },
  "message": "Format configuration updated successfully"
}
```

### 3. Preview Format
**URL**: `POST /employee-number-format/preview`

**Description**: Preview what the NEXT employee number would look like with these settings without saving.

**Body**:
```json
{
  "prefix": "TECH",
  "include_year": false,
  "include_month": false,
  "separator": "_",
  "sequence_length": 5
}
```

**Example Response**:
```json
{
  "preview": "TECH_00001"
}
```

### 4. Regenerate All Employee Numbers
**URL**: `POST /employee-number-format/regenerate`

**Description**: Manually updates ALL existing employee numbers to follow the current active format. This resets the sequence based on the original employee creation order.

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/hris/employee-number-format/regenerate" \
  -H "Authorization: Bearer $TOKEN"
```

**Example Response**:
```json
{
  "data": {
    "summary": {
      "success": true,
      "updated_count": 150,
      "last_sequence": 150
    }
  },
  "message": "All employee numbers have been regenerated"
}
```
