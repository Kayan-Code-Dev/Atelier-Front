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
import {
  Form,
  FormField,
  FormItem,
  FormLabel,
  FormControl,
  FormMessage,
} from "@/components/ui/form";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import {
  useCreateExpenseMutationOptions,
} from "@/api/v2/expenses/expenses.hooks";
import {
  EXPENSE_CATEGORIES_WITH_SUBS,
  TCreateExpenseRequest,
} from "@/api/v2/expenses/expenses.types";

// Accept string or number from form controls and coerce to string (avoids "Expected string, received number")
const stringOrNumber = (msg: string) =>
  z.union([z.string(), z.number()]).transform((v) => (v == null || v === "" ? "" : String(v))).pipe(z.string().min(1, { message: msg }));
const optionalStringOrNumber = () =>
  z.union([z.string(), z.number()]).transform((v) => (v == null || v === "" ? undefined : String(v))).optional();

const createExpenseSchema = z.object({
  branch_id: stringOrNumber("الفرع مطلوب"),
  category: stringOrNumber("الفئة مطلوبة"),
  subcategory: stringOrNumber("الفئة الفرعية مطلوبة"),
  amount: stringOrNumber("المبلغ مطلوب"),
  expense_date: stringOrNumber("تاريخ المصروف مطلوب"),
  vendor: stringOrNumber("اسم المورد مطلوب"),
  reference_number: stringOrNumber("رقم المرجع مطلوب"),
  description: stringOrNumber("الوصف مطلوب"),
  notes: optionalStringOrNumber(),
});

type CreateExpenseFormValues = z.infer<typeof createExpenseSchema>;

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

const labelClass = "text-sm font-medium text-slate-700 mb-1.5 block";
const errorClass = "text-xs text-red-500 mt-1";

