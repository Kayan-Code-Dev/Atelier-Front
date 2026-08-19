import {
  TCreateSupplierRequest,
  TCreateSupplierMinimalRequest,
  TCreateSupplierOrderRequest,
  TSupplierResponse,
  TSupplierOrderDetailResponse,
  TUpdateSupplierRequest,
  TUpdateSupplierOrderRequest,
  TSuppliersListResponse,
  TSupplierOrdersListResponse,
} from "./suppliers.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";

// ---------------------------------------------------------------------------
// Internal helpers
// ---------------------------------------------------------------------------

type TCreateSupplierStoreResponse = {
  message?: string;
  supplier: TSupplierResponse;
};

// ---------------------------------------------------------------------------
// Suppliers
// ---------------------------------------------------------------------------

export const createSupplierMinimal = async (
  data: TCreateSupplierMinimalRequest,
) => {
  try {
    const { data: response } = await api.post<TCreateSupplierStoreResponse>(
      "/suppliers/store",
      data,
    );
    return response?.supplier;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء المورد");
  }
};

export const createSupplier = async (data: TCreateSupplierRequest) => {
  try {
    const { data: response } = await api.post<TCreateSupplierStoreResponse>(
      "/suppliers/store",
      data,
    );
    return response?.supplier;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء المورد");
  }
};

export const updateSupplier = async (
  id: number,
  data: TUpdateSupplierRequest,
) => {
  try {
    const { data: response } = await api.put<TSupplierResponse>(
      `/suppliers/update/${id}`,
      data,
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى تحديث المورد");
  }
};

export const getSuppliers = async (
  page: number,
  per_page: number,
  search?: string
) => {
  try {
    const params: Record<string, string | number> = { page, per_page };
    if (search?.trim()) params.search = search.trim();
    const { data: response } = await api.get<TSuppliersListResponse>(
      "/suppliers",
      { params },
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب الموردين");
  }
};

export const deleteSupplier = async (id: number) => {
  try {
    const { data: response } = await api.delete<TSupplierResponse>(
      `/suppliers/delete/${id}`,
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى حذف المورد");
  }
};

export const getSuppliersList = async (): Promise<
  TSupplierResponse[] | undefined
> => {
  try {
    const { data: response } = await api.get<TSuppliersListResponse>(
      "/suppliers",
      { params: { page: 1, per_page: 500 } },
    );
    return response?.data ?? [];
  } catch (error) {
    populateError(error, "خطأ فى جلب قائمة الموردين");
  }
};

/** Export suppliers to Excel; same query params as suppliers index. */
export const exportSuppliersToExcel = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>("/suppliers/export", {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير الموردين");
  }
};

// ---------------------------------------------------------------------------
// Supplier Orders
// ---------------------------------------------------------------------------

export const getSupplierOrders = async (page: number, per_page: number) => {
  try {
    const { data: response } = await api.get<TSupplierOrdersListResponse>(
      "/supplier-orders",
      { params: { page, per_page } },
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب طلبيات الموردين");
  }
};

/** Export supplier orders to Excel; same query params as index (e.g. supplier_id). */
export const exportSupplierOrdersToExcel = async (
  params?: Record<string, unknown>
) => {
  try {
    const response = await api.get<Blob>("/supplier-orders/export", {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير طلبيات الموردين");
  }
};

export const getSupplierOrdersBySupplierId = async (
  supplierId: number,
  page: number,
  per_page: number,
) => {
  try {
    const { data: response } = await api.get<TSupplierOrdersListResponse>(
      "/supplier-orders",
      { params: { supplier_id: supplierId, page, per_page } },
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب طلبيات المورد");
  }
};

/** Up to 500 orders for list UI (stats, filters, table) — all or one supplier. */
export const getSupplierOrdersListSnapshot = async (
  supplierId?: number,
): Promise<TSupplierOrdersListResponse | undefined> => {
  if (supplierId != null && supplierId > 0) {
    return getSupplierOrdersBySupplierId(supplierId, 1, 500);
  }
  return getSupplierOrders(1, 500);
};

/**
 * Fetches all orders for a supplier then returns the one matching `orderId`.
 * Falls back to `undefined` when the order is not found.
 */
export const getSupplierOrder = async (
  supplierId: number,
  orderId: number,
): Promise<TSupplierOrderDetailResponse | undefined> => {
  try {
    const { data: response } = await api.get<TSupplierOrdersListResponse>(
      "/supplier-orders",
      { params: { supplier_id: supplierId, page: 1, per_page: 500 } },
    );
    return response?.data?.find((o) => o.id === orderId);
  } catch (error) {
    populateError(error, "خطأ فى جلب تفاصيل الطلبية");
  }
};

export const createSupplierOrder = async (
  data: TCreateSupplierOrderRequest,
) => {
  try {
    const { data: response } = await api.post(
      "/supplier-orders/store",
      data,
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء طلبية المورد");
  }
};

export const updateSupplierOrder = async (
  id: number,
  data: TUpdateSupplierOrderRequest,
) => {
  try {
    const { data: response } = await api.put(
      `/supplier-orders/update/${id}`,
      data,
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى تحديث طلبية المورد");
  }
};

export const addPaymentToSupplierOrder = async (
  id: number,
  body: { clothes: { cloth_id: number; amount: number }[] },
) => {
  try {
    const { data: response } = await api.post(
      `/supplier-orders/add-payment/${id}`,
      body,
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى إضافة الدفعة");
  }
};

export const receiveSupplierOrder = async (id: number) => {
  try {
    const { data: response } = await api.post(`/purchase-orders/${id}/receive`);
    return response;
  } catch (error) {
    populateError(error, "خطأ فى استلام الطلبية");
    throw error;
  }
};

export const returnSupplierOrder = async (id: number) => {
  try {
    const { data: response } = await api.post(
      `/supplier-orders/return/${id}`,
      {},
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى إرجاع الطلبية");
  }
};
