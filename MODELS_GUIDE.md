# HRMS Models & Seeders - Complete Guide

## ✅ Models Created (23 Total Models)

### Authentication & Multi-Tenant Models (2) 🆕
1. **Tenant.php** - Multi-tenant organization management
2. **User.php** - User authentication (updated with tenant support)

### Core Organizational Models (4)
3. **Department.php** - Hierarchical department structure
4. **Level.php** - Organizational levels (Junior, Senior, etc.)
5. **Grade.php** - Pay grades with salary ranges
6. **Position.php** - Job positions

### Employee Core Model (1)
7. **Employee.php** - Main employee record

### Employee Detail Models (8)
8. **EmployeeEmploymentDetail.php** - Employment information
9. **EmployeeContactDetail.php** - Contact information
10. **EmployeeFinancialDetail.php** - Banking & salary
11. **EmployeeMedicalDetail.php** - Health information
12. **EmployeeAddress.php** - Addresses
13. **EmployeeEmergencyContact.php** - Emergency contacts
14. **EmployeeDependent.php** - Dependents/family
15. **EmployeeEducation.php** - Educational qualifications

### Skills & Qualifications Models (4) ✨
16. **Skill.php** - Master skills list
17. **EmployeeSkill.php** - Employee skills with proficiency
18. **EmployeeWorkExperience.php** - Work history
19. **EmployeeCertification.php** - Professional certifications

### Document Management Models (2) ✨
20. **DocumentType.php** - Document type definitions
21. **EmployeeDocument.php** - Employee documents with file storage

### Tracking Models (2) ✨
22. **EmployeeHistory.php** - Change tracking
23. **EmployeeProfileCompleteness.php** - Profile completion tracking

---

## 📦 Database Seeder

**File:** `database/seeders/DatabaseSeeder.php`

**What it creates:**
- 2 users (admin & employee)
- 4 departments (IT, HR, Finance, Development)
- 4 levels (Junior, Mid, Senior, Lead)
- 3 grades (G1, G2, G3) with salary ranges
- 2 positions (Software Developer, HR Manager)
- 1 sample employee with employment & contact details

### Run the Seeder

```bash
# Run all seeders
php artisan db:seed

# Fresh migration with seed
php artisan migrate:fresh --seed
```

### Test Credentials

**Admin:**
- Email: `admin@hrms.local`
- Password: `password`

**Employee:**
- Email: `john.doe@hrms.local`
- Password: `password`

---

## 🔍 Model Details

### 1. Tenant Model 🆕
**Purpose:** Multi-tenant organization management

**Key Relationships:**
- `users()` - Users in this tenant
- `employees()` - Employees in this tenant
- `departments()` - Departments in this tenant
- `positions()` - Positions in this tenant
- `levels()` - Levels in this tenant
- `grades()` - Grades in this tenant

**Scopes:**
- `active()` - Active tenants only

**Features:**
- Tenant isolation for all data
- Custom domain support
- Tenant-specific settings (JSON)

---

### 2. User Model 🆕
**Purpose:** User authentication with multi-tenant support

**Key Relationships:**
- `tenant()` - Tenant this user belongs to
- `employee()` - Associated employee record

**Features:**
- Laravel Sanctum token authentication
- Custom `createToken()` method that includes `tenant_id`
- Tokens are scoped to specific tenants

**Important:** Users must provide `tenant_id` when logging in. The token will be scoped to that tenant.

---

### 3. Department Model
**Purpose:** Hierarchical organizational structure

**Key Relationships:**
- `parent()` - Parent department
- `children()` - Child departments
- `manager()` - Department manager (Employee)
- `employees()` - Employees in department (hasManyThrough)
- `positions()` - Positions in department

**Scopes:**
- `active()` - Active departments only
- `forTenant($id)` - Filter by tenant

---

### 4. Level Model
**Purpose:** Organizational hierarchy levels

**Key Relationships:**
- `positions()` - Positions at this level

**Scopes:**
- `active()` - Active levels only
- `forTenant($id)` - Filter by tenant

**Features:**
- Rank-based ordering
- Used for career progression

---

### 3. Grade Model
**Purpose:** Pay grades with salary ranges

**Key Relationships:**
- `positions()` - Positions in this grade

**Scopes:**
- `active()` - Active grades only
- `forTenant($id)` - Filter by tenant

**Features:**
- Min/max salary ranges
- Rank-based ordering

---

### 4. Position Model
**Purpose:** Job positions in the organization

**Key Relationships:**
- `department()` - Department
- `level()` - Organizational level
- `grade()` - Pay grade
- `reportsTo()` - Position this reports to
- `subordinates()` - Positions reporting to this
- `employees()` - Employees in position (hasManyThrough)

**Features:**
- Reporting structure
- Salary ranges
- Required qualifications

---

### 5. Employee Model
**Purpose:** Core employee information

**Key Relationships:**
- `user()` - Associated user account
- `employmentDetails()` - Employment info (hasOne)
- `contactDetails()` - Contact info (hasOne)
- `financialDetails()` - Financial info (hasOne)
- `medicalDetails()` - Medical info (hasOne)
- `addresses()` - Addresses (hasMany)
- `emergencyContacts()` - Emergency contacts (hasMany)
- `dependents()` - Dependents (hasMany)
- `education()` - Education records (hasMany)
- `workExperience()` - Work history (hasMany) ✨
- `skills()` - Skills (hasMany) ✨
- `certifications()` - Certifications (hasMany) ✨
- `documents()` - Documents (hasMany) ✨
- `history()` - Change history (hasMany) ✨
- `profileCompleteness()` - Profile completion (hasOne) ✨

**Computed Attributes:**
- `full_name` - Concatenated full name

