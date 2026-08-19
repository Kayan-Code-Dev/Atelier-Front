# Tool Dependency Graph

Logical dependencies for planning (not DB FKs). Solid edges = typically required before; dashed = optional enrichment.

```mermaid
flowchart TB
  subgraph identity [Identity]
    SearchCustomer
    GetCustomerProfile
    UpsertCustomerProfile
  end

  subgraph catalog [Catalog]
    ResolveProduct
    SearchProducts
    SearchAvailability
    CheckItemAvailability
    GetPublishedPricing
  end

  subgraph book [Reservations]
    FindAvailableSlots
    CreateReservation
    GetReservation
    RescheduleReservation
    CancelReservation
  end

  subgraph money [Money]
    GetOutstandingBalance
    GetInvoice
    RegisterPaymentProof
    RequestApproval
    MarkInvoicePaid
  end

  subgraph collab [Collab]
    GenerateConversationSummary
    TransferConversation
    CreateTask
    NotifyStaff
  end

  SearchCustomer --> GetCustomerProfile
  UpsertCustomerProfile --> GetCustomerProfile

  ResolveProduct --> CheckItemAvailability
  SearchAvailability --> CheckItemAvailability
  CheckItemAvailability --> CreateRentalHold
  GetPublishedPricing --> GenerateQuotation
  GenerateQuotation --> AcceptQuotation

  GetCustomerProfile --> FindAvailableSlots
  FindAvailableSlots --> CreateReservation
  CreateReservation --> NotifyCustomer
  GetReservation --> RescheduleReservation
  GetReservation --> CancelReservation
  FindAvailableSlots --> RescheduleReservation

  GetCustomerProfile --> GetOutstandingBalance
  GetCustomerProfile --> ListOpenOrdersForCustomer
  ListOpenOrdersForCustomer --> GetOrderStatus
  GetOrderStatus --> TrackDelivery
  GetInvoice --> SendInvoiceDocument
  RegisterPaymentProof --> CreateTask
  RegisterPaymentProof --> NotifyStaff
  RegisterPaymentProof --> RequestApproval
  RequestApproval --> MarkInvoicePaid

  GenerateConversationSummary --> TransferConversation
  TransferConversation --> NotifyStaff
  TransferConversation --> CreateTask
  CreateComplaint --> TransferConversation
```

## Example plans (from Use Cases)

### “المتبقي عليّ وأحجز بروفة”
1. `SearchCustomer` / `GetCustomerProfile`  
2. `GetOutstandingBalance`  
3. `FindAvailableSlots`  
4. `CreateReservation` (Approval if required)  
5. Reply composition (not a Tool)

### “استئجار فستان لتاريخ معيّن”
1. Qualify → `SearchAvailability`  
2. `CheckItemAvailability`  
3. Optional `CreateRentalHold`  
4. `FindAvailableSlots` → `CreateReservation`  
5. `NotifyCustomer` / Reply  

### “إيصال تحويل”
1. `GetCustomerProfile`  
2. Optional `GetInvoice` / `GetOutstandingBalance`  
3. `RegisterPaymentProof`  
4. `CreateTask` + `NotifyStaff`  
5. **Not** `MarkInvoicePaid` without Approval/Human  
