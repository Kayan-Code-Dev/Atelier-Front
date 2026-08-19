import { useState, useMemo, useEffect } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useSearchParams } from "react-router";
import * as z from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import {
  useGetExpensesQueryOptions,
  useExportExpensesToExcelMutationOptions,
} from "@/api/v2/expenses/expenses.hooks";
import { getExpenses } from "@/api/v2/expenses/expenses.service";
import {
  EXPENSE_CATEGORIES_WITH_SUBS,
  getExpenseCategoryDisplay,
  TExpense,
  TExpenseStatus,
  TGetExpensesParams,
} from "@/api/v2/expenses/expenses.types";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import useDebounce from "@/hooks/useDebounce";
import { toast } from "sonner";
import { formatDate } from "@/utils/formatDate";

export const STATUS_OPTIONS: { value: TExpenseStatus | "all"; label: string }[] = [
  { value: "all", label: "الكل" },
  { value: "pending", label: "معلق" },
  { value: "paid", label: "مدفوع" },
  { value: "cancelled", label: "ملغي" },
];

export const statusConfig: Record<
  TExpenseStatus,
  { color: string; bg: string; icon: string }
> = {
  pending: { color: "#92400E", bg: "#FEF3C7", icon: "ri-time-line" },
  paid: { color: "#065F46", bg: "#D1FAE5", icon: "ri-checkbox-circle-line" },
  cancelled: { color: "#991B1B", bg: "#FEE2E2", icon: "ri-close-circle-line" },
};

const CATEGORY_OPTIONS = [
  { value: "all", label: "الكل" },
  ...EXPENSE_CATEGORIES_WITH_SUBS.map((c) => ({ value: c.id, label: c.name })),
];

export const filterSchema = z.object({
  branch_id: z.string().optional(),
  cashbox_id: z.string().optional(),
  status: z
    .enum(["all", "pending", "paid", "cancelled"])
    .optional(),
  category: z.string().optional(),
  subcategory: z.string().optional(),
  start_date: z.string().optional(),
  end_date: z.string().optional(),
  search: z.string().optional(),
});

export type FilterFormValues = z.infer<typeof filterSchema>;

export function getStatusLabel(status: TExpenseStatus) {
  const map: Record<TExpenseStatus, string> = {
    pending: "معلق",
    paid: "مدفوع",
    cancelled: "ملغي",
  };
  return map[status] || status;
}

const per_page = 10;

