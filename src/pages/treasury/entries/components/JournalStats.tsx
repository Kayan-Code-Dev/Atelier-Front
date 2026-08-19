import { JournalEntry } from "../journal.types";

interface Props {
  entries: JournalEntry[];
}

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

export default function JournalStats({ entries }: Props) {
  const approved = entries.filter((e) => e.status === "معتمد");
  const draft = entries.filter((e) => e.status === "مسودة");
  const totalDebit = approved.reduce((s, e) => s + e.totalDebit, 0);
  const totalCredit = approved.reduce((s, e) => s + e.totalCredit, 0);
  const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01;

  const typeColors: Record<string, string> = {
    عادي: "bg-blue-50 text-blue-600",
    افتتاحي: "bg-emerald-50 text-emerald-600",
    تسوية: "bg-amber-50 text-amber-600",
    إقفال: "bg-purple-50 text-purple-600",
  };

  const typeCounts = entries.reduce<Record<string, number>>((acc, e) => {
    acc[e.type] = (acc[e.type] || 0) + 1;
    return acc;
  }, {});

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600">
              <i className="ri-file-list-3-line text-xl" />
            </div>
            <span className="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">{entries.length} قيد</span>
          </div>
          <p className="text-2xl font-bold text-gray-800">{approved.length}</p>
          <p className="text-sm text-gray-500 mt-1">قيود معتمدة</p>
          {draft.length > 0 && (
            <p className="text-xs text-amber-500 mt-1">
              <i className="ri-time-line ml-1" />
              {draft.length} مسودة بانتظار الاعتماد
            </p>
          )}
        </div>

        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-red-50 text-red-500">
              <i className="ri-arrow-left-up-line text-xl" />
            </div>
            <span className="text-xs bg-red-50 text-red-500 px-2 py-0.5 rounded-full">مدين</span>
          </div>
          <p className="text-xl font-bold text-gray-800">{fmt(totalDebit)}</p>
          <p className="text-sm text-gray-500 mt-1">إجمالي المدين</p>
          <p className="text-xs text-gray-400 mt-1">ج.م</p>
        </div>

        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-green-50 text-green-600">
              <i className="ri-arrow-right-down-line text-xl" />
            </div>
            <span className="text-xs bg-green-50 text-green-600 px-2 py-0.5 rounded-full">دائن</span>
          </div>
          <p className="text-xl font-bold text-gray-800">{fmt(totalCredit)}</p>
          <p className="text-sm text-gray-500 mt-1">إجمالي الدائن</p>
          <p className="text-xs text-gray-400 mt-1">ج.م</p>
        </div>

        <div className={`rounded-xl border p-4 ${isBalanced ? "bg-emerald-50 border-emerald-100" : "bg-red-50 border-red-200"}`}>
          <div className="flex items-center justify-between mb-3">
            <div className={`w-10 h-10 flex items-center justify-center rounded-lg ${isBalanced ? "bg-emerald-100 text-emerald-600" : "bg-red-100 text-red-500"}`}>
              <i className={`text-xl ${isBalanced ? "ri-shield-check-line" : "ri-error-warning-line"}`} />
            </div>
            <span className={`text-xs px-2 py-0.5 rounded-full ${isBalanced ? "bg-emerald-100 text-emerald-700" : "bg-red-100 text-red-600"}`}>
              {isBalanced ? "متوازن" : "غير متوازن"}
            </span>
          </div>
          <p className={`text-xl font-bold ${isBalanced ? "text-emerald-700" : "text-red-600"}`}>
            {isBalanced ? "القيود متوازنة" : `فرق: ${fmt(Math.abs(totalDebit - totalCredit))}`}
          </p>
          <p className={`text-sm mt-1 ${isBalanced ? "text-emerald-600" : "text-red-500"}`}>
            {isBalanced ? "مدين = دائن ✓" : "يوجد فرق في الميزان"}
          </p>
        </div>
      </div>

      <div className="flex gap-3 flex-wrap">
        {Object.entries(typeCounts).map(([type, count]) => (
          <div key={type} className={`flex items-center gap-2 px-4 py-2 rounded-lg border text-sm font-medium ${typeColors[type] || "bg-gray-50 text-gray-600"}`}>
            <span>{type}</span>
            <span className="bg-white/70 rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold">{count}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
