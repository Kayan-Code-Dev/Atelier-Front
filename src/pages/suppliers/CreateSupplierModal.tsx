import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Switch } from "@/components/ui/switch";
import { useForm, useWatch, useFieldArray } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useMutation } from "@tanstack/react-query";
import { useCallback, useEffect } from "react";

import {
  useCreateSupplierMinimalMutationOptions,
  useCreateSupplierMutationOptions,
} from "@/api/v2/suppliers/suppliers.hooks";
import {
  TCreateSupplierRequest,
  TCreateSupplierClothItem,
} from "@/api/v2/suppliers/suppliers.types";
import { toast } from "sonner";
import { CustomCalendar } from "@/components/custom/CustomCalendar";
import { CategoriesSelect } from "@/components/custom/CategoriesSelect";
import { SubcategoriesSelect } from "@/components/custom/SubcategoriesSelect";
import { EntitySelect } from "@/components/custom/EntitySelect";

const clothItemSchema = z.object({
  code: z.string().min(1, "كود الصنف مطلوب"),
  category_id: z.string().min(1, "قسم المنتجات مطلوب"),
  subcategory_id: z.string().min(1, "قسم المنتجات الفرعي مطلوب"),
  entity_type: z.enum(["branch", "factory", "workshop"], {
    required_error: "نوع المكان مطلوب",
  }),
  entity_id: z.string().min(1, "المكان مطلوب"),
  price: z.number().min(0, "السعر يجب أن يكون ≥ 0"),
  payment: z.number().min(0, "المدفوع يجب أن يكون ≥ 0"),
});

const formSchema = z
  .object({
    name: z.string().min(1, { message: "اسم المورد مطلوب" }),
    code: z.string().min(1, { message: "كود المورد مطلوب" }),
    phone: z.string().optional(),
    address: z.string().optional(),
    supplier_notes: z.string().optional(),
    add_order: z.boolean().default(false),
    order_date: z.string().optional(),
    total_amount: z.number().optional(),
    payment_amount: z.number().optional(),
    remaining_payment: z.number().optional(),
    notes: z.string().optional(),
    clothes: z.array(clothItemSchema).optional(),
  })
  .superRefine((data, ctx) => {
    if (!data.add_order) return;
    if (!data.order_date?.length)
      ctx.addIssue({
        code: "custom",
        message: "تاريخ الطلبية مطلوب",
        path: ["order_date"],
      });
    if (data.total_amount == null || data.total_amount < 0)
      ctx.addIssue({
        code: "custom",
        message: "الإجمالي ≥ 0",
        path: ["total_amount"],
      });
    if (data.payment_amount == null || data.payment_amount < 0)
      ctx.addIssue({
        code: "custom",
        message: "المدفوع ≥ 0",
        path: ["payment_amount"],
      });
    if (data.remaining_payment == null || data.remaining_payment < 0)
      ctx.addIssue({
        code: "custom",
        message: "المتبقي ≥ 0",
        path: ["remaining_payment"],
      });
    if (!data.clothes?.length)
      ctx.addIssue({
        code: "custom",
        message: "يجب إضافة صنف واحد على الأقل",
        path: ["clothes"],
      });
    const hasBranch = data.clothes?.some(
      (c) => c.entity_type === "branch" && c.entity_id,
    );
    if (!hasBranch)
      ctx.addIssue({
        code: "custom",
        message: "يجب اختيار الفرع في صنف واحد على الأقل",
        path: ["clothes"],
      });
    if (
      data.total_amount != null &&
      data.payment_amount != null &&
      data.remaining_payment != null &&
      data.remaining_payment !== data.total_amount - data.payment_amount
    ) {
      ctx.addIssue({
        code: "custom",
        message: "المتبقي يجب أن يساوي (الإجمالي - المدفوع)",
        path: ["remaining_payment"],
      });
    }
  });

type FormValues = z.infer<typeof formSchema>;

const defaultClothItem = {
  code: "",
  category_id: "",
  subcategory_id: "",
  entity_type: "branch" as const,
  entity_id: "",
  price: 0,
  payment: 0,
};

