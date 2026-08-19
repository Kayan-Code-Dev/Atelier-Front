import { TSupplierResponse } from "@/api/v2/suppliers/suppliers.types";
import { formatDate, toEnglishNumerals } from "@/utils/formatDate";
import { parseMoney, formatAccountCurrency } from "./supplierAccountHelpers";

type Props = {
  supplier: TSupplierResponse | undefined;
  isLoading: boolean;
};

export default function SupplierAccountHeader({ supplier, isLoading }: Props) {
  if (isLoading) {
    return (
      <div className="bg-white border border-gray-100 rounded-2xl p-5 animate-pulse">
        <div className="h-14 w-14 rounded-2xl bg-gray-100 mb-4" />
        <div className="h-6 w-48 bg-gray-100 rounded mb-2" />
        <div className="h-20 bg-gray-50 rounded-xl" />
      </div>
    );
  }

  if (!supplier) return null;

  const totalPurchases = parseMoney(
    supplier.total_order_amount ?? supplier.total_purchases,
  );
  const totalReturns = parseMoney(
    supplier.total_refund ?? supplier.total_returns,
  );
  const totalPaid = parseMoney(supplier.total_payment ?? supplier.paid);
  const remaining = parseMoney(supplier.total_remaining ?? supplier.remaining);
  const netPurchases = totalPurchases - totalReturns;
  const balance = Math.max(0, remaining);

  const kpis = [
    {
      label: "إجمالي المشتريات",
      value: formatAccountCurrency(totalPurchases),
      sub: "قيمة الطلبيات",
      box: "bg-violet-50 text-violet-700",
    },
    {
      label: "إجمالي المرتجعات",
      value: formatAccountCurrency(totalReturns),
      sub: "مخصوم من الحساب",
      box: "bg-orange-50 text-orange-700",
    },
    {
      label: "المدفوع",
      value: formatAccountCurrency(totalPaid),
      sub: "دفعات مسجّلة",
      box: "bg-emerald-50 text-emerald-700",
    },
    {
      label: "صافي المشتريات",
      value: formatAccountCurrency(netPurchases),
      sub: "بعد المرتجعات",
      box: "bg-slate-50 text-slate-700",
    },
  ];

  return (
    <div className="bg-white border border-gray-100 rounded-2xl p-5">
      <div className="flex items-start justify-between gap-4 mb-5">
        <div className="flex items-center gap-4 min-w-0">
          <div className="w-14 h-14 flex items-center justify-center rounded-2xl bg-amber-100 text-amber-600 font-black text-xl flex-shrink-0">
            {(supplier.name || "?").charAt(0)}
          </div>
          <div className="min-w-0">
            <div className="flex items-center gap-2 flex-wrap">
              <h2 className="text-lg font-black text-slate-800 truncate">
                {supplier.name}
              </h2>
            </div>
            <div className="flex items-center gap-3 mt-1.5 flex-wrap text-xs text-slate-400">
              <span className="flex items-center gap-1">
                <i className="ri-hashtag text-slate-300" />
                {supplier.code || "—"}
              </span>
              {supplier.phone && (
                <span className="flex items-center gap-1">
                  <i className="ri-phone-line text-slate-300" />
                  <span dir="ltr">{toEnglishNumerals(supplier.phone)}</span>
                </span>
              )}
              {supplier.address && (
                <span className="flex items-center gap-1 line-clamp-1">
                  <i className="ri-map-pin-line text-slate-300 shrink-0" />
                  {supplier.address}
                </span>
              )}
            </div>
          </div>
        </div>
        <div className="text-left flex-shrink-0">
          <p className="text-xs text-slate-400 mb-0.5">تاريخ التسجيل</p>
          <p className="text-sm font-semibold text-slate-600" dir="ltr">
            {supplier.created_at
              ? toEnglishNumerals(formatDate(supplier.created_at))
              : "—"}
          </p>
        </div>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div className="col-span-2 md:col-span-1 bg-slate-800 rounded-xl p-4 text-white">
          <p className="text-slate-300 text-xs mb-1">الرصيد المستحق</p>
          <p
            className={`text-xl font-black ${
              balance <= 0 ? "text-emerald-400" : "text-amber-400"
            }`}
          >
            {balance > 0 ? formatAccountCurrency(balance) : "مسدّد"}
          </p>
          {balance > 0 && (
            <p className="text-slate-400 text-[10px] mt-0.5">ج.م متبقية</p>
          )}
          {balance <= 0 && (
            <p className="text-emerald-400 text-[10px] mt-0.5">الحساب مغلق</p>
          )}
        </div>

        {kpis.map((k) => (
          <div key={k.label} className={`rounded-xl p-4 ${k.box}`}>
            <p className="text-xs opacity-80 mb-1">{k.label}</p>
            <p className="text-lg font-black tabular-nums" dir="ltr">
              {k.value}
            </p>
            <p className="text-[10px] opacity-70 mt-0.5">{k.sub}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
