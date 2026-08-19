import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import { TFactoryResponse } from "@/api/v2/factories/factories.types";
import { useMutation } from "@tanstack/react-query";
import { useDeleteFactoryMutationOptions } from "@/api/v2/factories/factories.hooks";
import { toast } from "sonner";

type Props = {
  factory: TFactoryResponse | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function DeleteFactoryModal({
  factory,
  open,
  onOpenChange,
}: Props) {
  const { mutate: deleteFactory, isPending } = useMutation(
    useDeleteFactoryMutationOptions()
  );

  const handleDelete = (onClose: () => void) => {
    if (!factory) return;
    deleteFactory(factory.id, {
      onSuccess: () => {
        toast.success(`تم حذف المصنع ${factory.name} بنجاح.`);
        onClose();
      },
      onError: () => {
        toast.error("حدث خطأ ما أثناء حذف المصنع. الرجاء المحاولة مرة أخرى.");
      },
    });
  };

  return (
    <ControlledConfirmationModal
      open={open}
      onOpenChange={onOpenChange}
      variantLayout="card"
      contentClassName="sm:max-w-lg"
      alertTitle="حذف المصنع"
      alertMessage={
        <>
          هل أنت متأكد أنك تريد حذف المصنع{" "}
          <strong className="text-gray-900">{factory?.name}</strong>
          {factory?.factory_code ? (
            <>
              {" "}
              <span
                className="inline-block font-mono text-xs font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-md align-middle"
                dir="ltr"
              >
                {factory.factory_code}
              </span>
            </>
          ) : null}
          ؟
          <span className="block mt-3 text-xs text-gray-400 font-normal leading-relaxed">
            لا يمكن التراجع عن هذا الإجراء؛ سيتم إزالة المصنع من القائمة.
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

