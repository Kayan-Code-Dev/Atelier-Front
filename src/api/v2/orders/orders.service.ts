import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { getPayments } from "@/api/v2/payments/payments.service";
import {
  TCreateOrderRequest,
  TCreateOrderWithNewClientRequest,
  TOrder,
  TReturnOrderItemRequest,
  TUpdateOrderRequest,
  TAddPaymentRequest,
} from "./orders.types";


export const createOrder = async (
  data: TCreateOrderRequest | TCreateOrderWithNewClientRequest
): Promise<TOrder | undefined> => {
  try {
    const { data: responseData } = await api.post<TOrder>(`/orders`, data);
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى إضافة الطلب");
  }
};

export type TOrderListFilters = {
  process_type?: "rent" | "sold";
  status?: string;
  order_id?: string | number;
  date_from?: string;
  date_to?: string;
  returned?: boolean;
  overdue?: boolean;
  delayed?: boolean;
  client_id?: string | number;
  cloth_name?: string;
  cloth_code?: string;
  category_id?: string | number;
  subcategory_id?: string | number;
  visit_date_from?: string;
  visit_date_to?: string;
  delivery_date_from?: string;
  delivery_date_to?: string;
  return_date_from?: string;
  return_date_to?: string;
  employee_id?: string | number;
  search?: string;
};

function buildOrderListParams(
  filters?: TOrderListFilters,
  options?: { page?: number; per_page?: number }
): Record<string, string | number | boolean> {
  const params: Record<string, string | number | boolean> = {};
  if (options?.page != null) params.page = options.page;
  if (options?.per_page != null) params.per_page = options.per_page;
  if (filters?.process_type) params.process_type = filters.process_type;
  if (filters?.status) params.status = filters.status;
  if (filters?.date_from) params.date_from = filters.date_from;
  if (filters?.date_to) params.date_to = filters.date_to;
  if (filters?.returned === true) params.returned = 1;
  if (filters?.overdue === true) params.overdue = 1;
  if (filters?.delayed === true) params.delayed = true;
  if (filters?.delayed === false) params.delayed = false;
  if (filters?.order_id !== undefined && filters.order_id !== "" && filters.order_id != null) {
    const normalizedOrderId =
      typeof filters.order_id === "string" ? Number(filters.order_id) : filters.order_id;
    if (Number.isFinite(normalizedOrderId as number)) {
      params.id = normalizedOrderId;
      params.order_id = normalizedOrderId;
    }
  }
  const clientId =
    filters?.client_id !== undefined && filters?.client_id !== "" && filters?.client_id != null
      ? typeof filters.client_id === "string"
        ? Number(filters.client_id)
        : filters.client_id
      : undefined;
  if (clientId != null && Number.isFinite(clientId)) params.client_id = clientId;
  if (filters?.cloth_name?.trim()) {
    const clothName = filters.cloth_name.trim();
    params.cloth_name = clothName;
    params.item_name = clothName;
    params.name = clothName;
  }
  if (filters?.cloth_code?.trim()) {
    const clothCode = filters.cloth_code.trim();
    params.cloth_code = clothCode;
    params.item_code = clothCode;
    params.code = clothCode;
  }
  if (filters?.category_id !== undefined && filters.category_id !== "" && filters.category_id != null) {
    const categoryId = typeof filters.category_id === "string" ? Number(filters.category_id) : filters.category_id;
    if (Number.isFinite(categoryId)) params.category_id = categoryId;
  }
  if (filters?.subcategory_id !== undefined && filters.subcategory_id !== "" && filters.subcategory_id != null) {
    const subcategoryId = typeof filters.subcategory_id === "string" ? Number(filters.subcategory_id) : filters.subcategory_id;
    if (Number.isFinite(subcategoryId)) params.subcategory_id = subcategoryId;
  }
  // API doc: visit_from, visit_to (visit_datetime range)
  if (filters?.visit_date_from) params.visit_from = filters.visit_date_from;
  if (filters?.visit_date_to) params.visit_to = filters.visit_date_to;
  // API doc: delivery_from, delivery_to
  if (filters?.delivery_date_from) params.delivery_from = filters.delivery_date_from;
  if (filters?.delivery_date_to) params.delivery_to = filters.delivery_date_to;
  if (filters?.return_date_from) params.return_date_from = filters.return_date_from;
  if (filters?.return_date_to) params.return_date_to = filters.return_date_to;
  if (filters?.employee_id !== undefined && filters.employee_id !== "" && filters.employee_id != null) {
    params.employee_id = typeof filters.employee_id === "string" ? Number(filters.employee_id) : filters.employee_id;
  }
  if (filters?.search?.trim()) {
    params.search = filters.search.trim();
  }
  return params;
}

