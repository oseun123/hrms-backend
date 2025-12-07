# HRMS API - Laravel Migrations

This directory contains all Laravel database migrations for the HRMS (Human Resource Management System) module.

## Overview

**Total Migrations:** 37 tables
**Database:** MySQL/PostgreSQL
**Laravel Version:** 10+ (PHP 8.1+) or Laravel 8.x (PHP 7.4)

## Migration Files

### Core Organizational Tables (1-4)
1. `2024_01_01_000001_create_departments_table.php` - Hierarchical department structure
2. `2024_01_01_000002_create_levels_table.php` - Organizational levels (Junior, Senior, etc.)
3. `2024_01_01_000003_create_grades_table.php` - Pay grades with salary ranges
4. `2024_01_01_000004_create_positions_table.php` - Job positions with level/grade references

### Employee Core & Details (5-9)
5. `2024_01_01_000005_create_employees_table.php` - Core employee information
6. `2024_01_01_000006_create_employee_employment_details_table.php` - Employment specifics
7. `2024_01_01_000007_create_employee_contact_details_table.php` - Contact information
8. `2024_01_01_000008_create_employee_financial_details_table.php` - Banking & compensation
9. `2024_01_01_000009_create_employee_medical_details_table.php` - Health information

### Personal Information (10-12)
10. `2024_01_01_000010_create_employee_addresses_table.php` - Multiple address types
11. `2024_01_01_000011_create_employee_emergency_contacts_table.php` - Emergency contacts
12. `2024_01_01_000012_create_employee_dependents_table.php` - Family/dependents

### Qualifications (13-17)
13. `2024_01_01_000013_create_employee_education_table.php` - Educational background
14. `2024_01_01_000014_create_employee_work_experience_table.php` - Work history
15. `2024_01_01_000015_create_skills_table.php` - Skills master data
16. `2024_01_01_000016_create_employee_skills_table.php` - Employee skills with proficiency
17. `2024_01_01_000017_create_employee_certifications_table.php` - Professional certifications

### Document Management (18-19)
18. `2024_01_01_000018_create_document_types_table.php` - Configurable document types
19. `2024_01_01_000019_create_employee_documents_table.php` - Document storage with expiry

### History & Customization (20-22)
20. `2024_01_01_000020_create_employee_history_table.php` - Track all employee changes
21. `2024_01_01_000021_create_custom_fields_table.php` - Dynamic field definitions
22. `2024_01_01_000022_create_employee_custom_fields_table.php` - Custom field values

### Employee Number System (23-24)
23. `2024_01_01_000023_create_employee_number_formats_table.php` - Configurable formats (STAFF/2025/001)
24. `2024_01_01_000024_create_employee_number_sequences_table.php` - Sequence tracking

### Approval System (25-29)
25. `2024_01_01_000025_create_approval_workflows_table.php` - Workflow definitions
26. `2024_01_01_000026_create_approval_levels_table.php` - Multi-level approval hierarchy
27. `2024_01_01_000027_create_pending_approvals_table.php` - Approval requests with JSON data
28. `2024_01_01_000028_create_approval_actions_table.php` - Individual approval actions
29. `2024_01_01_000029_create_approval_notifications_table.php` - Approval notifications

### Authentication & Onboarding (30-31)
30. `2024_01_01_000030_create_password_reset_tokens_table.php` - Secure password resets
31. `2024_01_01_000031_create_employee_onboarding_status_table.php` - Onboarding progress

### Profile Completeness (32-37)
32. `2024_01_01_000032_create_profile_sections_table.php` - Configurable sections
33. `2024_01_01_000033_create_profile_fields_table.php` - Field definitions
34. `2024_01_01_000034_create_employee_profile_completeness_table.php` - Completion tracking
35. `2024_01_01_000035_create_profile_completion_notifications_table.php` - Nudges & reminders
36. `2024_01_01_000036_create_profile_completion_history_table.php` - Historical tracking
37. `2024_01_01_000037_create_profile_completion_rules_table.php` - Notification rules

## Running Migrations

### Prerequisites
1. Configure your `.env` file with database credentials
2. Ensure you have a `users` table (Laravel default or custom)

### Run All Migrations
```bash
php artisan migrate
```

### Run Specific Migration
```bash
php artisan migrate --path=/database/migrations/2024_01_01_000001_create_departments_table.php
```

### Rollback Migrations
```bash
php artisan migrate:rollback
```

### Fresh Migration (Drop all tables and re-migrate)
```bash
php artisan migrate:fresh
```

## Key Features

### Multi-Tenancy Support
- Every table includes `tenant_id` for data isolation
- Supports multiple organizations in single database

### Soft Deletes
- Most tables include `deleted_at` for data recovery
- Records are never permanently deleted

### Audit Trail
- `created_by`, `updated_by` fields track who made changes
- `created_at`, `updated_at` timestamps on all tables

### Foreign Key Constraints
- Proper relationships between tables
- Cascade deletes where appropriate
- Restrict deletes for critical references

### Indexes
- Strategic indexes on frequently queried columns
- Composite indexes for complex queries
- Unique constraints where needed

## Database Schema Highlights

### Employee Number Generation
- Configurable format: `STAFF/2025/001`
- Auto-incrementing with year/month support
- Reset options: never, yearly, monthly

### Approval System
- Multi-level approval workflows
- JSON storage for current and proposed data
- Complete audit trail of all approvals
- Delegation and escalation support

### Profile Completeness
- Weighted completion calculation
- Section-based tracking
- Automated notifications and nudges
- Historical tracking of changes

### Employee Onboarding
- Track welcome email status
- Password reset token management
- First login tracking
- Profile completion monitoring

## Next Steps

1. **Create Models** - Laravel Eloquent models for each table
2. **Seeders** - Default data (roles, permissions, document types, etc.)
3. **Factories** - Test data generation
4. **API Controllers** - RESTful endpoints
5. **Validation** - Request validation rules
6. **Multi-Tenancy** - Implement tenant scoping middleware

## Notes

- All ENUM fields can be extended as needed
- JSON fields in `pending_approvals` store complete change history
- Foreign keys reference `users` table (ensure it exists first)
- Decimal fields use (15,2) for currency, (5,2) for percentages

## Support

For questions or issues with migrations, refer to:
- Database schema documentation: `hris-database-schema.md`
- Implementation plan: `implementation_plan.md`
