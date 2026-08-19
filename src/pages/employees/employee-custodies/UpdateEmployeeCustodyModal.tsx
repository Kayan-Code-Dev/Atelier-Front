import {
  Dialog,
  DialogContent,
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
import { useMutation } from "@tanstack/react-query";
import { useUpdateEmployeeCustodyMutationOptions } from "@/api/v2/employees/employee-custodies/employee-custodies.hooks";
import {
  TEmployeeCustody,
  TUpdateEmployeeCustody,
} from "@/api/v2/employees/employee-custodies/employee-custodies.types";
import { toast } from "sonner";
import { useEffect } from "react";
import { DatePicker } from "@/components/custom/DatePicker";
import { getCustodyTypeVisual } from "./custodyDisplayConfig";
import { cn } from "@/lib/utils";

const FORM_ID = "employee-custody-update-form";

const formSchema = z.object({
  name: z.string().min(1, { message: "الاسم مطلوب" }),
  description: z.string().optional(),
  serial_number: z.string().min(1, { message: "الرقم التسلسلي مطلوب" }),
  value: z.number().min(0, { message: "القيمة يجب أن تكون أكبر من أو تساوي صفر" }),
  expected_return_date: z.date({ required_error: "تاريخ الإرجاع المتوقع مطلوب" }),
  notes: z.string().optional(),
});

type Props = {
  employeeCustody: TEmployeeCustody | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function UpdateEmployeeCustodyModal({
  employeeCustody,
  open,
  onOpenChange,
}: Props) {
  const { mutate: updateEmployeeCustody, isPending } = useMutation(
    useUpdateEmployeeCustodyMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      description: "",
      serial_number: "",
      value: 0,
      expected_return_date: undefined,
      notes: "",
    },
  });

  useEffect(() => {
    if (employeeCustody && open) {
      form.reset({
        name: employeeCustody.name,
        description: employeeCustody.description || "",
        serial_number: employeeCustody.serial_number,
        value: employeeCustody.value,
        expected_return_date: new Date(employeeCustody.expected_return_date),
        notes: employeeCustody.notes || "",
      });
    }
  }, [employeeCustody, open, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!employeeCustody) return;

    const requestData: TUpdateEmployeeCustody = {
      name: values.name,
      description: values.description || "",
      serial_number: values.serial_number,
      value: values.value,
      expected_return_date: values.expected_return_date.toISOString().split("T")[0],
      notes: values.notes || "",
    };

    updateEmployeeCustody(
      { id: employeeCustody.id, data: requestData },
      {
        onSuccess: () => {
          toast.success("تم تحديث الضمان بنجاح", {
            description: "تم تحديث بيانات الضمان بنجاح.",
          });
          form.reset();
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء تحديث الضمان", {
            description: error.message,
          });
        },
      }
    );
  };

  if (!employeeCustody) return null;

  const headerVisual = getCustodyTypeVisual(employeeCustody.type);
  const inputClass =
    "border-gray-200 focus-visible:ring-2 focus-visible:ring-violet-200 focus-visible:border-violet-300";

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton={false}
        className="sm:max-w-3xl rounded-2xl border-gray-100 p-0 gap-0 overflow-hidden"
        bodyClassName="p-0 max-h-[min(90vh,800px)] overflow-y-auto"
        footer={
          <div className="flex gap-3 justify-end px-6 py-4 border-t border-gray-100 bg-white">
            <Button
              type="button"
              variant="outline"
              className="border-gray-200 text-gray-600"
              onClick={() => onOpenChange(false)}
            >
              إلغاء
            </Button>
            <Button
              type="submit"
              form={FORM_ID}
              disabled={isPending}
              className="bg-violet-600 hover:bg-violet-700 text-white"
            >
              {isPending ? "جاري التحديث..." : "حفظ التعديلات"}
            </Button>
          </div>
        }
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-10">
          <div className="flex items-center gap-3 min-w-0">
            <div
              className={cn(
                "w-9 h-9 flex items-center justify-center rounded-xl shrink-0",
                headerVisual.bg
              )}
            >
              <i className={cn(headerVisual.icon, headerVisual.color, "text-lg")} />
            </div>
            <div className="min-w-0">
              <h2 className="text-lg font-bold text-gray-800 truncate">تجديد / تحديث الضمان</h2>
              <p className="text-xs text-gray-500 truncate">{employeeCustody.name}</p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors shrink-0"
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-gray-500 text-lg" />
          </button>
        </div>

        <div className="px-6 pb-6 pt-5">
          <Form {...form}>
            <form
              id={FORM_ID}
              onSubmit={form.handleSubmit(onSubmit)}
              className="space-y-4"
              dir="rtl"
            >
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>الاسم</FormLabel>
                    <FormControl>
                      <Input
                        placeholder="اسم الضمان"
                        className={inputClass}
                        {...field}
                        disabled={isPending}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="description"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>الوصف (اختياري)</FormLabel>
                    <FormControl>
                      <Textarea
                        placeholder="وصف الضمان..."
                        className={inputClass}
                        {...field}
                        disabled={isPending}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="serial_number"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>الرقم التسلسلي</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="الرقم التسلسلي"
                          className={inputClass}
                          {...field}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="value"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>القيمة (ج.م)</FormLabel>
                      <FormControl>
                        <div className="relative">
                          <Input
                            placeholder="0"
                            className={cn(inputClass, "pl-12")}
                            value={field.value ?? ""}
                            onChange={(e) => {
                              const val = e.target.value.replace(/[^0-9.]/g, "");
                              field.onChange(val === "" ? 0 : Number(val) || 0);
                            }}
                            disabled={isPending}
                          />
                          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">
                            ج.م
                          </span>
                        </div>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <FormField
                control={form.control}
                name="expected_return_date"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>تاريخ الإرجاع المتوقع</FormLabel>
                    <FormControl>
                      <DatePicker
                        value={field.value}
                        onChange={field.onChange}
                        placeholder="اختر تاريخ الإرجاع المتوقع"
                        showLabel={false}
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
                    <FormLabel>ملاحظات (اختياري)</FormLabel>
                    <FormControl>
                      <Textarea
                        placeholder="ملاحظات إضافية..."
                        className={inputClass}
                        {...field}
                        disabled={isPending}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </form>
          </Form>
        </div>
      </DialogContent>
    </Dialog>
  );
}
