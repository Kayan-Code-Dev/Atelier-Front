import { useMutation, useQuery } from "@tanstack/react-query";
import { useCallback, useEffect, useState } from "react";
import {
  useAddPaymentToSupplierOrderMutationOptions,
  useGetSupplierOrderQueryOptions,
} from "@/api/v2/suppliers/suppliers.hooks";
import {
  TSupplierOrderResponse,
  TSupplierOrderClothItem,
  resolveClothId,
} from "@/api/v2/suppliers/suppliers.types";
import { toEnglishNumerals } from "@/utils/formatDate";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";
import { cn } from "@/lib/utils";

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const INPUT_PROJECT_CLASS =
  "w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50";

function formatCurrency(value: string | number | null | undefined): string {
  if (value == null || value === "") return "—";
  const num = typeof value === "number" ? value : parseFloat(String(value));
  if (Number.isNaN(num)) return "—";
  return `${toEnglishNumerals(num.toLocaleString("en-US", { minimumFractionDigits: 2 }))} ج.م`;
}

function clothDisplayName(cloth: TSupplierOrderClothItem): string {
  return (
    [cloth.name, cloth.code].filter(Boolean).join(" — ") ||
    `قطعة #${resolveClothId(cloth)}`
  );
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

type Props = {
  order: TSupplierOrderResponse | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function AddPaymentToSupplierOrderModal({
  order,
  open,
  onOpenChange,
}: Props) {
  const orderId = order?.id ?? 0;
  const supplierId = order?.supplier_id ?? 0;

  const { data: orderDetail, isLoading } = useQuery(
    useGetSupplierOrderQueryOptions(supplierId, orderId, {
      enabled: open && orderId > 0 && supplierId > 0,
    }),
  );

  const { mutate: addPayment, isPending } = useMutation(
    useAddPaymentToSupplierOrderMutationOptions(),
  );

  const orderClothes = orderDetail?.clothes ?? [];

  const [amounts, setAmounts] = useState<Record<number, string>>({});

  useEffect(() => {
    if (!open) return;
    if (orderClothes.length > 0) {
      const init: Record<number, string> = {};
      orderClothes.forEach((_, i) => {
        init[i] = "";
      });
      setAmounts(init);
    }
  }, [open, orderClothes.length]);

  const handleClose = useCallback(() => {
    if (isPending) return;
    setAmounts({});
    onOpenChange(false);
  }, [isPending, onOpenChange]);

  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") handleClose();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [open, handleClose]);

  const handleAmountChange = useCallback((index: number, value: string) => {
    setAmounts((prev) => ({ ...prev, [index]: value }));
  }, []);

  const handleSubmit = () => {
    if (!order) return;

    const clothes = orderClothes
      .map((c, i) => ({
        cloth_id: resolveClothId(c),
        amount: parseFloat(amounts[i] ?? "") || 0,
      }))
      .filter((c) => c.cloth_id > 0 && c.amount > 0);

    if (clothes.length === 0) {
      toast.error("أدخل مبلغاً أكبر من صفر لقطعة واحدة على الأقل");
      return;
    }

    addPayment(
      { id: order.id, clothes },
      {
        onSuccess: () => {
          toast.success("تم إضافة الدفعة بنجاح");
          setAmounts({});
          onOpenChange(false);
        },
        onError: (err: { message?: string }) => {
          toast.error("حدث خطأ أثناء إضافة الدفعة", {
            description: err?.message,
          });
        },
      },
    );
  };

  const remaining = order
    ? parseFloat(String(order.remaining_payment || 0))
    : 0;

  if (!open || !order) return null;

  return (
    <div
      className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      dir="rtl"
      onClick={handleClose}
      role="presentation"
    >
      <div
        className="bg-white rounded-2xl w-full max-w-lg max-h-[92vh] overflow-hidden shadow-lg flex flex-col"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-payment-order-title"
      >
        <div className="flex items-start justify-between gap-3 px-6 pt-6 pb-3 shrink-0 border-b border-gray-100">
          <div>
            <h3
              id="add-payment-order-title"
              className="font-bold text-lg text-gray-800"
            >
              إضافة دفعة لطلبية
            </h3>
            {order && (
              <p className="text-xs text-gray-500 mt-1 leading-relaxed">
                الطلبية{" "}
                <span className="font-semibold text-gray-700 tabular-nums" dir="ltr">
                  #{toEnglishNumerals(order.id)}
                </span>
                {order.order_number && (
                  <span className="text-gray-600">
                    {" "}
                    ({order.order_number})
                  </span>
                )}
                <span className="block mt-0.5">
                  المتبقي على الطلبية:{" "}
                  <span className="font-medium text-amber-800 tabular-nums" dir="ltr">
                    {formatCurrency(order.remaining_payment)}
                  </span>
                </span>
              </p>
            )}
          </div>
          <button
            type="button"
            onClick={handleClose}
            className="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg hover:bg-gray-100 cursor-pointer disabled:opacity-50"
            disabled={isPending}
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-lg" />
          </button>
        </div>

        <div className="overflow-y-auto flex-1 px-6 py-4 min-h-0">
          {isLoading ? (
            <div className="flex flex-col items-center justify-center py-14 gap-3 text-gray-500">
              <Loader2 className="h-9 w-9 animate-spin text-blue-600" />
              <p className="text-sm">جاري تحميل تفاصيل الطلبية...</p>
            </div>
          ) : orderClothes.length === 0 ? (
            <div className="rounded-xl border border-gray-200 bg-gray-50/80 py-10 px-4 text-center text-sm text-gray-500">
              لا توجد قطع في هذه الطلبية.
            </div>
          ) : (
            <div className="space-y-3">
              <p className="text-xs text-gray-500 font-normal">
                أدخل مبلغ الدفعة لكل قطعة (ج.م)
              </p>

              <div className="space-y-3 max-h-[min(320px,50vh)] overflow-y-auto pr-0.5">
                {orderClothes.map((cloth, index) => (
                  <div
                    key={`cloth-${index}-${resolveClothId(cloth)}`}
                    className="rounded-xl border border-gray-200 bg-white p-3.5 space-y-2 shadow-sm"
                  >
                    <div className="text-sm font-semibold text-gray-800">
                      {clothDisplayName(cloth)}
                    </div>

                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                      <span>
                        المدفوع:{" "}
                        <span className="tabular-nums text-gray-700" dir="ltr">
                          {formatCurrency(cloth.payment)}
                        </span>
                      </span>
                      <span>
                        الباقي:{" "}
                        <span className="tabular-nums text-gray-700" dir="ltr">
                          {formatCurrency(cloth.remaining)}
                        </span>
                      </span>
                    </div>

                    <div className="flex flex-wrap items-center gap-2 pt-1">
                      <label className="text-xs text-gray-500 shrink-0">
                        مبلغ الدفعة
                      </label>
                      <input
                        type="number"
                        min={0}
                        step={0.01}
                        placeholder="0.00"
                        className={cn(INPUT_PROJECT_CLASS, "max-w-[160px]")}
                        dir="ltr"
                        value={amounts[index] ?? ""}
                        onChange={(e) =>
                          handleAmountChange(index, e.target.value)
                        }
                        disabled={isPending}
                      />
                      <span className="text-xs text-gray-400">ج.م</span>
                    </div>
                  </div>
                ))}
              </div>

              {remaining > 0 && (
                <p className="text-xs text-gray-500 rounded-lg bg-blue-50/60 border border-blue-100 px-3 py-2">
                  إجمالي المتبقي على الطلبية:{" "}
                  <span className="font-semibold text-gray-800 tabular-nums" dir="ltr">
                    {formatCurrency(remaining)}
                  </span>
                </p>
              )}
            </div>
          )}
        </div>

        {!isLoading && orderClothes.length > 0 && (
          <div className="shrink-0 px-6 pb-6 pt-2 border-t border-gray-100 flex gap-3">
            <button
              type="button"
              disabled={isPending}
              onClick={handleSubmit}
              className="flex-1 py-2.5 bg-blue-700 text-white rounded-xl text-sm font-bold cursor-pointer hover:bg-blue-800 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isPending ? "جاري الإضافة..." : "إضافة الدفعة"}
            </button>
            <button
              type="button"
              disabled={isPending}
              onClick={handleClose}
              className="flex-1 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm cursor-pointer whitespace-nowrap hover:bg-gray-200 disabled:opacity-50"
            >
              إلغاء
            </button>
          </div>
        )}

        {!isLoading && orderClothes.length === 0 && order && (
          <div className="shrink-0 px-6 pb-6 pt-2 border-t border-gray-100">
            <button
              type="button"
              onClick={handleClose}
              className="w-full py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm cursor-pointer hover:bg-gray-200"
            >
              إغلاق
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
