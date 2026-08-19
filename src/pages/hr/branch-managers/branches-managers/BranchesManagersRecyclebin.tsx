import { useForceDeleteBranchesManagerMutationOptions, useGetDeletedBranchesManagersQueryOptions, useRestoreBranchesManagerMutationOptions } from "@/api/hr/branch-managers/branches-managers/branches-managers.hooks"
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
import { RotateCcw } from "lucide-react";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import TableSkeleton from "@/components/custom/TableSkeleton";
import CustomPagination from "@/components/custom/CustomPagination";
import { ConfirmationModal } from "@/components/custom/ConfirmationModal";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Trash2 } from "lucide-react";
import { toast } from "sonner";
import { formatPhone } from "@/utils/formatPhone";

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

export default function BranchesManagersRecyclebin() {
    const [searchParams] = useSearchParams();
    const page = Number(searchParams.get("page"));
    const { data, isLoading } = useQuery(useGetDeletedBranchesManagersQueryOptions(page));
    const { data: permissions } = useMyPermissions();
    const { mutate: deleteBranchesManager, isPending: isDeleting } = useMutation(useForceDeleteBranchesManagerMutationOptions());
    const { mutate: restoreManager, isPending: isRestoring } = useMutation(useRestoreBranchesManagerMutationOptions());

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

    const handleRestore = (onCloseModal: () => void, id: string) => {
        restoreManager(id, {
            onSuccess: () => {
                toast.success("تم استعادة المدير بنجاح");
                onCloseModal?.();
            },
            onError: () => {
                toast.error("حدث خطأ أثناء استعادة المدير");
            },
        });
    };

    return (
        <div dir="rtl" className="w-full">
            <Card className="w-full">
                <CardHeader>
                    <div>
                        <CardTitle>إدارة مديرين الفروع المحذوفين</CardTitle>
                        <CardDescription>
                            حذف واستعادة حسابات مديرين الفروع في النظام.
                        </CardDescription>
                    </div>
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
                                        <TableRow key={manager.uuid} className="border-b border-[#e1e2e9]">
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
                                                <Badge variant="secondary">محذوف</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <ConfirmationModal
                                                        alertTitle="هل أنت متأكد من استعادة هذا المشرف؟"
                                                        alertMessage={<>سيتم استعادة المدير {<strong>{manager?.first_name}</strong>} للنظام.</>}
                                                        confirmLabel="تأكيد الاستعادة"
                                                        pendingLabel="جاري الاستعادة..."
                                                        handleConfirmation={(onCloseModal) => handleRestore(onCloseModal, manager.uuid)}
                                                        isPending={isRestoring}
                                                        confirmButtonClassName="bg-green-700 hover:bg-green-700/90"
                                                    >
                                                        <Button
                                                            variant="default"
                                                            size="icon"
                                                            title="استعادة"
                                                            disabled={!permissions?.includes("Restore-BranchManagerDeleted")}
                                                        >
                                                            <RotateCcw className="h-4 w-4" />                                                        </Button>
                                                    </ConfirmationModal>

                                                    <ConfirmationModal
                                                        alertTitle="هل أنت متأكد من حذف هذا المدير"
                                                        alertMessage={<>سيتم حذف المدير {<strong>{manager?.first_name}</strong>} نهائيًا. هذا الإجراء لا يمكن التراجع عنه.</>}
                                                        confirmLabel="تأكيد الحذف"
                                                        pendingLabel="جاري الحذف..."
                                                        handleConfirmation={(onCloseModal) => handleDelete(onCloseModal, manager.uuid)}
                                                        isPending={isDeleting}
                                                    >
                                                        <Button
                                                            variant="destructive"
                                                            size="icon"
                                                            title="حذف نهائي"
                                                            disabled={!permissions?.includes("Force-BranchManagerDeleted")}
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
        </div>)
}
