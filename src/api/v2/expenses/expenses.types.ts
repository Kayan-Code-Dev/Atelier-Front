import { TBranchResponse } from "../branches/branches.types";
import { TCashbox } from "../cashboxes/cashboxes.types";


export type TExpenseSubcategory = { id: string; name: string };


export type TExpenseCategoryWithSubs = {
  id: string;
  name: string;
  subcategories: TExpenseSubcategory[];
};


export const EXPENSE_CATEGORIES_WITH_SUBS: TExpenseCategoryWithSubs[] = [
  {
    id: "operating",
    name: "مصاريف التشغيل",
    subcategories: [
      { id: "shop_rent", name: "إيجار المحل" },
      { id: "electricity", name: "كهرباء" },
      { id: "water", name: "مياه" },
      { id: "internet", name: "إنترنت" },
      { id: "maintenance", name: "صيانة" },
    ],
  },
  {
    id: "salaries_wages",
    name: "رواتب وأجور",
    subcategories: [
      { id: "employee_salaries", name: "رواتب الموظفين" },
      { id: "commissions", name: "عمولات" },
      { id: "bonuses", name: "مكافآت" },
      { id: "employee_advances", name: "سلف موظفين" },
    ],
  },
  {
    id: "materials_raw",
    name: "مواد وخامات",
    subcategories: [
      { id: "fabrics", name: "أقمشة" },
      { id: "threads", name: "خيوط" },
      { id: "accessories", name: "إكسسوارات" },
      { id: "zippers_buttons", name: "سوست وأزرار" },
      { id: "sewing_tools", name: "أدوات خياطة" },
    ],
  },
  {
    id: "marketing",
    name: "مصاريف التسويق",
    subcategories: [
      { id: "fb_instagram_ads", name: "إعلانات فيسبوك / انستجرام" },
      { id: "product_photography", name: "تصوير منتجات" },
      { id: "designs", name: "تصميمات" },
      { id: "brochure_printing", name: "طباعة بروشورات" },
    ],
  },
  {
    id: "daily_operating",
    name: "مصاريف تشغيل يومية",
    subcategories: [
      { id: "transportation", name: "مواصلات" },
      { id: "hospitality", name: "ضيافة (شاي / قهوة)" },
      { id: "cleaning_supplies", name: "أدوات نظافة" },
      { id: "stationery", name: "قرطاسية" },
    ],
  },
  {
    id: "financial",
    name: "مصاريف مالية",
    subcategories: [
      { id: "transfer_fees", name: "رسوم تحويل" },
      { id: "bank_commissions", name: "عمولات بنكية" },
      { id: "taxes", name: "ضرائب" },
      { id: "government_fees", name: "رسوم حكومية" },
    ],
  },
  {
    id: "other",
    name: "مصاريف أخرى",
    subcategories: [{ id: "miscellaneous", name: "أخرى / متنوع" }],
  },
];


export const ExpenseCategories = EXPENSE_CATEGORIES_WITH_SUBS.flatMap((cat) =>
  cat.subcategories.map((sub) => ({
    id: `${cat.id}:${sub.id}`,
    name: `${cat.name} – ${sub.name}`,
  }))
);


export function getExpenseCategoryDisplay(
  categoryId: string | null | undefined,
  subcategoryId?: string | null
): string {
  if (!categoryId) return "—";
  const cat = EXPENSE_CATEGORIES_WITH_SUBS.find((c) => c.id === categoryId);
  if (!cat) return categoryId;
  if (subcategoryId) {
    const sub = cat.subcategories.find((s) => s.id === subcategoryId);
    if (sub) return `${cat.name} – ${sub.name}`;
  }
  return cat.name;
}

export type TExpense = {
  id: number;
  cashbox_id: number;
  branch_id: number;
  category: string;
  subcategory?: string | null;
  /** API returns amount as a string, but can be treated as number in UI */
  amount: number | string;
  expense_date: string;
  vendor: string;
  reference_number: string;
  description: string;
  notes: string;
  status: TExpenseStatus;
  approved_by?: number | null;
  approved_at: string | null;
  
  paid_at?: string | null;
  transaction_id: number | null;
  
  cashbox_balance_before?: number | null;
  cashbox_balance_after?: number | null;
  cashbox_daily_income_total?: number | null;
  cashbox_daily_expense_total?: number | null;
  cashbox_snapshot_meta?: {
    date: string;
    cashbox_id: number;
    cashbox_name: string;
    opening_balance: number;
    total_income: number;
    total_expense: number;
    net_change: number;
    closing_balance: number;
    transaction_count: number;
    reversal_count: number;
  } | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  branch: TBranchResponse;
  cashbox: TCashbox;
  creator: {
    id: number;
    name: string;
    email: string;
  };
  approver?: {
    id: number;
    name: string;
    email: string;
  } | null;
};

export type TExpenseStatus = "pending" | "paid" | "cancelled";

/** Aligned with GET /api/v1/expenses index filters */
export type TGetExpensesParams = {
  page?: number;
  per_page?: number;
  branch_id?: number;
  cashbox_id?: number;
  category?: string;
  subcategory?: string;
  status?: TExpenseStatus;
  start_date?: string;
  end_date?: string;
  date_from?: string;
  date_to?: string;
  amount_min?: number;
  amount_max?: number;
  amount_from?: number;
  amount_to?: number;
  vendor?: string;
  reference_number?: string;
  created_by?: number;
  approved_by?: number;
  transaction_id?: number;
  search?: string;
};

export type TCreateExpenseRequest = {
  branch_id: number;
  category: string;
  subcategory?: string | null;
  amount: number;
  expense_date: string;
  vendor: string;
  reference_number: string;
  description: string;
  notes: string;
};

export type TUpdateExpenseRequest = Partial<
  Omit<TCreateExpenseRequest, "branch_id">
>;

export type TGetExpenseSummaryParams = {
  branch_id?: number;
  start_date: string;
  end_date: string;
};

export type TExpenseCategoriesApiResponse = {
  categories: Record<string, string>;
};

export type TGetExpenseSummaryResponse = {
  period: {
    start_date: string;
    end_date: string;
  };
  total_paid: number;
  by_category: [
    {
      category: string;
      total: number;
      count: number;
    }[]
  ];
  by_status: Record<
    TExpenseStatus,
    {
      status: TExpenseStatus;
      total: number;
      count: number;
    }
  >;
};
