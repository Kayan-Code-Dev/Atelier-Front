import { useMutation, useQuery } from "@tanstack/react-query";
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
import { useCreateEmployeeCustodyMutationOptions, useGetEmployeeCustodyTypesQueryOptions } from "@/api/v2/employees/employee-custodies/employee-custodies.hooks";
import {
  TCreateEmployeeCustody,
  TEmployeeCustodyConditionOnAssignment,
} from "@/api/v2/employees/employee-custodies/employee-custodies.types";
import { toast } from "sonner";
import { EmployeesSelect } from "@/components/custom/EmployeesSelect";
import { EmployeeCustodyTypesSelect } from "@/components/custom/EmployeeCustodyTypesSelect";
import { DatePicker } from "@/components/custom/DatePicker";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { getCustodyTypeVisual } from "./custodyDisplayConfig";
import { cn } from "@/lib/utils";

const FORM_ID = "employee-custody-create-form";

const CONDITION_OPTIONS: { value: TEmployeeCustodyConditionOnAssignment; label: string }[] = [
  { value: "new", label: "جديد" },
  { value: "good", label: "جيد" },
  { value: "fair", label: "مقبول" },
  { value: "poor", label: "ضعيف" },
];

const formSchema = z.object({
  employee_id: z.string().min(1, { message: "الموظف مطلوب" }),
  type: z.string().min(1, { message: "نوع الضمان مطلوب" }),
  name: z.string().min(1, { message: "الاسم مطلوب" }),
  description: z.string().optional(),
  serial_number: z.string().min(1, { message: "الرقم التسلسلي مطلوب" }),
  asset_tag: z.string().min(1, { message: "علامة الأصل مطلوبة" }),
  value: z.number().min(0, { message: "القيمة يجب أن تكون أكبر من أو تساوي صفر" }),
  condition_on_assignment: z.enum(["new", "good", "fair", "poor"]),
  assigned_date: z.date({ required_error: "تاريخ التعيين مطلوب" }),
  expected_return_date: z.date({ required_error: "تاريخ الإرجاع المتوقع مطلوب" }),
  notes: z.string().optional(),
});

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function CreateEmployeeCustodyModal({ open, onOpenChange }: Props) {
  const { data: custodyTypes = [], isPending: typesLoading } = useQuery(
    useGetEmployeeCustodyTypesQueryOptions()
  );

  const { mutate: createEmployeeCustody, isPending } = useMutation(
    useCreateEmployeeCustodyMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      employee_id: "",
      type: "",
      name: "",
      description: "",
      serial_number: "",
      asset_tag: "",
      value: 0,
      condition_on_assignment: "good",
      assigned_date: undefined,
      expected_return_date: undefined,
      notes: "",
    },
  });

  const watchedType = form.watch("type");

  const headerVisual = getCustodyTypeVisual(watchedType || "default");

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    const requestData: TCreateEmployeeCustody = {
      employee_id: Number(values.employee_id),
      type: values.type,
      name: values.name,
      description: values.description || "",
      serial_number: values.serial_number,
      asset_tag: values.asset_tag,
      value: values.value,
      condition_on_assignment: values.condition_on_assignment,
      assigned_date: values.assigned_date.toISOString().split("T")[0],
      expected_return_date: values.expected_return_date.toISOString().split("T")[0],
      notes: values.notes || "",
    };

    createEmployeeCustody(requestData, {
      onSuccess: () => {
        toast.success("تم إنشاء الضمان بنجاح", {
          description: "تمت إضافة الضمان بنجاح للنظام.",
        });
        form.reset();
        onOpenChange(false);
      },
      onError: (error) => {
        toast.error("حدث خطأ أثناء إنشاء الضمان", {
          description: error.message,
        });
      },
    });
  };

  const inputClass =
    "border-gray-200 focus-visible:ring-2 focus-visible:ring-violet-200 focus-visible:border-violet-300";

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton={false}
        className="sm:max-w-3xl rounded-2xl border-gray-100 p-0 gap-0 overflow-hidden"
        bodyClassName="p-0 max-h-[min(90vh,860px)] overflow-y-auto"
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
              {isPending ? (
                "جاري الحفظ..."
              ) : (
                <>
                  <i className="ri-save-line ml-1.5" />
                  حفظ الضمان
                </>
              )}
            </Button>
          </div>
        }
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-white sticky top-0 z-10">
          <div className="flex items-center gap-3 min-w-0">
            <div
              className={cn(
                "w-9 h-9 flex items-center justify-center rounded-xl shrink-0",
                headerVisual.bg
              )}
            >
              <i className={cn(headerVisual.icon, headerVisual.color, "text-lg")} />
            </div>
            <h2 className="text-lg font-bold text-gray-800 truncate">إضافة ضمان / عهدة</h2>
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
              className="space-y-5"
              dir="rtl"
            >
              <FormField
                control={form.control}
                name="type"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-sm font-semibold text-gray-700">
                      نوع الضمان
                    </FormLabel>
                    {custodyTypes.length > 0 && !typesLoading ? (
                      <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        {custodyTypes.map((t) => {
                          const v = getCustodyTypeVisual(t.key);
                          const selected = field.value === t.key;
                          return (
                            <button
                              key={t.key}
                              type="button"
                              disabled={isPending}
                              onClick={() => field.onChange(t.key)}
                              className={cn(
                                "flex items-center gap-2 p-2.5 rounded-xl border-2 text-right transition-all",
                                selected
                                  ? cn(v.bg, "border-violet-400", v.color)
                                  : "border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
                              )}
                            >
                              <div
                                className={cn(
                                  "w-7 h-7 flex items-center justify-center rounded-lg shrink-0",
                                  selected ? "bg-white/60" : "bg-gray-100"
                                )}
                              >
                                <i
                                  className={cn(
                                    v.icon,
                                    "text-sm",
                                    selected ? v.color : "text-gray-400"
                                  )}
                                />
                              </div>
                              <span className="text-xs font-medium leading-tight">{t.name}</span>
                            </button>
                          );
                        })}
                      </div>
                    ) : (
                      <FormControl>
                        <EmployeeCustodyTypesSelect
                          value={field.value}
                          onChange={field.onChange}
                          disabled={isPending || typesLoading}
                          placeholder="اختر نوع الضمان..."
                        />
                      </FormControl>
                    )}
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="employee_id"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-sm font-semibold text-gray-700">الموظف</FormLabel>
                    <FormControl>
                      <EmployeesSelect
                        params={{ per_page: 10 }}
                        value={field.value}
                        onChange={field.onChange}
                        disabled={isPending}
                        placeholder="اختر الموظف..."
                        className="border-gray-200"
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

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
                  name="asset_tag"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>علامة الأصل</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="علامة الأصل"
                          className={inputClass}
                          {...field}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                <FormField
                  control={form.control}
                  name="condition_on_assignment"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>الحالة عند التعيين</FormLabel>
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
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="assigned_date"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>تاريخ التعيين</FormLabel>
                      <FormControl>
                        <DatePicker
                          value={field.value}
                          onChange={field.onChange}
                          placeholder="اختر تاريخ التعيين"
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
                  name="expected_return_date"
                  render={({ field }) => {
                    const assignedDate = form.watch("assigned_date");
                    return (
                      <FormItem>
                        <FormLabel>تاريخ الإرجاع المتوقع</FormLabel>
                        <FormControl>
                          <DatePicker
                            value={field.value}
                            onChange={field.onChange}
                            placeholder="اختر تاريخ الإرجاع المتوقع"
                            showLabel={false}
                            disabled={isPending}
                            minDate={assignedDate}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    );
                  }}
                />
              </div>

              <FormField
                control={form.control}
                name="notes"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>ملاحظات إضافية (اختياري)</FormLabel>
                    <FormControl>
                      <Textarea
                        placeholder="أي ملاحظات أخرى..."
                        className={inputClass}
                        rows={2}
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