export function CreateExpenseModal({ open, onOpenChange }: Props) {
  const { mutate: createExpense, isPending } = useMutation(
    useCreateExpenseMutationOptions()
  );

  const form = useForm<CreateExpenseFormValues>({
    resolver: zodResolver(createExpenseSchema),
    defaultValues: {
      branch_id: "",
      category: "",
      subcategory: "",
      amount: "",
      expense_date: "",
      vendor: "",
      reference_number: "",
      description: "",
      notes: "",
    },
  });

  const selectedCategory = EXPENSE_CATEGORIES_WITH_SUBS.find(
    (c) => c.id === form.watch("category")
  );
  const availableSubcategories = selectedCategory?.subcategories ?? [];

  const handleSubmit = (values: CreateExpenseFormValues) => {
    const payload: TCreateExpenseRequest = {
      branch_id: Number(values.branch_id),
      category: values.category,
      subcategory: values.subcategory || null,
      amount: Number(values.amount),
      expense_date: values.expense_date,
      vendor: values.vendor,
      reference_number: values.reference_number,
      description: values.description,
      notes: values.notes || "",
    };

    createExpense(payload, {
      onSuccess: () => {
        toast.success("تم إنشاء المصروف بنجاح");
        form.reset();
        onOpenChange(false);
      },
      onError: (error: { message?: string }) => {
        toast.error("حدث خطأ أثناء إنشاء المصروف", {
          description: error.message,
        });
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className="sm:max-w-2xl p-0 gap-0 overflow-hidden"
        dir="rtl"
      >
        <DialogHeader className="-mx-6 px-6 pt-6 pb-4 border-b border-slate-200/80 mb-0">
          <div className="flex items-center gap-3">
            <div
              className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
              style={{ background: "#FEE2E2", color: "#DC2626" }}
            >
              <i className="ri-add-circle-line text-xl" />
            </div>
            <div>
              <DialogTitle className="text-lg font-bold text-slate-800">
                إضافة مصروف جديد
              </DialogTitle>
              <DialogDescription className="text-sm text-slate-500 mt-0.5">
                املأ البيانات التالية لإضافة مصروف جديد
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(handleSubmit)} className="flex flex-col">
            <div className="flex-1 overflow-y-auto -mx-6 px-6 py-5 space-y-6">
              
              <div className="sys-card p-5 space-y-4">
                <div className="flex items-center gap-2 mb-1">
                  <i className="ri-file-list-3-line text-slate-500 text-sm" />
                  <h3 className="text-sm font-bold text-slate-700">
                    البيانات الأساسية
                  </h3>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <FormField
                    control={form.control}
                    name="branch_id"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className={labelClass}>الفرع</FormLabel>
                        <FormControl>
                          <BranchesSelect
                            value={field.value}
                            onChange={field.onChange}
                            disabled={isPending}
                            className="w-full"
                          />
                        </FormControl>
                        <FormMessage className={errorClass} />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="category"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className={labelClass}>الفئة</FormLabel>
                        <FormControl>
                          <select
                            className="sys-select w-full"
                            value={field.value}
                            onChange={(e) => {
                              field.onChange(e.target.value);
                              form.setValue("subcategory", "");
                            }}
                            disabled={isPending}
                          >
                            <option value="">اختر الفئة...</option>
                            {EXPENSE_CATEGORIES_WITH_SUBS.map((c) => (
                              <option key={c.id} value={c.id}>
                                {c.name}
                              </option>
                            ))}
                          </select>
                        </FormControl>
                        <FormMessage className={errorClass} />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="subcategory"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className={labelClass}>الفئة الفرعية</FormLabel>
                        <FormControl>
                          <select
                            className="sys-select w-full"
                            value={field.value}
                            onChange={(e) => field.onChange(e.target.value)}
                            disabled={!form.watch("category") || isPending}
                          >
                            <option value="">اختر الفئة الفرعية...</option>
                            {availableSubcategories.map((sub) => (
                              <option key={sub.id} value={sub.id}>
                                {sub.name}
                              </option>
                            ))}
                          </select>
                        </FormControl>
                        <FormMessage className={errorClass} />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="amount"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className={labelClass}>المبلغ (ج.م)</FormLabel>
                        <FormControl>
                          <input
                            type="text"
                            inputMode="decimal"
                            placeholder="0.00"
                            className="sys-input w-full"
                            value={field.value ?? ""}
                            onChange={(e) => {
                              const val = e.target.value.replace(/[^0-9.]/g, "");
                              field.onChange(val);
                            }}
                            disabled={isPending}
                          />
                        </FormControl>
                        <FormMessage className={errorClass} />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="expense_date"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className={labelClass}>تاريخ المصروف</FormLabel>
                        <FormControl>
                          <input
                            type="date"
                            className="sys-input w-full"
                            value={field.value ?? ""}
                            onChange={(e) => field.onChange(e.target.value)}
                            disabled={isPending}
                          />
                        </FormControl>
                        <FormMessage className={errorClass} />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="vendor"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className={labelClass}>المورد</FormLabel>
                        <FormControl>
                          <input
                            type="text"
                            placeholder="اسم المورد"
                            className="sys-input w-full"
                            {...field}
                            disabled={isPending}
                          />
                        </FormControl>
                        <FormMessage className={errorClass} />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="reference_number"
                    render={({ field }) => (
                      <FormItem className="md:col-span-2">
                        <FormLabel className={labelClass}>رقم المرجع</FormLabel>
                        <FormControl>
                          <input
                            type="text"
                            placeholder="رقم الفاتورة أو المرجع"
                            className="sys-input w-full"
                            {...field}
                            disabled={isPending}
                          />
                        </FormControl>
                        <FormMessage className={errorClass} />
                      </FormItem>
                    )}
                  />
                </div>
              </div>

              
              <div className="sys-card p-5 space-y-4">
                <div className="flex items-center gap-2 mb-1">
                  <i className="ri-file-text-line text-slate-500 text-sm" />
                  <h3 className="text-sm font-bold text-slate-700">
                    الوصف والملاحظات
                  </h3>
                </div>
                <FormField
                  control={form.control}
                  name="description"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>الوصف</FormLabel>
                      <FormControl>
                        <textarea
                          rows={3}
                          placeholder="وصف المصروف..."
                          className="sys-input w-full min-h-[80px] resize-y"
                          {...field}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage className={errorClass} />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="notes"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        ملاحظات <span className="text-slate-400 font-normal">(اختياري)</span>
                      </FormLabel>
                      <FormControl>
                        <textarea
                          rows={2}
                          placeholder="ملاحظات إضافية..."
                          className="sys-input w-full min-h-[60px] resize-y"
                          {...field}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage className={errorClass} />
                    </FormItem>
                  )}
                />
              </div>
            </div>

            <DialogFooter className="gap-3 -mx-6 px-6 py-4 mt-0 border-t border-slate-200/80 bg-slate-50/50">
              <button
                type="button"
                onClick={() => onOpenChange(false)}
                disabled={isPending}
                className="flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors disabled:opacity-60"
                style={{
                  background: "#F1F5F9",
                  color: "#475569",
                  border: "1px solid #E2E8F0",
                }}
              >
                <i className="ri-close-line" /> إلغاء
              </button>
              <button
                type="submit"
                disabled={isPending}
                className="blue-btn flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold cursor-pointer disabled:opacity-60"
              >
                {isPending ? (
                  <>
                    <i className="ri-loader-4-line animate-spin text-lg" />
                    جاري الحفظ...
                  </>
                ) : (
                  <>
                    <i className="ri-save-line" /> حفظ
                  </>
                )}
              </button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
