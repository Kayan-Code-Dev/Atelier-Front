import { useState, useEffect } from 'react';

type DocType = 'customer' | 'admin' | 'receipt';

interface RuleSet {
  title: string;
  items: string[];
}

interface InvoiceRulesState {
  customer: RuleSet;
  admin: RuleSet;
  receipt: RuleSet;
}

const STORAGE_KEY = "dressnmore_invoice_rules_v1";

const defaultRules: InvoiceRulesState = {
  customer: {
    title: 'الشروط والأحكام',
    items: [
      'يُعدّ توقيع العميل على هذه الفاتورة إقراراً بصحة البيانات واستلام القطع كاملةً وسليمةً.',
      'يلتزم العميل بإعادة القطع في التاريخ المحدد، وأي تأخير يستوجب غرامة يومية وفق السياسة المعتمدة.',
      'في حال تلف أي قطعة أو فقدانها يتحمل العميل كامل قيمة الإحلال المحددة من الإدارة.',
      'يُمنع تعديل أو خياطة أي من القطع المستأجرة دون إذن كتابي مسبق.',
      'لا تُسترد المبالغ المدفوعة إلا في حالات الإلغاء المبكر وفق شروط سياسة الإلغاء المعتمدة.',
    ],
  },
  admin: {
    title: 'ملاحظات إدارية داخلية',
    items: [
      'هذه النسخة سرية ومخصصة للاستخدام الداخلي فقط — يُحظر مشاركتها مع العميل.',
      'يجب التحقق من هوية العميل وتوثيق حالة القطع فور الاستلام والإرجاع.',
      'في حال وجود تلف أو نقص يُرفع تقرير فوري للمدير المسؤول مع توثيق بالصور.',
      'لا تُغلق الفاتورة ماليًا إلا بعد استلام القطع كاملةً وتسوية المبلغ المتبقي.',
      'يجب أرشفة هذه النسخة في ملف العميل فور إغلاق الفاتورة.',
    ],
  },
  receipt: {
    title: 'شروط إقرار الاستلام',
    items: [
      'يُقرّ العميل باستلام جميع القطع المذكورة أعلاه كاملةً وسليمةً وخاليةً من أي عيوب.',
      'يتعهد العميل بعدم إخراج القطع عن حدود المناسبة المُصرّح بها دون إذن مسبق.',
      'التأخر عن موعد الإرجاع المحدد يستوجب غرامة يومية قدرها 5% من قيمة الفاتورة.',
      'يُلزم العميل بالإبلاغ الفوري عن أي حادثة تتعلق بالقطع المستأجرة.',
      'يُعتبر هذا الإقرار وثيقةً قانونيةً ملزمةً لكلا الطرفين.',
    ],
  },
};

const docConfig: { type: DocType; label: string; icon: string; color: string; bg: string; border: string; desc: string }[] = [
  {
    type: 'customer',
    label: 'فاتورة العميل',
    icon: 'ri-file-text-line',
    color: '#166534',
    bg: '#F0FDF4',
    border: '#BBF7D0',
    desc: 'الشروط والأحكام التي تظهر في فاتورة العميل',
  },
  {
    type: 'admin',
    label: 'النسخة الإدارية',
    icon: 'ri-file-shield-2-line',
    color: '#92400E',
    bg: '#FEF3C7',
    border: '#FDE68A',
    desc: 'الملاحظات الداخلية للاستخدام الإداري فقط',
  },
  {
    type: 'receipt',
    label: 'إقرار الاستلام',
    icon: 'ri-file-check-2-line',
    color: '#1E40AF',
    bg: '#EFF6FF',
    border: '#BFDBFE',
    desc: 'شروط إقرار الاستلام والتسليم',
  },
];

export function loadInvoiceRules(): InvoiceRulesState {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) return JSON.parse(raw) as InvoiceRulesState;
  } catch (e) {
    // ignore parse errors, fall back to defaults
    void e;
  }
  return defaultRules;
}

