import { useState } from "react";
import { useNavigate } from "react-router";
import { useQuery } from "@tanstack/react-query";
import { useGetBranchesQueryOptions } from "@/api/v2/branches/branches.hooks";
import { useGetCashboxesQueryOptions } from "@/api/v2/cashboxes/cashboxes.hooks";
import { Input } from "@/components/ui/input";
import CustomPagination from "@/components/custom/CustomPagination";

const statusColors: Record<string, string> = {
  active: "bg-emerald-50 text-emerald-700 border-emerald-200",
  closed: "bg-red-50 text-red-700 border-red-200",
  pending: "bg-amber-50 text-amber-700 border-amber-200",
};

function normalizeStatus(status?: string | null): string {
  if (!status) return "active";
  const s = status.toLowerCase();
  if (s.includes("close") || s.includes("مغلق")) return "closed";
  if (s.includes("pending") || s.includes("قيد")) return "pending";
  return "active";
}

function statusLabel(status?: string | null): string {
  const s = normalizeStatus(status);
  return s === "active" ? "نشط" : s === "closed" ? "مغلق" : "قيد الإنشاء";
}

const formatMoney = (v: number | string | null | undefined): string => {
  if (v === null || v === undefined) return "—";
  const n = typeof v === "number" ? v : Number(v);
  if (Number.isNaN(n)) return "—";
  return n.toLocaleString("ar-EG", { minimumFractionDigits: 2 });
};

export default function BranchReportsPage() {
  const navigate = useNavigate();
  const [search, setSearch] = useState("");
  const [page] = useState(1);
  const perPage = 12;

  const { data: branchesData, isPending: branchesLoading } = useQuery(
    useGetBranchesQueryOptions(page, perPage)
  );

  const { data: cashboxesData, isPending: cashboxesLoading } = useQuery(
    useGetCashboxesQueryOptions({ per_page: 100 })
  );

  const branches = branchesData?.data ?? [];
  const allCashboxes = cashboxesData?.data ?? [];

  // Filter branches
  const filtered = search.trim()
    ? branches.filter(
        (b) =>
          b.name.toLowerCase().includes(search.toLowerCase()) ||
          b.branch_code.toLowerCase().includes(search.toLowerCase()) ||
          (b.phone ?? "").includes(search)
      )
    : branches;

  // Build stats per branch
  const branchStats = (branchId: number) => {
    const boxes = allCashboxes.filter((c) => c.branch_id === branchId);
    const totalBalance = boxes.reduce((s, c) => s + Number(c.current_balance ?? 0), 0);
    const todayIncome = boxes.reduce((s, c) => s + Number(c.today_income ?? 0), 0);
    const todayExpense = boxes.reduce((s, c) => s + Number(c.today_expense ?? 0), 0);
    return { boxCount: boxes.length, totalBalance, todayIncome, todayExpense };
  };

  return (
    <div dir="rtl" className="space-y-5">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-800">تقارير الفروع</h1>
          <p className="text-sm text-slate-500 mt-0.5">
            عرض جميع الفروع مع حساباتها الصندوقية والتقارير المالية
          </p>
        </div>
        <div className="relative w-full max-w-xs">
          <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <Input
            placeholder="البحث في الفروع..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pr-9"
          />
        </div>
      </div>

      {/* Branches Grid */}
      {branchesLoading || cashboxesLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="rounded-xl border border-gray-200 p-5 animate-pulse">
              <div className="h-4 bg-gray-200 rounded w-3/4 mb-3" />
              <div className="h-3 bg-gray-100 rounded w-1/2 mb-2" />
              <div className="h-8 bg-gray-100 rounded w-full mt-4" />
            </div>
          ))}
        </div>
      ) : filtered.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-20 text-center">
          <div className="w-16 h-16 flex items-center justify-center bg-gray-100 rounded-2xl mb-4">
            <i className="ri-building-line text-3xl text-gray-400" />
          </div>
          <p className="text-base font-semibold text-gray-500">لا توجد فروع</p>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filtered.map((branch) => {
              const stats = branchStats(branch.id);
              const st = normalizeStatus(branch.status);
              return (
                <div
                  key={branch.id}
                  onClick={() => navigate(`/branch-reports/${branch.id}`)}
                  className="rounded-xl border border-gray-200 bg-white p-5 cursor-pointer hover:border-blue-300 hover:shadow-md transition-all group"
                >
                  {/* Header */}
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center gap-3">
                      <div className="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center">
                        <i className="ri-building-2-line text-xl text-blue-600" />
                      </div>
                      <div>
                        <h3 className="font-bold text-slate-800 text-sm">{branch.name}</h3>
                        <p className="text-xs text-slate-400 font-mono">#{branch.branch_code}</p>
                      </div>
                    </div>
                    <span className={`text-xs font-medium px-2 py-0.5 rounded-full border ${statusColors[st]}`}>
                      {statusLabel(branch.status)}
                    </span>
                  </div>

                  {/* Stats Row */}
                  <div className="grid grid-cols-3 gap-2 mb-4">
                    <div className="bg-gray-50 rounded-lg p-2 text-center">
                      <p className="text-xs text-gray-400">الصناديق</p>
                      <p className="text-lg font-bold text-slate-700">{stats.boxCount}</p>
                    </div>
                    <div className="bg-gray-50 rounded-lg p-2 text-center">
                      <p className="text-xs text-gray-400">الرصيد</p>
                      <p className="text-sm font-bold text-blue-600">{formatMoney(stats.totalBalance)}</p>
                    </div>
                    <div className="bg-gray-50 rounded-lg p-2 text-center">
                      <p className="text-xs text-gray-400">حركات اليوم</p>
                      <p className="text-sm font-bold text-emerald-600">+{formatMoney(stats.todayIncome)}</p>
                    </div>
                  </div>

                  {/* Footer */}
                  <div className="flex items-center justify-between pt-3 border-t border-gray-100">
                    <span className="text-xs text-gray-400">
                      {branch.phone ?? "لا يوجد هاتف"}
                    </span>
                    <span className="text-xs text-blue-600 font-medium group-hover:underline">
                      عرض التفاصيل ←
                    </span>
                  </div>
                </div>
              );
            })}
          </div>

          <CustomPagination
            totalElements={branchesData?.total ?? 0}
            totalPages={branchesData?.total_pages ?? 1}
            totalElementsLabel="إجمالي الفروع"
            isLoading={branchesLoading}
          />
        </>
      )}
    </div>
  );
}
