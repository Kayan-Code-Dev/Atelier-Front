# Buy Order Flow - Frontend Guide

This document explains the complete buy order cycle, constraints, and API integration for frontend developers.

## Table of Contents

1. [Overview](#overview)
2. [Buy Order Constraints](#buy-order-constraints)
3. [Complete Buy Order Flow](#complete-buy-order-flow)
4. [API Endpoints](#api-endpoints)
5. [State Diagram](#state-diagram)
6. [Revenue & Cashbox](#revenue--cashbox)
7. [Post-Sale Restrictions](#post-sale-restrictions)
8. [Error Handling](#error-handling)
9. [Testing](#testing)

---

## Overview

A **buy order** is when a customer purchases a cloth item outright (not renting or tailoring). The cloth becomes the customer's property and is removed from inventory availability.

### Key Characteristics

| Feature | Buy Order |
|---------|-----------|
| **Items per order** | Exactly 1 item |
| **Item type** | `buy` only (no mixing with rent/tailoring) |
| **Payment required** | Full payment before delivery |
| **Custody required** | No |
| **Final cloth status** | `sold` |
| **Revenue tracking** | Added to entity's cashbox on delivery |

---

## Buy Order Constraints

### 1. Single Item Requirement

Buy orders must contain **exactly 1 item** of type `buy`. You cannot:
- Mix buy items with rent or tailoring items
- Have multiple buy items in one order

```json
// ✅ VALID - Single buy item
{
  "items": [
    { "cloth_id": 1, "price": 500, "type": "buy" }
  ]
}

// ❌ INVALID - Multiple items with buy
{
  "items": [
    { "cloth_id": 1, "price": 500, "type": "buy" },
    { "cloth_id": 2, "price": 100, "type": "rent", ... }
  ]
}

// ❌ INVALID - Multiple buy items
{
  "items": [
    { "cloth_id": 1, "price": 500, "type": "buy" },
    { "cloth_id": 2, "price": 300, "type": "buy" }
  ]
}
```

### 2. Cloth Availability Checks

Before creating a buy order, the system validates:

| Check | Description | Error Message |
|-------|-------------|---------------|
| **Pending Rent** | Cloth must NOT have active/future rent reservations | "Cannot sell cloth with pending rent reservations" |
| **Already Sold** | Cloth status must NOT be `sold` | "Cannot sell cloth that is already sold" |

### 3. Payment Before Delivery

Buy orders require **full payment** (remaining = 0) before delivery can be marked:

```
Order remaining: 500.00
→ Cannot deliver (payment required)

Order remaining: 0.00  
→ Can deliver ✓
```

---

## Complete Buy Order Flow

### Step-by-Step Process

```
┌─────────────────────────────────────────────────────────────────┐
│                     BUY ORDER FLOW                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. CHECK CLOTH AVAILABILITY                                    │
│     ├── Verify status ≠ 'sold'                                  │
│     └── Verify no pending rent reservations                     │
│                        ↓                                         │
│  2. CREATE ORDER                                                 │
│     ├── POST /api/v1/orders                                     │
│     ├── items: [{ type: "buy", cloth_id, price }]              │
│     └── Order status: "created"                                 │
│                        ↓                                         │
│  3. ADD PAYMENT                                                  │
│     ├── POST /api/v1/orders/{id}/add-payment                   │
│     ├── Amount = total_price                                    │
│     └── Order status: "paid"                                    │
│                        ↓                                         │
│  4. DELIVER ORDER                                                │
│     ├── POST /api/v1/orders/{id}/deliver                       │
│     ├── Validates payment is complete                           │
│     ├── Sets cloth status to "sold"                             │
│     ├── Records revenue in cashbox                              │
│     └── Order status: "delivered"                               │
│                        ↓                                         │
│  5. POST-SALE STATE                                              │
│     ├── Cloth is now "sold"                                     │
│     ├── Cloth cannot be rented                                  │
│     ├── Cloth cannot be transferred                             │
│     ├── Order cannot be edited                                  │
│     └── Order cannot be deleted                                 │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Order Status Progression

```
created → partially_paid → paid → delivered
```

### Cloth Status Change

```
ready_for_rent → sold (on delivery)
```

---

## API Endpoints

### 1. Create Buy Order

```http
POST /api/v1/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "client_id": 1,
  "entity_type": "branch",
  "entity_id": 1,
  "paid": 0,                    // Optional initial payment
  "items": [
    {
      "cloth_id": 123,
      "price": 500.00,
      "type": "buy",
      "notes": "Customer loved the design"
    }
  ]
}
```

**Response (201 Created):**
```json
{
  "id": 1,
  "client_id": 1,
  "total_price": 500.00,
  "status": "created",
  "paid": 0,
  "remaining": 500.00,
  "items": [...]
}
```

### 2. Add Payment

```http
POST /api/v1/orders/{order_id}/add-payment
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 500.00,
  "payment_type": "normal",
  "notes": "Full payment for purchase"
}
```

**Response:**
```json
{
  "message": "Payment added successfully",
  "payment": {...},
  "order": {
    "id": 1,
    "paid": 500.00,
    "remaining": 0,
    "status": "paid"
  }
}
```

### 3. Deliver Order

```http
POST /api/v1/orders/{order_id}/deliver
Authorization: Bearer {token}
```

**Validation:**
- For buy-only orders: `remaining` must equal 0
- No custody records required (unlike rent orders)

**Response:**
```json
{
  "message": "Order delivered successfully",
  "order": {
    "id": 1,
    "status": "delivered",
    "items": [
      {
        "id": 123,
        "status": "sold",    // Cloth is now sold
        ...
      }
    ]
  }
}
```

### 4. View Cloth History

```http
GET /api/v1/clothes/{cloth_id}/history
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "action": "created",
      "entity_type": "branch",
      "entity_id": 1,
      "created_at": "2025-01-01 10:00:00"
    },
    {
      "id": 2,
      "action": "ordered",
      "order_id": 1,
      "created_at": "2025-01-10 14:30:00"
    },
    {
      "id": 3,
      "action": "status_changed",
      "status": "sold",
      "notes": "Status changed from ready_for_rent to sold",
      "created_at": "2025-01-10 15:00:00"
    }
  ]
}
```

---

## State Diagram

```
                           BUY ORDER STATES
┌─────────────────────────────────────────────────────────────────┐
│                                                                  │
│    ┌──────────┐    payment    ┌────────────────┐                │
│    │ created  │──────────────►│ partially_paid │                │
│    └────┬─────┘               └───────┬────────┘                │
│         │                             │                          │
│         │ full payment                │ remaining payment        │
│         ▼                             ▼                          │
│    ┌─────────────────────────────────────┐                      │
│    │              paid                    │                      │
│    │   (remaining = 0, ready to deliver) │                      │
│    └─────────────────┬───────────────────┘                      │
│                      │                                           │
│                      │ deliver                                   │
│                      ▼                                           │
│    ┌─────────────────────────────────────┐                      │
│    │            delivered                 │                      │
│    │   • Cloth status → "sold"           │                      │
│    │   • Revenue → Cashbox               │                      │
│    │   • Order locked (no edit/delete)   │                      │
│    └─────────────────────────────────────┘                      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

                        CLOTH STATUS
┌─────────────────────────────────────────────────────────────────┐
│                                                                  │
│    ┌────────────────┐         ┌────────────────┐                │
│    │ ready_for_rent │────────►│      sold      │                │
│    └────────────────┘         └────────────────┘                │
│          ↑                           │                           │
│          │                           │ BLOCKED:                  │
│          │                           │ • Cannot rent             │
│          └───────────────────────────│ • Cannot transfer         │
│                    X                 │ • Cannot re-sell          │
│              (not reversible)        │                           │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Revenue & Cashbox

When a buy order is delivered:

1. **Cloth status** is set to `sold`
2. **Sale revenue** is recorded as income in the branch's cashbox
3. **Transaction** is created with:
   - Category: `cloth_sale`
   - Amount: Item price from order
   - Reference: Order ID and Cloth ID

### Cashbox Transaction Example

```json
{
  "cashbox_id": 1,
  "type": "income",
  "amount": 500.00,
  "category": "cloth_sale",
  "description": "Cloth sale: WD-001 (Wedding Dress #1) - Order #1",
  "reference_type": "App\\Models\\Order",
  "reference_id": 1,
  "metadata": {
    "cloth_id": 123,
    "cloth_code": "WD-001",
    "order_id": 1,
    "sale_type": "buy"
  }
}
```

---

## Post-Sale Restrictions

Once a cloth is sold, the following restrictions apply:

### 1. Cloth Cannot Be Transferred

```http
POST /api/v1/transfers
{
  "cloth_ids": [123]  // 123 is sold
}

Response (422):
{
  "message": "Sold cloth cannot be transferred",
  "errors": {
    "cloth_ids.0": ["Cloth ID 123 (WD-001) is sold and cannot be transferred."]
  }
}
```

### 2. Cloth Cannot Be Rented

When checking availability or creating rent orders, sold cloths are automatically excluded.

### 3. Order Cannot Be Edited

```http
PUT /api/v1/orders/{id}

Response (422):
{
  "message": "Cannot edit order with sold items",
  "errors": {
    "order": ["This order contains sold items and cannot be modified."]
  }
}
```

### 4. Order Cannot Be Deleted

```http
DELETE /api/v1/orders/{id}

Response (422):
{
  "message": "Cannot delete order with sold items",
  "errors": {
    "order": ["This order contains sold items and cannot be deleted."]
  }
}
```

---

## Error Handling

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| "Buy orders must have exactly 1 item" | Multiple items in buy order | Remove extra items |
| "Cannot sell cloth with pending rent reservations" | Cloth has active/future rents | Cancel rents or choose different cloth |
| "Cannot sell cloth that is already sold" | Cloth status is `sold` | Choose different cloth |
| "Cannot deliver buy order. Payment must be completed first." | Order has remaining balance | Add payment to cover remaining |
| "Cannot edit order with sold items" | Order contains sold cloth | Order is locked |
| "Cannot delete order with sold items" | Order contains sold cloth | Order is locked |
| "Sold cloth cannot be transferred" | Attempting to transfer sold cloth | Sold cloths stay in final location |

### Frontend UX Recommendations

1. **Disable buy button** for cloths with pending rent reservations
2. **Hide sold cloths** from rental availability views
3. **Show "Sold" badge** on sold cloth cards
4. **Disable edit/delete buttons** on orders with sold items
5. **Show payment remaining** prominently before delivery button
6. **Display success message** with revenue info after delivery

---

## Testing

### Run Test Suite

```bash
# Run test and cleanup (rollback data)
php artisan test:buy-order --cleanup

# Run test and keep data
php artisan test:buy-order
```

### Test Scenarios Covered

| # | Scenario | Validates |
|---|----------|-----------|
| 1 | Create valid buy order | Single item creation |
| 2 | Reject multi-item buy orders | Item count validation |
| 3 | Block cloth with pending rent | Rent conflict check |
| 4 | Block already sold cloth | Status validation |
| 5 | Require payment for delivery | Payment validation |
| 6 | Set cloth to sold on delivery | Status transition |
| 7 | Exclude sold cloth from rent | Availability check |
| 8 | Track cloth history | History logging |
| 9 | Calculate revenue | Financial tracking |
| 10 | Block transfer of sold cloth | Post-sale restriction |
| 11 | Block edit of order with sold items | Post-sale restriction |
| 12 | Block delete of order with sold items | Post-sale restriction |

---

## Quick Reference

### Frontend Checklist

- [ ] Validate cloth is available (not sold, no pending rents)
- [ ] Create order with exactly 1 buy item
- [ ] Collect full payment before delivery
- [ ] Call deliver endpoint
- [ ] Update UI to show cloth as sold
- [ ] Disable edit/delete on the order
- [ ] Hide/exclude cloth from rent availability

### API Sequence

```
1. GET  /api/v1/clothes/{id}           → Check status
2. GET  /api/v1/clothes/{id}/available → Check rent conflicts
3. POST /api/v1/orders                 → Create buy order
4. POST /api/v1/orders/{id}/add-payment → Add payment
5. POST /api/v1/orders/{id}/deliver    → Deliver order
6. GET  /api/v1/clothes/{id}/history   → View sale history
```

### Status Values

| Entity | Status | Meaning |
|--------|--------|---------|
| Order | `created` | New order, no payment |
| Order | `partially_paid` | Some payment received |
| Order | `paid` | Full payment, ready to deliver |
| Order | `delivered` | Completed |
| Cloth | `ready_for_rent` | Available |
| Cloth | `sold` | Purchased, permanently unavailable |

