import { useMemo } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useSearchParams } from "react-router";
import { parseFilenameFromContentDisposition, downloadBlob } from "@/api/api.utils";
import {
  useExportTransactionsToCSVMutationOptions,
  useGetTransactionsQueryOptions,
} from "@/api/v2/transactions/transactions.hooks";
import { TTransaction, TTransactionsParams } from "@/api/v2/transactions/transactions.types";
import { toast } from "sonner";

const PER_PAGE_DEFAULT = 50;

export function getCategoryLabel(category: TTransaction["category"]) {
  switch (category) {
    case "payment":
      return "دفعة عميل";
    case "expense":
      return "مصروف";
    case "salary_expense":
      return "راتب / رواتب";
    case "receivable_payment":
      return "تحصيل مستحقات";
    case "reversal":
      return "عكس / استرداد";
    default:
      return category;
  }
}

export function useCashboxTransactionsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Number(searchParams.get("page")) || 1;
  const per_page = Number(searchParams.get("per_page")) || PER_PAGE_DEFAULT;
  const cashboxIdParam = searchParams.get("cashbox_id") || "";
  const startDate = searchParams.get("start_date") || "";
  const endDate = searchParams.get("end_date") || "";
  const sort = (searchParams.get("sort") as "asc" | "desc") || "desc";
  const typeFilter = searchParams.get("type") || "";
  const expenseCategory = searchParams.get("expense_category") || "";
  const paymentType = searchParams.get("payment_type") || "";

  const params: TTransactionsParams = useMemo(
    () => ({
      page,
      per_page,
      cashbox_id: cashboxIdParam ? Number(cashboxIdParam) : undefined,
      start_date: startDate || undefined,
      end_date: endDate || undefined,
      sort,
      type: typeFilter || undefined,
      expense_category: expenseCategory || undefined,
      payment_type: paymentType || undefined,
    }),
    [
      page,
      per_page,
      cashboxIdParam,
      startDate,
      endDate,
      sort,
      typeFilter,
      expenseCategory,
      paymentType,
    ],
  );

  const { data, isPending, isError, error } = useQuery(
    useGetTransactionsQueryOptions(params)
  );

  const { mutate: exportTransactions, isPending: isExporting } = useMutation(
    useExportTransactionsToCSVMutationOptions()
  );

  const handleExport = () => {
    exportTransactions(params, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) ||
          "cashbox-transactions.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير كشف المعاملات بنجاح");
      },
      onError: (err: unknown) => {
        const message = err instanceof Error ? err.message : "خطأ أثناء التصدير.";
        toast.error("خطأ أثناء تصدير كشف المعاملات.", { description: message });
      },
    });
  };

  const handleFiltersChange = (updates: Record<string, string>) => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      Object.entries(updates).forEach(([key, value]) => {
        if (value) {
          next.set(key, value);
        } else {
          next.delete(key);
        }
      });
      next.set("page", "1");
      if (!next.get("per_page")) next.set("per_page", String(per_page));
      return next;
    });
  };

  const handleResetFilters = () => {
    setSearchParams({
      page: "1",
      per_page: String(PER_PAGE_DEFAULT),
      sort: "desc",
    });
  };

  const num = (v: number | string | null | undefined) => {
    if (v === null || v === undefined) return 0;
    const n = typeof v === "number" ? v : Number(v);
    return Number.isNaN(n) ? 0 : n;
  };

  const items = data?.data ?? [];
  const total = data?.total ?? 0;
  const totalPages = data?.total_pages ?? 1;

  const totalIncome = items
    .filter((tx) => tx.type === "income")
    .reduce((s, tx) => s + num(tx.amount), 0);
  const totalExpense = items
    .filter((tx) => tx.type === "expense")
    .reduce((s, tx) => s + num(tx.amount), 0);
  const totalReversalAbs = items
    .filter((tx) => tx.type === "reversal")
    .reduce((s, tx) => s + Math.abs(num(tx.amount)), 0);

  // When sort=desc: first item = newest, last item = oldest. Opening = balance before oldest tx.
  // When sort=asc: first item = oldest, last item = newest. Opening = balance before first tx.
  const oldestIdx = sort === "desc" ? items.length - 1 : 0;
  const openingBalance =
    items.length > 0
      ? typeof items[oldestIdx].cashbox_balance_before === "number"
        ? items[oldestIdx].cashbox_balance_before
        : Number(items[oldestIdx].cashbox_balance_before) || 0
      : 0;

  const newestIdx = sort === "desc" ? 0 : items.length - 1;
  const lastBalanceRaw =
    items.length > 0
      ? (items[newestIdx].cashbox_balance_after != null
          ? items[newestIdx].cashbox_balance_after
          : items[newestIdx].balance_after)
      : openingBalance;
  const lastBalance =
    typeof lastBalanceRaw === "number"
      ? lastBalanceRaw
      : Number(lastBalanceRaw) || openingBalance;

  const stats = {
    openingBalance,
    totalIncome,
    totalExpense,
    totalReversalAbs,
    closingBalance: lastBalance,
    netPeriod: totalIncome - totalExpense,
  };

  return {
    params,
    cashboxIdParam,
    startDate,
    endDate,
    sort,
    typeFilter,
    expenseCategory,
    paymentType,
    page,
    per_page,
    data,
    items,
    isPending,
    isError,
    error,
    total,
    totalPages,
    stats,
    isExporting,
    handleExport,
    handleFiltersChange,
    handleResetFilters,
  };
}
