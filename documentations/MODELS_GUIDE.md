# HRMS Models & Seeders - Complete Guide

## ✅ Models Created (62 Total Models)

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

### Onboarding Models (1) 🆕

24. **EmployeeOnboardingStatus.php** - Employee onboarding progress tracking

### Approval Workflow Models (2) 🆕

25. **ProfileChangeRequest.php** - Employee self-service change requests
26. **IncorrectDetailReport.php** - Employee-reported data issues

### Leave Management Models (11) 🆕

58. **LeaveType.php** - Leave type definitions
59. **LeaveGroup.php** - Employee groups for policy assignment
60. **LeavePolicy.php** - Rules for leave types within groups
61. **LeaveWorkflow.php** - Multi-level approval chain definitions
62. **LeaveWorkflowLevel.php** - Specific levels in a workflow
63. **LeaveRequest.php** - Employee leave applications
64. **LeaveApproval.php** - Individual approval steps for a request
65. **LeaveBalance.php** - Employee leave entitlement and usage tracking
66. **LeaveAdjustment.php** - Manual corrections to leave balances
67. **LeaveYearEndProcessing.php** - Year-end rollover tracking
68. **LeaveAllowanceRequest.php** - Request for annual leave allowance payout 🆕

### Payroll Management Models (9) 🆕 ✨

36. **TaxScheme.php** - PAYE scheme configurations
37. **TaxBand.php** - Progressive tax brackets within a scheme
38. **SalaryComponent.php** - Master earnings and deductions list
39. **PayGroup.php** - Employee salary groupings and templates
40. **AnnualSalaryStructure.php** - Employee annual Gross-to-Net headers
41. **AnnualSalaryStructureItem.php** - Individual component values for annual structure
42. **BatchPayment.php** - Monthly payroll transaction headers
43. **MonthlyPayment.php** - Individual employee records in a batch
44. **MonthlyPaymentItem.php** - Monthly component breakdown for an employee
45. **WageItem.php** - Reusable salary package templates 🆕
46. **LeaveAllowanceRequest.php** - Annual leave allowance payout tracking 🆕
47. **PayrollReporting** - Comprehensive reporting suite for payroll data.

### Performance Management Models (17) 🆕 ✨

87. **PerformanceGoal.php** - High-level goal templates
88. **PerformanceObjective.php** - Strategic objectives under a goal
89. **PerformanceMeasureTarget.php** - Specific KPIs and metrics
90. **Competency.php** - Master list of employee competencies
91. **CompetencyRatingScale.php** - Rating definitions (1-5 scale)
92. **EmployeeDeliverable.php** - Assigned goals for a cycle
93. **EmployeeDeliverableDetail.php** - Individual targets in a deliverable
94. **Appraisal.php** - Specific appraisal process instance
95. **AppraisalSubmission.php** - Employee score submission header
96. **AppraisalLevelScore.php** - Summary scores per reviewer level
97. **AppraisalGoalScore.php** - Individual goal scores/comments
98. **AppraisalCompetencyScore.php** - Individual competency scores/comments
99. **AppraisalAttachment.php** - Evidence and attachments
100.    **AppraisalReviewerConfig.php** - Custom reviewer mapping per employee
101.    **AppraisalReporting** - Comprehensive reporting suite leveraging existing `AppraisalSubmission` and `AppraisalLevelScore` models. Includes a dedicated `AppraisalReportController` for generating cycle status, league tables, departmental comparisons, and competency gap reports.
102.    **AppraisalAnalytics** - Advanced visualization layer for real-time tracking of completion rates, bell curves, and performance trends.

### Request Management Models (5) 🆕 ✨

105.    **RequestWorkflow.php** - Approval path configuration
106.    **RequestWorkflowLevel.php** - Steps in an approval workflow
107.    **RequestTemplate.php** - Predefined and custom form templates
108.    **RequestSubmission.php** - Employee request submissions
109.    **RequestApproval.php** - Individual approval records

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
- `creator()` - User who created this record
- `updater()` - User who last updated this record

**Scopes:**

- `active()` - Active departments only
- `forTenant($id)` - Filter by tenant

