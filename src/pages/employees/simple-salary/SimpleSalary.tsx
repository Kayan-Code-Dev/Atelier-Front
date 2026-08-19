import { Fragment, useMemo, useState } from "react";
import { useQueries, useQuery, useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "react-router";
import { useGetEmployeesQueryOptions } from "@/api/v2/employees/employees.hooks";
import type { TEmployee, TGetEmployeesParams } from "@/api/v2/employees/employees.types";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { SIMPLE_SALARY_KEY } from "@/api/simple-salary/simple-salary.hooks";
import { getSimpleSalarySummary } from "@/api/simple-salary/simple-salary.service";
import type { TSimpleSalarySummary } from "@/api/simple-salary/simple-salary.types";
import useDebounce from "@/hooks/useDebounce";
import { getDefaultPeriod, PERIOD_REGEX } from "./constants";
import { periodToArabicLabel } from "./monthLabel";
import { SalaryStatsGrid } from "./components/SalaryStatsGrid";
import { SimpleSalaryAdjustmentModal } from "./modals/SimpleSalaryAdjustmentModal";
import { SalariesBulkPrintModal, type SalariesBulkPrintRow } from "./modals/SalariesBulkPrintModal";
import { SimpleSalaryPrintDateRangeModal } from "./modals/SimpleSalaryPrintDateRangeModal";
import { SimpleSalaryPrintModal } from "./modals/SimpleSalaryPrintModal";
import { PaySimpleSalaryModal } from "./modals/PaySimpleSalaryModal";

const PER_PAGE = 15;

type StatusFilter = "all" | "paid" | "unpaid";

function fmt(n: number) {
  return n.toLocaleString("ar-SA", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}

function allowancesTotal(emp: TEmployee): number {
  return (
    emp.housing_allowance + emp.transport_allowance + emp.other_allowances
  );
}

function branchLabel(emp: TEmployee): string {
  return emp.branches?.[0]?.name ?? "—";
}

export default function SimpleSalary() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [branchId, setBranchId] = useState("");
  const [search, setSearch] = useState("");
  const [period, setPeriod] = useState(getDefaultPeriod);
  const [statusFilter, setStatusFilter] = useState<StatusFilter>("all");
  const [expandedId, setExpandedId] = useState<number | null>(null);

  const [adjustmentOpen, setAdjustmentOpen] = useState(false);
  const [adjustmentEmployeeId, setAdjustmentEmployeeId] = useState<number | undefined>();
  const [bulkPrintOpen, setBulkPrintOpen] = useState(false);
  const [printEmployee, setPrintEmployee] = useState<{
    id: number;
    name: string;
  } | null>(null);
  const [printRangeOpen, setPrintRangeOpen] = useState(false);
  const [printSummary, setPrintSummary] = useState<TSimpleSalarySummary | null>(null);
  const [printModalOpen, setPrintModalOpen] = useState(false);
  const [paySummary, setPaySummary] = useState<TSimpleSalarySummary | null>(null);
  const [payOpen, setPayOpen] = useState(false);

  const debouncedSearch = useDebounce({ value: search, delay: 400 });
  const periodValid = PERIOD_REGEX.test(period);
  const monthLabel = periodToArabicLabel(period);

  const params: TGetEmployeesParams = useMemo(
    () => ({
      page,
      per_page: PER_PAGE,
      search: debouncedSearch || undefined,
      branch_id: branchId ? Number(branchId) : undefined,
    }),
    [page, debouncedSearch, branchId]
  );

  const { data: employeesData, isPending, isError, error } = useQuery(
    useGetEmployeesQueryOptions(params)
  );

  const employees = useMemo(() => employeesData?.data ?? [], [employeesData?.data]);
  const totalPages = employeesData?.total_pages ?? 1;
  const totalCount = employeesData?.total ?? 0;

  const summaryQueries = useQueries({
    queries: employees.map((emp) => ({
      queryKey: [SIMPLE_SALARY_KEY, "summary", emp.id, periodValid ? period : null],
      queryFn: () => getSimpleSalarySummary(emp.id, period),
      enabled: periodValid && employees.length > 0,
      staleTime: 1000 * 60 * 2,
    })),
  });

  const rowModels = useMemo(
    () =>
      employees.map((emp, i) => ({
        emp,
        i,
        q: summaryQueries[i],
      })),
    [employees, summaryQueries]
  );

  const filteredRows = useMemo(() => {
    return rowModels.filter(({ q }) => {
      const s = q.data;
      if (statusFilter === "all") return true;
      if (q.isPending) return true;
      if (!s) return statusFilter === "unpaid";
      if (statusFilter === "paid") return s.fully_paid;
      return !s.fully_paid;
    });
  }, [rowModels, statusFilter]);

  const stats = useMemo(() => {
    let totalNet = 0;
    let totalBonuses = 0;
    let totalDeductions = 0;
    let paidCount = 0;
    let unpaidCount = 0;
    rowModels.forEach(({ q }) => {
      const s = q.data;
      if (!s) return;
      totalNet += s.net_to_pay;
      totalBonuses += s.total_additions ?? 0;
      totalDeductions += s.total_deductions;
      if (s.fully_paid) paidCount += 1;
      else unpaidCount += 1;
    });
    return {
      totalNet,
      totalBonuses,
      totalDeductions,
      totalOvertime: 0,
      paidCount,
      unpaidCount,
    };
  }, [rowModels]);

  const bulkPrintRows: SalariesBulkPrintRow[] = useMemo(() => {
    return filteredRows.map(({ emp, q }) => {
      const s = q.data;
      const all = allowancesTotal(emp);
      return {
      name: emp.user?.name ?? emp.employee_code,
      code: emp.employee_code,
        roleLabel: emp.job_title?.name ?? "—",
        branchLabel: branchLabel(emp),
        basic: emp.base_salary,
        allowances: all,
        additions: s?.total_additions ?? 0,
        deductions: s?.total_deductions ?? 0,
        net: s?.net_to_pay ?? emp.base_salary + all,
        paid: s?.fully_paid ?? false,
      };
    });
  }, [filteredRows]);

  const invalidateSummaries = () => {
    void queryClient.invalidateQueries({ queryKey: [SIMPLE_SALARY_KEY] });
  };

  const adjTypeConfig = {
    bonus: { label: "مكافأة", color: "text-emerald-600 bg-emerald-50", icon: "ri-award-line" },
    deduction: {
      label: "خصم",
      color: "text-orange-600 bg-orange-50",
      icon: "ri-subtract-line",
    },
  };

  return (
    <div dir="rtl" className="min-h-screen bg-[#f8f8fb] p-4 sm:p-6">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">كشوفات الرواتب الشهرية</h1>
          <p className="text-sm text-gray-500 mt-1">
            إدارة رواتب الموظفين — المكافآت والخصومات والدفع
          </p>
        </div>
            <div className="flex flex-wrap items-center gap-3">
          <button
            type="button"
            onClick={() => {
              setAdjustmentEmployeeId(undefined);
              setAdjustmentOpen(true);
            }}
            className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 whitespace-nowrap"
          >
            <i className="ri-add-line" /> إضافة تعديل
          </button>
          <button
            type="button"
            onClick={() => setBulkPrintOpen(true)}
            className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-900 whitespace-nowrap"
          >
            <i className="ri-printer-line" /> طباعة الكشف
          </button>
        </div>
      </div>

      <SalaryStatsGrid monthLabel={monthLabel} {...stats} />

      <div className="bg-white rounded-xl border border-gray-100 p-4 mb-5 flex flex-col gap-4 xl:flex-row xl:items-center xl:flex-wrap">
        <div className="flex items-center gap-2">
          <i className="ri-calendar-line text-gray-400" />
          <input
            type="month"
            value={period}
            onChange={(e) => {
              setPeriod(e.target.value);
              setPage(1);
            }}
            className="text-sm font-semibold text-gray-700 bg-transparent border border-gray-200 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-violet-200"
          />
            </div>

        <div className="h-5 w-px bg-gray-200 hidden xl:block" />

        <div className="flex flex-col gap-2 min-w-[10rem] flex-1">
          <span className="text-xs text-gray-500">الفرع</span>
                <BranchesSelect
                  value={branchId}
            onChange={(v) => {
              setBranchId(v);
              setPage(1);
            }}
            className="w-full max-w-xs border-gray-200 rounded-lg"
                />
              </div>

        <div className="relative flex-1 min-w-[12rem]">
          <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
          <input
            type="text"
            placeholder="بحث بالاسم أو الكود أو المسمى..."
                    value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
            className="w-full pr-9 pl-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-200"
                  />
                </div>

        <div className="flex gap-1 bg-gray-100 p-1 rounded-lg self-start">
          {(
            [
              { value: "all" as const, label: "الكل" },
              { value: "unpaid" as const, label: "معلق" },
              { value: "paid" as const, label: "مدفوع" },
            ] as const
          ).map((s) => (
            <button
              key={s.value}
              type="button"
              onClick={() => setStatusFilter(s.value)}
              className={`px-4 py-1.5 rounded-md text-xs font-medium transition-all whitespace-nowrap ${
                statusFilter === s.value
                  ? "bg-white text-gray-800 shadow-sm"
                  : "text-gray-500 hover:text-gray-700"
              }`}
            >
              {s.label}
            </button>
          ))}
              </div>

        <button
          type="button"
          onClick={() => {
            setBranchId("");
            setSearch("");
            setStatusFilter("all");
            setPage(1);
          }}
          className="text-xs text-gray-500 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50 self-start"
        >
                مسح الفلاتر
        </button>
      </div>

      {!periodValid ? (
        <div className="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm mb-5">
          اختر شهراً صالحاً (YYYY-MM) لعرض ملخصات الرواتب.
        </div>
      ) : null}

      {isError ? (
        <div className="rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm mb-5">
          {error?.message ?? "خطأ في التحميل"}
        </div>
      ) : null}

      <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[1100px]">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-100">
                <th className="text-right px-3 py-3.5 text-xs font-semibold text-gray-500 w-10" />
                <th className="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">
                  الموظف
                </th>
                <th className="text-center px-3 py-3.5 text-xs font-semibold text-gray-500">
                  الأساسي
                </th>
                <th className="text-center px-3 py-3.5 text-xs font-semibold text-gray-500">
                  البدلات
                </th>
                <th className="text-center px-3 py-3.5 text-xs font-semibold text-gray-500">
                  إضافي
                </th>
                <th className="text-center px-3 py-3.5 text-xs font-semibold text-emerald-600">
                  مكافآت
                </th>
                <th className="text-center px-3 py-3.5 text-xs font-semibold text-orange-500">
                  خصومات
                </th>
                <th className="text-center px-3 py-3.5 text-xs font-semibold text-red-500">
                  جزاءات
                </th>
                <th className="text-center px-3 py-3.5 text-xs font-semibold text-gray-800">
                  الصافي
                </th>
                <th className="text-center px-3 py-3.5 text-xs font-semibold text-gray-500">
                  الحالة
                </th>
                <th className="text-center px-3 py-3.5 text-xs font-semibold text-gray-500">
                  إجراءات
                </th>
              </tr>
            </thead>
            <tbody>
              {isPending ? (
                <tr>
                  <td colSpan={11} className="py-16 text-center text-gray-500 text-sm">
                    جاري التحميل...
                  </td>
                </tr>
              ) : filteredRows.length === 0 ? (
                <tr>
                  <td colSpan={11} className="py-16 text-center">
                    <div className="w-14 h-14 flex items-center justify-center mx-auto rounded-full bg-gray-100 mb-3">
                      <i className="ri-search-line text-gray-400 text-2xl" />
                    </div>
                    <p className="text-sm text-gray-500">لا توجد نتائج مطابقة</p>
                  </td>
                </tr>
              ) : (
                filteredRows.map(({ emp, q }) => {
                  const s = q.data;
                  const all = allowancesTotal(emp);
                  const overtime = 0;
                  const additions = s?.total_additions ?? 0;
                  const deductions = s?.total_deductions ?? 0;
                  const net = s?.net_to_pay ?? null;
                  const paid = s?.fully_paid ?? false;
                  const loading = q.isPending;
                  const additionsList = s?.additions ?? [];
                  const hasAdj = additionsList.length > 0 || (s?.deductions?.length ?? 0) > 0;
                  const isExpanded = expandedId === emp.id;

                  return (
                    <Fragment key={emp.id}>
                      <tr
                        className={`border-b border-gray-50 hover:bg-gray-50/50 transition-colors ${
                          isExpanded ? "bg-violet-50/30" : ""
                        }`}
                      >
                        <td className="px-3 py-4">
                          {hasAdj ? (
                            <button
                              type="button"
                              onClick={() => setExpandedId(isExpanded ? null : emp.id)}
                              className="w-6 h-6 flex items-center justify-center rounded-full hover:bg-gray-200 text-gray-400 transition-colors"
                              aria-expanded={isExpanded}
                            >
                              <i
                                className={`${
                                  isExpanded ? "ri-arrow-up-s-line" : "ri-arrow-down-s-line"
                                } text-sm`}
                              />
                            </button>
                          ) : null}
                        </td>
                        <td className="px-4 py-4">
                          <div className="flex items-center gap-3">
                            <div className="w-9 h-9 flex items-center justify-center rounded-full bg-violet-100 text-violet-600 font-bold text-sm shrink-0">
                              {(emp.user?.name ?? "?").charAt(0)}
                </div>
                            <div className="min-w-0">
                              <p className="text-sm font-semibold text-gray-800 truncate">
                                {emp.user?.name ?? emp.employee_code}
                              </p>
                              <p className="text-xs text-gray-400 truncate">
                                {emp.job_title?.name ?? "—"} · {branchLabel(emp)}
                              </p>
                            </div>
              </div>
                        </td>
                        <td className="px-3 py-4 text-center text-sm text-gray-700 tabular-nums">
                          {fmt(emp.base_salary)}
                        </td>
                        <td className="px-3 py-4 text-center text-sm text-gray-600 tabular-nums">
                          {fmt(all)}
                        </td>
                        <td className="px-3 py-4 text-center text-sm text-violet-600 tabular-nums">
                          {overtime > 0 ? fmt(overtime) : <span className="text-gray-300">—</span>}
                        </td>
                        <td className="px-3 py-4 text-center">
                          {loading ? (
                            <span className="text-gray-300 text-sm">…</span>
                          ) : additions > 0 ? (
                            <span className="text-emerald-600 font-semibold text-sm tabular-nums">
                              +{fmt(additions)}
                            </span>
                          ) : (
                            <span className="text-gray-300 text-sm">—</span>
                          )}
                        </td>
                        <td className="px-3 py-4 text-center">
                          {loading ? (
                            <span className="text-gray-300 text-sm">…</span>
                          ) : deductions > 0 ? (
                            <span className="text-orange-500 font-semibold text-sm tabular-nums">
                              -{fmt(deductions)}
                            </span>
                          ) : (
                            <span className="text-gray-300 text-sm">—</span>
                          )}
                        </td>
                        <td className="px-3 py-4 text-center">
                          <span className="text-gray-300 text-sm">—</span>
                        </td>
                        <td className="px-3 py-4 text-center">
                          {loading || net == null ? (
                            <span className="text-gray-400 text-sm">…</span>
                          ) : (
                            <>
                              <span className="text-base font-bold text-gray-800 tabular-nums">
                                {fmt(net)}
                              </span>
                              <span className="text-xs text-gray-400 mr-1">ج.م</span>
                            </>
                          )}
                        </td>
                        <td className="px-3 py-4 text-center">
                          {loading ? (
                            <span className="text-gray-300 text-xs">…</span>
                          ) : (
                            <span
                              className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap ${
                                paid
                                  ? "bg-emerald-50 text-emerald-600"
                                  : "bg-amber-50 text-amber-600"
                              }`}
                            >
                              <span
                                className={`w-1.5 h-1.5 rounded-full ${
                                  paid ? "bg-emerald-500" : "bg-amber-500"
                                }`}
                              />
                              {paid ? "مدفوع" : "معلق"}
                            </span>
                          )}
                        </td>
                        <td className="px-3 py-4">
                          <div className="flex items-center justify-center gap-1 flex-wrap">
                            <button
                              type="button"
                              title="عرض تفاصيل الراتب"
                              onClick={() => navigate(`/employees/salary-detail/${emp.id}`)}
                              className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-indigo-100 text-indigo-400 transition-colors"
                            >
                              <i className="ri-eye-line text-base" />
                            </button>
                            <button
                              type="button"
                              title="إضافة تعديل"
                              onClick={() => {
                                setAdjustmentEmployeeId(emp.id);
                                setAdjustmentOpen(true);
                              }}
                              className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-violet-100 text-violet-500 transition-colors"
                            >
                              <i className="ri-add-circle-line text-base" />
                            </button>
                            {!loading && s && !paid && (s.remaining_to_pay ?? 0) > 0 ? (
                              <button
                                  type="button"
                                title="تسجيل دفعة"
                                onClick={() => {
                                  setPaySummary(s);
                                  setPayOpen(true);
                                }}
                                className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-emerald-100 text-emerald-500 transition-colors"
                              >
                                <i className="ri-checkbox-circle-line text-base" />
                              </button>
                            ) : null}
                            <button
                                  type="button"
                              title="طباعة"
                              onClick={() => {
                                setPrintEmployee({
                                  id: emp.id,
                                  name: emp.user?.name ?? emp.employee_code,
                                });
                                setPrintRangeOpen(true);
                              }}
                              className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 transition-colors"
                            >
                              <i className="ri-printer-line text-base" />
                            </button>
                          </div>
                        </td>
                      </tr>

                      {isExpanded && s && hasAdj ? (
                        <tr className="bg-violet-50/20">
                          <td colSpan={11} className="px-6 sm:px-8 pb-4 pt-2">
                            <div className="border border-violet-100 rounded-xl bg-white p-4">
                              <p className="text-xs font-bold text-gray-600 mb-3 flex items-center gap-2">
                                <i className="ri-list-check-2 text-violet-500" />
                                تفاصيل المكافآت والخصومات — {monthLabel}
                              </p>
                              <div className="space-y-2">
                                {additionsList.map((a) => {
                                  const cfg = adjTypeConfig.bonus;
                                  return (
                                    <div
                                      key={`a-${a.id}`}
                                      className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 py-2 px-3 rounded-lg bg-gray-50"
                                    >
                                      <div className="flex items-center gap-3 flex-wrap">
                                        <span
                                          className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${cfg.color}`}
                                        >
                                          <i className={cfg.icon} /> {cfg.label}
                                        </span>
                                        <span className="text-sm text-gray-700">{a.reason}</span>
                                      </div>
                                      <div className="flex items-center gap-4 flex-wrap">
                                        <span className="text-sm font-bold text-emerald-600 tabular-nums">
                                          +{fmt(a.amount)} ج.م
                                        </span>
                                        <span className="text-xs text-gray-400">{a.date}</span>
                                      </div>
                                    </div>
                                  );
                                })}
                                {(s.deductions ?? []).map((d) => {
                                  const cfg = adjTypeConfig.deduction;
                                  return (
                                    <div
                                      key={`d-${d.id}`}
                                      className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 py-2 px-3 rounded-lg bg-gray-50"
                                    >
                                      <div className="flex items-center gap-3 flex-wrap">
                                        <span
                                          className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${cfg.color}`}
                                        >
                                          <i className={cfg.icon} /> {cfg.label}
                                        </span>
                                        <span className="text-sm text-gray-700">{d.reason}</span>
                                      </div>
                                      <div className="flex items-center gap-4 flex-wrap">
                                        <span className="text-sm font-bold text-red-500 tabular-nums">
                                          -{fmt(d.amount)} ج.م
                                        </span>
                                        <span className="text-xs text-gray-400">{d.date}</span>
                                      </div>
                                    </div>
                                  );
                                })}
                              </div>
                              <button
                                type="button"
                                onClick={() => {
                                  setAdjustmentEmployeeId(emp.id);
                                  setAdjustmentOpen(true);
                                }}
                                className="mt-3 flex items-center gap-1.5 text-xs text-violet-600 hover:text-violet-700 font-medium"
                              >
                                <i className="ri-add-line" /> إضافة تعديل جديد
                              </button>
                            </div>
                          </td>
                        </tr>
                      ) : null}
                    </Fragment>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {filteredRows.length > 0 ? (
          <div className="px-5 py-3 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
            <span className="text-gray-400">{filteredRows.length} موظف في الصفحة</span>
            <div className="flex flex-wrap items-center gap-4">
              <span className="text-gray-500">
                إجمالي الصافي (معروض):{" "}
                <span className="font-bold text-gray-800">
                  {fmt(
                    filteredRows.reduce((sum, { q }) => sum + (q.data?.net_to_pay ?? 0), 0)
                  )}{" "}
                  ج.م
                </span>
              </span>
              <span className="text-emerald-600">
                المكافآت: +
                {fmt(
                  filteredRows.reduce(
                    (sum, { q }) => sum + (q.data?.total_additions ?? 0),
                    0
                  )
                )}{" "}
                ج.م
              </span>
              <span className="text-red-500">
                الخصومات: -
                {fmt(
                  filteredRows.reduce((sum, { q }) => sum + (q.data?.total_deductions ?? 0), 0)
                )}{" "}
                ج.م
              </span>
            </div>
          </div>
        ) : null}
                </div>

      <div className="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-gray-500">
        <span>
                    عرض {(page - 1) * PER_PAGE + 1} – {Math.min(page * PER_PAGE, totalCount)} من {totalCount}
        </span>
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => setPage((p) => Math.max(1, p - 1))}
            disabled={page <= 1}
            className="px-3 py-2 rounded-lg border border-gray-200 text-gray-600 disabled:opacity-40 hover:bg-violet-50"
          >
            <i className="ri-arrow-right-s-line" /> السابق
          </button>
          <span className="px-3 py-2 rounded-lg bg-violet-50 border border-violet-200 text-violet-800 font-semibold min-w-[5rem] text-center">
                          {page} / {totalPages}
                        </span>
          <button
            type="button"
            onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
            disabled={page >= totalPages}
            className="px-3 py-2 rounded-lg border border-gray-200 text-gray-600 disabled:opacity-40 hover:bg-violet-50"
          >
            التالي <i className="ri-arrow-left-s-line" />
          </button>
                </div>
      </div>

      <p className="text-[11px] text-gray-400 mt-3">
        الإحصاءات العلوية والجدول يعكسان صفحة الموظفين الحالية فقط. غيّر الصفحة لمراجعة بقية
        الموظفين.
      </p>

      <SimpleSalaryAdjustmentModal
        open={adjustmentOpen}
        onOpenChange={setAdjustmentOpen}
        period={period}
        defaultEmployeeId={adjustmentEmployeeId}
        onSuccess={invalidateSummaries}
      />

      <SalariesBulkPrintModal
        open={bulkPrintOpen}
        onClose={() => setBulkPrintOpen(false)}
        period={period}
        rows={bulkPrintRows}
      />

      {printEmployee ? (
        <SimpleSalaryPrintDateRangeModal
          open={printRangeOpen}
          onOpenChange={(open) => {
            setPrintRangeOpen(open);
            if (!open) setPrintEmployee(null);
          }}
          employeeId={printEmployee.id}
          employeeName={printEmployee.name}
          period={period}
          onPrintReady={(s) => {
            setPrintSummary(s);
            setPrintModalOpen(true);
          }}
        />
      ) : null}

      <SimpleSalaryPrintModal
        open={printModalOpen}
        onOpenChange={(o) => {
          setPrintModalOpen(o);
          if (!o) setPrintSummary(null);
        }}
        summary={printSummary}
      />

      {paySummary ? (
        <PaySimpleSalaryModal
          open={payOpen}
          onOpenChange={(o) => {
            setPayOpen(o);
            if (!o) setPaySummary(null);
          }}
          summary={paySummary}
          onSuccess={() => {
            invalidateSummaries();
            setPayOpen(false);
            setPaySummary(null);
          }}
        />
      ) : null}
    </div>
  );
}
