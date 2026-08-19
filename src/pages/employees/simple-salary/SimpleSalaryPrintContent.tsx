import type { TSimpleSalarySummary } from "@/api/simple-salary/simple-salary.types";
import { PAYMENT_METHOD_LABELS } from "@/api/simple-salary/simple-salary.types";
import { formatDate } from "@/utils/formatDate";
import { formatSimpleSalaryMoney } from "./utils";

export type SimpleSalaryPrintContentProps = {
  summary: TSimpleSalarySummary;
};

type PrintRow = {
  type: "addition" | "deduction" | "payment";
  typeLabel: string;
  date: string;
  reasonOrMethod: string;
  amount: number;
  amountSign: "+" | "-";
  notes?: string | null;
  cashbox?: string;
  reference?: string | null;
};

export function SimpleSalaryPrintContent({ summary }: SimpleSalaryPrintContentProps) {
  const printDate = formatDate(new Date().toISOString());
  const printTime = new Date().toLocaleTimeString("ar-EG", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  });

  const rows: PrintRow[] = [];

  (summary.additions ?? []).forEach((a) => {
    rows.push({
      type: "addition",
      typeLabel: "مكافأة",
      date: a.date,
      reasonOrMethod: a.reason,
      amount: a.amount,
      amountSign: "+",
      notes: a.notes,
    });
  });

  summary.deductions.forEach((d) => {
    rows.push({
      type: "deduction",
      typeLabel: "خصم",
      date: d.date,
      reasonOrMethod: d.reason,
      amount: d.amount,
      amountSign: "-",
      notes: d.notes,
    });
  });

  summary.payments.forEach((p) => {
    rows.push({
      type: "payment",
      typeLabel: "دفعة راتب",
      date: p.paid_at,
      reasonOrMethod: PAYMENT_METHOD_LABELS[p.payment_method] ?? p.payment_method,
      amount: p.amount,
      amountSign: "-",
      cashbox: p.cashbox?.name,
      reference: p.payment_reference,
      notes: p.notes,
    });
  });

  rows.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());

  return (
    <div
      dir="rtl"
      className="salary-print-root w-full max-w-[210mm] mx-auto bg-white text-gray-900"
    >
      <style>{`
        .salary-print-root { max-width: 100%; margin: 0 auto; background: #fff; }
        .salary-print-header { background: #2563eb; color: #fff; padding: 14px 20px; margin-bottom: 20px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .salary-print-title { margin: 0; font-size: 20px; font-weight: 700; }
        .salary-print-subtitle { margin: 4px 0 0 0; font-size: 11px; opacity: 0.95; }
        .salary-print-body { padding: 0 20px 24px; }
        .salary-print-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px 24px; margin-bottom: 20px; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
        .salary-print-info-item { display: flex; flex-direction: column; gap: 2px; }
        .salary-print-info-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
        .salary-print-info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
        .salary-print-mono { font-family: Consolas, Monaco, monospace; }
        .salary-print-summary { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 24px; }
        .salary-print-summary-item { padding: 12px 14px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; display: flex; flex-direction: column; gap: 4px; }
        .salary-print-summary-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
        .salary-print-summary-value { font-size: 14px; font-weight: 700; color: #1e293b; }
        .salary-print-deduction { color: #b45309; }
        .salary-print-addition { color: #047857; }
        .salary-print-net { color: #1d4ed8; }
        .salary-print-table-wrapper { border: 2px solid #1e293b; border-radius: 8px; overflow: hidden; padding: 16px; }
        .salary-print-table-title { margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #1e293b; padding: 0; }
        .salary-print-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .salary-print-table th, .salary-print-table td { padding: 10px 12px; text-align: right; border-bottom: 1px solid #e2e8f0; border-left: 1px solid #e2e8f0; }
        .salary-print-table th:first-child, .salary-print-table td:first-child { border-left: none; }
        .salary-print-table thead tr { background: #f1f5f9; }
        .salary-print-table thead th { font-weight: 600; color: #334155; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid #cbd5e1; }
        .salary-print-table tbody tr:last-child td { border-bottom: none; }
        .salary-print-row-alt { background: #f8fafc; }
        .salary-print-num { font-variant-numeric: tabular-nums; }
        .salary-print-type { font-weight: 600; }
        .salary-print-amount { font-weight: 600; font-variant-numeric: tabular-nums; }
        .salary-print-notes { color: #64748b; font-size: 11px; }
        .salary-print-empty { text-align: center; color: #94a3b8; padding: 24px; }
        .salary-print-paid-badge { margin-top: 20px; padding: 10px 16px; background: #d1fae5; color: #065f46; font-weight: 700; text-align: center; border-radius: 6px; }
        @media (max-width: 640px) { .salary-print-summary { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .salary-print-info-grid { grid-template-columns: 1fr; } }
      `}</style>
      {/* Header */}
      <div className="salary-print-header">
        <div className="salary-print-header-inner">
          <div>
            <h1 className="salary-print-title">كشف راتب — فاتورة</h1>
            <p className="salary-print-subtitle">
              تاريخ الطباعة: {printDate} — {printTime}
            </p>
          </div>
        </div>
      </div>

      {/* Body */}
      <div className="salary-print-body">
        {/* Employee info */}
        <div className="salary-print-info-grid">
          <div className="salary-print-info-item">
            <span className="salary-print-info-label">الموظف</span>
            <span className="salary-print-info-value">{summary.employee.name}</span>
          </div>
          <div className="salary-print-info-item">
            <span className="salary-print-info-label">كود الموظف</span>
            <span className="salary-print-info-value salary-print-mono">{summary.employee.employee_code}</span>
          </div>
          <div className="salary-print-info-item">
            <span className="salary-print-info-label">الفترة</span>
            <span className="salary-print-info-value">{summary.period}</span>
          </div>
          {(summary.from_date || summary.to_date) && (
            <div className="salary-print-info-item" style={{ gridColumn: "1 / -1" }}>
              <span className="salary-print-info-label">نطاق التاريخ</span>
              <span className="salary-print-info-value">
                {summary.from_date ? formatDate(summary.from_date) : "—"} إلى{" "}
                {summary.to_date ? formatDate(summary.to_date) : "—"}
              </span>
            </div>
          )}
        </div>

        {/* Summary */}
        <div className="salary-print-summary">
          <div className="salary-print-summary-item">
            <span className="salary-print-summary-label">الراتب</span>
            <span className="salary-print-summary-value">{formatSimpleSalaryMoney(summary.salary)} ج.م</span>
          </div>
          <div className="salary-print-summary-item">
            <span className="salary-print-summary-label">إجمالي الخصومات</span>
            <span className="salary-print-summary-value salary-print-deduction">{formatSimpleSalaryMoney(summary.total_deductions)} ج.م</span>
          </div>
          <div className="salary-print-summary-item">
            <span className="salary-print-summary-label">إجمالي المكافآت</span>
            <span className="salary-print-summary-value salary-print-addition">{formatSimpleSalaryMoney(summary.total_additions ?? 0)} ج.م</span>
          </div>
          <div className="salary-print-summary-item">
            <span className="salary-print-summary-label">الصافي المستحق</span>
            <span className="salary-print-summary-value salary-print-net">{formatSimpleSalaryMoney(summary.net_to_pay)} ج.م</span>
          </div>
          <div className="salary-print-summary-item">
            <span className="salary-print-summary-label">المدفوع / المتبقي</span>
            <span className="salary-print-summary-value">{formatSimpleSalaryMoney(summary.total_paid)} / {formatSimpleSalaryMoney(summary.remaining_to_pay)} ج.م</span>
          </div>
        </div>

        {/* Table wrapper - outer border */}
        <div className="salary-print-table-wrapper">
          <h2 className="salary-print-table-title">جدول الحركات (مكافآت — خصومات — دفعات)</h2>
          <table className="salary-print-table">
            <thead>
              <tr>
                <th>#</th>
                <th>النوع</th>
                <th>التاريخ</th>
                <th>السبب / طريقة الدفع</th>
                <th>المبلغ</th>
                <th>الصندوق</th>
                <th>مرجع</th>
                <th>ملاحظات</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 ? (
                <tr>
                  <td colSpan={8} className="salary-print-empty">لا توجد حركات مسجلة</td>
                </tr>
              ) : (
                rows.map((row, idx) => (
                  <tr key={`${row.type}-${idx}`} className={idx % 2 === 1 ? "salary-print-row-alt" : ""}>
                    <td className="salary-print-num">{idx + 1}</td>
                    <td className="salary-print-type">{row.typeLabel}</td>
                    <td className="salary-print-num">{formatDate(row.date)}</td>
                    <td>{row.reasonOrMethod}</td>
                    <td className="salary-print-amount">{row.amountSign}{formatSimpleSalaryMoney(row.amount)} ج.م</td>
                    <td>{row.cashbox ?? "—"}</td>
                    <td>{row.reference ?? "—"}</td>
                    <td className="salary-print-notes">{row.notes ?? "—"}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {summary.fully_paid && (
          <div className="salary-print-paid-badge">مُدفوع بالكامل</div>
        )}
      </div>
    </div>
  );
}
