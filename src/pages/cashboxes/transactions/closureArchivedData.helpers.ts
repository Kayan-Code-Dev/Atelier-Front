import type { TClosureArchivedDataType } from "@/api/v2/cashboxes/cashboxes.types";
import type { TTransaction } from "@/api/v2/transactions/transactions.types";
import { getCategoryLabel } from "./hooks/useCashboxTransactionsPage";

export const CLOSURE_ARCHIVED_TYPE_OPTIONS: {
  value: TClosureArchivedDataType;
  label: string;
}[] = [
  { value: "transactions", label: "حركات الدفتر (كامل)" },
  { value: "payments_expenses", label: "مدفوعات ومصروفات" },
  { value: "payments", label: "دفعات الطلبات" },
  { value: "expenses", label: "مصروفات" },
  { value: "manual_payments", label: "دفعات يدوية" },
  { value: "tailoring_payments", label: "دفعات الخياطة" },
  { value: "salary_payments", label: "رواتب" },
];

export const ARCHIVED_TYPE_LABELS_AR: Record<string, string> = {
  income: "إيراد",
  expense: "مصروف",
  reversal: "عكس حركة",
};

export const ARCHIVED_COLUMN_LABELS_AR: Record<string, string> = {
  amount: "المبلغ",
  total: "الإجمالي",
  type: "النوع",
  category: "التصنيف",
  description: "الوصف",
  created_at: "تاريخ الإنشاء",
  paid_at: "تاريخ الدفع",
  deleted_at: "تاريخ الحذف",
  name: "الاسم",
  title: "العنوان",
  balance_after: "الرصيد بعد",
  cashbox_balance_before: "الرصيد قبل",
  cashbox_balance_after: "الرصيد بعد",
  cashbox: "الصندوق",
  is_reversed: "معكوس؟",
  branch_id: "معرف الفرع",
  status: "الحالة",
  notes: "ملاحظات",
  payment_method: "طريقة الدفع",
  received_from: "المستلم من",
  transaction_id: "رقم الحركة",
  cashbox_id: "الصندوق",
  creator: "المستخدم",
  order_id: "رقم الطلب",
  expense_id: "رقم المصروف",
  is_archived: "مؤرشف؟",
  email: "البريد",
  phone: "الهاتف",
  customer_id: "العميل",
  supplier_id: "المورد",
  vendor: "المورد",
  employee: "الموظف",
  user_id: "المستخدم",
  creator_id: "أنشأه",
  created_by: "المستخدم",
  invoice_ref: "مرجع الفاتورة",
  currency: "العملة",
  quantity: "الكمية",
  unit_price: "سعر الوحدة",
  discount: "الخصم",
  tax: "الضريبة",
  subtotal: "المجموع الفرعي",
};

export const ARCHIVED_GLOBAL_HIDDEN_COLUMNS = [
  "id",
  "reference_id",
  "closure_id",
  "metadata",
  "updated_at",
  "reversed_transaction_id",
  "created_by",
  "reference_type",
  "reversals",
  "cashbox_daily_expense_total",
  "cashbox_daily_income_total",
  "cashbox_snapshot_meta",
  "approved_at",
  "approved_by",
  "approver",
  "branch",
  "branch_id",
  "deleted_at",
  "expense_date",
  "reference_number",
  "subcategory",
  "transaction",
  "payment_date",
  "payment_type",
  "tailoring_order",
  "user",
  "employee_id",
] as const;

export const ARCHIVED_HIDDEN_COLUMNS_BY_TYPE: Partial<
  Record<TClosureArchivedDataType, readonly string[]>
> = {
  payments: ["order"],
  payments_expenses: ["order"],
  tailoring_payments: ["tailoring_order_id", "tailoringOrderId"],
  salary_payments: ["payment_reference", "period"],
};

export const PREFERRED_ARCHIVED_COLUMN_KEYS = [
  "created_at",
  "paid_at",
  "type",
  "category",
  "amount",
  "total",
  "balance_after",
  "description",
  "name",
  "status",
  "payment_method",
  "cashbox",
  "cashbox_id",
  "cashbox_balance_before",
  "cashbox_balance_after",
  "creator",
  "transaction_id",
  "order_id",
  "expense_id",
  "vendor",
  "employee",
  "is_reversed",
  "is_archived",
  "notes",
] as const;

function buildArchivedHiddenColumnSet(
  archivedType: TClosureArchivedDataType | undefined
): Set<string> {
  const set = new Set<string>(ARCHIVED_GLOBAL_HIDDEN_COLUMNS);
  if (archivedType == null) return set;
  const extra = ARCHIVED_HIDDEN_COLUMNS_BY_TYPE[archivedType];
  if (extra) for (const k of extra) set.add(k);
  return set;
}

function dedupeCashboxColumns(keys: string[]): string[] {
  if (keys.includes("cashbox") && keys.includes("cashbox_id")) {
    return keys.filter((k) => k !== "cashbox_id");
  }
  return keys;
}

export function archivedColumnHeader(key: string): string {
  return ARCHIVED_COLUMN_LABELS_AR[key] ?? key;
}

const CLOSURE_DETAILS_CATEGORY_LABELS_AR: Record<string, string> = {
  initial_balance: "رصيد افتتاحي",
  new_initial_balance: "رصيد ابتدائي بعد الترحيل",
  opening_balance: "رصيد الافتتاح",
  closing_balance: "رصيد الإقفال",
  net_change: "صافي الحركة",
  carry_forward: "مرحل من فترة سابقة",
  adjustment: "تسوية",
  transfer: "تحويل",
  other: "أخرى",
};

export function closureDetailsCategoryLabel(key: string): string {
  const extra = CLOSURE_DETAILS_CATEGORY_LABELS_AR[key];
  if (extra != null) return extra;
  return getCategoryLabel(key as TTransaction["category"]);
}