function saveInvoiceRules(rules: InvoiceRulesState) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(rules));
}

export default function InvoiceRulesSettingsTab() {
  const [rules, setRules] = useState<InvoiceRulesState>(() => loadInvoiceRules());
  const [activeDoc, setActiveDoc] = useState<DocType>('customer');
  const [editingIdx, setEditingIdx] = useState<number | null>(null);
  const [editText, setEditText] = useState('');
  const [newRule, setNewRule] = useState('');
  const [showAdd, setShowAdd] = useState(false);
  const [savedFlash, setSavedFlash] = useState(false);
  const [editTitle, setEditTitle] = useState(false);
  const [titleText, setTitleText] = useState('');

  const cfg = docConfig.find((d) => d.type === activeDoc)!;
  const current = rules[activeDoc];

  useEffect(() => {
    setEditingIdx(null);
    setShowAdd(false);
    setNewRule('');
    setEditTitle(false);
  }, [activeDoc]);

  const saveAll = (updated: InvoiceRulesState) => {
    setRules(updated);
    saveInvoiceRules(updated);
    setSavedFlash(true);
    setTimeout(() => setSavedFlash(false), 2500);
  };

  const updateItems = (items: string[]) => {
    const updated = { ...rules, [activeDoc]: { ...current, items } };
    saveAll(updated);
  };

  const handleEditStart = (idx: number) => {
    setEditingIdx(idx);
    setEditText(current.items[idx]);
    setShowAdd(false);
  };

  const handleEditSave = (idx: number) => {
    if (!editText.trim()) return;
    const items = [...current.items];
    items[idx] = editText.trim();
    updateItems(items);
    setEditingIdx(null);
  };

  const handleDelete = (idx: number) => {
    const items = current.items.filter((_, i) => i !== idx);
    updateItems(items);
  };

  const handleAddRule = () => {
    if (!newRule.trim()) return;
    updateItems([...current.items, newRule.trim()]);
    setNewRule('');
    setShowAdd(false);
  };

  const handleMoveUp = (idx: number) => {
    if (idx === 0) return;
    const items = [...current.items];
    [items[idx - 1], items[idx]] = [items[idx], items[idx - 1]];
    updateItems(items);
  };

  const handleMoveDown = (idx: number) => {
    if (idx === current.items.length - 1) return;
    const items = [...current.items];
    [items[idx], items[idx + 1]] = [items[idx + 1], items[idx]];
    updateItems(items);
  };

  const handleSaveTitle = () => {
    const updated = { ...rules, [activeDoc]: { ...current, title: titleText.trim() || current.title } };
    saveAll(updated);
    setEditTitle(false);
  };

  const handleResetToDefault = () => {
    const updated = { ...rules, [activeDoc]: defaultRules[activeDoc] };
    saveAll(updated);
  };

  return (
    <div className="space-y-5">
      <div
        className="rounded-xl p-4 flex items-start gap-3"
        style={{ background: "#FEF3C7", border: "1px solid #FDE68A" }}
      >
        <i className="ri-information-line mt-0.5 shrink-0" style={{ color: "#D97706" }} />
        <div>
          <p className="text-sm font-black" style={{ color: "#92400E" }}>
            واجهة تجريبية — غير مربوطة بالخادم
          </p>
          <p className="text-xs mt-1 leading-relaxed" style={{ color: "#78350F" }}>
            تُحفظ القواعد محلياً في المتصفح فقط (localStorage). عند جاهزية الـ API يمكن ربطها لاحقاً دون تغيير التصميم.
          </p>
        </div>
      </div>

      {/* Header banner */}
      <div
        className="rounded-2xl p-5 flex items-center gap-4"
        style={{ background: 'linear-gradient(135deg, #0C1A3E 0%, #1E3A7B 100%)' }}
      >
        <div
          className="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
          style={{ background: 'rgba(194,150,74,0.2)' }}
        >
          <i className="ri-file-list-3-line text-xl" style={{ color: '#E8BF7A' }} />
        </div>
        <div className="flex-1">
          <h3 className="text-white font-black text-base">قواعد الفواتير العامة</h3>
          <p className="text-white/50 text-xs mt-0.5">
            أضف وعدّل القواعد والشروط التي تظهر تلقائياً في كل نوع فاتورة عند الطباعة
          </p>
        </div>
        {savedFlash && (
          <div
            className="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold"
            style={{ background: '#D1FAE5', color: '#065F46' }}
          >
            <i className="ri-checkbox-circle-fill" />
            تم الحفظ
          </div>
        )}
      </div>

      {/* Doc type selector */}
      <div className="grid grid-cols-3 gap-3">
        {docConfig.map((doc) => (
          <button
            key={doc.type}
            onClick={() => setActiveDoc(doc.type)}
            className="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all duration-150 text-right"
            style={
              activeDoc === doc.type
                ? { background: doc.bg, borderColor: doc.border }
                : { background: '#FFFFFF', borderColor: '#EEF2F8' }
            }
          >
            <div
              className="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
              style={{
                background: activeDoc === doc.type ? doc.color : '#F1F5F9',
              }}
            >
              <i
                className={`${doc.icon} text-base`}
                style={{ color: activeDoc === doc.type ? '#FFFFFF' : '#94A3B8' }}
              />
            </div>
            <div className="min-w-0">
              <p
                className="text-sm font-black truncate"
                style={{ color: activeDoc === doc.type ? doc.color : '#475569' }}
              >
                {doc.label}
              </p>
              <p className="text-[10px] text-slate-400 mt-0.5 truncate">{rules[doc.type].items.length} قاعدة</p>
            </div>
          </button>
        ))}
      </div>

      {/* Rules editor card */}
      <div className="bg-white rounded-2xl border border-slate-100 overflow-hidden">
        {/* Card header */}
        <div
          className="px-5 py-4 flex items-center justify-between"
          style={{ background: cfg.bg, borderBottom: `1px solid ${cfg.border}` }}
        >
          <div className="flex items-center gap-3">
            <div
              className="w-9 h-9 rounded-xl flex items-center justify-center"
              style={{ background: cfg.color }}
            >
              <i className={`${cfg.icon} text-white text-base`} />
            </div>
            <div>
              {editTitle ? (
                <div className="flex items-center gap-2">
                  <input
                    type="text"
                    value={titleText}
                    onChange={(e) => setTitleText(e.target.value)}
                    onKeyDown={(e) => { if (e.key === 'Enter') handleSaveTitle(); if (e.key === 'Escape') setEditTitle(false); }}
                    className="text-sm font-black border rounded-lg px-3 py-1 outline-none"
                    style={{ borderColor: cfg.color, color: cfg.color, background: '#FFFFFF' }}
                    autoFocus
                  />
                  <button
                    onClick={handleSaveTitle}
                    className="text-xs px-2 py-1 rounded-lg font-bold cursor-pointer"
                    style={{ background: cfg.color, color: '#FFF' }}
                  >
                    حفظ
                  </button>
                  <button
                    onClick={() => setEditTitle(false)}
                    className="text-xs px-2 py-1 rounded-lg cursor-pointer text-slate-500 hover:bg-slate-100"
                  >
                    إلغاء
                  </button>
                </div>
              ) : (
                <div className="flex items-center gap-2">
                  <span className="text-sm font-black" style={{ color: cfg.color }}>
                    {current.title}
                  </span>
                  <button
                    onClick={() => { setEditTitle(true); setTitleText(current.title); }}
                    className="w-5 h-5 flex items-center justify-center rounded cursor-pointer hover:bg-white/60 transition-colors"
                  >
                    <i className="ri-edit-line text-xs" style={{ color: cfg.color }} />
                  </button>
                </div>
              )}
              <p className="text-xs mt-0.5" style={{ color: cfg.color, opacity: 0.7 }}>
                {cfg.desc}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={handleResetToDefault}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer transition-colors whitespace-nowrap"
              style={{ background: 'rgba(255,255,255,0.7)', color: '#64748B', border: '1px solid #E2E8F0' }}
              onMouseEnter={(e) => { (e.currentTarget as HTMLElement).style.background = '#FFF'; }}
              onMouseLeave={(e) => { (e.currentTarget as HTMLElement).style.background = 'rgba(255,255,255,0.7)'; }}
            >
              <i className="ri-reset-left-line" />
              إعادة تعيين
            </button>
          </div>
        </div>

        {/* Rules list */}
        <div className="p-5 space-y-2.5">
          {current.items.length === 0 && (
            <div className="text-center py-10 text-slate-400">
              <i className="ri-file-list-line text-4xl block mb-2 opacity-30" />
              <p className="text-sm">لا توجد قواعد — أضف قاعدة جديدة</p>
            </div>
          )}

          {current.items.map((item, idx) => (
            <div
              key={idx}
              className="rounded-xl border transition-all duration-150"
              style={
                editingIdx === idx
                  ? { borderColor: cfg.color, background: cfg.bg }
                  : { borderColor: '#EEF2F8', background: '#F8FAFC' }
              }
            >
              {editingIdx === idx ? (
                <div className="p-3">
                  <textarea
                    value={editText}
                    onChange={(e) => setEditText(e.target.value)}
                    rows={3}
                    className="w-full text-sm rounded-lg px-3 py-2 outline-none resize-none"
                    style={{ border: `1.5px solid ${cfg.color}`, background: '#FFFFFF' }}
                    autoFocus
                  />
                  <div className="flex items-center gap-2 mt-2">
                    <button
                      onClick={() => handleEditSave(idx)}
                      className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer whitespace-nowrap"
                      style={{ background: cfg.color, color: '#FFF' }}
                    >
                      <i className="ri-save-line" />
                      حفظ التعديل
                    </button>
                    <button
                      onClick={() => setEditingIdx(null)}
                      className="px-3 py-1.5 rounded-lg text-xs text-slate-500 cursor-pointer hover:bg-slate-100 whitespace-nowrap"
                    >
                      إلغاء
                    </button>
                  </div>
                </div>
              ) : (
                <div className="flex items-start gap-3 p-3">
                  {/* Number badge */}
                  <div
                    className="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-black flex-shrink-0 mt-0.5"
                    style={{ background: cfg.color, color: '#FFF' }}
                  >
                    {idx + 1}
                  </div>
                  {/* Text */}
                  <p className="flex-1 text-sm text-slate-700 leading-relaxed">{item}</p>
                  {/* Actions */}
                  <div className="flex items-center gap-1 flex-shrink-0">
                    <button
                      onClick={() => handleMoveUp(idx)}
                      disabled={idx === 0}
                      className="w-7 h-7 flex items-center justify-center rounded-lg cursor-pointer text-slate-400 hover:bg-slate-200 transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                    >
                      <i className="ri-arrow-up-s-line text-sm" />
                    </button>
                    <button
                      onClick={() => handleMoveDown(idx)}
                      disabled={idx === current.items.length - 1}
                      className="w-7 h-7 flex items-center justify-center rounded-lg cursor-pointer text-slate-400 hover:bg-slate-200 transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                    >
                      <i className="ri-arrow-down-s-line text-sm" />
                    </button>
                    <button
                      onClick={() => handleEditStart(idx)}
                      className="w-7 h-7 flex items-center justify-center rounded-lg cursor-pointer transition-colors hover:bg-blue-50"
                      style={{ color: '#3B82F6' }}
                    >
                      <i className="ri-edit-line text-sm" />
                    </button>
                    <button
                      onClick={() => handleDelete(idx)}
                      className="w-7 h-7 flex items-center justify-center rounded-lg cursor-pointer transition-colors hover:bg-red-50"
                      style={{ color: '#EF4444' }}
                    >
                      <i className="ri-delete-bin-line text-sm" />
                    </button>
                  </div>
                </div>
              )}
            </div>
          ))}

          {/* Add new rule */}
          {showAdd ? (
            <div
              className="rounded-xl border-2 border-dashed p-4"
              style={{ borderColor: cfg.color, background: cfg.bg }}
            >
              <p className="text-xs font-bold mb-2" style={{ color: cfg.color }}>
                نص القاعدة الجديدة
              </p>
              <textarea
                value={newRule}
                onChange={(e) => setNewRule(e.target.value)}
                rows={3}
                placeholder="اكتب نص القاعدة هنا..."
                className="w-full text-sm rounded-lg px-3 py-2 outline-none resize-none"
                style={{ border: `1.5px solid ${cfg.color}`, background: '#FFFFFF' }}
                autoFocus
                maxLength={500}
              />
              <p className="text-xs text-slate-400 mt-1 text-left">{newRule.length}/500</p>
              <div className="flex items-center gap-2 mt-2">
                <button
                  onClick={handleAddRule}
                  disabled={!newRule.trim()}
                  className="flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold cursor-pointer transition-colors whitespace-nowrap disabled:opacity-50"
                  style={{ background: cfg.color, color: '#FFF' }}
                >
                  <i className="ri-add-line" />
                  إضافة القاعدة
                </button>
                <button
                  onClick={() => { setShowAdd(false); setNewRule(''); }}
                  className="px-4 py-2 rounded-lg text-xs text-slate-500 cursor-pointer hover:bg-slate-100 whitespace-nowrap"
                >
                  إلغاء
                </button>
              </div>
            </div>
          ) : (
            <button
              onClick={() => { setShowAdd(true); setEditingIdx(null); }}
              className="w-full flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-dashed text-sm font-bold cursor-pointer transition-all duration-150"
              style={{ borderColor: '#E2E8F0', color: '#94A3B8' }}
              onMouseEnter={(e) => {
                (e.currentTarget as HTMLElement).style.borderColor = cfg.color;
                (e.currentTarget as HTMLElement).style.color = cfg.color;
                (e.currentTarget as HTMLElement).style.background = cfg.bg;
              }}
              onMouseLeave={(e) => {
                (e.currentTarget as HTMLElement).style.borderColor = '#E2E8F0';
                (e.currentTarget as HTMLElement).style.color = '#94A3B8';
                (e.currentTarget as HTMLElement).style.background = 'transparent';
              }}
            >
              <i className="ri-add-circle-line text-base" />
              إضافة قاعدة جديدة
            </button>
          )}
        </div>

        {/* Footer info */}
        <div
          className="px-5 py-3 flex items-center justify-between"
          style={{ background: '#F8FAFC', borderTop: '1px solid #EEF2F8' }}
        >
          <div className="flex items-center gap-2 text-xs text-slate-400">
            <i className="ri-information-line" />
            <span>محلي فقط — لا تُرسل للخادم حتى يتوفر الـ API</span>
          </div>
          <span className="text-xs font-bold" style={{ color: cfg.color }}>
            {current.items.length} قاعدة
          </span>
        </div>
      </div>

      {/* Preview hint */}
      <div
        className="rounded-xl p-4 flex items-start gap-3"
        style={{ background: '#F8FAFC', border: '1px solid #EEF2F8' }}
      >
        <i className="ri-eye-line mt-0.5 text-slate-400" />
        <div>
          <p className="text-sm font-bold text-slate-600">معاينة التغييرات</p>
          <p className="text-xs text-slate-400 mt-0.5">
            بعد ربط الـ API يمكن عرض هذه القواعد في قوالب الطباعة؛ حالياً التعديل للمعاينة والتجربة فقط.
          </p>
        </div>
      </div>
    </div>
  );
}
