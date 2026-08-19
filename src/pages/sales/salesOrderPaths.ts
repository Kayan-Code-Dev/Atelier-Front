import { SOLD_PROCESS_TYPE } from "@/lib/salesOrderConstants";

/** `/orders/123?process_type=sold` */
export function soldOrderDetailPath(orderId: number): string {
  return `/orders/${orderId}?process_type=${SOLD_PROCESS_TYPE}`;
}

export function soldOrdersListPath(): string {
  return `/orders/list?process_type=${SOLD_PROCESS_TYPE}`;
}
