import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormMessage,
} from "@/components/ui/form";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useEffect } from "react";
import { useMutation, useInfiniteQuery } from "@tanstack/react-query";
import { useCreateWorkshopMutationOptions } from "@/api/v2/workshop/workshops.hooks";
import { TCreateWorkshopRequest } from "@/api/v2/workshop/workshop.types";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";
import { useInfiniteCitiesQueryOptions } from "@/api/v2/content-managment/city/city.hooks";
import type { TCity } from "@/api/v2/content-managment/city/city.types";

const formSchema = z.object({
  workshop_code: z.string().min(1, { message: "كود الورشة مطلوب" }),
  name: z.string().min(2, { message: "اسم الورشة مطلوب" }),
  street: z.string().min(1, { message: "الشارع مطلوب" }),
  building: z.string().min(1, { message: "المبنى مطلوب" }),
  city_id: z.string().min(1, { message: "المدينة مطلوبة" }),
  notes: z.string().optional(),
  inventory_name: z.string().min(1, { message: "اسم المخزن مطلوب" }),
});

type FormValues = z.infer<typeof formSchema>;

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

const ACCENT = "bg-slate-100 text-slate-700";
const ICON = "ri-tools-line";

function flattenPaged<T>(
  data: { pages: Array<{ data?: T[] } | undefined> } | undefined
): T[] {
  if (!data?.pages) return [];
  return data.pages.flatMap((p) => p?.data ?? []);
}

function fieldClass(err?: boolean) {
  return `w-full px-3 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 ${
    err ? "border-red-400" : "border-gray-200"
  }`;
}

