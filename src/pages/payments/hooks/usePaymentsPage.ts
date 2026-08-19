import { useState, useMemo, useEffect, useRef } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { toast } from "sonner";
import { useSearchParams } from "react-router";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import {
  useGetAllManualCashboxPaymentsQueryOptions,
  useExportPaymentsToCSVMutationOptions,
  useGetManualCashboxPaymentsQueryOptions,
  useGetPaymentsQueryOptions,
  useGetTailoringPaymentsQueryOptions,
  useMarkPaymentAsPaidMutationOptions,
  useMarkPaymentAsCanceledMutationOptions,
} from "@/api/v2/payments/payments.hooks";
import { getPayments } from "@/api/v2/payments/payments.service";
import {
  TPayment,
  TPaymentsScope,
  TPaymentStatus,
  TPaymentType,
  TGetPaymentsParams,
} from "@/api/v2/payments/payments.types";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import { useGetBranchesQueryOptions } from "@/api/v2/branches/branches.hooks";
import { useGetCashboxesQueryOptions } from "@/api/v2/cashboxes/cashboxes.hooks";
import { formatDate } from "@/utils/formatDate";
import useDebounce from "@/hooks/useDebounce";

export const PAYMENT_STATUSES: { value: TPaymentStatus | "all"; label: string }[] = [
  { value: "all", label: "الكل" },
  { value: "pending", label: "معلق" },
  { value: "paid", label: "مدفوع" },
  { value: "canceled", label: "ملغي" },
];

export const PAYMENT_TYPES: { value: TPaymentType | "all"; label: string }[] = [
  { value: "all", label: "الكل" },
  { value: "initial", label: "مبدئي" },
  { value: "fee", label: "رسوم" },
  { value: "normal", label: "عادي" },
];

export const statusConfig: Record<
  TPaymentStatus,
  { color: string; bg: string; icon: string }
> = {
  pending: { color: "#92400E", bg: "#FEF3C7", icon: "ri-time-line" },
  paid: { color: "#065F46", bg: "#D1FAE5", icon: "ri-checkbox-circle-line" },
  canceled: { color: "#991B1B", bg: "#FEE2E2", icon: "ri-close-circle-line" },
};

export const paymentTypeIcons: Record<TPaymentType, string> = {
  initial: "ri-arrow-right-circle-line",
  fee: "ri-money-dollar-circle-line",
  normal: "ri-bank-card-line",
};

export const filterSchema = z.object({
  scope: z.enum(["payments", "tailoring", "manual"]).optional(),
  status: z.enum(["all", "pending", "paid", "canceled"]).optional(),
  payment_type: z.enum(["all", "initial", "fee", "normal"]).optional(),
  client_id: z.string().optional(),
  order_id: z.string().optional(),
  employee_id: z.string().optional(),
  inventory_id: z.string().optional(),
  branch_id: z.string().optional(),
  cashbox_id: z.string().optional(),
  date_from: z.string().optional(),
  date_to: z.string().optional(),
  amount_min: z.string().optional(),
  amount_max: z.string().optional(),
  notes: z.string().optional(),
  search: z.string().optional(),
});

export type FilterFormValues = z.infer<typeof filterSchema>;

export type TPaymentListRow = {
  id: number;
  order_id?: number | null;
  amount: number | string;
  status: TPaymentStatus;
  payment_type: TPaymentType;
  payment_date: string;
  created_at: string;
  notes: string;
  description?: string;
  payment_method?: string | null;
  received_from?: string | null;
  transaction_id?: number | null;
  order?: TPayment["order"];
  cashbox?: TPayment["cashbox"];
  source: TPaymentsScope;
};

const DEFAULT_PER_PAGE = 10;

export function getClientName(client: {
  name?: string;
  first_name?: string;
  middle_name?: string;
  last_name?: string;
} | null | undefined) {
  if (!client) return "—";
  if (typeof client.name === "string" && client.name.trim()) return client.name.trim();
  const parts = [client.first_name, client.middle_name, client.last_name].filter(
    Boolean
  ) as string[];
  return parts.length ? parts.join(" ").trim() : "—";
}

