import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router";
import {
  useTailoringOrdersQuery,
  useTailoringWorkflowStatusesQuery,
} from "@/api/v2/tailoring-orders/tailoringOrders.hooks";
import { priorityColors } from "@/pages/tailoring/tailoring.ui";
import { buildWorkflowStageDefs, mapTailoringResourceToOrder } from "./tailoringOrder.adapter";
import KanbanBoard from "./components/KanbanBoard";
import TailoringStats from "./components/TailoringStats";
import TailoringTable from "./components/TailoringTable";

type ViewMode = "kanban" | "table";

export default function TailoringOrdersList() {
  const navigate = useNavigate();
  const [view, setView] = useState<ViewMode>("kanban");
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [stageFilter, setStageFilter] = useState("الكل");
  const [statusFilter, setStatusFilter] = useState("الكل");
  const [priorityFilter, setPriorityFilter] = useState("الكل");

  useEffect(() => {
    const t = window.setTimeout(() => setDebouncedSearch(search.trim()), 400);
    return () => window.clearTimeout(t);
  }, [search]);

  const { data: workflow, isLoading: workflowLoading } = useTailoringWorkflowStatusesQuery();
  const stageDefs = useMemo(() => buildWorkflowStageDefs(workflow), [workflow]);

  const { data: listRes, isLoading: listLoading, isError } = useTailoringOrdersQuery({
    page: 1,
    per_page: 100,
    search: debouncedSearch || undefined,
    sort_by: "created_at",
    sort_dir: "desc",
  });

  const orders = useMemo(() => {
    const rows = listRes?.data ?? [];
    return rows.map(mapTailoringResourceToOrder);
  }, [listRes]);

  const today = useMemo(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }, []);

  const urgentOrders = useMemo(() => {
    return orders
      .filter((o) => {
        const due = new Date(o.dueDate.replace(/\//g, "-"));
        const diff = Math.ceil((due.getTime() - today.getTime()) / 86400000);
        return (diff <= 2 && diff >= 0 && o.status === "نشط") || o.status === "متأخر";
      })
      .slice(0, 5);
  }, [orders, today]);

  const readyOrders = useMemo(
    () => orders.filter((o) => o.currentStage === "ready_for_delivery"),
    [orders],
  );

  const filtered = useMemo(() => {
    return orders.filter((o) => {
      const matchStage = stageFilter === "الكل" || o.currentStage === stageFilter;
      const matchStatus = statusFilter === "الكل" || o.status === statusFilter;
      const matchPriority = priorityFilter === "الكل" || o.priority === priorityFilter;
      return matchStage && matchStatus && matchPriority;
    });
  }, [orders, stageFilter, statusFilter, priorityFilter]);

  const statusOptions = ["الكل", "نشط", "منجز", "متأخر", "ملغي"];
  const priorityOptions = ["الكل", "VIP", "عاجل", "عادي"];

  const loading = workflowLoading || listLoading;

  if (isError) {
    return (
      <div className="p-6" dir="rtl">
        <p className="text-sm text-red-600">تعذر تحميل أوامر التفصيل. تحقق من الاتصال والصلاحيات.</p>
      </div>
    );
  }

  return (
    <div className="p-6" dir="rtl">
      <div className="mb-6 flex items-center justify-between">
        <div>
          <div className="mb-1 flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50">
              <i className="ri-scissors-cut-line text-xl text-rose-500" />
            </div>
            <div>
              <h1 className="text-xl font-black text-slate-800">قسم التفصيل</h1>
              <p className="text-xs text-slate-400">
                إدارة أوامر التفصيل — القياسات — الأقمشة — مراحل الإنتاج
              </p>
            </div>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => navigate("/tailoring/choose-client")}
            className="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-700"
          >
            <i className="ri-add-line" />
            أمر تفصيل جديد
          </button>
        </div>
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-20 text-slate-500">
          <i className="ri-loader-4-line mr-2 animate-spin text-xl" /> جاري التحميل...
        </div>
      ) : (
        <>
          <TailoringStats orders={orders} />

          {(urgentOrders.length > 0 || readyOrders.length > 0) && (
            <div className="mb-5 grid grid-cols-2 gap-4">
              {urgentOrders.length > 0 && (
                <div className="rounded-xl border border-red-200 bg-red-50 p-4">
                  <div className="mb-3 flex items-center gap-2">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100">
                      <i className="ri-alarm-warning-line text-red-600" />
                    </div>
                    <h3 className="text-sm font-bold text-red-700">تحتاج متابعة عاجلة</h3>
                    <span className="rounded-full bg-red-200 px-2 py-0.5 text-xs font-bold text-red-700">
                      {urgentOrders.length}
                    </span>
                  </div>
                  <div className="space-y-2">
                    {urgentOrders.map((o) => {
                      const due = new Date(o.dueDate.replace(/\//g, "-"));
                      const diff = Math.ceil((due.getTime() - today.getTime()) / 86400000);
                      return (
                        <button
                          key={o.id}
                          type="button"
                          onClick={() => navigate(`/tailoring/orders/${o.id}`)}
                          className="flex w-full cursor-pointer items-center justify-between rounded-lg bg-white px-3 py-2 text-right transition-colors hover:bg-red-50"
                        >
                          <div className="flex items-center gap-2">
                            <span
                              className={`rounded-full px-1.5 py-0.5 text-xs font-medium ${priorityColors[o.priority]}`}
                            >
                              {o.priority}
                            </span>
                            <span className="text-sm font-semibold text-slate-800">{o.customer.name}</span>
                            <span className="text-xs text-slate-400">— {o.garmentType}</span>
                          </div>
                          <span
                            className={`text-xs font-bold ${diff < 0 ? "text-red-600" : "text-orange-600"}`}
                          >
                            {diff < 0
                              ? `متأخر ${Math.abs(diff)} يوم`
                              : diff === 0
                                ? "اليوم!"
                                : `${diff} يوم`}
                          </span>
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}

              {readyOrders.length > 0 && (
                <div className="rounded-xl border border-orange-200 bg-orange-50 p-4">
                  <div className="mb-3 flex items-center gap-2">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100">
                      <i className="ri-gift-2-line text-orange-600" />
                    </div>
                    <h3 className="text-sm font-bold text-orange-700">جاهزة للتسليم</h3>
                    <span className="rounded-full bg-orange-200 px-2 py-0.5 text-xs font-bold text-orange-700">
                      {readyOrders.length}
                    </span>
                  </div>
                  <div className="space-y-2">
                    {readyOrders.map((o) => (
                      <button
                        key={o.id}
                        type="button"
                        onClick={() => navigate(`/tailoring/orders/${o.id}`)}
                        className="flex w-full cursor-pointer items-center justify-between rounded-lg bg-white px-3 py-2 text-right transition-colors hover:bg-orange-50"
                      >
                        <div className="flex items-center gap-2">
                          <span className="text-sm font-semibold text-slate-800">{o.customer.name}</span>
                          <span className="text-xs text-slate-400">— {o.garmentType}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          {o.pricing.remaining > 0 && (
                            <span className="text-xs font-semibold text-rose-600">
                              {o.pricing.remaining.toLocaleString("en-US")} ج.م
                            </span>
                          )}
                          <a
                            href={`https://wa.me/966${o.customer.whatsapp.replace(/^0/, "")}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex h-6 w-6 items-center justify-center rounded bg-emerald-100 text-emerald-600 transition-colors hover:bg-emerald-200"
                            onClick={(e) => e.stopPropagation()}
                            title="واتساب"
                          >
                            <i className="ri-whatsapp-line text-xs" />
                          </a>
                        </div>
                      </button>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          <div className="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
            <div className="relative min-w-48 flex-1">
              <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="ابحث بالاسم أو الرقم أو نوع الثوب..."
                className="w-full rounded-lg border border-slate-200 py-2 pl-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
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
              {priorityOptions.map((p) => (
                <button
                  key={p}
                  type="button"
                  onClick={() => setPriorityFilter(p)}
                  className={`cursor-pointer whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${
                    priorityFilter === p ? "bg-white text-slate-800" : "text-slate-500 hover:text-slate-700"
                  }`}
                >
                  {p}
                </button>
              ))}
            </div>

            <div className="flex items-center gap-1 rounded-lg bg-slate-100 p-1">
              {statusOptions.map((s) => (
                <button
                  key={s}
                  type="button"
                  onClick={() => setStatusFilter(s)}
                  className={`cursor-pointer whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${
                    statusFilter === s ? "bg-white text-slate-800" : "text-slate-500 hover:text-slate-700"
                  }`}
                >
                  {s}
                </button>
              ))}
            </div>

            <div className="whitespace-nowrap text-xs font-medium text-slate-400">{filtered.length} نتيجة</div>

            <div className="mr-auto flex items-center gap-1 rounded-lg bg-slate-100 p-1">
              <button
                type="button"
                onClick={() => setView("kanban")}
                className={`flex h-8 w-8 cursor-pointer items-center justify-center rounded-md transition-colors ${
                  view === "kanban" ? "bg-white text-slate-800" : "text-slate-400 hover:text-slate-600"
                }`}
                title="عرض كانبان"
              >
                <i className="ri-layout-column-line text-base" />
              </button>
              <button
                type="button"
                onClick={() => setView("table")}
                className={`flex h-8 w-8 cursor-pointer items-center justify-center rounded-md transition-colors ${
                  view === "table" ? "bg-white text-slate-800" : "text-slate-400 hover:text-slate-600"
                }`}
                title="عرض جدول"
              >
                <i className="ri-table-line text-base" />
              </button>
            </div>
          </div>

          {view === "kanban" ? (
            <KanbanBoard orders={filtered} stages={stageDefs} />
          ) : (
            <TailoringTable orders={filtered} stages={stageDefs} />
          )}
        </>
      )}
    </div>
  );
}
