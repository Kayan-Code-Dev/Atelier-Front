import {
  useGetClothesQueryOptions,
  useDeleteClothesMutationOptions,
} from "@/api/v2/clothes/clothes.hooks";
import {
  TClothResponse,
  TClothesStatus,
  TGetClothesRequestParams,
} from "@/api/v2/clothes/clothes.types";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { CategoriesSelect } from "@/components/custom/CategoriesSelect";
import CustomPagination from "@/components/custom/CustomPagination";
import { SubcategoriesSelect } from "@/components/custom/SubcategoriesSelect";
import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { TableRow, TableCell } from "@/components/ui/table";
import useDebounce from "@/hooks/useDebounce";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useMemo } from "react";
import { useGetBranchesQueryOptions } from "@/api/v2/branches/branches.hooks";
import { useGetFactoriesQueryOptions } from "@/api/v2/factories/factories.hooks";
import { useGetWorkshopsQueryOptions } from "@/api/v2/workshop/workshops.hooks";
import { useCategoriesQueryOptions } from "@/api/v2/content-managment/category/category.hooks";
import { Pencil, Trash2, RotateCcw, Filter } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { useSearchParams, useNavigate } from "react-router";
import { ClothesTableSkeleton } from "./ClothesTableSkeleton";
import { EditClothModal } from "../EditClothModal";
import { TransferClothModal } from "../transfer/TransferClothModal";
import { toast } from "sonner";
import { ClothesProductsStats } from "./ClothesProductsStats";
import { ClothesGridView } from "./ClothesGridView";
import { clothPrice, locationVisual, statusVisual } from "./clothesViewUtils";

const STATUS_CHIPS: { value: "" | TClothesStatus; label: string; dot?: string }[] =
  [
    { value: "", label: "الكل" },
    { value: "ready_for_rent", label: "جاهز للإيجار", dot: "#10B981" },
    { value: "repairing", label: "قيد الإصلاح", dot: "#F59E0B" },
    { value: "damaged", label: "تالف", dot: "#EF4444" },
    { value: "die", label: "ميت", dot: "#9CA3AF" },
    { value: "rented", label: "محجوز", dot: "#3B82F6" },
  ];

const STATUS_DOT: Record<string, string> = {
  "": "transparent",
  ready_for_rent: "#10B981",
  repairing: "#F59E0B",
  damaged: "#EF4444",
  die: "#9CA3AF",
  rented: "#3B82F6",
};

