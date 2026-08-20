import { useEffect, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import {
  APP_SETTINGS_KEY,
  useAppSettingsQueryOptions,
  useUpdateAppSettingsMutationOptions,
} from "@/api/v2/settings/settings.hooks";
import type { TCompanySettings } from "@/api/v2/settings/settings.types";

const inputClass =
  "w-full px-4 py-2.5 rounded-xl text-sm text-slate-700 outline-none";
const inputStyle = { background: "#F8FAFC", border: "1.5px solid #E2E8F0" } as const;

const EMPTY: TCompanySettings = {
  name: "",
  phone: "",
  email: "",
  address: "",
  tax_number: "",
  commercial_register: "",
};

export default function CompanySettingsTab() {
  const queryClient = useQueryClient();
  const { data, isPending, isError, error, refetch } = useQuery(
    useAppSettingsQueryOptions()
  );
  const { mutate, isPending: saving } = useMutation(
    useUpdateAppSettingsMutationOptions()
  );
  const [draft, setDraft] = useState<TCompanySettings>(EMPTY);

  useEffect(() => {
    if (data?.company) setDraft({ ...EMPTY, ...data.company });
  }, [data]);

  const save = () => {
    mutate(
      { company: draft },
      {
        onSuccess: (next) => {
          queryClient.setQueryData(APP_SETTINGS_KEY, next);
          toast.success("تم حفظ بيانات الشركة");
        },
        onError: (err: Error) => {
          toast.error(err.message || "تعذر الحفظ");
        },
      }
    );
  };

  if (isPending) {
    return (
      <div className="flex min-h-[30vh] items-center justify-center gap-2 text-slate-400">
        <Loader2 className="h-6 w-6 animate-spin" />
        <span className="text-sm">جاري التحميل...</span>
      </div>
    );
  }

  if (isError) {
    return (
      <div className="rounded-2xl border border-rose-100 bg-rose-50 p-5 text-sm text-rose-700">
        <p className="font-bold mb-2">تعذر تحميل بيانات الشركة</p>
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

  const fields: { key: keyof TCompanySettings; label: string; multiline?: boolean }[] = [
    { key: "name", label: "اسم النشاط / الشركة" },
    { key: "phone", label: "هاتف التواصل" },
    { key: "email", label: "البريد الإلكتروني" },
    { key: "address", label: "العنوان", multiline: true },
    { key: "tax_number", label: "الرقم الضريبي" },
    { key: "commercial_register", label: "السجل التجاري" },
  ];

  return (
    <div className="space-y-5 max-w-2xl">
      <p className="text-xs text-slate-500">
        بيانات الشركة تظهر على المستندات والطباعة عند تفعيلها من إعدادات الفواتير.
      </p>
      <div className="rounded-2xl border border-slate-100 bg-white p-5 space-y-4">
        {fields.map((f) => (
          <label key={f.key} className="block space-y-1.5">
            <span className="text-xs font-bold text-slate-600">{f.label}</span>
            {f.multiline ? (
              <textarea
                rows={3}
                className={inputClass}
                style={inputStyle}
                value={draft[f.key]}
                onChange={(e) =>
                  setDraft((d) => ({ ...d, [f.key]: e.target.value }))
                }
              />
            ) : (
              <input
                className={inputClass}
                style={inputStyle}
                value={draft[f.key]}
                onChange={(e) =>
                  setDraft((d) => ({ ...d, [f.key]: e.target.value }))
                }
              />
            )}
          </label>
        ))}
        <div className="pt-2 flex justify-end">
          <button
            type="button"
            disabled={saving}
            onClick={save}
            className="px-5 py-2.5 rounded-xl text-sm font-bold text-white disabled:opacity-60"
            style={{ background: "#0C1A3E" }}
          >
            {saving ? "جارٍ الحفظ..." : "حفظ بيانات الشركة"}
          </button>
        </div>
      </div>
    </div>
  );
}
