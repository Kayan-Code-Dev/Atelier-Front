import { useCallback, useEffect, useMemo, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useNavigate, useSearchParams } from "react-router";
import { toast } from "sonner";

import {
  useGetSuppliersListQueryOptions,
  useExportSupplierOrdersToExcelMutationOptions,
  useSupplierOrdersListSnapshotQueryOptions,
  useUpdateSupplierOrderMutationOptions,
  useReceiveSupplierOrderMutationOptions,
} from "@/api/v2/suppliers/suppliers.hooks";
import type { TSupplierOrderResponse } from "@/api/v2/suppliers/suppliers.types";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import { EditSupplierOrderModal } from "../EditSupplierOrderModal";
import { AddPaymentToSupplierOrderModal } from "../AddPaymentToSupplierOrderModal";

import SupOrderStats from "./components/SupOrderStats";
import SupOrderFilters from "./components/SupOrderFilters";
import SupOrderTable from "./components/SupOrderTable";
import { buildUpdatePayloadFromOrder } from "./buildUpdatePayloadFromOrder";
import {
  displayStatusToApi,
  mapApiOrderToVM,
} from "./mapApiOrderToVM";
import type { SupplierOrderStatusAr, SupplierOrderVM } from "./types";

const initialFilters = {
  search: "",
  supplier: "الكل",
  status: "الكل",
  branch: "الكل",
  dateFrom: "",
  dateTo: "",
};

function parseMoney(v: string | number | null | undefined): number {
  if (v == null || v === "") return 0;
  const n = typeof v === "number" ? v : parseFloat(String(v));
  return Number.isNaN(n) ? 0 : n;
}

function canAddPaymentVm(vm: SupplierOrderVM): boolean {
  const o = vm._raw;
  const s = (o.status || "").toLowerCase();
  if (s === "cancelled" || s.includes("ملغ")) return false;
  if (s === "delivered" || s.includes("تسليم")) return false;
  return parseMoney(o.remaining_payment) > 0;
}

function statusLockedVm(vm: SupplierOrderVM): boolean {
  return !vm._raw.clothes || vm._raw.clothes.length === 0;
}

export default function SupplierOrdersPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const supplierIdParam = Number(searchParams.get("supplier_id")) || 0;

  const [filters, setFilters] = useState(initialFilters);
  const [showAddModal, setShowAddModal] = useState(false);
  const [selectedOrder, setSelectedOrder] =
    useState<TSupplierOrderResponse | null>(null);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [orderForPayment, setOrderForPayment] =
    useState<TSupplierOrderResponse | null>(null);
  const [isPaymentOpen, setIsPaymentOpen] = useState(false);

  const { data: snap, isPending, isError, error, refetch } = useQuery(
    useSupplierOrdersListSnapshotQueryOptions(supplierIdParam),
  );

  const { data: suppliersList } = useQuery(useGetSuppliersListQueryOptions());

  const supplierFromList = useMemo(() => {
    if (supplierIdParam <= 0 || !suppliersList?.length) return undefined;
    return suppliersList.find((s) => s.id === supplierIdParam);
  }, [supplierIdParam, suppliersList]);

  const { mutate: exportSupplierOrdersToExcel, isPending: isExporting } =
    useMutation(useExportSupplierOrdersToExcelMutationOptions());

  const { mutate: updateOrder, isPending: isUpdatingStatus } = useMutation(
    useUpdateSupplierOrderMutationOptions(),
  );
  const { mutate: receiveOrder } = useMutation(
    useReceiveSupplierOrderMutationOptions(),
  );

  useEffect(() => {
    if (supplierIdParam > 0 && supplierFromList?.name) {
      setFilters((f) => ({ ...f, supplier: supplierFromList.name }));
    }
    if (supplierIdParam === 0) {
      setFilters((f) => ({ ...f, supplier: "الكل" }));
    }
  }, [supplierIdParam, supplierFromList?.name]);

  const vms: SupplierOrderVM[] = useMemo(() => {
    const rows = snap?.data ?? [];
    return [...rows]
      .map(mapApiOrderToVM)
      .sort(
        (a, b) =>
          new Date(b.orderDate).getTime() - new Date(a.orderDate).getTime(),
      );
  }, [snap?.data]);

  const branchOptions = useMemo(() => {
    const names = new Set<string>();
    vms.forEach((o) => {
      if (o.branch && o.branch !== "—") names.add(o.branch);
    });
    return ["الكل", ...Array.from(names).sort((a, b) => a.localeCompare(b, "ar"))];
  }, [vms]);

  const supplierOptions = useMemo(() => {
    const list = suppliersList ?? [];
    return list.map((s) => ({ id: s.id, name: s.name }));
  }, [suppliersList]);

  const filtered = useMemo(() => {
    return vms.filter((o) => {
      if (filters.search) {
        const raw = filters.search;
        const q = raw.toLowerCase();
        if (
          !o.orderRef.toLowerCase().includes(q) &&
          !o.supplierName.includes(raw) &&
          !o.supplierCode.toLowerCase().includes(q) &&
          !o.items.some((i) => i.name.includes(raw))
        )
          return false;
      }
      if (filters.supplier !== "الكل" && o.supplierName !== filters.supplier)
        return false;
      if (filters.status !== "الكل" && o.status !== filters.status)
        return false;
      if (filters.branch !== "الكل" && o.branch !== filters.branch)
        return false;
      if (filters.dateFrom && o.orderDate < filters.dateFrom) return false;
      if (filters.dateTo && o.orderDate > filters.dateTo) return false;
      return true;
    });
  }, [vms, filters]);

  const handleFilterChange = useCallback((key: string, value: string) => {
    setFilters((p) => ({ ...p, [key]: value }));
  }, []);

  const handleExportExcel = () => {
    const params =
      supplierIdParam > 0 ? { supplier_id: supplierIdParam } : undefined;
    exportSupplierOrdersToExcel(params, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) ||
          "supplier-orders.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير طلبيات الموردين بنجاح");
      },
      onError: (err: { message?: string }) => {
        toast.error("خطأ أثناء التصدير", { description: err?.message });
      },
    });
  };

  const handleReceive = (vm: SupplierOrderVM) => {
    receiveOrder(vm.id, {
      onSuccess: () => {
        toast.success("تم استلام الطلبية وإضافة القطع للمخزون");
        void refetch();
      },
      onError: (err: { message?: string }) => {
        toast.error("تعذر استلام الطلبية", { description: err?.message });
      },
    });
  };

  const handleStatusChange = (vm: SupplierOrderVM, st: SupplierOrderStatusAr) => {
    if (st === "مستلم") {
      handleReceive(vm);
      return;
    }
    const api = displayStatusToApi(st);
    const payload = buildUpdatePayloadFromOrder(vm._raw, api);
    if (!payload) {
      toast.error(
        "لا توجد قطع في بيانات القائمة — افتح «تعديل» لتحديث الحالة.",
      );
      return;
    }
    updateOrder(
      { id: vm.id, data: payload },
      {
        onSuccess: () => {
          toast.success("تم تحديث الحالة");
          void refetch();
        },
        onError: (err: { message?: string }) => {
          toast.error("تعذر تحديث الحالة", { description: err?.message });
        },
      },
    );
  };

  const capped =
    snap?.total != null && snap.total > (snap.data?.length ?? 0) && (snap.data?.length ?? 0) >= 500;

  return (
    <div dir="rtl" className="p-6 space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">طلبيات الموردين</h1>
          <p className="text-sm text-gray-400 mt-0.5">
            كشف طلبيات الشراء — مرتبة حسب الأحدث أولاً
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {supplierIdParam > 0 && (
            <button
              type="button"
              onClick={() => navigate("/suppliers/orders", { replace: true })}
              className="px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 whitespace-nowrap"
            >
              عرض كل الطلبيات
            </button>
          )}
          <button
            type="button"
            onClick={handleExportExcel}
            disabled={isExporting}
            className="flex items-center gap-2 px-4 py-2 text-sm text-green-700 border border-green-200 rounded-lg hover:bg-green-50 cursor-pointer whitespace-nowrap disabled:opacity-50"
          >
            <div className="w-4 h-4 flex items-center justify-center">
              <i className="ri-file-excel-line" />
            </div>
            {isExporting ? "..." : "تصدير Excel"}
          </button>
          <button
            type="button"
            onClick={() => window.print()}
            className="flex items-center gap-2 px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 cursor-pointer whitespace-nowrap"
          >
            <div className="w-4 h-4 flex items-center justify-center">
              <i className="ri-printer-line" />
            </div>
            طباعة
          </button>
          <button
            type="button"
            data-onboarding-target="purchase_order_created"
            onClick={() => setShowAddModal(true)}
            className="flex items-center gap-2 px-4 py-2 text-sm bg-blue-700 text-white rounded-lg hover:bg-blue-800 cursor-pointer whitespace-nowrap"
          >
            <div className="w-4 h-4 flex items-center justify-center">
              <i className="ri-add-line" />
            </div>
            طلبية جديدة
          </button>
        </div>
      </div>

      {capped && (
        <p className="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
          يوجد أكثر من {snap?.data?.length} طلبية؛ تُعرض أحدث {snap?.data?.length}{" "}
          في الإحصائيات والجدول. استخدم تصدير Excel للحصول على ملف أوسع من الخادم.
        </p>
      )}

      {isError && (
        <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-red-600 text-sm">
          {error?.message ?? "حدث خطأ أثناء تحميل البيانات."}
        </div>
      )}

      {isPending && !snap && (
        <p className="text-sm text-gray-500 text-center py-8">جاري التحميل...</p>
      )}

      {!isError && snap && (
        <>
          <SupOrderStats entries={filtered} all={vms} />
          <SupOrderFilters
            filters={filters}
            onChange={handleFilterChange}
            onReset={() => setFilters(initialFilters)}
            count={filtered.length}
            total={vms.length}
            supplierOptions={supplierOptions}
            branchOptions={branchOptions}
          />
          <SupOrderTable
            entries={filtered}
            onStatusChange={handleStatusChange}
            statusChangeDisabled={statusLockedVm}
            onReceive={handleReceive}
            onPayment={(vm) => {
              setOrderForPayment(vm._raw);
              setIsPaymentOpen(true);
            }}
            onEdit={(vm) => {
              setSelectedOrder(vm._raw);
              setIsEditOpen(true);
            }}
            onView={(vm) =>
              navigate(`/suppliers/accounts?supplier_id=${vm.supplierId}`)
            }
            canAddPayment={canAddPaymentVm}
            onDelete={() =>
              toast.message("الحذف غير متاح من هذه الشاشة حالياً")
            }
          />
        </>
      )}

      {showAddModal && (
        <div
          className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
          onClick={() => setShowAddModal(false)}
          role="presentation"
        >
          <div
            className="bg-white rounded-2xl w-full max-w-lg p-6"
            onClick={(e) => e.stopPropagation()}
            role="dialog"
            aria-modal="true"
          >
            <div className="flex items-center justify-between mb-5">
              <h3 className="font-bold text-lg text-gray-800">
                طلبية شراء جديدة
              </h3>
              <button
                type="button"
                onClick={() => setShowAddModal(false)}
                className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 cursor-pointer"
              >
                <i className="ri-close-line" />
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="text-xs text-gray-500 block mb-1.5">
                  المورد <span className="text-red-400">*</span>
                </label>
                <select className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50 cursor-pointer">
                  <option value="">— اختر المورد —</option>
                  {(suppliersList ?? []).map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-xs text-gray-500 block mb-1.5">
                    تاريخ الطلب
                  </label>
                  <input
                    type="date"
                    className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
                  />
                </div>
                <div>
                  <label className="text-xs text-gray-500 block mb-1.5">
                    التسليم المتوقع
                  </label>
                  <input
                    type="date"
                    className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
                  />
                </div>
              </div>
              <div>
                <label className="text-xs text-gray-500 block mb-1.5">
                  المنتج
                </label>
                <input
                  type="text"
                  placeholder="اسم المنتج المطلوب"
                  className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
                />
              </div>
              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="text-xs text-gray-500 block mb-1.5">
                    الكمية
                  </label>
                  <input
                    type="number"
                    placeholder="0"
                    className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
                  />
                </div>
                <div>
                  <label className="text-xs text-gray-500 block mb-1.5">
                    الوحدة
                  </label>
                  <select className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50 cursor-pointer">
                    {["متر", "قطعة", "بكرة", "كيلو", "جرام"].map((u) => (
                      <option key={u}>{u}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="text-xs text-gray-500 block mb-1.5">
                    سعر الوحدة
                  </label>
                  <input
                    type="number"
                    placeholder="0.00"
                    className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
                  />
                </div>
              </div>
              <div>
                <label className="text-xs text-gray-500 block mb-1.5">
                  ملاحظات
                </label>
                <textarea
                  rows={2}
                  placeholder="ملاحظات..."
                  className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50 resize-none"
                />
              </div>
            </div>
            <div className="flex gap-3 mt-5">
              <button
                type="button"
                onClick={() => {
                  setShowAddModal(false);
                  navigate(
                    supplierIdParam > 0
                      ? `/suppliers/orders/add?supplier_id=${supplierIdParam}`
                      : "/suppliers/orders/add",
                  );
                }}
                className="flex-1 py-2.5 bg-blue-700 text-white rounded-xl text-sm font-bold cursor-pointer hover:bg-blue-800 whitespace-nowrap"
              >
                حفظ الطلبية
              </button>
              <button
                type="button"
                onClick={() => setShowAddModal(false)}
                className="flex-1 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm cursor-pointer whitespace-nowrap"
              >
                إلغاء
              </button>
            </div>
          </div>
        </div>
      )}

      <EditSupplierOrderModal
        order={selectedOrder}
        open={isEditOpen}
        onOpenChange={(open) => {
          setIsEditOpen(open);
          if (!open) {
            void refetch();
          }
        }}
      />

      <AddPaymentToSupplierOrderModal
        order={orderForPayment}
        open={isPaymentOpen}
        onOpenChange={(open) => {
          setIsPaymentOpen(open);
          if (!open) {
            setOrderForPayment(null);
            void refetch();
          }
        }}
      />

      {isUpdatingStatus && (
        <div className="fixed bottom-4 left-4 z-40 text-xs bg-white border shadow rounded-lg px-3 py-2 text-gray-600">
          جاري تحديث الحالة...
        </div>
      )}
    </div>
  );
}
