import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import { TBranchManager } from "@/api/hr/branch-managers/branches/branch-manager.types";
import { useDeleteBranchManagerMutationOptions } from "@/api/hr/branch-managers/branches/branch-manager.hooks";
import { useMutation } from "@tanstack/react-query";
import { toast } from "sonner";

type Props = {
  manager: TBranchManager | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function DeleteBranchManagerModal({
  manager,
  open,
  onOpenChange,
}: Props) {
  const { mutate: deleteManager, isPending } = useMutation(useDeleteBranchManagerMutationOptions());

  const handleDelete = (onClose: () => void) => {
    if (!manager) return;
    deleteManager(manager.uuid, {
      onSuccess: () => {
        toast.success(`تم حذف المدير ${manager.name} بنجاح.`);
        onClose();
      },
      onError: () => {
        toast.error("حدث خطأ ما أثناء مسح المدير. الرجاء المحاولة مرة أخرى.");
      },
    });
  };

  return (
    <ControlledConfirmationModal
      open={open}
      onOpenChange={onOpenChange}
      alertTitle="حذف المدير"
      alertMessage={
        <>
          سيتم نقل المدير <strong>{manager?.name}</strong> إلى سلة المحذوفات. هل
          أنت متأكد؟
        </>
      }
      handleConfirmation={handleDelete}
      isPending={isPending}
      pendingLabel="جاري الحذف..."
      confirmLabel="تأكيد الحذف"
      variant="destructive"
    />
  );
}
