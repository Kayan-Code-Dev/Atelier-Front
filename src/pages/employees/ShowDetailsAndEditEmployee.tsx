import { useParams, Link } from "react-router";
import { useQuery } from "@tanstack/react-query";
import {
  useGetEmployeeQueryOptions,
  useGetEmployeeAssignmentsQueryOptions,
} from "@/api/v2/employees/employees.hooks";
import { Skeleton } from "@/components/ui/skeleton";
import { getRoleLabel } from "@/lib/roleLabels";
import { formatDate } from "@/utils/formatDate";
import { formatPhone } from "@/utils/formatPhone";
import { useState } from "react";
import { cn } from "@/lib/utils";
import EditEmployee from "./EditEmployee";
import { TerminateEmployeeModal } from "./TerminateEmployeeModal";

const getEmploymentTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    full_time: "دوام كامل",
    part_time: "دوام جزئي",
    contract: "عقد",
    intern: "متدرب",
  };
  return labels[type] || type;
};

const getEmploymentStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    active: "نشط",
    on_leave: "في إجازة",
    suspended: "معلق",
    terminated: "منتهي",
  };
  return labels[status] || status;
};

function statusPillClass(status: string) {
  const map: Record<string, string> = {
    active: "bg-emerald-50 text-emerald-700 border-emerald-200",
    on_leave: "bg-amber-50 text-amber-700 border-amber-200",
    suspended: "bg-red-50 text-red-700 border-red-200",
    terminated: "bg-gray-50 text-gray-600 border-gray-200",
  };
  return cn(
    "text-xs px-2 py-1 rounded-lg font-medium border w-fit inline-block",
    map[status] || "bg-gray-50 text-gray-600 border-gray-200",
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
    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
      <div className="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2 bg-gray-50/90">
        <i className={cn(icon, "text-lg text-emerald-600")} />
        <h2 className="text-sm font-bold text-gray-900">{title}</h2>
      </div>
      <div className="p-5">{children}</div>
    </div>
  );
}

