import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Pencil, Plus, Trash2 } from "lucide-react"
import { InventoryTableSkeleton } from "../InventoryTableSkeleton"
import { format, parse } from "date-fns"
import { arEG } from "date-fns/locale"
import CustomPagination from "@/components/custom/CustomPagination"
import { useDeleteEmployeesInventoryMutationOptions, useGetEmployeesInventoryQueryOptions } from "@/api/inventory/employees/inventory.hooks"
import { useMutation, useQuery } from "@tanstack/react-query"
import { useSearchParams } from "react-router"
import { useMyPermissions } from "@/api/auth/auth.hooks"
import { CREATE_INVENTORY, DELETE_INVENTORY, UPDATE_INVENTORY } from "@/lib/permissions.helper"
import { useState } from "react"
import { TEmployeeInvantoryItem } from "@/api/inventory/inventory.types"
import { Badge } from "@/components/ui/badge"
import { ConfirmationModal } from "@/components/custom/ConfirmationModal"
import { CreateEmployeesInventoryModal } from "./CreateEmployeesInventoryModal"
import { EditEmployeesInventoryModal } from "./EditEmployeesInventoryModal"

const getStatusVariant = (status: TEmployeeInvantoryItem["status"]) => {
    const normalizedStatus = status.trim();
    switch (normalizedStatus) {
        case "مرتفع":
            return "bg-green-600 text-white hover:bg-green-600/80";
        case "كافية":
            return "bg-yellow-500 text-white hover:bg-yellow-500/80";
        case "منخفضة":
            return "bg-destructive text-white hover:bg-destructive/90";
        default:
            return "bg-yellow-500 text-white hover:bg-yellow-500/80";
    }
};

const headerText = [
    "اسم الصنف",
    "قسم المنتجات",
    "قسم المنتجات الفرعي",
    "الكمية",
    "الحالة",
    "آخر تحديث",
    "إجراءات"
]

const EmployeesInventory = () => {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [selectedItem, setSelectedItem] = useState<TEmployeeInvantoryItem | null>(null);
    const [searchParams] = useSearchParams();
    const page = Number(searchParams.get("page"))
    const { data, isLoading } = useQuery(useGetEmployeesInventoryQueryOptions(page));
    const { data: permissions } = useMyPermissions();
    const { mutate: deleteInventory, isPending: isDeleting } = useMutation(useDeleteEmployeesInventoryMutationOptions());
    const hasCreatePermission = permissions && permissions.includes(CREATE_INVENTORY);
    const hasEditPermission = permissions && permissions.includes(UPDATE_INVENTORY);
    const hasDeletePermission = permissions && permissions.includes(DELETE_INVENTORY);

    const handleOpenEdit = (item: TEmployeeInvantoryItem) => {
        setIsEditModalOpen(true);
        setSelectedItem(item);
    }

    const handleDelete = (onCloseModal: () => void, id: number) => {
        deleteInventory(id,
            {
                onSuccess: () => onCloseModal?.(),
            }
        );
    }

    return (
        <div dir="rtl" className="w-full">
            <Card className="w-full">
                <CardHeader className="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>مخزون الموظفين</CardTitle>
                        <CardDescription>
                            عرض وإدارة جميع الأصناف الموجودة في مخازن الموظفين.
                        </CardDescription>
                    </div>
                    <Button className="bg-main-gold hover:bg-main-gold/80" onClick={() => setIsCreateModalOpen(true)} disabled={!hasCreatePermission}>
                        <Plus className="ml-2 h-4 w-4" />
                        إضافة صنف جديد
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
                                    <InventoryTableSkeleton rows={6} />
                                ) : data && data.data.length > 0 ? (
                                    data.data.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-medium text-center">{item.name}</TableCell>
                                            <TableCell className="text-center">{item.category_name}</TableCell>
                                            <TableCell className="text-center">{item.sub_category_name}</TableCell>
                                            <TableCell className="text-center">{item.quantity}</TableCell>
                                            <TableCell className="text-center">
                                                <Badge className={getStatusVariant(item.status)}>
                                                    {item.status.trim()}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {format(
                                                    parse(item.updated_at, "dd-MM-yyyy", new Date()),
                                                    "P",
                                                    { locale: arEG }
                                                )}
                                            </TableCell>
                                            {/* Actions Cell */}
                                            <TableCell>
                                                <div className="flex items-center gap-2 justify-center">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="تعديل"
                                                        onClick={() => handleOpenEdit(item)}
                                                        disabled={!hasEditPermission}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <ConfirmationModal
                                                        alertTitle="هل أنت متأكد من حذف هذا الصنف"
                                                        alertMessage={<>سيتم حذف الصنف {<strong>{item?.name}</strong>}.</>}
                                                        confirmLabel="تأكيد الحذف"
                                                        pendingLabel="جاري الحذف..."
                                                        handleConfirmation={(onCloseModal) => handleDelete(onCloseModal, item.id)}
                                                        isPending={isDeleting}
                                                    >
                                                        <Button
                                                            variant="destructive"
                                                            size="icon"
                                                            title="حذف"
                                                            disabled={!hasDeletePermission}
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
                                            colSpan={8} // Updated colSpan to 8
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            لا توجد أصناف لعرضها.
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
                        totalElementsLabel="اجمالى المخزن"
                        totalElements={data?.total}
                        totalPages={data?.total_pages}
                    />
                </CardFooter>
            </Card>

            {/* --- Modals --- */}
            <CreateEmployeesInventoryModal
                open={isCreateModalOpen}
                onOpenChange={setIsCreateModalOpen}
            />
            <EditEmployeesInventoryModal
                item={selectedItem}
                open={isEditModalOpen}
                onOpenChange={setIsEditModalOpen}
            />
            {/*
            <ControlledConfirmationModal
                open={isDeleteModalOpen}
                onOpenChange={setIsDeleteModalOpen}
                alertTitle="حذف الصنف"
                alertMessage={
                    <>
                        هل أنت متأكد أنك تريد حذف الصنف{" "}
                        <strong>{selectedItem?.name}</strong>؟
                    </>
                }
                handleConfirmation={handleDelete}
                isPending={isDeleting}
                pendingLabel="جاري الحذف..."
                confirmLabel="تأكيد الحذف"
                variant="destructive"
            /> */}

        </div>
    )
}

export default EmployeesInventory