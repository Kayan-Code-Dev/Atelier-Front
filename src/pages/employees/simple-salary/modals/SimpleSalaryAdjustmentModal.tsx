import { useEffect, useState } from "react";
import { useMutation } from "@tanstack/react-query";
import {
  useCreateSimpleSalaryAdditionMutationOptions,
  useCreateSimpleSalaryDeductionMutationOptions,
} from "@/api/simple-salary/simple-salary.hooks";
import { EmployeesSelect } from "@/components/custom/EmployeesSelect";
import { toast } from "sonner";
import { PERIOD_REGEX } from "../constants";

type AdjustmentKind = "bonus" | "deduction";

const adjustmentTypes: {
  value: AdjustmentKind;
  label: string;
  icon: string;
  color: string;
  bg: string;
}[] = [
  {
    value: "bonus",
    label: "مكافأة / حافز",
    icon: "ri-award-line",
    color: "text-emerald-600",
    bg: "bg-emerald-50 border-emerald-300",
  },
  {
    value: "deduction",
    label: "خصم",
    icon: "ri-subtract-line",
    color: "text-orange-600",
    bg: "bg-orange-50 border-orange-300",
  },
];

const bonusReasons = [
  "أداء ممتاز",
  "تحقيق المبيعات",
  "العمل الإضافي",
  "حافز شهري",
  "مكافأة خاصة",
];
const deductionReasons = [
  "غياب بدون إذن",
  "تأخر متكرر",
  "سلفة راتب",
  "خصم بدل",
  "تجاوز الإجازة",
];

export type SimpleSalaryAdjustmentModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  period: string;
  defaultEmployeeId?: number;
  onSuccess?: () => void;
};

