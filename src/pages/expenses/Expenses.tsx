import { useExpensesPage } from "./hooks/useExpensesPage";
import { ExpenseStats } from "./components/ExpenseStats";
import { ExpenseFilters } from "./components/ExpenseFilters";
import { ExpenseTable } from "./components/ExpenseTable";
import { CreateExpenseModal } from "./CreateExpenseModal";
import { UpdateExpenseModal } from "./UpdateExpenseModal";
import { DeleteExpenseModal } from "./DeleteExpenseModal";
import { CancelExpenseModal } from "./CancelExpenseModal";
import { PayExpenseModal } from "./PayExpenseModal";
import { ExpenseDetailsModal } from "./ExpenseDetailsModal";

function Expenses() {
  const {
    form,
    data,
    isPending,
    isError,
    error,
    items,
    stats,
    totalAmount,
    paidAmount,
    paidCount,
    pendingCount,
    cancelledCount,
    selectedExpense,
    setSelectedExpense,
    isCreateModalOpen,
    setIsCreateModalOpen,
    isUpdateModalOpen,
    setIsUpdateModalOpen,
    isDeleteModalOpen,
    setIsDeleteModalOpen,
    isDetailsModalOpen,
    setIsDetailsModalOpen,
    isCancelModalOpen,
    setIsCancelModalOpen,
    isPayModalOpen,
    setIsPayModalOpen,
    isExporting,
    handleExport,
    isExportingPDF,
    handleExportPDF,
    handleResetFilters,
    openDetails,
    openUpdate,
    openDelete,
    openCancel,
    openPay,
    CATEGORY_OPTIONS,
    EXPENSE_CATEGORIES_WITH_SUBS,
  } = useExpensesPage();

  return (
    <div dir="rtl" className="space-y-5">
      <ExpenseStats stats={stats} />

      <ExpenseFilters
        form={form}
        itemsCount={items.length}
        totalCount={data?.total ?? 0}
        isExporting={isExporting}
        isExportingPDF={isExportingPDF}
        onResetFilters={handleResetFilters}
        onExport={handleExport}
        onExportPDF={handleExportPDF}
        onOpenCreate={() => setIsCreateModalOpen(true)}
        categoryOptions={CATEGORY_OPTIONS}
        categoriesWithSubs={EXPENSE_CATEGORIES_WITH_SUBS}
      />

      <ExpenseTable
        items={items}
        isPending={isPending}
        isError={isError}
        error={error}
        totalAmount={totalAmount}
        paidAmount={paidAmount}
        paidCount={paidCount}
        pendingCount={pendingCount}
        cancelledCount={cancelledCount}
        total={data?.total}
        totalPages={data?.total_pages}
        onResetFilters={handleResetFilters}
        onViewDetails={openDetails}
        onUpdate={openUpdate}
        onDelete={openDelete}
        onCancel={openCancel}
        onPay={openPay}
      />

      <CreateExpenseModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
      <UpdateExpenseModal
        open={isUpdateModalOpen}
        onOpenChange={setIsUpdateModalOpen}
        expense={selectedExpense}
      />
      <DeleteExpenseModal
        open={isDeleteModalOpen}
        onOpenChange={setIsDeleteModalOpen}
        expense={selectedExpense}
      />
      <CancelExpenseModal
        open={isCancelModalOpen}
        onOpenChange={setIsCancelModalOpen}
        expense={selectedExpense}
      />
      <PayExpenseModal
        open={isPayModalOpen}
        onOpenChange={setIsPayModalOpen}
        expense={selectedExpense}
      />
      <ExpenseDetailsModal
        open={isDetailsModalOpen}
        onOpenChange={(open) => {
          setIsDetailsModalOpen(open);
          if (!open) setSelectedExpense(null);
        }}
        expense={selectedExpense}
      />
    </div>
  );
}

export default Expenses;
