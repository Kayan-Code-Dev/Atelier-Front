import { useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import {
  useCloseCashboxPeriodMutationOptions,
  useGetCashboxClosuresQueryOptions,
} from "@/api/v2/cashboxes/cashboxes.hooks";
import type { TCashboxClosure } from "@/api/v2/cashboxes/cashboxes.types";
import { toast } from "sonner";
import { CashboxClosuresTable } from "./CashboxClosuresTable";

interface Props {
  cashboxId: number;
  cashboxName: string;
  openingBalance: number;
  totalIncome: number;
  totalExpense: number;
  closingBalance: number;
  transactionCount: number;
  dateFrom: string;
  dateTo: string;
  onClose: () => void;
  onPeriodClosed: () => void;
}

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

const num = (v: string | number) =>
  typeof v === "number" ? v : Number(v) || 0;

type Step = "confirm" | "closing" | "done";

export function PeriodCloseModal({
  cashboxId,
  cashboxName,
  openingBalance,
  totalIncome,
  totalExpense,
  closingBalance,
  transactionCount,
  dateFrom,
  dateTo,
  onClose,
  onPeriodClosed,
}: Props) {
  const [step, setStep] = useState<Step>("confirm");
  const [notes, setNotes] = useState("");
  const [showHistory, setShowHistory] = useState(false);
  const [closuresHistoryPage, setClosuresHistoryPage] = useState(1);
  const [closureResult, setClosureResult] = useState<TCashboxClosure | null>(
    null
  );
  const CLOSURES_PER_PAGE = 10;

  const netResult = totalIncome - totalExpense;
  const today = new Date().toISOString().slice(0, 10);
  const periodFrom = dateFrom || today;
  const periodTo = dateTo || today;

  const { mutate: closePeriod } = useMutation(
    useCloseCashboxPeriodMutationOptions()
  );

  const {
    data: closuresData,
    isPending: closuresHistoryPending,
    isError: closuresHistoryError,
  } = useQuery({
    ...useGetCashboxClosuresQueryOptions(cashboxId, {
      per_page: CLOSURES_PER_PAGE,
      page: closuresHistoryPage,
    }),
    enabled: showHistory,
  });
  const closureHistory = closuresData?.closures?.data ?? [];
  const closuresMeta = closuresData?.closures;
  const closuresHistoryTotalPages =
    closuresMeta?.total_pages ??
    Math.max(
      1,
      Math.ceil(
        (closuresMeta?.total ?? 0) /
          (closuresMeta?.per_page ?? CLOSURES_PER_PAGE)
      )
    );

  const handleConfirmClose = () => {
    setStep("closing");
    closePeriod(
      { cashboxId, data: notes ? { notes } : undefined },
      {
        onSuccess: (result) => {
          if (result?.closure) {
            setClosureResult(result.closure);
          }
          setStep("done");
          onPeriodClosed();
        },
        onError: (err: unknown) => {
          setStep("confirm");
          const msg =
            err instanceof Error ? err.message : "حدث خطأ أثناء الإقفال";
          toast.error("فشل إقفال الفترة", { description: msg });
        },
      }
    );
  };

  const doneClosing = closureResult;
  const doneClosingBalance = doneClosing
    ? num(doneClosing.closing_balance)
    : closingBalance;
  const formatIsoDate = (iso: string) => {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString("ar-EG", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  };
  const doneFrom = doneClosing?.from_date
    ? formatIsoDate(doneClosing.from_date)
    : periodFrom;
  const doneTo = doneClosing?.to_date
    ? formatIsoDate(doneClosing.to_date)
    : periodTo;
  const doneClosedBy = doneClosing?.closer?.name ?? "—";

  return (
    <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 flex items-center justify-center rounded-xl bg-orange-100 text-orange-600">
              <i className="ri-lock-line" />
            </div>
            <div>
              <h2 className="font-bold text-gray-800">
                إقفال الفترة المحاسبية
              </h2>
              <p className="text-xs text-gray-400">{cashboxName}</p>
            </div>
          </div>
          {step !== "closing" && (
            <button
              onClick={onClose}
              className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 cursor-pointer"
            >
              <i className="ri-close-line" />
            </button>
          )}
        </div>

        <div className="p-6">
          {/* Step: Confirm */}
          {step === "confirm" && (
            <div className="space-y-5">
              {/* Period Summary */}
              <div className="bg-orange-50 rounded-xl border border-orange-100 p-4">
                <div className="flex items-center gap-2 mb-3">
                  <div className="w-5 h-5 flex items-center justify-center text-orange-500">
                    <i className="ri-calendar-line text-sm" />
                  </div>
                  <h3 className="text-sm font-semibold text-orange-700">
                    ملخص الفترة المراد إقفالها
                  </h3>
                </div>
                <div className="grid grid-cols-2 gap-3 text-xs">
                  <div>
                    <p className="text-gray-500">من تاريخ</p>
                    <p className="font-semibold text-gray-800">{periodFrom}</p>
                  </div>
                  <div>
                    <p className="text-gray-500">إلى تاريخ</p>
                    <p className="font-semibold text-gray-800">{periodTo}</p>
                  </div>
                  <div>
                    <p className="text-gray-500">عدد الحركات</p>
                    <p className="font-semibold text-gray-800">
                      {transactionCount} حركة
                    </p>
                  </div>
                  <div>
                    <p className="text-gray-500">الصندوق</p>
                    <p className="font-semibold text-gray-800">{cashboxName}</p>
                  </div>
                </div>
              </div>

              {/* Balance Summary */}
              <div className="grid grid-cols-2 gap-3">
                <div className="bg-amber-50 rounded-xl border border-amber-100 p-4 text-center">
                  <p className="text-xs text-gray-500 mb-1">
                    الرصيد الافتتاحي
                  </p>
                  <p className="text-xl font-bold text-amber-700">
                    {fmt(openingBalance)}
                  </p>
                  <p className="text-xs text-gray-400">ج.م</p>
                </div>
                <div
                  className={`rounded-xl border p-4 text-center ${netResult >= 0 ? "bg-green-50 border-green-100" : "bg-red-50 border-red-100"}`}
                >
                  <p className="text-xs text-gray-500 mb-1">صافي الفترة</p>
                  <p
                    className={`text-xl font-bold ${netResult >= 0 ? "text-green-700" : "text-red-600"}`}
                  >
                    {netResult >= 0 ? "+" : ""}
                    {fmt(netResult)}
                  </p>
                  <p className="text-xs text-gray-400">ج.م</p>
                </div>
                <div className="bg-green-50 rounded-xl border border-green-100 p-3 text-center">
                  <p className="text-xs text-gray-500 mb-1">
                    إجمالي الإيرادات
                  </p>
                  <p className="text-lg font-bold text-green-700">
                    +{fmt(totalIncome)}
                  </p>
                  <p className="text-xs text-gray-400">ج.م</p>
                </div>
                <div className="bg-red-50 rounded-xl border border-red-100 p-3 text-center">
                  <p className="text-xs text-gray-500 mb-1">
                    إجمالي المصاريف
                  </p>
                  <p className="text-lg font-bold text-red-600">
                    -{fmt(totalExpense)}
                  </p>
                  <p className="text-xs text-gray-400">ج.م</p>
                </div>
              </div>

              {/* Closing Balance Highlight */}
              <div className="bg-gradient-to-l from-emerald-600 to-emerald-700 rounded-xl p-5 text-white text-center">
                <div className="w-10 h-10 flex items-center justify-center rounded-full bg-white/20 mx-auto mb-2">
                  <i className="ri-arrow-right-up-line text-lg" />
                </div>
                <p className="text-emerald-200 text-xs mb-1">رصيد الإقفال</p>
                <p className="text-3xl font-bold">{fmt(closingBalance)}</p>
                <p className="text-emerald-200 text-sm">ج.م</p>
                <p className="text-emerald-200 text-xs mt-2">
                  سيُرحَّل تلقائياً كـ رصيد افتتاحي للفترة الجديدة
                </p>
              </div>

              {/* Notes */}
              <div>
                <label className="text-xs text-gray-600 font-medium block mb-1.5">
                  ملاحظات (اختياري)
                </label>
                <input
                  type="text"
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-emerald-400 bg-gray-50"
                  placeholder="مثال: إقفال نهاية شهر مارس 2026"
                />
              </div>

              {/* Warning */}
              <div className="flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-lg p-3.5">
                <div className="w-5 h-5 flex items-center justify-center text-amber-500 mt-0.5">
                  <i className="ri-alert-line" />
                </div>
                <div>
                  <p className="text-xs font-semibold text-amber-700 mb-0.5">
                    تنبيه مهم
                  </p>
                  <p className="text-xs text-amber-600">
                    بعد إقفال الفترة، سيتم تجميد جميع حركاتها ولن يمكن
                    تعديلها. رصيد الإقفال{" "}
                    <strong>{fmt(closingBalance)} ج.م</strong> سيصبح الرصيد
                    الافتتاحي للفترة الجديدة.
                  </p>
                </div>
              </div>

              {/* Buttons */}
              <div className="flex gap-3">
                <button
                  onClick={onClose}
                  className="flex-1 py-2.5 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors cursor-pointer"
                >
                  إلغاء
                </button>
                <button
                  onClick={handleConfirmClose}
                  className="flex-1 py-2.5 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors cursor-pointer font-medium"
                >
                  <i className="ri-lock-line ml-1.5" />
                  تأكيد إقفال الفترة
                </button>
              </div>

              {/* History Toggle */}
              <button
                onClick={() => {
                  setShowHistory(!showHistory);
                  if (!showHistory) setClosuresHistoryPage(1);
                }}
                className="w-full flex items-center justify-center gap-1.5 text-xs text-gray-400 hover:text-gray-600 py-2 cursor-pointer"
              >
                <i className="ri-history-line" />
                {showHistory ? "إخفاء" : "عرض"} سجل الفترات المقفلة
              </button>

              {showHistory && (
                <div className="-mx-2">
                  <CashboxClosuresTable
                    title="الفترات المقفلة سابقاً"
                    closures={closureHistory}
                    isPending={closuresHistoryPending}
                    isError={closuresHistoryError}
                    page={closuresMeta?.current_page ?? closuresHistoryPage}
                    totalPages={closuresHistoryTotalPages}
                    total={closuresMeta?.total}
                    perPage={closuresMeta?.per_page ?? CLOSURES_PER_PAGE}
                    showPagination={closuresHistoryTotalPages > 1}
                    onPageChange={setClosuresHistoryPage}
                  />
                </div>
              )}
            </div>
          )}

          {/* Step: Closing */}
          {step === "closing" && (
            <div className="py-12 text-center space-y-4">
              <div className="w-16 h-16 flex items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mx-auto">
                <i className="ri-loader-4-line text-3xl animate-spin" />
              </div>
              <p className="text-gray-700 font-medium">
                جاري إقفال الفترة...
              </p>
              <p className="text-gray-400 text-sm">
                يتم تجميد الحركات وترحيل الرصيد
              </p>
            </div>
          )}

          {/* Step: Done */}
          {step === "done" && (
            <div className="py-10 text-center space-y-5">
              <div className="w-16 h-16 flex items-center justify-center rounded-full bg-green-100 text-green-600 mx-auto">
                <i className="ri-check-double-line text-3xl" />
              </div>
              <div>
                <h3 className="text-lg font-bold text-gray-800 mb-1">
                  تم إقفال الفترة بنجاح!
                </h3>
                <p className="text-sm text-gray-500">
                  رصيد الإقفال{" "}
                  <strong className="text-emerald-600">
                    {fmt(doneClosingBalance)} ج.م
                  </strong>{" "}
                  مرحَّل كرصيد افتتاحي للفترة الجديدة
                </p>
              </div>
              <div className="bg-emerald-50 rounded-xl border border-emerald-100 p-4 text-xs text-right space-y-1.5">
                <div className="flex justify-between">
                  <span className="text-gray-500">الفترة المقفلة</span>
                  <span className="font-medium text-gray-700">
                    {doneFrom} — {doneTo}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-500">رصيد الإقفال</span>
                  <span className="font-bold text-emerald-600">
                    {fmt(doneClosingBalance)} ج.م
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-500">
                    الرصيد الافتتاحي الجديد
                  </span>
                  <span className="font-bold text-amber-600">
                    {fmt(doneClosingBalance)} ج.م
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-500">أُقفل بواسطة</span>
                  <span className="font-medium text-gray-700">
                    {doneClosedBy}
                  </span>
                </div>
              </div>
              <button
                onClick={onClose}
                className="w-full py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors cursor-pointer"
              >
                إغلاق وبدء الفترة الجديدة
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
