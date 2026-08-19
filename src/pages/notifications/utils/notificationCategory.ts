import type { Notification } from "@/api/v2/notifications/notifications.types";

export type NotificationCategory =
  | "sales"
  | "rental"
  | "tailoring"
  | "treasury"
  | "delivery"
  | "employees"
  | "suppliers"
  | "customers"
  | "inventory"
  | "system";

export const categoryConfig: Record<
  NotificationCategory,
  { label: string; icon: string; color: string; bg: string }
> = {
  sales: { label: "المبيعات", icon: "ri-store-3-line", color: "#22C55E", bg: "#DCFCE7" },
  rental: { label: "الإيجار", icon: "ri-key-2-line", color: "#8B5CF6", bg: "#EDE9FE" },
  tailoring: { label: "التفصيل", icon: "ri-scissors-cut-line", color: "#F59E0B", bg: "#FEF3C7" },
  treasury: { label: "الخزنة", icon: "ri-safe-2-line", color: "#0EA5E9", bg: "#E0F2FE" },
  delivery: { label: "التسليمات", icon: "ri-truck-line", color: "#6366F1", bg: "#EEF2FF" },
  employees: { label: "الموظفون", icon: "ri-user-star-line", color: "#EC4899", bg: "#FCE7F3" },
  suppliers: { label: "الموردون", icon: "ri-building-2-line", color: "#14B8A6", bg: "#CCFBF1" },
  customers: { label: "العملاء", icon: "ri-group-line", color: "#F97316", bg: "#FFEDD5" },
  inventory: { label: "المخزون", icon: "ri-price-tag-3-line", color: "#84CC16", bg: "#F7FEE7" },
  system: { label: "النظام", icon: "ri-settings-3-line", color: "#64748B", bg: "#F1F5F9" },
};

export function getNotificationCategory(n: Notification): NotificationCategory {
  const rt = (n.reference_type ?? "").toLowerCase();
  const type = (n.type ?? "").toLowerCase();

  if (rt.includes("supplierorder") || rt.includes("supplier") || type.includes("supplier"))
    return "suppliers";
  if (rt.includes("expense") || type.includes("expense")) return "treasury";
  if (rt.includes("payment") || type.includes("payment")) return "treasury";
  if (rt.includes("delivery") || rt.includes("deliver") || type.includes("delivery"))
    return "delivery";
  if (rt.includes("return") || type.includes("return")) return "delivery";
  if (rt.includes("order") || type.includes("order")) {
    const orderType = (n.metadata?.order_type ?? "") as string;
    if (orderType === "rent") return "rental";
    if (orderType === "tailoring") return "tailoring";
    return "sales";
  }
  if (rt.includes("client") || rt.includes("customer") || type.includes("customer"))
    return "customers";
  if (rt.includes("employee") || type.includes("employee") || type.includes("payroll"))
    return "employees";
  if (rt.includes("product") || rt.includes("inventory") || type.includes("inventory"))
    return "inventory";

  return "system";
}

export function getNotificationDisplay(n: Notification): {
  icon: string;
  color: string;
} {
  const cat = getNotificationCategory(n);
  const cfg = categoryConfig[cat];
  return { icon: cfg.icon, color: cfg.color };
}
