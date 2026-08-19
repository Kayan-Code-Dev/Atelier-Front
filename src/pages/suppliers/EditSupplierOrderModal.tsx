import { Input } from "@/components/ui/input";
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
import { useForm, useFieldArray, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useCallback, useEffect, useMemo } from "react";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";

import {
  TSupplierOrderResponse,
  TSupplierOrderDetailResponse,
  TUpdateSupplierOrderRequest,
  resolveClothId,
} from "@/api/v2/suppliers/suppliers.types";
import {
  useUpdateSupplierOrderMutationOptions,
  useGetSuppliersListQueryOptions,
  useGetSupplierOrderQueryOptions,
} from "@/api/v2/suppliers/suppliers.hooks";
import { CategoriesSelect } from "@/components/custom/CategoriesSelect";
import { SubcategoriesSelect } from "@/components/custom/SubcategoriesSelect";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { CustomCalendar } from "@/components/custom/CustomCalendar";
import { toEnglishNumerals } from "@/utils/formatDate";
import { cn } from "@/lib/utils";

// ---------------------------------------------------------------------------
// Schema
// ---------------------------------------------------------------------------

const clothSchema = z.object({
  cloth_id: z.number(),
  price: z.number().min(0),
  payment: z.number().min(0),
  notes: z.string().optional().nullable(),
  category_id: z.string().optional(),
  subcategory_id: z.string().optional(),
});

const formSchema = z.object({
  supplier_id: z.string().min(1, { message: "المورد مطلوب" }),
  branch_id: z.string().min(1, { message: "الفرع مطلوب" }),
  order_number: z.string().min(1, { message: "رقم الطلبية مطلوب" }),
  type: z.string().optional(),
  order_date: z.string().min(1, { message: "تاريخ الطلبية مطلوب" }),
  status: z.string().optional(),
  total_amount: z.number(),
  payment_amount: z.number().min(0),
  remaining_payment: z.number(),
  notes: z.string().optional().nullable(),
  clothes: z.array(clothSchema),
});

type FormValues = z.infer<typeof formSchema>;

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const ORDER_STATUS_OPTIONS = [
  { value: "pending", label: "قيد الانتظار" },
  { value: "confirmed", label: "مؤكد" },
  { value: "delivered", label: "تم التسليم" },
  { value: "cancelled", label: "ملغى" },
] as const;

const EMPTY_FORM: FormValues = {
  supplier_id: "",
  branch_id: "",
  order_number: "",
  type: "fabric",
  order_date: "",
  status: "pending",
  total_amount: 0,
  payment_amount: 0,
  remaining_payment: 0,
  notes: "",
  clothes: [],
};

const INPUT_PROJECT_CLASS =
  "w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50";

const LABEL_PROJECT = "text-xs text-gray-500 font-normal";

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function toNumber(v: string | number | null | undefined): number {
  if (typeof v === "number") return v;
  return parseFloat(String(v)) || 0;
}

