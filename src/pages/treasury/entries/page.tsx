import { useMemo, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { useGetTransactionsQueryOptions } from "@/api/v2/transactions/transactions.hooks";
import {
  useGetCashboxesQueryOptions,
  useManualCashboxPaymentMutationOptions,
} from "@/api/v2/cashboxes/cashboxes.hooks";
import { useCreateExpenseMutationOptions } from "@/api/v2/expenses/expenses.hooks";
import {
  TTransaction,
  TTransactionsParams,
} from "@/api/v2/transactions/transactions.types";
import { JournalEntry, JournalLine } from "./journal.types";
import JournalStats from "./components/JournalStats";
import JournalFilters from "./components/JournalFilters";
import JournalTable from "./components/JournalTable";
import AddManualPaymentModal from "./components/AddManualPaymentModal";

const initialFilters = { search: "", type: "", status: "", branch: "الكل", dateFrom: "", dateTo: "" };

function toNumber(value: number | string | null | undefined): number {
  if (value == null) return 0;
  return typeof value === "number" ? value : Number(value) || 0;
}

function mapTransactionToJournalEntry(tx: TTransaction): JournalEntry {
  const rawAmount = toNumber(tx.amount);
  const amount = Math.abs(rawAmount);
  const cashboxAccount = { code: "1100", name: "الصندوق النقدي" };

  const revenueAccountByCategory: Record<string, { code: string; name: string }> = {
    payment: { code: "4100", name: "إيرادات المبيعات" },
    receivable_payment: { code: "4200", name: "إيرادات الإيجار" },
    reversal: { code: "4400", name: "إيرادات أخرى" },
  };

  const expenseAccountByCategory: Record<string, { code: string; name: string }> = {
    expense: { code: "5400", name: "مصروف الخدمات" },
    salary_expense: { code: "5200", name: "مصروف الرواتب" },
  };

  let lines: JournalLine[] = [];
  if (tx.type === "income") {
    const revenue = revenueAccountByCategory[tx.category] ?? {
      code: "4400",
      name: "إيرادات أخرى",
    };
    lines = [
      {
        id: `${tx.id}-1`,
        account: cashboxAccount.name,
        accountCode: cashboxAccount.code,
        description: tx.description ?? "تحصيل نقدي",
        debit: amount,
        credit: 0,
      },
      {
        id: `${tx.id}-2`,
        account: revenue.name,
        accountCode: revenue.code,
        description: tx.description ?? "إثبات إيراد",
        debit: 0,
        credit: amount,
      },
    ];
  } else if (tx.type === "expense") {
    const expenseAcc = expenseAccountByCategory[tx.category] ?? {
      code: "5400",
      name: "مصروف الخدمات",
    };
    lines = [
      {
        id: `${tx.id}-1`,
        account: expenseAcc.name,
        accountCode: expenseAcc.code,
        description: tx.description ?? "إثبات مصروف",
        debit: amount,
        credit: 0,
      },
      {
        id: `${tx.id}-2`,
        account: cashboxAccount.name,
        accountCode: cashboxAccount.code,
        description: tx.description ?? "صرف نقدي",
        debit: 0,
        credit: amount,
      },
    ];
  } else {
    const reversalDebit =
      rawAmount >= 0
        ? { account: "تسويات / عكس", code: "4999", amount }
        : { account: cashboxAccount.name, code: cashboxAccount.code, amount };
    const reversalCredit =
      rawAmount >= 0
        ? { account: cashboxAccount.name, code: cashboxAccount.code, amount }
        : { account: "تسويات / عكس", code: "4999", amount };

    lines = [
      {
        id: `${tx.id}-1`,
        account: reversalDebit.account,
        accountCode: reversalDebit.code,
        description: tx.description ?? "عكس قيد",
        debit: reversalDebit.amount,
        credit: 0,
      },
      {
        id: `${tx.id}-2`,
        account: reversalCredit.account,
        accountCode: reversalCredit.code,
        description: tx.description ?? "أثر نقدي",
        debit: 0,
        credit: reversalCredit.amount,
      },
    ];
  }

  const totalDebit = lines.reduce((s, l) => s + l.debit, 0);
  const totalCredit = lines.reduce((s, l) => s + l.credit, 0);

  const metadata = (tx.metadata ?? {}) as Record<string, unknown>;
  const isManualPayment = metadata.manual_payment === true;
  const paymentMethod =
    typeof metadata.payment_method === "string" ? metadata.payment_method : "";
  const receivedFrom =
    typeof metadata.received_from === "string" ? metadata.received_from : "";
  const manualNotes =
    typeof metadata.notes === "string" ? metadata.notes : "";
  const branchName =
    (typeof metadata.branch_name === "string" && metadata.branch_name) ||
    (typeof metadata.branch === "string" && metadata.branch) ||
    tx.cashbox?.name ||
    "—";

  return {
    id: String(tx.id),
    entryNumber: `JE-${tx.id}`,
    date: tx.created_at.slice(0, 10),
    description: tx.description || "قيد ناتج عن حركة خزنة",
    reference: tx.reference_type && tx.reference_id ? `${tx.reference_type}:${tx.reference_id}` : "",
    branch: branchName,
    type: tx.type === "reversal" ? "تسوية" : "عادي",
    status: tx.is_reversed ? "ملغي" : "معتمد",
    lines,
    createdBy: tx.creator?.name ?? "النظام",
    approvedBy: tx.is_reversed ? "" : "النظام",
    notes: isManualPayment
      ? [
          "Manual Payment",
          paymentMethod ? `الطريقة: ${paymentMethod}` : "",
          receivedFrom ? `المستلم من: ${receivedFrom}` : "",
          manualNotes ? `ملاحظات: ${manualNotes}` : "",
        ]
          .filter(Boolean)
          .join(" | ")
      : "",
    totalDebit,
    totalCredit,
  };
}

export default function EntriesPage() {
  const [filters, setFilters] = useState(initialFilters);
  const [isManualPaymentOpen, setIsManualPaymentOpen] = useState(false);

  const handleFilterChange = (key: string, value: string) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
  };

  const params: TTransactionsParams = useMemo(
    () => ({
      page: 1,
      per_page: 500,
      sort: "desc",
      start_date: filters.dateFrom || undefined,
      end_date: filters.dateTo || undefined,
    }),
    [filters.dateFrom, filters.dateTo]
  );

  const { data, isPending, isError, error } = useQuery(
    useGetTransactionsQueryOptions(params)
  );
  const { data: cashboxesData } = useQuery(
    useGetCashboxesQueryOptions({ page: 1, per_page: 200, is_active: true })
  );
  const { mutate: createManualPayment, isPending: isCreatingManualPayment } =
    useMutation(useManualCashboxPaymentMutationOptions());
  const { mutate: createExpense, isPending: isCreatingExpense } = useMutation(
    useCreateExpenseMutationOptions()
  );

  const entries = useMemo(
    () => (data?.data ?? []).map(mapTransactionToJournalEntry),
    [data?.data]
  );

  const filtered = useMemo(() => {
    return entries.filter((e) => {
      if (
        filters.search &&
        !e.entryNumber.toLowerCase().includes(filters.search.toLowerCase()) &&
        !e.description.includes(filters.search) &&
        !e.reference.toLowerCase().includes(filters.search.toLowerCase()) &&
        !e.lines.some((l) => l.account.includes(filters.search))
      ) return false;
      if (filters.type && e.type !== filters.type) return false;
      if (filters.status && e.status !== filters.status) return false;
      if (filters.branch !== "الكل" && e.branch !== filters.branch) return false;
      if (filters.dateFrom && e.date < filters.dateFrom) return false;
      if (filters.dateTo && e.date > filters.dateTo) return false;
      return true;
    });
  }, [entries, filters]);

  const handleApprove = () => {
    toast.info("اعتماد القيود سيُربط عبر API مخصص لاحقاً.");
  };

  const handleDelete = () => {
    toast.info("حذف القيود سيُربط عبر API مخصص لاحقاً.");
  };

  const handleManualPaymentSubmit = (
    payload:
      | {
          entryFlow: "inside";
          cashboxId: number;
          amount: number;
          description: string;
          payment_method?: string;
          received_from?: string;
          notes?: string;
        }
      | {
          entryFlow: "outside";
          branch_id: number;
          category: string;
          subcategory?: string | null;
          amount: number;
          expense_date: string;
          vendor: string;
          reference_number: string;
          description: string;
          notes: string;
        }
  ) => {
    if (payload.entryFlow === "outside") {
      createExpense(
        {
          branch_id: payload.branch_id,
          category: payload.category,
          subcategory: payload.subcategory ?? null,
          amount: payload.amount,
          expense_date: payload.expense_date,
          vendor: payload.vendor,
          reference_number: payload.reference_number,
          description: payload.description,
          notes: payload.notes,
        },
        {
          onSuccess: (result) => {
            if (!result) return;
            toast.success("تم إنشاء المصروف بنجاح", {
              description: `رقم المصروف #${result.id}`,
            });
            setIsManualPaymentOpen(false);
          },
        }
      );
      return;
    }

    createManualPayment(
      {
        cashboxId: payload.cashboxId,
        data: {
          amount: payload.amount,
          description: payload.description,
          payment_method: payload.payment_method,
          received_from: payload.received_from,
          notes: payload.notes,
        },
      },
      {
        onSuccess: (result) => {
          if (!result) return;
          toast.success("تم إضافة القيد المحاسبي بنجاح", {
            description: `الرصيد الحالي: ${Number(
              result.cashbox.current_balance
            ).toLocaleString("ar-EG")} ج.م · رقم العملية اليدوية #${
              result.manual_payment.id
            }`,
          });
          setIsManualPaymentOpen(false);
        },
      }
    );
  };

  const handleExportExcel = () => {
    const rows = filtered.flatMap((e) =>
      e.lines.map((l) => ({
        "رقم القيد": e.entryNumber,
        "التاريخ": e.date,
        "البيان": e.description,
        "الفرع": e.branch,
        "النوع": e.type,
        "الحالة": e.status,
        "كود الحساب": l.accountCode,
        "اسم الحساب": l.account,
        "بيان السطر": l.description,
        "مدين": l.debit || "",
        "دائن": l.credit || "",
      }))
    );
    if (!rows.length) return;
    const header = Object.keys(rows[0]).join(",");
    const csv = "data:text/csv;charset=utf-8,\uFEFF" + header + "\n" + rows.map((r) => Object.values(r).join(",")).join("\n");
    const link = document.createElement("a");
    link.href = encodeURI(csv);
    link.download = `القيود-المحاسبية-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
  };

  return (
    <div className="p-6 space-y-5" dir="rtl">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">القيود المحاسبية</h1>
          <p className="text-sm text-gray-400 mt-0.5">نظام القيد المزدوج — كل قيد له طرف مدين وطرف دائن متوازنان</p>
        </div>
        <div className="flex items-center gap-2">
          <button onClick={handleExportExcel} className="flex items-center gap-2 px-4 py-2 text-sm text-green-700 border border-green-200 rounded-lg hover:bg-green-50 transition-colors cursor-pointer whitespace-nowrap">
            <i className="ri-file-excel-line" />تصدير Excel
          </button>
          <button onClick={() => window.print()} className="flex items-center gap-2 px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors cursor-pointer whitespace-nowrap">
            <i className="ri-file-pdf-line" />تصدير PDF
          </button>
          <button
            onClick={() => setIsManualPaymentOpen(true)}
            className="flex items-center gap-2 px-4 py-2 text-sm bg-blue-700 text-white rounded-lg hover:bg-blue-800 transition-colors cursor-pointer whitespace-nowrap"
          >
            <i className="ri-add-line" />إضافة قيد محاسبي
          </button>
        </div>
      </div>

      <div className="bg-blue-900 rounded-xl px-5 py-3 flex items-center gap-4">
        <div className="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-700 shrink-0">
          <i className="ri-information-line text-blue-200 text-lg" />
        </div>
        <div className="text-sm">
          <span className="text-white font-semibold">مبدأ القيد المزدوج: </span>
          <span className="text-blue-200">لكل عملية مالية طرفان — </span>
          <span className="text-red-300 font-medium">الطرف المدين (Dr.) </span>
          <span className="text-blue-200">هو الحساب الذي يزيد بالدخول، والطرف الدائن (Cr.) </span>
          <span className="text-green-300 font-medium">هو الحساب الذي يزيد بالخروج. </span>
          <span className="text-blue-200">المجموع دائماً متساوٍ.</span>
        </div>
      </div>

      <JournalStats entries={filtered} />
      <JournalFilters filters={filters} onFilterChange={handleFilterChange} onReset={() => setFilters(initialFilters)} count={filtered.length} total={entries.length} />
      <JournalTable
        entries={filtered}
        onApprove={handleApprove}
        onDelete={handleDelete}
        isPending={isPending}
        isError={isError}
        errorMessage={error instanceof Error ? error.message : undefined}
      />
      <AddManualPaymentModal
        open={isManualPaymentOpen}
        onOpenChange={setIsManualPaymentOpen}
        cashboxes={cashboxesData?.data ?? []}
        isSubmitting={isCreatingManualPayment || isCreatingExpense}
        onSubmit={handleManualPaymentSubmit}
      />
    </div>
  );
}
