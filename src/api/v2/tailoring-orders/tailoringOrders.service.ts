import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import type {
  TAddTailoringOrderPaymentPayload,
  TCreateTailoringOrderPayload,
  TGetTailoringOrdersApiParams,
  TPatchTailoringOrderMeasurementsPayload,
  TPatchTailoringOrderStatusPayload,
  TTailoringOrderResource,
  TTailoringOrdersListResponse,
  TTailoringWorkflowStatusesResponse,
} from "./tailoringOrders.types";

function unwrapResource(
  raw: TTailoringOrderResource | { data: TTailoringOrderResource },
): TTailoringOrderResource {
  if (raw && typeof raw === "object" && "data" in raw && raw.data != null) {
    return raw.data;
  }
  return raw as TTailoringOrderResource;
}

export async function getTailoringWorkflowStatuses(): Promise<
  TTailoringWorkflowStatusesResponse | undefined
> {
  try {
    const { data } = await api.get<
      TTailoringWorkflowStatusesResponse | { data: TTailoringWorkflowStatusesResponse }
    >("/tailoring-orders/workflow-statuses");
    if (
      data &&
      typeof data === "object" &&
      "statuses" in data &&
      Array.isArray((data as TTailoringWorkflowStatusesResponse).statuses)
    ) {
      return data as TTailoringWorkflowStatusesResponse;
    }
    if (data && typeof data === "object" && "data" in data) {
      return (data as { data: TTailoringWorkflowStatusesResponse }).data;
    }
    return undefined;
  } catch (error) {
    populateError(error, "خطأ في جلب مراحل التفصيل");
  }
}

export async function getTailoringOrdersList(
  params?: TGetTailoringOrdersApiParams,
): Promise<TTailoringOrdersListResponse | undefined> {
  try {
    const { data } = await api.get<TTailoringOrdersListResponse>("/tailoring-orders", {
      params,
    });
    return data;
  } catch (error) {
    populateError(error, "خطأ في جلب أوامر التفصيل");
  }
}

export async function getTailoringOrderById(
  id: number,
): Promise<TTailoringOrderResource | undefined> {
  try {
    const { data } = await api.get<
      TTailoringOrderResource | { data: TTailoringOrderResource }
    >(`/tailoring-orders/${id}`);
    return unwrapResource(data as TTailoringOrderResource | { data: TTailoringOrderResource });
  } catch (error) {
    populateError(error, "خطأ في جلب أمر التفصيل");
  }
}

export async function createTailoringOrder(
  body: TCreateTailoringOrderPayload,
): Promise<TTailoringOrderResource | undefined> {
  try {
    const { data } = await api.post<
      TTailoringOrderResource | { data: TTailoringOrderResource }
    >("/tailoring-orders", body);
    return unwrapResource(data as TTailoringOrderResource | { data: TTailoringOrderResource });
  } catch (error) {
    populateError(error, "خطأ في إنشاء أمر التفصيل");
  }
}

export async function patchTailoringOrderMeasurements(
  id: number,
  body: TPatchTailoringOrderMeasurementsPayload,
): Promise<TTailoringOrderResource | undefined> {
  try {
    const { data } = await api.patch<
      TTailoringOrderResource | { data: TTailoringOrderResource }
    >(`/tailoring-orders/${id}/measurements`, body);
    return unwrapResource(data as TTailoringOrderResource | { data: TTailoringOrderResource });
  } catch (error) {
    populateError(error, "خطأ في تحديث المقاسات");
  }
}

export async function patchTailoringOrderStatus(
  id: number,
  body: TPatchTailoringOrderStatusPayload,
): Promise<TTailoringOrderResource | undefined> {
  try {
    const { data } = await api.patch<
      TTailoringOrderResource | { data: TTailoringOrderResource }
    >(`/tailoring-orders/${id}/status`, body);
    return unwrapResource(data as TTailoringOrderResource | { data: TTailoringOrderResource });
  } catch (error) {
    populateError(error, "خطأ في تحديث المرحلة");
  }
}

export async function addTailoringOrderPayment(
  id: number,
  body: TAddTailoringOrderPaymentPayload,
): Promise<TTailoringOrderResource | undefined> {
  try {
    const { data } = await api.post<
      TTailoringOrderResource | { data: TTailoringOrderResource }
    >(`/tailoring-orders/${id}/payments`, body);
    return unwrapResource(data as TTailoringOrderResource | { data: TTailoringOrderResource });
  } catch (error) {
    populateError(error, "خطأ في تسجيل الدفعة");
  }
}
