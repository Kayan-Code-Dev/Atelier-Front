import { useState } from "react";
import { format, parse } from "date-fns";
import { arEG } from "date-fns/locale";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationPrevious,
  PaginationNext,
} from "@/components/ui/pagination";
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
import { CreateBranchTransferModal } from "./CreateBranchTransferModal"; // Adjust path
import { TInventoryTransfersItem } from "@/api/inventory/inventory.types"; // Adjust path
import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import {
  useApproveBranchTransferOperationMutationOptions,
  useGetBranchTransferOperationsQueryOptions,
  useRejectBranchTransferOperationMutationOptions,
} from "@/api/inventory/branches/inventory.hooks";
import { useMutation, useQuery } from "@tanstack/react-query";
import { TransfersTableSkeleton } from "../../branches-managers/transfer-operations/TransfersTableSkeleton";
import { toast } from "sonner";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import {
  APPROVE_INVENTORYTRANSFER,
  CREATE_INVENTORYTRANSFER,
  REJECT_INVENTORYTRANSFER,
} from "@/lib/permissions.helper";

const getStatusVariant = (status: TInventoryTransfersItem["status"]) => {
  const normalizedStatus = status.trim();
  switch (normalizedStatus) {
    case "تم القبول":
    case "تم الوصول":
      return "bg-green-600 text-white hover:bg-green-600/80";
    case "تم الرفض":
      return "bg-destructive text-destructive-foreground hover:bg-destructive/90";
    case "قيد الانتظار":
    default:
      return "bg-yellow-500 text-white hover:bg-yellow-500/80";
  }
};

function BranchesTransferOperations() {
  const [page, setPage] = useState(1);

  // Modal State
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isApproveModalOpen, setIsApproveModalOpen] = useState(false);
  const [isRejectModalOpen, setIsRejectModalOpen] = useState(false);
  const [selectedTransfer, setSelectedTransfer] =
    useState<TInventoryTransfersItem | null>(null);

  // Data Fetching
  const { data, isPending } = useQuery(useGetBranchTransferOperationsQueryOptions(page));

  // Mutations
  const { mutate: approveTransfer, isPending: isApproving } =
    useMutation(useApproveBranchTransferOperationMutationOptions());
  const { mutate: rejectTransfer, isPending: isRejecting } =
    useMutation(useRejectBranchTransferOperationMutationOptions());

  // --- Pagination Handlers ---
  const handlePreviousPage = () => {
    setPage((prev) => Math.max(1, prev - 1));
  };
  const handleNextPage = () => {
    setPage((prev) => Math.min(prev + 1, data?.total_pages || 1));
  };

  const { data: permissions } = useMyPermissions();

  const hasCreatePermission =
    permissions && permissions.includes(CREATE_INVENTORYTRANSFER);
  const hasApprovePermission =
    permissions && permissions.includes(APPROVE_INVENTORYTRANSFER);
  const hasRejectPermission =
    permissions && permissions.includes(REJECT_INVENTORYTRANSFER);

  // --- Modal Action Handlers ---
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
        onSuccess: () => onCloseModal(),
        onError: (error) => {
          toast.error("حدث خطأ أثناء القبول", {
            description: error.message,
          });
        },
      });
    }
  };

  const handleReject = (onCloseModal: () => void) => {
    if (selectedTransfer) {
      rejectTransfer(selectedTransfer.uuid, {
        onSuccess: () => onCloseModal(),
        onError: (error) => {
          toast.error("حدث خطأ أثناء الرفض", {
            description: error.message,
          });
        },
      });
    }
  };

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
          <Button onClick={() => setIsCreateModalOpen(true)} disabled={!hasCreatePermission} className="bg-main-gold">
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
                  <TableHead className="text-center">إجراءات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isPending ? (
                  <TransfersTableSkeleton rows={7} />
                ) : data && data.data.length > 0 ? (
                  data.data.map((item) => {
                    const isPending = item.status.trim() === "قيد الانتظار";
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
                          {isPending ? (
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
                      colSpan={7}
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
          <div className="text-sm text-muted-foreground">
            إجمالي الطلبات: {data?.total || 0}
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
                صفحة {page} من {data?.total_pages || 1}
              </PaginationItem>
              <PaginationItem>
                <PaginationPrevious
                  href="#"
                  onClick={(e) => {
                    e.preventDefault();
                    handleNextPage();
                  }}
                  aria-disabled={page === data?.total_pages || isPending}
                  className={
                    page === data?.total_pages || isPending
                      ? "pointer-events-none opacity-50"
                      : ""
                  }
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </CardFooter>
      </Card>

      {/* --- Modals --- */}
      <CreateBranchTransferModal
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
        variant="default"
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

export default BranchesTransferOperations;
