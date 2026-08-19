import { Dialog, DialogContent } from "@/components/ui/dialog";
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
import { useMutation } from "@tanstack/react-query";
import { useCreateSimpleSalaryDeductionMutationOptions } from "@/api/simple-salary/simple-salary.hooks";
import { toast } from "sonner";
import { PERIOD_REGEX } from "../constants";
import {
  addDeductionSchema,
  getAddDeductionDefaultValues,
  type AddDeductionFormValues,
} from "../simpleSalary.schema";

const FORM_ID = "simple-salary-add-deduction-form";

export type AddSimpleSalaryDeductionModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  employeeId: number;
  employeeName: string;
  period: string;
  onSuccess?: () => void;
};

export function AddSimpleSalaryDeductionModal({
  open,
  onOpenChange,
  employeeId,
  employeeName,
  period,
  onSuccess,
}: AddSimpleSalaryDeductionModalProps) {
  const mutation = useMutation(useCreateSimpleSalaryDeductionMutationOptions());

  const form = useForm<AddDeductionFormValues>({
    resolver: zodResolver(addDeductionSchema),
    defaultValues: getAddDeductionDefaultValues(),
  });

  const handleSubmit = form.handleSubmit((values) => {
    if (!PERIOD_REGEX.test(period)) {
      toast.error("صيغة الشهر غير صحيحة (YYYY-MM)");
      return;
    }
    mutation.mutate(
      {
        employee_id: employeeId,
        period,
        amount: values.amount,
        reason: values.reason,
        date: values.date,
        notes: values.notes || undefined,
      },
      {
        onSuccess: (result) => {
          if (result?.deduction) {
            toast.success(result.message ?? "تمت إضافة الخصم.");
            form.reset(getAddDeductionDefaultValues());
            onOpenChange(false);
            onSuccess?.();
          }
        },
      }
    );
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton={false}
        className="gap-0 overflow-hidden rounded-2xl border-gray-100 p-0 sm:max-w-md"
        bodyClassName="p-0"
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
              disabled={mutation.isPending}
              className="bg-orange-500 hover:bg-orange-600 text-white"
            >
              {mutation.isPending ? "جاري الحفظ..." : "إضافة الخصم"}
            </Button>
          </div>
        }
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h2 className="text-lg font-bold text-gray-800">إضافة خصم</h2>
            <p className="text-xs text-gray-500 mt-0.5">
              {employeeName} — الفترة {period}
            </p>
          </div>
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500"
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-lg" />
          </button>
        </div>
        <Form {...form}>
          <form
            id={FORM_ID}
            onSubmit={handleSubmit}
            className="space-y-5 px-6 py-5"
            dir="rtl"
          >
            <FormField
              control={form.control}
              name="amount"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>المبلغ</FormLabel>
                  <FormControl>
                    <Input
                      type="number"
                      step="0.01"
                      min="0.01"
                      placeholder="0.00"
                      className="border-gray-200 focus-visible:ring-violet-200"
                      value={field.value === 0 ? "" : field.value}
                      onChange={(e) =>
                        field.onChange(
                          e.target.value === "" ? 0 : e.target.valueAsNumber
                        )
                      }
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
                  <FormLabel>سبب الخصم</FormLabel>
                  <FormControl>
                    <Input
                      placeholder="مثال: غياب يوم واحد"
                      className="border-gray-200 focus-visible:ring-violet-200"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="date"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>تاريخ الخصم</FormLabel>
                  <FormControl>
                    <Input
                      type="date"
                      className="border-gray-200 focus-visible:ring-violet-200"
                      {...field}
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
                      placeholder="ملاحظات إضافية"
                      rows={2}
                      className="border-gray-200 focus-visible:ring-violet-200"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
