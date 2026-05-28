# Payroll API - Complete Testing Reference

> [!IMPORTANT]
> **Multi-Tenant Authentication**: `tenant_id` is automatically injected from your authentication token. Do not include it in request bodies.

Base URL: `http://localhost:8000/api/payroll`

**Authentication**: All requests require Bearer token in header:

```
Authorization: Bearer {your-token-here}
```

---

## Table of Contents

1. [Setup - Tax Schemes & Bands](#1-setup---tax-schemes--bands)
2. [Setup - Salary Components](#2-setup---salary-components)
3. [Setup - Wage Items](#3-setup---wage-items)
4. [Pay Groups & Employee Assignment](#4-pay-groups--employee-assignment)
5. [Annual Salary Structures](#5-annual-salary-structures)
6. [Monthly Batch Processing](#6-monthly-batch-processing)
7. [Leave Allowance Management](#7-leave-allowance-management)
8. [Employee Payslips](#8-employee-payslips)
9. [Payroll Analytics & Reporting](#9-payroll-analytics--reporting)
10. [Testing Workflow](#testing-workflow)

---

## 1. Setup - Tax Schemes & Bands

**Required Permission**: `payroll.setup`

### List Tax Schemes

**URL**: `GET /setup/tax-schemes`
**Purpose**: View all configured PAYE schemes and their bands.

### Create Tax Scheme

**URL**: `POST /setup/tax-schemes`
**Body**:

```json
{
    "name": "Nigeria PAYE 2024",
    "description": "Statutory PAYE for Nigeria",
    "employee_pension_percentage": 8.0,
    "employer_pension_percentage": 10.0,
    "bands": [
        {
            "lower_limit": 0,
            "upper_limit": 300000,
            "rate_percentage": 7,
            "flat_amount": 0
        }
    ]
}
```

### Update Tax Scheme

**URL**: `PUT /setup/tax-schemes/{id}`

### Delete Tax Scheme

**URL**: `DELETE /setup/tax-schemes/{id}`

---

## 2. Setup - Salary Components

**Required Permission**: `payroll.setup`

### List Components

**URL**: `GET /components`

### Create Component

**URL**: `POST /components`
**Body**:

```json
{
    "name": "Annual Basic",
    "code": "BASIC",
    "type": "earning",
    "category": "fixed",
    "is_taxable": true,
    "calculation_type": "fixed",
    "amount_value": 3000000
}
```

---

## 3. Setup - Wage Items

**Required Permission**: `payroll.setup`
**Purpose**: Reusable salary package templates containing component combinations.

### List Wage Items

**URL**: `GET /wage-items`

### Create Wage Item

**URL**: `POST /wage-items`
**Body**:

```json
{
    "name": "Standard Tech Package",
    "description": "Standard components for developers",
    "is_active": true,
    "has_leave_allowance": true,
    "component_ids": [1, 2, 5]
}
```

---

## 4. Pay Groups & Employee Assignment

**Required Permission**: `payroll.setup`

### List Pay Groups

**URL**: `GET /pay-groups`

### Create Pay Group

**URL**: `POST /pay-groups`
**Body**:

```json
{
    "name": "Mid-Level Engineering",
    "min_annual_gross": 5000000,
    "max_annual_gross": 10000000,
    "tax_scheme_id": 1,
    "component_ids": [1, 2, 3]
}
```

### Assign Employees to Group

**URL**: `POST /pay-groups/{id}/assign`
**Body**: `{"employee_ids": [1, 5]}`

### Unassign Employee

**URL**: `DELETE /pay-groups/{id}/employees/{employeeId}`

---

## 5. Annual Salary Structures

**Required Permission**: `payroll.annual_structures`

### Bulk Generate Structures

**URL**: `POST /annual-structures/generate`
**Body**: `{"pay_group_id": 1, "ignore_existing": true}`

### Preview/Calculate

**URL**: `POST /annual-structures/calculate`
**Purpose**: Simulation of Gross-to-Net without saving.

### List All Structures

**URL**: `GET /annual-structures`

---

## 6. Monthly Batch Processing

**Required Permission**: `payroll.processing`

### Generate Monthly Batch

**URL**: `POST /batches/generate`
**Body**: `{"pay_group_id": 1, "month": 1, "year": 2026}`

### Authorize Batch (Finalize)

**URL**: `PATCH /batches/{id}/authorize`

### Add Ad-hoc Adjustment

**URL**: `POST /batches/items`

---

## 7. Leave Allowance Management

**Required Permission**: `payroll.leave_allowances`

### List Requests

**URL**: `GET /leave-allowances`

### Approve Request

**URL**: `POST /leave-allowances/{id}/approve`

---

## 8. Employee Payslips

**Required Permission**: No specific permission required for personal payslips, or covered by `payroll.dashboard`.

### My Payslips

**URL**: `GET /payslips/my-payslips`

### Download Payslip Data

**URL**: `GET /payslips/{id}/download`

---

## 9. Payroll Analytics & Reporting

**Required Permission**: `payroll.dashboard` (for analytics) or `payroll.reports` (for specific reports).

### Dashboard Analytics

**URL**: `GET /analytics`

### Monthly Summary Report

**URL**: `GET /reports/monthly-summary?month=1&year=2026`

### Departmental Expenditure

**URL**: `GET /reports/departmental`

### Variance Report

**URL**: `GET /reports/variance?month=1&year=2026&compare_month=12&compare_year=2025`

### Statutory Compliance Report

**URL**: `GET /reports/statutory`

---

## 10. Testing Workflow

1.  **Setup**: Create Tax Scheme -> Components -> Wage Items -> Pay Groups.
2.  **Assignment**: Assign Employees to Pay Groups.
3.  **Structure**: Generate Annual Salary Structures for groups.
4.  **Processing**: Generate Monthly Batch -> Add Adjustments -> Authorize.
5.  **Verification**: Download Payslips -> View Analytics.

```

```
