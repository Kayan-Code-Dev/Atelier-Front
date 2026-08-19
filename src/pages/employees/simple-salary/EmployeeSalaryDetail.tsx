import { useMemo, useState } from "react";
import { Link, useNavigate, useParams } from "react-router";
import { useQueryClient, useQueries, useQuery } from "@tanstack/react-query";
import { useGetEmployeeQueryOptions } from "@/api/v2/employees/employees.hooks";
import {
  useGetSimpleSalaryAdditionsQueryOptions,
  useGetSimpleSalaryDeductionsQueryOptions,
  SIMPLE_SALARY_KEY,
} from "@/api/simple-salary/simple-salary.hooks";
import { getSimpleSalarySummary } from "@/api/simple-salary/simple-salary.service";
import type { TSimpleSalarySummary } from "@/api/simple-salary/simple-salary.types";
import { getDefaultPeriod, PERIOD_REGEX } from "./constants";
import { periodToArabicLabel } from "./monthLabel";
import { SimpleSalaryAdjustmentModal } from "./modals/SimpleSalaryAdjustmentModal";
import { SimpleSalaryPrintDateRangeModal } from "./modals/SimpleSalaryPrintDateRangeModal";
import { SimpleSalaryPrintModal } from "./modals/SimpleSalaryPrintModal";
import { EmployeePayrollActionModal } from "./modals/EmployeePayrollActionModal";