**Scopes:**
- `active()` - Active employees only
- `forTenant($id)` - Filter by tenant

---

### 14. Skill Model ✨ **NEW**
**Purpose:** Master list of skills

**Key Relationships:**
- `employees()` - Employees with this skill (belongsToMany)

**Features:**
- Skill categorization
- Active/inactive status

**Usage:**
```php
$skill = Skill::find(1);
$employees = $skill->employees; // Get all employees with this skill
```

---

### 15. EmployeeSkill Model ✨ **NEW**
**Purpose:** Employee skills with proficiency tracking

**Key Relationships:**
- `employee()` - Employee
- `skill()` - Skill

**Features:**
- Proficiency levels (beginner, intermediate, advanced, expert)
- Years of experience
- Certification tracking
- Last used date

---

### 16. EmployeeWorkExperience Model ✨ **NEW**
**Purpose:** Employee work history

**Computed Attributes:**
- `is_current` - Check if current employment
- `duration_in_months` - Calculate duration

**Features:**
- Company and position tracking
- Start and end dates
- Responsibilities
- Reason for leaving

---

### 17. EmployeeCertification Model ✨ **NEW**
**Purpose:** Professional certifications

**Computed Attributes:**
- `is_expired` - Check if expired

**Scopes:**
- `expired()` - Get expired certifications
- `valid()` - Get valid certifications

**Features:**
- Issue and expiry dates
- Credential ID and URL
- Issuing organization

---

### 18. DocumentType Model ✨ **NEW**
**Purpose:** Document type definitions

**Key Relationships:**
- `documents()` - Documents of this type

**Scopes:**
- `active()` - Active document types
- `required()` - Required documents

**Features:**
- Required/optional flags
- Expiry tracking
- Allowed extensions
- File size limits

---

### 19. EmployeeDocument Model ✨ **NEW**
**Purpose:** Employee document storage

**Key Relationships:**
- `employee()` - Employee
- `documentType()` - Document type
- `uploader()` - User who uploaded

**Scopes:**
- `expired()` - Expired documents
- `expiringSoon($days)` - Expiring soon

**Features:**
- File storage integration
- Issue and expiry dates
- File size and MIME type tracking

---

### 20. EmployeeHistory Model ✨ **NEW**
**Purpose:** Track employee changes

**Key Relationships:**
- `employee()` - Employee
- `approver()` - User who approved
- `creator()` - User who created

**Scopes:**
- `ofType($type)` - Filter by change type

**Features:**
- Change type tracking
- Previous and new values (JSON)
- Effective date
- Approval tracking

---

### 21. EmployeeProfileCompleteness Model ✨ **NEW**
**Purpose:** Track profile completion

**Key Relationships:**
- `employee()` - Employee

**Computed Attributes:**
- `is_complete` - Check if 100% complete

**Scopes:**
- `incomplete()` - Incomplete profiles
- `belowThreshold($threshold)` - Below threshold

**Tracked Sections:**
- Overall percentage
- Basic info percentage
- Employment info percentage
- Contact info percentage
- Financial info percentage
- Medical info percentage
- Education percentage
- Documents percentage

---

## 🧪 Testing with Tinker

```bash
# Start Tinker
php artisan tinker

# Test employee relationships
$employee = App\Models\Employee::first();
$employee->employmentDetails;
$employee->contactDetails;
$employee->skills; // ✨ NEW
$employee->documents; // ✨ NEW
$employee->profileCompleteness; // ✨ NEW

# Test department hierarchy
$dept = App\Models\Department::first();
$dept->children;
$dept->employees;

# Test scopes
App\Models\Department::active()->get();
App\Models\Employee::forTenant(1)->get();
App\Models\EmployeeCertification::valid()->get(); // ✨ NEW
```

---

## 📊 Database Structure

```
employees (main table)
├── employee_employment_details (1:1)
├── employee_contact_details (1:1)
├── employee_financial_details (1:1)
├── employee_medical_details (1:1)
├── employee_addresses (1:many)
├── employee_emergency_contacts (1:many)
├── employee_dependents (1:many)
├── employee_education (1:many)
├── employee_work_experience (1:many) ✨ NEW
├── employee_skills (1:many) ✨ NEW
├── employee_certifications (1:many) ✨ NEW
├── employee_documents (1:many) ✨ NEW
├── employee_history (1:many) ✨ NEW
└── employee_profile_completeness (1:1) ✨ NEW

departments (hierarchical)
├── parent (self-referencing)
├── children (self-referencing)
├── manager (employee)
└── positions (1:many)

positions
├── department
├── level
├── grade
└── employees (1:many via employment_details)
```

---

## ⚡ Quick Commands

```bash
# Create a new model
php artisan make:model ModelName

# Create model with migration
php artisan make:model ModelName -m

# List all models
php artisan model:show

# Clear cache
php artisan cache:clear
php artisan config:clear
```

---

## 🎯 Summary

**Total Models: 23** (Updated December 2025)
- ✅ Authentication & Multi-Tenant (2 models) 🆕
- ✅ Core HRIS (4 models)
- ✅ Employee Core (1 model)
- ✅ Employee Details (8 models)
- ✅ Skills & Qualifications (4 models)
- ✅ Document Management (2 models)
- ✅ Tracking (2 models)

**All models include:**
- ✅ Mass assignment protection (`$fillable`)
- ✅ Type casting (`$casts`)
- ✅ Relationships (belongsTo, hasMany, etc.)
- ✅ Scopes (active, forTenant, etc.)
- ✅ Soft deletes (where applicable)
- ✅ Multi-tenant support via `tenant_id` 🆕

**Status:** ✅ All HRIS models complete and ready to use! 🎉

