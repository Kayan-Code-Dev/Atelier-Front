import { useState, useMemo } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Link, useNavigate } from "react-router";
import { toast } from "sonner";
import { EmployeeDetailsModal } from "./EmployeeDetailsModal";
import {
  useGetEmployeesQueryOptions,
  useExportEmployeesToExcelMutationOptions,
} from "@/api/v2/employees/employees.hooks";
import { useGetJobTitlesQueryOptions } from "@/api/v2/content-managment/job-titles/job-titles.hooks";
import type { TEmployee, TGetEmployeesParams } from "@/api/v2/employees/employees.types";
import useDebounce from "@/hooks/useDebounce";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";

const ROLE_BADGE_CLASSES = [
  "bg-purple-50 text-purple-700",
  "bg-blue-50 text-blue-700",
  "bg-teal-50 text-teal-700",
  "bg-emerald-50 text-emerald-700",
  "bg-orange-50 text-orange-700",
  "bg-pink-50 text-pink-700",
  "bg-gray-100 text-gray-700",
];

const STATUS_UI: Record<
  string,
  { label: string; pill: string }
> = {
  active: {
    label: "نشط",
    pill: "bg-emerald-50 text-emerald-700 border-emerald-200",
  },
  on_leave: {
    label: "إجازة",
    pill: "bg-amber-50 text-amber-700 border-amber-200",
  },
  suspended: {
    label: "موقوف",
    pill: "bg-red-50 text-red-700 border-red-200",
  },
  terminated: {
    label: "منتهي",
    pill: "bg-gray-50 text-gray-600 border-gray-200",
  },
};

function primaryBranchName(employee: TEmployee): string {
  const b = employee.branches?.[0];
  return b?.name ?? "—";
}

