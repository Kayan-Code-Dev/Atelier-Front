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
import { useState, useRef, useEffect } from "react";
import { useMutation, useInfiniteQuery } from "@tanstack/react-query";
import { useCreateBranchMutationOptions } from "@/api/v2/branches/branches.hooks";
import { TCreateBranchRequest } from "@/api/v2/branches/branches.types";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";
import { useInfiniteCitiesQueryOptions } from "@/api/v2/content-managment/city/city.hooks";
import { useInfiniteCurrenciesQueryOptions } from "@/api/v2/content-managment/currency/currency.hooks";
import type { TCity } from "@/api/v2/content-managment/city/city.types";
import type { TCurrency } from "@/api/v2/content-managment/currency/currency.types";

const STEPS = [
  "الهوية البصرية",
  "البيانات الأساسية",
  "المالية والضريبة",
  "العنوان والمخزن",
] as const;

const formSchema = z
  .object({
    branch_code: z.string().min(1, { message: "كود الفرع مطلوب" }),
    name: z.string().min(2, { message: "اسم الفرع مطلوب" }),
    street: z.string().min(1, { message: "الشارع مطلوب" }),
    building: z.string().min(1, { message: "المبنى مطلوب" }),
    city_id: z.string().min(1, { message: "المدينة مطلوبة" }),
    notes: z.string().optional(),
    inventory_name: z.string().min(1, { message: "اسم المخزن مطلوب" }),
    phone: z.string().optional(),
    vat_enabled: z.boolean().optional(),
    vat_type: z.enum(["fixed", "percentage"]).nullable().optional(),
    vat_value: z.union([z.string(), z.number()]).optional().nullable(),
    currency_id: z.string().min(1, { message: "العملة مطلوبة" }),
  })
  .superRefine((data, ctx) => {
    if (data.vat_enabled) {
      if (!data.vat_type) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: "نوع الضريبة مطلوب عند تفعيلها",
          path: ["vat_type"],
        });
      }
      if (
        data.vat_value == null ||
        data.vat_value === "" ||
        Number.isNaN(Number(data.vat_value)) ||
        Number(data.vat_value) <= 0
      ) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: "قيمة الضريبة مطلوبة عند تفعيلها",
          path: ["vat_value"],
        });
      }
    }
  });

type FormValues = z.infer<typeof formSchema>;

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

function flattenPaged<T>(
  data: { pages: Array<{ data?: T[] } | undefined> } | undefined
): T[] {
  if (!data?.pages) return [];
  return data.pages.flatMap((p) => p?.data ?? []);
}

