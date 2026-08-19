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
import { useEffect, type ReactNode } from "react";
import { useMutation, useInfiniteQuery } from "@tanstack/react-query";
import {
  TWorkshopResponse,
  TUpdateWorkshopRequest,
} from "@/api/v2/workshop/workshop.types";
import { useUpdateWorkshopMutationOptions } from "@/api/v2/workshop/workshops.hooks";
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
  workshop: TWorkshopResponse | null;
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

function SectionTitle({ children }: { children: ReactNode }) {
  return (
    <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 flex items-center gap-2">
      <span className="h-px flex-1 bg-gray-100 max-w-8" />
      {children}
      <span className="h-px flex-1 bg-gray-100" />
    </h3>
  );
}

export function EditWorkshopModal({ workshop, open, onOpenChange }: Props) {
  const { mutate: updateWorkshop, isPending } = useMutation(
    useUpdateWorkshopMutationOptions()
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
    if (workshop && open) {
      form.reset({
        workshop_code: workshop.workshop_code,
        name: workshop.name,
        street: workshop.address?.street || "",
        building: workshop.address?.building || "",
        city_id: workshop.address ? String(workshop.address.city_id) : "",
        notes: workshop.address?.notes || "",
        inventory_name: workshop.inventory?.name || "",
      });
    }
  }, [workshop, open, form]);

  const closeModal = () => onOpenChange(false);

  const onSubmit = (values: FormValues) => {
    if (!workshop) return;

    const requestData: TUpdateWorkshopRequest = {
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

    updateWorkshop(
      { id: workshop.id, data: requestData },
      {
        onSuccess: () => {
          toast.success("تم تحديث الورشة بنجاح", {
            description: `تم تحديث الورشة "${workshop.name}" بنجاح.`,
          });
          closeModal();
        },
        onError: (error: Error & { message?: string }) => {
          toast.error("حدث خطأ أثناء تحديث الورشة", {
            description: error.message,
          });
        },
      }
    );
  };

  if (!open || !workshop) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center"
      dir="rtl"
    >
      <button
        type="button"
        aria-label="إغلاق"
        className="absolute inset-0 bg-black/40 backdrop-blur-sm border-0 cursor-default"
        onClick={closeModal}
      />

      <div className="relative bg-white rounded-2xl w-full max-w-xl mx-4 max-h-[92vh] flex flex-col overflow-hidden shadow-xl">
        <div className="px-6 pt-6 pb-4 shrink-0 border-b border-gray-100">
          <div className="flex items-center justify-between gap-3">
            <div className="flex items-center gap-3 min-w-0">
              <div
                className={`w-11 h-11 flex items-center justify-center rounded-xl shrink-0 ${ACCENT}`}
              >
                <i className={`${ICON} text-xl`} />
              </div>
              <div className="min-w-0">
                <h2 className="text-lg font-bold text-gray-900 truncate">
                  تعديل الورشة
                </h2>
                <p className="text-xs text-gray-400 mt-0.5 truncate">
                  {workshop.name}
                </p>
              </div>
            </div>
            <button
              type="button"
              onClick={closeModal}
              className="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 transition-colors shrink-0"
            >
              <i className="ri-close-line" />
            </button>
          </div>
        </div>

        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="flex flex-col flex-1 overflow-hidden min-h-0"
          >
            <div className="flex-1 overflow-y-auto px-6 py-4 space-y-8">
              <section>
                <SectionTitle>البيانات الأساسية</SectionTitle>
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <FormField
                      control={form.control}
                      name="workshop_code"
                      render={({ field }) => (
                        <FormItem>
                          <label className="block text-xs font-medium text-gray-600 mb-1.5">
                            كود الورشة <span className="text-red-500">*</span>
                          </label>
                          <FormControl>
                            <Input
                              dir="ltr"
                              placeholder="WS001"
                              className="rounded-lg py-2.5 font-mono border-gray-200 focus-visible:ring-slate-200 focus-visible:border-slate-400"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage className="text-xs" />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name="name"
                      render={({ field }) => (
                        <FormItem>
                          <label className="block text-xs font-medium text-gray-600 mb-1.5">
                            اسم الورشة <span className="text-red-500">*</span>
                          </label>
                          <FormControl>
                            <Input
                              className="rounded-lg py-2.5 border-gray-200 focus-visible:ring-slate-200 focus-visible:border-slate-400"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage className="text-xs" />
                        </FormItem>
                      )}
                    />
                  </div>
                </div>
              </section>

              <section>
                <SectionTitle>العنوان</SectionTitle>
                <div className="space-y-4">
                  <FormField
                    control={form.control}
                    name="city_id"
                    render={({ field }) => (
                      <FormItem>
                        <label className="block text-xs font-semibold text-gray-600 mb-2">
                          المدينة <span className="text-red-500">*</span>
                        </label>
                        {citiesQuery.isPending && allCities.length === 0 ? (
                          <div className="flex justify-center py-8">
                            <Loader2 className="h-8 w-8 animate-spin text-slate-600" />
                          </div>
                        ) : (
                          <div className="grid grid-cols-3 gap-2 max-h-52 overflow-y-auto">
                            {allCities.map((c) => (
                              <button
                                key={c.id}
                                type="button"
                                onClick={() => field.onChange(String(c.id))}
                                className={`px-3 py-2 rounded-lg border text-xs font-medium transition-all text-center ${
                                  field.value === String(c.id)
                                    ? "border-slate-600 bg-slate-50 text-slate-800"
                                    : "border-gray-200 text-gray-600 hover:border-gray-300"
                                }`}
                              >
                                {c.name}
                              </button>
                            ))}
                          </div>
                        )}
                        {citiesQuery.hasNextPage && (
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="w-full mt-2"
                            disabled={citiesQuery.isFetchingNextPage}
                            onClick={() => citiesQuery.fetchNextPage()}
                          >
                            {citiesQuery.isFetchingNextPage ? (
                              <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                              "تحميل المزيد من المدن"
                            )}
                          </Button>
                        )}
                        <FormMessage className="text-xs mt-1" />
                      </FormItem>
                    )}
                  />

                  <div className="grid grid-cols-2 gap-4">
                    <FormField
                      control={form.control}
                      name="street"
                      render={({ field }) => (
                        <FormItem>
                          <label className="block text-xs font-medium text-gray-600 mb-1.5">
                            الشارع <span className="text-red-500">*</span>
                          </label>
                          <FormControl>
                            <div className="relative">
                              <div className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                <i className="ri-road-map-line text-sm" />
                              </div>
                              <Input
                                className="pr-9 rounded-lg py-2.5 border-gray-200 focus-visible:ring-slate-200 focus-visible:border-slate-400"
                                {...field}
                              />
                            </div>
                          </FormControl>
                          <FormMessage className="text-xs" />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name="building"
                      render={({ field }) => (
                        <FormItem>
                          <label className="block text-xs font-medium text-gray-600 mb-1.5">
                            المبنى / الطابق{" "}
                            <span className="text-red-500">*</span>
                          </label>
                          <FormControl>
                            <div className="relative">
                              <div className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                <i className="ri-building-2-line text-sm" />
                              </div>
                              <Input
                                className="pr-9 rounded-lg py-2.5 border-gray-200 focus-visible:ring-slate-200 focus-visible:border-slate-400"
                                {...field}
                              />
                            </div>
                          </FormControl>
                          <FormMessage className="text-xs" />
                        </FormItem>
                      )}
                    />
                  </div>

                  <FormField
                    control={form.control}
                    name="notes"
                    render={({ field }) => (
                      <FormItem>
                        <label className="block text-xs font-medium text-gray-600 mb-1.5">
                          ملاحظات{" "}
                          <span className="text-gray-400 font-normal">
                            (اختياري)
                          </span>
                        </label>
                        <FormControl>
                          <textarea
                            rows={3}
                            maxLength={500}
                            className="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-400 resize-none"
                            {...field}
                          />
                        </FormControl>
                        <p className="text-[10px] text-gray-400 mt-1 text-left">
                          {(field.value ?? "").length}/500
                        </p>
                        <FormMessage className="text-xs" />
                      </FormItem>
                    )}
                  />
                </div>
              </section>

              <section>
                <SectionTitle>المخزن</SectionTitle>
                <FormField
                  control={form.control}
                  name="inventory_name"
                  render={({ field }) => (
                    <FormItem>
                      <label className="block text-xs font-medium text-gray-600 mb-1.5">
                        اسم المخزن <span className="text-red-500">*</span>
                      </label>
                      <FormControl>
                        <div className="relative">
                          <div className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                            <i className="ri-store-2-line text-sm" />
                          </div>
                          <Input
                            className="pr-9 rounded-lg py-2.5 border-gray-200 focus-visible:ring-slate-200 focus-visible:border-slate-400"
                            {...field}
                          />
                        </div>
                      </FormControl>
                      <FormMessage className="text-xs" />
                    </FormItem>
                  )}
                />
              </section>
            </div>

            <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3 shrink-0 bg-white">
              <button
                type="button"
                onClick={closeModal}
                className="px-5 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                إلغاء
              </button>
              <Button
                type="submit"
                disabled={isPending}
                className="px-5 py-2 h-auto bg-slate-700 hover:bg-slate-800 text-white rounded-lg"
              >
                {isPending ? (
                  <>
                    <Loader2 className="ml-2 h-4 w-4 animate-spin" />
                    جاري الحفظ...
                  </>
                ) : (
                  <>
                    <i className="ri-save-line ml-1" /> حفظ التغييرات
                  </>
                )}
              </Button>
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
}
