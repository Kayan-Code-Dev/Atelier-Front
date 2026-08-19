import { usePaymentsPage } from "./hooks/usePaymentsPage";
import { PaymentStats } from "./components/PaymentStats";
import { PaymentFilters } from "./components/PaymentFilters";
import { PaymentTable } from "./components/PaymentTable";
import { PaymentDetailsModal } from "./PaymentDetailsModal";

function Payments() {
  const {
    form,
    data,
    isPending,
    isError,
    error,
    items,
    scope,
    branches,
    cashboxes,
    stats,
    totalAmount,
    completedCount,
    pendingCount,
    cancelledCount,
    showNotes,
    setShowNotes,
    selectedPayment,
    isDetailsModalOpen,
    setIsDetailsModalOpen,
    isExporting,
    isExportingPDF,
    markAsPaidMutation,
    markAsCanceledMutation,
    handleViewDetails,
    handleMarkAsPaid,
    handleMarkAsCanceled,
    handleResetFilters,
    handleExport,
    handleExportPDF,
    setSelectedPayment,
  } = usePaymentsPage();

  return (
    <div dir="rtl" className="space-y-5">
      <PaymentStats stats={stats} />

      <PaymentFilters
        form={form}
        branches={branches}
        cashboxes={cashboxes}
        itemsCount={items.length}
        totalCount={data?.total ?? 0}
        isExporting={isExporting}
        isExportingPDF={isExportingPDF}
        onResetFilters={handleResetFilters}
        onExport={handleExport}
        onExportPDF={handleExportPDF}
      />

      <PaymentTable
        items={items}
        scope={scope}
        isPending={isPending}
        isError={isError}
        error={error}
        totalAmount={totalAmount}
        completedCount={completedCount}
        pendingCount={pendingCount}
        cancelledCount={cancelledCount}
        total={data?.total}
        totalPages={data?.total_pages}
        showNotes={showNotes}
        setShowNotes={setShowNotes}
        onViewDetails={handleViewDetails}
        onMarkAsPaid={handleMarkAsPaid}
        onMarkAsCanceled={handleMarkAsCanceled}
        onResetFilters={handleResetFilters}
        markAsPaidMutation={markAsPaidMutation}
        markAsCanceledMutation={markAsCanceledMutation}
      />

      {showNotes && (
        <div
          className="fixed inset-0 z-10"
          onClick={() => setShowNotes(null)}
          aria-hidden
        />
      )}

      {selectedPayment && (
        <PaymentDetailsModal
          payment={selectedPayment}
          open={isDetailsModalOpen}
          onOpenChange={(open) => {
            setIsDetailsModalOpen(open);
            if (!open) setSelectedPayment(null);
          }}
        />
      )}
    </div>
  );
}

export default Payments;
