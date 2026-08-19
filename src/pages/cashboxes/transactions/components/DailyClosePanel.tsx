import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import {
  useGetCashboxDailySummaryQueryOptions,
  useGetCashboxClosuresQueryOptions,
} from "@/api/v2/cashboxes/cashboxes.hooks";
import { CashboxClosuresTable } from "./CashboxClosuresTable";

interface Props {
  cashboxId: number;
  cashboxName: string;
}

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

const CLOSURES_PER_PAGE = 10;

export function DailyClosePanel({ cashboxId, cashboxName }: Props) {
  const [closuresPage, setClosuresPage] = useState(1);

  const today = new Date().toISOString().slice(0, 10);
  const todayLabel = new Date().toLocaleDateString("ar-EG", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  const { data: dailySummary, isPending } = useQuery({
    ...useGetCashboxDailySummaryQueryOptions(cashboxId, today),
    enabled: !!cashboxId,
  });

  const {
    data: closuresData,
    isPending: closuresPending,
    isError: closuresError,
  } = useQuery({
    ...useGetCashboxClosuresQueryOptions(cashboxId, {
      per_page: CLOSURES_PER_PAGE,
      page: closuresPage,
    }),
    enabled: !!cashboxId,
  });

  const closureRows = closuresData?.closures?.data ?? [];
  const closuresMeta = closuresData?.closures;
  const closuresTotalPages =
    closuresMeta?.total_pages ??
    Math.max(
      1,
      Math.ceil((closuresMeta?.total ?? 0) / (closuresMeta?.per_page ?? CLOSURES_PER_PAGE))
    );

  const dayOpening = dailySummary?.opening_balance ?? 0;
  const dayIncome = dailySummary?.total_income ?? 0;
  const dayExpense = dailySummary?.total_expense ?? 0;
  const dayClosing = dailySummary?.closing_balance ?? 0;

  const tomorrowLabel = new Date(Date.now() + 86400000).toLocaleDateString(
    "ar-EG",
    { weekday: "long", month: "short", day: "numeric" }
  );

  return (
    <div className="space-y-5">
      <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
        {/* Header */}
        <div className="px-5 py-3 border-b border-gray-50 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-violet-100 text-violet-600">
              <i className="ri-moon-line text-sm" />
            </div>
            <div>
              <h3 className="text-sm font-semibold text-gray-700">
                إقفال اليوم — {cashboxName}
              </h3>
              <p className="text-xs text-gray-400">{todayLabel}</p>
            </div>
          </div>
        </div>

        <div className="p-5">
          {isPending ? (
            <div className="grid grid-cols-4 gap-3 mb-4">
              {Array.from({ length: 4 }).map((_, i) => (
                <div
                  key={i}
                  className="rounded-lg bg-gray-50 border border-gray-100 p-3 text-center animate-pulse h-20"
                />
              ))}
            </div>
          ) : (
            <>
              <div className="grid grid-cols-4 gap-3 mb-4">
                <div className="rounded-lg bg-amber-50 border border-amber-100 p-3 text-center">
                  <p className="text-xs text-gray-500 mb-1">
                    رصيد افتتاح اليوم
                  </p>
                  <p className="text-sm font-bold text-amber-700">
                    {fmt(dayOpening)}
                  </p>
                  <p className="text-xs text-gray-400">ج.م</p>
                </div>
                <div className="rounded-lg bg-green-50 border border-green-100 p-3 text-center">
                  <p className="text-xs text-gray-500 mb-1">إيرادات اليوم</p>
                  <p className="text-sm font-bold text-green-700">
                    +{fmt(dayIncome)}
                  </p>
                  <p className="text-xs text-gray-400">ج.م</p>
                </div>
                <div className="rounded-lg bg-red-50 border border-red-100 p-3 text-center">
                  <p className="text-xs text-gray-500 mb-1">مصاريف اليوم</p>
                  <p className="text-sm font-bold text-red-600">
                    -{fmt(dayExpense)}
                  </p>
                  <p className="text-xs text-gray-400">ج.م</p>
                </div>
                <div className="rounded-lg bg-violet-50 border border-violet-200 p-3 text-center">
                  <p className="text-xs text-gray-500 mb-1">
                    رصيد إقفال اليوم
                  </p>
                  <p className="text-lg font-bold text-violet-700">
                    {fmt(dayClosing)}
                  </p>
                  <p className="text-xs text-gray-400">ج.م</p>
                </div>
              </div>

              <div className="flex items-center gap-3 bg-violet-50 rounded-lg p-3 mb-4 border border-violet-100">
                <div className="w-8 h-8 flex items-center justify-center rounded-full bg-violet-100 text-violet-600">
                  <i className="ri-arrow-right-up-line" />
                </div>
                <div className="flex-1">
                  <p className="text-xs font-semibold text-violet-700 mb-0.5">
                    الترحيل التلقائي
                  </p>
                  <p className="text-xs text-violet-600">
                    سيُستخدم <strong>{fmt(dayClosing)} ج.م</strong> كـ رصيد
                    افتتاحي ليوم <strong>{tomorrowLabel}</strong>
                  </p>
                </div>
                <div className="text-violet-400">
                  <i className="ri-check-double-line text-sm" />
                </div>
              </div>

              {dailySummary && (
                <div className="flex items-center gap-2 bg-gray-50 rounded-lg px-4 py-2.5 border border-gray-100">
                  <i className="ri-file-list-3-line text-gray-400 text-sm" />
                  <p className="text-xs text-gray-600">
                    عدد الحركات اليوم:{" "}
                    <strong>{dailySummary.transaction_count}</strong>
                    {dailySummary.reversal_count > 0 && (
                      <span className="text-amber-600 mr-2">
                        ({dailySummary.reversal_count} عكس)
                      </span>
                    )}
                  </p>
                </div>
              )}
            </>
          )}
        </div>
      </div>

      <CashboxClosuresTable
        title={`سجل إقفالات الصندوق — ${cashboxName}`}
        closures={closureRows}
        isPending={closuresPending}
        isError={closuresError}
        page={closuresMeta?.current_page ?? closuresPage}
        totalPages={closuresTotalPages}
        total={closuresMeta?.total}
        perPage={closuresMeta?.per_page ?? CLOSURES_PER_PAGE}
        showPagination={closuresTotalPages > 1}
        onPageChange={(p) => setClosuresPage(p)}
      />
    </div>
  );
}