---

### 4. Level Model

**Purpose:** Organizational hierarchy levels

**Key Relationships:**

- `positions()` - Positions at this level
- `employees()` - Employees at this level (hasManyThrough via positions)
- `creator()` - User who created this record
- `updater()` - User who last updated this record

**Scopes:**

- `active()` - Active levels only
- `forTenant($id)` - Filter by tenant
- `ordered()` - Order by rank

**Features:**

- Rank-based ordering
- Used for career progression

---

### 5. Grade Model

**Purpose:** Pay grades with salary ranges

**Key Relationships:**

- `positions()` - Positions in this grade
- `employees()` - Employees in this grade (hasManyThrough via positions)
- `creator()` - User who created this record
- `updater()` - User who last updated this record

**Scopes:**

- `active()` - Active grades only
- `forTenant($id)` - Filter by tenant
- `ordered()` - Order by rank

**Features:**

- Min/max salary ranges
- Rank-based ordering

---

### 6. Position Model

**Purpose:** Job positions in the organization

**Key Relationships:**

- `department()` - Department
- `level()` - Organizational level
- `grade()` - Pay grade
- `reportsTo()` - Position this reports to
- `subordinates()` - Positions reporting to this
- `employees()` - Employees in position (hasManyThrough)
- `creator()` - User who created this record
- `updater()` - User who last updated this record

**Scopes:**

- `active()` - Active positions only
- `forTenant($id)` - Filter by tenant

**Features:**

- Reporting structure
- Salary ranges
- Required qualifications

---

### 7. Employee Model

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
- `onboardingStatus()` - Onboarding status (hasOne) 🆕
- `profileChangeRequests()` - Change requests (hasMany) 🆕
- `incorrectDetailReports()` - Reported issues (hasMany) 🆕
- `creator()` - User who created this record
- `updater()` - User who last updated this record

**Computed Attributes:**

- `full_name` - Concatenated full name
- `photo_url` - Full URL to employee photo

**Scopes:**

- `active()` - Active employees only
- `forTenant($id)` - Filter by tenant

---

### 8. Skill Model ✨

**Purpose:** Master list of skills

**Key Relationships:**

- `employees()` - Employees with this skill (belongsToMany through employee_skills pivot)
    - Pivot fields: `proficiency_level`, `years_of_experience`, `last_used`, `is_certified`, `certification_name`, `certification_date`

**Scopes:**

- `active()` - Active skills only
- `forTenant($id)` - Filter by tenant

**Features:**

- Skill categorization
- Active/inactive status

**Usage:**

```php
$skill = Skill::find(1);
$employees = $skill->employees; // Get all employees with this skill
$employees = $skill->employees()->wherePivot('proficiency_level', 'expert')->get();
```

---

### 9. EmployeeEmploymentDetail Model

**Purpose:** Employment information and job details

**Key Relationships:**

- `employee()` - Employee
- `department()` - Department
- `position()` - Position
- `manager()` - Manager (Employee)

**Features:**

- Employment type and status tracking
- Probation period management
- Contract dates
- Termination details
- Work location and schedule
- Remote work eligibility

---

### 10. EmployeeSkill Model ✨

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

### 11. EmployeeWorkExperience Model ✨

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

### 12. EmployeeCertification Model ✨

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

### 13. DocumentType Model ✨

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

### 14. EmployeeDocument Model ✨

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

### 15. EmployeeHistory Model ✨

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

### 16. EmployeeProfileCompleteness Model ✨

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
- Work experience percentage
- Skills percentage
- Certifications percentage

---

### 17. EmployeeOnboardingStatus Model 🆕

**Purpose:** Track employee onboarding progress

**Key Relationships:**

- `employee()` - Employee

**Features:**

- User account creation tracking
- Welcome email status
- Password reset status
- First login tracking
- Profile completion status
- Onboarding completion tracking

**Tracked Milestones:**

- `user_created` - User account created
- `welcome_email_sent` - Welcome email sent
- `password_reset_sent` - Password reset link sent
- `first_login_completed` - Employee completed first login
- `profile_completed` - Employee completed profile
- `onboarding_completed` - Full onboarding process completed

