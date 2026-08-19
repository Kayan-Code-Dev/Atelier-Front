import { useState } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { Eye, Check, X, Plus } from "lucide-react";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationPrevious,
  PaginationNext,
} from "@/components/ui/pagination";
import { useGetPaymentsQueryOptions, useMarkPaymentAsPaidMutationOptions, useMarkPaymentAsCanceledMutationOptions } from "@/api/v2/payments/payments.hooks";
import { TPayment, TPaymentStatus, TPaymentType } from "@/api/v2/payments/payments.types";
import { formatDate } from "@/utils/formatDate";
import { PaymentDetailsModal } from "@/pages/payments/PaymentDetailsModal";
import { CreatePaymentModal } from "./CreatePaymentModal";
import { toast } from "sonner";
import { useGetOrderDetailsQueryOptions } from "@/api/v2/orders/orders.hooks";
import { TOrder } from "@/api/v2/orders/orders.types";
import { getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";

type Props = {
  orderId: number;
  order?: TOrder;
};

const getStatusLabel = (status: TPaymentStatus) => {
  const statusMap = {
    pending: "معلق",
    paid: "مدفوع",
    canceled: "ملغي",
  };
  return statusMap[status];
};

const getStatusBadgeClass = (status: TPaymentStatus) => {
  const classMap = {
    pending: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
    paid: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    canceled: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
  };
  return classMap[status];
};

const getPaymentTypeLabel = (type: TPaymentType) => {
  const typeMap = {
    initial: "مبدئي",
    fee: "رسوم",
    normal: "عادي",
  };
  return typeMap[type];
};

function PaymentsTableSkeleton({ rows = 5 }: { rows?: number }) {
  return Array.from({ length: rows }).map((_, index) => (
    <TableRow key={index}>
      <TableCell>
        <Skeleton className="h-5 w-16" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-24" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-24" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-24" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-32" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-32" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-20" />
      </TableCell>
    </TableRow>
  ));
}

export function OrderPaymentsTable({ orderId, order: orderProp }: Props) {
  const [page, setPage] = useState(1);
  const [selectedPayment, setSelectedPayment] = useState<TPayment | null>(null);
  const [isDetailsModalOpen, setIsDetailsModalOpen] = useState(false);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const per_page = 10;

  // Fetch order details if not provided
  const { data: orderData } = useQuery({
    ...useGetOrderDetailsQueryOptions(orderId),
    enabled: !!orderId && !orderProp,
  });

  const order = orderProp || orderData;

  const { data, isPending } = useQuery({
    ...useGetPaymentsQueryOptions({
      order_id: orderId,
      page,
      per_page,
    }),
    enabled: !!orderId,
  });

  // Mutations
  const markAsPaidMutation = useMutation(
    useMarkPaymentAsPaidMutationOptions()
  );
  const markAsCanceledMutation = useMutation(
    useMarkPaymentAsCanceledMutationOptions()
  );

  const handlePreviousPage = () => {
    setPage((prev) => Math.max(1, prev - 1));
  };

  const handleNextPage = () => {
    setPage((prev) => Math.min(prev + 1, data?.total_pages || 1));
  };

  const handleViewDetails = (payment: TPayment) => {
    setSelectedPayment(payment);
    setIsDetailsModalOpen(true);
  };

  const handleMarkAsPaid = async (paymentId: number) => {
    try {
      await markAsPaidMutation.mutateAsync(paymentId);
      toast.success("تم تحديث حالة الدفعة إلى مدفوع بنجاح");
    } catch {
      toast.error("حدث خطأ أثناء تحديث حالة الدفعة");
    }
  };

  const handleMarkAsCanceled = async (paymentId: number) => {
    try {
      await markAsCanceledMutation.mutateAsync(paymentId);
      toast.success("تم إلغاء الدفعة بنجاح");
    } catch {
      toast.error("حدث خطأ أثناء إلغاء الدفعة");
    }
  };

  return (
    <div className="bg-white rounded-lg p-6 shadow-sm border-t">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold">المدفوعات</h3>
        <Button
          variant="default"
          size="sm"
          onClick={() => setIsCreateModalOpen(true)}
        >
          <Plus className="ml-2 h-4 w-4" />
          إضافة دفعة
        </Button>
      </div>
      <div className="table-responsive-wrapper">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="text-center">#</TableHead>
              <TableHead className="text-center">المبلغ</TableHead>
              <TableHead className="text-center">الحالة</TableHead>
              <TableHead className="text-center">نوع الدفعة</TableHead>
              <TableHead className="text-center">تاريخ الدفع</TableHead>
              <TableHead className="text-center">تاريخ الإنشاء</TableHead>
              <TableHead className="text-center">الإجراءات</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isPending ? (
              <PaymentsTableSkeleton rows={per_page} />
            ) : data?.data && data.data.length > 0 ? (
              data.data.map((payment: TPayment, index: number) => (
                <TableRow key={payment.id}>
                  <TableCell className="text-center font-medium">
                    {(page - 1) * per_page + index + 1}
                  </TableCell>
                  <TableCell className="text-center font-medium" dir="ltr">
                    {(() => {
                      const { currency_symbol } = getOrderCurrencyInfo(order as any);
                      return `${payment.amount.toLocaleString()} ${currency_symbol}`;
                    })()}
                  </TableCell>
                  <TableCell className="text-center">
                    <span
                      className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusBadgeClass(
                        payment.status
                      )}`}
                    >
                      {getStatusLabel(payment.status)}
                    </span>
                  </TableCell>
                  <TableCell className="text-center">
                    {getPaymentTypeLabel(payment.payment_type)}
                  </TableCell>
                  <TableCell className="text-center">
                    {formatDate(payment.payment_date)}
                  </TableCell>
                  <TableCell className="text-center">
                    {formatDate(payment.created_at)}
                  </TableCell>
                  <TableCell className="text-center">
                    <div className="flex items-center gap-2 justify-center">
                      <Button
                        variant="ghost"
                        size="icon"
                        title="عرض التفاصيل"
                        onClick={() => handleViewDetails(payment)}
                      >
                        <Eye className="h-4 w-4" />
                      </Button>
                      {payment.status === "pending" && (
                        <>
                          <Button
                            variant="default"
                            size="icon"
                            title="تحديد كمدفوع"
                            onClick={() => handleMarkAsPaid(payment.id)}
                            disabled={
                              markAsPaidMutation.isPending ||
                              markAsCanceledMutation.isPending
                            }
                          >
                            <Check className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="destructive"
                            size="icon"
                            title="إلغاء"
                            onClick={() => handleMarkAsCanceled(payment.id)}
                            disabled={
                              markAsPaidMutation.isPending ||
                              markAsCanceledMutation.isPending
                            }
                          >
                            <X className="h-4 w-4" />
                          </Button>
                        </>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={7}
                  className="text-center text-muted-foreground py-10"
                >
                  لا توجد مدفوعات لعرضها
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
      {data && (
        <div className="mt-4 flex items-center justify-between">
          <div className="text-sm text-muted-foreground">
            إجمالي المدفوعات: {data.total || 0}
          </div>
          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationNext
                  href="#"
                  onClick={(e) => {
                    e.preventDefault();
                    handlePreviousPage();
                  }}
                  aria-disabled={page === 1}
                  className={page === 1 ? "pointer-events-none opacity-50" : ""}
                />
              </PaginationItem>
              <PaginationItem className="font-medium">
                صفحة {page} من {data.total_pages || 1}
              </PaginationItem>
              <PaginationItem>
                <PaginationPrevious
                  href="#"
                  onClick={(e) => {
                    e.preventDefault();
                    handleNextPage();
                  }}
                  aria-disabled={page === data.total_pages || isPending}
                  className={
                    page === data.total_pages || isPending
                      ? "pointer-events-none opacity-50"
                      : ""
                  }
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </div>
      )}

      {/* Payment Details Modal */}
      {selectedPayment && (
        <PaymentDetailsModal
          payment={selectedPayment}
          open={isDetailsModalOpen}
          onOpenChange={setIsDetailsModalOpen}
        />
      )}

      {/* Create Payment Modal */}
      {order && (
        <CreatePaymentModal
          open={isCreateModalOpen}
          onOpenChange={setIsCreateModalOpen}
          order={order}
          onSuccess={() => {
            // Refetch payments after successful creation
            window.location.reload();
          }}
        />
      )}
    </div>
  );
}

