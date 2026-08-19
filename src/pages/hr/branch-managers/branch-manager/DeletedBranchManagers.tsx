import { useState } from "react";
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
import { RotateCcw, Trash2 } from "lucide-react"; // Icons for Restore and Delete

// Import Types and Hooks
import { TBranchManager } from "@/api/hr/branch-managers/branches/branch-manager.types";
import {
  useGetAllDeletedBranchManagersQueryOptions,
  useRestoreBranchManagerMutationOptions,
  useDeleteBranchManagerPermanentlyMutationOptions,
} from "@/api/hr/branch-managers/branches/branch-manager.hooks";
import { useQuery, useMutation } from "@tanstack/react-query";
import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import ManagersTableSkeleton from "./ManagersTableSkeleton";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import {
  FORCE_BRANCHDELETED,
  RESTORE_BRANCHDELETED,
} from "@/lib/permissions.helper";
import { useSearchParams } from "react-router";
import CustomPagination from "@/components/custom/CustomPagination";
import { formatPhone } from "@/utils/formatPhone";

function DeletedBranchManagers() {
  const [searchParams] = useSearchParams();
  const page = Number(searchParams.get("page"));
  // Modal State
  const [isRestoreModalOpen, setIsRestoreModalOpen] = useState(false);
  const [isDeletePermModalOpen, setIsDeletePermModalOpen] = useState(false);
  const [selectedManager, setSelectedManager] = useState<TBranchManager | null>(
    null
  );

  const { data: permissions } = useMyPermissions();
  const hasRestorePermissions =
    permissions && permissions.includes(RESTORE_BRANCHDELETED);
  const hasForceDeletePermission =
    permissions && permissions.includes(FORCE_BRANCHDELETED);

  // Data Fetching
  const { data, isPending } = useQuery(useGetAllDeletedBranchManagersQueryOptions(page));

  // Mutations
  const { mutate: restoreManager, isPending: isRestoring } =
    useMutation(useRestoreBranchManagerMutationOptions());
  const { mutate: deletePermManager, isPending: isDeletingPerm } =
    useMutation(useDeleteBranchManagerPermanentlyMutationOptions());

  // --- Modal Action Handlers ---
  const handleOpenRestore = (manager: TBranchManager) => {
    setSelectedManager(manager);
    setIsRestoreModalOpen(true);
  };
  const handleOpenDeletePerm = (manager: TBranchManager) => {
    setSelectedManager(manager);
    setIsDeletePermModalOpen(true);
  };

  // --- Confirmation Handlers ---
  const handleRestore = (onCloseModal: () => void) => {
    if (selectedManager) {
      restoreManager(selectedManager.uuid, {
        onSuccess: () => onCloseModal(),
        // onError handled by hook
      });
    }
  };

  const handleDeletePerm = (onCloseModal: () => void) => {
    if (selectedManager) {
      deletePermManager(selectedManager.uuid, {
        onSuccess: () => onCloseModal(),
        // onError handled by hook
      });
    }
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader>
          <CardTitle>مدراء الفروع المحذوفين</CardTitle>
          <CardDescription>
            عرض واستعادة أو حذف مدراء الفروع المحذوفين نهائياً.
          </CardDescription>
        </CardHeader>

        <CardContent>
          <div className="table-responsive-wrapper">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="text-center">الاسم</TableHead>
                  <TableHead className="text-center">
                    البريد الإلكتروني
                  </TableHead>
                  <TableHead className="text-center">رقم الهاتف</TableHead>
                  <TableHead className="text-center">الموقع</TableHead>
                  <TableHead className="text-center">الحالة</TableHead>
                  <TableHead className="text-center">إجراءات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isPending ? (
                  <ManagersTableSkeleton rows={5} />
                ) : data && data.data.length > 0 ? (
                  data.data.map((manager) => (
                    <TableRow key={manager.uuid}>
                      <TableCell className="font-medium text-center">
                        {manager.name}
                      </TableCell>
                      <TableCell className="text-center">
                        {manager.email}
                      </TableCell>
                      <TableCell className="text-center" dir="ltr">
                        {formatPhone(manager.phone, "-")}
                      </TableCell>
                      <TableCell className="text-center">
                        {manager.location}
                      </TableCell>
                      <TableCell className="text-center">
                        {/* Assuming deleted items don't show active/blocked */}
                        <Badge variant="secondary">محذوف</Badge>
                      </TableCell>
                      <TableCell className="text-center">
                        <div className="flex items-center gap-2 justify-center">
                          {/* Restore Button */}
                          <Button
                            variant="default" // Default (blue/primary) button
                            size="icon"
                            title="استعادة"
                            onClick={() => handleOpenRestore(manager)}
                            disabled={!hasRestorePermissions}
                          >
                            <RotateCcw className="h-4 w-4" />
                          </Button>

                          {/* Delete Permanently Button */}
                          <Button
                            variant="destructive"
                            size="icon"
                            title="حذف نهائي"
                            onClick={() => handleOpenDeletePerm(manager)}
                            disabled={!hasForceDeletePermission}
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
                      colSpan={6} // Ensure this matches the number of columns
                      className="py-10 text-center text-muted-foreground"
                    >
                      لا يوجد مدراء فروع محذوفين لعرضهم.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>

        <CardFooter>
          <CustomPagination totalElementsLabel="إجمالي المدراء المحذوفين" totalElements={data?.total} totalPages={data?.total_pages} isLoading={isPending} />
        </CardFooter>
      </Card>

      {/* --- Modals --- */}
      {/* Restore Modal */}
      <ControlledConfirmationModal
        open={isRestoreModalOpen}
        onOpenChange={setIsRestoreModalOpen}
        alertTitle="استعادة مدير الفرع"
        alertMessage={
          <>
            هل أنت متأكد أنك تريد استعادة المدير{" "}
            <strong>{selectedManager?.name}</strong>؟ سيتم إعادته إلى القائمة
            الرئيسية.
          </>
        }
        handleConfirmation={handleRestore}
        isPending={isRestoring}
        pendingLabel="جاري الاستعادة..."
        confirmLabel="تأكيد الاستعادة"
        variant="default" // Use default variant for non-destructive
      />

      {/* Delete Permanently Modal */}
      <ControlledConfirmationModal
        open={isDeletePermModalOpen}
        onOpenChange={setIsDeletePermModalOpen}
        alertTitle="حذف مدير الفرع نهائياً"
        alertMessage={
          <>
            سيتم حذف المدير <strong>{selectedManager?.name}</strong> نهائياً.
            <strong className="text-destructive">
              {" "}
              لا يمكن التراجع عن هذا الإجراء!
            </strong>
          </>
        }
        handleConfirmation={handleDeletePerm}
        isPending={isDeletingPerm}
        pendingLabel="جاري الحذف..."
        confirmLabel="تأكيد الحذف النهائي"
        variant="destructive" // Use destructive variant
      />

      
    </div>
  );
}

export default DeletedBranchManagers;
