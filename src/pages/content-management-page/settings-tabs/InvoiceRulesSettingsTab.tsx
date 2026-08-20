import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import {
  APP_SETTINGS_KEY,
  useAppSettingsQueryOptions,
  useUpdateAppSettingsMutationOptions,
} from "@/api/v2/settings/settings.hooks";
import type {
  TInvoiceRuleKey,
  TInvoiceSettings,
  TInvoiceTemplate,
} from "@/api/v2/settings/settings.types";

type DocGroup = {
  id: string;
  label: string;
  color: string;
  customerKey: TInvoiceRuleKey;
  workshopKey: TInvoiceRuleKey;
};

const DOC_GROUPS: DocGroup[] = [
  {
    id: "rental",
    label: "الإيجار",
    color: "#166534",
    customerKey: "rental_customer",
    workshopKey: "rental_workshop",
  },
  {
    id: "sale",
    label: "البيع",
    color: "#1E40AF",
    customerKey: "sale_customer",
    workshopKey: "sale_workshop",
  },
  {
    id: "tailoring",
    label: "التفصيل",
    color: "#92400E",
    customerKey: "tailoring_customer",
    workshopKey: "tailoring_workshop",
  },
];

const TEMPLATES: { id: TInvoiceTemplate; label: string }[] = [
  { id: "premium", label: "فاخر" },
  { id: "classic", label: "كلاسيك" },
  { id: "compact", label: "مختصر" },
];

const emptyRules = (): Record<TInvoiceRuleKey, string[]> => ({
  rental_customer: [],
  rental_workshop: [],
  sale_customer: [],
  sale_workshop: [],
  tailoring_customer: [],
  tailoring_workshop: [],
});

