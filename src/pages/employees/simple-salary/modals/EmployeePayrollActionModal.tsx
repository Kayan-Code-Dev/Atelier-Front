import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { useGetSimpleSalarySummaryQueryOptions } from "@/api/simple-salary/simple-salary.hooks";
import { getDefaultPeriod, PERIOD_REGEX } from "../constants";
import { SimpleSalarySummaryCard } from "../SimpleSalarySummaryCard";
import { AddSimpleSalaryDeductionModal } from "./AddSimpleSalaryDeductionModal";
import { AddSimpleSalaryAdditionModal } from "./AddSimpleSalaryAdditionModal";
import { PaySimpleSalaryModal } from "./PaySimpleSalaryModal";
import { SimpleSalaryPrintModal } from "./SimpleSalaryPrintModal";
import { Loader2 } from "lucide-react";

export type EmployeePayrollActionModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  employeeId: number;
  employeeName: string;
  employeeCode?: string;
};

export function EmployeePayrollActionModal({
  open,
  onOpenChange,
  employeeId,
  employeeName,
  employeeCode = "",
}: EmployeePayrollActionModalProps) {
  const [period, setPeriod] = useState(getDefaultPeriod());
  const [addDeductionOpen, setAddDeductionOpen] = useState(false);
  const [addAdditionOpen, setAddAdditionOpen] = useState(false);
  const [payModalOpen, setPayModalOpen] = useState(false);
  const [printModalOpen, setPrintModalOpen] = useState(false);

  const periodValid = useMemo(() => !!period && PERIOD_REGEX.test(period), [period]);

  const {
    data: summary,
    isPending: summaryPending,
    refetch: refetchSummary,
  } = useQuery({
    ...useGetSimpleSalarySummaryQueryOptions(employeeId, periodValid ? period : null),
    enabled: open && periodValid,
    staleTime: 1000 * 60 * 2,
  });

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent
          showCloseButton={false}
          className="max-h-[90vh] w-[95vw] max-w-[calc(100%-2rem)] sm:max-w-[90vw] sm:w-[90vw] gap-0 overflow-hidden rounded-2xl border-gray-100 p-0"
          bodyClassName="p-0 max-h-[min(90vh,900px)] overflow-y-auto"
        >
          <div className="sticky top-0 z-10 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-white px-5 py-4">
            <div className="flex flex-wrap items-center gap-3 min-w-0">
              <div className="w-9 h-9 flex items-center justify-center rounded-xl bg-violet-50 shrink-0">
                <i className="ri-file-list-3-line text-violet-600 text-lg" />
              </div>
              <div className="min-w-0">
                <h2 className="text-lg font-bold text-gray-800 truncate">
                  كشف الراتب — {employeeName}
                </h2>
                {employeeCode ? (
                  <p className="text-xs font-mono text-gray-400">{employeeCode}</p>
                ) : null}
              </div>
            </div>
            <div className="flex items-center gap-2 flex-wrap">
              <div className="flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3">
                <i className="ri-calendar-line text-gray-400 text-sm" />
                <input
                  type="month"
                  value={period}
                  onChange={(e) => setPeriod(e.target.value)}
                  className="h-9 min-w-[8.5rem] border-0 bg-transparent p-0 font-mono text-sm text-gray-800 focus:outline-none focus:ring-0"
                />
              </div>
              <button
                type="button"
                onClick={() => onOpenChange(false)}
                className="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 transition-colors"
                aria-label="إغلاق"
              >
                <i className="ri-close-line text-lg" />
              </button>
            </div>
          </div>

          <div className="space-y-5 px-5 py-5">
            {!periodValid && (
              <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-gray-200 bg-gray-50 py-10 text-center">
                <i className="ri-calendar-line text-gray-300 text-3xl" />
                <p className="text-sm text-gray-500">اختر شهراً صالحاً (YYYY-MM) لعرض الملخص.</p>
              </div>
            )}

            {periodValid && summaryPending && (
              <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-gray-100 bg-[#fafaff] py-14">
                <Loader2 className="h-8 w-8 animate-spin text-violet-600" />
                <p className="text-sm font-medium text-gray-500">جاري تحميل الملخص...</p>
              </div>
            )}

            {periodValid && !summaryPending && summary && (
              <SimpleSalarySummaryCard
                summary={summary}
                onAddDeduction={() => setAddDeductionOpen(true)}
                onAddAddition={() => setAddAdditionOpen(true)}
                onPay={() => setPayModalOpen(true)}
                onPrint={() => setPrintModalOpen(true)}
              />
            )}

            {periodValid && !summaryPending && !summary && (
              <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-gray-200 bg-gray-50 py-12 text-center">
                <i className="ri-file-chart-line text-gray-300 text-3xl" />
                <p className="text-sm text-gray-500">لا توجد بيانات لهذا الموظف في الشهر المحدد.</p>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>

      {summary ? (
        <>
          <AddSimpleSalaryDeductionModal
            open={addDeductionOpen}
            onOpenChange={setAddDeductionOpen}
            employeeId={summary.employee.id}
            employeeName={summary.employee.name}
            period={summary.period}
            onSuccess={() => refetchSummary()}
          />
          <AddSimpleSalaryAdditionModal
            open={addAdditionOpen}
            onOpenChange={setAddAdditionOpen}
            employeeId={summary.employee.id}
            employeeName={summary.employee.name}
            period={summary.period}
            onSuccess={() => refetchSummary()}
          />
          <PaySimpleSalaryModal
            open={payModalOpen}
            onOpenChange={setPayModalOpen}
            summary={summary}
            onSuccess={() => refetchSummary()}
          />
          <SimpleSalaryPrintModal
            open={printModalOpen}
            onOpenChange={setPrintModalOpen}
            summary={summary}
          />
        </>
      ) : null}
    </>
  );
}