export function getStatusLabel(status: TPaymentStatus) {
  const statusMap: Record<TPaymentStatus, string> = {
    pending: "معلق",
    paid: "مدفوع",
    canceled: "ملغي",
  };
  return statusMap[status];
}

export function getPaymentTypeLabel(type: TPaymentType) {
  const typeMap: Record<TPaymentType, string> = {
    initial: "مبدئي",
    fee: "رسوم",
    normal: "عادي",
  };
  return typeMap[type];
}

export function usePaymentsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Number(searchParams.get("page")) || 1;
  const per_page = Number(searchParams.get("per_page")) || DEFAULT_PER_PAGE;
  const [showNotes, setShowNotes] = useState<string | null>(null);
  const [selectedPayment, setSelectedPayment] = useState<TPayment | null>(null);
  const [isDetailsModalOpen, setIsDetailsModalOpen] = useState(false);
  const [isExportingPDF, setIsExportingPDF] = useState(false);

  const form = useForm<FilterFormValues>({
    resolver: zodResolver(filterSchema),
    defaultValues: {
      status: (searchParams.get("status") as TPaymentStatus | "all") || "all",
      scope: (searchParams.get("scope") as TPaymentsScope) || "payments",
      payment_type:
        (searchParams.get("payment_type") as TPaymentType | "all") || "all",
      client_id: searchParams.get("client_id") || undefined,
      order_id: searchParams.get("order_id") || undefined,
      employee_id: searchParams.get("employee_id") || undefined,
      inventory_id: searchParams.get("inventory_id") || undefined,
      branch_id: searchParams.get("branch_id") || undefined,
      cashbox_id: searchParams.get("cashbox_id") || undefined,
      date_from: searchParams.get("date_from") || undefined,
      date_to: searchParams.get("date_to") || undefined,
      amount_min: searchParams.get("amount_min") || undefined,
      amount_max: searchParams.get("amount_max") || undefined,
      notes: searchParams.get("notes") || undefined,
      search: searchParams.get("search") || undefined,
    },
  });

  const formValues = form.watch();
  const debouncedFormValues = useDebounce({ value: formValues, delay: 500 });

  const prevFormValuesRef = useRef<FilterFormValues | null>(null);
  const isInitialMount = useRef(true);
  const skipNextSyncRef = useRef(false);

  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false;
      prevFormValuesRef.current = formValues;
      return;
    }
    const prevValues = prevFormValuesRef.current;
    if (prevValues !== null) {
      const hasChanged = Object.keys(formValues).some(
        (key) =>
          formValues[key as keyof FilterFormValues] !==
          prevValues[key as keyof FilterFormValues]
      );
      if (hasChanged && page !== 1) {
        setSearchParams((prev) => {
          const newParams = new URLSearchParams(prev);
          newParams.set("page", "1");
          return newParams;
        });
      }
    }
    prevFormValuesRef.current = formValues;
  }, [formValues, page, setSearchParams]);

  const params: TGetPaymentsParams = useMemo(() => {
    const values = debouncedFormValues;
    const orderIdNum = values.order_id?.trim() ? Number(values.order_id) : NaN;
    const clientIdNum = values.client_id?.trim() ? Number(values.client_id) : NaN;
    const employeeIdNum = values.employee_id?.trim()
      ? Number(values.employee_id)
      : NaN;
    const inventoryIdNum = values.inventory_id?.trim()
      ? Number(values.inventory_id)
      : NaN;
    const branchIdNum = values.branch_id?.trim() ? Number(values.branch_id) : NaN;
    const cashboxIdNum = values.cashbox_id?.trim()
      ? Number(values.cashbox_id)
      : NaN;
    const amountMinNum = values.amount_min?.trim()
      ? Number(values.amount_min)
      : NaN;
    const amountMaxNum = values.amount_max?.trim()
      ? Number(values.amount_max)
      : NaN;
    return {
      page,
      per_page,
      status:
        (values.status && values.status !== "all"
          ? values.status
          : undefined) as TPaymentStatus | undefined,
      payment_type:
        (values.payment_type && values.payment_type !== "all"
          ? values.payment_type
          : undefined) as TPaymentType | undefined,
      order_id: Number.isFinite(orderIdNum) ? orderIdNum : undefined,
      client_id: Number.isFinite(clientIdNum) ? clientIdNum : undefined,
      employee_id: Number.isFinite(employeeIdNum) ? employeeIdNum : undefined,
      inventory_id: Number.isFinite(inventoryIdNum) ? inventoryIdNum : undefined,
      branch_id: Number.isFinite(branchIdNum) ? branchIdNum : undefined,
      cashbox_id: Number.isFinite(cashboxIdNum) ? cashboxIdNum : undefined,
      date_from: values.date_from?.trim() || undefined,
      date_to: values.date_to?.trim() || undefined,
      amount_min: Number.isFinite(amountMinNum) ? amountMinNum : undefined,
      amount_max: Number.isFinite(amountMaxNum) ? amountMaxNum : undefined,
      search: values.search?.trim() || values.notes?.trim() || undefined,
    };
  }, [page, per_page, debouncedFormValues]);

  useEffect(() => {
    if (skipNextSyncRef.current) {
      skipNextSyncRef.current = false;
      setSearchParams(
        (prev) => {
          const next = new URLSearchParams();
          next.set("page", prev.get("page") || "1");
          next.set("per_page", prev.get("per_page") || String(per_page));
          return next;
        },
        { replace: true }
      );
      return;
    }
    const next = new URLSearchParams();
    next.set("page", String(page));
    next.set("per_page", String(per_page));
    if (params.status) next.set("status", params.status);
    if (params.payment_type) next.set("payment_type", params.payment_type);
    if (params.client_id != null) next.set("client_id", String(params.client_id));
    if (params.order_id != null) next.set("order_id", String(params.order_id));
    if (params.employee_id != null)
      next.set("employee_id", String(params.employee_id));
    if (params.inventory_id != null)
      next.set("inventory_id", String(params.inventory_id));
    if (params.branch_id != null) next.set("branch_id", String(params.branch_id));
    if (params.cashbox_id != null)
      next.set("cashbox_id", String(params.cashbox_id));
    if (params.date_from) next.set("date_from", params.date_from);
    if (params.date_to) next.set("date_to", params.date_to);
    if (params.amount_min != null)
      next.set("amount_min", String(params.amount_min));
    if (params.amount_max != null)
      next.set("amount_max", String(params.amount_max));
    if (params.search) next.set("search", params.search);
    setSearchParams(next, { replace: true });
  }, [params, page, per_page, setSearchParams]);

  const scope = (formValues.scope as TPaymentsScope | undefined) || "payments";
  const mainPaymentsQuery = useQuery({
    ...useGetPaymentsQueryOptions(params),
    enabled: scope === "payments",
  });
  const tailoringQuery = useQuery({
    ...useGetTailoringPaymentsQueryOptions({
      page,
      per_page,
      status: params.status ?? undefined,
      payment_type: params.payment_type ?? undefined,
      cashbox_id: params.cashbox_id ?? undefined,
      start_date: params.date_from,
      end_date: params.date_to,
    }),
    enabled: scope === "tailoring",
  });
  const manualQuery = useQuery({
    ...useGetManualCashboxPaymentsQueryOptions(params.cashbox_id ?? null, {
      page,
      per_page,
      start_date: params.date_from,
      end_date: params.date_to,
    }),
    enabled: scope === "manual" && params.cashbox_id != null,
  });
  const manualAllQuery = useQuery({
    ...useGetAllManualCashboxPaymentsQueryOptions({
      page,
      per_page,
      start_date: params.date_from,
      end_date: params.date_to,
      branch_id: params.branch_id,
      cashbox_id: params.cashbox_id,
    }),
    enabled: scope === "manual" && params.cashbox_id == null,
  });

  const { data: branchesData } = useQuery(useGetBranchesQueryOptions(1, 500));
  const branches = branchesData?.data ?? [];
  const { data: cashboxesData } = useQuery(
    useGetCashboxesQueryOptions({ page: 1, per_page: 500, is_active: true })
  );
  const cashboxes = cashboxesData?.data ?? [];
  const cashboxById = useMemo(() => {
    const map = new Map<number, (typeof cashboxes)[number]>();
    for (const c of cashboxes) map.set(c.id, c);
    return map;
  }, [cashboxes]);

  const markAsPaidMutation = useMutation(useMarkPaymentAsPaidMutationOptions());
  const markAsCanceledMutation = useMutation(
    useMarkPaymentAsCanceledMutationOptions()
  );
  const { mutate: exportPaymentsToCSV, isPending: isExporting } = useMutation(
    useExportPaymentsToCSVMutationOptions()
  );

  const handleViewDetails = (payment: TPaymentListRow) => {
    if (payment.source !== "payments") return;
    setSelectedPayment(payment as unknown as TPayment);
    setIsDetailsModalOpen(true);
  };

  const handleMarkAsPaid = async (paymentId: number) => {
    try {
      await markAsPaidMutation.mutateAsync(paymentId);
      toast.success("تم تحديث حالة الدفعة إلى مدفوع بنجاح");
    } catch {
      toast.error("حدث خطأ أثناء تحديث حالة الدفعة");
    }
  };

  const handleMarkAsCanceled = async (paymentId: number) => {
    try {
      await markAsCanceledMutation.mutateAsync(paymentId);
      toast.success("تم إلغاء الدفعة بنجاح");
    } catch {
      toast.error("حدث خطأ أثناء إلغاء الدفعة");
    }
  };

  const handleResetFilters = () => {
    skipNextSyncRef.current = true;
    form.reset({
      status: "all",
      scope: "payments",
      payment_type: "all",
      client_id: undefined,
      order_id: undefined,
      employee_id: undefined,
      inventory_id: undefined,
      branch_id: undefined,
      cashbox_id: undefined,
      date_from: undefined,
      date_to: undefined,
      amount_min: undefined,
      amount_max: undefined,
      notes: undefined,
      search: undefined,
    });
    setSearchParams({ page: "1", per_page: String(per_page) });
  };

  const handleExport = () => {
    if (scope !== "payments") {
      toast.info("تصدير Excel متاح حالياً لقائمة المدفوعات الأساسية فقط.");
      return;
    }
    exportPaymentsToCSV(params, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "payments.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير المدفوعات بنجاح");
      },
      onError: (err: { message?: string }) => {
        toast.error("خطأ أثناء تصدير المدفوعات. الرجاء المحاولة مرة أخرى.", {
          description: err?.message,
        });
      },
    });
  };

  const handleExportPDF = async () => {
    if (scope !== "payments") {
      toast.info("تصدير PDF متاح حالياً لقائمة المدفوعات الأساسية فقط.");
      return;
    }
    setIsExportingPDF(true);
    try {
      const pdfParams: TGetPaymentsParams = {
        ...params,
        page: 1,
        per_page: 500,
      };
      const result = await getPayments(pdfParams);
      const payments = result?.data ?? [];
      const total = result?.total ?? 0;
      if (payments.length === 0) {
        toast.info("لا توجد مدفوعات لتصديرها");
        return;
      }
      const printDate = new Date().toLocaleDateString("ar-EG", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });
      const rows = payments
        .map((p) => {
          const amount =
            typeof p.amount === "number" ? p.amount : Number(p.amount) || 0;
          const clientName = getClientName(p.order?.client);
          const branchName =
            p.cashbox?.branch?.name ?? p.order?.branch?.name ?? "—";
          return `<tr>
              <td>${p.id}</td>
              <td>${clientName}</td>
              <td>${branchName}</td>
              <td>${amount.toLocaleString("ar-EG")} ج.م</td>
              <td>${getStatusLabel(p.status)}</td>
              <td>${getPaymentTypeLabel(p.payment_type)}</td>
              <td>${formatDate(p.payment_date)}</td>
              <td>${formatDate(p.created_at)}</td>
              <td>${p.notes || "—"}</td>
            </tr>`;
        })
        .join("");
      const totalSum = payments.reduce(
        (s, p) =>
          s + (typeof p.amount === "number" ? p.amount : Number(p.amount) || 0),
        0
      );
      const html = `
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تقرير المدفوعات</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 20px; font-size: 12px; }
    h1 { font-size: 18px; margin-bottom: 8px; }
    .meta { color: #64748b; margin-bottom: 16px; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: right; }
    th { background: #0369A1; color: white; font-weight: 700; }
    tr:nth-child(even) { background: #f8fafc; }
    .footer { margin-top: 16px; font-weight: 700; color: #1e40af; }
    @media print { body { padding: 10px; } }
  </style>
</head>
<body>
  <h1>تقرير المدفوعات</h1>
  <div class="meta">تاريخ الطباعة: ${printDate} | يعرض ${payments.length} من ${total} سجل</div>
  <table>
    <thead>
      <tr>
        <th>رقم</th>
        <th>العميل</th>
        <th>الفرع</th>
        <th>المبلغ</th>
        <th>الحالة</th>
        <th>نوع الدفعة</th>
        <th>تاريخ الدفع</th>
        <th>تاريخ الإنشاء</th>
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
    } catch (err) {
      toast.error("حدث خطأ أثناء تصدير PDF");
    } finally {
      setIsExportingPDF(false);
    }
  };

  const data =
    scope === "payments"
      ? mainPaymentsQuery.data
      : scope === "tailoring"
        ? tailoringQuery.data
        : params.cashbox_id != null
          ? manualQuery.data?.payments
          : manualAllQuery.data;
  const isPending =
    scope === "payments"
      ? mainPaymentsQuery.isPending
      : scope === "tailoring"
        ? tailoringQuery.isPending
        : params.cashbox_id != null
          ? manualQuery.isPending
          : manualAllQuery.isPending;
  const isError =
    scope === "payments"
      ? mainPaymentsQuery.isError
      : scope === "tailoring"
        ? tailoringQuery.isError
        : params.cashbox_id != null
          ? manualQuery.isError
          : manualAllQuery.isError;
  const error =
    scope === "payments"
      ? (mainPaymentsQuery.error as Error | null)
      : scope === "tailoring"
        ? (tailoringQuery.error as Error | null)
        : params.cashbox_id != null
          ? (manualQuery.error as Error | null)
          : (manualAllQuery.error as Error | null);

  const manualPaymentsData =
    params.cashbox_id != null
      ? manualQuery.data?.payments?.data ?? []
      : manualAllQuery.data?.data ?? [];

  const items: TPaymentListRow[] = useMemo(() => {
    if (scope === "payments") {
      return (mainPaymentsQuery.data?.data ?? []).map((p) => ({
        ...p,
        notes: p.notes ?? "",
        source: "payments",
      }));
    }
    if (scope === "tailoring") {
      return (tailoringQuery.data?.data ?? []).map((p) => ({
        cashbox:
          (p.cashbox_id ? cashboxById.get(p.cashbox_id) : undefined) ??
          (p.cashbox
            ? ({
                ...p.cashbox,
                branch:
                  p.cashbox.branch ??
                  (p.cashbox_id ? cashboxById.get(p.cashbox_id)?.branch ?? null : null),
              } as TPayment["cashbox"])
            : undefined),
        id: p.id,
        order_id: p.tailoring_order_id,
        amount: p.amount,
        status: p.status,
        payment_type: p.payment_type,
        payment_date: p.payment_date,
        created_at: p.created_at,
        notes: p.notes ?? "",
        order: p.tailoring_order
          ? ({
              id: p.tailoring_order.id,
              client_id: p.tailoring_order.client_id ?? 0,
              branch_id: p.tailoring_order.branch_id ?? 0,
              branch: p.tailoring_order.branch ?? null,
              client:
                (p.tailoring_order.client as TPayment["order"]["client"]) ??
                undefined,
              total_price: p.tailoring_order.total_price ?? "0",
              paid: String(p.tailoring_order.paid ?? "0"),
              remaining: String(p.tailoring_order.remaining ?? "0"),
              status: p.tailoring_order.status ?? "—",
            } as unknown as TPayment["order"])
          : undefined,
        source: "tailoring",
      }));
    }
    return manualPaymentsData.map((p) => ({
      cashbox:
        p.cashbox ??
        (p.cashbox_id ? cashboxById.get(p.cashbox_id) : undefined) ??
        ({
          id: p.cashbox_id,
          name: `#${p.cashbox_id}`,
          branch_id: 0,
          branch: null,
        } as TPayment["cashbox"]),
      id: p.id,
      order_id: null,
      amount: p.amount,
      status: "paid",
      payment_type: "normal",
      payment_date: p.paid_at,
      created_at: p.created_at ?? p.paid_at,
      notes: p.notes ?? p.description ?? "",
      description: p.description,
      payment_method: p.payment_method ?? null,
      received_from: p.received_from ?? null,
      transaction_id: p.transaction_id ?? null,
      source: "manual",
    }));
  }, [
    scope,
    mainPaymentsQuery.data,
    tailoringQuery.data,
    manualPaymentsData,
    cashboxById,
  ]);
  const totalAmount = items.reduce(
    (s, p) => s + (typeof p.amount === "number" ? p.amount : Number(p.amount) || 0),
    0
  );
  const completedAmount = items
    .filter((p) => p.status === "paid")
    .reduce(
      (s, p) =>
        s + (typeof p.amount === "number" ? p.amount : Number(p.amount) || 0),
      0
    );
  const pendingAmount = items
    .filter((p) => p.status === "pending")
    .reduce(
      (s, p) =>
        s + (typeof p.amount === "number" ? p.amount : Number(p.amount) || 0),
      0
    );
  const completedCount = items.filter((p) => p.status === "paid").length;
  const pendingCount = items.filter((p) => p.status === "pending").length;
  const cancelledCount = items.filter((p) => p.status === "canceled").length;

  const stats = [
    {
      label: "إجمالي المدفوعات",
      value: data?.total ?? 0,
      sub: "معاملة",
      icon: "ri-bank-card-line",
      color: "#2563EB",
      bg: "#DBEAFE",
    },
    {
      label: "المبلغ الكلي",
      value: totalAmount.toLocaleString("en-US"),
      sub: "جنيه مصري",
      icon: "ri-money-dollar-circle-line",
      color: "#0EA5E9",
      bg: "#E0F2FE",
    },
    {
      label: "تم التحصيل",
      value: completedAmount.toLocaleString("en-US"),
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
    params,
    scope,
    data,
    isPending,
    isError,
    error,
    items,
    cashboxes,
    branches,
    stats,
    totalAmount,
    completedCount,
    pendingCount,
    cancelledCount,
    showNotes,
    setShowNotes,
    selectedPayment,
    isDetailsModalOpen,
    setIsDetailsModalOpen,
    isExporting,
    isExportingPDF,
    markAsPaidMutation,
    markAsCanceledMutation,
    handleViewDetails,
    handleMarkAsPaid,
    handleMarkAsCanceled,
    handleResetFilters,
    handleExport,
    handleExportPDF,
    setSelectedPayment,
  };
}
