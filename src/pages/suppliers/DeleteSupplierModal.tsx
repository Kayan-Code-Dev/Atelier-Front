import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import { TSupplierResponse } from "@/api/v2/suppliers/suppliers.types";

type Props = {
  supplier: TSupplierResponse | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onConfirm: () => void;
  isDeleting: boolean;
};

export function DeleteSupplierModal({
  supplier,
  open,
  onOpenChange,
  onConfirm,
  isDeleting,
}: Props) {
  return (
    <ControlledConfirmationModal
      open={open}
      onOpenChange={onOpenChange}
      alertTitle="حذف المورد"
      alertMessage={
        <>
          هل أنت متأكد أنك تريد حذف المورد{" "}
          <strong>{supplier?.name}</strong>؟
        </>
      }
      handleConfirmation={onConfirm}
      isPending={isDeleting}
      pendingLabel="جاري الحذف..."
      confirmLabel="تأكيد الحذف"
      variant="destructive"
    />
  );
}





