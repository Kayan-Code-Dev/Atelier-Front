import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormMessage,
} from "@/components/ui/form";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useEffect } from "react";
import { useMutation } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { useUpdateClothesMutationOptions } from "@/api/v2/clothes/clothes.hooks";
import {
  TUpdateClothesRequest,
  TClothResponse,
  TClothesStatus,
} from "@/api/v2/clothes/clothes.types";
import { EntitySelect } from "@/components/custom/EntitySelect";
import { CategoriesSelect } from "@/components/custom/CategoriesSelect";
import { SubcategoriesSelect } from "@/components/custom/SubcategoriesSelect";
import { toast } from "sonner";
import { cn } from "@/lib/utils";

const formSchema = z.object({
  code: z.string().min(1, { message: "كود المنتج مطلوب" }),
  breast_size: z.string().optional(),
  waist_size: z.string().optional(),
  sleeve_size: z.string().optional(),
  category_id: z.string().optional(),
  subcategory_id: z.string().optional(),
  status: z.enum(
    [
      "damaged",
      "burned",
      "scratched",
      "ready_for_rent",
      "rented",
      "die",
      "repairing",
    ],
    { required_error: "الحالة مطلوبة" }
  ),
  entity_type: z.enum(["branch", "factory", "workshop"], {
    required_error: "نوع المكان مطلوب",
  }),
  entity_id: z.string({ required_error: "المكان مطلوب" }),
  notes: z.string().max(500).optional(),
});

