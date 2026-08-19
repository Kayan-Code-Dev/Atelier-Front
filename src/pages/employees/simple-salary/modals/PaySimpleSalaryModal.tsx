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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation } from "@tanstack/react-query";
import { usePaySimpleSalaryMutationOptions } from "@/api/simple-salary/simple-salary.hooks";
import {
  PAYMENT_METHODS,
  PAYMENT_METHOD_LABELS,
  type TSimpleSalaryPayRequest,
  type TSimpleSalarySummary,
} from "@/api/simple-salary/simple-salary.types";
import { toast } from "sonner";
import {
  payFormSchema,
  getPayFormDefaultValues,
  type PayFormValues,
} from "../simpleSalary.schema";

const FORM_ID = "simple-salary-pay-form";

export type PaySimpleSalaryModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  summary: TSimpleSalarySummary;
  onSuccess?: () => void;
};

export function PaySimpleSalaryModal({
  open,
  onOpenChange,
  summary,
  onSuccess,
}: PaySimpleSalaryModalProps) {
  const mutation = useMutation(usePaySimpleSalaryMutationOptions());
  const remaining = summary.remaining_to_pay ?? 0;

  const form = useForm<PayFormValues>({
    resolver: zodResolver(payFormSchema),
    defaultValues: getPayFormDefaultValues(),
  });

  const amount = form.watch("amount");
  const amountNum =
    amount != null && typeof amount === "number" && amount > 0 ? amount : null;
  const isOverRemaining =
    amountNum != null &&
    amountNum > 0 &&
    remaining > 0 &&
    amountNum > remaining;

  const handleSubmit = form.handleSubmit((values) => {
    const body: TSimpleSalaryPayRequest = {
      employee_id: summary.employee.id,
      period: summary.period,
      payment_method: values.payment_method,
      payment_reference: values.payment_reference || undefined,
      notes: values.notes || undefined,
    };
    if (values.amount != null && values.amount > 0) {
      body.amount = values.amount;
    }
    mutation.mutate(body, {
      onSuccess: (result) => {
        if (result?.payment) {
          toast.success(result.message ?? "تم تسجيل الدفعة بنجاح.");
          form.reset(getPayFormDefaultValues());
          onOpenChange(false);
          onSuccess?.();
        }
      },
    });
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
              disabled={mutation.isPending || isOverRemaining}
              className="bg-violet-600 hover:bg-violet-700 text-white"
            >
              {mutation.isPending ? "جاري التسجيل..." : "تسجيل الدفعة"}
            </Button>
          </div>
        }
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h2 className="text-lg font-bold text-gray-800">دفع الراتب / دفعة</h2>
            <p className="text-xs text-gray-500 mt-1">
              {summary.employee.name} — {summary.period} — المتبقي:{" "}
              <span className="font-semibold tabular-nums text-gray-800">
                {remaining.toLocaleString("ar-SA", { minimumFractionDigits: 2 })} ج.م
              </span>
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
              name="payment_method"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>طريقة الدفع</FormLabel>
                  <Select onValueChange={field.onChange} value={field.value}>
                    <FormControl>
                      <SelectTrigger className="border-gray-200 focus:ring-violet-200">
                        <SelectValue placeholder="اختر طريقة الدفع" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      {PAYMENT_METHODS.map((m) => (
                        <SelectItem key={m} value={m}>
                          {PAYMENT_METHOD_LABELS[m]}
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
              name="amount"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>
                    مبلغ الدفعة (اختياري — فارغ = دفع كامل المتبقي)
                  </FormLabel>
                  <FormControl>
                    <Input
                      type="number"
                      step="0.01"
                      min="0.01"
                      placeholder={remaining.toFixed(2)}
                      className="border-gray-200 focus-visible:ring-violet-200"
                      value={field.value ?? ""}
                      onChange={(e) =>
                        field.onChange(
                          e.target.value === "" ? null : e.target.valueAsNumber
                        )
                      }
                    />
                  </FormControl>
                  {isOverRemaining && (
                    <p className="text-xs font-medium text-destructive">
                      المبلغ يتجاوز المتبقي ({remaining.toLocaleString("en-US")}).
                    </p>
                  )}
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="payment_reference"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>مرجع الدفع (اختياري)</FormLabel>
                  <FormControl>
                    <Input
                      placeholder="مثال: رقم تحويل"
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
                      placeholder="ملاحظات"
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
