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
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useEffect } from "react";
import { useMutation } from "@tanstack/react-query";
import { useCreateCustodyMutationOptions } from "@/api/v2/custody/custody.hooks";
import {
  TCreateCustodyRequest,
  TCustodyType,
} from "@/api/v2/custody/custody.types";
import { toast } from "sonner";
import { UploadFileField } from "@/components/custom/UploadFile";
import { cn } from "@/lib/utils";

const custodyTypes: TCustodyType[] = ["money", "physical_item", "document"];

const custodyTypeLabels: Record<TCustodyType, string> = {
  money: "مال",
  physical_item: "عنصر مادي",
  document: "مستند",
};

const custodyTypeIcons: Record<TCustodyType, string> = {
  money: "ri-money-dollar-circle-line",
  physical_item: "ri-shirt-line",
  document: "ri-file-shield-2-line",
};

// Schema for the form
const formSchema = z
  .object({
    type: z.enum(["money", "physical_item", "document"], {
      required_error: "نوع الضمان مطلوب",
    }),
    description: z.string().min(1, { message: "الوصف مطلوب" }),
    value: z.number().optional(),
    photos: z.array(z.instanceof(File)).optional(),
    notes: z.string().min(1, { message: "الملاحظات مطلوبة" }),
  })
  .refine(
    (data) => {
      if (data.type === "money") {
        return data.value !== undefined && data.value > 0;
      }
      return true;
    },
    {
      message: "القيمة مطلوبة لنوع المال",
      path: ["value"],
    }
  )
  .refine(
    (data) => {
      if (data.type === "physical_item" || data.type === "document") {
        return data.photos && data.photos.length > 0;
      }
      return true;
    },
    {
      message: "الصور مطلوبة (حد أقصى صورتان)",
      path: ["photos"],
    }
  )
  .refine(
    (data) => {
      if (data.photos) {
        return data.photos.length <= 2;
      }
      return true;
    },
    {
      message: "الحد الأقصى صورتان",
      path: ["photos"],
    }
  );

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  orderId: number;
  /** Called after successful create (e.g. to refetch parent list) */
  onSuccess?: () => void;
  
  currencySymbol?: string;
};

const fieldLabelClass =
  "text-[11px] font-semibold uppercase tracking-wide text-slate-500";

const controlClass =
  "border-slate-200 bg-white shadow-none focus-visible:border-slate-300 focus-visible:ring-[3px] focus-visible:ring-emerald-500/15";

export function CreateCustodyModal({
  open,
  onOpenChange,
  orderId,
  onSuccess,
  currencySymbol = "ج.م",
}: Props) {
  const { mutate: createCustody, isPending } = useMutation(
    useCreateCustodyMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      type: "money",
      description: "",
      value: undefined,
      photos: undefined,
      notes: "",
    },
  });

  const custodyType = form.watch("type");

  // Clear conditional fields when type changes
  useEffect(() => {
    if (custodyType === "money") {
      form.setValue("photos", undefined);
    } else {
      form.setValue("value", undefined);
    }
  }, [custodyType, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    const requestData: TCreateCustodyRequest = {
      type: values.type,
      description: values.description,
      notes: values.notes,
      ...(values.type === "money" && { value: values.value }),
      ...((values.type === "physical_item" || values.type === "document") &&
        values.photos && { photos: values.photos }),
    };

    createCustody(
      {
        order_id: orderId,
        data: requestData,
      },
      {
        onSuccess: () => {
          toast.success("تم إنشاء الضمان بنجاح", {
            description: "تمت إضافة الضمان بنجاح للنظام.",
          });
          form.reset();
          onOpenChange(false);
          onSuccess?.();
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء إنشاء الضمان", {
            description: error.message,
          });
        },
      }
    );
  };

  const handleOpenChange = (newOpen: boolean) => {
    if (!newOpen) {
      form.reset();
    }
    onOpenChange(newOpen);
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent
        className="gap-0 border-slate-200 sm:max-w-2xl"
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
                  إضافة ضمان
                </DialogTitle>
                <DialogDescription className="text-sm leading-relaxed text-slate-500">
                  طلب رقم{" "}
                  <span className="font-semibold text-slate-700">#{orderId}</span>
                  — سجّل مبلغًا أو عنصرًا ماديًا أو مستندًا كضمان للطلب.
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
            <FormField
              control={form.control}
              name="type"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <FormLabel className={fieldLabelClass}>نوع الضمان</FormLabel>
                  <FormControl>
                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                      {custodyTypes.map((type) => {
                        const selected = field.value === type;
                        return (
                          <button
                            key={type}
                            type="button"
                            onClick={() => field.onChange(type)}
                            className={cn(
                              "flex flex-col items-center gap-2 rounded-lg border-2 p-3.5 text-center transition-all",
                              selected
                                ? "border-indigo-500 bg-indigo-50/80 text-indigo-900 shadow-sm"
                                : "border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50/80"
                            )}
                          >
                            <i
                              className={cn(
                                custodyTypeIcons[type],
                                "text-2xl",
                                selected ? "text-indigo-600" : "text-slate-400"
                              )}
                              aria-hidden
                            />
                            <span className="text-xs font-semibold">
                              {custodyTypeLabels[type]}
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
              name="description"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <FormLabel className={fieldLabelClass}>الوصف</FormLabel>
                  <FormControl>
                    <Textarea
                      placeholder="وصف مختصر للضمان"
                      className={cn("min-h-[88px] resize-y rounded-lg", controlClass)}
                      {...field}
                      rows={3}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {custodyType === "money" && (
              <FormField
                control={form.control}
                name="value"
                render={({ field }) => (
                  <FormItem className="space-y-2">
                    <FormLabel className={fieldLabelClass}>
                      القيمة ({currencySymbol})
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder="0"
                        className={cn("h-10 rounded-lg", controlClass)}
                        value={field.value ?? ""}
                        onChange={(e) => {
                          const val = e.target.value.replace(/[^0-9.]/g, "");
                          const value = val ? parseFloat(val) : undefined;
                          field.onChange(value);
                        }}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            )}

            {(custodyType === "physical_item" ||
              custodyType === "document") && (
              <div className="rounded-lg border border-slate-200 bg-slate-50/40 p-4">
                <FormField
                  control={form.control}
                  name="photos"
                  render={() => (
                    <FormItem className="space-y-2">
                      <FormLabel className={fieldLabelClass}>
                        صور الضمان (حتى صورتان)
                      </FormLabel>
                      <FormControl>
                        <UploadFileField
                          name="photos"
                          multiple
                          maxFiles={2}
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
            )}

            <FormField
              control={form.control}
              name="notes"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <FormLabel className={fieldLabelClass}>ملاحظات داخلية</FormLabel>
                  <FormControl>
                    <Textarea
                      placeholder="تفاصيل إضافية للفريق"
                      className={cn("min-h-[88px] resize-y rounded-lg", controlClass)}
                      {...field}
                      rows={3}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

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
                حفظ الضمان
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
