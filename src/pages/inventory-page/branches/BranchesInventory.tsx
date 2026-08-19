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
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Plus, Pencil, Trash2 } from "lucide-react";
import { TBranchInventoryItem } from "@/api/inventory/inventory.types"; // Adjust path
import { CreateBranchesInventoryModal } from "./CreateBranchesInventoryModal"; // Adjust path
import { EditBranchesInventoryModal } from "./EditBranchesInventoryModal"; // Adjust path
import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import {
  useDeleteBranchesInventoryMutationOptions,
  useGetBranchesInventoryQueryOptions,
} from "@/api/inventory/branches/inventory.hooks";
import { useMutation, useQuery } from "@tanstack/react-query";
import { InventoryTableSkeleton } from "../InventoryTableSkeleton";
import { useSearchParams } from "react-router";
import CustomPagination from "@/components/custom/CustomPagination";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import { CREATE_INVENTORY, DELETE_INVENTORY, UPDATE_INVENTORY } from "@/lib/permissions.helper";

/**
 * Helper function to determine badge color based on status
 */
const getStatusVariant = (status: TBranchInventoryItem["status"]) => {
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

function BranchesInventory() {
  const [searchParams] = useSearchParams();
  const page = Number(searchParams.get("page"));
  // --- Modal State ---
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [selectedItem, setSelectedItem] = useState<TBranchInventoryItem | null>(null);

  const {data : permissions} = useMyPermissions()

  const hasCreatePermission = permissions && permissions.includes(CREATE_INVENTORY)
  const hasEditPermission = permissions && permissions.includes(UPDATE_INVENTORY)
  const hasDeletePermission = permissions && permissions.includes(DELETE_INVENTORY)

  // --- Data Fetching ---
  const { data, isPending } = useQuery(useGetBranchesInventoryQueryOptions(page));
  const { mutate: deleteItem, isPending: isDeleting } =
    useMutation(useDeleteBranchesInventoryMutationOptions());

  // --- Modal Action Handlers ---
  const handleOpenEdit = (item: TBranchInventoryItem) => {
    setSelectedItem(item);
    setIsEditModalOpen(true);
  };
  const handleOpenDelete = (item: TBranchInventoryItem) => {
    setSelectedItem(item);
    setIsDeleteModalOpen(true);
  };

  // --- Confirmation Handler ---
  const handleDelete = (onCloseModal: () => void) => {
    if (selectedItem) {
      deleteItem(selectedItem.id, {
        onSuccess: () => onCloseModal(),
      });
    }
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>مخزون الفروع</CardTitle>
            <CardDescription>
              عرض وإدارة جميع الأصناف الموجودة في مخازن الفروع.
            </CardDescription>
          </div>
          <Button onClick={() => setIsCreateModalOpen(true)} disabled={!hasCreatePermission}>
            <Plus className="ml-2 h-4 w-4" />
            إضافة صنف جديد
          </Button>
        </CardHeader>

        <CardContent>
          <div className="table-responsive-wrapper">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="text-center">اسم الصنف</TableHead>
                  <TableHead className="text-center">قسم المنتجات</TableHead>
                  <TableHead className="text-center">قسم المنتجات الفرعي</TableHead>
                  <TableHead className="text-center">الكمية</TableHead>
                  <TableHead className="text-center">الحالة</TableHead>
                  <TableHead className="text-center">آخر تحديث</TableHead>
                  <TableHead className="text-center">إجراءات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isPending ? (
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
                          <Button
                            variant="destructive"
                            size="icon"
                            title="حذف"
                            onClick={() => handleOpenDelete(item)}
                            disabled={!hasDeletePermission}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
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
            isLoading={isPending}
            totalElementsLabel="اجمالى المخزن"
            totalElements={data?.total}
            totalPages={data?.total_pages}
          />
        </CardFooter>
      </Card>

      {/* --- Modals --- */}
      <CreateBranchesInventoryModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
      <EditBranchesInventoryModal
        item={selectedItem}
        open={isEditModalOpen}
        onOpenChange={setIsEditModalOpen}
      />
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
      />

      
    </div>
  );
}

export default BranchesInventory;
