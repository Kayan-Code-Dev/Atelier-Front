import { useRef } from "react";
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Printer } from "lucide-react";
import type { TSimpleSalarySummary } from "@/api/simple-salary/simple-salary.types";
import { SimpleSalaryPrintContent } from "../SimpleSalaryPrintContent";
import { sanitizePrintHtml } from "@/lib/print-utils";

const PRINT_STYLES = `
  @page { size: A4; margin: 14mm; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  * { box-sizing: border-box; -webkit-font-smoothing: antialiased; }
  html, body { margin: 0; padding: 0; width: 100%; font-family: 'Cairo', 'Segoe UI', Arial, sans-serif; direction: rtl; font-size: 13px; line-height: 1.5; color: #1f2937; background: #fff; }
  .salary-print-root { max-width: 100%; margin: 0 auto; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .salary-print-header { background: #2563eb !important; color: #fff !important; padding: 14px 20px; margin-bottom: 20px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .salary-print-header-inner { display: flex; align-items: center; justify-content: space-between; }
  .salary-print-title { margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.02em; }
  .salary-print-subtitle { margin: 4px 0 0 0; font-size: 11px; opacity: 0.95; }
  .salary-print-body { padding: 0 20px 24px; }
  .salary-print-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px 24px; margin-bottom: 20px; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
  .salary-print-info-item { display: flex; flex-direction: column; gap: 2px; }
  .salary-print-info-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
  .salary-print-info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
  .salary-print-mono { font-family: 'Consolas', 'Monaco', monospace; }
  .salary-print-summary { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 24px; }
  .salary-print-summary-item { padding: 12px 14px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; display: flex; flex-direction: column; gap: 4px; }
  .salary-print-summary-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
  .salary-print-summary-value { font-size: 14px; font-weight: 700; color: #1e293b; }
  .salary-print-deduction { color: #b45309 !important; }
  .salary-print-addition { color: #047857 !important; }
  .salary-print-net { color: #1d4ed8 !important; }
  .salary-print-table-wrapper { border: 2px solid #1e293b !important; border-radius: 8px; overflow: hidden; padding: 16px; page-break-inside: avoid; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .salary-print-table-title { margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #1e293b; padding: 0 4px; }
  .salary-print-table { width: 100%; border-collapse: collapse; font-size: 12px; }
  .salary-print-table th, .salary-print-table td { padding: 10px 12px; text-align: right; border-bottom: 1px solid #e2e8f0; border-left: 1px solid #e2e8f0; }
  .salary-print-table th:first-child, .salary-print-table td:first-child { border-left: none; }
  .salary-print-table thead tr { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .salary-print-table thead th { font-weight: 600; color: #334155; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid #cbd5e1; }
  .salary-print-table tbody tr:last-child td { border-bottom: none; }
  .salary-print-row-alt { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .salary-print-num { font-variant-numeric: tabular-nums; }
  .salary-print-type { font-weight: 600; }
  .salary-print-amount { font-weight: 600; font-variant-numeric: tabular-nums; }
  .salary-print-notes { color: #64748b; font-size: 11px; }
  .salary-print-empty { text-align: center !important; color: #94a3b8; padding: 24px !important; }
  .salary-print-paid-badge { margin-top: 20px; padding: 10px 16px; background: #d1fae5 !important; color: #065f46 !important; font-weight: 700; text-align: center; border-radius: 6px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  @media print { .salary-print-root * { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
`;

export type SimpleSalaryPrintModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  summary: TSimpleSalarySummary | null;
};

export function SimpleSalaryPrintModal({
  open,
  onOpenChange,
  summary,
}: SimpleSalaryPrintModalProps) {
  const printRef = useRef<HTMLDivElement>(null);

  const handlePrint = () => {
    if (!summary) return;
    const printContent = document.getElementById("salary-print-content");
    if (!printContent) return;
    const printWindow = window.open("", "_blank");
    if (!printWindow) return;
    const bodyHtml = sanitizePrintHtml(printContent.innerHTML);
    printWindow.document.write(`
      <!DOCTYPE html>
      <html dir="rtl" lang="ar">
        <head>
          <meta charset="utf-8" />
          <title>كشف راتب — ${summary.employee.name} — ${summary.period}</title>
          <style>${PRINT_STYLES}</style>
        </head>
        <body>${bodyHtml}</body>
      </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    let printed = false;
    const doPrint = () => {
      if (printed) return;
      printed = true;
      printWindow.print();
      printWindow.close();
    };
    printWindow.onload = () => setTimeout(doPrint, 300);
    setTimeout(doPrint, 1500);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton={false}
        className="max-h-[90vh] max-w-[95vw] overflow-auto rounded-2xl border-gray-100 p-0 gap-0"
        bodyClassName="p-0"
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 className="text-lg font-bold text-gray-800 flex items-center gap-2">
            <Printer className="h-5 w-5 text-gray-600" />
            طباعة فاتورة كشف الراتب
          </h2>
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500"
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-lg" />
          </button>
        </div>
        <div className="p-6">
          {summary ? (
            <>
              <div
                id="salary-print-content"
                ref={printRef}
                className="bg-white rounded-xl border border-gray-100 shadow-sm print:shadow-none"
              >
                <SimpleSalaryPrintContent summary={summary} />
              </div>
              <div className="flex justify-end gap-3 pt-5 border-t border-gray-100 mt-5">
                <Button
                  variant="outline"
                  className="border-gray-200 text-gray-600"
                  onClick={() => onOpenChange(false)}
                >
                  إلغاء
                </Button>
                <Button
                  onClick={handlePrint}
                  className="bg-gray-800 hover:bg-gray-900 text-white gap-2"
                >
                  <Printer className="h-4 w-4" />
                  طباعة
                </Button>
              </div>
            </>
          ) : (
            <p className="text-sm text-gray-500 py-8 text-center">لا توجد بيانات للطباعة</p>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
