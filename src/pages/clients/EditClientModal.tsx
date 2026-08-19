import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
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
import {
  TClientResponse,
  TClientSource,
  CLIENT_SOURCES,
  CLIENT_SOURCE_LABELS,
  TUpdateClientRequest,
} from "@/api/v2/clients/clients.types";
import { useUpdateClientMutationOptions } from "@/api/v2/clients/clients.hooks";
import { toast } from "sonner";
import { CitiesSelect } from "@/components/custom/CitiesSelect";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";

const inputClass =
  "rounded-xl border-[1.5px] border-transparent bg-[#F5F4F0] text-sm shadow-none focus-visible:border-[#D4AF37] focus-visible:ring-0";

const formSchema = z.object({
  name: z.string().min(1, { message: "الاسم مطلوب" }),
  date_of_birth: z.string().optional(),
  national_id: z.string().optional(),
  source: z.enum(CLIENT_SOURCES),
  address: z.string().min(1, { message: "العنوان مطلوب" }),
  city_id: z.string({ required_error: "المدينة مطلوبة" }),
  notes: z.string().optional(),
  phone: z.string().min(1, { message: "رقم الهاتف مطلوب" }),
  phone2: z.string().optional(),
});

type Props = {
  client: TClientResponse | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function EditClientModal({ client, open, onOpenChange }: Props) {
  const { mutate: updateClient, isPending } = useMutation(
    useUpdateClientMutationOptions(),
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      date_of_birth: "",
      national_id: "",
      source: "other",
      address: "",
      city_id: "",
      notes: "",
      phone: "",
      phone2: "",
    },
  });

  const displayName =
    client?.name ??
    [client?.first_name, client?.middle_name, client?.last_name]
      .filter(Boolean)
      .join(" ")
      .trim() ??
    "";

  useEffect(() => {
    if (client && open) {
      const fullName =
        client.name?.trim() ??
        [client.first_name, client.middle_name, client.last_name]
          .filter(Boolean)
          .join(" ")
          .trim();
      const addressStr = client.address
        ? [client.address.street, client.address.building]
            .filter(Boolean)
            .join("، ") || ""
        : "";
      form.reset({
        name: fullName || "",
        date_of_birth: client.date_of_birth || "",
        national_id: client.national_id || "",
        source: (client.source as TClientSource) || "other",
        address: addressStr,
        city_id: client.address ? String(client.address.city_id) : "",
        notes: client.address?.notes || "",
        phone: client.phones?.[0]?.phone || "",
        phone2: client.phones?.[1]?.phone || "",
      });
    }
  }, [client, open, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!client) return;

    const phones: { phone: string; type: string }[] = [];
    if (values.phone?.trim()) {
      phones.push({ phone: values.phone.trim(), type: "mobile" });
    }
    if (values.phone2?.trim()) {
      phones.push({ phone: values.phone2.trim(), type: "whatsapp" });
    }

    const requestData: TUpdateClientRequest = {
      name: values.name.trim(),
      date_of_birth: values.date_of_birth || undefined,
      national_id: values.national_id?.trim() || undefined,
      source: values.source as TClientSource,
      address: {
        city_id: Number(values.city_id),
        address: values.address.trim(),
      },
      phones,
    };

    updateClient(
      { id: client.id, data: requestData },
      {
        onSuccess: () => {
          toast.success("تم تحديث العميل بنجاح", {
            description: `تم تحديث العميل "${values.name.trim()}" بنجاح.`,
          });
          form.reset();
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء تحديث العميل", {
            description: error.message,
          });
        },
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton={false}
        bodyClassName="p-0 min-h-0"
        className="sm:max-w-2xl p-0 gap-0 overflow-hidden rounded-2xl border-[#F0EDE5]"
      >
        <DialogHeader className="sr-only">
          <DialogTitle>تعديل العميل</DialogTitle>
          <DialogDescription>تعديل بيانات العميل وحفظ التغييرات</DialogDescription>
        </DialogHeader>

        <div
          className="px-5 py-4 flex items-center justify-between border-b"
          style={{ borderColor: "#F0EDE5", background: "#F8F7F4" }}
        >
          <div className="flex items-center gap-3">
            <div
              className="w-10 h-10 rounded-xl flex items-center justify-center"
              style={{ background: "#FFFBEB" }}
            >
              <i className="ri-edit-2-line text-lg" style={{ color: "#D4AF37" }} />
            </div>
            <div>
              <h2 className="text-lg font-bold" style={{ color: "#1A1A2E" }}>
                تعديل بيانات العميل
              </h2>
              <p className="text-xs mt-0.5" style={{ color: "#9CA3AF" }}>
                {displayName || "—"}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            className="w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer transition-colors hover:opacity-80"
            style={{ background: "#F5F4F0", color: "#6B7280" }}
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-lg" />
          </button>
        </div>

        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="px-5 py-4 space-y-5 max-h-[min(70vh,560px)] overflow-y-auto"
            dir="rtl"
          >
            <div
              className="rounded-xl p-4 space-y-4"
              style={{ background: "#F8F7F4", border: "1px solid #F0EDE5" }}
            >
              <p
                className="text-xs font-bold flex items-center gap-2"
                style={{ color: "#1A1A2E" }}
              >
                <i className="ri-user-line" style={{ color: "#D4AF37" }} />
                البيانات الأساسية
              </p>
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-xs font-semibold text-[#6B7280]">
                      الاسم
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder="الاسم الكامل للعميل"
                        className={inputClass}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <FormField
                  control={form.control}
                  name="date_of_birth"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-xs font-semibold text-[#6B7280]">
                        تاريخ الميلاد
                      </FormLabel>
                      <FormControl>
                        <Input type="date" className={inputClass} {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="national_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-xs font-semibold text-[#6B7280]">
                        الرقم القومي
                      </FormLabel>
                      <FormControl>
                        <Input className={inputClass} {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="source"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-xs font-semibold text-[#6B7280]">
                        المصدر
                      </FormLabel>
                      <Select
                        onValueChange={field.onChange}
                        value={field.value}
                      >
                        <FormControl>
                          <SelectTrigger
                            className={cn(
                              inputClass,
                              "h-10 w-full data-placeholder:text-muted-foreground",
                            )}
                          >
                            <SelectValue placeholder="اختر المصدر" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {CLIENT_SOURCES.map((source) => (
                            <SelectItem key={source} value={source}>
                              {CLIENT_SOURCE_LABELS[source]}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </div>

            <div
              className="rounded-xl p-4 space-y-4"
              style={{ background: "#F8F7F4", border: "1px solid #F0EDE5" }}
            >
              <p
                className="text-xs font-bold flex items-center gap-2"
                style={{ color: "#1A1A2E" }}
              >
                <i className="ri-phone-line" style={{ color: "#3B82F6" }} />
                أرقام الهاتف
              </p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="phone"
                  render={({ field }) => (
                    <FormItem dir="ltr">
                      <FormLabel className="text-xs font-semibold text-[#6B7280]">
                        رقم الهاتف
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="أدخل رقم الهاتف"
                          dir="ltr"
                          className={inputClass}
                          {...field}
                          value={field.value ?? ""}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="phone2"
                  render={({ field }) => (
                    <FormItem dir="ltr">
                      <FormLabel className="text-xs font-semibold text-[#6B7280]">
                        رقم الواتس (اختياري)
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="رقم إضافي"
                          dir="ltr"
                          className={inputClass}
                          {...field}
                          value={field.value ?? ""}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </div>

            <div
              className="rounded-xl p-4 space-y-4"
              style={{ background: "#F8F7F4", border: "1px solid #F0EDE5" }}
            >
              <p
                className="text-xs font-bold flex items-center gap-2"
                style={{ color: "#1A1A2E" }}
              >
                <i className="ri-map-pin-line" style={{ color: "#10B981" }} />
                العنوان
              </p>
              <FormField
                control={form.control}
                name="address"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-xs font-semibold text-[#6B7280]">
                      العنوان
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder="أدخل العنوان الكامل"
                        className={inputClass}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="city_id"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-xs font-semibold text-[#6B7280]">
                      المدينة
                    </FormLabel>
                    <FormControl>
                      <CitiesSelect
                        value={field.value}
                        onChange={field.onChange}
                        disabled={isPending}
                        className={cn(
                          inputClass,
                          "h-10 w-full data-placeholder:text-muted-foreground",
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
                  <FormItem>
                    <FormLabel className="text-xs font-semibold text-[#6B7280]">
                      ملاحظات (اختياري)
                    </FormLabel>
                    <FormControl>
                      <Textarea
                        className={cn(inputClass, "min-h-[88px] resize-y")}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <div
              className="flex flex-col-reverse sm:flex-row gap-3 pt-2 pb-1"
              style={{ borderTop: "1px solid #F0EDE5" }}
            >
              <button
                type="button"
                onClick={() => onOpenChange(false)}
                disabled={isPending}
                className="flex-1 py-2.5 rounded-xl font-semibold text-sm cursor-pointer disabled:opacity-50"
                style={{ background: "#F5F4F0", color: "#6B7280" }}
              >
                إلغاء
              </button>
              <button
                type="submit"
                disabled={isPending}
                className="flex-1 py-2.5 rounded-xl font-bold text-sm cursor-pointer gold-gradient-btn disabled:opacity-50"
                style={{ color: "#1A1A2E" }}
              >
                {isPending ? (
                  <span className="inline-flex items-center justify-center gap-2">
                    <i className="ri-loader-4-line animate-spin" />
                    جاري الحفظ...
                  </span>
                ) : (
                  "حفظ التغييرات"
                )}
              </button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
