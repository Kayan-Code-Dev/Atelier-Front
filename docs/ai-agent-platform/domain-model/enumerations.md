# Domain Enumerations

Official named sets. Each value has one meaning project-wide.

## Conversation Status
| Value | Meaning |
|-------|---------|
| `New` | Accepted, not yet actively owned for work |
| `ActiveAI` | Digital employee owns the conversation |
| `AwaitingCustomer` | Waiting on customer reply |
| `ToolRunning` | Tool execution in flight |
| `PendingApproval` | Waiting on human approval |
| `ActiveHuman` | Human staff owns replies |
| `Snoozed` | Deferred until a wake condition |
| `Resolved` | Operationally finished |
| `Closed` | Archived |
| `Blocked` | Safety/legal stop |

## Conversation Priority
`Low` · `Normal` · `High` · `Urgent` — staffing urgency, not channel priority alone.

## Conversation Source
`InboundChannel` · `StaffOutbound` · `Automation` · `System` — how the conversation originated.

## Conversation Outcome
`InformationalResolved` · `BookingAssisted` · `HandedToHuman` · `Abandoned` · `Blocked` · `Unknown` — closed-result classification for analytics.

## Operating Mode
`Assistant` · `Hybrid` · `FullAuto` — autonomy overlay (see architecture).

## Agent Status
`Draft` · `Testing` · `Active` · `Paused` · `Retired`

## Agent Health
`Healthy` · `Degraded` · `Down` · `Unknown`

## Permission Effect
`Allow` · `Deny` · `RequireHumanApproval`

## Permission Level (optional coarse UI grouping)
`None` · `Read` · `WriteBounded` · `Sensitive` · `Forbidden`

## Message Type
`Text` · `Image` · `Audio` · `Document` · `SystemNotice` · `Template`

## Message Direction
`Inbound` · `Outbound` · `Internal` (staff/system note not sent to customer)

## Message Author Kind
`Customer` · `AIAgent` · `HumanStaff` · `System`

## Channel Type
`WhatsApp` · `FacebookMessenger` · `Instagram` · `WebChat` · `MobileAppChat` · `Telegram` · `Email`

## Channel Account Status
`Connecting` · `Active` · `Paused` · `Disconnected` · `Error`

## Ownership
`AI` · `Human` · `SharedAssist`

## Task Status
`Open` · `InProgress` · `Done` · `Cancelled`

## Approval Status
`Requested` · `Granted` · `Rejected` · `TimedOut`

## Workflow Status
`Draft` · `Active` · `Paused` · `Retired`

## Automation Trigger
`TimeSchedule` · `DomainEvent` · `ConversationState` · `SLABreach` · `Manual`

## Tool Status (catalog)
`Registered` · `Enabled` · `Disabled` · `Deprecated`

## Tool Execution Status
`Authorized` · `Running` · `Succeeded` · `Failed` · `Denied`

## Learning Status
`Captured` · `Reviewed` · `Accepted` · `Rejected`

## Knowledge Document Status
`Draft` · `Published` · `Superseded` · `Archived`

## Notification Type
`Handover` · `ApprovalNeeded` · `ToolFailure` · `ChannelHealth` · `DailyDigest` · `System`

## Escalation Reason
`CustomerRequestedHuman` · `LowConfidence` · `PermissionDenied` · `RequiresApproval` · `ToolFailure` · `Safety` · `PolicyQuietHours` · `StaffForceTake` · `AmbiguousIntent` · `HighRiskFinancial`

## Confidence Level
`Low` · `Medium` · `High` — bands derived from Confidence Score.

## Context Provider Type
`Policy` · `Persona` · `Memory` · `Customer` · `OperationalFacts` · `Knowledge` · `Settings` · `ChannelLimits`

## Business Object Type (reference only)
`Customer` · `Invoice` · `RentalOrder` · `TailoringOrder` · `SalesOrder` · `DressProduct` · `Delivery` · `Payment` · `AppointmentOrBookingSignal`
