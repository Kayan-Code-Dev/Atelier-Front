import type { CSSProperties } from "react";
import type { TDashboardOverviewParams } from "@/api/v2/dashboard/dashboard.types";

export const PERIOD_OPTIONS: { value: TDashboardOverviewParams["period"]; label: string }[] = [
  { value: "month", label: "هذا الشهر" },
  { value: "today", label: "اليوم" },
  { value: "week", label: "هذا الأسبوع" },
  { value: "year", label: "هذه السنة" },
  { value: "last_week", label: "الأسبوع الماضي" },
  { value: "last_month", label: "الشهر الماضي" },
];

export const PERIOD_LABELS: Record<string, string> = Object.fromEntries(
  PERIOD_OPTIONS.map((o) => [o.value, o.label])
);

export const ORDER_STATUS_LABELS: Record<string, string> = {
  created: "تم الإنشاء",
  delivered: "تم التسليم",
  paid: "مدفوع",
  partially_paid: "مدفوع جزئياً",
  canceled: "ملغي",
};

export const PAYMENT_METHOD_LABELS: Record<string, string> = {
  initial: "دفعة أولى",
  normal: "عادي",
  fee: "رسوم",
};

export const CHART_COLORS = [
  "var(--chart-1)",
  "var(--chart-2)",
  "var(--chart-3)",
  "var(--chart-4)",
  "var(--chart-5)",
  "#4f6bf7",
  "#16c098",
  "#f59e0b",
] as const;

export const CHART_TOOLTIP_STYLE: CSSProperties = {
  borderRadius: "var(--radius-xl)",
  border: "1px solid var(--border)",
  boxShadow: "var(--shadow-lg)",
  padding: "12px 16px",
  backgroundColor: "var(--card)",
  fontSize: "13px",
};

export const CHART_CONTAINER_CLASS =
  "h-full w-full rounded-xl bg-muted/15 p-4 border border-border/50";


export const ORDER_TYPE_CONFIG: Record<
  string,
  { bg: string; color: string; icon: string }
> = {
  buy: { bg: "#F5F0FE", color: "#7C3AED", icon: "ri-shopping-bag-3-line" },
  rent: { bg: "#EBF3FE", color: "#1A3A6B", icon: "ri-key-2-line" },
  tailoring: { bg: "#E8F8F1", color: "#059669", icon: "ri-scissors-cut-line" },
  mixed: { bg: "#FEF7E0", color: "#C2820A", icon: "ri-file-list-3-line" },
  unknown: { bg: "#F4F7FB", color: "#64748B", icon: "ri-file-list-3-line" },
};
