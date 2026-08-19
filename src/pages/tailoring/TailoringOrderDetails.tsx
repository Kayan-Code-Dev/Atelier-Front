import { useMemo, useState } from "react";
import { useParams, useNavigate, Link } from "react-router";
import {
  useAddTailoringOrderPaymentMutation,
  usePatchTailoringOrderStatusMutation,
  useTailoringOrderQuery,
  useTailoringWorkflowStatusesQuery,
} from "@/api/v2/tailoring-orders/tailoringOrders.hooks";
import { statusColors, paymentColors, priorityColors, type Measurements } from "@/pages/tailoring/tailoring.ui";
import {
  buildWorkflowStageDefs,
  mapTailoringResourceToOrder,
} from "./tailoringOrder.adapter";
import { nowMysqlDateTime } from "./tailoringDatetime";

const measurementLabels: { key: keyof Measurements; label: string }[] = [
  { key: "height",      label: "الطول الكلي" },
  { key: "shoulder",    label: "الكتف" },
  { key: "chest",       label: "الصدر" },
  { key: "waist",       label: "الخصر" },
  { key: "hips",        label: "الأرداف" },
  { key: "sleeveLength",label: "طول الكمام" },
  { key: "sleeveWidth", label: "اتساع الكمام" },
  { key: "dressLength", label: "طول الثوب" },
  { key: "neckline",    label: "فتحة الرقبة" },
];

