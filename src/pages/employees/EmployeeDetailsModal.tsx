import { useGetEmployeeQueryOptions } from "@/api/v2/employees/employees.hooks";
import { TEmployee } from "@/api/v2/employees/employees.types";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Skeleton } from "@/components/ui/skeleton";
import { getRoleLabel } from "@/lib/roleLabels";
import { formatDate } from "@/utils/formatDate";
import { formatPhone } from "@/utils/formatPhone";
import { useQuery } from "@tanstack/react-query";
import { cn } from "@/lib/utils";

type Props = {
  employee: TEmployee | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

const getEmploymentTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    full_time: "دوام كامل",
    part_time: "دوام جزئي",
    contract: "عقد",
    intern: "متدرّب",
  };
  return labels[type] || type;
};

const getEmploymentStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    active: "نشط",
    on_leave: "في إجازة",
    suspended: "معلّق",
    terminated: "منتهي",
  };
  return labels[status] || status;
};

const statusPill = (status: string) => {
  const map: Record<string, string> = {
    active: "bg-emerald-50 text-emerald-700 border-emerald-200",
    on_leave: "bg-amber-50 text-amber-700 border-amber-200",
    suspended: "bg-red-50 text-red-700 border-red-200",
    terminated: "bg-gray-50 text-gray-600 border-gray-200",
  };
  return cn(
    "text-xs px-2 py-1 rounded-lg font-medium border w-fit",
    map[status] || "bg-gray-50 text-gray-600 border-gray-200",
  );
};

function Field({
  label,
  children,
  className,
}: {
  label: string;
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "rounded-xl p-3 bg-gray-50 border border-gray-100",
        className,
      )}
    >
      <p className="text-xs text-gray-400">{label}</p>
      <div className="text-sm font-semibold text-gray-800 mt-0.5 wrap-break-word">
        {children}
      </div>
    </div>
  );
}

function SectionCard({
  icon,
  title,
  children,
}: {
  icon: string;
  title: string;
  children: React.ReactNode;
}) {
  return (
    <div className="rounded-xl border border-gray-100 bg-white overflow-hidden">
      <div className="px-4 py-3 border-b border-gray-100 flex items-center gap-2 bg-gray-50/90">
        <i className={cn(icon, "text-lg text-emerald-600")} />
        <h3 className="text-xs font-bold text-gray-900">{title}</h3>
      </div>
      <div className="p-4">{children}</div>
    </div>
  );
}

