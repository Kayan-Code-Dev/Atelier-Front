import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Download, Plus } from "lucide-react";
import { useMemo, useState } from "react";
import { toast } from "sonner";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { BranchesTableSkeleton } from "./BranchesTableSkeleton";
import { CreateBranchModal } from "./CreateBranchModal";
import { EditBranchModal } from "./EditBranchModal";

import {
  useDeleteBranchMutationOptions,
  useExportBranchesToCSVQueryOptions,
  useGetBranchesQueryOptions,
} from "@/api/v2/branches/branches.hooks";
import { TBranchResponse } from "@/api/v2/branches/branches.types";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import CustomPagination from "@/components/custom/CustomPagination";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useSearchParams } from "react-router";

const statusColors: Record<string, string> = {
  نشط: "bg-emerald-50 text-emerald-700 border-emerald-200",
  مغلق: "bg-red-50 text-red-700 border-red-200",
  "قيد الإنشاء": "bg-amber-50 text-amber-700 border-amber-200",
};

type BranchUiStatus = keyof typeof statusColors;

function normalizeBranchStatus(b: TBranchResponse): BranchUiStatus {
  const raw = b.status?.trim();
  if (!raw) return "نشط";
  const lower = raw.toLowerCase();
  if (raw === "مغلق" || lower === "closed" || lower === "inactive") {
    return "مغلق";
  }
  if (
    raw === "قيد الإنشاء" ||
    raw.includes("قيد الإنشاء") ||
    lower === "pending" ||
    lower === "building"
  ) {
    return "قيد الإنشاء";
  }
  if (raw.includes("قيد")) {
    return "قيد الإنشاء";
  }
  if (raw === "نشط" || lower === "active") return "نشط";
  return "نشط";
}

function branchImageUrl(b: TBranchResponse): string {
  return (b.image_url || b.image) ?? "";
}

function cityName(b: TBranchResponse): string {
  return b.address?.city?.name?.trim() || "—";
}

function streetLine(b: TBranchResponse): string {
  const s = [b.address?.street, b.address?.building].filter(Boolean).join(" ");
  return s || "";
}

function formatCurrencyLine(b: TBranchResponse): string {
  const parts = [b.currency_name, b.currency_code, b.currency_symbol].filter(
    Boolean
  );
  return parts.length ? parts.join(" ") : "—";
}

function vatSummary(b: TBranchResponse): string {
  if (!b.vat_enabled) return "غير مفعّل";
  if (b.vat_type && b.vat_value != null) {
    return b.vat_type === "percentage"
      ? `${b.vat_value}%`
      : `${b.vat_value} ثابت`;
  }
  return "مفعّل";
}

function shortText(s: string | null | undefined, max: number): string {
  const t = s?.trim();
  if (!t) return "—";
  return t.length > max ? `${t.slice(0, max)}…` : t;
}

