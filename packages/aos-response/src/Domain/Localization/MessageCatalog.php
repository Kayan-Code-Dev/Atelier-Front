<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Localization;

/**
 * Built-in AR/EN catalogs for user-facing replies (no LLM).
 */
final class MessageCatalog
{
    /**
     * @return array<string, array<string, string>>
     */
    public function all(): array
    {
        return [
            'ar' => [
                'empty' => 'لا توجد نتائج لعرضها.',
                'generic_success' => 'تم تنفيذ الطلب بنجاح.',
                'generic_partial' => 'تم تنفيذ جزء من الطلب بنجاح، وتعذّر إكمال بعض الخطوات.',
                'generic_failure' => 'تعذّر إتمام الطلب. يرجى المحاولة لاحقًا أو التواصل مع الدعم.',
                'CreateCustomer.success' => 'تم إنشاء العميل ":name" بنجاح.',
                'SearchCustomer.success' => 'تم العثور على العميل ":name".',
                'CreateReservation.success' => 'تم إنشاء الحجز بنجاح ليوم :day الساعة :time.',
                'CheckAvailability.success' => 'الفستان متاح في التاريخ المطلوب.',
                'CancelReservation.success' => 'تم إلغاء الحجز بنجاح.',
                'GenerateReport.success' => 'مبيعات اليوم بلغت :amount جنيه من خلال :count عملية حجز.',
                'CreateInvoice.success' => 'تم إنشاء الفاتورة رقم :invoice بنجاح.',
                'error.dress_unavailable' => 'لا يمكن إتمام الحجز لأن الفستان محجوز في هذا التاريخ.',
                'error.customer_not_found' => 'لم يتم العثور على العميل المطلوب.',
                'error.permission_denied' => 'ليست لديك صلاحية لتنفيذ هذا الإجراء.',
                'error.validation' => 'البيانات المدخلة غير مكتملة أو غير صحيحة.',
                'error.generic' => 'حدث خطأ أثناء تنفيذ العملية. يرجى المحاولة مرة أخرى.',
                'multi.prefix' => 'تم إنجاز الآتي:',
            ],
            'en' => [
                'empty' => 'There is nothing to show.',
                'generic_success' => 'Your request completed successfully.',
                'generic_partial' => 'Part of your request succeeded; some steps could not be completed.',
                'generic_failure' => 'We could not complete your request. Please try again later or contact support.',
                'CreateCustomer.success' => 'Customer ":name" was created successfully.',
                'SearchCustomer.success' => 'Customer ":name" was found.',
                'CreateReservation.success' => 'Reservation created successfully for :day at :time.',
                'CheckAvailability.success' => 'The dress is available on the requested date.',
                'CancelReservation.success' => 'The reservation was cancelled successfully.',
                'GenerateReport.success' => 'Today\'s sales reached :amount EGP across :count bookings.',
                'CreateInvoice.success' => 'Invoice :invoice was created successfully.',
                'error.dress_unavailable' => 'The reservation cannot be completed because the dress is already booked on this date.',
                'error.customer_not_found' => 'The requested customer was not found.',
                'error.permission_denied' => 'You do not have permission to perform this action.',
                'error.validation' => 'The provided data is incomplete or invalid.',
                'error.generic' => 'Something went wrong while processing your request. Please try again.',
                'multi.prefix' => 'Completed the following:',
            ],
        ];
    }
}
