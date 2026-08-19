import DashboardStats from "../project-style/DashboardStats";
import type {
  TDashboardSales,
  TDashboardClients,
  TDashboardPayments,
  TDashboardInventory,
  TDashboardFinancial,
} from "@/api/v2/dashboard/dashboard.types";

type Props = {
  sales: TDashboardSales | undefined;
  clients: TDashboardClients | undefined;
  payments: TDashboardPayments | undefined;
  inventory: TDashboardInventory | undefined;
  financial: TDashboardFinancial | undefined;
};

export function DashboardKpisSection({
  sales,
  clients,
  payments,
  inventory,
  financial,
}: Props) {
  return (
    <DashboardStats
      sales={sales}
      clients={clients}
      payments={payments}
      inventory={inventory}
      financial={financial}
    />
  );
}
