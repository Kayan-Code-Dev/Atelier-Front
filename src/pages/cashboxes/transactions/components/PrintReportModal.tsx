import React, { useState } from "react";
import type { TTransaction } from "@/api/v2/transactions/transactions.types";
import { getCategoryLabel } from "../hooks/useCashboxTransactionsPage";

type ReportType = "full" | "summary" | "movements";

interface Props {
  entries: TTransaction[];
  selectedCashboxName: string;
  dateFrom: string;
  dateTo: string;
  openingBalance: number;
  periodIncome: number;
  periodExpenses: number;
  currentBalance: number;
  onClose: () => void;
}

const reportOptions: { type: ReportType; label: string; sublabel: string; icon: string }[] = [
  { type: "full", label: "التقرير الشامل", sublabel: "ملخص الأرصدة + دفتر الأستاذ الكامل", icon: "ri-file-list-3-line" },
  { type: "summary", label: "ملخص الأرصدة", sublabel: "الأرصدة والإجماليات فقط بدون تفاصيل", icon: "ri-bar-chart-grouped-line" },
  { type: "movements", label: "كشف الحركات", sublabel: "دفتر الأستاذ فقط بدون ملخص", icon: "ri-book-open-line" },
];

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

const fmtDate = (d: string) =>
  d ? new Date(d).toLocaleDateString("ar-EG", { year: "numeric", month: "long", day: "numeric" }) : "—";

const formatDateShort = (s: string) =>
  new Date(s).toLocaleDateString("ar-EG", { year: "numeric", month: "short", day: "numeric" });

