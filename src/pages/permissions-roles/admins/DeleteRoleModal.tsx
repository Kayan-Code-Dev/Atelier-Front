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
import { useDeleteRoleMutationOptions } from "@/api/permissions-roles/admins/roles/roles.hooks";
import { useMutation } from "@tanstack/react-query";
import { TRoleItem } from "@/api/permissions-roles/admins/roles/roles.types";
import { toast } from "sonner";

type Props = {
  role: TRoleItem | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function DeleteRoleModal({ role, open, onOpenChange }: Props) {
  // Mock mutation hook (replace with your actual hook)
  const { mutate: deleteRole, isPending } = useMutation(useDeleteRoleMutationOptions());

  const handleDelete = () => {
    if (role) {
      deleteRole(role.id, {
        onSuccess: () => {
          toast.success("تم المسح بنجاح");
          onOpenChange(false);
        },
        onError: (error: any) => {
          toast.error("خطأ عند مسح الصلاحية", {
            description: error.message,
          });
        },
      });
    }
  };

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent className="text-center">
        <AlertDialogHeader className="text-center">
          <AlertDialogTitle className="text-center">هل أنت متأكد من حذف هذا الدور؟</AlertDialogTitle>
          <AlertDialogDescription className="text-center">
            سيتم حذف الدور "<strong>{role?.name}</strong>" نهائيًا. هذا الإجراء
            لا يمكن التراجع عنه.
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
