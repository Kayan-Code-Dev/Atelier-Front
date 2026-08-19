import { useCallback, useEffect, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";
import type { TCategory } from "@/api/v2/content-managment/category/category.type";
import type { TSubcategory } from "@/api/v2/content-managment/subcategory/subcategory.types";
import {
  useCategoriesQueryOptions,
  useCreateCategoryMutationOptions,
  useDeleteCategoryMutationOptions,
  useExportCategoriesToCSVMutationOptions,
  useUpdateCategoryMutationOptions,
} from "@/api/v2/content-managment/category/category.hooks";
import {
  SUBCATEGORIES_KEY,
  useCreateSubcategoryMutationOptions,
  useDeleteSubcategoryMutationOptions,
  useExportSubcategoriesToCSVMutationOptions,
  useSubcategoriesQueryOptions,
  useUpdateSubcategoryMutationOptions,
} from "@/api/v2/content-managment/subcategory/subcategory.hooks";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";

const LIST_PER_PAGE = 200;

const CATEGORY_COLORS = [
  "#E879A0",
  "#3B82F6",
  "#C2964A",
  "#1E293B",
  "#10B981",
  "#8B5CF6",
  "#F59E0B",
  "#EF4444",
] as const;

const CATEGORY_ICONS = [
  "ri-price-tag-3-line",
  "ri-shirt-line",
  "ri-gem-line",
  "ri-women-line",
  "ri-handbag-line",
] as const;

function categoryVisual(id: number): { color: string; icon: string } {
  const n = Math.abs(id);
  return {
    color: CATEGORY_COLORS[n % CATEGORY_COLORS.length],
    icon: CATEGORY_ICONS[n % CATEGORY_ICONS.length],
  };
}

/**
 * الأصناف والتصنيفات — نفس تصميم `project/.../CategoriesTab` مع ربط API الأقسام والفروع.
 */
export default function ProductTaxonomySettingsTab() {
  const queryClient = useQueryClient();

  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [showAddCat, setShowAddCat] = useState(false);
  const [showAddSub, setShowAddSub] = useState(false);
  const [newCatName, setNewCatName] = useState("");
  const [newSubName, setNewSubName] = useState("");
  const [editingCatId, setEditingCatId] = useState<number | null>(null);
  const [editingSubId, setEditingSubId] = useState<number | null>(null);
  const [editCatName, setEditCatName] = useState("");
  const [editSubName, setEditSubName] = useState("");
  const [savedMsg, setSavedMsg] = useState("");

  const showSaved = useCallback((msg: string) => {
    setSavedMsg(msg);
    window.setTimeout(() => setSavedMsg(""), 2500);
  }, []);

  const {
    data: catPage,
    isPending: catsLoading,
    isError: catsError,
    error: catsErr,
  } = useQuery(useCategoriesQueryOptions(1, LIST_PER_PAGE));

  const categories = catPage?.data ?? [];

  const { data: allSubsPage } = useQuery(
    useSubcategoriesQueryOptions(1, LIST_PER_PAGE)
  );

  const subCountByCategory = useMemo(() => {
    const m = new Map<number, number>();
    for (const s of allSubsPage?.data ?? []) {
      m.set(s.category_id, (m.get(s.category_id) ?? 0) + 1);
    }
    return m;
  }, [allSubsPage]);

  const totalSubs =
    allSubsPage?.total ?? allSubsPage?.data?.length ?? 0;

  const {
    data: subsPage,
    isPending: subsLoading,
    isError: subsError,
  } = useQuery({
    ...useSubcategoriesQueryOptions(1, LIST_PER_PAGE, selectedId ?? undefined),
    enabled: selectedId != null,
  });

  const subsForSelected: TSubcategory[] = subsPage?.data ?? [];

  const selectedCat = useMemo(
    () => categories.find((c) => c.id === selectedId) ?? null,
    [categories, selectedId]
  );

  useEffect(() => {
    if (!categories.length) {
      setSelectedId(null);
      return;
    }
    if (selectedId == null || !categories.some((c) => c.id === selectedId)) {
      setSelectedId(categories[0].id);
    }
  }, [categories, selectedId]);

  const createCat = useMutation(useCreateCategoryMutationOptions());
  const updateCat = useMutation(useUpdateCategoryMutationOptions());
  const deleteCat = useMutation(useDeleteCategoryMutationOptions());
  const createSub = useMutation(useCreateSubcategoryMutationOptions());
  const updateSub = useMutation(useUpdateSubcategoryMutationOptions());
  const deleteSub = useMutation(useDeleteSubcategoryMutationOptions());
  const exportCats = useMutation(useExportCategoriesToCSVMutationOptions());
  const exportSubs = useMutation(useExportSubcategoriesToCSVMutationOptions());

  const handleExportCategories = () => {
    exportCats.mutate(undefined, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) ||
          "categories.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير الأقسام");
      },
      onError: (e: Error) => toast.error(e.message),
    });
  };

  const handleExportSubcategories = () => {
    exportSubs.mutate(undefined, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) ||
          "subcategories.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير الأقسام الفرعية");
      },
      onError: (e: Error) => toast.error(e.message),
    });
  };

  const handleAddCategory = () => {
    const name = newCatName.trim();
    if (!name) return;
    createCat.mutate(
      { name, description: "" },
      {
        onSuccess: (created) => {
          if (created) {
            setSelectedId(created.id);
            showSaved(`تمت إضافة قسم "${created.name}"`);
          }
          setNewCatName("");
          setShowAddCat(false);
        },
        onError: (e: Error) => toast.error(e.message),
      }
    );
  };

  const handleDeleteCategory = (cat: TCategory) => {
    if (
      !window.confirm(
        `حذف القسم «${cat.name}»؟ قد يؤثر على الأقسام الفرعية والمنتجات المرتبطة.`
      )
    ) {
      return;
    }
    deleteCat.mutate(cat.id, {
      onSuccess: () => {
        showSaved("تم حذف القسم");
        void queryClient.invalidateQueries({ queryKey: [SUBCATEGORIES_KEY] });
        setSelectedId((prev) => (prev === cat.id ? null : prev));
      },
      onError: (e: Error) => toast.error(e.message),
    });
  };

  const handleSaveCatEdit = (catId: number) => {
    const name = editCatName.trim();
    if (!name) return;
    updateCat.mutate(
      { id: catId, req: { name } },
      {
        onSuccess: () => {
          setEditingCatId(null);
          showSaved("تم تعديل اسم القسم");
        },
        onError: (e: Error) => toast.error(e.message),
      }
    );
  };

  const handleAddSubCategory = () => {
    const name = newSubName.trim();
    if (!name || selectedId == null) return;
    createSub.mutate(
      { name, description: "", category_id: selectedId },
      {
        onSuccess: (created) => {
          if (created) showSaved(`تمت إضافة قسم فرعي "${created.name}"`);
          setNewSubName("");
          setShowAddSub(false);
        },
        onError: (e: Error) => toast.error(e.message),
      }
    );
  };

  const handleDeleteSub = (sub: TSubcategory) => {
    if (!window.confirm(`حذف «${sub.name}»؟`)) return;
    deleteSub.mutate(sub.id, {
      onSuccess: () => {
        showSaved("تم حذف القسم الفرعي");
      },
      onError: (e: Error) => toast.error(e.message),
    });
  };

  const handleSaveSubEdit = (subId: number) => {
    const name = editSubName.trim();
    if (!name) return;
    updateSub.mutate(
      { id: subId, req: { name } },
      {
        onSuccess: () => {
          setEditingSubId(null);
          showSaved("تم تعديل اسم القسم الفرعي");
        },
        onError: (e: Error) => toast.error(e.message),
      }
    );
  };

  const organizedCount = categories.filter(
    (c) => (subCountByCategory.get(c.id) ?? 0) > 0
  ).length;
  const withoutSubCount = categories.length - organizedCount;

  if (catsLoading) {
    return (
      <div dir="rtl" className="flex justify-center py-20 text-slate-400">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  if (catsError) {
    return (
      <div dir="rtl" className="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">
        تعذر تحميل الأقسام: {catsErr?.message ?? "خطأ غير معروف"}
      </div>
    );
  }

  return (
    <div dir="rtl" className="space-y-4">
      <div
        className="rounded-2xl p-5 flex flex-wrap items-center gap-4"
        style={{
          background: "linear-gradient(135deg, #0C1A3E 0%, #1E3A7B 100%)",
        }}
      >
        <div
          className="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
          style={{ background: "rgba(194,150,74,0.2)" }}
        >
          <i className="ri-folder-3-line text-xl" style={{ color: "#E8BF7A" }} />
        </div>
        <div className="min-w-0 flex-1">
          <h3 className="text-white font-black text-base">
            إدارة الأقسام والتصنيفات
          </h3>
          <p className="text-white/50 text-xs mt-0.5">
            تنظيم المنتجات في أقسام وأقسام فرعية — {categories.length} قسم رئيسي /{" "}
            {totalSubs} قسم فرعي
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2 mr-auto">
          <button
            type="button"
            onClick={handleExportCategories}
            disabled={exportCats.isPending}
            className="text-xs font-bold px-3 py-2 rounded-xl border border-white/20 text-white/90 hover:bg-white/10 disabled:opacity-50"
          >
            {exportCats.isPending ? (
              <Loader2 className="inline h-3.5 w-3.5 animate-spin ms-1" />
            ) : (
              <i className="ri-file-excel-2-line ms-1" />
            )}
            تصدير الأقسام
          </button>
          <button
            type="button"
            onClick={handleExportSubcategories}
            disabled={exportSubs.isPending}
            className="text-xs font-bold px-3 py-2 rounded-xl border border-white/20 text-white/90 hover:bg-white/10 disabled:opacity-50"
          >
            {exportSubs.isPending ? (
              <Loader2 className="inline h-3.5 w-3.5 animate-spin ms-1" />
            ) : (
              <i className="ri-file-excel-2-line ms-1" />
            )}
            تصدير الفرعية
          </button>
        </div>
        <div className="flex items-center gap-3 shrink-0">
          <div className="text-center">
            <p className="text-white/40 text-xs">الأقسام</p>
            <p className="text-white font-black text-xl">{categories.length}</p>
          </div>
          <div
            className="w-px h-8"
            style={{ background: "rgba(255,255,255,0.12)" }}
          />
          <div className="text-center">
            <p className="text-white/40 text-xs">الفرعية</p>
            <p className="text-white font-black text-xl">{totalSubs}</p>
          </div>
        </div>
      </div>

      {savedMsg ? (
        <div
          className="rounded-xl px-4 py-3 flex items-center gap-2 text-sm font-semibold"
          style={{
            background: "#F0FDF4",
            border: "1px solid #BBF7D0",
            color: "#15803D",
          }}
        >
          <i className="ri-checkbox-circle-fill" />
          {savedMsg}
        </div>
      ) : null}

      <div className="grid grid-cols-1 lg:grid-cols-5 gap-4">
        <div className="lg:col-span-2">
          <div
            className="rounded-2xl bg-white overflow-hidden"
            style={{ border: "1px solid #EEF2F8" }}
          >
            <div
              className="px-4 py-3 flex items-center justify-between"
              style={{
                background: "#F8FAFC",
                borderBottom: "1px solid #EEF2F8",
              }}
            >
              <span className="text-xs font-black text-slate-600 uppercase tracking-widest">
                الأقسام الرئيسية
              </span>
              <button
                type="button"
                onClick={() => setShowAddCat((v) => !v)}
                className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer transition-colors disabled:opacity-50"
                style={{ background: "#0C1A3E", color: "white" }}
                title="إضافة قسم جديد"
                disabled={createCat.isPending}
              >
                <i
                  className={`${showAddCat ? "ri-close-line" : "ri-add-line"} text-sm`}
                />
              </button>
            </div>

            {showAddCat ? (
              <div
                className="px-4 py-3"
                style={{
                  borderBottom: "1px solid #EEF2F8",
                  background: "#FFFBEB",
                }}
              >
                <input
                  autoFocus
                  type="text"
                  value={newCatName}
                  onChange={(e) => setNewCatName(e.target.value)}
                  onKeyDown={(e) => e.key === "Enter" && handleAddCategory()}
                  placeholder="اسم القسم الجديد..."
                  className="w-full px-3 py-2 rounded-xl text-sm outline-none mb-2"
                  style={{
                    border: "1.5px solid #FDE68A",
                    background: "#FFFBEB",
                  }}
                />
                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={handleAddCategory}
                    disabled={createCat.isPending}
                    className="flex-1 py-1.5 rounded-lg text-xs font-bold cursor-pointer whitespace-nowrap disabled:opacity-50"
                    style={{ background: "#0C1A3E", color: "white" }}
                  >
                    {createCat.isPending ? "…" : "إضافة"}
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      setShowAddCat(false);
                      setNewCatName("");
                    }}
                    className="px-3 py-1.5 rounded-lg text-xs cursor-pointer whitespace-nowrap"
                    style={{ background: "#F1F5F9", color: "#64748B" }}
                  >
                    إلغاء
                  </button>
                </div>
              </div>
            ) : null}

            <div className="divide-y divide-slate-50 max-h-96 overflow-y-auto">
              {categories.length === 0 ? (
                <div className="p-6 text-center text-slate-400 text-sm">
                  <i className="ri-folder-open-line text-2xl mb-1 block" />
                  لا توجد أقسام بعد
                </div>
              ) : (
                categories.map((cat) => {
                  const { color, icon } = categoryVisual(cat.id);
                  const isSelected = selectedId === cat.id;
                  const subN = subCountByCategory.get(cat.id) ?? 0;
                  return (
                    <div
                      key={cat.id}
                      className="relative flex items-center gap-3 px-4 py-3 cursor-pointer transition-colors group"
                      style={{
                        background: isSelected ? `${color}08` : "white",
                      }}
                      onClick={() => setSelectedId(cat.id)}
                    >
                      {isSelected ? (
                        <span
                          className="absolute end-0 top-0 bottom-0 w-0.5 rounded-full"
                          style={{ background: color }}
                        />
                      ) : null}
                      <div
                        className="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                        style={{ background: `${color}15` }}
                      >
                        <i className={`${icon} text-sm`} style={{ color }} />
                      </div>
                      {editingCatId === cat.id ? (
                        <input
                          autoFocus
                          type="text"
                          value={editCatName}
                          onChange={(e) => setEditCatName(e.target.value)}
                          onKeyDown={(e) => {
                            if (e.key === "Enter") handleSaveCatEdit(cat.id);
                            if (e.key === "Escape") setEditingCatId(null);
                          }}
                          className="flex-1 text-sm px-2 py-1 rounded-lg outline-none min-w-0"
                          style={{
                            border: "1.5px solid #C2964A",
                            background: "#FFFBEB",
                          }}
                          onClick={(e) => e.stopPropagation()}
                        />
                      ) : (
                        <div className="flex-1 min-w-0">
                          <p
                            className="text-sm font-bold truncate"
                            style={{
                              color: isSelected ? "#1E293B" : "#475569",
                            }}
                          >
                            {cat.name}
                          </p>
                          <p className="text-[11px] text-slate-400">
                            {subN} قسم فرعي
                          </p>
                        </div>
                      )}
                      {editingCatId === cat.id ? (
                        <button
                          type="button"
                          onClick={(e) => {
                            e.stopPropagation();
                            handleSaveCatEdit(cat.id);
                          }}
                          disabled={updateCat.isPending}
                          className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer disabled:opacity-50"
                          style={{ background: "#D1FAE5", color: "#065F46" }}
                        >
                          <i className="ri-check-line text-sm" />
                        </button>
                      ) : (
                        <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                          <button
                            type="button"
                            onClick={(e) => {
                              e.stopPropagation();
                              setEditingCatId(cat.id);
                              setEditCatName(cat.name);
                            }}
                            className="w-6 h-6 rounded-lg flex items-center justify-center cursor-pointer hover:bg-slate-100"
                            title="تعديل"
                          >
                            <i className="ri-edit-line text-xs text-slate-500" />
                          </button>
                          <button
                            type="button"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleDeleteCategory(cat);
                            }}
                            disabled={deleteCat.isPending}
                            className="w-6 h-6 rounded-lg flex items-center justify-center cursor-pointer hover:bg-red-50 disabled:opacity-50"
                            title="حذف"
                          >
                            <i className="ri-delete-bin-line text-xs text-red-400" />
                          </button>
                        </div>
                      )}
                    </div>
                  );
                })
              )}
            </div>
          </div>
        </div>

        <div className="lg:col-span-3">
          <div
            className="rounded-2xl bg-white overflow-hidden min-h-[280px]"
            style={{ border: "1px solid #EEF2F8" }}
          >
            {selectedCat ? (
              <>
                <div
                  className="px-4 py-3 flex flex-wrap items-center justify-between gap-2"
                  style={{
                    background: "#F8FAFC",
                    borderBottom: "1px solid #EEF2F8",
                  }}
                >
                  <div className="flex items-center gap-2 min-w-0">
                    {(() => {
                      const { color, icon } = categoryVisual(selectedCat.id);
                      return (
                        <>
                          <div
                            className="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                            style={{ background: `${color}15` }}
                          >
                            <i className={`${icon} text-sm`} style={{ color }} />
                          </div>
                          <div className="min-w-0">
                            <span className="text-sm font-black text-slate-700 truncate block">
                              {selectedCat.name}
                            </span>
                            <span className="text-[11px] text-slate-400">
                              — الأقسام الفرعية
                            </span>
                          </div>
                        </>
                      );
                    })()}
                  </div>
                  <button
                    type="button"
                    onClick={() => setShowAddSub((v) => !v)}
                    className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer whitespace-nowrap disabled:opacity-50"
                    style={{ background: "#0C1A3E", color: "white" }}
                    disabled={createSub.isPending}
                  >
                    <i
                      className={`${showAddSub ? "ri-close-line" : "ri-add-line"} text-xs`}
                    />
                    {showAddSub ? "إلغاء" : "إضافة فرعي"}
                  </button>
                </div>

                {showAddSub ? (
                  <div
                    className="px-4 py-3"
                    style={{
                      borderBottom: "1px solid #EEF2F8",
                      background: "#FFFBEB",
                    }}
                  >
                    <div className="flex flex-wrap gap-2">
                      <input
                        autoFocus
                        type="text"
                        value={newSubName}
                        onChange={(e) => setNewSubName(e.target.value)}
                        onKeyDown={(e) =>
                          e.key === "Enter" && handleAddSubCategory()
                        }
                        placeholder={`قسم فرعي جديد لـ "${selectedCat.name}"...`}
                        className="flex-1 min-w-[160px] px-3 py-2 rounded-xl text-sm outline-none"
                        style={{
                          border: "1.5px solid #FDE68A",
                          background: "#FFFBEB",
                        }}
                      />
                      <button
                        type="button"
                        onClick={handleAddSubCategory}
                        disabled={createSub.isPending}
                        className="px-4 py-2 rounded-xl text-xs font-bold cursor-pointer whitespace-nowrap disabled:opacity-50"
                        style={{ background: "#0C1A3E", color: "white" }}
                      >
                        إضافة
                      </button>
                    </div>
                  </div>
                ) : null}

                {subsError ? (
                  <div className="p-4 text-sm text-red-600">
                    تعذر تحميل الأقسام الفرعية.
                  </div>
                ) : subsLoading ? (
                  <div className="flex justify-center py-16 text-slate-400">
                    <Loader2 className="h-7 w-7 animate-spin" />
                  </div>
                ) : (
                  <div className="p-3 space-y-2 max-h-96 overflow-y-auto">
                    {subsForSelected.length === 0 ? (
                      <div className="text-center py-8 text-slate-400 text-sm">
                        <i className="ri-folder-open-line text-3xl mb-2 block" />
                        <p>لا توجد أقسام فرعية</p>
                        <p className="text-xs mt-1">
                          اضغط &ldquo;إضافة فرعي&rdquo; لإنشاء أول قسم
                        </p>
                      </div>
                    ) : (
                      subsForSelected.map((sub, idx) => {
                        const { color } = categoryVisual(selectedCat.id);
                        return (
                          <div
                            key={sub.id}
                            className="flex items-center gap-3 px-3 py-2.5 rounded-xl group transition-colors"
                            style={{
                              background: "#F8FAFC",
                              border: "1px solid #F1F5F9",
                            }}
                          >
                            <div
                              className="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 text-xs font-black"
                              style={{
                                background: `${color}12`,
                                color,
                              }}
                            >
                              {idx + 1}
                            </div>
                            {editingSubId === sub.id ? (
                              <input
                                autoFocus
                                type="text"
                                value={editSubName}
                                onChange={(e) => setEditSubName(e.target.value)}
                                onKeyDown={(e) => {
                                  if (e.key === "Enter")
                                    handleSaveSubEdit(sub.id);
                                  if (e.key === "Escape")
                                    setEditingSubId(null);
                                }}
                                className="flex-1 text-sm px-2 py-1 rounded-lg outline-none min-w-0"
                                style={{
                                  border: "1.5px solid #C2964A",
                                  background: "#FFFBEB",
                                }}
                              />
                            ) : (
                              <span className="flex-1 text-sm font-semibold text-slate-700 truncate">
                                {sub.name}
                              </span>
                            )}
                            {editingSubId === sub.id ? (
                              <button
                                type="button"
                                onClick={() => handleSaveSubEdit(sub.id)}
                                disabled={updateSub.isPending}
                                className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer disabled:opacity-50"
                                style={{
                                  background: "#D1FAE5",
                                  color: "#065F46",
                                }}
                              >
                                <i className="ri-check-line text-sm" />
                              </button>
                            ) : (
                              <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                  type="button"
                                  onClick={() => {
                                    setEditingSubId(sub.id);
                                    setEditSubName(sub.name);
                                  }}
                                  className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer hover:bg-slate-100"
                                  title="تعديل"
                                >
                                  <i className="ri-edit-line text-xs text-slate-500" />
                                </button>
                                <button
                                  type="button"
                                  onClick={() => handleDeleteSub(sub)}
                                  disabled={deleteSub.isPending}
                                  className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer hover:bg-red-50 disabled:opacity-50"
                                  title="حذف"
                                >
                                  <i className="ri-delete-bin-line text-xs text-red-400" />
                                </button>
                              </div>
                            )}
                          </div>
                        );
                      })
                    )}
                  </div>
                )}
              </>
            ) : (
              <div className="flex flex-col items-center justify-center min-h-[240px] py-16 text-slate-400">
                <i className="ri-folder-3-line text-4xl mb-3" />
                <p className="text-sm">
                  اختر قسماً من القائمة لعرض أقسامه الفرعية
                </p>
              </div>
            )}
          </div>
        </div>
      </div>

      <div
        className="rounded-2xl p-4 grid grid-cols-2 sm:grid-cols-4 gap-3"
        style={{ background: "#F8FAFC", border: "1px solid #EEF2F8" }}
      >
        <div className="text-center">
          <p className="text-2xl font-black text-slate-800">{categories.length}</p>
          <p className="text-xs text-slate-500 mt-0.5">قسم رئيسي</p>
        </div>
        <div className="text-center">
          <p className="text-2xl font-black text-slate-800">{totalSubs}</p>
          <p className="text-xs text-slate-500 mt-0.5">قسم فرعي</p>
        </div>
        <div className="text-center">
          <p className="text-2xl font-black text-slate-800">{organizedCount}</p>
          <p className="text-xs text-slate-500 mt-0.5">أقسام منظّمة</p>
        </div>
        <div className="text-center">
          <p className="text-2xl font-black text-slate-800">{withoutSubCount}</p>
          <p className="text-xs text-slate-500 mt-0.5">بدون فرعي</p>
        </div>
      </div>
    </div>
  );
}
