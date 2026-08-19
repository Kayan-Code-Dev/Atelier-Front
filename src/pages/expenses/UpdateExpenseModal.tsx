import { useEffect } from "react";
import { useMutation } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { toast } from "sonner";
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
  FormField,
  FormItem,
  FormLabel,
  FormControl,
  FormMessage,
} from "@/components/ui/form";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  useUpdateExpenseMutationOptions,
} from "@/api/v2/expenses/expenses.hooks";
import {
  EXPENSE_CATEGORIES_WITH_SUBS,
  TExpense,
  TUpdateExpenseRequest,
} from "@/api/v2/expenses/expenses.types";
import { DatePicker } from "@/components/custom/DatePicker";

const createExpenseSchema = z.object({
  branch_id: z.string().min(1, { message: "الفرع مطلوب" }),
  category: z.string().min(1, { message: "الفئة مطلوبة" }),
  subcategory: z.string().optional(),
  amount: z.string().min(1, { message: "المبلغ مطلوب" }),
  expense_date: z.string().min(1, { message: "تاريخ المصروف مطلوب" }),
  vendor: z.string().min(1, { message: "اسم المورد مطلوب" }),
  reference_number: z.string().min(1, { message: "رقم المرجع مطلوب" }),
  description: z.string().min(1, { message: "الوصف مطلوب" }),
  notes: z.string().optional(),
});

const updateExpenseSchema = createExpenseSchema
  .omit({ branch_id: true })
  .partial()
  .extend({
    amount: z.string().optional(),
    expense_date: z.string().optional(),
  });

type UpdateExpenseFormValues = z.infer<typeof updateExpenseSchema>;

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  expense: TExpense | null;
};

export function UpdateExpenseModal({
  open,
  onOpenChange,
  expense,
}: Props) {
  const { mutate: updateExpense, isPending } = useMutation(
    useUpdateExpenseMutationOptions()
  );

  const form = useForm<UpdateExpenseFormValues>({
    resolver: zodResolver(updateExpenseSchema),
    defaultValues: {},
  });

  const selectedCategory = EXPENSE_CATEGORIES_WITH_SUBS.find(
    (c) => c.id === form.watch("category")
  );
  const availableSubcategories = selectedCategory?.subcategories ?? [];

  useEffect(() => {
    if (expense && open) {
      form.reset({
        category: expense.category,
        subcategory: expense.subcategory ?? "",
        amount: expense.amount.toString(),
        expense_date: expense.expense_date
          ? expense.expense_date.slice(0, 10)
          : "",
        vendor: expense.vendor,
        reference_number: expense.reference_number,
        description: expense.description,
        notes: expense.notes,
      });
    }
  }, [expense, open, form]);

  const handleSubmit = (values: UpdateExpenseFormValues) => {
    if (!expense) return;

    const payload: TUpdateExpenseRequest = {};

    if (values.category) payload.category = values.category;
    if (values.subcategory !== undefined) payload.subcategory = values.subcategory || null;
    if (values.amount) payload.amount = Number(values.amount);
    if (values.expense_date) payload.expense_date = values.expense_date;
    if (values.vendor) payload.vendor = values.vendor;
    if (values.reference_number)
      payload.reference_number = values.reference_number;
    if (values.description) payload.description = values.description;
    if (values.notes !== undefined) payload.notes = values.notes;

    updateExpense(
      { id: expense.id, data: payload },
      {
        onSuccess: () => {
          toast.success("تم تحديث المصروف بنجاح");
          onOpenChange(false);
        },
        onError: (error: any) => {
          toast.error("حدث خطأ أثناء تحديث المصروف", {
            description: error.message,
          });
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle className="text-center">تعديل المصروف</DialogTitle>
          <DialogDescription className="text-center">
            عدل بيانات المصروف المطلوب.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(handleSubmit)}
            className="space-y-6"
            dir="rtl"
          >
            <div className="modal-section">
              <p className="modal-section-title">البيانات الأساسية</p>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="category"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>الفئة</FormLabel>
                    <Select
                      value={field.value || ""}
                      onValueChange={(val) => {
                        field.onChange(val);
                        form.setValue("subcategory", "");
                      }}
                    >
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="اختر الفئة..." />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        {EXPENSE_CATEGORIES_WITH_SUBS.map((c) => (
                          <SelectItem key={c.id} value={c.id}>
                            {c.name}
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
                name="subcategory"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>الفئة الفرعية</FormLabel>
                    <Select
                      value={field.value || ""}
                      onValueChange={field.onChange}
                      disabled={!form.watch("category")}
                    >
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="اختر الفئة الفرعية..." />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        {availableSubcategories.map((sub) => (
                          <SelectItem key={sub.id} value={sub.id}>
                            {sub.name}
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
                    <FormLabel>المبلغ</FormLabel>
                    <FormControl>
                      <Input
                        placeholder="0.00"
                        value={field.value ?? ""}
                        onChange={(e) => {
                          const val = e.target.value.replace(/[^0-9.]/g, "");
                          field.onChange(val);
                        }}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="expense_date"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>تاريخ المصروف</FormLabel>
                    <FormControl>
                    <DatePicker
                      value={field.value ? new Date(field.value) : undefined}
                      onChange={(date) =>
                        field.onChange(
                          date ? date.toISOString().slice(0, 10) : ""
                        )
                      }
                      placeholder="اختر تاريخ المصروف"
                    />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="vendor"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>المورد</FormLabel>
                    <FormControl>
                      <Input placeholder="اسم المورد" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="reference_number"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>رقم المرجع</FormLabel>
                    <FormControl>
                      <Input placeholder="رقم الفاتورة أو المرجع" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              </div>
            </div>

            <div className="modal-section">
              <p className="modal-section-title">الوصف والملاحظات</p>
            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الوصف</FormLabel>
                  <FormControl>
                    <Textarea
                      rows={3}
                      placeholder="وصف المصروف..."
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
                      rows={2}
                      placeholder="ملاحظات إضافية..."
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            </div>

            <DialogFooter className="gap-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => onOpenChange(false)}
              >
                إلغاء
              </Button>
              <Button type="submit" disabled={isPending || !expense}>
                {isPending ? "جاري الحفظ..." : "حفظ التعديلات"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}

