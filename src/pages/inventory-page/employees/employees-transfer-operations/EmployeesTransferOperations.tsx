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
import { TInventoryTransfersItem } from "@/api/inventory/inventory.types"; // Adjust path
import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import { TransfersTableSkeleton } from "../../branches-managers/transfer-operations/TransfersTableSkeleton";
import { toast } from "sonner";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import {
    APPROVE_INVENTORYTRANSFER,
    CREATE_INVENTORYTRANSFER,
    REJECT_INVENTORYTRANSFER,
} from "@/lib/permissions.helper";
import CreateEmployeeTransferModal from "./CreateEmployeeTransferModal";
import { useApproveEmployeeTransferOperationMutationOptions, useGetEmployeeTransferOperationsQueryOptions, useRejectEmployeeTransferOperationMutationOptions } from "@/api/inventory/employees/inventory.hooks";
import { useMutation, useQuery } from "@tanstack/react-query";
import CustomPagination from "@/components/custom/CustomPagination";
import { useSearchParams } from "react-router";

const getStatusVariant = (status: TInventoryTransfersItem["status"]) => {
    const normalizedStatus = status.trim();
    switch (normalizedStatus) {
        case "تم القبول":
        case "تم الوصول":
            return "bg-green-600 text-white hover:bg-green-600/80";
        case "تم الرفض":
            return "bg-destructive text-white hover:bg-destructive/90";
        case "قيد الانتظار":
        default:
            return "bg-yellow-500 text-white hover:bg-yellow-500/80";
    }
};

const headerText = [
    "الصنف",
    "الكمية",
    "من فرع",
    "الي فرع",
    "الحالة",
    "تاريخ الطلب",
    "إجراءات"
]

function EmployeesTransferOperations() {
    // Modal State
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isApproveModalOpen, setIsApproveModalOpen] = useState(false);
    const [isRejectModalOpen, setIsRejectModalOpen] = useState(false);
    const [selectedTransfer, setSelectedTransfer] =
        useState<TInventoryTransfersItem | null>(null);
    const [searchParams] = useSearchParams();
    const page = Number(searchParams.get("page"));

    // Data Fetching
    const { data, isLoading } = useQuery(useGetEmployeeTransferOperationsQueryOptions(page));
    const { data: permissions } = useMyPermissions();

    // Mutations
    const { mutate: approveTransfer, isPending: isApproving } =
        useMutation(useApproveEmployeeTransferOperationMutationOptions());
    const { mutate: rejectTransfer, isPending: isRejecting } =
        useMutation(useRejectEmployeeTransferOperationMutationOptions());


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
                    <Button onClick={() => setIsCreateModalOpen(true)} disabled={!hasCreatePermission} className="bg-main-gold hover:bg-main-gold/80">
                        <Plus className="ml-2 h-4 w-4" />
                        إنشاء طلب نقل
                    </Button>
                </CardHeader>

                <CardContent>
                    <div className="table-responsive-wrapper">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {headerText.map((text) => <TableHead key={text} className="text-center">{text}</TableHead>)}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    <TransfersTableSkeleton rows={7} />
                                ) : data && data.data.length > 0 ? (
                                    data.data.map((item) => {
                                        const isLoading = item.status.trim() === "قيد الانتظار";
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
                                                    {isLoading ? (
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
                    <CustomPagination
                        isLoading={isLoading}
                        totalElementsLabel="اجمالى الطلبات"
                        totalElements={data?.total}
                        totalPages={data?.total_pages}
                    />
                </CardFooter>
            </Card>

            {/* --- Modals --- */}
            <CreateEmployeeTransferModal
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

export default EmployeesTransferOperations;