type Props = {
  cloth: TClothResponse | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

const STATUS_OPTIONS: { value: TClothesStatus; label: string }[] = [
  { value: "ready_for_rent", label: "متوفر" },
  { value: "rented", label: "محجوز" },
  { value: "damaged", label: "تالف" },
  { value: "burned", label: "محترق" },
  { value: "scratched", label: "مخدوش" },
  { value: "repairing", label: "قيد الصيانة" },
  { value: "die", label: "ميت" },
];

const inputClass =
  "w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300";
const inputErrorClass = "border-red-400 focus:ring-red-100";

const selectTriggerClass =
  "w-full rounded-xl border-slate-200 h-auto min-h-[42px] py-2.5 shadow-none";

const entitySelectShell =
  "[&_[data-slot=form-label]]:block [&_[data-slot=form-label]]:text-xs [&_[data-slot=form-label]]:font-bold [&_[data-slot=form-label]]:text-slate-600 [&_[data-slot=form-label]]:mb-1.5 [&_[data-slot=select-trigger]]:rounded-xl [&_[data-slot=select-trigger]]:border-slate-200 [&_[data-slot=select-trigger]]:h-auto [&_[data-slot=select-trigger]]:min-h-[42px] [&_[data-slot=select-trigger]]:py-2.5";

export function EditClothModal({ cloth, open, onOpenChange }: Props) {
  const { mutate: updateCloth, isPending } = useMutation(
    useUpdateClothesMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      code: "",
      breast_size: "",
      waist_size: "",
      sleeve_size: "",
      category_id: "",
      subcategory_id: "",
      status: "ready_for_rent",
      entity_type: undefined,
      entity_id: "",
      notes: "",
    },
  });

  useEffect(() => {
    if (cloth && open) {
      form.reset({
        code: cloth.code,
        breast_size: cloth.breast_size || "",
        waist_size: cloth.waist_size || "",
        sleeve_size: cloth.sleeve_size || "",
        category_id: cloth.category_id != null ? String(cloth.category_id) : "",
        subcategory_id:
          cloth.subcategory_ids?.[0] != null
            ? String(cloth.subcategory_ids[0])
            : "",
        status: cloth.status,
        entity_type: cloth.entity_type,
        entity_id: cloth.entity_id.toString(),
        notes: cloth.notes || "",
      });
    }
  }, [cloth, open, form]);

  const close = () => onOpenChange(false);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!cloth) return;

    const requestData: TUpdateClothesRequest = {
      code: values.code,
      status: values.status,
      entity_type: values.entity_type,
      entity_id: Number(values.entity_id),
      notes: values.notes?.trim() || undefined,
      breast_size: values.breast_size || undefined,
      waist_size: values.waist_size || undefined,
      sleeve_size: values.sleeve_size || undefined,
      category_id: values.category_id ? Number(values.category_id) : undefined,
      subcategory_ids: values.subcategory_id
        ? [Number(values.subcategory_id)]
        : undefined,
    };

    updateCloth(
      { id: cloth.id, req: requestData },
      {
        onSuccess: () => {
          toast.success("تم تعديل المنتج بنجاح", {
            description: "تم تحديث بيانات المنتج بنجاح.",
          });
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء تعديل المنتج", {
            description: error.message,
          });
        },
      }
    );
  };

  const notesWatch = form.watch("notes") ?? "";
  const categoryId = form.watch("category_id");

  if (!open || !cloth) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      style={{ background: "rgba(0,0,0,0.45)" }}
      dir="rtl"
      onClick={close}
      role="presentation"
    >
      <div
        className="w-full max-w-3xl rounded-2xl bg-white overflow-hidden shadow-xl flex flex-col max-h-[90vh]"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-cloth-title"
      >
        <div
          className="px-6 py-4 flex items-center justify-between shrink-0"
          style={{
            background: "linear-gradient(135deg, #0C1A3E, #1E3A7B)",
          }}
        >
          <div className="flex items-center gap-3 min-w-0">
            <div
              className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
              style={{ background: "rgba(194,150,74,0.2)" }}
            >
              <i
                className="ri-pencil-line text-base"
                style={{ color: "#E8BF7A" }}
              />
            </div>
            <div className="min-w-0">
              <h2
                id="edit-cloth-title"
                className="text-white font-black text-base truncate"
              >
                تعديل المنتج
              </h2>
              <p className="text-white/50 text-xs mt-0.5 truncate font-mono">
                {cloth.code}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={close}
            className="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors"
            style={{ background: "rgba(255,255,255,0.1)" }}
          >
            <i className="ri-close-line text-white text-base" />
          </button>
        </div>

        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="flex flex-col flex-1 min-h-0"
          >
            <div
              className="overflow-y-auto p-6 space-y-5 flex-1"
              style={{ maxHeight: "calc(90vh - 64px - 73px)" }}
            >
              <FormField
                control={form.control}
                name="code"
                render={({ field, fieldState }) => (
                  <FormItem>
                    <label className="block text-xs font-bold text-slate-600 mb-1.5">
                      كود المنتج <span className="text-red-400">*</span>
                    </label>
                    <FormControl>
                      <input
                        type="text"
                        placeholder="كود المنتج..."
                        dir="ltr"
                        className={cn(
                          inputClass,
                          "font-mono",
                          fieldState.error && inputErrorClass
                        )}
                        disabled={isPending}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage className="text-xs mt-1" />
                  </FormItem>
                )}
              />

              <div>
                <div className="flex items-center gap-2 mb-3 flex-wrap">
                  <label className="text-xs font-bold text-slate-600">
                    المقاسات
                  </label>
                  <span
                    className="text-[11px] px-2 py-0.5 rounded-full font-semibold"
                    style={{ background: "#F1F5F9", color: "#94A3B8" }}
                  >
                    اختياري
                  </span>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  {(
                    [
                      { name: "breast_size" as const, label: "مقاس الصدر" },
                      { name: "waist_size" as const, label: "مقاس الخصر" },
                      { name: "sleeve_size" as const, label: "مقاس الكم" },
                    ] as const
                  ).map((item) => (
                    <FormField
                      key={item.name}
                      control={form.control}
                      name={item.name}
                      render={({ field }) => (
                        <FormItem>
                          <label className="block text-[11px] text-slate-500 mb-1">
                            {item.label}
                          </label>
                          <FormControl>
                            <div className="relative">
                              <input
                                type="text"
                                inputMode="decimal"
                                placeholder="—"
                                className={cn(inputClass, "pr-10 pl-3")}
                                disabled={isPending}
                                {...field}
                              />
                              <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none">
                                cm
                              </span>
                            </div>
                          </FormControl>
                          <FormMessage className="text-xs" />
                        </FormItem>
                      )}
                    />
                  ))}
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="category_id"
                  render={({ field, fieldState }) => (
                    <FormItem>
                      <label className="block text-xs font-bold text-slate-600 mb-1.5">
                        القسم
                        <span
                          className="text-[11px] font-semibold mr-1"
                          style={{ color: "#94A3B8" }}
                        >
                          (اختياري)
                        </span>
                      </label>
                      <FormControl>
                        <CategoriesSelect
                          value={field.value ?? ""}
                          onChange={(id) => {
                            field.onChange(id);
                            form.setValue("subcategory_id", "");
                          }}
                          disabled={isPending}
                          className={cn(
                            "rounded-xl border-slate-200 h-auto py-2.5 min-h-[42px]",
                            fieldState.error && "border-red-400"
                          )}
                        />
                      </FormControl>
                      <FormMessage className="text-xs mt-1" />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="subcategory_id"
                  render={({ field, fieldState }) => (
                    <FormItem>
                      <label className="block text-xs font-bold text-slate-600 mb-1.5">
                        القسم الفرعي
                      </label>
                      <FormControl>
                        <SubcategoriesSelect
                          value={field.value ?? ""}
                          onChange={field.onChange}
                          category_id={
                            categoryId ? Number(categoryId) : undefined
                          }
                          disabled={isPending}
                          className={cn(
                            "rounded-xl border-slate-200 h-auto py-2.5 min-h-[42px]",
                            fieldState.error && "border-red-400",
                            !categoryId && "opacity-50"
                          )}
                        />
                      </FormControl>
                      <FormMessage className="text-xs mt-1" />
                    </FormItem>
                  )}
                />
              </div>

              <FormField
                control={form.control}
                name="status"
                render={({ field, fieldState }) => (
                  <FormItem>
                    <label className="block text-xs font-bold text-slate-600 mb-1.5">
                      الحالة <span className="text-red-400">*</span>
                    </label>
                    <FormControl>
                      <Select
                        onValueChange={field.onChange}
                        value={field.value}
                        disabled={isPending}
                      >
                        <SelectTrigger
                          className={cn(
                            selectTriggerClass,
                            fieldState.error && inputErrorClass
                          )}
                        >
                          <SelectValue placeholder="اختر الحالة..." />
                        </SelectTrigger>
                        <SelectContent>
                          {STATUS_OPTIONS.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                              {option.label}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </FormControl>
                    <FormMessage className="text-xs mt-1" />
                  </FormItem>
                )}
              />

              <div className={cn("rounded-xl p-4 space-y-1", entitySelectShell)} style={{ background: "#FAFBFD", border: "1px solid #EEF2F8" }}>
                <p className="text-xs font-bold text-slate-600 mb-3">
                  الموقع والتخزين
                </p>
                <EntitySelect
                  mode="form"
                  control={form.control}
                  entityTypeName="entity_type"
                  entityIdName="entity_id"
                  entityTypeLabel="نوع المكان"
                  entityIdLabel="المكان"
                  disabled={isPending}
                />
              </div>

              <FormField
                control={form.control}
                name="notes"
                render={({ field, fieldState }) => (
                  <FormItem>
                    <div className="flex items-center gap-2 mb-1.5">
                      <label className="text-xs font-bold text-slate-600">
                        ملاحظات
                      </label>
                      <span
                        className="text-[11px] px-2 py-0.5 rounded-full font-semibold"
                        style={{ background: "#F1F5F9", color: "#94A3B8" }}
                      >
                        اختياري
                      </span>
                    </div>
                    <FormControl>
                      <textarea
                        rows={3}
                        maxLength={500}
                        placeholder="ملاحظات إضافية..."
                        className={cn(
                          inputClass,
                          "resize-none min-h-[88px]",
                          fieldState.error && inputErrorClass
                        )}
                        disabled={isPending}
                        {...field}
                      />
                    </FormControl>
                    <p className="text-[11px] text-slate-400 text-left mt-1">
                      {notesWatch.length}/500
                    </p>
                    <FormMessage className="text-xs" />
                  </FormItem>
                )}
              />
            </div>

            <div
              className="px-6 py-4 flex items-center justify-between gap-3 shrink-0"
              style={{
                background: "#F8FAFC",
                borderTop: "1px solid #EEF2F8",
              }}
            >
              <button
                type="button"
                onClick={close}
                disabled={isPending}
                className="px-5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors disabled:opacity-50"
                style={{ background: "#F1F5F9", color: "#475569" }}
              >
                إلغاء
              </button>
              <button
                type="submit"
                disabled={isPending}
                className="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap text-white disabled:opacity-60 transition-opacity"
                style={{
                  background: "linear-gradient(135deg, #0C1A3E, #1E3A7B)",
                }}
              >
                {isPending ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  <i className="ri-save-line" />
                )}
                {isPending ? "جاري الحفظ..." : "حفظ التغييرات"}
              </button>
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
}
