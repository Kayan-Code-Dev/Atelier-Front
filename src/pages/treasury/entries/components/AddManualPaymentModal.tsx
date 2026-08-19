import { useMemo, useState } from "react";
import { TCashbox } from "@/api/v2/cashboxes/cashboxes.types";
import { EXPENSE_CATEGORIES_WITH_SUBS } from "@/api/v2/expenses/expenses.types";

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  cashboxes: TCashbox[];
  isSubmitting: boolean;
  onSubmit: (
    payload:
      | {
          entryFlow: "inside";
          cashboxId: number;
          amount: number;
          description: string;
          payment_method?: string;
          received_from?: string;
          notes?: string;
        }
      | {
          entryFlow: "outside";
          branch_id: number;
          category: string;
          subcategory?: string | null;
          amount: number;
          expense_date: string;
          vendor: string;
          reference_number: string;
          description: string;
          notes: string;
        }
  ) => void;
};

export default function AddManualPaymentModal({
  open,
  onOpenChange,
  cashboxes,
  isSubmitting,
  onSubmit,
}: Props) {
  const [cashboxId, setCashboxId] = useState<string>("");
  const [entryFlow, setEntryFlow] = useState<"inside" | "outside">("inside");
  const [amount, setAmount] = useState<string>("");
  const [description, setDescription] = useState<string>("");
  const [paymentMethod, setPaymentMethod] = useState<string>("cash");
  const [receivedFrom, setReceivedFrom] = useState<string>("");
  const [notes, setNotes] = useState<string>("");
  const [category, setCategory] = useState<string>("");
  const [subcategory, setSubcategory] = useState<string>("");
  const [expenseDate, setExpenseDate] = useState<string>(
    new Date().toISOString().slice(0, 10)
  );
  const [vendor, setVendor] = useState<string>("");
  const [referenceNumber, setReferenceNumber] = useState<string>("");

  const canSubmit = useMemo(() => {
    const amountNum = Number(amount);
    const commonValid =
      Number(cashboxId) > 0 &&
      amountNum > 0 &&
      amountNum >= 0.01 &&
      description.trim().length > 0 &&
      description.trim().length <= 500 &&
      notes.trim().length <= 1000 &&
      !Number.isNaN(amountNum);

    if (entryFlow === "inside") {
      return (
        commonValid &&
        paymentMethod.trim().length <= 50 &&
        receivedFrom.trim().length <= 255
      );
    }

    return (
      commonValid &&
      category.trim().length > 0 &&
      expenseDate.trim().length > 0 &&
      vendor.trim().length > 0 &&
      referenceNumber.trim().length > 0
    );
  }, [
    cashboxId,
    amount,
    description,
    paymentMethod,
    receivedFrom,
    notes,
    entryFlow,
    category,
    expenseDate,
    vendor,
    referenceNumber,
  ]);

  const availableSubcategories = useMemo(
    () =>
      EXPENSE_CATEGORIES_WITH_SUBS.find((c) => c.id === category)?.subcategories ??
      [],
    [category]
  );

  const handleSubmit = () => {
    if (!canSubmit) return;
    const selectedCashbox = cashboxes.find((c) => c.id === Number(cashboxId));
    if (!selectedCashbox) return;

    if (entryFlow === "inside") {
      onSubmit({
        entryFlow: "inside",
        cashboxId: Number(cashboxId),
        amount: Number(amount),
        description: description.trim(),
        payment_method: paymentMethod.trim() || "cash",
        received_from: receivedFrom.trim() || undefined,
        notes: notes.trim() || undefined,
      });
      return;
    }

    onSubmit({
      entryFlow: "outside",
      branch_id: selectedCashbox.branch_id,
      category: category.trim(),
      subcategory: subcategory.trim() || null,
      amount: Number(amount),
      expense_date: expenseDate,
      vendor: vendor.trim(),
      reference_number: referenceNumber.trim(),
      description: description.trim(),
      notes: notes.trim(),
    });
  };

  return (
    open ? (
      <div
        className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
        onClick={() => onOpenChange(false)}
      >
        <div
          dir="rtl"
          className="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col"
          onClick={(e) => e.stopPropagation()}
        >
          <div className="bg-blue-900 text-white px-6 py-4 flex items-center justify-between shrink-0">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-700">
                <i className="ri-file-add-line text-lg" />
              </div>
              <div>
                <h2 className="font-bold text-lg">إضافة قيد محاسبي جديد</h2>
                <p className="text-blue-300 text-xs">
                  اختر نوع القيد ثم أدخل بياناته
                </p>
              </div>
            </div>
            <button
              onClick={() => onOpenChange(false)}
              className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-700 cursor-pointer text-blue-200 hover:text-white transition-colors"
            >
              <i className="ri-close-line text-xl" />
            </button>
          </div>

          <div className="overflow-y-auto flex-1 p-6 space-y-4">
            <div>
              <label className="text-xs text-gray-500 block mb-1.5">نوع القيد *</label>
              <div className="grid grid-cols-2 gap-2 rounded-lg bg-slate-100 p-1">
                <button
                  type="button"
                  onClick={() => setEntryFlow("inside")}
                  className={`px-3 py-2 rounded-md text-sm transition-colors cursor-pointer ${
                    entryFlow === "inside"
                      ? "bg-white text-blue-700 font-semibold shadow-sm"
                      : "text-slate-600 hover:text-slate-800"
                  }`}
                >
                  داخل الصندوق
                </button>
                <button
                  type="button"
                  onClick={() => setEntryFlow("outside")}
                  className={`px-3 py-2 rounded-md text-sm transition-colors cursor-pointer ${
                    entryFlow === "outside"
                      ? "bg-white text-red-700 font-semibold shadow-sm"
                      : "text-slate-600 hover:text-slate-800"
                  }`}
                >
                  خارج الصندوق
                </button>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="text-xs text-gray-500 block mb-1.5">الصندوق *</label>
                <select
                  value={cashboxId}
                  onChange={(e) => setCashboxId(e.target.value)}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                >
                  <option value="">اختر الصندوق</option>
                  {cashboxes.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="text-xs text-gray-500 block mb-1.5">المبلغ *</label>
                <input
                  type="number"
                  min="0.01"
                  step="0.01"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                />
              </div>
              <div className="col-span-2">
                <label className="text-xs text-gray-500 block mb-1.5">وصف القيد *</label>
                <input
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                />
              </div>
              {entryFlow === "inside" ? (
                <>
                  <div>
                    <label className="text-xs text-gray-500 block mb-1.5">طريقة الدفع</label>
                    <input
                      value={paymentMethod}
                      onChange={(e) => setPaymentMethod(e.target.value)}
                      className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                    />
                  </div>
                  <div>
                    <label className="text-xs text-gray-500 block mb-1.5">مستلم من</label>
                    <input
                      value={receivedFrom}
                      onChange={(e) => setReceivedFrom(e.target.value)}
                      className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                    />
                  </div>
                </>
              ) : (
                <>
                  <div>
                    <label className="text-xs text-gray-500 block mb-1.5">الفئة *</label>
                    <select
                      value={category}
                      onChange={(e) => {
                        setCategory(e.target.value);
                        setSubcategory("");
                      }}
                      className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                    >
                      <option value="">اختر الفئة</option>
                      {EXPENSE_CATEGORIES_WITH_SUBS.map((c) => (
                        <option key={c.id} value={c.id}>
                          {c.name}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500 block mb-1.5">الفئة الفرعية</label>
                    <select
                      value={subcategory}
                      onChange={(e) => setSubcategory(e.target.value)}
                      disabled={!category}
                      className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 disabled:bg-gray-100 focus:outline-none focus:border-blue-400"
                    >
                      <option value="">اختر الفئة الفرعية</option>
                      {availableSubcategories.map((sub) => (
                        <option key={sub.id} value={sub.id}>
                          {sub.name}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500 block mb-1.5">تاريخ المصروف *</label>
                    <input
                      type="date"
                      value={expenseDate}
                      onChange={(e) => setExpenseDate(e.target.value)}
                      className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                    />
                  </div>
                  <div>
                    <label className="text-xs text-gray-500 block mb-1.5">المورد *</label>
                    <input
                      value={vendor}
                      onChange={(e) => setVendor(e.target.value)}
                      className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                    />
                  </div>
                  <div className="col-span-2">
                    <label className="text-xs text-gray-500 block mb-1.5">رقم المرجع *</label>
                    <input
                      value={referenceNumber}
                      onChange={(e) => setReferenceNumber(e.target.value)}
                      className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                    />
                  </div>
                </>
              )}
              <div className="col-span-2">
                <label className="text-xs text-gray-500 block mb-1.5">ملاحظات</label>
                <textarea
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm min-h-20 bg-gray-50 focus:outline-none focus:border-blue-400"
                />
              </div>
            </div>
          </div>

          <div className="border-t border-gray-100 px-6 py-4 flex items-center justify-between shrink-0 bg-gray-50">
            <button
              type="button"
              onClick={() => onOpenChange(false)}
              className="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-white cursor-pointer whitespace-nowrap"
            >
              إلغاء
            </button>
            <button
              type="button"
              onClick={handleSubmit}
              disabled={!canSubmit || isSubmitting}
              className={`px-5 py-2.5 text-sm text-white rounded-lg cursor-pointer whitespace-nowrap transition-colors ${
                !canSubmit || isSubmitting
                  ? "bg-gray-300 cursor-not-allowed"
                  : "bg-blue-700 hover:bg-blue-800"
              }`}
            >
              <i className="ri-check-double-line ml-1" />
              {isSubmitting
                ? "جاري الحفظ..."
                : entryFlow === "outside"
                  ? "إنشاء المصروف"
                  : "اعتماد القيد"}
            </button>
          </div>
        </div>
      </div>
    ) : null
  );
}
