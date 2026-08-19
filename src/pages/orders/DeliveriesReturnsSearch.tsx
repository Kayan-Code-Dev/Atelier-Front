import { useState, useMemo, useCallback, useEffect } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { useSearchParams, useNavigate } from "react-router";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import {
  useExportOrdersToCSVMutationOptions,
  useGetOrdersQueryOptions,
  useDeliverOrderMutationOptions,
  useFinishOrderMutationOptions,
} from "@/api/v2/orders/orders.hooks";
import { ORDER_NEEDS_CUSTODY } from "@/api/v2/orders/order.errors";
import { getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";
import type { TOrder } from "@/api/v2/orders/orders.types";
import { getOrders } from "@/api/v2/orders/orders.service";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import useDebounce from "@/hooks/useDebounce";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { CustomCalendar } from "@/components/custom/CustomCalendar";
import { ClientsSelect } from "@/components/custom/ClientsSelect";
import { EmployeesSelect } from "@/components/custom/EmployeesSelect";
import { CategoriesSelect } from "@/components/custom/CategoriesSelect";
import { SubcategoriesSelect } from "@/components/custom/SubcategoriesSelect";
import CustomPagination from "@/components/custom/CustomPagination";
import { DeliverySearchStats } from "./delivery-search/components/DeliverySearchStats";
import {
  DeliverySearchFilters,
  type DeliverySearchUiFilters,
} from "./delivery-search/components/DeliverySearchFilters";
import { DeliverySearchTable } from "./delivery-search/components/DeliverySearchTable";
import { mapOrderToDeliveryRow } from "./delivery-search/mapOrderToDeliveryRow";
import { applyDeliveryUiFilters } from "./delivery-search/filterDeliveryRows";
import type { DeliverySearchRow, DeliverySearchStatus } from "./delivery-search/deliverySearch.types";
import { CreateCustodyModal } from "./CreateCustodyModal";
import {
  ordersFilterSchema,
  type OrdersFilterFormValues,
  ORDERS_FILTER_FORM_DEFAULTS,
  ORDERS_FILTER_DEBOUNCE_MS,
} from "./ordersFilterForm";
const FETCH_PER_PAGE = 500;
const TABLE_PER_PAGE = 10;

const defaultUiFilters = (): DeliverySearchUiFilters => ({
  search: "",
  invoiceType: "الكل",
  deliveryStatus: "الكل",
  branch: "الكل",
  dateFrom: "",
  dateTo: "",
});

function DeliveriesReturnsSearch() {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const page = Number(searchParams.get("page")) || 1;

  const [showAdvanced, setShowAdvanced] = useState(false);
  const [uiFilters, setUiFilters] = useState<DeliverySearchUiFilters>(() => {
    const s = defaultUiFilters();
    const q = searchParams.get("search");
    if (q) s.search = q;
    return s;
  });
  const [custodyModalOrder, setCustodyModalOrder] = useState<TOrder | null>(null);

  const form = useForm<OrdersFilterFormValues>({
    resolver: zodResolver(ordersFilterSchema),
    defaultValues: ORDERS_FILTER_FORM_DEFAULTS,
  });

  const formValues = form.watch();
  const debouncedFormValues = useDebounce({
    value: formValues,
    delay: ORDERS_FILTER_DEBOUNCE_MS,
  });

  const debouncedSearch = useDebounce({
    value: uiFilters.search,
    delay: ORDERS_FILTER_DEBOUNCE_MS,
  });

  const searchFromUrl = searchParams.get("search") ?? "";
  useEffect(() => {
    setUiFilters((prev) => {
      if (prev.search === searchFromUrl) return prev;
      return { ...prev, search: searchFromUrl };
    });
  }, [searchFromUrl]);

  useEffect(() => {
    const q = debouncedSearch.trim();
    setSearchParams(
      (prev) => {
        const cur = prev.get("search") ?? "";
        if (q === cur) return prev;
        const next = new URLSearchParams(prev);
        if (q) next.set("search", q);
        else next.delete("search");
        next.set("page", "1");
        return next;
      },
      { replace: true },
    );
  }, [debouncedSearch, setSearchParams]);

  const urlCategoryId = searchParams.get("category_id") ?? "";
  const urlSubcategoryId = searchParams.get("subcategory_id") ?? "";

  const apiFilters = useMemo(() => {
    const v = debouncedFormValues;
    const categoryId =
      urlCategoryId.trim() || (v.category_id?.trim() ? v.category_id : undefined);
    const subcategoryId =
      urlSubcategoryId.trim() || (v.subcategory_id?.trim() ? v.subcategory_id : undefined);
    return {
      order_id: v.order_id && v.order_id.trim() !== "" ? v.order_id : undefined,
      client_id: v.client_id && v.client_id.trim() !== "" ? v.client_id : undefined,
      employee_id: v.employee_id && v.employee_id.trim() !== "" ? v.employee_id : undefined,
      cloth_name: v.cloth_name && v.cloth_name.trim() !== "" ? v.cloth_name : undefined,
      cloth_code: v.cloth_code && v.cloth_code.trim() !== "" ? v.cloth_code : undefined,
      category_id: categoryId,
      subcategory_id: subcategoryId,
      visit_date_from: v.visit_date_from || undefined,
      visit_date_to: v.visit_date_to || undefined,
      delivery_date_from: v.delivery_date_from || undefined,
      delivery_date_to: v.delivery_date_to || undefined,
      return_date_from: v.return_date_from || undefined,
      return_date_to: v.return_date_to || undefined,
      search: debouncedSearch.trim() !== "" ? debouncedSearch.trim() : undefined,
    };
  }, [debouncedFormValues, urlCategoryId, urlSubcategoryId, debouncedSearch]);

  useEffect(() => {
    form.setValue("category_id", urlCategoryId);
    form.setValue("subcategory_id", urlSubcategoryId);
  }, [urlCategoryId, urlSubcategoryId, form]);

  const updateUrlCategorySubcategory = useCallback(
    (categoryId: string, subcategoryId: string) => {
      setSearchParams(
        (prev) => {
          const next = new URLSearchParams(prev);
          if (categoryId.trim()) next.set("category_id", categoryId);
          else next.delete("category_id");
          if (subcategoryId.trim()) next.set("subcategory_id", subcategoryId);
          else next.delete("subcategory_id");
          next.set("page", "1");
          return next;
        },
        { replace: true },
      );
    },
    [setSearchParams],
  );

  const { data, isPending, refetch } = useQuery(
    useGetOrdersQueryOptions(1, FETCH_PER_PAGE, apiFilters),
  );

  const allMapped = useMemo(
    () => (data?.data ?? []).map(mapOrderToDeliveryRow),
    [data?.data],
  );

  const branches = useMemo(() => {
    const set = new Set(allMapped.map((r) => r.branchName).filter(Boolean));
    return Array.from(set).sort();
  }, [allMapped]);

  const filteredRows = useMemo(
    () =>
      applyDeliveryUiFilters(allMapped, {
        ...uiFilters,
        search: debouncedSearch,
      }),
    [allMapped, uiFilters, debouncedSearch],
  );

  const totalPages = Math.max(1, Math.ceil(filteredRows.length / TABLE_PER_PAGE));
  const safePage = Math.min(Math.max(1, page), totalPages);
  const pagedRows = useMemo(() => {
    const start = (safePage - 1) * TABLE_PER_PAGE;
    return filteredRows.slice(start, start + TABLE_PER_PAGE);
  }, [filteredRows, safePage]);

  useEffect(() => {
    if (page > totalPages && totalPages >= 1) {
      setSearchParams(
        (prev) => {
          const n = new URLSearchParams(prev);
          n.set("page", String(totalPages));
          return n;
        },
        { replace: true },
      );
    }
  }, [page, totalPages, setSearchParams]);

  const handleStatusFilter = useCallback(
    (status: DeliverySearchStatus | null) => {
      setUiFilters((prev) => ({
        ...prev,
        deliveryStatus: status === null ? "الكل" : status,
      }));
      setSearchParams(
        (prev) => {
          const n = new URLSearchParams(prev);
          n.set("page", "1");
          return n;
        },
        { replace: true },
      );
    },
    [setSearchParams],
  );

  const activeStatStatus: DeliverySearchStatus | null =
    uiFilters.deliveryStatus === "الكل" ? null : uiFilters.deliveryStatus;

  const { mutate: exportOrdersToCSV, isPending: isExporting } = useMutation(
    useExportOrdersToCSVMutationOptions(),
  );

  const deliverMutation = useMutation(useDeliverOrderMutationOptions());
  const finishMutation = useMutation(useFinishOrderMutationOptions());

  const handleViewOrder = useCallback(
    (row: DeliverySearchRow) => navigate(`/orders/${row.order.id}`),
    [navigate],
  );

  const handleWorkflowConfirm = useCallback(
    async (row: DeliverySearchRow, action: string) => {
      try {
        if (action === "تأكيد الإرجاع") {
          await finishMutation.mutateAsync(row.order.id);
          toast.success(`تم إنهاء الطلب #${row.order.id} بنجاح`);
        } else {
          await deliverMutation.mutateAsync(row.order.id);
          toast.success(`تم تسليم الطلب #${row.order.id} بنجاح`);
        }
        await refetch();
      } catch (e: unknown) {
        const msg = e instanceof Error ? e.message : String(e);
        const isReturn = action === "تأكيد الإرجاع";
        if (!isReturn && msg.includes(ORDER_NEEDS_CUSTODY)) {
          setCustodyModalOrder(row.order);
        }
        toast.error(isReturn ? "خطأ في إنهاء الطلب" : "خطأ في تسليم الطلب", {
          description: msg,
        });
        throw e;
      }
    },
    [deliverMutation, finishMutation, refetch],
  );

  const handleExport = () => {
    exportOrdersToCSV(apiFilters, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "deliveries-returns.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير البيانات بنجاح");
      },
      onError: (error: { message?: string }) => {
        toast.error("خطأ أثناء التصدير. الرجاء المحاولة مرة أخرى.", {
          description: error?.message,
        });
      },
    });
  };

  const [isExportingPDF, setIsExportingPDF] = useState(false);

  const handleExportPDF = async () => {
    setIsExportingPDF(true);
    try {
      const result = await getOrders(1, 500, apiFilters);
      const payments = result?.data ?? [];
      if (payments.length === 0) {
        toast.info("لا توجد طلبات لتصديرها");
        return;
      }
      const printDate = new Date().toLocaleDateString("ar-EG", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });
      const rows = payments
        .map((o) => {
          const row = mapOrderToDeliveryRow(o);
          return `<tr>
            <td>${row.invoiceNumber}</td>
            <td>${row.customerName}</td>
            <td>${row.branchName}</td>
            <td>${row.deliveryStatus}</td>
            <td>${row.paymentStatus}</td>
            <td>${row.totalAmount.toLocaleString("ar-EG")} ${row.currencySymbol}</td>
          </tr>`;
        })
        .join("");
      const html = `<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"/><title>تقرير</title>
        <style>body{font-family:Segoe UI,Tahoma,sans-serif;padding:20px;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #e2e8f0;padding:8px;text-align:right}th{background:#0369A1;color:#fff}</style></head><body>
        <h1>بحث التسليمات والإرجاعات</h1><p class="meta">${printDate}</p>
        <table><thead><tr><th>فاتورة</th><th>عميل</th><th>فرع</th><th>حالة التسليم</th><th>الدفع</th><th>المبلغ</th></tr></thead><tbody>${rows}</tbody></table></body></html>`;
      const win = window.open("", "_blank");
      if (!win) {
        toast.error("الرجاء السماح بالنوافذ المنبثقة");
        return;
      }
      win.document.write(html);
      win.document.close();
      win.focus();
      setTimeout(() => win.print(), 400);
      toast.success("تم فتح نافذة الطباعة");
    } catch {
      toast.error("حدث خطأ أثناء التصدير");
    } finally {
      setIsExportingPDF(false);
    }
  };

  const advancedSection = (
    <div className="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <button
        type="button"
        onClick={() => setShowAdvanced((v) => !v)}
        className="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
      >
        <span>فلاتر متقدمة (رقم الفاتورة، الصنف، التصنيف، مواعيد مفصّلة)</span>
        <i className={`ri-arrow-${showAdvanced ? "up" : "down"}-s-line text-lg`} />
      </button>
      {showAdvanced ? (
        <div className="px-4 pb-4 border-t border-slate-100 pt-4">
          <Form {...form}>
            <form className="space-y-4">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <FormField
                  control={form.control}
                  name="order_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>رقم الفاتورة</FormLabel>
                      <FormControl>
                        <Input type="text" inputMode="numeric" placeholder="مثال: 1024" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="client_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>العميل</FormLabel>
                      <FormControl>
                        <ClientsSelect value={field.value ?? ""} onChange={field.onChange} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="employee_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>الموظف</FormLabel>
                      <FormControl>
                        <EmployeesSelect
                          params={{ per_page: 20 }}
                          value={field.value ?? ""}
                          onChange={field.onChange}
                          placeholder="اختر الموظف..."
                          searchPlaceholder="ابحث عن موظف..."
                          allowClear
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="cloth_name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>الصنف</FormLabel>
                      <FormControl>
                        <Input placeholder="ابحث باسم الصنف" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="cloth_code"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>كود الصنف</FormLabel>
                      <FormControl>
                        <Input placeholder="ابحث بكود الصنف" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="category_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>قسم المنتجات</FormLabel>
                      <FormControl>
                        <CategoriesSelect
                          value={field.value ?? ""}
                          onChange={(value) => {
                            field.onChange(value);
                            form.setValue("subcategory_id", "");
                            updateUrlCategorySubcategory(value ?? "", "");
                          }}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="subcategory_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>قسم المنتجات الفرعي</FormLabel>
                      <FormControl>
                        <SubcategoriesSelect
                          value={field.value ?? ""}
                          onChange={(value) => {
                            field.onChange(value);
                            updateUrlCategorySubcategory(
                              form.watch("category_id") ?? "",
                              value ?? "",
                            );
                          }}
                          category_id={
                            form.watch("category_id")?.trim()
                              ? Number(form.watch("category_id"))
                              : undefined
                          }
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="visit_date_from"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>تاريخ التأجير من</FormLabel>
                      <FormControl>
                        <CustomCalendar
                          value={field.value}
                          onChange={field.onChange}
                          placeholder="اختر التاريخ"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="visit_date_to"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>تاريخ التأجير إلى</FormLabel>
                      <FormControl>
                        <CustomCalendar
                          value={field.value}
                          onChange={field.onChange}
                          placeholder="اختر التاريخ"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="delivery_date_from"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>تاريخ التسليم من</FormLabel>
                      <FormControl>
                        <CustomCalendar
                          value={field.value}
                          onChange={field.onChange}
                          placeholder="اختر التاريخ"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="delivery_date_to"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>تاريخ التسليم إلى</FormLabel>
                      <FormControl>
                        <CustomCalendar
                          value={field.value}
                          onChange={field.onChange}
                          placeholder="اختر التاريخ"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="return_date_from"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>تاريخ الاسترجاع من</FormLabel>
                      <FormControl>
                        <CustomCalendar
                          value={field.value}
                          onChange={field.onChange}
                          placeholder="اختر التاريخ"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="return_date_to"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>تاريخ الاسترجاع إلى</FormLabel>
                      <FormControl>
                        <CustomCalendar
                          value={field.value}
                          onChange={field.onChange}
                          placeholder="اختر التاريخ"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
              <div className="flex justify-end">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => {
                    form.reset(ORDERS_FILTER_FORM_DEFAULTS);
                    setUiFilters(defaultUiFilters());
                    setSearchParams({ page: "1" });
                  }}
                >
                  مسح الفلاتر
                </Button>
              </div>
            </form>
          </Form>
        </div>
      ) : null}
    </div>
  );

  return (
    <div className="p-4 md:p-6 space-y-5 min-h-screen" dir="rtl">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h1 className="text-xl font-black text-slate-800">بحث التسليمات والإرجاعات</h1>
          <p className="text-sm text-slate-500 mt-0.5">
            تتبع حالة تسليم وإرجاع جميع الفواتير — إيجار، بيع، تفصيل
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={handleExport}
            disabled={isExporting}
            className="flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 cursor-pointer transition-colors whitespace-nowrap disabled:opacity-50"
          >
            <i className="ri-file-excel-2-line text-emerald-600 text-base" />
            {isExporting ? "جاري التصدير..." : "Excel"}
          </button>
          <button
            type="button"
            onClick={() => void handleExportPDF()}
            disabled={isExportingPDF}
            className="flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 cursor-pointer transition-colors whitespace-nowrap disabled:opacity-50"
          >
            <i className="ri-file-pdf-2-line text-red-500 text-base" />
            PDF
          </button>
          <button
            type="button"
            onClick={() => window.print()}
            className="flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 cursor-pointer transition-colors whitespace-nowrap"
          >
            <i className="ri-printer-line text-slate-500 text-base" />
            طباعة
          </button>
        </div>
      </div>

      <DeliverySearchStats
        records={allMapped}
        onStatusFilter={handleStatusFilter}
        activeStatus={activeStatStatus}
      />

      <DeliverySearchFilters
        filters={uiFilters}
        onChange={setUiFilters}
        branches={branches}
        resultCount={filteredRows.length}
        totalCount={allMapped.length}
        advancedSection={advancedSection}
      />

      {isPending && data == null ? (
        <div className="bg-white rounded-xl border border-slate-200 overflow-hidden">
          <div className="p-8 flex flex-col items-center gap-3">
            <span className="w-10 h-10 border-2 border-slate-200 border-t-slate-500 rounded-full animate-spin" />
            <p className="text-sm text-slate-500">جاري تحميل البيانات...</p>
          </div>
        </div>
      ) : (
        <DeliverySearchTable
          records={pagedRows}
          onViewInvoice={handleViewOrder}
          onWorkflowConfirm={handleWorkflowConfirm}
        />
      )}

      <div className="flex justify-end">
        <CustomPagination
          totalElementsLabel="إجمالي النتائج"
          totalElements={filteredRows.length}
          totalPages={totalPages}
          isLoading={isPending}
        />
      </div>

      <CreateCustodyModal
        open={custodyModalOrder !== null}
        onOpenChange={(open) => {
          if (!open) setCustodyModalOrder(null);
        }}
        orderId={custodyModalOrder?.id ?? 0}
        currencySymbol={
          custodyModalOrder
            ? getOrderCurrencyInfo(custodyModalOrder).currency_symbol
            : undefined
        }
        onSuccess={() => {
          setCustodyModalOrder(null);
          void refetch();
        }}
      />
    </div>
  );
}

export default DeliveriesReturnsSearch;
