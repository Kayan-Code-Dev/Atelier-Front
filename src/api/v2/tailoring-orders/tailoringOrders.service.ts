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

/** Atelier stages are client-defined; stats has no workflow-statuses route. */
export async function getTailoringWorkflowStatuses(): Promise<
  TTailoringWorkflowStatusesResponse | undefined
> {
  return undefined;
}

export async function getTailoringOrdersList(
  params?: TGetTailoringOrdersApiParams,
): Promise<TTailoringOrdersListResponse | undefined> {
  try {
    const { data } = await api.get<TTailoringOrdersListResponse>("/tailoring/orders", {
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
    >(`/tailoring/orders/${id}`);
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
    >("/tailoring/orders", body);
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
    const { data } = await api.put<
      TTailoringOrderResource | { data: TTailoringOrderResource }
    >(`/tailoring/orders/${id}/measurements`, body);
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
    const { data } = await api.post<
      TTailoringOrderResource | { data: TTailoringOrderResource }
    >(`/tailoring/orders/${id}/change-stage`, body);
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
    >(`/invoices/${id}/payments`, body);
    return unwrapResource(data as TTailoringOrderResource | { data: TTailoringOrderResource });
  } catch (error) {
    populateError(error, "خطأ في تسجيل الدفعة");
  }
}