export function SimpleSalaryAdjustmentModal({
  open,
  onOpenChange,
  period,
  defaultEmployeeId,
  onSuccess,
}: SimpleSalaryAdjustmentModalProps) {
  const [kind, setKind] = useState<AdjustmentKind>("bonus");
  const [employeeId, setEmployeeId] = useState("");
  const [amount, setAmount] = useState("");
  const [description, setDescription] = useState("");
  const [date, setDate] = useState(() => new Date().toISOString().split("T")[0]);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const addMut = useMutation(useCreateSimpleSalaryAdditionMutationOptions());
  const dedMut = useMutation(useCreateSimpleSalaryDeductionMutationOptions());

  useEffect(() => {
    if (open) {
      setKind("bonus");
      setEmployeeId(defaultEmployeeId != null ? String(defaultEmployeeId) : "");
      setAmount("");
      setDescription("");
      setDate(new Date().toISOString().split("T")[0]);
      setErrors({});
    }
  }, [open, defaultEmployeeId]);

  const currentReasons = kind === "bonus" ? bonusReasons : deductionReasons;
  const typeConfig = adjustmentTypes.find((t) => t.value === kind)!;

  const validate = () => {
    const e: Record<string, string> = {};
    if (!employeeId) e.employeeId = "اختر موظفاً";
    if (!amount || Number.isNaN(Number(amount)) || Number(amount) <= 0) {
      e.amount = "أدخل مبلغاً صحيحاً";
    }
    if (!description.trim()) e.description = "أدخل وصفاً";
    if (!PERIOD_REGEX.test(period)) e.period = "الشهر غير صالح في الصفحة الرئيسية";
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const handleSubmit = () => {
    if (!validate()) return;
    const id = Number(employeeId);
    const body = {
      employee_id: id,
      period,
      amount: Number(amount),
      reason: description.trim(),
      date,
    };
    const done = () => {
      toast.success(kind === "bonus" ? "تمت إضافة المكافأة." : "تمت إضافة الخصم.");
      onOpenChange(false);
      onSuccess?.();
    };
    if (kind === "bonus") {
      addMut.mutate(body, {
        onSuccess: (r) => {
          if (r?.addition) done();
        },
      });
    } else {
      dedMut.mutate(body, {
        onSuccess: (r) => {
          if (r?.deduction) done();
        },
      });
    }
  };

  const pending = addMut.isPending || dedMut.isPending;

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4"
      onClick={() => onOpenChange(false)}
      role="presentation"
    >
      <div
        className="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col"
        onClick={(e) => e.stopPropagation()}
        dir="rtl"
      >
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
          <h2 className="text-lg font-bold text-gray-800">إضافة تعديل على الراتب</h2>
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-gray-500 text-lg" />
          </button>
        </div>

        <div className="p-6 space-y-5 overflow-y-auto flex-1">
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">نوع التعديل</label>
            <div className="grid grid-cols-2 gap-3">
              {adjustmentTypes.map((t) => (
                <button
                  key={t.value}
                  type="button"
                  onClick={() => {
                    setKind(t.value);
                    setDescription("");
                  }}
                  className={`flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition-all ${
                    kind === t.value
                      ? `${t.bg} border-current`
                      : "border-gray-200 bg-white hover:bg-gray-50"
                  }`}
                >
                  <div
                    className={`w-9 h-9 flex items-center justify-center rounded-lg ${
                      kind === t.value ? "bg-white" : "bg-gray-100"
                    }`}
                  >
                    <i
                      className={`${t.icon} text-lg ${
                        kind === t.value ? t.color : "text-gray-500"
                      }`}
                    />
                  </div>
                  <span
                    className={`text-xs font-semibold ${
                      kind === t.value ? t.color : "text-gray-500"
                    }`}
                  >
                    {t.label}
                  </span>
                </button>
              ))}
            </div>
          </div>

          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1.5">الموظف</label>
            <EmployeesSelect
              params={{ per_page: 10 }}
              value={employeeId}
              onChange={(v) => setEmployeeId(v || "")}
              disabled={pending || defaultEmployeeId != null}
              placeholder="اختر الموظف..."
              className={errors.employeeId ? "border-red-400" : "border-gray-200"}
            />
            {defaultEmployeeId != null ? (
              <p className="text-xs text-gray-400 mt-1">موظف محدد من الجدول</p>
            ) : null}
            {errors.employeeId ? (
              <p className="text-xs text-red-500 mt-1">{errors.employeeId}</p>
            ) : null}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                المبلغ (ج.م)
              </label>
              <div className="relative">
                <input
                  type="number"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  placeholder="0.00"
                  min="0.01"
                  step="0.01"
                  className={`w-full px-3 py-2.5 pl-12 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-200 ${
                    errors.amount ? "border-red-400" : "border-gray-200"
                  }`}
                />
                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">
                  ج.م
                </span>
              </div>
              {errors.amount ? (
                <p className="text-xs text-red-500 mt-1">{errors.amount}</p>
              ) : null}
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1.5">التاريخ</label>
              <input
                type="date"
                value={date}
                onChange={(e) => setDate(e.target.value)}
                className="w-full px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-200"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">السبب / الوصف</label>
            <div className="flex flex-wrap gap-2 mb-2">
              {currentReasons.map((r) => (
                <button
                  key={r}
                  type="button"
                  onClick={() => setDescription(r)}
                  className={`px-3 py-1 rounded-full text-xs border transition-all whitespace-nowrap ${
                    description === r
                      ? `${typeConfig.bg} ${typeConfig.color} border-current`
                      : "border-gray-200 text-gray-600 hover:border-gray-300 bg-white"
                  }`}
                >
                  {r}
                </button>
              ))}
            </div>
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="أو اكتب وصفاً مخصصاً..."
              rows={2}
              maxLength={200}
              className={`w-full px-3 py-2.5 rounded-lg border text-sm resize-none focus:outline-none focus:ring-2 focus:ring-violet-200 ${
                errors.description ? "border-red-400" : "border-gray-200"
              }`}
            />
            {errors.description ? (
              <p className="text-xs text-red-500 mt-0.5">{errors.description}</p>
            ) : null}
            {errors.period ? (
              <p className="text-xs text-red-500 mt-1">{errors.period}</p>
            ) : null}
          </div>
        </div>

        <div className="px-6 pb-5 pt-3 flex gap-3 justify-end border-t border-gray-100 flex-shrink-0">
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            className="px-5 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 whitespace-nowrap"
          >
            إلغاء
          </button>
          <button
            type="button"
            onClick={handleSubmit}
            disabled={pending}
            className={`px-6 py-2.5 rounded-lg text-sm text-white font-medium whitespace-nowrap disabled:opacity-50 ${
              kind === "bonus"
                ? "bg-emerald-500 hover:bg-emerald-600"
                : "bg-orange-500 hover:bg-orange-600"
            }`}
          >
            {pending ? "جاري الحفظ..." : (
              <>
                <i className={`${typeConfig.icon} ml-1`} /> حفظ التعديل
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
}
