import { useEffect, useMemo } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";

import { useCreateClothesMutationOptions } from "@/api/v2/clothes/clothes.hooks";
import {
  TCreateClothesRequest,
  TClothesStatus,
} from "@/api/v2/clothes/clothes.types";
import { useCategoriesQueryOptions } from "@/api/v2/content-managment/category/category.hooks";
import { useSubcategoriesQueryOptions } from "@/api/v2/content-managment/subcategory/subcategory.hooks";
import { CategoriesSelect } from "@/components/custom/CategoriesSelect";
import { SubcategoriesSelect } from "@/components/custom/SubcategoriesSelect";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormMessage,
} from "@/components/ui/form";
import { cn } from "@/lib/utils";

const formSchema = z.object({
  code: z.string().min(1, { message: "كود المنتج مطلوب" }),
  category_id: z.string().min(1, { message: "القسم مطلوب" }),
  subcategory_id: z.string().min(1, { message: "القسم الفرعي مطلوب" }),
  branch_id: z.string().min(1, { message: "الفرع مطلوب" }),
  breast_size: z.string().optional(),
  waist_size: z.string().optional(),
  sleeve_size: z.string().optional(),
  notes: z.string().optional(),
});

type FormValues = z.infer<typeof formSchema>;

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

const DEFAULT_STATUS: TClothesStatus = "ready_for_rent";

const inputClass =
  "w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300";
const inputErrorClass = "border-red-400 focus:ring-red-100";

