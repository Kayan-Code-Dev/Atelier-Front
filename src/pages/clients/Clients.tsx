import { useState, useMemo, useCallback } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useNavigate, useSearchParams } from "react-router";
import { toast } from "sonner";
import {
  useExportClientsToCSVMutationOptions,
  useGetClientsQueryOptions,
} from "@/api/v2/clients/clients.hooks";
import type { TClientResponse } from "@/api/v2/clients/clients.types";
import { CLIENT_SOURCE_LABELS } from "@/api/v2/clients/clients.types";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import { formatPhone } from "@/utils/formatPhone";
import CustomPagination from "@/components/custom/CustomPagination";
import { CreateClientModal } from "./CreateClientModal";
import { EditClientModal } from "./EditClientModal";
import { DeleteClientModal } from "./DeleteClientModal";
import { Skeleton } from "@/components/ui/skeleton";
import {
  clientCode,
  deriveClientStatus,
  getClientCity,
  getClientDisplayName,
  getPrimaryPhone,
  joinDateLabel,
  type ClientUiStatus,
} from "./clientsViewModel";

const statusConfig: Record<
  ClientUiStatus,
  { bg: string; color: string }
> = {
  VIP: { bg: "#FEF9E7", color: "#D97706" },
  نشط: { bg: "#D1FAE5", color: "#065F46" },
  جديد: { bg: "#DBEAFE", color: "#1D4ED8" },
  "غير نشط": { bg: "#F3F4F6", color: "#6B7280" },
};

const statuses: (ClientUiStatus | "الكل")[] = [
  "الكل",
  "VIP",
  "نشط",
  "جديد",
  "غير نشط",
];

