interface ProductLine {
  name: string;
  category: string;
  quantity: number;
  unitPrice: number;
}

interface SummaryData {
  branch: string;
  employee: string;
  customerName: string;
  deliveryDate: string;
  eventDate: string;
  returnDate: string;
  products: ProductLine[];
  vatMode: "none" | "percentage" | "fixed";
  vatPercentagePoints: number | null;
  vatFixedAmount?: number;
  deposit: number;
  discount: number;
  discountReason: string;
}

type Props = {
  data: SummaryData;
};

export function RentalCreateInvoiceSummary({ data }: Props) {
  const subtotal = data.products.reduce((s, p) => s + p.unitPrice * p.quantity, 0);
  const taxAmount =
    data.vatMode === "percentage" && data.vatPercentagePoints != null
      ? subtotal * (data.vatPercentagePoints / 100)
      : data.vatMode === "fixed" && data.vatFixedAmount != null
        ? data.vatFixedAmount
        : 0;
  const totalWithTax = subtotal + taxAmount;
  const afterDiscount = totalWithTax - data.discount;
  const remaining = Math.max(0, afterDiscount - data.deposit);

  const isEmpty = !data.customerName && data.products.length === 0;

  return (
    <div className="sticky top-6 rounded-lg border border-slate-200 bg-white p-5">
      <div className="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
        <div className="flex h-8 w-8 items-center justify-center rounded-md bg-amber-50">
          <i className="ri-file-text-line text-lg text-amber-600" />
        </div>
        <h3 className="text-sm font-bold text-slate-800">ملخص الفاتورة</h3>
      </div>

      {isEmpty ? (
        <div className="py-8 text-center">
          <i className="ri-file-add-line mb-2 block text-3xl text-slate-300" />
          <p className="text-xs text-slate-400">
            ابدأ بإدخال بيانات الفاتورة وستظهر هنا تلقائياً
          </p>
        </div>
      ) : (
        <div className="space-y-4 text-sm">
          {(data.branch || data.employee) && (
            <div className="space-y-1.5">
              {data.branch && (
                <div className="flex items-center justify-between">
                  <span className="text-xs text-slate-400">الفرع</span>
                  <span className="text-xs font-medium text-slate-700">{data.branch}</span>
                </div>
              )}
              {data.employee && (
                <div className="flex items-center justify-between">
                  <span className="text-xs text-slate-400">الموظف</span>
                  <span className="text-xs font-medium text-slate-700">{data.employee}</span>
                </div>
              )}
            </div>
          )}

          {data.customerName && (
            <div className="rounded-md border border-slate-100 bg-slate-50 p-3">
              <div className="mb-1.5 text-xs font-semibold text-slate-600">العميل</div>
              <div className="text-sm font-bold text-slate-800">{data.customerName}</div>
            </div>
          )}

          {(data.deliveryDate || data.eventDate || data.returnDate) && (
            <div className="space-y-1">
              <div className="mb-1 text-xs font-semibold text-slate-600">التواريخ</div>
              {data.deliveryDate && (
                <div className="flex items-center justify-between">
                  <span className="text-xs text-slate-400">الاستلام</span>
                  <span className="text-xs text-slate-700">{data.deliveryDate}</span>
                </div>
              )}
              {data.eventDate && (
                <div className="flex items-center justify-between">
                  <span className="text-xs text-slate-400">الفرح</span>
                  <span className="text-xs text-slate-700">{data.eventDate}</span>
                </div>
              )}
              {data.returnDate && (
                <div className="flex items-center justify-between">
                  <span className="text-xs text-slate-400">الاسترجاع</span>
                  <span className="text-xs text-slate-700">{data.returnDate}</span>
                </div>
              )}
            </div>
          )}

          {data.products.length > 0 && (
            <div>
              <div className="mb-2 text-xs font-semibold text-slate-600">الأصناف المختارة</div>
              <div className="space-y-1.5">
                {data.products.map((p, i) => (
                  <div key={i} className="flex items-start justify-between gap-2">
                    <div className="flex-1">
                      <div className="text-xs font-medium leading-tight text-slate-700">
                        {p.name}
                      </div>
                      <div className="text-xs text-slate-400">{p.category}</div>
                    </div>
                    <div className="whitespace-nowrap text-xs font-semibold text-slate-700">
                      {(p.unitPrice * p.quantity).toLocaleString("ar-EG")} ج.م
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {data.products.length > 0 && (
            <div className="space-y-2 border-t border-slate-100 pt-3">
              <div className="flex items-center justify-between">
                <span className="text-xs text-slate-500">الإجمالي قبل الضريبة</span>
                <span className="text-xs font-medium text-slate-700">
                  {subtotal.toLocaleString("ar-EG")} ج.م
                </span>
              </div>
              {taxAmount > 0 && (
                <div className="flex items-center justify-between">
                  <span className="text-xs text-slate-500">
                    {data.vatMode === "percentage" &&
                    data.vatPercentagePoints != null
                      ? `الضريبة (${data.vatPercentagePoints}%)`
                      : "الضريبة (مبلغ ثابت)"}
                  </span>
                  <span className="text-xs font-medium text-slate-700">
                    {taxAmount.toLocaleString("ar-EG")} ج.م
                  </span>
                </div>
              )}
              {data.discount > 0 && (
                <div className="flex items-center justify-between">
                  <span className="text-xs text-amber-600">خصم</span>
                  <span className="text-xs font-medium text-amber-600">
                    - {data.discount.toLocaleString("ar-EG")} ج.م
                  </span>
                </div>
              )}
              <div className="flex items-center justify-between border-t border-slate-200 pt-2">
                <span className="text-sm font-semibold text-slate-700">الإجمالي النهائي</span>
                <span className="text-sm font-bold text-slate-800">
                  {afterDiscount.toLocaleString("ar-EG")} ج.م
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-xs font-medium text-emerald-600">العربون المدفوع</span>
                <span className="text-xs font-semibold text-emerald-700">
                  {data.deposit.toLocaleString("ar-EG")} ج.م
                </span>
              </div>
              <div className="flex items-center justify-between rounded-md border border-rose-100 bg-rose-50 px-3 py-2">
                <span className="text-sm font-semibold text-rose-700">المتبقي</span>
                <span className="text-sm font-bold text-rose-700">
                  {remaining.toLocaleString("ar-EG")} ج.م
                </span>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export type { SummaryData };
