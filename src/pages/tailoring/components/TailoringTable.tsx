import { useNavigate } from "react-router";
import {
  type TailoringOrder,
  tailoringStages,
  type TailoringStageDef,
  statusColors,
  priorityColors,
  paymentColors,
  projectOrderRouteId,
} from "@/pages/tailoring/tailoring.ui";

interface TailoringTableProps {
  orders: TailoringOrder[];
  stages?: TailoringStageDef[];
}

export default function TailoringTable({ orders, stages }: TailoringTableProps) {
  const stageList = stages ?? tailoringStages;
  const navigate = useNavigate();

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table className="w-full text-sm" dir="rtl">
        <thead>
          <tr className="border-b border-slate-200 bg-slate-50">
            <th className="px-4 py-3 text-right text-xs font-semibold text-slate-500">الطلب</th>
            <th className="px-4 py-3 text-right text-xs font-semibold text-slate-500">العميلة</th>
            <th className="px-4 py-3 text-right text-xs font-semibold text-slate-500">الثوب / القماش</th>
            <th className="px-4 py-3 text-right text-xs font-semibold text-slate-500">الخياطة</th>
            <th className="px-4 py-3 text-right text-xs font-semibold text-slate-500">المرحلة</th>
            <th className="px-4 py-3 text-right text-xs font-semibold text-slate-500">التسليم</th>
            <th className="px-4 py-3 text-right text-xs font-semibold text-slate-500">المالية</th>
            <th className="px-4 py-3 text-right text-xs font-semibold text-slate-500">الإجراءات</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {orders.map((order) => {
            const stage = stageList.find((s) => s.key === order.currentStage);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const due = new Date(order.dueDate.replace(/\//g, "-"));
            const daysLeft = Math.ceil((due.getTime() - today.getTime()) / 86400000);
            const stageIdx = stageList.findIndex((s) => s.key === order.currentStage);
            const progress =
              stageList.length > 1 ? Math.round((stageIdx / (stageList.length - 1)) * 100) : 0;
            const isUrgent = daysLeft <= 3 && daysLeft >= 0;
            const isOverdue = daysLeft < 0;
            const routeId = projectOrderRouteId(order.id);

            return (
              <tr
                key={order.id}
                className={`align-top transition-colors hover:bg-slate-50/70 ${isOverdue ? "bg-red-50/30" : ""}`}
              >
                <td className="px-4 py-3.5">
                  <div className="font-mono text-xs font-bold text-slate-500">{order.orderNumber}</div>
                  <div className="mt-1 flex flex-wrap items-center gap-1">
                    <span className={`rounded-full px-1.5 py-0.5 text-xs font-medium ${statusColors[order.status]}`}>
                      {order.status}
                    </span>
                    {order.priority !== "عادي" && (
                      <span
                        className={`rounded-full px-1.5 py-0.5 text-xs font-medium ${priorityColors[order.priority]}`}
                      >
                        {order.priority}
                      </span>
                    )}
                  </div>
                  <div className="mt-1 text-xs text-slate-400">{order.orderDate.slice(5)}</div>
                </td>

                <td className="min-w-[150px] px-4 py-3.5">
                  <div className="flex items-center gap-2">
                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-rose-100 to-pink-100">
                      <span className="text-xs font-bold text-rose-600">{order.customer.name[0]}</span>
                    </div>
                    <div>
                      <div className="text-sm font-semibold leading-tight text-slate-800">{order.customer.name}</div>
                      <div className="mt-0.5 text-xs text-slate-400" dir="ltr">
                        {order.customer.phone}
                      </div>
                    </div>
                  </div>
                  <div className="mr-10 mt-1 text-xs text-slate-400">{order.branchName}</div>
                </td>

                <td className="min-w-[150px] px-4 py-3.5">
                  <div className="text-sm font-semibold text-slate-700">{order.garmentType}</div>
                  <div className="mt-0.5 text-xs text-slate-400">
                    {order.fabric.type} — {order.fabric.color}
                  </div>
                  {order.design.hasEmbroidery && (
                    <div className="mt-1.5 flex items-center gap-1">
                      <i className="ri-magic-line text-xs text-pink-400" />
                      <span className="text-xs text-pink-600">تطريز</span>
                    </div>
                  )}
                </td>

                <td className="px-4 py-3.5">
                  <div className="flex items-center gap-1.5">
                    <div className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100">
                      <i className="ri-user-star-line text-xs text-slate-500" />
                    </div>
                    <span className="whitespace-nowrap text-xs text-slate-700">{order.tailorName}</span>
                  </div>
                </td>

                <td className="min-w-[160px] px-4 py-3.5">
                  {stage && (
                    <span
                      className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium ${stage.bg} ${stage.text} ${stage.border}`}
                    >
                      <i className={`${stage.icon} text-xs`} />
                      {stage.label}
                    </span>
                  )}
                  <div className="mt-2 h-1.5 w-28 overflow-hidden rounded-full bg-slate-100">
                    <div
                      className="h-full rounded-full bg-gradient-to-l from-emerald-400 to-sky-400"
                      style={{ width: `${progress}%` }}
                    />
                  </div>
                  <div className="mt-0.5 text-xs text-slate-400">{progress}%</div>
                </td>

                <td className="px-4 py-3.5">
                  <div
                    className={`flex items-center gap-1 text-xs font-semibold ${
                      isOverdue ? "text-red-600" : isUrgent ? "text-orange-600" : "text-slate-600"
                    }`}
                  >
                    <i
                      className={
                        isOverdue ? "ri-alarm-warning-line" : isUrgent ? "ri-alarm-line" : "ri-calendar-check-line"
                      }
                    />
                    {order.dueDate.slice(5)}
                  </div>
                  <div
                    className={`mt-1 text-xs ${isOverdue ? "text-red-500" : isUrgent ? "text-orange-500" : "text-slate-400"}`}
                  >
                    {isOverdue ? `متأخر ${Math.abs(daysLeft)} يوم` : daysLeft === 0 ? "اليوم!" : `${daysLeft} يوم`}
                  </div>
                  {order.eventDate && (
                    <div className="mt-0.5 flex items-center gap-1 text-xs text-slate-300">
                      <i className="ri-heart-line text-xs" />
                      {order.eventDate.slice(5)}
                    </div>
                  )}
                </td>

                <td className="min-w-[130px] px-4 py-3.5">
                  <div className="text-sm font-black text-slate-700">
                    {order.pricing.price.toLocaleString("en-US")} ج.م
                  </div>
                  <div className="mt-0.5 flex items-center gap-1 text-xs text-emerald-600">
                    <i className="ri-check-line text-xs" />
                    {order.pricing.deposit.toLocaleString("en-US")}
                  </div>
                  {order.pricing.remaining > 0 && (
                    <div className="flex items-center gap-1 text-xs text-rose-500">
                      <i className="ri-time-line text-xs" />
                      {order.pricing.remaining.toLocaleString("en-US")} متبقي
                    </div>
                  )}
                  <div
                    className={`mt-1.5 inline-block rounded-full px-1.5 py-0.5 text-xs font-medium ${paymentColors[order.paymentStatus]}`}
                  >
                    {order.paymentStatus}
                  </div>
                </td>

                <td className="px-4 py-3.5">
                  <div className="flex items-center gap-1">
                    <button
                      type="button"
                      onClick={() => routeId && navigate(`/tailoring/orders/${routeId}`)}
                      className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50"
                      title="تفاصيل"
                    >
                      <i className="ri-eye-line text-sm" />
                    </button>
                    <a
                      href={`https://wa.me/966${order.customer.whatsapp.replace(/^0/, "")}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                      title="واتساب"
                      onClick={(e) => e.stopPropagation()}
                    >
                      <i className="ri-whatsapp-line text-sm" />
                    </a>
                    <button
                      type="button"
                      className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-sky-50 hover:text-sky-600"
                      title="تحديث المرحلة"
                    >
                      <i className="ri-arrow-right-up-line text-sm" />
                    </button>
                  </div>
                </td>
              </tr>
            );
          })}
          {orders.length === 0 && (
            <tr>
              <td colSpan={8} className="py-16 text-center text-slate-400">
                <i className="ri-scissors-cut-line mb-2 block text-4xl opacity-30" />
                <p className="text-sm">لا توجد أوامر مطابقة للفلتر</p>
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
