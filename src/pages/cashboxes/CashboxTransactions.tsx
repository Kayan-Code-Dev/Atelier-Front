import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router";
import { useQuery } from "@tanstack/react-query";
import { useCashboxTransactionsPage } from "./transactions/hooks/useCashboxTransactionsPage";
import { CashboxSelector } from "./transactions/components/CashboxSelector";
import { TransactionStats } from "./transactions/components/TransactionStats";
import { TransactionFilters } from "./transactions/components/TransactionFilters";
import { TransactionLedger } from "./transactions/components/TransactionLedger";
import { PdfLedgerTable } from "./transactions/components/PdfLedgerTable";
import { PrintReportModal } from "./transactions/components/PrintReportModal";
import { PeriodCloseModal } from "./transactions/components/PeriodCloseModal";
import { DailyClosePanel } from "./transactions/components/DailyClosePanel";
import { PaymentDetailsModal } from "@/pages/payments/PaymentDetailsModal";
import { ExpenseDetailsModal } from "@/pages/expenses/ExpenseDetailsModal";
import { CashboxTransactionDetailsModal } from "./CashboxTransactionDetailsModal";
import { useGetPaymentByIdQueryOptions } from "@/api/v2/payments/payments.hooks";
import {
  useGetExpenseByIdQueryOptions,
  useGetExpenseCategoriesQueryOptions,
} from "@/api/v2/expenses/expenses.hooks";
import {
  useGetCashboxesQueryOptions,
  useGetCashboxQueryOptions,
  useGetCashboxDailySummaryQueryOptions,
} from "@/api/v2/cashboxes/cashboxes.hooks";
import { useGetTransactionsQueryOptions } from "@/api/v2/transactions/transactions.hooks";
import type { TTransaction } from "@/api/v2/transactions/transactions.types";
import { expenseCategoryOptionLabelAr } from "./transactions/expenseCategoryLabels";

type CashboxTransactionsProps = {
  pageTitle?: string;
  pageSubtitle?: string;
};

