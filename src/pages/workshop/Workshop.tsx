import { useMemo, useState } from "react";
import { toast } from "sonner";
import { CreateWorkshopModal } from "./CreateWorkshopModal";
import { DeleteWorkshopModal } from "./DeleteWorkshopModal";
import { EditWorkshopModal } from "./EditWorkshopModal";
import { WorkshopsTableSkeleton } from "./WorkshopsTableSkeleton";

import { TWorkshopResponse } from "@/api/v2/workshop/workshop.types";
import {
  useExportWorkshopsToCSVMutationOptions,
  useGetWorkshopsQueryOptions,
} from "@/api/v2/workshop/workshops.hooks";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import CustomPagination from "@/components/custom/CustomPagination";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Link, useSearchParams } from "react-router";
import { Download } from "lucide-react";

type FacilityStatus = "نشط" | "مغلق" | "قيد الإنشاء";
type StatusFilter = "all" | FacilityStatus;

const statusConfig: Record<
  FacilityStatus,
  { label: string; color: string; bg: string; dot: string }
> = {
  نشط: {
    label: "نشط",
    color: "text-emerald-700",
    bg: "bg-emerald-50",
    dot: "bg-emerald-500",
  },
  مغلق: {
    label: "مغلق",
    color: "text-red-700",
    bg: "bg-red-50",
    dot: "bg-red-500",
  },
  "قيد الإنشاء": {
    label: "قيد الإنشاء",
    color: "text-amber-700",
    bg: "bg-amber-50",
    dot: "bg-amber-400",
  },
};

function normalizeWorkshopStatus(w: TWorkshopResponse): FacilityStatus {
  const raw = w.status?.trim();
  if (!raw) return "نشط";
  const lower = raw.toLowerCase();
  if (raw === "مغلق" || lower === "closed" || lower === "inactive")
    return "مغلق";
  if (
    raw === "قيد الإنشاء" ||
    raw.includes("قيد الإنشاء") ||
    lower === "pending" ||
    lower === "building"
  )
    return "قيد الإنشاء";
  if (raw.includes("قيد")) return "قيد الإنشاء";
  if (raw === "نشط" || lower === "active") return "نشط";
  return "نشط";
}

function cityName(w: TWorkshopResponse): string {
  return w.address?.city?.name?.trim() || "—";
}

function streetLine(w: TWorkshopResponse): string {
  const parts = [w.address?.street, w.address?.building].filter(Boolean);
  return parts.join(" — ") || "";
}

