import { useState, useMemo, useEffect } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useSearchParams, useNavigate } from "react-router";
import {
  useGetAllEmployeeCustodiesQueryOptions,
  useDeleteEmployeeCustodyMutationOptions,
  useGetEmployeeCustodyTypesQueryOptions,
} from "@/api/v2/employees/employee-custodies/employee-custodies.hooks";
import { TEmployeeCustody } from "@/api/v2/employees/employee-custodies/employee-custodies.types";
import { EmployeesSelect } from "@/components/custom/EmployeesSelect";
import CustomPagination from "@/components/custom/CustomPagination";
import { toast } from "sonner";
import { CreateEmployeeCustodyModal } from "./CreateEmployeeCustodyModal";
import { UpdateEmployeeCustodyModal } from "./UpdateEmployeeCustodyModal";
import { MarkAsReturnedModal } from "./MarkAsReturnedModal";
import { MarkAsLostModal } from "./MarkAsLostModal";
import { MarkAsDamagedModal } from "./MarkAsDamagedModal";
import { EmployeeCustodyDetailsModal } from "./EmployeeCustodyDetailsModal";
import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import { formatDate } from "@/utils/formatDate";
import {
  type CustodyDisplayStatus,
  getCustodyDisplayStatus,
  getCustodyTypeVisual,
  custodyTypeLabel,
  daysUntil,
  CUSTODY_DISPLAY_STATUS_CONFIG,
} from "./custodyDisplayConfig";

const LIST_FETCH_PER_PAGE = 500;
const GRID_PAGE_SIZE = 12;

type StatusFilter = "all" | CustodyDisplayStatus;

const STATUS_PILLS: { value: StatusFilter; label: string }[] = [
  { value: "all", label: "الكل" },
  { value: "active", label: "نشطة" },
  { value: "expiring_soon", label: "تنتهي قريباً" },
  { value: "expired", label: "منتهية" },
  { value: "returned", label: "مُعادة" },
  { value: "damaged", label: "تالف" },
  { value: "lost", label: "مفقود" },
];