**Usage:**

```php
$employee = Employee::find(1);
$status = $employee->onboardingStatus;
if (!$status->onboarding_completed) {
    // Send reminder
}
```

---

### 18. ProfileChangeRequest Model 🆕

**Purpose:** Employee self-service change requests with approval workflow

**Key Relationships:**

- `employee()` - Employee who submitted the request
- `reviewer()` - HR user who reviewed the request
- `tenant()` - Tenant this request belongs to

**Features:**

- Field-level change tracking
- Previous and new values (JSON)
- Status tracking (pending, approved, declined)
- Reason for change
- Reviewer comments
- Action tracking (update, add, delete)
- Temporary file upload support
- Reviewed timestamp

**Scopes:**

- `pending()` - Pending requests only
- `approved()` - Approved requests
- `declined()` - Declined requests

**Usage:**

```php
// Get all pending change requests
$pending = ProfileChangeRequest::pending()->get();

// Approve a request
$request->update([
    'status' => 'approved',
    'reviewed_by' => auth()->id(),
    'reviewed_at' => now(),
    'reviewer_comments' => 'Approved'
]);
```

---

### 19. IncorrectDetailReport Model 🆕

**Purpose:** Employee-reported data issues for HR review

**Key Relationships:**

- `employee()` - Employee who reported the issue
- `resolver()` - HR user who resolved the issue
- `tenant()` - Tenant this report belongs to

**Features:**

- Field name tracking
- Current incorrect value
- Suggested correct value
- Employee explanation
- Status tracking (pending, resolved, dismissed)
- Resolver notes
- Resolution timestamp

**Scopes:**

- `pending()` - Pending reports only
- `resolved()` - Resolved reports
- `dismissed()` - Dismissed reports

**Usage:**

```php
// Get all pending incorrect detail reports
$pending = IncorrectDetailReport::pending()->get();

// Resolve a report
$report->update([
    'status' => 'resolved',
    'resolved_by' => auth()->id(),
    'resolved_at' => now(),
    'resolver_notes' => 'Corrected in system'
]);
```

---

### 20. Leave Models (Leave Management) 🆕

#### LeaveType Model

**Purpose:** Global definition of leave types (Annual, Sick, etc.)
**Relationships:** `policies()`, `requests()`, `balances()`

#### LeaveGroup Model

**Purpose:** Groups of employees for policy assignment.
**Relationships:** `policies()`, `employmentDetails()`

#### LeavePolicy Model

**Purpose:** Technical rules governing a leave type for a specific group.
**Relationships:** `leaveType()`, `leaveGroup()`, `workflow()`
**Features:** Entitlement, carry-forward, negative balance, and notice rules.

#### LeaveWorkflow Models

**Purpose:** Multi-level approval chain configuration.
**LeaveWorkflow**: Header record.
**LeaveWorkflowLevel**: Individual steps (`approver_type`: manager, hr, etc.)

#### LeaveRequest Model

**Purpose:** Core transaction record for an employee taking time off.
**Relationships:** `employee()`, `leaveType()`, `approvals()`, `canceller()` (User), `leaveAllowanceRequest()` (hasOne)
**Computed Attributes:** `attachment_url`
**Casts:** `start_date` (date), `end_date` (date), `duration_days` (decimal), `applied_at` (datetime), `cancelled_at` (datetime), `request_leave_allowance` (boolean)
**Statuses:** `pending`, `approved`, `declined`, `cancelled`, `partially_cancelled`

#### LeaveApproval Model

**Purpose:** Tracks each level of the approval process for a request.
**Relationships:** `leaveRequest()`, `approver()` (User)

#### LeaveBalance Model

**Purpose:** Tracks total entitlement, usage, and available days per year.
**Relationships:** `employee()`, `leaveType()`
**Computed:** `available_balance` (Entitlement + CF + Accrued + Adjustment - Used - Pending)

#### LeaveAdjustment Model

**Purpose:** Audit trail for manual balance corrections by HR/Admin.
**Relationships:** `employee()`, `leaveType()`, `adjuster()` (User)

