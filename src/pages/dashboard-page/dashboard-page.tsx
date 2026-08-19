import { useQuery } from "@tanstack/react-query";
import { useNavigate } from "react-router";
import { useGetDashboardOverviewQueryOptions } from "@/api/v2/dashboard/dashboard.hooks";
import { useGetOrdersQueryOptions } from "@/api/v2/orders/orders.hooks";
import { useDashboardFilters } from "./hooks/index";
import {
  DashboardHeader,
  DashboardFilters,
  DataContextStrip,
  DashboardSkeleton,
  DashboardError,
  DashboardFooter,
} from "./components/index";
import {
  DashboardKpisSection,
  DashboardGrowthSection,
  DashboardChartsSection,
  DashboardOverviewSection,
  DashboardActivitySection,
} from "./sections/index";

export default function DashboardPage() {
  const navigate = useNavigate();
  const {
    filters,
    dateFrom,
    dateTo,
    setDateFrom,
    setDateTo,
    showFilters,
    setShowFilters,
    queryParams,
    periodLabel,
    activeFilters,
    setFilter,
    resetFilters,
  } = useDashboardFilters();

  const { data, isPending, isError, error } = useQuery(
    useGetDashboardOverviewQueryOptions(queryParams)
  );
  const { data: ordersData } = useQuery({
    ...useGetOrdersQueryOptions(1, 8),
    staleTime: 1000 * 60 * 2,
  });

  const orders = ordersData?.data ?? [];
  const recentOrders = orders.slice(0, 4);

  if (isPending) return <DashboardSkeleton />;
  if (isError) return <DashboardError message={error?.message} />;
  if (!data) return null;

  const { business } = data;
  const sales = business?.sales;
  const clients = business?.clients;
  const payments = business?.payments;
  const inventory = business?.inventory;
  const financial = business?.financial;

  return (
    <div className="space-y-4 fade-in">
      <DashboardHeader
        periodLabel={periodLabel}
        onToggleFilters={setShowFilters}
        onNavigateReports={() => navigate("/sales/reports")}
        onNavigateCashboxes={() => navigate("/cashboxes")}
      />

      {showFilters && (
        <DashboardFilters
          filters={filters}
          dateFrom={dateFrom}
          dateTo={dateTo}
          onFilterChange={setFilter}
          onDateFromChange={setDateFrom}
          onDateToChange={setDateTo}
          onReset={resetFilters}
        />
      )}

      <DataContextStrip
        periodFrom={data.business?.sales?.period?.from}
        periodTo={data.business?.sales?.period?.to}
        generatedAt={data.generated_at}
        activeFilters={activeFilters}
        onResetFilters={resetFilters}
      />

      <DashboardKpisSection
        sales={sales}
        clients={clients}
        payments={payments}
        inventory={inventory}
        financial={financial}
      />

      <DashboardGrowthSection
        sales={sales}
        clients={clients}
        growthRates={business?.growth_rates}
      />

      <DashboardChartsSection
        sales={sales}
        financial={financial}
        recentOrders={recentOrders}
      />

      <DashboardOverviewSection financial={financial} />

      <DashboardActivitySection orders={orders} />

      <DashboardFooter />
    </div>
  );
}
