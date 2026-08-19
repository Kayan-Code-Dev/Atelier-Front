type StatItem = {
  label: string;
  value: string | number;
  sub: string;
  icon: string;
  color: string;
  bg: string;
};

type Props = {
  stats: StatItem[];
};

export function ExpenseStats({ stats }: Props) {
  return (
    <div className="grid grid-cols-4 gap-4">
      {stats.map((s) => (
        <div key={s.label} className="stat-card flex items-center gap-4">
          <div
            className="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
            style={{ background: s.bg }}
          >
            <i className={`${s.icon} text-xl`} style={{ color: s.color }} />
          </div>
          <div className="min-w-0">
            <p className="text-xl font-bold text-slate-800 truncate">{s.value}</p>
            <p className="text-xs text-slate-400 mt-0.5">{s.label}</p>
            <p className="text-xs font-medium mt-0.5" style={{ color: s.color }}>
              {s.sub}
            </p>
          </div>
        </div>
      ))}
    </div>
  );
}
