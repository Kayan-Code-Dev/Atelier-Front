import { useState } from "react";
import type { DeliverySearchRow, DeliverySearchStatus } from "../deliverySearch.types";
import {
  borderByStatus,
  deliveryStatusConfig,
  invoiceTypeConfig,
  paymentStatusConfig,
} from "../deliverySearch.config";

interface DeliverySearchTableProps {
  records: DeliverySearchRow[];
  onViewInvoice: (row: DeliverySearchRow) => void;
  onWorkflowConfirm: (row: DeliverySearchRow, action: string) => Promise<void>;
}

const actionsByStatus: Record<
  DeliverySearchStatus,
  { label: string; icon: string; color: string; bg: string }[]
> = {
  "ينتظر التسليم": [
    {
      label: "تأكيد التسليم",
      icon: "ri-truck-line",
      color: "text-emerald-700",
      bg: "bg-emerald-50 hover:bg-emerald-100 border-emerald-200",
    },
  ],
  "تأخر التسليم": [
    {
      label: "تأكيد التسليم",
      icon: "ri-truck-line",
      color: "text-red-700",
      bg: "bg-red-50 hover:bg-red-100 border-red-200",
    },
  ],
  "جاهز للاستلام": [
    {
      label: "تأكيد الاستلام",
      icon: "ri-checkbox-circle-line",
      color: "text-emerald-700",
      bg: "bg-emerald-50 hover:bg-emerald-100 border-emerald-200",
    },
  ],
  "تم التسليم": [],
  "ينتظر الإرجاع": [
    {
      label: "تأكيد الإرجاع",
      icon: "ri-arrow-go-back-line",
      color: "text-violet-700",
      bg: "bg-violet-50 hover:bg-violet-100 border-violet-200",
    },
  ],
  "تأخر الإرجاع": [
    {
      label: "تأكيد الإرجاع",
      icon: "ri-arrow-go-back-line",
      color: "text-orange-700",
      bg: "bg-orange-50 hover:bg-orange-100 border-orange-200",
    },
  ],
  "تم الإرجاع": [],
  "قيد التنفيذ": [],
  ملغي: [],
};