function Employees() {
  const navigate = useNavigate();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [jobTitleId, setJobTitleId] = useState("");
  const [employmentStatus, setEmploymentStatus] = useState("all");

  const [isDetailsModalOpen, setIsDetailsModalOpen] = useState(false);
  const [selectedEmployee, setSelectedEmployee] = useState<TEmployee | null>(
    null,
  );

  const debouncedSearch = useDebounce({ value: search, delay: 400 });

  const queryParams: TGetEmployeesParams = useMemo(() => {
    const params: TGetEmployeesParams = {
      page,
      per_page: 50,
    };
    if (debouncedSearch.trim()) params.search = debouncedSearch.trim();
    if (jobTitleId) params.job_title_id = Number(jobTitleId);
    if (employmentStatus && employmentStatus !== "all") {
      params.employment_status = employmentStatus as TGetEmployeesParams["employment_status"];
    }
    return params;
  }, [page, debouncedSearch, jobTitleId, employmentStatus]);

  const { data, isPending, isError, error } = useQuery(
    useGetEmployeesQueryOptions(queryParams),
  );

  const { data: jobTitlesData } = useQuery(
    useGetJobTitlesQueryOptions({ page: 1, per_page: 200 }),
  );

  const { mutate: exportEmployeesToExcel, isPending: isExporting } =
    useMutation(useExportEmployeesToExcelMutationOptions());

  const handleExport = () => {
    exportEmployeesToExcel(queryParams, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "employees.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير الموظفين بنجاح");
      },
      onError: (err: { message?: string }) => {
        toast.error("خطأ أثناء تصدير الموظفين", {
          description: err.message,
        });
      },
    });
  };

  const stats = useMemo(() => {
    const rows = data?.data ?? [];
    const total = data?.total ?? 0;
    const activeOnPage = rows.filter((e) => e.employment_status === "active")
      .length;
    const leaveOnPage = rows.filter((e) => e.employment_status === "on_leave")
      .length;
    const salarySumOnPage = rows.reduce((s, e) => s + (e.base_salary ?? 0), 0);
    return { total, activeOnPage, leaveOnPage, salarySumOnPage };
  }, [data]);

  const handleOpenDetails = (employee: TEmployee) => {
    setSelectedEmployee(employee);
    setIsDetailsModalOpen(true);
  };

  const handleFilterChange = () => setPage(1);

  const rows = data?.data ?? [];
  const totalPages = data?.total_pages ?? 1;

  return (
    <div className="p-6 min-h-screen bg-gray-50/50" dir="rtl">
      {/* Header — مطابق project/src/pages/employees/page.tsx */}
      <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
          <h1 className="text-xl font-bold text-gray-900">الموظفون</h1>
          <p className="text-sm text-gray-400 mt-0.5">
            إدارة بيانات الموظفين والصلاحيات
          </p>
        </div>
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={handleExport}
            disabled={isExporting}
            className="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 cursor-pointer transition-colors disabled:opacity-50"
            title="تصدير Excel"
            aria-label="تصدير Excel"
          >
            <i
              className={`ri-file-excel-2-line text-lg ${isExporting ? "animate-pulse" : ""}`}
            />
          </button>
          <button
            type="button"
            onClick={() => navigate("/employees/add")}
            className="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 cursor-pointer transition-colors whitespace-nowrap flex items-center gap-2"
          >
            <div className="w-4 h-4 flex items-center justify-center">
              <i className="ri-user-add-line text-sm" />
            </div>
            موظف جديد
          </button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {[
          {
            label: "إجمالي الموظفين",
            value: stats.total,
            icon: "ri-team-line",
            color: "text-gray-600 bg-gray-100",
          },
          {
            label: "موظفون نشطون",
            value: stats.activeOnPage,
            icon: "ri-user-smile-line",
            color: "text-emerald-600 bg-emerald-100",
          },
          {
            label: "في إجازة",
            value: stats.leaveOnPage,
            icon: "ri-calendar-line",
            color: "text-amber-600 bg-amber-100",
          },
          {
            label: "إجمالي الرواتب",
            value: `${stats.salarySumOnPage.toLocaleString("en-US")} ج.م`,
            icon: "ri-money-dollar-circle-line",
            color: "text-teal-600 bg-teal-100",
          },
        ].map((s, i) => (
          <div
            key={i}
            className="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3"
          >
            <div
              className={`w-10 h-10 flex items-center justify-center rounded-lg ${s.color}`}
            >
              <i className={`${s.icon} text-lg`} />
            </div>
            <div>
              <p className="text-xs text-gray-400">{s.label}</p>
              <p className="text-base font-bold text-gray-800 mt-0.5">
                {s.value}
              </p>
            </div>
          </div>
        ))}
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl border border-gray-100 p-4 mb-5 flex flex-wrap items-center gap-3">
        <div className="relative flex-1 min-w-[200px] max-w-xs">
          <div className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center text-gray-400 pointer-events-none">
            <i className="ri-search-line text-sm" />
          </div>
          <input
            type="text"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              handleFilterChange();
            }}
            placeholder="بحث بالاسم أو البريد أو الهاتف..."
            className="w-full border border-gray-200 rounded-lg pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400"
          />
        </div>
        <select
          value={jobTitleId}
          onChange={(e) => {
            setJobTitleId(e.target.value);
            handleFilterChange();
          }}
          className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-100 min-w-[160px]"
        >
          <option value="">كل الأدوار</option>
          {(jobTitlesData?.data ?? []).map((jt) => (
            <option key={jt.id} value={String(jt.id)}>
              {jt.name}
            </option>
          ))}
        </select>
        <select
          value={employmentStatus}
          onChange={(e) => {
            setEmploymentStatus(e.target.value);
            handleFilterChange();
          }}
          className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-100 min-w-[140px]"
        >
          <option value="all">كل الحالات</option>
          <option value="active">نشط</option>
          <option value="on_leave">إجازة</option>
          <option value="suspended">موقوف</option>
          <option value="terminated">منتهي</option>
        </select>
        <span className="text-xs text-gray-400 mr-auto">
          {data?.total != null ? `${data.total} موظف` : "—"}
        </span>
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
        {isError && (
          <div className="p-6 text-center text-red-600 text-sm">
            حدث خطأ أثناء تحميل البيانات. {error?.message}
          </div>
        )}
        {!isError && (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[900px]">
              <thead>
                <tr className="bg-gray-50 border-b border-gray-100">
                  {[
                    "#",
                    "الموظف",
                    "الدور الوظيفي",
                    "الفرع",
                    "تاريخ التوظيف",
                    "الراتب الأساسي",
                    "الحالة",
                    "إجراءات",
                  ].map((h) => (
                    <th
                      key={h}
                      className="text-right px-4 py-3 text-xs font-semibold text-gray-500 whitespace-nowrap"
                    >
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {isPending ? (
                  Array.from({ length: 8 }).map((_, i) => (
                    <tr key={i} className="border-b border-gray-50">
                      <td colSpan={8} className="px-4 py-3">
                        <div className="h-10 bg-gray-100 rounded-lg animate-pulse" />
                      </td>
                    </tr>
                  ))
                ) : rows.length > 0 ? (
                  rows.map((emp, idx) => {
                    const globalIndex =
                      (page - 1) * (queryParams.per_page ?? 50) + idx + 1;
                    const st =
                      STATUS_UI[emp.employment_status] ?? {
                        label: emp.employment_status,
                        pill: "bg-gray-50 text-gray-600 border-gray-200",
                      };
                    const roleClass =
                      ROLE_BADGE_CLASSES[
                        emp.job_title_id % ROLE_BADGE_CLASSES.length
                      ];
                    return (
                      <tr
                        key={emp.id}
                        className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
                      >
                        <td className="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">
                          {globalIndex}
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-3">
                            <div className="w-8 h-8 flex items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold shrink-0">
                              {emp.user.name.charAt(0)}
                            </div>
                            <div className="min-w-0">
                              <p className="text-sm font-medium text-gray-800 truncate">
                                {emp.user.name}
                              </p>
                              <p className="text-xs text-gray-400 truncate" dir="ltr">
                                {emp.user.email}
                              </p>
                            </div>
                          </div>
                        </td>
                        <td className="px-4 py-3">
                          <span
                            className={`text-xs px-2 py-1 rounded-lg font-medium ${roleClass}`}
                          >
                            {emp.job_title?.name ?? "—"}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                          {primaryBranchName(emp)}
                        </td>
                        <td className="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                          {emp.hire_date
                            ? emp.hire_date.replace(/-/g, "/")
                            : "—"}
                        </td>
                        <td className="px-4 py-3 text-sm font-semibold text-gray-800 whitespace-nowrap">
                          {emp.base_salary.toLocaleString("en-US")}{" "}
                          <span className="text-xs font-normal text-gray-400">
                            ج.م
                          </span>
                        </td>
                        <td className="px-4 py-3">
                          <span
                            className={`text-xs px-2 py-1 rounded-lg font-medium border ${st.pill}`}
                          >
                            {st.label}
                          </span>
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-1.5">
                            <button
                              type="button"
                              onClick={() => handleOpenDetails(emp)}
                              className="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 cursor-pointer transition-colors"
                              title="عرض"
                              aria-label="عرض"
                            >
                              <i className="ri-eye-line text-xs" />
                            </button>
                            <Link
                              to={`/employees/${emp.id}`}
                              className="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 cursor-pointer transition-colors"
                              title="تعديل"
                              aria-label="تعديل"
                            >
                              <i className="ri-edit-line text-xs" />
                            </Link>
                            <button
                              type="button"
                              className="w-7 h-7 flex items-center justify-center rounded-lg border border-red-100 text-red-400 opacity-40 cursor-not-allowed transition-colors"
                              title="الحذف غير متاح من القائمة"
                              disabled
                              aria-disabled
                            >
                              <i className="ri-delete-bin-line text-xs" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                ) : null}
              </tbody>
            </table>
          </div>
        )}

        {!isPending && !isError && rows.length === 0 && (
          <div className="py-16 text-center">
            <div className="w-12 h-12 flex items-center justify-center bg-gray-100 rounded-full mx-auto mb-3">
              <i className="ri-user-search-line text-xl text-gray-400" />
            </div>
            <p className="text-sm text-gray-400">
              لا يوجد موظفون مطابقون للبحث
            </p>
          </div>
        )}

        {!isError && rows.length > 0 && totalPages > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-xs text-gray-500">
            <span>
              صفحة {page} من {totalPages}
            </span>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page <= 1 || isPending}
                className="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:pointer-events-none cursor-pointer"
              >
                السابق
              </button>
              <button
                type="button"
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={page >= totalPages || isPending}
                className="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:pointer-events-none cursor-pointer"
              >
                التالي
              </button>
            </div>
          </div>
        )}
      </div>

      <EmployeeDetailsModal
        employee={selectedEmployee}
        open={isDetailsModalOpen}
        onOpenChange={setIsDetailsModalOpen}
      />
    </div>
  );
}

export default Employees;
