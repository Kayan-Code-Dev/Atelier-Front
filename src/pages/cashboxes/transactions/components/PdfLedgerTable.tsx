import type { TTransaction } from "@/api/v2/transactions/transactions.types";
import { getCategoryLabel } from "../hooks/useCashboxTransactionsPage";

type Props = {
  items: TTransaction[];
  openingBalance: number;
  periodIncome: number;
  periodExpenses: number;
  currentBalance: number;
};

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

const formatDateShort = (s: string) =>
  new Date(s).toLocaleDateString("ar-EG", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });


export function PdfLedgerTable({
  items,
  openingBalance,
  periodIncome,
  periodExpenses,
  currentBalance,
}: Props) {
  const sortedEntries = [...items].sort(
    (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
  );

  return (
    <div className="bg-white p-6">
      <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
        دفتر الأستاذ — كشف الحركات المالية
      </div>
      <table className="w-full text-xs border-collapse">
        <thead>
          <tr className="bg-slate-800 text-white">
            <th className="text-right py-2.5 px-3 font-semibold">التاريخ</th>
            <th className="text-right py-2.5 px-3 font-semibold">رقم المرجع</th>
            <th className="text-right py-2.5 px-3 font-semibold">البيان</th>
            <th className="text-right py-2.5 px-3 font-semibold">الفئة</th>
            <th className="text-right py-2.5 px-3 font-semibold">الصندوق</th>
            <th className="text-right py-2.5 px-3 font-semibold">المستخدم</th>
            <th className="text-center py-2.5 px-3 font-semibold text-green-300">دائن</th>
            <th className="text-center py-2.5 px-3 font-semibold text-red-300">مدين</th>
            <th className="text-center py-2.5 px-3 font-semibold text-blue-200">الرصيد</th>
          </tr>
        </thead>
        <tbody>
          <tr className="bg-amber-50 border-b border-amber-100">
            <td colSpan={7} className="px-3 py-2 text-amber-700 font-semibold text-xs">
              رصيد أول المدة (افتتاحي)
            </td>
            <td className="px-3 py-2 text-center" />
            <td className="px-3 py-2 text-center font-bold text-amber-700">
              {fmt(openingBalance)}
            </td>
          </tr>
          {sortedEntries.map((entry, idx) => {
            const isIncome = entry.type === "income";
            const isReversal = entry.type === "reversal";
            const amount =
              typeof entry.amount === "number"
                ? entry.amount
                : Number(entry.amount) || 0;
            const balRaw =
              entry.cashbox_balance_after != null
                ? entry.cashbox_balance_after
                : entry.balance_after;
            const bal =
              typeof balRaw === "number" ? balRaw : Number(balRaw) || 0;
            return (
              <tr
                key={entry.id}
                className={`border-b border-gray-50 ${
                  idx % 2 === 0 ? "bg-white" : "bg-slate-50/40"
                }`}
              >
                <td className="px-3 py-2.5 text-slate-500 whitespace-nowrap">
                  {formatDateShort(entry.created_at)}
                </td>
                <td className="px-3 py-2.5">
                  <span
                    className={`rounded px-1.5 py-0.5 font-mono text-xs ${
                      isReversal
                        ? "bg-amber-50 text-amber-800"
                        : isIncome
                          ? "bg-green-50 text-green-700"
                          : "bg-red-50 text-red-600"
                    }`}
                  >
                    #{entry.id}
                  </span>
                </td>
                <td className="max-w-[140px] px-3 py-2.5 text-slate-700">
                  <p className="truncate">
                    {entry.description || getCategoryLabel(entry.category)}
                  </p>
                </td>
                <td className="px-3 py-2.5">
                  <span className="whitespace-nowrap rounded-full bg-slate-100 px-1.5 py-0.5 text-slate-600">
                    {getCategoryLabel(entry.category)}
                  </span>
                </td>
                <td className="whitespace-nowrap px-3 py-2.5 text-slate-500">
                  {entry.cashbox?.name ?? "—"}
                </td>
                <td className="whitespace-nowrap px-3 py-2.5 text-slate-600">
                  {entry.creator?.name ?? "—"}
                </td>
                <td className="px-3 py-2.5 text-center">
                  {isReversal ? (
                    amount >= 0 ? (
                      <span className="font-semibold text-emerald-600">{fmt(amount)}</span>
                    ) : (
                      <span className="text-slate-300">—</span>
                    )
                  ) : isIncome ? (
                    <span className="font-semibold text-green-600">{fmt(amount)}</span>
                  ) : (
                    <span className="text-slate-300">—</span>
                  )}
                </td>
                <td className="px-3 py-2.5 text-center">
                  {isReversal ? (
                    amount < 0 ? (
                      <span className="font-semibold text-red-500">{fmt(Math.abs(amount))}</span>
                    ) : (
                      <span className="text-slate-300">—</span>
                    )
                  ) : !isIncome ? (
                    <span className="font-semibold text-red-500">{fmt(amount)}</span>
                  ) : (
                    <span className="text-slate-300">—</span>
                  )}
                </td>
                <td className="px-3 py-2.5 text-center font-bold">{fmt(bal)}</td>
              </tr>
            );
          })}
          <tr className="bg-slate-800 text-white font-bold text-xs">
            <td colSpan={6} className="px-3 py-3 text-right">
              الإجماليات — {items.length} قيد
            </td>
            <td className="px-3 py-3 text-center text-green-300">
              {fmt(periodIncome)}
            </td>
            <td className="px-3 py-3 text-center text-red-300">
              {fmt(periodExpenses)}
            </td>
            <td className="px-3 py-3 text-center text-green-300">
              {fmt(currentBalance)}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  );
}
