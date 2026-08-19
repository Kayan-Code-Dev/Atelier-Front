import { useCallback, useEffect, useState } from "react";
import { CreateSupplierOrderForm } from "./CreateSupplierOrderForm";

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  initialSupplierId?: string;
};

export function CreateSupplierOrderModal({
  open,
  onOpenChange,
  initialSupplierId,
}: Props) {
  const [formKey, setFormKey] = useState(0);
  const [pending, setPending] = useState(false);

  useEffect(() => {
    if (open) setFormKey((k) => k + 1);
  }, [open]);

  useEffect(() => {
    if (!open) setPending(false);
  }, [open]);

  const handleClose = useCallback(() => {
    if (!pending) onOpenChange(false);
  }, [pending, onOpenChange]);

  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") handleClose();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [open, handleClose]);

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      dir="rtl"
      onClick={handleClose}
      role="presentation"
    >
      <div
        className="bg-white rounded-2xl w-full max-w-4xl max-h-[92vh] overflow-hidden shadow-lg flex flex-col"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-supplier-order-title"
      >
        <div className="flex items-start justify-between gap-3 px-6 pt-6 pb-2 shrink-0 border-b border-gray-100">
          <div>
            <h3
              id="create-supplier-order-title"
              className="font-bold text-lg text-gray-800"
            >
              إضافة طلبية مورد
            </h3>
            <p className="text-xs text-gray-500 mt-1">
              املأ البيانات لإضافة طلبية جديدة مع أصناف المنتجات.
            </p>
          </div>
          <button
            type="button"
            onClick={handleClose}
            className="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg hover:bg-gray-100 cursor-pointer disabled:opacity-50"
            disabled={pending}
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-lg" />
          </button>
        </div>
        <div className="overflow-y-auto flex-1 px-6 py-4 min-h-0">
          <CreateSupplierOrderForm
            key={formKey}
            mode="modal"
            onSuccess={() => onOpenChange(false)}
            onCancel={() => onOpenChange(false)}
            onPendingChange={setPending}
            initialSupplierId={initialSupplierId}
          />
        </div>
      </div>
    </div>
  );
}
