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
import { cn } from "@/lib/utils";
import { ReactNode } from "react";

type Props = {
  alertTitle: string;
  alertMessage: ReactNode;
  handleConfirmation: (onCloseModal: () => void) => void;
  isPending: boolean;
  pendingLabel: string;
  confirmLabel: string;

  open: boolean;
  onOpenChange: (open: boolean) => void;
  cancelLabel?: string;
  variant?: "default" | "destructive";
  /** default: AlertDialog عادي | card: أيقونة مركزة | brand: رأس متدرج مثل نوافذ المنتجات */
  variantLayout?: "default" | "card" | "brand";

  confirmButtonClassName?: string;
  cancelButtonClassName?: string;
  contentClassName?: string;
};

export function ControlledConfirmationModal({
  alertTitle,
  alertMessage,
  handleConfirmation,
  isPending,
  pendingLabel,
  confirmLabel,
  open,
  onOpenChange,
  cancelLabel = "إلغاء",
  variant = "destructive",
  variantLayout = "default",
  confirmButtonClassName,
  cancelButtonClassName,
  contentClassName,
}: Props) {
  const isCard = variantLayout === "card";
  const isBrand = variantLayout === "brand";
  const isStructured = isCard || isBrand;

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent
        dir="rtl"
        className={cn(
          isStructured &&
            "gap-0 rounded-2xl border-gray-100 p-0 shadow-xl sm:max-w-md overflow-hidden",
          contentClassName
        )}
      >
        {isBrand ? (
          <>
            <div
              className="px-6 py-4 flex items-start gap-3"
              style={{
                background: "linear-gradient(135deg, #0C1A3E, #1E3A7B)",
              }}
            >
              <div
                className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                style={{ background: "rgba(248,113,113,0.22)" }}
              >
                <i className="ri-delete-bin-line text-lg text-red-200" />
              </div>
              <div className="min-w-0 pt-0.5">
                <AlertDialogTitle className="text-white font-black text-base leading-tight border-0 p-0 m-0 text-right">
                  {alertTitle}
                </AlertDialogTitle>
                <p className="text-white/55 text-xs mt-1.5 text-right leading-relaxed">
                  لا يمكن التراجع عن هذا الإجراء بعد التأكيد.
                </p>
              </div>
            </div>
            <div className="px-6 pt-5 pb-1">
              <AlertDialogDescription asChild>
                <div className="text-sm text-slate-600 text-center leading-relaxed">
                  {alertMessage}
                </div>
              </AlertDialogDescription>
            </div>
          </>
        ) : (
          <AlertDialogHeader
            className={cn(
              isCard &&
                "items-center text-center space-y-3 px-6 pt-8 pb-2 sm:text-center"
            )}
          >
            {isCard && (
              <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50">
                <i className="ri-delete-bin-line text-2xl text-red-500" />
              </div>
            )}
            <AlertDialogTitle
              className={cn(
                isCard && "text-lg font-bold text-gray-900",
                !isCard && "text-center"
              )}
            >
              {alertTitle}
            </AlertDialogTitle>
            <AlertDialogDescription
              className={cn(
                isCard && "text-sm text-gray-500 leading-relaxed",
                !isCard && "text-center"
              )}
            >
              {alertMessage}
            </AlertDialogDescription>
          </AlertDialogHeader>
        )}
        <AlertDialogFooter
          className={cn(
            isStructured &&
              "mt-0 flex-row flex-wrap justify-stretch gap-3 border-t px-6 py-4 sm:flex-row",
            isBrand && "justify-between border-[#EEF2F8] bg-[#F8FAFC]",
            isCard && !isBrand && "justify-center gap-2 border-gray-100"
          )}
        >
          <AlertDialogCancel
            className={cn(
              isStructured &&
                "mt-0 flex-1 min-w-[120px] rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50",
              isCard &&
                !isBrand &&
                "rounded-lg border-gray-200 text-gray-700 hover:bg-gray-50",
              cancelButtonClassName
            )}
          >
            {cancelLabel}
          </AlertDialogCancel>
          <AlertDialogAction
            onClick={() => handleConfirmation(() => onOpenChange(false))}
            disabled={isPending}
            className={cn(
              isStructured &&
                "mt-0 flex-1 min-w-[120px] rounded-xl border-0 bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500",
              isCard &&
                !isBrand &&
                "rounded-lg border-0 bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500",
              variant === "destructive" &&
                !isStructured &&
                "bg-destructive text-destructive-foreground hover:bg-destructive/90",
              confirmButtonClassName
            )}
          >
            {isPending ? pendingLabel : confirmLabel}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}