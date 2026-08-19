import { useMemo, useState } from "react";
import { useLocation, useNavigate } from "react-router";
import { useQuery } from "@tanstack/react-query";
import { DollarSign, FileText, TrendingUp } from "lucide-react";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { EmployeesSelect } from "@/components/custom/EmployeesSelect";
import { ORDERS_KEY } from "@/api/v2/orders/orders.hooks";
import { getOrders, type TOrderListFilters } from "@/api/v2/orders/orders.service";
import type { TOrder, TOrderItem } from "@/api/v2/orders/orders.types";
import {
  saleDateInputClass,
  saleLabelClass,
  saleProjectSelectTriggerClass,
  saleSectionCardClass,
  saleSectionTitleClass,
} from "@/pages/sales/createSaleInvoiceProjectStyles";

type ReportType = "main" | "daily" | "products" | "by-employee";

function orderTotalAmount(o: TOrder): number {
  const n = parseFloat(o.total_price || "0");
  return Number.isFinite(n) ? n : 0;
}

function lineItemRevenue(item: TOrderItem): number {
  const p = parseFloat(item.price || "0");
  const q = item.quantity ?? 0;
  const pr = Number.isFinite(p) ? p : 0;
  return pr * q;
}

async function fetchAllSoldOrders(filters: TOrderListFilters): Promise<TOrder[]> {
  const perPage = 100;
  const first = await getOrders(1, perPage, filters);
  if (!first?.data) return [];
  const all = [...first.data];
  for (let page = 2; page <= first.total_pages; page++) {
    const res = await getOrders(page, perPage, filters);
    if (res?.data?.length) all.push(...res.data);
  }
  return all;
}

function employeeLabel(o: TOrder): string {
  return (
    o.employee_name ||
    o.employee?.user?.name ||
    (o.employee_id != null ? String(o.employee_id) : "") ||
    "غير محدد"
  );
}

function applyBranchFilter(orders: TOrder[], branchId: string): TOrder[] {
  if (!branchId) return orders;
  const bid = Number(branchId);
  if (!Number.isFinite(bid)) return orders;
  return orders.filter((o) => o.entity_type === "branch" && o.entity_id === bid);
}

