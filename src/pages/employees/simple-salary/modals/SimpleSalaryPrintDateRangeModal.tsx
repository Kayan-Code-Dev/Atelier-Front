import { useState } from "react";
import type { TSimpleSalarySummary } from "@/api/simple-salary/simple-salary.types";
import { getSimpleSalarySummary } from "@/api/simple-salary/simple-salary.service";
import { toast } from "sonner";

export type SimpleSalaryPrintDateRangeModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  employeeId: number;
  employeeName: string;
  /** Payroll period YYYY-MM (must match كشوفات الشهر الحالي) */
  period: string;
  onPrintReady: (summary: TSimpleSalarySummary) => void;
};

export function SimpleSalaryPrintDateRangeModal({
  open,
  onOpenChange,
  employeeId,
  employeeName,
  period,
  onPrintReady,
}: SimpleSalaryPrintDateRangeModalProps) {
  const [fromDate, setFromDate] = useState("");
  const [toDate, setToDate] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    if (fromDate && toDate && fromDate > toDate) {
      toast.error("من التاريخ يجب أن يكون قبل أو يساوي إلى التاريخ");
      return;
    }
    setLoading(true);
    const params: { from_date?: string; to_date?: string } = {};
    if (fromDate) params.from_date = fromDate;
    if (toDate) params.to_date = toDate;
    const summary = await getSimpleSalarySummary(employeeId, period, params);
    setLoading(false);
    if (summary) {
      onOpenChange(false);
      onPrintReady(summary);
    } else {
      toast.error("لا توجد بيانات للطباعة");
    }
  };

  const handleClose = (nextOpen: boolean) => {
    if (!nextOpen) {
      setFromDate("");
      setToDate("");
    }
    onOpenChange(nextOpen);
  };

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4"
      onClick={() => handleClose(false)}
      role="presentation"
    >
      <div
        className="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden"
        onClick={(e) => e.stopPropagation()}
        dir="rtl"
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h2 className="text-lg font-bold text-gray-800 flex items-center gap-2">
              <i className="ri-printer-line text-gray-600" />
              طباعة فاتورة كشف الراتب
            </h2>
            <p className="text-xs text-gray-500 mt-1">{employeeName}</p>
          </div>
          <button
            type="button"
            onClick={() => handleClose(false)}
            className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500"
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-lg" />
          </button>
        </div>

        <div className="p-6 space-y-4">
          <p className="text-sm text-gray-600">
            اختر نطاق التاريخ للطباعة (اختياري). الفترة المحاسبية:{" "}
            <span className="font-mono font-semibold text-gray-800">{period}</span>
          </p>
          <div className="grid gap-2">
            <label htmlFor="from_date" className="text-sm font-semibold text-gray-700">
              من تاريخ (اختياري)
            </label>
            <input
              id="from_date"
              type="date"
              value={fromDate}
              onChange={(e) => setFromDate(e.target.value)}
              className="w-full px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-200"
            />
          </div>
          <div className="grid gap-2">
            <label htmlFor="to_date" className="text-sm font-semibold text-gray-700">
              إلى تاريخ (اختياري)
            </label>
            <input
              id="to_date"
              type="date"
              value={toDate}
              onChange={(e) => setToDate(e.target.value)}
              className="w-full px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-200"
            />
          </div>
        </div>

        <div className="px-6 pb-5 flex gap-3 justify-end border-t border-gray-100">
          <button
            type="button"
            onClick={() => handleClose(false)}
            className="px-5 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50"
          >
            إلغاء
          </button>
          <button
            type="button"
            onClick={handleSubmit}
            disabled={loading}
            className="px-5 py-2.5 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-900 disabled:opacity-50 flex items-center gap-2"
          >
            {loading ? (
              <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
            ) : (
              <i className="ri-printer-line" />
            )}
            عرض وطباعة
          </button>
        </div>
      </div>
    </div>
  );
}
