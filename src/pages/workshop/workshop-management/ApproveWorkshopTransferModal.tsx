import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import { useMutation } from "@tanstack/react-query";
import { useApproveWorkshopTransferMutationOptions } from "@/api/v2/workshop/workshops.hooks";
import { TWorkshopTransfer } from "@/api/v2/workshop/workshop.types";
import { TTransferClothesStatus } from "@/api/v2/clothes/transfer-clothes/transfer-clothes.types";
import { toast } from "sonner";
import { useState, useMemo } from "react";

const getStatusVariant = (status: TTransferClothesStatus) => {
  switch (status) {
    case "approved":
      return "bg-green-600 text-white hover:bg-green-600/80";
    case "rejected":
      return "bg-destructive text-destructive-foreground hover:bg-destructive/90";
    case "partially_approved":
      return "bg-green-600 text-white hover:bg-green-600/80";
    case "pending":
    case "partially_pending":
    default:
      return "bg-yellow-500 text-white hover:bg-yellow-500/80";
  }
};

const getStatusLabel = (status: TTransferClothesStatus) => {
  const labels: Record<TTransferClothesStatus, string> = {
    pending: "قيد الانتظار",
    approved: "تم القبول",
    rejected: "تم الرفض",
    partially_pending: "قيد الانتظار (جزئي)",
    partially_approved: "تم القبول (جزئي)",
  };
  return labels[status] || status;
};

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  transfer: TWorkshopTransfer | null;
  workshopId: number;
};

export function ApproveWorkshopTransferModal({
  open,
  onOpenChange,
  transfer,
  workshopId,
}: Props) {
  const [selectedItemIds, setSelectedItemIds] = useState<number[]>([]);

  const { mutate: approveTransfer, isPending } = useMutation(
    useApproveWorkshopTransferMutationOptions()
  );

  // Get pending items
  const pendingItems = useMemo(() => {
    if (!transfer) return [];
    return transfer.items.filter((item) => item.status === "pending");
  }, [transfer]);

  // Handle checkbox selection
  const handleToggleItemSelection = (itemId: number) => {
    setSelectedItemIds((prev) =>
      prev.includes(itemId)
        ? prev.filter((id) => id !== itemId)
        : [...prev, itemId]
    );
  };

  // Handle select all pending items
  const handleSelectAllPending = () => {
    const pendingItemIds = pendingItems.map((item) => item.id);
    if (pendingItemIds.every((id) => selectedItemIds.includes(id))) {
      setSelectedItemIds([]);
    } else {
      setSelectedItemIds(pendingItemIds);
    }
  };

  const handleApprove = () => {
    if (!transfer || selectedItemIds.length === 0) {
      toast.error("يرجى اختيار قطعة واحدة على الأقل");
      return;
    }

    approveTransfer(
      {
        workshop_id: workshopId,
        transfer_id: transfer.id,
        items: selectedItemIds,
      },
      {
        onSuccess: () => {
          toast.success("تم قبول القطع المحددة بنجاح");
          setSelectedItemIds([]);
          onOpenChange(false);
        },
        onError: (error: any) => {
          toast.error("حدث خطأ أثناء قبول القطع", {
            description: error.message,
          });
        },
      }
    );
  };

  const handleOpenChange = (newOpen: boolean) => {
    if (!newOpen) {
      setSelectedItemIds([]);
    }
    onOpenChange(newOpen);
  };

  if (!transfer) return null;

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="sm:max-w-4xl">
        <DialogHeader>
          <DialogTitle className="text-center">قبول طلب النقل</DialogTitle>
          <DialogDescription className="text-center">
            اختر القطع التي تريد قبولها من طلب النقل رقم {transfer.id}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4" dir="rtl">
          {/* Transfer Info */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm font-medium text-muted-foreground">من:</p>
              <p className="text-base">{transfer.from_entity_name}</p>
            </div>
            <div>
              <p className="text-sm font-medium text-muted-foreground">إلى:</p>
              <p className="text-base">{transfer.to_entity_name}</p>
            </div>
            {transfer.notes && (
              <div className="col-span-2">
                <p className="text-sm font-medium text-muted-foreground">
                  الملاحظات:
                </p>
                <p className="text-base">{transfer.notes}</p>
              </div>
            )}
          </div>

          {/* Items Table */}
          <div>
            <div className="flex items-center justify-between mb-2">
              <p className="text-sm font-medium text-muted-foreground">
                القطع:
              </p>
              {pendingItems.length > 0 && (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={handleSelectAllPending}
                >
                  {pendingItems.every((item) =>
                    selectedItemIds.includes(item.id)
                  )
                    ? "إلغاء تحديد الكل"
                    : "تحديد الكل"}
                </Button>
              )}
            </div>
            <div className="border rounded-md">
              <Table>
                <TableHeader>
                  <TableRow>
                    {pendingItems.length > 0 && (
                      <TableHead className="text-center w-12">
                        <Checkbox
                          checked={
                            pendingItems.every((item) =>
                              selectedItemIds.includes(item.id)
                            ) && pendingItems.length > 0
                          }
                          onCheckedChange={handleSelectAllPending}
                        />
                      </TableHead>
                    )}
                    <TableHead className="text-center">الكود</TableHead>
                    <TableHead className="text-center">الاسم</TableHead>
                    <TableHead className="text-center">الحالة</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {transfer.items.map((item) => {
                    const isPending = item.status === "pending";
                    const isSelected = selectedItemIds.includes(item.id);
                    return (
                      <TableRow key={item.id}>
                        {pendingItems.length > 0 && (
                          <TableCell className="text-center">
                            {isPending ? (
                              <Checkbox
                                checked={isSelected}
                                onCheckedChange={() =>
                                  handleToggleItemSelection(item.id)
                                }
                              />
                            ) : null}
                          </TableCell>
                        )}
                        <TableCell className="text-center">
                          {item.cloth_code}
                        </TableCell>
                        <TableCell className="text-center">
                          {item.cloth_name ?? item.cloth_code}
                        </TableCell>
                        <TableCell className="text-center">
                          <Badge className={getStatusVariant(item.status)}>
                            {getStatusLabel(item.status)}
                          </Badge>
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            onClick={() => handleOpenChange(false)}
            disabled={isPending}
          >
            إلغاء
          </Button>
          <Button
            onClick={handleApprove}
            disabled={isPending || selectedItemIds.length === 0}
            className="bg-green-600 hover:bg-green-600/80"
          >
            {isPending
              ? "جاري القبول..."
              : `قبول المحدد (${selectedItemIds.length})`}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

