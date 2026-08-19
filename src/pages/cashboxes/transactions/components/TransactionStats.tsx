const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

type Props = {
  stats: {
    openingBalance: number;
    totalIncome: number;
    totalExpense: number;
    closingBalance: number;
    netPeriod: number;
  };
  todayClosingBalance?: number | null;
  openingBalanceSublabel?: string;
  periodLabel?: string;
  selectedCashboxName?: string;
};

export function TransactionStats({
  stats,
  todayClosingBalance,
  openingBalanceSublabel,
  periodLabel = "الفترة المحددة",
  selectedCashboxName = "جميع الصناديق",
}: Props) {
  const currentBalance = stats.closingBalance;
  const dayCloseBalance =
    todayClosingBalance != null && !Number.isNaN(todayClosingBalance)
      ? todayClosingBalance
      : currentBalance;
  const availableBalance = Math.max(0, currentBalance);

  const cards = [
    {
      label: "الرصيد الافتتاحي",
      sublabel: openingBalanceSublabel ?? "بداية الفترة",
      value: stats.openingBalance,
      icon: "ri-flag-line",
      color: "amber",
      bg: "bg-amber-50",
      border: "border-amber-100",
      text: "text-amber-700",
      iconBg: "bg-amber-100 text-amber-600",
    },
    {
      label: "إجمالي الإيرادات",
      sublabel: periodLabel,
      value: stats.totalIncome,
      icon: "ri-arrow-down-circle-line",
      color: "green",
      bg: "bg-green-50",
      border: "border-green-100",
      text: "text-green-700",
      iconBg: "bg-green-100 text-green-600",
      prefix: "+",
    },
    {
      label: "إجمالي المصاريف",
      sublabel: periodLabel,
      value: stats.totalExpense,
      icon: "ri-arrow-up-circle-line",
      color: "red",
      bg: "bg-red-50",
      border: "border-red-100",
      text: "text-red-600",
      iconBg: "bg-red-100 text-red-500",
      prefix: "-",
    },
    {
      label: "الرصيد الحالي",
      sublabel: "افتتاحي + إيرادات - مصاريف",
      value: currentBalance,
      icon: "ri-wallet-3-line",
      color: "sky",
      bg: currentBalance >= 0 ? "bg-sky-50" : "bg-rose-50",
      border: currentBalance >= 0 ? "border-sky-100" : "border-rose-100",
      text: currentBalance >= 0 ? "text-sky-700" : "text-rose-600",
      iconBg: currentBalance >= 0 ? "bg-sky-100 text-sky-600" : "bg-rose-100 text-rose-500",
    },
    {
      label: "الإجمالي المتاح بالخزنة",
      sublabel: "الرصيد القابل للصرف",
      value: availableBalance,
      icon: "ri-safe-line",
      color: "emerald",
      bg: "bg-emerald-50",
      border: "border-emerald-100",
      text: "text-emerald-700",
      iconBg: "bg-emerald-100 text-emerald-600",
    },
    {
      label: "رصيد إقفال اليوم",
      sublabel: "يُرحَّل تلقائياً لليوم التالي",
      value: dayCloseBalance,
      icon: "ri-moon-line",
      color: "violet",
      bg: "bg-violet-50",
      border: "border-violet-100",
      text: "text-violet-700",
      iconBg: "bg-violet-100 text-violet-600",
      isCarryForward: true,
    },
  ];

  return (
    <div className="space-y-3">
      {/* Title */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <div className="w-5 h-5 flex items-center justify-center text-gray-500">
            <i className="ri-bar-chart-grouped-line text-sm" />
          </div>
          <h3 className="text-sm font-semibold text-gray-700">
            ملخص أرصدة {selectedCashboxName}
          </h3>
        </div>
        <div className="flex items-center gap-1.5 text-xs text-gray-400">
          <i className="ri-refresh-line" />
          <span>تحديث تلقائي</span>
        </div>
      </div>

      {/* Balance Cards Grid */}
      <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
        {cards.map((card) => (
          <div
            key={card.label}
            className={`rounded-xl border p-4 ${card.bg} ${card.border} relative overflow-hidden`}
          >
            {card.isCarryForward && (
              <div className="absolute top-2 left-2">
                <span className="text-xs bg-violet-200 text-violet-700 px-1.5 py-0.5 rounded-full flex items-center gap-0.5">
                  <i className="ri-arrow-right-up-line text-xs" />
                  ترحيل
                </span>
              </div>
            )}
            <div className={`w-8 h-8 flex items-center justify-center rounded-lg mb-2 text-sm ${card.iconBg}`}>
              <i className={card.icon} />
            </div>
            <p className="text-xs text-gray-500 mb-1">{card.label}</p>
            <p className={`text-lg font-bold ${card.text} leading-tight`}>
              {card.prefix || ""}{fmt(card.value)}
              <span className="text-xs font-normal opacity-70 mr-0.5">ج.م</span>
            </p>
            <p className="text-xs text-gray-400 mt-1 truncate">{card.sublabel}</p>
          </div>
        ))}
      </div>

      {/* Day Carry-Forward Info */}
      <div className="flex items-center gap-3 bg-violet-50 border border-violet-100 rounded-lg px-4 py-2.5">
        <div className="w-6 h-6 flex items-center justify-center text-violet-500">
          <i className="ri-arrow-right-up-line" />
        </div>
        <p className="text-xs text-violet-700">
          رصيد إقفال اليوم{" "}
          <strong>{fmt(dayCloseBalance)} ج.م</strong>{" "}
          يُرحَّل تلقائياً كـ <strong>رصيد افتتاحي</strong> لليوم التالي
        </p>
        <div className="mr-auto flex items-center gap-1 text-xs text-violet-500">
          <i className="ri-check-double-line" />
          <span>تلقائي</span>
        </div>
      </div>
    </div>
  );
}
