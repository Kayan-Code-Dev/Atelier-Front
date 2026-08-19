import {
  mutationOptions,
  queryOptions,
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import {
  addTailoringOrderPayment,
  createTailoringOrder,
  getTailoringOrderById,
  getTailoringOrdersList,
  getTailoringWorkflowStatuses,
  patchTailoringOrderMeasurements,
  patchTailoringOrderStatus,
} from "./tailoringOrders.service";
import type {
  TAddTailoringOrderPaymentPayload,
  TCreateTailoringOrderPayload,
  TGetTailoringOrdersApiParams,
  TPatchTailoringOrderMeasurementsPayload,
  TPatchTailoringOrderStatusPayload,
} from "./tailoringOrders.types";
import { TRANSACTIONS_KEY } from "../transactions/transactions.hooks";

export const TAILORING_ORDERS_KEY = "tailoring-orders";

export function useTailoringWorkflowStatusesQueryOptions() {
  return queryOptions({
    queryKey: [TAILORING_ORDERS_KEY, "workflow-statuses"],
    queryFn: () => getTailoringWorkflowStatuses(),
    staleTime: 1000 * 60 * 30,
  });
}

export function useTailoringWorkflowStatusesQuery() {
  return useQuery(useTailoringWorkflowStatusesQueryOptions());
}

export function useTailoringOrdersQueryOptions(params?: TGetTailoringOrdersApiParams) {
  return queryOptions({
    queryKey: [TAILORING_ORDERS_KEY, "list", params],
    queryFn: () => getTailoringOrdersList(params),
    staleTime: 1000 * 60,
  });
}

export function useTailoringOrdersQuery(params?: TGetTailoringOrdersApiParams) {
  return useQuery(useTailoringOrdersQueryOptions(params));
}

export function useTailoringOrderQueryOptions(id: number | null) {
  return queryOptions({
    queryKey: [TAILORING_ORDERS_KEY, "detail", id],
    queryFn: () => getTailoringOrderById(id!),
    enabled: id != null && id > 0,
    staleTime: 1000 * 30,
  });
}

export function useTailoringOrderQuery(id: number | null) {
  return useQuery(useTailoringOrderQueryOptions(id));
}

export function useCreateTailoringOrderMutation() {
  const queryClient = useQueryClient();
  return useMutation(
    mutationOptions({
      mutationFn: (body: TCreateTailoringOrderPayload) => createTailoringOrder(body),
      onSettled: () => {
        queryClient.invalidateQueries({ queryKey: [TAILORING_ORDERS_KEY] });
      },
    }),
  );
}

export function usePatchTailoringOrderMeasurementsMutation() {
  const queryClient = useQueryClient();
  return useMutation(
    mutationOptions({
      mutationFn: ({
        id,
        body,
      }: {
        id: number;
        body: TPatchTailoringOrderMeasurementsPayload;
      }) => patchTailoringOrderMeasurements(id, body),
      onSettled: (_, __, { id }) => {
        queryClient.invalidateQueries({ queryKey: [TAILORING_ORDERS_KEY, "detail", id] });
        queryClient.invalidateQueries({ queryKey: [TAILORING_ORDERS_KEY, "list"] });
      },
    }),
  );
}

export function usePatchTailoringOrderStatusMutation() {
  const queryClient = useQueryClient();
  return useMutation(
    mutationOptions({
      mutationFn: ({
        id,
        body,
      }: {
        id: number;
        body: TPatchTailoringOrderStatusPayload;
      }) => patchTailoringOrderStatus(id, body),
      onSettled: (_, __, { id }) => {
        queryClient.invalidateQueries({ queryKey: [TAILORING_ORDERS_KEY, "detail", id] });
        queryClient.invalidateQueries({ queryKey: [TAILORING_ORDERS_KEY, "list"] });
      },
    }),
  );
}

export function useAddTailoringOrderPaymentMutation() {
  const queryClient = useQueryClient();
  return useMutation(
    mutationOptions({
      mutationFn: ({
        id,
        body,
      }: {
        id: number;
        body: TAddTailoringOrderPaymentPayload;
      }) => addTailoringOrderPayment(id, body),
      onSettled: (_, __, { id }) => {
        queryClient.invalidateQueries({ queryKey: [TAILORING_ORDERS_KEY, "detail", id] });
        queryClient.invalidateQueries({ queryKey: [TAILORING_ORDERS_KEY, "list"] });
        queryClient.invalidateQueries({ queryKey: [TRANSACTIONS_KEY] });
      },
    }),
  );
}