function fmt(n: number) {
  return n.toLocaleString("ar-SA", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}

export default function EmployeeSalaryDetail() {
  const queryClient = useQueryClient();
  const { id: idParam } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const employeeId = idParam ? Number(idParam) : NaN;
  const validId = Number.isFinite(employeeId) && employeeId > 0;

  const [selectedYear, setSelectedYear] = useState(() =>
    String(new Date().getFullYear())
  );
  const [adjustmentOpen, setAdjustmentOpen] = useState(false);
  const [payrollOpen, setPayrollOpen] = useState(false);
  const [printRangeOpen, setPrintRangeOpen] = useState(false);
  const [printSummary, setPrintSummary] = useState<TSimpleSalarySummary | null>(null);
  const [printModalOpen, setPrintModalOpen] = useState(false);

  const { data: employee, isPending: empPending, isError: empError } = useQuery({
    ...useGetEmployeeQueryOptions(employeeId),
    enabled: validId,
  });

  const monthsInYear = useMemo(() => {
    return Array.from({ length: 12 }, (_, i) => {
      const m = String(i + 1).padStart(2, "0");
      return `${selectedYear}-${m}`;
    });
  }, [selectedYear]);

  const monthQueries = useQueries({
    queries: monthsInYear.map((m) => ({
      queryKey: [SIMPLE_SALARY_KEY, "summary", employeeId, m],
      queryFn: () => getSimpleSalarySummary(employeeId, m),
      enabled: validId && PERIOD_REGEX.test(m),
      staleTime: 1000 * 60 * 2,
    })),
  });

  const monthData = useMemo(() => {
    return monthsInYear.map((m, i) => ({
      period: m,
      summary: monthQueries[i]?.data ?? null,
      pending: monthQueries[i]?.isPending ?? false,
    }));
  }, [monthsInYear, monthQueries]);

  const yearSalaries = useMemo(
    () => monthData.filter((x) => x.summary != null).map((x) => x.summary!),
    [monthData]
  );

  const maxNet = Math.max(...yearSalaries.map((s) => s.net_to_pay), 1);

  const totalNet = yearSalaries.reduce((s, e) => s + e.net_to_pay, 0);
  const totalBonuses = yearSalaries.reduce((s, e) => s + (e.total_additions ?? 0), 0);
  const totalDeductions = yearSalaries.reduce((s, e) => s + e.total_deductions, 0);
  const paidMonths = yearSalaries.filter((s) => s.fully_paid).length;

  const { data: additionsRes } = useQuery({
    ...useGetSimpleSalaryAdditionsQueryOptions({
      employee_id: employeeId,
      per_page: 200,
    }),
    enabled: validId,
  });
  const { data: deductionsRes } = useQuery({
    ...useGetSimpleSalaryDeductionsQueryOptions({
      employee_id: employeeId,
      per_page: 200,
    }),
    enabled: validId,
  });

  const adjustmentLines = useMemo(() => {
    const adds =
      additionsRes?.data?.map((a) => ({
        id: a.id,
        kind: "bonus" as const,
        date: a.date,
        period: a.period,
        desc: a.reason,
        amount: a.amount,
      })) ?? [];
    const deds =
      deductionsRes?.data?.map((d) => ({
        id: d.id,
        kind: "deduction" as const,
        date: d.date,
        period: d.period,
        desc: d.reason,
        amount: d.amount,
      })) ?? [];
    return [...adds, ...deds].sort((a, b) => b.date.localeCompare(a.date));
  }, [additionsRes?.data, deductionsRes?.data]);

  const fixedTotal = employee
    ? employee.base_salary +
      employee.housing_allowance +
      employee.transport_allowance +
      employee.other_allowances
    : 0;

  const branchName = employee?.branches?.[0]?.name ?? "—";

  if (!validId) {
    return (
      <div className="min-h-screen bg-[#f8f8fb] flex items-center justify-center p-6">
        <p className="text-gray-600">معرّف غير صالح</p>
      </div>
    );
  }

  if (empPending) {
    return (
      <div className="min-h-screen bg-[#f8f8fb] flex items-center justify-center p-6">
        <p className="text-gray-500 text-sm">جاري التحميل...</p>
      </div>
    );
  }

  if (empError || !employee) {
    return (
      <div className="min-h-screen bg-[#f8f8fb] flex items-center justify-center p-6">
        <div className="text-center">
          <div className="w-16 h-16 flex items-center justify-center mx-auto rounded-full bg-gray-100 mb-4">
            <i className="ri-user-unfollow-line text-gray-400 text-2xl" />
          </div>
          <p className="text-gray-600 font-medium">الموظف غير موجود</p>
          <button
            type="button"
            onClick={() => navigate("/employees/salaries")}
            className="mt-4 text-sm text-violet-600 hover:underline"
          >
            العودة لكشوفات الرواتب
          </button>
        </div>
      </div>
    );
  }

  const empName = employee.user?.name ?? employee.employee_code;
  const roleLabel = employee.job_title?.name ?? "—";
  const defaultPeriod = getDefaultPeriod();

  return (
    <div className="min-h-screen bg-[#f8f8fb] p-4 sm:p-6" dir="rtl">
      <div className="flex items-center gap-2 text-sm text-gray-400 mb-5">
        <Link
          to="/employees/salaries"
          className="hover:text-violet-600 transition-colors"
        >
          كشوفات الرواتب
        </Link>
        <i className="ri-arrow-left-s-line" />
        <span className="text-gray-700 font-medium">{empName}</span>
      </div>

      <div className="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 mb-6">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div className="flex items-center gap-5">
            <div className="w-16 h-16 flex items-center justify-center rounded-2xl bg-violet-100 text-violet-600 text-2xl font-bold shrink-0">
              {empName.charAt(0)}
            </div>
            <div>
              <h2 className="text-xl font-bold text-gray-800 mb-1">{empName}</h2>
              <div className="flex flex-wrap items-center gap-2 text-xs text-gray-400">
                <span className="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                  {roleLabel}
                </span>
                <span>
                  <i className="ri-map-pin-line ml-1" />
                  {branchName}
                </span>
                <span>
                  <i className="ri-calendar-line ml-1" />
                  التوظيف: {employee.hire_date}
                </span>
              </div>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <button
              type="button"
              onClick={() => setAdjustmentOpen(true)}
              className="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 whitespace-nowrap"
            >
              <i className="ri-add-circle-line text-violet-500" /> إضافة تعديل
            </button>
            <button
              type="button"
              onClick={() => setPayrollOpen(true)}
              className="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-violet-200 text-sm text-violet-700 bg-violet-50 hover:bg-violet-100 whitespace-nowrap"
            >
              <i className="ri-wallet-3-line" /> كشف الراتب الشهري
            </button>
            <button
              type="button"
              onClick={() => setPrintRangeOpen(true)}
              className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-900 whitespace-nowrap"
            >
              <i className="ri-printer-line" /> طباعة الكشف
            </button>
          </div>
        </div>

        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-5 pt-5 border-t border-gray-100">
          {[
            {
              label: "الراتب الأساسي",
              val: `${fmt(employee.base_salary)} ج.م`,
              icon: "ri-money-dollar-circle-line",
              color: "text-violet-600",
            },
            {
              label: `صافي الرواتب (${selectedYear})`,
              val: `${fmt(totalNet)} ج.م`,
              icon: "ri-wallet-3-line",
              color: "text-emerald-600",
            },
            {
              label: "إجمالي المكافآت",
              val: `${fmt(totalBonuses)} ج.م`,
              icon: "ri-award-line",
              color: "text-amber-500",
            },
            {
              label: "إجمالي الخصومات",
              val: `${fmt(totalDeductions)} ج.م`,
              icon: "ri-subtract-line",
              color: "text-red-500",
            },
          ].map((s) => (
            <div key={s.label} className="text-center p-3 rounded-xl bg-gray-50">
              <i className={`${s.icon} ${s.color} text-xl mb-1 block`} />
              <p className="text-sm font-bold text-gray-800">{s.val}</p>
              <p className="text-xs text-gray-400 mt-0.5">{s.label}</p>
            </div>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div className="xl:col-span-2 space-y-5">
          <div className="bg-white rounded-xl border border-gray-100 p-5">
            <div className="flex flex-wrap items-center justify-between gap-3 mb-4">
              <h3 className="text-sm font-bold text-gray-800">مخطط الرواتب الشهرية</h3>
              <div className="flex items-center gap-2">
                <select
                  value={selectedYear}
                  onChange={(e) => setSelectedYear(e.target.value)}
                  className="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-violet-200 bg-white"
                >
                  {[0, 1, 2].map((dy) => {
                    const y = String(new Date().getFullYear() - dy);
                    return (
                      <option key={y} value={y}>
                        {y}
                      </option>
                    );
                  })}
                </select>
                <span className="text-xs text-gray-400">{paidMonths} شهر مدفوع</span>
              </div>
            </div>

            {yearSalaries.length > 0 ? (
              <div className="flex items-end gap-2 sm:gap-3 h-32">
                {monthData.map(({ period, summary, pending }) => {
                  const net = summary?.net_to_pay ?? 0;
                  const barH = summary ? Math.max((net / maxNet) * 100, 8) : 4;
                  return (
                    <div
                      key={period}
                      className="flex-1 flex flex-col items-center gap-1.5 min-w-0 group"
                    >
                      <span className="text-[10px] text-gray-500 font-medium opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap tabular-nums">
                        {pending ? "…" : summary ? fmt(net) : "—"}
                      </span>
                      <div
                        className={`w-full rounded-t-lg transition-all ${
                          summary?.fully_paid
                            ? "bg-violet-500 group-hover:bg-violet-600"
                            : summary
                              ? "bg-violet-200 group-hover:bg-violet-300"
                              : "bg-gray-100"
                        }`}
                        style={{ height: `${barH}%` }}
                      />
                      <span className="text-[10px] text-gray-400 whitespace-nowrap">
                        {period.slice(5, 7)}
                      </span>
                    </div>
                  );
                })}
              </div>
            ) : (
              <div className="h-32 flex items-center justify-center text-sm text-gray-400">
                لا توجد بيانات ملخص لهذا العام
              </div>
            )}

            <div className="flex items-center gap-4 mt-3 pt-3 border-t border-gray-50">
              <div className="flex items-center gap-1.5">
                <span className="w-3 h-3 rounded-sm bg-violet-500" />
                <span className="text-xs text-gray-500">مدفوع</span>
              </div>
              <div className="flex items-center gap-1.5">
                <span className="w-3 h-3 rounded-sm bg-violet-200" />
                <span className="text-xs text-gray-500">معلق / جزئي</span>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="text-sm font-bold text-gray-800">سجل الرواتب الشهرية</h3>
              <span className="text-xs text-gray-400">{yearSalaries.length} شهر ببيانات</span>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[640px]">
                <thead>
                  <tr className="bg-gray-50 border-b border-gray-100">
                    <th className="text-right px-4 py-3 text-xs font-semibold text-gray-500">
                      الشهر
                    </th>
                    <th className="text-center px-3 py-3 text-xs font-semibold text-gray-500">
                      الأساسي
                    </th>
                    <th className="text-center px-3 py-3 text-xs font-semibold text-emerald-600">
                      مكافآت
                    </th>
                    <th className="text-center px-3 py-3 text-xs font-semibold text-red-500">
                      خصومات
                    </th>
                    <th className="text-center px-3 py-3 text-xs font-semibold text-gray-800">
                      الصافي
                    </th>
                    <th className="text-center px-3 py-3 text-xs font-semibold text-gray-500">
                      الحالة
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {[...monthData]
                    .filter((x) => x.summary)
                    .sort((a, b) => b.period.localeCompare(a.period))
                    .map(({ period, summary }) => {
                      if (!summary) return null;
                      return (
                        <tr
                          key={period}
                          className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
                        >
                          <td className="px-4 py-3">
                            <p className="text-sm font-semibold text-gray-700">
                              {periodToArabicLabel(period)}
                            </p>
                          </td>
                          <td className="px-3 py-3 text-center text-sm text-gray-700 tabular-nums">
                            {fmt(summary.salary)}
                          </td>
                          <td className="px-3 py-3 text-center">
                            {(summary.total_additions ?? 0) > 0 ? (
                              <span className="text-emerald-600 font-semibold text-sm tabular-nums">
                                +{fmt(summary.total_additions ?? 0)}
                              </span>
                            ) : (
                              <span className="text-gray-300 text-sm">—</span>
                            )}
                          </td>
                          <td className="px-3 py-3 text-center">
                            {summary.total_deductions > 0 ? (
                              <span className="text-red-500 font-semibold text-sm tabular-nums">
                                -{fmt(summary.total_deductions)}
                              </span>
                            ) : (
                              <span className="text-gray-300 text-sm">—</span>
                            )}
                          </td>
                          <td className="px-3 py-3 text-center">
                            <span className="text-base font-bold text-gray-800 tabular-nums">
                              {fmt(summary.net_to_pay)}
                            </span>
                            <span className="text-xs text-gray-400 mr-0.5">ج.م</span>
                          </td>
                          <td className="px-3 py-3 text-center">
                            <span
                              className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${
                                summary.fully_paid
                                  ? "bg-emerald-50 text-emerald-600"
                                  : "bg-amber-50 text-amber-600"
                              }`}
                            >
                              <span
                                className={`w-1.5 h-1.5 rounded-full ${
                                  summary.fully_paid ? "bg-emerald-500" : "bg-amber-400"
                                }`}
                              />
                              {summary.fully_paid ? "مدفوع" : "معلق"}
                            </span>
                          </td>
                        </tr>
                      );
                    })}
                </tbody>
              </table>
            </div>
            {yearSalaries.length === 0 ? (
              <div className="text-center py-10 text-sm text-gray-400">
                لا توجد سجلات ملخص لهذا العام
              </div>
            ) : null}
            {yearSalaries.length > 0 ? (
              <div className="px-5 py-3 border-t border-gray-100 bg-gray-50 flex flex-wrap items-center justify-between gap-2 text-xs">
                <span className="font-semibold text-gray-600">إجمالي {selectedYear}</span>
                <div className="flex flex-wrap gap-4">
                  <span className="text-gray-500">
                    الصافي:{" "}
                    <span className="font-bold text-gray-800">{fmt(totalNet)} ج.م</span>
                  </span>
                  <span className="text-emerald-600">
                    مكافآت: +{fmt(totalBonuses)} ج.م
                  </span>
                  <span className="text-red-500">خصومات: -{fmt(totalDeductions)} ج.م</span>
                </div>
              </div>
            ) : null}
          </div>
        </div>

        <div className="space-y-5">
          <div className="bg-white rounded-xl border border-gray-100 p-5">
            <h3 className="text-sm font-bold text-gray-800 mb-4">تفصيل مكونات الراتب</h3>
            <div className="space-y-3">
              {[
                {
                  label: "الراتب الأساسي",
                  val: employee.base_salary,
                  color: "bg-violet-500",
                  pct: 100,
                },
                {
                  label: "بدل السكن",
                  val: employee.housing_allowance,
                  color: "bg-indigo-400",
                  pct: employee.base_salary
                    ? Math.min(100, (employee.housing_allowance / employee.base_salary) * 100)
                    : 0,
                },
                {
                  label: "بدل النقل",
                  val: employee.transport_allowance,
                  color: "bg-sky-400",
                  pct: employee.base_salary
                    ? Math.min(100, (employee.transport_allowance / employee.base_salary) * 100)
                    : 0,
                },
                {
                  label: "بدلات أخرى",
                  val: employee.other_allowances,
                  color: "bg-amber-400",
                  pct: employee.base_salary
                    ? Math.min(100, (employee.other_allowances / employee.base_salary) * 100)
                    : 0,
                },
              ].map((item) => (
                <div key={item.label}>
                  <div className="flex items-center justify-between mb-1">
                    <span className="text-xs text-gray-600">{item.label}</span>
                    <span className="text-xs font-semibold text-gray-800 tabular-nums">
                      {fmt(item.val)} ج.م
                    </span>
                  </div>
                  <div className="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div
                      className={`h-full ${item.color} rounded-full`}
                      style={{ width: `${Math.min(100, item.pct)}%` }}
                    />
                  </div>
                </div>
              ))}
            </div>
            <div className="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
              <span className="text-xs text-gray-500 font-semibold">المجموع الثابت</span>
              <span className="text-sm font-bold text-gray-800 tabular-nums">
                {fmt(fixedTotal)} ج.م
              </span>
            </div>
          </div>

          <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div className="px-4 py-3.5 border-b border-gray-100 flex items-center justify-between">
              <h3 className="text-sm font-bold text-gray-800">سجل التعديلات</h3>
              <span className="text-xs text-gray-400">{adjustmentLines.length} تعديل</span>
            </div>
            <div className="divide-y divide-gray-50 max-h-96 overflow-y-auto">
              {adjustmentLines.map((line) => {
                const cfg =
                  line.kind === "bonus"
                    ? {
                        label: "مكافأة",
                        color: "text-emerald-600 bg-emerald-50",
                        sign: "+",
                        amt: "text-emerald-600",
                      }
                    : {
                        label: "خصم",
                        color: "text-orange-600 bg-orange-50",
                        sign: "-",
                        amt: "text-red-500",
                      };
                return (
                  <div key={`${line.kind}-${line.id}`} className="px-4 py-3 hover:bg-gray-50/50">
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-0.5 flex-wrap">
                          <span
                            className={`text-xs px-2 py-0.5 rounded-full font-semibold ${cfg.color}`}
                          >
                            {cfg.label}
                          </span>
                          <span className="text-xs text-gray-400">
                            {periodToArabicLabel(line.period)}
                          </span>
                        </div>
                        <p className="text-sm text-gray-700 truncate">{line.desc}</p>
                        <p className="text-xs text-gray-400 mt-0.5">{line.date}</p>
                      </div>
                      <span className={`text-sm font-bold shrink-0 tabular-nums ${cfg.amt}`}>
                        {cfg.sign}
                        {fmt(line.amount)} ج.م
                      </span>
                    </div>
                  </div>
                );
              })}
              {adjustmentLines.length === 0 ? (
                <div className="px-4 py-8 text-center text-sm text-gray-400">
                  لا توجد تعديلات مسجلة
                </div>
              ) : null}
            </div>
            <div className="px-4 py-3 border-t border-gray-100 bg-gray-50">
              <button
                type="button"
                onClick={() => setAdjustmentOpen(true)}
                className="w-full flex items-center justify-center gap-2 text-xs text-violet-600 font-medium hover:text-violet-700 py-1"
              >
                <i className="ri-add-circle-line" /> إضافة تعديل جديد
              </button>
            </div>
          </div>
        </div>
      </div>

      <SimpleSalaryAdjustmentModal
        open={adjustmentOpen}
        onOpenChange={setAdjustmentOpen}
        period={defaultPeriod}
        defaultEmployeeId={employeeId}
        onSuccess={() => {
          monthQueries.forEach((q) => void q.refetch());
          void queryClient.invalidateQueries({ queryKey: [SIMPLE_SALARY_KEY, "additions"] });
          void queryClient.invalidateQueries({ queryKey: [SIMPLE_SALARY_KEY, "deductions"] });
        }}
      />

      <EmployeePayrollActionModal
        open={payrollOpen}
        onOpenChange={setPayrollOpen}
        employeeId={employeeId}
        employeeName={empName}
        employeeCode={employee.employee_code}
      />

      <SimpleSalaryPrintDateRangeModal
        open={printRangeOpen}
        onOpenChange={setPrintRangeOpen}
        employeeId={employeeId}
        employeeName={empName}
        period={defaultPeriod}
        onPrintReady={(s) => {
          setPrintSummary(s);
          setPrintModalOpen(true);
        }}
      />

      <SimpleSalaryPrintModal
        open={printModalOpen}
        onOpenChange={(o) => {
          setPrintModalOpen(o);
          if (!o) setPrintSummary(null);
        }}
        summary={printSummary}
      />
    </div>
  );
}
