import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { useEffect } from "react";
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
import { Loader2 } from "lucide-react";
import { DepartmentsSelect } from "@/components/custom/departments-select";
import { JobTitlesSelect } from "@/components/custom/JobTitlesSelect";
import { RolesSelect } from "@/components/custom/roles-select";
import { FactoriesSelect } from "@/components/custom/FactoriesSelect";
import { WorkshopsSelect } from "@/components/custom/WorkshopsSelect";
import { BranchesSelect as MultiBranchesSelect } from "@/components/custom/MultiBranchesSelect";
import {
  useUpdateEmployeeQueryOptions,
  useGetEmployeeAssignmentsQueryOptions,
} from "@/api/v2/employees/employees.hooks";
import {
  TUpdateEmployeeRequest,
  TEmployee,
  EMPLOYMENT_STATUS,
} from "@/api/v2/employees/employees.types";
import { TEntity } from "@/lib/types/entity.types";

// Schema for the form - only fields that can be updated
const formSchema = z.object({
  name: z.string().min(2, { message: "الاسم مطلوب" }),
  email: z.string().email({ message: "بريد إلكتروني غير صالح" }),
  department_id: z.string().optional(),
  job_title_id: z.string().optional(),
  employment_status: z
    .enum(EMPLOYMENT_STATUS as unknown as [string, ...string[]])
    .optional(),
  roles: z.array(z.string()).optional(),
  base_salary: z.string().optional(),
  factory_ids: z.array(z.string()).optional(),
  workshop_ids: z.array(z.string()).optional(),
  branch_ids: z.array(z.string()).optional(),
});

type FormValues = z.infer<typeof formSchema>;

type EditEmployeeProps = {
  employee: TEmployee;
  onCancel: () => void;
  onSuccess: () => void;
};

const employmentStatusLabels: Record<string, string> = {
  active: "نشط",
  on_leave: "في إجازة",
  suspended: "معلق",
  terminated: "منتهي",
};

const inputClass =
  "w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 transition-all";

const labelClass = "text-xs font-medium text-gray-600 mb-1.5 block";

function EditSection({
  icon,
  title,
  subtitle,
  children,
}: {
  icon: string;
  title: string;
  subtitle?: string;
  children: React.ReactNode;
}) {
  return (
    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
      <div className="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
        <div className="w-8 h-8 flex items-center justify-center text-emerald-600">
          <i className={cn(icon, "text-lg")} />
        </div>
        <div>
          <h2 className="text-sm font-semibold text-gray-800">{title}</h2>
          {subtitle && (
            <p className="text-xs text-gray-400 mt-0.5">{subtitle}</p>
          )}
        </div>
      </div>
      <div className="p-6">{children}</div>
    </div>
  );
}

