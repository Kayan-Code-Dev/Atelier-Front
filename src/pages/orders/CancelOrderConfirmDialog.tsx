import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { cn } from "@/lib/utils";

function parsePaid(paid: unknown): number {
  if (paid === null || paid === undefined) return 0;
  const raw =
    typeof paid === "number" ? paid : parseFloat(String(paid).replace(/,/g, ""));
  const n = Number.isFinite(raw) ? raw : 0;
  return n;
}

export type CancelOrderConfirmDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  orderId: number;
  
  subtitle?: string;
  paidAmount?: string | number | null;
  currencySymbol?: string;
  onConfirm: () => void;
  isConfirming?: boolean;
};

export function CancelOrderConfirmDialog({
  open,
  onOpenChange,
  orderId,
  subtitle,
  paidAmount,
  currencySymbol = "ج.م",
  onConfirm,
  isConfirming = false,
}: CancelOrderConfirmDialogProps) {
  const paidNum = parsePaid(paidAmount);
  const hasPayments = paidNum > 0;

  const handleOpenChange = (next: boolean) => {
    if (isConfirming && !next) return;
    onOpenChange(next);
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent
        className="gap-0 border-slate-200 sm:max-w-md"
        bodyClassName="p-0"
        showCloseButton={false}
        dir="rtl"
      >
        <DialogHeader className="mb-0 space-y-0 border-b border-rose-100/80 bg-linear-to-l from-rose-50/95 to-white px-5 pb-4 pt-5 text-right sm:text-right">
          <div className="flex items-start justify-between gap-3">
            <div className="flex min-w-0 flex-1 items-start gap-3">
              <div
                className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-rose-200 bg-white text-rose-600 shadow-sm"
                aria-hidden
              >
                <i className="ri-prohibited-line text-2xl" />
              </div>
              <div className="min-w-0 flex-1 space-y-1">
                <DialogTitle className="text-lg font-bold tracking-tight text-slate-900">
                  إلغاء الطلب
                </DialogTitle>
                <DialogDescription asChild>
                  <div className="space-y-1 text-sm leading-relaxed text-slate-600">
                    <p>
                      الطلب{" "}
                      <span className="font-semibold text-slate-800 tabular-nums">
                        #{orderId}
                      </span>
                      {subtitle ? (
                        <>
                          {" "}
                          <span className="text-slate-500">—</span>{" "}
                          <span className="text-slate-700">{subtitle}</span>
                        </>
                      ) : null}
                    </p>
                    <p className="text-xs text-slate-500">
                      سيتم تغيير حالة الطلب إلى «ملغى». تأكد قبل المتابعة.
                    </p>
                  </div>
                </DialogDescription>
              </div>
            </div>
            <button
              type="button"
              disabled={isConfirming}
              onClick={() => handleOpenChange(false)}
              className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-800 disabled:pointer-events-none disabled:opacity-50"
              aria-label="إغلاق"
            >
              <i className="ri-close-line text-xl" />
            </button>
          </div>
        </DialogHeader>

        <div className="space-y-4 px-5 py-4">
          <div className="rounded-lg border border-slate-200 bg-slate-50/60 px-4 py-3 text-sm text-slate-700">
            <div className="flex gap-2">
              <i className="ri-information-line mt-0.5 shrink-0 text-lg text-slate-400" />
              <p>
                لا يمكن التراجع عن الإلغاء من الواجهة. إن وُجدت مدفوعات أو ضمانات
                مرتبطة بالطلب، راجعها مع الإدارة المالية قبل التأكيد.
              </p>
            </div>
          </div>

          {hasPayments && (
            <div
              className="flex gap-3 rounded-lg border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-950"
              role="status"
            >
              <i className="ri-error-warning-line mt-0.5 shrink-0 text-xl text-amber-600" />
              <div>
                <p className="font-semibold text-amber-900">تنبيه: يوجد مبلغ مدفوع</p>
                <p className="mt-1 text-amber-900/90">
                  المسجَّل مدفوعاً حوالي{" "}
                  <span className="font-bold tabular-nums" dir="ltr">
                    {paidNum.toLocaleString("ar-EG")} {currencySymbol}
                  </span>
                  . تأكد من معالجة السجلات المحاسبية قبل إلغاء الطلب.
                </p>
              </div>
            </div>
          )}
        </div>

        <div className="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/40 px-5 py-4 sm:flex-row sm:justify-end sm:gap-3">
          <Button
            type="button"
            variant="outline"
            className="border-slate-200 text-slate-700 hover:bg-white"
            disabled={isConfirming}
            onClick={() => handleOpenChange(false)}
          >
            رجوع
          </Button>
          <Button
            type="button"
            className={cn(
              "bg-rose-600 text-white hover:bg-rose-700",
              "shadow-sm"
            )}
            disabled={isConfirming}
            isLoading={isConfirming}
            onClick={onConfirm}
          >
            {!isConfirming && <i className="ri-close-circle-line ml-2 text-base" />}
            تأكيد إلغاء الطلب
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
