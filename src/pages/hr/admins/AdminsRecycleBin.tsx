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
import { RotateCcw, Trash2 } from "lucide-react";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import { useForceDeleteAdminMutationOptions, useGetDeletedAdminsQueryOptions, useRestoreAdminMutationOptions } from "@/api/hr/admins/admins.hooks";
import { useMutation, useQuery } from "@tanstack/react-query";
import { ConfirmationModal } from "@/components/custom/ConfirmationModal";
import TableSkeleton from "@/components/custom/TableSkeleton";
import { toast } from "sonner";
import CustomPagination from "@/components/custom/CustomPagination";
import { useSearchParams } from "react-router";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import { formatPhone } from "@/utils/formatPhone";

const headerText = [
    "المشرف",
    "رقم الهاتف",
    "رقم الهوية",
    "الموقع",
    "الحالة",
    "إجراءات",
]

const AdminsRecycleBin = () => {
    const [searchParams] = useSearchParams();
    const page = Number(searchParams.get("page"));
    // Data Fetching
    const { data, isLoading } = useQuery(useGetDeletedAdminsQueryOptions(page));
    const { data: permissions } = useMyPermissions();
    const { mutate: forceDeleteAdmin, isPending: isDeleting } = useMutation(useForceDeleteAdminMutationOptions());
    const { mutate: restoreAdmin, isPending: isRestoring } = useMutation(useRestoreAdminMutationOptions());

    const handleDelete = (onCloseModal: () => void, id: string) => {
        forceDeleteAdmin(id, {
            onSuccess: () => {
                toast.success("تم حذف المشرف بنجاح");
                onCloseModal?.();
            },
            onError: () => {
                toast.error("حدث خطأ أثناء حذف المشرف");
            },
        });
    };
    const handleRestore = (onCloseModal: () => void, id: string) => {
        restoreAdmin(id, {
            onSuccess: () => {
                toast.success("تم استعادة المشرف بنجاح");
                onCloseModal?.();
            },
            onError: () => {
                toast.error("حدث خطأ أثناء استعادة المشرف");
            },
        });
    };

    return (
        <div dir="rtl" className="w-full">
            <Card className="w-full">
                <CardHeader className="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>إدارة المشرفين المحذوفين</CardTitle>
                        <CardDescription>
                            عرض وحذف واستعادة حسابات المشرفين في النظام.
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
                                    <TableSkeleton rows={5} cols={4} />
                                ) : data && data.data.length > 0 ? (
                                    data.data.map((admin) => (
                                        <TableRow key={admin.uuid} className="border-b border-[#e1e2e9]">
                                            <TableCell >
                                                <div className="flex items-center gap-3">
                                                    <Avatar>
                                                        <AvatarImage src={admin.image} alt={admin.name} />
                                                        <AvatarFallback>
                                                            {admin.name.substring(0, 2)}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="font-medium">
                                                        <div>{admin.name}</div>
                                                        <div className="text-sm text-muted-foreground">
                                                            {admin.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="[direction:ltr] text-right">{formatPhone(admin.phone, "-")}</TableCell>
                                            <TableCell className="text-start">{admin.id_number}</TableCell>
                                            <TableCell className="text-start">
                                                {admin.city}, {admin.country}
                                            </TableCell>
                                            <TableCell className="text-start">
                                                <Badge variant="secondary">محذوف</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2 justify-center">
                                                    <ConfirmationModal
                                                        alertTitle="هل أنت متأكد من استعادة هذا المشرف؟"
                                                        alertMessage={`سيتم استعادة المشرف ${<strong>{admin?.name}</strong>} للنظام.`}
                                                        confirmLabel="تأكيد الاستعادة"
                                                        pendingLabel="جاري الاستعادة..."
                                                        handleConfirmation={(onCloseModal) => handleRestore(onCloseModal, admin.uuid)}
                                                        isPending={isRestoring}
                                                        confirmButtonClassName="bg-green-700 hover:bg-green-700/90"
                                                    >
                                                        <Button
                                                            variant="default"
                                                            size="icon"
                                                            title="استعادة"
                                                            disabled={!permissions?.includes("Restore-AdminDeleted")}
                                                        >
                                                            <RotateCcw className="h-4 w-4" />
                                                        </Button>
                                                    </ConfirmationModal>

                                                    <ConfirmationModal
                                                        alertTitle="هل أنت متأكد من حذف هذا المشرف؟"
                                                        alertMessage={<>سيتم حذف المشرف {<strong>{admin?.name}</strong>} نهائيًا. هذا الإجراء لا يمكن التراجع عنه.</>}
                                                        confirmLabel="تأكيد الحذف"
                                                        pendingLabel="جاري الحذف..."
                                                        handleConfirmation={(onCloseModal) => handleDelete(onCloseModal, admin.uuid)}
                                                        isPending={isDeleting}
                                                    >
                                                        <Button
                                                            variant="destructive"
                                                            size="icon"
                                                            title="حذف نهائي"
                                                            disabled={!permissions?.includes("Force-AdminDeleted")}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </ConfirmationModal>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            لا يوجد مشرفين لعرضهم.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>

                <CardFooter>
                    <CustomPagination totalElementsLabel="إجمالي المشرفين" totalElements={data?.total} totalPages={data?.total_pages} isLoading={isLoading} />
                </CardFooter>
            </Card>
        </div>)
}

export default AdminsRecycleBin