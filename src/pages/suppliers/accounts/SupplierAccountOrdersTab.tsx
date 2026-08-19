import { useEffect, useMemo, useState } from "react";
import type { UseQueryResult } from "@tanstack/react-query";
import { useMutation } from "@tanstack/react-query";
import { toast } from "sonner";
import { Pencil, Banknote, RotateCcw } from "lucide-react";

import { useReturnSupplierOrderMutationOptions } from "@/api/v2/suppliers/suppliers.hooks";
import {
  TSupplierOrderResponse,
  TSupplierOrdersListResponse,
} from "@/api/v2/suppliers/suppliers.types";
import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { formatDate, toEnglishNumerals } from "@/utils/formatDate";
import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import { SupplierOrdersTableSkeleton } from "../SupplierOrdersTableSkeleton";
import { EditSupplierOrderModal } from "../EditSupplierOrderModal";
import { AddPaymentToSupplierOrderModal } from "../AddPaymentToSupplierOrderModal";
import { isActiveOrder, parseMoney } from "./supplierAccountHelpers";

const TABLE_PER_PAGE = 10;

type Props = {
  supplierId: number;
  /** Shared snapshot query from parent — avoids duplicate GET supplier-orders?page=1&per_page=10 */
  ordersQuery: UseQueryResult<TSupplierOrdersListResponse | undefined, Error>;
};

function getStatusBadgeVariant(
  status: string,
): "default" | "secondary" | "success" | "warning" | "destructive" | "outline" {
  const s = (status || "").toLowerCase();
  if (s === "pending" || s === "قيد الانتظار") return "warning";
  if (s === "completed" || s === "مكتمل" || s === "delivered") return "success";
  if (s === "cancelled" || s === "ملغى") return "destructive";
  return "secondary";
}

const STATUS_LABELS: Record<string, string> = {
  pending: "قيد الانتظار",
  completed: "مكتمل",
  cancelled: "ملغى",
  delivered: "تم التسليم",
};

function getStatusLabel(status: string): string {
  return STATUS_LABELS[(status || "").toLowerCase()] || status || "—";
}

function formatCurrency(value: string | number | null | undefined): string {
  if (value == null || value === "") return "—";
  const num = typeof value === "number" ? value : parseFloat(value);
  if (Number.isNaN(num)) return "—";
  return `${toEnglishNumerals(num.toLocaleString("en-US", { minimumFractionDigits: 2 }))} ج.م`;
}

