type DashboardHeaderProps = {
  periodLabel: string;
  onToggleFilters: () => void;
  onNavigateReports: () => void;
  onNavigateCashboxes: () => void;
};

const today = new Date().toLocaleDateString("ar-EG", {
  weekday: "long",
  year: "numeric",
  month: "long",
  day: "numeric",
});

export function DashboardHeader({
  periodLabel,
  onToggleFilters,
  onNavigateReports,
  onNavigateCashboxes,
}: DashboardHeaderProps) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1
          className="text-[20px] font-black leading-tight"
          style={{ color: "var(--color-text-primary)" }}
        >
          مرحباً،&nbsp;
          <span style={{ color: "var(--color-accent)" }}>المدير العام</span>
          &nbsp;👋
        </h1>
        <p
          className="text-[12.5px] mt-0.5 font-medium"
          style={{ color: "var(--color-text-muted)" }}
        >
          {today} · {periodLabel}
        </p>
      </div>
      <div className="flex items-center gap-2 flex-wrap">
        <button
          onClick={onToggleFilters}
          className="flex items-center gap-1.5 px-3.5 py-2 text-[13px] font-bold rounded-xl border cursor-pointer transition-colors whitespace-nowrap"
          style={{
            background: "white",
            borderColor: "var(--color-border)",
            color: "var(--color-text-secondary)",
          }}
          onMouseEnter={(e) => {
            (e.currentTarget as HTMLElement).style.background = "#F4F7FB";
          }}
          onMouseLeave={(e) => {
            (e.currentTarget as HTMLElement).style.background = "white";
          }}
        >
          <i className="ri-filter-3-line text-[14px]" />
          الفلاتر
        </button>
        <button
          onClick={onNavigateReports}
          className="flex items-center gap-1.5 px-3.5 py-2 text-[13px] font-bold rounded-xl border cursor-pointer transition-colors whitespace-nowrap"
          style={{
            background: "white",
            borderColor: "var(--color-border)",
            color: "var(--color-text-secondary)",
          }}
          onMouseEnter={(e) => {
            (e.currentTarget as HTMLElement).style.background = "#F4F7FB";
          }}
          onMouseLeave={(e) => {
            (e.currentTarget as HTMLElement).style.background = "white";
          }}
        >
          <i className="ri-line-chart-line text-[14px]" />
          التقارير
        </button>
        <button
          onClick={onNavigateCashboxes}
          className="flex items-center gap-1.5 px-3.5 py-2 text-[13px] font-bold rounded-xl cursor-pointer transition-all whitespace-nowrap btn-solid"
        >
          <i className="ri-safe-line text-[14px]" />
          الخزنة
        </button>
      </div>
    </div>
  );
}
