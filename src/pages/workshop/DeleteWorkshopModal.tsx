import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import { TWorkshopResponse } from "@/api/v2/workshop/workshop.types";
import { useMutation } from "@tanstack/react-query";
import { useDeleteWorkshopMutationOptions } from "@/api/v2/workshop/workshops.hooks";
import { toast } from "sonner";

type Props = {
  workshop: TWorkshopResponse | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function DeleteWorkshopModal({
  workshop,
  open,
  onOpenChange,
}: Props) {
  const { mutate: deleteWorkshop, isPending } = useMutation(
    useDeleteWorkshopMutationOptions()
  );

  const handleDelete = (onClose: () => void) => {
    if (!workshop) return;
    deleteWorkshop(workshop.id, {
      onSuccess: () => {
        toast.success(`تم حذف الورشة ${workshop.name} بنجاح.`);
        onClose();
      },
      onError: () => {
        toast.error("حدث خطأ ما أثناء حذف الورشة. الرجاء المحاولة مرة أخرى.");
      },
    });
  };

  return (
    <ControlledConfirmationModal
      open={open}
      onOpenChange={onOpenChange}
      variantLayout="card"
      alertTitle="حذف الورشة"
      alertMessage={
        <>
          هل أنت متأكد أنك تريد حذف الورشة{" "}
          <strong>{workshop?.name}</strong>؟
          <span className="block mt-2 text-gray-400 font-normal">
            لا يمكن التراجع عن هذا الإجراء.
          </span>
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

