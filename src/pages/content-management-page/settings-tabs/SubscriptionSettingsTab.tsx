import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { useNavigate } from "react-router";
import {
  getSubscriptionOverview,
  type QuotaItem,
} from "@/api/v2/subscription/subscription.service";
import { recordTrialCommercialSignal } from "@/api/v2/trial-onboarding/trialOnboarding.service";

function statusMeta(status?: string) {
  switch (status) {
    case "expired":
      return { label: "منتهي", color: "#EF4444", bg: "#FEF2F2" };
    case "cancelled":
      return { label: "ملغي", color: "#64748B", bg: "#F1F5F9" };
    default:
      return { label: "نشط", color: "#22C55E", bg: "#F0FDF4" };
  }
}

function MiniQuota({ item }: { item: QuotaItem }) {
  const pct = item.unlimited ? 0 : item.percent;
  const color =
    item.exhausted || pct >= 90 ? "#EF4444" : pct >= 70 ? "#F59E0B" : "#0EA5E9";

  return (
    <div className="rounded-xl p-3 bg-slate-50 border border-slate-100">
      <p className="text-[11px] font-bold text-slate-500 mb-1">{item.label}</p>
      <p className="text-sm font-black text-slate-800">
        {item.used}
        <span className="text-slate-400 font-normal text-xs">
          {" "}
          / {item.unlimited ? "∞" : item.limit}
        </span>
      </p>
      <div className="mt-2 h-1.5 rounded-full bg-slate-200 overflow-hidden">
        <div
          className="h-full rounded-full"
          style={{
            width: `${item.unlimited ? Math.min(item.used, 8) : pct}%`,
            background: color,
          }}
        />
      </div>
    </div>
  );
}