export function CreateWorkshopModal({ open, onOpenChange }: Props) {
  const { mutate: createWorkshop, isPending } = useMutation(
    useCreateWorkshopMutationOptions()
  );

  const citiesQuery = useInfiniteQuery({
    ...useInfiniteCitiesQueryOptions(40),
    enabled: open,
  });

  const allCities = flattenPaged<TCity>(citiesQuery.data);

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      workshop_code: "",
      name: "",
      street: "",
      building: "",
      city_id: "",
      notes: "",
      inventory_name: "",
    },
  });

  useEffect(() => {
    if (!open) {
      form.reset();
    }
  }, [open, form.reset]);

  const closeModal = () => onOpenChange(false);

  const onSubmit = (values: FormValues) => {
    const requestData: TCreateWorkshopRequest = {
      workshop_code: values.workshop_code,
      name: values.name,
      address: {
        street: values.street,
        building: values.building,
        city_id: Number(values.city_id),
        notes: values.notes || "",
      },
      inventory_name: values.inventory_name,
    };

    createWorkshop(requestData, {
      onSuccess: () => {
        toast.success("تم إنشاء الورشة بنجاح", {
          description: "تمت إضافة الورشة بنجاح للنظام.",
        });
        form.reset();
        onOpenChange(false);
      },
      onError: (error: Error & { message?: string }) => {
        toast.error("حدث خطأ أثناء إنشاء الورشة", {
          description: error.message,
        });
      },
    });
  };

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center"
      dir="rtl"
    >
      <button
        type="button"
        aria-label="إغلاق"
        className="absolute inset-0 bg-black/30 border-0 cursor-default"
        onClick={closeModal}
      />

      <div
        className="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] flex flex-col overflow-hidden"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
          <div className="flex items-center gap-3 min-w-0">
            <div
              className={`w-9 h-9 flex items-center justify-center rounded-xl shrink-0 ${ACCENT}`}
            >
              <i className={`${ICON} text-lg`} />
            </div>
            <h2 className="text-lg font-bold text-gray-800 truncate">
              إضافة ورشة جديد
            </h2>
          </div>
          <button
            type="button"
            onClick={closeModal}
            className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors shrink-0"
          >
            <i className="ri-close-line text-gray-500 text-lg" />
          </button>
        </div>

        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="flex flex-col flex-1 min-h-0"
          >
            <div className="p-6 overflow-y-auto flex-1 space-y-5">
              <div className="grid grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="workshop_code"
                  render={({ field, fieldState }) => (
                    <FormItem>
                      <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                        كود ورشة <span className="text-red-400">*</span>
                      </label>
                      <FormControl>
                        <input
                          type="text"
                          placeholder="مثال: WS001"
                          dir="ltr"
                          className={fieldClass(!!fieldState.error)}
                          {...field}
                        />
                      </FormControl>
                      <FormMessage className="text-xs mt-1" />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field, fieldState }) => (
                    <FormItem>
                      <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                        اسم ورشة <span className="text-red-400">*</span>
                      </label>
                      <FormControl>
                        <input
                          type="text"
                          placeholder="أدخل اسم الورشة..."
                          className={fieldClass(!!fieldState.error)}
                          {...field}
                        />
                      </FormControl>
                      <FormMessage className="text-xs mt-1" />
                    </FormItem>
                  )}
                />
              </div>

              <FormField
                control={form.control}
                name="inventory_name"
                render={({ field, fieldState }) => (
                  <FormItem>
                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                      اسم المخزن <span className="text-red-400">*</span>
                    </label>
                    <FormControl>
                      <input
                        type="text"
                        placeholder="مثال: مخزن الورشة الرئيسية"
                        className={fieldClass(!!fieldState.error)}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage className="text-xs mt-1" />
                  </FormItem>
                )}
              />

              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  العنوان <span className="text-red-400">*</span>
                </label>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <FormField
                    control={form.control}
                    name="city_id"
                    render={({ field }) => (
                      <FormItem className="sm:col-span-1">
                        <FormControl>
                          {citiesQuery.isPending && allCities.length === 0 ? (
                            <div className="flex justify-center py-6 rounded-lg border border-gray-200">
                              <Loader2 className="h-6 w-6 animate-spin text-slate-500" />
                            </div>
                          ) : (
                            <div className="max-h-40 overflow-y-auto rounded-lg border border-gray-200 p-1 space-y-1">
                              {allCities.map((c) => (
                                <button
                                  key={c.id}
                                  type="button"
                                  onClick={() => field.onChange(String(c.id))}
                                  className={`w-full text-right px-2 py-2 rounded-md text-xs font-medium transition-colors ${
                                    field.value === String(c.id)
                                      ? "bg-slate-100 text-slate-800 border border-slate-300"
                                      : "text-gray-600 hover:bg-gray-50 border border-transparent"
                                  }`}
                                >
                                  {c.name}
                                </button>
                              ))}
                            </div>
                          )}
                        </FormControl>
                        {citiesQuery.hasNextPage && (
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="w-full mt-1 h-8 text-xs"
                            disabled={citiesQuery.isFetchingNextPage}
                            onClick={() => citiesQuery.fetchNextPage()}
                          >
                            {citiesQuery.isFetchingNextPage ? (
                              <Loader2 className="h-3 w-3 animate-spin" />
                            ) : (
                              "تحميل المزيد"
                            )}
                          </Button>
                        )}
                        <FormMessage className="text-xs mt-1" />
                        <span className="text-[10px] text-gray-400 block mt-1">
                          المدينة
                        </span>
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name="street"
                    render={({ field, fieldState }) => (
                      <FormItem>
                        <FormControl>
                          <Input
                            placeholder="اسم الشارع"
                            className={fieldClass(!!fieldState.error)}
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className="text-xs mt-1" />
                        <span className="text-[10px] text-gray-400 block mt-1">
                          الشارع
                        </span>
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name="building"
                    render={({ field, fieldState }) => (
                      <FormItem>
                        <FormControl>
                          <Input
                            placeholder="رقم المبنى / الحي"
                            className={fieldClass(!!fieldState.error)}
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className="text-xs mt-1" />
                        <span className="text-[10px] text-gray-400 block mt-1">
                          المبنى
                        </span>
                      </FormItem>
                    )}
                  />
                </div>
              </div>

              <FormField
                control={form.control}
                name="notes"
                render={({ field }) => (
                  <FormItem>
                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                      ملاحظات{" "}
                      <span className="text-gray-400 font-normal">(اختياري)</span>
                    </label>
                    <FormControl>
                      <textarea
                        placeholder="أي ملاحظات إضافية..."
                        rows={2}
                        maxLength={300}
                        className="w-full px-3 py-2.5 rounded-lg border border-gray-200 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-slate-300"
                        {...field}
                      />
                    </FormControl>
                    <p className="text-[10px] text-gray-400 mt-1 text-left">
                      {(field.value ?? "").length}/300
                    </p>
                    <FormMessage className="text-xs" />
                  </FormItem>
                )}
              />
            </div>

            <div className="px-6 pb-5 pt-3 flex gap-3 justify-end border-t border-gray-100 shrink-0 bg-white">
              <button
                type="button"
                onClick={closeModal}
                className="px-5 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors whitespace-nowrap"
              >
                إلغاء
              </button>
              <button
                type="submit"
                disabled={isPending}
                className="px-6 py-2.5 rounded-lg bg-slate-700 text-white text-sm font-medium hover:bg-slate-800 transition-colors whitespace-nowrap flex items-center gap-2 disabled:opacity-60"
              >
                {isPending ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    جاري الإنشاء...
                  </>
                ) : (
                  <>
                    <i className="ri-save-line" /> إضافة ورشة
                  </>
                )}
              </button>
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
}
