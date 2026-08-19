import { Fragment, useState } from "react";
import type {
  TCashboxClosure,
  TCashboxClosureDetails,
} from "@/api/v2/cashboxes/cashboxes.types";
import { closureDetailsCategoryLabel } from "../closureArchivedData.helpers";
import { ClosureArchivedDataModal } from "./ClosureArchivedDataModal";

const formatMoney = (value: string | number | null | undefined): string => {
  if (value === null || value === undefined) return "—";
  const n = typeof value === "number" ? value : Number(value);
  if (Number.isNaN(n)) return "—";
  return n.toLocaleString("ar-EG", { minimumFractionDigits: 2 });
};

const formatDateShort = (iso: string) => {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleDateString("ar-EG", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const formatDateTimeShort = (iso: string) => {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleString("ar-EG", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

function DetailsBreakdown({
  details,
}: {
  details: TCashboxClosureDetails | null | undefined;
}) {
  if (!details || typeof details !== "object") return null;
  const income = details.income_by_category
    ? Object.entries(details.income_by_category).map(([k, v]) => (
        <span key={k} className="text-green-700">
          {closureDetailsCategoryLabel(k)}: {formatMoney(v.total)} ({v.count})
        </span>
      ))
    : null;
  const expense = details.expense_by_category
    ? Object.entries(details.expense_by_category).map(([k, v]) => (
        <span key={k} className="text-red-600">
          {closureDetailsCategoryLabel(k)}: {formatMoney(v.total)} ({v.count})
        </span>
      ))
    : null;

  const lines: { label: string; value: string }[] = [];
  if (details.order_payments)
    lines.push({
      label: "دفعات الطلبات",
      value: `${formatMoney(details.order_payments.total)} (${details.order_payments.count})`,
    });
  if (details.tailoring_payments)
    lines.push({
      label: "دفعات الخياطة",
      value: `${formatMoney(details.tailoring_payments.total)} (${details.tailoring_payments.count})`,
    });
  if (details.manual_payments)
    lines.push({
      label: "دفعات يدوية",
      value: `${formatMoney(details.manual_payments.total)} (${details.manual_payments.count})`,
    });
  if (details.business_expenses)
    lines.push({
      label: "مصروفات تشغيل",
      value: `${formatMoney(details.business_expenses.total)} (${details.business_expenses.count})`,
    });
  if (details.salary_payments)
    lines.push({
      label: "رواتب",
      value: `${formatMoney(details.salary_payments.total)} (${details.salary_payments.count})`,
    });
  if (details.reversals && details.reversals.count > 0)
    lines.push({
      label: "عكوس",
      value: `${formatMoney(details.reversals.total)} (${details.reversals.count})`,
    });

  return (
    <div className="text-xs text-slate-600 space-y-2 py-2 px-1">
      {income && income.length > 0 && (
        <div className="flex flex-wrap gap-x-3 gap-y-1">
          <span className="font-semibold text-slate-500">إيراد حسب الفئة:</span>
          {income}
        </div>
      )}
      {expense && expense.length > 0 && (
        <div className="flex flex-wrap gap-x-3 gap-y-1">
          <span className="font-semibold text-slate-500">مصروف حسب الفئة:</span>
          {expense}
        </div>
      )}
      {lines.length > 0 && (
        <ul className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 list-none p-0 m-0">
          {lines.map((l) => (
            <li key={l.label}>
              <span className="text-slate-500">{l.label}: </span>
              <span className="font-medium text-slate-800">{l.value}</span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

type Props = {
  closures: TCashboxClosure[];
  isPending?: boolean;
  isError?: boolean;
  title?: string;
  page?: number;
  totalPages?: number;
  total?: number;
  perPage?: number;
  onPageChange?: (page: number) => void;
  showPagination?: boolean;
};

export function CashboxClosuresTable({
  closures,
  isPending,
  isError,
  title = "جدول الإقفالات",
  page = 1,
  totalPages = 1,
  total,
  perPage = 10,
  onPageChange,
  showPagination = false,
}: Props) {
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [archiveClosureId, setArchiveClosureId] = useState<number | null>(null);

  const canPrev = showPagination && page > 1;
  const canNext = showPagination && page < totalPages;

  return (
    <div className="sys-card overflow-hidden">
      <div className="px-5 py-3 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
        <h3 className="font-semibold text-slate-700 text-sm flex items-center gap-2">
          <i className="ri-table-line text-violet-500" />
          {title}
        </h3>
        {total != null && (
          <span className="text-xs text-slate-400">
            إجمالي السجلات:{" "}
            <span className="font-semibold text-slate-600">{total}</span>
          </span>
        )}
      </div>

      <div className="overflow-x-auto">
        <table className="sys-table min-w-[1180px]">
          <thead>
            <tr>
              <th className="w-10">#</th>
              <th>من</th>
              <th>إلى</th>
              <th>افتتاحي</th>
              <th>إيرادات</th>
              <th>مصاريف</th>
              <th>صافي</th>
              <th>رصيد الإقفال</th>
              <th>رصيد جديد</th>
              <th>حركات</th>
              <th>عكس</th>
              <th>المُقفِل</th>
              <th>تاريخ الإقفال</th>
              <th>ملاحظات</th>
              <th className="w-24">تفاصيل</th>
              <th className="w-28">أرشيف</th>
            </tr>
          </thead>
          <tbody>
            {isPending && (
              <tr>
                <td colSpan={16} className="text-center py-10 text-slate-400">
                  جاري التحميل...
                </td>
              </tr>
            )}
            {isError && !isPending && (
              <tr>
                <td colSpan={16} className="text-center py-10 text-red-500">
                  تعذر تحميل الإقفالات
                </td>
              </tr>
            )}
            {!isPending && !isError && closures.length === 0 && (
              <tr>
                <td colSpan={16} className="text-center py-10 text-slate-400">
                  لا توجد إقفالات مسجلة
                </td>
              </tr>
            )}
            {!isPending &&
              !isError &&
              closures.map((row, idx) => {
                const isOpen = expandedId === row.id;
                const rowNum = (page - 1) * perPage + idx + 1;
                return (
                  <Fragment key={row.id}>
                    <tr className="align-middle">
                      <td className="text-slate-500">{rowNum}</td>
                      <td className="whitespace-nowrap">
                        {formatDateShort(row.from_date)}
                      </td>
                      <td className="whitespace-nowrap">
                        {formatDateShort(row.to_date)}
                      </td>
                      <td className="tabular-nums">
                        {formatMoney(row.opening_balance)}
                      </td>
                      <td className="tabular-nums text-green-700">
                        +{formatMoney(row.total_income)}
                      </td>
                      <td className="tabular-nums text-red-600">
                        −{formatMoney(row.total_expense)}
                      </td>
                      <td className="tabular-nums font-medium">
                        {formatMoney(row.net_change)}
                      </td>
                      <td className="tabular-nums font-semibold text-emerald-700">
                        {formatMoney(row.closing_balance)}
                      </td>
                      <td className="tabular-nums text-amber-700">
                        {formatMoney(row.new_initial_balance)}
                      </td>
                      <td>{row.transaction_count}</td>
                      <td>{row.reversal_count}</td>
                      <td className="whitespace-nowrap max-w-[140px] truncate">
                        {row.closer?.name ?? `#${row.closed_by}`}
                      </td>
                      <td className="whitespace-nowrap text-xs">
                        {formatDateTimeShort(row.closed_at)}
                      </td>
                      <td className="max-w-[120px] truncate text-xs text-slate-500">
                        {row.notes ?? "—"}
                      </td>
                      <td>
                        <button
                          type="button"
                          onClick={() =>
                            setExpandedId(isOpen ? null : row.id)
                          }
                          className="text-xs text-violet-600 hover:text-violet-800 font-medium"
                        >
                          {isOpen ? "إخفاء" : "عرض"}
                        </button>
                      </td>
                      <td>
                        <button
                          type="button"
                          onClick={() => setArchiveClosureId(row.id)}
                          className="text-xs text-slate-600 hover:text-blue-700 font-medium whitespace-nowrap"
                        >
                          <i className="ri-archive-line ml-1" />
                          البيانات
                        </button>
                      </td>
                    </tr>
                    {isOpen && (
                      <tr className="bg-slate-50/80">
                        <td colSpan={16} className="!p-4 border-b border-slate-100">
                          <p className="text-xs font-semibold text-slate-600 mb-1">
                            تفصيل الإقفال #{row.id}
                          </p>
                          <DetailsBreakdown details={row.details} />
                        </td>
                      </tr>
                    )}
                  </Fragment>
                );
              })}
          </tbody>
        </table>
      </div>

      {showPagination && onPageChange && totalPages > 1 && (
        <div className="px-5 py-3 border-t border-slate-200/80 flex items-center justify-between gap-3 flex-wrap">
          <span className="text-xs text-slate-500">
            صفحة {page} من {totalPages}
          </span>
          <div className="flex items-center gap-2">
            <button
              type="button"
              disabled={!canPrev}
              onClick={() => onPageChange(page - 1)}
              className="px-3 py-1.5 text-sm rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50"
            >
              السابق
            </button>
            <button
              type="button"
              disabled={!canNext}
              onClick={() => onPageChange(page + 1)}
              className="px-3 py-1.5 text-sm rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50"
            >
              التالي
            </button>
          </div>
        </div>
      )}

      <ClosureArchivedDataModal
        open={archiveClosureId != null}
        onOpenChange={(next) => {
          if (!next) setArchiveClosureId(null);
        }}
        closureId={archiveClosureId ?? 0}
      />
    </div>
  );
}
