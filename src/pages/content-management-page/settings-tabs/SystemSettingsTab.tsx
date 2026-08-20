import { useEffect, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import {
  APP_SETTINGS_KEY,
  useAppSettingsQueryOptions,
  useUpdateAppSettingsMutationOptions,
} from "@/api/v2/settings/settings.hooks";

const CURRENCIES = [
  "EGP",
  "USD",
  "SAR",
  "AED",
  "SYP",
  "JOD",
  "KWD",
  "ILS",
  "TRY",
  "EUR",
] as const;

const TIMEZONES = [
  "Africa/Cairo",
  "Asia/Riyadh",
  "Asia/Dubai",
  "Asia/Damascus",
  "Asia/Amman",
  "Asia/Kuwait",
  "Asia/Jerusalem",
  "Europe/Istanbul",
  "UTC",
] as const;

const inputClass =
  "w-full px-4 py-2.5 rounded-xl text-sm text-slate-700 outline-none";
const inputStyle = { background: "#F8FAFC", border: "1.5px solid #E2E8F0" } as const;

export default function SystemSettingsTab() {
  const queryClient = useQueryClient();
  const { data, isPending, isError, error, refetch } = useQuery(
    useAppSettingsQueryOptions()
  );
  const { mutate, isPending: saving } = useMutation(
    useUpdateAppSettingsMutationOptions()
  );

  const [timezone, setTimezone] = useState("Africa/Cairo");
  const [currency, setCurrency] = useState("EGP");
  const [locale, setLocale] = useState("ar");
  const [dateFormat, setDateFormat] = useState("Y-m-d");

  useEffect(() => {
    if (!data) return;
    setTimezone(data.timezone || "Africa/Cairo");
    setCurrency(data.currency || "EGP");
    setLocale(data.locale || "ar");
    setDateFormat(data.date_format || "Y-m-d");
  }, [data]);

  const save = () => {
    mutate(
      { timezone, currency, locale, date_format: dateFormat },
      {
        onSuccess: (next) => {
          queryClient.setQueryData(APP_SETTINGS_KEY, next);
          toast.success("تم حفظ إعدادات النظام");
        },
        onError: (err: Error) => toast.error(err.message || "تعذر الحفظ"),
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
        <p className="font-bold mb-2">تعذر تحميل إعدادات النظام</p>
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

  return (
    <div className="space-y-5 max-w-xl">
      <div className="rounded-2xl border border-slate-100 bg-white p-5 space-y-4">
        <label className="block space-y-1.5">
          <span className="text-xs font-bold text-slate-600">المنطقة الزمنية</span>
          <select
            className={inputClass}
            style={inputStyle}
            value={timezone}
            onChange={(e) => setTimezone(e.target.value)}
          >
            {TIMEZONES.map((tz) => (
              <option key={tz} value={tz}>
                {tz}
              </option>
            ))}
          </select>
        </label>

        <label className="block space-y-1.5">
          <span className="text-xs font-bold text-slate-600">العملة الافتراضية</span>
          <select
            className={inputClass}
            style={inputStyle}
            value={currency}
            onChange={(e) => setCurrency(e.target.value)}
          >
            {CURRENCIES.map((c) => (
              <option key={c} value={c}>
                {c}
                {data?.currency === c && data.currency_label
                  ? ` — ${data.currency_label}`
                  : ""}
              </option>
            ))}
          </select>
        </label>

        <label className="block space-y-1.5">
          <span className="text-xs font-bold text-slate-600">لغة الواجهة</span>
          <select
            className={inputClass}
            style={inputStyle}
            value={locale}
            onChange={(e) => setLocale(e.target.value)}
          >
            <option value="ar">العربية</option>
            <option value="en">English</option>
          </select>
        </label>

        <label className="block space-y-1.5">
          <span className="text-xs font-bold text-slate-600">صيغة التاريخ</span>
          <select
            className={inputClass}
            style={inputStyle}
            value={dateFormat}
            onChange={(e) => setDateFormat(e.target.value)}
          >
            <option value="Y-m-d">2026-08-20</option>
            <option value="d/m/Y">20/08/2026</option>
            <option value="d-m-Y">20-08-2026</option>
          </select>
        </label>

        <div className="pt-2 flex justify-end">
          <button
            type="button"
            disabled={saving}
            onClick={save}
            className="px-5 py-2.5 rounded-xl text-sm font-bold text-white disabled:opacity-60"
            style={{ background: "#0C1A3E" }}
          >
            {saving ? "جارٍ الحفظ..." : "حفظ إعدادات النظام"}
          </button>
        </div>
      </div>
    </div>
  );
}
