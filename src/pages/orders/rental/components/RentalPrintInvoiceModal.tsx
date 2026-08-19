import { useEffect, useMemo, useState, type ReactElement } from "react";
import { useQuery } from "@tanstack/react-query";
import type { TOrder } from "@/api/v2/orders/orders.types";
import { useGetOrderDetailsQueryOptions } from "@/api/v2/orders/orders.hooks";
import { getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";
import { loadInvoiceRules } from "@/lib/invoice-print-rules";
import { useOrderEmployeeResolvedName } from "@/components/custom/OrderEmployeeName";
import { mapOrderToRentalPrintView } from "../rentalPrintMapper";

type DocType = "customer" | "admin" | "receipt";

export type RentalPrintInvoiceModalProps = {
  order: TOrder | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  
  defaultDocType?: DocType;
};

const docOptions: { type: DocType; label: string; sublabel: string; icon: string }[] = [
  { type: "customer", label: "فاتورة العميل",    sublabel: "كاملة مع الأسعار والمدفوعات",   icon: "ri-file-text-line" },
  { type: "admin",    label: "النسخة الإدارية",  sublabel: "بدون أسعار — للأرشيف الداخلي",  icon: "ri-file-shield-2-line" },
  { type: "receipt",  label: "إقرار الاستلام",   sublabel: "توثيق تسليم واسترجاع القطع",    icon: "ri-file-check-2-line" },
];


const DEFAULT_PRINT_LOGO_URL = "/dressnmore-logo.jpg";

function effectivePrintLogoUrl(order: TOrder | null): string {
  if (!order) return DEFAULT_PRINT_LOGO_URL;
  const inv = order.inventory?.inventoriable as
    | { image_url?: string; image?: string }
    | undefined;
  const branchImage = inv?.image_url ?? inv?.image ?? null;
  return (typeof branchImage === "string" && branchImage.trim()) || DEFAULT_PRINT_LOGO_URL;
}


const BranchLogo = ({
  small = false,
  logoUrl,
  branchName,
}: {
  small?: boolean;
  logoUrl: string;
  branchName: string;
}) => (
  <div className={`flex items-center gap-3 ${small ? "" : ""}`}>
    <div
      className={`flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-white shadow-md ring-2 ring-slate-200/80 ${small ? "h-10 w-10" : "h-14 w-14"}`}
    >
      <img src={logoUrl} alt={`شعار ${branchName}`} className="h-full w-full object-cover" />
    </div>
    <div>
      <div className={`font-black text-slate-800 tracking-tight leading-tight ${small ? "text-base" : "text-xl"}`}>
        {branchName}
      </div>
      <div className={`text-slate-500 ${small ? "text-[10px]" : "text-xs"} mt-0.5`}>
        للأزياء والمناسبات الراقية
      </div>
    </div>
  </div>
);

export function RentalPrintInvoiceModal({
  order,
  open,
  onOpenChange,
  defaultDocType = "admin",
}: RentalPrintInvoiceModalProps) {
  const orderId = order?.id ?? 0;
  const { data: orderDetails, isPending } = useQuery({
    ...useGetOrderDetailsQueryOptions(orderId),
    enabled: open && orderId > 0,
  });

  const orderToPrint = orderDetails ?? order ?? null;
  const printLogoUrl = useMemo(() => effectivePrintLogoUrl(orderToPrint), [orderToPrint]);
  const resolvedEmployeeName = useOrderEmployeeResolvedName(
    orderToPrint,
    open && !!orderToPrint
  );
  const view = useMemo(
    () =>
      orderToPrint
        ? mapOrderToRentalPrintView(orderToPrint, {
            employeeDisplayName: resolvedEmployeeName,
          })
        : null,
    [orderToPrint, resolvedEmployeeName]
  );
  const currencySymbol = getOrderCurrencyInfo(orderToPrint ?? undefined).currency_symbol;

  const [docType, setDocType] = useState<DocType>(defaultDocType);
  const printRules = loadInvoiceRules();

  useEffect(() => {
    if (open) setDocType(defaultDocType);
  }, [open, defaultDocType]);

  const printDate = new Date().toLocaleDateString("ar-EG", {
    weekday: "long", year: "numeric", month: "long", day: "numeric",
    hour: "2-digit", minute: "2-digit",
  });

  const totalPaid = view?.paidTotal ?? 0;
  const netTotal = view?.pricing.totalWithTax ?? 0;
  const remaining = view?.remainingTotal ?? 0;

  if (!open || !order) return null;
  if (isPending || !view) {
    return (
      <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50" dir="rtl">
        <div className="rounded-xl bg-white px-8 py-6 text-sm text-slate-600 shadow-lg">
          جاري تحميل تفاصيل الطلب...
        </div>
      </div>
    );
  }

  
  const handlePrint = () => {
    const el = document.getElementById("print-doc-area");
    if (!el) return;
    const clone = el.cloneNode(true) as HTMLElement;
    clone.id = "print-rental-clone";
    const portal = document.createElement("div");
    portal.id = "print-rental-portal";
    portal.style.cssText = "position:absolute;left:-9999px;top:0;width:800px;";
    portal.appendChild(clone);
    document.body.appendChild(portal);

    const styleId = "print-rental-print-style";
    const style = document.createElement("style");
    style.id = styleId;
    style.textContent = `
      @media print {
        body > *:not(#print-rental-portal) { display: none !important; }
        #print-rental-portal { display: block !important; position: static !important; left: auto !important; }
        #print-rental-portal, #print-rental-clone { width: 100% !important; }
        #print-rental-clone table { width: 100% !important; }
        #print-rental-clone thead { display: table-header-group !important; }
        #print-rental-clone * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { margin: 10mm; size: A4; }
      }
    `;
    document.head.appendChild(style);

    const cleanup = () => {
      portal.remove();
      document.getElementById(styleId)?.remove();
      window.removeEventListener("afterprint", cleanup);
    };
    window.addEventListener("afterprint", cleanup);
    window.print();
  };

  /* ─── Print date banner ─── */
  const PrintDateBanner = () => (
    <div
      className="flex items-center justify-between text-[10px] px-4 py-2 rounded-lg mb-5"
      style={{ background: "#F1F5F9", color: "#64748B" }}
    >
      <div className="flex items-center gap-1.5">
        <i className="ri-printer-line" />
        <span>تاريخ الطباعة:</span>
        <span className="font-semibold text-slate-700">{printDate}</span>
      </div>
      <div className="flex items-center gap-1.5">
        <i className="ri-git-branch-line" />
        <span>{view.branchName}</span>
      </div>
    </div>
  );

  /* ─── Shared header ─── */
  const SharedHeader = ({ badge, badgeColor = "#334155", subtitle }: { badge?: string; badgeColor?: string; subtitle?: string }) => (
    <div className="mb-6 pb-5 border-b-2 border-slate-800">
      <PrintDateBanner />
      <div className="flex items-start justify-between">
        <BranchLogo logoUrl={printLogoUrl} branchName={view.branchName} />
        <div className="text-right">
          {badge && (
            <div
              className="inline-block text-xs font-bold px-4 py-1.5 rounded-full mb-2"
              style={{ background: badgeColor, color: "#fff" }}
            >
              {badge}
            </div>
          )}
          {subtitle && <div className="text-[10px] text-slate-400 mb-1">{subtitle}</div>}
          <div className="text-xs text-slate-400 mb-0.5">رقم الفاتورة</div>
          <div className="text-3xl font-black text-slate-800">{view.invoiceNumber}</div>
          <div className="text-xs text-slate-400 mt-1.5">تاريخ الإصدار</div>
          <div className="text-sm font-semibold text-slate-700">{view.invoiceDate}</div>
        </div>
      </div>
    </div>
  );

  /* ─── Shared customer + dates block ─── */
  const CustomerDatesBlock = () => (
    <div className="grid grid-cols-2 gap-5 mb-7">
      <div className="bg-slate-50 rounded-lg p-4">
        <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">بيانات العميل</div>
        <div className="text-base font-bold text-slate-800 mb-2">{view.customer.name}</div>
        {[
          { label: "الرقم القومي", value: view.customer.nationalId, mono: true },
          { label: "الهاتف",       value: view.customer.phone,       ltr: true },
          { label: "واتساب",       value: view.customer.whatsapp,    ltr: true },
          { label: "العنوان",      value: view.customer.address },
        ].map((r) => (
          <div key={r.label} className="flex gap-2 text-xs mb-1">
            <span className="text-slate-400 w-20 shrink-0">{r.label}</span>
            <span className={`text-slate-700 ${r.mono ? "font-mono" : ""}`} dir={r.ltr ? "ltr" : undefined}>{r.value}</span>
          </div>
        ))}
      </div>
      <div className="bg-slate-50 rounded-lg p-4">
        <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">تواريخ الطلب</div>
        <div className="space-y-2.5">
          {[
            { label: "تاريخ التسليم",   value: view.dates.delivery,   icon: "ri-calendar-check-line" },
            { label: "تاريخ الفرح",     value: view.dates.event,      icon: "ri-heart-line" },
            { label: "تاريخ الاسترجاع", value: view.dates.returnDate, icon: "ri-calendar-2-line" },
          ].map((d) => (
            <div key={d.label} className="flex items-center justify-between">
              <div className="flex items-center gap-1.5 text-xs text-slate-500">
                <i className={`${d.icon} text-slate-400 text-sm`} />
                {d.label}
              </div>
              <span className="text-sm font-semibold text-slate-800">{d.value}</span>
            </div>
          ))}
        </div>
        <div className="mt-3 pt-3 border-t border-slate-200 flex items-center justify-between">
          <span className="text-xs text-slate-400">الموظف المسؤول</span>
          <span className="text-xs font-semibold text-slate-700">{view.employeeName}</span>
        </div>
      </div>
    </div>
  );

  /* ─── Products table ─── */
  const ProductsTable = ({ showPrices }: { showPrices: boolean }) => (
    <div className="mb-6">
      <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">الأصناف المستأجرة</div>
      <table className="w-full text-sm border-collapse">
        <thead>
          <tr style={{ background: "#0C1A3E" }} className="text-white">
            <th className="text-right py-2.5 px-4 font-semibold">#</th>
            <th className="text-right py-2.5 px-4 font-semibold">اسم الصنف</th>
            <th className="text-right py-2.5 px-4 font-semibold">الفئة</th>
            <th className="text-right py-2.5 px-4 font-semibold">الكمية</th>
            {showPrices && <th className="text-right py-2.5 px-4 font-semibold">سعر الوحدة</th>}
            {showPrices && <th className="text-right py-2.5 px-4 font-semibold">الإجمالي</th>}
          </tr>
        </thead>
        <tbody>
          {view.products.map((p, i) => (
            <tr key={p.productId} className={i % 2 === 0 ? "bg-white" : "bg-slate-50"}>
              <td className="py-2.5 px-4 text-slate-400">{i + 1}</td>
              <td className="py-2.5 px-4 font-medium text-slate-800">{p.name}</td>
              <td className="py-2.5 px-4 text-slate-500">{p.category}</td>
              <td className="py-2.5 px-4 text-slate-600">{p.quantity}</td>
              {showPrices && <td className="py-2.5 px-4 text-slate-700">{p.unitPrice.toLocaleString()} {currencySymbol}</td>}
              {showPrices && <td className="py-2.5 px-4 font-semibold text-slate-800">{(p.unitPrice * p.quantity).toLocaleString()} {currencySymbol}</td>}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );

  /* ─── Terms block ─── */
  const TermsBlock = ({ type }: { type: DocType }) => {
    const terms = printRules[type];
    const colors: Record<DocType, { bg: string; border: string; title: string; bullet: string }> = {
      customer: { bg: "#F0FDF4", border: "#BBF7D0", title: "#166534", bullet: "#22C55E" },
      admin:    { bg: "#FEF3C7", border: "#FDE68A", title: "#92400E", bullet: "#D97706" },
      receipt:  { bg: "#EFF6FF", border: "#BFDBFE", title: "#1E40AF", bullet: "#3B82F6" },
    };
    const c = colors[type];
    return (
      <div
        className="rounded-lg p-4 mb-6"
        style={{ background: c.bg, border: `1px solid ${c.border}` }}
      >
        <div className="flex items-center gap-2 mb-3">
          <i className="ri-shield-check-line text-sm" style={{ color: c.bullet }} />
          <span className="text-xs font-black uppercase tracking-wider" style={{ color: c.title }}>
            {terms.title}
          </span>
        </div>
        {terms.items.length === 0 ? (
          <p className="text-xs italic" style={{ color: c.title, opacity: 0.5 }}>لا توجد قواعد مضافة</p>
        ) : (
          <ol className="space-y-1.5">
            {terms.items.map((item, idx) => (
              <li key={idx} className="flex gap-2 text-xs" style={{ color: c.title }}>
                <span className="font-bold shrink-0" style={{ color: c.bullet }}>{idx + 1}.</span>
                <span className="leading-relaxed opacity-90">{item}</span>
              </li>
            ))}
          </ol>
        )}
      </div>
    );
  };

  /* ─── Signature footer — customer only ─── */
  const CustomerSignatureFooter = () => (
    <div className="border-t-2 border-slate-800 pt-5 mt-2">
      <div className="grid grid-cols-2 gap-8 mb-5">
        {/* Customer signature — large */}
        <div>
          <div className="text-xs font-bold text-slate-600 mb-1">توقيع العميل</div>
          <div className="h-20 border border-dashed border-slate-300 rounded-lg bg-slate-50" />
          <div className="mt-2 flex gap-2 text-xs text-slate-500">
            <span>الاسم:</span>
            <span className="flex-1 border-b border-dashed border-slate-300 pb-0.5">{view.customer.name}</span>
          </div>
          <div className="mt-1.5 flex gap-2 text-xs text-slate-500">
            <span>التاريخ:</span>
            <span className="flex-1 border-b border-dashed border-slate-300 pb-0.5">&nbsp;</span>
          </div>
        </div>
        {/* Stamp */}
        <div className="flex flex-col items-center justify-center">
          <div
            className="w-24 h-24 rounded-full border-2 border-dashed border-slate-300 flex items-center justify-center"
            style={{ background: "#F8FAFC" }}
          >
            <div className="text-center">
              <i className="ri-stamp-line text-2xl text-slate-300" />
              <div className="text-[9px] text-slate-300 mt-0.5">ختم المحل</div>
            </div>
          </div>
        </div>
      </div>
      <div className="text-center text-xs text-slate-400 pt-3 border-t border-slate-100">
        {view.branchName} | {view.invoiceNumber}
      </div>
    </div>
  );

  /* ─── Admin signature footer — no customer ─── */
  const AdminSignatureFooter = () => (
    <div className="border-t-2 border-slate-800 pt-5 mt-2">
      <div className="grid grid-cols-3 gap-4 mb-5">
        {["المدير المسؤول", "موظف الاستلام", "موظف الإرجاع"].map((label) => (
          <div key={label} className="text-center">
            <div className="h-12 border-b border-dashed border-slate-300 mb-2" />
            <div className="text-xs text-slate-400">{label}</div>
          </div>
        ))}
      </div>
      <div className="text-center text-xs text-slate-400">
        {view.branchName} | للاستخدام الداخلي فقط
      </div>
    </div>
  );

  /* ─── Receipt signature footer ─── */
  const ReceiptSignatureFooter = () => (
    <div className="border-t-2 border-slate-800 pt-5 mt-2">
      <div className="grid grid-cols-2 gap-8 mb-5">
        {/* Customer signature only */}
        <div>
          <div className="text-xs font-bold text-slate-600 mb-1">توقيع العميل المستلِم</div>
          <div className="h-20 border border-dashed border-slate-300 rounded-lg bg-slate-50" />
          <div className="mt-2 flex gap-2 text-xs text-slate-500">
            <span>الاسم:</span>
            <span className="flex-1 border-b border-dashed border-slate-300 pb-0.5">{view.customer.name}</span>
          </div>
          <div className="mt-1.5 flex gap-2 text-xs text-slate-500">
            <span>التاريخ:</span>
            <span className="flex-1 border-b border-dashed border-slate-300 pb-0.5">&nbsp;</span>
          </div>
        </div>
        <div>
          <div className="text-xs font-bold text-slate-600 mb-1">توقيع موظف التسليم</div>
          <div className="h-20 border border-dashed border-slate-300 rounded-lg bg-slate-50" />
          <div className="mt-2 flex gap-2 text-xs text-slate-500">
            <span>الموظف:</span>
            <span className="flex-1 border-b border-dashed border-slate-300 pb-0.5">{view.employeeName}</span>
          </div>
          <div className="mt-1.5 flex gap-2 text-xs text-slate-500">
            <span>التاريخ:</span>
            <span className="flex-1 border-b border-dashed border-slate-300 pb-0.5">&nbsp;</span>
          </div>
        </div>
      </div>
      <div className="text-center text-xs text-slate-400 pt-3 border-t border-slate-100">
        {view.branchName} | رقم الفاتورة: {view.invoiceNumber}
      </div>
    </div>
  );

  
  const CustomerInvoiceDoc = () => (
    <div className="text-sm" style={{ fontFamily: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" }}>
      <SharedHeader badge="فاتورة العميل" badgeColor="#166534" />
      <CustomerDatesBlock />
      <ProductsTable showPrices />

      {/* Pricing + Payments */}
      <div className="grid grid-cols-2 gap-5 mb-7">
        <div>
          <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">ملخص الفاتورة</div>
          <div className="space-y-2">
            <div className="flex justify-between text-sm">
              <span className="text-slate-500">الإجمالي الفرعي</span>
              <span className="font-medium">{view.pricing.subtotal.toLocaleString()} {currencySymbol}</span>
            </div>
            {view.pricing.taxAmount > 0 && (
              <div className="flex justify-between text-sm">
                <span className="text-slate-500">الضريبة ({(view.pricing.taxRate * 100).toFixed(0)}%)</span>
                <span className="font-medium">{view.pricing.taxAmount.toLocaleString()} {currencySymbol}</span>
              </div>
            )}
            {view.pricing.discount > 0 && (
              <div className="flex justify-between text-sm">
                <span className="text-amber-600">خصم {view.pricing.discountReason && `(${view.pricing.discountReason})`}</span>
                <span className="text-amber-600 font-medium">- {view.pricing.discount.toLocaleString()} {currencySymbol}</span>
              </div>
            )}
            <div className="flex justify-between pt-2 border-t border-slate-200 font-bold text-base">
              <span>الإجمالي النهائي</span>
              <span>{netTotal.toLocaleString()} {currencySymbol}</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-emerald-600">إجمالي المدفوع</span>
              <span className="font-semibold text-emerald-700">{totalPaid.toLocaleString()} {currencySymbol}</span>
            </div>
            <div className="flex justify-between bg-rose-50 rounded-lg px-3 py-2">
              <span className="text-rose-700 font-bold">المتبقي</span>
              <span className="font-bold text-rose-700">{remaining.toLocaleString()} {currencySymbol}</span>
            </div>
          </div>
        </div>
        <div>
          <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">سجل المدفوعات</div>
          {view.paymentHistory.length === 0 ? (
            <p className="text-xs text-slate-400 italic">لا توجد مدفوعات مسجلة</p>
          ) : (
            <div className="space-y-1.5">
              {view.paymentHistory.map((pay) => (
                <div key={pay.id} className="flex justify-between items-start bg-slate-50 rounded px-3 py-1.5">
                  <div>
                    <div className="text-xs font-medium text-slate-700">{pay.method}</div>
                    <div className="text-xs text-slate-400">{pay.date}{pay.note ? ` — ${pay.note}` : ""}</div>
                  </div>
                  <span className="text-sm font-bold text-emerald-700">{pay.amount.toLocaleString()} {currencySymbol}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {view.notes && (
        <div className="mb-5 bg-amber-50 border border-amber-100 rounded-lg p-3">
          <div className="text-xs font-bold text-amber-700 mb-1">ملاحظات</div>
          <div className="text-xs text-amber-800">{view.notes}</div>
        </div>
      )}

      <TermsBlock type="customer" />
      <CustomerSignatureFooter />
    </div>
  );

  
  const AdminCopyDoc = () => (
    <div className="text-sm" style={{ fontFamily: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" }}>
      <SharedHeader badge="نسخة إدارية — سري" badgeColor="#7C3AED" subtitle="للاستخدام الداخلي فقط — لا تُشارك مع العميل" />
      <CustomerDatesBlock />
      <ProductsTable showPrices={false} />

      {/* Admin-only info block */}
      <div className="bg-slate-50 rounded-lg p-4 mb-6 border border-slate-200">
        <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">بيانات داخلية</div>
        <div className="grid grid-cols-2 gap-x-8 gap-y-2 text-xs">
          {[
            { label: "الفرع",           value: view.branchName },
            { label: "الموظف",          value: view.employeeName },
            { label: "حالة الفاتورة",   value: view.statusLabel },
            { label: "حالة الدفع",      value: view.paymentStatusLabel },
            { label: "عدد الأصناف",     value: `${view.products.length} صنف` },
            { label: "إجمالي الكميات",  value: `${view.products.reduce((s, p) => s + p.quantity, 0)} قطعة` },
          ].map(({ label, value }) => (
            <div key={label} className="flex justify-between border-b border-slate-100 pb-1.5">
              <span className="text-slate-400">{label}</span>
              <span className="font-medium text-slate-700">{value}</span>
            </div>
          ))}
        </div>
      </div>

      <div className="mb-5 min-h-[60px] border border-dashed border-slate-300 rounded-lg p-3">
        <div className="text-xs font-semibold text-slate-500 mb-1">ملاحظات داخلية</div>
        <div className="text-xs text-slate-600">{view.notes || "—"}</div>
      </div>

      <TermsBlock type="admin" />
      <AdminSignatureFooter />
    </div>
  );

  
  const ReceiptAcknowledgmentDoc = () => (
    <div className="text-sm" style={{ fontFamily: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" }}>
      {/* Centered header */}
      <div className="mb-6 pb-5 border-b-2 border-slate-800">
        <PrintDateBanner />
        <div className="flex items-center justify-between">
          <BranchLogo logoUrl={printLogoUrl} branchName={view.branchName} />
          <div className="text-center">
            <div
              className="text-lg font-black text-slate-800 border-2 border-slate-800 px-6 py-2 rounded-lg inline-block"
            >
              إقرار استلام مستأجرات
            </div>
            <div className="text-xs text-slate-400 mt-1.5">
              رقم: {view.invoiceNumber} | التاريخ: {view.invoiceDate}
            </div>
          </div>
          <div className="text-right">
            <div
              className="inline-block text-xs font-bold px-3 py-1 rounded-full"
              style={{ background: "#1D4ED8", color: "#fff" }}
            >
              إقرار رسمي
            </div>
          </div>
        </div>
      </div>

      {/* Acknowledgment text */}
      <div className="bg-slate-50 rounded-lg p-5 mb-6 border border-slate-200 leading-relaxed">
        <p className="text-sm text-slate-700">
          أنا الموقّع أدناه /&nbsp;
          <span className="font-bold text-slate-900 border-b border-dashed border-slate-400 px-1">{view.customer.name}</span>،
          حامل الهوية رقم:&nbsp;
          <span className="font-mono font-bold text-slate-900">{view.customer.nationalId}</span>،
          أُقرّ وأُؤكد بأنني قد استلمت القطع المبيّنة أدناه من&nbsp;
          <strong>{view.branchName}</strong>
          &nbsp;بتاريخ <span className="font-semibold">{view.dates.delivery}</span>،
          للاستخدام في مناسبة بتاريخ <span className="font-semibold">{view.dates.event}</span>،
          وأتعهد بإعادتها كاملةً وسليمةً بتاريخ&nbsp;
          <span className="font-semibold text-rose-700">{view.dates.returnDate}</span> على أقصى تقدير.
        </p>
      </div>

      {/* Items */}
      <div className="mb-6">
        <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">القطع المستلمة</div>
        <table className="w-full border-collapse text-sm">
          <thead>
            <tr style={{ background: "#0C1A3E" }} className="text-white">
              <th className="text-right py-2.5 px-4">#</th>
              <th className="text-right py-2.5 px-4">الصنف</th>
              <th className="text-right py-2.5 px-4">الفئة</th>
              <th className="text-right py-2.5 px-4">الكمية</th>
              <th className="text-right py-2.5 px-4">الحالة عند الاستلام</th>
            </tr>
          </thead>
          <tbody>
            {view.products.map((p, i) => (
              <tr key={p.productId} className={i % 2 === 0 ? "bg-white" : "bg-slate-50"}>
                <td className="py-2.5 px-4 text-slate-400">{i + 1}</td>
                <td className="py-2.5 px-4 font-medium text-slate-800">{p.name}</td>
                <td className="py-2.5 px-4 text-slate-500">{p.category}</td>
                <td className="py-2.5 px-4 text-slate-600">{p.quantity}</td>
                <td className="py-2.5 px-4">
                  <span className="text-xs border border-emerald-200 text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">
                    سليمة ✓
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Return info */}
      <div className="grid grid-cols-2 gap-5 mb-5">
        <div className="border border-dashed border-slate-300 rounded-lg p-4">
          <div className="text-xs font-bold text-slate-600 mb-3">خانة استلام الإرجاع</div>
          <div className="space-y-2 text-xs text-slate-500">
            {["تاريخ التسليم الفعلي", "حالة القطع عند الإرجاع", "ملاحظات"].map((lbl) => (
              <div key={lbl} className="flex justify-between">
                <span>{lbl}</span>
                <span className="w-28 border-b border-dashed border-slate-300">&nbsp;</span>
              </div>
            ))}
          </div>
        </div>
        <div className="border border-dashed border-slate-300 rounded-lg p-4">
          <div className="text-xs font-bold text-slate-600 mb-3">تواريخ مهمة</div>
          <div className="space-y-2 text-xs">
            <div className="flex justify-between">
              <span className="text-slate-400">موعد الفرح</span>
              <span className="font-semibold text-slate-700">{view.dates.event}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-400">آخر موعد للإرجاع</span>
              <span className="font-semibold text-rose-600">{view.dates.returnDate}</span>
            </div>
          </div>
        </div>
      </div>

      {view.notes && (
        <div className="mb-5 bg-amber-50 border border-amber-100 rounded-lg p-3">
          <div className="text-xs font-bold text-amber-700 mb-1">ملاحظات خاصة</div>
          <div className="text-xs text-amber-800">{view.notes}</div>
        </div>
      )}

      <TermsBlock type="receipt" />
      <ReceiptSignatureFooter />
    </div>
  );

  const docMap: Record<DocType, ReactElement> = {
    customer: <CustomerInvoiceDoc />,
    admin:    <AdminCopyDoc />,
    receipt:  <ReceiptAcknowledgmentDoc />,
  };

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex" dir="rtl">
      {/* Left Panel */}
      <div className="no-print w-64 bg-slate-900 text-white flex flex-col shrink-0">
        <div className="p-5 border-b border-slate-700">
          <div className="font-bold text-sm mb-0.5">خيارات الطباعة</div>
          <div className="text-xs text-slate-400">فاتورة {view.invoiceNumber}</div>
        </div>

        <div className="p-3 flex-1 space-y-2">
          {docOptions.map((opt) => (
            <button
              key={opt.type}
              onClick={() => setDocType(opt.type)}
              className={`w-full text-right px-4 py-3 rounded-lg transition-colors cursor-pointer ${
                docType === opt.type ? "bg-white text-slate-900" : "text-slate-300 hover:bg-slate-800"
              }`}
            >
              <div className="flex items-start gap-3">
                <div className={`w-8 h-8 flex items-center justify-center rounded-md shrink-0 mt-0.5 ${
                  docType === opt.type ? "bg-slate-900 text-white" : "bg-slate-700 text-slate-300"
                }`}>
                  <i className={`${opt.icon} text-base`} />
                </div>
                <div>
                  <div className="font-semibold text-sm">{opt.label}</div>
                  <div className="text-xs mt-0.5 text-slate-500">{opt.sublabel}</div>
                </div>
              </div>
            </button>
          ))}
        </div>

        <div className="p-4 border-t border-slate-700 space-y-2">
          <button
            onClick={handlePrint}
            className="w-full flex items-center justify-center gap-2 bg-white text-slate-900 py-2.5 rounded-lg font-semibold text-sm hover:bg-slate-100 transition-colors cursor-pointer whitespace-nowrap"
          >
            <i className="ri-printer-line" />
            طباعة
          </button>
          <button
            onClick={() => onOpenChange(false)}
            className="w-full flex items-center justify-center gap-2 border border-slate-600 text-slate-300 py-2 rounded-lg text-sm hover:bg-slate-800 transition-colors cursor-pointer whitespace-nowrap"
          >
            <i className="ri-close-line" />
            إغلاق
          </button>
        </div>
      </div>

      {/* Right Panel */}
      <div className="flex-1 overflow-y-auto bg-slate-100 p-8">
        <div className="no-print flex items-center justify-between mb-4">
          <div className="flex items-center gap-2 text-sm text-slate-600">
            <i className={`${docOptions.find((d) => d.type === docType)?.icon} text-slate-500`} />
            <span className="font-medium">{docOptions.find((d) => d.type === docType)?.label}</span>
          </div>
          <div className="text-xs text-slate-400">معاينة قبل الطباعة</div>
        </div>

        <div
          id="print-doc-area"
          className="bg-white rounded-xl w-full max-w-[750px] mx-auto min-h-[1000px] p-10"
        >
          {docMap[docType]}
        </div>
      </div>
    </div>
  );
}
