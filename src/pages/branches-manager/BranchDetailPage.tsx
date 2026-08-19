import { useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router";
import { useQuery } from "@tanstack/react-query";
import { useGetBranchQueryOptions } from "@/api/v2/branches/branches.hooks";
import { useGetCashboxesQueryOptions } from "@/api/v2/cashboxes/cashboxes.hooks";
import { useGetTransactionsQueryOptions } from "@/api/v2/transactions/transactions.hooks";
import { TransactionLedger } from "../cashboxes/transactions/components/TransactionLedger";
import { TransactionStats } from "../cashboxes/transactions/components/TransactionStats";
import { TransactionFilters } from "../cashboxes/transactions/components/TransactionFilters";
import { useSearchParams } from "react-router";
import CustomPagination from "@/components/custom/CustomPagination";

const formatMoney = (v: number | string | null | undefined): string => {
  if (v === null || v === undefined) return "—";
  const n = typeof v === "number" ? v : Number(v);
  if (Number.isNaN(n)) return "—";
  return n.toLocaleString("ar-EG", { minimumFractionDigits: 2 });
};

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

export default function BranchDetailPage() {
  const { branchId } = useParams<{ branchId: string }>();
  const navigate = useNavigate();
  const id = Number(branchId) || 0;
  const [searchParams, setSearchParams] = useSearchParams();

  const [activeTab, setActiveTab] = useState<"cashboxes" | "transactions">("cashboxes");
  const [selectedCashboxId, setSelectedCashboxId] = useState<number | null>(null);

  // Transaction filters
  const startDate = searchParams.get("start_date") || "";
  const endDate = searchParams.get("end_date") || "";
  const sort = (searchParams.get("sort") as "asc" | "desc") || "desc";
  const typeFilter = searchParams.get("type") || "";
  const expenseCategory = searchParams.get("expense_category") || "";
  const paymentType = searchParams.get("payment_type") || "";

  // Fetch branch
  const { data: branch, isPending: branchLoading } = useQuery(
    useGetBranchQueryOptions(id)
  );

  // Fetch cashboxes for this branch
  const { data: cashboxesData, isPending: cashboxesLoading } = useQuery(
    useGetCashboxesQueryOptions({ branch_id: id, per_page: 100 })
  );

  // Fetch transactions for selected cashbox
  const { data: txData, isPending: txLoading } = useQuery(
    useGetTransactionsQueryOptions({
      cashbox_id: selectedCashboxId ?? undefined,
      start_date: startDate || undefined,
      end_date: endDate || undefined,
      sort,
      type: typeFilter || undefined,
      expense_category: expenseCategory || undefined,
      payment_type: paymentType || undefined,
    })
  );

  const cashboxes = cashboxesData?.data ?? [];
  const txItems = txData?.data ?? [];
  const txTotal = txData?.total ?? 0;
  const txTotalPages = txData?.total_pages ?? 1;

  // Calculate stats
  const totalBalance = cashboxes.reduce((s, c) => s + Number(c.current_balance ?? 0), 0);
  const totalInitial = cashboxes.reduce((s, c) => s + Number(c.initial_balance ?? 0), 0);

  const totalIncome = txItems
    .filter((t: any) => t.type === "income")
    .reduce((s: number, t: any) => s + (typeof t.amount === "number" ? t.amount : Number(t.amount) || 0), 0);
  const totalExpense = txItems
    .filter((t: any) => t.type === "expense")
    .reduce((s: number, t: any) => s + (typeof t.amount === "number" ? t.amount : Number(t.amount) || 0), 0);
  const totalReversalAbs = txItems
    .filter((t: any) => t.type === "reversal")
    .reduce((s: number, t: any) => s + Math.abs(typeof t.amount === "number" ? t.amount : Number(t.amount) || 0), 0);

  const stats = {
    openingBalance: totalInitial,
    totalIncome,
    totalExpense,
    totalReversalAbs,
    closingBalance: totalBalance,
    netPeriod: totalIncome - totalExpense,
  };

  const handleFiltersChange = (updates: Record<string, string>) => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      Object.entries(updates).forEach(([key, value]) => {
        if (value) next.set(key, value);
        else next.delete(key);
      });
      return next;
    });
  };

  const handleResetFilters = () => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      ["start_date", "end_date", "sort", "type", "expense_category", "payment_type", "page"].forEach((k) =>
        next.delete(k)
      );
      return next;
    });
  };

  const expenseCategoryOptions = useMemo(() => [], []);

  if (branchLoading) {
    return (
      <div dir="rtl" className="flex flex-col items-center justify-center py-20">
        <div className="w-8 h-8 border-3 border-blue-600 border-t-transparent rounded-full animate-spin" />
        <p className="text-gray-500 mt-3 text-sm">جاري تحميل بيانات الفرع...</p>
      </div>
    );
  }

  if (!branch) {
    return (
      <div dir="rtl" className="flex flex-col items-center justify-center py-20 text-center">
        <div className="w-16 h-16 flex items-center justify-center bg-gray-100 rounded-2xl mb-4">
          <i className="ri-error-warning-line text-3xl text-gray-400" />
        </div>
        <p className="text-base font-semibold text-gray-500 mb-4">الفرع غير موجود</p>
        <button
          onClick={() => navigate("/branch-reports")}
          className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
        >
          العودة للفروع
        </button>
      </div>
    );
  }

  const st = normalizeStatus(branch.status);

  return (
    <div dir="rtl" className="space-y-5">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-sm text-gray-500">
        <button onClick={() => navigate("/branch-reports")} className="hover:text-blue-600 cursor-pointer">
          الفروع
        </button>
        <i className="ri-arrow-left-s-line text-xs" />
        <span className="text-slate-800 font-medium">{branch.name}</span>
      </div>

      {/* Branch Info Card */}
      <div className="rounded-xl border border-gray-200 bg-white p-5">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center">
              <i className="ri-building-2-line text-2xl text-blue-600" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-slate-800">{branch.name}</h1>
              <div className="flex items-center gap-2 mt-1">
                <span className={`text-xs font-medium px-2 py-0.5 rounded-full border ${statusColors[st]}`}>
                  {statusLabel(branch.status)}
                </span>
                <span className="text-xs text-gray-400 font-mono">#{branch.branch_code}</span>
                {branch.phone && (
                  <span className="text-xs text-gray-400">
                    <i className="ri-phone-line ml-0.5" />
                    {branch.phone}
                  </span>
                )}
              </div>
            </div>
          </div>
          {/* Summary Stats */}
          <div className="flex gap-3">
            <div className="bg-gray-50 rounded-lg px-4 py-2 text-center">
              <p className="text-xs text-gray-400">الصناديق</p>
              <p className="text-lg font-bold text-slate-700">{cashboxes.length}</p>
            </div>
            <div className="bg-blue-50 rounded-lg px-4 py-2 text-center">
              <p className="text-xs text-blue-400">إجمالي الرصيد</p>
              <p className="text-lg font-bold text-blue-700">{formatMoney(totalBalance)} ج.م</p>
            </div>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex items-center gap-1 bg-gray-100 rounded-xl p-1 w-fit">
        <button
          onClick={() => { setActiveTab("cashboxes"); setSelectedCashboxId(null); }}
          className={`px-5 py-2 text-sm rounded-lg transition-all cursor-pointer whitespace-nowrap ${
            activeTab === "cashboxes"
              ? "bg-white text-gray-800 font-semibold shadow-sm"
              : "text-gray-500 hover:text-gray-700"
          }`}
        >
          <i className="ri-wallet-3-line ml-1.5" />
          الصناديق
        </button>
        <button
          onClick={() => setActiveTab("transactions")}
          className={`px-5 py-2 text-sm rounded-lg transition-all cursor-pointer whitespace-nowrap ${
            activeTab === "transactions"
              ? "bg-white text-gray-800 font-semibold shadow-sm"
              : "text-gray-500 hover:text-gray-700"
          }`}
        >
          <i className="ri-book-open-line ml-1.5" />
          كشف المعاملات
        </button>
      </div>

      {/* Cashboxes Tab */}
      {activeTab === "cashboxes" && (
        <div>
          {cashboxesLoading ? (
            <div className="flex items-center justify-center py-10">
              <div className="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
            </div>
          ) : cashboxes.length === 0 ? (
            <div className="flex flex-col items-center py-16 text-center">
              <div className="w-14 h-14 bg-gray-100 rounded-xl flex items-center justify-center mb-3">
                <i className="ri-wallet-3-line text-2xl text-gray-400" />
              </div>
              <p className="text-gray-500 font-medium">لا توجد صناديق لهذا الفرع</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {cashboxes.map((box) => (
                <div
                  key={box.id}
                  className="rounded-xl border border-gray-200 bg-white p-4"
                >
                  <div className="flex items-center justify-between mb-3">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <i className="ri-wallet-3-line text-lg text-emerald-600" />
                      </div>
                      <div>
                        <h3 className="font-bold text-slate-800 text-sm">{box.name}</h3>
                        <span
                          className={`text-xs px-2 py-0.5 rounded-full ${
                            box.is_active
                              ? "bg-emerald-50 text-emerald-600"
                              : "bg-gray-100 text-gray-400"
                          }`}
                        >
                          {box.is_active ? "نشط" : "معطل"}
                        </span>
                      </div>
                    </div>
                    <button
                      onClick={() => { setSelectedCashboxId(box.id); setActiveTab("transactions"); }}
                      className="text-xs text-blue-600 hover:underline cursor-pointer"
                    >
                      عرض المعاملات
                    </button>
                  </div>
                  <div className="grid grid-cols-3 gap-2">
                    <div className="bg-gray-50 rounded-lg p-2 text-center">
                      <p className="text-[10px] text-gray-400">الرصيد الحالي</p>
                      <p className="text-sm font-bold text-blue-700">{formatMoney(box.current_balance)}</p>
                    </div>
                    <div className="bg-gray-50 rounded-lg p-2 text-center">
                      <p className="text-[10px] text-gray-400">رصيد مبدئي</p>
                      <p className="text-sm font-bold text-slate-700">{formatMoney(box.initial_balance)}</p>
                    </div>
                    <div className="bg-gray-50 rounded-lg p-2 text-center">
                      <p className="text-[10px] text-gray-400">وصف</p>
                      <p className="text-xs text-slate-600 truncate">{box.description || "—"}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Transactions Tab */}
      {activeTab === "transactions" && (
        <div className="space-y-4">
          {/* Cashbox selector */}
          {cashboxes.length > 0 && (
            <div className="flex items-center gap-2 flex-wrap">
              <span className="text-sm text-gray-500">اختر الصندوق:</span>
              {cashboxes.map((box) => (
                <button
                  key={box.id}
                  onClick={() => setSelectedCashboxId(box.id)}
                  className={`px-3 py-1.5 text-xs rounded-full transition-all cursor-pointer ${
                    selectedCashboxId === box.id
                      ? "bg-blue-600 text-white font-medium"
                      : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                  }`}
                >
                  {box.name}
                  <span className={`mr-1.5 ${selectedCashboxId === box.id ? 'text-blue-200' : 'text-gray-400'}`}>
                    {formatMoney(box.current_balance)}
                  </span>
                </button>
              ))}
              {selectedCashboxId === null && (
                <span className="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded-full">
                  اختر صندوقاً لعرض معاملاته
                </span>
              )}
            </div>
          )}

          {selectedCashboxId !== null && (
            <>
              <TransactionStats
                stats={stats}
                periodLabel={startDate && endDate ? `${startDate} — ${endDate}` : "كل الفترات"}
                selectedCashboxName={cashboxes.find(c => c.id === selectedCashboxId)?.name ?? ""}
              />

              <TransactionFilters
                startDate={startDate}
                endDate={endDate}
                sort={sort}
                typeFilter={typeFilter}
                expenseCategory={expenseCategory}
                paymentType={paymentType}
                expenseCategoryOptions={expenseCategoryOptions}
                onFiltersChange={handleFiltersChange}
                onReset={handleResetFilters}
              />

              <TransactionLedger
                items={txItems}
                isPending={txLoading}
                isError={false}
                error={null}
                openingBalance={stats.openingBalance}
                totalIncome={stats.totalIncome}
                totalExpense={stats.totalExpense}
                totalReversalAbs={stats.totalReversalAbs}
                closingBalance={stats.closingBalance}
                total={txTotal}
                totalPages={txTotalPages}
                onResetFilters={handleResetFilters}
                onViewPayment={() => {}}
                onViewExpense={() => {}}
                onViewTransaction={() => {}}
              />

              <CustomPagination
                totalElements={txTotal}
                totalPages={txTotalPages}
                totalElementsLabel="إجمالي المعاملات"
                isLoading={txLoading}
              />
            </>
          )}
        </div>
      )}
    </div>
  );
}
