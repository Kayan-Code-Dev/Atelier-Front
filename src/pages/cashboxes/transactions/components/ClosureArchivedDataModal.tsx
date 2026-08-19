import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { useGetClosureArchivedDataQueryOptions } from "@/api/v2/cashboxes/cashboxes.hooks";
import type { TClosureArchivedDataType } from "@/api/v2/cashboxes/cashboxes.types";
import {
  CLOSURE_ARCHIVED_TYPE_OPTIONS,
  archivedColumnHeader,
  closureNumeric,
  collectAllArchivedColumnKeys,
  formatArchivedCellValue,
} from "../closureArchivedData.helpers";

const PER_PAGE = 30;

const filterControlClass =
  "flex h-10 min-h-10 w-full min-w-[200px] items-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-400 focus:outline-none";

const filterLabelClass =
  "mb-1.5 block text-xs font-medium text-gray-500";

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  closureId: number;
};

const fmtMoney = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

export function ClosureArchivedDataModal({
  open,
  onOpenChange,
  closureId,
}: Props) {
  const [archivedType, setArchivedType] =
    useState<TClosureArchivedDataType>("payments_expenses");
  const [page, setPage] = useState(1);

  useEffect(() => {
    if (!open) {
      setPage(1);
      setArchivedType("payments_expenses");
    }
  }, [open]);

  const params = useMemo(
    () => ({
      type: archivedType,
      page,
      per_page: PER_PAGE,
    }),
    [archivedType, page]
  );

  const queryEnabled = open && closureId > 0;

  const { data, isPending, isError, error } = useQuery({
    ...useGetClosureArchivedDataQueryOptions(
      closureId,
      params,
      queryEnabled
    ),
  });

  const rows = data?.data ?? [];
  const columns = useMemo(
    () =>
      collectAllArchivedColumnKeys(data?.data ?? [], archivedType),
    [data, archivedType]
  );

  const handleTypeChange = (t: TClosureArchivedDataType) => {
    setArchivedType(t);
    setPage(1);
  };

  const totalPages = data?.total_pages ?? 1;
  const canPrev = page > 1;
  const canNext = page < totalPages;

  const closure = data?.closure;
  const cashboxName = data?.cashbox?.name;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className="w-[min(96vw,1320px)] max-w-[1320px] max-h-[92vh] flex flex-col gap-0 overflow-hidden p-0 sm:max-w-[min(96vw,1320px)]"
        dir="rtl"
        bodyClassName="flex flex-col flex-1 min-h-0 overflow-y-auto overflow-x-hidden p-0 gap-0 max-h-[inherit]"
        aria-describedby="closure-archived-desc"
        footer={
          <div className="border-t border-slate-200 bg-white px-6 py-3 shadow-[0_-4px_12px_-2px_rgba(15,23,42,0.08)]">
            <div className="flex justify-end">
              <Button
                type="button"
                variant="secondary"
                onClick={() => onOpenChange(false)}
              >
                إغلاق
              </Button>
            </div>
          </div>
        }
      >
        <div className="shrink-0 border-b border-slate-200/90 bg-linear-to-l from-slate-50 to-white px-6 py-5">
          <div className="flex flex-wrap items-start gap-4">
            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
              <i className="ri-archive-2-line text-2xl" />
            </div>
            <div className="min-w-0 flex-1 space-y-1 text-right">
              <DialogHeader className="border-0 p-0 m-0 space-y-1 text-right">
                <DialogTitle className="text-xl font-bold text-slate-800 sm:text-2xl">
                  أرشيف فترة الإقفال
                </DialogTitle>
                <DialogDescription
                  id="closure-archived-desc"
                  className="text-right text-sm text-slate-500"
                >
                  عرض كامل الحقول لكل سجل مؤرشف حسب نوع البيانات المختار.
                </DialogDescription>
              </DialogHeader>
              {(cashboxName || closure) && (
                <p className="text-sm text-slate-600">
                  <span className="font-semibold text-slate-800">
                    {cashboxName ?? "الصندوق"}
                  </span>
                  {closure != null && (
                    <>
                      {" "}
                      · إقفال{" "}
                      <span className="font-mono text-blue-700">
                        #{closure.id}
                      </span>
                      {" · "}
                      <span className="text-slate-500">
                        {String(closure.from_date).slice(0, 10)} ←{" "}
                        {String(closure.to_date).slice(0, 10)}
                      </span>
                    </>
                  )}
                </p>
              )}
            </div>
          </div>

          {closure != null && !isPending && (
            <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
              <div className="rounded-xl border border-amber-100 bg-amber-50/90 p-3 text-center shadow-sm">
                <p className="text-[11px] font-medium text-amber-800/80">
                  رصيد الافتتاح
                </p>
                <p className="mt-1 text-sm font-bold tabular-nums text-amber-900">
                  {fmtMoney(closureNumeric(closure.opening_balance))}{" "}
                  <span className="text-xs font-normal opacity-70">ج.م</span>
                </p>
              </div>
              <div className="rounded-xl border border-green-100 bg-green-50/90 p-3 text-center shadow-sm">
                <p className="text-[11px] font-medium text-green-800/80">
                  إجمالي الإيرادات
                </p>
                <p className="mt-1 text-sm font-bold tabular-nums text-green-800">
                  +{fmtMoney(closureNumeric(closure.total_income))}
                </p>
              </div>
              <div className="rounded-xl border border-red-100 bg-red-50/90 p-3 text-center shadow-sm">
                <p className="text-[11px] font-medium text-red-800/80">
                  إجمالي المصروفات
                </p>
                <p className="mt-1 text-sm font-bold tabular-nums text-red-700">
                  −{fmtMoney(closureNumeric(closure.total_expense))}
                </p>
              </div>
              <div className="rounded-xl border border-sky-100 bg-sky-50/90 p-3 text-center shadow-sm">
                <p className="text-[11px] font-medium text-sky-800/80">الصافي</p>
                <p className="mt-1 text-sm font-bold tabular-nums text-sky-900">
                  {fmtMoney(closureNumeric(closure.net_change))}
                </p>
              </div>
              <div className="rounded-xl border border-emerald-200 bg-emerald-50/90 p-3 text-center shadow-sm">
                <p className="text-[11px] font-medium text-emerald-800/80">
                  رصيد الإقفال
                </p>
                <p className="mt-1 text-sm font-bold tabular-nums text-emerald-800">
                  {fmtMoney(closureNumeric(closure.closing_balance))}
                </p>
              </div>
              <div className="rounded-xl border border-violet-100 bg-violet-50/90 p-3 text-center shadow-sm">
                <p className="text-[11px] font-medium text-violet-800/80">
                  حركات / عكس
                </p>
                <p className="mt-1 text-sm font-bold text-violet-900">
                  {closure.transaction_count}
                  {closure.reversal_count > 0 && (
                    <span className="mr-1 text-xs font-normal text-violet-600">
                      ({closure.reversal_count} عكس)
                    </span>
                  )}
                </p>
              </div>
            </div>
          )}
        </div>

        <div className="shrink-0 border-b border-blue-100/80 bg-white px-6 py-4">
          <div className="w-full rounded-xl border border-blue-100 bg-white p-4 shadow-sm">
            <div className="flex flex-wrap items-end gap-4">
              <div className="min-w-[220px] flex-1">
                <label htmlFor="closure-archived-type" className={filterLabelClass}>
                  نوع البيانات المعروضة
                </label>
                <select
                  id="closure-archived-type"
                  value={archivedType}
                  onChange={(e) =>
                    handleTypeChange(e.target.value as TClosureArchivedDataType)
                  }
                  className={filterControlClass}
                >
                  {CLOSURE_ARCHIVED_TYPE_OPTIONS.map((o) => (
                    <option key={o.value} value={o.value}>
                      {o.label}
                    </option>
                  ))}
                </select>
              </div>
              {data != null && (
                <div className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-600">
                  <i className="ri-database-2-line text-slate-400" />
                  <span>
                    إجمالي السجلات:{" "}
                    <strong className="text-slate-800">{data.total}</strong>
                  </span>
                  <span className="text-slate-400">|</span>
                  <span>
                    الأعمدة:{" "}
                    <strong className="text-slate-800">{columns.length}</strong>
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="px-6 pb-2">
          <div className="sys-card flex flex-col overflow-hidden">
            <div className="flex shrink-0 items-center justify-between border-b border-slate-200/80 px-5 py-3">
              <h3 className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <i className="ri-table-line text-blue-500" />
                جدول السجلات المؤرشفة
              </h3>
              {!isPending && !isError && (
                <span className="text-xs text-slate-400">
                  {rows.length} سجل في هذه الصفحة · {columns.length} عمود
                </span>
              )}
            </div>

            <div className="max-h-[min(60vh,620px)] w-full overflow-auto">
              {isPending && (
                <div className="space-y-2 p-5">
                  <Skeleton className="h-10 w-full" />
                  {Array.from({ length: 8 }).map((_, i) => (
                    <Skeleton key={i} className="h-9 w-full" />
                  ))}
                </div>
              )}
              {isError && (
                <div className="flex flex-col items-center justify-center py-16 text-center">
                  <i className="ri-error-warning-line mb-3 text-4xl text-red-400" />
                  <p className="text-sm font-medium text-red-600">
                    {error instanceof Error ? error.message : "تعذر تحميل البيانات"}
                  </p>
                </div>
              )}
              {!isPending && !isError && rows.length === 0 && (
                <div className="py-16 text-center text-slate-400">
                  <i className="ri-inbox-line mb-3 block text-5xl opacity-60" />
                  <p className="text-sm font-medium text-slate-500">
                    لا توجد سجلات لهذا النوع في هذا الإقفال
                  </p>
                </div>
              )}
              {!isPending && !isError && rows.length > 0 && columns.length > 0 && (
                <table
                  className="sys-table w-max min-w-full [&_tbody_tr]:h-auto [&_tbody_tr_td]:align-top! [&_tbody_tr_td]:py-2! [&_tbody_tr_td]:leading-snug!"
                  style={{ tableLayout: "auto" }}
                >
                  <thead className="sticky top-0 z-10 shadow-sm [&_tr]:shadow-md">
                    <tr>
                      <th className="!min-w-[48px]">#</th>
                      {columns.map((key) => (
                        <th
                          key={key}
                          className="!whitespace-normal !text-right !align-bottom !leading-snug"
                          title={archivedColumnHeader(key)}
                        >
                          {archivedColumnHeader(key)}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.map((row, rowIdx) => {
                      const globalIdx = (page - 1) * PER_PAGE + rowIdx + 1;
                      return (
                        <tr
                          key={
                            row.id != null
                              ? `id-${String(row.id)}-${rowIdx}`
                              : `row-${rowIdx}`
                          }
                        >
                          <td className="bg-slate-50/80 text-center align-top font-medium leading-snug text-slate-500 tabular-nums">
                            {globalIdx}
                          </td>
                          {columns.map((key) => {
                            const text = formatArchivedCellValue(
                              key,
                              row[key],
                              row,
                              { responseCashbox: data?.cashbox }
                            );
                            return (
                              <td
                                key={key}
                                className="max-w-[min(28rem,40vw)] min-w-[100px] align-top text-xs leading-snug wrap-break-word whitespace-pre-wrap text-slate-700"
                              >
                                {text}
                              </td>
                            );
                          })}
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              )}
            </div>

            {!isPending && !isError && rows.length > 0 && (
              <div
                className="shrink-0 border-t border-slate-200 px-5 py-2.5 text-xs text-slate-500"
                style={{ background: "#F8FAFC" }}
              >
                <span className="font-medium text-slate-600">
                  جميع حقول كل سطر معروضة بالأعمدة أعلاه؛ مرّر أفقياً لرؤية الأعمدة
                  الزائدة.
                </span>
              </div>
            )}

            {data != null && totalPages > 1 && (
              <div
                className="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-3"
                style={{ background: "#F8FAFC", borderColor: "#E2E8F0" }}
              >
                <span className="text-sm text-slate-500">
                  صفحة{" "}
                  <span className="font-semibold text-slate-800">
                    {data.current_page}
                  </span>{" "}
                  من{" "}
                  <span className="font-semibold text-slate-800">
                    {totalPages}
                  </span>
                </span>
                <div className="flex items-center gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={!canPrev}
                    onClick={() => setPage((p) => p - 1)}
                    className="gap-1 border-slate-200"
                  >
                    <i className="ri-arrow-right-s-line text-lg" />
                    السابق
                  </Button>
                  <span
                    className="min-w-[88px] rounded-lg border px-3 py-1.5 text-center text-sm font-semibold text-sky-800"
                    style={{
                      background: "rgba(3, 105, 161, 0.08)",
                      borderColor: "rgba(3, 105, 161, 0.2)",
                    }}
                  >
                    {page} / {totalPages}
                  </span>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={!canNext}
                    onClick={() => setPage((p) => p + 1)}
                    className="gap-1 border-slate-200"
                  >
                    التالي
                    <i className="ri-arrow-left-s-line text-lg" />
                  </Button>
                </div>
              </div>
            )}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
