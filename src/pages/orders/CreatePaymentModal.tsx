import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { DatePicker } from "@/components/custom/DatePicker";
import { useForm, useFieldArray, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useMutation } from "@tanstack/react-query";
import { useCreatePaymentMutationOptions } from "@/api/v2/payments/payments.hooks";
import {
  TCreatePaymentRequest,
  TPaymentStatus,
  TPaymentType,
} from "@/api/v2/payments/payments.types";
import { toast } from "sonner";
import { format } from "date-fns";
import { TOrder } from "@/api/v2/orders/orders.types";
import { getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";
import { cn } from "@/lib/utils";

const paymentStatuses: TPaymentStatus[] = ["paid", "canceled", "pending"];

const paymentStatusLabels: Record<TPaymentStatus, string> = {
  pending: "معلق",
  paid: "مدفوع",
  canceled: "ملغي",
};

const paymentStatusIcons: Record<TPaymentStatus, string> = {
  pending: "ri-time-line",
  paid: "ri-checkbox-circle-line",
  canceled: "ri-close-circle-line",
};

const paymentTypes: TPaymentType[] = ["initial", "fee", "normal"];

const paymentTypeLabels: Record<TPaymentType, string> = {
  initial: "مبدئي",
  fee: "رسوم",
  normal: "عادي",
};

const paymentTypeIcons: Record<TPaymentType, string> = {
  initial: "ri-flag-line",
  fee: "ri-percent-line",
  normal: "ri-exchange-dollar-line",
};

const clothPaymentSchema = z.object({
  cloth_id: z.number().min(1, { message: "يجب اختيار قطعة" }),
  amount: z
    .number({
      required_error: "المبلغ مطلوب",
    })
    .min(0.01, { message: "المبلغ يجب أن يكون أكبر من صفر" }),
});

const formSchema = z.object({
  cloth_payments: z
    .array(clothPaymentSchema)
    .min(1, { message: "يجب اختيار قطعة واحدة على الأقل" }),
  status: z.enum(["paid", "canceled", "pending"], {
    required_error: "الحالة مطلوبة",
  }),
  payment_type: z.enum(["initial", "fee", "normal"], {
    required_error: "نوع الدفعة مطلوب",
  }),
  payment_date: z.string().min(1, { message: "تاريخ الدفع مطلوب" }),
  notes: z.string().min(1, { message: "الملاحظات مطلوبة" }),
});

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  order: TOrder;
  onSuccess?: () => void;
};

const fieldLabelClass =
  "text-[11px] font-semibold uppercase tracking-wide text-slate-500";

const controlClass =
  "border-slate-200 bg-white shadow-none focus-visible:border-slate-300 focus-visible:ring-[3px] focus-visible:ring-emerald-500/15";

function parseMoney(v: string | number | undefined | null): number {
  const n = typeof v === "number" ? v : parseFloat(String(v ?? "0"));
  return Number.isFinite(n) ? n : 0;
}