export function useExpensesPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Number(searchParams.get("page")) || 1;

  const [selectedExpense, setSelectedExpense] = useState<TExpense | null>(null);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isUpdateModalOpen, setIsUpdateModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isDetailsModalOpen, setIsDetailsModalOpen] = useState(false);
  const [isCancelModalOpen, setIsCancelModalOpen] = useState(false);
  const [isPayModalOpen, setIsPayModalOpen] = useState(false);
  const [isExportingPDF, setIsExportingPDF] = useState(false);

  const form = useForm<FilterFormValues>({
    resolver: zodResolver(filterSchema),
    defaultValues: {
      branch_id: searchParams.get("branch_id") || undefined,
      cashbox_id: searchParams.get("cashbox_id") || undefined,
      status: (() => {
        const v = searchParams.get("status");
        if (
          v === "all" ||
          v === "pending" ||
          v === "paid" ||
          v === "cancelled"
        ) {
          return v;
        }
        return "all" satisfies FilterFormValues["status"];
      })(),
      category: searchParams.get("category") || undefined,
      subcategory: searchParams.get("subcategory") || undefined,
      start_date: searchParams.get("start_date") || undefined,
      end_date: searchParams.get("end_date") || undefined,
      search: searchParams.get("search") || undefined,
    },
  });

  const branchId = form.watch("branch_id");
  const cashboxId = form.watch("cashbox_id");
  const status = form.watch("status");
  const category = form.watch("category");
  const startDate = form.watch("start_date");
  const endDate = form.watch("end_date");
  const subcategory = form.watch("subcategory");
  const search = form.watch("search");

  const debouncedSearch = useDebounce({ value: search, delay: 500 });
  const debouncedSubcategory = useDebounce({ value: subcategory, delay: 500 });

  useEffect(() => {
    const params = new URLSearchParams();
    params.set("page", String(page));
    if (branchId) params.set("branch_id", String(branchId));
    if (cashboxId) params.set("cashbox_id", String(cashboxId));
    if (status && status !== "all") params.set("status", String(status));
    if (category && category !== "all") params.set("category", String(category));
    if (startDate) params.set("start_date", String(startDate));
    if (endDate) params.set("end_date", String(endDate));
    if (debouncedSubcategory)
      params.set("subcategory", String(debouncedSubcategory));
    if (debouncedSearch) params.set("search", String(debouncedSearch));
    setSearchParams(params, { replace: true });
  }, [
    page,
    branchId,
    cashboxId,
    status,
    category,
    startDate,
    endDate,
    debouncedSubcategory,
    debouncedSearch,
    setSearchParams,
  ]);

  const queryParams: TGetExpensesParams = useMemo(() => {
    return {
      page,
      per_page,
      branch_id: branchId ? Number(branchId) : undefined,
      cashbox_id: cashboxId ? Number(cashboxId) : undefined,
      status:
        status && status !== "all" ? (status as TExpenseStatus) : undefined,
      category: category && category !== "all" ? category : undefined,
      subcategory: debouncedSubcategory?.trim() || undefined,
      start_date: startDate || undefined,
      end_date: endDate || undefined,
      search: (debouncedSearch as string | undefined) || undefined,
    };
  }, [
    page,
    branchId,
    cashboxId,
    status,
    category,
    debouncedSubcategory,
    startDate,
    endDate,
    debouncedSearch,
  ]);

  const { data, isPending, isError, error } = useQuery(
    useGetExpensesQueryOptions(queryParams)
  );

  const { mutate: exportExpensesToExcel, isPending: isExporting } = useMutation(
    useExportExpensesToExcelMutationOptions()
  );

  const handleExport = () => {
    exportExpensesToExcel(queryParams, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "expenses.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير المصروفات بنجاح");
      },
      onError: (err: { message?: string }) => {
        toast.error("خطأ أثناء تصدير المصروفات", {
          description: err?.message,
        });
      },
    });
  };

  const handleExportPDF = async () => {
    setIsExportingPDF(true);
    try {
      const pdfParams: TGetExpensesParams = {
        ...queryParams,
        page: 1,
        per_page: 500,
      };
      const result = await getExpenses(pdfParams);
      const expenses = result?.data ?? [];
      const total = result?.total ?? 0;
      if (expenses.length === 0) {
        toast.info("لا توجد مصروفات لتصديرها");
        return;
      }
      const printDate = new Date().toLocaleDateString("ar-EG", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });
      const rows = expenses
        .map((e) => {
          const amount =
            typeof e.amount === "number" ? e.amount : Number(e.amount) || 0;
          const branchName = e.branch?.name ?? "—";
          const cashboxName = e.cashbox?.name ?? "—";
          const categoryDisplay = getExpenseCategoryDisplay(e.category, e.subcategory);
          return `<tr>
              <td>${e.id}</td>
              <td>${branchName}</td>
              <td>${cashboxName}</td>
              <td>${categoryDisplay}</td>
              <td>${e.vendor || "—"}</td>
              <td>${amount.toLocaleString("ar-EG")} ج.م</td>
              <td>${formatDate(e.expense_date)}</td>
              <td>${getStatusLabel(e.status)}</td>
              <td>${e.creator?.name ?? "—"}</td>
              <td>${e.notes || "—"}</td>
            </tr>`;
        })
        .join("");
      const totalSum = expenses.reduce(
        (s, e) =>
          s + (typeof e.amount === "number" ? e.amount : Number(e.amount) || 0),
        0
      );
      const html = `
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تقرير المصروفات</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 20px; font-size: 12px; }
    h1 { font-size: 18px; margin-bottom: 8px; }
    .meta { color: #64748b; margin-bottom: 16px; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: right; }
    th { background: #DC2626; color: white; font-weight: 700; }
    tr:nth-child(even) { background: #f8fafc; }
    .footer { margin-top: 16px; font-weight: 700; color: #DC2626; }
    @media print { body { padding: 10px; } }
  </style>
</head>
<body>
  <h1>تقرير المصروفات</h1>
  <div class="meta">تاريخ الطباعة: ${printDate} | يعرض ${expenses.length} من ${total} سجل</div>
  <table>
    <thead>
      <tr>
        <th>رقم</th>
        <th>الفرع</th>
        <th>الصندوق</th>
        <th>الفئة</th>
        <th>المورد</th>
        <th>المبلغ</th>
        <th>التاريخ</th>
        <th>الحالة</th>
        <th>بواسطة</th>
        <th>ملاحظات</th>
      </tr>
    </thead>
    <tbody>${rows}</tbody>
  </table>
  <div class="footer">الإجمالي: ${totalSum.toLocaleString("ar-EG")} ج.م</div>
</body>
</html>`;
      const win = window.open("", "_blank");
      if (!win) {
        toast.error("الرجاء السماح بالنوافذ المنبثقة لتصدير PDF");
        return;
      }
      win.document.write(html);
      win.document.close();
      win.focus();
      setTimeout(() => {
        win.print();
      }, 400);
      toast.success(
        "تم فتح نافذة الطباعة. اختر «حفظ كـ PDF» لحفظ الملف."
      );
    } catch {
      toast.error("حدث خطأ أثناء تصدير PDF");
    } finally {
      setIsExportingPDF(false);
    }
  };

  const handleResetFilters = () => {
    form.reset({
      branch_id: undefined,
      cashbox_id: undefined,
      status: "all",
      category: undefined,
      subcategory: undefined,
      start_date: undefined,
      end_date: undefined,
      search: undefined,
    });
    setSearchParams({ page: "1" });
  };

  const openDetails = (expense: TExpense) => {
    setSelectedExpense(expense);
    setIsDetailsModalOpen(true);
  };

  const openUpdate = (expense: TExpense) => {
    setSelectedExpense(expense);
    setIsUpdateModalOpen(true);
  };

  const openDelete = (expense: TExpense) => {
    setSelectedExpense(expense);
    setIsDeleteModalOpen(true);
  };

  const openCancel = (expense: TExpense) => {
    setSelectedExpense(expense);
    setIsCancelModalOpen(true);
  };

  const openPay = (expense: TExpense) => {
    setSelectedExpense(expense);
    setIsPayModalOpen(true);
  };

  const items = data?.data ?? [];
  const totalAmount = items.reduce(
    (s, e) => s + (typeof e.amount === "number" ? e.amount : Number(e.amount) || 0),
    0
  );
  const paidAmount = items
    .filter((e) => e.status === "paid")
    .reduce(
      (s, e) =>
        s + (typeof e.amount === "number" ? e.amount : Number(e.amount) || 0),
      0
    );
  const pendingAmount = items
    .filter((e) => e.status === "pending")
    .reduce(
      (s, e) =>
        s + (typeof e.amount === "number" ? e.amount : Number(e.amount) || 0),
      0
    );
  const paidCount = items.filter((e) => e.status === "paid").length;
  const pendingCount = items.filter((e) => e.status === "pending").length;
  const cancelledCount = items.filter((e) => e.status === "cancelled").length;

  const stats = [
    {
      label: "إجمالي المصروفات",
      value: data?.total ?? 0,
      sub: "معاملة",
      icon: "ri-shopping-bag-3-line",
      color: "#DC2626",
      bg: "#FEE2E2",
    },
    {
      label: "إجمالي المبلغ",
      value: totalAmount.toLocaleString("en-US"),
      sub: "جنيه مصري",
      icon: "ri-money-dollar-circle-line",
      color: "#7C3AED",
      bg: "#EDE9FE",
    },
    {
      label: "تم الصرف",
      value: paidAmount.toLocaleString("en-US"),
      sub: "جنيه مصري",
      icon: "ri-checkbox-circle-line",
      color: "#065F46",
      bg: "#D1FAE5",
    },
    {
      label: "في الانتظار",
      value: pendingAmount.toLocaleString("en-US"),
      sub: "جنيه مصري",
      icon: "ri-time-line",
      color: "#92400E",
      bg: "#FEF3C7",
    },
  ];

  return {
    form,
    queryParams,
    data,
    isPending,
    isError,
    error,
    items,
    stats,
    totalAmount,
    paidAmount,
    paidCount,
    pendingCount,
    cancelledCount,
    selectedExpense,
    setSelectedExpense,
    isCreateModalOpen,
    setIsCreateModalOpen,
    isUpdateModalOpen,
    setIsUpdateModalOpen,
    isDeleteModalOpen,
    setIsDeleteModalOpen,
    isDetailsModalOpen,
    setIsDetailsModalOpen,
    isCancelModalOpen,
    setIsCancelModalOpen,
    isPayModalOpen,
    setIsPayModalOpen,
    isExporting,
    handleExport,
    isExportingPDF,
    handleExportPDF,
    handleResetFilters,
    openDetails,
    openUpdate,
    openDelete,
    openCancel,
    openPay,
    CATEGORY_OPTIONS,
    EXPENSE_CATEGORIES_WITH_SUBS,
    getExpenseCategoryDisplay,
  };
}