export default function SalesReports() {
  const navigate = useNavigate();
  const location = useLocation();
  const pathSegments = location.pathname.split("/").filter(Boolean);
  const lastSegment = pathSegments[pathSegments.length - 1];
  const reportType: ReportType =
    lastSegment === "reports" || !lastSegment
      ? "main"
      : ["daily", "products", "by-employee"].includes(lastSegment)
        ? (lastSegment as Exclude<ReportType, "main">)
        : "main";
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [branchId, setBranchId] = useState("");
  const [employeeId, setEmployeeId] = useState("");

  const listFilters = useMemo((): TOrderListFilters => {
    const base: TOrderListFilters = { process_type: "sold" };
    if (reportType === "main") {
      if (dateFrom) base.date_from = dateFrom;
      if (dateTo) base.date_to = dateTo;
      if (employeeId) base.employee_id = Number(employeeId);
      return base;
    }
    if (reportType === "daily") {
      if (dateFrom) base.date_from = dateFrom;
      if (dateTo) base.date_to = dateTo;
      return base;
    }
    if (reportType === "products") {
      if (dateFrom) base.date_from = dateFrom;
      if (dateTo) base.date_to = dateTo;
      return base;
    }
    if (reportType === "by-employee") {
      if (dateFrom) base.date_from = dateFrom;
      if (dateTo) base.date_to = dateTo;
      if (employeeId) base.employee_id = Number(employeeId);
      return base;
    }
    return base;
  }, [reportType, dateFrom, dateTo, employeeId]);

  const { data: ordersRaw = [], isPending } = useQuery({
    queryKey: [ORDERS_KEY, "sales-reports", listFilters] as const,
    queryFn: () => fetchAllSoldOrders(listFilters),
    staleTime: 60_000,
  });

  const ordersFiltered = useMemo(() => {
    let list = ordersRaw;
    if (
      reportType === "main" ||
      reportType === "products" ||
      reportType === "by-employee"
    ) {
      list = applyBranchFilter(list, branchId);
    }
    return list;
  }, [ordersRaw, branchId, reportType]);

  const summary = useMemo(() => {
    const list = ordersFiltered;
    const total_sales = list.reduce((s, o) => s + orderTotalAmount(o), 0);
    const invoices_count = list.length;
    const average_invoice_value =
      invoices_count > 0 ? total_sales / invoices_count : 0;
    return { total_sales, invoices_count, average_invoice_value };
  }, [ordersFiltered]);

  const formatMoney = (n: number) =>
    n.toLocaleString("en-US", { minimumFractionDigits: 2 });

  const formatDateStr = (s: string) => {
    try {
      const d = new Date(s);
      return isNaN(d.getTime()) ? s : d.toLocaleDateString("ar-EG");
    } catch {
      return s;
    }
  };

  const handleBackToReports = () => navigate("/sales/reports");

  const cards = [
    {
      key: "total_sales",
      label: "إجمالي المبيعات",
      value: formatMoney(summary.total_sales) + " ج.م",
      icon: DollarSign,
      bgClass: "bg-amber-50",
      iconClass: "text-amber-600",
    },
    {
      key: "invoices_count",
      label: "عدد الطلبات (بيع)",
      value: summary.invoices_count,
      icon: FileText,
      bgClass: "bg-blue-500/10",
      iconClass: "text-blue-600",
    },
    {
      key: "average",
      label: "متوسط قيمة الطلب",
      value: formatMoney(summary.average_invoice_value) + " ج.م",
      icon: TrendingUp,
      bgClass: "bg-emerald-500/10",
      iconClass: "text-emerald-600",
    },
  ];

  const reportTitles: Record<Exclude<ReportType, "main">, string> = {
    daily: "المبيعات اليومية",
    products: "المنتجات الأكثر مبيعاً",
    "by-employee": "المبيعات حسب الموظف",
  };

  const reportDescriptions: Record<Exclude<ReportType, "main">, string> = {
    daily: "تفصيل المبيعات حسب اليوم",
    products: "ترتيب المنتجات حسب المبيعات",
    "by-employee": "تفصيل المبيعات لكل موظف",
  };

  const renderFilters = (type: ReportType) => {
    if (type === "daily") {
      return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className={saleLabelClass}>من تاريخ</label>
            <input
              type="date"
              className={saleDateInputClass}
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
            />
          </div>
          <div>
            <label className={saleLabelClass}>إلى تاريخ</label>
            <input
              type="date"
              className={saleDateInputClass}
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
            />
          </div>
        </div>
      );
    }

    if (type === "products") {
      return (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div>
            <label className={saleLabelClass}>من تاريخ</label>
            <input
              type="date"
              className={saleDateInputClass}
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
            />
          </div>
          <div>
            <label className={saleLabelClass}>إلى تاريخ</label>
            <input
              type="date"
              className={saleDateInputClass}
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
            />
          </div>
          <div>
            <label className={saleLabelClass}>الفرع</label>
            <BranchesSelect
              value={branchId}
              onChange={setBranchId}
              className={saleProjectSelectTriggerClass}
            />
          </div>
        </div>
      );
    }

    if (type === "by-employee") {
      return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <label className={saleLabelClass}>من تاريخ</label>
            <input
              type="date"
              className={saleDateInputClass}
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
            />
          </div>
          <div>
            <label className={saleLabelClass}>إلى تاريخ</label>
            <input
              type="date"
              className={saleDateInputClass}
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
            />
          </div>
          <div>
            <label className={saleLabelClass}>الفرع</label>
            <BranchesSelect
              value={branchId}
              onChange={setBranchId}
              className={saleProjectSelectTriggerClass}
            />
          </div>
          <div>
            <label className={saleLabelClass}>الموظف</label>
            <EmployeesSelect
              params={{ per_page: 20 }}
              value={employeeId}
              onChange={setEmployeeId}
              className={saleProjectSelectTriggerClass}
            />
          </div>
        </div>
      );
    }

    return (
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <label className={saleLabelClass}>من تاريخ</label>
          <input
            type="date"
            className={saleDateInputClass}
            value={dateFrom}
            onChange={(e) => setDateFrom(e.target.value)}
          />
        </div>
        <div>
          <label className={saleLabelClass}>إلى تاريخ</label>
          <input
            type="date"
            className={saleDateInputClass}
            value={dateTo}
            onChange={(e) => setDateTo(e.target.value)}
          />
        </div>
        <div>
          <label className={saleLabelClass}>الفرع</label>
          <BranchesSelect
            value={branchId}
            onChange={setBranchId}
            className={saleProjectSelectTriggerClass}
            placeholder="جميع الفروع"
          />
        </div>
        <div>
          <label className={saleLabelClass}>الموظف</label>
          <EmployeesSelect
            params={{ per_page: 20 }}
            value={employeeId}
            onChange={setEmployeeId}
            className={saleProjectSelectTriggerClass}
          />
        </div>
      </div>
    );
  };

  const renderTable = (
    headers: string[],
    rows: Array<Array<string | number>>,
    emptyMessage = "لا توجد بيانات"
  ) => (
    <div className="overflow-hidden rounded-lg border border-slate-100">
      <div
        className="grid bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-400"
        style={{ gridTemplateColumns: `repeat(${headers.length}, minmax(0, 1fr))` }}
      >
        {headers.map((h) => (
          <span key={h} className="text-center">
            {h}
          </span>
        ))}
      </div>
      {rows.length === 0 ? (
        <div className="py-12 text-center text-sm text-slate-400">{emptyMessage}</div>
      ) : (
        rows.map((r, idx) => (
          <div
            key={`${idx}-${String(r[0])}`}
            className="grid items-center border-t border-slate-50 px-3 py-2.5 text-sm"
            style={{ gridTemplateColumns: `repeat(${headers.length}, minmax(0, 1fr))` }}
          >
            {r.map((cell, cIdx) => (
              <span
                key={cIdx}
                className={`text-center ${cIdx === headers.length - 1 ? "font-semibold text-slate-800" : "text-slate-700"}`}
              >
                {cell}
              </span>
            ))}
          </div>
        ))
      )}
    </div>
  );

  if (reportType !== "main") {
    const orders = ordersFiltered;
    const dailyGrouped = orders.reduce(
      (acc, inv) => {
        const date = inv.created_at?.split("T")[0] ?? "غير معروف";
        if (!acc[date]) acc[date] = { total: 0, count: 0 };
        acc[date].total += orderTotalAmount(inv);
        acc[date].count += 1;
        return acc;
      },
      {} as Record<string, { total: number; count: number }>
    );
    const productMap = orders.reduce(
      (acc, inv) => {
        (inv.items ?? []).forEach((item) => {
          const key = item.code || `i-${item.id}`;
          if (!acc[key]) acc[key] = { qty: 0, revenue: 0 };
          acc[key].qty += item.quantity;
          acc[key].revenue += lineItemRevenue(item);
        });
        return acc;
      },
      {} as Record<string, { qty: number; revenue: number }>
    );
    const employeeMap = orders.reduce(
      (acc, inv) => {
        const key = employeeLabel(inv);
        if (!acc[key]) acc[key] = { total: 0, count: 0 };
        acc[key].total += orderTotalAmount(inv);
        acc[key].count += 1;
        return acc;
      },
      {} as Record<string, { total: number; count: number }>
    );

    const dailyEntries = Object.entries(dailyGrouped).sort(([a], [b]) =>
      b.localeCompare(a)
    );
    const productEntries = Object.entries(productMap).sort(
      ([, a], [, b]) => b.revenue - a.revenue
    );
    const employeeEntries = Object.entries(employeeMap).sort(
      ([, a], [, b]) => b.total - a.total
    );

    const rowsDaily = dailyEntries.map(([date, { total, count }]) => [
      formatDateStr(date),
      count,
      `${formatMoney(total)} ج.م`,
    ]);
    const rowsProducts = productEntries.map(([code, { qty, revenue }]) => [
      code,
      qty,
      `${formatMoney(revenue)} ج.م`,
    ]);
    const rowsEmployees = employeeEntries.map(([name, { total, count }]) => [
      name,
      count,
      `${formatMoney(total)} ج.م`,
    ]);

    return (
      <div dir="rtl" className="min-h-screen bg-slate-50 p-6">
        <div className="space-y-5">
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={handleBackToReports}
              className="flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-50"
            >
              <i className="ri-arrow-right-line" />
            </button>
            <div>
              <h1 className="text-xl font-bold text-slate-800">{reportTitles[reportType]}</h1>
              <p className="text-xs text-slate-400">{reportDescriptions[reportType]}</p>
            </div>
          </div>

          <div className={saleSectionCardClass}>
            <h2 className={saleSectionTitleClass}>
              <i className="ri-filter-3-line text-indigo-500" /> الفلاتر
            </h2>
            {renderFilters(reportType)}
          </div>

          <div className={saleSectionCardClass}>
            <h2 className={saleSectionTitleClass}>
              <i className="ri-table-2 text-indigo-500" /> النتائج
            </h2>
            {isPending ? (
              <div className="space-y-2">
                <div className="h-10 animate-pulse rounded-lg bg-slate-100" />
                <div className="h-10 animate-pulse rounded-lg bg-slate-100" />
                <div className="h-10 animate-pulse rounded-lg bg-slate-100" />
              </div>
            ) : reportType === "daily" ? (
              renderTable(["التاريخ", "عدد الطلبات", "الإجمالي"], rowsDaily)
            ) : reportType === "products" ? (
              renderTable(["كود المنتج", "الكمية المباعة", "إجمالي المبيعات"], rowsProducts)
            ) : (
              renderTable(["الموظف", "عدد الطلبات", "إجمالي المبيعات"], rowsEmployees)
            )}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div dir="rtl" className="min-h-screen bg-slate-50 p-6">
      <div className="space-y-5">
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() => navigate("/sales")}
            className="flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-50"
          >
            <i className="ri-arrow-right-line" />
          </button>
          <div>
            <h1 className="text-xl font-bold text-slate-800">تقارير المبيعات</h1>
            <p className="text-xs text-slate-400">
              إجمالي مبيعات الطلبات، متوسط الفاتورة، وتقارير تفصيلية
            </p>
          </div>
        </div>

        <div className={saleSectionCardClass}>
          <h2 className={saleSectionTitleClass}>
            <i className="ri-filter-3-line text-indigo-500" /> فلاتر التقرير العام
          </h2>
          {renderFilters("main")}
        </div>

        <div className="grid gap-4 sm:grid-cols-3">
          {cards.map((c) => (
            <div key={c.key} className={saleSectionCardClass}>
              <div className="mb-3 flex items-center justify-between">
                <span className="text-sm font-medium text-slate-500">{c.label}</span>
                <span
                  className={`flex h-9 w-9 items-center justify-center rounded-lg ${c.bgClass} ${c.iconClass}`}
                >
                  <c.icon className="h-5 w-5" />
                </span>
              </div>
              <div className="text-2xl font-bold text-slate-800">{c.value}</div>
            </div>
          ))}
        </div>

        <div className={saleSectionCardClass}>
          <h2 className={saleSectionTitleClass}>
            <i className="ri-file-chart-line text-indigo-500" /> تقارير إضافية
          </h2>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <button
              type="button"
              className="rounded-xl border border-slate-200 bg-white p-4 text-right transition-colors hover:bg-slate-50"
              onClick={() => navigate("/sales/reports/daily")}
            >
              <p className="font-semibold text-slate-800">المبيعات اليومية</p>
              <p className="mt-1 text-sm text-slate-500">تفصيل المبيعات حسب اليوم</p>
            </button>
            <button
              type="button"
              className="rounded-xl border border-slate-200 bg-white p-4 text-right transition-colors hover:bg-slate-50"
              onClick={() => navigate("/sales/reports/products")}
            >
              <p className="font-semibold text-slate-800">المنتجات الأكثر مبيعاً</p>
              <p className="mt-1 text-sm text-slate-500">ترتيب المنتجات حسب المبيعات</p>
            </button>
            <button
              type="button"
              className="rounded-xl border border-slate-200 bg-white p-4 text-right transition-colors hover:bg-slate-50"
              onClick={() => navigate("/sales/reports/by-employee")}
            >
              <p className="font-semibold text-slate-800">المبيعات حسب الموظف</p>
              <p className="mt-1 text-sm text-slate-500">تفصيل المبيعات لكل موظف</p>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