function Clients() {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Number(searchParams.get("page")) || 1;
  const search = searchParams.get("search") || undefined;
  const source = searchParams.get("source") || undefined;
  const dateOfBirthFrom = searchParams.get("date_of_birth_from") || undefined;
  const dateOfBirthTo = searchParams.get("date_of_birth_to") || undefined;

  const [filterStatus, setFilterStatus] = useState<ClientUiStatus | "الكل">(
    "الكل",
  );
  const [viewMode, setViewMode] = useState<"table" | "cards">("table");
  const [selectedClient, setSelectedClient] = useState<TClientResponse | null>(
    null,
  );
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [editTarget, setEditTarget] = useState<TClientResponse | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<TClientResponse | null>(null);
  const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);

  const listParams = useMemo(
    () => ({
      ...(search ? { search } : {}),
      ...(source ? { source } : {}),
      ...(dateOfBirthFrom ? { date_of_birth_from: dateOfBirthFrom } : {}),
      ...(dateOfBirthTo ? { date_of_birth_to: dateOfBirthTo } : {}),
    }),
    [search, source, dateOfBirthFrom, dateOfBirthTo],
  );

  const perPage = 10;
  const { data, isPending, isError, error } = useQuery(
    useGetClientsQueryOptions(
      page,
      perPage,
      Object.keys(listParams).length ? listParams : undefined,
    ),
  );

  const { data: statsData } = useQuery(
    useGetClientsQueryOptions(
      1,
      500,
      Object.keys(listParams).length ? listParams : undefined,
    ),
  );

  const { mutate: exportClientsToCSV, isPending: isExporting } = useMutation(
    useExportClientsToCSVMutationOptions(),
  );

  const setFilter = useCallback(
    (key: string, value: string) => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (value?.trim()) next.set(key, value.trim());
      else next.delete(key);
      next.set("page", "1");
      return next;
    });
    },
    [setSearchParams],
  );

  const clearFilters = useCallback(() => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.delete("search");
      next.delete("source");
      next.delete("date_of_birth_from");
      next.delete("date_of_birth_to");
      next.set("page", "1");
      return next;
    });
  }, [setSearchParams]);

  const handleExport = () => {
    exportClientsToCSV(listParams, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "clients.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير العملاء بنجاح");
      },
      onError: (err: Error) => {
        toast.error("خطأ أثناء تصدير العملاء.", {
          description: err.message,
        });
      },
    });
  };

  const pageRows = data?.data ?? [];
  const filteredRows = useMemo(() => {
    return pageRows.filter((c) => {
      if (filterStatus === "الكل") return true;
      return deriveClientStatus(c) === filterStatus;
    });
  }, [pageRows, filterStatus]);

  const statsSample = statsData?.data ?? [];
  const totalClients = statsData?.total ?? 0;
  const vipCount = useMemo(
    () => statsSample.filter((c) => deriveClientStatus(c) === "VIP").length,
    [statsSample],
  );
  const newCount = useMemo(
    () => statsSample.filter((c) => deriveClientStatus(c) === "جديد").length,
    [statsSample],
  );

  const openEdit = (c: TClientResponse) => {
    setEditTarget(c);
    setIsEditModalOpen(true);
  };

  const openDelete = (c: TClientResponse) => {
    setDeleteTarget(c);
    setIsDeleteModalOpen(true);
  };

  const searchInputValue = search ?? "";

  return (
    <div dir="rtl" className="w-full space-y-5 clients-fade-in p-1">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
          <h2 className="text-2xl font-bold" style={{ color: "#1A1A2E" }}>
            العملاء
          </h2>
          <p className="text-sm mt-0.5" style={{ color: "#9CA3AF" }}>
            إجمالي {totalClients.toLocaleString("ar-EG")} عميل
          </p>
          </div>
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={() => setShowAdvancedFilters((v) => !v)}
            className="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm cursor-pointer whitespace-nowrap border border-[#F0EDE5] bg-white"
            style={{ color: "#374151" }}
          >
            <i className="ri-filter-3-line" />
            {showAdvancedFilters ? "إخفاء الفلاتر" : "فلاتر متقدمة"}
          </button>
          <button
            type="button"
              onClick={handleExport}
              disabled={isExporting}
            className="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm cursor-pointer whitespace-nowrap border border-[#F0EDE5] bg-white disabled:opacity-50"
            style={{ color: "#374151" }}
          >
            <i className="ri-download-2-line" />
            {isExporting ? "جاري التصدير..." : "تصدير Excel"}
          </button>
          <button
            type="button"
            data-onboarding-target="customer_setup"
            onClick={() => setIsCreateModalOpen(true)}
            className="flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm cursor-pointer whitespace-nowrap gold-gradient-btn"
            style={{ color: "#1A1A2E" }}
          >
            <i className="ri-user-add-line" />
            عميل جديد
          </button>
        </div>
          </div>

      {showAdvancedFilters && (
        <div
          className="rounded-2xl p-4 space-y-3 clients-slide-down"
          style={{ background: "white", border: "1px solid #F0EDE5" }}
        >
          <div className="flex flex-wrap items-center gap-4">
            <div className="flex flex-col gap-1 min-w-[160px]">
              <label className="text-xs font-semibold" style={{ color: "#6B7280" }}>
                المصدر
              </label>
              <input
                type="text"
                placeholder="مصدر العميل"
                value={source ?? ""}
                onChange={(e) => setFilter("source", e.target.value)}
                className="w-full px-3 py-2 text-sm rounded-xl outline-none"
                style={{ background: "#F5F4F0", border: "1.5px solid transparent" }}
              />
            </div>
            <div className="flex flex-col gap-1 min-w-[140px]">
              <label className="text-xs font-semibold" style={{ color: "#6B7280" }}>
                تاريخ الميلاد من
              </label>
              <input
                type="date"
                value={dateOfBirthFrom ?? ""}
                onChange={(e) =>
                  setFilter("date_of_birth_from", e.target.value)
                }
                className="w-full px-3 py-2 text-sm rounded-xl outline-none"
                style={{ background: "#F5F4F0", border: "1.5px solid transparent" }}
              />
            </div>
            <div className="flex flex-col gap-1 min-w-[140px]">
              <label className="text-xs font-semibold" style={{ color: "#6B7280" }}>
                إلى
              </label>
              <input
                type="date"
                value={dateOfBirthTo ?? ""}
                onChange={(e) => setFilter("date_of_birth_to", e.target.value)}
                className="w-full px-3 py-2 text-sm rounded-xl outline-none"
                style={{ background: "#F5F4F0", border: "1.5px solid transparent" }}
              />
            </div>
            <button
              type="button"
              onClick={clearFilters}
              className="self-end px-4 py-2 rounded-xl text-sm font-semibold"
              style={{ background: "#F5F4F0", color: "#6B7280" }}
            >
              مسح الفلاتر
            </button>
          </div>
        </div>
      )}

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          {
            label: "إجمالي العملاء",
            value: totalClients.toLocaleString("ar-EG"),
            icon: "ri-group-line",
            color: "#3B82F6",
            bg: "#EFF6FF",
          },
          {
            label: "عملاء VIP",
            value: vipCount.toLocaleString("ar-EG"),
            icon: "ri-vip-crown-line",
            color: "#D97706",
            bg: "#FEF9E7",
          },
          {
            label: "عملاء جدد (عيّنة)",
            value: newCount.toLocaleString("ar-EG"),
            icon: "ri-user-add-line",
            color: "#10B981",
            bg: "#ECFDF5",
          },
          {
            label: "إجمالي المبيعات",
            value: "—",
            icon: "ri-money-dollar-circle-line",
            color: "#D4AF37",
            bg: "#FFFBEB",
          },
        ].map((s) => (
          <div
            key={s.label}
            className="rounded-2xl p-4 customers-card-hover"
            style={{ background: "white", border: "1px solid #F0EDE5" }}
          >
            <div className="flex items-center gap-3">
              <div
                className="w-10 h-10 rounded-xl flex items-center justify-center"
                style={{ background: s.bg }}
              >
                <i className={`${s.icon} text-lg`} style={{ color: s.color }} />
              </div>
              <div>
                <p className="text-lg font-bold" style={{ color: "#1A1A2E" }}>
                  {s.value}
                </p>
                <p className="text-xs" style={{ color: "#9CA3AF" }}>
                  {s.label}
                </p>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Filters row */}
      <div
        className="rounded-2xl p-4 flex flex-wrap items-center gap-3"
        style={{ background: "white", border: "1px solid #F0EDE5" }}
      >
        <div
          className="flex items-center gap-1 p-1 rounded-xl overflow-x-auto max-w-full"
          style={{ background: "#F5F4F0" }}
        >
          {statuses.map((s) => (
            <button
              key={s}
              type="button"
              onClick={() => setFilterStatus(s)}
              className="px-3 py-1.5 rounded-lg text-xs font-semibold cursor-pointer transition-all whitespace-nowrap"
              style={{
                background: filterStatus === s ? "white" : "transparent",
                color: filterStatus === s ? "#1A1A2E" : "#6B7280",
                fontWeight: filterStatus === s ? 700 : 500,
              }}
            >
              {s}
            </button>
          ))}
        </div>
        <div className="flex-1 relative min-w-[200px]">
          <i
            className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-sm pointer-events-none"
            style={{ color: "#9CA3AF" }}
          />
          <input
            type="text"
            value={searchInputValue}
            onChange={(e) => setFilter("search", e.target.value)}
            placeholder="بحث باسم العميل أو رقم الهاتف..."
            className="w-full pr-8 pl-3 py-2 text-sm rounded-xl outline-none"
            style={{ background: "#F5F4F0", border: "1.5px solid transparent" }}
            onFocus={(e) => {
              e.target.style.borderColor = "#D4AF37";
            }}
            onBlur={(e) => {
              e.target.style.borderColor = "transparent";
            }}
          />
        </div>
        <div
          className="flex items-center gap-1 p-1 rounded-xl shrink-0"
          style={{ background: "#F5F4F0" }}
        >
          <button
            type="button"
            onClick={() => setViewMode("table")}
            className="w-8 h-8 rounded-lg flex items-center justify-center cursor-pointer transition-colors"
            style={{
              background: viewMode === "table" ? "white" : "transparent",
              color: viewMode === "table" ? "#1A1A2E" : "#9CA3AF",
            }}
            title="جدول"
          >
            <i className="ri-table-line text-sm" />
          </button>
          <button
            type="button"
            onClick={() => setViewMode("cards")}
            className="w-8 h-8 rounded-lg flex items-center justify-center cursor-pointer transition-colors"
            style={{
              background: viewMode === "cards" ? "white" : "transparent",
              color: viewMode === "cards" ? "#1A1A2E" : "#9CA3AF",
            }}
            title="بطاقات"
          >
            <i className="ri-layout-grid-line text-sm" />
          </button>
        </div>
      </div>

      {isError && (
        <div
          className="rounded-2xl p-6 text-center text-sm"
          style={{ background: "#FEF2F2", color: "#B91C1C", border: "1px solid #FECACA" }}
        >
          حدث خطأ أثناء تحميل البيانات. {error?.message}
        </div>
      )}

      {!isError && viewMode === "table" && (
        <div
          className="rounded-2xl overflow-hidden"
          style={{ background: "white", border: "1px solid #F0EDE5" }}
        >
          <div className="overflow-x-auto">
            <div className="min-w-[900px]">
              <div
                className="grid grid-cols-8 gap-3 px-5 py-3.5 text-xs font-bold uppercase tracking-wider"
                style={{
                  background: "#F8F7F4",
                  color: "#9CA3AF",
                  borderBottom: "1px solid #F0EDE5",
                }}
              >
                <span>الكود</span>
                <span className="col-span-2">الاسم</span>
                <span>الهاتف</span>
                <span>المدينة</span>
                <span>الطلبات</span>
                <span>الحالة</span>
                <span className="text-center">الإجراءات</span>
              </div>
                  {isPending ? (
                <div className="px-5 py-2 space-y-0">
                  {Array.from({ length: 5 }).map((_, i) => (
                    <div
                      key={i}
                      className="grid grid-cols-8 gap-3 px-0 py-4 items-center"
                      style={{
                        borderBottom:
                          i < 4 ? "1px solid #F5F4F0" : "none",
                      }}
                    >
                      <Skeleton className="h-4 w-14" />
                      <div className="col-span-2 flex gap-2">
                        <Skeleton className="h-9 w-9 rounded-full shrink-0" />
                        <div className="flex-1 space-y-2">
                          <Skeleton className="h-4 w-full max-w-[140px]" />
                          <Skeleton className="h-3 w-20" />
                        </div>
                      </div>
                      <Skeleton className="h-4 w-24" />
                      <Skeleton className="h-4 w-20" />
                      <Skeleton className="h-4 w-12" />
                      <Skeleton className="h-6 w-16 rounded-full" />
                      <div className="flex justify-center gap-1.5">
                        <Skeleton className="h-8 w-8 rounded-lg" />
                        <Skeleton className="h-8 w-8 rounded-lg" />
                        <Skeleton className="h-8 w-8 rounded-lg" />
                      </div>
                    </div>
                  ))}
                </div>
              ) : filteredRows.length === 0 ? (
                <div className="px-5 py-16 text-center text-sm text-muted-foreground">
                  لا يوجد عملاء لعرضها.
                </div>
              ) : (
                filteredRows.map((c, i) => {
                  const name = getClientDisplayName(c);
                  const st = deriveClientStatus(c);
                  const cfg = statusConfig[st];
                  return (
                    <div
                      key={c.id}
                      role="button"
                      tabIndex={0}
                      onKeyDown={(e) => {
                        if (e.key === "Enter" || e.key === " ")
                          setSelectedClient(c);
                      }}
                      className="grid grid-cols-8 gap-3 px-5 py-4 items-center cursor-pointer hover:bg-gray-50 transition-colors"
                      style={{
                        borderBottom:
                          i < filteredRows.length - 1
                            ? "1px solid #F5F4F0"
                            : "none",
                      }}
                      onClick={() => setSelectedClient(c)}
                    >
                      <span
                        className="text-xs font-bold"
                        style={{ color: "#D4AF37" }}
                      >
                        {clientCode(c.id)}
                      </span>
                      <div className="col-span-2 flex items-center gap-2.5 min-w-0">
                        <div
                          className="w-9 h-9 rounded-full shrink-0 flex items-center justify-center font-bold text-sm"
                          style={{
                            background:
                              "linear-gradient(135deg, #D4AF37, #F5E28A)",
                            color: "#1A1A2E",
                          }}
                        >
                          {name.charAt(0)}
                        </div>
                        <div className="min-w-0">
                          <p
                            className="text-sm font-semibold truncate"
                            style={{ color: "#1A1A2E" }}
                          >
                            {name}
                          </p>
                          <p className="text-xs" style={{ color: "#9CA3AF" }}>
                            منذ {joinDateLabel(c)}
                          </p>
                        </div>
                      </div>
                      <span className="text-sm" style={{ color: "#374151" }}>
                        {formatPhone(getPrimaryPhone(c), "-")}
                      </span>
                      <span className="text-sm" style={{ color: "#374151" }}>
                        {getClientCity(c)}
                      </span>
                      <div>
                        <p
                          className="text-sm font-bold"
                          style={{ color: "#1A1A2E" }}
                        >
                          —
                        </p>
                        <p className="text-xs" style={{ color: "#9CA3AF" }}>
                          —
                        </p>
                      </div>
                      <span
                        className="customers-status-badge"
                        style={{
                          background: cfg.bg,
                          color: cfg.color,
                          width: "fit-content",
                        }}
                      >
                        {st}
                            </span>
                      <div className="flex items-center justify-center gap-1.5">
                        <button
                          type="button"
                          className="w-8 h-8 rounded-lg flex items-center justify-center cursor-pointer"
                          style={{ background: "#EFF6FF", color: "#3B82F6" }}
                          onClick={(e) => {
                            e.stopPropagation();
                            setSelectedClient(c);
                          }}
                          title="عرض"
                        >
                          <i className="ri-eye-line text-sm" />
                        </button>
                        <button
                          type="button"
                          className="w-8 h-8 rounded-lg flex items-center justify-center cursor-pointer"
                          style={{ background: "#ECFDF5", color: "#10B981" }}
                          onClick={(e) => {
                            e.stopPropagation();
                            openEdit(c);
                          }}
                              title="تعديل"
                        >
                          <i className="ri-edit-line text-sm" />
                        </button>
                        <button
                          type="button"
                          className="w-8 h-8 rounded-lg flex items-center justify-center cursor-pointer"
                          style={{ background: "#FEF2F2", color: "#DC2626" }}
                          onClick={(e) => {
                            e.stopPropagation();
                            openDelete(c);
                          }}
                              title="حذف"
                        >
                          <i className="ri-delete-bin-line text-sm" />
                        </button>
                      </div>
                    </div>
                  );
                })
              )}
            </div>
          </div>
          <div
            className="px-4 py-3 border-t"
            style={{ borderColor: "#F0EDE5" }}
          >
            <CustomPagination
              totalElementsLabel="إجمالي العملاء"
              totalElements={data?.total}
              totalPages={data?.total_pages}
              isLoading={isPending}
            />
          </div>
        </div>
      )}

      {!isError && viewMode === "cards" && (
        <>
          {isPending ? (
            <div className="rounded-2xl p-8 border border-[#F0EDE5] bg-white text-center text-muted-foreground">
              جاري التحميل...
            </div>
          ) : filteredRows.length === 0 ? (
            <div className="rounded-2xl p-16 border border-[#F0EDE5] bg-white text-center text-muted-foreground">
              لا يوجد عملاء لعرضها.
                          </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {filteredRows.map((c) => {
                const name = getClientDisplayName(c);
                const st = deriveClientStatus(c);
                const cfg = statusConfig[st];
                return (
                  <div
                    key={c.id}
                    className="rounded-2xl p-5 cursor-pointer customers-card-hover"
                    style={{
                      background: "white",
                      border: "1px solid #F0EDE5",
                    }}
                    onClick={() => setSelectedClient(c)}
                  >
                    <div className="flex items-start justify-between mb-4 gap-2">
                      <div className="flex items-center gap-3 min-w-0">
                        <div
                          className="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg shrink-0"
                          style={{
                            background:
                              "linear-gradient(135deg, #D4AF37, #F5E28A)",
                            color: "#1A1A2E",
                          }}
                        >
                          {name.charAt(0)}
                        </div>
                        <div className="min-w-0">
                          <p
                            className="font-bold text-sm truncate"
                            style={{ color: "#1A1A2E" }}
                          >
                            {name}
                          </p>
                          <p className="text-xs" style={{ color: "#9CA3AF" }}>
                            {getClientCity(c)}
                          </p>
                        </div>
                      </div>
                      <span
                        className="customers-status-badge shrink-0"
                        style={{ background: cfg.bg, color: cfg.color }}
                      >
                        {st}
                      </span>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      {[
                        {
                          label: "الطلبات",
                          value: "—",
                        },
                        {
                          label: "الإنفاق الكلي",
                          value: "—",
                        },
                        {
                          label: "الهاتف",
                          value: formatPhone(getPrimaryPhone(c), "-"),
                        },
                        {
                          label: "تاريخ التسجيل",
                          value: joinDateLabel(c),
                        },
                      ].map((item) => (
                        <div
                          key={item.label}
                          className="rounded-xl p-3"
                          style={{ background: "#F8F7F4" }}
                        >
                          <p className="text-xs" style={{ color: "#9CA3AF" }}>
                            {item.label}
                          </p>
                          <p
                            className="text-sm font-bold mt-0.5 wrap-break-word"
                            style={{ color: "#1A1A2E" }}
                          >
                            {item.value}
                          </p>
                        </div>
                      ))}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
          <div
            className="rounded-2xl px-4 py-3 bg-white border"
            style={{ borderColor: "#F0EDE5" }}
          >
          <CustomPagination
            totalElementsLabel="إجمالي العملاء"
            totalElements={data?.total}
            totalPages={data?.total_pages}
            isLoading={isPending}
          />
          </div>
        </>
      )}

      {/* Detail modal — project layout */}
      {selectedClient && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4"
          style={{ background: "rgba(0,0,0,0.4)" }}
          onClick={() => setSelectedClient(null)}
          role="presentation"
        >
          <div
            className="w-full max-w-md rounded-2xl p-6 clients-slide-down max-h-[90vh] overflow-y-auto"
            style={{ background: "white" }}
            onClick={(e) => e.stopPropagation()}
            role="dialog"
            aria-modal="true"
          >
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-bold text-lg" style={{ color: "#1A1A2E" }}>
                ملف العميل
              </h3>
              <button
                type="button"
                onClick={() => setSelectedClient(null)}
                className="w-8 h-8 rounded-xl flex items-center justify-center cursor-pointer"
                style={{ background: "#F5F4F0" }}
              >
                <i className="ri-close-line" />
              </button>
            </div>
            <div
              className="flex items-center gap-4 mb-5 p-4 rounded-xl"
              style={{ background: "#F8F7F4" }}
            >
              <div
                className="w-14 h-14 rounded-full flex items-center justify-center font-bold text-2xl shrink-0"
                style={{
                  background: "linear-gradient(135deg, #D4AF37, #F5E28A)",
                  color: "#1A1A2E",
                }}
              >
                {getClientDisplayName(selectedClient).charAt(0)}
              </div>
              <div>
                <p className="font-bold text-base" style={{ color: "#1A1A2E" }}>
                  {getClientDisplayName(selectedClient)}
                </p>
                <p className="text-sm" style={{ color: "#9CA3AF" }}>
                  {getClientCity(selectedClient)} · انضم {joinDateLabel(selectedClient)}
                </p>
                <span
                  className="customers-status-badge mt-1"
                  style={{
                    background:
                      statusConfig[deriveClientStatus(selectedClient)].bg,
                    color: statusConfig[deriveClientStatus(selectedClient)].color,
                  }}
                >
                  {deriveClientStatus(selectedClient)}
                </span>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3 mb-4">
              {[
                {
                  label: "رقم الهاتف",
                  value: formatPhone(getPrimaryPhone(selectedClient), "-"),
                },
                {
                  label: "البريد الإلكتروني",
                  value: "—",
                },
                {
                  label: "الرقم القومي",
                  value: selectedClient.national_id ?? "—",
                },
                {
                  label: "تاريخ الميلاد",
                  value: selectedClient.date_of_birth ?? "—",
                },
                {
                  label: "المصدر",
                  value:
                    (selectedClient.source &&
                      CLIENT_SOURCE_LABELS[
                        selectedClient.source as keyof typeof CLIENT_SOURCE_LABELS
                      ]) ||
                    selectedClient.source ||
                    "—",
                },
                {
                  label: "الملاحظات",
                  value:
                    selectedClient.address?.notes?.trim() || "لا توجد ملاحظات",
                },
              ].map((row) => (
                <div
                  key={row.label}
                  className="rounded-xl p-3"
                  style={{ background: "#F8F7F4" }}
                >
                  <p className="text-xs" style={{ color: "#9CA3AF" }}>
                    {row.label}
                  </p>
                  <p
                    className="text-sm font-semibold mt-0.5 wrap-break-word"
                    style={{ color: "#1A1A2E" }}
                  >
                    {row.value}
                  </p>
                </div>
              ))}
            </div>
            <button
              type="button"
              className="w-full py-2.5 rounded-xl font-bold text-sm cursor-pointer gold-gradient-btn mb-2"
              style={{ color: "#1A1A2E" }}
              onClick={() => {
                navigate(`/orders/list?client_id=${selectedClient.id}`);
                setSelectedClient(null);
              }}
            >
              عرض سجل الطلبات
            </button>
            <button
              type="button"
              className="w-full py-2.5 rounded-xl font-semibold text-sm cursor-pointer border border-[#E5E7EB]"
              style={{ color: "#374151" }}
              onClick={() => {
                const c = selectedClient;
                setSelectedClient(null);
                openEdit(c);
              }}
            >
              تعديل البيانات
            </button>
          </div>
        </div>
      )}

      <CreateClientModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
      <EditClientModal
        client={editTarget}
        open={isEditModalOpen}
        onOpenChange={(open) => {
          setIsEditModalOpen(open);
          if (!open) setEditTarget(null);
        }}
      />
      <DeleteClientModal
        client={deleteTarget}
        open={isDeleteModalOpen}
        onOpenChange={(open) => {
          setIsDeleteModalOpen(open);
          if (!open) setDeleteTarget(null);
        }}
      />
    </div>
  );
}

export default Clients;