#### LeaveYearEndProcessing Model 🆕

**Purpose:** Track year-end rollover processing to prevent duplicate execution.
**Relationships:** `tenant()`, `processedBy()` (User)
**Features:**

- Tracks which leave years have been processed for each tenant
- Records processing timestamp and who triggered it
- Stores summary statistics (employees processed, etc.)
- Unique constraint prevents duplicate processing for the same year
- Provides audit trail for year-end operations

**Usage:**

```php
// Check if year-end has been processed
$processed = LeaveYearEndProcessing::where('tenant_id', $tenantId)
    ->where('from_year', 2026)
    ->exists();

// Get processing details
$processing = LeaveYearEndProcessing::where('tenant_id', $tenantId)
    ->where('from_year', 2026)
    ->with('processedBy')
    ->first();
```

---

### 21. Payroll Models (Payroll Management) 🆕 ✨

#### TaxScheme & TaxBand Models

**Purpose:** Define progressive tax rules (PAYE) and statutory pension percentages.
**Relationships:** `bands()`, `payGroups()`
**Features:**

- Configurable Employee/Employer pension % (calculated on gross).
- Multi-tier progressive tax brackets.

#### SalaryComponent Model

**Purpose:** Master list of all earnings and deductions.
**Relationships:** `payGroups()`
**Features:**

- Categorization (fixed, variable, statutory).
- Taxability and deductibility flags.
- Formula support for future engine extensions.

#### PayGroup Model

**Purpose:** Groups employees by annual gross range and shared salary components.
**Relationships:** `taxScheme()`, `components()`, `employees()`, `annualStructures()`
**Use Case:** Assigning 10 developers to the same 5 earnings components simultaneously.

#### WageItem Model 🆕

**Purpose:** Defines reusable salary package templates that can be assigned to Pay Groups.
**Relationships:** `components()` (WageItemComponent), `payGroups()`
**Features:**

- `has_leave_allowance`: Special flag to indicate if this package is eligible for annual leave allowance.
- Links multiple `SalaryComponent` models to a single item name (e.g., "Senior Management Pack").

#### AnnualSalaryStructure & Items

**Purpose:** Stores the Gross-to-Net breakdown for a specific employee.
**Header fields:** `total_annual_gross`, `total_annual_tax`, `total_annual_pension`, `total_annual_net`.
**Item fields:**

- `frequency`: `monthly` or `annual`.
- `payment_month`: `1-12`, `anniversary`, or `birthday`.
- `amount`: Annualized value.

#### BatchPayment, MonthlyPayment & Items

**Purpose:** Monthly payroll transactions.
**BatchPayment Statuses:** `draft`, `authorized`, `cancelled`.
**MonthlyPayment Features:**

- `calculateGrossToNet()`: Logic to subtract tax, pension, and other deductions.
- `items()`: Breakdown of earnings and deductions for that specific month.

#### LeaveAllowanceRequest Model 🆕

**Purpose:** Tracks requests for leave allowance payout made during leave booking.
**Statuses:** `pending`, `approved`, `declined`, `paid`.
**Relationships:** `employee()`, `leaveRequest()`, `batchPayment()`, `monthlyPayment()`
**Flow:** Employee books leave -> Selects "Request Allowance" -> HR Approves -> System includes it in the next Payroll Batch for that employee.

#### Payroll Analytics & Reporting Suite

---

### 22. Request Models (Request Management) 🆕 ✨

#### RequestWorkflow Model

**Purpose:** Header record for multi-level approval paths.
**Relationships:** `levels()` (hasMany), `templates()` (hasMany)
**Features:** Active/Inactive status, tenant-scoped.

#### RequestWorkflowLevel Model

**Purpose:** Individual steps within an approval workflow.
**Relationships:** `workflow()` (belongsTo)
**Features:** `approver_type` (manager, hr, team_lead, etc.), `approver_id` for specific employees.

#### RequestTemplate Model

