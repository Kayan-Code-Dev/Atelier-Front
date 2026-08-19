import { toast } from "sonner";
import type { TOrder } from "@/api/v2/orders/orders.types";
import { OrderDetailsModal } from "@/pages/orders/OrderDetailsModal";
import { ReturnOrderSelectItemsModal } from "./ReturnOrderSelectItemsModal";
import ReturnInvoicesStats from "./components/ReturnInvoicesStats";
import ReturnInvoicesFilters from "./components/ReturnInvoicesFilters";
import ReturnInvoicesTable from "./components/ReturnInvoicesTable";
import { useReturnInvoicesData } from "./components/useReturnInvoicesData";
import { RETURNS_FILTER } from "./constants";

const canReturnOrder = (order: TOrder) =>
  order.status === "delivered" || order.status === "partially_paid";

function ReturnsList() {
  const {
    filters,
    mappedRows,
    filteredRows,
    employeeOptions,
    clientOptions,
    isPending,
    isError,
    error,
    data,
    isViewModalOpen,
    setIsViewModalOpen,
    selectedOrder,
    orderToReturn,
    handleFilterChange,
    handleResetFilters,
    handleNavigateOrder,
    handleViewOrder,
    handleReturnOrder,
    handleReturnSuccess,
    handleCloseReturn,
  } = useReturnInvoicesData({ apiFilter: RETURNS_FILTER });

  const handleExport = () => {
    const rows = filteredRows.map(({ invoice: e }) => ({
      "#": e.id,
      "رقم الفاتورة": e.invoiceRef,
      "اسم العميل": e.customer.name,
      "الرقم القومي": e.customer.nationalId,
      الهاتف: e.customer.phone,
      "تاريخ الفاتورة": e.dates.invoiceDate,
      "موعد الفرح": e.dates.eventDate,
      "موعد الاسترجاع": e.dates.returnDate,
      "الاسترجاع الفعلي": e.dates.actualReturnDate || "-",
      الإجمالي: e.pricing.total,
      المدفوع: e.pricing.paid,
      المتبقي: e.pricing.remaining,
      "حالة الدفع": e.paymentStatus,
      "حالة الإرجاع": e.deliveryStatus,
      "نوع الإرجاع": e.returnType,
      "أيام التأخير": e.penalty.delayDays,
      "غرامة/يوم": e.penalty.penaltyPerDay,
      "إجمالي الغرامة": e.penalty.totalPenalty,
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
    link.download = `إرجاعات-الفواتير-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    toast.success("تم تصدير الملف");
  };

  return (
    <div className="p-6 space-y-5 min-h-screen w-full" dir="rtl">
      <div className="flex items-start justify-between">
          <div>
          <h1 className="text-2xl font-bold text-gray-800">
            إرجاعات الفواتير
          </h1>
          <p className="text-sm text-gray-400 mt-0.5">
            متابعة إرجاع جميع الفواتير — فوري / مجدول / متأخر مع احتساب
            الغرامات
          </p>
          </div>
          <div className="flex items-center gap-2">
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
              <ReturnInvoicesStats
                entries={filteredRows.map((r) => r.invoice)}
                allEntries={mappedRows.map((r) => r.invoice)}
              />

              <ReturnInvoicesFilters
                filters={filters}
                onChange={handleFilterChange}
                onReset={handleResetFilters}
                count={filteredRows.length}
                total={mappedRows.length}
                employeeOptions={employeeOptions}
                clientOptions={clientOptions}
              />

              <ReturnInvoicesTable
                rows={filteredRows}
                onNavigateOrder={handleNavigateOrder}
                onViewOrder={handleViewOrder}
                onReturnOrder={handleReturnOrder}
                canReturn={canReturnOrder}
              />
            </>
          )}
        </>
      ) : null}

      <OrderDetailsModal
        order={selectedOrder}
        open={isViewModalOpen}
        onOpenChange={setIsViewModalOpen}
      />

      <ReturnOrderSelectItemsModal
        open={!!orderToReturn}
        onOpenChange={handleCloseReturn}
        order={orderToReturn}
        onSuccess={handleReturnSuccess}
      />
    </div>
  );
}

export default ReturnsList;