export default function InvoiceRulesSettingsTab() {
  const queryClient = useQueryClient();
  const { data, isPending, isError, error, refetch } = useQuery(
    useAppSettingsQueryOptions()
  );
  const { mutate, isPending: saving } = useMutation(
    useUpdateAppSettingsMutationOptions()
  );

  const [invoice, setInvoice] = useState<TInvoiceSettings | null>(null);
  const [activeGroup, setActiveGroup] = useState(DOC_GROUPS[0].id);
  const [side, setSide] = useState<"customer" | "workshop">("customer");
  const [editingIdx, setEditingIdx] = useState<number | null>(null);
  const [editText, setEditText] = useState("");

  useEffect(() => {
    if (!data?.invoice) return;
    setInvoice({
      ...data.invoice,
      rules: { ...emptyRules(), ...(data.invoice.rules ?? {}) },
    });
  }, [data]);

  const group = useMemo(
    () => DOC_GROUPS.find((g) => g.id === activeGroup) ?? DOC_GROUPS[0],
    [activeGroup]
  );

  const ruleKey: TInvoiceRuleKey =
    side === "customer" ? group.customerKey : group.workshopKey;
  const items = invoice?.rules?.[ruleKey] ?? [];

  const persist = (next: TInvoiceSettings, msg = "تم حفظ إعدادات الفواتير") => {
    setInvoice(next);
    mutate(
      { invoice: next },
      {
        onSuccess: (res) => {
          queryClient.setQueryData(APP_SETTINGS_KEY, res);
          toast.success(msg);
        },
        onError: (err: Error) => toast.error(err.message || "تعذر الحفظ"),
      }
    );
  };

  const updateItems = (nextItems: string[]) => {
    if (!invoice) return;
    persist({
      ...invoice,
      rules: { ...invoice.rules, [ruleKey]: nextItems },
    });
  };

  if (isPending || !invoice) {
    return (
      <div className="flex min-h-[30vh] items-center justify-center gap-2 text-slate-400">
        <Loader2 className="h-6 w-6 animate-spin" />
        <span className="text-sm">جاري تحميل قواعد الفواتير...</span>
      </div>
    );
  }

  if (isError) {
    return (
      <div className="rounded-2xl border border-rose-100 bg-rose-50 p-5 text-sm text-rose-700">
        <p className="font-bold mb-2">تعذر تحميل إعدادات الفواتير</p>
        <p className="text-xs mb-3 opacity-80">{(error as Error)?.message}</p>
        <button
          type="button"
          onClick={() => void refetch()}
          className="px-4 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold"
        >
          إعادة المحاولة
        </button>
      </div>
    );
  }

  const toggles: { key: keyof TInvoiceSettings; label: string }[] = [
    { key: "show_logo", label: "إظهار الشعار" },
    { key: "show_tax", label: "إظهار الضريبة" },
    { key: "show_discount", label: "إظهار الخصم" },
    { key: "show_customer_rules", label: "إظهار شروط العميل" },
    { key: "show_workshop_notes", label: "إظهار ملاحظات الورشة" },
  ];

  return (
    <div className="space-y-5">
      <div className="rounded-2xl border border-slate-100 bg-white p-5 space-y-4">
        <h3 className="text-sm font-black text-slate-800">خيارات الطباعة</h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
          {toggles.map((t) => (
            <label
              key={t.key}
              className="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700"
            >
              {t.label}
              <input
                type="checkbox"
                checked={Boolean(invoice[t.key])}
                onChange={(e) =>
                  persist(
                    { ...invoice, [t.key]: e.target.checked },
                    "تم تحديث خيارات الطباعة"
                  )
                }
              />
            </label>
          ))}
        </div>

        <label className="block space-y-1.5">
          <span className="text-xs font-bold text-slate-600">قالب الفاتورة</span>
          <select
            className="w-full px-4 py-2.5 rounded-xl text-sm"
            style={{ background: "#F8FAFC", border: "1.5px solid #E2E8F0" }}
            value={invoice.template}
            onChange={(e) =>
              persist({
                ...invoice,
                template: e.target.value as TInvoiceTemplate,
              })
            }
          >
            {TEMPLATES.map((t) => (
              <option key={t.id} value={t.id}>
                {t.label}
              </option>
            ))}
          </select>
        </label>

        <label className="block space-y-1.5">
          <span className="text-xs font-bold text-slate-600">نص تذييل الفاتورة</span>
          <input
            className="w-full px-4 py-2.5 rounded-xl text-sm"
            style={{ background: "#F8FAFC", border: "1.5px solid #E2E8F0" }}
            value={invoice.footer_text}
            onChange={(e) =>
              setInvoice({ ...invoice, footer_text: e.target.value })
            }
            onBlur={() => persist(invoice, "تم حفظ نص التذييل")}
          />
        </label>
      </div>

      <div className="flex flex-wrap gap-2">
        {DOC_GROUPS.map((g) => (
          <button
            key={g.id}
            type="button"
            onClick={() => {
              setActiveGroup(g.id);
              setEditingIdx(null);
            }}
            className="px-3 py-2 rounded-xl text-xs font-bold"
            style={
              activeGroup === g.id
                ? { background: `${g.color}18`, color: g.color, border: `1px solid ${g.color}40` }
                : { background: "#fff", color: "#64748B", border: "1px solid #EEF2F8" }
            }
          >
            {g.label}
          </button>
        ))}
      </div>

      <div className="flex gap-2">
        {(
          [
            { id: "customer" as const, label: "شروط العميل" },
            { id: "workshop" as const, label: "ملاحظات الورشة" },
          ] as const
        ).map((s) => (
          <button
            key={s.id}
            type="button"
            onClick={() => {
              setSide(s.id);
              setEditingIdx(null);
            }}
            className="px-3 py-2 rounded-xl text-xs font-bold"
            style={
              side === s.id
                ? { background: "#0C1A3E", color: "#fff" }
                : { background: "#F1F5F9", color: "#64748B" }
            }
          >
            {s.label}
          </button>
        ))}
      </div>

      <div className="rounded-2xl border border-slate-100 bg-white p-5 space-y-3">
        <div className="flex items-center justify-between gap-3">
          <h3 className="text-sm font-black text-slate-800">
            {group.label} — {side === "customer" ? "العميل" : "الورشة"}
          </h3>
          <button
            type="button"
            disabled={saving}
            onClick={() => updateItems([...items, "شرط جديد"])}
            className="px-3 py-2 rounded-xl text-xs font-bold text-white"
            style={{ background: group.color }}
          >
            إضافة بند
          </button>
        </div>

        {items.length === 0 ? (
          <p className="text-xs text-slate-400">لا توجد بنود بعد.</p>
        ) : (
          <ul className="space-y-2">
            {items.map((item, idx) => (
              <li
                key={`${ruleKey}-${idx}`}
                className="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5"
              >
                {editingIdx === idx ? (
                  <div className="space-y-2">
                    <textarea
                      rows={2}
                      className="w-full rounded-lg text-sm px-3 py-2"
                      style={{ border: "1px solid #E2E8F0" }}
                      value={editText}
                      onChange={(e) => setEditText(e.target.value)}
                    />
                    <div className="flex gap-2">
                      <button
                        type="button"
                        className="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600"
                        onClick={() => {
                          const next = [...items];
                          next[idx] = editText.trim();
                          if (!next[idx]) next.splice(idx, 1);
                          updateItems(next);
                          setEditingIdx(null);
                        }}
                      >
                        حفظ
                      </button>
                      <button
                        type="button"
                        className="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 bg-white border"
                        onClick={() => setEditingIdx(null)}
                      >
                        إلغاء
                      </button>
                    </div>
                  </div>
                ) : (
                  <div className="flex items-start gap-2">
                    <p className="flex-1 text-sm text-slate-700">{item}</p>
                    <button
                      type="button"
                      className="text-xs font-bold text-indigo-600"
                      onClick={() => {
                        setEditingIdx(idx);
                        setEditText(item);
                      }}
                    >
                      تعديل
                    </button>
                    <button
                      type="button"
                      className="text-xs font-bold text-rose-600"
                      onClick={() => updateItems(items.filter((_, i) => i !== idx))}
                    >
                      حذف
                    </button>
                  </div>
                )}
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}
