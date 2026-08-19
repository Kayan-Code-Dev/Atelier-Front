import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
} from "@/components/ui/table";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { Banknote, Download, FileBarChart, Plus, Trash2 } from "lucide-react";
import { useCallback, useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { SuppliersTableSkeleton } from "./SuppliersTableSkeleton";
import { CreateSupplierModal } from "./CreateSupplierModal";
import { CreateSupplierOrderModal } from "./CreateSupplierOrderModal";
import { DeleteSupplierModal } from "./DeleteSupplierModal";

import {
  useGetSuppliersQueryOptions,
  useDeleteSupplierMutationOptions,
  useExportSuppliersToExcelMutationOptions,
} from "@/api/v2/suppliers/suppliers.hooks";
import { TSupplierResponse } from "@/api/v2/suppliers/suppliers.types";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import CustomPagination from "@/components/custom/CustomPagination";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useSearchParams, useNavigate } from "react-router";
import { toEnglishNumerals } from "@/utils/formatDate";

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

function formatSupplierCurrency(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === "") return "-";
  const num = typeof value === "string" ? parseFloat(value) : value;
  if (Number.isNaN(num)) return "-";
  return `${num.toLocaleString("en-EG", { minimumFractionDigits: 2 })} ج.م`;
}

function getNetBalance(
  totalPurchases: string | number | null | undefined,
  totalRefunds: string | number | null | undefined
): number {
  const p = totalPurchases != null && totalPurchases !== "" ? Number(totalPurchases) : 0;
  const r = totalRefunds != null && totalRefunds !== "" ? Number(totalRefunds) : 0;
  if (Number.isNaN(p) || Number.isNaN(r)) return 0;
  return p - r;
}

function parseNum(v: string | number | null | undefined): number {
  if (v == null || v === "") return 0;
  const n = typeof v === "number" ? v : parseFloat(String(v));
  return Number.isNaN(n) ? 0 : n;
}