export default function SupplierAccountOrdersTab({
  supplierId,
  ordersQuery,
}: Props) {
  const [page, setPage] = useState(1);

  const { data: ordersSnap, isPending, isError, error } = ordersQuery;

  const fullList = useMemo(() => ordersSnap?.data ?? [], [ordersSnap]);

  useEffect(() => {
    setPage(1);
  }, [supplierId]);

  const totalCount = ordersSnap?.total ?? fullList.length;
  const totalPages = Math.max(1, Math.ceil(totalCount / TABLE_PER_PAGE));

  const pageRows = useMemo(() => {
    const start = (page - 1) * TABLE_PER_PAGE;
    return fullList.slice(start, start + TABLE_PER_PAGE);
  }, [fullList, page]);

  const isPartialDataset =
    ordersSnap != null && totalCount > fullList.length;
  const tableStartIndex = (page - 1) * TABLE_PER_PAGE;
  const pageBeyondLoaded =
    !isPending &&
    !isError &&
    isPartialDataset &&
    tableStartIndex >= fullList.length;

  const [selectedOrder, setSelectedOrder] =
    useState<TSupplierOrderResponse | null>(null);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [orderForPayment, setOrderForPayment] =
    useState<TSupplierOrderResponse | null>(null);
  const [isPaymentOpen, setIsPaymentOpen] = useState(false);
  const [orderToReturn, setOrderToReturn] =
    useState<TSupplierOrderResponse | null>(null);

  const { mutate: returnOrder, isPending: isReturning } = useMutation(
    useReturnSupplierOrderMutationOptions(),
  );

  const isActive = (o: TSupplierOrderResponse) => isActiveOrder(o);
  const canEdit = (o: TSupplierOrderResponse) => isActive(o);
  const canAddPayment = (o: TSupplierOrderResponse) =>
    isActive(o) && parseMoney(o.remaining_payment) > 0;
  const canReturn = (o: TSupplierOrderResponse) => isActive(o);

  const handleConfirmReturn = (onClose: () => void) => {
    if (!orderToReturn) return;
    returnOrder(orderToReturn.id, {
      onSuccess: () => {
        toast.success("تم إرجاع الطلبية بنجاح");
        setOrderToReturn(null);
        onClose();
      },
      onError: (err: { message?: string }) => {
        toast.error("حدث خطأ أثناء إرجاع الطلبية", {
          description: err?.message,
        });
      },
    });
  };

  const canPrev = page > 1;
  const canNext = page < totalPages && !isPending;

  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        {isError && (
          <div className="p-6 text-center text-destructive text-sm">
            {error?.message ?? "حدث خطأ أثناء تحميل الطلبيات."}
          </div>
        )}
        {!isError && (
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow className="bg-gray-50 hover:bg-gray-50">
                  <TableHead className="text-center whitespace-nowrap w-12">
                    #
                  </TableHead>
                  <TableHead className="text-center whitespace-nowrap">
                    رقم الطلبية
                  </TableHead>
                  <TableHead className="text-center whitespace-nowrap">
                    التاريخ
                  </TableHead>
                  <TableHead className="text-center whitespace-nowrap">
                    الحالة
                  </TableHead>
                  <TableHead className="text-center whitespace-nowrap">
                    الإجمالي
                  </TableHead>
                  <TableHead className="text-center whitespace-nowrap">
                    المدفوع
                  </TableHead>
                  <TableHead className="text-center whitespace-nowrap">
                    المتبقي
                  </TableHead>
                  <TableHead className="text-center whitespace-nowrap">
                    الفرع
                  </TableHead>
                  <TableHead className="text-center whitespace-nowrap w-36">
                    إجراءات
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isPending ? (
                  <SupplierOrdersTableSkeleton rows={5} />
                ) : pageBeyondLoaded ? (
                  <TableRow>
                    <TableCell
                      colSpan={9}
                      className="h-28 text-center text-sm text-amber-800 bg-amber-50/60"
                    >
                      تم تحميل أول{" "}
                      {toEnglishNumerals(fullList.length)} طلبية في هذا الطلب؛
                      الصفحات
                      الأبعد تحتاج تحميلًا إضافيًا من الخادم أو ضيّق النطاق.
                    </TableCell>
                  </TableRow>
                ) : pageRows.length > 0 ? (
                  pageRows.map((order) => (
                    <TableRow key={order.id}>
                      <TableCell className="text-center font-medium">
                        <span dir="ltr" className="tabular-nums">
                          {toEnglishNumerals(order.id)}
                        </span>
                      </TableCell>
                      <TableCell className="text-center font-medium">
                        <span dir="ltr">
                          {toEnglishNumerals(order.order_number) || "—"}
                        </span>
                      </TableCell>
                      <TableCell className="text-center text-muted-foreground">
                        <span dir="ltr">
                          {order.order_date
                            ? toEnglishNumerals(formatDate(order.order_date))
                            : "—"}
                        </span>
                      </TableCell>
                      <TableCell className="text-center">
                        <Badge variant={getStatusBadgeVariant(order.status)}>
                          {getStatusLabel(order.status)}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-center tabular-nums">
                        <span dir="ltr">
                          {toEnglishNumerals(
                            formatCurrency(order.total_amount),
                          )}
                        </span>
                      </TableCell>
                      <TableCell className="text-center tabular-nums">
                        <span dir="ltr">
                          {toEnglishNumerals(
                            formatCurrency(order.payment_amount),
                          )}
                        </span>
                      </TableCell>
                      <TableCell className="text-center tabular-nums">
                        <span dir="ltr">
                          {toEnglishNumerals(
                            formatCurrency(order.remaining_payment),
                          )}
                        </span>
                      </TableCell>
                      <TableCell className="text-center text-muted-foreground">
                        {order.branch?.name ?? "—"}
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center justify-center gap-1 flex-wrap">
                          {canEdit(order) && (
                            <Button
                              variant="ghost"
                              size="icon"
                              className="h-8 w-8"
                              title="تحديث الطلبية"
                              onClick={() => {
                                setSelectedOrder(order);
                                setIsEditOpen(true);
                              }}
                            >
                              <Pencil className="h-4 w-4" />
                            </Button>
                          )}
                          {canAddPayment(order) && (
                            <Button
                              type="button"
                              variant="outline"
                              size="icon"
                              className="h-8 w-8"
                              title="إضافة دفعة"
                              onClick={() => {
                                setOrderForPayment(order);
                                setIsPaymentOpen(true);
                              }}
                            >
                              <Banknote className="h-4 w-4" />
                            </Button>
                          )}
                          {canReturn(order) && (
                            <Button
                              variant="outline"
                              size="icon"
                              className="h-8 w-8"
                              title="إرجاع الطلبية"
                              onClick={() => setOrderToReturn(order)}
                            >
                              <RotateCcw className="h-4 w-4" />
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                ) : fullList.length > 0 ? (
                  <TableRow>
                    <TableCell
                      colSpan={9}
                      className="h-32 text-center text-muted-foreground"
                    >
                      لا توجد طلبيات في هذه الصفحة.
                    </TableCell>
                  </TableRow>
                ) : (
                  <TableRow>
                    <TableCell
                      colSpan={9}
                      className="h-32 text-center text-muted-foreground"
                    >
                      لا توجد طلبيات لعرضها.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        )}

        {!isError && ordersSnap != null && ordersSnap.total != null && (
          <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-gray-100 bg-gray-50/80">
            <span className="text-sm text-slate-600">
              إجمالي الطلبيات:{" "}
              <strong className="text-slate-800">{ordersSnap.total}</strong>
            </span>
            <div className="flex items-center gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={!canPrev}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                السابق
              </Button>
              <span className="text-xs text-slate-500 tabular-nums" dir="ltr">
                {page} / {totalPages}
              </span>
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={!canNext}
                onClick={() =>
                  setPage((p) => Math.min(totalPages, p + 1))
                }
              >
                التالي
              </Button>
            </div>
          </div>
        )}
      </div>

      <EditSupplierOrderModal
        order={selectedOrder}
        open={isEditOpen}
        onOpenChange={setIsEditOpen}
      />

      <AddPaymentToSupplierOrderModal
        order={orderForPayment}
        open={isPaymentOpen}
        onOpenChange={(open) => {
          setIsPaymentOpen(open);
          if (!open) setOrderForPayment(null);
        }}
      />

      <ControlledConfirmationModal
        open={!!orderToReturn}
        onOpenChange={(open) => !open && setOrderToReturn(null)}
        alertTitle="إرجاع الطلبية"
        alertMessage={
          orderToReturn ? (
            <>
              هل أنت متأكد من إرجاع الطلبية{" "}
              <strong>#{orderToReturn.id}</strong>
              {orderToReturn.order_number && (
                <> ({orderToReturn.order_number})</>
              )}
              ؟
            </>
          ) : null
        }
        handleConfirmation={handleConfirmReturn}
        isPending={isReturning}
        pendingLabel="جاري الإرجاع..."
        confirmLabel="إرجاع الطلبية"
        cancelLabel="إلغاء"
        variant="destructive"
      />
    </div>
  );
}
