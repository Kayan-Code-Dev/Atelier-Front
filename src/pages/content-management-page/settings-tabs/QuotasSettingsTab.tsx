import { useQuery } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { getSubscriptionUsage, type QuotaItem } from "@/api/v2/subscription/subscription.service";

function barColor(item: QuotaItem): string {
  if (item.unlimited) return "#22C55E";
  if (item.percent >= 90 || item.exhausted) return "#EF4444";
  if (item.percent >= 70) return "#F59E0B";
  return "#0EA5E9";
}

function formatLimit(item: QuotaItem): string {
  if (item.unlimited) return "غير محدود";
  return String(item.limit);
}

function QuotaCard({ item }: { item: QuotaItem }) {
  const color = barColor(item);
  const width = item.unlimited ? Math.min(item.used > 0 ? 12 : 0, 100) : item.percent;

  return (
    <div
      className="rounded-2xl p-4 border bg-white"
      style={{
        borderColor: item.exhausted ? "#FECACA" : "#EEF2F8",
        background: item.exhausted ? "#FEF2F2" : "#FFFFFF",
      }}
    >
      <div className="flex items-start justify-between gap-3 mb-3">
        <div>
          <p className="text-sm font-black text-slate-800">{item.label}</p>
          <p className="text-[11px] text-slate-400 mt-0.5">
            {item.period === "monthly" ? "كوتة شهرية" : "حد إجمالي"}
          </p>
        </div>
        {item.exhausted ? (
          <span className="text-[10px] font-bold px-2 py-1 rounded-full bg-rose-100 text-rose-600">
            مكتملة
          </span>
        ) : null}
      </div>

      <p className="text-2xl font-black text-slate-900 leading-none">
        {item.used}
        <span className="text-sm font-bold text-slate-400 mr-1">
          / {formatLimit(item)}
        </span>
      </p>
      <p className="text-xs text-slate-500 mt-2">
        {item.unlimited
          ? `${item.used} ${item.unit} مستهلك`
          : `متبقي ${item.remaining ?? 0} ${item.unit}`}
      </p>

      <div className="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
        <div
          className="h-full rounded-full transition-all duration-500"
          style={{ width: `${width}%`, background: color }}
        />
      </div>
      {!item.unlimited ? (
        <p className="text-[10px] text-slate-400 mt-1.5">{item.percent}% مستهلك</p>
      ) : null}
    </div>
  );
}

export default function QuotasSettingsTab() {
  const { data, isPending, isError, error, refetch, isFetching } = useQuery({
    queryKey: ["subscription-usage"],
    queryFn: getSubscriptionUsage,
    staleTime: 30_000,
  });

  if (isPending) {
    return (
      <div className="flex min-h-[30vh] items-center justify-center gap-2 text-slate-400">
        <Loader2 className="h-6 w-6 animate-spin" />
        <span className="text-sm">جاري تحميل الكوتة...</span>
      </div>
    );
  }

  if (isError) {
    return (
      <div className="rounded-2xl border border-rose-100 bg-rose-50 p-5 text-sm text-rose-700">
        <p className="font-bold mb-2">تعذر تحميل بيانات الكوتة</p>
        <p className="text-xs mb-3 opacity-80">
          {(error as Error)?.message || "حدث خطأ غير متوقع"}
        </p>
        <button
          type="button"
          onClick={() => void refetch()}
          className="px-4 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold"
        >
          إعادة المحاولة
        </button>
      </div>
    );
  }

  const items = data?.items ?? [];
  const invoices = items.filter((i) => i.group === "invoices");
  const chat = items.filter((i) => i.group === "chat");
  const assistant = items.filter((i) => i.group === "assistant");
  const capacity = items.filter((i) => i.group === "capacity");

  return (
    <div className="space-y-5">
      <div
        className="rounded-2xl p-5 flex flex-wrap items-center justify-between gap-3"
        style={{
          background: "linear-gradient(135deg, #0C1A3E 0%, #1E3A7B 100%)",
        }}
      >
        <div>
          <p className="text-white font-black text-lg">الاستهلاك والكوتة</p>
          <p className="text-white/60 text-xs mt-1">
            فترة القياس: {data?.period?.label || data?.period?.month}
            {data?.period?.from && data?.period?.to
              ? ` · من ${data.period.from} إلى ${data.period.to}`
              : ""}
          </p>
        </div>
        <button
          type="button"
          onClick={() => void refetch()}
          disabled={isFetching}
          className="px-4 py-2 rounded-xl text-xs font-bold bg-white/10 text-white border border-white/20"
        >
          {isFetching ? "جارٍ التحديث..." : "تحديث الآن"}
        </button>
      </div>

      <section className="space-y-3">
        <h3 className="text-sm font-black text-slate-800 flex items-center gap-2">
          <i className="ri-file-list-3-line text-emerald-600" />
          كوتة الفواتير هذا الشهر
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
          {invoices.map((item) => (
            <QuotaCard key={item.key} item={item} />
          ))}
        </div>
      </section>

      <section className="space-y-3">
        <h3 className="text-sm font-black text-slate-800 flex items-center gap-2">
          <i className="ri-chat-smile-3-line text-indigo-600" />
          كوتة الشات الذكي هذا الشهر
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {chat.map((item) => (
            <QuotaCard key={item.key} item={item} />
          ))}
        </div>
      </section>

      {assistant.length > 0 ? (
      <section className="space-y-3">
        <h3 className="text-sm font-black text-slate-800 flex items-center gap-2">
          <i className="ri-robot-2-line text-teal-600" />
          كوتة المساعد الذكي هذا الشهر
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {assistant.map((item) => (
            <QuotaCard key={item.key} item={item} />
          ))}
        </div>
      </section>
      ) : null}

      <section className="space-y-3">
        <h3 className="text-sm font-black text-slate-800 flex items-center gap-2">
          <i className="ri-building-line text-amber-600" />
          حدود الطاقة الاستيعابية
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {capacity.map((item) => (
            <QuotaCard key={item.key} item={item} />
          ))}
        </div>
      </section>
    </div>
  );
}