function Suppliers() {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const page = Number(searchParams.get("page")) || 1;
  const search = searchParams.get("search") || undefined;

  const [draftSearch, setDraftSearch] = useState(search ?? "");
  useEffect(() => {
    setDraftSearch(search ?? "");
  }, [search]);

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isCreateOrderModalOpen, setIsCreateOrderModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [selectedSupplier, setSelectedSupplier] = useState<TSupplierResponse | null>(
    null
  );

  /** One API request: aggregate + table pagination are derived client-side. */
  const FETCH_PER_PAGE = 500;
  const TABLE_PER_PAGE = 10;

  const { data, isPending, isError, error } = useQuery(
    useGetSuppliersQueryOptions(1, FETCH_PER_PAGE, search),
  );

  const fullList = useMemo(() => data?.data ?? [], [data]);
  const totalSuppliersFromApi = data?.total ?? fullList.length;
  const tableTotalPages = Math.max(
    1,
    Math.ceil(totalSuppliersFromApi / TABLE_PER_PAGE),
  );

  const pageRows = useMemo(() => {
    const start = (page - 1) * TABLE_PER_PAGE;
    return fullList.slice(start, start + TABLE_PER_PAGE);
  }, [fullList, page]);

  const isPartialDataset = totalSuppliersFromApi > fullList.length;
  const tableStartIndex = (page - 1) * TABLE_PER_PAGE;
  const pageBeyondLoaded =
    !isPending &&
    Boolean(data) &&
    isPartialDataset &&
    tableStartIndex >= fullList.length;

  const stats = useMemo(() => {
    const totalSuppliersCount = totalSuppliersFromApi;
    const totalOrders = fullList.reduce(
      (s, x) => s + parseNum(x.orders_count ?? x.purchases_count),
      0,
    );
    const totalPurchases = fullList.reduce(
      (s, x) => s + parseNum(x.total_order_amount ?? x.total_purchases),
      0,
    );
    const totalDue = fullList.reduce(
      (s, x) => s + Math.max(0, parseNum(x.total_remaining ?? x.remaining)),
      0,
    );
    const totalPaid = fullList.reduce(
      (s, x) => s + parseNum(x.total_payment ?? x.paid),
      0,
    );
    return { totalSuppliersCount, totalOrders, totalPurchases, totalDue, totalPaid };
  }, [fullList, totalSuppliersFromApi]);

  const footerTotals = useMemo(() => {
    const totalPurchases = fullList.reduce(
      (s, x) => s + parseNum(x.total_order_amount ?? x.total_purchases),
      0,
    );
    const totalPaid = fullList.reduce(
      (s, x) => s + parseNum(x.total_payment ?? x.paid),
      0,
    );
    const totalDue = fullList.reduce(
      (s, x) => s + Math.max(0, parseNum(x.total_remaining ?? x.remaining)),
      0,
    );
    return { totalPurchases, totalPaid, totalDue };
  }, [fullList]);

  const { mutate: deleteSupplier, isPending: isDeleting } = useMutation(
    useDeleteSupplierMutationOptions()
  );
  const { mutate: exportSuppliersToExcel, isPending: isExporting } = useMutation(
    useExportSuppliersToExcelMutationOptions()
  );

  const applySearch = useCallback(() => {
    const v = draftSearch.trim();
    setSearchParams(
      (prev) => {
        const n = new URLSearchParams(prev);
        if (v) n.set("search", v);
        else n.delete("search");
        n.set("page", "1");
        return n;
      },
      { replace: true }
    );
  }, [draftSearch, setSearchParams]);

  const clearFilters = useCallback(() => {
    setDraftSearch("");
    setSearchParams(
      (prev) => {
        const n = new URLSearchParams(prev);
        n.delete("search");
        n.set("page", "1");
        return n;
      },
      { replace: true }
    );
  }, [setSearchParams]);

  const handleExport = () => {
    exportSuppliersToExcel(
      search?.trim() ? { search: search.trim() } : undefined,
      {
        onSuccess: (result) => {
          if (!result) return;
          const filename =
            parseFilenameFromContentDisposition(result.headers) || "suppliers.xlsx";
          downloadBlob(result.data, filename);
          toast.success("تم تصدير الموردين بنجاح");
        },
        onError: (err: { message?: string }) => {
          toast.error("خطأ أثناء تصدير الموردين", {
            description: err.message,
          });
        },
      }
    );
  };

  const handleOpenDelete = (supplier: TSupplierResponse) => {
    setSelectedSupplier(supplier);
    setIsDeleteModalOpen(true);
  };

  const handleViewSupplierOrders = (supplier: TSupplierResponse) => {
    navigate(`/suppliers/orders?supplier_id=${supplier.id}`);
  };

  const handleViewSupplierAccount = (supplier: TSupplierResponse) => {
    navigate(`/suppliers/accounts?supplier_id=${supplier.id}`);
  };

  const handleDelete = () => {
    if (!selectedSupplier) return;
    deleteSupplier(selectedSupplier.id, {
      onSuccess: () => {
        toast.success("تم حذف المورد بنجاح");
        setIsDeleteModalOpen(false);
        setSelectedSupplier(null);
      },
      onError: (err: { message?: string }) => {
        toast.error("حدث خطأ أثناء حذف المورد", {
          description: err.message,
        });
      },
    });
  };

  const hasActiveFilters = Boolean(search?.trim());

  return (
    <div dir="rtl" className="w-full p-4 md:p-6 space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">الموردون</h1>
          <p className="text-sm text-gray-400 mt-0.5">
            إجمالي {toEnglishNumerals(stats.totalSuppliersCount)} موردين
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button
            variant="outline"
            className="border-green-200 text-green-700 hover:bg-green-50"
            onClick={handleExport}
            disabled={isExporting}
          >
            <Download className="ml-2 h-4 w-4" />
            {isExporting ? "جاري التصدير..." : "تصدير Excel"}
          </Button>
          <Button
            variant="outline"
            onClick={() => navigate("/suppliers/accounts")}
          >
            <FileBarChart className="ml-2 h-4 w-4" />
            حسابات الموردين
          </Button>
          <Button variant="outline" onClick={() => navigate("/suppliers/orders")}>
            طلبيات الموردين
          </Button>
          <Button
            variant="outline"
            className="rounded-xl border-amber-200 text-amber-900 hover:bg-amber-50"
            onClick={() => setIsCreateOrderModalOpen(true)}
          >
            <i className="ri-shopping-cart-2-line ml-2 text-lg leading-none" />
            إضافة طلبية
          </Button>
          <Button
            className="bg-blue-700 hover:bg-blue-800"
            data-onboarding-target="supplier_setup"
            onClick={() => setIsCreateModalOpen(true)}
          >
            <Plus className="ml-2 h-4 w-4" />
            مورد جديد
          </Button>
        </div>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          {
            label: "إجمالي الموردين",
            value: toEnglishNumerals(stats.totalSuppliersCount),
            icon: "ri-store-2-line",
            color: "bg-blue-50 text-blue-600",
            sub: "في نتائج التصفية الحالية",
          },
          {
            label: "إجمالي الطلبيات",
            value: toEnglishNumerals(stats.totalOrders),
            icon: "ri-shopping-cart-2-line",
            color: "bg-amber-50 text-amber-600",
            sub: "عدد الطلبيات المجمّع",
          },
          {
            label: "إجمالي المشتريات",
            value: `${fmt(stats.totalPurchases)} ج.م`,
            icon: "ri-money-dollar-circle-line",
            color: "bg-green-50 text-green-600",
            sub: "قيمة إجمالية",
            isStr: true,
          },
          {
            label: "مستحقات للموردين",
            value: `${fmt(Math.max(0, stats.totalDue))} ج.م`,
            icon: "ri-alarm-warning-line",
            color:
              stats.totalDue > 0
                ? "bg-red-50 text-red-500"
                : "bg-gray-50 text-gray-400",
            sub: "متبقي غير مدفوع",
            isStr: true,
          },
        ].map((s) => (
          <div
            key={s.label}
            className="bg-white rounded-xl border border-blue-100 p-4 text-right"
          >
            <div className="flex items-center justify-between mb-3">
              <div
                className={`w-10 h-10 flex items-center justify-center rounded-lg ${s.color}`}
              >
                <i className={`${s.icon} text-xl`} />
              </div>
            </div>
            <p
              className="text-xl font-bold text-gray-800 tabular-nums w-full text-right"
              dir="ltr"
            >
              {s.value}
            </p>
            <p className="text-sm text-gray-500 mt-1">{s.label}</p>
            <p className="text-xs text-gray-400 mt-0.5">{s.sub}</p>
          </div>
        ))}
      </div>

      <div className="bg-white rounded-xl border border-blue-100 p-4 flex flex-wrap items-center gap-3">
        <div className="relative flex-1 min-w-[200px]">
          <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
          <input
            type="text"
            value={draftSearch}
            onChange={(e) => setDraftSearch(e.target.value)}
            onKeyDown={(e) => e.key === "Enter" && applySearch()}
            placeholder="بحث بالاسم أو الكود أو رقم الهاتف..."
            className="w-full pr-9 pl-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          />
        </div>
        <Button type="button" variant="secondary" onClick={applySearch}>
          بحث
        </Button>
        {hasActiveFilters && (
          <button
            type="button"
            onClick={clearFilters}
            className="text-xs text-red-500 border border-red-200 px-3 py-2 rounded-lg hover:bg-red-50 cursor-pointer whitespace-nowrap flex items-center gap-1"
          >
            <i className="ri-refresh-line" />
            مسح
          </button>
        )}
      </div>

      {isError && (
        <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-center text-red-600 text-sm">
          حدث خطأ أثناء تحميل البيانات.{" "}
          {error?.message ? `(${error.message})` : null}
        </div>
      )}

      {!isError && (
        <div className="bg-white rounded-xl border border-blue-100 overflow-hidden">
          <div
            className="grid bg-blue-900 text-white text-xs font-medium"
            style={{ gridTemplateColumns: "50px 1fr 1fr 1fr 1fr 120px" }}
          >
            <div className="px-4 py-3 text-center">#</div>
            <div className="px-4 py-3">بيانات المورد</div>
            <div className="px-4 py-3 text-center">المشتريات والمرتجعات</div>
            <div className="px-4 py-3 text-center">الحساب</div>
            <div className="px-4 py-3 text-center">الرصيد</div>
            <div className="px-4 py-3 text-center">الإجراءات</div>
          </div>

          <div className="divide-y divide-gray-50">
            {isPending ? (
              <div className="p-4 overflow-x-auto">
                <Table>
                  <TableBody>
                    <SuppliersTableSkeleton rows={5} />
                  </TableBody>
                </Table>
              </div>
            ) : pageBeyondLoaded ? (
              <div className="py-12 px-4 text-center text-sm text-amber-800 bg-amber-50/80">
                تم تحميل أول{" "}
                <span className="font-semibold tabular-nums" dir="ltr">
                  {toEnglishNumerals(fullList.length)}
                </span>{" "}
                موردًا في هذا الطلب. الصفحات الأبعد تحتاج تحميلًا إضافيًا من
                الخادم — ارجع لصفحة أقل أو ضيّق البحث.
              </div>
            ) : pageRows.length > 0 ? (
              pageRows.map((supplier) => {
                const netBalance = getNetBalance(
                  supplier.total_order_amount ?? supplier.total_purchases,
                  supplier.total_refund ?? supplier.total_returns
                );
                const remaining = parseNum(
                  supplier.total_remaining ?? supplier.remaining
                );
                const o = supplier.orders_count ?? supplier.purchases_count;
                const r = supplier.refund_orders_count ?? supplier.returns_count;
                const netOrders =
                  supplier.net_purchases_count != null
                    ? supplier.net_purchases_count
                    : o != null && r != null
                      ? Number(o) - Number(r)
                      : null;

                return (
                  <div
                    key={supplier.id}
                    className="grid hover:bg-blue-50/20 transition-colors group"
                    style={{
                      gridTemplateColumns: "50px 1fr 1fr 1fr 1fr 120px",
                    }}
                  >
                    <div className="px-4 py-4 text-center flex items-start justify-center pt-5">
                      <button
                        type="button"
                        onClick={() => handleViewSupplierAccount(supplier)}
                        className="font-bold text-blue-600 text-sm hover:underline"
                      >
                        <span dir="ltr" className="tabular-nums">
                          #{toEnglishNumerals(supplier.id)}
                        </span>
                      </button>
                    </div>

                    <div className="px-4 py-4 space-y-1.5">
                      <p className="text-sm font-semibold text-gray-800">
                        <span className="text-gray-400 text-xs ml-1">
                          اسم المورد:
                        </span>
                        {supplier.name ?? "-"}
                      </p>
                      <p className="text-xs text-gray-500">
                        <span className="text-gray-400 ml-1">كود المورد:</span>
                        <span className="font-mono text-blue-600">
                          {supplier.code ?? "-"}
                        </span>
                      </p>
                      <p className="text-xs text-gray-500">
                        <span className="text-gray-400 ml-1">رقم المورد:</span>
                        <span dir="ltr" className="inline-block">
                          {toEnglishNumerals(supplier.phone?.trim()) || "-"}
                        </span>
                      </p>
                      <p className="text-xs text-gray-400">
                        <span className="ml-1">العنوان:</span>
                        {supplier.address?.trim() ?? "-"}
                      </p>
                    </div>

                    <div className="px-4 py-4 border-x border-gray-100 flex flex-col justify-center gap-2">
                      <div className="flex items-center justify-between text-xs">
                        <span className="text-gray-400">عدد المشتريات:</span>
                        <span className="font-semibold text-gray-700" dir="ltr">
                          {toEnglishNumerals(
                            supplier.orders_count ?? supplier.purchases_count
                          ) || "-"}
                        </span>
                      </div>
                      <div className="flex items-center justify-between text-xs">
                        <span className="text-gray-400">عدد المرتجعات:</span>
                        <span
                          className={`font-semibold ${
                            parseNum(
                              supplier.refund_orders_count ??
                                supplier.returns_count
                            ) > 0
                              ? "text-red-500"
                              : "text-gray-400"
                          }`}
                          dir="ltr"
                        >
                          {toEnglishNumerals(
                            supplier.refund_orders_count ??
                              supplier.returns_count
                          ) || "-"}
                        </span>
                      </div>
                      <div className="flex items-center justify-between text-xs border-t border-gray-100 pt-2 mt-1">
                        <span className="text-gray-400">صافي المشتريات:</span>
                        <span className="font-bold text-blue-700" dir="ltr">
                          {netOrders != null
                            ? toEnglishNumerals(netOrders)
                            : "-"}
                        </span>
                      </div>
                    </div>

                    <div className="px-4 py-4 border-l border-gray-100 flex flex-col justify-center gap-2">
                      <div className="flex items-center justify-between text-xs">
                        <span className="text-gray-400">إجمالي المشتريات:</span>
                        <span className="font-semibold text-gray-700" dir="ltr">
                          {toEnglishNumerals(
                            formatSupplierCurrency(
                              supplier.total_order_amount ??
                                supplier.total_purchases
                            )
                          )}
                        </span>
                      </div>
                      <div className="flex items-center justify-between text-xs">
                        <span className="text-gray-400">إجمالي المرتجعات:</span>
                        <span
                          className={`font-semibold ${
                            parseNum(
                              supplier.total_refund ?? supplier.total_returns
                            ) > 0
                              ? "text-red-400"
                              : "text-gray-400"
                          }`}
                          dir="ltr"
                        >
                          {toEnglishNumerals(
                            formatSupplierCurrency(
                              supplier.total_refund ?? supplier.total_returns
                            )
                          )}
                        </span>
                      </div>
                      <div className="flex items-center justify-between text-xs border-t border-gray-100 pt-2 mt-1">
                        <span className="text-gray-400">صافي الرصيد:</span>
                        <span className="font-bold text-blue-700" dir="ltr">
                          {toEnglishNumerals(fmt(netBalance))} ج.م
                        </span>
                      </div>
                    </div>

                    <div className="px-4 py-4 border-l border-gray-100 flex flex-col justify-center gap-2">
                      <div className="flex items-center justify-between text-xs">
                        <span className="text-gray-400">المدفوع:</span>
                        <span className="font-semibold text-green-600" dir="ltr">
                          {toEnglishNumerals(
                            formatSupplierCurrency(
                              supplier.total_payment ?? supplier.paid
                            )
                          )}
                        </span>
                      </div>
                      <div className="flex items-center justify-between text-xs">
                        <span className="text-gray-400">المتبقي:</span>
                        <span
                          className={`font-semibold ${
                            remaining > 0 ? "text-red-500" : "text-gray-400"
                          }`}
                          dir="ltr"
                        >
                          {toEnglishNumerals(
                            formatSupplierCurrency(
                              supplier.total_remaining ?? supplier.remaining
                            )
                          )}
                        </span>
                      </div>
                      {remaining <= 0 && (
                        <span className="text-xs bg-green-50 text-green-600 px-2 py-0.5 rounded-full text-center">
                          مسدّد بالكامل
                        </span>
                      )}
                    </div>

                    <div className="px-4 py-4 border-l border-gray-100 flex flex-col items-center justify-center gap-2">
                      <TooltipProvider delayDuration={300}>
                        <div className="flex flex-wrap items-center gap-1 justify-center">
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <Button
                                variant="outline"
                                size="icon"
                                className="h-8 w-8 shrink-0 border-blue-200 text-blue-600 hover:bg-blue-50"
                                onClick={() =>
                                  handleViewSupplierAccount(supplier)
                                }
                              >
                                <FileBarChart className="h-4 w-4" />
                              </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top">
                              حساب المورد
                            </TooltipContent>
                          </Tooltip>
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <Button
                                variant="outline"
                                size="icon"
                                className="h-8 w-8 shrink-0"
                                onClick={() =>
                                  handleViewSupplierOrders(supplier)
                                }
                              >
                                <Banknote className="h-4 w-4" />
                              </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top">
                              طلبيات المورد
                            </TooltipContent>
                          </Tooltip>
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <Button
                                variant="destructive"
                                size="icon"
                                className="h-8 w-8 shrink-0"
                                onClick={() => handleOpenDelete(supplier)}
                                disabled={isDeleting}
                              >
                                <Trash2 className="h-4 w-4" />
                              </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top">
                              حذف المورد
                            </TooltipContent>
                          </Tooltip>
                        </div>
                      </TooltipProvider>
                    </div>
                  </div>
                );
              })
            ) : fullList.length > 0 ? (
              <div className="py-12 text-center text-gray-500 text-sm">
                لا توجد موردين في هذه الصفحة. استخدم التصفح للعودة إلى صفحة
                ضمن النطاق.
              </div>
            ) : (
              <div className="py-16 text-center text-gray-400 text-sm">
                لا توجد موردين تطابق البحث
              </div>
            )}
          </div>

          {data && fullList.length > 0 && (
            <div className="bg-blue-900 text-white px-5 py-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between text-xs">
              <span className="text-blue-200">
                {toEnglishNumerals(fullList.length)} مورد في الإجمالي المعروض
                للتذييل
              </span>
              <div className="flex flex-col sm:flex-row sm:gap-6 gap-1">
                <span className="text-blue-200">
                  إجمالي المشتريات:{" "}
                  <strong className="text-white" dir="ltr">
                    {fmt(footerTotals.totalPurchases)} ج.م
                  </strong>
                </span>
                <span className="text-green-300">
                  المدفوع:{" "}
                  <strong dir="ltr">{fmt(footerTotals.totalPaid)} ج.م</strong>
                </span>
                <span className="text-red-300">
                  المتبقي:{" "}
                  <strong dir="ltr">
                    {fmt(Math.max(0, footerTotals.totalDue))} ج.م
                  </strong>
                </span>
              </div>
            </div>
          )}

          <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100">
            <CustomPagination
              totalElementsLabel="إجمالي الموردين"
              totalElements={data?.total}
              totalPages={tableTotalPages}
              isLoading={isPending}
            />
          </div>
        </div>
      )}

      <CreateSupplierModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
      <CreateSupplierOrderModal
        open={isCreateOrderModalOpen}
        onOpenChange={setIsCreateOrderModalOpen}
      />
      <DeleteSupplierModal
        supplier={selectedSupplier}
        open={isDeleteModalOpen}
        onOpenChange={setIsDeleteModalOpen}
        onConfirm={handleDelete}
        isDeleting={isDeleting}
      />
    </div>
  );
}

export default Suppliers;
