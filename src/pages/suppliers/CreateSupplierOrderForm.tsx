import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Loader2 } from "lucide-react";
import { useEffect } from "react";
import { useForm, useFieldArray, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useNavigate, useSearchParams } from "react-router";
import { toast } from "sonner";

import {
  useCreateSupplierOrderMutationOptions,
  useCreateSupplierMinimalMutationOptions,
  useGetSuppliersListQueryOptions,
} from "@/api/v2/suppliers/suppliers.hooks";
import {
  TCreateSupplierOrderRequest,
  TCreateSupplierOrderClothItem,
} from "@/api/v2/suppliers/suppliers.types";
import { CustomCalendar } from "@/components/custom/CustomCalendar";
import { CategoriesSelect } from "@/components/custom/CategoriesSelect";
import { SubcategoriesSelect } from "@/components/custom/SubcategoriesSelect";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { toEnglishNumerals } from "@/utils/formatDate";
import { cn } from "@/lib/utils";

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const INPUT_PROJECT_CLASS =
  "w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50";

const STATUS_OPTIONS = [
  { value: "ready_for_rent", label: "متوفر" },
  { value: "rented", label: "محجوز" },
  { value: "damaged", label: "تالف" },
  { value: "burned", label: "محترق" },
  { value: "scratched", label: "مخدوش" },
  { value: "repairing", label: "قيد الصيانة" },
  { value: "die", label: "ميت" },
] as const;

const DEFAULT_CLOTH_ITEM = {
  code: "",
  breast_size: "",
  waist_size: "",
  sleeve_size: "",
  notes: null as string | null,
  status: "ready_for_rent",
  entity_type: "branch" as const,
  entity_id: "",
  category_id: "",
  subcategory_id: "",
  price: 0,
  payment: 0,
};

// ---------------------------------------------------------------------------
// Schema
// ---------------------------------------------------------------------------

const clothItemSchema = z.object({
  code: z.string().min(1, "كود الصنف مطلوب"),
  breast_size: z.string().optional(),
  waist_size: z.string().optional(),
  sleeve_size: z.string().optional(),
  notes: z.string().optional().nullable(),
  status: z.string().optional(),
  entity_type: z.enum(["branch", "factory", "workshop"], {
    required_error: "نوع المكان مطلوب",
  }),
  entity_id: z.string().min(1, "المكان مطلوب"),
  category_id: z.string().min(1, "قسم المنتجات مطلوب"),
  subcategory_id: z.string().min(1, "قسم المنتجات الفرعي مطلوب"),
  price: z.number().min(0, "السعر يجب أن يكون ≥ 0"),
  payment: z.number().min(0, "المدفوع يجب أن يكون ≥ 0"),
});

const formSchema = z
  .object({
    add_new_supplier: z.boolean().default(false),
    supplier_id: z.string().optional(),
    supplier_name: z.string().optional(),
    supplier_code: z.string().optional(),
    supplier_phone: z.string().optional(),
    supplier_address: z.string().optional(),
    order_date: z.string().min(1, { message: "تاريخ الطلبية مطلوب" }),
    total_amount: z.number().min(0),
    payment_amount: z.number().min(0),
    remaining_payment: z.number().min(0),
    notes: z.string().optional().nullable(),
    clothes: z
      .array(clothItemSchema)
      .min(1, { message: "يجب إضافة صنف واحد على الأقل" }),
  })
  .superRefine((data, ctx) => {
    if (data.add_new_supplier) {
      if (!data.supplier_name?.trim())
        ctx.addIssue({
          code: "custom",
          message: "اسم المورد مطلوب",
          path: ["supplier_name"],
        });
      if (!data.supplier_code?.trim())
        ctx.addIssue({
          code: "custom",
          message: "كود المورد مطلوب",
          path: ["supplier_code"],
        });
    } else {
      if (!data.supplier_id?.trim())
        ctx.addIssue({
          code: "custom",
          message: "المورد مطلوب",
          path: ["supplier_id"],
        });
    }
  });

export type FormValues = z.infer<typeof formSchema>;

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export type CreateSupplierOrderFormProps = {
  mode?: "page" | "modal";
  onSuccess?: () => void;
  onCancel?: () => void;
  onPendingChange?: (pending: boolean) => void;
  initialSupplierId?: string;
};

