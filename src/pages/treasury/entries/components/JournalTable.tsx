import { useState } from "react";
import { JournalEntry } from "../journal.types";
import { formatDate } from "@/utils/formatDate";

interface Props {
  entries: JournalEntry[];
  onApprove: (id: string) => void;
  onDelete: (id: string) => void;
  isPending?: boolean;
  isError?: boolean;
  errorMessage?: string;
}

const fmt = (n: number) =>
  n === 0 ? "" : new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

const typeColors: Record<string, string> = {
  عادي: "bg-blue-50 text-blue-600 border-blue-100",
  افتتاحي: "bg-emerald-50 text-emerald-600 border-emerald-100",
  تسوية: "bg-amber-50 text-amber-600 border-amber-100",
  إقفال: "bg-purple-50 text-purple-600 border-purple-100",
};

const statusColors: Record<string, string> = {
  معتمد: "bg-green-50 text-green-600",
  مسودة: "bg-amber-50 text-amber-600",
  ملغي: "bg-red-50 text-red-500",
};

export default function JournalTable({
  entries,
  onApprove,
  onDelete,
  isPending = false,
  isError = false,
  errorMessage,
}: Props) {
  const [expanded, setExpanded] = useState<Set<string>>(new Set());
  const [actionMenu, setActionMenu] = useState<string | null>(null);

  const toggleExpand = (id: string) => {
    setExpanded((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const isBalanced = (e: JournalEntry) => Math.abs(e.totalDebit - e.totalCredit) < 0.01;

  if (isPending) {
    return (
      <div className="bg-white rounded-xl border border-blue-100 p-16 text-center">
        <p className="text-gray-500 text-sm">جاري تحميل القيود...</p>
      </div>
    );
  }

  if (isError) {
    return (
      <div className="bg-white rounded-xl border border-red-100 p-16 text-center">
        <p className="text-red-500 text-sm">حدث خطأ أثناء تحميل القيود. {errorMessage ?? ""}</p>
      </div>
    );
  }

  if (entries.length === 0) {
    return (
      <div className="bg-white rounded-xl border border-blue-100 p-16 text-center">
        <div className="w-16 h-16 flex items-center justify-center mx-auto rounded-full bg-blue-50 text-blue-300 mb-4">
          <i className="ri-inbox-line text-3xl" />
        </div>
        <p className="text-gray-500 text-sm">لا توجد قيود تطابق الفلاتر المحددة</p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl border border-blue-100 overflow-hidden">
      <div className="px-5 py-3 border-b border-blue-50 flex items-center justify-between">
        <h3 className="font-semibold text-gray-700 text-sm flex items-center gap-2">
          <i className="ri-book-2-line text-blue-500" />
          سجل القيود المحاسبية
        </h3>
        <div className="flex items-center gap-2 text-xs text-gray-400">
          <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-full bg-green-400 inline-block" />معتمد</span>
          <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-full bg-amber-400 inline-block" />مسودة</span>
          <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-full bg-red-400 inline-block" />ملغي</span>
        </div>
      </div>
      <div className="divide-y divide-gray-50">
        {entries.map((entry) => {
          const isOpen = expanded.has(entry.id);
          const balanced = isBalanced(entry);
          return (
            <div key={entry.id} className="group">
              <div
                className={`flex items-center gap-3 px-5 py-3.5 cursor-pointer hover:bg-blue-50/40 transition-colors ${isOpen ? "bg-blue-50/60" : ""}`}
                onClick={() => toggleExpand(entry.id)}
              >
                <div className="w-7 h-7 flex items-center justify-center text-gray-400">
                  <i className={`text-sm transition-transform duration-200 ${isOpen ? "ri-arrow-down-s-line rotate-0" : "ri-arrow-left-s-line"}`} />
                </div>
                <span className={`w-2 h-2 rounded-full shrink-0 ${entry.status === "معتمد" ? "bg-green-400" : entry.status === "مسودة" ? "bg-amber-400" : "bg-red-400"}`} />
                <span className="font-mono text-sm text-blue-700 font-semibold min-w-[120px]">{entry.entryNumber}</span>
                <span className="text-sm text-gray-500 min-w-[110px]">{formatDate(entry.date)}</span>
                <span className="text-sm text-gray-700 flex-1 truncate">{entry.description}</span>
                <span className="text-xs text-gray-400 min-w-[90px] text-center">{entry.branch}</span>
                <span className={`text-xs px-2 py-0.5 rounded-full border whitespace-nowrap ${typeColors[entry.type] || "bg-gray-50 text-gray-500"}`}>{entry.type}</span>
                <span className="text-xs text-gray-400 min-w-[50px] text-center">{entry.lines.length} سطر</span>
                <span className="text-sm font-semibold text-red-500 min-w-[110px] text-left" dir="ltr">{fmt(entry.totalDebit)}</span>
                <span className="text-sm font-semibold text-green-600 min-w-[110px] text-left" dir="ltr">{fmt(entry.totalCredit)}</span>
                <div className={`w-6 h-6 flex items-center justify-center rounded-full ${balanced ? "bg-green-100 text-green-500" : "bg-red-100 text-red-500"}`}>
                  <i className={`text-xs ${balanced ? "ri-check-line" : "ri-close-line"}`} />
                </div>
                <span className={`text-xs px-2 py-0.5 rounded-full whitespace-nowrap ${statusColors[entry.status]}`}>{entry.status}</span>
                <div className="relative" onClick={(e) => e.stopPropagation()}>
                  <button className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-100 text-gray-400 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity" onClick={() => setActionMenu(actionMenu === entry.id ? null : entry.id)}>
                    <i className="ri-more-2-line text-sm" />
                  </button>
                  {actionMenu === entry.id && (
                    <div className="absolute left-0 top-full mt-1 bg-white rounded-xl border border-gray-100 py-1 z-20 min-w-[160px]" style={{ boxShadow: "0 4px 20px rgba(0,0,0,0.08)" }}>
                      {entry.status === "مسودة" && (
                        <button className="flex items-center gap-2 px-4 py-2.5 text-sm text-green-600 hover:bg-green-50 w-full text-right cursor-pointer" onClick={() => { onApprove(entry.id); setActionMenu(null); }}>
                          <i className="ri-check-double-line" />اعتماد القيد
                        </button>
                      )}
                      <button className="flex items-center gap-2 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 w-full text-right cursor-pointer" onClick={() => { onDelete(entry.id); setActionMenu(null); }}>
                        <i className="ri-delete-bin-line" />حذف
                      </button>
                    </div>
                  )}
                </div>
              </div>
              {isOpen && (
                <div className="bg-blue-50/30 border-t border-blue-100 px-6 py-4 animate-[fadeIn_0.2s_ease]">
                  <div className="flex flex-wrap gap-4 mb-4 text-xs text-gray-500">
                    {entry.reference && (
                      <span className="flex items-center gap-1">
                        <i className="ri-link text-blue-400" />
                        مرجع: <span className="text-blue-600 font-mono">{entry.reference}</span>
                      </span>
                    )}
                    <span className="flex items-center gap-1">
                      <i className="ri-user-line text-blue-400" />
                      أنشأ بواسطة: <span className="text-gray-700">{entry.createdBy}</span>
                    </span>
                    {entry.approvedBy && (
                      <span className="flex items-center gap-1">
                        <i className="ri-shield-check-line text-green-500" />
                        اعتمد بواسطة: <span className="text-gray-700">{entry.approvedBy}</span>
                      </span>
                    )}
                    {entry.notes && (
                      <span className="flex items-center gap-1">
                        <i className="ri-sticky-note-line text-amber-400" />
                        {entry.notes}
                      </span>
                    )}
                  </div>

                  <div className="rounded-xl overflow-hidden border border-blue-100">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="bg-blue-900 text-white text-xs">
                          <th className="px-4 py-2.5 text-right font-medium w-[100px]">كود الحساب</th>
                          <th className="px-4 py-2.5 text-right font-medium">اسم الحساب</th>
                          <th className="px-4 py-2.5 text-right font-medium">البيان</th>
                          <th className="px-4 py-2.5 text-center font-medium text-red-200 w-[140px]">مدين ﴿Dr.﴾</th>
                          <th className="px-4 py-2.5 text-center font-medium text-green-200 w-[140px]">دائن ﴿Cr.﴾</th>
                        </tr>
                      </thead>
                      <tbody>
                        {entry.lines.map((line, idx) => (
                          <tr key={line.id} className={`border-b border-blue-50 ${idx % 2 === 0 ? "bg-white" : "bg-blue-50/20"}`}>
                            <td className="px-4 py-3">
                              <span className="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{line.accountCode}</span>
                            </td>
                            <td className="px-4 py-3 font-medium text-gray-700 text-sm">
                              {line.credit > 0 && <span className="text-gray-300 mr-2">--</span>}
                              {line.account}
                            </td>
                            <td className="px-4 py-3 text-gray-500 text-xs">{line.description}</td>
                            <td className="px-4 py-3 text-center">
                              {line.debit > 0 ? (
                                <span className="text-red-500 font-bold text-sm">{fmt(line.debit)}</span>
                              ) : (
                                <span className="text-gray-200">-</span>
                              )}
                            </td>
                            <td className="px-4 py-3 text-center">
                              {line.credit > 0 ? (
                                <span className="text-green-600 font-bold text-sm">{fmt(line.credit)}</span>
                              ) : (
                                <span className="text-gray-200">-</span>
                              )}
                            </td>
                          </tr>
                        ))}
                        <tr className="bg-blue-900 text-white font-bold text-sm">
                          <td colSpan={3} className="px-4 py-3 text-right text-blue-200 text-xs">
                            الإجماليات
                          </td>
                          <td className="px-4 py-3 text-center text-red-200">{fmt(entry.totalDebit)}</td>
                          <td className="px-4 py-3 text-center text-green-200">{fmt(entry.totalCredit)}</td>
                        </tr>
                        <tr className={`text-xs ${balanced ? "bg-green-50" : "bg-red-50"}`}>
                          <td colSpan={5} className={`px-4 py-2.5 text-center font-medium ${balanced ? "text-green-600" : "text-red-500"}`}>
                            {balanced ? (
                              <span>
                                <i className="ri-check-double-line ml-1" />
                                القيد متوازن - مدين = دائن = {fmt(entry.totalDebit)} ج.م
                              </span>
                            ) : (
                              <span>
                                <i className="ri-error-warning-line ml-1" />
                                تحذير: القيد غير متوازن - الفرق = {fmt(Math.abs(entry.totalDebit - entry.totalCredit))} ج.م
                              </span>
                            )}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          );
        })}
      </div>

      <div className="bg-blue-900 text-white px-5 py-3 flex items-center justify-between text-sm">
        <span className="text-blue-200 text-xs">{entries.length} قيد إجمالي</span>
        <div className="flex items-center gap-8">
          <div className="text-center">
            <p className="text-blue-300 text-xs">إجمالي المدين</p>
            <p className="text-red-200 font-bold">
              {fmt(entries.reduce((s, e) => s + e.totalDebit, 0))} ج.م
            </p>
          </div>
          <div className="text-center">
            <p className="text-blue-300 text-xs">إجمالي الدائن</p>
            <p className="text-green-200 font-bold">
              {fmt(entries.reduce((s, e) => s + e.totalCredit, 0))} ج.م
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