const inputProjectClass =
  "w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50";

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function CreateSupplierModal({ open, onOpenChange }: Props) {
  const mutateMinimal = useMutation(useCreateSupplierMinimalMutationOptions());
  const mutateWithOrder = useMutation(useCreateSupplierMutationOptions());

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      code: "",
      phone: "",
      address: "",
      supplier_notes: "",
      add_order: false,
      order_date: "",
      total_amount: 0,
      payment_amount: 0,
      remaining_payment: 0,
      notes: "",
      clothes: [],
    },
  });

  const addOrder = useWatch({ control: form.control, name: "add_order" });
  const clothes = useWatch({ control: form.control, name: "clothes" });

  const { fields, append, remove } = useFieldArray({
    control: form.control,
    name: "clothes",
  });

  useEffect(() => {
    if (!addOrder || !Array.isArray(clothes) || clothes.length === 0) return;
    const total = clothes.reduce((sum, c) => sum + (Number(c?.price) || 0), 0);
    const payment = clothes.reduce(
      (sum, c) => sum + (Number(c?.payment) || 0),
      0,
    );
    form.setValue("total_amount", total);
    form.setValue("payment_amount", payment);
    form.setValue("remaining_payment", Math.max(0, total - payment));
  }, [addOrder, clothes, form]);

  useEffect(() => {
    if (addOrder && fields.length === 0) {
      append(defaultClothItem);
    }
  }, [addOrder, fields.length, append]);

  const isPending = mutateMinimal.isPending || mutateWithOrder.isPending;

  const handleOpenChange = useCallback(
    (next: boolean) => {
      if (!next) {
        form.reset({
          name: "",
          code: "",
          phone: "",
          address: "",
          supplier_notes: "",
          add_order: false,
          order_date: "",
          total_amount: 0,
          payment_amount: 0,
          remaining_payment: 0,
          notes: "",
          clothes: [],
        });
      }
      onOpenChange(next);
    },
    [form, onOpenChange],
  );

  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") handleOpenChange(false);
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [open, handleOpenChange]);

  const onSubmit = (values: FormValues) => {
    if (!values.add_order) {
      mutateMinimal.mutate(
        {
          name: values.name,
          code: values.code,
          ...(values.phone?.trim() && { phone: values.phone.trim() }),
          ...(values.address?.trim() && { address: values.address.trim() }),
        },
        {
          onSuccess: () => {
            toast.success("تم إنشاء المورد بنجاح");
            handleOpenChange(false);
          },
          onError: (error: { message?: string }) => {
            toast.error("حدث خطأ أثناء إنشاء المورد", {
              description: error?.message,
            });
          },
        },
      );
      return;
    }

    const clothesPayload: TCreateSupplierClothItem[] = (values.clothes ?? []).map(
      (c) => ({
        code: c.code,
        category_id: c.category_id ? Number(c.category_id) : undefined,
        subcategory_id: c.subcategory_id ? Number(c.subcategory_id) : undefined,
        entity_type: c.entity_type as "branch" | "factory" | "workshop",
        entity_id: Number(c.entity_id),
        price: Number(c.price),
      }),
    );

    const firstCloth = values.clothes?.[0];
    const firstBranchCloth = (values.clothes ?? []).find(
      (c) => c.entity_type === "branch",
    );
    const branchId = firstBranchCloth?.entity_id
      ? Number(firstBranchCloth.entity_id)
      : 0;

    const requestData: TCreateSupplierRequest = {
      name: values.name,
      code: values.code,
      ...(values.phone?.trim() && { phone: values.phone.trim() }),
      ...(values.address?.trim() && { address: values.address.trim() }),
      category_id: firstCloth?.category_id ? Number(firstCloth.category_id) : 0,
      subcategory_id: firstCloth?.subcategory_id
        ? Number(firstCloth.subcategory_id)
        : 0,
      branch_id: branchId,
      order_date: values.order_date!,
      total_amount: Number(values.total_amount),
      payment_amount: Number(values.payment_amount),
      remaining_payment: Number(values.remaining_payment),
      notes: values.notes || undefined,
      clothes: clothesPayload,
    };

    mutateWithOrder.mutate(requestData, {
      onSuccess: () => {
        toast.success("تم إنشاء المورد والطلبية بنجاح");
        handleOpenChange(false);
      },
      onError: (error: { message?: string }) => {
        toast.error("حدث خطأ أثناء إنشاء المورد", {
          description: error?.message,
        });
      },
    });
  };

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      dir="rtl"
      onClick={() => !isPending && handleOpenChange(false)}
      role="presentation"
    >
      <div
        className={`bg-white rounded-2xl w-full p-6 shadow-lg ${
          addOrder
            ? "max-w-2xl max-h-[92vh] overflow-y-auto"
            : "max-w-md"
        }`}
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-supplier-title"
      >
        <div className="flex items-center justify-between mb-5">
          <h3
            id="create-supplier-title"
            className="font-bold text-lg text-gray-800"
          >
            إضافة مورد جديد
          </h3>
          <button
            type="button"
            onClick={() => !isPending && handleOpenChange(false)}
            className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 cursor-pointer disabled:opacity-50"
            disabled={isPending}
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-lg" />
          </button>
        </div>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-xs text-gray-500 font-normal">
                      اسم المورد
                    </FormLabel>
                    <FormControl>
                      <input
                        type="text"
                        placeholder="الاسم الكامل أو اسم الشركة"
                        className={inputProjectClass}
                        disabled={isPending}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="code"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-xs text-gray-500 font-normal">
                      كود المورد <span className="text-red-400">*</span>
                    </FormLabel>
                    <FormControl>
                      <input
                        type="text"
                        placeholder="مثال: SUP-001"
                        className={inputProjectClass}
                        disabled={isPending}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="phone"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-xs text-gray-500 font-normal">
                      رقم الهاتف
                    </FormLabel>
                    <FormControl>
                      <input
                        type="text"
                        placeholder="01xxxxxxxxxx"
                        className={inputProjectClass}
                        disabled={isPending}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="address"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-xs text-gray-500 font-normal">
                      العنوان
                    </FormLabel>
                    <FormControl>
                      <input
                        type="text"
                        placeholder="المدينة - الحي"
                        className={inputProjectClass}
                        disabled={isPending}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="supplier_notes"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-xs text-gray-500 font-normal">
                      ملاحظات
                    </FormLabel>
                    <FormControl>
                      <input
                        type="text"
                        placeholder="ملاحظات إضافية..."
                        className={inputProjectClass}
                        disabled={isPending}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <FormField
              control={form.control}
              name="add_order"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between rounded-xl border border-blue-100 bg-blue-50/40 px-4 py-3 gap-3">
                  <div className="space-y-0.5 text-right">
                    <FormLabel className="text-sm font-semibold text-gray-800">
                      إنشاء طلبية مع المورد
                    </FormLabel>
                    <p className="text-xs text-gray-500">
                      توسيع النموذج لإدخال تاريخ الطلبية والأصناف والفرع
                    </p>
                  </div>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                      disabled={isPending}
                    />
                  </FormControl>
                </FormItem>
              )}
            />

            {addOrder && (
              <div className="border border-blue-100 rounded-xl p-4 space-y-4 bg-gray-50/50">
                <p className="text-xs font-bold text-blue-900 border-b border-blue-100 pb-2">
                  بيانات الطلبية
                </p>
                <FormField
                  control={form.control}
                  name="order_date"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-xs text-gray-500 font-normal">
                        تاريخ الطلبية
                      </FormLabel>
                      <FormControl>
                        <CustomCalendar
                          value={field.value ?? ""}
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
                  name="notes"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-xs text-gray-500 font-normal">
                        ملاحظات الطلبية (اختياري)
                      </FormLabel>
                      <FormControl>
                        <input
                          type="text"
                          placeholder="ملاحظات..."
                          className={inputProjectClass}
                          disabled={isPending}
                          {...field}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <div className="border-t border-gray-200 pt-4">
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-sm font-semibold text-gray-800">
                      أصناف الطلبية
                    </h3>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => append(defaultClothItem)}
                      disabled={isPending}
                    >
                      إضافة صنف
                    </Button>
                  </div>
                  {fields.map((field, index) => (
                    <div
                      key={field.id}
                      className="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-xl border border-gray-200 bg-white mb-3"
                    >
                      <FormField
                        control={form.control}
                        name={`clothes.${index}.code`}
                        render={({ field: f }) => (
                          <FormItem>
                            <FormLabel className="text-xs text-gray-500">
                              كود الصنف
                            </FormLabel>
                            <FormControl>
                              <input
                                type="text"
                                className={inputProjectClass}
                                placeholder="CLT-001"
                                disabled={isPending}
                                {...f}
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
                            <FormLabel className="text-xs text-gray-500">
                              قسم المنتجات
                            </FormLabel>
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
                            <FormLabel className="text-xs text-gray-500">
                              القسم الفرعي
                            </FormLabel>
                            <FormControl>
                              <SubcategoriesSelect
                                value={f.value ?? ""}
                                onChange={f.onChange}
                                category_id={
                                  form.watch(`clothes.${index}.category_id`)
                                    ? Number(
                                        form.watch(
                                          `clothes.${index}.category_id`,
                                        ),
                                      )
                                    : undefined
                                }
                                disabled={
                                  isPending ||
                                  !form.watch(`clothes.${index}.category_id`)
                                }
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                      <FormField
                        control={form.control}
                        name={`clothes.${index}.price`}
                        render={({ field: f }) => (
                          <FormItem>
                            <FormLabel className="text-xs text-gray-500">
                              السعر
                            </FormLabel>
                            <FormControl>
                              <input
                                type="text"
                                inputMode="decimal"
                                className={inputProjectClass}
                                placeholder="0"
                                value={f.value ?? ""}
                                onChange={(e) => {
                                  const val = e.target.value.replace(
                                    /[^0-9.]/g,
                                    "",
                                  );
                                  f.onChange(
                                    val === "" ? 0 : Number(val) || 0,
                                  );
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
                            <FormLabel className="text-xs text-gray-500">
                              المدفوع
                            </FormLabel>
                            <FormControl>
                              <input
                                type="text"
                                inputMode="decimal"
                                className={inputProjectClass}
                                placeholder="0"
                                value={f.value ?? ""}
                                onChange={(e) => {
                                  const val = e.target.value.replace(
                                    /[^0-9.]/g,
                                    "",
                                  );
                                  f.onChange(
                                    val === "" ? 0 : Number(val) || 0,
                                  );
                                }}
                                disabled={isPending}
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                      <div className="sm:col-span-2">
                        <EntitySelect
                          mode="form"
                          control={form.control}
                          entityTypeName={
                            `clothes.${index}.entity_type` as const
                          }
                          entityIdName={`clothes.${index}.entity_id` as const}
                          entityTypeLabel="نوع المكان"
                          entityIdLabel="المكان"
                          disabled={isPending}
                        />
                      </div>
                      {fields.length > 1 && (
                        <div className="sm:col-span-2">
                          <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            onClick={() => remove(index)}
                            disabled={isPending}
                          >
                            حذف الصنف
                          </Button>
                        </div>
                      )}
                    </div>
                  ))}
                  {form.formState.errors.clothes?.message && (
                    <p className="text-sm text-destructive">
                      {form.formState.errors.clothes.message}
                    </p>
                  )}
                </div>

                <div className="grid grid-cols-3 gap-3 border-t border-gray-200 pt-4">
                  <FormField
                    control={form.control}
                    name="total_amount"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-gray-500">
                          المجموع
                        </FormLabel>
                        <FormControl>
                          <input
                            type="text"
                            readOnly
                            className={`${inputProjectClass} bg-gray-100`}
                            value={field.value ?? ""}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name="payment_amount"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-gray-500">
                          المدفوع
                        </FormLabel>
                        <FormControl>
                          <input
                            type="text"
                            readOnly
                            className={`${inputProjectClass} bg-gray-100`}
                            value={field.value ?? ""}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name="remaining_payment"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-xs text-gray-500">
                          المتبقي
                        </FormLabel>
                        <FormControl>
                          <input
                            type="text"
                            readOnly
                            className={`${inputProjectClass} bg-gray-100`}
                            value={field.value ?? ""}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </div>
              </div>
            )}

            <div className="flex gap-3 mt-5">
              <button
                type="submit"
                disabled={isPending}
                className="flex-1 py-2.5 bg-blue-700 text-white rounded-xl text-sm font-bold cursor-pointer hover:bg-blue-800 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isPending
                  ? "جاري الحفظ..."
                  : addOrder
                    ? "إنشاء المورد والطلبية"
                    : "حفظ المورد"}
              </button>
              <button
                type="button"
                disabled={isPending}
                onClick={() => handleOpenChange(false)}
                className="flex-1 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm cursor-pointer whitespace-nowrap hover:bg-gray-200 disabled:opacity-50"
              >
                إلغاء
              </button>
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
}