**Purpose:** Defines the structure of requests (Cash, Expense, etc.) or custom-built forms.
**Relationships:** `tenant()`, `workflow()`, `submissions()`, `creator()`
**Casts:** `fields` (json), `is_active` (boolean)
**Features:** Supports predefined system templates via `template_key` and custom form fields via a dynamic JSON schema.

#### RequestSubmission Model

**Purpose:** Core record for an employee's request.
**Relationships:** `employee()`, `template()`, `approvals()`, `tenant()`
**Casts:** `form_data` (json), `submitted_at` (datetime), `completed_at` (datetime)
**Statuses:** `pending`, `approved`, `declined`, `cancelled`
**Features:** Tracks the `current_level` of approval and generates unique `reference_number`.

#### RequestApproval Model

**Purpose:** Individual approval/decline actions for a submission.
**Relationships:** `submission()`, `approver()` (User)
**Statuses:** `pending`, `approved`, `declined`
**Features:** Captures reviewer comments and timestamps for each level of the chain.

**Analytics**: Virtual module providing cost trends, component breakdowns, and departmental expenditure data via consolidated API endpoints.
**Reporting**: Dedicated suite for generating statutory compliance, variance reports, and annual audits.

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

### 22. Performance Management Models 🆕 ✨

#### PerformanceSetting Model

**Purpose:** Global configuration for weightages and workflow levels.
**Fields:** `results_weight`, `competency_weight`, `reviewer_levels`, `goal_structure`, `reviewer_config` (JSON).

> [!NOTE]
> **Dynamic Reviewer Workflow**: The `reviewer_config` column stores a JSON array defining the reviewer type for each level (e.g., `manager`, `system_hr`, `team_lead`).

#### PerformanceGoal, Objective & Measure Models

**Purpose:** Hierarchical goal setting structure.
**PerformanceGoal**: Parent goal (e.g., "Revenue Growth").
**PerformanceObjective**: Specific focuses (e.g., "Direct Sales").
**PerformanceMeasureTarget**: Actual metrics (e.g., "$1M target").

#### Competency Model

**Purpose:** Professional behaviors and skills tracked organization-wide.
**Relationships:** `ratingScales()`, `appraisalScores()`

#### EmployeeDeliverable Model

**Purpose:** Links activated goals to a specific employee and cycle.
**Flow:** Assigned by Admin -> Activated by Manager/Admin -> Appears in Appraisal.

#### AppraisalSubmission & Scoring Models

**Purpose:** The core evaluation records.
**AppraisalSubmission**: Header for the employee's appraisal. Includes **Snapshots** (`reviewer_config`, `results_weight`, `competency_weight`) to preserve settings at the time of activation or custom override.
**AppraisalLevelScore**: Tracks progress through evaluator levels. Stores both raw percentage and **Weighted Scores** (`goals_weighted_score`, `competency_weighted_score`). Supports `'SYSTEM_HR'` as a wildcard `reviewer_id`.
**Goal/Comp Scores**: Granular values and comments for every item.

#### AppraisalAttachment Model

**Purpose**: Evidence and supporting documents for appraisals.
**Fields**: `file_url`, `storage_driver`, `original_filename`.

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

**Total Models: 62** (Updated February 2026)

- ✅ Authentication & Multi-Tenant (2 models)
- ✅ Core HRIS (4 models)
- ✅ Employee Core (1 model)
- ✅ Employee Details (8 models)
- ✅ Skills & Qualifications (4 models)
- ✅ Document Management (2 models)
- ✅ Tracking (2 models)
- ✅ Onboarding (1 model) 🆕
- ✅ Approval Workflow (2 models) 🆕
- ✅ Leave Management (11 models) 🆕
- ✅ Payroll Management (9 models) 🆕 ✨
- ✅ Performance Management (17 models) 🆕 ✨

**All models include:**

- ✅ Mass assignment protection (`$fillable`)
- ✅ Type casting (`$casts`)
- ✅ Relationships (belongsTo, hasMany, etc.)
- ✅ Scopes (active, forTenant, etc.)
- ✅ Soft deletes (where applicable)
- ✅ Multi-tenant support via `tenant_id` 🆕

**Status:** ✅ All HRIS models complete and ready to use! 🎉
