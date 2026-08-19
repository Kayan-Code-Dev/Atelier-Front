import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useMutation } from "@tanstack/react-query";
import { toast } from "sonner";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { DatePicker } from "@/components/custom/DatePicker";
import { useTerminateEmployeeQueryOptions } from "@/api/v2/employees/employees.hooks";
import { TEmployee } from "@/api/v2/employees/employees.types";
import { cn } from "@/lib/utils";
import { Loader2 } from "lucide-react";

const formSchema = z.object({
  termination_date: z.string().min(1, { message: "تاريخ إنهاء الخدمة مطلوب" }),
  reason: z.string().min(1, { message: "سبب إنهاء الخدمة مطلوب" }),
});

type FormValues = z.infer<typeof formSchema>;

const inputClass =
  "w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 transition-all";

const labelClass = "text-xs font-medium text-gray-600 mb-1.5 block";

type Props = {
  employee: TEmployee;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSuccess?: () => void;
};

export function TerminateEmployeeModal({
  employee,
  open,
  onOpenChange,
  onSuccess,
}: Props) {
  const { mutate: terminateEmployee, isPending } = useMutation(
    useTerminateEmployeeQueryOptions(),
  );

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      termination_date: "",
      reason: "",
    },
  });

  const onSubmit = (values: FormValues) => {
    terminateEmployee(
      {
        id: employee.id,
        data: {
          termination_date: values.termination_date,
          reason: values.reason,
        },
      },
      {
        onSuccess: () => {
          toast.success("تم إنهاء خدمة الموظف بنجاح", {
            description: `تم إنهاء خدمة ${employee.user.name} بنجاح.`,
          });
          form.reset();
          onOpenChange(false);
          onSuccess?.();
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء إنهاء خدمة الموظف", {
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
        className="sm:max-w-lg p-0 gap-0 overflow-hidden rounded-2xl border-gray-100"
        dir="rtl"
      >
        <DialogHeader className="sr-only">
          <DialogTitle>إنهاء خدمة الموظف</DialogTitle>
          <DialogDescription>
            تأكيد إنهاء خدمة الموظف: {employee.user.name}
          </DialogDescription>
        </DialogHeader>

        <div
          className="px-5 py-4 flex items-center justify-between border-b border-red-100"
          style={{ background: "#FEF2F2" }}
        >
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl flex items-center justify-center bg-white/80 border border-red-100">
              <i className="ri-user-unfollow-line text-lg text-red-600" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-gray-900">
                إنهاء خدمة الموظف
              </h2>
              <p className="text-xs text-red-700/80 mt-0.5">
                {employee.user.name}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => {
              form.reset();
              onOpenChange(false);
            }}
            className="w-9 h-9 rounded-xl flex items-center justify-center text-gray-500 hover:bg-white/80 border border-red-100 transition-colors"
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-lg" />
          </button>
        </div>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)}>
            <div className="px-5 py-4 space-y-4 bg-gray-50/50">
              <div className="rounded-xl border border-gray-100 bg-white p-4 space-y-4">
                <p className="text-xs text-gray-500 leading-relaxed">
                  سيتم تسجيل تاريخ إنهاء الخدمة والسبب في سجل الموظف. تأكد من
                  صحة البيانات قبل التأكيد.
                </p>
                <FormField
                  control={form.control}
                  name="termination_date"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        تاريخ إنهاء الخدمة{" "}
                        <span className="text-red-500">*</span>
                      </FormLabel>
                      <FormControl>
                        <DatePicker
                          value={field.value ? new Date(field.value) : undefined}
                          onChange={(date) => {
                            field.onChange(
                              date ? date.toISOString().split("T")[0] : "",
                            );
                          }}
                          placeholder="اختر تاريخ إنهاء الخدمة"
                          showLabel={false}
                          disabled={isPending}
                          allowFutureDates={true}
                          allowPastDates={true}
                          buttonClassName={cn(
                            inputClass,
                            "w-full justify-start font-normal text-right",
                          )}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="reason"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        سبب إنهاء الخدمة{" "}
                        <span className="text-red-500">*</span>
                      </FormLabel>
                      <FormControl>
                        <Textarea
                          placeholder="أدخل سبب إنهاء الخدمة..."
                          className={cn(inputClass, "min-h-[100px] resize-y")}
                          {...field}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </div>

            <div
              className="flex flex-col-reverse sm:flex-row gap-3 px-5 py-4 border-t border-gray-100 bg-white"
            >
              <button
                type="button"
                onClick={() => {
                  form.reset();
                  onOpenChange(false);
                }}
                disabled={isPending}
                className="flex-1 py-2.5 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors disabled:opacity-50"
              >
                إلغاء
              </button>
              <button
                type="submit"
                disabled={isPending}
                className="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-red-600 hover:bg-red-700 transition-colors disabled:opacity-60 flex items-center justify-center gap-2"
              >
                {isPending ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    جاري المعالجة...
                  </>
                ) : (
                  <>
                    <i className="ri-check-line text-lg" />
                    تأكيد إنهاء الخدمة
                  </>
                )}
              </button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
