import RecentActivity from "../project-style/RecentActivity";
import type { TOrder } from "@/api/v2/orders/orders.types";

type Props = {
  orders: TOrder[];
};

export function DashboardActivitySection({ orders }: Props) {
  return <RecentActivity orders={orders} />;
}