const ISO_DATE_LIKE =
  /^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}|.\d+Z?)?$/;

function tryFormatDateString(s: string): string | null {
  if (!ISO_DATE_LIKE.test(s) && !s.includes("T")) return null;
  const d = new Date(s);
  if (Number.isNaN(d.getTime())) return null;
  return d.toLocaleString("ar-EG", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export function formatArchivedCell(v: unknown): string {
  if (v === null || v === undefined) return "—";
  if (typeof v === "boolean") return v ? "نعم" : "لا";
  if (typeof v === "number") {
    if (Number.isNaN(v)) return "—";
    return v.toLocaleString("ar-EG", { maximumFractionDigits: 6 });
  }
  if (typeof v === "string") {
    const trimmed = v.trim();
    const asDate = tryFormatDateString(trimmed);
    if (asDate) return asDate;
    const asNum = Number(trimmed);
    if (trimmed !== "" && !Number.isNaN(asNum) && /^-?\d+\.?\d*$/.test(trimmed))
      return asNum.toLocaleString("ar-EG", { maximumFractionDigits: 4 });
    return trimmed;
  }
  if (typeof v === "object") {
    try {
      return JSON.stringify(v, null, 0);
    } catch {
      return String(v);
    }
  }
  return String(v);
}

export type TArchivedFormatContext = {
  responseCashbox?: { id: number; name: string };
};

function formatArchivedEmployeeSummary(value: unknown): string | null {
  if (value === null || typeof value !== "object" || Array.isArray(value)) {
    return null;
  }
  const o = value as Record<string, unknown>;
  let name: string | undefined;
  let email: string | undefined;

  const user = o.user;
  if (user !== null && typeof user === "object" && !Array.isArray(user)) {
    const u = user as Record<string, unknown>;
    if (typeof u.name === "string" && u.name.trim()) name = u.name.trim();
    if (typeof u.email === "string" && u.email.trim()) email = u.email.trim();
  }

  if (!name && typeof o.name === "string" && o.name.trim()) {
    name = o.name.trim();
  }

  const lines: string[] = [];
  if (name) lines.push(name);
  if (email) lines.push(email);
  if (lines.length > 0) return lines.join("\n");
  return null;
}

function formatArchivedMoneyCell(value: unknown): string | null {
  if (value === null || value === undefined) return null;
  const n =
    typeof value === "number"
      ? value
      : Number(String(value).replace(/,/g, ""));
  if (Number.isNaN(n)) return null;
  return n.toLocaleString("ar-EG", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

export function formatArchivedCellValue(
  key: string,
  value: unknown,
  row: Record<string, unknown>,
  ctx?: TArchivedFormatContext
): string {
  if (key === "type" && typeof value === "string") {
    const t = value.toLowerCase();
    return ARCHIVED_TYPE_LABELS_AR[t] ?? formatArchivedCell(value);
  }

  if (key === "category" && typeof value === "string") {
    return getCategoryLabel(value as TTransaction["category"]);
  }

  if (
    key === "balance_after" ||
    key === "cashbox_balance_before" ||
    key === "cashbox_balance_after"
  ) {
    const m = formatArchivedMoneyCell(value);
    if (m !== null) return m;
    return formatArchivedCell(value);
  }

  if (key === "cashbox" && value !== null && typeof value === "object") {
    const o = value as Record<string, unknown>;
    if (typeof o.name === "string" && o.name.trim()) return o.name.trim();
    return formatArchivedCell(value);
  }

  if (key === "vendor" && value !== null && typeof value === "object") {
    const o = value as Record<string, unknown>;
    if (typeof o.name === "string" && o.name.trim()) return o.name.trim();
    return formatArchivedCell(value);
  }

  if (key === "employee" && value !== null && typeof value === "object") {
    const summary = formatArchivedEmployeeSummary(value);
    if (summary !== null) return summary;
    return formatArchivedCell(value);
  }

  if (key === "cashbox_id") {
    const n = typeof value === "number" ? value : Number(value);
    if (
      !Number.isNaN(n) &&
      ctx?.responseCashbox != null &&
      ctx.responseCashbox.id === n
    ) {
      return ctx.responseCashbox.name;
    }
    return formatArchivedCell(value);
  }

  if (key === "creator") {
    const obj =
      value !== null && typeof value === "object" && !Array.isArray(value)
        ? (value as Record<string, unknown>)
        : row.creator !== null &&
            typeof row.creator === "object" &&
            !Array.isArray(row.creator)
          ? (row.creator as Record<string, unknown>)
          : null;
    if (obj != null) {
      const n = obj.name;
      if (typeof n === "string" && n.trim()) return n.trim();
    }
    return formatArchivedCell(value);
  }

  return formatArchivedCell(value);
}

export function collectAllArchivedColumnKeys(
  rows: Record<string, unknown>[],
  archivedType?: TClosureArchivedDataType
): string[] {
  const set = new Set<string>();
  for (const r of rows) {
    Object.keys(r).forEach((k) => set.add(k));
  }

  const preferred = PREFERRED_ARCHIVED_COLUMN_KEYS as readonly string[];
  const rest = [...set]
    .filter((k) => !preferred.includes(k))
    .sort((a, b) => a.localeCompare(b));

  const hidden = buildArchivedHiddenColumnSet(archivedType);
  const keys = [...preferred.filter((k) => set.has(k)), ...rest].filter(
    (k) => !hidden.has(k)
  );

  return dedupeCashboxColumns(keys);
}

export function closureNumeric(
  v: string | number | null | undefined
): number {
  if (v === null || v === undefined) return 0;
  const n = typeof v === "number" ? v : Number(v);
  return Number.isNaN(n) ? 0 : n;
}
