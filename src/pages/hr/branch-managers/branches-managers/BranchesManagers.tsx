import { useBlockBranchesManagerMutationOptions, useGetBranchesManagersQueryOptions } from "@/api/hr/branch-managers/branches-managers/branches-managers.hooks"
import { useQuery, useMutation } from "@tanstack/react-query"
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
import { useSearchParams } from "react-router";
import { useState } from "react";
import { Ban, CheckCircle, Plus } from "lucide-react";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import TableSkeleton from "@/components/custom/TableSkeleton";
import CustomPagination from "@/components/custom/CustomPagination";
import { useDeleteBranchesManagerMutationOptions } from "@/api/hr/branch-managers/branches-managers/branches-managers.hooks";
import { TBranchesManager } from "@/api/hr/branch-managers/branches-managers/branches-managers.types"
import { ConfirmationModal } from "@/components/custom/ConfirmationModal";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Pencil, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { formatPhone } from "@/utils/formatPhone";
import EditBranchesManagerModal from "./EditBranchesManagerModal";
import CreateBranchesManagerModal from "./CreateBranchesManagerModal";

const headerText = [
    "المدير",
    "رقم الهاتف",
    "رقم الهوية",
    "الموقع",
    "اسم الفرع",
    "رقم الفرع",
    "الحالة",
    "إجراءات",
]

