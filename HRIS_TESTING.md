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
2. [Departments API](#departments-api)
3. [Levels API](#levels-api)
4. [Grades API](#grades-api)
5. [Positions API](#positions-api)
6. [Employees API](#employees-api)
7. [Financial Details API](#financial-details-api)
8. [Medical Details API](#medical-details-api)
9. [Addresses API](#addresses-api)
10. [Emergency Contacts API](#emergency-contacts-api)
11. [Education API](#education-api)
12. [Dependents API](#dependents-api)
13. [Skills API](#skills-api)
14. [Documents API](#documents-api)
15. [Work Experience API](#work-experience-api)
16. [Certifications API](#certifications-api)
17. [Employee History API](#employee-history-api)
18. [Profile Completeness API](#profile-completeness-api)
19. [Audit Logs API](#audit-logs-api)
20. [Notifications API](#notifications-api)
21. [Testing Checklist](#testing-checklist)
22. [Common Test Scenarios](#common-test-scenarios)

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

## Departments API

### 1. Get All Departments
**URL**: `GET /departments`

**Query Parameters**:
- `search` (optional): Search in code, name, description
- `is_active` (optional): Filter by active status (true/false)
- `parent_id` (optional): Filter by parent department

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

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/levels" \
  -H "Authorization: Bearer $TOKEN"
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

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/hris/grades" \
  -H "Authorization: Bearer $TOKEN"
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

**Example Request**:
```bash
# Get all positions
curl -X GET "http://localhost:8000/api/hris/positions" \
  -H "Authorization: Bearer $TOKEN"

# Get positions by department
curl -X GET "http://localhost:8000/api/hris/positions?department_id=1" \
  -H "Authorization: Bearer $TOKEN"
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
- `department_id` (optional): Filter by department
- `is_active` (optional): Filter by active status
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

**Required Fields**:
- `tenant_id` - Tenant ID
- `employee_number` - Unique employee number
- `first_name` - Employee's first name
- `last_name` - Employee's last name
- **`email`** - Employee's email (must be unique, used for login)

**Body**:
```json
{
  "tenant_id": 1,
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

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/hris/employees" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
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

**Example Response**:
```json
{
  "success": true,
  "message": "Employee and user account created successfully",
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
      }
    },
    "temporary_password": "Temp1234!",
    "note": "Please share the temporary password with the employee. They should change it on first login."
  }
}
```

> [!NOTE]
> The temporary password is returned in the response. Make sure to securely share it with the employee. They will need to change it on first login.

### 4. Update Employee
**URL**: `PUT /employees/{id}`

### 5. Delete Employee
**URL**: `DELETE /employees/{id}`

### 6. Employment Details API

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
  "payment_method": "bank_transfer"
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
- `payment_method`: Optional, one of: `bank_transfer`, `cash`, `cheque`

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

### Get Employee Work Experience
**URL**: `GET /employees/{id}` (included in employee response)

Work experience is loaded as part of the employee details when you fetch a single employee.

**Example Response** (partial):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "workExperience": [
      {
        "id": 1,
        "employee_id": 1,
        "company_name": "Tech Corp",
        "job_title": "Senior Developer",
        "industry": "Technology",
        "start_date": "2018-01-01",
        "end_date": "2022-12-31",
        "is_current": false,
        "responsibilities": "Led development team, code reviews",
        "achievements": "Increased team productivity by 40%",
        "reason_for_leaving": "Career advancement",
        "supervisor_name": "Jane Smith",
        "supervisor_contact": "jane@techcorp.com"
      }
    ]
  }
}
```

**Note**: Work experience management endpoints (create, update, delete) are accessed through the employee endpoint. The relationship is automatically loaded when fetching employee details.

---

## Certifications API

### Get Employee Certifications
**URL**: `GET /employees/{id}` (included in employee response)

Certifications are loaded as part of the employee details when you fetch a single employee.

**Example Response** (partial):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "certifications": [
      {
        "id": 1,
        "employee_id": 1,
        "certification_name": "AWS Certified Solutions Architect",
        "issuing_organization": "Amazon Web Services",
        "certification_number": "AWS-SA-12345",
        "issue_date": "2023-01-15",
        "expiry_date": "2026-01-15",
        "does_not_expire": false,
        "verification_url": "https://aws.amazon.com/verification/12345",
        "description": "Professional level AWS certification"
      }
    ]
  }
}
```

**Note**: Certification management endpoints (create, update, delete) are accessed through the employee endpoint. The relationship is automatically loaded when fetching employee details.

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
        "position_id": 1,
        "grade_id": 1,
        "position_title": "Junior Developer"
      },
      "new_value": {
        "position_id": 2,
        "grade_id": 2,
        "position_title": "Senior Developer"
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
        "basic_salary": 50000
      },
      "new_value": {
        "basic_salary": 75000
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

**Note**: Employee history is automatically tracked for major changes. This endpoint provides a complete audit trail of employee career progression.

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

### New Features Tests (Option 1)
- [ ] Test work experience relationship loading
- [ ] Test skills relationship loading
- [ ] Test certifications relationship loading
- [ ] Test documents relationship loading
- [ ] Test employee history endpoint
- [ ] Test profile completeness endpoint
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
# 4. Upload documents
# 5. Check profile completeness
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

**Total Endpoints**: 40+

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

**Address Types**: `home`, `work`, `mailing`

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
7. Add reporting features

---

**Happy Testing!** 🚀