export function EmployeeDetailsModal({ employee, open, onOpenChange }: Props) {
  const { data, isPending } = useQuery({
    ...useGetEmployeeQueryOptions(employee?.id || 0),
    enabled: open && !!employee?.id,
  });

  const employeeData = data || employee;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton={false}
        bodyClassName="p-0 min-h-0"
        className="sm:max-w-4xl p-0 gap-0 overflow-hidden rounded-2xl border-gray-100"
      >
        <DialogHeader className="sr-only">
          <DialogTitle>تفاصيل الموظف</DialogTitle>
          <DialogDescription>
            عرض جميع المعلومات المتعلقة بالموظف
          </DialogDescription>
        </DialogHeader>

        <div
          className="px-5 py-4 flex items-center justify-between border-b border-gray-100 bg-gray-50/90"
        >
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-100">
              <i className="ri-eye-line text-lg text-emerald-700" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-gray-900">تفاصيل الموظف</h2>
              <p className="text-xs text-gray-400 mt-0.5">
                {employeeData?.user.name ?? "—"}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            className="w-9 h-9 rounded-xl flex items-center justify-center text-gray-500 hover:bg-gray-100 transition-colors border border-gray-200"
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-lg" />
          </button>
        </div>

        <div className="px-5 py-4 max-h-[min(75vh,640px)] overflow-y-auto space-y-4 bg-gray-50/50">
          {isPending ? (
            <div className="space-y-3">
              <Skeleton className="h-24 w-full rounded-xl" />
              <Skeleton className="h-24 w-full rounded-xl" />
              <Skeleton className="h-24 w-full rounded-xl" />
            </div>
          ) : employeeData ? (
            <>
              <SectionCard icon="ri-user-line" title="المعلومات الأساسية">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <Field label="الاسم">{employeeData.user.name}</Field>
                  <Field label="البريد الإلكتروني">
                    <span dir="ltr" className="inline-block">
                      {employeeData.user.email}
                    </span>
                  </Field>
                  <Field label="كود الموظف">{employeeData.employee_code}</Field>
                  <Field label="القسم">
                    {employeeData.department?.name || "—"}
                  </Field>
                  <Field label="المسمى الوظيفي">
                    {employeeData.job_title?.name || "—"}
                  </Field>
                  <Field label="المدير">
                    {employeeData.manager?.user?.name || "—"}
                  </Field>
                  <Field label="نوع التوظيف">
                    {getEmploymentTypeLabel(employeeData.employment_type)}
                  </Field>
                  <Field label="حالة التوظيف">
                    <span
                      className={statusPill(employeeData.employment_status)}
                    >
                      {getEmploymentStatusLabel(
                        employeeData.employment_status,
                      )}
                    </span>
                  </Field>
                  {employeeData.user.roles &&
                    employeeData.user.roles.length > 0 && (
                      <Field label="الأدوار" className="sm:col-span-2">
                        <div className="flex flex-wrap gap-2 mt-1">
                          {employeeData.user.roles.map((role) => (
                            <span
                              key={role.id}
                              className="text-xs px-2 py-1 rounded-lg border border-gray-200 bg-white text-gray-700"
                            >
                              {getRoleLabel(role.name)}
                            </span>
                          ))}
                        </div>
                      </Field>
                    )}
                  <Field label="تاريخ التوظيف">
                    {formatDate(employeeData.hire_date)}
                  </Field>
                  {employeeData.termination_date && (
                    <Field label="تاريخ إنهاء الخدمة">
                      {formatDate(employeeData.termination_date)}
                    </Field>
                  )}
                  <Field label="تاريخ انتهاء فترة التجربة">
                    {formatDate(employeeData.probation_end_date)}
                  </Field>
                </div>
              </SectionCard>

              <SectionCard icon="ri-money-dollar-circle-line" title="المعلومات المالية">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <Field label="الراتب الأساسي">
                    {employeeData.base_salary?.toLocaleString() || 0} ج.م
                  </Field>
                  <Field label="بدل النقل">
                    {employeeData.transport_allowance?.toLocaleString() || 0}{" "}
                    ج.م
                  </Field>
                  <Field label="بدل السكن">
                    {employeeData.housing_allowance?.toLocaleString() || 0}{" "}
                    ج.م
                  </Field>
                  <Field label="بدلات أخرى">
                    {employeeData.other_allowances?.toLocaleString() || 0} ج.م
                  </Field>
                  <Field label="معدل الإضافي">
                    {employeeData.overtime_rate || 0}
                  </Field>
                  <Field label="معدل العمولة">
                    {employeeData.commission_rate || 0}%
                  </Field>
                </div>
              </SectionCard>

              <SectionCard icon="ri-calendar-event-line" title="معلومات الإجازات">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <Field label="رصيد أيام الإجازة">
                    {employeeData.vacation_days_balance || 0} يوم
                  </Field>
                  <Field label="أيام الإجازة المستخدمة">
                    {employeeData.vacation_days_used || 0} يوم
                  </Field>
                  <Field label="إجمالي أيام الإجازة السنوية">
                    {employeeData.annual_vacation_days || 0} يوم
                  </Field>
                </div>
              </SectionCard>

              <SectionCard icon="ri-time-line" title="جدول العمل">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <Field label="وقت بدء العمل">
                    {employeeData.work_start_time || "—"}
                  </Field>
                  <Field label="وقت انتهاء العمل">
                    {employeeData.work_end_time || "—"}
                  </Field>
                  <Field label="ساعات العمل اليومية">
                    {employeeData.work_hours_per_day || 0} ساعة
                  </Field>
                  <Field label="حد التأخير (بالدقائق)">
                    {employeeData.late_threshold_minutes || 0} دقيقة
                  </Field>
                </div>
              </SectionCard>

              {(employeeData.bank_name ||
                employeeData.bank_account_number ||
                employeeData.bank_iban) && (
                <SectionCard icon="ri-bank-line" title="معلومات البنك">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {employeeData.bank_name && (
                      <Field label="اسم البنك">{employeeData.bank_name}</Field>
                    )}
                    {employeeData.bank_account_number && (
                      <Field label="رقم الحساب">
                        {employeeData.bank_account_number}
                      </Field>
                    )}
                    {employeeData.bank_iban && (
                      <Field label="IBAN">{employeeData.bank_iban}</Field>
                    )}
                  </div>
                </SectionCard>
              )}

              {(employeeData.emergency_contact_name ||
                employeeData.emergency_contact_phone) && (
                <SectionCard
                  icon="ri-phone-line"
                  title="جهة الاتصال في حالات الطوارئ"
                >
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {employeeData.emergency_contact_name && (
                      <Field label="الاسم">
                        {employeeData.emergency_contact_name}
                      </Field>
                    )}
                    {employeeData.emergency_contact_phone && (
                      <Field label="رقم الهاتف">
                        <span dir="ltr" className="inline-block">
                          {formatPhone(
                            employeeData.emergency_contact_phone,
                            "-",
                          )}
                        </span>
                      </Field>
                    )}
                    {employeeData.emergency_contact_relation && (
                      <Field label="العلاقة">
                        {employeeData.emergency_contact_relation}
                      </Field>
                    )}
                  </div>
                </SectionCard>
              )}

              {employeeData.branches && employeeData.branches.length > 0 && (
                <SectionCard icon="ri-building-line" title="الفروع">
                  <div className="flex flex-wrap gap-2">
                    {employeeData.branches.map((branch) => (
                      <span
                        key={branch.id}
                        className="text-xs px-2 py-1 rounded-lg border border-gray-200 bg-white text-gray-700"
                      >
                        {branch.name}
                      </span>
                    ))}
                  </div>
                </SectionCard>
              )}
            </>
          ) : (
            <div className="text-center text-gray-400 py-12 text-sm">
              لا توجد بيانات للموظف
            </div>
          )}
        </div>

        <div className="px-5 py-3 border-t border-gray-100 bg-white flex justify-end">
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            className="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors"
          >
            إغلاق
          </button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
