import type { DeliverySearchRow, DeliverySearchStatus } from "../deliverySearch.types";

interface DeliverySearchStatsProps {
  records: DeliverySearchRow[];
  onStatusFilter: (status: DeliverySearchStatus | null) => void;
  activeStatus: DeliverySearchStatus | null;
}

interface StatCard {
  label: string;
  status: DeliverySearchStatus | null;
  color: string;
  bg: string;
  border: string;
  icon: string;
  ring: string;
  urgent?: boolean;
}

const statCards: StatCard[] = [
  {
    label: "إجمالي الفواتير",
    status: null,
    color: "text-slate-700",
    bg: "bg-white",
    border: "border-slate-200",
    icon: "ri-file-list-3-line",
    ring: "ring-slate-400",
  },
  {
    label: "ينتظر التسليم",
    status: "ينتظر التسليم",
    color: "text-amber-700",
    bg: "bg-amber-50",
    border: "border-amber-200",
    icon: "ri-time-line",
    ring: "ring-amber-500",
  },
  {
    label: "تأخر التسليم",
    status: "تأخر التسليم",
    color: "text-red-700",
    bg: "bg-red-50",
    border: "border-red-200",
    icon: "ri-alarm-warning-line",
    ring: "ring-red-500",
    urgent: true,
  },
  {
    label: "جاهز للاستلام",
    status: "جاهز للاستلام",
    color: "text-emerald-700",
    bg: "bg-emerald-50",
    border: "border-emerald-200",
    icon: "ri-gift-line",
    ring: "ring-emerald-500",
  },
  {
    label: "ينتظر الإرجاع",
    status: "ينتظر الإرجاع",
    color: "text-sky-700",
    bg: "bg-sky-50",
    border: "border-sky-200",
    icon: "ri-arrow-go-back-line",
    ring: "ring-sky-500",
  },
  {
    label: "تأخر الإرجاع",
    status: "تأخر الإرجاع",
    color: "text-orange-700",
    bg: "bg-orange-50",
    border: "border-orange-200",
    icon: "ri-error-warning-line",
    ring: "ring-orange-500",
    urgent: true,
  },
];

export function DeliverySearchStats({
  records,
  onStatusFilter,
  activeStatus,
}: DeliverySearchStatsProps) {
  const getCount = (status: DeliverySearchStatus | null) => {
    if (!status) return records.length;
    return records.filter((r) => r.deliveryStatus === status).length;
  };

  const urgentCount = records.filter(
    (r) => r.deliveryStatus === "تأخر التسليم" || r.deliveryStatus === "تأخر الإرجاع"
  ).length;

  return (
    <div className="space-y-3">
      {urgentCount > 0 && (
        <div className="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-red-50 border border-red-200">
          <span className="w-5 h-5 flex items-center justify-center shrink-0">
            <i className="ri-alarm-warning-fill text-red-600 text-base" />
          </span>
          <p className="text-sm font-semibold text-red-700">
            تنبيه: يوجد <span className="font-black">{urgentCount}</span> فاتورة
            {urgentCount > 1 ? " " : " "}متأخرة تحتاج إجراءً عاجلاً
          </p>
        </div>
      )}

      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        {statCards.map((card) => {
          const count = getCount(card.status);
          const isActive = activeStatus === card.status;
          return (
            <button
              key={card.label}
              type="button"
              onClick={() => onStatusFilter(isActive ? null : card.status)}
              className={[
                "flex flex-col items-start gap-2 p-4 rounded-xl border cursor-pointer transition-all duration-150 text-right",
                isActive
                  ? `${card.bg} ${card.border} ring-2 ring-offset-1 ${card.ring}`
                  : "bg-white border-slate-200 hover:bg-slate-50/80",
              ].join(" ")}
            >
              <div className="flex items-center justify-between w-full">
                <span
                  className={`w-8 h-8 rounded-lg flex items-center justify-center ${card.bg} ${card.border} border`}
                >
                  <i className={`${card.icon} text-base ${card.color}`} />
                </span>
                {card.urgent && count > 0 && (
                  <span className="w-2 h-2 rounded-full bg-red-500 animate-pulse" />
                )}
              </div>
              <div>
                <p className={`text-2xl font-black ${card.color}`}>{count}</p>
                <p className="text-xs text-slate-500 mt-0.5 leading-tight">{card.label}</p>
              </div>
            </button>
          );
        })}
      </div>
    </div>
  );
}