export const getOrders = async (
  page: number,
  per_page: number,
  filters?: TOrderListFilters
) => {
  try {
    const params = buildOrderListParams(filters, { page, per_page });
    const { data: responseData } = await api.get<TPaginationResponse<TOrder>>(
      `/orders`,
      { params },
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب الطلبات");
  }
};

export const getOrderDetails = async (id: number) => {
  try {
    const { data: responseData } = await api.get<TOrder>(`/orders/${id}`);
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب الطلب");
  }
};

export const deleteOrder = async (id: number) => {
  try {
    await api.delete(`/orders/${id}`);
  } catch (error: any) {
    populateError(error, "خطأ فى حذف الطلب");
  }
};

// deliver, finish, cancel
export type TOrderStatus =
  | "deliver"
  | "finish"
  | "cancel"
  | "paid"
  | "returned"
  | "delivered";

export const updateOrderStatus = async (id: number, status: TOrderStatus) => {
  try {
    await api.post(`/orders/${id}/${status}`);
  } catch (error: any) {
    populateError(error, "خطأ فى تحديث حالة الطلب");
  }
};

/** Update order status via PATCH (more flexible) */
export const updateOrderStatusV2 = async (id: number, status: string) => {
  try {
    const { data: responseData } = await api.patch<TOrder>(
      `/orders/${id}/status`,
      { status },
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى تحديث حالة الطلب");
  }
};

/** Add order payment */
export const addOrderPayment = async (id: number, data: TAddPaymentRequest) => {
  try {
    const { data: responseData } = await api.post<TOrder>(
      `/orders/${id}/payments`,
      data,
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى إضافة الدفعة");
  }
};

/** Update order (full payload) */
export const updateOrder = async (id: number, data: TUpdateOrderRequest) => {
  try {
    const { data: responseData } = await api.put<TOrder>(`/orders/${id}`, data);
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى تحديث الطلب");
  }
};


export const getOrderPayments = async (id: number) => {
  try {
    const page = await getPayments({
      order_id: id,
      page: 1,
      per_page: 100,
    });
    return page?.data ?? [];
  } catch (error: any) {
    populateError(error, "خطأ فى جلب الدفعات");
  }
};

/** Update paid amount for order */
export const updateOrderPayment = async (id: number, amount: number) => {
  try {
    const { data: responseData } = await api.patch<TOrder>(
      `/orders/${id}/payment`,
      {
        paid_amount: amount,
      },
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى تحديث المبلغ المدفوع");
  }
};

export const returnOrderItem = async (
  order_id: number,
  item_id: number,
  data: TReturnOrderItemRequest,
) => {
  try {
    const formData = new FormData();
    formData.append("entity_type", data.entity_type);
    formData.append("entity_id", data.entity_id.toString());
    formData.append("note", data.note);
    data.photos.forEach((photo, index) => {
      formData.append(`photos[${index}]`, photo);
    });
    await api.post(`/orders/${order_id}/items/${item_id}/return`, formData);
  } catch (error: any) {
    populateError(error, "خطأ فى إرجاع المنتجات");
  }
};

/** Export orders to Excel; uses same query params as order index. Returns blob + headers for filename from Content-Disposition. */
export const exportOrdersToCSV = async (filters?: TOrderListFilters) => {
  try {
    const params = buildOrderListParams(filters);
    const response = await api.get<Blob>(`/orders/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير الطلبات");
  }
};

/** Export deliveries to CSV */
export const exportDeliveriesToCSV = async (filters?: {
  date_from?: string;
  date_to?: string;
}) => {
  try {
    const params: any = {};
    if (filters?.date_from) params.date_from = filters.date_from;
    if (filters?.date_to) params.date_to = filters.date_to;

    const { data } = await api.get(`/orders/deliveries/export`, {
      params,
      responseType: "blob",
    });
    return data;
  } catch (error) {
    populateError(error, "خطأ فى تصدير التسليمات");
  }
};

/** Mark order as delivered */
export const markAsDelivered = async (id: number) => {
  try {
    const { data: responseData } = await api.patch<TOrder>(
      `/orders/${id}/deliver`,
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى تسليم الطلب");
  }
};

/** Mark order as cancelled */
export const markAsCancelled = async (id: number) => {
  try {
    const { data: responseData } = await api.patch<TOrder>(
      `/orders/${id}/cancel`,
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى إلغاء الطلب");
  }
};
