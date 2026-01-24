# Leave Management API - Complete Testing Reference

> [!IMPORTANT]
> **Authentication**: All requests require a Bearer token in the header. `tenant_id` is automatically injected from your authentication token.
>
> ```
> Authorization: Bearer {your-token-here}
> ```

Base URL: `http://localhost:8000/api/leave`

---

## Table of Contents

1. [Configuration & Setup](#1-configuration--setup)
    - [Leave Types](#leave-types)
    - [Leave Groups](#leave-groups)
    - [Leave Policies](#leave-policies)
    - [Approval Workflows](#approval-workflows)
2. [Leave Requests Lifecycle](#2-leave-requests-lifecycle)
    - [Submit & List](#submit--list)
    - [Details & Management](#details--management)
    - [Cancellations](#cancellations)
3. [Approvals API](#3-approvals-api)
    - [Actions](#actions)
    - [History & Nudging](#history--nudging)
4. [Balances API](#4-balances-api)
    - [My Balances](#my-balances)
    - [Adjustments & Audit](#adjustments--audit)
5. [Analytics & Reports](#5-analytics--reports)

---

## 1. Configuration & Setup

### Leave Types

**Endpoints**:

- `GET /types` (List all)
- `POST /types` (Create)
- `GET /types/{id}` (Details)
- `PUT /types/{id}` (Update)
- `DELETE /types/{id}` (Delete)

**Example Request (Create)**:

```json
{
    "name": "Sick Leave",
    "code": "SL",
    "description": "Medical leave for illness",
    "is_paid": true,
    "requires_attachment": true,
    "is_active": true
}
```

### Leave Groups

**Endpoints**: `GET /groups`, `POST /groups`, `GET /groups/{id}`, `PUT /groups/{id}`, `DELETE /groups/{id}`
**Purpose**: Define groups of employees (e.g., "Standard Staff", "Management") for rule application.

### Leave Policies

**Endpoints**: `GET /policies`, `POST /policies`, `GET /policies/{id}`, `PUT /policies/{id}`, `DELETE /policies/{id}`
**Key Parameters**:

- `leave_group_id`, `leave_type_id`
- `entitlement_days`: (e.g., 21)
- `accrual_frequency`: `yearly`, `monthly`, `on_hire`, `manual`
- `allow_carry_forward`, `max_carry_forward_days`
- `allow_negative_balance`, `notice_period_days`
- `leave_workflow_id`: Workflow to trigger on submission.

### Approval Workflows

**Endpoints**: `GET /workflows`, `POST /workflows`, `GET /workflows/{id}`, `PUT /workflows/{id}`, `DELETE /workflows/{id}`
**Structure**: Workflows contain `levels` defining who handles each stage (`manager`, `hr`, `specific_employee`).

---

## 2. Leave Requests Lifecycle

### Submit & List

**1. Get Requests List**

- `GET /requests`
- **Filters**: `status` (pending, approved, etc.), `employee_id`, `start_date`, `end_date`.

**2. Calculate Duration**

- `GET /requests/calculate-duration`
- **Params**: `employee_id`, `leave_type_id`, `start_date`, `end_date`
- **Purpose**: Calculate days excluding weekends/holidays based on employee policy.

**3. Submit Request**

- `POST /requests`
- **Type**: `multipart/form-data`
- **Fields**: `employee_id`, `leave_type_id`, `start_date`, `end_date`, `reason`, `attachment` (File).

### Details & Management

**1. Get Request Details**

- `GET /requests/{id}` (Fetches including approval chain)

**2. Update Request**

- `PUT /requests/{id}` (Allowed only for `pending` or `approved` requests).
- **Note**: Re-initializes the approval chain if dates/types change.

**3. Delete Request**

- `DELETE /requests/{id}`

### Cancellations

**1. Full Cancellation**

- `POST /requests/{id}/cancel`
- **Purpose**: Fully cancels the request and restores balance.

**2. Partial Cancellation**

- `POST /requests/{id}/partial-cancel`
  **Body**:

```json
{
    "new_start_date": "2026-02-01",
    "new_end_date": "2026-02-03",
    "reason": "Returing earlier than planned"
}
```

---

## 3. Approvals API

### Actions

**1. Get Pending Approvals**

- `GET /approvals/pending`
- **Search**: `?search=John` (Filters by employee name/number)

**2. Process Approval**

- `POST /approvals/{id}/action`
  **Body**: `{ "status": "approved", "comments": "Approved" }`

### History & Nudging

**1. Approval History**

- `GET /approvals/history`
- **Filters**: `status` (approved, declined), `search`, `start_date`, `end_date`.

**2. Nudge Approver**

- `POST /approvals/{id}/nudge`
- **Purpose**: Allows requester to send a reminder notification to the current pending approver.

---

## 4. Balances API

### My Balances

- `GET /balances/my-balance`
- **Purpose**: Employee self-service view of entitlement and available days.

### Adjustments & Audit

**1. Admin Balance List**

- `GET /balances`
- **Params**: `year`, `employee_id`

**2. Manual Adjustment**

- `POST /balances/adjust`
- **Purpose**: Admin correction of days.
- **Fields**: `employee_id`, `leave_type_id`, `year`, `adjustment_type` (addition/deduction), `amount`, `reason`.

**3. Adjustment Audit History**

- `GET /balances/adjustments`
- **Params**: `employee_id`, `leave_type_id`

---

## 5. Analytics & Reports

### Performance & Trends

- **Dashboard Stats**: `GET /analytics/dashboard-stats` (Summary cards)
- **Monthly Usage**: `GET /analytics/usage` (12-month trend)
- **Usage Summary**: `GET /analytics/usage-summary` (Aggregated metrics, peak conflicts, avg latency)

### Operational Reports

- **Calendar Data**: `GET /analytics/calendar`
- **Params**: `department_id` (optional), `month` (optional), `year` (optional)
- **Purpose**: Grid/Calendar view of who is off. If `month` is omitted, data for the entire `year` is returned.

- **History Report**: `GET /analytics/history-report` (Exportable flat list)
- **Balance Report**: `GET /analytics/balance-report` (Snapshot of all entitlements)
- **Liability Report**: `GET /analytics/liability-report` (Estimated financial/operational risk of unused leave)
- **Absenteeism Patterns**: `GET /analytics/absenteeism-pattern` (Identification of frequent short-term or weekend-adjacent leave trends)
- **Approver Latency**: `GET /analytics/latency-report` (Efficiency tracking for the approval workflow)
- **Conflict & Overlap**: `GET /analytics/conflict-report` (Early warning for dates with multiple staff members away in the same team)
- **Active Leaves**: `GET /analytics/active-leaves` (Who is on leave today?)