function BranchManger() {
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Number(searchParams.get("page")) || 1;
  const search = searchParams.get("search") || undefined;
  const vatEnabledParam = searchParams.get("vat_enabled");

  const listParams = useMemo(() => {
    const p: { search?: string; vat_enabled?: boolean } = {};
    if (search?.trim()) p.search = search.trim();
    if (vatEnabledParam === "true") p.vat_enabled = true;
    if (vatEnabledParam === "false") p.vat_enabled = false;
    return Object.keys(p).length ? p : undefined;
  }, [search, vatEnabledParam]);

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [selectedBranch, setSelectedBranch] = useState<TBranchResponse | null>(
    null
  );
  const [viewMode, setViewMode] = useState<"cards" | "table">("cards");
  const [filterCity, setFilterCity] = useState("الكل");
  const [filterStatus, setFilterStatus] = useState("الكل");
  const [filterInventory, setFilterInventory] = useState("الكل");

  const filterVatLabel =
    vatEnabledParam === "true"
      ? "مفعّلة"
      : vatEnabledParam === "false"
        ? "غير مفعّلة"
        : "الكل";

  const { mutate: exportBranchesToCSV, isPending: isExporting } = useMutation(
    useExportBranchesToCSVQueryOptions()
  );

  const setFilter = (key: string, value: string) => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (value !== "" && value !== "all") next.set(key, value);
      else next.delete(key);
      next.set("page", "1");
      return next;
    });
  };

  const setVatFilter = (v: string) => {
    if (v === "الكل") setFilter("vat_enabled", "all");
    else if (v === "مفعّلة") setFilter("vat_enabled", "true");
    else setFilter("vat_enabled", "false");
  };

  const clearFilters = () => {
    setFilterCity("الكل");
    setFilterStatus("الكل");
    setFilterInventory("الكل");
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.delete("search");
      next.delete("vat_enabled");
      next.set("page", "1");
      return next;
    });
  };

  const per_page = 10;
  const { data, isPending, isError, error } = useQuery(
    useGetBranchesQueryOptions(page, per_page, listParams)
  );

  const { mutate: deleteBranch, isPending: isDeleting } = useMutation(
    useDeleteBranchMutationOptions()
  );

  const branches = data?.data ?? [];

  const cities = useMemo(() => {
    const set = new Set<string>();
    for (const b of branches) {
      const c = b.address?.city?.name?.trim();
      if (c) set.add(c);
    }
    return ["الكل", ...Array.from(set).sort((a, b) => a.localeCompare(b, "ar"))];
  }, [branches]);

  const inventories = useMemo(() => {
    const set = new Set<string>();
    for (const b of branches) {
      const n = b.inventory?.name?.trim();
      if (n) set.add(n);
    }
    return ["الكل", ...Array.from(set).sort((a, b) => a.localeCompare(b, "ar"))];
  }, [branches]);

  const filteredBranches = useMemo(() => {
    return branches.filter((b) => {
      const c = cityName(b);
      const matchCity = filterCity === "الكل" || c === filterCity;
      const inv = b.inventory?.name?.trim() || "";
      const matchInv =
        filterInventory === "الكل" || inv === filterInventory;
      const st = normalizeBranchStatus(b);
      const matchStatus = filterStatus === "الكل" || filterStatus === st;
      return matchCity && matchInv && matchStatus;
    });
  }, [branches, filterCity, filterInventory, filterStatus]);

  const stats = useMemo(() => {
    const total = data?.total ?? 0;
    let active = 0;
    let building = 0;
    let closed = 0;
    for (const b of branches) {
      const s = normalizeBranchStatus(b);
      if (s === "نشط") active += 1;
      else if (s === "قيد الإنشاء") building += 1;
      else if (s === "مغلق") closed += 1;
    }
    const vatEnabledCount = branches.filter((b) => b.vat_enabled).length;
    return { total, active, building, closed, vatEnabledCount };
  }, [data?.total, branches]);

  const handleOpenEdit = (branch: TBranchResponse) => {
    setSelectedBranch(branch);
    setIsEditModalOpen(true);
  };
  const handleOpenDelete = (branch: TBranchResponse) => {
    setSelectedBranch(branch);
    setIsDeleteModalOpen(true);
  };

  const handleDelete = (onCloseModal: () => void) => {
    if (selectedBranch) {
      deleteBranch(selectedBranch.id, {
        onSuccess: () => {
          toast.success("تم حذف الفرع بنجاح");
          onCloseModal();
        },
        onError: (err: unknown) => {
          const message = err instanceof Error ? err.message : String(err);
          toast.error("خطأ أثناء حذف الفرع. الرجاء المحاولة مرة أخرى.", {
            description: message,
          });
        },
      });
    }
  };

  const handleExport = () => {
    exportBranchesToCSV(listParams, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "branches.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير الفروع بنجاح");
      },
      onError: (err: unknown) => {
        const message = err instanceof Error ? err.message : String(err);
        toast.error("خطأ أثناء تصدير الفروع. الرجاء المحاولة مرة أخرى.", {
          description: message,
        });
      },
    });
  };

  const showClearFilters =
    Boolean(search?.trim()) ||
    vatEnabledParam === "true" ||
    vatEnabledParam === "false" ||
    filterCity !== "الكل" ||
    filterStatus !== "الكل" ||
    filterInventory !== "الكل";

  return (
    <div dir="rtl" className="w-full p-6 min-h-screen bg-gray-50/50">
      <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-xl font-bold text-gray-900">إدارة الفروع</h1>
          <p className="text-sm text-gray-400 mt-0.5">
            عرض وإدارة جميع فروع الشركة
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={handleExport}
            disabled={isExporting}
            className="px-3 py-2 border border-gray-200 bg-white text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap flex items-center gap-2 disabled:opacity-50"
          >
            <Download className="h-4 w-4" />
            {isExporting ? "جاري التصدير..." : "تصدير Excel"}
          </button>
          <button
            type="button"
            data-onboarding-target="branch_setup"
            onClick={() => setIsCreateModalOpen(true)}
            className="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors whitespace-nowrap flex items-center gap-2"
          >
            <Plus className="h-4 w-4" />
            فرع جديد
          </button>
        </div>
      </div>

      {isError && (
        <div className="mb-6 rounded-xl border border-red-100 bg-red-50/80 p-4 text-sm text-red-700">
          حدث خطأ أثناء تحميل البيانات. الرجاء المحاولة مرة أخرى.
          {error?.message ? ` (${error.message})` : null}
        </div>
      )}

      {!isError && (
        <>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div className="bg-white rounded-xl border border-gray-100 p-4 col-span-1">
              <div className="flex items-center justify-between mb-3">
                <span className="text-xs font-semibold text-gray-500">
                  توزيع الفروع
                </span>
                <div className="w-6 h-6 flex items-center justify-center text-emerald-600">
                  <i className="ri-map-pin-2-line text-sm" />
                </div>
              </div>
              <div className="flex items-end gap-4 flex-wrap">
                <div>
                  <p className="text-3xl font-bold text-gray-900">
                    {isPending ? "…" : stats.total}
                  </p>
                  <p className="text-xs text-gray-400 mt-0.5">إجمالي الفروع</p>
                </div>
                <div className="flex flex-col gap-1 mb-0.5">
                  <div className="flex items-center gap-1.5">
                    <span className="w-2 h-2 rounded-full bg-emerald-400 shrink-0" />
                    <span className="text-xs text-gray-500">
                      {isPending ? "…" : `${stats.active} نشط`}
                    </span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <span className="w-2 h-2 rounded-full bg-amber-400 shrink-0" />
                    <span className="text-xs text-gray-500">
                      {isPending ? "…" : `${stats.building} قيد الإنشاء`}
                    </span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <span className="w-2 h-2 rounded-full bg-red-400 shrink-0" />
                    <span className="text-xs text-gray-500">
                      {isPending ? "…" : `${stats.closed} مغلق`}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-xl border border-gray-100 p-4">
              <div className="flex items-center justify-between mb-3">
                <span className="text-xs font-semibold text-gray-500">
                  الموظفون والطلبات
                </span>
                <div className="w-6 h-6 flex items-center justify-center text-teal-600">
                  <i className="ri-team-line text-sm" />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <p className="text-2xl font-bold text-gray-400">—</p>
                  <p className="text-xs text-gray-400">إجمالي الموظفين</p>
                </div>
                <div>
                  <p className="text-2xl font-bold text-gray-400">—</p>
                  <p className="text-xs text-gray-400">إجمالي الطلبات</p>
                </div>
              </div>
              <p className="text-[10px] text-gray-400 mt-2">
                غير متوفر من واجهة الفروع حالياً
              </p>
            </div>

            <div className="bg-white rounded-xl border border-gray-100 p-4">
              <div className="flex items-center justify-between mb-3">
                <span className="text-xs font-semibold text-gray-500">
                  الإيرادات الكلية
                </span>
                <div className="w-6 h-6 flex items-center justify-center text-emerald-600">
                  <i className="ri-money-dollar-circle-line text-sm" />
                </div>
              </div>
              <p className="text-2xl font-bold text-gray-400 mb-1">—</p>
              <p className="text-xs text-gray-400 mb-3">غير متوفر</p>
              <div className="flex items-center gap-2 flex-wrap">
                <span className="text-xs text-gray-500">ضريبة القيمة المضافة:</span>
                <span className="text-xs font-semibold text-emerald-600">
                  {isPending ? "…" : `${stats.vatEnabledCount} فرع مفعّل`}
                </span>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl border border-gray-100 p-4 mb-5 flex items-center gap-3 flex-wrap">
            <div className="relative flex-1 min-w-48">
              <div className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center text-gray-400 pointer-events-none">
                <i className="ri-search-line text-sm" />
              </div>
              <Input
                type="text"
                value={search ?? ""}
                onChange={(e) => setFilter("search", e.target.value)}
                placeholder="بحث بالاسم، الكود، الهاتف، العملة..."
                className="pr-9 border-gray-200 rounded-lg focus-visible:ring-emerald-100 focus-visible:border-emerald-400"
              />
            </div>

            <Select value={filterCity} onValueChange={setFilterCity}>
              <SelectTrigger className="w-[160px] border-gray-200 rounded-lg">
                <SelectValue placeholder="كل المدن" />
              </SelectTrigger>
              <SelectContent>
                {cities.map((c) => (
                  <SelectItem key={c} value={c}>
                    {c === "الكل" ? "كل المدن" : c}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            <Select value={filterStatus} onValueChange={setFilterStatus}>
              <SelectTrigger className="w-[150px] border-gray-200 rounded-lg">
                <SelectValue placeholder="كل الحالات" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="الكل">كل الحالات</SelectItem>
                <SelectItem value="نشط">نشط</SelectItem>
                <SelectItem value="قيد الإنشاء">قيد الإنشاء</SelectItem>
                <SelectItem value="مغلق">مغلق</SelectItem>
              </SelectContent>
            </Select>

            <Select value={filterInventory} onValueChange={setFilterInventory}>
              <SelectTrigger className="w-[160px] border-gray-200 rounded-lg">
                <SelectValue placeholder="كل المخازن" />
              </SelectTrigger>
              <SelectContent>
                {inventories.map((inv) => (
                  <SelectItem key={inv} value={inv}>
                    {inv === "الكل" ? "كل المخازن" : inv}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            <Select value={filterVatLabel} onValueChange={setVatFilter}>
              <SelectTrigger className="w-[180px] border-gray-200 rounded-lg">
                <SelectValue placeholder="ضريبة القيمة المضافة" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="الكل">ضريبة القيمة المضافة</SelectItem>
                <SelectItem value="مفعّلة">مفعّلة</SelectItem>
                <SelectItem value="غير مفعّلة">غير مفعّلة</SelectItem>
              </SelectContent>
            </Select>

            {showClearFilters ? (
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="text-xs text-gray-500"
                onClick={clearFilters}
              >
                مسح الفلاتر
              </Button>
            ) : null}

            <div className="flex items-center gap-1 border border-gray-200 rounded-lg p-0.5 mr-auto">
              <button
                type="button"
                title="بطاقات"
                onClick={() => setViewMode("cards")}
                className={`w-7 h-7 flex items-center justify-center rounded-md transition-colors ${
                  viewMode === "cards"
                    ? "bg-emerald-600 text-white"
                    : "text-gray-400 hover:text-gray-600"
                }`}
              >
                <i className="ri-layout-grid-line text-sm" />
              </button>
              <button
                type="button"
                title="جدول"
                onClick={() => setViewMode("table")}
                className={`w-7 h-7 flex items-center justify-center rounded-md transition-colors ${
                  viewMode === "table"
                    ? "bg-emerald-600 text-white"
                    : "text-gray-400 hover:text-gray-600"
                }`}
              >
                <i className="ri-list-check text-sm" />
              </button>
            </div>

            <span className="text-xs text-gray-400">
              {filteredBranches.length} فرع في العرض
            </span>
          </div>

          {viewMode === "cards" && (
            <>
              {isPending ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                  {Array.from({ length: 6 }).map((_, i) => (
                    <div
                      key={i}
                      className="bg-white rounded-xl border border-gray-100 overflow-hidden animate-pulse"
                    >
                      <div className="h-36 bg-gray-100" />
                      <div className="p-4 space-y-3">
                        <div className="h-4 bg-gray-100 rounded w-2/3" />
                        <div className="h-3 bg-gray-100 rounded w-full" />
                        <div className="grid grid-cols-3 gap-2">
                          <div className="h-12 bg-gray-50 rounded-lg" />
                          <div className="h-12 bg-gray-50 rounded-lg" />
                          <div className="h-12 bg-gray-50 rounded-lg" />
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                  {filteredBranches.map((branch) => {
                    const img = branchImageUrl(branch);
                    const status = normalizeBranchStatus(branch);
                    return (
                      <div
                        key={branch.id}
                        className="bg-white rounded-xl border border-gray-100 overflow-hidden hover:border-emerald-200 transition-all group"
                      >
                        <div className="relative h-36 overflow-hidden bg-gray-100">
                          {img ? (
                            <img
                              src={img}
                              alt={branch.name}
                              className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300"
                            />
                          ) : (
                            <div className="w-full h-full flex items-center justify-center text-gray-300">
                              <i className="ri-store-2-line text-4xl" />
                            </div>
                          )}
                          <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent" />
                          <div className="absolute top-2 left-2">
                            <span
                              className={`text-[10px] font-semibold px-2 py-0.5 rounded-full border ${statusColors[status]}`}
                            >
                              {status}
                            </span>
                          </div>
                          <div className="absolute bottom-2 right-2">
                            <span className="text-xs font-mono text-white/90 bg-black/30 px-1.5 py-0.5 rounded">
                              {branch.branch_code}
                            </span>
                          </div>
                        </div>

                        <div className="p-4">
                          <div className="flex items-start justify-between mb-3 gap-2">
                            <div className="min-w-0">
                              <h3 className="text-sm font-bold text-gray-800 truncate">
                                {branch.name}
                              </h3>
                              <p className="text-xs text-gray-400 mt-0.5 flex items-center gap-1 line-clamp-2">
                                <i className="ri-map-pin-line text-[10px] shrink-0" />
                                {cityName(branch)}
                                {streetLine(branch)
                                  ? ` — ${streetLine(branch)}`
                                  : ""}
                              </p>
                            </div>
                            {branch.vat_enabled ? (
                              <div
                                className="w-6 h-6 flex items-center justify-center text-teal-600 bg-teal-50 rounded-lg shrink-0"
                                title="ضريبة القيمة المضافة مفعّلة"
                              >
                                <i className="ri-percent-line text-xs" />
                              </div>
                            ) : null}
                          </div>

                          <div className="grid grid-cols-3 gap-2 mb-4">
                            {[
                              {
                                label: "مخزن",
                                value: shortText(branch.inventory?.name, 10),
                                icon: "ri-archive-line",
                              },
                              {
                                label: "عملة",
                                value: shortText(formatCurrencyLine(branch), 12),
                                icon: "ri-exchange-dollar-line",
                              },
                              {
                                label: "هاتف",
                                value: shortText(branch.phone ?? undefined, 12),
                                icon: "ri-phone-line",
                              },
                            ].map((s) => (
                              <div
                                key={s.label}
                                className="bg-gray-50 rounded-lg p-2 text-center min-w-0"
                              >
                                <div className="w-4 h-4 flex items-center justify-center text-gray-400 mx-auto mb-0.5">
                                  <i className={`${s.icon} text-xs`} />
                                </div>
                                <p
                                  className="text-xs font-bold text-gray-700 truncate"
                                  title={String(s.value)}
                                >
                                  {s.value}
                                </p>
                                <p className="text-[10px] text-gray-400">
                                  {s.label}
                                </p>
                              </div>
                            ))}
                          </div>

                          <div className="flex items-center justify-between pt-3 border-t border-gray-50 gap-2">
                            <div className="flex items-center gap-2 min-w-0">
                              <div className="w-5 h-5 flex items-center justify-center bg-gray-100 rounded-full text-gray-500 shrink-0">
                                <i className="ri-user-star-line text-[10px]" />
                              </div>
                              <span className="text-xs text-gray-500 truncate">
                                {branch.phone || "—"}
                              </span>
                            </div>
                            <div className="flex items-center gap-1 shrink-0">
                              <button
                                type="button"
                                title="تعديل"
                                onClick={() => handleOpenEdit(branch)}
                                className="w-6 h-6 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 transition-colors"
                              >
                                <i className="ri-edit-line text-[10px]" />
                              </button>
                              <button
                                type="button"
                                title="حذف"
                                onClick={() => handleOpenDelete(branch)}
                                className="w-6 h-6 flex items-center justify-center rounded-lg border border-red-100 text-red-400 hover:bg-red-50 transition-colors"
                              >
                                <i className="ri-delete-bin-line text-[10px]" />
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    );
                  })}

                  <button
                    type="button"
                    onClick={() => setIsCreateModalOpen(true)}
                    className="bg-white rounded-xl border-2 border-dashed border-gray-200 min-h-72 flex flex-col items-center justify-center gap-3 hover:border-emerald-300 hover:bg-emerald-50/30 transition-all group"
                  >
                    <div className="w-10 h-10 flex items-center justify-center bg-gray-100 group-hover:bg-emerald-100 rounded-full text-gray-400 group-hover:text-emerald-600 transition-colors">
                      <i className="ri-add-line text-lg" />
                    </div>
                    <p className="text-sm text-gray-400 group-hover:text-emerald-600 transition-colors font-medium">
                      إضافة فرع جديد
                    </p>
                  </button>
                </div>
              )}

              {!isPending &&
                branches.length > 0 &&
                filteredBranches.length === 0 && (
                  <div className="py-20 text-center">
                    <div className="w-14 h-14 flex items-center justify-center bg-gray-100 rounded-full mx-auto mb-4">
                      <i className="ri-map-pin-2-line text-2xl text-gray-400" />
                    </div>
                    <p className="text-sm text-gray-400">
                      لا يوجد فروع مطابقة للفلاتر
                    </p>
                  </div>
                )}

              {!isPending && branches.length === 0 && (
                <div className="py-20 text-center">
                  <div className="w-14 h-14 flex items-center justify-center bg-gray-100 rounded-full mx-auto mb-4">
                    <i className="ri-map-pin-2-line text-2xl text-gray-400" />
                  </div>
                  <p className="text-sm text-gray-400">لا توجد فروع لعرضها</p>
                </div>
              )}
            </>
          )}

          {viewMode === "table" && (
            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
              <div className="table-responsive-wrapper">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-gray-50 border-b border-gray-100 hover:bg-gray-50">
                      {[
                        "#",
                        "الفرع",
                        "المدينة",
                        "المخزن",
                        "العملة",
                        "الضريبة",
                        "الحالة",
                        "إجراءات",
                      ].map((h) => (
                        <TableHead
                          key={h}
                          className="text-right px-4 py-3 text-xs font-semibold text-gray-500 whitespace-nowrap"
                        >
                          {h}
                        </TableHead>
                      ))}
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {isPending ? (
                      <BranchesTableSkeleton rows={5} />
                    ) : filteredBranches.length > 0 ? (
                      filteredBranches.map((branch, idx) => {
                        const status = normalizeBranchStatus(branch);
                        return (
                          <TableRow
                            key={branch.id}
                            className="border-b border-gray-50 hover:bg-gray-50/50"
                          >
                            <TableCell className="px-4 py-3 text-xs text-gray-400">
                              {(page - 1) * per_page + idx + 1}
                            </TableCell>
                            <TableCell className="px-4 py-3">
                              <div className="flex items-center gap-2">
                                <img
                                  src={
                                    branchImageUrl(branch) ||
                                    "/dressnmore-logo.jpg"
                                  }
                                  alt=""
                                  className="h-9 w-9 rounded-full object-cover border border-gray-100 shrink-0"
                                />
                                <div className="min-w-0">
                                  <span className="text-sm font-semibold text-gray-800 block truncate">
                                    {branch.name}
                                  </span>
                                  <span className="text-xs text-gray-400 font-mono">
                                    {branch.branch_code}
                                  </span>
                                </div>
                              </div>
                            </TableCell>
                            <TableCell className="px-4 py-3 text-xs text-gray-600">
                              <div className="flex items-center gap-1">
                                <i className="ri-map-pin-line text-gray-400 text-xs" />
                                {cityName(branch)}
                              </div>
                            </TableCell>
                            <TableCell className="px-4 py-3 text-xs text-gray-600">
                              {branch.inventory?.name || "—"}
                            </TableCell>
                            <TableCell className="px-4 py-3 text-sm font-semibold text-gray-800">
                              {formatCurrencyLine(branch)}
                            </TableCell>
                            <TableCell className="px-4 py-3">
                              {branch.vat_enabled ? (
                                <span className="text-xs text-teal-600 bg-teal-50 px-2 py-0.5 rounded-full font-medium">
                                  {vatSummary(branch)}
                                </span>
                              ) : (
                                <span className="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-full">
                                  غير مفعّلة
                                </span>
                              )}
                            </TableCell>
                            <TableCell className="px-4 py-3">
                              <span
                                className={`text-xs px-2 py-1 rounded-lg font-medium border ${statusColors[status]}`}
                              >
                                {status}
                              </span>
                            </TableCell>
                            <TableCell className="px-4 py-3">
                              <div className="flex items-center gap-1.5 justify-end">
                                <button
                                  type="button"
                                  title="تعديل"
                                  onClick={() => handleOpenEdit(branch)}
                                  className="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors"
                                >
                                  <i className="ri-edit-line text-xs" />
                                </button>
                                <button
                                  type="button"
                                  title="حذف"
                                  onClick={() => handleOpenDelete(branch)}
                                  className="w-7 h-7 flex items-center justify-center rounded-lg border border-red-100 text-red-400 hover:bg-red-50 transition-colors"
                                >
                                  <i className="ri-delete-bin-line text-xs" />
                                </button>
                              </div>
                            </TableCell>
                          </TableRow>
                        );
                      })
                    ) : branches.length === 0 ? (
                      <TableRow>
                        <TableCell
                          colSpan={8}
                          className="py-16 text-center text-sm text-gray-400"
                        >
                          <div className="w-12 h-12 flex items-center justify-center bg-gray-100 rounded-full mx-auto mb-3">
                            <i className="ri-map-pin-2-line text-xl text-gray-400" />
                          </div>
                          لا توجد فروع لعرضها
                        </TableCell>
                      </TableRow>
                    ) : (
                      <TableRow>
                        <TableCell
                          colSpan={8}
                          className="py-16 text-center text-sm text-gray-400"
                        >
                          <div className="w-12 h-12 flex items-center justify-center bg-gray-100 rounded-full mx-auto mb-3">
                            <i className="ri-map-pin-2-line text-xl text-gray-400" />
                          </div>
                          لا يوجد فروع مطابقة للفلاتر
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>
            </div>
          )}

          <div className="mt-6 flex justify-center">
            <CustomPagination
              totalElementsLabel="إجمالي الفروع"
              totalElements={data?.total}
              totalPages={data?.total_pages}
              isLoading={isPending}
            />
          </div>
        </>
      )}

      <CreateBranchModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
      <EditBranchModal
        branch={selectedBranch}
        open={isEditModalOpen}
        onOpenChange={setIsEditModalOpen}
      />
      <ControlledConfirmationModal
        open={isDeleteModalOpen}
        onOpenChange={setIsDeleteModalOpen}
        variantLayout="card"
        alertTitle="حذف الفرع"
        alertMessage={
          <>
            هل أنت متأكد أنك تريد حذف الفرع{" "}
            <strong>{selectedBranch?.name}</strong>؟
          </>
        }
        handleConfirmation={handleDelete}
        isPending={isDeleting}
        pendingLabel="جاري الحذف..."
        confirmLabel="تأكيد الحذف"
        variant="destructive"
      />
    </div>
  );
}

export default BranchManger;