function ClothesTableContent() {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const searchParamsRef = useRef(searchParams);
  searchParamsRef.current = searchParams;
  const page = Number(searchParams.get("page")) || 1;
  const per_page = 10;

  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [selectedCloth, setSelectedCloth] = useState<TClothResponse | null>(
    null
  );
  const [transferModalOpen, setTransferModalOpen] = useState(false);
  const [clothForTransfer, setClothForTransfer] =
    useState<TClothResponse | null>(null);

  const [searchInput, setSearchInput] = useState(
    () => searchParams.get("search") || ""
  );
  const [viewMode, setViewMode] = useState<"table" | "grid">("table");
  const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);

  const [idFilter, setIdFilter] = useState(() => searchParams.get("id") || "");
  const [categoryId, setCategoryId] = useState(
    () => searchParams.get("category_id") || ""
  );
  const [subcategoryIds, setSubcategoryIds] = useState<string[]>(() => {
    const subcatParam = searchParams.get("subcat_id");
    return subcatParam ? subcatParam.split(",").filter(Boolean) : [];
  });
  const [branchId, setBranchId] = useState(
    () => searchParams.get("branch_id") || searchParams.get("entity_id") || ""
  );
  const [breastSize, setBreastSize] = useState(
    () => searchParams.get("breast_size") || ""
  );
  const [waistSize, setWaistSize] = useState(
    () => searchParams.get("waist_size") || ""
  );
  const [sleeveSize, setSleeveSize] = useState(
    () => searchParams.get("sleeve_size") || ""
  );
  const [createdFrom, setCreatedFrom] = useState(
    () => searchParams.get("created_from") || ""
  );
  const [createdTo, setCreatedTo] = useState(
    () => searchParams.get("created_to") || ""
  );
  const [statusFilter, setStatusFilter] = useState<string>(
    () => searchParams.get("status") || ""
  );

  const debouncedSearch = useDebounce({ value: searchInput.trim(), delay: 400 });
  const debouncedIdFilter = useDebounce({ value: idFilter.trim(), delay: 400 });
  const debouncedCategoryId = useDebounce({ value: categoryId, delay: 300 });
  const debouncedSubcategoryIds = useDebounce({
    value: subcategoryIds,
    delay: 300,
  });
  const debouncedBranchId = useDebounce({ value: branchId, delay: 300 });
  const debouncedBreastSize = useDebounce({
    value: breastSize.trim(),
    delay: 400,
  });
  const debouncedWaistSize = useDebounce({
    value: waistSize.trim(),
    delay: 400,
  });
  const debouncedSleeveSize = useDebounce({
    value: sleeveSize.trim(),
    delay: 400,
  });
  const debouncedCreatedFrom = useDebounce({
    value: createdFrom.trim(),
    delay: 300,
  });
  const debouncedCreatedTo = useDebounce({
    value: createdTo.trim(),
    delay: 300,
  });
  const debouncedStatusFilter = useDebounce({
    value: statusFilter,
    delay: 300,
  });

  const queryParams: TGetClothesRequestParams = {
    page,
    per_page,
    ...(debouncedSearch && { search: debouncedSearch }),
    ...(debouncedIdFilter && { id: debouncedIdFilter }),
    ...(debouncedBranchId && { branch_id: Number(debouncedBranchId) }),
    ...(debouncedBreastSize && { breast_size: debouncedBreastSize }),
    ...(debouncedWaistSize && { waist_size: debouncedWaistSize }),
    ...(debouncedSleeveSize && { sleeve_size: debouncedSleeveSize }),
    ...(debouncedCreatedFrom && { created_from: debouncedCreatedFrom }),
    ...(debouncedCreatedTo && { created_to: debouncedCreatedTo }),
    ...(debouncedCategoryId && { category_id: Number(debouncedCategoryId) }),
    ...(debouncedSubcategoryIds.length > 0 && {
      subcat_id: debouncedSubcategoryIds.map(Number),
    }),
    ...(debouncedStatusFilter && {
      status: debouncedStatusFilter as TClothesStatus,
    }),
  };

  const { data, isPending, isError, error } = useQuery(
    useGetClothesQueryOptions(queryParams)
  );

  const { data: branchesData } = useQuery(useGetBranchesQueryOptions(1, 500));
  const { data: categoriesData } = useQuery(useCategoriesQueryOptions(1, 500));
  const { data: factoriesData } = useQuery(useGetFactoriesQueryOptions(1, 500));
  const { data: workshopsData } = useQuery(useGetWorkshopsQueryOptions(1, 500));

  const entityNamesMap = useMemo(() => {
    const map = new Map<string, string>();
    branchesData?.data?.forEach((b) => map.set(`branch-${b.id}`, b.name));
    factoriesData?.data?.forEach((f) => map.set(`factory-${f.id}`, f.name));
    workshopsData?.data?.forEach((w) => map.set(`workshop-${w.id}`, w.name));
    return map;
  }, [branchesData?.data, factoriesData?.data, workshopsData?.data]);

  const { mutate: deleteCloth, isPending: isDeleting } = useMutation(
    useDeleteClothesMutationOptions()
  );

  const handleOpenEdit = (cloth: TClothResponse) => {
    setSelectedCloth(cloth);
    setIsEditModalOpen(true);
  };

  const handleOpenDelete = (cloth: TClothResponse) => {
    setSelectedCloth(cloth);
    setIsDeleteModalOpen(true);
  };

  const handleOpenTransfer = (cloth: TClothResponse) => {
    setClothForTransfer(cloth);
    setTransferModalOpen(true);
  };

  const handleDelete = (onCloseModal: () => void) => {
    if (selectedCloth) {
      deleteCloth(selectedCloth.id, {
        onSuccess: () => {
          toast.success("تم حذف المنتج بنجاح", {
            description: "تم حذف المنتج من النظام.",
          });
          onCloseModal();
        },
        onError: (err: Error & { message?: string }) => {
          toast.error("حدث خطأ أثناء حذف المنتج", {
            description: err.message,
          });
        },
      });
    }
  };

  const skipNextSyncRef = useRef(false);
  const handleResetFilters = () => {
    skipNextSyncRef.current = true;
    setSearchInput("");
    setIdFilter("");
    setCategoryId("");
    setSubcategoryIds([]);
    setBranchId("");
    setBreastSize("");
    setWaistSize("");
    setSleeveSize("");
    setCreatedFrom("");
    setCreatedTo("");
    setStatusFilter("");
    setSearchParams(() => {
      const next = new URLSearchParams();
      next.set("page", "1");
      return next;
    });
  };

  useEffect(() => {
    if (skipNextSyncRef.current) {
      skipNextSyncRef.current = false;
      return;
    }
    const params = new URLSearchParams(searchParamsRef.current);
    const prevSearch = params.get("search") || null;
    const prevId = params.get("id") || null;
    const prevCategoryId = params.get("category_id") || null;
    const prevSubcatId = params.get("subcat_id") || null;
    const prevBranchId =
      params.get("branch_id") || params.get("entity_id") || null;
    const prevBreast = params.get("breast_size") || null;
    const prevWaist = params.get("waist_size") || null;
    const prevSleeve = params.get("sleeve_size") || null;
    const prevCreatedFrom = params.get("created_from") || null;
    const prevCreatedTo = params.get("created_to") || null;
    const prevStatus = params.get("status") || null;

    const newSearch = debouncedSearch || null;
    const newId = debouncedIdFilter || null;
    const newCategoryId = debouncedCategoryId || null;
    const newSubcatId =
      debouncedSubcategoryIds.length > 0
        ? debouncedSubcategoryIds.join(",")
        : null;
    const newBranchId = debouncedBranchId || null;
    const newBreast = debouncedBreastSize || null;
    const newWaist = debouncedWaistSize || null;
    const newSleeve = debouncedSleeveSize || null;
    const newCreatedFrom = debouncedCreatedFrom || null;
    const newCreatedTo = debouncedCreatedTo || null;
    const newStatus = debouncedStatusFilter || null;

    const paramsChanged =
      prevSearch !== newSearch ||
      prevId !== newId ||
      prevCategoryId !== newCategoryId ||
      prevSubcatId !== newSubcatId ||
      prevBranchId !== newBranchId ||
      prevBreast !== newBreast ||
      prevWaist !== newWaist ||
      prevSleeve !== newSleeve ||
      prevCreatedFrom !== newCreatedFrom ||
      prevCreatedTo !== newCreatedTo ||
      prevStatus !== newStatus;

    if (!paramsChanged) return;

    const nextParams = new URLSearchParams(searchParamsRef.current);
    if (newSearch) nextParams.set("search", newSearch);
    else nextParams.delete("search");
    if (newId) nextParams.set("id", newId);
    else nextParams.delete("id");
    if (newCategoryId) nextParams.set("category_id", newCategoryId);
    else nextParams.delete("category_id");
    if (newSubcatId) nextParams.set("subcat_id", newSubcatId);
    else nextParams.delete("subcat_id");
    if (newBranchId) nextParams.set("branch_id", newBranchId);
    else {
      nextParams.delete("branch_id");
      nextParams.delete("entity_id");
    }
    if (newBreast) nextParams.set("breast_size", newBreast);
    else nextParams.delete("breast_size");
    if (newWaist) nextParams.set("waist_size", newWaist);
    else nextParams.delete("waist_size");
    if (newSleeve) nextParams.set("sleeve_size", newSleeve);
    else nextParams.delete("sleeve_size");
    if (newCreatedFrom) nextParams.set("created_from", newCreatedFrom);
    else nextParams.delete("created_from");
    if (newCreatedTo) nextParams.set("created_to", newCreatedTo);
    else nextParams.delete("created_to");
    if (newStatus) nextParams.set("status", newStatus);
    else nextParams.delete("status");
    nextParams.set("page", "1");
    setSearchParams(nextParams);
  }, [
    debouncedSearch,
    debouncedIdFilter,
    debouncedCategoryId,
    debouncedSubcategoryIds,
    debouncedBranchId,
    debouncedBreastSize,
    debouncedWaistSize,
    debouncedSleeveSize,
    debouncedCreatedFrom,
    debouncedCreatedTo,
    debouncedStatusFilter,
    setSearchParams,
  ]);

  const getMeasurementsDisplay = (cloth: TClothResponse) => {
    if (cloth.measurements) return cloth.measurements;
    const parts = [
      cloth.breast_size,
      cloth.waist_size,
      cloth.sleeve_size,
    ].filter(Boolean);
    return parts.length ? parts.join(" / ") : "";
  };

  const getEntityDisplay = (cloth: TClothResponse) => {
    if (cloth.entity_name?.trim()) return cloth.entity_name.trim();
    const key = `${cloth.entity_type}-${cloth.entity_id}`;
    const name = entityNamesMap.get(key);
    if (name) return name;
    const typeLabel =
      cloth.entity_type === "branch"
        ? "فرع"
        : cloth.entity_type === "factory"
          ? "مصنع"
          : "ورشة";
    return `${typeLabel} (${cloth.entity_id})`;
  };

  const getSubcategoryDisplay = (cloth: TClothResponse) => {
    if (cloth.subcategory_names?.length)
      return cloth.subcategory_names.join("، ");
    const subcategories = (
      cloth as { subcategories?: { name?: string }[] }
    ).subcategories;
    if (subcategories?.length)
      return subcategories
        .map((s) => s.name ?? "")
        .filter(Boolean)
        .join("، ");
    return "";
  };

  const rows = data?.data ?? [];
  const pageSum = rows.reduce((s, c) => s + clothPrice(c), 0);
  const hasFilters =
    Boolean(searchInput.trim()) ||
    Boolean(branchId) ||
    Boolean(categoryId) ||
    Boolean(statusFilter) ||
    Boolean(idFilter) ||
    subcategoryIds.length > 0 ||
    Boolean(breastSize) ||
    Boolean(waistSize) ||
    Boolean(sleeveSize) ||
    Boolean(createdFrom) ||
    Boolean(createdTo);

  const categories = categoriesData?.data ?? [];
  const branches = branchesData?.data ?? [];

  return (
    <div className="w-full space-y-5">
      {isError && (
        <div className="rounded-xl border border-red-100 bg-red-50/80 p-4 text-sm text-red-700">
          {error?.message ?? "حدث خطأ أثناء التحميل"}
        </div>
      )}

      <ClothesProductsStats items={rows} totalFiltered={data?.total} />

      <div
        className="rounded-2xl p-4 space-y-3"
        style={{ background: "white", border: "1px solid #F1F5F9" }}
      >
        <div className="flex flex-wrap items-center gap-2.5">
          <div className="relative flex-1 min-w-56">
            <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none" />
            <input
              type="text"
              placeholder="ابحث بالكود أو القسم..."
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              className="w-full rounded-xl text-sm py-2.5 pr-9 pl-9 outline-none transition-colors"
              style={{
                background: "#F8FAFC",
                border: "1px solid #EEF2F8",
                color: "#334155",
              }}
            />
            {searchInput ? (
              <button
                type="button"
                onClick={() => setSearchInput("")}
                className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500"
              >
                <i className="ri-close-line text-sm" />
              </button>
            ) : null}
          </div>

          <select
            value={branchId}
            onChange={(e) => setBranchId(e.target.value)}
            className="rounded-xl text-sm py-2.5 px-3 outline-none cursor-pointer min-w-[150px]"
            style={{
              background: "#F8FAFC",
              border: "1px solid #EEF2F8",
              color: "#334155",
            }}
          >
            <option value="">الكل</option>
            {branches.map((b) => (
              <option key={b.id} value={String(b.id)}>
                {b.name}
              </option>
            ))}
          </select>

          <select
            value={categoryId}
            onChange={(e) => {
              setCategoryId(e.target.value);
              setSubcategoryIds([]);
            }}
            className="rounded-xl text-sm py-2.5 px-3 outline-none cursor-pointer min-w-[130px]"
            style={{
              background: "#F8FAFC",
              border: "1px solid #EEF2F8",
              color: "#334155",
            }}
          >
            <option value="">الكل</option>
            {categories.map((c) => (
              <option key={c.id} value={String(c.id)}>
                {c.name}
              </option>
            ))}
          </select>

          <div className="flex items-center gap-1.5 flex-wrap">
            {STATUS_CHIPS.map((s) => {
              const active =
                s.value === "" ? !statusFilter : statusFilter === s.value;
              return (
                <button
                  key={s.label}
                  type="button"
                  onClick={() => setStatusFilter(s.value)}
                  className="flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all"
                  style={{
                    background: active ? "#0C1A3E" : "#F8FAFC",
                    color: active ? "white" : "#475569",
                    border: `1px solid ${active ? "#0C1A3E" : "#EEF2F8"}`,
                  }}
                >
                  {s.value !== "" && (
                    <span
                      className="inline-block w-1.5 h-1.5 rounded-full"
                      style={{
                        background: active
                          ? "rgba(255,255,255,0.6)"
                          : STATUS_DOT[s.value] ?? "#94A3B8",
                      }}
                    />
                  )}
                  {s.label}
                </button>
              );
            })}
          </div>

          <div className="flex-1 min-w-[1rem]" />

          <Button
            type="button"
            variant="outline"
            size="sm"
            className="rounded-xl border-[#EEF2F8] bg-[#F8FAFC] text-xs font-bold h-auto py-2"
            onClick={() => setShowAdvancedFilters((p) => !p)}
          >
            <Filter className="ml-1 h-3.5 w-3.5" />
            {showAdvancedFilters ? "إخفاء المتقدم" : "فلاتر متقدمة"}
          </Button>

          {hasFilters ? (
            <button
              type="button"
              onClick={handleResetFilters}
              className="flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-colors"
              style={{
                background: "#FEF2F2",
                color: "#991B1B",
                border: "1px solid #FECACA",
              }}
            >
              <i className="ri-refresh-line" />
              مسح الفلاتر
            </button>
          ) : null}

          <div
            className="flex rounded-xl overflow-hidden p-0.5 gap-0.5"
            style={{ background: "#F1F5F9" }}
          >
            {(["table", "grid"] as const).map((v) => (
              <button
                key={v}
                type="button"
                onClick={() => setViewMode(v)}
                className="px-3 py-2 rounded-lg transition-all text-sm"
                style={{
                  background: viewMode === v ? "white" : "transparent",
                  color: viewMode === v ? "#0C1A3E" : "#94A3B8",
                  fontWeight: viewMode === v ? 700 : 500,
                }}
              >
                <i
                  className={
                    v === "table" ? "ri-list-check-2" : "ri-grid-line"
                  }
                />
              </button>
            ))}
          </div>
        </div>

        <div
          className="flex flex-wrap items-center gap-2 pt-1"
          style={{ borderTop: "1px solid #F8FAFC" }}
        >
          <i className="ri-filter-3-line text-slate-300 text-xs" />
          <span className="text-xs text-slate-400">
            يُعرض
            <span className="font-black text-slate-700 mx-1">{rows.length}</span>
            من إجمالي
            <span className="font-black text-slate-700 mx-1">
              {data?.total ?? rows.length}
            </span>
            منتج
          </span>
          {hasFilters ? (
            <span
              className="text-[11px] font-bold px-2 py-0.5 rounded-full"
              style={{ background: "#FEF3C7", color: "#92400E" }}
            >
              فلتر نشط
            </span>
          ) : null}
        </div>

        {showAdvancedFilters ? (
          <div className="rounded-xl border border-[#EEF2F8] bg-[#FAFBFD] p-4 space-y-4">
            <div className="flex flex-wrap items-center gap-2">
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="text-xs"
                onClick={handleResetFilters}
              >
                <RotateCcw className="ml-1 h-3.5 w-3.5" />
                إعادة تعيين الكل
              </Button>
            </div>
            <div>
              <p className="text-xs font-medium text-slate-500 mb-2">
                تحديد المنتج والموقع
              </p>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-slate-700">
                    ID
                  </label>
                  <Input
                    placeholder="1 أو 1,2,3"
                    value={idFilter}
                    onChange={(e) => setIdFilter(e.target.value)}
                    className="h-9 rounded-xl border-[#EEF2F8]"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-slate-700">
                    الفرع (بحث متقدم)
                  </label>
                  <BranchesSelect
                    value={branchId}
                    onChange={(id) => setBranchId(id || "")}
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-slate-700">
                    القسم
                  </label>
                  <CategoriesSelect
                    value={categoryId}
                    onChange={(id) => {
                      setCategoryId(id);
                      setSubcategoryIds([]);
                    }}
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-slate-700">
                    القسم الفرعي
                  </label>
                  <SubcategoriesSelect
                    multiple
                    value={subcategoryIds}
                    onChange={(ids) => setSubcategoryIds(ids)}
                    category_id={categoryId ? Number(categoryId) : undefined}
                  />
                </div>
              </div>
            </div>
            <div>
              <p className="text-xs font-medium text-slate-500 mb-2">المقاسات</p>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">مقاس الصدر</label>
                  <Input
                    value={breastSize}
                    onChange={(e) => setBreastSize(e.target.value)}
                    className="h-9 rounded-xl"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">مقاس الخصر</label>
                  <Input
                    value={waistSize}
                    onChange={(e) => setWaistSize(e.target.value)}
                    className="h-9 rounded-xl"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">مقاس الكم</label>
                  <Input
                    value={sleeveSize}
                    onChange={(e) => setSleeveSize(e.target.value)}
                    className="h-9 rounded-xl"
                  />
                </div>
              </div>
            </div>
            <div>
              <p className="text-xs font-medium text-slate-500 mb-2">
                تاريخ الإنشاء
              </p>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 max-w-md">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">من</label>
                  <Input
                    type="date"
                    value={createdFrom}
                    onChange={(e) => setCreatedFrom(e.target.value)}
                    className="h-9 rounded-xl"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">إلى</label>
                  <Input
                    type="date"
                    value={createdTo}
                    onChange={(e) => setCreatedTo(e.target.value)}
                    className="h-9 rounded-xl"
                  />
                </div>
              </div>
            </div>
          </div>
        ) : null}
      </div>

      {viewMode === "table" ? (
        <div
          className="rounded-2xl overflow-hidden"
          style={{ background: "white", border: "1px solid #F1F5F9" }}
        >
          <div
            className="px-5 py-3 flex flex-wrap items-center justify-between gap-2"
            style={{ background: "#FAFBFD", borderBottom: "1px solid #F1F5F9" }}
          >
            <div className="flex items-center gap-2">
              <i className="ri-table-line text-slate-400 text-sm" />
              <span className="text-xs font-bold text-slate-500">
                قائمة المنتجات
              </span>
              <span
                className="text-[11px] font-bold px-2 py-0.5 rounded-full"
                style={{ background: "#EEF2FF", color: "#1E3A7B" }}
              >
                {rows.length} في الصفحة
              </span>
            </div>
            <div className="flex flex-wrap items-center gap-3 text-[11px] text-slate-400">
              <span>
                <i
                  className="ri-circle-fill text-[8px] ml-1"
                  style={{ color: "#10B981" }}
                />
                جاهز للإيجار
              </span>
              <span>
                <i
                  className="ri-circle-fill text-[8px] ml-1"
                  style={{ color: "#F59E0B" }}
                />
                قيد الإصلاح
              </span>
              <span>
                <i
                  className="ri-circle-fill text-[8px] ml-1"
                  style={{ color: "#EF4444" }}
                />
                تالف
              </span>
              <span>
                <i
                  className="ri-circle-fill text-[8px] ml-1"
                  style={{ color: "#9CA3AF" }}
                />
                ميت
              </span>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full min-w-[1000px] text-right">
              <thead>
                <tr
                  style={{
                    background: "#FAFBFD",
                    borderBottom: "2px solid #F1F5F9",
                  }}
                >
                  {[
                    "#",
                    "كود المنتج",
                    "القسم",
                    "الفئة الفرعية",
                    "المقاسات",
                    "السعر",
                    "الفرع",
                    "الحالة",
                    "إجراءات",
                  ].map((h, i) => (
                    <th
                      key={h}
                      className="px-4 py-3 text-right text-[11px] font-black tracking-wide whitespace-nowrap"
                      style={{ color: "#94A3B8", width: i === 0 ? 40 : undefined }}
                    >
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {isPending ? (
                  <ClothesTableSkeleton rows={5} />
                ) : rows.length === 0 ? (
                  <tr>
                    <td colSpan={9} className="text-center py-16">
                      <div className="flex flex-col items-center gap-3">
                        <div
                          className="w-14 h-14 rounded-2xl flex items-center justify-center"
                          style={{ background: "#F8FAFC" }}
                        >
                          <i className="ri-search-line text-2xl text-slate-300" />
                        </div>
                        <p className="text-sm font-semibold text-slate-400">
                          لا توجد نتائج
                        </p>
                        <p className="text-xs text-slate-300">
                          جرّب تغيير معايير البحث أو الفلاتر
                        </p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  rows.map((cloth, index) => {
                    const sc = statusVisual(cloth.status);
                    const lv = locationVisual(cloth.entity_type);
                    const catName =
                      cloth.category_name?.trim() ||
                      (cloth as { category?: { name?: string } }).category
                        ?.name?.trim() ||
                      "—";
                    const sub = getSubcategoryDisplay(cloth);
                    const sizes = getMeasurementsDisplay(cloth);
                    const price = clothPrice(cloth);
                    const rowNum = (page - 1) * per_page + index + 1;
                    const hasParts =
                      cloth.breast_size ||
                      cloth.waist_size ||
                      cloth.sleeve_size;

                    return (
                      <TableRow
                        key={cloth.id}
                        className="border-b transition-colors hover:bg-slate-50/60 cursor-pointer"
                        style={{ borderColor: "#F8FAFC" }}
                        role="button"
                        tabIndex={0}
                        onClick={() => navigate(`/clothes/details/${cloth.id}`)}
                        onKeyDown={(e) => {
                          if (e.key === "Enter" || e.key === " ") {
                            e.preventDefault();
                            navigate(`/clothes/details/${cloth.id}`);
                          }
                        }}
                      >
                        <TableCell className="px-4 py-3">
                          <span className="text-[11px] font-bold text-slate-300 tabular-nums">
                            {String(rowNum).padStart(2, "0")}
                          </span>
                        </TableCell>
                        <TableCell className="px-4 py-3">
                          <div className="flex items-center gap-2">
                            <div
                              className="w-1.5 h-8 rounded-full shrink-0"
                              style={{ background: sc.dot }}
                            />
                            <div className="min-w-0">
                              <p className="font-black text-sm text-slate-800 font-mono tracking-wide">
                                {cloth.code}
                              </p>
                              {sub ? (
                                <p className="text-[10px] text-slate-400 mt-0.5 truncate max-w-[200px]">
                                  {sub}
                                </p>
                              ) : null}
                            </div>
                          </div>
                        </TableCell>
                        <TableCell className="px-4 py-3">
                          <span
                            className="text-xs font-bold px-2.5 py-1 rounded-lg inline-block max-w-[140px] truncate"
                            style={{
                              background: "#F1F5F9",
                              color: "#475569",
                            }}
                            title={catName}
                          >
                            {catName}
                          </span>
                        </TableCell>
                        <TableCell className="px-4 py-3">
                          <span className="text-xs text-slate-500 line-clamp-2">
                            {sub || "—"}
                          </span>
                        </TableCell>
                        <TableCell className="px-4 py-3">
                          {hasParts ? (
                            <div className="flex gap-1 flex-wrap">
                              {cloth.breast_size ? (
                                <span
                                  className="text-[10px] px-1.5 py-0.5 rounded font-semibold"
                                  style={{
                                    background: "#EEF2FF",
                                    color: "#3730A3",
                                  }}
                                >
                                  ص {cloth.breast_size}
                                </span>
                              ) : null}
                              {cloth.waist_size ? (
                                <span
                                  className="text-[10px] px-1.5 py-0.5 rounded font-semibold"
                                  style={{
                                    background: "#F0FDF4",
                                    color: "#166534",
                                  }}
                                >
                                  خ {cloth.waist_size}
                                </span>
                              ) : null}
                              {cloth.sleeve_size ? (
                                <span
                                  className="text-[10px] px-1.5 py-0.5 rounded font-semibold"
                                  style={{
                                    background: "#FFF7ED",
                                    color: "#9A3412",
                                  }}
                                >
                                  ك {cloth.sleeve_size}
                                </span>
                              ) : null}
                            </div>
                          ) : sizes ? (
                            <span className="text-xs text-slate-500">{sizes}</span>
                          ) : (
                            <span className="text-slate-300 text-sm">—</span>
                          )}
                        </TableCell>
                        <TableCell className="px-4 py-3">
                          {price > 0 ? (
                            <div>
                              <span className="text-sm font-black text-slate-800">
                                {price.toLocaleString("ar-EG")}
                              </span>
                              <span className="text-[10px] text-slate-400 mr-1">
                                ج.م
                              </span>
                            </div>
                          ) : (
                            <span className="text-slate-300 text-sm">—</span>
                          )}
                        </TableCell>
                        <TableCell className="px-4 py-3">
                          <div className="flex items-center gap-1.5 min-w-0">
                            <div
                              className="w-5 h-5 rounded flex items-center justify-center shrink-0"
                              style={{ background: lv.bg }}
                            >
                              <i
                                className={`${lv.icon} text-[11px]`}
                                style={{ color: lv.color }}
                              />
                            </div>
                            <span className="text-xs text-slate-600 font-medium truncate">
                              {getEntityDisplay(cloth)}
                            </span>
                          </div>
                        </TableCell>
                        <TableCell className="px-4 py-3">
                          <div
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold whitespace-nowrap"
                            style={{ background: sc.bg, color: sc.color }}
                          >
                            <i className={`${sc.icon} text-xs`} />
                            {sc.label}
                          </div>
                        </TableCell>
                        <TableCell
                          className="px-4 py-3"
                          onClick={(e) => e.stopPropagation()}
                        >
                          <div
                            className="inline-flex items-center gap-0.5 p-0.5 rounded-xl"
                            style={{
                              background: "#F8FAFC",
                              border: "1px solid #EEF2F8",
                              boxShadow: "0 1px 2px rgba(15,23,42,0.04)",
                            }}
                          >
                            <button
                              type="button"
                              className="w-8 h-8 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.97]"
                              style={{ background: "#EEF2FF" }}
                              title="تعديل"
                              onClick={() => handleOpenEdit(cloth)}
                            >
                              <Pencil
                                className="h-3.5 w-3.5"
                                style={{ color: "#3730A3" }}
                              />
                            </button>
                            <button
                              type="button"
                              className="w-8 h-8 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.97]"
                              style={{ background: "#D1FAE5" }}
                              title="طلب نقل"
                              onClick={() => handleOpenTransfer(cloth)}
                            >
                              <i
                                className="ri-arrow-left-right-line text-sm"
                                style={{ color: "#065F46" }}
                              />
                            </button>
                            <button
                              type="button"
                              className="w-8 h-8 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.97]"
                              style={{ background: "#FEE2E2" }}
                              title="حذف"
                              onClick={() => handleOpenDelete(cloth)}
                            >
                              <Trash2 className="h-3.5 w-3.5 text-red-600" />
                            </button>
                          </div>
                        </TableCell>
                      </TableRow>
                    );
                  })
                )}
              </tbody>
              {!isPending && rows.length > 0 ? (
                <tfoot>
                  <tr
                    style={{
                      background: "#FAFBFD",
                      borderTop: "2px solid #F1F5F9",
                    }}
                  >
                    <td colSpan={5} className="px-4 py-3">
                      <span className="text-xs font-bold text-slate-400">
                        إجمالي {rows.length} منتج في الصفحة
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div>
                        <span className="text-sm font-black text-slate-700">
                          {pageSum.toLocaleString("ar-EG")}
                        </span>
                        <span className="text-[10px] text-slate-400 mr-1">
                          ج.م
                        </span>
                      </div>
                    </td>
                    <td colSpan={3} />
                  </tr>
                </tfoot>
              ) : null}
            </table>
          </div>
        </div>
      ) : isPending ? (
        <div
          className="rounded-2xl flex items-center justify-center py-24"
          style={{ background: "white", border: "1px solid #F1F5F9" }}
        >
          <div className="flex flex-col items-center gap-3 text-slate-400">
            <i className="ri-loader-4-line text-3xl animate-spin" />
            <span className="text-sm font-medium">جاري التحميل...</span>
          </div>
        </div>
      ) : (
        <ClothesGridView
          clothes={rows}
          getEntityDisplay={getEntityDisplay}
          getSubcategoryDisplay={getSubcategoryDisplay}
          onOpenDetails={(id) => navigate(`/clothes/details/${id}`)}
          onEdit={handleOpenEdit}
          onDelete={handleOpenDelete}
          onTransfer={handleOpenTransfer}
        />
      )}

      <div className="flex justify-center pt-2">
        <CustomPagination
          totalElementsLabel="إجمالي المنتجات"
          totalElements={data?.total}
          totalPages={data?.total_pages}
          isLoading={isPending}
        />
      </div>

      <EditClothModal
        cloth={selectedCloth}
        open={isEditModalOpen}
        onOpenChange={setIsEditModalOpen}
      />
      <TransferClothModal
        open={transferModalOpen}
        onOpenChange={(o) => {
          setTransferModalOpen(o);
          if (!o) setClothForTransfer(null);
        }}
        initialCloth={clothForTransfer}
      />
      <ControlledConfirmationModal
        open={isDeleteModalOpen}
        onOpenChange={setIsDeleteModalOpen}
        variantLayout="brand"
        alertTitle="حذف المنتج"
        alertMessage={
          <>
            هل أنت متأكد أنك تريد حذف المنتج{" "}
            <strong>{selectedCloth?.code}</strong>؟
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

export default ClothesTableContent;
