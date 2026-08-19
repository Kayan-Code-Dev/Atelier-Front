import { useState, useMemo, useCallback } from "react";
import { useQuery } from "@tanstack/react-query";
import { useNavigate } from "react-router";
import { useGetOrdersQueryOptions } from "@/api/v2/orders/orders.hooks";
import type { TOrder } from "@/api/v2/orders/orders.types";
import { mapOrderToReturnInvoice } from "./mapOrderToReturnInvoice";
import type { ReturnInvoiceTableRow } from "./returnInvoiceProject.types";
import type { ReturnInvoicesFiltersState } from "./ReturnInvoicesFilters";
import { FETCH_PER_PAGE } from "../constants";

interface UseReturnInvoicesDataOptions {
  apiFilter: Record<string, unknown>;
  sortByDelay?: boolean;
}

export function useReturnInvoicesData({
  apiFilter,
  sortByDelay = false,
}: UseReturnInvoicesDataOptions) {
  const navigate = useNavigate();

  const [filters, setFilters] = useState<ReturnInvoicesFiltersState>({
    search: "",
    client: "الكل",
    employee: "الكل",
    dateFrom: "",
    dateTo: "",
  });

  const [isViewModalOpen, setIsViewModalOpen] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState<TOrder | null>(null);
  const [orderToReturn, setOrderToReturn] = useState<TOrder | null>(null);

  const { data, isPending, isError, error, refetch } = useQuery(
    useGetOrdersQueryOptions(1, FETCH_PER_PAGE, apiFilter),
  );

  const mappedRows = useMemo<ReturnInvoiceTableRow[]>(() => {
    const rows = (data?.data ?? []).map((o) => ({
      invoice: mapOrderToReturnInvoice(o),
      order: o,
    }));
    if (sortByDelay) {
      rows.sort(
        (a, b) => b.invoice.penalty.delayDays - a.invoice.penalty.delayDays,
      );
    }
    return rows;
  }, [data?.data, sortByDelay]);

  const employeeOptions = useMemo(() => {
    const s = new Set<string>();
    mappedRows.forEach((r) => {
      if (r.invoice.employee && r.invoice.employee !== "-")
        s.add(r.invoice.employee);
    });
    return Array.from(s).sort();
  }, [mappedRows]);

  const clientOptions = useMemo(() => {
    const s = new Set<string>();
    mappedRows.forEach((r) => {
      if (r.invoice.customer.name && r.invoice.customer.name !== "-")
        s.add(r.invoice.customer.name);
    });
    return Array.from(s).sort();
  }, [mappedRows]);

  const filteredRows = useMemo(() => {
    const q = filters.search.trim();
    return mappedRows.filter(({ invoice: e }) => {
      if (q) {
        const ok =
          e.customer.name.includes(q) ||
          e.invoiceRef.toLowerCase().includes(q.toLowerCase()) ||
          e.customer.nationalId.includes(q) ||
          e.customer.phone.includes(q);
        if (!ok) return false;
      }
      if (filters.client !== "الكل" && e.customer.name !== filters.client)
        return false;
      if (filters.employee !== "الكل" && e.employee !== filters.employee)
        return false;
      if (filters.dateFrom && e.dates.returnDate < filters.dateFrom)
        return false;
      if (filters.dateTo && e.dates.returnDate > filters.dateTo) return false;
      return true;
    });
  }, [mappedRows, filters]);

  const handleFilterChange = useCallback(
    (key: keyof ReturnInvoicesFiltersState, value: string) => {
      setFilters((prev) => ({ ...prev, [key]: value }));
    },
    [],
  );

  const handleResetFilters = useCallback(() => {
    setFilters({
      search: "",
      client: "الكل",
      employee: "الكل",
      dateFrom: "",
      dateTo: "",
    });
  }, []);

  const handleNavigateOrder = useCallback(
    (id: number) => navigate(`/orders/${id}`),
    [navigate],
  );

  const handleViewOrder = useCallback((order: TOrder) => {
    setSelectedOrder(order);
    setIsViewModalOpen(true);
  }, []);

  const handleReturnOrder = useCallback((order: TOrder) => {
    setOrderToReturn(order);
  }, []);

  const handleReturnSuccess = useCallback(() => {
    refetch();
    setOrderToReturn(null);
  }, [refetch]);

  const handleCloseReturn = useCallback((open: boolean) => {
    if (!open) setOrderToReturn(null);
  }, []);

  return {
    filters,
    mappedRows,
    filteredRows,
    employeeOptions,
    clientOptions,
    isPending,
    isError,
    error,
    data,

    isViewModalOpen,
    setIsViewModalOpen,
    selectedOrder,
    orderToReturn,

    handleFilterChange,
    handleResetFilters,
    handleNavigateOrder,
    handleViewOrder,
    handleReturnOrder,
    handleReturnSuccess,
    handleCloseReturn,
    refetch,
  };
}