export function CreateBranchModal({ open, onOpenChange }: Props) {
  const { mutate: createBranch, isPending } = useMutation(
    useCreateBranchMutationOptions()
  );

  const coverRef = useRef<HTMLInputElement>(null);
  const logoRef = useRef<HTMLInputElement>(null);
  const [step, setStep] = useState(0);
  const [coverFile, setCoverFile] = useState<File | null>(null);
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [coverPreview, setCoverPreview] = useState("");
  const [logoPreview, setLogoPreview] = useState("");

  const citiesQuery = useInfiniteQuery({
    ...useInfiniteCitiesQueryOptions(40),
    enabled: open,
  });
  const currenciesQuery = useInfiniteQuery({
    ...useInfiniteCurrenciesQueryOptions(30),
    enabled: open,
  });

  const allCities = flattenPaged<TCity>(citiesQuery.data);
  const allCurrencies = flattenPaged<TCurrency>(currenciesQuery.data);

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      branch_code: "",
      name: "",
      street: "",
      building: "",
      city_id: "",
      notes: "",
      inventory_name: "",
      phone: "",
      vat_enabled: false,
      vat_type: "percentage",
      vat_value: "15",
      currency_id: "",
    },
  });

  const vatEnabled = form.watch("vat_enabled");
  const vatType = form.watch("vat_type");
  const vatValueStr = String(form.watch("vat_value") ?? "");
  const currencyId = form.watch("currency_id");
  const cityId = form.watch("city_id");

  const selectedCurrency = allCurrencies.find(
    (c) => String(c.id) === currencyId
  );
  const selectedCity = allCities.find((c) => String(c.id) === cityId);

  useEffect(() => {
    if (!coverFile) {
      setCoverPreview("");
      return;
    }
    const u = URL.createObjectURL(coverFile);
    setCoverPreview(u);
    return () => URL.revokeObjectURL(u);
  }, [coverFile]);

  useEffect(() => {
    if (!logoFile) {
      setLogoPreview("");
      return;
    }
    const u = URL.createObjectURL(logoFile);
    setLogoPreview(u);
    return () => URL.revokeObjectURL(u);
  }, [logoFile]);

  useEffect(() => {
    if (!open) {
      setStep(0);
      setCoverFile(null);
      setLogoFile(null);
      form.reset();
      if (coverRef.current) coverRef.current.value = "";
      if (logoRef.current) logoRef.current.value = "";
    }
  }, [open, form.reset]);

  const handleImageUpload = (
    which: "cover" | "logo",
    file: File | null
  ) => {
    if (!file) return;
    if (which === "cover") {
      setCoverFile(file);
    } else {
      setLogoFile(file);
    }
  };

  const validateStep = async (s: number): Promise<boolean> => {
    if (s === 0) return true;
    if (s === 1) {
      return form.trigger(["branch_code", "name", "inventory_name"]);
    }
    if (s === 2) {
      const okCurrency = await form.trigger(["currency_id"]);
      if (!okCurrency) return false;
      if (form.getValues("vat_enabled")) {
        return form.trigger(["vat_type", "vat_value"]);
      }
      return true;
    }
    if (s === 3) {
      return form.trigger(["city_id", "street", "building"]);
    }
    return true;
  };

  const handleNext = async () => {
    const ok = await validateStep(step);
    if (!ok) return;
    setStep((prev) => Math.min(prev + 1, STEPS.length - 1));
  };

  const handleBack = () => {
    setStep((prev) => Math.max(prev - 1, 0));
  };

  const closeModal = () => {
    onOpenChange(false);
  };

  const onSubmit = (values: FormValues) => {
    const imageFile = coverFile ?? logoFile ?? undefined;
    const requestData: TCreateBranchRequest = {
      branch_code: values.branch_code,
      name: values.name,
      address: {
        street: values.street,
        building: values.building,
        city_id: Number(values.city_id),
        notes: values.notes || "",
      },
      inventory_name: values.inventory_name,
      phone: values.phone || undefined,
      image: imageFile,
      vat_enabled: values.vat_enabled ?? false,
      vat_type: values.vat_enabled
        ? (values.vat_type as "fixed" | "percentage")
        : undefined,
      vat_value:
        values.vat_enabled &&
        values.vat_value != null &&
        values.vat_value !== ""
          ? Number(values.vat_value)
          : undefined,
      currency_id: values.currency_id ? Number(values.currency_id) : undefined,
    };

    createBranch(requestData, {
      onSuccess: () => {
        toast.success("تم إنشاء الفرع بنجاح", {
          description: "تمت إضافة الفرع بنجاح للنظام.",
        });
        form.reset();
        setCoverFile(null);
        setLogoFile(null);
        setStep(0);
        if (coverRef.current) coverRef.current.value = "";
        if (logoRef.current) logoRef.current.value = "";
        onOpenChange(false);
      },
      onError: (error: Error & { message?: string }) => {
        toast.error("حدث خطأ أثناء إنشاء الفرع", {
          description: error.message,
        });
      },
    });
  };

  const vatPreviewAmount =
    vatValueStr && parseFloat(vatValueStr) > 0
      ? vatType === "percentage"
        ? ((1000 * parseFloat(vatValueStr)) / 100).toFixed(2)
        : parseFloat(vatValueStr).toFixed(2)
      : null;

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center" dir="rtl">
      <button
        type="button"
        aria-label="إغلاق"
        className="absolute inset-0 bg-black/40 backdrop-blur-sm border-0 cursor-default"
        onClick={closeModal}
      />

      <div className="relative bg-white rounded-2xl w-full max-w-xl mx-4 max-h-[92vh] flex flex-col overflow-hidden shadow-xl">
        <div className="px-6 pt-6 pb-4 shrink-0">
          <div className="flex items-center justify-between mb-5">
            <div>
              <h2 className="text-lg font-bold text-gray-900">إنشاء فرع جديد</h2>
              <p className="text-xs text-gray-400 mt-0.5">
                الخطوة {step + 1} من {STEPS.length} — {STEPS[step]}
              </p>
            </div>
            <button
              type="button"
              onClick={closeModal}
              className="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 transition-colors"
            >
              <i className="ri-close-line" />
            </button>
          </div>

          <div className="flex items-center gap-0">
            {STEPS.map((label, i) => (
              <div key={label} className="flex items-center flex-1">
                <div className="flex flex-col items-center flex-1">
                  <div
                    className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all ${
                      i < step
                        ? "bg-emerald-600 text-white"
                        : i === step
                          ? "bg-emerald-600 text-white ring-4 ring-emerald-100"
                          : "bg-gray-100 text-gray-400"
                    }`}
                  >
                    {i < step ? (
                      <i className="ri-check-line text-xs" />
                    ) : (
                      i + 1
                    )}
                  </div>
                  <span
                    className={`text-[10px] mt-1 text-center leading-tight ${
                      i === step
                        ? "text-emerald-600 font-semibold"
                        : "text-gray-400"
                    }`}
                  >
                    {label}
                  </span>
                </div>
                {i < STEPS.length - 1 && (
                  <div
                    className={`h-0.5 flex-1 mx-1 mt-[-14px] transition-all ${
                      i < step ? "bg-emerald-500" : "bg-gray-100"
                    }`}
                  />
                )}
              </div>
            ))}
          </div>
        </div>

        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="flex flex-col flex-1 overflow-hidden min-h-0"
          >
            <div className="flex-1 overflow-y-auto px-6 py-4">
              {step === 0 && (
                <div className="space-y-5">
                  <div>
                    <label className="block text-xs font-semibold text-gray-600 mb-2">
                      صورة الغلاف
                      <span className="font-normal text-gray-400 mr-1">
                        (اختياري — تُستخدم كصورة الفرع في النظام)
                      </span>
                    </label>
                    <button
                      type="button"
                      onClick={() => coverRef.current?.click()}
                      className="relative w-full h-36 rounded-xl overflow-hidden border-2 border-dashed border-gray-200 hover:border-emerald-400 cursor-pointer transition-all group bg-gradient-to-br from-gray-50 to-gray-100 block p-0"
                    >
                      {coverPreview ? (
                        <>
                          <img
                            src={coverPreview}
                            alt="غلاف الفرع"
                            className="w-full h-full object-cover object-top"
                          />
                          <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span className="text-white text-xs font-medium flex items-center gap-1.5">
                              <i className="ri-pencil-line" /> تغيير الصورة
                            </span>
                          </div>
                        </>
                      ) : (
                        <div className="flex flex-col items-center justify-center h-full gap-2">
                          <div className="w-10 h-10 flex items-center justify-center bg-white rounded-full text-gray-400 group-hover:text-emerald-500 transition-colors">
                            <i className="ri-image-add-line text-xl" />
                          </div>
                          <p className="text-xs text-gray-400 group-hover:text-emerald-500 transition-colors">
                            انقر لرفع صورة الغلاف
                          </p>
                          <p className="text-[10px] text-gray-300">
                            PNG أو JPG — يُنصح بـ 1200×400 بكسل
                          </p>
                        </div>
                      )}
                    </button>
                    <input
                      ref={coverRef}
                      type="file"
                      accept="image/*"
                      className="hidden"
                      onChange={(e) =>
                        handleImageUpload(
                          "cover",
                          e.target.files?.[0] ?? null
                        )
                      }
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-gray-600 mb-2">
                      شعار الفرع (اللوجو)
                      <span className="font-normal text-gray-400 mr-1">
                        (اختياري — يُستخدم إن لم تختر غلافاً)
                      </span>
                    </label>
                    <div className="flex items-center gap-5">
                      <button
                        type="button"
                        onClick={() => logoRef.current?.click()}
                        className="relative w-20 h-20 rounded-2xl overflow-hidden border-2 border-dashed border-gray-200 hover:border-emerald-400 cursor-pointer transition-all group bg-gradient-to-br from-gray-50 to-gray-100 shrink-0 p-0"
                      >
                        {logoPreview ? (
                          <>
                            <img
                              src={logoPreview}
                              alt="لوجو الفرع"
                              className="w-full h-full object-cover"
                            />
                            <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-2xl">
                              <i className="ri-pencil-line text-white text-sm" />
                            </div>
                          </>
                        ) : (
                          <div className="flex flex-col items-center justify-center h-full gap-1">
                            <div className="w-7 h-7 flex items-center justify-center text-gray-300 group-hover:text-emerald-400 transition-colors">
                              <i className="ri-store-2-line text-xl" />
                            </div>
                            <p className="text-[10px] text-gray-300 text-center leading-tight">
                              رفع لوجو
                            </p>
                          </div>
                        )}
                      </button>
                      <input
                        ref={logoRef}
                        type="file"
                        accept="image/*"
                        className="hidden"
                        onChange={(e) =>
                          handleImageUpload(
                            "logo",
                            e.target.files?.[0] ?? null
                          )
                        }
                      />
                      <div>
                        <p className="text-xs font-medium text-gray-700 mb-1">
                          شعار الفرع
                        </p>
                        <p className="text-xs text-gray-400 leading-relaxed">
                          يُرسل للخادم كصورة الفرع إن لم يُرفع غلاف.
                          <br />
                          يُنصح بصورة مربعة بدقة 512×512 بكسل.
                        </p>
                        <button
                          type="button"
                          onClick={() => logoRef.current?.click()}
                          className="mt-2 text-xs text-emerald-600 hover:text-emerald-700 cursor-pointer flex items-center gap-1 font-medium"
                        >
                          <i className="ri-upload-2-line text-xs" />
                          {logoPreview ? "تغيير الشعار" : "رفع شعار"}
                        </button>
                      </div>
                    </div>
                  </div>

                  {(logoPreview || coverPreview) && (
                    <div>
                      <p className="text-xs font-semibold text-gray-500 mb-2">
                        معاينة البطاقة
                      </p>
                      <div className="rounded-xl overflow-hidden border border-gray-100 w-64">
                        <div className="relative h-24 bg-gradient-to-br from-gray-200 to-gray-300">
                          {coverPreview ? (
                            <img
                              src={coverPreview}
                              alt=""
                              className="w-full h-full object-cover object-top"
                            />
                          ) : null}
                          <div className="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent" />
                        </div>
                        <div className="px-3 pb-3 -mt-5 relative">
                          <div className="w-10 h-10 rounded-xl border-2 border-white overflow-hidden bg-white">
                            {logoPreview ? (
                              <img
                                src={logoPreview}
                                alt=""
                                className="w-full h-full object-cover"
                              />
                            ) : (
                              <div className="w-full h-full flex items-center justify-center bg-gray-100 text-gray-400">
                                <i className="ri-store-2-line text-sm" />
                              </div>
                            )}
                          </div>
                          <p className="text-xs font-bold text-gray-800 mt-1">
                            {form.watch("name") || "اسم الفرع"}
                          </p>
                          <p className="text-[10px] text-gray-400">
                            {selectedCity?.name || "المدينة"}
                          </p>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              )}

              {step === 1 && (
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <FormField
                      control={form.control}
                      name="branch_code"
                      render={({ field }) => (
                        <FormItem>
                          <label className="block text-xs font-medium text-gray-600 mb-1.5">
                            كود الفرع <span className="text-red-500">*</span>
                          </label>
                          <FormControl>
                            <Input
                              placeholder="BR001"
                              dir="ltr"
                              className="rounded-lg py-2.5 font-mono border-gray-200 focus-visible:ring-emerald-100 focus-visible:border-emerald-400"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage className="text-xs flex items-center gap-1" />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name="name"
                      render={({ field }) => (
                        <FormItem>
                          <label className="block text-xs font-medium text-gray-600 mb-1.5">
                            اسم الفرع <span className="text-red-500">*</span>
                          </label>
                          <FormControl>
                            <Input
                              placeholder="مثال: الرياض - الروضة"
                              className="rounded-lg py-2.5 border-gray-200 focus-visible:ring-emerald-100 focus-visible:border-emerald-400"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage className="text-xs" />
                        </FormItem>
                      )}
                    />
                  </div>

                  <FormField
                    control={form.control}
                    name="phone"
                    render={({ field }) => (
                      <FormItem>
                        <label className="block text-xs font-medium text-gray-600 mb-1.5">
                          رقم الهاتف{" "}
                          <span className="text-gray-400 font-normal">
                            (اختياري)
                          </span>
                        </label>
                        <FormControl>
                          <div className="relative">
                            <div className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center text-gray-400 pointer-events-none">
                              <i className="ri-phone-line text-sm" />
                            </div>
                            <Input
                              type="tel"
                              placeholder="05xxxxxxxxx"
                              dir="ltr"
                              className="pr-9 rounded-lg py-2.5 border-gray-200 focus-visible:ring-emerald-100 focus-visible:border-emerald-400"
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
                    name="inventory_name"
                    render={({ field }) => (
                      <FormItem>
                        <label className="block text-xs font-medium text-gray-600 mb-1.5">
                          اسم المخزن <span className="text-red-500">*</span>
                        </label>
                        <FormControl>
                          <div className="relative">
                            <div className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center text-gray-400 pointer-events-none">
                              <i className="ri-archive-line text-sm" />
                            </div>
                            <Input
                              placeholder="مخزن الفرع الرئيسي"
                              className="pr-9 rounded-lg py-2.5 border-gray-200 focus-visible:ring-emerald-100 focus-visible:border-emerald-400"
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
                            placeholder="أي ملاحظات إضافية عن الفرع..."
                            rows={3}
                            maxLength={500}
                            className="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 transition-all resize-none"
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
              )}

              {step === 2 && (
                <div className="space-y-5">
                  <FormField
                    control={form.control}
                    name="currency_id"
                    render={({ field }) => (
                      <FormItem>
                        <label className="block text-xs font-semibold text-gray-600 mb-2">
                          عملة الفرع <span className="text-red-500">*</span>
                        </label>
                        {currenciesQuery.isPending &&
                        allCurrencies.length === 0 ? (
                          <div className="flex justify-center py-8">
                            <Loader2 className="h-8 w-8 animate-spin text-emerald-600" />
                          </div>
                        ) : (
                          <div className="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto">
                            {allCurrencies.map((c) => (
                              <button
                                key={c.id}
                                type="button"
                                onClick={() =>
                                  field.onChange(String(c.id))
                                }
                                className={`px-3 py-2.5 rounded-xl border text-xs font-medium transition-all text-center ${
                                  field.value === String(c.id)
                                    ? "border-emerald-500 bg-emerald-50 text-emerald-700"
                                    : "border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50"
                                }`}
                              >
                                <span className="font-bold block text-sm">
                                  {c.code}
                                </span>
                                <span className="text-[10px] text-gray-400 block mt-0.5 truncate">
                                  {c.name.split(" ")[0]}
                                </span>
                              </button>
                            ))}
                          </div>
                        )}
                        {currenciesQuery.hasNextPage && (
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="w-full mt-2"
                            disabled={currenciesQuery.isFetchingNextPage}
                            onClick={() => currenciesQuery.fetchNextPage()}
                          >
                            {currenciesQuery.isFetchingNextPage ? (
                              <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                              "تحميل المزيد من العملات"
                            )}
                          </Button>
                        )}
                        <FormMessage className="text-xs flex items-center gap-1 mt-1" />
                      </FormItem>
                    )}
                  />

                  <div className="rounded-xl border border-gray-100 overflow-hidden">
                    <button
                      type="button"
                      onClick={() =>
                        form.setValue("vat_enabled", !vatEnabled)
                      }
                      className={`w-full flex items-center justify-between px-4 py-3.5 cursor-pointer transition-colors text-right border-0 ${
                        vatEnabled
                          ? "bg-emerald-50"
                          : "bg-gray-50 hover:bg-gray-100"
                      }`}
                    >
                      <div className="flex items-center gap-3">
                        <div
                          className={`w-5 h-5 rounded flex items-center justify-center border transition-all ${
                            vatEnabled
                              ? "bg-emerald-600 border-emerald-600"
                              : "bg-white border-gray-300"
                          }`}
                        >
                          {vatEnabled && (
                            <i className="ri-check-line text-[10px] text-white" />
                          )}
                        </div>
                        <div>
                          <p className="text-sm font-semibold text-gray-800">
                            ضريبة القيمة المضافة
                          </p>
                          <p className="text-xs text-gray-400">
                            تفعيل الضريبة وتحديد نوعها وقيمتها
                          </p>
                        </div>
                      </div>
                      {vatEnabled && (
                        <span className="text-xs font-semibold text-emerald-600 bg-emerald-100 px-2 py-1 rounded-full">
                          مفعّلة
                        </span>
                      )}
                    </button>

                    {vatEnabled && (
                      <div className="px-4 py-4 bg-white border-t border-gray-100 space-y-4">
                        <FormField
                          control={form.control}
                          name="vat_type"
                          render={({ field }) => (
                            <FormItem>
                              <label className="block text-xs font-medium text-gray-600 mb-2">
                                نوع الضريبة
                              </label>
                              <div className="grid grid-cols-2 gap-3">
                                <button
                                  type="button"
                                  onClick={() =>
                                    field.onChange("percentage")
                                  }
                                  className={`flex items-center gap-2.5 px-4 py-3 rounded-xl border transition-all ${
                                    field.value === "percentage"
                                      ? "border-emerald-500 bg-emerald-50"
                                      : "border-gray-200 hover:border-gray-300"
                                  }`}
                                >
                                  <div
                                    className={`w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 ${
                                      field.value === "percentage"
                                        ? "border-emerald-500"
                                        : "border-gray-300"
                                    }`}
                                  >
                                    {field.value === "percentage" && (
                                      <div className="w-2 h-2 rounded-full bg-emerald-500" />
                                    )}
                                  </div>
                                  <div className="text-right">
                                    <p
                                      className={`text-sm font-semibold ${
                                        field.value === "percentage"
                                          ? "text-emerald-700"
                                          : "text-gray-700"
                                      }`}
                                    >
                                      نسبة مئوية %
                                    </p>
                                    <p className="text-[10px] text-gray-400">
                                      مثال: 15% من المبلغ
                                    </p>
                                  </div>
                                </button>
                                <button
                                  type="button"
                                  onClick={() => field.onChange("fixed")}
                                  className={`flex items-center gap-2.5 px-4 py-3 rounded-xl border transition-all ${
                                    field.value === "fixed"
                                      ? "border-emerald-500 bg-emerald-50"
                                      : "border-gray-200 hover:border-gray-300"
                                  }`}
                                >
                                  <div
                                    className={`w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 ${
                                      field.value === "fixed"
                                        ? "border-emerald-500"
                                        : "border-gray-300"
                                    }`}
                                  >
                                    {field.value === "fixed" && (
                                      <div className="w-2 h-2 rounded-full bg-emerald-500" />
                                    )}
                                  </div>
                                  <div className="text-right">
                                    <p
                                      className={`text-sm font-semibold ${
                                        field.value === "fixed"
                                          ? "text-emerald-700"
                                          : "text-gray-700"
                                      }`}
                                    >
                                      مبلغ ثابت
                                    </p>
                                    <p className="text-[10px] text-gray-400">
                                      مثال: 50 ج.م لكل فاتورة
                                    </p>
                                  </div>
                                </button>
                              </div>
                              <FormMessage className="text-xs" />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name="vat_value"
                          render={({ field }) => (
                            <FormItem>
                              <label className="block text-xs font-medium text-gray-600 mb-1.5">
                                {vatType === "percentage"
                                  ? "نسبة الضريبة"
                                  : "مبلغ الضريبة الثابت"}
                                <span className="text-red-500 mr-1">*</span>
                              </label>
                              <FormControl>
                                <div className="relative">
                                  <Input
                                    type="number"
                                    min={0}
                                    placeholder={
                                      vatType === "percentage" ? "15" : "50"
                                    }
                                    dir="ltr"
                                    className="rounded-xl py-2.5 pl-16 border-emerald-200 bg-emerald-50/50 focus-visible:ring-emerald-100 focus-visible:border-emerald-400"
                                    value={field.value ?? ""}
                                    onChange={field.onChange}
                                  />
                                  <div className="absolute left-3 top-1/2 -translate-y-1/2 bg-emerald-600 text-white text-xs font-bold px-2 py-0.5 rounded-lg">
                                    {vatType === "percentage"
                                      ? "%"
                                      : selectedCurrency?.code || "—"}
                                  </div>
                                </div>
                              </FormControl>
                              <FormMessage className="text-xs flex items-center gap-1" />
                              {vatPreviewAmount && (
                                <div className="mt-2 text-xs text-emerald-700 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-100">
                                  على فاتورة بقيمة{" "}
                                  <strong>1,000 {selectedCurrency?.code ?? ""}</strong>
                                  ، ستكون الضريبة ={" "}
                                  <strong>
                                    {vatType === "percentage"
                                      ? `${vatPreviewAmount} ${selectedCurrency?.code ?? ""}`
                                      : `${vatPreviewAmount} ${selectedCurrency?.code ?? ""}`}
                                  </strong>
                                </div>
                              )}
                            </FormItem>
                          )}
                        />
                      </div>
                    )}
                  </div>
                </div>
              )}

              {step === 3 && (
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
                            <Loader2 className="h-8 w-8 animate-spin text-emerald-600" />
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
                                    ? "border-emerald-500 bg-emerald-50 text-emerald-700"
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
                        <FormMessage className="text-xs flex items-center gap-1 mt-1" />
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
                              <div className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center text-gray-400 pointer-events-none">
                                <i className="ri-road-map-line text-sm" />
                              </div>
                              <Input
                                placeholder="شارع الملك فهد"
                                className="pr-9 rounded-lg py-2.5 border-gray-200 focus-visible:ring-emerald-100 focus-visible:border-emerald-400"
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
                            المبنى / الطابق <span className="text-red-500">*</span>
                          </label>
                          <FormControl>
                            <div className="relative">
                              <div className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center text-gray-400 pointer-events-none">
                                <i className="ri-building-2-line text-sm" />
                              </div>
                              <Input
                                placeholder="برج رقم 5 — الدور 3"
                                className="pr-9 rounded-lg py-2.5 border-gray-200 focus-visible:ring-emerald-100 focus-visible:border-emerald-400"
                                {...field}
                              />
                            </div>
                          </FormControl>
                          <FormMessage className="text-xs" />
                        </FormItem>
                      )}
                    />
                  </div>

                  <div className="bg-gray-50 rounded-xl p-4 border border-gray-100 mt-2">
                    <p className="text-xs font-semibold text-gray-600 mb-3 flex items-center gap-1.5">
                      <i className="ri-file-list-3-line text-emerald-600" />{" "}
                      ملخص الفرع الجديد
                    </p>
                    <div className="space-y-2">
                      {[
                        {
                          label: "الكود",
                          value: form.watch("branch_code") || "—",
                        },
                        {
                          label: "الاسم",
                          value: form.watch("name") || "—",
                        },
                        {
                          label: "العملة",
                          value: selectedCurrency?.code || "—",
                        },
                        {
                          label: "الضريبة",
                          value: vatEnabled
                            ? `${vatValueStr}${vatType === "percentage" ? "%" : ` ${selectedCurrency?.code ?? ""} ثابت`}`
                            : "غير مفعّلة",
                        },
                        {
                          label: "المدينة",
                          value: selectedCity?.name || "—",
                        },
                      ].map((row) => (
                        <div
                          key={row.label}
                          className="flex items-center justify-between gap-2"
                        >
                          <span className="text-xs text-gray-400 shrink-0">
                            {row.label}
                          </span>
                          <span className="text-xs font-semibold text-gray-700 text-left truncate">
                            {row.value}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              )}
            </div>

            <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between shrink-0 bg-white">
              <button
                type="button"
                onClick={step === 0 ? closeModal : handleBack}
                className="px-5 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap flex items-center gap-1.5"
              >
                <i
                  className={step === 0 ? "ri-close-line" : "ri-arrow-right-line"}
                />
                {step === 0 ? "إلغاء" : "السابق"}
              </button>

              <div className="flex items-center gap-1.5">
                {STEPS.map((_, i) => (
                  <div
                    key={i}
                    className={`h-1.5 rounded-full transition-all ${
                      i === step
                        ? "w-5 bg-emerald-500"
                        : i < step
                          ? "w-1.5 bg-emerald-300"
                          : "w-1.5 bg-gray-200"
                    }`}
                  />
                ))}
              </div>

              {step < STEPS.length - 1 ? (
                <button
                  type="button"
                  onClick={handleNext}
                  className="px-5 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors whitespace-nowrap flex items-center gap-1.5"
                >
                  التالي <i className="ri-arrow-left-line" />
                </button>
              ) : (
                <Button
                  type="submit"
                  disabled={isPending}
                  className="px-5 py-2 h-auto bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg"
                >
                  {isPending ? (
                    <>
                      <Loader2 className="ml-2 h-4 w-4 animate-spin" />
                      جاري الإنشاء...
                    </>
                  ) : (
                    <>
                      <i className="ri-add-circle-line ml-1" /> إنشاء الفرع
                    </>
                  )}
                </Button>
              )}
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
}