function mapDetailToForm(d: TSupplierOrderDetailResponse): FormValues {
  const clothes = (d.clothes ?? []).map((c) => ({
    cloth_id: resolveClothId(c),
    price: toNumber(c.price),
    payment: toNumber(c.payment),
    notes: c.notes ?? "",
    category_id: c.category_id != null ? String(c.category_id) : "",
    subcategory_id: (c.subcategory_ids ?? [])[0] != null ? String((c.subcategory_ids ?? [])[0]) : "",
  }));

  const totalAmount = clothes.reduce((s, c) => s + c.price, 0);
  const paymentAmount = clothes.reduce((s, c) => s + c.payment, 0);

  return {
    supplier_id: String(d.supplier_id ?? ""),
    branch_id: d.branch_id != null ? String(d.branch_id) : "",
    order_number: d.order_number ?? "",
    type: d.type ?? "fabric",
    order_date: d.order_date ?? "",
    status: d.status ?? "pending",
    total_amount: totalAmount,
    payment_amount: toNumber(d.payment_amount) || paymentAmount,
    remaining_payment:
      toNumber(d.remaining_payment) || totalAmount - paymentAmount,
    notes: d.notes ?? "",
    clothes,
  };
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

type Props = {
  order: TSupplierOrderResponse | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function EditSupplierOrderModal({ order, open, onOpenChange }: Props) {
  const orderId = order?.id ?? 0;
  const supplierId = order?.supplier_id ?? 0;

  // ---- queries ----
  const {
    data: orderDetail,
    isLoading: isLoadingDetail,
    isFetched,
  } = useQuery(
    useGetSupplierOrderQueryOptions(supplierId, orderId, {
      enabled: open && orderId > 0 && supplierId > 0,
    }),
  );

  const { data: suppliersList, isLoading: isLoadingSuppliers } = useQuery(
    useGetSuppliersListQueryOptions(),
  );

  const { mutate: updateOrder, isPending } = useMutation(
    useUpdateSupplierOrderMutationOptions(),
  );

  const handleClose = useCallback(() => {
    if (isPending) return;
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

  const detailNotAvailable = isFetched && !orderDetail && orderId > 0;

  // ---- form ----
  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: EMPTY_FORM,
  });

  const { fields } = useFieldArray({
    control: form.control,
    name: "clothes",
  });

  const watchedClothes = useWatch({ control: form.control, name: "clothes" });

  const totals = useMemo(() => {
    const list = watchedClothes ?? [];
    const total = list.reduce((s, c) => s + (Number(c?.price) || 0), 0);
    const paid = list.reduce((s, c) => s + (Number(c?.payment) || 0), 0);
    return { total_amount: total, payment_amount: paid, remaining_payment: total - paid };
  }, [watchedClothes]);

  useEffect(() => {
    if (open && orderDetail) {
      form.reset(mapDetailToForm(orderDetail));
    }
  }, [orderDetail, open, form]);

  // ---- submit ----
  const onSubmit = useCallback(
    (values: FormValues) => {
      if (!order) return;

      const clothes = (values.clothes ?? []).map((c) => {
        const price = Number(c.price) || 0;
        const payment = Number(c.payment) || 0;
        return {
          cloth_id: c.cloth_id,
          price,
          payment,
          remaining: price - payment,
          notes: c.notes?.trim() || null,
          category_id: c.category_id ? Number(c.category_id) : undefined,
          subcategory_ids: c.subcategory_id ? [Number(c.subcategory_id)] : undefined,
        };
      });

      const firstCloth = values.clothes?.[0];
      const payload: TUpdateSupplierOrderRequest = {
        supplier_id: Number(values.supplier_id),
        category_id: firstCloth?.category_id ? Number(firstCloth.category_id) : 0,
        subcategory_id: firstCloth?.subcategory_id ? Number(firstCloth.subcategory_id) : 0,
        branch_id: Number(values.branch_id),
        order_number: values.order_number.trim(),
        type: values.type?.trim() || undefined,
        order_date: values.order_date?.slice(0, 10) ?? "",
        status: values.status?.trim() || undefined,
        total_amount: totals.total_amount,
        payment_amount: totals.payment_amount,
        remaining_payment: totals.remaining_payment,
        notes: values.notes?.trim() || null,
        clothes,
      };

      updateOrder(
        { id: order.id, data: payload },
        {
          onSuccess: () => {
            toast.success("تم تحديث الطلبية بنجاح");
            handleClose();
          },
          onError: (err: { message?: string }) => {
            toast.error("حدث خطأ أثناء تحديث الطلبية", {
              description: err?.message,
            });
          },
        },
      );
    },
    [order, totals, updateOrder, handleClose],
  );

  const showLoader = isLoadingDetail || isLoadingSuppliers;

  if (!open) return null;

  // ---- render ----
  return (
    <div
      className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      dir="rtl"
      onClick={handleClose}
      role="presentation"
    >
      <div
        className="bg-white rounded-2xl w-full max-w-2xl max-h-[92vh] overflow-hidden shadow-lg flex flex-col"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-supplier-order-title"
      >
        <div className="flex items-start justify-between gap-3 px-6 pt-6 pb-3 shrink-0 border-b border-gray-100">
          <div>
            <h3
              id="edit-supplier-order-title"
              className="font-bold text-lg text-gray-800"
            >
              تحديث طلبية مورد
            </h3>
            <p className="text-xs text-gray-500 mt-1 leading-relaxed">
              تعديل بيانات الطلبية والقطع
              {order && (
                <span className="block mt-1 font-medium text-gray-700 tabular-nums" dir="ltr">
                  #{toEnglishNumerals(order.id)}
                  {order.order_number ? ` · ${order.order_number}` : ""}
                </span>
              )}
            </p>
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
          {showLoader && !orderDetail ? (
            <div className="flex flex-col items-center justify-center py-14 gap-3 text-gray-500">
              <Loader2 className="h-9 w-9 animate-spin text-blue-600" />
              <p className="text-sm">جاري تحميل بيانات الطلبية...</p>
            </div>
          ) : detailNotAvailable ? (
            <div className="rounded-xl border border-gray-200 bg-gray-50/80 py-10 px-4 text-center text-sm text-gray-600">
              تعذر تحميل تفاصيل الطلبية. التعديل غير متاح.
            </div>
          ) : (
            <Form {...form}>
              <form
                onSubmit={form.handleSubmit(onSubmit)}
                className="space-y-3"
              >
              {/* Supplier */}
              <FormField
                control={form.control}
                name="supplier_id"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className={LABEL_PROJECT}>المورد</FormLabel>
                    <Select
                      onValueChange={field.onChange}
                      value={field.value}
                      disabled={isLoadingSuppliers || isPending}
                    >
                      <FormControl>
                        <SelectTrigger className={cn(INPUT_PROJECT_CLASS)}>
                          <SelectValue placeholder="اختر المورد" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        {isLoadingSuppliers ? (
                          <div className="flex items-center justify-center p-2">
                            <Loader2 className="h-4 w-4 animate-spin" />
                          </div>
                        ) : (
                          suppliersList?.map((s) => (
                            <SelectItem key={s.id} value={String(s.id)}>
                              {s.name}
                            </SelectItem>
                          ))
                        )}
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />

              {/* Branch */}
              <FormField
                control={form.control}
                name="branch_id"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className={LABEL_PROJECT}>الفرع</FormLabel>
                    <FormControl>
                      <BranchesSelect
                        value={field.value}
                        onChange={field.onChange}
                        disabled={isPending}
                        className={cn(INPUT_PROJECT_CLASS)}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              {/* Order number / type */}
              <div className="grid grid-cols-2 gap-3">
                <FormField
                  control={form.control}
                  name="order_number"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={LABEL_PROJECT}>رقم الطلبية</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="SO-20260201-0001"
                          className={cn(INPUT_PROJECT_CLASS)}
                          {...field}
                          value={field.value ?? ""}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="type"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={LABEL_PROJECT}>نوع الطلبية</FormLabel>
                      <Select
                        onValueChange={field.onChange}
                        value={field.value ?? "fabric"}
                        disabled={isPending}
                      >
                        <FormControl>
                          <SelectTrigger className={cn(INPUT_PROJECT_CLASS)}>
                            <SelectValue placeholder="نوع الطلبية" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          <SelectItem value="fabric">قماش</SelectItem>
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              {/* Date / Status */}
              <div className="grid grid-cols-2 gap-3">
                <FormField
                  control={form.control}
                  name="order_date"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={LABEL_PROJECT}>تاريخ الطلبية</FormLabel>
                      <FormControl>
                        <CustomCalendar
                          value={field.value}
                          onChange={field.onChange}
                          placeholder="اختر التاريخ"
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="status"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={LABEL_PROJECT}>حالة الطلبية</FormLabel>
                      <Select
                        onValueChange={field.onChange}
                        value={field.value ?? "pending"}
                        disabled={isPending}
                      >
                        <FormControl>
                          <SelectTrigger className={cn(INPUT_PROJECT_CLASS)}>
                            <SelectValue placeholder="الحالة" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {ORDER_STATUS_OPTIONS.map((opt) => (
                            <SelectItem key={opt.value} value={opt.value}>
                              {opt.label}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              {/* Computed totals (read-only) */}
              <div className="grid grid-cols-3 gap-3 rounded-xl border border-blue-100 bg-gray-50/80 p-3">
                <FormItem>
                  <FormLabel className={cn(LABEL_PROJECT)}>الإجمالي (محسوب)</FormLabel>
                  <Input
                    readOnly
                    value={toEnglishNumerals(totals.total_amount)}
                    className={cn(INPUT_PROJECT_CLASS, "bg-gray-100 tabular-nums")}
                    dir="ltr"
                  />
                </FormItem>
                <FormItem>
                  <FormLabel className={cn(LABEL_PROJECT)}>المدفوع (محسوب)</FormLabel>
                  <Input
                    readOnly
                    value={toEnglishNumerals(totals.payment_amount)}
                    className={cn(INPUT_PROJECT_CLASS, "bg-gray-100 tabular-nums")}
                    dir="ltr"
                  />
                </FormItem>
                <FormItem>
                  <FormLabel className={cn(LABEL_PROJECT)}>المتبقي (محسوب)</FormLabel>
                  <Input
                    readOnly
                    value={toEnglishNumerals(totals.remaining_payment)}
                    className={cn(INPUT_PROJECT_CLASS, "bg-gray-100 tabular-nums")}
                    dir="ltr"
                  />
                </FormItem>
              </div>

              {/* Notes */}
              <FormField
                control={form.control}
                name="notes"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className={LABEL_PROJECT}>ملاحظات (اختياري)</FormLabel>
                    <FormControl>
                      <Input
                        placeholder="ملاحظات..."
                        className={cn(INPUT_PROJECT_CLASS)}
                        {...field}
                        value={field.value ?? ""}
                        disabled={isPending}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              {/* Clothes */}
              {fields.length > 0 && (
                <div className="space-y-2 pt-1 border-t border-gray-100">
                  <FormLabel className="text-sm font-semibold text-gray-800">
                    القطع
                  </FormLabel>
                  <div className="space-y-3">
                    {fields.map((field, index) => (
                      <div
                        key={field.id}
                        className="grid grid-cols-2 gap-3 p-3.5 rounded-xl border border-gray-200 bg-white shadow-sm"
                      >
                        <span className="col-span-2 text-xs font-semibold text-gray-600">
                          قطعة #{toEnglishNumerals(index + 1)}
                        </span>

                        <FormField
                          control={form.control}
                          name={`clothes.${index}.price`}
                          render={({ field: f }) => (
                            <FormItem>
                              <FormLabel className={LABEL_PROJECT}>السعر</FormLabel>
                              <FormControl>
                                <Input
                                  type="number"
                                  min={0}
                                  className={cn(INPUT_PROJECT_CLASS)}
                                  value={f.value ?? ""}
                                  onChange={(e) => {
                                    const v = e.target.value.replace(
                                      /[^0-9.]/g,
                                      "",
                                    );
                                    f.onChange(v === "" ? 0 : Number(v) || 0);
                                  }}
                                  disabled={isPending}
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name={`clothes.${index}.payment`}
                          render={({ field: f }) => (
                            <FormItem>
                              <FormLabel className={LABEL_PROJECT}>المدفوع</FormLabel>
                              <FormControl>
                                <Input
                                  type="number"
                                  min={0}
                                  className={cn(INPUT_PROJECT_CLASS)}
                                  value={f.value ?? ""}
                                  onChange={(e) => {
                                    const v = e.target.value.replace(
                                      /[^0-9.]/g,
                                      "",
                                    );
                                    f.onChange(v === "" ? 0 : Number(v) || 0);
                                  }}
                                  disabled={isPending}
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name={`clothes.${index}.category_id`}
                          render={({ field: f }) => (
                            <FormItem>
                              <FormLabel className={LABEL_PROJECT}>قسم (اختياري)</FormLabel>
                              <FormControl>
                                <CategoriesSelect
                                  value={f.value ?? ""}
                                  onChange={(id) => {
                                    f.onChange(id);
                                    form.setValue(
                                      `clothes.${index}.subcategory_id`,
                                      "",
                                    );
                                  }}
                                  disabled={isPending}
                                  className={cn(INPUT_PROJECT_CLASS)}
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name={`clothes.${index}.subcategory_id`}
                          render={({ field: f }) => (
                            <FormItem>
                              <FormLabel className={LABEL_PROJECT}>
                                قسم فرعي (اختياري)
                              </FormLabel>
                              <FormControl>
                                <SubcategoriesSelect
                                  value={f.value ?? ""}
                                  onChange={f.onChange}
                                  category_id={
                                    form.watch(`clothes.${index}.category_id`)
                                      ? Number(form.watch(`clothes.${index}.category_id`))
                                      : undefined
                                  }
                                  disabled={
                                    isPending ||
                                    !form.watch(`clothes.${index}.category_id`)
                                  }
                                  className={cn(INPUT_PROJECT_CLASS)}
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name={`clothes.${index}.notes`}
                          render={({ field: f }) => (
                            <FormItem className="col-span-2">
                              <FormLabel className={LABEL_PROJECT}>
                                ملاحظات (اختياري)
                              </FormLabel>
                              <FormControl>
                                <Input
                                  placeholder="ملاحظات للقطعة"
                                  className={cn(INPUT_PROJECT_CLASS)}
                                  {...f}
                                  value={f.value ?? ""}
                                  disabled={isPending}
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                      </div>
                    ))}
                  </div>
                </div>
              )}

              <div className="flex gap-3 pt-4 border-t border-gray-100">
                <button
                  type="submit"
                  disabled={isPending || !orderDetail}
                  className="flex-1 py-2.5 bg-blue-700 text-white rounded-xl text-sm font-bold cursor-pointer hover:bg-blue-800 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isPending ? "جاري التحديث..." : "تحديث الطلبية"}
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
            </form>
          </Form>
          )}
        </div>

        {(detailNotAvailable || (showLoader && !orderDetail)) && (
          <div className="shrink-0 px-6 pb-6 pt-0 border-t border-gray-100">
            <button
              type="button"
              onClick={handleClose}
              className="w-full py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm cursor-pointer hover:bg-gray-200 mt-3"
            >
              إغلاق
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