export default function TailoringDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const detailId =
    id && /^\d+$/.test(id) && Number(id) > 0 ? Number(id) : null;
  const { data: rawApi, isLoading: apiLoading, isError: apiError } = useTailoringOrderQuery(detailId);
  const { data: workflow } = useTailoringWorkflowStatusesQuery();
  const patchStatus = usePatchTailoringOrderStatusMutation();
  const addPaymentMut = useAddTailoringOrderPaymentMutation();

  const orderFromApi = useMemo(() => (rawApi ? mapTailoringResourceToOrder(rawApi) : null), [rawApi]);

  const stageDefs = useMemo(() => buildWorkflowStageDefs(workflow), [workflow]);
  const [showStageModal, setShowStageModal]   = useState(false);
  const [showPayModal, setShowPayModal]       = useState(false);
  const [showPrintModal, setShowPrintModal]   = useState(false);
  const [successMsg, setSuccessMsg]           = useState("");
  const [activeTab, setActiveTab]             = useState<"progress" | "measurements" | "payments">("progress");

  // Payment form state
  const [payAmount, setPayAmount] = useState("");
  const [payMethod, setPayMethod] = useState<"كاش" | "تحويل" | "بطاقة">("كاش");
  const [payNotes, setPayNotes]   = useState("");

  if (!id || detailId == null) {
    return (
      <div className="flex min-h-[60vh] flex-col items-center justify-center" dir="rtl">
        <i className="ri-scissors-cut-line mb-4 text-6xl text-slate-200" />
        <h2 className="mb-2 text-xl font-bold text-slate-700">الأمر غير موجود</h2>
        <p className="mb-4 text-sm text-slate-500">معرّف الطلب غير صالح.</p>
        <button
          type="button"
          onClick={() => navigate("/tailoring/orders")}
          className="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg bg-slate-800 px-4 py-2 text-sm text-white"
        >
          <i className="ri-arrow-right-line" /> العودة للقائمة
        </button>
      </div>
    );
  }

  if (apiLoading) {
    return (
      <div className="flex min-h-[60vh] flex-col items-center justify-center text-slate-500" dir="rtl">
        <i className="ri-loader-4-line mb-1 animate-spin text-2xl" /> جاري التحميل...
      </div>
    );
  }

  if (apiError) {
    return (
      <div className="flex min-h-[60vh] flex-col items-center justify-center" dir="rtl">
        <p className="text-sm text-red-600">تعذر تحميل الأمر.</p>
        <button
          type="button"
          onClick={() => navigate("/tailoring/orders")}
          className="mt-4 flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm text-white cursor-pointer whitespace-nowrap"
        >
          <i className="ri-arrow-right-line" /> العودة للقائمة
        </button>
      </div>
    );
  }

  if (!orderFromApi) {
    return (
      <div className="flex min-h-[60vh] flex-col items-center justify-center" dir="rtl">
        <i className="ri-scissors-cut-line mb-4 text-6xl text-slate-200" />
        <h2 className="mb-2 text-xl font-bold text-slate-700">الأمر غير موجود</h2>
        <button
          type="button"
          onClick={() => navigate("/tailoring/orders")}
          className="mt-4 flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg bg-slate-800 px-4 py-2 text-sm text-white"
        >
          <i className="ri-arrow-right-line" /> العودة للقائمة
        </button>
      </div>
    );
  }

  const order = orderFromApi;

  const currentStageIdx = stageDefs.findIndex((s) => s.key === order.currentStage);
  const currentStageInfo = stageDefs[currentStageIdx];
  const progress =
    stageDefs.length > 1 ? Math.round((currentStageIdx / (stageDefs.length - 1)) * 100) : 0;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const due = new Date(order.dueDate.replace(/\//g, "-"));
  const daysLeft = Math.ceil((due.getTime() - today.getTime()) / 86400000);
  const totalPaid = order.paymentRecords.reduce((s, r) => s + r.amount, 0);
  const remaining = order.pricing.price - totalPaid;

  const advanceStage = async () => {
    if (currentStageIdx < 0 || currentStageIdx >= stageDefs.length - 1) return;
    const nextStage = stageDefs[currentStageIdx + 1];
    if (nextStage.key === "delivered" && remaining > 0) {
      setSuccessMsg("لا يمكن التسليم قبل سداد المبلغ بالكامل.");
      setTimeout(() => setSuccessMsg(""), 4000);
      return;
    }
    try {
      await patchStatus.mutateAsync({ id: detailId!, body: { status: nextStage.key } });
      setShowStageModal(false);
      setSuccessMsg(`تم التحديث إلى: ${nextStage.label}`);
      setTimeout(() => setSuccessMsg(""), 3500);
    } catch {
      // ignored
    }
  };

  const addPayment = async () => {
    const amount = parseFloat(payAmount);
    if (!amount || amount <= 0 || amount > remaining) return;
    try {
      await addPaymentMut.mutateAsync({
        id: detailId!,
        body: {
          amount,
          payment_date: nowMysqlDateTime(),
          notes: payNotes || undefined,
          status: "paid",
          payment_type: "normal",
        },
      });
      setPayAmount("");
      setPayNotes("");
      setShowPayModal(false);
      setSuccessMsg(`تم تسجيل دفعة ${amount.toLocaleString("en-US")} ج.م بنجاح`);
      setTimeout(() => setSuccessMsg(""), 3500);
    } catch {
      /* handled */
    }
  };

  return (
    <div className="p-6" dir="rtl">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-xs text-slate-400 mb-4">
        <Link to="/tailoring/orders" className="hover:text-slate-600 flex items-center gap-1 cursor-pointer">
          <i className="ri-scissors-cut-line" /> قسم التفصيل
        </Link>
        <i className="ri-arrow-left-s-line" />
        <span className="text-slate-600 font-semibold">{order.garmentType} — {order.orderNumber}</span>
      </div>

      {/* Header */}
      <div className="flex items-start justify-between mb-5">
        <div className="flex items-start gap-3">
          <button
            onClick={() => navigate("/tailoring/orders")}
            className="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 cursor-pointer transition-colors mt-0.5"
          >
            <i className="ri-arrow-right-line" />
          </button>
          <div>
            <div className="flex items-center gap-2 flex-wrap">
              <h1 className="text-xl font-black text-slate-800">{order.garmentType}</h1>
              <span className="text-slate-400 font-mono text-sm">{order.orderNumber}</span>
              <span className={`text-xs px-2.5 py-1 rounded-full font-medium ${statusColors[order.status]}`}>
                {order.status}
              </span>
              <span className={`text-xs px-2.5 py-1 rounded-full font-medium ${priorityColors[order.priority]}`}>
                {order.priority}
              </span>
              <span className={`text-xs px-2.5 py-1 rounded-full font-medium ${paymentColors[order.paymentStatus]}`}>
                {order.paymentStatus}
              </span>
              {daysLeft < 0 && (
                <span className="text-xs px-2.5 py-1 rounded-full font-medium bg-red-50 text-red-700 border border-red-200 flex items-center gap-1">
                  <i className="ri-alarm-warning-line" /> متأخر {Math.abs(daysLeft)} يوم
                </span>
              )}
            </div>
            <p className="text-xs text-slate-400 mt-1.5">
              طُلب {order.orderDate} &nbsp;•&nbsp; {order.branchName} &nbsp;•&nbsp; خياطة: {order.tailorName}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => setShowPrintModal(true)}
            className="flex items-center gap-2 border border-slate-200 text-slate-600 px-3 py-2 rounded-lg text-sm hover:bg-slate-50 transition-colors cursor-pointer whitespace-nowrap"
          >
            <i className="ri-printer-line" /> طباعة
          </button>
          <a
            href={`https://wa.me/966${order.customer.whatsapp.replace(/^0/, "")}`}
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 py-2 rounded-lg text-sm hover:bg-emerald-100 transition-colors cursor-pointer whitespace-nowrap"
          >
            <i className="ri-whatsapp-line" /> واتساب
          </a>
          {remaining > 0 && (
            <button
              onClick={() => setShowPayModal(true)}
              className="flex items-center gap-2 bg-emerald-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors cursor-pointer whitespace-nowrap"
            >
              <i className="ri-money-dollar-circle-line" /> تسجيل دفعة
            </button>
          )}
          {currentStageIdx >= 0 && currentStageIdx < stageDefs.length - 1 && (
            <button
              onClick={() => setShowStageModal(true)}
              className="flex items-center gap-2 bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-700 transition-colors cursor-pointer whitespace-nowrap"
            >
              <i className="ri-arrow-right-up-line" /> تحديث المرحلة
            </button>
          )}
        </div>
      </div>

      {/* Success Toast */}
      {successMsg && (
        <div className="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm animate-fade-in">
          <i className="ri-checkbox-circle-line text-lg" /> {successMsg}
        </div>
      )}

      {/* ─── Stage Tracker ─── */}
      <div className="bg-white rounded-xl border border-slate-200 p-5 mb-5 overflow-x-auto">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-sm font-bold text-slate-700">تتبع مراحل الإنتاج</h3>
          <div className="flex items-center gap-2">
            <span className="text-xs text-slate-400">{progress}% مكتمل</span>
            <div className="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
              <div className="h-full bg-gradient-to-l from-emerald-400 to-sky-400 rounded-full" style={{ width: `${progress}%` }} />
            </div>
          </div>
        </div>
        <div className="flex items-center gap-0 min-w-max">
          {stageDefs.map((stage, idx) => {
            const isDone    = idx < currentStageIdx;
            const isCurrent = idx === currentStageIdx;
            return (
              <div key={stage.key} className="flex items-center">
                <div className="flex flex-col items-center gap-1.5 w-24">
                  <div className={`w-9 h-9 flex items-center justify-center rounded-full border-2 transition-all ${
                    isDone    ? "bg-emerald-500 border-emerald-500 text-white" :
                    isCurrent ? `${stage.bg} ${stage.text} border-current` :
                    "bg-slate-50 border-slate-200 text-slate-300"
                  }`} style={isCurrent ? { borderColor: stage.color } : {}}>
                    {isDone ? <i className="ri-check-line text-sm" /> : <i className={`${stage.icon} text-sm`} />}
                  </div>
                  <span className={`text-xs text-center leading-tight font-medium ${isDone ? "text-emerald-600" : isCurrent ? stage.text : "text-slate-300"}`}>
                    {stage.label}
                  </span>
                  {isCurrent && (
                    <span className="text-xs text-white px-2 py-0.5 rounded-full font-bold" style={{ background: stage.color }}>الآن</span>
                  )}
                </div>
                {idx < stageDefs.length - 1 && (
                  <div className={`h-0.5 w-4 mx-0.5 mb-6 ${idx < currentStageIdx ? "bg-emerald-400" : "bg-slate-200"}`} />
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* ─── Main Grid ─── */}
      <div className="grid grid-cols-3 gap-5">

        {/* Col 1 */}
        <div className="space-y-4">
          <div className="bg-white rounded-xl border border-slate-200 p-4">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 flex items-center justify-center bg-gradient-to-br from-rose-100 to-pink-100 rounded-full">
                <span className="text-base font-black text-rose-600">{order.customer.name[0]}</span>
              </div>
              <div>
                <h3 className="font-bold text-slate-800 text-sm">{order.customer.name}</h3>
                <div className="text-xs text-slate-400">عميلة دائمة</div>
              </div>
            </div>
            <div className="space-y-2">
              {[
                { icon: "ri-phone-line",    val: order.customer.phone,    dir: "ltr" },
                { icon: "ri-whatsapp-line", val: order.customer.whatsapp, dir: "ltr" },
                { icon: "ri-map-pin-line",  val: order.customer.address,  dir: "rtl" },
              ].map((item) => (
                <div key={item.icon} className="flex items-center gap-2 text-xs">
                  <div className="w-6 h-6 flex items-center justify-center bg-slate-50 rounded text-slate-400 shrink-0">
                    <i className={item.icon} />
                  </div>
                  <span className="text-slate-700" dir={item.dir}>{item.val}</span>
                </div>
              ))}
            </div>
            <div className="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-400 flex items-center gap-1">
              <i className="ri-map-pin-2-line" /> {order.branchName}
            </div>
          </div>

          <div className="bg-white rounded-xl border border-slate-200 p-4">
            <h3 className="font-bold text-slate-700 text-sm mb-3 flex items-center gap-2">
              <i className="ri-calendar-line text-slate-400" /> التواريخ
            </h3>
            <div className="space-y-2.5">
              {[
                { label: "تاريخ الطلب",    val: order.orderDate,  icon: "ri-file-add-line",         color: "text-slate-400" },
                { label: "موعد التسليم",   val: order.dueDate,    icon: "ri-calendar-check-line",   color: daysLeft < 0 ? "text-red-500" : daysLeft <= 3 ? "text-orange-500" : "text-emerald-500" },
                { label: "تاريخ المناسبة", val: order.eventDate,  icon: "ri-heart-line",            color: "text-pink-400" },
              ].map((d) => (
                <div key={d.label} className="flex items-center justify-between">
                  <div className="flex items-center gap-1.5 text-xs text-slate-500">
                    <i className={`${d.icon} ${d.color}`} /> {d.label}
                  </div>
                  <span className="text-xs font-semibold text-slate-700">{d.val}</span>
                </div>
              ))}
              <div className={`flex items-center justify-between pt-2 border-t border-slate-100 text-xs font-bold ${daysLeft < 0 ? "text-red-600" : daysLeft <= 3 ? "text-orange-600" : "text-emerald-600"}`}>
                <div className="flex items-center gap-1">
                  <i className="ri-time-line" /> الوقت المتبقي
                </div>
                <span>
                  {daysLeft < 0 ? `متأخر ${Math.abs(daysLeft)} يوم` : daysLeft === 0 ? "اليوم!" : `${daysLeft} يوم`}
                </span>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl border border-slate-200 p-4">
            <h3 className="font-bold text-slate-700 text-sm mb-3 flex items-center gap-2">
              <i className="ri-shopping-bag-line text-amber-400" /> القماش
            </h3>
            <div className="space-y-2 text-xs">
              {[
                { label: "نوع الثوب",  val: order.garmentType },
                { label: "نوع القماش", val: order.fabric.type },
                { label: "اللون",      val: order.fabric.color },
                { label: "الكمية",     val: order.fabric.quantity },
                { label: "المورد",     val: order.fabric.supplier || "—" },
              ].map((r) => (
                <div key={r.label} className="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2">
                  <span className="text-slate-400">{r.label}</span>
                  <span className="font-semibold text-slate-700">{r.val}</span>
                </div>
              ))}
              {order.fabric.notes && (
                <div className="bg-amber-50 border border-amber-100 rounded-lg p-2 text-amber-700">
                  {order.fabric.notes}
                </div>
              )}
            </div>

            <div className="mt-3 pt-3 border-t border-slate-100">
              <div className="text-xs font-bold text-slate-600 mb-1.5">التصميم</div>
              <p className="text-xs text-slate-600 leading-relaxed">{order.design.description}</p>
              <div className="text-xs text-slate-400 mt-1">الأسلوب: {order.design.style}</div>
            </div>
            {order.design.hasEmbroidery && (
              <div className="mt-3 bg-pink-50 border border-pink-100 rounded-lg p-2.5">
                <div className="flex items-center gap-1.5 mb-1">
                  <i className="ri-magic-line text-pink-500" />
                  <span className="text-xs font-bold text-pink-700">تطريز</span>
                </div>
                <p className="text-xs text-pink-700">{order.design.embroideryNotes}</p>
              </div>
            )}
          </div>
        </div>

        {/* Col 2 */}
        <div className="space-y-4">
          {/* Tab nav */}
          <div className="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
            {[
              { key: "progress",     label: "سجل التقدم",  icon: "ri-history-line" },
              { key: "measurements", label: "القياسات",    icon: "ri-ruler-line" },
              { key: "payments",     label: "الدفعات",     icon: "ri-money-dollar-circle-line" },
            ].map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key as typeof activeTab)}
                className={`flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-semibold transition-colors cursor-pointer ${
                  activeTab === tab.key ? "bg-white text-slate-800" : "text-slate-500 hover:text-slate-700"
                }`}
              >
                <i className={tab.icon} /> {tab.label}
              </button>
            ))}
          </div>

          {activeTab === "measurements" && (
            <div className="bg-white rounded-xl border border-slate-200 p-4">
              {detailId != null && (
                <div className="mb-3 flex justify-end">
                  <Link
                    to={`/tailoring/orders/${detailId}/edit-measurements`}
                    className="text-xs font-semibold text-emerald-600 hover:text-emerald-700"
                  >
                    <i className="ri-pencil-line ml-1" />
                    تعديل المقاسات
                  </Link>
                </div>
              )}
              <div className="grid grid-cols-2 gap-2">
                {measurementLabels.map(({ key, label }) => {
                  const val = order.measurements[key];
                  if (typeof val !== "number" || val === 0) return null;
                  return (
                    <div key={key} className="flex items-center justify-between bg-violet-50 rounded-lg px-3 py-2.5">
                      <span className="text-xs text-violet-600">{label}</span>
                      <span className="text-sm font-black text-violet-800">
                        {val}<span className="text-xs font-normal text-violet-400"> سم</span>
                      </span>
                    </div>
                  );
                })}
              </div>
              {order.measurements.notes && (
                <div className="mt-3 bg-amber-50 border border-amber-100 rounded-lg p-3 flex items-start gap-2">
                  <i className="ri-alert-line text-amber-500 shrink-0 mt-0.5" />
                  <div>
                    <div className="text-xs font-bold text-amber-700 mb-0.5">ملاحظة مهمة</div>
                    <p className="text-xs text-amber-700">{order.measurements.notes}</p>
                  </div>
                </div>
              )}
            </div>
          )}

          {activeTab === "progress" && (
            <div className="bg-white rounded-xl border border-slate-200 p-4">
              <div className="flex justify-between items-center mb-4">
                <span className="text-xs text-slate-500">{order.stageLog.length} مراحل مكتملة</span>
                <span className={`text-xs px-2.5 py-1 rounded-full font-medium ${currentStageInfo?.bg} ${currentStageInfo?.text} border ${currentStageInfo?.border}`}>
                  {currentStageInfo?.label}
                </span>
              </div>
              <div className="relative">
                <div className="absolute right-[14px] top-2 bottom-2 w-px bg-slate-100" />
                <div className="space-y-4">
                  {order.stageLog.map((log, idx) => {
                    const stageInfo = stageDefs.find((s) => s.key === log.stage);
                    return (
                      <div key={idx} className="flex items-start gap-3 pr-1">
                        <div className={`w-7 h-7 flex items-center justify-center rounded-full border-2 shrink-0 relative z-10 ${stageInfo?.bg}`} style={{ borderColor: stageInfo?.color }}>
                          <i className={`${stageInfo?.icon} text-xs ${stageInfo?.text}`} />
                        </div>
                        <div className="flex-1 pb-1">
                          <div className="flex items-start justify-between gap-2">
                            <span className={`text-xs font-bold ${stageInfo?.text}`}>{stageInfo?.label}</span>
                            <span className="text-xs text-slate-400 whitespace-nowrap">{log.completedAt.slice(5)}</span>
                          </div>
                          <div className="text-xs text-slate-400">بواسطة: {log.doneBy}</div>
                          {log.notes && (
                            <div className="text-xs text-slate-500 mt-1 bg-slate-50 rounded px-2 py-1">{log.notes}</div>
                          )}
                        </div>
                      </div>
                    );
                  })}
                  {currentStageIdx < stageDefs.length - 1 && (
                    <div className="flex items-center gap-3 pr-1 opacity-35">
                      <div className="w-7 h-7 flex items-center justify-center rounded-full border-2 border-dashed border-slate-300 bg-slate-50 shrink-0 relative z-10">
                        <i className="ri-more-line text-xs text-slate-400" />
                      </div>
                      <span className="text-xs text-slate-400">{stageDefs.length - 1 - currentStageIdx} مرحلة متبقية</span>
                    </div>
                  )}
                </div>
              </div>
              {currentStageIdx < stageDefs.length - 1 && (
                <button
                  onClick={() => setShowStageModal(true)}
                  className="w-full mt-5 flex items-center justify-center gap-2 bg-slate-800 text-white py-2.5 rounded-lg text-xs font-bold hover:bg-slate-700 cursor-pointer whitespace-nowrap"
                >
                  <i className="ri-arrow-right-up-line" />
                  الانتقال إلى: {stageDefs[currentStageIdx + 1]?.label}
                </button>
              )}
              {currentStageIdx === stageDefs.length - 1 && (
                <div className="mt-4 flex items-center justify-center gap-2 bg-emerald-50 text-emerald-700 py-2.5 rounded-lg text-xs font-bold border border-emerald-200">
                  <i className="ri-check-double-line" /> الأمر مكتمل بالكامل
                </div>
              )}
            </div>
          )}

          {activeTab === "payments" && (
            <div className="bg-white rounded-xl border border-slate-200 p-4">
              {/* Summary bar */}
              <div className="bg-slate-50 rounded-lg p-3 mb-4">
                <div className="flex justify-between text-xs mb-2">
                  <span className="text-slate-500">إجمالي التكلفة</span>
                  <span className="font-black text-slate-800">{order.pricing.price.toLocaleString("en-US")} ج.م</span>
                </div>
                <div className="h-2 bg-slate-200 rounded-full overflow-hidden mb-2">
                  <div
                    className="h-full bg-emerald-500 rounded-full transition-all"
                    style={{ width: `${Math.min((totalPaid / order.pricing.price) * 100, 100)}%` }}
                  />
                </div>
                <div className="flex justify-between text-xs">
                  <span className="text-emerald-600 font-semibold">محصّل: {totalPaid.toLocaleString("en-US")} ج.م</span>
                  <span className={`font-semibold ${remaining > 0 ? "text-rose-600" : "text-emerald-600"}`}>
                    {remaining > 0 ? `متبقي: ${remaining.toLocaleString("en-US")} ج.م` : "مسدد بالكامل ✓"}
                  </span>
                </div>
              </div>

              {/* Records */}
              {order.paymentRecords.length === 0 ? (
                <div className="text-center py-8 text-slate-400">
                  <i className="ri-money-dollar-circle-line text-3xl block mb-2 opacity-30" />
                  <p className="text-xs">لا توجد دفعات مسجلة</p>
                </div>
              ) : (
                <div className="space-y-2 mb-3">
                  {order.paymentRecords.map((rec) => (
                    <div key={rec.id} className="flex items-center justify-between bg-emerald-50 rounded-lg px-3 py-2.5">
                      <div className="flex items-center gap-2">
                        <div className="w-6 h-6 flex items-center justify-center bg-emerald-100 rounded-full">
                          <i className="ri-check-line text-xs text-emerald-600" />
                        </div>
                        <div>
                          <div className="text-xs font-bold text-emerald-700">{rec.amount.toLocaleString("en-US")} ج.م</div>
                          <div className="text-xs text-slate-400">{rec.method} — {rec.date.slice(5)}</div>
                        </div>
                      </div>
                      {rec.notes && <span className="text-xs text-slate-400">{rec.notes}</span>}
                    </div>
                  ))}
                </div>
              )}

              {remaining > 0 && (
                <button
                  onClick={() => setShowPayModal(true)}
                  className="w-full flex items-center justify-center gap-2 bg-emerald-600 text-white py-2.5 rounded-lg text-xs font-bold hover:bg-emerald-700 cursor-pointer whitespace-nowrap"
                >
                  <i className="ri-add-line" /> تسجيل دفعة جديدة
                </button>
              )}
            </div>
          )}
        </div>

        {/* Col 3 */}
        <div className="space-y-4">
          <div className="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 text-white">
            <div className="flex items-center gap-2 mb-4">
              <i className="ri-scissors-cut-line text-lg text-slate-300" />
              <h3 className="font-bold text-sm text-slate-100">ملخص الأمر</h3>
            </div>
            <div className="space-y-3 text-sm">
              <div className="flex justify-between border-b border-slate-700 pb-3">
                <span className="text-slate-400 text-xs">العميلة</span>
                <span className="font-bold text-white">{order.customer.name}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400 text-xs">الثوب</span>
                <span className="font-semibold text-slate-100">{order.garmentType}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400 text-xs">الخياطة</span>
                <span className="font-semibold text-slate-100">{order.tailorName}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400 text-xs">التسليم</span>
                <span className={`font-bold ${daysLeft < 0 ? "text-red-400" : daysLeft <= 3 ? "text-orange-400" : "text-emerald-400"}`}>
                  {order.dueDate.slice(5)} {daysLeft < 0 ? "⚠" : ""}
                </span>
              </div>
              <div className="flex justify-between border-t border-slate-700 pt-3">
                <span className="text-slate-400 text-xs">السعر الإجمالي</span>
                <span className="text-xl font-black text-white">{order.pricing.price.toLocaleString("en-US")} ج.م</span>
              </div>
              {remaining > 0 ? (
                <div className="bg-rose-900/40 rounded-lg p-2.5 flex justify-between items-center">
                  <span className="text-rose-300 text-xs">متبقي للتحصيل</span>
                  <span className="text-rose-300 font-black">{remaining.toLocaleString("en-US")} ج.م</span>
                </div>
              ) : (
                <div className="bg-emerald-900/30 rounded-lg p-2.5 flex items-center justify-center gap-2">
                  <i className="ri-check-double-line text-emerald-400" />
                  <span className="text-emerald-400 text-xs font-bold">مدفوع بالكامل</span>
                </div>
              )}
            </div>
          </div>

          {order.notes && (
            <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
              <div className="flex items-center gap-2 mb-2">
                <i className="ri-sticky-note-2-line text-amber-500" />
                <h3 className="font-bold text-amber-700 text-sm">ملاحظات</h3>
              </div>
              <p className="text-sm text-amber-800 leading-relaxed">{order.notes}</p>
            </div>
          )}

          <div className="bg-white rounded-xl border border-slate-200 p-4">
            <h3 className="font-bold text-slate-700 text-sm mb-3 flex items-center gap-2">
              <i className="ri-information-line text-slate-400" /> معلومات إضافية
            </h3>
            <div className="space-y-2 text-xs">
              <div className="flex items-center justify-between">
                <span className="text-slate-400">الفرع</span>
                <span className="font-medium text-slate-700">{order.branchName}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-400">الخياطة</span>
                <span className="font-medium text-slate-700">{order.tailorName}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-400">الأولوية</span>
                <span className={`px-2 py-0.5 rounded-full font-medium ${priorityColors[order.priority]}`}>{order.priority}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-400">الدفعات</span>
                <span className="font-medium text-slate-700">{order.paymentRecords.length} دفعة</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-400">مراحل منجزة</span>
                <span className="font-medium text-slate-700">{order.stageLog.length} / {stageDefs.length}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ─── Stage Advance Modal ─── */}
      {showStageModal && currentStageIdx < stageDefs.length - 1 && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50" onClick={() => setShowStageModal(false)}>
          <div className="bg-white rounded-2xl w-96 p-6" dir="rtl" onClick={(e) => e.stopPropagation()}>
            <div className="w-14 h-14 flex items-center justify-center bg-slate-100 rounded-2xl mx-auto mb-4">
              <i className="ri-arrow-right-up-line text-2xl text-slate-600" />
            </div>
            <h3 className="font-black text-slate-800 text-base mb-2 text-center">تحديث مرحلة الإنتاج</h3>
            <p className="text-sm text-slate-500 mb-5 text-center">
              من <span className={`font-bold ${stageDefs[currentStageIdx].text}`}>{stageDefs[currentStageIdx].label}</span>
              {" ← "}
              <span className={`font-bold ${stageDefs[currentStageIdx + 1].text}`}>{stageDefs[currentStageIdx + 1].label}</span>
            </p>
            <div className="flex gap-3">
              <button onClick={() => setShowStageModal(false)} className="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm cursor-pointer hover:bg-slate-50 whitespace-nowrap">إلغاء</button>
              <button onClick={advanceStage} className="flex-1 py-2.5 rounded-xl bg-slate-800 text-white text-sm font-bold cursor-pointer hover:bg-slate-700 whitespace-nowrap">تأكيد</button>
            </div>
          </div>
        </div>
      )}

      {/* ─── Payment Modal ─── */}
      {showPayModal && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50" onClick={() => setShowPayModal(false)}>
          <div className="bg-white rounded-2xl w-[420px] p-6" dir="rtl" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between mb-5">
              <h3 className="font-black text-slate-800 text-base">تسجيل دفعة جديدة</h3>
              <button onClick={() => setShowPayModal(false)} className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50 cursor-pointer">
                <i className="ri-close-line" />
              </button>
            </div>

            {/* Summary */}
            <div className="bg-slate-50 rounded-xl p-3 mb-5 flex justify-between text-xs">
              <div>
                <p className="text-slate-400">إجمالي السعر</p>
                <p className="font-black text-slate-700 text-sm">{order.pricing.price.toLocaleString("en-US")} ج.م</p>
              </div>
              <div>
                <p className="text-slate-400">محصّل</p>
                <p className="font-black text-emerald-600 text-sm">{totalPaid.toLocaleString("en-US")} ج.م</p>
              </div>
              <div>
                <p className="text-slate-400">متبقي</p>
                <p className="font-black text-rose-600 text-sm">{remaining.toLocaleString("en-US")} ج.م</p>
              </div>
            </div>

            <div className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-600 mb-1.5">المبلغ (ج.م) <span className="text-red-500">*</span></label>
                <input
                  type="number"
                  min={1}
                  max={remaining}
                  value={payAmount}
                  onChange={(e) => setPayAmount(e.target.value)}
                  placeholder={`أقصى: ${remaining.toLocaleString("en-US")}`}
                  className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200"
                />
                <button
                  onClick={() => setPayAmount(String(remaining))}
                  className="text-xs text-emerald-600 mt-1 cursor-pointer hover:underline"
                >
                  تسديد كامل المتبقي ({remaining.toLocaleString("en-US")} ج.م)
                </button>
              </div>
              <div>
                <label className="block text-xs font-semibold text-slate-600 mb-1.5">طريقة الدفع</label>
                <div className="flex gap-2">
                  {(["كاش", "تحويل", "بطاقة"] as const).map((m) => (
                    <button
                      key={m}
                      onClick={() => setPayMethod(m)}
                      className={`flex-1 py-2 rounded-lg border text-xs font-semibold transition-colors cursor-pointer ${
                        payMethod === m ? "border-emerald-400 bg-emerald-50 text-emerald-700" : "border-slate-200 text-slate-500 hover:bg-slate-50"
                      }`}
                    >
                      {m}
                    </button>
                  ))}
                </div>
              </div>
              <div>
                <label className="block text-xs font-semibold text-slate-600 mb-1.5">ملاحظة (اختياري)</label>
                <input
                  type="text"
                  value={payNotes}
                  onChange={(e) => setPayNotes(e.target.value)}
                  placeholder="مثل: دفعة أخيرة، عربون إضافي..."
                  className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200"
                />
              </div>
            </div>
            <div className="flex gap-3 mt-5">
              <button onClick={() => setShowPayModal(false)} className="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm cursor-pointer whitespace-nowrap">إلغاء</button>
              <button
                onClick={addPayment}
                disabled={!payAmount || parseFloat(payAmount) <= 0 || parseFloat(payAmount) > remaining}
                className="flex-1 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold cursor-pointer hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
              >
                <i className="ri-check-line ml-1" /> تأكيد الدفعة
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ─── Print Modal ─── */}
      {showPrintModal && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50" onClick={() => setShowPrintModal(false)}>
          <div className="bg-white rounded-2xl w-96 p-6" dir="rtl" onClick={(e) => e.stopPropagation()}>
            <h3 className="font-black text-slate-800 text-base mb-4 text-center">اختر نوع الطباعة</h3>
            <div className="grid grid-cols-2 gap-3 mb-5">
              {[
                { icon: "ri-ruler-line",         label: "بطاقة القياسات",     desc: "جميع القياسات للخياط" },
                { icon: "ri-file-list-3-line",   label: "ورقة الأمر",          desc: "تفاصيل كاملة للأمر" },
                { icon: "ri-money-dollar-circle-line", label: "إيصال الدفع",  desc: "سجل الدفعات المالية" },
                { icon: "ri-file-paper-2-line",  label: "طباعة كاملة",        desc: "الأمر بكل تفاصيله" },
              ].map((item) => (
                <button
                  key={item.label}
                  onClick={() => setShowPrintModal(false)}
                  className="flex flex-col items-center gap-2 border border-slate-200 rounded-xl p-4 hover:bg-slate-50 cursor-pointer transition-colors"
                >
                  <div className="w-10 h-10 flex items-center justify-center bg-slate-100 rounded-lg">
                    <i className={`${item.icon} text-xl text-slate-600`} />
                  </div>
                  <span className="text-xs font-bold text-slate-700">{item.label}</span>
                  <span className="text-xs text-slate-400 text-center">{item.desc}</span>
                </button>
              ))}
            </div>
            <button onClick={() => setShowPrintModal(false)} className="w-full py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm cursor-pointer hover:bg-slate-50 whitespace-nowrap">إلغاء</button>
          </div>
        </div>
      )}

    </div>
  );
}
