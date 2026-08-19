import { useGetEmployeeCustodyByIdQueryOptions, useGetEmployeeCustodyTypesQueryOptions } from "@/api/v2/employees/employee-custodies/employee-custodies.hooks";
import { TEmployeeCustody } from "@/api/v2/employees/employee-custodies/employee-custodies.types";
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { Skeleton } from "@/components/ui/skeleton";
import { formatDate } from "@/utils/formatDate";
import { useQuery } from "@tanstack/react-query";
import {
  CUSTODY_DISPLAY_STATUS_CONFIG,
  custodyTypeLabel,
  getCustodyDisplayStatus,
  getCustodyTypeVisual,
} from "./custodyDisplayConfig";
import { cn } from "@/lib/utils";

type Props = {
  employeeCustody: TEmployeeCustody | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

const getConditionLabel = (condition: string) => {
  const labels: Record<string, string> = {
    new: "جديد",
    good: "جيد",
    fair: "مقبول",
    poor: "ضعيف",
  };
  return labels[condition] || condition;
};

export function EmployeeCustodyDetailsModal({
  employeeCustody,
  open,
  onOpenChange,
}: Props) {
  const { data: types = [] } = useQuery({
    ...useGetEmployeeCustodyTypesQueryOptions(),
    enabled: open,
  });

  const { data, isPending } = useQuery({
    ...useGetEmployeeCustodyByIdQueryOptions(employeeCustody?.id || 0),
    enabled: open && !!employeeCustody?.id,
  });

  const custodyData = data || employeeCustody;

  const headerVisual = custodyData
    ? getCustodyTypeVisual(custodyData.type)
    : getCustodyTypeVisual("");
  const displayStatus = custodyData ? getCustodyDisplayStatus(custodyData) : "active";
  const statusCfg = CUSTODY_DISPLAY_STATUS_CONFIG[displayStatus];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton={false}
        className="sm:max-w-4xl rounded-2xl border-gray-100 p-0 gap-0 overflow-hidden"
        bodyClassName="p-0 max-h-[min(90vh,880px)] overflow-y-auto"
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-10">
          <div className="flex items-center gap-3 min-w-0">
            <div
              className={cn(
                "w-9 h-9 flex items-center justify-center rounded-xl shrink-0",
                headerVisual.bg
              )}
            >
              <i className={cn(headerVisual.icon, headerVisual.color, "text-lg")} />
            </div>
            <div className="min-w-0">
              <h2 className="text-lg font-bold text-gray-800 truncate">تفاصيل الضمان</h2>
              {custodyData ? (
                <p className="text-xs text-gray-500 truncate">{custodyData.name}</p>
              ) : null}
            </div>
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

        <div className="p-6 space-y-6" dir="rtl">
          {isPending ? (
            <div className="space-y-4">
              <Skeleton className="h-6 w-full" />
              <Skeleton className="h-6 w-full" />
              <Skeleton className="h-6 w-full" />
            </div>
          ) : custodyData ? (
            <>
              <div className="flex flex-wrap items-center gap-2">
                <span
                  className={cn(
                    "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold",
                    statusCfg.bg,
                    statusCfg.color
                  )}
                >
                  <span className={cn("w-1.5 h-1.5 rounded-full", statusCfg.dot)} />
                  {statusCfg.label}
                </span>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="rounded-xl border border-gray-100 bg-[#fafaff] p-4">
                  <p className="text-xs font-semibold text-violet-600 mb-1">الموظف</p>
                  <p className="text-base font-medium text-gray-800">
                    {custodyData.employee?.user?.name || "-"}
                  </p>
                </div>
                <div className="rounded-xl border border-gray-100 bg-[#fafaff] p-4">
                  <p className="text-xs font-semibold text-violet-600 mb-1">نوع الضمان</p>
                  <p className="text-base text-gray-800">
                    {custodyTypeLabel(custodyData.type, types)}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-500">الاسم</p>
                  <p className="text-base text-gray-900">{custodyData.name}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-500">الوصف</p>
                  <p className="text-base text-gray-800">{custodyData.description || "-"}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-500">الرقم التسلسلي</p>
                  <p className="text-base text-gray-900">{custodyData.serial_number}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-500">علامة الأصل</p>
                  <p className="text-base text-gray-900">{custodyData.asset_tag}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-500">القيمة</p>
                  <p className="text-base font-semibold text-gray-900">
                    {custodyData.value.toLocaleString("ar-SA")} ج.م
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-500">الحالة عند التعيين</p>
                  <p className="text-base text-gray-800">
                    {getConditionLabel(custodyData.condition_on_assignment)}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-500">تاريخ التعيين</p>
                  <p className="text-base text-gray-800">{formatDate(custodyData.assigned_date)}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-500">تاريخ الإرجاع المتوقع</p>
                  <p className="text-base text-gray-800">
                    {formatDate(custodyData.expected_return_date)}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-500">معين بواسطة</p>
                  <p className="text-base text-gray-800">{custodyData.assigned_by?.name || "-"}</p>
                </div>
              </div>

              {custodyData.notes ? (
                <div className="border border-gray-100 rounded-xl p-4 bg-white">
                  <h3 className="text-sm font-semibold text-violet-700 mb-2">ملاحظات</h3>
                  <p className="text-base text-gray-700">{custodyData.notes}</p>
                </div>
              ) : null}

              <div className="border border-gray-100 rounded-xl p-4">
                <h3 className="text-sm font-semibold text-gray-700 mb-3">معلومات إضافية</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p className="text-sm font-medium text-gray-500">تاريخ الإنشاء</p>
                    <p className="text-base text-gray-800">{formatDate(custodyData.created_at)}</p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-500">آخر تحديث</p>
                    <p className="text-base text-gray-800">{formatDate(custodyData.updated_at)}</p>
                  </div>
                </div>
              </div>

              <div className="flex justify-end pt-2">
                <button
                  type="button"
                  onClick={() => onOpenChange(false)}
                  className="px-5 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700"
                >
                  إغلاق
                </button>
              </div>
            </>
          ) : (
            <div className="text-center text-gray-500 py-8">لا توجد بيانات للضمان</div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
