import { useNavigate } from "react-router";
import {
  type TailoringOrder,
  tailoringStages,
  type TailoringStageDef,
  statusColors,
  priorityColors,
  projectOrderRouteId,
} from "@/pages/tailoring/tailoring.ui";

interface KanbanBoardProps {
  orders: TailoringOrder[];
  /** When omitted, uses default stage definitions for display. */
  stages?: TailoringStageDef[];
}

const colorMap: Record<string, string> = {
  ذهبي: "#D97706",
  أسود: "#1e293b",
  عاجي: "#F5F0E8",
  "وردي باهت": "#F9A8D4",
  كحلي: "#1e3a5f",
  تركواز: "#0EA5E9",
  "أحمر خمري": "#9B1C1C",
  "رمادي فاتح": "#9CA3AF",
  "أخضر زمردي": "#059669",
  أبيض: "#F8FAFC",
};

function OrderCard({
  order,
  navigate,
}: {
  order: TailoringOrder;
  navigate: ReturnType<typeof useNavigate>;
}) {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const due = new Date(order.dueDate.replace(/\//g, "-"));
  const daysLeft = Math.ceil((due.getTime() - today.getTime()) / 86400000);
  const isUrgent = daysLeft <= 3;
  const isOverdue = daysLeft < 0;

  const priorityBorder =
    order.priority === "VIP"
      ? "border-amber-400"
      : order.priority === "عاجل"
        ? "border-rose-400"
        : "border-slate-200";
  const priorityAccent =
    order.priority === "VIP" ? "bg-amber-400" : order.priority === "عاجل" ? "bg-rose-400" : "bg-slate-300";

  const routeId = projectOrderRouteId(order.id);

  return (
    <div
      role="button"
      tabIndex={0}
      onClick={() => routeId && navigate(`/tailoring/orders/${routeId}`)}
      onKeyDown={(e) => {
        if (e.key === "Enter" && routeId) navigate(`/tailoring/orders/${routeId}`);
      }}
      className={`group relative cursor-pointer overflow-hidden rounded-lg border-2 ${priorityBorder} bg-white p-3.5 transition-all hover:border-opacity-80`}
    >
      <div className={`absolute right-0 top-0 h-full w-1 rounded-r-lg ${priorityAccent}`} />

      <div className="mb-2 flex items-start justify-between pr-2">
        <div>
          <span className="font-mono text-xs text-slate-400">{order.orderNumber}</span>
          {order.priority !== "عادي" && (
            <span
              className={`mr-1.5 rounded-full px-1.5 py-0.5 text-xs font-medium ${priorityColors[order.priority]}`}
            >
              {order.priority}
            </span>
          )}
        </div>
        <span className={`rounded-full px-1.5 py-0.5 text-xs font-medium ${statusColors[order.status]}`}>
          {order.status}
        </span>
      </div>

      <div className="mb-0.5 pr-2 text-sm font-bold text-slate-800 group-hover:text-slate-900">
        {order.customer.name}
      </div>
      <div className="mb-3 pr-2 text-xs text-slate-500">{order.garmentType}</div>

      <div className="mb-3 flex items-center gap-1.5 pr-2">
        <div
          className="h-3 w-3 shrink-0 rounded-full border border-slate-200"
          style={{ background: colorMap[order.fabric.color] || "#94a3b8" }}
        />
        <span className="truncate text-xs text-slate-400">
          {order.fabric.type} — {order.fabric.color}
        </span>
        {order.design.hasEmbroidery && (
          <i className="ri-magic-line shrink-0 text-xs text-pink-400" title="يتضمن تطريز" />
        )}
      </div>

      <div
        className={`mb-3 flex items-center gap-1 pr-2 text-xs ${
          isOverdue ? "text-red-600" : isUrgent ? "text-orange-600" : "text-slate-400"
        }`}
      >
        <i className={isOverdue || isUrgent ? "ri-alarm-warning-line" : "ri-calendar-2-line"} />
        {isOverdue
          ? `متأخر ${Math.abs(daysLeft)} يوم`
          : daysLeft === 0
            ? "اليوم آخر موعد!"
            : `${daysLeft} يوم متبقي — ${order.dueDate.slice(5)}`}
      </div>

      <div className="flex items-center justify-between border-t border-slate-100 pt-2.5 pr-2">
        <div>
          <div className="text-xs font-black text-slate-700">
            {order.pricing.price.toLocaleString("en-US")} ج.م
          </div>
          {order.pricing.remaining > 0 && (
            <div className="text-xs text-rose-500">
              متبقي {order.pricing.remaining.toLocaleString("en-US")}
            </div>
          )}
        </div>
        <div className="flex items-center gap-1">
          <div className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100">
            <i className="ri-user-star-line text-xs text-slate-500" />
          </div>
          <span className="max-w-[60px] truncate text-xs text-slate-400">{order.tailorName.split(" ")[0]}</span>
        </div>
      </div>
    </div>
  );
}

export default function KanbanBoard({ orders, stages }: KanbanBoardProps) {
  const navigate = useNavigate();
  const stageList = stages ?? tailoringStages;

  return (
    <div className="flex gap-4 overflow-x-auto pb-4" style={{ minWidth: "max-content" }}>
      {stageList.map((stage) => {
        const stageOrders = orders.filter((o) => o.currentStage === stage.key);
        const totalValue = stageOrders.reduce((s, o) => s + o.pricing.price, 0);

        return (
          <div key={stage.key} className="w-56 flex-shrink-0">
            <div className={`mb-3 rounded-xl border px-3 py-3 ${stage.bg} ${stage.border}`}>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="flex h-7 w-7 items-center justify-center">
                    <i className={`${stage.icon} text-sm ${stage.text}`} />
                  </div>
                  <span className={`text-xs font-bold ${stage.text}`}>{stage.label}</span>
                </div>
                <span
                  className={`flex h-6 w-6 items-center justify-center rounded-full text-sm font-black ${stage.text} bg-white/70`}
                >
                  {stageOrders.length}
                </span>
              </div>
              {stageOrders.length > 0 && (
                <div className={`mt-1.5 text-xs ${stage.text} opacity-70`}>
                  {totalValue.toLocaleString("en-US")} ج.م
                </div>
              )}
            </div>

            <div className="space-y-2.5">
              {stageOrders.length === 0 ? (
                <div className="rounded-xl border-2 border-dashed border-slate-200 py-8 text-center">
                  <i className="ri-inbox-line mb-1 block text-2xl text-slate-200" />
                  <span className="text-xs text-slate-300">لا يوجد</span>
                </div>
              ) : (
                stageOrders.map((order) => (
                  <OrderCard key={order.id} order={order} navigate={navigate} />
                ))
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
}