export function CreateSupplierOrderForm({
  mode = "page",
  onSuccess,
  onCancel,
  onPendingChange,
  initialSupplierId: initialSupplierIdProp,
}: CreateSupplierOrderFormProps) {
  const isModal = mode === "modal";
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const initialSupplierId =
    initialSupplierIdProp ?? searchParams.get("supplier_id") ?? "";

  // ---- mutations ----
  const { mutate: createSupplierMinimal, isPending: isCreatingSupplier } =
    useMutation(useCreateSupplierMinimalMutationOptions());
  const { mutate: createSupplierOrder, isPending: isCreatingOrder } =
    useMutation(useCreateSupplierOrderMutationOptions());
  const isPending = isCreatingSupplier || isCreatingOrder;

  useEffect(() => {
    onPendingChange?.(isPending);
  }, [isPending, onPendingChange]);

  // ---- queries ----
  const { data: suppliersList, isLoading: isLoadingSuppliers } = useQuery(
    useGetSuppliersListQueryOptions(),
  );

  // ---- form ----
  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      add_new_supplier: false,
      supplier_id: initialSupplierId,
      supplier_name: "",
      supplier_code: "",
      supplier_phone: "",
      supplier_address: "",
      order_date: "",
      total_amount: 0,
      payment_amount: 0,
      remaining_payment: 0,
      notes: "",
      clothes: [DEFAULT_CLOTH_ITEM],
    },
  });

  const addNewSupplier = useWatch({
    control: form.control,
    name: "add_new_supplier",
  });

  const { fields, append, remove } = useFieldArray({
    control: form.control,
    name: "clothes",
  });

  const clothes = useWatch({ control: form.control, name: "clothes" });

  // Sync initial supplier id from URL
  useEffect(() => {
    if (initialSupplierId && form.getValues("supplier_id") !== initialSupplierId) {
      form.setValue("supplier_id", initialSupplierId);
    }
  }, [initialSupplierId, form]);

  // Auto-compute totals from clothes
  useEffect(() => {
    if (!Array.isArray(clothes) || clothes.length === 0) return;
    const total = clothes.reduce((s, c) => s + (Number(c?.price) || 0), 0);
    const payment = clothes.reduce((s, c) => s + (Number(c?.payment) || 0), 0);
    const remaining = clothes.reduce(
      (s, c) =>
        s + Math.max(0, (Number(c?.price) || 0) - (Number(c?.payment) || 0)),
      0,
    );
    form.setValue("total_amount", total);
    form.setValue("payment_amount", payment);
    form.setValue("remaining_payment", remaining);
  }, [clothes, form]);

  // ---- handlers ----
  const resetForm = () => {
    form.reset({
      add_new_supplier: false,
      supplier_id: initialSupplierId,
      supplier_name: "",
      supplier_code: "",
      supplier_phone: "",
      supplier_address: "",
      order_date: "",
      total_amount: 0,
      payment_amount: 0,
      remaining_payment: 0,
      notes: "",
      clothes: [DEFAULT_CLOTH_ITEM],
    });
  };

  const submitOrder = (supplierId: number, values: FormValues) => {
    const clothesPayload: TCreateSupplierOrderClothItem[] = values.clothes.map(
      (c) => {
        const price = Number(c.price) || 0;
        const payment = Number(c.payment) || 0;
        return {
          code: c.code,
          breast_size: c.breast_size || undefined,
          waist_size: c.waist_size || undefined,
          sleeve_size: c.sleeve_size || undefined,
          notes: c.notes ?? undefined,
          status: c.status || undefined,
          entity_type: "branch" as const,
          entity_id: Number(c.entity_id),
          category_id: c.category_id ? Number(c.category_id) : undefined,
          subcategory_ids: c.subcategory_id ? [Number(c.subcategory_id)] : undefined,
          price,
          payment,
          remaining: Math.max(0, price - payment),
        };
      },
    );

    const firstCloth = values.clothes[0];
    const payload: TCreateSupplierOrderRequest = {
      supplier_id: supplierId,
      category_id: firstCloth?.category_id ? Number(firstCloth.category_id) : 0,
      subcategory_id: firstCloth?.subcategory_id ? Number(firstCloth.subcategory_id) : 0,
      branch_id: firstCloth?.entity_id ? Number(firstCloth.entity_id) : 0,
      order_date: values.order_date?.slice(0, 10) ?? "",
      total_amount: clothesPayload.reduce((s, c) => s + (c.price ?? 0), 0),
      payment_amount: clothesPayload.reduce(
        (s, c) => s + (c.payment ?? 0),
        0,
      ),
      remaining_payment: clothesPayload.reduce(
        (s, c) => s + (c.remaining ?? 0),
        0,
      ),
      notes: values.notes ?? undefined,
      clothes: clothesPayload,
    };

    createSupplierOrder(payload, {
      onSuccess: () => {
        toast.success("تم إنشاء طلبية المورد بنجاح");
        resetForm();
        if (mode === "modal" && onSuccess) onSuccess();
        else navigate("/suppliers/orders");
      },
      onError: (err: { message?: string }) => {
        toast.error("حدث خطأ أثناء إنشاء طلبية المورد", {
          description: err?.message,
        });
      },
    });
  };

  const onSubmit = (values: FormValues) => {
    if (values.add_new_supplier) {
      createSupplierMinimal(
        {
          name: values.supplier_name!.trim(),
          code: values.supplier_code!.trim(),
          ...(values.supplier_phone?.trim() && {
            phone: values.supplier_phone.trim(),
          }),
          ...(values.supplier_address?.trim() && {
            address: values.supplier_address.trim(),
          }),
        },
        {
          onSuccess: (data) => {
            if (data?.id) {
              submitOrder(data.id, values);
            } else {
              toast.error("لم يتم الحصول على معرف المورد بعد الإنشاء");
            }
          },
          onError: (err: { message?: string }) => {
            toast.error("حدث خطأ أثناء إنشاء المورد", {
              description: err?.message,
            });
          },
        },
      );
    } else {
      submitOrder(Number(values.supplier_id), values);
    }
  };

  const handleCancel = () => {
    if (mode === "modal" && onCancel) onCancel();
    else navigate("/suppliers/orders");
  };

  // ---- render ----
  const formContent = (
    <Form {...form}>
      <form
        onSubmit={form.handleSubmit(onSubmit)}
        className={cn("space-y-4", isModal && "space-y-3")}
      >
        {/* Toggle: new / existing supplier */}
        <FormField
          control={form.control}
          name="add_new_supplier"
          render={({ field }) => (
            <FormItem
              className={cn(
                "flex flex-row items-center justify-between rounded-lg border p-4",
                isModal &&
                  "rounded-xl border-blue-100 bg-blue-50/40 px-4 py-3 gap-3",
              )}
            >
              <div className="space-y-0.5">
                <FormLabel
                  className={cn(
                    isModal ? "text-sm font-semibold text-gray-800" : "text-base",
                  )}
                >
                  إضافة مورد جديد لهذه الطلبية
                </FormLabel>
                <p
                  className={cn(
                    isModal ? "text-xs text-gray-500" : "text-sm text-muted-foreground",
                  )}
                >
                  عند التفعيل أدخل اسم وكود المورد الجديد بدل اختيار مورد موجود
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

        {/* Supplier selection / creation */}
        {!addNewSupplier ? (
          <FormField
            control={form.control}
            name="supplier_id"
            render={({ field }) => (
              <FormItem>
                <FormLabel
                  className={cn(isModal && "text-xs text-gray-500 font-normal")}
                >
                  المورد
                </FormLabel>
                <Select
                  onValueChange={field.onChange}
                  value={field.value}
                  disabled={isLoadingSuppliers || isPending}
                >
                  <FormControl>
                    <SelectTrigger
                      className={cn(isModal && INPUT_PROJECT_CLASS)}
                    >
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
        ) : (
          <div
            className={cn(
              "grid grid-cols-2 gap-4 rounded-lg border p-4",
              isModal && "rounded-xl border-blue-100 bg-gray-50/50",
            )}
          >
            {(
              [
                ["supplier_name", "اسم المورد", "اسم المورد"],
                ["supplier_code", "كود المورد", "كود المورد"],
                ["supplier_phone", "رقم المورد", "رقم الهاتف"],
                ["supplier_address", "الموقع", "عنوان / موقع المورد"],
              ] as const
            ).map(([name, label, placeholder]) => (
              <FormField
                key={name}
                control={form.control}
                name={name}
                render={({ field }) => (
                  <FormItem>
                    <FormLabel
                      className={cn(isModal && "text-xs text-gray-500 font-normal")}
                    >
                      {label}
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder={placeholder}
                        className={cn(isModal && INPUT_PROJECT_CLASS)}
                        {...field}
                        value={field.value ?? ""}
                        disabled={isPending}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            ))}
          </div>
        )}

        {/* Order date */}
        <FormField
          control={form.control}
          name="order_date"
          render={({ field }) => (
            <FormItem>
              <FormLabel
                className={cn(isModal && "text-xs text-gray-500 font-normal")}
              >
                تاريخ الطلبية
              </FormLabel>
              <FormControl>
                <CustomCalendar
                  value={field.value}
                  onChange={field.onChange}
                  placeholder="اختر تاريخ الطلبية"
                  disabled={isPending}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        {/* Notes */}
        <FormField
          control={form.control}
          name="notes"
          render={({ field }) => (
            <FormItem>
              <FormLabel
                className={cn(isModal && "text-xs text-gray-500 font-normal")}
              >
                ملاحظات (اختياري)
              </FormLabel>
              <FormControl>
                <Input
                  placeholder="ملاحظات..."
                  className={cn(isModal && INPUT_PROJECT_CLASS)}
                  {...field}
                  value={field.value ?? ""}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        {/* Clothes items */}
        <div className={cn("border-t pt-4", isModal && "border-gray-100")}>
          <div className="flex items-center justify-between mb-4">
            <h3
              className={cn(
                "text-sm font-medium",
                isModal && "font-semibold text-gray-800",
              )}
            >
              أصناف الطلبية
            </h3>
            <Button
              type="button"
              variant="outline"
              size="sm"
              className={cn(
                isModal && "rounded-lg border-gray-200 text-gray-700 hover:bg-gray-50",
              )}
              onClick={() => append(DEFAULT_CLOTH_ITEM)}
              disabled={isPending}
            >
              إضافة صنف
            </Button>
          </div>

          {fields.map((field, index) => (
            <div
              key={field.id}
              className={cn(
                "grid grid-cols-2 gap-4 p-4 rounded-lg border bg-muted/30 mb-4",
                isModal && "rounded-xl border-gray-200 bg-white",
              )}
            >
              <FormField
                control={form.control}
                name={`clothes.${index}.code`}
                render={({ field: f }) => (
                  <FormItem>
                    <FormLabel
                      className={cn(isModal && "text-xs text-gray-500 font-normal")}
                    >
                      كود الصنف
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder="CL-001"
                        className={cn(isModal && INPUT_PROJECT_CLASS)}
                        {...f}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name={`clothes.${index}.entity_id`}
                render={({ field: f }) => (
                  <FormItem>
                    <FormLabel
                      className={cn(isModal && "text-xs text-gray-500 font-normal")}
                    >
                      الفرع
                    </FormLabel>
                    <FormControl>
                      <BranchesSelect
                        value={f.value ?? ""}
                        onChange={f.onChange}
                        disabled={isPending}
                        className={cn(isModal && INPUT_PROJECT_CLASS)}
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
                    <FormLabel
                      className={cn(isModal && "text-xs text-gray-500 font-normal")}
                    >
                      قسم المنتجات
                    </FormLabel>
                    <FormControl>
                      <CategoriesSelect
                        value={f.value ?? ""}
                        onChange={(id) => {
                          f.onChange(id);
                          form.setValue(`clothes.${index}.subcategory_id`, "");
                        }}
                        disabled={isPending}
                        className={cn(isModal && INPUT_PROJECT_CLASS)}
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
                    <FormLabel
                      className={cn(isModal && "text-xs text-gray-500 font-normal")}
                    >
                      قسم المنتجات الفرعي
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
                        className={cn(isModal && INPUT_PROJECT_CLASS)}
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
                    <FormLabel
                      className={cn(isModal && "text-xs text-gray-500 font-normal")}
                    >
                      السعر
                    </FormLabel>
                    <FormControl>
                      <Input
                        className={cn(isModal && INPUT_PROJECT_CLASS)}
                        value={f.value ?? ""}
                        onChange={(e) => {
                          const v = e.target.value.replace(/[^0-9.]/g, "");
                          f.onChange(v === "" ? 0 : Number(v) || 0);
                        }}
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
                    <FormLabel
                      className={cn(isModal && "text-xs text-gray-500 font-normal")}
                    >
                      المدفوع
                    </FormLabel>
                    <FormControl>
                      <Input
                        className={cn(isModal && INPUT_PROJECT_CLASS)}
                        value={f.value ?? ""}
                        onChange={(e) => {
                          const v = e.target.value.replace(/[^0-9.]/g, "");
                          f.onChange(v === "" ? 0 : Number(v) || 0);
                        }}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name={`clothes.${index}.status`}
                render={({ field: f }) => (
                  <FormItem>
                    <FormLabel
                      className={cn(isModal && "text-xs text-gray-500 font-normal")}
                    >
                      الحالة
                    </FormLabel>
                    <Select
                      onValueChange={f.onChange}
                      value={f.value}
                      disabled={isPending}
                    >
                      <FormControl>
                        <SelectTrigger
                          className={cn(isModal && INPUT_PROJECT_CLASS)}
                        >
                          <SelectValue placeholder="الحالة" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        {STATUS_OPTIONS.map((opt) => (
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

              {(
                [
                  ["breast_size", "مقاس الصدر (اختياري)", "38"],
                  ["waist_size", "مقاس الخصر (اختياري)", "32"],
                  ["sleeve_size", "مقاس الكم (اختياري)", "34"],
                ] as const
              ).map(([name, label, placeholder]) => (
                <FormField
                  key={name}
                  control={form.control}
                  name={`clothes.${index}.${name}`}
                  render={({ field: f }) => (
                    <FormItem>
                      <FormLabel
                        className={cn(isModal && "text-xs text-gray-500 font-normal")}
                      >
                        {label}
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder={placeholder}
                          className={cn(isModal && INPUT_PROJECT_CLASS)}
                          {...f}
                          value={f.value ?? ""}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              ))}

              <FormField
                control={form.control}
                name={`clothes.${index}.notes`}
                render={({ field: f }) => (
                  <FormItem className="col-span-2">
                    <FormLabel
                      className={cn(isModal && "text-xs text-gray-500 font-normal")}
                    >
                      ملاحظات (اختياري)
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder="ملاحظات على القطعة"
                        className={cn(isModal && INPUT_PROJECT_CLASS)}
                        {...f}
                        value={f.value ?? ""}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              {fields.length > 1 && (
                <div className="col-span-2">
                  <Button
                    type="button"
                    variant={isModal ? "outline" : "destructive"}
                    size="sm"
                    className={cn(
                      isModal &&
                        "rounded-lg border-red-200 text-red-700 hover:bg-red-50",
                    )}
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

        {/* Computed totals (read-only) */}
        <div
          className={cn(
            "grid grid-cols-3 gap-4 pt-4 border-t rounded-lg border bg-muted/20 p-3",
            isModal && "border-blue-100 bg-gray-50/80 rounded-xl border-t-gray-100",
          )}
        >
          {(
            [
              ["total_amount", "الإجمالي (محسوب)"],
              ["payment_amount", "المدفوع (محسوب)"],
              ["remaining_payment", "المتبقي (محسوب)"],
            ] as const
          ).map(([name, label]) => (
            <FormField
              key={name}
              control={form.control}
              name={name}
              render={({ field }) => (
                <FormItem>
                  <FormLabel
                    className={cn(
                      "text-xs",
                      isModal && "text-gray-500 font-normal",
                    )}
                  >
                    {label}
                  </FormLabel>
                  <FormControl>
                    <Input
                      readOnly
                      value={toEnglishNumerals(field.value ?? 0)}
                      className={cn(
                        "tabular-nums",
                        isModal && INPUT_PROJECT_CLASS,
                        isModal && "bg-gray-100",
                      )}
                      dir="ltr"
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          ))}
        </div>

        {/* Actions */}
        {isModal ? (
          <div className="flex gap-3 pt-4 border-t border-gray-100">
            <button
              type="submit"
              disabled={isPending}
              className="flex-1 py-2.5 bg-blue-700 text-white rounded-xl text-sm font-bold cursor-pointer hover:bg-blue-800 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isPending ? "جاري الإنشاء..." : "إنشاء طلبية"}
            </button>
            <button
              type="button"
              disabled={isPending}
              onClick={handleCancel}
              className="flex-1 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm cursor-pointer whitespace-nowrap hover:bg-gray-200 disabled:opacity-50"
            >
              إلغاء
            </button>
          </div>
        ) : (
          <div className="flex items-center gap-4 pt-4 border-t">
            <Button type="button" variant="outline" onClick={handleCancel}>
              إلغاء
            </Button>
            <Button type="submit" disabled={isPending}>
              {isPending ? "جاري الإنشاء..." : "إنشاء طلبية"}
            </Button>
          </div>
        )}
      </form>
    </Form>
  );

  if (mode === "modal") {
    return formContent;
  }

  return (
    <div dir="rtl">
      <Card>
        <CardHeader>
          <CardTitle>إضافة طلبية مورد</CardTitle>
          <CardDescription>
            املأ البيانات لإضافة طلبية جديدة للمورد مع أصناف المنتجات.
          </CardDescription>
        </CardHeader>
        <CardContent>{formContent}</CardContent>
      </Card>
    </div>
  );
}