interface ConfirmModalProps {
  record: DeliverySearchRow;
  action: string;
  isPending: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

function ConfirmModal({
  record,
  action,
  isPending,
  onConfirm,
  onCancel,
}: ConfirmModalProps) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div
        className="absolute inset-0 bg-black/30 backdrop-blur-sm"
        onClick={onCancel}
        aria-hidden
      />
      <div className="relative bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl border border-slate-200">
        <div className="flex flex-col items-center gap-4 text-center">
          <div className="w-14 h-14 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center">
            <i className="ri-checkbox-circle-line text-2xl text-emerald-600" />
          </div>
          <div>
            <h3 className="text-base font-black text-slate-800">{action}</h3>
            <p className="text-sm text-slate-500 mt-1">
              فاتورة <span className="font-bold text-slate-700">{record.invoiceNumber}</span> للعميل{" "}
              <span className="font-bold text-slate-700">{record.customerName}</span>
            </p>
          </div>
          <div className="flex gap-3 w-full">
            <button
              type="button"
              onClick={onConfirm}
              disabled={isPending}
              className="flex-1 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 cursor-pointer transition-colors whitespace-nowrap disabled:opacity-50"
            >
              {isPending ? "جاري التنفيذ..." : "تأكيد"}
            </button>
            <button
              type="button"
              onClick={onCancel}
              disabled={isPending}
              className="flex-1 py-2.5 rounded-lg bg-slate-100 text-slate-700 text-sm font-bold hover:bg-slate-200 cursor-pointer transition-colors whitespace-nowrap disabled:opacity-50"
            >
              إلغاء
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export function DeliverySearchTable({
  records,
  onViewInvoice,
  onWorkflowConfirm,
}: DeliverySearchTableProps) {
  const [confirmModal, setConfirmModal] = useState<{
    record: DeliverySearchRow;
    action: string;
  } | null>(null);
  const [confirmPending, setConfirmPending] = useState(false);
  const [updatedStatuses, setUpdatedStatuses] = useState<
    Record<string, DeliverySearchStatus>
  >({});

  const getStatus = (r: DeliverySearchRow): DeliverySearchStatus =>
    updatedStatuses[r.id] ?? r.deliveryStatus;

  const handleAction = (record: DeliverySearchRow, actionLabel: string) => {
    setConfirmModal({ record, action: actionLabel });
  };

  const handleConfirm = async () => {
    if (!confirmModal) return;
    const { record, action } = confirmModal;
    try {
      setConfirmPending(true);
      await onWorkflowConfirm(record, action);
      let newStatus: DeliverySearchStatus = record.deliveryStatus;
      if (action === "تأكيد التسليم" || action === "تأكيد الاستلام") {
        newStatus = "تم التسليم";
      } else if (action === "تأكيد الإرجاع") {
        newStatus = "تم الإرجاع";
      }
      setUpdatedStatuses((prev) => ({ ...prev, [record.id]: newStatus }));
      setConfirmModal(null);
    } catch {
      /* الخطأ يُعرض من الصفحة الأم */
    } finally {
      setConfirmPending(false);
    }
  };

  const navigateToInvoice = (record: DeliverySearchRow) => {
    onViewInvoice(record);
  };

  if (records.length === 0) {
    return (
      <div className="bg-white rounded-xl border border-slate-200 py-16 flex flex-col items-center gap-3">
        <span className="w-14 h-14 flex items-center justify-center">
          <i className="ri-search-line text-4xl text-slate-300" />
        </span>
        <p className="text-sm font-semibold text-slate-400">لا توجد نتائج مطابقة</p>
      </div>
    );
  }

  return (
    <>
      <div className="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm" dir="rtl">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50">
                <th className="text-right px-4 py-3 text-xs font-black text-slate-500 whitespace-nowrap">
                  الفاتورة
                </th>
                <th className="text-right px-4 py-3 text-xs font-black text-slate-500 whitespace-nowrap">
                  العميل
                </th>
                <th className="text-right px-4 py-3 text-xs font-black text-slate-500 whitespace-nowrap">
                  الفرع
                </th>
                <th className="text-right px-4 py-3 text-xs font-black text-slate-500 whitespace-nowrap">
                  تاريخ الإنشاء
                </th>
                <th className="text-right px-4 py-3 text-xs font-black text-slate-500 whitespace-nowrap">
                  موعد التسليم
                </th>
                <th className="text-right px-4 py-3 text-xs font-black text-slate-500 whitespace-nowrap">
                  موعد الإرجاع
                </th>
                <th className="text-right px-4 py-3 text-xs font-black text-slate-500 whitespace-nowrap">
                  حالة التسليم
                </th>
                <th className="text-right px-4 py-3 text-xs font-black text-slate-500 whitespace-nowrap">
                  الدفع
                </th>
                <th className="text-right px-4 py-3 text-xs font-black text-slate-500 whitespace-nowrap">
                  المبلغ
                </th>
                <th className="text-center px-4 py-3 text-xs font-black text-slate-500 whitespace-nowrap">
                  الإجراءات
                </th>
              </tr>
            </thead>
            <tbody>
              {records.map((record) => {
                const status = getStatus(record);
                const statusCfg = deliveryStatusConfig[status];
                const typeCfg = invoiceTypeConfig[record.invoiceType];
                const payCfg =
                  paymentStatusConfig[record.paymentStatus] ??
                  paymentStatusConfig["غير مدفوع"];
                const actions = actionsByStatus[status];
                const leftBorderColor = borderByStatus[status];

                return (
                  <tr
                    key={record.id}
                    className={`border-b border-slate-100 hover:bg-slate-50 transition-colors duration-100 border-r-4 ${leftBorderColor}`}
                  >
                    <td className="px-4 py-3">
                      <div className="flex flex-col gap-1">
                        <span className="font-black text-slate-800 text-sm">
                          {record.invoiceNumber}
                        </span>
                        <span
                          className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border ${typeCfg.bg} ${typeCfg.color} ${typeCfg.border} w-fit`}
                        >
                          <span className="w-3.5 h-3.5 flex items-center justify-center">
                            <i className={`${typeCfg.icon} text-[10px]`} />
                          </span>
                          {record.invoiceType}
                        </span>
                      </div>
                    </td>

                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <div
                          className="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-black flex-shrink-0"
                          style={{
                            background: "linear-gradient(135deg, #64748b, #475569)",
                          }}
                        >
                          {record.customerName.charAt(0)}
                        </div>
                        <div>
                          <p className="font-semibold text-slate-800 text-xs whitespace-nowrap">
                            {record.customerName}
                          </p>
                          <p className="text-slate-400 text-[11px]">{record.customerPhone}</p>
                        </div>
                      </div>
                    </td>

                    <td className="px-4 py-3">
                      <span className="text-xs text-slate-600 whitespace-nowrap">
                        {record.branchName}
                      </span>
                    </td>

                    <td className="px-4 py-3">
                      <span className="text-xs text-slate-600 whitespace-nowrap">
                        {record.invoiceDate}
                      </span>
                    </td>

                    <td className="px-4 py-3">
                      <span
                        className={`text-xs font-semibold whitespace-nowrap ${
                          status === "تأخر التسليم" ? "text-red-600" : "text-slate-700"
                        }`}
                      >
                        {record.deliveryDate}
                      </span>
                    </td>

                    <td className="px-4 py-3">
                      {record.returnDate ? (
                        <span
                          className={`text-xs font-semibold whitespace-nowrap ${
                            status === "تأخر الإرجاع" ? "text-orange-600" : "text-slate-700"
                          }`}
                        >
                          {record.returnDate}
                        </span>
                      ) : (
                        <span className="text-slate-300 text-xs">—</span>
                      )}
                    </td>

                    <td className="px-4 py-3">
                      <span
                        className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border ${statusCfg.bg} ${statusCfg.color} ${statusCfg.border} whitespace-nowrap`}
                      >
                        <span
                          className={`w-1.5 h-1.5 rounded-full flex-shrink-0 ${statusCfg.dot}`}
                        />
                        {statusCfg.label}
                      </span>
                    </td>

                    <td className="px-4 py-3">
                      <span
                        className={`inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold border ${payCfg.bg} ${payCfg.color} ${payCfg.border} whitespace-nowrap`}
                      >
                        {record.paymentStatus}
                      </span>
                    </td>

                    <td className="px-4 py-3">
                      <div className="flex flex-col gap-0.5">
                        <span className="text-xs font-black text-slate-800">
                          {record.totalAmount.toLocaleString()}
                        </span>
                        {record.remaining > 0 && (
                          <span className="text-[11px] text-red-500 font-semibold">
                            متبقي: {record.remaining.toLocaleString()}
                          </span>
                        )}
                      </div>
                    </td>

                    <td className="px-4 py-3">
                      <div className="flex items-center justify-center gap-1.5">
                        {actions.map((act) => (
                          <button
                            key={act.label}
                            type="button"
                            onClick={() => handleAction(record, act.label)}
                            title={act.label}
                            className={`flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-[11px] font-bold cursor-pointer transition-colors whitespace-nowrap ${act.color} ${act.bg}`}
                          >
                            <span className="w-3.5 h-3.5 flex items-center justify-center">
                              <i className={`${act.icon} text-xs`} />
                            </span>
                            {act.label}
                          </button>
                        ))}
                        <button
                          type="button"
                          onClick={() => navigateToInvoice(record)}
                          title="عرض الفاتورة"
                          className="flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-[11px] font-bold cursor-pointer transition-colors whitespace-nowrap text-slate-600 bg-slate-50 hover:bg-slate-100 border-slate-200"
                        >
                          <span className="w-3.5 h-3.5 flex items-center justify-center">
                            <i className="ri-eye-line text-xs" />
                          </span>
                          عرض
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>

            <tfoot>
              <tr className="bg-slate-50 border-t-2 border-slate-200">
                <td colSpan={8} className="px-4 py-3 text-xs font-black text-slate-600 text-right">
                  إجمالي النتائج المعروضة ({records.length} فاتورة)
                </td>
                <td className="px-4 py-3">
                  <div className="flex flex-col gap-0.5">
                    <span className="text-xs font-black text-slate-800">
                      {records.reduce((s, r) => s + r.totalAmount, 0).toLocaleString()}
                    </span>
                    <span className="text-[11px] text-red-500 font-semibold">
                      متبقي: {records.reduce((s, r) => s + r.remaining, 0).toLocaleString()}
                    </span>
                  </div>
                </td>
                <td />
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      {confirmModal ? (
        <ConfirmModal
          record={confirmModal.record}
          action={confirmModal.action}
          isPending={confirmPending}
          onConfirm={() => void handleConfirm()}
          onCancel={() => !confirmPending && setConfirmModal(null)}
        />
      ) : null}
    </>
  );
}
