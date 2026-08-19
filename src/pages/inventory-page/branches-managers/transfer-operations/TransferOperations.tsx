import { useState } from "react";
import { format, parse } from "date-fns";
import { arEG } from "date-fns/locale";
// Shadcn Components
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import {
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardFooter,
  CardDescription,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Check, X, Plus } from "lucide-react";
import { toast} from "sonner";

import { CreateTransferModal } from "./CreateTransferModal"; // Adjust path
import { TransfersTableSkeleton } from "./TransfersTableSkeleton"; // Adjust path

// Import Types and Hooks
import { TInventoryTransfersItem } from "@/api/inventory/inventory.types"; // Adjust path
import {
  useGetInventoryTransfersQueryOptions,
  useApproveInventoryTransferMutationOptions,
  useRejectInventoryTransferMutationOptions,
} from "@/api/inventory/branches-managers/inventory.hooks"; // Adjust path
import { useQuery, useMutation } from "@tanstack/react-query";
import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import CustomPagination from "@/components/custom/CustomPagination";
import { useSearchParams } from "react-router";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import {
  APPROVE_INVENTORYTRANSFER,
  CREATE_INVENTORYTRANSFER,
  REJECT_INVENTORYTRANSFER,
} from "@/lib/permissions.helper";

/**
 * Helper function to determine badge color based on status
 */
const getStatusVariant = (status: TInventoryTransfersItem["status"]) => {
  const normalizedStatus = status.trim();
  switch (normalizedStatus) {
    case "تم القبول":
    case "تم الوصول":
      return "bg-green-600 text-white hover:bg-green-600/80";
    case "تم الرفض":
      return "bg-destructive text-destructive-foreground hover:bg-destructive/90";
    case "قيد الإنتظار":
    default:
      return "bg-yellow-500 text-white hover:bg-yellow-500/80";
  }
};

