import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import {
  useGetBranchesQueryOptions,
  useUpdateBranchMutationOptions,
} from "@/api/v2/branches/branches.hooks";
import { useCurrenciesQueryOptions } from "@/api/v2/content-managment/currency/currency.hooks";
import type { TBranchResponse } from "@/api/v2/branches/branches.types";
import type { TCurrency } from "@/api/v2/content-managment/currency/currency.types";

type BranchDraft = {
  vat_enabled: boolean;
  vat_value: number;
  currency_id: number | null;
};

function resolveCurrencyId(
  branch: TBranchResponse,
  currencies: TCurrency[]
): number | null {
  const anyBranch = branch as TBranchResponse & { currency_id?: number | null };
  if (anyBranch.currency_id != null) return anyBranch.currency_id;
  const code = branch.currency_code?.trim();
  if (!code) return currencies[0]?.id ?? null;
  const found = currencies.find(
    (c) => c.code.toLowerCase() === code.toLowerCase()
  );
  return found?.id ?? currencies[0]?.id ?? null;
}

export default function BranchSettingsTab() {
  const { data: branchesRes, isPending: branchesLoading } = useQuery(
    useGetBranchesQueryOptions(1, 100)
  );
  const { data: currenciesRes, isPending: currenciesLoading } = useQuery(
    useCurrenciesQueryOptions(1, 200)
  );

  const currencies = currenciesRes?.data ?? [];
  const branches = branchesRes?.data ?? [];

  const [drafts, setDrafts] = useState<Record<number, BranchDraft>>({});
  const [savingId, setSavingId] = useState<number | null>(null);

  useEffect(() => {
    if (!branches.length) return;
    setDrafts((prev) => {
      const next = { ...prev };
      let changed = false;
      for (const b of branches) {
        const existing = next[b.id];
        const resolved = resolveCurrencyId(b, currencies);
        if (!existing) {
          next[b.id] = {
            vat_enabled: Boolean(b.vat_enabled),
            vat_value:
              b.vat_value != null && !Number.isNaN(Number(b.vat_value))
                ? Number(b.vat_value)
                : 14,
            currency_id: resolved,
          };
          changed = true;
        } else if (
          existing.currency_id == null &&
          resolved != null &&
          currencies.length > 0
        ) {
          next[b.id] = { ...existing, currency_id: resolved };
          changed = true;
        }
      }
      return changed ? next : prev;
    });
  }, [branches, currencies]);

  const { mutate: updateBranch } = useMutation(useUpdateBranchMutationOptions());

  const handleSave = (branch: TBranchResponse) => {
    const d = drafts[branch.id];
    if (!d) return;
    setSavingId(branch.id);
    updateBranch(
      {
        id: branch.id,
        data: {
          vat_enabled: d.vat_enabled,
          vat_type: d.vat_enabled ? "percentage" : null,
          vat_value: d.vat_enabled ? d.vat_value : null,
          currency_id: d.currency_id,
        },
      },
      {
        onSuccess: () => {
          toast.success("تم حفظ إعدادات الفرع", {
            description: branch.name,
          });
        },
        onError: (e: Error) => {
          toast.error(e.message ?? "تعذر الحفظ");
        },
        onSettled: () => setSavingId(null),
      }
    );
  };

  const vatOnCount = useMemo(
    () => branches.filter((b) => drafts[b.id]?.vat_enabled).length,
    [branches, drafts]
  );

  if (branchesLoading || currenciesLoading) {
    return (
      <div className="flex justify-center py-20 text-slate-400">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div
        className="rounded-2xl p-5 flex items-center gap-4"
        style={{
          background: "linear-gradient(135deg, #0C1A3E 0%, #1E3A7B 100%)",
        }}
      >
        <div
          className="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
          style={{ background: "rgba(194,150,74,0.2)" }}
        >
          <i className="ri-map-pin-2-line text-xl" style={{ color: "#E8BF7A" }} />
        </div>
        <div>
          <h3 className="text-white font-black text-base">إعدادات الفروع</h3>
          <p className="text-white/50 text-xs mt-0.5">
            ضريبة القيمة المضافة والعملة لكل فرع — مربوط بـ API الفروع
          </p>
        </div>
        <div className="mr-auto flex items-center gap-3 text-center">
          <div>
            <p className="text-white/40 text-xs">ضريبة مفعّلة</p>
            <p className="text-white font-black text-xl">{vatOnCount}</p>
          </div>
          <div
            className="w-px h-8"
            style={{ background: "rgba(255,255,255,0.12)" }}
          />
          <div>
            <p className="text-white/40 text-xs">إجمالي الفروع</p>
            <p className="text-white font-black text-xl">{branches.length}</p>
          </div>
        </div>
      </div>

      <div
        className="rounded-xl p-4 flex items-start gap-3"
        style={{ background: "#FFFBEB", border: "1px solid #FDE68A" }}
      >
        <i className="ri-information-line mt-0.5" style={{ color: "#D97706" }} />
        <p className="text-xs" style={{ color: "#78350F" }}>
          تُطبّق نسبة الضريبة على الفواتير الجديدة حسب إعدادات الخادم. التغييرات لا تُلغى الفواتير السابقة.
        </p>
      </div>

      {currencies.length === 0 && branches.length > 0 ? (
        <div
          className="rounded-xl p-4 flex items-start gap-3"
          style={{ background: "#FEF3C7", border: "1px solid #FCD34D" }}
        >
          <i className="ri-error-warning-line mt-0.5 text-amber-700" />
          <p className="text-xs text-amber-900">
            لا توجد عملات في النظام. أضف عملة من تبويب «العملات» قبل ربط عملة الفرع.
          </p>
        </div>
      ) : null}

      {branches.length === 0 ? (
        <p className="text-sm text-slate-500 text-center py-12">لا توجد فروع لعرضها.</p>
      ) : (
        branches.map((branch) => {
          const d = drafts[branch.id];
          if (!d) return null;
          const cityLabel = branch.address?.city?.name ?? "—";
          return (
            <div
              key={branch.id}
              className="rounded-2xl bg-white border border-slate-100 overflow-hidden"
            >
              <div
                className="px-5 py-4 flex items-center gap-3"
                style={{
                  background: "#F8FAFC",
                  borderBottom: "1px solid #EEF2F8",
                }}
              >
                <div
                  className="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                  style={{ background: "#0C1A3E" }}
                >
                  <i className="ri-map-pin-2-line text-sm text-white" />
                </div>
                <div className="flex-1 min-w-0">
                  <span className="font-black text-slate-800 text-sm">
                    {branch.name}
                  </span>
                  <p className="text-xs text-slate-400 mt-0.5 truncate">
                    {cityLabel} · {branch.branch_code}
                  </p>
                </div>
              </div>
              <div className="p-5">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-5 items-end">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-3">
                      ضريبة القيمة المضافة
                    </label>
                    <div className="flex items-center gap-3">
                      <button
                        type="button"
                        onClick={() =>
                          setDrafts((p) => ({
                            ...p,
                            [branch.id]: {
                              ...p[branch.id]!,
                              vat_enabled: !p[branch.id]!.vat_enabled,
                            },
                          }))
                        }
                        className="relative w-12 h-6 rounded-full transition-all duration-200 flex-shrink-0"
                        style={{
                          background: d.vat_enabled ? "#22C55E" : "#CBD5E1",
                        }}
                      >
                        <span
                          className="absolute top-1 w-4 h-4 rounded-full bg-white transition-all duration-200"
                          style={{
                            right: d.vat_enabled ? "4px" : "calc(100% - 20px)",
                          }}
                        />
                      </button>
                      <span
                        className={`text-sm font-bold ${d.vat_enabled ? "text-green-600" : "text-slate-400"}`}
                      >
                        {d.vat_enabled ? "مفعّلة" : "معطّلة"}
                      </span>
                    </div>
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1.5">
                      نسبة الضريبة %
                    </label>
                    <input
                      type="number"
                      min={0}
                      max={100}
                      step={0.5}
                      value={d.vat_value}
                      disabled={!d.vat_enabled}
                      onChange={(e) =>
                        setDrafts((p) => ({
                          ...p,
                          [branch.id]: {
                            ...p[branch.id]!,
                            vat_value: parseFloat(e.target.value) || 0,
                          },
                        }))
                      }
                      className="w-full px-4 py-2.5 rounded-xl text-sm font-bold outline-none border"
                      style={{
                        background: d.vat_enabled ? "#F8FAFC" : "#F1F5F9",
                        borderColor: "#E2E8F0",
                        color: d.vat_enabled ? "#1E293B" : "#94A3B8",
                      }}
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1.5">
                      عملة الفرع
                    </label>
                    <select
                      value={d.currency_id ?? ""}
                      disabled={currencies.length === 0}
                      onChange={(e) =>
                        setDrafts((p) => ({
                          ...p,
                          [branch.id]: {
                            ...p[branch.id]!,
                            currency_id: e.target.value
                              ? Number(e.target.value)
                              : null,
                          },
                        }))
                      }
                      className="w-full px-4 py-2.5 rounded-xl text-sm font-semibold outline-none border border-slate-200 bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                      {currencies.length === 0 ? (
                        <option value="">— لا توجد عملات —</option>
                      ) : (
                        currencies.map((c) => (
                          <option key={c.id} value={c.id}>
                            {c.name} ({c.code})
                          </option>
                        ))
                      )}
                    </select>
                  </div>
                </div>
                <div className="flex items-center justify-end mt-4 pt-4 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => handleSave(branch)}
                    disabled={
                      savingId === branch.id ||
                      currencies.length === 0 ||
                      d.currency_id == null
                    }
                    className="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white disabled:opacity-50"
                    style={{ background: "#0C1A3E" }}
                  >
                    {savingId === branch.id ? (
                      <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                      <i className="ri-save-line" />
                    )}
                    حفظ الإعدادات
                  </button>
                </div>
              </div>
            </div>
          );
        })
      )}
    </div>
  );
}
