import { useState } from "react";
import { useForm, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useMutation } from "@tanstack/react-query";
import { toast } from "sonner";
import { useNavigate } from "react-router";
import { Eye, EyeOff, Loader2 } from "lucide-react";
import { format, parse } from "date-fns";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";
import { RolesSelect } from "@/components/custom/roles-select";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { DatePicker } from "@/components/custom/DatePicker";
import { EmployeesSelect } from "@/components/custom/EmployeesSelect";
import { useCreateEmployeeQueryOptions } from "@/api/v2/employees/employees.hooks";
import { TCreateEmployeeRequest } from "@/api/v2/employees/employees.types";

// ---------------------------------------------------------------------------
// Schema
// ---------------------------------------------------------------------------

const COMMISSION_TYPES = ["percentage", "fixed"] as const;

const formSchema = z.object({
  name: z.string().min(2, { message: "الاسم مطلوب (حرفان على الأقل)" }),
  email: z.string().email({ message: "بريد إلكتروني غير صالح" }),
  password: z
    .string()
    .min(6, { message: "كلمة المرور يجب أن تكون 6 أحرف على الأقل" }),
  hire_date: z.string().min(1, { message: "تاريخ التوظيف مطلوب" }),

  branch_id: z.string().min(1, { message: "يجب اختيار الفرع" }),

  roles: z.array(z.string()).optional(),
  manager_id: z.string().optional(),

  base_salary: z.string().min(1, { message: "الراتب الأساسي مطلوب" }),
  transport_allowance: z.string().optional(),
  housing_allowance: z.string().optional(),
  other_allowances: z.string().optional(),
  overtime_rate: z.string().optional(),
  commission_type: z.enum(COMMISSION_TYPES).optional(),
  commission_rate: z.string().optional(),

  annual_vacation_days: z.string().optional(),
  probation_end_date: z.string().optional(),
  work_start_time: z.string().optional(),
  work_end_time: z.string().optional(),
  work_hours_per_day: z.string().optional(),
  late_threshold_minutes: z.string().optional(),
});

type FormValues = z.infer<typeof formSchema>;

const inputClass =
  "w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 transition-all";

const labelClass = "block text-xs font-medium text-gray-600 mb-1.5";