export function CreatePaymentModal({ open, onOpenChange, order, onSuccess }: Props) {
  const { currency_symbol: sym } = getOrderCurrencyInfo(order);

  const { mutate: createPayment, isPending } = useMutation(
    useCreatePaymentMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      cloth_payments: [],
      status: "pending",
      payment_type: "normal",
      payment_date: new Date().toISOString().split("T")[0],
      notes: "",
    },
  });

  const { fields, append, remove } = useFieldArray({
    control: form.control,
    name: "cloth_payments",
  });

  const watchedClothPayments = useWatch({
    control: form.control,
    name: "cloth_payments",
  });

  const draftLineTotal = (watchedClothPayments ?? []).reduce((sum, row) => {
    const v = row?.amount;
    const n = typeof v === "number" ? v : parseFloat(String(v));
    return sum + (Number.isFinite(n) ? n : 0);
  }, 0);

  const dateToString = (date: Date | undefined): string => {
    if (!date) return "";
    return date.toISOString().split("T")[0];
  };

  const stringToDate = (dateString: string | undefined): Date | undefined => {
    if (!dateString) return undefined;
    return new Date(dateString);
  };

  const availableClothes = order.items || [];
  const selectedClothIds = fields.map((field) => field.cloth_id);
  const unselectedClothes = availableClothes.filter(
    (cloth) => !selectedClothIds.includes(cloth.cloth_id || cloth.id)
  );

  const orderTotal = parseMoney(order.total_price);
  const orderPaid = parseMoney(order.paid);
  const orderRemaining = parseMoney(order.remaining);
  const clientName = order.client?.name?.trim() || "—";
  const progressDenominator =
    orderTotal > 0 ? orderTotal : Math.max(orderPaid + orderRemaining, 1);
  const paidPctBeforePayment =
    progressDenominator > 0
      ? Math.min(100, Math.round((orderPaid / progressDenominator) * 100))
      : 0;

  const handleAddCloth = () => {
    if (unselectedClothes.length > 0) {
      const firstUnselected = unselectedClothes[0];
      append({
        cloth_id: firstUnselected.cloth_id || firstUnselected.id,
        amount: 0,
      });
    }
  };

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    const paymentDate = values.payment_date
      ? format(new Date(values.payment_date + "T00:00:00"), "yyyy-MM-dd HH:mm:ss")
      : format(new Date(), "yyyy-MM-dd HH:mm:ss");

    const requestData: TCreatePaymentRequest = {
      order_id: order.id,
      cloth_payments: values.cloth_payments,
      status: values.status,
      payment_type: values.payment_type,
      payment_date: paymentDate,
      notes: values.notes,
    };

    createPayment(requestData, {
      onSuccess: () => {
        toast.success("تم تسجيل الدفعة بنجاح", {
          description: "تمت إضافة الدفعة إلى الطلب.",
        });
        form.reset({
          cloth_payments: [],
          status: "pending",
          payment_type: "normal",
          payment_date: new Date().toISOString().split("T")[0],
          notes: "",
        });
        onOpenChange(false);
        onSuccess?.();
      },
      onError: (error) => {
        toast.error("حدث خطأ أثناء تسجيل الدفعة", {
          description: error.message,
        });
      },
    });
  };

  const resetDefaults = () => ({
    cloth_payments: [] as { cloth_id: number; amount: number }[],
    status: "pending" as TPaymentStatus,
    payment_type: "normal" as TPaymentType,
    payment_date: new Date().toISOString().split("T")[0],
    notes: "",
  });

  const handleOpenChange = (newOpen: boolean) => {
    if (!newOpen) {
      form.reset(resetDefaults());
    }
    onOpenChange(newOpen);
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent
        className="gap-0 border-slate-200 sm:max-w-4xl"
        bodyClassName="p-0"
        showCloseButton={false}
      >
        <DialogHeader className="mb-0 space-y-0 border-0 border-b border-slate-200 bg-linear-to-l from-slate-50/90 to-white px-6 pb-5 pt-6 text-right sm:text-right">
          <div className="flex items-start justify-between gap-3">
            <div className="flex flex-1 items-start gap-3">
              <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-600">
                <i className="ri-wallet-3-line text-2xl" aria-hidden />
              </div>
              <div className="min-w-0 flex-1 space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                  <DialogTitle className="text-lg font-bold tracking-tight text-slate-800">
                    تسجيل دفعة جديدة
                  </DialogTitle>
                  <span className="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-500">
                    طلب #{order.id}
                  </span>
                </div>
                <DialogDescription className="text-sm leading-relaxed text-slate-500">
                  <span className="font-medium text-slate-600">{clientName}</span>
                  <span className="mx-1.5 text-slate-300">·</span>
                  وزّع المبلغ على الأصناف ثم أكمل حالة الدفعة ونوعها والتاريخ والملاحظات.
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

        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="space-y-5 px-6 py-5"
            dir="rtl"
          >
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
              <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                  إجمالي الطلب
                </div>
                <div className="mt-1 text-lg font-bold text-slate-800 tabular-nums">
                  {orderTotal.toLocaleString("ar-EG")}{" "}
                  <span className="text-sm font-semibold text-slate-500">{sym}</span>
                </div>
              </div>
              <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                  المدفوع حتى الآن
                </div>
                <div className="mt-1 text-lg font-bold text-emerald-700 tabular-nums">
                  {orderPaid.toLocaleString("ar-EG")}{" "}
                  <span className="text-sm font-semibold text-emerald-600/90">{sym}</span>
                </div>
              </div>
              <div className="rounded-lg border border-rose-100 bg-rose-50/70 px-4 py-3">
                <div className="text-[11px] font-semibold uppercase tracking-wide text-rose-700/90">
                  المتبقي
                </div>
                <div className="mt-1 text-lg font-bold text-rose-700 tabular-nums">
                  {orderRemaining.toLocaleString("ar-EG")}{" "}
                  <span className="text-sm font-semibold text-rose-600">{sym}</span>
                </div>
              </div>
            </div>

            <div className="rounded-lg border border-slate-100 bg-slate-50/50 p-4">
              <div className="mb-2 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className="text-sm font-semibold text-emerald-700">
                    {orderPaid.toLocaleString("ar-EG")} {sym}
                  </span>
                  <span className="text-xs text-slate-400">مدفوع</span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="text-xs text-slate-400">متبقي</span>
                  <span className="text-sm font-semibold text-rose-600">
                    {orderRemaining.toLocaleString("ar-EG")} {sym}
                  </span>
                </div>
              </div>
              <div className="h-3 overflow-hidden rounded-full bg-slate-100">
                <div
                  className="h-full rounded-full bg-emerald-500 transition-all duration-500"
                  style={{ width: `${paidPctBeforePayment}%` }}
                />
              </div>
              <div className="mt-1 text-center text-xs text-slate-500">
                {paidPctBeforePayment}% من إجمالي{" "}
                {progressDenominator.toLocaleString("ar-EG")} {sym}
              </div>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
              <div className="space-y-3 lg:col-span-7">
              <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2">
                <div className="flex items-center gap-2">
                  <i className="ri-shopping-bag-line text-slate-400" aria-hidden />
                  <FormLabel className={cn(fieldLabelClass, "mt-0 text-slate-700")}>
                    توزيع المبلغ على الأصناف
                  </FormLabel>
                </div>
                <button
                  type="button"
                  onClick={handleAddCloth}
                  disabled={unselectedClothes.length === 0 || isPending}
                  className="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-800 transition-colors hover:bg-emerald-100 disabled:pointer-events-none disabled:opacity-50"
                >
                  <i className="ri-add-line text-base" aria-hidden />
                  إضافة صنف
                </button>
              </div>

              {fields.length === 0 ? (
                <div className="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-slate-200 bg-slate-50/40 py-10 text-center">
                  <i className="ri-shirt-line text-3xl text-slate-300" aria-hidden />
                  <p className="text-sm text-slate-500">
                    لم يُضف أي صنف. اضغط «إضافة صنف» لربط مبالغ الدفعة ببنود الطلب.
                  </p>
                </div>
              ) : null}

              <div className="space-y-3">
                {fields.map((field, index) => {
                  const cloth = availableClothes.find(
                    (c) => (c.cloth_id || c.id) === field.cloth_id
                  );

                  return (
                    <div
                      key={field.id}
                      className="rounded-lg border border-slate-200 bg-white p-4 shadow-none"
                    >
                      <div className="mb-3 flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-600">
                          بند {index + 1}
                        </span>
                        <button
                          type="button"
                          onClick={() => remove(index)}
                          disabled={isPending}
                          className="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600 disabled:pointer-events-none disabled:opacity-50"
                          aria-label="حذف البند"
                        >
                          <i className="ri-delete-bin-line text-lg" aria-hidden />
                        </button>
                      </div>

                      <div className="space-y-3">
                        <FormField
                          control={form.control}
                          name={`cloth_payments.${index}.cloth_id`}
                          render={({ field: clothField }) => (
                            <FormItem className="space-y-2">
                              <FormLabel className={fieldLabelClass}>الصنف</FormLabel>
                              <Select
                                value={clothField.value?.toString()}
                                onValueChange={(value) => {
                                  clothField.onChange(Number(value));
                                }}
                                disabled={isPending}
                              >
                                <FormControl>
                                  <SelectTrigger
                                    className={cn("h-10 rounded-lg", controlClass)}
                                  >
                                    <SelectValue placeholder="اختر الصنف" />
                                  </SelectTrigger>
                                </FormControl>
                                <SelectContent>
                                  {availableClothes.map((c) => {
                                    const clothId = c.cloth_id || c.id;
                                    const isSelectedElsewhere =
                                      selectedClothIds.includes(clothId) &&
                                      clothField.value !== clothId;
                                    return (
                                      <SelectItem
                                        key={clothId}
                                        value={clothId.toString()}
                                        disabled={isSelectedElsewhere}
                                      >
                                        {c.code}
                                        {c.name ? ` — ${c.name}` : ""}
                                        {isSelectedElsewhere ? " (مختار في بند آخر)" : ""}
                                      </SelectItem>
                                    );
                                  })}
                                </SelectContent>
                              </Select>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name={`cloth_payments.${index}.amount`}
                          render={({ field: amountField }) => (
                            <FormItem className="space-y-2">
                              <FormLabel className={fieldLabelClass}>
                                المبلغ ({sym})
                              </FormLabel>
                              <FormControl>
                                <Input
                                  placeholder="0"
                                  className={cn("h-10 rounded-lg", controlClass)}
                                  value={amountField.value ?? ""}
                                  onChange={(e) => {
                                    const val = e.target.value.replace(/[^0-9.]/g, "");
                                    const value = val ? parseFloat(val) : undefined;
                                    amountField.onChange(value);
                                  }}
                                  disabled={isPending}
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        {cloth ? (
                          <div className="rounded-md border border-slate-100 bg-slate-50/80 px-3 py-2 text-xs text-slate-600">
                            {(() => {
                              const quantity = cloth.quantity ?? 1;
                              const pricePerUnit = parseMoney(cloth.price);
                              const totalPrice = pricePerUnit * quantity;
                              const paid = parseMoney(cloth.item_paid);
                              const remaining = Math.max(0, totalPrice - paid);
                              return (
                                <div className="flex flex-wrap gap-x-4 gap-y-1">
                                  <span>
                                    إجمالي السعر:{" "}
                                    <strong className="text-slate-800 tabular-nums">
                                      {totalPrice.toLocaleString("ar-EG")} {sym}
                                    </strong>
                                  </span>
                                  <span>
                                    المدفوع:{" "}
                                    <strong className="text-slate-800 tabular-nums">
                                      {paid.toLocaleString("ar-EG")} {sym}
                                    </strong>
                                  </span>
                                  <span>
                                    المتبقي:{" "}
                                    <strong className="text-emerald-800 tabular-nums">
                                      {remaining.toLocaleString("ar-EG")} {sym}
                                    </strong>
                                  </span>
                                </div>
                              );
                            })()}
                          </div>
                        ) : null}
                      </div>
                    </div>
                  );
                })}
              </div>

              {fields.length > 0 ? (
                <div className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm">
                  <span className="text-slate-500">مجموع مبالغ هذه الدفعة</span>
                  <span className="font-bold tabular-nums text-slate-800">
                    {draftLineTotal.toLocaleString("ar-EG")} {sym}
                  </span>
                </div>
              ) : null}

              {fields.length > 0 && form.formState.errors.cloth_payments && (
                <p className="text-sm text-red-600">
                  {form.formState.errors.cloth_payments.message ||
                    form.formState.errors.cloth_payments.root?.message}
                </p>
              )}
              </div>

              <div className="lg:col-span-5">
                <div className="space-y-5 rounded-lg border border-slate-200 bg-white p-4 shadow-none">
                  <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <i className="ri-settings-3-line text-slate-400" aria-hidden />
                    <span className="text-sm font-semibold text-slate-700">بيانات الدفعة</span>
                  </div>

                  <FormField
                    control={form.control}
                    name="status"
                    render={({ field }) => (
                      <FormItem className="space-y-2">
                        <FormLabel className={fieldLabelClass}>حالة الدفعة</FormLabel>
                        <FormControl>
                          <div className="grid grid-cols-3 gap-2">
                            {paymentStatuses.map((status) => {
                              const selected = field.value === status;
                              return (
                                <button
                                  key={status}
                                  type="button"
                                  onClick={() => field.onChange(status)}
                                  disabled={isPending}
                                  className={cn(
                                    "flex flex-col items-center gap-1.5 rounded-lg border-2 p-2.5 text-center transition-all disabled:opacity-50",
                                    selected
                                      ? "border-emerald-500 bg-emerald-50/80 text-emerald-900 shadow-sm"
                                      : "border-slate-200 bg-slate-50/50 text-slate-600 hover:border-slate-300 hover:bg-slate-50"
                                  )}
                                >
                                  <i
                                    className={cn(
                                      paymentStatusIcons[status],
                                      "text-xl",
                                      selected ? "text-emerald-600" : "text-slate-400"
                                    )}
                                    aria-hidden
                                  />
                                  <span className="text-[11px] font-semibold leading-tight">
                                    {paymentStatusLabels[status]}
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
                    name="payment_type"
                    render={({ field }) => (
                      <FormItem className="space-y-2">
                        <FormLabel className={fieldLabelClass}>نوع الدفعة</FormLabel>
                        <FormControl>
                          <div className="grid grid-cols-3 gap-2">
                            {paymentTypes.map((type) => {
                              const selected = field.value === type;
                              return (
                                <button
                                  key={type}
                                  type="button"
                                  onClick={() => field.onChange(type)}
                                  disabled={isPending}
                                  className={cn(
                                    "flex flex-col items-center gap-1.5 rounded-lg border-2 p-2.5 text-center transition-all disabled:opacity-50",
                                    selected
                                      ? "border-slate-700 bg-slate-100 text-slate-900 shadow-sm"
                                      : "border-slate-200 bg-slate-50/50 text-slate-600 hover:border-slate-300 hover:bg-slate-50"
                                  )}
                                >
                                  <i
                                    className={cn(
                                      paymentTypeIcons[type],
                                      "text-xl",
                                      selected ? "text-slate-800" : "text-slate-400"
                                    )}
                                    aria-hidden
                                  />
                                  <span className="text-[11px] font-semibold leading-tight">
                                    {paymentTypeLabels[type]}
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
                    name="payment_date"
                    render={({ field }) => (
                      <FormItem className="space-y-2">
                        <FormLabel className={fieldLabelClass}>تاريخ الدفع</FormLabel>
                        <FormControl>
                          <DatePicker
                            value={stringToDate(field.value)}
                            onChange={(date) => {
                              field.onChange(dateToString(date));
                            }}
                            label=""
                            placeholder="اختر التاريخ"
                            allowPastDates={true}
                            allowFutureDates={true}
                            showLabel={false}
                            disabled={isPending}
                            buttonClassName={cn(
                              "h-10 w-full justify-between rounded-lg font-normal",
                              controlClass
                            )}
                          />
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
                            placeholder="تفاصيل إضافية تُحفظ مع الدفعة"
                            className={cn("min-h-[88px] resize-y rounded-lg", controlClass)}
                            {...field}
                            rows={3}
                            disabled={isPending}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </div>
              </div>
            </div>

            <DialogFooter className="-mx-6 mt-0 flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/50 px-6 py-4 sm:flex-row sm:justify-end sm:gap-2">
              <Button
                type="button"
                variant="outline"
                className="h-10 w-full rounded-lg border-slate-200 bg-white text-slate-700 shadow-none hover:bg-slate-50 sm:w-auto"
                onClick={() => handleOpenChange(false)}
                disabled={isPending}
              >
                إلغاء
              </Button>
              <Button
                type="submit"
                disabled={isPending}
                isLoading={isPending}
                className="h-10 w-full rounded-lg border border-emerald-600 bg-emerald-600 font-medium text-white shadow-none hover:bg-emerald-700 sm:w-auto"
              >
                <i className="ri-check-line text-lg" aria-hidden />
                تسجيل الدفعة
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
