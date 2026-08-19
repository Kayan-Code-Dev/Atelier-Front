import { useState, useMemo, useCallback } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { useNavigate } from "react-router";
import {
  useGetOrdersQueryOptions,
  useDeliverOrderMutationOptions,
  useFinishOrderMutationOptions,
  useCancelOrderMutationOptions,
  useDeleteOrderMutationOptions,
} from "@/api/v2/orders/orders.hooks";
import { getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";
import { ORDER_NEEDS_CUSTODY } from "@/api/v2/orders/order.errors";
import type { TOrder } from "@/api/v2/orders/orders.types";
import { CancelOrderConfirmDialog } from "@/pages/orders/CancelOrderConfirmDialog";
import { CreateCustodyModal } from "@/pages/orders/CreateCustodyModal";
import { CreatePaymentModal } from "@/pages/orders/CreatePaymentModal";
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
import { mapOrderToDeliveryInvoiceProject } from "./invoices-project/mapOrderToDeliveryInvoiceProject";
import type { ProjectDeliveryStatus } from "./invoices-project/deliveryInvoiceProject.types";
import DeliveryInvoicesStats from "./invoices-project/DeliveryInvoicesStats";
import DeliveryInvoicesFilters, {
  type DeliveryInvoicesFiltersState,
} from "./invoices-project/DeliveryInvoicesFilters";
import DeliveryInvoicesTable from "./invoices-project/DeliveryInvoicesTable";

const FETCH_PER_PAGE = 500;

const initialFilters: DeliveryInvoicesFiltersState = {
  search: "",
  paymentStatus: "الكل",
  deliveryStatus: "الكل",
  employee: "الكل",
  branch: "الكل",
  eventDateFrom: "",
  eventDateTo: "",
};

function DeliveriesList() {
  const navigate = useNavigate();
  const [filters, setFilters] = useState<DeliveryInvoicesFiltersState>(initialFilters);
  const [showCancelDialog, setShowCancelDialog] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [orderToAction, setOrderToAction] = useState<TOrder | null>(null);
  const [custodyModalOrder, setCustodyModalOrder] = useState<TOrder | null>(null);
  const [paymentModalOrder, setPaymentModalOrder] = useState<TOrder | null>(null);

  const { data, isPending, isError, error, refetch } = useQuery(
    useGetOrdersQueryOptions(1, FETCH_PER_PAGE, {}),
  );

  const mappedRows = useMemo(() => {
    const list = (data?.data ?? []).map((o) => ({
      invoice: mapOrderToDeliveryInvoiceProject(o),
      order: o,
    }));
    list.sort((a, b) => {
      const ae = a.invoice.dates.eventDate || "9999-12-31";
      const be = b.invoice.dates.eventDate || "9999-12-31";
      return ae.localeCompare(be);
    });
    return list;
  }, [data?.data]);

  const employeeOptions = useMemo(() => {
    const s = new Set<string>();
    mappedRows.forEach((r) => {
      if (r.invoice.employee && r.invoice.employee !== "-") s.add(r.invoice.employee);
    });
    return Array.from(s).sort();
  }, [mappedRows]);

  const branchOptions = useMemo(() => {
    const s = new Set<string>();
    mappedRows.forEach((r) => {
      if (r.invoice.branch && r.invoice.branch !== "-") s.add(r.invoice.branch);
    });
    return Array.from(s).sort();
  }, [mappedRows]);

  const filteredRows = useMemo(() => {
    const q = filters.search.trim();
    return mappedRows.filter(({ invoice: e }) => {
      if (q) {
        const ok =
          e.customer.name.includes(q) ||
          e.invoiceRef.toLowerCase().includes(q.toLowerCase()) ||
          e.customer.nationalId.includes(q) ||
          e.customer.phone.includes(q);
        if (!ok) return false;
      }
      if (filters.paymentStatus !== "الكل" && e.paymentStatus !== filters.paymentStatus)
        return false;
      if (filters.deliveryStatus !== "الكل" && e.deliveryStatus !== filters.deliveryStatus)
        return false;
      if (filters.employee !== "الكل" && e.employee !== filters.employee) return false;
      if (filters.branch !== "الكل" && e.branch !== filters.branch) return false;
      if (filters.eventDateFrom && e.dates.eventDate && e.dates.eventDate < filters.eventDateFrom)
        return false;
      if (filters.eventDateTo && e.dates.eventDate && e.dates.eventDate > filters.eventDateTo)
        return false;
      return true;
    });
  }, [mappedRows, filters]);

  const handleFilterChange = useCallback((key: keyof DeliveryInvoicesFiltersState, value: string) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
  }, []);

  const handleResetFilters = useCallback(() => {
    setFilters(initialFilters);
  }, []);

  const handleExport = () => {
    const rows = filteredRows.map(({ invoice: e }) => ({
      "#": e.id,
      "رقم الفاتورة": e.invoiceRef,
      "اسم العميل": e.customer.name,
      "الرقم القومي": e.customer.nationalId,
      الهاتف: e.customer.phone,
      "تاريخ الفاتورة": e.dates.invoiceDate,
      "موعد الاستلام": e.dates.pickupDate,
      "موعد الفرح": e.dates.eventDate,
      "موعد الاسترجاع": e.dates.returnDate,
      الإجمالي: e.pricing.total,
      المدفوع: e.pricing.paid,
      المتبقي: e.pricing.remaining,
      "حالة الدفع": e.paymentStatus,
      "حالة التسليم": e.deliveryStatus,
      الموظف: e.employee,
      الفرع: e.branch,
    }));
    if (rows.length === 0) {
      toast.info("لا توجد بيانات للتصدير");
      return;
    }
    const header = Object.keys(rows[0]).join(",");
    const csv =
      "data:text/csv;charset=utf-8,\uFEFF" +
      header +
      "\n" +
      rows.map((r) => Object.values(r).join(",")).join("\n");
    const link = document.createElement("a");
    link.href = encodeURI(csv);
    link.download = `تسليمات-الفواتير-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    toast.success("تم تصدير الملف");
  };

  const { mutate: deliverOrder, isPending: isDelivering } = useMutation(
    useDeliverOrderMutationOptions(),
  );
  const { mutate: finishOrder } = useMutation(useFinishOrderMutationOptions());
  const { mutate: cancelOrder, isPending: isCanceling } = useMutation(
    useCancelOrderMutationOptions(),
  );
  const { mutate: deleteOrder, isPending: isDeleting } = useMutation(
    useDeleteOrderMutationOptions(),
  );

  const handleDeliver = (order: TOrder) => {
    deliverOrder(order.id, {
      onSuccess: () => {
        toast.success(`تم تسليم الطلب #${order.id} بنجاح`);
        void refetch();
      },
      onError: (err: { message?: string }) => {
        const msg = err?.message ?? "";
        if (typeof msg === "string" && msg.includes(ORDER_NEEDS_CUSTODY)) {
          setCustodyModalOrder(order);
        }
        toast.error("خطأ في تسليم الطلب", { description: msg });
      },
    });
  };

  const handleProjectStatusChange = (order: TOrder, status: ProjectDeliveryStatus) => {
    const current = mapOrderToDeliveryInvoiceProject(order).deliveryStatus;
    if (status === current) return;

    if (status === "تم التسليم") {
      handleDeliver(order);
      return;
    }
    if (status === "تم الاسترجاع") {
      finishOrder(order.id, {
        onSuccess: () => {
          toast.success(`تم إنهاء الطلب #${order.id} بنجاح`);
          void refetch();
        },
        onError: (err: { message?: string }) => {
          toast.error("خطأ في إنهاء الطلب", { description: err?.message });
        },
      });
      return;
    }
    if (status === "ملغي") {
      setOrderToAction(order);
      setShowCancelDialog(true);
      return;
    }
    toast.info("تغيير الحالة من القائمة متاح لتسليم أو إنهاء أو إلغاء فقط من هنا.");
  };

  const handleCancelOrder = () => {
    if (!orderToAction) return;
    cancelOrder(orderToAction.id, {
      onSuccess: () => {
        toast.success(`تم إلغاء الطلب #${orderToAction.id} بنجاح`);
        setShowCancelDialog(false);
        setOrderToAction(null);
        void refetch();
      },
      onError: (err: { message?: string }) => {
        toast.error("خطأ أثناء إلغاء الطلب", { description: err?.message });
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
        void refetch();
      },
      onError: (err: { message?: string }) => {
        toast.error("خطأ أثناء حذف الطلب", { description: err?.message });
      },
    });
  };

  return (
    <div className="p-6 space-y-5 min-h-screen w-full" dir="rtl">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">تسليمات الفواتير</h1>
          <p className="text-sm text-gray-400 mt-0.5">
            كل فاتورة تُعرض هنا حسب موعد فرح العميل — مرتبة تصاعدياً بالتاريخ (حتى{" "}
            {FETCH_PER_PAGE} طلباً)
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2 no-print">
          <button
            type="button"
            onClick={handleExport}
            className="flex items-center gap-2 px-4 py-2 text-sm text-green-700 border border-green-200 rounded-lg hover:bg-green-50 cursor-pointer whitespace-nowrap"
          >
            <div className="w-4 h-4 flex items-center justify-center">
              <i className="ri-file-excel-line" />
            </div>
            تصدير Excel
          </button>
          <button
            type="button"
            onClick={() => window.print()}
            className="flex items-center gap-2 px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 cursor-pointer whitespace-nowrap"
          >
            <div className="w-4 h-4 flex items-center justify-center">
              <i className="ri-printer-line" />
            </div>
            طباعة
          </button>
          <button
            type="button"
            onClick={() => navigate("/orders/rental/create")}
            className="flex items-center gap-2 px-4 py-2 text-sm bg-blue-700 text-white rounded-lg hover:bg-blue-800 cursor-pointer whitespace-nowrap"
          >
            <div className="w-4 h-4 flex items-center justify-center">
              <i className="ri-add-line" />
            </div>
            فاتورة جديدة
          </button>
        </div>
      </div>

      {isError ? (
        <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-8 text-center text-sm text-red-700">
          {error?.message ?? "تعذر تحميل البيانات"}
        </div>
      ) : null}

      {!isError ? (
        <>
          {isPending && data == null ? (
            <div className="rounded-xl border border-blue-100 bg-white py-12 text-center text-gray-500 text-sm">
              <i className="ri-loader-4-line inline-block animate-spin text-xl ml-2" />
              جاري تحميل البيانات...
            </div>
          ) : (
            <>
              <DeliveryInvoicesStats
                entries={filteredRows.map((r) => r.invoice)}
                allEntries={mappedRows.map((r) => r.invoice)}
              />

              <DeliveryInvoicesFilters
                filters={filters}
                onChange={handleFilterChange}
                onReset={handleResetFilters}
                count={filteredRows.length}
                total={mappedRows.length}
                employeeOptions={employeeOptions}
                branchOptions={branchOptions}
              />

              <DeliveryInvoicesTable
                rows={filteredRows}
                onStatusChange={handleProjectStatusChange}
                onNavigateOrder={(id) => navigate(`/orders/${id}`)}
                onCancelClick={(order) => {
                  setOrderToAction(order);
                  setShowCancelDialog(true);
                }}
                onDeleteClick={(order) => {
                  setOrderToAction(order);
                  setShowDeleteDialog(true);
                }}
                onPaymentClick={(order) => setPaymentModalOrder(order)}
                onCustodyClick={(order) => setCustodyModalOrder(order)}
                onEdit={(order) =>
                  navigate("/orders/update-clothes-in-order", { state: { order } })
                }
                onDeliver={handleDeliver}
                isDelivering={isDelivering}
              />
            </>
          )}
        </>
      ) : null}

      <CancelOrderConfirmDialog
        open={showCancelDialog}
        onOpenChange={setShowCancelDialog}
        orderId={orderToAction?.id ?? 0}
        subtitle={orderToAction?.client?.name?.trim() || undefined}
        paidAmount={orderToAction?.paid}
        currencySymbol={
          orderToAction ? getOrderCurrencyInfo(orderToAction).currency_symbol : "ج.م"
        }
        onConfirm={handleCancelOrder}
        isConfirming={isCanceling}
      />

      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف الطلب #{orderToAction?.id}</AlertDialogTitle>
            <AlertDialogDescription>
              هذا الإجراء لا يمكن التراجع عنه. سيتم حذف الطلب بشكل نهائي.
              {Number(orderToAction?.paid ?? 0) > 0 ? (
                <span className="block mt-2 text-red-600">
                  تنبيه: الطلب يحتوي على مدفوعات.
                </span>
              ) : null}
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

      {paymentModalOrder ? (
        <CreatePaymentModal
          open
          onOpenChange={(open) => {
            if (!open) setPaymentModalOrder(null);
          }}
          order={paymentModalOrder}
          onSuccess={() => {
            setPaymentModalOrder(null);
            void refetch();
          }}
        />
      ) : null}
    </div>
  );
}

export default DeliveriesList;
