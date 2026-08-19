import { useMemo } from "react";
import type { TOrder } from "@/api/v2/orders/orders.types";
import { fmtNumber } from "@/utils/formatDate";
import { OrderDetailsModal } from "@/pages/orders/OrderDetailsModal";
import { ReturnOrderSelectItemsModal } from "@/pages/returns/ReturnOrderSelectItemsModal";
import ReturnInvoicesStats from "@/pages/returns/components/ReturnInvoicesStats";
import ReturnInvoicesFilters from "@/pages/returns/components/ReturnInvoicesFilters";
import ReturnInvoicesTable from "@/pages/returns/components/ReturnInvoicesTable";
import { useReturnInvoicesData } from "@/pages/returns/components/useReturnInvoicesData";
import { OVERDUE_RETURNS_FILTER } from "@/pages/returns/constants";

const canReturnOrder = (order: TOrder) => order.status !== "canceled";

function OverdueReturnsList() {
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
  } = useReturnInvoicesData({
    apiFilter: OVERDUE_RETURNS_FILTER,
    sortByDelay: true,
  });

  const totalUnpaidPenalties = useMemo(
    () => filteredRows.reduce((s, r) => s + r.invoice.penalty.totalPenalty, 0),
    [filteredRows],
  );

  return (
    <div className="p-6 space-y-5 min-h-screen w-full" dir="rtl">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-3">
            <span className="w-8 h-8 flex items-center justify-center rounded-lg bg-red-100 text-red-500">
              <i className="ri-alarm-warning-line text-lg" />
            </span>
            الإرجاعات المتأخرة
          </h1>
          <p className="text-sm text-gray-400 mt-0.5 mr-11">
            الفواتير التي تجاوزت موعد الاسترجاع المحدد — مرتبة حسب عدد أيام
            التأخير
          </p>
        </div>
        <div className="flex items-center gap-2">
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
              {/* Alert Banner */}
              {mappedRows.length > 0 ? (
                <div className="bg-red-50 border border-red-200 rounded-xl px-5 py-4 flex items-start gap-4">
                  <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-red-100 text-red-500 flex-shrink-0">
                    <i className="ri-alarm-warning-line text-xl" />
                  </div>
                  <div className="flex-1">
                    <p className="font-semibold text-red-700 text-sm">
                      يوجد {mappedRows.length} فاتورة متأخرة الاسترجاع
                    </p>
                    <p className="text-red-500 text-xs mt-0.5">
                      إجمالي الغرامات المستحقة:
                      <strong className="mr-1">
                        {fmtNumber(totalUnpaidPenalties)} ج.م
                      </strong>
                      — يرجى التواصل مع العملاء فوراً
                    </p>
                  </div>
                  <div className="text-left">
                    <span className="text-3xl font-bold text-red-500">
                      {mappedRows.length}
                    </span>
                    <p className="text-xs text-red-400">متأخر</p>
                  </div>
                </div>
              ) : (
                <div className="bg-green-50 border border-green-200 rounded-xl px-5 py-4 flex items-center gap-4">
                  <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-green-100 text-green-600">
                    <i className="ri-shield-check-line text-xl" />
                  </div>
                  <div>
                    <p className="font-semibold text-green-700 text-sm">
                      لا توجد إرجاعات متأخرة حالياً
                    </p>
                    <p className="text-green-500 text-xs mt-0.5">
                      جميع العملاء ملتزمون بمواعيد الاسترجاع
                    </p>
                  </div>
                </div>
              )}

              <ReturnInvoicesStats
                entries={filteredRows.map((r) => r.invoice)}
                allEntries={mappedRows.map((r) => r.invoice)}
                isOverduePage
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
                lateOnly
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

export default OverdueReturnsList;