const DEFAULT_VALUES: FormValues = {
  name: "",
  email: "",
  password: "",
  hire_date: "",
  branch_id: "",
  roles: [],
  manager_id: "",
  base_salary: "",
  transport_allowance: "",
  housing_allowance: "",
  other_allowances: "",
  overtime_rate: "",
  commission_type: undefined,
  commission_rate: "",
  annual_vacation_days: "",
  probation_end_date: "",
  work_start_time: "",
  work_end_time: "",
  work_hours_per_day: "",
  late_threshold_minutes: "",
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function parseTimeForInput(value: string): string {
  if (!value) return "";
  try {
    if (value.includes(":")) {
      const parts = value.split(":");
      if (parts.length === 3)
        return format(parse(value, "HH:mm:ss", new Date()), "HH:mm");
      if (parts.length === 2) return value;
    }
    return format(parse(value, "HH:mm:ss", new Date()), "HH:mm");
  } catch {
    return value.includes(":") ? value.split(":").slice(0, 2).join(":") : "";
  }
}

function formatTimeForApi(value: string): string {
  if (!value) return "";
  try {
    return format(parse(value, "HH:mm", new Date()), "HH:mm:ss");
  } catch {
    return value + ":00";
  }
}

function numericOnly(v: string) {
  return v.replace(/[^0-9.]/g, "");
}

function intOnly(v: string) {
  return v.replace(/[^0-9]/g, "");
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

function collectErrorMessages(
  errors: Record<string, unknown> | undefined,
): string[] {
  const out: string[] = [];
  const walk = (o: unknown) => {
    if (!o || typeof o !== "object") return;
    const rec = o as Record<string, unknown>;
    if (typeof rec.message === "string") {
      out.push(rec.message);
      return;
    }
    for (const v of Object.values(rec)) walk(v);
  };
  walk(errors);
  return out;
}

function CreateEmployee() {
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [success, setSuccess] = useState(false);

  const { mutate: createEmployee, isPending } = useMutation(
    useCreateEmployeeQueryOptions(),
  );

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: DEFAULT_VALUES,
  });

  const commissionType = useWatch({
    control: form.control,
    name: "commission_type",
  });

  // ---- submit ----
  const onSubmit = (values: FormValues) => {
    const requestData: TCreateEmployeeRequest = {
      name: values.name,
      email: values.email,
      password: values.password,
      hire_date: values.hire_date,
      branch_ids: [Number(values.branch_id)],
      roles: values.roles?.length ? values.roles.map(Number) : undefined,
      manager_id: values.manager_id ? Number(values.manager_id) : undefined,
      base_salary: Number(values.base_salary),
      transport_allowance: values.transport_allowance
        ? Number(values.transport_allowance)
        : undefined,
      housing_allowance: values.housing_allowance
        ? Number(values.housing_allowance)
        : undefined,
      other_allowances: values.other_allowances
        ? Number(values.other_allowances)
        : undefined,
      overtime_rate: values.overtime_rate
        ? Number(values.overtime_rate)
        : undefined,
      commission_rate: values.commission_rate
        ? Number(values.commission_rate)
        : undefined,
      annual_vacation_days: values.annual_vacation_days
        ? Number(values.annual_vacation_days)
        : undefined,
      probation_end_date: values.probation_end_date || undefined,
      work_start_time: values.work_start_time
        ? formatTimeForApi(values.work_start_time)
        : undefined,
      work_end_time: values.work_end_time
        ? formatTimeForApi(values.work_end_time)
        : undefined,
      work_hours_per_day: values.work_hours_per_day
        ? Number(values.work_hours_per_day)
        : undefined,
      late_threshold_minutes: values.late_threshold_minutes
        ? Number(values.late_threshold_minutes)
        : undefined,
    };

    createEmployee(requestData, {
      onSuccess: () => {
        form.reset(DEFAULT_VALUES);
        setSuccess(true);
        setTimeout(() => navigate("/employees"), 2000);
      },
      onError: (error: { message?: string }) => {
        toast.error("حدث خطأ أثناء إنشاء الموظف", {
          description: error?.message,
        });
      },
    });
  };

  const { submitCount, errors: formErrors } = form.formState;
  const errorMessages = collectErrorMessages(
    formErrors as Record<string, unknown>,
  );
  const showErrorBanner = submitCount > 0 && errorMessages.length > 0;

  if (success) {
    return (
      <div
        className="min-h-screen flex items-center justify-center bg-gray-50/50"
        dir="rtl"
      >
        <div className="text-center p-10">
          <div className="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i className="ri-check-line text-3xl text-emerald-600" />
          </div>
          <h2 className="text-xl font-bold text-gray-800 mb-2">
            تم إنشاء الموظف بنجاح!
          </h2>
          <p className="text-sm text-gray-500">
            جاري الانتقال إلى قائمة الموظفين...
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50/50" dir="rtl">
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)}>
          <div className="bg-white border-b border-gray-100 px-8 py-5 flex flex-wrap items-center justify-between gap-3 sticky top-0 z-10">
            <div className="flex items-center gap-4">
              <button
                type="button"
                onClick={() => navigate("/employees")}
                className="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 cursor-pointer transition-colors"
                aria-label="رجوع"
              >
                <i className="ri-arrow-right-line text-sm" />
              </button>
              <div>
                <h1 className="text-lg font-bold text-gray-900">
                  إنشاء موظف جديد
                </h1>
                <p className="text-xs text-gray-400 mt-0.5">
                  أملأ البيانات المطلوبة لإضافة موظف جديد للنظام
                </p>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => form.reset(DEFAULT_VALUES)}
                disabled={isPending}
                className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors whitespace-nowrap disabled:opacity-50"
              >
                إعادة تعيين
              </button>
              <button
                type="submit"
                disabled={isPending}
                className="px-5 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 cursor-pointer transition-colors whitespace-nowrap flex items-center gap-2 disabled:opacity-60"
              >
                {isPending ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    جاري الإنشاء...
                  </>
                ) : (
                  <>
                    <div className="w-4 h-4 flex items-center justify-center">
                      <i className="ri-user-add-line text-sm" />
                    </div>
                    إنشاء موظف
                  </>
                )}
              </button>
            </div>
          </div>

          {showErrorBanner && (
            <div
              className="mx-8 mt-5 p-4 bg-red-50 border border-red-100 rounded-xl flex items-start gap-3"
              data-error
            >
              <div className="w-5 h-5 flex items-center justify-center text-red-500 mt-0.5">
                <i className="ri-error-warning-line" />
              </div>
              <div>
                <p className="text-sm font-medium text-red-700 mb-1">
                  يرجى تصحيح الأخطاء التالية:
                </p>
                <ul className="space-y-0.5">
                  {errorMessages.map((e, i) => (
                    <li key={i} className="text-xs text-red-600">
                      • {e}
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          )}

          <div className="max-w-4xl mx-auto px-8 py-6 space-y-5">
            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
              <div className="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                <div className="w-8 h-8 flex items-center justify-center text-emerald-600">
                  <i className="ri-user-line text-lg" />
                </div>
                <div>
                  <h2 className="text-sm font-semibold text-gray-800">
                    البيانات الأساسية
                  </h2>
                  <p className="text-xs text-gray-400 mt-0.5">
                    الحقول المطلوبة لإنشاء الحساب
                  </p>
                </div>
              </div>
              <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-4" dir="rtl">
                {/* Name */}
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        الاسم <span className="text-red-500">*</span>
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="اسم الموظف"
                          className={inputClass}
                          {...field}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Email */}
                <FormField
                  control={form.control}
                  name="email"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        البريد الإلكتروني{" "}
                        <span className="text-red-500">*</span>
                      </FormLabel>
                      <FormControl>
                        <Input
                          type="email"
                          placeholder="example@email.com"
                          className={cn(inputClass, "text-left")}
                          dir="ltr"
                          {...field}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Password */}
                <FormField
                  control={form.control}
                  name="password"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        كلمة المرور{" "}
                        <span className="text-red-500">*</span>
                      </FormLabel>
                      <FormControl>
                        <div className="relative">
                          <Input
                            type={showPassword ? "text" : "password"}
                            placeholder="••••••••"
                            className={cn(inputClass, "pl-10 pr-3")}
                            dir="ltr"
                            {...field}
                            disabled={isPending}
                          />
                          <button
                            type="button"
                            onClick={() => setShowPassword((p) => !p)}
                            className="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer"
                            tabIndex={-1}
                          >
                            {showPassword ? (
                              <EyeOff className="h-4 w-4" />
                            ) : (
                              <Eye className="h-4 w-4" />
                            )}
                          </button>
                        </div>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Hire date */}
                <FormField
                  control={form.control}
                  name="hire_date"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        تاريخ التوظيف{" "}
                        <span className="text-red-500">*</span>
                      </FormLabel>
                      <FormControl>
                        <DatePicker
                          value={
                            field.value
                              ? parse(field.value, "yyyy-MM-dd", new Date())
                              : undefined
                          }
                          onChange={(d) =>
                            field.onChange(
                              d ? format(d, "yyyy-MM-dd") : "",
                            )
                          }
                          placeholder="اختر تاريخ التوظيف"
                          showLabel={false}
                          disabled={isPending}
                          allowFutureDates
                          allowPastDates
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

                {/* Branch (required - single) */}
                <FormField
                  control={form.control}
                  name="branch_id"
                  render={({ field }) => (
                    <FormItem className="md:col-span-2">
                      <FormLabel className={labelClass}>
                        الفرع <span className="text-red-500">*</span>
                      </FormLabel>
                      <FormControl>
                        <div className="rounded-lg border border-gray-200 overflow-hidden bg-white">
                          <BranchesSelect
                            value={field.value ?? ""}
                            onChange={field.onChange}
                            disabled={isPending}
                          />
                        </div>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </div>

            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
              <div className="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                <div className="w-8 h-8 flex items-center justify-center text-blue-600">
                  <i className="ri-shield-user-line text-lg" />
                </div>
                <div>
                  <h2 className="text-sm font-semibold text-gray-800">الصلاحيات</h2>
                  <p className="text-xs text-gray-400 mt-0.5">
                    تحديد صلاحيات الموظف والمدير المباشر (اختياري)
                  </p>
                </div>
              </div>
              <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-4" dir="rtl">
                {/* Roles */}
                <FormField
                  control={form.control}
                  name="roles"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>الصلاحيات</FormLabel>
                      <FormControl>
                        <div className="rounded-lg border border-gray-200 overflow-hidden bg-white">
                          <RolesSelect
                            multi
                            value={field.value || []}
                            onChange={field.onChange}
                            disabled={isPending}
                          />
                        </div>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Manager */}
                <FormField
                  control={form.control}
                  name="manager_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        المدير المباشر (اختياري)
                      </FormLabel>
                      <FormControl>
                        <div className="rounded-lg border border-gray-200 overflow-hidden bg-white">
                          <EmployeesSelect
                            params={{ per_page: 20 }}
                            value={field.value}
                            onChange={field.onChange}
                            disabled={isPending}
                            placeholder="اختر المدير المباشر"
                          />
                        </div>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </div>

            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
              <div className="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                <div className="w-8 h-8 flex items-center justify-center text-emerald-600">
                  <i className="ri-money-dollar-circle-line text-lg" />
                </div>
                <div>
                  <h2 className="text-sm font-semibold text-gray-800">
                    معلومات الراتب
                  </h2>
                  <p className="text-xs text-gray-400 mt-0.5">
                    الراتب الأساسي مطلوب، والبدلات اختيارية
                  </p>
                </div>
              </div>
              <div className="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" dir="rtl">
                {/* Base salary (required) */}
                <FormField
                  control={form.control}
                  name="base_salary"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        الراتب الأساسي{" "}
                        <span className="text-red-500">*</span>
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="0"
                          className={inputClass}
                          value={field.value ?? ""}
                          onChange={(e) =>
                            field.onChange(numericOnly(e.target.value))
                          }
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Transport */}
                <FormField
                  control={form.control}
                  name="transport_allowance"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>بدل المواصلات</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="0"
                          className={inputClass}
                          value={field.value ?? ""}
                          onChange={(e) =>
                            field.onChange(numericOnly(e.target.value))
                          }
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Housing */}
                <FormField
                  control={form.control}
                  name="housing_allowance"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>بدل السكن</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="0"
                          className={inputClass}
                          value={field.value ?? ""}
                          onChange={(e) =>
                            field.onChange(numericOnly(e.target.value))
                          }
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Other allowances */}
                <FormField
                  control={form.control}
                  name="other_allowances"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>بدلات أخرى</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="0"
                          className={inputClass}
                          value={field.value ?? ""}
                          onChange={(e) =>
                            field.onChange(numericOnly(e.target.value))
                          }
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Overtime */}
                <FormField
                  control={form.control}
                  name="overtime_rate"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        معدل العمل الإضافي
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="0"
                          className={inputClass}
                          value={field.value ?? ""}
                          onChange={(e) =>
                            field.onChange(numericOnly(e.target.value))
                          }
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Commission type */}
                <FormField
                  control={form.control}
                  name="commission_type"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>نوع العمولة</FormLabel>
                      <Select
                        onValueChange={field.onChange}
                        value={field.value ?? ""}
                        disabled={isPending}
                      >
                        <FormControl>
                          <SelectTrigger
                            className={cn(
                              inputClass,
                              "h-10 focus-visible:ring-0 focus-visible:ring-offset-0",
                            )}
                          >
                            <SelectValue placeholder="اختر نوع العمولة" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          <SelectItem value="percentage">نسبة مئوية (%)</SelectItem>
                          <SelectItem value="fixed">مبلغ ثابت</SelectItem>
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Commission rate */}
                {commissionType && (
                  <FormField
                    control={form.control}
                    name="commission_rate"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className={labelClass}>
                          {commissionType === "percentage"
                            ? "نسبة العمولة (%)"
                            : "مبلغ العمولة"}
                        </FormLabel>
                        <FormControl>
                          <Input
                            placeholder={
                              commissionType === "percentage" ? "10" : "500"
                            }
                            className={inputClass}
                            value={field.value ?? ""}
                            onChange={(e) =>
                              field.onChange(numericOnly(e.target.value))
                            }
                            disabled={isPending}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                )}
              </div>
            </div>

            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
              <div className="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                <div className="w-8 h-8 flex items-center justify-center text-orange-600">
                  <i className="ri-time-line text-lg" />
                </div>
                <div>
                  <h2 className="text-sm font-semibold text-gray-800">جدول العمل</h2>
                  <p className="text-xs text-gray-400 mt-0.5">
                    أوقات العمل والإجازات (اختياري)
                  </p>
                </div>
              </div>
              <div className="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" dir="rtl">
                {/* Work start */}
                <FormField
                  control={form.control}
                  name="work_start_time"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>وقت بدء العمل</FormLabel>
                      <FormControl>
                        <Input
                          type="time"
                          className={inputClass}
                          value={parseTimeForInput(field.value ?? "")}
                          onChange={(e) => field.onChange(e.target.value)}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Work end */}
                <FormField
                  control={form.control}
                  name="work_end_time"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>وقت انتهاء العمل</FormLabel>
                      <FormControl>
                        <Input
                          type="time"
                          className={inputClass}
                          value={parseTimeForInput(field.value ?? "")}
                          onChange={(e) => field.onChange(e.target.value)}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Hours per day */}
                <FormField
                  control={form.control}
                  name="work_hours_per_day"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        ساعات العمل اليومية
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="8"
                          className={inputClass}
                          value={field.value ?? ""}
                          onChange={(e) =>
                            field.onChange(intOnly(e.target.value))
                          }
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Late threshold */}
                <FormField
                  control={form.control}
                  name="late_threshold_minutes"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        حد التأخير (بالدقائق)
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="15"
                          className={inputClass}
                          value={field.value ?? ""}
                          onChange={(e) =>
                            field.onChange(intOnly(e.target.value))
                          }
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Annual vacation */}
                <FormField
                  control={form.control}
                  name="annual_vacation_days"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        أيام الإجازة السنوية
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="21"
                          className={inputClass}
                          value={field.value ?? ""}
                          onChange={(e) =>
                            field.onChange(intOnly(e.target.value))
                          }
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Probation end */}
                <FormField
                  control={form.control}
                  name="probation_end_date"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        تاريخ انتهاء فترة التجربة
                      </FormLabel>
                      <FormControl>
                        <DatePicker
                          value={
                            field.value
                              ? parse(field.value, "yyyy-MM-dd", new Date())
                              : undefined
                          }
                          onChange={(d) =>
                            field.onChange(
                              d ? format(d, "yyyy-MM-dd") : "",
                            )
                          }
                          placeholder="اختر التاريخ"
                          showLabel={false}
                          disabled={isPending}
                          allowFutureDates
                          allowPastDates
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
              </div>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 pb-8">
              <button
                type="button"
                onClick={() => form.reset(DEFAULT_VALUES)}
                disabled={isPending}
                className="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors whitespace-nowrap disabled:opacity-50"
              >
                إعادة تعيين
              </button>
              <button
                type="submit"
                disabled={isPending}
                className="px-6 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 cursor-pointer transition-colors whitespace-nowrap flex items-center gap-2 disabled:opacity-60"
              >
                {isPending ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    جاري الإنشاء...
                  </>
                ) : (
                  <>
                    <div className="w-4 h-4 flex items-center justify-center">
                      <i className="ri-user-add-line text-sm" />
                    </div>
                    إنشاء الموظف
                  </>
                )}
              </button>
            </div>
          </div>
        </form>
      </Form>
    </div>
  );
}

export default CreateEmployee;
