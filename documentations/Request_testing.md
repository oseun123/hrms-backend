# Request Management API - Complete Testing Reference

> [!IMPORTANT]
> **Multi-Tenant Authentication**: `tenant_id` is automatically injected from your authentication token. Do not include it in request bodies for resource creation.

Base URL: `http://localhost:8000/api/requests`

**Authentication**: All requests require Bearer token in header:

```
Authorization: Bearer {your-token-here}
```

---

## Table of Contents

1. [Workflow Configuration](#1-workflow-configuration)
2. [Template Management](#2-template-management)
3. [Employee Request Submissions](#3-employee-request-submissions)
4. [Approval Queue Processing](#4-approval-queue-processing)
5. [Dashboard & Analytics](#5-dashboard--analytics)
6. [Testing Workflow](#testing-workflow)

---

## 1. Workflow Configuration

**Required Permission**: `requests.configuration`

### List Workflows
**URL**: `GET /workflows`

### Create Workflow
**URL**: `POST /workflows`
**Body**:
```json
{
    "name": "Standard Multi-Level Approval",
    "description": "Standard flow for general requests",
    "is_active": true,
    "levels": [
        {
            "level": 1,
            "approver_type": "manager",
            "approver_id": null
        },
        {
            "level": 2,
            "approver_type": "hr",
            "approver_id": null
        }
    ]
}
```

---

## 2. Template Management

**Required Permission**: `requests.configuration`

### List Templates
**URL**: `GET /templates`

### Create Custom Template
**URL**: `POST /templates`
**Body**:
```json
{
    "name": "Laptop Request",
    "description": "Request for a new company laptop",
    "category": "custom",
    "icon": "LaptopOutlined",
    "request_workflow_id": 1,
    "is_active": true,
    "fields": [
        {
            "id": "field_1",
            "type": "text",
            "label": "Reason for Request",
            "required": true
        },
        {
            "id": "field_2",
            "type": "select",
            "label": "Preferred Brand",
            "options": ["MacBook", "Dell", "HP"],
            "required": true
        }
    ]
}
```

---

## 3. Employee Request Submissions

**Required Permission**: `requests.templates` (to submit) | `requests.dashboard` (to view my)

### My Submissions
**URL**: `GET /submissions/my`

### Submit Request
**URL**: `POST /submissions`
**Body**:
```json
{
    "template_id": 1,
    "form_data": {
        "field_1": "My current laptop is broken",
        "field_2": "MacBook"
    }
}
```

### Cancel Submission
**URL**: `POST /submissions/{id}/cancel`

---

## 4. Approval Queue Processing

**Required Permission**: `requests.approvals`

### Pending Approvals
**URL**: `GET /approvals/pending`

### Process Action (Approve/Decline)
**URL**: `POST /approvals/{id}/action`
**Body**:
```json
{
    "status": "approved",
    "comments": "Proceed with procurement."
}
```

### Approval History
**URL**: `GET /approvals/history`

---

## 5. Dashboard & Analytics

**Required Permission**: `requests.dashboard`

### Dashboard Stats
**URL**: `GET /stats`
**Returns**:
```json
{
    "total_submitted": 10,
    "pending_approvals": 2,
    "total_approved": 7,
    "total_declined": 1
}
```

---

## 6. Testing Workflow

1.  **Workflows**: Create a 2-level workflow (Manager -> HR).
2.  **Templates**: Create a template and link it to the workflow created in step 1.
3.  **Submission**: As an employee, submit a request using that template.
4.  **Approval L1**: Log in as the manager and approve the request in `/approvals/pending`.
5.  **Approval L2**: Log in as HR and approve the request.
6.  **Verification**: Check `/submissions/my` to confirm status is now `approved`.