function formatCreatedAt(iso: string): string {
  if (!iso) return "—";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso.slice(0, 10);
  return d.toLocaleDateString("ar-EG", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

const ACCENT = "bg-slate-100 text-slate-700";
const ICON = "ri-tools-line";

function Workshop() {
  const [searchParams] = useSearchParams();
  const page = Number(searchParams.get("page")) || 1;

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [selectedWorkshop, setSelectedWorkshop] =
    useState<TWorkshopResponse | null>(null);

  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState<StatusFilter>("all");
  const [cityFilter, setCityFilter] = useState("all");

  const per_page = 10;
  const { data, isPending, isError, error } = useQuery(
    useGetWorkshopsQueryOptions(page, per_page)
  );

  const { mutate: exportWorkshopsToCSV, isPending: isExporting } = useMutation(
    useExportWorkshopsToCSVMutationOptions()
  );

  const workshops = data?.data ?? [];

  const cities = useMemo(() => {
    const set = new Set<string>();
    for (const w of workshops) {
      const c = w.address?.city?.name?.trim();
      if (c) set.add(c);
    }
    return Array.from(set).sort((a, b) => a.localeCompare(b, "ar"));
  }, [workshops]);

  const filtered = useMemo(() => {
    const q = search.trim();
    return workshops.filter((w) => {
      const inv = w.inventory?.name ?? "";
      const city = cityName(w);
      const matchSearch =
        !q ||
        w.name.includes(q) ||
        w.workshop_code.toLowerCase().includes(q.toLowerCase()) ||
        city.includes(q) ||
        inv.includes(q);
      const st = normalizeWorkshopStatus(w);
      const matchStatus = statusFilter === "all" || statusFilter === st;
      const matchCity = cityFilter === "all" || city === cityFilter;
      return matchSearch && matchStatus && matchCity;
    });
  }, [workshops, search, statusFilter, cityFilter]);

  const activeCount = useMemo(
    () => workshops.filter((w) => normalizeWorkshopStatus(w) === "نشط").length,
    [workshops]
  );
  const constructionCount = useMemo(
    () =>
      workshops.filter((w) => normalizeWorkshopStatus(w) === "قيد الإنشاء")
        .length,
    [workshops]
  );

  const handleOpenEdit = (workshop: TWorkshopResponse) => {
    setSelectedWorkshop(workshop);
    setIsEditModalOpen(true);
  };
  const handleOpenDelete = (workshop: TWorkshopResponse) => {
    setSelectedWorkshop(workshop);
    setIsDeleteModalOpen(true);
  };

  const handleExport = () => {
    exportWorkshopsToCSV(undefined, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) ||
          "workshops.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير الورش بنجاح");
      },
      onError: (err: unknown) => {
        const message = err instanceof Error ? err.message : String(err);
        toast.error("خطأ أثناء تصدير الورش. الرجاء المحاولة مرة أخرى.", {
          description: message,
        });
      },
    });
  };

  const showClearFilters =
    Boolean(search.trim()) ||
    statusFilter !== "all" ||
    cityFilter !== "all";

  const total = data?.total ?? 0;

  return (
    <div className="min-h-screen bg-[#f8f8fb] p-6" dir="rtl">
      {isError && (
        <div className="mb-6 rounded-xl border border-red-100 bg-red-50/80 p-4 text-sm text-red-700">
          حدث خطأ أثناء تحميل البيانات. الرجاء المحاولة مرة أخرى.
          {error?.message ? ` (${error.message})` : null}
        </div>
      )}

      {!isError && (
        <>
          <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div className="flex items-center gap-4 min-w-0">
              <div
                className={`w-12 h-12 flex items-center justify-center rounded-2xl shrink-0 ${ACCENT}`}
              >
                <i className={`${ICON} text-2xl`} />
              </div>
              <div className="min-w-0">
                <h1 className="text-2xl font-bold text-gray-800">
                  إدارة الورشات
                </h1>
                <p className="text-sm text-gray-500 mt-0.5">
                  عرض وإدارة الورشات المرتبطة بفروع الشركة
                </p>
              </div>
            </div>
            <div className="flex flex-wrap items-center gap-2">
              <button
                type="button"
                onClick={handleExport}
                disabled={isExporting}
                className="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors disabled:opacity-50"
              >
                <Download className="h-4 w-4" />
                {isExporting ? "جاري التصدير..." : "تصدير Excel"}
              </button>
              <button
                type="button"
                onClick={() => setIsCreateModalOpen(true)}
                className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-slate-700 text-white text-sm font-medium hover:bg-slate-800 transition-colors whitespace-nowrap"
              >
                <i className="ri-add-line" /> إضافة ورشة
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {[
              {
                label: "إجمالي الورشات",
                val: isPending ? "…" : total,
                icon: ICON,
                color: "text-slate-700",
                bg: "bg-slate-100",
                numeric: true,
              },
              {
                label: "نشطة",
                val: isPending ? "…" : activeCount,
                icon: "ri-checkbox-circle-line",
                color: "text-emerald-600",
                bg: "bg-emerald-50",
                numeric: true,
              },
              {
                label: "قيد الإنشاء",
                val: isPending ? "…" : constructionCount,
                icon: "ri-building-4-line",
                color: "text-amber-600",
                bg: "bg-amber-50",
                numeric: true,
              },
              {
                label: "ارتباطات بالفروع",
                val: "—",
                sub: "غير متوفر من الـ API",
                icon: "ri-git-branch-line",
                color: "text-violet-600",
                bg: "bg-violet-50",
                numeric: false,
              },
            ].map((s) => (
              <div
                key={s.label}
                className="bg-white rounded-xl p-5 border border-gray-100"
              >
                <div className="flex items-center gap-3 mb-3">
                  <div
                    className={`w-9 h-9 flex items-center justify-center rounded-lg ${s.bg}`}
                  >
                    <i className={`${s.icon} ${s.color} text-lg`} />
                  </div>
                  <span className="text-sm text-gray-500">{s.label}</span>
                </div>
                {s.numeric ? (
                  <p className="text-2xl font-bold text-gray-800">{s.val}</p>
                ) : (
                  <>
                    <p className="text-2xl font-bold text-gray-400">{s.val}</p>
                    {"sub" in s && s.sub ? (
                      <p className="text-[10px] text-gray-400 mt-1">{s.sub}</p>
                    ) : null}
                  </>
                )}
              </div>
            ))}
          </div>

          <div className="bg-white rounded-xl border border-gray-100 p-4 mb-5 flex items-center gap-4 flex-wrap">
            <div className="relative flex-1 min-w-52">
              <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none" />
              <input
                type="text"
                placeholder="بحث في الورشات..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full pr-9 pl-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>

            <select
              value={cityFilter}
              onChange={(e) => setCityFilter(e.target.value)}
              className="px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200 bg-white cursor-pointer"
            >
              <option value="all">جميع المدن</option>
              {cities.map((c) => (
                <option key={c} value={c}>
                  {c}
                </option>
              ))}
            </select>

            <div className="flex gap-1 bg-gray-100 p-1 rounded-lg">
              {(
                [
                  { value: "all" as const, label: "الكل" },
                  { value: "نشط" as const, label: "نشطة" },
                  { value: "قيد الإنشاء" as const, label: "قيد الإنشاء" },
                  { value: "مغلق" as const, label: "مغلقة" },
                ] as const
              ).map((s) => (
                <button
                  key={s.value}
                  type="button"
                  onClick={() => setStatusFilter(s.value)}
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

            {showClearFilters ? (
              <button
                type="button"
                onClick={() => {
                  setSearch("");
                  setStatusFilter("all");
                  setCityFilter("all");
                }}
                className="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1 whitespace-nowrap"
              >
                <i className="ri-close-line" /> مسح الفلاتر
              </button>
            ) : null}
          </div>

          <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full min-w-[900px]">
                <thead>
                  <tr className="bg-gray-50 border-b border-gray-100">
                    <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                      الكود
                    </th>
                    <th className="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                      الاسم
                    </th>
                    <th className="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                      الفروع المرتبطة
                    </th>
                    <th className="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                      المخزن
                    </th>
                    <th className="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                      الموقع
                    </th>
                    <th className="text-center px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                      الحالة
                    </th>
                    <th className="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                      تاريخ الإنشاء
                    </th>
                    <th className="text-center px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                      إجراءات
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {isPending ? (
                    <WorkshopsTableSkeleton rows={5} />
                  ) : filtered.length > 0 ? (
                    filtered.map((item, idx) => {
                      const st = normalizeWorkshopStatus(item);
                      const sc = statusConfig[st];
                      const notes = item.address?.notes?.trim();
                      return (
                        <tr
                          key={item.id}
                          className={`border-b border-gray-50 hover:bg-gray-50/50 transition-colors ${
                            idx % 2 === 1 ? "bg-gray-50/20" : ""
                          }`}
                        >
                          <td className="px-5 py-4">
                            <Link
                              to={`/workshop/${item.id}`}
                              className="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-md inline-block hover:bg-slate-200 transition-colors"
                            >
                              {item.workshop_code}
                            </Link>
                          </td>
                          <td className="px-4 py-4">
                            <div className="flex items-center gap-2.5">
                              <div
                                className={`w-8 h-8 flex items-center justify-center rounded-lg shrink-0 ${ACCENT}`}
                              >
                                <i className={`${ICON} text-sm`} />
                              </div>
                              <div className="min-w-0">
                                <Link
                                  to={`/workshop/${item.id}`}
                                  className="text-sm font-semibold text-gray-800 hover:text-slate-700 block truncate"
                                >
                                  {item.name}
                                </Link>
                                {notes ? (
                                  <p className="text-xs text-gray-400 truncate max-w-36">
                                    {notes}
                                  </p>
                                ) : null}
                              </div>
                            </div>
                          </td>
                          <td className="px-4 py-4">
                            <span className="text-xs text-gray-400">—</span>
                          </td>
                          <td className="px-4 py-4">
                            <div className="flex items-center gap-1.5 min-w-0">
                              <i className="ri-store-2-line text-gray-400 text-sm shrink-0" />
                              <span className="text-sm text-gray-700 truncate">
                                {item.inventory?.name || "—"}
                              </span>
                            </div>
                          </td>
                          <td className="px-4 py-4">
                            <p className="text-sm text-gray-700 font-medium">
                              {cityName(item)}
                            </p>
                            {streetLine(item) ? (
                              <p className="text-xs text-gray-400 truncate max-w-36">
                                {streetLine(item)}
                              </p>
                            ) : null}
                          </td>
                          <td className="px-4 py-4 text-center">
                            <span
                              className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ${sc.bg} ${sc.color}`}
                            >
                              <span
                                className={`w-1.5 h-1.5 rounded-full ${sc.dot}`}
                              />
                              {sc.label}
                            </span>
                          </td>
                          <td className="px-4 py-4">
                            <span className="text-xs text-gray-500">
                              {formatCreatedAt(item.created_at)}
                            </span>
                          </td>
                          <td className="px-4 py-4">
                            <div className="flex items-center justify-center gap-1">
                              <button
                                type="button"
                                title="تعديل"
                                onClick={() => handleOpenEdit(item)}
                                className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 transition-colors"
                              >
                                <i className="ri-edit-line text-base" />
                              </button>
                              <button
                                type="button"
                                title="حذف"
                                onClick={() => handleOpenDelete(item)}
                                className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition-colors"
                              >
                                <i className="ri-delete-bin-line text-base" />
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

            {!isPending && filtered.length === 0 && (
              <div className="text-center py-16">
                <div className="w-14 h-14 flex items-center justify-center mx-auto rounded-full bg-gray-100 mb-3">
                  <i className={`${ICON} text-gray-400 text-2xl`} />
                </div>
                <p className="text-sm text-gray-500 mb-4">
                  {workshops.length === 0
                    ? "لا توجد ورشات بعد"
                    : "لا توجد نتائج مطابقة"}
                </p>
                {workshops.length === 0 && (
                  <button
                    type="button"
                    onClick={() => setIsCreateModalOpen(true)}
                    className="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800 transition-colors whitespace-nowrap"
                  >
                    <i className="ri-add-line ml-1" /> إضافة ورشة الأولى
                  </button>
                )}
              </div>
            )}

            {!isPending && filtered.length > 0 && (
              <div className="px-5 py-3 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <span className="text-xs text-gray-400">
                  {filtered.length} ورشة معروضة
                  {filtered.length !== workshops.length
                    ? ` من ${workshops.length} في القائمة`
                    : ""}
                  {workshops.length > 0 && data?.total != null
                    ? ` — الإجمالي ${data.total}`
                    : ""}
                </span>
                <div className="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                  <span>
                    <span className="font-semibold text-emerald-600">
                      {activeCount}
                    </span>{" "}
                    نشطة في القائمة
                  </span>
                  {constructionCount > 0 && (
                    <span>
                      <span className="font-semibold text-amber-600">
                        {constructionCount}
                      </span>{" "}
                      قيد الإنشاء
                    </span>
                  )}
                </div>
              </div>
            )}
          </div>

          <div className="mt-6 flex justify-center">
            <CustomPagination
              totalElementsLabel="إجمالي الورش"
              totalElements={data?.total}
              totalPages={data?.total_pages}
              isLoading={isPending}
            />
          </div>
        </>
      )}

      <CreateWorkshopModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
      <EditWorkshopModal
        workshop={selectedWorkshop}
        open={isEditModalOpen}
        onOpenChange={setIsEditModalOpen}
      />
      <DeleteWorkshopModal
        workshop={selectedWorkshop}
        open={isDeleteModalOpen}
        onOpenChange={setIsDeleteModalOpen}
      />
    </div>
  );
}

export default Workshop;