export default function BranchesManagers() {
    const [selectedManager, setSelectedManager] = useState<TBranchesManager | null>(null);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [searchParams] = useSearchParams();
    const page = Number(searchParams.get("page"));
    const { data, isLoading } = useQuery(useGetBranchesManagersQueryOptions(page));
    const { data: permissions } = useMyPermissions();
    const { mutate: deleteBranchesManager, isPending: isDeleting } = useMutation(useDeleteBranchesManagerMutationOptions());
    const { mutate: blockManager, isPending: isBlocking } = useMutation(useBlockBranchesManagerMutationOptions());

    const handleBlock = (onClose: () => void, manager: TBranchesManager) => {
        if (!manager) return;
        blockManager(manager.uuid, {
            onSuccess: () => {
                toast.success(
                    `تم ${manager.blocked ? "إلغاء" : "حظر"} المدير ${manager.first_name
                    } بنجاح.`
                );
                onClose();
            },
            onError: () => {
                toast.error("حدث خطأ ما أثناء حظر المدير. الرجاء المحاولة مرة أخرى.");
            },
        });
    };

    const handleDelete = (onCloseModal: () => void, id: string) => {
        deleteBranchesManager(id, {
            onSuccess: () => {
                toast.success("تم حذف مدير الفرع بنجاح");
                onCloseModal?.();
            },
            onError: () => {
                toast.error("حدث خطأ أثناء حذف مدير الفرع");
            },
        });
    };

    const handleOpenEdit = (manager: TBranchesManager) => {
        setIsEditModalOpen(true);
        setSelectedManager(manager);
    };


    return (
        <div dir="rtl" className="w-full">
            <Card className="w-full">
                <CardHeader className="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>إدارة مديرين الفروع</CardTitle>
                        <CardDescription>
                            عرض وتعديل وإنشاء حسابات مديرين الفروع في النظام.
                        </CardDescription>
                    </div>
                    <Button onClick={() => setIsCreateModalOpen(true)} disabled={!permissions?.includes("Create-BranchManager")} className="bg-main-gold hover:bg-main-gold/90">
                        <Plus className="ml-2 h-4 w-4" />
                        إنشاء مدير فروع جديد
                    </Button>
                </CardHeader>

                <CardContent>
                    <div className="table-responsive-wrapper">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {headerText.map((text) => <TableHead key={text} className="text-start">{text}</TableHead>)}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    <TableSkeleton rows={5} cols={6} />
                                ) : data && data.data.length > 0 ? (
                                    data.data.map((manager) => (
                                        <TableRow key={manager.uuid}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <Avatar>
                                                        <AvatarImage src={manager.image_url} alt={manager.first_name} />
                                                        <AvatarFallback>
                                                            {manager.first_name.substring(0, 2)}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="font-medium">
                                                        <div>{manager.first_name} {manager.last_name}</div>
                                                        <div className="text-sm text-muted-foreground">
                                                            {manager.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="[direction:ltr] text-right">{formatPhone(manager.phone, "-")}</TableCell>
                                            <TableCell className="text-start">{manager.id_number}</TableCell>
                                            <TableCell className="text-start">
                                                {manager.location}
                                            </TableCell>
                                            <TableCell className="text-start">{manager.branch_name}</TableCell>
                                            <TableCell className="text-start">{manager.branch_number}</TableCell>
                                            <TableCell className="text-start">
                                                <Badge
                                                    variant={manager.blocked ? "destructive" : "default"}
                                                    className={
                                                        manager.status === "active" && !manager.blocked
                                                            ? "bg-green-600 text-white"
                                                            : ""
                                                    }
                                                >
                                                    {manager.blocked
                                                        ? "محظور"
                                                        : manager.status === "active"
                                                            ? "نشط"
                                                            : "غير نشط"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="تعديل"
                                                        onClick={() => handleOpenEdit(manager)}
                                                        disabled={!permissions?.includes("Update-BranchManager")}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <ConfirmationModal
                                                        alertTitle={manager.blocked ? "إلغاء حظر المدير" : "حظر المدير"}
                                                        alertMessage={<>
                                                            هل أنت متأكد أنك تريد {manager.blocked ? "إلغاء حظر" : "حظر"} المدير{" "}
                                                            <strong>{manager?.first_name}</strong>؟
                                                        </>}
                                                        pendingLabel={manager.blocked ? "جاري الإلغاء..." : "جاري الحظر..."}
                                                        confirmLabel={manager.blocked ? "تأكيد الإلغاء" : "تأكيد الحظر"}
                                                        handleConfirmation={(onCloseModal) => handleBlock(onCloseModal, manager)}
                                                        isPending={isBlocking}
                                                    >
                                                        <Button
                                                            variant={
                                                                manager.blocked ? "default" : "destructive"
                                                            }
                                                            size="icon"
                                                            title={manager.blocked ? "إلغاء الحظر" : "حظر"}
                                                        >
                                                            {manager.blocked ? (
                                                                <CheckCircle className="h-4 w-4" />
                                                            ) : (
                                                                <Ban className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                    </ConfirmationModal>
                                                    <ConfirmationModal
                                                        alertTitle="هل أنت متأكد من حذف هذا المدير؟"
                                                        alertMessage={<>سيتم حذف المشرف {<strong>{manager?.first_name}</strong>}.</>}
                                                        confirmLabel="تأكيد الحذف"
                                                        pendingLabel="جاري الحذف..."
                                                        handleConfirmation={(onCloseModal) => handleDelete(onCloseModal, manager.uuid)}
                                                        isPending={isDeleting}
                                                    >
                                                        <Button
                                                            variant="destructive"
                                                            size="icon"
                                                            title="حذف"
                                                            disabled={!permissions?.includes("Delete-BranchManager")}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </ConfirmationModal>
                                                </div>
                                            </TableCell>
                                        </TableRow >))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            لا يوجد مديرين فروع لعرضهم.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>

                <CardFooter>
                    <CustomPagination totalElementsLabel="إجمالي المديرين" totalElements={data?.total} totalPages={data?.total_pages} isLoading={isLoading} />
                </CardFooter>
            </Card>

            {/* --- Modals --- */}
            <CreateBranchesManagerModal
                open={isCreateModalOpen}
                onOpenChange={setIsCreateModalOpen}
            />
            <EditBranchesManagerModal
                manager={selectedManager}
                open={isEditModalOpen}
                onOpenChange={setIsEditModalOpen}
            />
        </div>)
}