function EmployeeCustodies() {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Number(searchParams.get("page")) || 1;

  const [statusFilter, setStatusFilter] = useState<StatusFilter>("all");
  const [typeFilter, setTypeFilter] = useState("");
  const [searchQuery, setSearchQuery] = useState("");
  const [employeeId, setEmployeeId] = useState("");

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isDetailsModalOpen, setIsDetailsModalOpen] = useState(false);
  const [isReturnedModalOpen, setIsReturnedModalOpen] = useState(false);
  const [isLostModalOpen, setIsLostModalOpen] = useState(false);
  const [isDamagedModalOpen, setIsDamagedModalOpen] = useState(false);
  const [selectedEmployeeCustody, setSelectedEmployeeCustody] =
    useState<TEmployeeCustody | null>(null);

  const { data: custodyTypes = [] } = useQuery(useGetEmployeeCustodyTypesQueryOptions());

  const queryParams = useMemo(
    () => ({
      page: 1,
      per_page: LIST_FETCH_PER_PAGE,
      ...(employeeId ? { employee_id: Number(employeeId) } : {}),
      ...(typeFilter ? { type: typeFilter } : {}),
    }),
    [employeeId, typeFilter]
  );

  const { data, isPending, isError, error } = useQuery(
    useGetAllEmployeeCustodiesQueryOptions(queryParams)
  );

  const { mutate: deleteEmployeeCustody, isPending: isDeleting } = useMutation(
    useDeleteEmployeeCustodyMutationOptions()
  );

  const rows = useMemo(() => data?.data ?? [], [data]);

  const stats = useMemo(() => {
    let active = 0;
    let expiringSoon = 0;
    let expired = 0;
    let totalValue = 0;
    for (const c of rows) {
      const ds = getCustodyDisplayStatus(c);
      if (ds === "active") active += 1;
      if (ds === "expiring_soon") expiringSoon += 1;
      if (ds === "expired") expired += 1;
      if (ds === "active" || ds === "expiring_soon") {
        totalValue += c.value;
      }
    }
    return { active, expiringSoon, expired, totalValue };
  }, [rows]);

  const filtered = useMemo(() => {
    const q = searchQuery.trim().toLowerCase();
    return rows.filter((c) => {
      const empName = c.employee?.user?.name?.toLowerCase() ?? "";
      const serial = c.serial_number?.toLowerCase() ?? "";
      const name = c.name?.toLowerCase() ?? "";
      const desc = c.description?.toLowerCase() ?? "";
      const asset = c.asset_tag?.toLowerCase() ?? "";
      const matchSearch =
        !q ||
        empName.includes(q) ||
        serial.includes(q) ||
        name.includes(q) ||
        desc.includes(q) ||
        asset.includes(q);
      const ds = getCustodyDisplayStatus(c);
      const matchStatus = statusFilter === "all" || ds === statusFilter;
      return matchSearch && matchStatus;
    });
  }, [rows, searchQuery, statusFilter]);

  const totalPages = Math.max(1, Math.ceil(filtered.length / GRID_PAGE_SIZE));

  useEffect(() => {
    if (page > totalPages) {
      setSearchParams(
        (prev) => {
          const next = new URLSearchParams(prev);
          next.set("page", String(totalPages));
          return next;
        },
        { replace: true }
      );
    }
  }, [page, totalPages, setSearchParams]);

  const paginated = useMemo(() => {
    const start = (page - 1) * GRID_PAGE_SIZE;
    return filtered.slice(start, start + GRID_PAGE_SIZE);
  }, [filtered, page]);

  const fmt = (n: number) => n.toLocaleString("ar-SA");

  const resetPage = () => {
    setSearchParams(
      (prev) => {
        const next = new URLSearchParams(prev);
        next.set("page", "1");
        return next;
      },
      { replace: true }
    );
  };

  const handleOpenEdit = (custody: TEmployeeCustody) => {
    setSelectedEmployeeCustody(custody);
    setIsEditModalOpen(true);
  };

  const handleOpenDelete = (custody: TEmployeeCustody) => {
    setSelectedEmployeeCustody(custody);
    setIsDeleteModalOpen(true);
  };

  const handleOpenDetails = (custody: TEmployeeCustody) => {
    setSelectedEmployeeCustody(custody);
    setIsDetailsModalOpen(true);
  };

  const handleOpenReturned = (custody: TEmployeeCustody) => {
    setSelectedEmployeeCustody(custody);
    setIsReturnedModalOpen(true);
  };

  const handleOpenLost = (custody: TEmployeeCustody) => {
    setSelectedEmployeeCustody(custody);
    setIsLostModalOpen(true);
  };

  const handleOpenDamaged = (custody: TEmployeeCustody) => {
    setSelectedEmployeeCustody(custody);
    setIsDamagedModalOpen(true);
  };

  const handleDelete = (onCloseModal: () => void) => {
    if (selectedEmployeeCustody) {
      deleteEmployeeCustody(selectedEmployeeCustody.id, {
        onSuccess: () => {
          toast.success("تم حذف الضمان بنجاح", {
            description: "تم حذف الضمان من النظام.",
          });
          onCloseModal();
        },
        onError: (err) => {
          toast.error("حدث خطأ أثناء حذف الضمان", {
            description: err.message,
          });
        },
      });
    }
  };

  const truncated =
    (data?.total ?? 0) > rows.length
      ? `عرض ${rows.length} سجلًا محمّلًا من أصل ${data?.total ?? 0} — الإحصاءات للسجلات المحمّلة فقط`
      : null;

  return (
    <div dir="rtl" className="min-h-screen bg-[#f8f8fb] p-4 sm:p-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
          <div>
          <h1 className="text-2xl font-bold text-gray-800">الكفالات والضمانات</h1>
          <p className="text-sm text-gray-500 mt-1">
            تتبع ضمانات الموظفين وتواريخ الإرجاع المتوقعة والحالات
          </p>
        </div>
        <button
          type="button"
          onClick={() => setIsCreateModalOpen(true)}
          className="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 transition-colors whitespace-nowrap"
        >
          <i className="ri-add-line" /> ضمان جديد
        </button>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
        {[
          {
            label: "إجمالي الضمانات",
            val: String(data?.total ?? rows.length),
            icon: "ri-file-shield-2-line",
            color: "text-violet-600",
            bg: "bg-violet-50",
            alert: false,
          },
          {
            label: "نشطة",
            val: String(stats.active),
            icon: "ri-checkbox-circle-line",
            color: "text-emerald-600",
            bg: "bg-emerald-50",
            alert: false,
          },
          {
            label: "تنتهي قريباً",
            val: String(stats.expiringSoon),
            icon: "ri-alarm-warning-line",
            color: "text-amber-600",
            bg: "bg-amber-50",
            alert: stats.expiringSoon > 0,
          },
          {
            label: "منتهية",
            val: String(stats.expired),
            icon: "ri-close-circle-line",
            color: "text-red-600",
            bg: "bg-red-50",
            alert: stats.expired > 0,
          },
          {
            label: "إجمالي القيمة النشطة",
            val: `${fmt(stats.totalValue)} ج.م`,
            icon: "ri-money-dollar-circle-line",
            color: "text-gray-700",
            bg: "bg-gray-100",
            alert: false,
          },
        ].map((stat) => (
          <div
            key={stat.label}
            className={`bg-white rounded-xl p-4 border ${
              stat.alert ? "border-amber-200" : "border-gray-100"
            } relative overflow-hidden`}
          >
            {stat.alert ? (
              <div className="absolute top-0 left-0 right-0 h-0.5 bg-amber-400" />
            ) : null}
            <div className="flex items-center gap-2.5 mb-2.5">
              <div
                className={`w-9 h-9 flex items-center justify-center rounded-lg ${stat.bg}`}
              >
                <i className={`${stat.icon} ${stat.color} text-lg`} />
              </div>
              <span className="text-xs text-gray-500 font-medium leading-tight">
                {stat.label}
              </span>
            </div>
            <p className="text-xl font-bold text-gray-800">{stat.val}</p>
          </div>
        ))}
      </div>

      {stats.expiringSoon > 0 ? (
        <div className="mb-5 bg-amber-50 border border-amber-200 rounded-xl px-5 py-3.5 flex flex-col sm:flex-row sm:items-center gap-3">
          <div className="w-8 h-8 flex items-center justify-center flex-shrink-0">
            <i className="ri-alarm-warning-line text-amber-500 text-xl" />
          </div>
          <div className="flex-1">
            <p className="text-sm font-semibold text-amber-800">
              تنبيه: {stats.expiringSoon} ضمان تنتهي خلال 45 يوماً (من السجلات المحمّلة)
            </p>
            <p className="text-xs text-amber-600 mt-0.5">
              يُنصح بتحديث تاريخ الإرجاع أو إتمام الإجراء المناسب
            </p>
          </div>
          <button
            type="button"
            onClick={() => {
              setStatusFilter("expiring_soon");
              resetPage();
            }}
            className="sm:mr-auto text-xs text-amber-700 font-semibold border border-amber-300 px-3 py-1.5 rounded-lg hover:bg-amber-100 whitespace-nowrap"
          >
            عرض الضمانات
          </button>
        </div>
      ) : null}

      <div className="bg-white rounded-xl border border-gray-100 p-4 mb-5 flex flex-col lg:flex-row lg:items-center gap-4 flex-wrap">
        <div className="relative flex-1 min-w-[13rem]">
          <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
          <input
            type="text"
            placeholder="بحث بالاسم أو التسلسلي أو الوصف..."
            value={searchQuery}
            onChange={(e) => {
              setSearchQuery(e.target.value);
              resetPage();
            }}
            className="w-full pr-9 pl-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-200"
          />
        </div>

                <EmployeesSelect
                  params={{ per_page: 10 }}
                  value={employeeId}
          onChange={(v) => {
            setEmployeeId(v || "");
            resetPage();
                  }}
                  placeholder="جميع الموظفين"
          allowClear
          className="min-w-[12rem] w-full sm:w-56 border-gray-200 rounded-lg"
        />

        <select
          value={typeFilter}
          onChange={(e) => {
            setTypeFilter(e.target.value);
            resetPage();
          }}
          className="px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-200 bg-white min-w-[10rem]"
        >
          <option value="">جميع الأنواع</option>
          {custodyTypes.map((t) => (
            <option key={t.key} value={t.key}>
              {t.name}
            </option>
          ))}
        </select>

        <div className="flex gap-1 bg-gray-100 p-1 rounded-lg flex-wrap">
          {STATUS_PILLS.map((s) => (
            <button
              key={s.value}
              type="button"
              onClick={() => {
                setStatusFilter(s.value);
                resetPage();
              }}
              className={`px-3 py-1.5 rounded-md text-xs font-medium transition-all whitespace-nowrap ${
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
            setEmployeeId("");
            setTypeFilter("");
            setSearchQuery("");
            setStatusFilter("all");
            resetPage();
          }}
          className="text-xs text-gray-500 hover:text-violet-600 border border-gray-200 rounded-lg px-3 py-2"
        >
          مسح الفلاتر
        </button>
      </div>

      {isError ? (
        <div className="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm mb-5">
          حدث خطأ أثناء تحميل البيانات: {error?.message}
        </div>
      ) : null}

      {isPending ? (
        <div className="text-center py-20 text-gray-500 text-sm">جاري التحميل...</div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          {paginated.map((c) => {
            const ds = getCustodyDisplayStatus(c);
            const statusCfg = CUSTODY_DISPLAY_STATUS_CONFIG[ds];
            const typeCfg = getCustodyTypeVisual(c.type);
            const typeLabel = custodyTypeLabel(c.type, custodyTypes);
            const days = daysUntil(c.expected_return_date);
            const isAssigned = c.status === "assigned";
            const actionable = isAssigned;

            return (
              <div
                key={c.id}
                className={`bg-white rounded-xl border overflow-hidden transition-all hover:-translate-y-0.5 ${
                  ds === "expiring_soon"
                    ? "border-amber-200"
                    : ds === "expired"
                      ? "border-red-200"
                      : "border-gray-100"
                }`}
              >
                <div
                  className={`h-1 w-full ${
                    ds === "active"
                      ? "bg-emerald-400"
                      : ds === "expiring_soon"
                        ? "bg-amber-400"
                        : ds === "expired"
                          ? "bg-red-400"
                          : ds === "returned"
                            ? "bg-sky-400"
                            : ds === "damaged"
                              ? "bg-orange-400"
                              : ds === "lost"
                                ? "bg-gray-400"
                                : "bg-gray-300"
                  }`}
                />

                <div className="p-4">
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center gap-2.5 min-w-0">
                      <div
                        className={`w-10 h-10 flex items-center justify-center rounded-xl shrink-0 ${typeCfg.bg}`}
                      >
                        <i className={`${typeCfg.icon} ${typeCfg.color} text-lg`} />
                      </div>
                      <div className="min-w-0">
                        <p className="text-sm font-bold text-gray-800 truncate">{typeLabel}</p>
                        <p className="text-xs text-gray-400 truncate">{c.serial_number}</p>
                      </div>
                    </div>
                    <span
                      className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 mr-2 ${statusCfg.bg} ${statusCfg.color}`}
                    >
                      <span className={`w-1.5 h-1.5 rounded-full ${statusCfg.dot}`} />
                      {statusCfg.label}
                    </span>
                  </div>

                  <div className="flex items-center gap-2 mb-3 py-2 border-t border-b border-gray-50">
                    <div className="w-7 h-7 flex items-center justify-center rounded-full bg-violet-100 text-violet-600 text-xs font-bold shrink-0">
                      {(c.employee?.user?.name || "?").charAt(0)}
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-semibold text-gray-800 truncate">
                        {c.employee?.user?.name || "—"}
                      </p>
                      <p className="text-xs text-gray-400 truncate">{c.name}</p>
                    </div>
                    <button
                      type="button"
                      onClick={() =>
                        navigate(`/employees/salary-detail/${c.employee_id}`)
                      }
                      className="text-xs text-violet-500 hover:text-violet-700 shrink-0 p-1"
                      title="تفاصيل الراتب"
                    >
                      <i className="ri-external-link-line" />
                    </button>
              </div>

                  <div className="space-y-1.5 mb-3">
                    <div className="flex items-center justify-between gap-2">
                      <span className="text-xs text-gray-400">القيمة</span>
                      <span className="text-sm font-bold text-gray-800">
                        {fmt(c.value)} ج.م
                      </span>
                    </div>
                    <div className="flex items-center justify-between gap-2">
                      <span className="text-xs text-gray-400">تاريخ التعيين</span>
                      <span className="text-xs text-gray-600">{formatDate(c.assigned_date)}</span>
                    </div>
                    <div className="flex items-center justify-between gap-2">
                      <span className="text-xs text-gray-400">الإرجاع المتوقع</span>
                      <span
                        className={`text-xs font-semibold ${
                          ds === "expired"
                            ? "text-red-600"
                            : ds === "expiring_soon"
                              ? "text-amber-600"
                              : "text-gray-700"
                        }`}
                      >
                        {formatDate(c.expected_return_date)}
                      </span>
                    </div>
              </div>

                  {c.status === "assigned" ? (
                    <div
                      className={`text-center py-1.5 rounded-lg text-xs font-semibold mb-3 ${
                        days < 0
                          ? "bg-red-50 text-red-600"
                          : days <= 45
                            ? "bg-amber-50 text-amber-700"
                            : "bg-gray-50 text-gray-500"
                      }`}
                    >
                      {days < 0
                        ? `متأخر ${Math.abs(days)} يوم`
                        : days === 0
                          ? "يستحق الإرجاع اليوم"
                          : `متبقي ${days} يوم`}
            </div>
                  ) : null}

                  {c.description ? (
                    <p className="text-xs text-gray-500 mb-3 line-clamp-2">{c.description}</p>
        ) : null}

                  <div className="flex flex-wrap gap-2 pt-2 border-t border-gray-50">
                    <button
                      type="button"
                      onClick={() => handleOpenDetails(c)}
                      className="flex-1 min-w-[4.5rem] py-1.5 rounded-lg text-xs text-gray-600 bg-gray-50 hover:bg-gray-100 font-medium"
                    >
                      <i className="ri-eye-line ml-1" />
                      تفاصيل
                    </button>
                    {actionable ? (
                      <>
                        <button
                          type="button"
                          onClick={() => handleOpenEdit(c)}
                          className="flex-1 min-w-[4.5rem] py-1.5 rounded-lg text-xs text-violet-600 bg-violet-50 hover:bg-violet-100 font-medium"
                        >
                          <i className="ri-refresh-line ml-1" />
                          تجديد
                        </button>
                        <button
                          type="button"
                          onClick={() => handleOpenReturned(c)}
                          className="flex-1 min-w-[4.5rem] py-1.5 rounded-lg text-xs text-sky-600 bg-sky-50 hover:bg-sky-100 font-medium"
                        >
                          <i className="ri-arrow-go-back-line ml-1" />
                          إرجاع
                        </button>
                        <button
                          type="button"
                          onClick={() => handleOpenDamaged(c)}
                          className="py-1.5 px-2 rounded-lg text-xs text-orange-600 bg-orange-50 hover:bg-orange-100"
                          title="تلف"
                        >
                          <i className="ri-error-warning-line" />
                        </button>
                        <button
                          type="button"
                          onClick={() => handleOpenLost(c)}
                          className="py-1.5 px-2 rounded-lg text-xs text-amber-700 bg-amber-50 hover:bg-amber-100"
                          title="فقدان"
                        >
                          <i className="ri-question-line" />
                        </button>
                        <button
                          type="button"
                          onClick={() => handleOpenDelete(c)}
                          className="py-1.5 px-2 rounded-lg text-xs text-gray-400 hover:bg-red-50 hover:text-red-500"
                          title="حذف"
                        >
                          <i className="ri-delete-bin-line" />
                        </button>
                      </>
                    ) : (
                      <p className="text-xs text-gray-400 w-full text-center py-1">
                        لا إجراءات متاحة على هذه الحالة
                      </p>
                    )}
                  </div>
                </div>
              </div>
            );
          })}

          <button
            type="button"
            onClick={() => setIsCreateModalOpen(true)}
            className="bg-white rounded-xl border-2 border-dashed border-gray-200 min-h-48 flex flex-col items-center justify-center gap-3 hover:border-violet-300 hover:bg-violet-50/30 transition-all group"
          >
            <div className="w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 group-hover:bg-violet-100 transition-colors">
              <i className="ri-add-line text-gray-400 group-hover:text-violet-500 text-2xl transition-colors" />
            </div>
            <span className="text-sm text-gray-400 group-hover:text-violet-600 font-medium transition-colors">
              إضافة ضمان جديد
            </span>
          </button>

          {!isPending && filtered.length === 0 ? (
            <div className="col-span-full text-center py-16">
              <div className="w-14 h-14 flex items-center justify-center mx-auto rounded-full bg-gray-100 mb-3">
                <i className="ri-file-shield-2-line text-gray-400 text-2xl" />
                          </div>
              <p className="text-sm text-gray-500">لا توجد ضمانات مطابقة لمعايير البحث</p>
            </div>
          ) : null}
            </div>
          )}

      <div className="mt-5 bg-white rounded-xl border border-gray-100 px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <span className="text-xs text-gray-400">
          {filtered.length} ضمان مطابقة — معروض {paginated.length} في الصفحة {page} من {totalPages}
        </span>
        <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 text-xs">
          {truncated ? <span className="text-amber-700 font-medium">{truncated}</span> : null}
          <span className="text-gray-500">
            إجمالي القيمة النشطة (محمّل):{" "}
            <span className="font-bold text-gray-800">{fmt(stats.totalValue)} ج.م</span>
          </span>
          {stats.expiringSoon > 0 ? (
            <span className="text-amber-600 font-semibold">
              <i className="ri-alarm-warning-line ml-1" />
              {stats.expiringSoon} تنتهي قريباً
            </span>
          ) : null}
          {stats.expired > 0 ? (
            <span className="text-red-500 font-semibold">
              <i className="ri-close-circle-line ml-1" />
              {stats.expired} منتهية
            </span>
          ) : null}
        </div>
      </div>

      <div className="mt-4 flex justify-center sm:justify-start">
          <CustomPagination
          totalElementsLabel="الضمانات المطابقة"
          totalElements={filtered.length}
          totalPages={totalPages}
            isLoading={isPending}
          tone="violet"
        />
      </div>

      <CreateEmployeeCustodyModal open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen} />
      <UpdateEmployeeCustodyModal
        employeeCustody={selectedEmployeeCustody}
        open={isEditModalOpen}
        onOpenChange={setIsEditModalOpen}
      />
      <ControlledConfirmationModal
        open={isDeleteModalOpen}
        onOpenChange={setIsDeleteModalOpen}
        contentClassName="rounded-2xl border-gray-100 bg-white sm:max-w-md"
        alertTitle="حذف الضمان"
        alertMessage={
          <>
            هل أنت متأكد أنك تريد حذف الضمان{" "}
            <strong>{selectedEmployeeCustody?.name}</strong>؟
          </>
        }
        handleConfirmation={handleDelete}
        isPending={isDeleting}
        pendingLabel="جاري الحذف..."
        confirmLabel="تأكيد الحذف"
        variant="destructive"
        confirmButtonClassName="bg-red-600 text-white hover:bg-red-700"
        cancelButtonClassName="border-gray-200 text-gray-600"
      />
      <EmployeeCustodyDetailsModal
        employeeCustody={selectedEmployeeCustody}
        open={isDetailsModalOpen}
        onOpenChange={setIsDetailsModalOpen}
      />
      <MarkAsReturnedModal
        employeeCustody={selectedEmployeeCustody}
        open={isReturnedModalOpen}
        onOpenChange={setIsReturnedModalOpen}
      />
      <MarkAsLostModal
        employeeCustody={selectedEmployeeCustody}
        open={isLostModalOpen}
        onOpenChange={setIsLostModalOpen}
      />
      <MarkAsDamagedModal
        employeeCustody={selectedEmployeeCustody}
        open={isDamagedModalOpen}
        onOpenChange={setIsDamagedModalOpen}
      />
    </div>
  );
}

export default EmployeeCustodies;
