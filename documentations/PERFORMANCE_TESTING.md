# Performance API - Complete Testing Reference

> [!IMPORTANT]
> **Multi-Tenant Authentication**: `tenant_id` is automatically injected from your authentication token. Do not include it in request bodies unless explicitly stated.

Base URL: `http://localhost:8000/api/performance`

**Authentication**: All requests require Bearer token in header:

```
Authorization: Bearer {your-token-here}
```

---

## Table of Contents

1. [Performance Settings](#1-performance-settings)
2. [Areas of Focus](#2-areas-of-focus)
3. [Goals & Objectives](#3-goals--objectives)
4. [Competencies](#4-competencies)
5. [Deliverables Management](#5-deliverables-management)
6. [Appraisals](#6-appraisals)
7. [Appraisal Submissions](#7-appraisal-submissions)
8. [Appraisal Tracking](#8-appraisal-tracking)
9. [Appraisal Analytics](#9-appraisal-analytics)
10. [Performance Reporting](#10-performance-reporting)
11. [Testing Checklist](#11-testing-checklist)

---

## 1. Performance Settings

**Required Permission**: `performance.setup`

### Get All Settings

**URL**: `GET /settings`

### Update Settings

**URL**: `PUT /settings`

---

## 2. Areas of Focus

**Required Permission**: `performance.setup`

### Get All Areas

**URL**: `GET /areas-of-focus`

### Create Area

**URL**: `POST /areas-of-focus`

---

## 3. Goals & Objectives

**Required Permission**: `performance.setup`

### Get All Goals

**URL**: `GET /goals`

### Create Goal

**URL**: `POST /goals`

---

## 4. Competencies

**Required Permission**: `performance.setup`

### Get All Competencies

**URL**: `GET /competencies`

### Update Bulk Weightages

**URL**: `PUT /competencies/bulk-weightages`

---

## 5. Deliverables Management

**Required Permissions**:

- `performance.setup` (Global management)
- `performance.my_deliverables` (Personal view)
- `performance.team_deliverables` (Team management)
- `performance.employee_deliverables` (Organizational view)

### Get Global Deliverables

**URL**: `GET /deliverables`
**Permission**: `performance.setup`

### Get My Deliverables

**URL**: `GET /deliverables/my`
**Permission**: `performance.my_deliverables`

### Get Team Deliverables

**URL**: `GET /deliverables/team`
**Permission**: `performance.team_deliverables`

### Get Employee Deliverables

**URL**: `GET /deliverables/employees`
**Permission**: `performance.employee_deliverables`

### Assign & Activate

- `POST /deliverables/assign` (Permission: `performance.setup`)
- `POST /deliverables/activate` (Permission: `performance.setup`)

---

## 6. Appraisals

**Required Permission**: `performance.appraisal_management`

### Create Appraisal Cycle

**URL**: `POST /appraisals`

### Activate Appraisal

**URL**: `POST /appraisals/{id}/activate`

---

## 7. Appraisal Submissions

**Required Permissions**:

- `performance.my_deliverables` (Self-service)
- `performance.appraisal_management` (Admin actions)

### Employee Actions

- `GET /submissions/my-pending`
- `GET /submissions/my-history`
- `GET /submissions/{id}`
- `POST /submissions/{id}/submit-scores`
- `POST /submissions/{id}/forward`

### Admin/HR Actions

**Required Permission**: `performance.appraisal_management`

- `POST /submissions/{id}/return`
- `POST /submissions/{id}/restart`
- `PUT /submissions/{id}/settings`

---

## 8. Appraisal Tracking

**Required Permission**: `performance.dashboard`

### Get Appraisal Stats

**URL**: `GET /tracking/appraisal/{appraisalId}/stats`

---

## 9. Appraisal Analytics

**Required Permission**: `performance.dashboard`

### Analytics Endpoints

- `GET /analytics/appraisal/{id}/completion-stats`
- `GET /analytics/appraisal/{id}/score-distribution`
- `GET /analytics/appraisal/{id}/department-averages`

---

## 10. Performance Reporting

**Required Permission**: `performance.reports`

### Reports Endpoints

- `GET /reports/appraisal/{id}/cycle-status`
- `GET /reports/appraisal/{id}/league-table`
- `GET /reports/appraisal/{id}/departmental`

---

## 11. Testing Checklist

- [x] **Permission Check**: Ensure roles correctly restrict access to Performance sub-modules.
- [x] **Route Middleware**: Verify `permission` middleware is applied to all groups in `performance.php`.
- [x] **UI Visibility**: Verify Sidebar and Mega Menu items hide dynamically for restricted users.
- [ ] **Data Integrity**: Verify `employee` role defaults match the seeder requirements.
