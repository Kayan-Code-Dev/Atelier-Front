import {
  useGetCustodyDetailsQueryOptions,
  useReturnCustodyMutationOptions,
} from "@/api/v2/custody/custody.hooks";
import {
  TCustodyAction,
  TCustodyStatus,
  TCustodyType,
  TReturnCustodyRequest,
} from "@/api/v2/custody/custody.types";
import { UploadFileField } from "@/components/custom/UploadFile";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { cn } from "@/lib/utils";
import { formatDate } from "@/utils/formatDate";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useState, type ReactNode } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import * as z from "zod";

type Props = {
  custodyId: number | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  currencySymbol?: string;
};

const fieldLabelClass =
  "text-[11px] font-semibold uppercase tracking-wide text-slate-500";

const controlClass =
  "border-slate-200 bg-white shadow-none focus-visible:border-slate-300 focus-visible:ring-[3px] focus-visible:ring-emerald-500/15";

const getCustodyTypeLabel = (type: TCustodyType): string => {
  const labels: Record<TCustodyType, string> = {
    money: "مال",
    physical_item: "عنصر مادي",
    document: "مستند",
  };
  return labels[type] || type;
};

const custodyTypeIcons: Record<TCustodyType, string> = {
  money: "ri-money-dollar-circle-line",
  physical_item: "ri-shirt-line",
  document: "ri-file-shield-2-line",
};

const custodyStatusPill: Record<TCustodyStatus, string> = {
  pending: "bg-amber-50 text-amber-800 border border-amber-200",
  returned: "bg-emerald-50 text-emerald-700 border border-emerald-200",
  lost: "bg-rose-50 text-rose-700 border border-rose-200",
};

const getCustodyStatusLabel = (status: TCustodyStatus): string => {
  const labels: Record<TCustodyStatus, string> = {
    pending: "قيد الانتظار",
    returned: "تم الإرجاع",
    lost: "مفقود",
  };
  return labels[status] || status;
};

const custodyActions: TCustodyAction[] = ["returned_to_user", "forfeit"];

const custodyActionLabels: Record<TCustodyAction, string> = {
  returned_to_user: "إرجاع للعميل",
  forfeit: "مصادرة",
};

const custodyActionIcons: Record<TCustodyAction, string> = {
  returned_to_user: "ri-user-shared-line",
  forfeit: "ri-inbox-archive-line",
};

const returnCustodySchema = z.object({
  custody_action: z.enum(["returned_to_user", "forfeit"], {
    required_error: "نوع الإجراء مطلوب",
  }),
  notes: z.string().min(1, { message: "الملاحظات مطلوبة" }),
  reason_of_kept: z.string().optional(),
  acknowledgement_receipt_photos: z
    .array(z.instanceof(File))
    .min(1, { message: "صور إيصال الاستلام مطلوبة" }),
});

function DetailField({
  label,
  children,
}: {
  label: string;
  children: ReactNode;
}) {
  return (
    <div className="space-y-1">
      <p className={fieldLabelClass}>{label}</p>
      <div className="text-sm font-medium text-slate-800">{children}</div>
    </div>
  );
}