function TransferOperations() {
  const [searchParams] = useSearchParams();
  const page = Number(searchParams.get("page"));

  // Modal State
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isApproveModalOpen, setIsApproveModalOpen] = useState(false);
  const [isRejectModalOpen, setIsRejectModalOpen] = useState(false);
  const [selectedTransfer, setSelectedTransfer] =
    useState<TInventoryTransfersItem | null>(null);

  // Data Fetching
  const { data, isPending } = useQuery(useGetInventoryTransfersQueryOptions(page));

  // Mutations
  const { mutate: approveTransfer, isPending: isApproving } =
    useMutation(useApproveInventoryTransferMutationOptions());
  const { mutate: rejectTransfer, isPending: isRejecting } =
    useMutation(useRejectInventoryTransferMutationOptions());

  const handleOpenApprove = (transfer: TInventoryTransfersItem) => {
    setSelectedTransfer(transfer);
    setIsApproveModalOpen(true);
  };
  const handleOpenReject = (transfer: TInventoryTransfersItem) => {
    setSelectedTransfer(transfer);
    setIsRejectModalOpen(true);
  };

  // --- Confirmation Handlers ---
  const handleApprove = (onCloseModal: () => void) => {
    if (selectedTransfer) {
      approveTransfer(selectedTransfer.uuid, {
        onSuccess: () => {
          toast.success("تم قبول الطلب بنجاح");
          onCloseModal();
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء قبول الطلب", {
            description: error.message,
          });
        },
      });
    }
  };

  const handleReject = (onCloseModal: () => void) => {
    if (selectedTransfer) {
      rejectTransfer(selectedTransfer.uuid, {
        onSuccess: () => {
          toast.success("تم رفض الطلب بنجاح");
          onCloseModal();
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء رفض الطلب", {
            description: error.message,
          });
        },
      });
    }
  };

  const { data: permissions } = useMyPermissions();
  const hasCreatePermission =
    permissions && permissions.includes(CREATE_INVENTORYTRANSFER);
  const hasApprovePermission =
    permissions && permissions.includes(APPROVE_INVENTORYTRANSFER);
  const hasRejectPermission =
    permissions && permissions.includes(REJECT_INVENTORYTRANSFER);

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>عمليات نقل المخزون</CardTitle>
            <CardDescription>
              عرض وقبول أو رفض طلبات نقل المخزون بين الفروع.
            </CardDescription>
          </div>
          <Button
            onClick={() => setIsCreateModalOpen(true)}
            disabled={!hasCreatePermission}
            className="bg-main-gold"
          >
            <Plus className="ml-2 h-4 w-4" />
            إنشاء طلب نقل
          </Button>
        </CardHeader>

        <CardContent>
          <div className="table-responsive-wrapper">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="text-center">الصنف</TableHead>
                  <TableHead className="text-center">الكمية</TableHead>
                  <TableHead className="text-center">من فرع</TableHead>
                  <TableHead className="text-center">إلى فرع</TableHead>
                  <TableHead className="text-center">الحالة</TableHead>
                  <TableHead className="text-center">تاريخ الطلب</TableHead>
                  <TableHead className="text-center">تاريخ الوصول</TableHead>
                  <TableHead className="text-center">إجراءات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isPending ? (
                  <TransfersTableSkeleton rows={7} />
                ) : data && data.data.length > 0 ? (
                  data.data.map((item) => {
                    const isWaitingForAction = item.status === "قيد الانتظار";
                    return (
                      <TableRow key={item.uuid}>
                        <TableCell className="font-medium text-center">
                          {item.product_name}
                        </TableCell>
                        <TableCell className="text-center">
                          {item.quantity}
                        </TableCell>
                        <TableCell className="text-center">
                          {item.from_branch_name}
                        </TableCell>
                        <TableCell className="text-center">
                          {item.to_branch_name}
                        </TableCell>
                        <TableCell className="text-center">
                          <Badge className={getStatusVariant(item.status)}>
                            {item.status.trim()}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-center">
                          {format(
                            parse(item.transfer_date, "dd-MM-yyyy", new Date()),
                            "P",
                            { locale: arEG }
                          )}
                        </TableCell>
                        <TableCell className="text-center">
                          {item.arrival_date ? (
                            format(
                              parse(
                                item.arrival_date,
                                "dd-MM-yyyy",
                                new Date()
                              ),
                              "P",
                              { locale: arEG }
                            )
                          ) : (
                            <span className="text-muted-foreground">-</span>
                          )}
                        </TableCell>
                        <TableCell className="text-center">
                          {isWaitingForAction ? (
                            <div className="flex items-center gap-2 justify-center">
                              <Button
                                variant="default"
                                size="icon"
                                title="قبول"
                                className="bg-green-600 hover:bg-green-600/80"
                                onClick={() => handleOpenApprove(item)}
                                disabled={!hasApprovePermission}
                              >
                                <Check className="h-4 w-4" />
                              </Button>
                              <Button
                                variant="destructive"
                                size="icon"
                                title="رفض"
                                onClick={() => handleOpenReject(item)}
                                disabled={!hasRejectPermission}
                              >
                                <X className="h-4 w-4" />
                              </Button>
                            </div>
                          ) : (
                            <span className="text-muted-foreground">-</span>
                          )}
                        </TableCell>
                      </TableRow>
                    );
                  })
                ) : (
                  <TableRow>
                    <TableCell
                      colSpan={8}
                      className="py-10 text-center text-muted-foreground"
                    >
                      لا توجد طلبات نقل لعرضها.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>

        <CardFooter className="flex items-center justify-between">
          <CustomPagination
            totalElementsLabel="إجمالي الطلبات"
            totalElements={data?.total}
            totalPages={data?.total_pages}
            isLoading={isPending}
          />
        </CardFooter>
      </Card>

      {/* --- Modals --- */}
      <CreateTransferModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />

      <ControlledConfirmationModal
        open={isApproveModalOpen}
        onOpenChange={setIsApproveModalOpen}
        alertTitle="تأكيد القبول"
        alertMessage={
          <>
            هل أنت متأكد أنك تريد قبول طلب نقل{" "}
            <strong>{selectedTransfer?.quantity}</strong> من{" "}
            <strong>{selectedTransfer?.product_name}</strong>؟
          </>
        }
        handleConfirmation={handleApprove}
        isPending={isApproving}
        pendingLabel="جاري القبول..."
        confirmLabel="تأكيد القبول"
        variant="default" // Non-destructive
        confirmButtonClassName="bg-green-600 hover:bg-green-600/80"
      />

      <ControlledConfirmationModal
        open={isRejectModalOpen}
        onOpenChange={setIsRejectModalOpen}
        alertTitle="تأكيد الرفض"
        alertMessage={
          <>
            هل أنت متأكد أنك تريد رفض طلب نقل{" "}
            <strong>{selectedTransfer?.quantity}</strong> من{" "}
            <strong>{selectedTransfer?.product_name}</strong>؟
          </>
        }
        handleConfirmation={handleReject}
        isPending={isRejecting}
        pendingLabel="جاري الرفض..."
        confirmLabel="تأكيد الرفض"
        variant="destructive"
      />

      
    </div>
  );
}

export default TransferOperations;
