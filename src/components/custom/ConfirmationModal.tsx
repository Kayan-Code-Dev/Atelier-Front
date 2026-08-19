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
import { cloneElement, ReactElement, ReactNode, useState } from "react";

type Props = {
    alertTitle: string;
    alertMessage: ReactNode;
    handleConfirmation: (onCloseModal: () => void) => void;
    isPending: boolean;
    confirmButtonClassName?: string;
    cancelButtonClassName?: string;
    pendingLabel: string;
    confirmLabel: string;
    children: ReactElement<{ onClick?: () => void }>
};

export function ConfirmationModal({ alertTitle, alertMessage, handleConfirmation, isPending, confirmButtonClassName, cancelButtonClassName, pendingLabel, confirmLabel, children }: Props) {
    const [isModalOpen, setIsModalOpen] = useState(false);

    return (
        <>
            {cloneElement(children, { onClick: () => setIsModalOpen(true) })}
            <AlertDialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle className="text-center">{alertTitle}</AlertDialogTitle>
                        <AlertDialogDescription className="text-center">
                            {alertMessage}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel className={cancelButtonClassName}>إلغاء</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => handleConfirmation(() => setIsModalOpen(false))}
                            disabled={isPending}
                            className={cn("bg-destructive text-white hover:bg-destructive/90", confirmButtonClassName)}
                        >
                            {isPending ? pendingLabel : confirmLabel}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
