import { useMutation } from "@tanstack/react-query";
import { toast } from "sonner";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import {
  useCancelExpenseMutationOptions,
} from "@/api/v2/expenses/expenses.hooks";
import { TExpense } from "@/api/v2/expenses/expenses.types";

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  expense: TExpense | null;
};

export function CancelExpenseModal({
  open,
  onOpenChange,
  expense,
}: Props) {
  const { mutate: cancelExpense, isPending } = useMutation(
    useCancelExpenseMutationOptions()
  );

  const handleConfirm = () => {
    if (!expense) return;
    cancelExpense(expense.id, {
      onSuccess: () => {
        toast.success("تم إلغاء المصروف بنجاح");
        onOpenChange(false);
      },
      onError: (error: any) => {
        toast.error("حدث خطأ أثناء إلغاء المصروف", {
          description: error.message,
        });
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="text-center">إلغاء المصروف</DialogTitle>
          <DialogDescription className="text-center">
            هل تريد إلغاء هذا المصروف؟
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-2 text-right text-sm">
          <p>
            <span className="font-medium">المورد:</span> {expense?.vendor}
          </p>
          <p>
            <span className="font-medium">المبلغ:</span> {expense?.amount} ج.م
          </p>
        </div>
        <DialogFooter className="gap-2 sm:justify-between">
          <Button
            type="button"
            variant="outline"
            onClick={() => onOpenChange(false)}
          >
            إلغاء
          </Button>
          <Button
            type="button"
            variant="destructive"
            onClick={handleConfirm}
            disabled={isPending || !expense}
          >
            {isPending ? "جاري الإلغاء..." : "إلغاء"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

