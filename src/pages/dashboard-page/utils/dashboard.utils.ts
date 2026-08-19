import { ORDER_STATUS_LABELS } from "../constants/dashboard.constants";

export type SalesByStatusItem = {
  name: string;
  طلبات: number;
  إيرادات: number;
};

export function buildSalesByStatusData(
  byStatus: Record<string, { count: number; revenue: number }> | undefined
): SalesByStatusItem[] {
  if (!byStatus || Object.keys(byStatus).length === 0) return [];
  return Object.entries(byStatus).map(([status, info]) => ({
    name: ORDER_STATUS_LABELS[status] ?? status,
    طلبات: info?.count ?? 0,
    إيرادات: Math.round((info?.revenue ?? 0) / 1000),
  }));
}
