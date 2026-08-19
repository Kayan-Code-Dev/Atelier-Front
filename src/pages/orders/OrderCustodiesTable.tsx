import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useGetAllCustodiesQueryOptions } from "@/api/v2/custody/custody.hooks";
import type { TOrderCustody, TCustodyType, TCustodyStatus } from "@/api/v2/custody/custody.types";
import { formatDate } from "@/utils/formatDate";
import { CustodyDetailsModal } from "./CustodyDetailsModal";

export type OrderCustodiesTableProps = {
  orderId: number;
  clientId: number;
  
  currencySymbol?: string;
  
  embedded?: boolean;
};

const getCustodyTypeLabel = (type: TCustodyType): string => {
  const labels: Record<TCustodyType, string> = {
    money: "مال",
    physical_item: "عنصر مادي",
    document: "مستند",
  };
  return labels[type] || type;
};

const custodyStatusPill: Record<TCustodyStatus, string> = {
  pending: "bg-amber-50 text-amber-800 border border-amber-200",
  returned: "bg-emerald-50 text-emerald-700 border border-emerald-200",
  lost: "bg-rose-50 text-rose-700 border border-rose-200",
};

const getCustodyStatusLabel = (status: TCustodyStatus): string => {
  const labels: Record<TCustodyStatus, string> = {
    pending: "قيد الانتظار",
    returned: "تم الإرجاع",
    lost: "مفقود",
  };
  return labels[status] || status;
};

function TableSkeleton({ rows = 5 }: { rows?: number }) {
  return (
    <>
      {Array.from({ length: rows }).map((_, i) => (
        <tr key={i} className="border-t border-slate-100">
          {Array.from({ length: 8 }).map((__, j) => (
            <td key={j} className="px-4 py-3">
              <div className="h-4 animate-pulse rounded bg-slate-100" />
            </td>
          ))}
        </tr>
      ))}
    </>
  );
}

export function OrderCustodiesTable({
  orderId,
  clientId,
  currencySymbol = "ج.م",
  embedded = false,
}: OrderCustodiesTableProps) {
  const [page, setPage] = useState(1);
  const [selectedCustodyId, setSelectedCustodyId] = useState<number | null>(null);
  const [isDetailsModalOpen, setIsDetailsModalOpen] = useState(false);
  const per_page = 10;

  const { data, isPending } = useQuery({
    ...useGetAllCustodiesQueryOptions({
      order_id: orderId,
      client_id: clientId,
      page,
      per_page,
    }),
    enabled: !!orderId && !!clientId,
  });

  const totalPages = Math.max(1, data?.total_pages ?? 1);

  const tableBlock = (
    <>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[720px] text-sm">
          <thead>
            <tr className="bg-slate-50 text-slate-600">
              <th className="px-4 py-3 text-right font-medium">#</th>
              <th className="px-4 py-3 text-right font-medium">النوع</th>
              <th className="px-4 py-3 text-right font-medium">الوصف</th>
              <th className="px-4 py-3 text-right font-medium">القيمة</th>
              <th className="px-4 py-3 text-right font-medium">الحالة</th>
              <th className="px-4 py-3 text-right font-medium">تاريخ الإنشاء</th>
              <th className="px-4 py-3 text-right font-medium">تاريخ الإرجاع</th>
              <th className="px-4 py-3 text-center font-medium">عرض</th>
            </tr>
          </thead>
          <tbody>
            {isPending ? (
              <TableSkeleton rows={per_page} />
            ) : data?.data && data.data.length > 0 ? (
              data.data.map((custody: TOrderCustody, index: number) => (
                <tr
                  key={custody.id}
                  className={`border-t border-slate-100 ${
                    index % 2 === 1 ? "bg-slate-50/50" : "bg-white"
                  }`}
                >
                  <td className="px-4 py-3 text-slate-400 tabular-nums">
                    {(page - 1) * per_page + index + 1}
                  </td>
                  <td className="px-4 py-3 text-slate-700">{getCustodyTypeLabel(custody.type)}</td>
                  <td className="max-w-[220px] px-4 py-3 text-slate-600">
                    <span className="line-clamp-2">{custody.description}</span>
                  </td>
                  <td className="px-4 py-3 text-slate-700 tabular-nums" dir="ltr">
                    {custody.type === "money" && custody.value != null
                      ? `${custody.value} ${currencySymbol}`
                      : "—"}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-medium ${custodyStatusPill[custody.status]}`}
                    >
                      {getCustodyStatusLabel(custody.status)}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-slate-600">{formatDate(custody.created_at)}</td>
                  <td className="px-4 py-3 text-slate-600">
                    {custody.returned_at ? formatDate(custody.returned_at) : "—"}
                  </td>
                  <td className="px-4 py-3 text-center">
                    <button
                      type="button"
                      title="تفاصيل الضمان"
                      onClick={() => {
                        setSelectedCustodyId(custody.id);
                        setIsDetailsModalOpen(true);
                      }}
                      className="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-md border border-slate-200 text-slate-500 transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800"
                    >
                      <i className="ri-eye-line text-base" />
                    </button>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan={8} className="px-4 py-12 text-center text-sm text-slate-400">
                  <i className="ri-shield-check-line mb-2 block text-3xl text-slate-200" />
                  لا توجد ضمانات لعرضها
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {data && data.total > 0 ? (
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-xs text-slate-500">
          <span>
            إجمالي الضمانات:{" "}
            <span className="font-semibold text-slate-700">{data.total}</span>
          </span>
          <div className="flex items-center gap-2">
            <button
              type="button"
              disabled={page <= 1 || isPending}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              className="rounded-md border border-slate-200 px-3 py-1.5 font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:pointer-events-none disabled:opacity-40"
            >
              السابق
            </button>
            <span className="tabular-nums text-slate-600">
              صفحة {page} من {totalPages}
            </span>
            <button
              type="button"
              disabled={page >= totalPages || isPending}
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              className="rounded-md border border-slate-200 px-3 py-1.5 font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:pointer-events-none disabled:opacity-40"
            >
              التالي
            </button>
          </div>
        </div>
      ) : null}

      <CustodyDetailsModal
        custodyId={selectedCustodyId}
        open={isDetailsModalOpen}
        onOpenChange={setIsDetailsModalOpen}
        currencySymbol={currencySymbol}
      />
    </>
  );

  if (embedded) {
    return <div className="text-right">{tableBlock}</div>;
  }

  return (
    <div className="overflow-hidden rounded-lg border border-slate-200 bg-white" dir="rtl">
      <div className="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
        <i className="ri-shield-check-line text-slate-400" />
        <h2 className="text-sm font-semibold text-slate-700">الضمانات</h2>
      </div>
      {tableBlock}
    </div>
  );
}