export function PrintReportModal({
  entries,
  selectedCashboxName,
  dateFrom,
  dateTo,
  openingBalance,
  periodIncome,
  periodExpenses,
  currentBalance,
  onClose,
}: Props) {
  const [reportType, setReportType] = useState<ReportType>("full");

  const printDate = new Date().toLocaleDateString("ar-EG", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });

  const netResult = periodIncome - periodExpenses;
  const today = new Date().toISOString().slice(0, 10);

  const sortedEntries = [...entries].sort(
    (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
  );

  const periodFrom = dateFrom || (sortedEntries[0]?.created_at?.slice(0, 10) ?? today);
  const periodTo = dateTo || today;

  const handlePrint = () => {
    const el = document.getElementById("print-treasury-area");
    if (!el) return;
    const clone = el.cloneNode(true) as HTMLElement;
    clone.id = "print-treasury-clone";
    const portal = document.createElement("div");
    portal.id = "print-portal";
    portal.style.cssText = "position:absolute;left:-9999px;top:0;width:800px;";
    portal.appendChild(clone);
    document.body.appendChild(portal);

    const styleId = "print-treasury-print-style";
    const style = document.createElement("style");
    style.id = styleId;
    style.textContent = `
      @media print {
        body > *:not(#print-portal) { display: none !important; }
        #print-portal { display: block !important; position: static !important; left: auto !important; }
        #print-portal, #print-treasury-clone { width: 100% !important; }
        #print-treasury-clone * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { margin: 10mm; size: A4; }
      }
    `;
    document.head.appendChild(style);

    const cleanup = () => {
      portal.remove();
      document.getElementById(styleId)?.remove();
      window.removeEventListener("afterprint", cleanup);
    };
    window.addEventListener("afterprint", cleanup);
    window.print();
  };

  const ReportHeader = ({ badge }: { badge: string }) => (
    <div className="flex items-start justify-between mb-7 pb-5 border-b-2 border-slate-800">
      <div>
        <div className="text-2xl font-black text-slate-800">كشف المعاملات</div>
        <div className="text-sm text-slate-500 mt-0.5">عرض محاسبي شامل للخزنة</div>
        <div className="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
          <span className="inline-block w-1.5 h-1.5 rounded-full bg-slate-400" />
          {selectedCashboxName || "جميع الصناديق"}
        </div>
      </div>
      <div className="text-right">
        <div className="inline-block text-xs font-bold px-3 py-1 rounded-full border border-slate-300 text-slate-600 mb-3">
          {badge}
        </div>
        <div className="text-xs text-slate-400 mb-0.5">الفترة</div>
        <div className="text-sm font-bold text-slate-800">
          {fmtDate(periodFrom)} — {fmtDate(periodTo)}
        </div>
        <div className="text-xs text-slate-400 mt-1.5">تاريخ الطباعة</div>
        <div className="text-xs font-medium text-slate-600">{printDate}</div>
      </div>
    </div>
  );

  const BalanceSummaryBlock = () => (
    <div className="mb-7">
      <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">
        ملخص الأرصدة — {selectedCashboxName || "جميع الصناديق"}
      </div>
      <div className="grid grid-cols-3 gap-3 mb-3">
        <div className="border border-amber-200 bg-amber-50 rounded-xl p-4 text-center">
          <p className="text-xs text-slate-500 mb-1">الرصيد الافتتاحي</p>
          <p className="text-xl font-black text-amber-700">{fmt(openingBalance)}</p>
          <p className="text-xs text-slate-400">ج.م</p>
        </div>
        <div className="border border-green-200 bg-green-50 rounded-xl p-4 text-center">
          <p className="text-xs text-slate-500 mb-1">إجمالي الإيرادات</p>
          <p className="text-xl font-black text-green-700">+{fmt(periodIncome)}</p>
          <p className="text-xs text-slate-400">ج.م</p>
          <p className="text-xs text-green-500 mt-1">{entries.filter((e) => e.type === "income").length} حركة</p>
        </div>
        <div className="border border-red-200 bg-red-50 rounded-xl p-4 text-center">
          <p className="text-xs text-slate-500 mb-1">إجمالي المصاريف</p>
          <p className="text-xl font-black text-red-600">-{fmt(periodExpenses)}</p>
          <p className="text-xs text-slate-400">ج.م</p>
          <p className="text-xs text-red-400 mt-1">{entries.filter((e) => e.type === "expense").length} حركة</p>
        </div>
      </div>
      <div className="grid grid-cols-3 gap-3">
        <div className={`border rounded-xl p-4 text-center ${currentBalance >= 0 ? "border-sky-200 bg-sky-50" : "border-rose-200 bg-rose-50"}`}>
          <p className="text-xs text-slate-500 mb-1">الرصيد الحالي</p>
          <p className={`text-xl font-black ${currentBalance >= 0 ? "text-sky-700" : "text-rose-600"}`}>{fmt(currentBalance)}</p>
          <p className="text-xs text-slate-400">ج.م</p>
        </div>
        <div className="border border-emerald-200 bg-emerald-50 rounded-xl p-4 text-center">
          <p className="text-xs text-slate-500 mb-1">الإجمالي المتاح</p>
          <p className="text-xl font-black text-emerald-700">{fmt(Math.max(0, currentBalance))}</p>
          <p className="text-xs text-slate-400">ج.م</p>
        </div>
        <div className="border border-slate-200 bg-slate-50 rounded-xl p-4 text-center">
          <p className="text-xs text-slate-500 mb-1">صافي الفترة</p>
          <p className={`text-xl font-black ${netResult >= 0 ? "text-emerald-700" : "text-rose-600"}`}>
            {netResult >= 0 ? "+" : ""}{fmt(netResult)}
          </p>
          <p className="text-xs text-slate-400">ج.م</p>
        </div>
      </div>
      <div className={`mt-4 rounded-xl p-4 flex items-center justify-between ${netResult >= 0 ? "bg-emerald-700 text-white" : "bg-rose-600 text-white"}`}>
        <div>
          <p className="text-xs opacity-80 mb-0.5">صافي نتيجة الفترة</p>
          <p className="text-2xl font-black">{netResult >= 0 ? "+" : ""}{fmt(netResult)} ج.م</p>
        </div>
        <div className="text-right opacity-80">
          <p className="text-xs mb-0.5">عدد القيود الإجمالي</p>
          <p className="text-xl font-bold">{entries.length} قيد</p>
        </div>
      </div>
    </div>
  );

  const LedgerTable = () => (
    <div>
      <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
        دفتر الأستاذ — كشف الحركات المالية
      </div>
      <table className="w-full text-xs border-collapse">
        <thead>
          <tr className="bg-slate-800 text-white">
            <th className="text-right py-2.5 px-3 font-semibold">التاريخ</th>
            <th className="text-right py-2.5 px-3 font-semibold">رقم المرجع</th>
            <th className="text-right py-2.5 px-3 font-semibold">البيان</th>
            <th className="text-right py-2.5 px-3 font-semibold">الفئة</th>
            <th className="text-right py-2.5 px-3 font-semibold">الصندوق</th>
            <th className="text-right py-2.5 px-3 font-semibold">المستخدم</th>
            <th className="text-center py-2.5 px-3 font-semibold text-green-300">دائن</th>
            <th className="text-center py-2.5 px-3 font-semibold text-red-300">مدين</th>
            <th className="text-center py-2.5 px-3 font-semibold text-blue-200">الرصيد</th>
          </tr>
        </thead>
        <tbody>
          <tr className="bg-amber-50 border-b border-amber-100">
            <td colSpan={7} className="px-3 py-2 text-amber-700 font-semibold text-xs">
              رصيد أول المدة (افتتاحي)
            </td>
            <td className="px-3 py-2 text-center" />
            <td className="px-3 py-2 text-center font-bold text-amber-700">{fmt(openingBalance)}</td>
          </tr>
          {sortedEntries.map((entry, idx) => {
            const isIncome = entry.type === "income";
            const amount = typeof entry.amount === "number" ? entry.amount : Number(entry.amount) || 0;
            const bal = typeof entry.balance_after === "number" ? entry.balance_after : Number(entry.balance_after) || 0;
            return (
              <tr key={entry.id} className={`border-b border-gray-50 ${idx % 2 === 0 ? "bg-white" : "bg-slate-50/40"}`}>
                <td className="px-3 py-2.5 text-slate-500 whitespace-nowrap">{formatDateShort(entry.created_at)}</td>
                <td className="px-3 py-2.5">
                  <span className={`font-mono px-1.5 py-0.5 rounded text-xs ${isIncome ? "bg-green-50 text-green-700" : "bg-red-50 text-red-600"}`}>
                    #{entry.id}
                  </span>
                </td>
                <td className="px-3 py-2.5 text-slate-700 max-w-[140px]">
                  <p className="truncate">{entry.description || getCategoryLabel(entry.category)}</p>
                </td>
                <td className="px-3 py-2.5">
                  <span className="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded-full whitespace-nowrap">
                    {getCategoryLabel(entry.category)}
                  </span>
                </td>
                <td className="px-3 py-2.5 text-slate-500 whitespace-nowrap">{entry.cashbox?.name ?? "—"}</td>
                <td className="px-3 py-2.5 text-slate-600 whitespace-nowrap">{entry.creator?.name ?? "—"}</td>
                <td className="px-3 py-2.5 text-center">
                  {isIncome ? <span className="text-green-600 font-semibold">{fmt(amount)}</span> : <span className="text-slate-300">—</span>}
                </td>
                <td className="px-3 py-2.5 text-center">
                  {!isIncome ? <span className="text-red-500 font-semibold">{fmt(amount)}</span> : <span className="text-slate-300">—</span>}
                </td>
                <td className="px-3 py-2.5 text-center font-bold">{fmt(bal)}</td>
              </tr>
            );
          })}
          <tr className="bg-slate-800 text-white font-bold text-xs">
            <td colSpan={6} className="px-3 py-3 text-right">الإجماليات — {entries.length} قيد</td>
            <td className="px-3 py-3 text-center text-green-300">{fmt(periodIncome)}</td>
            <td className="px-3 py-3 text-center text-red-300">{fmt(periodExpenses)}</td>
            <td className="px-3 py-3 text-center text-green-300">{fmt(currentBalance)}</td>
          </tr>
        </tbody>
      </table>
    </div>
  );

  const ReportFooter = () => (
    <div className="border-t-2 border-slate-800 pt-5 mt-8">
      <div className="grid grid-cols-3 gap-6 mb-5">
        {["مُعدّ التقرير", "المراجع المحاسبي", "مدير الفرع"].map((label) => (
          <div key={label} className="text-center">
            <div className="h-12 border-b border-dashed border-slate-300 mb-2" />
            <div className="text-xs text-slate-400">{label}</div>
          </div>
        ))}
      </div>
      <div className="text-center text-xs text-slate-400">
        كشف المعاملات — {selectedCashboxName || "جميع الصناديق"} | طُبع بتاريخ {printDate}
      </div>
    </div>
  );

  const FullReportDoc = () => (
    <div className="text-sm" style={{ fontFamily: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" }}>
      <ReportHeader badge="تقرير شامل — أرصدة وحركات" />
      <BalanceSummaryBlock />
      <LedgerTable />
      <ReportFooter />
    </div>
  );

  const SummaryReportDoc = () => (
    <div className="text-sm" style={{ fontFamily: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" }}>
      <ReportHeader badge="ملخص الأرصدة" />
      <BalanceSummaryBlock />
      <ReportFooter />
    </div>
  );

  const MovementsReportDoc = () => (
    <div className="text-sm" style={{ fontFamily: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" }}>
      <ReportHeader badge="كشف الحركات المالية" />
      <div className="flex gap-4 mb-6 text-xs">
        <div className="flex items-center gap-1.5 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
          <span className="text-slate-500">افتتاحي:</span>
          <span className="font-bold text-amber-700">{fmt(openingBalance)} ج.م</span>
        </div>
        <div className="flex items-center gap-1.5 bg-green-50 border border-green-100 rounded-lg px-3 py-2">
          <span className="text-slate-500">إيرادات:</span>
          <span className="font-bold text-green-700">+{fmt(periodIncome)} ج.م</span>
        </div>
        <div className="flex items-center gap-1.5 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
          <span className="text-slate-500">مصاريف:</span>
          <span className="font-bold text-red-600">-{fmt(periodExpenses)} ج.م</span>
        </div>
        <div className="flex items-center gap-1.5 bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-2">
          <span className="text-slate-500">إقفال:</span>
          <span className="font-bold text-emerald-700">{fmt(currentBalance)} ج.م</span>
        </div>
      </div>
      <LedgerTable />
      <ReportFooter />
    </div>
  );

  const docMap: Record<ReportType, React.ReactElement> = {
    full: <FullReportDoc />,
    summary: <SummaryReportDoc />,
    movements: <MovementsReportDoc />,
  };

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex" dir="rtl">
      <div className="no-print w-64 bg-slate-900 text-white flex flex-col shrink-0">
        <div className="p-5 border-b border-slate-700">
          <div className="font-bold text-sm mb-0.5">طباعة تقرير الخزنة</div>
          <div className="text-xs text-slate-400 mt-1">{selectedCashboxName || "جميع الصناديق"}</div>
          <div className="text-xs text-slate-500 mt-0.5">{fmtDate(periodFrom)} — {fmtDate(periodTo)}</div>
        </div>
        <div className="px-4 py-3 bg-slate-800 border-b border-slate-700">
          <div className="flex items-center justify-between text-xs mb-1.5">
            <span className="text-slate-400">عدد القيود</span>
            <span className="font-bold text-white">{entries.length}</span>
          </div>
          <div className="flex items-center justify-between text-xs mb-1.5">
            <span className="text-slate-400">الإيرادات</span>
            <span className="font-bold text-green-400">+{fmt(periodIncome)}</span>
          </div>
          <div className="flex items-center justify-between text-xs mb-1.5">
            <span className="text-slate-400">المصاريف</span>
            <span className="font-bold text-red-400">-{fmt(periodExpenses)}</span>
          </div>
          <div className="flex items-center justify-between text-xs pt-1.5 border-t border-slate-600">
            <span className="text-slate-400">الرصيد الحالي</span>
            <span className={`font-bold ${currentBalance >= 0 ? "text-emerald-400" : "text-rose-400"}`}>{fmt(currentBalance)}</span>
          </div>
        </div>
        <div className="p-3 flex-1 space-y-2">
          {reportOptions.map((opt) => (
            <button
              key={opt.type}
              onClick={() => setReportType(opt.type)}
              className={`w-full text-right px-4 py-3 rounded-lg transition-colors cursor-pointer ${reportType === opt.type ? "bg-white text-slate-900" : "text-slate-300 hover:bg-slate-800"}`}
            >
              <div className="flex items-start gap-3">
                <div className={`w-8 h-8 flex items-center justify-center rounded-md shrink-0 mt-0.5 ${reportType === opt.type ? "bg-slate-900 text-white" : "bg-slate-700 text-slate-300"}`}>
                  <i className={`${opt.icon} text-base`} />
                </div>
                <div>
                  <div className="font-semibold text-sm">{opt.label}</div>
                  <div className="text-xs mt-0.5 text-slate-500">{opt.sublabel}</div>
                </div>
              </div>
            </button>
          ))}
        </div>
        <div className="p-4 border-t border-slate-700 space-y-2">
          <button
            onClick={handlePrint}
            className="w-full flex items-center justify-center gap-2 bg-white text-slate-900 py-2.5 rounded-lg font-semibold text-sm hover:bg-slate-100 transition-colors cursor-pointer"
          >
            <i className="ri-printer-line" />
            طباعة
          </button>
          <button
            onClick={onClose}
            className="w-full flex items-center justify-center gap-2 border border-slate-600 text-slate-300 py-2 rounded-lg text-sm hover:bg-slate-800 transition-colors cursor-pointer"
          >
            <i className="ri-close-line" />
            إغلاق
          </button>
        </div>
      </div>
      <div className="flex-1 overflow-y-auto bg-slate-100 p-8">
        <div className="no-print flex items-center justify-between mb-4">
          <div className="flex items-center gap-2 text-sm text-slate-600">
            <i className={`${reportOptions.find((r) => r.type === reportType)?.icon} text-slate-500`} />
            <span className="font-medium">{reportOptions.find((r) => r.type === reportType)?.label}</span>
          </div>
          <div className="flex items-center gap-2 text-xs text-slate-400">
            <i className="ri-eye-line" />
            معاينة قبل الطباعة
          </div>
        </div>
        <div id="print-treasury-area" className="bg-white rounded-xl w-full max-w-[800px] mx-auto p-10" style={{ minHeight: "1050px" }}>
          {docMap[reportType]}
        </div>
      </div>
    </div>
  );
}
