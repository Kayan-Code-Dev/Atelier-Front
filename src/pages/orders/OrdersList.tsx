import { useState, useMemo, useCallback, useEffect } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { useSearchParams, useNavigate } from "react-router";
import { Button } from "@/components/ui/button";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import CustomPagination from "@/components/custom/CustomPagination";
import {
  ordersFilterSchema,
  type OrdersFilterFormValues,
  ORDERS_FILTER_FORM_DEFAULTS,
  ORDERS_FILTER_DEBOUNCE_MS,
} from "./ordersFilterForm";
import {
  useExportOrdersToCSVMutationOptions,
  useGetOrdersQueryOptions,
  useCancelOrderMutationOptions,
  useDeleteOrderMutationOptions,
  useDeliverOrderMutationOptions,
} from "@/api/v2/orders/orders.hooks";
import { TOrder } from "@/api/v2/orders/orders.types";
import { RentalPrintInvoiceModal } from "./rental/components/RentalPrintInvoiceModal";
import { OrderReceiptAckPrintModal } from "./OrderReceiptAckPrintModal";
import { CancelOrderConfirmDialog } from "./CancelOrderConfirmDialog";
import { CreateCustodyModal } from "./CreateCustodyModal";
import { CreatePaymentModal } from "./CreatePaymentModal";
import { getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";
import RentalStats from "./rental/components/RentalStats";
import RentalTable from "./rental/components/RentalTable";
import {
  RENTAL_PAYMENT_FILTER_OPTIONS,
  RENTAL_STATUS_FILTER_OPTIONS,
} from "./rental/rental.constants";
import { filterRentalInvoices } from "./rental/rental.filters";
import SoldInvoicesStats from "@/pages/sales/components/SoldInvoicesStats";
import SoldInvoicesTable from "@/pages/sales/components/SoldInvoicesTable";
import { filterSoldInvoices } from "@/pages/sales/components/soldInvoices.helpers";
import { SOLD_PROCESS_TYPE } from "@/lib/salesOrderConstants";
import { soldOrderDetailPath } from "@/pages/sales/salesOrderPaths";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
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
import useDebounce from "@/hooks/useDebounce";

function OrdersList() {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const page = Number(searchParams.get("page")) || 1;
  const per_page = 10;
  const [showFilters, setShowFilters] = useState(false);

  const form = useForm<OrdersFilterFormValues>({
    resolver: zodResolver(ordersFilterSchema),
    defaultValues: ORDERS_FILTER_FORM_DEFAULTS,
  });

  const formValues = form.watch();
  const debouncedFormValues = useDebounce({
    value: formValues,
    delay: ORDERS_FILTER_DEBOUNCE_MS,
  });

  
  const [search, setSearch] = useState(() => searchParams.get("search") ?? "");
  const debouncedSearch = useDebounce({
    value: search,
    delay: ORDERS_FILTER_DEBOUNCE_MS,
  });

  const searchFromUrl = searchParams.get("search") ?? "";
  useEffect(() => {
    setSearch(searchFromUrl);
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
      { replace: true }
    );
  }, [debouncedSearch, setSearchParams]);

  const urlCategoryId = searchParams.get("category_id") ?? "";
  const urlSubcategoryId = searchParams.get("subcategory_id") ?? "";
  const processTypeParam = searchParams.get("process_type");
  const process_type: "rent" | typeof SOLD_PROCESS_TYPE | undefined =
    processTypeParam === "rent" || processTypeParam === SOLD_PROCESS_TYPE
      ? processTypeParam
      : undefined;
  const isSoldListView = process_type === SOLD_PROCESS_TYPE;

  const filters = useMemo(() => {
    const v = debouncedFormValues;
    const categoryId = urlCategoryId.trim() || (v.category_id?.trim() ? v.category_id : undefined);
    const subcategoryId = urlSubcategoryId.trim() || (v.subcategory_id?.trim() ? v.subcategory_id : undefined);
    const searchQ = debouncedSearch.trim();
    return {
      process_type,
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
      search: searchQ !== "" ? searchQ : undefined,
    };
  }, [debouncedFormValues, debouncedSearch, urlCategoryId, urlSubcategoryId, process_type]);

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
        { replace: true }
      );
    },
    [setSearchParams]
  );

  const [printModalOpen, setPrintModalOpen] = useState(false);
  const [selectedOrderForPrint, setSelectedOrderForPrint] = useState<TOrder | null>(null);
  const [printCopyLabel, setPrintCopyLabel] = useState<string | undefined>(undefined);
  const [ackModalOpen, setAckModalOpen] = useState(false);
  const [selectedOrderForAck, setSelectedOrderForAck] = useState<TOrder | null>(null);
  
  const [statusFilter, setStatusFilter] = useState<string>(RENTAL_STATUS_FILTER_OPTIONS[0]);
  const [paymentFilter, setPaymentFilter] = useState<string>(RENTAL_PAYMENT_FILTER_OPTIONS[0]);
  const [soldStatusFilter, setSoldStatusFilter] = useState("");
  const [soldPaymentFilter, setSoldPaymentFilter] = useState("");

  const { data, isPending, refetch } = useQuery(
    useGetOrdersQueryOptions(page, per_page, filters)
  );

  const [showCancelDialog, setShowCancelDialog] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [orderToAction, setOrderToAction] = useState<TOrder | null>(null);
  const [custodyModalOrder, setCustodyModalOrder] = useState<TOrder | null>(null);
  const [paymentModalOrder, setPaymentModalOrder] = useState<TOrder | null>(null);

  const ordersOnPageForView = useMemo(() => {
    const rows = data?.data ?? [];
    if (process_type === "sold") return rows;
    return rows.filter((o) => o.order_type === "rent");
  }, [data?.data, process_type]);

  const filteredOrdersForTable = useMemo(() => {
    if (isSoldListView) {
      return filterSoldInvoices(ordersOnPageForView, {
        search,
        statusFilter: soldStatusFilter,
        paymentFilter: soldPaymentFilter,
      });
    }
    return filterRentalInvoices(ordersOnPageForView, {
      search,
      statusFilter,
      paymentFilter,
    });
  }, [
    isSoldListView,
    ordersOnPageForView,
    search,
    statusFilter,
    paymentFilter,
    soldStatusFilter,
    soldPaymentFilter,
  ]);

  const statsCurrencySymbol = getOrderCurrencyInfo(
    ordersOnPageForView[0] ?? null
  ).currency_symbol;

  const { mutate: exportOrdersToCSV, isPending: isExporting } = useMutation(
    useExportOrdersToCSVMutationOptions()
  );

  const handleEditOrder = useCallback(
    (order: TOrder) => navigate("/orders/update-clothes-in-order", { state: { order } }),
    [navigate],
  );

  const handleViewOrder = useCallback(
    (order: TOrder) => {
      if (process_type === SOLD_PROCESS_TYPE) {
        navigate(soldOrderDetailPath(order.id));
      } else {
        navigate(`/orders/${order.id}`);
      }
    },
    [navigate, process_type],
  );

  const handlePrintInvoice = useCallback((order: TOrder, copyLabel?: string) => {
    setSelectedOrderForPrint(order);
    setPrintCopyLabel(copyLabel);
    setPrintModalOpen(true);
  }, []);

  const handlePrintAck = useCallback((order: TOrder) => {
    setSelectedOrderForAck(order);
    setAckModalOpen(true);
  }, []);

  const { mutate: deliverOrder, isPending: isDelivering } = useMutation(
    useDeliverOrderMutationOptions()
  );
  const { mutate: cancelOrder, isPending: isCanceling } = useMutation(
    useCancelOrderMutationOptions()
  );
  const { mutate: deleteOrder, isPending: isDeleting } = useMutation(
    useDeleteOrderMutationOptions()
  );

  const handleMarkAsDelivered = (order: TOrder) => {
    deliverOrder(order.id, {
      onSuccess: () => {
        toast.success(`تم تسليم الطلب #${order.id} بنجاح`);
        refetch();
      },
      onError: (error: { message?: string }) => {
        toast.error("خطأ أثناء تسليم الطلب", { description: error.message });
      },
    });
  };

  const handleAddPayment = useCallback((order: TOrder) => {
    setPaymentModalOrder(order);
  }, []);

  const handleOpenCancelDialog = useCallback((order: TOrder) => {
    setOrderToAction(order);
    setShowCancelDialog(true);
  }, []);

  const handleOpenDeleteDialog = useCallback((order: TOrder) => {
    setOrderToAction(order);
    setShowDeleteDialog(true);
  }, []);

  const handleCancelOrder = () => {
    if (!orderToAction) return;
    cancelOrder(orderToAction.id, {
      onSuccess: () => {
        toast.success(`تم إلغاء الطلب #${orderToAction.id} بنجاح`);
        setShowCancelDialog(false);
        setOrderToAction(null);
        refetch();
      },
      onError: (error: { message?: string }) => {
        toast.error("خطأ أثناء إلغاء الطلب", { description: error.message });
      },
    });
  };

  const handleDeleteOrder = () => {
    if (!orderToAction) return;
    deleteOrder(orderToAction.id, {
      onSuccess: () => {
        toast.success(`تم حذف الطلب #${orderToAction.id} بنجاح`);
        setShowDeleteDialog(false);
        setOrderToAction(null);
        refetch();
      },
      onError: (error: { message?: string }) => {
        toast.error("خطأ أثناء حذف الطلب", { description: error.message });
      },
    });
  };

  const isActive = (o: TOrder) =>
    o.status !== "canceled" && o.status !== "delivered";
  const canMarkAsDelivered = (o: TOrder) => isActive(o);
  const canCancelOrder = (o: TOrder) => isActive(o);
  const canEditOrder = (o: TOrder) => isActive(o);
  const canPrintOrder = (o: TOrder) => o.status !== "canceled";
  const canAddPayment = (o: TOrder) =>
    o.status !== "paid" && o.status !== "finished" && o.status !== "canceled";
  const canCreateCustodyForOrder = (o: TOrder) =>
    o.order_type === "rent" && isActive(o);
  const canDeleteOrder = (o: TOrder) =>
    o.status === "canceled" || o.status === "created";

  // --- Export Handler (same filters as list; filename from Content-Disposition) ---
  const handleExport = () => {
    exportOrdersToCSV(filters, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "orders.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير الطلبات بنجاح");
      },
      onError: (error: { message?: string }) => {
        toast.error("خطأ أثناء تصدير الطلبات. الرجاء المحاولة مرة أخرى.", {
          description: error?.message,
        });
      },
    });
  };

  return (
    <>
      {isSoldListView ? (
        <div className="p-6 space-y-6 min-h-screen bg-slate-50" dir="rtl">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-slate-800">قسم المبيعات المباشرة</h1>
              <p className="text-sm text-slate-400 mt-0.5">
                إدارة فواتير البيع والمدفوعات ومتابعة العملاء
              </p>
            </div>
            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => navigate("/sales/appointments")}
                className="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border border-violet-200 text-violet-700 bg-violet-50 hover:bg-violet-100 transition-colors cursor-pointer whitespace-nowrap"
              >
                <i className="ri-calendar-schedule-line" />
                جدول المواعيد
              </button>
              <button
                type="button"
                onClick={() => navigate("/sales/create")}
                className="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white cursor-pointer whitespace-nowrap transition-colors"
                style={{ background: "linear-gradient(135deg, #6366F1, #4F46E5)" }}
              >
                <i className="ri-add-line" />
                فاتورة بيع جديدة
              </button>
            </div>
          </div>

          <SoldInvoicesStats
            orders={ordersOnPageForView}
            totalInvoicesFromApi={data?.total ?? 0}
          />

          <div className="bg-white rounded-xl p-4 border border-slate-100 flex items-center gap-3 flex-wrap">
            <div className="flex items-center gap-2 flex-1 min-w-64 bg-slate-50 rounded-lg px-3 py-2 border border-slate-100">
              <i className="ri-search-line text-slate-400 text-sm" />
              <input
                className="bg-transparent text-sm flex-1 outline-none text-slate-700 placeholder-slate-400 text-right"
                placeholder="بحث باسم العميلة، رقم الفاتورة، الهاتف..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </div>

            <select
              className="text-sm bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-slate-600 outline-none cursor-pointer"
              value={soldStatusFilter}
              onChange={(e) => setSoldStatusFilter(e.target.value)}
            >
              <option value="">كل الحالات</option>
              <option value="معلقة">معلقة</option>
              <option value="مكتملة">مكتملة</option>
              <option value="ملغية">ملغية</option>
            </select>

            <select
              className="text-sm bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-slate-600 outline-none cursor-pointer"
              value={soldPaymentFilter}
              onChange={(e) => setSoldPaymentFilter(e.target.value)}
            >
              <option value="">كل حالات الدفع</option>
              <option value="مدفوع بالكامل">مدفوع بالكامل</option>
              <option value="مدفوع جزئياً">مدفوع جزئياً</option>
              <option value="غير مدفوع">غير مدفوع</option>
            </select>

            <button
              type="button"
              onClick={handleExport}
              disabled={isExporting}
              className="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold disabled:opacity-60 border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors"
            >
              <i className="ri-file-excel-2-line" />
              {isExporting ? "جاري التصدير..." : "تصدير Excel"}
            </button>

            {(search || soldStatusFilter || soldPaymentFilter) && (
              <button
                type="button"
                onClick={() => {
                  setSearch("");
                  setSoldStatusFilter("");
                  setSoldPaymentFilter("");
                }}
                className="flex items-center gap-1.5 text-sm text-red-500 hover:text-red-700 cursor-pointer transition-colors whitespace-nowrap"
              >
                <i className="ri-close-circle-line" />
                مسح الفلاتر
              </button>
            )}
          </div>

          <SoldInvoicesTable
            orders={filteredOrdersForTable}
            isPending={isPending}
            totalInvoicesFromApi={data?.total ?? 0}
            onPrint={(o) => handlePrintInvoice(o)}
          />

          <div className="mt-4 px-4 py-3 flex items-center justify-between flex-wrap gap-3 rounded-lg border border-slate-200 bg-slate-50">
            <span className="text-sm text-slate-500">
              إجمالي الطلبات:{" "}
              <span className="font-semibold text-slate-700">{data?.total ?? 0}</span>
              <span className="text-slate-400 mx-2">·</span>
              <span className="text-xs text-slate-400">
                {ordersOnPageForView.length} بيع في الصفحة
              </span>
            </span>
            <CustomPagination
              totalElementsLabel="إجمالي الطلبات"
              totalElements={data?.total || 0}
              totalPages={data?.total_pages || 1}
              isLoading={isPending}
            />
          </div>
        </div>
      ) : (
        <div className="p-6" dir="rtl">
      <div className="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
          <h1 className="text-xl font-bold text-slate-800">فواتير الإيجار</h1>
          <p className="text-sm text-slate-500 mt-0.5">
            إدارة عقود الإيجار ومتابعة المنتجات المؤجرة
          </p>
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <button
            type="button"
            onClick={handleExport}
            disabled={isExporting}
            className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold disabled:opacity-60 border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100/80 transition-colors"
          >
            <i className="ri-file-excel-2-line" />
            {isExporting ? "جاري التصدير..." : "تصدير Excel"}
          </button>
          <button
            type="button"
            onClick={() => navigate("/orders/rental/create")}
            className="flex items-center gap-2 bg-slate-800 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-700 transition-colors cursor-pointer whitespace-nowrap"
          >
            <i className="ri-add-line" />
            فاتورة إيجار جديدة
          </button>
        </div>
      </div>

      <RentalStats invoices={ordersOnPageForView} currencySymbol={statsCurrencySymbol} />

      <div className="bg-white rounded-lg border border-slate-200 px-4 py-3 mb-4 flex items-center gap-3 flex-wrap">
        <div className="relative flex-1 min-w-48">
          <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="ابحث بالاسم أو الهاتف أو الرقم القومي..."
            className="w-full border border-slate-200 rounded-md pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
          />
        </div>

        <div className="flex items-center gap-1 bg-slate-100 rounded-lg p-1 flex-wrap">
          {RENTAL_STATUS_FILTER_OPTIONS.map((s) => (
            <button
              key={s}
              type="button"
              onClick={() => setStatusFilter(s)}
              className={`px-3 py-1.5 rounded-md text-xs font-medium transition-colors cursor-pointer whitespace-nowrap ${
                statusFilter === s
                  ? "bg-white text-slate-800 shadow-sm"
                  : "text-slate-500 hover:text-slate-700"
              }`}
            >
              {s}
            </button>
          ))}
        </div>

        <select
          value={paymentFilter}
          onChange={(e) => setPaymentFilter(e.target.value)}
          className="border border-slate-200 rounded-md px-3 py-2 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-slate-200 cursor-pointer"
        >
          {RENTAL_PAYMENT_FILTER_OPTIONS.map((o) => (
            <option key={o} value={o}>
              {o}
            </option>
          ))}
        </select>

        <div className="text-xs text-slate-400 whitespace-nowrap">
          {filteredOrdersForTable.length} فاتورة
        </div>

        <button
          type="button"
          onClick={() => setShowFilters((p) => !p)}
          className="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors ms-auto"
        >
          <i className="ri-filter-3-line" />
          {showFilters ? "إخفاء الفلاتر" : "فلاتر متقدمة"}
        </button>
      </div>

      {showFilters && (
        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <h3 className="text-xs font-semibold text-slate-600 mb-3 flex items-center gap-2">
            <i className="ri-equalizer-line text-blue-500" />
            تصفية النتائج
          </h3>
          <Form {...form}>
                <form className="space-y-4">
                  <div className="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    {/* Invoice number */}
                    <FormField
                      control={form.control}
                      name="order_id"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>رقم الفاتورة</FormLabel>
                          <FormControl>
                            <Input
                              type="text"
                              inputMode="numeric"
                              placeholder="مثال: 1024"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    {/* Client */}
                    <FormField
                      control={form.control}
                      name="client_id"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>العميل</FormLabel>
                          <FormControl>
                            <ClientsSelect
                              value={field.value ?? ""}
                              onChange={field.onChange}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    {/* Employee */}
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

                    {/* Item name */}
                    <FormField
                      control={form.control}
                      name="cloth_name"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>الصنف</FormLabel>
                          <FormControl>
                            <Input
                              placeholder="ابحث باسم الصنف"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    {/* Item code (cloth_code) */}
                    <FormField
                      control={form.control}
                      name="cloth_code"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>كود الصنف</FormLabel>
                          <FormControl>
                            <Input
                              placeholder="ابحث بكود الصنف"
                              {...field}
                            />
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
                                updateUrlCategorySubcategory(form.watch("category_id") ?? "", value ?? "");
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

                    {/* Rental date from / to */}
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

                    {/* Delivery date from / to */}
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

                    {/* Return date from / to */}
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
                        setSearch("");
                        setStatusFilter(RENTAL_STATUS_FILTER_OPTIONS[0]);
                        setPaymentFilter(RENTAL_PAYMENT_FILTER_OPTIONS[0]);
                        setSearchParams((prev) => {
                          const next = new URLSearchParams();
                          next.set("page", "1");
                          next.set("per_page", String(per_page));
                          const pt = prev.get("process_type");
                          if (pt === "sold" || pt === "rent") {
                            next.set("process_type", pt);
                          }
                          return next;
                        });
                      }}
                    >
                      مسح الفلاتر
                    </Button>
                  </div>
                </form>
              </Form>
        </div>
      )}

      <RentalTable
        invoices={filteredOrdersForTable}
        isPending={isPending}
        isDelivering={isDelivering}
        onPrint={(o) => handlePrintInvoice(o)}
        onPrintClientCopy={(o) => handlePrintInvoice(o, "نسخة العميل")}
        onPrintAck={handlePrintAck}
        onEdit={handleEditOrder}
        onView={handleViewOrder}
        onCustody={(o) => setCustodyModalOrder(o)}
        onPayment={handleAddPayment}
        onDeliver={handleMarkAsDelivered}
        onCancel={handleOpenCancelDialog}
        onDelete={handleOpenDeleteDialog}
        canEdit={canEditOrder}
        canPrint={canPrintOrder}
        canAddPayment={canAddPayment}
        canCustody={canCreateCustodyForOrder}
        canDeliver={canMarkAsDelivered}
        canCancel={canCancelOrder}
        canDelete={canDeleteOrder}
      />

      <div className="mt-4 px-4 py-3 flex items-center justify-between flex-wrap gap-3 rounded-lg border border-slate-200 bg-slate-50">
        <span className="text-sm text-slate-500">
          إجمالي الطلبات:{" "}
          <span className="font-semibold text-slate-700">{data?.total ?? 0}</span>
          <span className="text-slate-400 mx-2">·</span>
          <span className="text-xs text-slate-400">
            {ordersOnPageForView.length} إيجار في الصفحة
          </span>
        </span>
        <CustomPagination
          totalElementsLabel="إجمالي الطلبات"
          totalElements={data?.total || 0}
          totalPages={data?.total_pages || 1}
          isLoading={isPending}
        />
      </div>
        </div>
      )}

      {/* Invoice Print Modal */}
      <RentalPrintInvoiceModal
        order={selectedOrderForPrint}
        open={printModalOpen}
        onOpenChange={setPrintModalOpen}
        defaultDocType={printCopyLabel === "نسخة العميل" ? "customer" : "admin"}
      />

      {/* Receipt Acknowledgment Print Modal */}
      <OrderReceiptAckPrintModal
        order={selectedOrderForAck}
        open={ackModalOpen}
        onOpenChange={setAckModalOpen}
      />

      <CancelOrderConfirmDialog
        open={showCancelDialog}
        onOpenChange={setShowCancelDialog}
        orderId={orderToAction?.id ?? 0}
        subtitle={orderToAction?.client?.name?.trim() || undefined}
        paidAmount={orderToAction?.paid}
        currencySymbol={
          orderToAction
            ? getOrderCurrencyInfo(orderToAction).currency_symbol
            : "ج.م"
        }
        onConfirm={handleCancelOrder}
        isConfirming={isCanceling}
      />

      {/* Delete Order Dialog */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف الطلب #{orderToAction?.id}</AlertDialogTitle>
            <AlertDialogDescription>
              ⚠️ هذا الإجراء لا يمكن التراجع عنه.
              <br />
              سيتم حذف الطلب بشكل نهائي من قاعدة البيانات.
              {Number(orderToAction?.paid ?? 0) > 0 && (
                <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded">
                  ⚠️ تنبيه: هذا الطلب لديه مدفوعات. الحذف قد يؤثر على السجلات المالية.
                </div>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>إلغاء</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteOrder}
              className="bg-red-600 hover:bg-red-700"
              disabled={isDeleting}
            >
              {isDeleting ? "جاري الحذف..." : "تأكيد الحذف"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Create Custody Modal */}
      {custodyModalOrder && (
        <CreateCustodyModal
          open={!!custodyModalOrder}
          onOpenChange={(open) => !open && setCustodyModalOrder(null)}
          orderId={custodyModalOrder.id}
          onSuccess={() => {
            refetch();
            setCustodyModalOrder(null);
          }}
        />
      )}

      {/* Add Payment Modal */}
      {paymentModalOrder && (
        <CreatePaymentModal
          open={!!paymentModalOrder}
          onOpenChange={(open) => {
            if (!open) setPaymentModalOrder(null);
          }}
          order={paymentModalOrder}
          onSuccess={() => {
            refetch();
            setPaymentModalOrder(null);
          }}
        />
      )}
    </>
  );
}

export { OrdersList };
export default OrdersList;
