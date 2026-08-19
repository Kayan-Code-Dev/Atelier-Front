import { useMemo, useState, useEffect } from "react";
import { useSearchParams } from "react-router";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { Check, X } from "lucide-react";
import CustomPagination from "@/components/custom/CustomPagination";
import { ControlledConfirmationModal } from "@/components/custom/ControlledConfirmationModal";
import { useHasPermission } from "@/api/auth/auth.hooks";
import {
  useGetTransferClothesQueryOptions,
  useExportTransferClothesToCSVMutationOptions,
  useApproveTransferClothesMutationOptions,
  useRejectTransferClothesMutationOptions,
  useApprovePartialTransferClothesMutationOptions,
  useRejectPartialTransferClothesMutationOptions,
} from "@/api/v2/clothes/transfer-clothes/transfer-clothes.hooks";
import type { TTransferClothesItem } from "@/api/v2/clothes/transfer-clothes/transfer-clothes.types";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import { TransferClothModal } from "./TransferClothModal";

const STATUS_LABEL: Record<string, string> = {
  pending: "قيد المراجعة",
  approved: "موافق",
  rejected: "مرفوض",
  partially_pending: "قيد مراجعة جزئية",
  partially_approved: "موافق جزئياً",
};

function formatDate(iso: string) {
  if (!iso) return "—";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso.slice(0, 10);
  return d.toLocaleDateString("ar-EG", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function productSummary(t: TTransferClothesItem) {
  if (!t.items?.length) return "—";
  return t.items
    .map((i) => i.cloth_code || String(i.cloth_id))
    .join("، ");
}

/** معرّفات بنود الطلب التي ما زالت قيد المراجعة (للموافقة/الرفض الجزئي) */
function pendingLineItemIds(t: TTransferClothesItem): number[] {
  return (
    t.items?.filter((i) => i.status === "pending").map((i) => i.id) ?? []
  );
}

function rowNeedsStatusActions(t: TTransferClothesItem): boolean {
  if (t.status === "approved" || t.status === "rejected") return false;
  if (t.status === "pending") return true;
  return pendingLineItemIds(t).length > 0;
}

export default function ProductTransferPage() {
  const [searchParams] = useSearchParams();
  const page = Number(searchParams.get("page")) || 1;
  const per_page = 15;

  const [historySearch, setHistorySearch] = useState("");
  const [showForm, setShowForm] = useState(false);
  const [successMsg, setSuccessMsg] = useState(false);

  const [approveCtx, setApproveCtx] = useState<{
    transfer: TTransferClothesItem;
    /** null = موافقة على الطلب كاملاً (حالة pending) */
    lineItemIds: number[] | null;
  } | null>(null);
  const [rejectCtx, setRejectCtx] = useState<{
    transfer: TTransferClothesItem;
    lineItemIds: number[] | null;
  } | null>(null);

  const { hasPermission: canApprove } = useHasPermission("transfers.approve");
  const { hasPermission: canReject } = useHasPermission("transfers.reject");

  const { data, isPending, isError, error } = useQuery(
    useGetTransferClothesQueryOptions({ page, per_page })
  );

  const { mutate: exportCsv, isPending: isExporting } = useMutation(
    useExportTransferClothesToCSVMutationOptions()
  );

  const { mutate: approveFull, isPending: approvingFull } = useMutation(
    useApproveTransferClothesMutationOptions()
  );
  const { mutate: rejectFull, isPending: rejectingFull } = useMutation(
    useRejectTransferClothesMutationOptions()
  );
  const { mutate: approvePartial, isPending: approvingPartial } = useMutation(
    useApprovePartialTransferClothesMutationOptions()
  );
  const { mutate: rejectPartial, isPending: rejectingPartial } = useMutation(
    useRejectPartialTransferClothesMutationOptions()
  );

  const isApproving = approvingFull || approvingPartial;
  const isRejecting = rejectingFull || rejectingPartial;

  const openApprove = (t: TTransferClothesItem) => {
    const pendingIds = pendingLineItemIds(t);
    if (t.status === "pending") {
      setApproveCtx({ transfer: t, lineItemIds: null });
    } else if (pendingIds.length > 0) {
      setApproveCtx({ transfer: t, lineItemIds: pendingIds });
    }
  };

  const openReject = (t: TTransferClothesItem) => {
    const pendingIds = pendingLineItemIds(t);
    if (t.status === "pending") {
      setRejectCtx({ transfer: t, lineItemIds: null });
    } else if (pendingIds.length > 0) {
      setRejectCtx({ transfer: t, lineItemIds: pendingIds });
    }
  };

  const handleConfirmApprove = (close: () => void) => {
    if (!approveCtx) return;
    const { transfer, lineItemIds } = approveCtx;
    if (lineItemIds?.length) {
      approvePartial(
        { id: transfer.id, cloth_ids: lineItemIds },
        {
          onSuccess: () => {
            toast.success("تمت الموافقة على المنتجات المحددة");
            close();
            setApproveCtx(null);
          },
          onError: (e: Error & { message?: string }) => {
            toast.error("تعذرت الموافقة", { description: e.message });
          },
        }
      );
    } else {
      approveFull(transfer.id, {
        onSuccess: () => {
          toast.success("تمت الموافقة على طلب النقل");
          close();
          setApproveCtx(null);
        },
        onError: (e: Error & { message?: string }) => {
          toast.error("تعذرت الموافقة", { description: e.message });
        },
      });
    }
  };

  const handleConfirmReject = (close: () => void) => {
    if (!rejectCtx) return;
    const { transfer, lineItemIds } = rejectCtx;
    if (lineItemIds?.length) {
      rejectPartial(
        { id: transfer.id, cloth_ids: lineItemIds },
        {
          onSuccess: () => {
            toast.success("تم رفض المنتجات المحددة");
            close();
            setRejectCtx(null);
          },
          onError: (e: Error & { message?: string }) => {
            toast.error("تعذر الرفض", { description: e.message });
          },
        }
      );
    } else {
      rejectFull(transfer.id, {
        onSuccess: () => {
          toast.success("تم رفض طلب النقل");
          close();
          setRejectCtx(null);
        },
        onError: (e: Error & { message?: string }) => {
          toast.error("تعذر الرفض", { description: e.message });
        },
      });
    }
  };

  useEffect(() => {
    if (!successMsg) return;
    const t = window.setTimeout(() => setSuccessMsg(false), 3500);
    return () => window.clearTimeout(t);
  }, [successMsg]);

  const filteredRows = useMemo(() => {
    const rows = data?.data ?? [];
    const q = historySearch.trim().toLowerCase();
    if (!q) return rows;
    return rows.filter((t) => {
      const prod = productSummary(t).toLowerCase();
      return (
        prod.includes(q) ||
        t.from_entity_name?.toLowerCase().includes(q) ||
        t.to_entity_name?.toLowerCase().includes(q) ||
        String(t.id).includes(q) ||
        t.notes?.toLowerCase().includes(q)
      );
    });
  }, [data?.data, historySearch]);

  const handleExport = () => {
    exportCsv(undefined, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) ||
          "transfers.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم التصدير بنجاح");
      },
      onError: (e: Error & { message?: string }) => {
        toast.error("فشل التصدير", { description: e.message });
      },
    });
  };

  return (
    <div className="space-y-5" dir="rtl">
      {successMsg && (
        <div
          className="flex items-center gap-3 p-4 rounded-xl"
          style={{ background: "#D1FAE5", border: "1px solid #6EE7B7" }}
        >
          <i className="ri-checkbox-circle-fill text-xl text-green-600" />
          <p className="text-sm font-semibold text-green-800">
            تم تسجيل طلب النقل بنجاح وسيتم تحديث موقع المنتج بعد الموافقة.
          </p>
        </div>
      )}

      {isError && (
        <div className="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">
          {error?.message ?? "تعذر تحميل السجل"}
        </div>
      )}

      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 className="text-lg font-bold text-slate-800">نقل المنتجات</h2>
          <p className="text-xs text-slate-500 mt-0.5">
            نقل المنتجات بين الفروع والورشة والمصنع
          </p>
        </div>
        <button
          type="button"
          onClick={() => setShowForm(true)}
          className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap text-white transition-opacity hover:opacity-95 active:opacity-90"
          style={{
            background: "linear-gradient(135deg, #0C1A3E, #1E3A7B)",
            boxShadow: "0 2px 8px rgba(12,26,62,0.25)",
          }}
        >
          <i className="ri-arrow-left-right-line" />
          طلب نقل جديد
        </button>
      </div>

      <TransferClothModal
        open={showForm}
        onOpenChange={setShowForm}
        initialCloth={null}
        onSuccess={() => {
          setSuccessMsg(true);
        }}
      />

      <div
        className="rounded-2xl p-4 flex flex-wrap items-center gap-3"
        style={{ background: "white", border: "1px solid #E2E8F0" }}
      >
        <div className="relative flex-1 min-w-[200px] max-w-sm">
          <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
          <input
            type="text"
            placeholder="ابحث في سجل النقل..."
            value={historySearch}
            onChange={(e) => setHistorySearch(e.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50/80 py-2.5 pr-9 pl-3 text-sm outline-none focus:ring-2 focus:ring-slate-200"
          />
        </div>
        <div className="flex-1 min-w-[1rem]" />
        <button
          type="button"
          onClick={handleExport}
          disabled={isExporting}
          className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold whitespace-nowrap disabled:opacity-50"
          style={{
            background: "#F0FDF4",
            color: "#065F46",
            border: "1px solid #D1FAE5",
          }}
        >
          <i className="ri-file-excel-2-line" />{" "}
          {isExporting ? "..." : "Excel"}
        </button>
        <button
          type="button"
          onClick={() =>
            toast.info("تصدير PDF غير مفعّل حالياً", {
              description: "سيُتاح لاحقاً.",
            })
          }
          className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold whitespace-nowrap"
          style={{
            background: "#FEF2F2",
            color: "#991B1B",
            border: "1px solid #FECACA",
          }}
        >
          <i className="ri-file-pdf-line" /> PDF
        </button>
      </div>

      <div
        className="rounded-2xl overflow-hidden"
        style={{ background: "white", border: "1px solid #E2E8F0" }}
      >
        <div className="px-5 py-4 border-b border-slate-100">
          <h3 className="font-bold text-sm text-slate-800">سجل عمليات النقل</h3>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[900px] text-right text-sm">
            <thead>
              <tr className="bg-slate-50 border-b border-slate-100">
                {[
                  "رقم الطلب",
                  "المنتج",
                  "من",
                  "إلى",
                  "التاريخ",
                  "الحالة",
                  "السبب",
                  "إجراءات",
                ].map((h) => (
                  <th
                    key={h}
                    className="px-4 py-3 font-bold text-slate-500 text-xs whitespace-nowrap"
                  >
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {isPending ? (
                <tr>
                  <td colSpan={8} className="py-16 text-center text-slate-400">
                    <i className="ri-loader-4-line text-2xl animate-spin inline-block" />
                  </td>
                </tr>
              ) : filteredRows.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-16 text-center">
                    <p className="text-sm text-slate-400">لا توجد بيانات</p>
                  </td>
                </tr>
              ) : (
                filteredRows.map((t) => (
                  <tr
                    key={t.id}
                    className="border-b border-slate-50 hover:bg-slate-50/50"
                  >
                    <td className="px-4 py-3">
                      <span className="font-mono text-xs font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">
                        #{t.id}
                      </span>
                    </td>
                    <td className="px-4 py-3 max-w-[220px]">
                      <p className="text-sm text-slate-700 truncate" title={productSummary(t)}>
                        {productSummary(t)}
                      </p>
                    </td>
                    <td className="px-4 py-3">
                      <span
                        className="text-xs px-2 py-0.5 rounded-full whitespace-nowrap"
                        style={{
                          background: "#FEF3C7",
                          color: "#92400E",
                        }}
                      >
                        {t.from_entity_name || "—"}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <span
                        className="text-xs px-2 py-0.5 rounded-full whitespace-nowrap"
                        style={{
                          background: "#DBEAFE",
                          color: "#1D4ED8",
                        }}
                      >
                        {t.to_entity_name || "—"}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <span className="text-xs text-slate-500">
                        {formatDate(t.transfer_date)}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <span className="text-xs font-medium text-slate-600">
                        {STATUS_LABEL[t.status] ?? t.status}
                      </span>
                    </td>
                    <td className="px-4 py-3 max-w-[180px]">
                      <span className="text-xs text-slate-500 line-clamp-2">
                        {t.notes || "—"}
                      </span>
                    </td>
                    <td
                      className="px-4 py-3 text-center"
                      onClick={(e) => e.stopPropagation()}
                    >
                      {rowNeedsStatusActions(t) &&
                      (canApprove || canReject) ? (
                        <div className="flex items-center gap-2 justify-center">
                          {canApprove ? (
                            <button
                              type="button"
                              title="موافقة على طلب النقل"
                              disabled={isApproving || isRejecting}
                              className="w-8 h-8 rounded-lg flex items-center justify-center transition-all hover:brightness-95 disabled:opacity-40"
                              style={{ background: "#16A34A" }}
                              onClick={() => openApprove(t)}
                            >
                              <Check
                                className="h-4 w-4 text-white"
                                strokeWidth={2.5}
                              />
                            </button>
                          ) : null}
                          {canReject ? (
                            <button
                              type="button"
                              title="رفض طلب النقل"
                              disabled={isApproving || isRejecting}
                              className="w-8 h-8 rounded-lg flex items-center justify-center transition-all hover:brightness-95 disabled:opacity-40"
                              style={{ background: "#DC2626" }}
                              onClick={() => openReject(t)}
                            >
                              <X
                                className="h-4 w-4 text-white"
                                strokeWidth={2.5}
                              />
                            </button>
                          ) : null}
                        </div>
                      ) : (
                        <span className="text-slate-300 text-sm">—</span>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      <div className="flex justify-center pt-2">
        <CustomPagination
          totalElementsLabel="إجمالي الطلبات"
          totalElements={data?.total}
          totalPages={data?.total_pages}
          isLoading={isPending}
        />
      </div>

      <ControlledConfirmationModal
        open={!!approveCtx}
        onOpenChange={(o) => {
          if (!o) setApproveCtx(null);
        }}
        alertTitle="تأكيد الموافقة"
        alertMessage={
          approveCtx ? (
            <>
              {approveCtx.lineItemIds?.length ? (
                <>
                  الموافقة على{" "}
                  <strong>{approveCtx.lineItemIds.length}</strong> منتج/منتجات
                  قيد المراجعة في الطلب{" "}
                  <strong className="font-mono">#{approveCtx.transfer.id}</strong>
                  ؟
                </>
              ) : (
                <>
                  الموافقة على طلب النقل رقم{" "}
                  <strong className="font-mono">
                    #{approveCtx.transfer.id}
                  </strong>{" "}
                  ({productSummary(approveCtx.transfer)})؟
                </>
              )}
            </>
          ) : null
        }
        handleConfirmation={handleConfirmApprove}
        isPending={isApproving}
        pendingLabel="جاري التنفيذ..."
        confirmLabel="تأكيد الموافقة"
        variant="default"
        confirmButtonClassName="bg-green-600 text-white hover:bg-green-700 border-0"
      />

      <ControlledConfirmationModal
        open={!!rejectCtx}
        onOpenChange={(o) => {
          if (!o) setRejectCtx(null);
        }}
        alertTitle="تأكيد الرفض"
        variantLayout="brand"
        alertMessage={
          rejectCtx ? (
            <>
              {rejectCtx.lineItemIds?.length ? (
                <>
                  رفض{" "}
                  <strong>{rejectCtx.lineItemIds.length}</strong> منتج/منتجات
                  قيد المراجعة في الطلب{" "}
                  <strong className="font-mono">#{rejectCtx.transfer.id}</strong>
                  ؟
                </>
              ) : (
                <>
                  رفض طلب النقل رقم{" "}
                  <strong className="font-mono">#{rejectCtx.transfer.id}</strong>{" "}
                  ({productSummary(rejectCtx.transfer)})؟
                </>
              )}
            </>
          ) : null
        }
        handleConfirmation={handleConfirmReject}
        isPending={isRejecting}
        pendingLabel="جاري التنفيذ..."
        confirmLabel="تأكيد الرفض"
        variant="destructive"
      />
    </div>
  );
}
