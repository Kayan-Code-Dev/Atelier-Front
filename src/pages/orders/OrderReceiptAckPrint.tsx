import { TOrder } from "@/api/v2/orders/orders.types";
import { OrderEmployeeName } from "@/components/custom/OrderEmployeeName";
import { getOrderCurrencyInfo, getOrderTypeLabel, getItemReceiptDisplay } from "@/api/v2/orders/order.utils";
import { formatPhone } from "@/utils/formatPhone";

const HEADER_BG = "#5170ff";

/** Receipt acknowledgment rules and instructions — at the bottom of the page */
const RULES_ITEMS = [
  "• ميعاد استلام الفستان من 1 ظهراً حتى 7 مساءً وإحضار 2000 جنيه تأمين للزفاف و 500 للسواريه.",
  "• لا يمكن استرجاع أو استبدال الفساتين بعد مرور 3 أيام من تاريخ الشراء إلا في حالة وجود عيب مصنعي.",
  "• يجب إحضار الفاتورة الأصلية مع البطاقة الشخصية عند الإرجاع أو الاستلام أو الاستبدال.",
];

type Props = {
  order: TOrder;
  logoUrl?: string;
};

function formatDate(s: string): string {
  if (!s) return "-";
  try {
    const d = new Date(s);
    if (isNaN(d.getTime())) return s;
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  } catch {
    return s;
  }
}


