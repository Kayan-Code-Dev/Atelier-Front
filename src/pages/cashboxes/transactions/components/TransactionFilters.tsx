type ExpenseCategoryOption = { slug: string; label: string };

const controlClass =
  "flex h-10 min-h-10 w-full items-center box-border rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-400 focus:outline-none";

const dateControlClass = `${controlClass} py-0 [&::-webkit-datetime-edit]:m-0 [&::-webkit-datetime-edit]:p-0 [&::-webkit-datetime-edit-fields-wrapper]:p-0`;

const labelClass =
  "mb-1.5 flex min-h-10 max-w-full items-end text-xs leading-snug text-gray-500";

const fieldColClass = "flex min-w-0 flex-col";

type Props = {
  startDate: string;
  endDate: string;
  sort: "asc" | "desc";
  typeFilter: string;
  expenseCategory: string;
  paymentType: string;
  expenseCategoryOptions: ExpenseCategoryOption[];
  onFiltersChange: (updates: Record<string, string>) => void;
  onReset: () => void;
};

export function TransactionFilters({
  startDate,
  endDate,
  sort,
  typeFilter,
  expenseCategory,
  paymentType,
  expenseCategoryOptions,
  onFiltersChange,
  onReset,
}: Props) {
  const hasActive = !!(
    startDate ||
    endDate ||
    typeFilter ||
    expenseCategory ||
    paymentType
  );

  return (
    <div className="w-full min-w-0 rounded-xl border border-blue-100 bg-white p-4" dir="rtl">
      <div
        className={`grid w-full min-w-0 items-end gap-3 sm:grid-cols-2 ${
          hasActive
            ? "lg:grid-cols-[repeat(6,minmax(0,1fr))_auto]"
            : "lg:grid-cols-6"
        }`}
      >
        <div className={fieldColClass}>
          <label className={labelClass}>من تاريخ</label>
          <input
            type="date"
            value={startDate}
            onChange={(e) => onFiltersChange({ start_date: e.target.value })}
            className={dateControlClass}
          />
        </div>
        <div className={fieldColClass}>
          <label className={labelClass}>إلى تاريخ</label>
          <input
            type="date"
            value={endDate}
            onChange={(e) => onFiltersChange({ end_date: e.target.value })}
            className={dateControlClass}
          />
        </div>
        <div className="min-w-[160px]">
          <label className={labelClass}>الترتيب</label>
          <select
            value={sort}
            onChange={(e) => onFiltersChange({ sort: e.target.value })}
            className={controlClass}
          >
            <option value="desc">الأحدث أولاً</option>
            <option value="asc">الأقدم أولاً</option>
          </select>
        </div>
        <div className={fieldColClass}>
          <label className={labelClass}>اتجاه الحركة</label>
          <select
            value={typeFilter}
            onChange={(e) => onFiltersChange({ type: e.target.value })}
            className={controlClass}
          >
            <option value="">الكل (إيراد + مصروف + عكس)</option>
            <option value="income">إيراد فقط</option>
            <option value="expense">مصروف فقط</option>
            <option value="reversal">عكس / استرداد فقط</option>
            <option value="income,expense">إيراد + مصروف (بدون عكس)</option>
            <option value="income,expense,reversal">كل الأنواع صراحة</option>
          </select>
        </div>
        <div className="min-w-[200px]">
          <label className={labelClass}>تصنيف مصروف</label>
          <select
            value={expenseCategory}
            onChange={(e) => onFiltersChange({ expense_category: e.target.value })}
            className={controlClass}
          >
            <option value="">— لا يُطبَّق على إيرادات —</option>
            {expenseCategoryOptions.map((o) => (
              <option key={o.slug} value={o.slug}>
                {o.label}
              </option>
            ))}
          </select>
        </div>
        <div className={fieldColClass}>
          <label className={labelClass}>نوع دفعة الطلب/التفصيل</label>
          <select
            value={paymentType}
            onChange={(e) => onFiltersChange({ payment_type: e.target.value })}
            className={controlClass}
          >
            <option value="">—</option>
            <option value="initial">مبدئي</option>
            <option value="fee">رسوم</option>
            <option value="normal">عادي</option>
            <option value="initial,normal">مبدئي + عادي</option>
          </select>
        </div>
        {hasActive && (
          <div className="flex items-end sm:col-span-2 lg:col-span-1 lg:justify-end">
            <button
              type="button"
              onClick={onReset}
              className="flex h-10 min-h-10 cursor-pointer items-center justify-center gap-1 whitespace-nowrap rounded-lg border border-red-200 px-4 text-sm text-red-500 transition-colors hover:bg-red-50"
            >
              <i className="ri-refresh-line ml-1" />
              مسح الفلاتر
            </button>
          </div>
        )}
      </div>
      <p className="mt-2 text-xs text-slate-400">
        فلتر «تصنيف المصروف» و«نوع الدفعة» يُرسلان معاً في الطلب؛ للعرض المعتاد يُفضّل الاعتماد على أحدهما.
      </p>
    </div>
  );
}
