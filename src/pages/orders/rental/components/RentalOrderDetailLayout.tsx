import { useMemo, useState, type ReactNode } from "react";
import { Link } from "react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import type { TOrder } from "@/api/v2/orders/orders.types";
import {
  getItemSubcategoryDisplay,
  getOrderCurrencyInfo,
} from "@/api/v2/orders/order.utils";
import { ORDERS_KEY } from "@/api/v2/orders/orders.hooks";
import { getPayments } from "@/api/v2/payments/payments.service";
import { useOrderEmployeeResolvedName } from "@/components/custom/OrderEmployeeName";
import { CreatePaymentModal } from "@/pages/orders/CreatePaymentModal";
import { OrderCustodiesTable } from "@/pages/orders/OrderCustodiesTable";
import { mapOrderToRentalPrintView } from "../rentalPrintMapper";
import { RentalPrintInvoiceModal } from "./RentalPrintInvoiceModal";
import {
  getRentalUiPayment,
  getRentalUiStatus,
  paymentStatusColors,
  rentalStatusColors,
} from "../rentalUi";
import { INVOICE_PAYMENT_METHOD_ICONS } from "@/pages/orders/shared/invoicePaymentMethodIcons";

export type RentalOrderDetailLayoutProps = {
  order: TOrder;
  toolbarActions: ReactNode;
  onReturnItem: (itemId: number) => void;
  
  onAddCustody?: () => void;
  
  custodyActionDisabled?: boolean;
};


