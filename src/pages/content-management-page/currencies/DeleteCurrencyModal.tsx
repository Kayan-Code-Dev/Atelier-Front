import { useMutation } from "@tanstack/react-query";
import { TCurrency } from "@/api/v2/content-managment/currency/currency.types";
import { useDeleteCurrencyMutationOptions } from "@/api/v2/content-managment/currency/currency.hooks";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { toast } from "sonner";

type Props = {
  currency: TCurrency | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function DeleteCurrencyModal({ currency, open, onOpenChange }: Props) {
  const { mutate: deleteCurrency, isPending } = useMutation(
    useDeleteCurrencyMutationOptions()
  );

  const handleDelete = () => {
    if (currency) {
      deleteCurrency(currency.id, {
        onSuccess: () => {
          toast.success("تم الحذف", {
            description: `تم حذف العملة "${currency.name}" بنجاح.`,
          });
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء الحذف. حاول مرة أخرى.", {
            description: (error as any).message,
          });
        },
      });
    }
  };

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle className="text-center">
            هل أنت متأكد من حذف هذه العملة؟
          </AlertDialogTitle>
          <AlertDialogDescription className="text-center">
            سيتم حذف العملة "<strong>{currency?.name}</strong>" نهائيًا. هذا
            الإجراء لا يمكن التراجع عنه.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>إلغاء</AlertDialogCancel>
          <AlertDialogAction
            onClick={handleDelete}
            disabled={isPending}
            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            {isPending ? "جاري الحذف..." : "تأكيد الحذف"}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}

