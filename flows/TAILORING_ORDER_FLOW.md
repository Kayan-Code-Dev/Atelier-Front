# Tailoring Order Flow - Frontend Guide

This document explains the complete tailoring order cycle with factory integration for frontend developers.

## Table of Contents

1. [Overview](#overview)
2. [Order-Level Tailoring Stages](#order-level-tailoring-stages)
3. [Item-Level Factory Status](#item-level-factory-status)
4. [Complete Flow Diagram](#complete-flow-diagram)
5. [API Endpoints - Branch Side](#api-endpoints---branch-side)
6. [API Endpoints - Factory Side](#api-endpoints---factory-side)
7. [Step-by-Step Implementation](#step-by-step-implementation)
8. [Data Models](#data-models)
9. [Priority Levels](#priority-levels)
10. [Error Handling](#error-handling)
11. [Frontend UX Recommendations](#frontend-ux-recommendations)

---

## Overview

A **tailoring order** is an order containing at least one item with `type: "tailoring"`. These orders follow a specialized workflow involving external factories for production.

### Key Characteristics

| Feature | Tailoring Order |
|---------|-----------------|
| **Item type** | `tailoring` |
| **Factory required** | Yes (must be assigned before production) |
| **Two-level tracking** | Order stages + Item statuses |
| **Factory visibility** | Factory sees items, NOT prices/payments |
| **Stage transitions** | Sequential only (no skipping) |

---

## Order-Level Tailoring Stages

The order progresses through 6 sequential stages:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       ORDER TAILORING STAGES                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────┐                                                           │
│  │  received    │ ← Order created, measurements taken                       │
│  └──────┬───────┘                                                           │
│         │ assign factory                                                     │
│         ▼                                                                    │
│  ┌──────────────────┐                                                       │
│  │ sent_to_factory  │ ← Factory notified, items set to pending_approval    │
│  └──────┬───────────┘                                                       │
│         │ factory starts work                                                │
│         ▼                                                                    │
│  ┌──────────────────┐                                                       │
│  │  in_production   │ ← Factory is actively working                         │
│  └──────┬───────────┘                                                       │
│         │ factory completes                                                  │
│         ▼                                                                    │
│  ┌────────────────────┐                                                     │
│  │ ready_from_factory │ ← Completed, ready for pickup/delivery              │
│  └──────┬─────────────┘                                                     │
│         │ received at branch                                                 │
│         ▼                                                                    │
│  ┌────────────────────┐                                                     │
│  │ ready_for_customer │ ← At branch, customer can pick up                   │
│  └──────┬─────────────┘                                                     │
│         │ customer picks up                                                  │
│         ▼                                                                    │
│  ┌──────────────┐                                                           │
│  │  delivered   │ ← Order complete                                          │
│  └──────────────┘                                                           │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Stage Values

| Stage | Value | Description |
|-------|-------|-------------|
| Order Received | `received` | Initial stage when order created |
| Sent to Factory | `sent_to_factory` | Factory assigned and notified |
| In Production | `in_production` | Factory actively working |
| Ready from Factory | `ready_from_factory` | Factory completed work |
| Ready for Customer | `ready_for_customer` | Back at branch for pickup |
| Delivered | `delivered` | Customer received order |

---

## Item-Level Factory Status

Each tailoring item has its own factory status that the factory manages:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     FACTORY ITEM STATUS FLOW                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌───────────────────────────┐                                              │
│  │ pending_factory_approval  │ ← Initial (set when sent_to_factory)         │
│  └────────────┬──────────────┘                                              │
│               │                                                              │
│       ┌───────┴───────┐                                                     │
│       ▼               ▼                                                     │
│  ┌─────────┐    ┌──────────┐                                                │
│  │ accepted│    │ rejected │ ← END (requires reason)                        │
│  └────┬────┘    └──────────┘                                                │
│       │                                                                      │
│       ▼                                                                      │
│  ┌─────────────┐                                                            │
│  │ in_progress │ ← Factory working on this item                             │
│  └──────┬──────┘                                                            │
│         │                                                                    │
│         ▼                                                                    │
│  ┌────────────────────┐                                                     │
│  │ ready_for_delivery │ ← Item complete                                     │
│  └──────┬─────────────┘                                                     │
│         │                                                                    │
│         ▼                                                                    │
│  ┌───────────────────────┐                                                  │
│  │ delivered_to_atelier  │ ← Handed back to branch                          │
│  └──────┬────────────────┘                                                  │
│         │                                                                    │
│         ▼                                                                    │
│  ┌────────┐                                                                 │
│  │ closed │ ← END                                                           │
│  └────────┘                                                                 │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Status Values

| Status | Value | Who Sets | Description |
|--------|-------|----------|-------------|
| Pending Approval | `pending_factory_approval` | System | Auto-set when order sent to factory |
| Accepted | `accepted` | Factory | Factory agrees to make item |
| Rejected | `rejected` | Factory | Factory declines (needs reason) |
| In Progress | `in_progress` | Factory | Work has started |
| Ready for Delivery | `ready_for_delivery` | Factory | Item completed |
| Delivered to Atelier | `delivered_to_atelier` | Branch | Item received at branch |
| Closed | `closed` | System | Final state |

---

## Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    COMPLETE TAILORING ORDER FLOW                            │
├──────────────────────────────────┬──────────────────────────────────────────┤
│         BRANCH SIDE              │           FACTORY SIDE                   │
├──────────────────────────────────┼──────────────────────────────────────────┤
│                                  │                                          │
│  1. CREATE ORDER                 │                                          │
│     POST /orders                 │                                          │
│     items: [{ type: "tailoring"}]│                                          │
│     ↓                            │                                          │
│  Order created with              │                                          │
│  tailoring_stage: null           │                                          │
│                                  │                                          │
│  2. UPDATE STAGE → received      │                                          │
│     PUT /orders/{id}/tailoring-  │                                          │
│     stage { stage: "received" }  │                                          │
│     ↓                            │                                          │
│  3. ASSIGN FACTORY               │                                          │
│     POST /orders/{id}/assign-    │                                          │
│     factory { factory_id: 1 }    │                                          │
│     ↓                            │                                          │
│  4. UPDATE STAGE → sent_to_      │                                          │
│     factory                      │                                          │
│     → Items: pending_factory_    │  ════════════════════════════════════   │
│       approval                   │  NOTIFICATION SENT TO FACTORY            │
│     → Factory notified           │  ════════════════════════════════════   │
│                                  │                                          │
│                                  │  5. VIEW ORDERS                          │
│                                  │     GET /factory/orders                  │
│                                  │     ↓                                    │
│                                  │  6. ACCEPT/REJECT ITEMS                  │
│                                  │     POST .../items/{id}/accept           │
│                                  │     { expected_delivery_date: "..." }    │
│                                  │     ↓                                    │
│                                  │  7. START PRODUCTION                     │
│                                  │     PUT .../items/{id}/status            │
│                                  │     { status: "in_progress" }            │
│                                  │     ↓                                    │
│  8. UPDATE STAGE → in_production │                                          │
│     (when factory starts)        │                                          │
│                                  │                                          │
│                                  │  9. COMPLETE PRODUCTION                  │
│                                  │     PUT .../items/{id}/status            │
│                                  │     { status: "ready_for_delivery" }     │
│                                  │                                          │
│  10. UPDATE STAGE → ready_from_  │                                          │
│      factory                     │                                          │
│      (when all items ready)      │                                          │
│      ↓                           │                                          │
│  11. UPDATE STAGE → ready_for_   │                                          │
│      customer                    │                                          │
│      (items received at branch)  │                                          │
│      ↓                           │                                          │
│  12. UPDATE STAGE → delivered    │                                          │
│      (customer picks up)         │                                          │
│                                  │                                          │
└──────────────────────────────────┴──────────────────────────────────────────┘
```

---

## API Endpoints - Branch Side

### Create Tailoring Order

```http
POST /api/v1/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "client_id": 1,
  "entity_type": "branch",
  "entity_id": 1,
  "items": [
    {
      "cloth_id": 123,
      "price": 1500.00,
      "type": "tailoring",
      "notes": "Custom alterations needed"
    }
  ]
}
```

### Assign Factory

```http
POST /api/v1/orders/{id}/assign-factory
Authorization: Bearer {token}
Content-Type: application/json

{
  "factory_id": 1,
  "expected_days": 14,
  "priority": "high",
  "factory_notes": "Rush order - VIP client"
}
```

**Response:**
```json
{
  "message": "Factory assigned successfully",
  "order": {
    "id": 1,
    "assigned_factory_id": 1,
    "expected_completion_date": "2025-02-15",
    "priority": "high",
    "assignedFactory": {
      "id": 1,
      "name": "Premium Tailoring Factory"
    }
  }
}
```

### Update Tailoring Stage

```http
PUT /api/v1/orders/{id}/tailoring-stage
Authorization: Bearer {token}
Content-Type: application/json

{
  "stage": "sent_to_factory",
  "notes": "Sent via courier",
  "factory_id": 1
}
```

**Response:**
```json
{
  "message": "Tailoring stage updated successfully",
  "order": {
    "id": 1,
    "tailoring_stage": "sent_to_factory",
    "tailoring_stage_changed_at": "2025-01-17 14:30:00",
    "sent_to_factory_date": "2025-01-17"
  }
}
```

### View Stage History

```http
GET /api/v1/orders/{id}/stage-history
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 3,
      "from_stage": "received",
      "to_stage": "sent_to_factory",
      "changed_by": 1,
      "notes": "Sent via courier",
      "created_at": "2025-01-17 14:30:00",
      "changedBy": { "id": 1, "name": "Admin User" }
    },
    {
      "id": 2,
      "from_stage": null,
      "to_stage": "received",
      "changed_by": 1,
      "notes": "Measurements complete",
      "created_at": "2025-01-17 10:00:00"
    }
  ]
}
```

---

## API Endpoints - Factory Side

> **Note:** Factory users do NOT see prices, payments, or full client details.

### List Assigned Orders

```http
GET /api/v1/factory/orders
Authorization: Bearer {factory_user_token}

Query Parameters:
- factory_status: Filter by item status
- per_page: Items per page (default: 15)
```

### View Order Details

```http
GET /api/v1/factory/orders/{id}
Authorization: Bearer {factory_user_token}
```

### Accept Item

```http
POST /api/v1/factory/orders/{orderId}/items/{itemId}/accept
Authorization: Bearer {factory_user_token}
Content-Type: application/json

{
  "expected_delivery_date": "2025-02-01",
  "notes": "Will use premium materials"
}
```

### Reject Item

```http
POST /api/v1/factory/orders/{orderId}/items/{itemId}/reject
Authorization: Bearer {factory_user_token}
Content-Type: application/json

{
  "rejection_reason": "Material not available",
  "notes": "Alternative materials suggested"
}
```

### Update Item Status

```http
PUT /api/v1/factory/orders/{orderId}/items/{itemId}/status
Authorization: Bearer {factory_user_token}
Content-Type: application/json

{
  "status": "in_progress",
  "notes": "Started cutting fabric"
}
```

**Valid status values:** `in_progress`, `ready_for_delivery`

### Update Item Notes

```http
PUT /api/v1/factory/orders/{orderId}/items/{itemId}/notes
Authorization: Bearer {factory_user_token}
Content-Type: application/json

{
  "notes": "Customer requested extra lining"
}
```

### Set Expected Delivery Date

```http
PUT /api/v1/factory/orders/{orderId}/items/{itemId}/delivery-date
Authorization: Bearer {factory_user_token}
Content-Type: application/json

{
  "expected_delivery_date": "2025-02-05"
}
```

### View Item Status History

```http
GET /api/v1/factory/orders/{orderId}/items/{itemId}/status-history
Authorization: Bearer {factory_user_token}
```

---

## Step-by-Step Implementation

### Frontend Checklist - Branch App

1. **Create Order**
   - [ ] Select items with `type: "tailoring"`
   - [ ] Collect client measurements
   - [ ] Submit order

2. **Assign Factory**
   - [ ] List available factories
   - [ ] Check factory capacity (`is_at_capacity`)
   - [ ] Set expected completion days
   - [ ] Set priority level

3. **Track Progress**
   - [ ] Display current stage badge
   - [ ] Show stage history timeline
   - [ ] Display per-item factory status
   - [ ] Show expected vs actual completion

4. **Advance Stages**
   - [ ] Only allow valid next stages
   - [ ] Require factory_id for `sent_to_factory`
   - [ ] Update stage with notes

### Frontend Checklist - Factory App

1. **View Orders**
   - [ ] List orders assigned to factory
   - [ ] Filter by item status
   - [ ] Show priority indicators

2. **Process Items**
   - [ ] Accept items with delivery date
   - [ ] Reject items with reason
   - [ ] Update status as work progresses
   - [ ] Add notes for communication

3. **Complete Work**
   - [ ] Mark items as ready_for_delivery
   - [ ] Confirm delivery to atelier

---

## Data Models

### Order Fields (Tailoring-Related)

| Field | Type | Description |
|-------|------|-------------|
| `tailoring_stage` | string | Current stage |
| `tailoring_stage_changed_at` | datetime | Last stage change |
| `expected_completion_date` | date | Expected done date |
| `actual_completion_date` | date | When actually done |
| `assigned_factory_id` | integer | Factory FK |
| `sent_to_factory_date` | date | When sent |
| `received_from_factory_date` | date | When received back |
| `factory_notes` | text | Notes for factory |
| `priority` | string | `low/normal/high/urgent` |

### Cloth-Order Pivot Fields (Factory-Related)

| Field | Type | Description |
|-------|------|-------------|
| `factory_status` | string | Item factory status |
| `factory_accepted_at` | datetime | Acceptance time |
| `factory_rejected_at` | datetime | Rejection time |
| `factory_rejection_reason` | string | Why rejected |
| `factory_expected_delivery_date` | date | Expected completion |
| `factory_notes` | text | Factory notes |

---

## Priority Levels

| Priority | Value | Use Case |
|----------|-------|----------|
| Low | `low` | Standard orders, flexible timeline |
| Normal | `normal` | Default priority |
| High | `high` | Important clients, tighter deadline |
| Urgent | `urgent` | Rush orders, highest priority |

---

## Error Handling

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| "This is not a tailoring order" | Order has no tailoring items | Check item types |
| "Invalid stage transition" | Trying to skip stages | Follow sequential flow |
| "Factory must be assigned" | No factory when going to `sent_to_factory` | Assign factory first |
| "Factory is at maximum capacity" | Factory full | Choose different factory |
| "Cannot accept item with current status" | Invalid status transition | Check current status |
| "Cannot modify item after delivery" | Item already delivered | Item is locked |
| "User is not assigned to a factory" | Factory endpoint accessed by non-factory user | Check user permissions |

### Status Transition Validation

```javascript
// Valid transitions - Order stages
const orderStageTransitions = {
  null: ['received'],
  'received': ['sent_to_factory'],
  'sent_to_factory': ['in_production'],
  'in_production': ['ready_from_factory'],
  'ready_from_factory': ['ready_for_customer'],
  'ready_for_customer': ['delivered'],
  'delivered': []  // Terminal
};

// Valid transitions - Factory item status
const factoryStatusTransitions = {
  null: ['pending_factory_approval'],
  'pending_factory_approval': ['accepted', 'rejected'],
  'accepted': ['in_progress'],
  'in_progress': ['ready_for_delivery'],
  'ready_for_delivery': ['delivered_to_atelier'],
  'delivered_to_atelier': ['closed'],
  'rejected': [],  // Terminal
  'closed': []     // Terminal
};
```

---

## Frontend UX Recommendations

### Branch App

1. **Stage Progress Bar**
   - Show all 6 stages
   - Highlight current stage
   - Gray out future stages
   - Check mark for completed stages

2. **Factory Status Matrix**
   - Show each item's factory status
   - Color-code: pending=yellow, accepted=blue, in_progress=orange, ready=green, rejected=red

3. **Overdue Indicators**
   - Compare `expected_completion_date` with today
   - Show red warning for overdue orders

4. **Stage Transition Buttons**
   - Only show valid next stage(s)
   - Require confirmation before transition

### Factory App

1. **New Orders Notification**
   - Badge count for pending_factory_approval items
   - Push notification when new orders arrive

2. **Item Action Cards**
   - Accept/Reject buttons for pending items
   - Status dropdown for in-progress items
   - Notes field always accessible

3. **Delivery Date Calendar**
   - Visual calendar for expected dates
   - Highlight overdue items

4. **Hide Financial Info**
   - No prices displayed
   - No payment information
   - Focus on production details only

---

## Quick Reference

### API Sequence - Create to Deliver

```
BRANCH:
1. POST /api/v1/orders                          → Create order
2. PUT  /api/v1/orders/{id}/tailoring-stage     → stage: received
3. POST /api/v1/orders/{id}/assign-factory      → Assign factory
4. PUT  /api/v1/orders/{id}/tailoring-stage     → stage: sent_to_factory

FACTORY:
5. GET  /api/v1/factory/orders                  → View orders
6. POST /api/v1/factory/orders/{id}/items/{id}/accept
7. PUT  /api/v1/factory/orders/{id}/items/{id}/status  → in_progress
8. PUT  /api/v1/factory/orders/{id}/items/{id}/status  → ready_for_delivery

BRANCH:
9.  PUT /api/v1/orders/{id}/tailoring-stage     → stage: in_production
10. PUT /api/v1/orders/{id}/tailoring-stage     → stage: ready_from_factory
11. PUT /api/v1/orders/{id}/tailoring-stage     → stage: ready_for_customer
12. PUT /api/v1/orders/{id}/tailoring-stage     → stage: delivered
```

### Stage Transition Rules

| From | Allowed Next |
|------|--------------|
| null | `received` |
| `received` | `sent_to_factory` |
| `sent_to_factory` | `in_production` |
| `in_production` | `ready_from_factory` |
| `ready_from_factory` | `ready_for_customer` |
| `ready_for_customer` | `delivered` |
| `delivered` | (none - final) |

### Factory Item Status Rules

| From | Allowed Next |
|------|--------------|
| `pending_factory_approval` | `accepted`, `rejected` |
| `accepted` | `in_progress` |
| `in_progress` | `ready_for_delivery` |
| `ready_for_delivery` | `delivered_to_atelier` |
| `delivered_to_atelier` | `closed` |
| `rejected` | (none - terminal) |
| `closed` | (none - terminal) |

