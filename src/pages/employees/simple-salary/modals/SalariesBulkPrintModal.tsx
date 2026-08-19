import { useRef } from "react";
import { periodToArabicLabel } from "../monthLabel";

export type SalariesBulkPrintRow = {
  name: string;
  code: string;
  roleLabel: string;
  branchLabel: string;
  basic: number;
  allowances: number;
  additions: number;
  deductions: number;
  net: number;
  paid: boolean;
};

export type SalariesBulkPrintModalProps = {
  open: boolean;
  onClose: () => void;
  period: string;
  rows: SalariesBulkPrintRow[];
};

function fmt(n: number) {
  return n.toLocaleString("ar-SA", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}

export function SalariesBulkPrintModal({
  open,
  onClose,
  period,
  rows,
}: SalariesBulkPrintModalProps) {
  const printRef = useRef<HTMLDivElement>(null);
  const monthLabel = periodToArabicLabel(period);

  const totalNet = rows.reduce((s, r) => s + r.net, 0);
  const totalAdd = rows.reduce((s, r) => s + r.additions, 0);
  const totalDed = rows.reduce((s, r) => s + r.deductions, 0);

  const handlePrint = () => {
    const el = printRef.current;
    if (!el) return;
    const win = window.open("", "_blank");
    if (!win) return;
    win.document.write(`<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"/>
      <title>كشف رواتب — ${monthLabel}</title>
      <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; color: #111; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .sub { color: #666; margin-bottom: 16px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #f3f4f6; font-weight: 600; }
        .sum { margin-top: 16px; font-size: 11px; color: #444; }
      </style></head><body>${el.innerHTML}</body></html>`);
    win.document.close();
    setTimeout(() => {
      win.print();
      win.close();
    }, 300);
  };

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden"
        onClick={(e) => e.stopPropagation()}
        dir="rtl"
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
          <h2 className="text-lg font-bold text-gray-800">طباعة كشف الرواتب</h2>
          <button
            type="button"
            onClick={onClose}
            className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100"
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-gray-500 text-lg" />
          </button>
        </div>

        <div className="p-6 overflow-y-auto flex-1">
          <div className="bg-gray-50 rounded-xl p-4 border border-gray-100 mb-4 text-sm text-gray-600">
            <p className="font-semibold text-gray-800">{monthLabel}</p>
            <p className="text-xs mt-1">
              {rows.length} موظف في هذه المعاينة — إجمالي الصافي:{" "}
              <span className="font-bold text-gray-900">{fmt(totalNet)} ج.م</span>
            </p>
          </div>

          <div ref={printRef} className="hidden print:block">
            <h1>كشف رواتب — {monthLabel}</h1>
            <p className="sub">
              تاريخ الطباعة: {new Date().toLocaleDateString("ar-SA")} — {rows.length} موظف
            </p>
            <table>
              <thead>
                <tr>
                  <th>الموظف</th>
                  <th>الكود</th>
                  <th>المسمى</th>
                  <th>الفرع</th>
                  <th>الأساسي</th>
                  <th>البدلات</th>
                  <th>مكافآت</th>
                  <th>خصومات</th>
                  <th>الصافي</th>
                  <th>الحالة</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r, i) => (
                  <tr key={i}>
                    <td style={{ textAlign: "right" }}>{r.name}</td>
                    <td>{r.code}</td>
                    <td>{r.roleLabel}</td>
                    <td>{r.branchLabel}</td>
                    <td>{fmt(r.basic)}</td>
                    <td>{fmt(r.allowances)}</td>
                    <td>{fmt(r.additions)}</td>
                    <td>{fmt(r.deductions)}</td>
                    <td style={{ fontWeight: 700 }}>{fmt(r.net)}</td>
                    <td>{r.paid ? "مدفوع" : "معلق"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            <p className="sum">
              إجمالي المكافآت: {fmt(totalAdd)} ج.م — إجمالي الخصومات: {fmt(totalDed)} ج.م — صافي
              المجموع: {fmt(totalNet)} ج.م
            </p>
          </div>

          <div className="rounded-xl border border-gray-100 overflow-x-auto max-h-64 overflow-y-auto">
            <table className="w-full text-xs">
              <thead>
                <tr className="bg-gray-50 border-b border-gray-100">
                  <th className="text-right px-3 py-2">الموظف</th>
                  <th className="px-2 py-2">الصافي</th>
                  <th className="px-2 py-2">الحالة</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r, i) => (
                  <tr key={i} className="border-b border-gray-50">
                    <td className="px-3 py-2 text-right font-medium">{r.name}</td>
                    <td className="px-2 py-2 text-center tabular-nums">{fmt(r.net)}</td>
                    <td className="px-2 py-2 text-center">
                      {r.paid ? (
                        <span className="text-emerald-600">مدفوع</span>
                      ) : (
                        <span className="text-amber-600">معلق</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        <div className="px-6 pb-5 pt-3 flex gap-3 justify-end border-t border-gray-100 flex-shrink-0">
          <button
            type="button"
            onClick={onClose}
            className="px-5 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50"
          >
            إلغاء
          </button>
          <button
            type="button"
            onClick={handlePrint}
            disabled={rows.length === 0}
            className="px-6 py-2.5 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-900 disabled:opacity-40 flex items-center gap-2"
          >
            <i className="ri-printer-line" /> طباعة ({rows.length})
          </button>
        </div>
      </div>
    </div>
  );
}