export function CustodyDetailsModal({
  custodyId,
  open,
  onOpenChange,
  currencySymbol = "ج.م",
}: Props) {
  const [showReturnForm, setShowReturnForm] = useState(false);

  const { data: custodyData, isPending } = useQuery({
    ...useGetCustodyDetailsQueryOptions(custodyId || 0),
    enabled: open && !!custodyId,
  });

  const { mutate: returnCustody, isPending: isReturning } = useMutation(
    useReturnCustodyMutationOptions()
  );

  const form = useForm<z.infer<typeof returnCustodySchema>>({
    resolver: zodResolver(returnCustodySchema),
    defaultValues: {
      custody_action: "returned_to_user",
      notes: "",
      reason_of_kept: "",
      acknowledgement_receipt_photos: undefined,
    },
  });

  const handleOpenChange = (newOpen: boolean) => {
    if (!newOpen) {
      form.reset();
      setShowReturnForm(false);
    }
    onOpenChange(newOpen);
  };

  const onSubmit = (values: z.infer<typeof returnCustodySchema>) => {
    if (!custodyId) return;

    const requestData: TReturnCustodyRequest = {
      custody_action: values.custody_action,
      notes: values.notes,
      ...(values.reason_of_kept && { reason_of_kept: values.reason_of_kept }),
      acknowledgement_receipt_photos: values.acknowledgement_receipt_photos,
    };

    returnCustody(
      {
        custody_id: custodyId,
        data: requestData,
      },
      {
        onSuccess: () => {
          toast.success("تم إرجاع الضمان بنجاح", {
            description: "تمت عملية إرجاع الضمان بنجاح.",
          });
          form.reset();
          setShowReturnForm(false);
          handleOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء إرجاع الضمان", {
            description: error.message,
          });
        },
      }
    );
  };

  const canReturn = custodyData?.status === "pending";

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent
        className="gap-0 border-slate-200 sm:max-w-3xl"
        bodyClassName="p-0"
        showCloseButton={false}
      >
        <DialogHeader className="mb-0 space-y-0 border-0 border-b border-slate-200 bg-linear-to-l from-slate-50/90 to-white px-6 pb-5 pt-6 text-right sm:text-right">
          <div className="flex items-start justify-between gap-3">
            <div className="flex flex-1 items-start gap-3">
              <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-600">
                <i className="ri-shield-check-line text-2xl" aria-hidden />
              </div>
              <div className="min-w-0 flex-1 space-y-1">
                <DialogTitle className="text-lg font-bold tracking-tight text-slate-800">
                  تفاصيل الضمان
                </DialogTitle>
                <DialogDescription className="text-sm leading-relaxed text-slate-500">
                  {custodyData ? (
                    <>
                      رقم{" "}
                      <span className="font-semibold text-slate-700">
                        #{custodyData.id}
                      </span>
                      <span className="mx-1 text-slate-300">·</span>
                      طلب{" "}
                      <span className="font-semibold text-slate-700">
                        #{custodyData.order_id}
                      </span>
                      <span className="mx-1 text-slate-300">·</span>
                      {getCustodyTypeLabel(custodyData.type)}
                    </>
                  ) : (
                    "عرض بيانات الضمان والإجراءات المتاحة."
                  )}
                </DialogDescription>
              </div>
            </div>
            <button
              type="button"
              onClick={() => handleOpenChange(false)}
              className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-800"
              aria-label="إغلاق"
            >
              <i className="ri-close-line text-xl" />
            </button>
          </div>
        </DialogHeader>

        <div className="max-h-[min(70vh,640px)] overflow-y-auto px-6 py-5" dir="rtl">
          {isPending ? (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {Array.from({ length: 6 }).map((_, i) => (
                  <Skeleton key={i} className="h-16 rounded-lg" />
                ))}
              </div>
              <Skeleton className="h-24 rounded-lg" />
              <Skeleton className="h-24 rounded-lg" />
            </div>
          ) : custodyData ? (
            <div className="space-y-5">
              <div className="flex flex-wrap items-center gap-2">
                <span
                  className={cn(
                    "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium",
                    custodyStatusPill[custodyData.status]
                  )}
                >
                  <i className="ri-shield-user-line" aria-hidden />
                  {getCustodyStatusLabel(custodyData.status)}
                </span>
                <span className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">
                  <i
                    className={custodyTypeIcons[custodyData.type]}
                    aria-hidden
                  />
                  {getCustodyTypeLabel(custodyData.type)}
                </span>
              </div>

              <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
                  <DetailField label="رقم الضمان">#{custodyData.id}</DetailField>
                </div>
                <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
                  <DetailField label="رقم الطلب">#{custodyData.order_id}</DetailField>
                </div>
                <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
                  <DetailField label="تاريخ الإنشاء">
                    {formatDate(custodyData.created_at)}
                  </DetailField>
                </div>
                {custodyData.returned_at ? (
                  <div className="rounded-lg border border-emerald-100 bg-emerald-50/50 px-4 py-3">
                    <DetailField label="تاريخ الإرجاع">
                      <span className="text-emerald-800">
                        {formatDate(custodyData.returned_at)}
                      </span>
                    </DetailField>
                  </div>
                ) : null}
                {custodyData.type === "money" && custodyData.value != null ? (
                  <div className="rounded-lg border border-indigo-100 bg-indigo-50/40 px-4 py-3 sm:col-span-2">
                    <p className={fieldLabelClass}>القيمة</p>
                    <p className="text-lg font-bold tabular-nums text-slate-800">
                      {Number(custodyData.value).toLocaleString("ar-EG")}{" "}
                      <span className="text-sm font-semibold text-slate-500">
                        {currencySymbol}
                      </span>
                    </p>
                  </div>
                ) : null}
              </div>

              <div className="rounded-lg border border-slate-200 bg-white">
                <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                  <i className="ri-file-text-line text-slate-400" aria-hidden />
                  <h3 className="text-sm font-semibold text-slate-700">الوصف</h3>
                </div>
                <div className="px-4 py-3 text-sm leading-relaxed text-slate-600">
                  {custodyData.description || "—"}
                </div>
              </div>

              <div className="rounded-lg border border-slate-200 bg-slate-50/40">
                <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                  <i className="ri-sticky-note-line text-slate-400" aria-hidden />
                  <h3 className="text-sm font-semibold text-slate-700">الملاحظات</h3>
                </div>
                <div className="px-4 py-3 text-sm leading-relaxed text-slate-600">
                  {custodyData.notes || "—"}
                </div>
              </div>

              {custodyData.photos && custodyData.photos.length > 0 ? (
                <div className="rounded-lg border border-slate-200 bg-white">
                  <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                    <i className="ri-image-line text-slate-400" aria-hidden />
                    <h3 className="text-sm font-semibold text-slate-700">الصور</h3>
                  </div>
                  <div className="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
                    {custodyData.photos.map((photo, index) => (
                      <div
                        key={photo.id}
                        className="overflow-hidden rounded-lg border border-slate-200 bg-slate-50 ring-1 ring-slate-100"
                      >
                        <img
                          src={photo.photo_url}
                          alt={`مرفق ${index + 1}`}
                          className="h-52 w-full object-cover"
                        />
                      </div>
                    ))}
                  </div>
                </div>
              ) : null}

              {canReturn ? (
                <div className="border-t border-slate-100 pt-2">
                  {!showReturnForm ? (
                    <Button
                      type="button"
                      onClick={() => setShowReturnForm(true)}
                      className="h-10 w-full rounded-lg border border-indigo-200 bg-indigo-50 font-medium text-indigo-800 shadow-none hover:bg-indigo-100"
                      variant="outline"
                    >
                      <i className="ri-arrow-go-back-line text-lg" aria-hidden />
                      إرجاع الضمان
                    </Button>
                  ) : (
                    <div className="rounded-lg border border-slate-200 bg-slate-50/30 p-4">
                      <div className="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                        <i className="ri-survey-line text-slate-400" aria-hidden />
                        <h3 className="text-sm font-semibold text-slate-800">
                          تنفيذ إرجاع الضمان
                        </h3>
                      </div>
                      <Form {...form}>
                        <form
                          onSubmit={form.handleSubmit(onSubmit)}
                          className="space-y-4"
                          dir="rtl"
                        >
                          <FormField
                            control={form.control}
                            name="custody_action"
                            render={({ field }) => (
                              <FormItem className="space-y-2">
                                <FormLabel className={fieldLabelClass}>
                                  نوع الإجراء
                                </FormLabel>
                                <FormControl>
                                  <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    {custodyActions.map((action) => {
                                      const selected = field.value === action;
                                      return (
                                        <button
                                          key={action}
                                          type="button"
                                          onClick={() => field.onChange(action)}
                                          disabled={isReturning}
                                          className={cn(
                                            "flex items-center gap-3 rounded-lg border-2 p-3 text-right transition-all disabled:opacity-50",
                                            selected
                                              ? "border-indigo-500 bg-indigo-50/80 text-indigo-900 shadow-sm"
                                              : "border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50/80"
                                          )}
                                        >
                                          <i
                                            className={cn(
                                              custodyActionIcons[action],
                                              "text-2xl",
                                              selected
                                                ? "text-indigo-600"
                                                : "text-slate-400"
                                            )}
                                            aria-hidden
                                          />
                                          <span className="text-sm font-semibold">
                                            {custodyActionLabels[action]}
                                          </span>
                                        </button>
                                      );
                                    })}
                                  </div>
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />

                          <FormField
                            control={form.control}
                            name="notes"
                            render={({ field }) => (
                              <FormItem className="space-y-2">
                                <FormLabel className={fieldLabelClass}>ملاحظات</FormLabel>
                                <FormControl>
                                  <Textarea
                                    placeholder="تفاصيل الإجراء…"
                                    className={cn(
                                      "min-h-[88px] resize-y rounded-lg",
                                      controlClass
                                    )}
                                    {...field}
                                    rows={3}
                                    disabled={isReturning}
                                  />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />

                          {form.watch("custody_action") === "forfeit" ? (
                            <FormField
                              control={form.control}
                              name="reason_of_kept"
                              render={({ field }) => (
                                <FormItem className="space-y-2">
                                  <FormLabel className={fieldLabelClass}>
                                    سبب المصادرة
                                  </FormLabel>
                                  <FormControl>
                                    <Textarea
                                      placeholder="اذكر السبب…"
                                      className={cn(
                                        "min-h-[72px] resize-y rounded-lg",
                                        controlClass
                                      )}
                                      {...field}
                                      rows={2}
                                      disabled={isReturning}
                                    />
                                  </FormControl>
                                  <FormMessage />
                                </FormItem>
                              )}
                            />
                          ) : null}

                          <div className="rounded-lg border border-slate-200 bg-white p-3">
                            <FormField
                              control={form.control}
                              name="acknowledgement_receipt_photos"
                              render={() => (
                                <FormItem className="space-y-2">
                                  <FormLabel className={fieldLabelClass}>
                                    صور إيصال الاستلام
                                  </FormLabel>
                                  <FormControl>
                                    <UploadFileField
                                      name="acknowledgement_receipt_photos"
                                      multiple
                                      accept="image/*"
                                      placeholder="اسحب الصور أو اختر من الجهاز"
                                      showPreview
                                    />
                                  </FormControl>
                                  <FormMessage />
                                </FormItem>
                              )}
                            />
                          </div>

                          <DialogFooter className="mt-2 flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/50 px-0 py-4 sm:flex-row sm:justify-end">
                            <Button
                              type="button"
                              variant="outline"
                              className="h-10 w-full rounded-lg border-slate-200 bg-white shadow-none hover:bg-slate-50 sm:w-auto"
                              onClick={() => {
                                form.reset();
                                setShowReturnForm(false);
                              }}
                              disabled={isReturning}
                            >
                              إلغاء
                            </Button>
                            <Button
                              type="submit"
                              disabled={isReturning}
                              isLoading={isReturning}
                              className="h-10 w-full rounded-lg border border-emerald-600 bg-emerald-600 font-medium text-white shadow-none hover:bg-emerald-700 sm:w-auto"
                            >
                              <i className="ri-check-line text-lg" aria-hidden />
                              تأكيد الإرجاع
                            </Button>
                          </DialogFooter>
                        </form>
                      </Form>
                    </div>
                  )}
                </div>
              ) : null}
            </div>
          ) : (
            <div className="rounded-lg border border-dashed border-slate-200 bg-slate-50/50 py-12 text-center">
              <i className="ri-file-search-line mb-2 text-4xl text-slate-300" aria-hidden />
              <p className="text-sm text-slate-500">لا توجد بيانات لعرضها.</p>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
