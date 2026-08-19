import { useState, useMemo } from "react";
import { useNavigate } from "react-router";
import {
  usePatchTailoringOrderStatusMutation,
  useTailoringOrdersQuery,
  useTailoringWorkflowStatusesQuery,
} from "@/api/v2/tailoring-orders/tailoringOrders.hooks";
import { priorityColors, paymentColors, type TailoringOrder } from "@/pages/tailoring/tailoring.ui";
import { buildWorkflowStageDefs, mapTailoringResourceToOrder } from "./tailoringOrder.adapter";

const statusBadge: Record<string, string> = {
  "نشط": "bg-sky-50 text-sky-700 border border-sky-200",
  "منجز": "bg-emerald-50 text-emerald-700 border border-emerald-200",
  "متأخر": "bg-red-50 text-red-700 border border-red-200",
  "ملغي": "bg-slate-100 text-slate-500 border border-slate-200",
};

export default function TailoringDeliveriesPage() {
  const navigate = useNavigate();
  const patchStatus = usePatchTailoringOrderStatusMutation();

  const { data: workflow } = useTailoringWorkflowStatusesQuery();
  const stageDefs = useMemo(() => buildWorkflowStageDefs(workflow), [workflow]);
  const stageMap = useMemo(() => {
    const m: Record<string, { label: string; bg: string; text: string; border: string }> = {};
    stageDefs.forEach((s) => {
      m[s.key] = { label: s.label, bg: s.bg, text: s.text, border: s.border };
    });
    return m;
  }, [stageDefs]);

  const { data: listRes, isLoading } = useTailoringOrdersQuery({
    page: 1,
    per_page: 100,
    sort_by: "delivery_date",
    sort_dir: "asc",
  });

  const sourceOrders = useMemo(() => {
    const rows = listRes?.data ?? [];
    return rows
      .map(mapTailoringResourceToOrder)
      .filter(
        (o) => o.currentStage === "ready_for_delivery" || o.currentStage === "delivered",
      );
  }, [listRes]);

  const [search, setSearch] = useState("");
  const [stageFilter, setStageFilter] = useState("الكل");
  const [statusFilter, setStatusFilter] = useState("الكل");
  const [deliverConfirm, setDeliverConfirm] = useState<TailoringOrder | null>(null);

  const statusOptions = ["الكل", "نشط", "منجز", "متأخر", "ملغي"];

  const filtered = useMemo(
    () =>
      sourceOrders.filter((o) => {
        const matchSearch =
          !search ||
          o.customer.name.includes(search) ||
          o.customer.phone.includes(search) ||
          o.orderNumber.includes(search) ||
          o.garmentType.includes(search) ||
          o.tailorName.includes(search);
        const matchStage = stageFilter === "الكل" || o.currentStage === stageFilter;
        const matchStatus = statusFilter === "الكل" || o.status === statusFilter;
        return matchSearch && matchStage && matchStatus;
      }),
    [sourceOrders, search, stageFilter, statusFilter],
  );

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const getDaysDiff = (dateStr: string) => {
    const d = new Date(dateStr.replace(/\//g, "-"));
    return Math.ceil((d.getTime() - today.getTime()) / 86400000);
  };

  const handleMarkDelivered = (order: TailoringOrder) => {
    setDeliverConfirm(order);
  };

  const confirmDeliver = async () => {
    if (!deliverConfirm) return;
    const idNum = Number(deliverConfirm.id);
    if (!Number.isNaN(idNum) && /^\d+$/.test(deliverConfirm.id)) {
      if (deliverConfirm.pricing.remaining > 0) {
        setDeliverConfirm(null);
        return;
      }
      try {
        await patchStatus.mutateAsync({ id: idNum, body: { status: "delivered" } });
      } catch {
        /* handled */
      }
      setDeliverConfirm(null);
      return;
    }
    setDeliverConfirm(null);
  };

  const readyCount = sourceOrders.filter((o) => o.currentStage === "ready_for_delivery").length;
  const pendingDeliveryCount = sourceOrders.filter(
    (o) => o.currentStage !== "delivered" && o.status !== "ملغي",
  ).length;
  const deliveredCount = sourceOrders.filter((o) => o.currentStage === "delivered").length;
  const lateCount = sourceOrders.filter((o) => {
    const diff = getDaysDiff(o.dueDate);
    return diff < 0 && o.status === "نشط";
  }).length;

  return (
    <div className="p-6" dir="rtl">
      {isLoading && (
        <div className="mb-4 flex items-center justify-center rounded-xl border border-slate-200 bg-white py-10 text-slate-500">
          <i className="ri-loader-4-line ml-2 animate-spin text-xl" /> جاري التحميل...
        </div>
      )}
      <div className="mb-6 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate("/tailoring/orders")}
            className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-md border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50"
          >
            <i className="ri-arrow-right-line" />
          </button>
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50">
            <i className="ri-gift-2-line text-xl text-orange-500" />
          </div>
          <div>
            <h1 className="text-xl font-black text-slate-800">تسليمات التفصيل</h1>
            <p className="text-xs text-slate-400">متابعة حالات جميع طلبات التفصيل وإجراءات التسليم</p>
          </div>
        </div>
        <button
          onClick={() => navigate("/tailoring/choose-client")}
          className="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg bg-sky-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-sky-600"
        >
          <i className="ri-add-line" />
          أمر تفصيل جديد
        </button>
      </div>

      <div className="mb-6 grid grid-cols-4 gap-4">
        <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50">
            <i className="ri-loader-4-line text-xl text-sky-500" />
          </div>
          <div>
            <p className="text-2xl font-black text-slate-800">{pendingDeliveryCount}</p>
            <p className="text-xs text-slate-500">قيد التنفيذ</p>
          </div>
        </div>
        <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-50">
            <i className="ri-gift-line text-xl text-orange-500" />
          </div>
          <div>
            <p className="text-2xl font-black text-orange-600">{readyCount}</p>
            <p className="text-xs text-slate-500">جاهزة للتسليم</p>
          </div>
        </div>
        <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
            <i className="ri-check-double-line text-xl text-emerald-500" />
          </div>
          <div>
            <p className="text-2xl font-black text-emerald-600">{deliveredCount}</p>
            <p className="text-xs text-slate-500">تم التسليم</p>
          </div>
        </div>
        <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50">
            <i className="ri-alarm-warning-line text-xl text-red-500" />
          </div>
          <div>
            <p className="text-2xl font-black text-red-600">{lateCount}</p>
            <p className="text-xs text-slate-500">متأخرة</p>
          </div>
        </div>
      </div>

      <div className="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
        <div className="relative min-w-52 flex-1">
          <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="ابحث بالاسم أو الرقم أو نوع الثوب..."
            className="w-full rounded-lg border border-slate-200 py-2 pl-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-sky-200"
          />
        </div>
        <select
          value={stageFilter}
          onChange={(e) => setStageFilter(e.target.value)}
          className="cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none"
        >
          <option value="الكل">جميع المراحل</option>
          {stageDefs.map((s) => (
            <option key={s.key} value={s.key}>
              {s.label}
            </option>
          ))}
        </select>
        <div className="flex items-center gap-1 rounded-lg bg-slate-100 p-1">
          {statusOptions.map((s) => (
            <button
              key={s}
              onClick={() => setStatusFilter(s)}
              className={`cursor-pointer whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${
                statusFilter === s ? "bg-white text-slate-800" : "text-slate-500 hover:text-slate-700"
              }`}
            >
              {s}
            </button>
          ))}
        </div>
        <span className="whitespace-nowrap text-xs font-medium text-slate-400">{filtered.length} نتيجة</span>
      </div>

      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm" dir="rtl">
            <thead>
              <tr className="bg-sky-600 text-right text-white">
                <th className="px-4 py-3 text-xs font-semibold">الأمر</th>
                <th className="px-4 py-3 text-xs font-semibold">العميلة</th>
                <th className="px-4 py-3 text-xs font-semibold">نوع الثوب</th>
                <th className="px-4 py-3 text-xs font-semibold">الخياط</th>
                <th className="px-4 py-3 text-xs font-semibold">المرحلة الحالية</th>
                <th className="px-4 py-3 text-xs font-semibold">الحالة</th>
                <th className="px-4 py-3 text-xs font-semibold">موعد التسليم</th>
                <th className="px-4 py-3 text-xs font-semibold">المتبقي</th>
                <th className="px-4 py-3 text-xs font-semibold">الدفع</th>
                <th className="px-4 py-3 text-center text-xs font-semibold">الإجراءات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filtered.length === 0 && (
                <tr>
                  <td colSpan={10} className="py-12 text-center text-sm text-slate-400">
                    <i className="ri-inbox-line mb-2 block text-3xl text-slate-200" />
                    لا توجد نتائج
                  </td>
                </tr>
              )}
              {filtered.map((order) => {
                const diff = getDaysDiff(order.dueDate);
                const isDeliveredNow = order.currentStage === "delivered";
                const stage =
                  stageMap[order.currentStage] ??
                  { label: order.currentStage, bg: "bg-slate-100", text: "text-slate-600", border: "border-slate-200" };
                const routeId = /^\d+$/.test(order.id) ? Number(order.id) : 0;

                return (
                  <tr key={order.id} className="transition-colors hover:bg-slate-50">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <span
                          className={`rounded-full border px-1.5 py-0.5 text-xs font-bold ${priorityColors[order.priority]}`}
                        >
                          {order.priority}
                        </span>
                        <span className="font-mono text-xs font-semibold text-slate-800">{order.orderNumber}</span>
                      </div>
                      <p className="mt-0.5 text-xs text-slate-400">{order.orderDate}</p>
                    </td>

                    <td className="px-4 py-3">
                      <p className="text-sm font-semibold text-slate-800">{order.customer.name}</p>
                      <p className="font-mono text-xs text-slate-400">{order.customer.phone}</p>
                    </td>

                    <td className="px-4 py-3">
                      <p className="text-sm font-medium text-slate-700">{order.garmentType}</p>
                      {order.design.hasEmbroidery && (
                        <span className="mt-0.5 flex items-center gap-1 text-xs text-pink-500">
                          <i className="ri-magic-line" /> تطريز
                        </span>
                      )}
                    </td>

                    <td className="px-4 py-3">
                      <p className="text-sm text-slate-700">{order.tailorName}</p>
                      <p className="text-xs text-slate-400">{order.branchName}</p>
                    </td>

                    <td className="px-4 py-3">
                      {isDeliveredNow ? (
                        <span className="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">
                          <i className="ri-check-double-line" /> تم التسليم
                        </span>
                      ) : (
                        <span
                          className={`inline-flex items-center gap-1 rounded-full border px-2 py-1 text-xs font-semibold ${stage.bg} ${stage.text} ${stage.border}`}
                        >
                          {stage.label}
                        </span>
                      )}
                    </td>

                    <td className="px-4 py-3">
                      <span
                        className={`inline-block rounded-full px-2 py-1 text-xs font-semibold ${statusBadge[order.status]}`}
                      >
                        {order.status}
                      </span>
                    </td>

                    <td className="px-4 py-3">
                      <p className="text-sm text-slate-700">{order.dueDate}</p>
                      {!isDeliveredNow && (
                        <p
                          className={`mt-0.5 text-xs font-semibold ${
                            diff < 0
                              ? "text-red-600"
                              : diff === 0
                                ? "text-orange-600"
                                : diff <= 2
                                  ? "text-amber-600"
                                  : "text-slate-400"
                          }`}
                        >
                          {diff < 0 ? `متأخر ${Math.abs(diff)} يوم` : diff === 0 ? "اليوم" : `${diff} يوم متبقي`}
                        </p>
                      )}
                    </td>

                    <td className="px-4 py-3">
                      {order.pricing.remaining > 0 ? (
                        <span className="text-sm font-bold text-rose-600">
                          {order.pricing.remaining.toLocaleString("en-US")} ج.م
                        </span>
                      ) : (
                        <span className="flex items-center gap-1 text-xs font-semibold text-emerald-600">
                          <i className="ri-check-line" /> مسدد
                        </span>
                      )}
                    </td>

                    <td className="px-4 py-3">
                      <span
                        className={`inline-block rounded-full px-2 py-1 text-xs font-semibold ${paymentColors[order.paymentStatus]}`}
                      >
                        {order.paymentStatus}
                      </span>
                    </td>

                    <td className="px-4 py-3">
                      <div className="flex items-center justify-center gap-1.5">
                        <button
                          onClick={() => routeId && navigate(`/tailoring/orders/${routeId}`)}
                          title="عرض التفاصيل"
                          className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-md border border-slate-200 text-slate-500 transition-colors hover:bg-sky-50 hover:text-sky-600"
                        >
                          <i className="ri-eye-line text-sm" />
                        </button>

                        <a
                          href={`https://wa.me/${order.customer.whatsapp.replace(/^0/, "966")}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          title="تواصل واتساب"
                          className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-md border border-slate-200 text-emerald-600 transition-colors hover:bg-emerald-50"
                        >
                          <i className="ri-whatsapp-line text-sm" />
                        </a>

                        <a
                          href={`tel:${order.customer.phone}`}
                          title="اتصال"
                          className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-md border border-slate-200 text-slate-500 transition-colors hover:bg-slate-100"
                        >
                          <i className="ri-phone-line text-sm" />
                        </a>

                        {!isDeliveredNow && order.currentStage === "ready" && (
                          <button
                            onClick={() => handleMarkDelivered(order)}
                            title="تأكيد التسليم"
                            className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-md border border-orange-200 bg-orange-50 text-orange-600 transition-colors hover:bg-orange-100"
                          >
                            <i className="ri-gift-2-line text-sm" />
                          </button>
                        )}

                        <button
                          title="طباعة"
                          className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-md border border-slate-200 text-slate-500 transition-colors hover:bg-slate-100"
                        >
                          <i className="ri-printer-line text-sm" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {deliverConfirm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
          <div className="w-[400px] rounded-xl bg-white p-7 text-center" dir="rtl">
            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-50">
              <i className="ri-gift-2-line text-4xl text-orange-500" />
            </div>
            <h3 className="mb-1 text-lg font-bold text-slate-800">تأكيد تسليم الطلب</h3>
            <p className="mb-1 text-sm text-slate-500">
              <span className="font-semibold text-slate-700">{deliverConfirm.customer.name}</span>
            </p>
            <p className="mb-1 text-sm text-slate-500">{deliverConfirm.garmentType}</p>
            {deliverConfirm.pricing.remaining > 0 && (
              <div className="my-4 rounded-lg border border-rose-100 bg-rose-50 px-4 py-2.5 text-right">
                <span className="text-xs text-rose-600">تنبيه: يوجد مبلغ متبقي</span>
                <p className="text-lg font-black text-rose-700">
                  {deliverConfirm.pricing.remaining.toLocaleString("en-US")} ج.م
                </p>
              </div>
            )}
            <p className="mb-5 text-sm text-slate-500">هل تم استلام الطلب من العميلة؟</p>
            <div className="flex gap-3">
              <button
                onClick={() => setDeliverConfirm(null)}
                className="flex-1 cursor-pointer whitespace-nowrap rounded-md border border-slate-200 py-2.5 text-sm text-slate-600 transition-colors hover:bg-slate-50"
              >
                إلغاء
              </button>
              <button
                onClick={confirmDeliver}
                className="flex-1 cursor-pointer whitespace-nowrap rounded-md bg-orange-500 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-orange-600"
              >
                <i className="ri-check-line ml-1" />
                تأكيد التسليم
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
