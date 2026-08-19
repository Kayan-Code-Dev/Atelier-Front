import { useQuery } from "@tanstack/react-query";
import { useGetCashboxesQueryOptions } from "@/api/v2/cashboxes/cashboxes.hooks";
import type { TCashbox } from "@/api/v2/cashboxes/cashboxes.types";

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n);

const CASHBOX_COLORS = [
  { active: "border-emerald-400 bg-emerald-50 text-emerald-700", hover: "hover:border-emerald-200 hover:bg-emerald-50/50", dot: "bg-emerald-400" },
  { active: "border-amber-400 bg-amber-50 text-amber-700", hover: "hover:border-amber-200 hover:bg-amber-50/50", dot: "bg-amber-400" },
  { active: "border-sky-400 bg-sky-50 text-sky-700", hover: "hover:border-sky-200 hover:bg-sky-50/50", dot: "bg-sky-400" },
  { active: "border-violet-400 bg-violet-50 text-violet-700", hover: "hover:border-violet-200 hover:bg-violet-50/50", dot: "bg-violet-400" },
  { active: "border-orange-400 bg-orange-50 text-orange-700", hover: "hover:border-orange-200 hover:bg-orange-50/50", dot: "bg-orange-400" },
  { active: "border-rose-400 bg-rose-50 text-rose-700", hover: "hover:border-rose-200 hover:bg-rose-50/50", dot: "bg-rose-400" },
];

type Props = {
  selectedCashboxId: string;
  onCashboxChange: (cashboxId: string) => void;
};

export function CashboxSelector({
  selectedCashboxId,
  onCashboxChange,
}: Props) {
  const { data: cashboxesData, isPending } = useQuery(
    useGetCashboxesQueryOptions({ per_page: 100 })
  );
  const cashboxes: TCashbox[] = cashboxesData?.data ?? [];

  const getColor = (index: number) => CASHBOX_COLORS[index % CASHBOX_COLORS.length];

  return (
    <div className="bg-white rounded-xl border border-gray-100 p-4">
      <div className="flex items-center gap-2 mb-3">
        <div className="w-5 h-5 flex items-center justify-center text-gray-500">
          <i className="ri-safe-2-line text-sm" />
        </div>
        <h3 className="text-sm font-semibold text-gray-700">عرض حسب الصندوق</h3>
        <span className="text-xs text-gray-400 mr-auto">
          اختر الصندوق لعرض أرصدته وحركاته
        </span>
      </div>

      {isPending ? (
        <div className="flex items-center justify-center py-8 text-gray-400">
          <i className="ri-loader-4-line animate-spin text-2xl" />
        </div>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
          
          <button
            type="button"
            onClick={() => onCashboxChange("")}
            className={`rounded-xl border-2 p-3 text-right transition-all cursor-pointer ${
              !selectedCashboxId
                ? "border-emerald-400 bg-emerald-50 text-emerald-700"
                : "border-gray-100 bg-white text-gray-600 hover:border-gray-200 hover:bg-gray-50/50"
            }`}
          >
            <div className="flex items-center justify-between mb-2">
              <div
                className={`w-6 h-6 flex items-center justify-center rounded-full text-sm ${
                  !selectedCashboxId ? "" : "text-gray-400"
                }`}
              >
                <i className="ri-store-2-line" />
              </div>
              {!selectedCashboxId && <div className="w-2 h-2 rounded-full bg-emerald-400" />}
            </div>
            <p className="text-xs font-semibold mb-1 whitespace-nowrap overflow-hidden text-ellipsis">
              الكل
            </p>
            <p className={`text-xs font-bold ${!selectedCashboxId ? "" : "text-slate-500"}`}>
              —
              <span className="font-normal text-xs mr-0.5 opacity-70">ج.م</span>
            </p>
            <p className="text-xs opacity-60 mt-0.5">جميع الصناديق</p>
          </button>

          
          {cashboxes.map((cb, idx) => {
            const balance =
              typeof cb.current_balance === "number"
                ? cb.current_balance
                : Number(cb.current_balance) || 0;
            const isActive = selectedCashboxId === String(cb.id);
            const color = getColor(idx);

            return (
              <button
                key={cb.id}
                type="button"
                onClick={() => onCashboxChange(String(cb.id))}
                className={`rounded-xl border-2 p-3 text-right transition-all cursor-pointer ${
                  isActive ? color.active : `border-gray-100 bg-white text-gray-600 ${color.hover}`
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <div
                    className={`w-6 h-6 flex items-center justify-center rounded-full text-sm ${
                      isActive ? "" : "text-gray-400"
                    }`}
                  >
                    <i className="ri-banknote-line" />
                  </div>
                  {isActive && <div className={`w-2 h-2 rounded-full ${color.dot}`} />}
                </div>
                <p className="text-xs font-semibold mb-1 whitespace-nowrap overflow-hidden text-ellipsis">
                  {cb.name}
                </p>
                <p className={`text-xs font-bold ${isActive ? "" : "text-gray-500"}`}>
                  {fmt(balance)}
                  <span className="font-normal text-xs mr-0.5 opacity-70">ج.م</span>
                </p>
                <p className="text-xs opacity-60 mt-0.5 truncate">
                  {cb.branch?.name ?? "—"}
                </p>
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
}
