import SectionOverview from "../project-style/SectionOverview";
import type { TDashboardFinancial } from "@/api/v2/dashboard/dashboard.types";

type Props = {
  financial: TDashboardFinancial | undefined;
};

export function DashboardOverviewSection({ financial }: Props) {
  return <SectionOverview financial={financial} />;
}