export function RentalOrderDetailLayout({
  order,
  toolbarActions,
  onReturnItem,
  onAddCustody,
  custodyActionDisabled = false,
}: RentalOrderDetailLayoutProps) {
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
      embeddedPayments?.length
        ? embeddedPayments
        : (listedPayments ?? []),
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

  const { currency_symbol: sym } = getOrderCurrencyInfo(order);

  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [showPrintModal, setShowPrintModal] = useState(false);
  const [successMsg, setSuccessMsg] = useState("");

  const uiStatus = getRentalUiStatus(order);
  const uiPayment = getRentalUiPayment(order);

  const totalPaid = view.paidTotal;
  const netTotal = Math.max(0, view.pricing.totalWithTax - view.pricing.discount);
  const remaining = view.remainingTotal;
  const paidPct =
    netTotal > 0 ? Math.min(100, Math.round((totalPaid / netTotal) * 100)) : 0;

  const invalidateOrder = () => {
    qc.invalidateQueries({ queryKey: [ORDERS_KEY, order.id] });
    qc.invalidateQueries({ queryKey: [ORDERS_KEY] });
    qc.invalidateQueries({ queryKey: [ORDERS_KEY, order.id, "payments-list"] });
  };

  const onPaymentSuccess = () => {
    setShowPaymentModal(false);
    setSuccessMsg("تم تسجيل الدفعة بنجاح");
    setTimeout(() => setSuccessMsg(""), 3500);
    invalidateOrder();
  };

  return (
    <div className="p-6" dir="rtl">
      <div className="mb-4 flex items-center gap-2 text-xs text-slate-400">
        <Link to="/orders/list" className="cursor-pointer hover:text-slate-600">
          فواتير الإيجار
        </Link>
        <i className="ri-arrow-left-s-line" />
        <span className="font-medium text-slate-600">
          تفاصيل الفاتورة {view.invoiceNumber}
        </span>
      </div>

      <div className="mb-6 flex items-start justify-between">
        <div className="flex items-center gap-4">
          <Link
            to="/orders/list"
            className="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50"
          >
            <i className="ri-arrow-right-line" />
          </Link>
          <div>
            <div className="flex flex-wrap items-center gap-3">
              <h1 className="text-xl font-bold text-slate-800">
                فاتورة إيجار {view.invoiceNumber}
              </h1>
              <span
                className={`rounded-full px-2.5 py-1 text-xs font-medium ${rentalStatusColors[uiStatus]}`}
              >
                {uiStatus}
              </span>
              <span
                className={`rounded-full px-2.5 py-1 text-xs font-medium ${paymentStatusColors[uiPayment]}`}
              >
                {uiPayment}
              </span>
            </div>
            <p className="mt-0.5 text-sm text-slate-400">
              {view.invoiceDate} — {view.branchName}
            </p>
          </div>
        </div>
        <div className="flex flex-wrap items-center justify-end gap-2">
          {toolbarActions}
          <button
            type="button"
            onClick={() => setShowPrintModal(true)}
            className="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 transition-colors hover:bg-slate-50"
          >
            <i className="ri-printer-line" />
            طباعة / المستندات
          </button>
          {remaining > 0 && (
            <button
              type="button"
              onClick={() => setShowPaymentModal(true)}
              className="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700"
            >
              <i className="ri-add-circle-line" />
              تسجيل دفعة
            </button>
          )}
        </div>
      </div>

      {successMsg ? (
        <div className="mb-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
          <i className="ri-checkbox-circle-line text-lg" />
          {successMsg}
        </div>
      ) : null}

      <div className="flex items-start gap-5">
        <div className="flex-1 space-y-5">
          <div className="rounded-lg border border-slate-200 bg-white">
            <div className="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
              <i className="ri-shopping-bag-line text-slate-400" />
              <h2 className="text-sm font-semibold text-slate-700">الأصناف المستأجرة</h2>
            </div>
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-slate-50 text-slate-600">
                  <th className="px-5 py-3 text-right font-medium">#</th>
                  <th className="px-5 py-3 text-right font-medium">الصنف</th>
                  <th className="px-5 py-3 text-right font-medium">الفئة</th>
                  <th className="px-5 py-3 text-right font-medium">الكمية</th>
                  <th className="px-5 py-3 text-right font-medium">سعر الوحدة</th>
                  <th className="px-5 py-3 text-right font-medium">الإجمالي</th>
                  <th className="w-[88px] px-4 py-3 text-center text-xs font-medium text-slate-500">
                    إجراءات
                  </th>
                </tr>
              </thead>
              <tbody>
                {(order.items ?? []).map((it, i) => {
                  const q = it.quantity ?? 1;
                  const unit = Number(it.price ?? 0);
                  const line = unit * q;
                  const canReturn = it.returnable !== 0 && order.status !== "canceled";
                  const cat =
                    getItemSubcategoryDisplay(it as Record<string, unknown>) ||
                    view.products[i]?.category ||
                    "—";
                  return (
                    <tr
                      key={it.id}
                      className={`border-t border-slate-100 ${i % 2 === 1 ? "bg-slate-50/40" : ""}`}
                    >
                      <td className="px-5 py-3 text-slate-400 tabular-nums">{i + 1}</td>
                      <td className="px-5 py-3 font-medium text-slate-800">
                        {it.name ?? it.code ?? "—"}
                      </td>
                      <td className="px-5 py-3 text-slate-500">{cat}</td>
                      <td className="px-5 py-3 text-slate-600 tabular-nums">{q}</td>
                      <td className="px-5 py-3 text-slate-700 tabular-nums" dir="ltr">
                        {unit.toLocaleString("ar-EG")} {sym}
                      </td>
                      <td className="px-5 py-3 font-semibold text-slate-800 tabular-nums" dir="ltr">
                        {line.toLocaleString("ar-EG")} {sym}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center justify-center gap-1.5">
                          <Link
                            to={`/orders/${order.id}/items/${it.id}`}
                            title="عرض تفاصيل الصنف والقياسات"
                            className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 text-slate-500 transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800"
                          >
                            <i className="ri-eye-line text-base" />
                          </Link>
                          <button
                            type="button"
                            title={
                              !canReturn
                                ? order.status === "canceled"
                                  ? "الطلب ملغى"
                                  : "غير قابل للإرجاع"
                                : "إرجاع هذا الصنف"
                            }
                            disabled={!canReturn}
                            onClick={() => onReturnItem(it.id)}
                            className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 text-rose-600 transition-colors hover:border-rose-200 hover:bg-rose-50 disabled:pointer-events-none disabled:border-slate-100 disabled:text-slate-300"
                          >
                            <i className="ri-arrow-go-back-line text-base" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>

            <div className="border-t border-slate-100 p-5">
              <div className="flex justify-end">
                <div className="w-72 space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-slate-500">الإجمالي الفرعي</span>
                    <span className="font-medium text-slate-700">
                      {view.pricing.subtotal.toLocaleString("ar-EG")} {sym}
                    </span>
                  </div>
                  {view.pricing.taxAmount > 0 && (
                    <div className="flex justify-between">
                      <span className="text-slate-500">
                        الضريبة ({(view.pricing.taxRate * 100).toFixed(0)}%)
                      </span>
                      <span className="font-medium text-slate-700">
                        {view.pricing.taxAmount.toLocaleString("ar-EG")} {sym}
                      </span>
                    </div>
                  )}
                  {view.pricing.discount > 0 && (
                    <div className="flex justify-between">
                      <span className="text-xs text-amber-600">
                        خصم{" "}
                        {view.pricing.discountReason && `(${view.pricing.discountReason})`}
                      </span>
                      <span className="font-medium text-amber-600">
                        - {view.pricing.discount.toLocaleString("ar-EG")} {sym}
                      </span>
                    </div>
                  )}
                  <div className="flex justify-between border-t border-slate-200 pt-2 text-base font-bold">
                    <span className="text-slate-800">الإجمالي النهائي</span>
                    <span className="text-slate-800">
                      {netTotal.toLocaleString("ar-EG")} {sym}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-lg border border-slate-200 bg-white">
            <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
              <div className="flex items-center gap-2">
                <i className="ri-wallet-3-line text-slate-400" />
                <h2 className="text-sm font-semibold text-slate-700">سجل المدفوعات</h2>
              </div>
              {remaining > 0 && (
                <button
                  type="button"
                  onClick={() => setShowPaymentModal(true)}
                  className="flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs text-emerald-700 transition-colors hover:bg-emerald-100"
                >
                  <i className="ri-add-line" />
                  تسجيل دفعة جديدة
                </button>
              )}
            </div>

            <div className="p-5">
              
              <div className="mb-6">
                <div className="mb-2 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-semibold text-emerald-700">
                      {totalPaid.toLocaleString("ar-EG")} {sym}
                    </span>
                    <span className="text-xs text-slate-400">مدفوع</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs text-slate-400">متبقي</span>
                    <span className="text-sm font-semibold text-rose-600">
                      {remaining.toLocaleString("ar-EG")} {sym}
                    </span>
                  </div>
                </div>
                <div className="h-3 overflow-hidden rounded-full bg-slate-100">
                  <div
                    className="h-full rounded-full bg-emerald-500 transition-all duration-500"
                    style={{ width: `${paidPct}%` }}
                  />
                </div>
                <div className="mt-1 text-center text-xs text-slate-500">
                  {paidPct}% من إجمالي {netTotal.toLocaleString("ar-EG")} {sym}
                </div>
              </div>

              {paymentsListPending && !embeddedPayments?.length ? (
                <div className="py-10 text-center">
                  <i className="ri-loader-4-line mb-2 block animate-spin text-3xl text-slate-300" />
                  <p className="text-sm text-slate-400">جاري تحميل المدفوعات…</p>
                </div>
              ) : view.paymentHistory.length === 0 ? (
                <div className="py-10 text-center">
                  <i className="ri-wallet-3-line mb-2 block text-4xl text-slate-200" />
                  <p className="text-sm text-slate-400">لا توجد مدفوعات مسجلة بعد</p>
                  {remaining > 0 && (
                    <button
                      type="button"
                      onClick={() => setShowPaymentModal(true)}
                      className="mt-3 cursor-pointer text-sm text-emerald-600 underline"
                    >
                      سجّل أول دفعة الآن
                    </button>
                  )}
                </div>
              ) : (
                <div className="relative">
                  <div className="absolute right-[18px] top-2 bottom-2 w-px bg-slate-100" />
                  <div className="space-y-4">
                    {view.paymentHistory.map((pay, idx) => {
                      const runningTotal = view.paymentHistory
                        .slice(0, idx + 1)
                        .reduce((s, p) => s + p.amount, 0);
                      return (
                        <div key={String(pay.id)} className="flex items-start gap-4 pr-2">
                          <div className="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 border-emerald-200 bg-emerald-50">
                            <i
                              className={`${INVOICE_PAYMENT_METHOD_ICONS[pay.method] ?? "ri-money-dollar-circle-line"} text-emerald-600 text-sm`}
                            />
                          </div>
                          <div className="flex-1 rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <div className="mb-1 flex items-start justify-between">
                              <div>
                                <div className="text-sm font-semibold text-slate-800">
                                  {pay.amount.toLocaleString("ar-EG")} {sym}
                                </div>
                                <div className="text-xs text-slate-500">{pay.method}</div>
                              </div>
                              <div className="text-right">
                                <div className="text-xs text-slate-400">{pay.date}</div>
                                <div className="mt-0.5 text-xs text-slate-500">
                                  بواسطة: {pay.receivedBy}
                                </div>
                              </div>
                            </div>
                            {pay.note ? (
                              <div className="mt-1 border-t border-slate-200 pt-1 text-xs text-slate-500">
                                {pay.note}
                              </div>
                            ) : null}
                            <div className="mt-1 text-xs text-slate-400">
                              الرصيد المدفوع بعد هذه الدفعة:{" "}
                              <span className="font-medium text-emerald-700">
                                {runningTotal.toLocaleString("ar-EG")} {sym}
                              </span>
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              )}
            </div>
          </div>

          {view.notes ? (
            <div className="flex items-start gap-3 rounded-lg border border-amber-100 bg-amber-50 p-4">
              <i className="ri-sticky-note-line mt-0.5 text-lg text-amber-500" />
              <div>
                <div className="mb-1 text-xs font-semibold text-amber-700">ملاحظات</div>
                <div className="text-sm text-amber-800">{view.notes}</div>
              </div>
            </div>
          ) : null}

          <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
              <div className="flex items-center gap-2">
                <i className="ri-shield-check-line text-slate-400" />
                <h2 className="text-sm font-semibold text-slate-700">الضمانات</h2>
              </div>
              {onAddCustody && order.status !== "canceled" ? (
                <button
                  type="button"
                  disabled={custodyActionDisabled}
                  onClick={onAddCustody}
                  className="flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-800 transition-colors hover:bg-indigo-100 disabled:pointer-events-none disabled:opacity-50"
                >
                  <i className="ri-add-line" />
                  إضافة ضمان
                </button>
              ) : null}
            </div>
            <OrderCustodiesTable
              embedded
              orderId={order.id}
              clientId={order.client_id}
              currencySymbol={sym}
            />
          </div>
        </div>

        <div className="w-72 shrink-0 space-y-4">
          <div className="rounded-lg border border-slate-200 bg-white p-4">
            <div className="mb-4 flex items-center gap-2">
              <div className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100">
                <i className="ri-user-line text-slate-500" />
              </div>
              <h3 className="text-sm font-semibold text-slate-700">بيانات العميل</h3>
            </div>
            <div className="space-y-2.5 text-sm">
              <div className="text-base font-bold text-slate-800">{view.customer.name}</div>
              <div className="flex items-start gap-2 text-xs">
                <i className="ri-id-card-line mt-0.5 text-slate-400" />
                <div>
                  <div className="text-slate-400">الرقم القومي</div>
                  <div className="mt-0.5 font-mono text-slate-700">{view.customer.nationalId}</div>
                </div>
              </div>
              <div className="flex items-start gap-2 text-xs">
                <i className="ri-phone-line mt-0.5 text-slate-400" />
                <div>
                  <div className="text-slate-400">الهاتف</div>
                  <div className="mt-0.5 text-slate-700" dir="ltr">
                    {view.customer.phone}
                  </div>
                </div>
              </div>
              <div className="flex items-start gap-2 text-xs">
                <i className="ri-whatsapp-line mt-0.5 text-slate-400" />
                <div>
                  <div className="text-slate-400">واتساب</div>
                  <div className="mt-0.5 text-slate-700" dir="ltr">
                    {view.customer.whatsapp}
                  </div>
                </div>
              </div>
              <div className="flex items-start gap-2 text-xs">
                <i className="ri-map-pin-line mt-0.5 shrink-0 text-slate-400" />
                <div>
                  <div className="text-slate-400">العنوان</div>
                  <div className="mt-0.5 leading-relaxed text-slate-700">{view.customer.address}</div>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-lg border border-slate-200 bg-white p-4">
            <div className="mb-3 flex items-center gap-2">
              <i className="ri-calendar-line text-slate-400" />
              <h3 className="text-sm font-semibold text-slate-700">التواريخ</h3>
            </div>
            <div className="space-y-2.5 text-xs">
              {[
                { label: "تاريخ الفاتورة", value: view.invoiceDate, icon: "ri-file-text-line" },
                { label: "التسليم", value: view.dates.delivery, icon: "ri-calendar-check-line" },
                { label: "الفرح", value: view.dates.event, icon: "ri-heart-line" },
                { label: "الاسترجاع", value: view.dates.returnDate, icon: "ri-calendar-2-line" },
              ].map((d) => (
                <div key={d.label} className="flex items-center justify-between">
                  <div className="flex items-center gap-1.5 text-slate-500">
                    <i className={`${d.icon} text-slate-400`} />
                    {d.label}
                  </div>
                  <span className="font-medium text-slate-700">{d.value}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="rounded-lg border border-slate-200 bg-white p-4">
            <div className="mb-3 flex items-center gap-2">
              <i className="ri-user-star-line text-slate-400" />
              <h3 className="text-sm font-semibold text-slate-700">الموظف المسؤول</h3>
            </div>
            <div className="text-sm font-bold text-slate-800">{employeeName}</div>
            <div className="mt-0.5 text-xs text-slate-400">{view.branchName}</div>
          </div>

          <div className="rounded-lg border border-slate-200 bg-white p-4">
            <div className="mb-3 flex items-center gap-2">
              <i className="ri-money-dollar-circle-line text-slate-400" />
              <h3 className="text-sm font-semibold text-slate-700">ملخص الدفع</h3>
            </div>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-xs text-slate-500">الإجمالي</span>
                <span className="font-semibold text-slate-700">
                  {netTotal.toLocaleString("ar-EG")} {sym}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-xs text-emerald-600">المدفوع</span>
                <span className="font-semibold text-emerald-700">
                  {totalPaid.toLocaleString("ar-EG")} {sym}
                </span>
              </div>
              <div className="flex justify-between rounded-md bg-rose-50 px-2 py-1.5">
                <span className="text-xs font-bold text-rose-700">المتبقي</span>
                <span className="font-bold text-rose-700">
                  {remaining.toLocaleString("ar-EG")} {sym}
                </span>
              </div>
            </div>
            {remaining > 0 ? (
              <button
                type="button"
                onClick={() => setShowPaymentModal(true)}
                className="mt-3 flex w-full cursor-pointer items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-emerald-600 py-2 text-xs font-medium text-white transition-colors hover:bg-emerald-700"
              >
                <i className="ri-add-circle-line" />
                تسجيل دفعة الآن
              </button>
            ) : (
              <div className="mt-3 flex items-center justify-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 py-2 text-xs font-medium text-emerald-700">
                <i className="ri-checkbox-circle-line" />
                مدفوع بالكامل
              </div>
            )}
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
        defaultDocType="admin"
      />
    </div>
  );
}
