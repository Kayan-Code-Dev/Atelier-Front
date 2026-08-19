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
import { useMarkEmployeeCustodyAsLostMutationOptions } from "@/api/v2/employees/employee-custodies/employee-custodies.hooks";
import { TEmployeeCustody } from "@/api/v2/employees/employee-custodies/employee-custodies.types";
import { toast } from "sonner";
import { cn } from "@/lib/utils";

const FORM_ID = "mark-custody-lost-form";

const formSchema = z.object({
  notes: z.string().min(1, { message: "الملاحظات مطلوبة" }),
});

type Props = {
  employeeCustody: TEmployeeCustody | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function MarkAsLostModal({ employeeCustody, open, onOpenChange }: Props) {
  const { mutate: markAsLost, isPending } = useMutation(
    useMarkEmployeeCustodyAsLostMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      notes: "",
    },
  });

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!employeeCustody) return;

    markAsLost(
      {
        id: employeeCustody.id,
        notes: values.notes,
      },
      {
        onSuccess: () => {
          toast.success("تم تسجيل فقدان الضمان بنجاح", {
            description: "تم تحديث حالة الضمان إلى 'مفقود'.",
          });
          form.reset();
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء تسجيل الفقدان", {
            description: error.message,
          });
        },
      }
    );
  };

  if (!employeeCustody) return null;

  const inputClass =
    "border-gray-200 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:border-amber-300";

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton={false}
        className="sm:max-w-2xl rounded-2xl border-gray-100 p-0 gap-0 overflow-hidden"
        bodyClassName="p-0 max-h-[min(90vh,560px)] overflow-y-auto"
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
              className="bg-amber-700 hover:bg-amber-800 text-white"
            >
              {isPending ? "جاري التسجيل..." : "تسجيل الفقدان"}
            </Button>
          </div>
        }
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 flex items-center justify-center rounded-xl bg-amber-50">
              <i className="ri-question-line text-amber-700 text-lg" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-gray-800">تسجيل فقدان الضمان</h2>
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
                name="notes"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>ملاحظات الفقدان</FormLabel>
                    <FormControl>
                      <Textarea
                        placeholder="أدخل ملاحظات حول فقدان الضمان..."
                        className={cn(inputClass, "min-h-[120px]")}
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