export default function SubscriptionSettingsTab() {
  const navigate = useNavigate();
  const [section, setSection] = useState<"overview" | "plans">("overview");
  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ["subscription-overview"],
    queryFn: getSubscriptionOverview,
    staleTime: 30_000,
  });

  const sub = data?.subscription;
  const status = statusMeta(sub?.lifecycle_status);
  const days = sub?.days_remaining ?? null;
  const usageItems = data?.usage?.items ?? [];
  const highlight = useMemo(
    () =>
      usageItems.filter((i) =>
        ["ai_chat", "invoices_sale", "invoices_rent", "invoices_tailoring"].includes(
          i.key
        )
      ),
    [usageItems]
  );

  if (isPending) {
    return (
      <div className="flex min-h-[30vh] items-center justify-center gap-2 text-slate-400">
        <Loader2 className="h-6 w-6 animate-spin" />
        <span className="text-sm">جاري تحميل الاشتراك...</span>
      </div>
    );
  }

  if (isError) {
    return (
      <div className="rounded-2xl border border-amber-100 bg-amber-50 p-5 text-sm text-amber-900 space-y-3">
        <p className="font-black">تعذر جلب بيانات الاشتراك من الخادم</p>
        <p className="text-xs opacity-80">{(error as Error)?.message}</p>
        <button
          type="button"
          onClick={() => void refetch()}
          className="px-4 py-2 rounded-xl bg-amber-600 text-white text-xs font-bold"
        >
          إعادة المحاولة
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      <div
        className="rounded-2xl p-6 relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, #0C1A3E 0%, #1E3A7B 100%)" }}
      >
        <div className="relative flex flex-wrap items-start justify-between gap-4">
          <div>
            <div className="flex items-center gap-2 mb-2">
              <span className="text-white font-black text-xl">
                {sub?.plan_name || "بدون باقة"}
              </span>
              <span
                className="text-[10px] px-2.5 py-1 rounded-full font-bold"
                style={{ color: status.color, background: `${status.color}22` }}
              >
                {status.label}
              </span>
              {sub?.is_demo ? (
                <span className="text-[10px] px-2.5 py-1 rounded-full font-bold bg-white/10 text-white/80">
                  تجريبي
                </span>
              ) : null}
            </div>
            <p className="text-white/55 text-xs">
              {typeof sub?.price === "number"
                ? `${sub.price} ${sub.currency_symbol || ""} · ${sub.billing_cycle || ""}`
                : null}
              {sub?.expires_at ? ` · ينتهي ${sub.expires_at}` : ""}
            </p>
          </div>
          <div className="text-center">
            <p className="text-4xl font-black text-emerald-300">{days ?? "—"}</p>
            <p className="text-white/50 text-xs mt-0.5">يوم متبقي</p>
          </div>
        </div>
      </div>

      <div
        className="flex items-center gap-1 p-1 rounded-xl bg-white border border-slate-100"
      >
        {(
          [
            { id: "overview", label: "نظرة عامة", icon: "ri-dashboard-3-line" },
            { id: "plans", label: "الباقات المتاحة", icon: "ri-exchange-line" },
          ] as const
        ).map((s) => (
          <button
            key={s.id}
            type="button"
            onClick={() => {
              setSection(s.id);
              if (s.id === "plans") {
                void recordTrialCommercialSignal("upgrade_clicked");
              }
            }}
            className="flex items-center gap-2 flex-1 justify-center py-2.5 rounded-lg text-sm font-bold"
            style={
              section === s.id
                ? { background: "#0C1A3E", color: "#fff" }
                : { color: "#64748B" }
            }
          >
            <i className={s.icon} />
            {s.label}
          </button>
        ))}
      </div>

      {section === "overview" ? (
        <div className="space-y-4">
          <div className="rounded-2xl p-5 bg-white border border-slate-100">
            <div className="flex items-center justify-between gap-3 mb-4">
              <h4 className="font-black text-slate-800 text-sm flex items-center gap-2">
                <i className="ri-pie-chart-2-line text-sky-500" />
                كوتة هذا الشهر (مختصر)
              </h4>
              <button
                type="button"
                onClick={() => navigate("/content/quotas")}
                className="text-xs font-bold text-indigo-600"
              >
                عرض التفاصيل
              </button>
            </div>
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
              {highlight.map((item) => (
                <MiniQuota key={item.key} item={item} />
              ))}
            </div>
          </div>

          <div className="rounded-2xl p-5 bg-white border border-slate-100">
            <h4 className="font-black text-slate-800 text-sm mb-3">ميزات الباقة المفعّلة</h4>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
              {Object.entries(sub?.features ?? {})
                .filter(([, v]) => ["1", "true", "yes", "enabled"].includes(String(v).toLowerCase()))
                .slice(0, 12)
                .map(([key]) => (
                  <div
                    key={key}
                    className="flex items-center gap-2 p-3 rounded-xl bg-slate-50 text-xs text-slate-600"
                  >
                    <i className="ri-check-line text-emerald-500" />
                    {key}
                  </div>
                ))}
              {!Object.keys(sub?.features ?? {}).length ? (
                <p className="text-xs text-slate-400">لا توجد ميزات معروضة.</p>
              ) : null}
            </div>
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          {(data?.available_plans ?? []).map((plan) => (
            <div
              key={plan.code || plan.id || plan.name}
              className="rounded-2xl p-5 border bg-white"
              style={{
                borderColor: plan.is_current ? "#6366F1" : "#E2E8F0",
                boxShadow: plan.is_current ? "0 0 0 2px #6366F122" : undefined,
              }}
            >
              <div className="flex items-center justify-between mb-3">
                <h3 className="font-black text-slate-800">{plan.name}</h3>
                {plan.is_current ? (
                  <span className="text-[10px] font-bold px-2 py-1 rounded-full bg-indigo-50 text-indigo-600">
                    الحالية
                  </span>
                ) : null}
              </div>
              <p className="text-2xl font-black text-slate-900 mb-3">
                {plan.price === 0
                  ? "مجاناً"
                  : `${plan.price} ${plan.currency_symbol || ""}`}
              </p>
              <ul className="space-y-1.5">
                {(plan.features ?? []).slice(0, 6).map((f) => (
                  <li key={f} className="text-xs text-slate-600 flex items-center gap-1.5">
                    <i className="ri-check-line text-emerald-500" />
                    {f}
                  </li>
                ))}
              </ul>
            </div>
          ))}
          {!(data?.available_plans ?? []).length ? (
            <p className="text-sm text-slate-400">لا توجد باقات متاحة للعرض حالياً.</p>
          ) : null}
        </div>
      )}
    </div>
  );
}