export function OrderReceiptAckPrint({
  order,
  logoUrl = "/dressnmore-logo.jpg",
}: Props) {
  const c = order.client;
  const clientName = c?.name?.trim() || "-";
  const nationalId = (c as { national_id?: string })?.national_id ?? "-";
  const addressStr = c?.address
    ? [c.address.street, c.address.building].filter(Boolean).join(" - ") || "-"
    : "-";
  const phones = c?.phones?.filter(p => p.phone?.trim()) || [];
  const phoneStr = phones.length > 0
    ? phones.map(p => {
        const phoneType = p.type === 'mobile' ? 'موبايل' : p.type === 'whatsapp' ? 'واتساب' : p.type || 'هاتف';
        const num = formatPhone(p.phone, "");
        return num ? `${num} (${phoneType})` : "";
      }).filter(Boolean).join(" - ")
    : null;
  const items = order.items ?? [];
  const startDate = order.delivery_date
    ? formatDate(order.delivery_date)
    : (items[0] as { delivery_date?: string })?.delivery_date
      ? formatDate((items[0] as { delivery_date: string }).delivery_date)
      : "-";
  const endDate = order.visit_datetime ? formatDate(order.visit_datetime) : "-";
  const paid = order.paid != null ? String(order.paid) : "-";
  const invoiceDate = order.created_at ? formatDate(order.created_at) : "-";
  const printDate = formatDate(new Date().toISOString());
  const printTime = new Date().toLocaleTimeString("en", { hour: "2-digit", minute: "2-digit", hour12: false });

  const branchImage =
    (order.inventory?.inventoriable as any)?.image_url ??
    (order.inventory?.inventoriable as any)?.image ??
    null;
  const effectiveLogoUrl = branchImage || logoUrl || "/dressnmore-logo.jpg";

  return (
    <article
      dir="rtl"
      lang="ar"
      className="ack-print-root w-full max-w-[148mm] min-h-[198mm] flex flex-col bg-white text-gray-900 text-[10px] leading-snug ack-print-document"
      style={{ fontFamily: "'Segoe UI', 'Cairo', Arial, sans-serif" }}
      itemScope
      itemType="https://schema.org/Receipt"
    >
      <meta itemProp="name" content={`إقرار استلام - طلب رقم ${order.id}`} />
      <meta itemProp="dateCreated" content={order.created_at} />
      
      <div className="ack-print-top-date w-full px-2 pb-0.5 text-left text-[8px] text-gray-600" dir="ltr">
        تاريخ الطباعة: {printDate} - {printTime}
      </div>
      
      {/* Header — minimized */}
      <header
        className="ack-print-header w-full py-1.5 mb-1.5 text-white rounded-b shadow-sm"
        style={{ backgroundColor: HEADER_BG }}
        role="banner"
      >
        <div className="ack-print-header-inner flex items-center justify-between gap-2 w-full px-2">
          <div className="ack-print-header-right text-right shrink-0 space-y-0.5 text-[9px]">
            <div className="flex items-baseline justify-end gap-2 flex-wrap">
              <span className="text-white/95 font-medium">رقم الفاتورة:</span>
              <span className="font-bold text-white" itemProp="identifier" style={{ fontVariantNumeric: "tabular-nums", fontFamily: "'Segoe UI', Arial, sans-serif" }}>{order.id}</span>
            </div>
            <div className="text-white/95 font-medium">
              اسم الموظف:{" "}
              <span className="font-semibold">
                <OrderEmployeeName order={order} className="inline-block" />
              </span>
            </div>
            <div className="text-white/95 font-medium">
              التاريخ: <span className="font-semibold">{invoiceDate}</span>
            </div>
            <div className="text-white/95 font-medium">
              نوع الفاتورة: <span className="font-semibold">{getOrderTypeLabel(order.order_type)}</span>
            </div>
          </div>
          <div className="ack-print-header-logo shrink-0 w-10 h-10 flex items-center justify-center rounded-full overflow-hidden bg-white shadow-md ring-2 ring-white/80">
            <img
              src={effectiveLogoUrl}
              alt="شعار الشركة"
              className="ack-print-logo-img w-full h-full object-cover"
              itemProp="image"
            />
          </div>
        </div>
      </header>

      <main className="ack-print-content flex-1 flex flex-col min-h-0 w-full px-2 pb-1 overflow-hidden">
        {/* Content from title to signature only */}
        <section className="ack-print-body shrink-0">
        {/* 1. Receipt acknowledgment title — at the top */}
        <header className="ack-print-title-wrap flex flex-col items-center justify-center text-center mb-1.5">
          <h1 className="ack-print-title text-[14px] font-bold text-gray-900 tracking-tight mb-0.5">
            إقرار استلام
          </h1>
          <span className="ack-print-title-line block h-0.5 w-12 rounded-full bg-gray-400" aria-hidden />
        </header>

        {/* 2. I received / National ID / Resident in / Phone */}
        <section className="ack-print-recipient text-right mb-1.5 space-y-0.5 text-[10px] min-w-0" itemScope itemType="https://schema.org/Person">
          <p className="text-gray-900">
            <span className="font-semibold text-gray-700">استلمت أنا :</span>{" "}
            <span className="font-bold text-gray-900" itemProp="name">{clientName}</span>
          </p>
          <p className="text-gray-900">
            <span className="font-semibold text-gray-700">الرقم القومي :</span>{" "}
            <span className="font-normal text-gray-900" style={{ fontVariantNumeric: "tabular-nums", fontFamily: "'Segoe UI', Arial, sans-serif" }}>{nationalId}</span>
          </p>
          <p className="text-gray-900">
            <span className="font-semibold text-gray-700">المقيم في :</span>{" "}
            <span className="font-normal text-gray-900" itemProp="address">{addressStr}</span>
          </p>
          {phoneStr && (
            <p className="text-gray-900">
              <span className="font-semibold text-gray-700">رقم الهاتف :</span>{" "}
              <span dir="ltr" className="font-normal text-gray-900 inline-block text-right" itemProp="telephone" style={{ fontVariantNumeric: "tabular-nums", fontFamily: "'Segoe UI', Arial, sans-serif" }}>{phoneStr}</span>
            </p>
          )}
        </section>

        {/* 3. Rental period */}
        <p className="ack-print-rental text-[10px] text-gray-900 mb-1 min-w-0 wrap-break-word">
          وذلك بتأجيره من تاريخ{" "}
          <time className="font-semibold" dateTime={order.delivery_date || undefined}>{startDate}</time> حتى تاريخ{" "}
          <time className="font-semibold" dateTime={order.visit_datetime || undefined}>{endDate}</time>
        </p>

        {/* 4. Products list: category subcategory (code) with numbering */}
        <section className="ack-print-items-list mb-1.5 text-[10px] text-gray-900 min-w-0">
          {items.length > 0 ? (
            <ol className="list-decimal list-inside space-y-0.5" itemProp="itemListElement" itemScope itemType="https://schema.org/ItemList">
              {items.map((item) => (
                <li key={item.id} className="font-medium" itemProp="itemListElement" itemScope itemType="https://schema.org/Product">
                  <span itemProp="name">{getItemReceiptDisplay(item as Record<string, unknown>)}</span>
                </li>
              ))}
            </ol>
          ) : (
            <p className="text-gray-500 font-normal">—</p>
          )}
        </section>

        {/* 5. Receipt acknowledgment and deposit payment */}
        <p className="ack-print-deposit text-[10px] text-gray-900 mb-1.5 min-w-0 wrap-break-word">
        وذلك إقرار مني بالاستلام ودفع عربون وقدره :{" "}
          {(() => {
            const { currency_symbol } = getOrderCurrencyInfo(order as any);
            return (
              <span
                className="font-bold text-gray-900"
                itemProp="totalPaymentDue"
                style={{
                  fontVariantNumeric: "tabular-nums",
                  fontFamily: "'Segoe UI', Arial, sans-serif",
                }}
                dir="ltr"
              >
                {paid} {currency_symbol}
              </span>
            );
          })()}
        </p>

        {/* 6. Recipient and signature */}
        <section className="ack-print-signature flex justify-end mt-1.5 pt-1.5 border-t border-gray-300 min-w-0">
          <div className="ack-print-signature-box text-right min-w-0 space-y-1 text-[10px] text-gray-900">
            <p className="flex items-center justify-end gap-1.5 flex-wrap">
              <span className="font-semibold text-gray-700 shrink-0">المستلم:</span>
              <span className="inline-block min-w-[60px] flex-1 max-w-[90px] h-3 border-b border-gray-400">&nbsp;</span>
            </p>
            <p className="flex items-center justify-end gap-1.5 flex-wrap">
              <span className="font-semibold text-gray-700 shrink-0">التوقيع:</span>
              <span className="inline-block min-w-[60px] flex-1 max-w-[90px] h-3 border-b border-gray-400">&nbsp;</span>
            </p>
          </div>
        </section>
        </section>
      </main>

      <div className="ack-print-bottom mt-auto flex flex-col shrink-0 w-full">
        <aside className="ack-print-rules pt-1 pb-1 border-t border-gray-200 shrink-0 min-w-0 px-2">
          <h2 className="ack-print-rules-title text-[9px] font-bold text-gray-800 mb-0.5">
            القواعد والتعليمات
          </h2>
          <div className="ack-print-notes-box rounded border border-gray-200 bg-gray-50 py-1 px-1.5 min-w-0">
            <ul className="list-none space-y-0.5 text-[8px] text-gray-800 leading-tight wrap-break-word">
              {RULES_ITEMS.map((text, i) => (
                <li key={i} className="font-normal wrap-break-word">
                  {text}
                </li>
              ))}
            </ul>
          </div>
        </aside>
        <footer
          className="ack-print-footer w-full py-1 px-2 text-center text-white rounded-t text-[8px] font-semibold shrink-0 min-w-0 wrap-break-word"
          style={{ backgroundColor: HEADER_BG }}
          role="contentinfo"
        >
          <span className="block">لا يرد العربون في حالة إلغاء الحجز</span><br />
          <span className="block mt-0.5">يجب إحضار الفاتورة الأصلية مع البطاقة الشخصية عند الإرجاع أو الاستلام أو الاستبدال.</span>
        </footer>
      </div>

      <style>{`
        .ack-print-top-date {
          font-size: 8px;
          color: #4b5563;
          padding: 0 8px 2px;
          text-align: left;
          direction: ltr;
        }
        @media print {
          .ack-print-top-date {
            font-size: 8px !important;
            color: #4b5563 !important;
            padding: 0 2mm 0.5mm !important;
            text-align: left !important;
            direction: ltr !important;
          }
          @page { size: A5 portrait; margin: 6mm; }
          html, body { 
            margin: 0 !important; 
            padding: 0 !important; 
            width: 100% !important; 
            min-height: 100% !important;
            background: #fff !important;
          }
          body * { visibility: hidden; }
          .ack-print-root, .ack-print-root * { visibility: visible; }
          .ack-print-root { 
            position: relative !important;
            left: 0 !important;
            top: 0 !important;
            transform: none !important;
            width: 100% !important; 
            max-width: 148mm !important;
            height: 198mm !important;
            min-height: 198mm !important;
            max-height: 198mm !important;
            padding: 0 !important;
            box-sizing: border-box !important; 
            page-break-inside: avoid;
            page-break-after: avoid !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
          .ack-print-content { flex: 1 1 auto !important; min-height: 0 !important; overflow: hidden !important; }
          .ack-print-bottom { margin-top: auto !important; flex-shrink: 0 !important; page-break-inside: avoid !important; }
          .ack-print-header-inner,
          .ack-print-content { max-width: 100% !important; }
          .ack-print-header, .ack-print-footer { 
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact; 
          }
          * { box-sizing: border-box !important; }
          .ack-print-root,
          .ack-print-content,
          .ack-print-notes-box,
          .ack-print-footer { overflow: hidden !important; }
          .ack-print-notes-box ul,
          .ack-print-notes-box li,
          .ack-print-footer { word-wrap: break-word !important; overflow-wrap: break-word !important; }
          .sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0,0,0,0) !important;
            white-space: nowrap !important;
            border: 0 !important;
          }
          .ack-print-notes-box li { page-break-inside: avoid; }
          .ack-print-signature-box span[class*="min-w"] {
            min-width: 100px !important;
            height: 18px !important;
          }
        }
      `}</style>
    </article>
  );
}
 