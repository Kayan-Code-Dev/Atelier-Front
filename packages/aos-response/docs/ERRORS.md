# Errors

`ErrorResponseGenerator` maps technical codes to user language:

| Code | User message (AR) |
|------|-------------------|
| `ReservationToolException` / `dress_unavailable` | لا يمكن إتمام الحجز لأن الفستان محجوز… |
| `customer_not_found` | لم يتم العثور على العميل… |
| `permission_denied` | ليست لديك صلاحية… |
| default | رسالة عامة بدون Exception/Stack |

`ResponsePolicy` strips Exception/Stack/SQL tokens and blocks secret payload keys.
