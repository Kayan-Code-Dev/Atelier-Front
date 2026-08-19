# Business Intents Catalog

Intents are **what the customer/system is trying to achieve**. They map to Use Cases and Tools. Unknown must never invent side effects.

## Conversation hygiene
| Intent | Description | Primary UCs |
|--------|-------------|-------------|
| `Greeting` | Hello / salam | UC-HYG-01 |
| `Goodbye` | Closing thanks | UC-HYG-02 |
| `SmallTalk` | Non-business chat | UC-HYG-03 |
| `SilenceTimeout` | No customer reply (system) | UC-HYG-04 |
| `ReturningCustomer` | Resume after long gap | UC-HYG-05 |

## Knowledge
| Intent | Description | Primary UCs |
|--------|-------------|-------------|
| `AskWorkingHours` | Hours / open now | UC-KNOW-01 |
| `AskLocation` | Address / map | UC-KNOW-02 |
| `AskCancellationPolicy` | Cancel/refund policy | UC-KNOW-03 |
| `AskSeasonalOffers` | Promos | UC-KNOW-04 |
| `AskPaymentMethods` | How to pay | UC-PAY-01 |

## Catalog & sales
| Intent | Description | Primary UCs |
|--------|-------------|-------------|
| `AskAvailability` | What is available | UC-CAT-01 |
| `CheckItemAvailability` | Specific item/date | UC-CAT-02 |
| `AskSizeGuide` | Measurements/sizes | UC-CAT-03 |
| `AskPrice` | Pricing | UC-SALES-01 |
| `RentDress` | Rental journey | UC-SALES-02 |
| `RequestTailoring` | Custom/tailor | UC-SALES-03 |
| `UnsupportedService` | Not offered | UC-SALES-04 |

## Appointments
| Intent | Description | Primary UCs |
|--------|-------------|-------------|
| `BookAppointment` | Book fitting/slot | UC-APPT-01 |
| `ConfirmAppointment` | Confirm details | UC-APPT-02 |
| `RescheduleAppointment` | Move slot | UC-APPT-03 |
| `CancelAppointment` | Cancel slot | UC-APPT-04 |

## Orders / invoices / delivery
| Intent | Description | Primary UCs |
|--------|-------------|-------------|
| `TrackOrder` | Order status | UC-ORD-01 |
| `CancelOrder` | Cancel order request | UC-ORD-02 |
| `AskInvoice` | Invoice copy | UC-INV-01 |
| `AskBalance` | Amount due / deposit | UC-INV-02 |
| `SubmitPaymentProof` | Receipt/transfer proof | UC-PAY-02 |
| `AskDeliveryStatus` | Delivery/pickup timing | UC-DEL-01 |
| `PostponeDelivery` | Delay delivery/pickup | UC-DEL-02 |

## Exceptions & collaboration
| Intent | Description | Primary UCs |
|--------|-------------|-------------|
| `Complaint` | Complaint / dissatisfaction | UC-CMP-01 |
| `RequestDiscount` | Ask discount | UC-CMP-02 |
| `RequestException` | Exception / special case | UC-CMP-03 |
| `EscalateHuman` | Explicit human request | UC-HUM-01 |
| `RequestManager` | Ask manager | UC-CMP-03 |

## Identity & media
| Intent | Description | Primary UCs |
|--------|-------------|-------------|
| `ProvideCustomerData` | Share profile/data | UC-ID-01 |
| `ChangePhoneNumber` | Update phone | UC-ID-02 |
| `SendImage` | Image media | UC-MED-01 |
| `SendVoiceNote` | Audio media | UC-MED-02 |
| `SendDocument` | PDF/doc | UC-MED-03 |

## Meta
| Intent | Description | Primary UCs |
|--------|-------------|-------------|
| `UnknownIntent` | Cannot classify safely | Clarify or Escalate |
| `ConflictingIntents` | Multiple incompatible goals | Clarify or Escalate |
| `SafetyViolation` | Abuse/threat/illegal | Block / Escalate |

## Automation-originated intents (system)
| Intent | Description | Primary UCs |
|--------|-------------|-------------|
| `AppointmentReminder` | Reminder outbound | UC-AUTO-01 |
| `FeedbackRequest` | Post-service ask | UC-AUTO-02 |
