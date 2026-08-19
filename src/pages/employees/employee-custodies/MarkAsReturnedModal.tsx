import { Dialog, DialogContent } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
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
import { useMarkEmployeeCustodyAsReturnedMutationOptions } from "@/api/v2/employees/employee-custodies/employee-custodies.hooks";
import {
  TEmployeeCustody,
  TEmployeeCustodyConditionOnAssignment,
} from "@/api/v2/employees/employee-custodies/employee-custodies.types";
import { toast } from "sonner";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";

const FORM_ID = "mark-custody-returned-form";

const CONDITION_OPTIONS: { value: TEmployeeCustodyConditionOnAssignment; label: string }[] = [
  { value: "new", label: "جديد" },
  { value: "good", label: "جيد" },
  { value: "fair", label: "مقبول" },
  { value: "poor", label: "ضعيف" },
];

const formSchema = z.object({
  condition_on_return: z.enum(["new", "good", "fair", "poor"]),
  return_notes: z.string().min(1, { message: "ملاحظات الإرجاع مطلوبة" }),
});

type Props = {
  employeeCustody: TEmployeeCustody | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function MarkAsReturnedModal({ employeeCustody, open, onOpenChange }: Props) {
  const { mutate: markAsReturned, isPending } = useMutation(
    useMarkEmployeeCustodyAsReturnedMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      condition_on_return: "good",
      return_notes: "",
    },
  });

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!employeeCustody) return;

    markAsReturned(
      {
        id: employeeCustody.id,
        data: {
          condition_on_return: values.condition_on_return,
          return_notes: values.return_notes,
        },
      },
      {
        onSuccess: () => {
          toast.success("تم تسجيل إرجاع الضمان بنجاح", {
            description: "تم تحديث حالة الضمان إلى 'مرجع'.",
          });
          form.reset();
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء تسجيل الإرجاع", {
            description: error.message,
          });
        },
      }
    );
  };

  if (!employeeCustody) return null;

  const inputClass =
    "border-gray-200 focus-visible:ring-2 focus-visible:ring-sky-200 focus-visible:border-sky-300";

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton={false}
        className="sm:max-w-2xl rounded-2xl border-gray-100 p-0 gap-0 overflow-hidden"
        bodyClassName="p-0 max-h-[min(90vh,640px)] overflow-y-auto"
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
              className="bg-sky-600 hover:bg-sky-700 text-white"
            >
              {isPending ? "جاري التسجيل..." : "تسجيل الإرجاع"}
            </Button>
          </div>
        }
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 flex items-center justify-center rounded-xl bg-sky-50">
              <i className="ri-arrow-go-back-line text-sky-600 text-lg" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-gray-800">تسجيل إرجاع الضمان</h2>
              <p className="text-xs text-gray-500">
                الموظف: {employeeCustody.employee?.user?.name || "—"}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
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
                name="condition_on_return"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>الحالة عند الإرجاع</FormLabel>
                    <Select
                      onValueChange={field.onChange}
                      value={field.value}
                      disabled={isPending}
                    >
                      <FormControl>
                        <SelectTrigger className={inputClass}>
                          <SelectValue placeholder="اختر الحالة" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        {CONDITION_OPTIONS.map((option) => (
                          <SelectItem key={option.value} value={option.value}>
                            {option.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="return_notes"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>ملاحظات الإرجاع</FormLabel>
                    <FormControl>
                      <Textarea
                        placeholder="أدخل ملاحظات حول حالة الضمان عند الإرجاع..."
                        className={cn(inputClass, "min-h-[100px]")}
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
