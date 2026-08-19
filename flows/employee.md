# Employee Creation Guide

## Overview

This guide explains how to create employees in the Bahaa-Eldin system, including prerequisites, API calls, and permission inheritance.

---

## Prerequisites

Before creating an employee, you should have:

1. **Department** (optional, but recommended)
2. **Job Title** with roles assigned (optional, but recommended for permission inheritance)
3. **Branch** (optional)

---

## API Endpoint

**POST** `/api/v1/employees`

**Authentication:** Bearer Token (Sanctum)

---

## Required Fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Employee's full name (creates User) |
| `email` | string | Unique email (creates User) |
| `password` | string | Minimum 8 characters (creates User) |
| `hire_date` | date | Format: `YYYY-MM-DD` |

---

## Optional Fields

| Field | Type | Description |
|-------|------|-------------|
| `department_id` | integer | FK to departments table |
| `job_title_id` | integer | FK to job_titles table (inherits JobTitle's roles) |
| `manager_id` | integer | FK to employees table (must be existing employee) |
| `employment_type` | string | `full_time`, `part_time`, `contract`, `intern` |
| `roles` | array | Direct role names to assign, e.g., `["admin", "hr_manager"]` |
| `base_salary` | number | Base monthly salary |
| `transport_allowance` | number | Transport allowance |
| `housing_allowance` | number | Housing allowance |
| `other_allowances` | number | Other allowances |
| `overtime_rate` | number | Multiplier for overtime (default: 1.5) |
| `commission_rate` | number | Percentage of order value (0-100) |
| `annual_vacation_days` | integer | Days per year (default: 21) |
| `probation_end_date` | date | End of probation period |
| `work_start_time` | time | Format: `HH:MM:SS` (default: 09:00:00) |
| `work_end_time` | time | Format: `HH:MM:SS` (default: 17:00:00) |
| `work_hours_per_day` | integer | Hours per day (default: 8) |
| `late_threshold_minutes` | integer | Grace period in minutes (default: 15) |
| `bank_name` | string | Bank name for salary payment |
| `bank_account_number` | string | Bank account number |
| `bank_iban` | string | Bank IBAN |
| `emergency_contact_name` | string | Emergency contact name |
| `emergency_contact_phone` | string | Emergency contact phone |
| `emergency_contact_relation` | string | Relationship (e.g., "spouse", "parent") |
| `notes` | string | Additional notes |
| `branch_ids` | array | Branch IDs to assign, e.g., `[1, 2]` |
| `primary_branch_id` | integer | Primary branch ID |

---

## Example Request

{
    "name": "Ahmed Mohamed",
    "email": "ahmed@company.com",
    "password": "SecurePass123",
    "hire_date": "2026-01-17",
    "department_id": 1,
    "job_title_id": 1,
    "employment_type": "full_time",
    "base_salary": 5000.00,
    "transport_allowance": 500.00,
    "housing_allowance": 1000.00,
    "roles": ["employee"],
    "branch_ids": [1],
    "primary_branch_id": 1
}---

## Example Response

{
    "message": "Employee created successfully.",
    "employee": {
        "id": 1,
        "user_id": 5,
        "employee_code": "EMP00001",
        "department_id": 1,
        "job_title_id": 1,
        "manager_id": null,
        "employment_type": "full_time",
        "employment_status": "active",
        "hire_date": "2026-01-17",
        "base_salary": "5000.00",
        "user": {
            "id": 5,
            "name": "Ahmed Mohamed",
            "email": "ahmed@company.com"
        },
        "department": {
            "id": 1,
            "name": "Sales"
        },
        "jobTitle": {
            "id": 1,
            "name": "Sales Manager",
            "level": "branch_manager"
        },
        "branches": [...]
    }
}---

## Permission Inheritance

When you create an employee with a `job_title_id`, they automatically inherit permissions:
