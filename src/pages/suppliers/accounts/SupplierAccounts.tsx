import { useCallback, useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useSearchParams } from "react-router";

import {
  useGetSuppliersListQueryOptions,
  useGetSupplierOrdersSnapshotQueryOptions,
} from "@/api/v2/suppliers/suppliers.hooks";
import SupplierAccountsSidebar from "./SupplierAccountsSidebar";
import SupplierAccountHeader from "./SupplierAccountHeader";
import SupplierAccountOrdersTab from "./SupplierAccountOrdersTab";
import SupplierAccountPaymentsTab from "./SupplierAccountPaymentsTab";
import SupplierAccountReturnsTab from "./SupplierAccountReturnsTab";
import SupplierAccountStatementTab from "./SupplierAccountStatementTab";
import {
  isCancelledOrder,
  isReturnedOrder,
  parseMoney,
} from "./supplierAccountHelpers";

type TabKey = "الطلبيات" | "المدفوعات" | "الإرجاعات" | "كشف الحساب";

const tabs: { key: TabKey; icon: string }[] = [
  { key: "الطلبيات", icon: "ri-file-list-3-line" },
  { key: "المدفوعات", icon: "ri-bank-card-line" },
  { key: "الإرجاعات", icon: "ri-arrow-go-back-line" },
  { key: "كشف الحساب", icon: "ri-file-chart-line" },
];

export default function SupplierAccounts() {
  const [searchParams, setSearchParams] = useSearchParams();
  const supplierIdParam = Number(searchParams.get("supplier_id")) || 0;

  const [selectedId, setSelectedId] = useState<number | null>(null);
  const tabParam = searchParams.get("tab");
  const [activeTab, setActiveTab] = useState<TabKey>(
    tabParam === "statement" ? "كشف الحساب" : "الطلبيات",
  );

  useEffect(() => {
    if (tabParam === "statement") setActiveTab("كشف الحساب");
  }, [tabParam]);

  const { data: suppliersList, isPending: listLoading } = useQuery(
    useGetSuppliersListQueryOptions(),
  );

  useEffect(() => {
    if (supplierIdParam > 0) setSelectedId(supplierIdParam);
  }, [supplierIdParam]);

  useEffect(() => {
    if (supplierIdParam > 0) return;
    if (!suppliersList?.length) return;
    setSelectedId((prev) => (prev === null ? suppliersList[0].id : prev));
  }, [supplierIdParam, suppliersList]);

  const supplier = useMemo(() => {
    if (selectedId == null || !suppliersList?.length) return undefined;
    return suppliersList.find((s) => s.id === selectedId);
  }, [selectedId, suppliersList]);

  const ordersQuery = useQuery({
    ...useGetSupplierOrdersSnapshotQueryOptions(selectedId ?? 0),
    enabled: !!selectedId,
  });
  const ordersSnap = ordersQuery.data;

  const tabBadges = useMemo(() => {
    const rows = ordersSnap?.data ?? [];
    const payments = rows.filter(
      (o) => !isCancelledOrder(o) && parseMoney(o.payment_amount) > 0,
    ).length;
    const returns = rows.filter((o) => isReturnedOrder(o)).length;
    const ordersTotal = ordersSnap?.total ?? rows.length;
    return {
      الطلبيات: ordersTotal,
      المدفوعات: payments,
      الإرجاعات: returns,
      "كشف الحساب": null as number | null,
    };
  }, [ordersSnap]);

  const handleSelectSupplier = useCallback(
    (id: number) => {
      setSelectedId(id);
      setActiveTab("الطلبيات");
      setSearchParams(
        (prev) => {
          const next = new URLSearchParams(prev);
          next.set("supplier_id", String(id));
          return next;
        },
        { replace: true },
      );
    },
    [setSearchParams],
  );

  return (
    <div
      dir="rtl"
      className="w-full flex gap-4 min-h-[calc(100vh-100px)] pb-6 px-1"
    >
      <div className="w-72 shrink-0 sticky top-0 self-start max-h-[calc(100vh-80px)]">
        <SupplierAccountsSidebar
          suppliers={suppliersList}
          selectedId={selectedId}
          onSelect={handleSelectSupplier}
          isLoading={listLoading}
        />
      </div>

      <div className="flex-1 min-w-0 space-y-4">
        {selectedId ? (
          <>
            <SupplierAccountHeader
              supplier={supplier}
              isLoading={listLoading}
            />

            <div className="flex items-center gap-2 overflow-x-auto pb-1">
              {tabs.map((tab) => {
                const isActive = activeTab === tab.key;
                const badgeCount = tabBadges[tab.key];
                return (
                  <button
                    key={tab.key}
                    type="button"
                    data-onboarding-target={
                      tab.key === "كشف الحساب" ? "account_statement" : undefined
                    }
                    onClick={() => setActiveTab(tab.key)}
                    className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold cursor-pointer transition-all whitespace-nowrap shrink-0 ${
                      isActive
                        ? "bg-slate-800 text-white"
                        : "bg-white border border-gray-200 text-slate-500 hover:bg-gray-50"
                    }`}
                  >
                    <i className={tab.icon} />
                    {tab.key}
                    {badgeCount != null && (
                      <span
                        className={`px-1.5 py-0.5 rounded-full text-[10px] font-bold ${
                          isActive
                            ? "bg-white/20 text-white"
                            : "bg-gray-100 text-slate-600"
                        }`}
                      >
                        {badgeCount}
                      </span>
                    )}
                  </button>
                );
              })}
            </div>

            <div key={`${selectedId}-${activeTab}`}>
              {activeTab === "الطلبيات" && (
                <SupplierAccountOrdersTab
                  supplierId={selectedId}
                  ordersQuery={ordersQuery}
                />
              )}
              {activeTab === "المدفوعات" && (
                <SupplierAccountPaymentsTab supplierId={selectedId} />
              )}
              {activeTab === "الإرجاعات" && (
                <SupplierAccountReturnsTab supplierId={selectedId} />
              )}
              {activeTab === "كشف الحساب" && (
                <SupplierAccountStatementTab supplierId={selectedId} />
              )}
            </div>
          </>
        ) : (
          <div className="flex flex-col items-center justify-center min-h-80 bg-white border border-gray-100 rounded-2xl">
            <div className="w-16 h-16 flex items-center justify-center rounded-2xl bg-amber-100 text-amber-500 text-3xl mb-4">
              <i className="ri-building-line" />
            </div>
            <h3 className="text-base font-bold text-slate-700 mb-1">
              لا يوجد موردون
            </h3>
            <p className="text-sm text-slate-400 text-center px-4">
              أضف موردين من قائمة الموردين أو تحقق من الصلاحيات.
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