export default function CashboxTransactions({
  pageTitle = "كشف المعاملات",
  pageSubtitle = "عرض محاسبي شامل — مدفوعات ومصروفات ورواتب حسب الصندوق",
}: CashboxTransactionsProps) {
  const navigate = useNavigate();
  const [selectedPaymentId, setSelectedPaymentId] = useState<number | null>(null);
  const [selectedExpenseId, setSelectedExpenseId] = useState<number | null>(null);
  const [selectedTransaction, setSelectedTransaction] = useState<TTransaction | null>(null);
  const [isTransactionDetailsOpen, setIsTransactionDetailsOpen] = useState(false);
  const [showPrintModal, setShowPrintModal] = useState(false);
  const [pdfPrintRequested, setPdfPrintRequested] = useState(false);
  const [showPeriodCloseModal, setShowPeriodCloseModal] = useState(false);
  const [activeTab, setActiveTab] = useState<"ledger" | "daily">("ledger");

  const {
    cashboxIdParam,
    startDate,
    endDate,
    sort,
    typeFilter,
    expenseCategory,
    paymentType,
    items,
    isPending,
    isError,
    error,
    stats,
    total,
    totalPages,
    isExporting,
    handleExport,
    handleFiltersChange,
    handleResetFilters,
  } = useCashboxTransactionsPage();

  const { data: cashboxesData } = useQuery(useGetCashboxesQueryOptions({ per_page: 100 }));
  const { data: categoriesApi } = useQuery(useGetExpenseCategoriesQueryOptions());
  const cashboxes = cashboxesData?.data ?? [];
  const expenseCategoryOptions = useMemo(() => {
    const c = categoriesApi?.categories;
    if (!c || typeof c !== "object") return [];
    return Object.entries(c).map(([slug, label]) => ({
      slug,
      label: expenseCategoryOptionLabelAr(slug, String(label)),
    }));
  }, [categoriesApi]);
  const selectedCashboxName =
    !cashboxIdParam
      ? "جميع الصناديق"
      : cashboxes.find((c) => String(c.id) === cashboxIdParam)?.name ?? "الصندوق";

  const cashboxNumericId = cashboxIdParam ? Number(cashboxIdParam) : 0;
  const todayIso = new Date().toISOString().slice(0, 10);

  const { data: cashboxDetail } = useQuery({
    ...useGetCashboxQueryOptions(cashboxNumericId),
    enabled: cashboxNumericId > 0,
  });

  const { data: todayDailySummary } = useQuery({
    ...useGetCashboxDailySummaryQueryOptions(cashboxNumericId, todayIso),
    enabled: cashboxNumericId > 0,
  });

  const numCashboxField = (v: number | string | undefined | null) => {
    if (v === undefined || v === null) return 0;
    const n = typeof v === "number" ? v : Number(v);
    return Number.isNaN(n) ? 0 : n;
  };

  const hasLedgerDateRange = !!(startDate || endDate);

  const periodLabel =
    startDate && endDate
      ? `${startDate} — ${endDate}`
      : startDate
        ? `من ${startDate}`
        : endDate
          ? `إلى ${endDate}`
          : "الفترة المحددة";

  const displayStats = useMemo(() => {
    if (!cashboxIdParam || !cashboxDetail) {
      return stats;
    }

    const apiCurrent = numCashboxField(cashboxDetail.current_balance);
    /** الرصيد البدائي للفترة الحالية بعد إقفال الفترة السابقة (من السيرفر) */
    const apiInitial = numCashboxField(cashboxDetail.initial_balance);

    if (!hasLedgerDateRange && todayDailySummary) {
      return {
        openingBalance: apiInitial,
        totalIncome: todayDailySummary.total_income,
        totalExpense: todayDailySummary.total_expense,
        totalReversalAbs: stats.totalReversalAbs,
        closingBalance: apiCurrent,
        netPeriod: todayDailySummary.net_change,
      };
    }

    if (!hasLedgerDateRange) {
      return {
        ...stats,
        openingBalance: apiInitial,
        closingBalance: apiCurrent,
        netPeriod: stats.totalIncome - stats.totalExpense,
      };
    }

    return {
      ...stats,
      closingBalance: apiCurrent,
      netPeriod: stats.totalIncome - stats.totalExpense,
    };
  }, [
    cashboxIdParam,
    cashboxDetail,
    todayDailySummary,
    hasLedgerDateRange,
    stats,
  ]);

  const statsPeriodLabel = useMemo(() => {
    if (!cashboxIdParam) return periodLabel;
    if (!hasLedgerDateRange && todayDailySummary?.date) {
      return `اليوم — ${todayDailySummary.date}`;
    }
    return periodLabel;
  }, [
    cashboxIdParam,
    hasLedgerDateRange,
    todayDailySummary?.date,
    periodLabel,
  ]);

  const todayClosingForStats =
    cashboxIdParam && todayDailySummary != null
      ? todayDailySummary.closing_balance
      : null;

  const printParams = useMemo(
    () => ({
      page: 1,
      per_page: 5000,
      cashbox_id: cashboxIdParam ? Number(cashboxIdParam) : undefined,
      start_date: startDate || undefined,
      end_date: endDate || undefined,
      sort,
      type: typeFilter || undefined,
      expense_category: expenseCategory || undefined,
      payment_type: paymentType || undefined,
    }),
    [cashboxIdParam, startDate, endDate, sort, typeFilter, expenseCategory, paymentType],
  );
  const { data: printData, isFetching: isPrintFetching } = useQuery({
    ...useGetTransactionsQueryOptions(printParams),
    enabled: showPrintModal || pdfPrintRequested,
  });
  const printItems: TTransaction[] = printData?.data ?? [];
  const sortedPrint = useMemo(
    () =>
      [...printItems].sort(
        (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
      ),
    [printItems]
  );
  const printOpening: number =
    sortedPrint.length > 0
      ? Number(sortedPrint[0].cashbox_balance_before) || 0
      : 0;
  const printIncome = sortedPrint
    .filter((t) => t.type === "income")
    .reduce((s, t) => s + (typeof t.amount === "number" ? t.amount : Number(t.amount) || 0), 0);
  const printExpenses = sortedPrint
    .filter((t) => t.type === "expense")
    .reduce((s, t) => s + (typeof t.amount === "number" ? t.amount : Number(t.amount) || 0), 0);
  const printClosing: number =
    sortedPrint.length > 0
      ? Number(sortedPrint[sortedPrint.length - 1].balance_after) || 0
      : printOpening;

  const paymentQueryOptions =
    selectedPaymentId != null
      ? useGetPaymentByIdQueryOptions(selectedPaymentId)
      : { queryKey: ["payment-details-disabled"], queryFn: async () => undefined as never, enabled: false };

  const expenseQueryOptions =
    selectedExpenseId != null
      ? useGetExpenseByIdQueryOptions(selectedExpenseId)
      : { queryKey: ["expense-details-disabled"], queryFn: async () => undefined as never, enabled: false };

  const { data: paymentDetails } = useQuery(paymentQueryOptions);
  const { data: expenseDetails } = useQuery(expenseQueryOptions);

  const handleOpenPayment = (id: number) => {
    setSelectedExpenseId(null);
    setSelectedTransaction(null);
    setIsTransactionDetailsOpen(false);
    setSelectedPaymentId(id);
  };

  const handleOpenExpense = (id: number) => {
    setSelectedPaymentId(null);
    setSelectedTransaction(null);
    setIsTransactionDetailsOpen(false);
    setSelectedExpenseId(id);
  };

  const handleOpenTransaction = (tx: TTransaction) => {
    setSelectedPaymentId(null);
    setSelectedExpenseId(null);
    setSelectedTransaction(tx);
    setIsTransactionDetailsOpen(true);
  };

  const handlePrintTableOnly = () => setPdfPrintRequested(true);

  useEffect(() => {
    if (!pdfPrintRequested || isPrintFetching) return;
    const el = document.getElementById("pdf-print-ledger-full");
    if (!el) return;

    const clone = el.cloneNode(true) as HTMLElement;
    clone.id = "pdf-print-ledger-clone";
    clone.style.cssText = "";

    const portal = document.createElement("div");
    portal.id = "pdf-print-portal";
    portal.style.cssText = "position:fixed;left:0;top:0;width:100%;background:white;z-index:999999;padding:20px;";
    portal.appendChild(clone);
    document.body.appendChild(portal);

    const styleId = "pdf-print-ledger-style";
    const style = document.createElement("style");
    style.id = styleId;
    style.textContent = `
      @media print {
        body > *:not(#pdf-print-portal) { display: none !important; }
        #pdf-print-portal {
          display: block !important;
          position: fixed !important;
          left: 0 !important;
          top: 0 !important;
          right: 0 !important;
          width: 100% !important;
          overflow: visible !important;
          background: white !important;
          padding: 0 !important;
        }
        #pdf-print-ledger-clone {
          position: static !important;
          left: auto !important;
          width: 100% !important;
          overflow: visible !important;
          display: block !important;
          background: white !important;
        }
        #pdf-print-ledger-clone table { width: 100% !important; }
        #pdf-print-ledger-clone thead { display: table-header-group !important; }
        #pdf-print-ledger-clone * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { margin: 10mm; size: A4 landscape; }
      }
    `;
    document.head.appendChild(style);

    const cleanup = () => {
      portal.remove();
      document.getElementById(styleId)?.remove();
      window.removeEventListener("afterprint", cleanup);
      setPdfPrintRequested(false);
    };
    window.addEventListener("afterprint", cleanup);

    window.print();
  }, [pdfPrintRequested, isPrintFetching]);

  return (
    <div dir="rtl" className="space-y-5">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-800">{pageTitle}</h1>
          <p className="text-sm text-slate-500 mt-0.5">{pageSubtitle}</p>
        </div>
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={handleExport}
            disabled={isExporting}
            className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold disabled:opacity-60"
            style={{
              background: "#F0FDF4",
              color: "#065F46",
              border: "1px solid #D1FAE5",
            }}
          >
            <i className="ri-file-excel-2-line" />
            {isExporting ? "جاري التصدير..." : "تصدير Excel"}
          </button>
          <button
            type="button"
            onClick={handlePrintTableOnly}
            disabled={pdfPrintRequested && isPrintFetching}
            className="flex items-center gap-2 px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors disabled:opacity-60"
          >
            <i className="ri-file-pdf-line" />
            {pdfPrintRequested && isPrintFetching ? "جاري التحضير..." : "PDF"}
          </button>
          <button
            type="button"
            onClick={() => setShowPrintModal(true)}
            className="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
          >
            <i className="ri-printer-line" />
            طباعة التقرير
          </button>
          {cashboxIdParam && (
            <button
              type="button"
              onClick={() => setShowPeriodCloseModal(true)}
              className="flex items-center gap-2 px-4 py-2 text-sm bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors font-medium"
            >
              <i className="ri-lock-line" />
              إقفال الفترة
            </button>
          )}
        </div>
      </div>

      <CashboxSelector
        selectedCashboxId={cashboxIdParam}
        onCashboxChange={(id) => handleFiltersChange({ cashbox_id: id })}
      />

      <TransactionStats
        stats={displayStats}
        todayClosingBalance={todayClosingForStats}
        openingBalanceSublabel={
          cashboxIdParam && !hasLedgerDateRange
            ? "الرصيد البدائي المرحّل (بعد الإقفال)"
            : undefined
        }
        periodLabel={statsPeriodLabel}
        selectedCashboxName={selectedCashboxName}
      />

      {/* Tabs */}
      {cashboxIdParam && (
        <div className="flex items-center gap-1 bg-gray-100 rounded-xl p-1 w-fit">
          <button
            onClick={() => setActiveTab("ledger")}
            className={`px-5 py-2 text-sm rounded-lg transition-all cursor-pointer whitespace-nowrap ${
              activeTab === "ledger"
                ? "bg-white text-gray-800 font-semibold shadow-sm"
                : "text-gray-500 hover:text-gray-700"
            }`}
          >
            <i className="ri-book-open-line ml-1.5" />
            دفتر الأستاذ
          </button>
          <button
            onClick={() => setActiveTab("daily")}
            className={`px-5 py-2 text-sm rounded-lg transition-all cursor-pointer whitespace-nowrap ${
              activeTab === "daily"
                ? "bg-white text-gray-800 font-semibold shadow-sm"
                : "text-gray-500 hover:text-gray-700"
            }`}
          >
            <i className="ri-moon-line ml-1.5" />
            إقفال اليوم
          </button>
        </div>
      )}

      {/* Daily Close Tab */}
      {activeTab === "daily" && cashboxIdParam && (
        <DailyClosePanel
          cashboxId={Number(cashboxIdParam)}
          cashboxName={selectedCashboxName}
        />
      )}

      {activeTab === "ledger" && (
        <>
          <TransactionFilters
            startDate={startDate}
            endDate={endDate}
            sort={sort}
            typeFilter={typeFilter}
            expenseCategory={expenseCategory}
            paymentType={paymentType}
            expenseCategoryOptions={expenseCategoryOptions}
            onFiltersChange={handleFiltersChange}
            onReset={handleResetFilters}
          />

          {(cashboxIdParam ||
            startDate ||
            endDate ||
            typeFilter ||
            expenseCategory ||
            paymentType) && (
            <div className="flex items-center gap-2 text-xs text-emerald-700 bg-emerald-50 px-4 py-2 rounded-lg border border-emerald-100">
              <i className="ri-filter-3-line" />
              <span>
                يعرض <strong>{items.length}</strong> حركة بناءً على الفلاتر
                المحددة
              </span>
            </div>
          )}

          <TransactionLedger
            items={items}
            isPending={isPending}
            isError={isError}
            error={error}
            openingBalance={stats.openingBalance}
            totalIncome={stats.totalIncome}
            totalExpense={stats.totalExpense}
            totalReversalAbs={stats.totalReversalAbs}
            closingBalance={stats.closingBalance}
            total={total}
            totalPages={totalPages}
            onResetFilters={handleResetFilters}
            onViewPayment={handleOpenPayment}
            onViewExpense={handleOpenExpense}
            onViewTailoringOrder={(orderId) =>
              navigate(`/tailoring/orders/${orderId}`)
            }
            onViewTransaction={handleOpenTransaction}
          />
        </>
      )}

      {pdfPrintRequested && (
        <div
          id="pdf-print-ledger-full"
          style={{
            position: "absolute",
            left: -9999,
            top: 0,
            width: 1100,
            overflow: "visible",
          }}
          aria-hidden="true"
        >
          <PdfLedgerTable
            items={printItems}
            openingBalance={printOpening}
            periodIncome={printIncome}
            periodExpenses={printExpenses}
            currentBalance={printClosing}
          />
        </div>
      )}

      {selectedPaymentId != null && paymentDetails && (
        <PaymentDetailsModal
          payment={paymentDetails}
          open={!!selectedPaymentId}
          onOpenChange={(open) => {
            if (!open) setSelectedPaymentId(null);
          }}
        />
      )}

      {selectedExpenseId != null && (
        <ExpenseDetailsModal
          open={!!selectedExpenseId}
          onOpenChange={(open) => {
            if (!open) setSelectedExpenseId(null);
          }}
          expense={expenseDetails ?? null}
        />
      )}

      <CashboxTransactionDetailsModal
        open={isTransactionDetailsOpen}
        onOpenChange={(open) => {
          setIsTransactionDetailsOpen(open);
          if (!open) setSelectedTransaction(null);
        }}
        transaction={selectedTransaction}
      />

      {showPrintModal && (
        <PrintReportModal
          entries={printItems}
          selectedCashboxName={selectedCashboxName}
          dateFrom={startDate}
          dateTo={endDate}
          openingBalance={printOpening}
          periodIncome={printIncome}
          periodExpenses={printExpenses}
          currentBalance={printClosing}
          onClose={() => setShowPrintModal(false)}
        />
      )}

      {showPeriodCloseModal && cashboxIdParam && (
        <PeriodCloseModal
          cashboxId={Number(cashboxIdParam)}
          cashboxName={selectedCashboxName}
          openingBalance={stats.openingBalance}
          totalIncome={stats.totalIncome}
          totalExpense={stats.totalExpense}
          closingBalance={stats.closingBalance}
          transactionCount={total}
          dateFrom={startDate}
          dateTo={endDate}
          onClose={() => setShowPeriodCloseModal(false)}
          onPeriodClosed={() => {
            /* queries already invalidated by the mutation hook */
          }}
        />
      )}
    </div>
  );
}