function EditEmployee({ employee, onCancel, onSuccess }: EditEmployeeProps) {
  const { mutate: updateEmployee, isPending } = useMutation(
    useUpdateEmployeeQueryOptions()
  );

  const { data: assignments } = useQuery({
    ...useGetEmployeeAssignmentsQueryOptions(employee.id),
  });

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: employee.user.name,
      email: employee.user.email,
      department_id: employee.department_id?.toString() || "",
      job_title_id: employee.job_title_id?.toString() || "",
      employment_status: employee.employment_status,
      roles: employee.user.roles?.map((r) => r.id.toString()) || [],
      base_salary: employee.base_salary?.toString() || "",
      factory_ids: [],
      workshop_ids: [],
      branch_ids: employee.branches?.map((b) => b.id.toString()) || [],
    },
  });

  // Update form when assignments are loaded
  useEffect(() => {
    if (assignments) {
      const factoryIds =
        assignments
          .filter((a) => a.entity_type === "factory")
          .map((a) => a.entity_id.toString()) || [];
      const workshopIds =
        assignments
          .filter((a) => a.entity_type === "workshop")
          .map((a) => a.entity_id.toString()) || [];
      const branchIds =
        assignments
          .filter((a) => a.entity_type === "branch")
          .map((a) => a.entity_id.toString()) || [];

      form.reset({
        name: employee.user.name,
        email: employee.user.email,
        department_id: employee.department_id?.toString() || "",
        job_title_id: employee.job_title_id?.toString() || "",
        employment_status: employee.employment_status,
        roles: employee.user.roles?.map((r) => r.id.toString()) || [],
        base_salary: employee.base_salary?.toString() || "",
        factory_ids: factoryIds,
        workshop_ids: workshopIds,
        branch_ids: branchIds.length > 0 ? branchIds : employee.branches?.map((b) => b.id.toString()) || [],
      });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [assignments]);

  const onSubmit = (values: FormValues) => {
    // Build entity_assignments array
    const entityAssignments: TUpdateEmployeeRequest["entity_assignments"] = [];

    // Add factories
    if (values.factory_ids && values.factory_ids.length > 0) {
      values.factory_ids.forEach((factoryId, index) => {
        entityAssignments.push({
          entity_type: "factory" as TEntity,
          entity_id: Number(factoryId),
          is_primary: index === 0,
        });
      });
    }

    // Add workshops
    if (values.workshop_ids && values.workshop_ids.length > 0) {
      values.workshop_ids.forEach((workshopId, index) => {
        entityAssignments.push({
          entity_type: "workshop" as TEntity,
          entity_id: Number(workshopId),
          is_primary: index === 0,
        });
      });
    }

    // Add branches
    if (values.branch_ids && values.branch_ids.length > 0) {
      values.branch_ids.forEach((branchId, index) => {
        entityAssignments.push({
          entity_type: "branch" as TEntity,
          entity_id: Number(branchId),
          is_primary: index === 0,
        });
      });
    }

    const requestData: TUpdateEmployeeRequest = {
      name: values.name,
      email: values.email,
      department_id: values.department_id
        ? Number(values.department_id)
        : undefined,
      job_title_id: values.job_title_id
        ? Number(values.job_title_id)
        : undefined,
      base_salary: values.base_salary
        ? Number(values.base_salary)
        : undefined,
      employment_status: values.employment_status as TUpdateEmployeeRequest["employment_status"],
      roles: values.roles ? values.roles.map(Number) : undefined,
      entity_assignments:
        entityAssignments.length > 0 ? entityAssignments : undefined,
    };

    updateEmployee(
      { id: employee.id, data: requestData },
      {
        onSuccess: () => {
          toast.success("تم تحديث الموظف بنجاح", {
            description: "تم تحديث بيانات الموظف بنجاح.",
          });
          onSuccess();
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء تحديث الموظف", {
            description: error.message,
          });
        },
      }
    );
  };

  return (
    <div className="min-h-screen bg-gray-50/50" dir="rtl">
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)}>
          <div className="bg-white border-b border-gray-100 px-6 py-5 flex flex-wrap items-center justify-between gap-3 sticky top-0 z-10">
            <div className="flex items-center gap-4">
              <button
                type="button"
                onClick={onCancel}
                className="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors"
                aria-label="رجوع"
              >
                <i className="ri-arrow-right-line text-lg" />
              </button>
              <div>
                <h1 className="text-lg font-bold text-gray-900">
                  تعديل بيانات الموظف
                </h1>
                <p className="text-xs text-gray-400 mt-0.5">
                  {employee.user.name} ·{" "}
                  <span className="font-mono text-gray-500">
                    {employee.employee_code}
                  </span>
                </p>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={onCancel}
                disabled={isPending}
                className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
              >
                إلغاء
              </button>
              <button
                type="submit"
                disabled={isPending}
                className="px-5 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors flex items-center gap-2 disabled:opacity-60"
              >
                {isPending ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    جاري التحديث...
                  </>
                ) : (
                  <>
                    <i className="ri-save-line text-base" />
                    حفظ التغييرات
                  </>
                )}
              </button>
            </div>
          </div>

          <div className="max-w-4xl mx-auto px-6 py-6 space-y-5">
            <EditSection
              icon="ri-user-line"
              title="المعلومات الأساسية"
              subtitle="الاسم والبريد الإلكتروني"
            >
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
              </div>
            </EditSection>

            <EditSection
              icon="ri-briefcase-line"
              title="معلومات الوظيفة"
              subtitle="القسم والمسمى وحالة التوظيف والأدوار"
            >
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="department_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>القسم</FormLabel>
                      <FormControl>
                        <div className="rounded-lg border border-gray-200 overflow-hidden bg-white">
                          <DepartmentsSelect
                            value={field.value}
                            onChange={field.onChange}
                            disabled={isPending}
                          />
                        </div>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="job_title_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>
                        المسمى الوظيفي
                      </FormLabel>
                      <FormControl>
                        <div className="rounded-lg border border-gray-200 overflow-hidden bg-white">
                          <JobTitlesSelect
                            value={field.value}
                            onChange={field.onChange}
                            disabled={isPending}
                          />
                        </div>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="employment_status"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>حالة التوظيف</FormLabel>
                      <Select
                        onValueChange={field.onChange}
                        value={field.value}
                        disabled={isPending}
                      >
                        <FormControl>
                          <SelectTrigger
                            className={cn(
                              inputClass,
                              "h-10 focus-visible:ring-0 focus-visible:ring-offset-0",
                            )}
                          >
                            <SelectValue placeholder="اختر حالة التوظيف" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {EMPLOYMENT_STATUS.map((status) => (
                            <SelectItem key={status} value={status}>
                              {employmentStatusLabels[status] || status}
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
                  name="roles"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>الأدوار</FormLabel>
                      <FormControl>
                        <div className="rounded-lg border border-gray-200 overflow-hidden bg-white">
                          <RolesSelect
                            multi={true}
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
              </div>
            </EditSection>

            <EditSection
              icon="ri-money-dollar-circle-line"
              title="معلومات الراتب"
              subtitle="الراتب الأساسي"
            >
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="base_salary"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>الراتب الأساسي</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="0"
                          className={inputClass}
                          value={field.value ?? ""}
                          onChange={(e) => {
                            const val = e.target.value.replace(/[^0-9.]/g, "");
                            field.onChange(val);
                          }}
                          disabled={isPending}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </EditSection>

            <EditSection
              icon="ri-links-line"
              title="التعيينات"
              subtitle="المصانع والورش والفروع"
            >
              <div className="space-y-6">
                <FormField
                  control={form.control}
                  name="factory_ids"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>المصانع</FormLabel>
                      <FormControl>
                        <div className="rounded-lg border border-gray-200 overflow-hidden bg-white">
                          <FactoriesSelect
                            multi={true}
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

                <FormField
                  control={form.control}
                  name="workshop_ids"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>الورش</FormLabel>
                      <FormControl>
                        <div className="rounded-lg border border-gray-200 overflow-hidden bg-white">
                          <WorkshopsSelect
                            multi={true}
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

                <FormField
                  control={form.control}
                  name="branch_ids"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={labelClass}>الفروع</FormLabel>
                      <FormControl>
                        <div className="rounded-lg border border-gray-200 overflow-hidden bg-white">
                          <MultiBranchesSelect
                            multi={true}
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
              </div>
            </EditSection>

            <div className="flex flex-wrap items-center justify-between gap-3 pb-10">
              <button
                type="button"
                onClick={onCancel}
                disabled={isPending}
                className="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-white bg-white/80 transition-colors disabled:opacity-50"
              >
                إلغاء
              </button>
              <button
                type="submit"
                disabled={isPending}
                className="px-6 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors flex items-center gap-2 disabled:opacity-60"
              >
                {isPending ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    جاري التحديث...
                  </>
                ) : (
                  <>
                    <i className="ri-save-line text-base" />
                    حفظ التغييرات
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

export default EditEmployee;