function DetailField({
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

function ShowDetailsAndEditEmployee() {
  const { id } = useParams<{ id: string }>();
  const employeeId = id ? Number(id) : 0;
  const [isEditMode, setIsEditMode] = useState(false);
  const [isTerminateModalOpen, setIsTerminateModalOpen] = useState(false);

  const { data: employee, isPending, refetch } = useQuery({
    ...useGetEmployeeQueryOptions(employeeId),
    enabled: employeeId > 0 && !isEditMode,
  });

  const { data: assignments, isPending: isAssignmentsPending } = useQuery({
    ...useGetEmployeeAssignmentsQueryOptions(employeeId),
    enabled: employeeId > 0 && !isEditMode,
  });

  if (isEditMode && employee) {
    return (
      <EditEmployee
        employee={employee}
        onCancel={() => setIsEditMode(false)}
        onSuccess={() => {
          setIsEditMode(false);
          refetch();
        }}
      />
    );
  }

  return (
    <div className="min-h-screen bg-gray-50/50 p-6" dir="rtl">
      <div className="max-w-5xl mx-auto">
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
          <div className="flex items-start gap-3">
            <Link
              to="/employees"
              className="w-10 h-10 shrink-0 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:bg-white bg-white shadow-sm transition-colors"
              aria-label="العودة للقائمة"
            >
              <i className="ri-arrow-right-line text-lg" />
            </Link>
            <div>
              <h1 className="text-xl font-bold text-gray-900">تفاصيل الموظف</h1>
              <p className="text-sm text-gray-400 mt-0.5">
                عرض جميع المعلومات المتعلقة بالموظف
              </p>
            </div>
          </div>
          {employee && (
            <div className="flex flex-wrap items-center gap-2">
              {employee.employment_status !== "terminated" && (
                <button
                  type="button"
                  onClick={() => setIsTerminateModalOpen(true)}
                  className="px-4 py-2 rounded-lg text-sm font-medium text-red-700 bg-white border border-red-200 hover:bg-red-50 transition-colors flex items-center gap-2"
                >
                  <i className="ri-user-unfollow-line text-base" />
                  إنهاء خدمة الموظف
                </button>
              )}
              <button
                type="button"
                onClick={() => setIsEditMode(true)}
                className="px-4 py-2 rounded-lg text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 transition-colors flex items-center gap-2 shadow-sm"
              >
                <i className="ri-edit-line text-base" />
                تعديل الموظف
              </button>
            </div>
          )}
        </div>

        <div className="space-y-5">
        {isPending ? (
          <div className="space-y-4">
            <Skeleton className="h-6 w-full" />
            <Skeleton className="h-6 w-full" />
            <Skeleton className="h-6 w-full" />
          </div>
        ) : employee ? (
          <>
            <SectionCard icon="ri-user-line" title="المعلومات الأساسية">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <DetailField label="الاسم">{employee.user.name}</DetailField>
                <DetailField label="البريد الإلكتروني">
                  <span dir="ltr" className="inline-block">
                    {employee.user.email}
                  </span>
                </DetailField>
                <DetailField label="كود الموظف">
                  {employee.employee_code}
                </DetailField>
                <DetailField label="القسم">
                  {employee.department?.name || "—"}
                </DetailField>
                <DetailField label="المسمى الوظيفي">
                  {employee.job_title?.name || "—"}
                </DetailField>
                <DetailField label="المدير">
                  {employee.manager?.user?.name || "—"}
                </DetailField>
                <DetailField label="نوع التوظيف">
                  {getEmploymentTypeLabel(employee.employment_type)}
                </DetailField>
                <DetailField label="حالة التوظيف">
                  <span
                    className={statusPillClass(employee.employment_status)}
                  >
                    {getEmploymentStatusLabel(employee.employment_status)}
                  </span>
                </DetailField>
                {employee.user.roles && employee.user.roles.length > 0 && (
                  <DetailField label="الأدوار" className="md:col-span-2">
                    <div className="flex flex-wrap gap-2 mt-1">
                      {employee.user.roles.map((role) => (
                        <span
                          key={role.id}
                          className="text-xs px-2 py-1 rounded-lg border border-gray-200 bg-white text-gray-700"
                        >
                          {getRoleLabel(role.name)}
                        </span>
                      ))}
                    </div>
                  </DetailField>
                )}
                <DetailField label="تاريخ التوظيف">
                  {formatDate(employee.hire_date)}
                </DetailField>
                {employee.termination_date && (
                  <DetailField label="تاريخ إنهاء الخدمة">
                    {formatDate(employee.termination_date)}
                  </DetailField>
                )}
                <DetailField label="تاريخ انتهاء فترة التجربة">
                  {formatDate(employee.probation_end_date)}
                </DetailField>
              </div>
            </SectionCard>

            <SectionCard
              icon="ri-money-dollar-circle-line"
              title="المعلومات المالية"
            >
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <DetailField label="الراتب الأساسي">
                  {employee.base_salary?.toLocaleString() || 0} ج.م
                </DetailField>
                <DetailField label="بدل النقل">
                  {employee.transport_allowance?.toLocaleString() || 0} ج.م
                </DetailField>
                <DetailField label="بدل السكن">
                  {employee.housing_allowance?.toLocaleString() || 0} ج.م
                </DetailField>
                <DetailField label="بدلات أخرى">
                  {employee.other_allowances?.toLocaleString() || 0} ج.م
                </DetailField>
                <DetailField label="معدل الإضافي">
                  {employee.overtime_rate || 0}
                </DetailField>
                <DetailField label="معدل العمولة">
                  {employee.commission_rate || 0}%
                </DetailField>
              </div>
            </SectionCard>

            <SectionCard icon="ri-calendar-event-line" title="معلومات الإجازات">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <DetailField label="رصيد أيام الإجازة">
                  {employee.vacation_days_balance || 0} يوم
                </DetailField>
                <DetailField label="أيام الإجازة المستخدمة">
                  {employee.vacation_days_used || 0} يوم
                </DetailField>
                <DetailField label="إجمالي أيام الإجازة السنوية">
                  {employee.annual_vacation_days || 0} يوم
                </DetailField>
              </div>
            </SectionCard>

            <SectionCard icon="ri-time-line" title="جدول العمل">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <DetailField label="وقت بدء العمل">
                  {employee.work_start_time || "—"}
                </DetailField>
                <DetailField label="وقت انتهاء العمل">
                  {employee.work_end_time || "—"}
                </DetailField>
                <DetailField label="ساعات العمل اليومية">
                  {employee.work_hours_per_day || 0} ساعة
                </DetailField>
                <DetailField label="حد التأخير (بالدقائق)">
                  {employee.late_threshold_minutes || 0} دقيقة
                </DetailField>
              </div>
            </SectionCard>

            {(employee.bank_name ||
              employee.bank_account_number ||
              employee.bank_iban) && (
              <SectionCard icon="ri-bank-line" title="معلومات البنك">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  {employee.bank_name && (
                    <DetailField label="اسم البنك">
                      {employee.bank_name}
                    </DetailField>
                  )}
                  {employee.bank_account_number && (
                    <DetailField label="رقم الحساب">
                      {employee.bank_account_number}
                    </DetailField>
                  )}
                  {employee.bank_iban && (
                    <DetailField label="IBAN">{employee.bank_iban}</DetailField>
                  )}
                </div>
              </SectionCard>
            )}

            {(employee.emergency_contact_name ||
              employee.emergency_contact_phone) && (
              <SectionCard
                icon="ri-phone-line"
                title="جهة الاتصال في حالات الطوارئ"
              >
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  {employee.emergency_contact_name && (
                    <DetailField label="الاسم">
                      {employee.emergency_contact_name}
                    </DetailField>
                  )}
                  {employee.emergency_contact_phone && (
                    <DetailField label="رقم الهاتف">
                      <span dir="ltr" className="inline-block">
                        {formatPhone(employee.emergency_contact_phone, "-")}
                      </span>
                    </DetailField>
                  )}
                  {employee.emergency_contact_relation && (
                    <DetailField label="العلاقة">
                      {employee.emergency_contact_relation}
                    </DetailField>
                  )}
                </div>
              </SectionCard>
            )}

            {employee.branches && employee.branches.length > 0 && (
              <SectionCard icon="ri-building-line" title="الفروع">
                <div className="flex flex-wrap gap-2">
                  {employee.branches.map((branch) => (
                    <span
                      key={branch.id}
                      className="text-xs px-2 py-1 rounded-lg border border-gray-200 bg-gray-50 text-gray-700"
                    >
                      {branch.name}
                    </span>
                  ))}
                </div>
              </SectionCard>
            )}

            {isAssignmentsPending ? (
              <SectionCard icon="ri-links-line" title="التعيينات">
                <Skeleton className="h-10 w-full rounded-xl" />
              </SectionCard>
            ) : assignments && assignments.length > 0 ? (
              <SectionCard icon="ri-links-line" title="التعيينات">
                <div className="space-y-4">
                  {assignments.filter((a) => a.entity_type === "factory")
                    .length > 0 && (
                    <div>
                      <h3 className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                        المصانع
                      </h3>
                      <div className="flex flex-wrap gap-2">
                        {assignments
                          .filter((a) => a.entity_type === "factory")
                          .map((assignment) => (
                            <span
                              key={assignment.id}
                              className={cn(
                                "text-xs px-2 py-1 rounded-lg border",
                                assignment.is_primary
                                  ? "bg-emerald-50 text-emerald-800 border-emerald-200"
                                  : "bg-white text-gray-700 border-gray-200",
                              )}
                            >
                              {assignment.entity_name}
                              {assignment.is_primary && (
                                <span className="mr-1">(رئيسي)</span>
                              )}
                            </span>
                          ))}
                      </div>
                    </div>
                  )}

                  {assignments.filter((a) => a.entity_type === "workshop")
                    .length > 0 && (
                    <div>
                      <h3 className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                        الورش
                      </h3>
                      <div className="flex flex-wrap gap-2">
                        {assignments
                          .filter((a) => a.entity_type === "workshop")
                          .map((assignment) => (
                            <span
                              key={assignment.id}
                              className={cn(
                                "text-xs px-2 py-1 rounded-lg border",
                                assignment.is_primary
                                  ? "bg-emerald-50 text-emerald-800 border-emerald-200"
                                  : "bg-white text-gray-700 border-gray-200",
                              )}
                            >
                              {assignment.entity_name}
                              {assignment.is_primary && (
                                <span className="mr-1">(رئيسي)</span>
                              )}
                            </span>
                          ))}
                      </div>
                    </div>
                  )}

                  {assignments.filter((a) => a.entity_type === "branch")
                    .length > 0 && (
                    <div>
                      <h3 className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                        الفروع (تعيينات)
                      </h3>
                      <div className="flex flex-wrap gap-2">
                        {assignments
                          .filter((a) => a.entity_type === "branch")
                          .map((assignment) => (
                            <span
                              key={assignment.id}
                              className={cn(
                                "text-xs px-2 py-1 rounded-lg border",
                                assignment.is_primary
                                  ? "bg-emerald-50 text-emerald-800 border-emerald-200"
                                  : "bg-white text-gray-700 border-gray-200",
                              )}
                            >
                              {assignment.entity_name}
                              {assignment.is_primary && (
                                <span className="mr-1">(رئيسي)</span>
                              )}
                            </span>
                          ))}
                      </div>
                    </div>
                  )}
                </div>
              </SectionCard>
            ) : null}
          </>
        ) : (
          <div className="text-center text-gray-400 py-16 text-sm rounded-xl border border-dashed border-gray-200 bg-white">
            لا توجد بيانات للموظف
          </div>
        )}
        </div>
      </div>

      {employee && (
        <TerminateEmployeeModal
          employee={employee}
          open={isTerminateModalOpen}
          onOpenChange={setIsTerminateModalOpen}
          onSuccess={() => {
            refetch();
          }}
        />
      )}
    </div>
  );
}

export default ShowDetailsAndEditEmployee;