export function CreateClothModal({ open, onOpenChange }: Props) {
  const { mutate: createCloth, isPending } = useMutation(
    useCreateClothesMutationOptions()
  );

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      code: "",
      category_id: "",
      subcategory_id: "",
      branch_id: "",
      breast_size: "",
      waist_size: "",
      sleeve_size: "",
      notes: "",
    },
  });

  const categoryId = form.watch("category_id");
  const subcategoryId = form.watch("subcategory_id");
  const codeVal = form.watch("code");

  const { data: categoriesRes } = useQuery({
    ...useCategoriesQueryOptions(1, 500),
    enabled: open,
  });
  const { data: subcategoriesRes } = useQuery({
    ...useSubcategoriesQueryOptions(
      1,
      200,
      categoryId ? Number(categoryId) : undefined
    ),
    enabled: open && Boolean(categoryId),
  });

  const previewName = useMemo(() => {
    const code = codeVal?.trim();
    if (!code) return "—";
    const cat = categoriesRes?.data?.find(
      (c) => String(c.id) === categoryId
    )?.name;
    const sub = subcategoriesRes?.data?.find(
      (s) => String(s.id) === subcategoryId
    )?.name;
    const parts = [code, cat, sub].filter(Boolean);
    return parts.join(" - ");
  }, [
    codeVal,
    categoryId,
    subcategoryId,
    categoriesRes?.data,
    subcategoriesRes?.data,
  ]);

  const hasSizes =
    Boolean(form.watch("breast_size")?.trim()) ||
    Boolean(form.watch("waist_size")?.trim()) ||
    Boolean(form.watch("sleeve_size")?.trim());

  useEffect(() => {
    if (!open) {
      form.reset();
    }
  }, [open, form]);

  const close = () => onOpenChange(false);

  const onSubmit = (values: FormValues) => {
    const requestData: TCreateClothesRequest = {
      code: values.code.trim(),
      status: DEFAULT_STATUS,
      entity_type: "branch",
      entity_id: Number(values.branch_id),
      notes: values.notes?.trim() || undefined,
      description: "",
      breast_size: values.breast_size?.trim() || "",
      waist_size: values.waist_size?.trim() || "",
      sleeve_size: values.sleeve_size?.trim() || "",
      category_id: Number(values.category_id),
      subcategory_ids: [Number(values.subcategory_id)],
    };

    createCloth(requestData, {
      onSuccess: () => {
        toast.success("تم إنشاء المنتج بنجاح", {
          description: "تمت إضافة المنتج بنجاح للنظام.",
        });
        form.reset();
        onOpenChange(false);
      },
      onError: (error: Error & { message?: string }) => {
        toast.error("حدث خطأ أثناء إنشاء المنتج", {
          description: error.message,
        });
      },
    });
  };

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      style={{ background: "rgba(0,0,0,0.45)" }}
      dir="rtl"
      onClick={close}
      role="presentation"
    >
      <div
        className="w-full max-w-2xl rounded-2xl bg-white overflow-hidden shadow-xl flex flex-col max-h-[90vh]"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-cloth-title"
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
                className="ri-price-tag-3-line text-base"
                style={{ color: "#E8BF7A" }}
              />
            </div>
            <div className="min-w-0">
              <h2
                id="create-cloth-title"
                className="text-white font-black text-base truncate"
              >
                إضافة منتج جديد
              </h2>
              <p className="text-white/50 text-xs mt-0.5 truncate">
                تفاصيل وبيانات المنتج
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
              <div
                className="rounded-xl p-3 flex items-center gap-3"
                style={{
                  background: "#F8FAFC",
                  border: "1px solid #EEF2F8",
                }}
              >
                <i className="ri-eye-line text-slate-400 text-sm shrink-0" />
                <div className="min-w-0">
                  <p className="text-[11px] text-slate-400">
                    اسم المنتج سيظهر كـ:
                  </p>
                  <p className="text-sm font-black text-slate-800 font-mono mt-0.5 break-all">
                    {previewName}
                  </p>
                </div>
              </div>

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
                        placeholder="مثال: EQ-1050"
                        dir="ltr"
                        className={cn(
                          inputClass,
                          "font-mono",
                          fieldState.error && inputErrorClass
                        )}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage className="text-xs mt-1" />
                  </FormItem>
                )}
              />

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="category_id"
                  render={({ field, fieldState }) => (
                    <FormItem>
                      <label className="block text-xs font-bold text-slate-600 mb-1.5">
                        القسم <span className="text-red-400">*</span>
                      </label>
                      <FormControl>
                        <CategoriesSelect
                          value={field.value}
                          onChange={(id) => {
                            field.onChange(id);
                            form.setValue("subcategory_id", "");
                          }}
                          disabled={isPending}
                          className={cn(
                            "rounded-xl border-slate-200 h-auto py-2.5",
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
                        القسم الفرعي <span className="text-red-400">*</span>
                      </label>
                      <FormControl>
                        <SubcategoriesSelect
                          value={field.value}
                          onChange={field.onChange}
                          category_id={
                            categoryId ? Number(categoryId) : undefined
                          }
                          disabled={isPending || !categoryId}
                          className={cn(
                            "rounded-xl border-slate-200 h-auto py-2.5",
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
                name="branch_id"
                render={({ field, fieldState }) => (
                  <FormItem>
                    <label className="block text-xs font-bold text-slate-600 mb-1.5">
                      الفرع المراد الإضافة فيه{" "}
                      <span className="text-red-400">*</span>
                    </label>
                    <FormControl>
                      <BranchesSelect
                        value={field.value}
                        onChange={field.onChange}
                        disabled={isPending}
                        placeholder="— اختر الفرع —"
                        className={cn(
                          "rounded-xl border-slate-200 h-auto py-2.5",
                          fieldState.error && "border-red-400"
                        )}
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
                  {hasSizes ? (
                    <span
                      className="text-[11px] px-2 py-0.5 rounded-full font-semibold"
                      style={{ background: "#D1FAE5", color: "#065F46" }}
                    >
                      <i className="ri-checkbox-circle-line ml-0.5" />
                      تم التعبئة
                    </span>
                  ) : null}
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  {(
                    [
                      {
                        name: "breast_size" as const,
                        label: "مقاس الصدر",
                      },
                      {
                        name: "waist_size" as const,
                        label: "مقاس الخصر",
                      },
                      {
                        name: "sleeve_size" as const,
                        label: "مقاس الكم",
                      },
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
                                type="number"
                                min={0}
                                placeholder="0"
                                className={cn(inputClass, "pr-10 pl-3")}
                                disabled={isPending}
                                {...field}
                                value={field.value ?? ""}
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

              <FormField
                control={form.control}
                name="notes"
                render={({ field }) => (
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
                        placeholder="أي ملاحظات إضافية عن المنتج..."
                        className={cn(inputClass, "resize-none min-h-[88px]")}
                        disabled={isPending}
                        {...field}
                      />
                    </FormControl>
                    <p className="text-[11px] text-slate-400 text-left mt-1">
                      {(field.value ?? "").length}/500
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
                {isPending ? "جاري الحفظ..." : "حفظ المنتج"}
              </button>
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
}
