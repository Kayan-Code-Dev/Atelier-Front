import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import { TBranchManager } from "@/api/hr/branch-managers/branches/branch-manager.types";
import { useBlockBranchManagerMutationOptions } from "@/api/hr/branch-managers/branches/branch-manager.hooks";
import { useMutation } from "@tanstack/react-query";
import { toast } from "sonner";

type Props = {
  manager: TBranchManager | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function BlockBranchManagerModal({
  manager,
  open,
  onOpenChange,
}: Props) {
  const { mutate: blockManager, isPending } = useMutation(useBlockBranchManagerMutationOptions());

  const handleBlock = (onClose: () => void) => {
    if (!manager) return;
    blockManager(manager.uuid, {
      onSuccess: () => {
        toast.success(
          `تم ${manager.blocked ? "إلغاء" : "حظر"} المدير ${
            manager.name
          } بنجاح.`
        );
        onClose();
      },
      onError: () => {
        toast.error("حدث خطأ ما أثناء حظر المدير. الرجاء المحاولة مرة أخرى.");
      },
    });
  };

  const isBlocked = manager?.blocked;

  return (
    <ControlledConfirmationModal
      open={open}
      onOpenChange={onOpenChange}
      alertTitle={isBlocked ? "إلغاء حظر المدير" : "حظر المدير"}
      alertMessage={
        <>
          هل أنت متأكد أنك تريد {isBlocked ? "إلغاء حظر" : "حظر"} المدير{" "}
          <strong>{manager?.name}</strong>؟
        </>
      }
      handleConfirmation={handleBlock}
      isPending={isPending}
      pendingLabel={isBlocked ? "جاري الإلغاء..." : "جاري الحظر..."}
      confirmLabel={isBlocked ? "تأكيد الإلغاء" : "تأكيد الحظر"}
      variant={isBlocked ? "default" : "destructive"}
    />
  );
}
