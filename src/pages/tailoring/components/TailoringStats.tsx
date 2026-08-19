import { useMemo } from "react";
import type { TailoringOrder } from "@/pages/tailoring/tailoring.ui";

interface TailoringStatsProps {
  orders: TailoringOrder[];
}

export default function TailoringStats({ orders }: TailoringStatsProps) {
  const stats = useMemo(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const total = orders.length;
    const active = orders.filter((o) => o.status === "نشط").length;
    const completed = orders.filter((o) => o.status === "منجز").length;
    const delayed = orders.filter((o) => o.status === "متأخر").length;
    const ready = orders.filter(
      (o) => o.currentStage === "ready" || o.currentStage === "ready_for_delivery",
    ).length;
    const vip = orders.filter((o) => o.priority === "VIP").length;
    const totalValue = orders.reduce((s, o) => s + o.pricing.price, 0);
    const collected = orders.reduce((s, o) => s + o.pricing.deposit, 0);
    const pending = orders.reduce((s, o) => s + o.pricing.remaining, 0);
    const noPayment = orders.filter((o) => o.paymentStatus === "غير مدفوع").length;
    const dueThisWeek = orders.filter((o) => {
      const due = new Date(o.dueDate.replace(/\//g, "-"));
      const diff = Math.ceil((due.getTime() - today.getTime()) / 86400000);
      return diff >= 0 && diff <= 7 && o.status === "نشط";
    }).length;
    const collectionRate = totalValue > 0 ? Math.round((collected / totalValue) * 100) : 0;
    return {
      total,
      active,
      completed,
      delayed,
      ready,
      vip,
      totalValue,
      collected,
      pending,
      noPayment,
      dueThisWeek,
      collectionRate,
    };
  }, [orders]);

  return (
    <div className="mb-6 grid grid-cols-4 gap-4">
      <div className="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-sky-50">
          <i className="ri-scissors-cut-line text-xl text-sky-600" />
        </div>
        <div className="min-w-0 flex-1">
          <p className="text-xs font-medium text-slate-500">قيد التنفيذ</p>
          <p className="mt-0.5 text-2xl font-black text-slate-800">{stats.active}</p>
          <p className="mt-0.5 text-xs text-slate-400">من أصل {stats.total} أمر</p>
        </div>
        <div className="text-left">
          <div className="whitespace-nowrap rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-600">
            {stats.vip} VIP
          </div>
        </div>
      </div>

      <div className="flex items-center gap-4 rounded-xl border border-orange-200 bg-white p-4">
        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-50">
          <i className="ri-gift-2-line text-xl text-orange-600" />
        </div>
        <div className="min-w-0 flex-1">
          <p className="text-xs font-medium text-orange-600">جاهز للتسليم</p>
          <p className="mt-0.5 text-2xl font-black text-orange-700">{stats.ready}</p>
          <p className="mt-0.5 text-xs text-slate-400">تنتظر استلام العميلة</p>
        </div>
        <div className="text-left">
          <div className="whitespace-nowrap rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-600">
            {stats.dueThisWeek} هذا الأسبوع
          </div>
        </div>
      </div>

      <div
        className={`flex items-center gap-4 rounded-xl border p-4 ${
          stats.delayed > 0 ? "border-red-200 bg-red-50" : "border-slate-200 bg-white"
        }`}
      >
        <div
          className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ${
            stats.delayed > 0 ? "bg-red-100" : "bg-slate-50"
          }`}
        >
          <i
            className={`ri-alarm-warning-line text-xl ${stats.delayed > 0 ? "text-red-600" : "text-slate-400"}`}
          />
        </div>
        <div className="min-w-0 flex-1">
          <p className={`text-xs font-medium ${stats.delayed > 0 ? "text-red-600" : "text-slate-500"}`}>
            متأخرة
          </p>
          <p
            className={`mt-0.5 text-2xl font-black ${stats.delayed > 0 ? "text-red-700" : "text-slate-800"}`}
          >
            {stats.delayed}
          </p>
          <p className="mt-0.5 text-xs text-slate-400">تحتاج متابعة عاجلة</p>
        </div>
        {stats.delayed > 0 && (
          <div className="flex h-8 w-8 animate-pulse items-center justify-center rounded-full bg-red-100">
            <i className="ri-error-warning-line text-sm text-red-600" />
          </div>
        )}
      </div>

      <div className="flex items-center gap-4 rounded-xl border border-emerald-200 bg-white p-4">
        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
          <i className="ri-check-double-line text-xl text-emerald-600" />
        </div>
        <div className="min-w-0 flex-1">
          <p className="text-xs font-medium text-emerald-600">منجزة</p>
          <p className="mt-0.5 text-2xl font-black text-emerald-700">{stats.completed}</p>
          <p className="mt-0.5 text-xs text-slate-400">أوامر مكتملة</p>
        </div>
      </div>

      <div className="col-span-2 rounded-xl border border-slate-200 bg-white p-4">
        <div className="mb-3 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50">
              <i className="ri-money-dollar-circle-line text-lg text-amber-600" />
            </div>
            <span className="text-sm font-semibold text-slate-700">الإيرادات الإجمالية</span>
          </div>
          <span className="text-xl font-black text-slate-800">
            {stats.totalValue.toLocaleString("en-US")} ج.م
          </span>
        </div>
        <div className="mb-2 flex items-center gap-4">
          <div className="flex-1">
            <div className="mb-1 flex justify-between text-xs">
              <span className="text-slate-400">المحصّل</span>
              <span className="font-semibold text-emerald-600">{stats.collectionRate}%</span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
              <div
                className="h-full rounded-full bg-emerald-400 transition-all"
                style={{ width: `${stats.collectionRate}%` }}
              />
            </div>
          </div>
        </div>
        <div className="flex flex-wrap items-center justify-between gap-2 text-xs">
          <div className="flex items-center gap-1 text-emerald-600">
            <i className="ri-check-line" />
            <span>محصّل: {stats.collected.toLocaleString("en-US")} ج.م</span>
          </div>
          <div className="flex items-center gap-1 text-rose-600">
            <i className="ri-time-line" />
            <span>متبقي: {stats.pending.toLocaleString("en-US")} ج.م</span>
          </div>
          {stats.noPayment > 0 && (
            <div className="flex items-center gap-1 text-slate-400">
              <i className="ri-alert-line" />
              <span>{stats.noPayment} غير مدفوع</span>
            </div>
          )}
        </div>
      </div>

      <div className="col-span-2 rounded-xl border border-slate-200 bg-white p-4">
        <div className="mb-3 flex items-center gap-2">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50">
            <i className="ri-pie-chart-line text-lg text-sky-600" />
          </div>
          <span className="text-sm font-semibold text-slate-700">توزيع الأوامر</span>
        </div>
        <div className="flex items-center gap-3">
          <div className="flex h-3 flex-1 overflow-hidden rounded-full bg-slate-100">
            {stats.total > 0 && (
              <>
                <div
                  className="h-full bg-sky-400 transition-all"
                  style={{ width: `${(stats.active / stats.total) * 100}%` }}
                  title="نشط"
                />
                <div
                  className="h-full bg-emerald-400 transition-all"
                  style={{ width: `${(stats.completed / stats.total) * 100}%` }}
                  title="منجز"
                />
                <div
                  className="h-full bg-red-400 transition-all"
                  style={{ width: `${(stats.delayed / stats.total) * 100}%` }}
                  title="متأخر"
                />
                <div
                  className="h-full bg-orange-400 transition-all"
                  style={{ width: `${(stats.ready / stats.total) * 100}%` }}
                  title="جاهز"
                />
              </>
            )}
          </div>
        </div>
        <div className="mt-2 flex flex-wrap items-center gap-4">
          {[
            { label: "نشط", count: stats.active, color: "bg-sky-400" },
            { label: "منجز", count: stats.completed, color: "bg-emerald-400" },
            { label: "جاهز", count: stats.ready, color: "bg-orange-400" },
            { label: "متأخر", count: stats.delayed, color: "bg-red-400" },
          ].map((item) => (
            <div key={item.label} className="flex items-center gap-1.5">
              <div className={`h-2.5 w-2.5 rounded-full ${item.color}`} />
              <span className="text-xs text-slate-500">
                {item.label} ({item.count})
              </span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
