import { useMemo, useState, type ReactNode } from "react";
import { useNavigate } from "react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import type { TOrder } from "@/api/v2/orders/orders.types";
import { getItemSubcategoryDisplay, getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";
import { ORDERS_KEY } from "@/api/v2/orders/orders.hooks";
import { getPayments } from "@/api/v2/payments/payments.service";
import { useOrderEmployeeResolvedName } from "@/components/custom/OrderEmployeeName";
import { CreatePaymentModal } from "@/pages/orders/CreatePaymentModal";
import { mapOrderToRentalPrintView } from "@/pages/orders/rental/rentalPrintMapper";
import { RentalPrintInvoiceModal } from "@/pages/orders/rental/components/RentalPrintInvoiceModal";
import {
  formatSaleInvoiceDate,
  getSaleInvoiceStatusLabel,
  getSalePaymentLabel,
  salePaymentColors,
  saleStatusColors,
} from "./soldInvoices.helpers";
import { INVOICE_PAYMENT_METHOD_ICONS } from "@/pages/orders/shared/invoicePaymentMethodIcons";

function clientPhones(order: TOrder) {
  const phones = order.client?.phones ?? [];
  const mobile =
    phones.find((p) => p.type === "mobile")?.phone ??
    phones[0]?.phone ??
    "—";
  const whatsapp =
    phones.find((p) => p.type === "whatsapp")?.phone ?? "—";
  return { mobile, whatsapp };
}

export type SoldInvoiceDetailLayoutProps = {
  order: TOrder;
  toolbarActions: ReactNode;
};

export function SoldInvoiceDetailLayout({
  order,
  toolbarActions,
}: SoldInvoiceDetailLayoutProps) {
  const navigate = useNavigate();
  const qc = useQueryClient();
  const embeddedPayments = (order as TOrder & { payments?: unknown[] }).payments;
  const { data: listedPayments, isPending: paymentsListPending } = useQuery({
    queryKey: [ORDERS_KEY, order.id, "payments-list"],
    queryFn: async () => {
      const page = await getPayments({
        order_id: order.id,
        page: 1,
        per_page: 100,
      });
      return page?.data ?? [];
    },
    enabled: Boolean(order.id) && !embeddedPayments?.length,
  });

  const employeeName = useOrderEmployeeResolvedName(order, true);
  const paymentsForMapper = useMemo(
    () =>
      embeddedPayments?.length ? embeddedPayments : (listedPayments ?? []),
    [embeddedPayments, listedPayments]
  );

  const view = useMemo(
    () =>
      mapOrderToRentalPrintView(order, {
        employeeDisplayName: employeeName,
        paymentsList: paymentsForMapper,
      }),
    [order, paymentsForMapper, employeeName]
  );

  const branchName =
    order.branch?.name ?? order.inventory?.inventoriable?.name ?? view.branchName;

  const { currency_symbol: sym } = getOrderCurrencyInfo(order);
  const { mobile, whatsapp } = clientPhones(order);

  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [showPrintModal, setShowPrintModal] = useState(false);

  const saleStatus = getSaleInvoiceStatusLabel(order);
  const paymentStatus = getSalePaymentLabel(order);

  const totalWithTax = view.pricing.totalWithTax;
  const discount = view.pricing.discount;
  const netTotal = Math.max(0, totalWithTax - discount);
  const collected = Number(order.paid ?? 0);
  const remaining = Math.max(0, netTotal - collected);
  const pct =
    netTotal > 0 ? Math.min(100, Math.round((collected / netTotal) * 100)) : 0;

  const taxRatePct =
    view.pricing.taxRate > 0 && view.pricing.taxRate <= 1
      ? Math.round(view.pricing.taxRate * 100)
      : view.pricing.taxRate > 1
        ? Math.round(view.pricing.taxRate)
        : 0;

  const invalidateOrder = () => {
    qc.invalidateQueries({ queryKey: [ORDERS_KEY, order.id] });
    qc.invalidateQueries({ queryKey: [ORDERS_KEY] });
    qc.invalidateQueries({ queryKey: [ORDERS_KEY, order.id, "payments-list"] });
  };

  const onPaymentSuccess = () => {
    setShowPaymentModal(false);
    invalidateOrder();
  };

  const invoiceDateStr = formatSaleInvoiceDate(order.created_at);

  return (
    <div className="min-h-screen bg-slate-50 p-6" dir="rtl">
      <div className="mb-6 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() => navigate("/orders/list?process_type=sold")}
            className="flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-50"
          >
            <i className="ri-arrow-right-line" />
          </button>
          <div>
            <h1 className="text-xl font-bold text-slate-800">
              فاتورة بيع #{order.id}
            </h1>
            <p className="text-xs text-slate-400">
              {invoiceDateStr} — {branchName}
            </p>
          </div>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <span
            className={`rounded-full px-3 py-1.5 text-xs font-semibold ${saleStatusColors[saleStatus] ?? "bg-slate-50 text-slate-600 border border-slate-200"}`}
          >
            {saleStatus}
          </span>
          <span
            className={`rounded-full px-3 py-1.5 text-xs font-semibold ${salePaymentColors[paymentStatus] ?? "bg-slate-50 text-slate-600 border border-slate-200"}`}
          >
            {paymentStatus}
          </span>
          <button
            type="button"
            onClick={() => setShowPrintModal(true)}
            className="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
          >
            <i className="ri-printer-line" />
            طباعة
          </button>
          {toolbarActions}
          {paymentStatus !== "مدفوع بالكامل" && remaining > 0 && (
            <button
              type="button"
              onClick={() => setShowPaymentModal(true)}
              className="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors"
              style={{
                background: "linear-gradient(135deg, #6366F1, #4F46E5)",
              }}
            >
              <i className="ri-add-circle-line" />
              تسجيل دفعة
            </button>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="space-y-5 lg:col-span-2">
          <div className="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <div className="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5">
              <i className="ri-shopping-bag-3-line text-indigo-500" />
              <h2 className="text-sm font-bold text-slate-700">المنتجات المباعة</h2>
            </div>
            <div
              className="grid bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-400"
              style={{
                gridTemplateColumns: "1fr 80px 120px 120px",
              }}
            >
              <span>المنتج</span>
              <span className="text-center">الكمية</span>
              <span className="text-center">سعر الوحدة</span>
              <span className="text-center">الإجمالي</span>
            </div>
            {(order.items ?? []).map((it) => {
              const q = it.quantity ?? 1;
              const unit = Number(it.price ?? 0);
              const line = unit * q;
              const cat =
                getItemSubcategoryDisplay(it as Record<string, unknown>) || "—";
              return (
                <div
                  key={it.id}
                  className="grid items-center border-t border-slate-50 px-4 py-3"
                  style={{
                    gridTemplateColumns: "1fr 80px 120px 120px",
                  }}
                >
                  <div>
                    <div className="text-sm font-semibold text-slate-800">
                      {it.name ?? it.code ?? "—"}
                    </div>
                    <div className="text-xs text-slate-400">{cat}</div>
                  </div>
                  <div className="text-center text-sm text-slate-600">{q}</div>
                  <div className="text-center text-sm text-slate-600">
                    {unit.toLocaleString("ar-SA")} {sym}
                  </div>
                  <div className="text-center text-sm font-bold text-slate-800">
                    {line.toLocaleString("ar-SA")} {sym}
                  </div>
                </div>
              );
            })}

            <div className="space-y-2 border-t border-slate-100 bg-slate-50 px-4 py-4">
              <div className="flex justify-between text-sm">
                <span className="text-slate-500">المبلغ الفرعي</span>
                <span className="font-semibold">
                  {view.pricing.subtotal.toLocaleString("ar-SA")} {sym}
                </span>
              </div>
              {view.pricing.taxAmount > 0 && (
                <div className="flex justify-between text-sm">
                  <span className="text-slate-500">
                    ضريبة ({taxRatePct}%)
                  </span>
                  <span className="font-semibold">
                    +{view.pricing.taxAmount.toLocaleString("ar-SA")} {sym}
                  </span>
                </div>
              )}
              {discount > 0 && (
                <div className="flex justify-between text-sm">
                  <span className="text-emerald-600">
                    خصم
                    {view.pricing.discountReason
                      ? ` — ${view.pricing.discountReason}`
                      : ""}
                  </span>
                  <span className="font-semibold text-emerald-600">
                    -{discount.toLocaleString("ar-SA")} {sym}
                  </span>
                </div>
              )}
              <div className="flex justify-between border-t border-slate-200 pt-2">
                <span className="font-bold text-slate-800">الإجمالي النهائي</span>
                <span className="text-lg font-bold text-indigo-600">
                  {netTotal.toLocaleString("ar-SA")} {sym}
                </span>
              </div>
            </div>
          </div>

          <div className="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <div className="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
              <div className="flex items-center gap-2">
                <i className="ri-history-line text-indigo-500" />
                <h2 className="text-sm font-bold text-slate-700">سجل المدفوعات</h2>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-xs text-slate-500">
                  {collected.toLocaleString("ar-SA")} / {netTotal.toLocaleString("ar-SA")}{" "}
                  {sym}
                </span>
                <span className="text-xs font-bold text-indigo-600">{pct}%</span>
              </div>
            </div>

            <div className="border-b border-slate-100 bg-slate-50 px-5 py-3">
              <div className="h-2 w-full rounded-full bg-slate-200">
                <div
                  className="h-2 rounded-full transition-all duration-500"
                  style={{
                    width: `${Math.min(pct, 100)}%`,
                    background:
                      pct >= 100 ? "#10B981" : pct >= 50 ? "#F59E0B" : "#EF4444",
                  }}
                />
              </div>
            </div>

            {paymentsListPending && !embeddedPayments?.length ? (
              <div className="py-8 text-center text-sm text-slate-400">
                <i className="ri-loader-4-line mb-2 block animate-spin text-2xl" />
                جاري تحميل المدفوعات…
              </div>
            ) : view.paymentHistory.length === 0 ? (
              <div className="py-8 text-center text-sm text-slate-400">
                <i className="ri-wallet-line mb-2 block text-2xl" />
                لا توجد مدفوعات مسجّلة بعد
              </div>
            ) : (
              <div className="space-y-3 p-4">
                {view.paymentHistory.map((pay, idx) => {
                  const cumulative = view.paymentHistory
                    .slice(0, idx + 1)
                    .reduce((s, p) => s + p.amount, 0);
                  return (
                    <div key={String(pay.id)} className="relative flex items-start gap-4">
                      <div className="flex flex-shrink-0 flex-col items-center">
                        <div
                          className="flex h-8 w-8 items-center justify-center rounded-full text-sm text-white"
                          style={{ background: "#6366F1" }}
                        >
                          <i
                            className={
                              INVOICE_PAYMENT_METHOD_ICONS[pay.method] ?? "ri-exchange-dollar-line"
                            }
                          />
                        </div>
                        {idx < view.paymentHistory.length - 1 && (
                          <div className="mt-1 h-6 w-0.5 bg-slate-200" />
                        )}
                      </div>
                      <div className="flex-1 pb-2">
                        <div className="flex items-start justify-between">
                          <div>
                            <span className="text-sm font-bold text-slate-800">
                              {pay.amount.toLocaleString("ar-SA")} {sym}
                            </span>
                            <span className="mx-2 text-slate-300">·</span>
                            <span className="text-xs text-slate-500">{pay.method}</span>
                          </div>
                          <div className="text-left">
                            <div className="text-xs text-slate-400">{pay.date}</div>
                            <div className="mt-0.5 text-xs text-indigo-500">
                              رصيد: {cumulative.toLocaleString("ar-SA")} {sym}
                            </div>
                          </div>
                        </div>
                        {pay.note ? (
                          <div className="mt-0.5 text-xs text-slate-400">{pay.note}</div>
                        ) : null}
                        <div className="text-xs text-slate-400">
                          استلم: {pay.receivedBy}
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        <div className="space-y-4">
          <div className="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
              <i className="ri-user-3-line text-indigo-500" />
              <h3 className="text-sm font-bold text-slate-700">بيانات العميلة</h3>
            </div>
            <div className="space-y-2.5 p-4">
              <div className="flex items-center gap-2">
                <div
                  className="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white"
                  style={{
                    background: "linear-gradient(135deg, #6366F1, #4F46E5)",
                  }}
                >
                  {(order.client?.name ?? "?").charAt(0)}
                </div>
                <div>
                  <div className="text-sm font-bold text-slate-800">
                    {order.client?.name ?? "—"}
                  </div>
                  <div className="text-xs text-slate-400">
                    هوية: {order.client?.national_id ?? "—"}
                  </div>
                </div>
              </div>
              <div className="space-y-1.5 pt-1">
                <div className="flex items-center gap-2 text-xs text-slate-500">
                  <i className="ri-phone-line text-slate-400" />
                  {mobile}
                </div>
                <div className="flex items-center gap-2 text-xs text-slate-500">
                  <i className="ri-whatsapp-line text-emerald-500" />
                  {whatsapp}
                </div>
                <div className="flex items-start gap-2 text-xs text-slate-500">
                  <i className="ri-map-pin-line mt-0.5 text-slate-400" />
                  <span>{view.customer.address}</span>
                </div>
              </div>
            </div>
          </div>

          <div className="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
              <i className="ri-information-line text-indigo-500" />
              <h3 className="text-sm font-bold text-slate-700">بيانات الفاتورة</h3>
            </div>
            <div className="space-y-2.5 p-4">
              {[
                { icon: "ri-store-2-line", label: "الفرع", value: branchName },
                { icon: "ri-user-star-line", label: "الموظفة", value: employeeName },
                {
                  icon: "ri-calendar-line",
                  label: "تاريخ الإصدار",
                  value: invoiceDateStr,
                },
              ].map((item) => (
                <div key={item.label} className="flex justify-between text-sm">
                  <span className="flex items-center gap-1.5 text-slate-400">
                    <i className={item.icon} />
                    {item.label}
                  </span>
                  <span className="font-medium text-slate-700">{item.value}</span>
                </div>
              ))}
              {order.order_notes ? (
                <div className="border-t border-slate-100 pt-2">
                  <div className="mb-1 text-xs text-slate-400">ملاحظات</div>
                  <div className="text-xs text-slate-600">{order.order_notes}</div>
                </div>
              ) : null}
            </div>
          </div>

          <div
            className="overflow-hidden rounded-xl border"
            style={{
              background: "linear-gradient(135deg, #EEF2FF, #F5F3FF)",
              borderColor: "#C7D2FE",
            }}
          >
            <div className="flex items-center gap-2 border-b border-indigo-100 px-4 py-3">
              <i className="ri-wallet-3-line text-indigo-600" />
              <h3 className="text-sm font-bold text-indigo-700">ملخص الدفع</h3>
            </div>
            <div className="space-y-3 p-4">
              <div className="flex justify-between text-sm">
                <span className="text-indigo-600">الإجمالي</span>
                <span className="font-bold text-indigo-800">
                  {netTotal.toLocaleString("ar-SA")} {sym}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-emerald-600">المحصّل</span>
                <span className="font-bold text-emerald-700">
                  {collected.toLocaleString("ar-SA")} {sym}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-amber-600">المتبقي</span>
                <span className="font-bold text-amber-700">
                  {remaining.toLocaleString("ar-SA")} {sym}
                </span>
              </div>
            </div>
            {paymentStatus !== "مدفوع بالكامل" && remaining > 0 ? (
              <div className="px-4 pb-4">
                <button
                  type="button"
                  onClick={() => setShowPaymentModal(true)}
                  className="w-full cursor-pointer rounded-lg py-2.5 text-sm font-bold text-white whitespace-nowrap"
                  style={{
                    background: "linear-gradient(135deg, #6366F1, #4F46E5)",
                  }}
                >
                  <i className="ri-add-circle-line ml-2" />
                  تسجيل دفعة الآن
                </button>
              </div>
            ) : null}
          </div>
        </div>
      </div>

      <CreatePaymentModal
        open={showPaymentModal}
        onOpenChange={setShowPaymentModal}
        order={order}
        onSuccess={onPaymentSuccess}
      />

      <RentalPrintInvoiceModal
        order={order}
        open={showPrintModal}
        onOpenChange={setShowPrintModal}
        defaultDocType="customer"
      />
    </div>
  );
}
