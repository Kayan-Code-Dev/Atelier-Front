import { ORDER_NEEDS_CUSTODY } from "@/api/v2/orders/order.errors";
import {
  useCancelOrderMutationOptions,
  useDeliverOrderMutationOptions,
  useFinishOrderMutationOptions,
  useGetOrderDetailsQueryOptions,
} from "@/api/v2/orders/orders.hooks";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useMemo, useState } from "react";
import { useParams } from "react-router";
import { toast } from "sonner";
import { CancelOrderConfirmDialog } from "./CancelOrderConfirmDialog";
import { CreateCustodyModal } from "./CreateCustodyModal";
import OrderDetailsSkeleton from "./OrderDetailsSkeleton";
import { OrderDetailsNonRentalView } from "./OrderDetailsNonRentalView";
import { SoldInvoiceDetailLayout } from "@/pages/sales/components/SoldInvoiceDetailLayout";
import { ReturnOrderItemModal } from "./ReturnOrderItemModal";
import { RentalOrderDetailLayout } from "./rental/components/RentalOrderDetailLayout";
import { getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";
import { SOLD_PROCESS_TYPE } from "@/lib/salesOrderConstants";

function OrderDetails() {
  const { id } = useParams<{ id: string }>();
  const orderId = id ? parseInt(id, 10) : 0;
  const [isCustodyModalOpen, setIsCustodyModalOpen] = useState(false);
  const [returnItemModal, setReturnItemModal] = useState<{
    open: boolean;
    itemId: number | null;
  }>({ open: false, itemId: null });
  const [cancelOrderDialogOpen, setCancelOrderDialogOpen] = useState(false);

  const { data: orderData, isPending } = useQuery({
    ...useGetOrderDetailsQueryOptions(orderId),
    enabled: !!orderId,
  });

  const { mutate: deliverOrder, isPending: isDelivering } = useMutation(
    useDeliverOrderMutationOptions()
  );

  const { mutate: finishOrder, isPending: isFinishing } = useMutation(
    useFinishOrderMutationOptions()
  );

  const { mutate: cancelOrder, isPending: isCanceling } = useMutation(
    useCancelOrderMutationOptions()
  );

  const isUpdating = isDelivering || isFinishing || isCanceling;

  const handleDeliverOrder = () => {
    if (orderData?.id) {
      deliverOrder(orderData.id, {
        onSuccess: () => {
          toast.success("تم تسليم الطلب");
        },
        onError: (error: any) => {
          toast.error("خطأ في تسليم الطلب", {
            description: error.message,
          });
          if (error.message.includes(ORDER_NEEDS_CUSTODY)) {
            setIsCustodyModalOpen(true);
          }
        },
      });
    }
  };

  const handleCancelOrder = () => {
    if (orderData?.id) {
      cancelOrder(orderData.id, {
        onSuccess: () => {
          toast.success("تم إلغاء الطلب");
          setCancelOrderDialogOpen(false);
        },
        onError: (error: any) => {
          toast.error("خطأ في إلغاء الطلب", {
            description: error.message,
          });
        },
      });
    }
  };

  const showFinishButton = useMemo(() => {
    const items = orderData?.items;
    if (!items?.length) return false;
    return items.every((item) => item.returnable === 0);
  }, [orderData?.items]);

  const orderToolbar =
    orderData ? (
      <div className="flex flex-wrap gap-2">
        {orderData.status === "paid" && (
          <Button
            variant="default"
            onClick={handleDeliverOrder}
            disabled={isUpdating}
            isLoading={isDelivering}
          >
            تسليم
          </Button>
        )}
        {showFinishButton && (
          <Button
            variant="default"
            onClick={() => finishOrder(orderData.id)}
            disabled={isUpdating}
            isLoading={isFinishing}
          >
            إنهاء
          </Button>
        )}
        {orderData.status !== "canceled" && (
          <Button
            variant="destructive"
            onClick={() => setCancelOrderDialogOpen(true)}
            disabled={isUpdating}
          >
            إلغاء
          </Button>
        )}
      </div>
    ) : null;

  return (
    <>
      {isPending ? (
        <div className="w-full space-y-6" dir="rtl">
          <OrderDetailsSkeleton />
        </div>
      ) : orderData?.order_type === "rent" ? (
        <div className="w-full space-y-6" dir="rtl">
          <RentalOrderDetailLayout
            order={orderData}
            toolbarActions={orderToolbar}
            onReturnItem={(itemId) => setReturnItemModal({ open: true, itemId })}
            onAddCustody={() => setIsCustodyModalOpen(true)}
            custodyActionDisabled={isUpdating}
          />
        </div>
      ) : orderData?.process_type === SOLD_PROCESS_TYPE ? (
        <SoldInvoiceDetailLayout order={orderData} toolbarActions={orderToolbar} />
      ) : orderData ? (
        <div className="w-full space-y-6" dir="rtl">
          <OrderDetailsNonRentalView
            orderData={orderData}
            orderToolbar={orderToolbar}
            onReturnItem={(itemId) => setReturnItemModal({ open: true, itemId })}
          />
        </div>
      ) : (
        <div className="w-full space-y-6" dir="rtl">
          <Card>
            <CardContent className="py-10">
              <p className="text-center text-muted-foreground">لا توجد بيانات لعرضها.</p>
            </CardContent>
          </Card>
        </div>
      )}

      {orderData && (
        <CancelOrderConfirmDialog
          open={cancelOrderDialogOpen}
          onOpenChange={setCancelOrderDialogOpen}
          orderId={orderData.id}
          subtitle={orderData.client?.name?.trim() || undefined}
          paidAmount={orderData.paid}
          currencySymbol={getOrderCurrencyInfo(orderData).currency_symbol}
          onConfirm={handleCancelOrder}
          isConfirming={isCanceling}
        />
      )}

      {orderData && (
        <CreateCustodyModal
          open={isCustodyModalOpen}
          onOpenChange={setIsCustodyModalOpen}
          orderId={orderData.id}
          currencySymbol={getOrderCurrencyInfo(orderData).currency_symbol}
        />
      )}

      {orderData && returnItemModal.itemId !== null && (
        <ReturnOrderItemModal
          open={returnItemModal.open}
          onOpenChange={(open) =>
            setReturnItemModal({
              open,
              itemId: open ? returnItemModal.itemId : null,
            })
          }
          orderId={orderData.id}
          itemId={returnItemModal.itemId}
          itemName={
            orderData.items.find((item) => item.id === returnItemModal.itemId)?.name
          }
        />
      )}
    </>
  );
}

export default OrderDetails;
