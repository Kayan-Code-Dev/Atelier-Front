import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createOrder,
  exportOrdersToCSV,
  getOrderDetails,
  getOrders,
  returnOrderItem,
  updateOrder,
  updateOrderStatus,
  deleteOrder,
  addOrderPayment,
} from "./orders.service";
import { CLOTHES_KEY } from "@/api/v2/clothes/clothes.hooks";
import {
  TCreateOrderRequest,
  TCreateOrderWithNewClientRequest,
  TReturnOrderItemRequest,
  TUpdateOrderRequest,
  TAddPaymentRequest,
} from "./orders.types";

export const ORDERS_KEY = "orders";

export const useCreateOrderMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: TCreateOrderRequest | TCreateOrderWithNewClientRequest) => createOrder(data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
    },
  });
};

export const useUpdateOrderMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: number; data: TUpdateOrderRequest }) =>
      updateOrder(id, data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
    },
  });
};

export const useGetOrdersQueryOptions = (
  page: number,
  per_page: number,
  filters?: {
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
  },
) => {
  return queryOptions({
    queryKey: [ORDERS_KEY, page, per_page, filters],
    queryFn: () => getOrders(page, per_page, filters),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetOrderDetailsQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [ORDERS_KEY, id],
    queryFn: () => getOrderDetails(id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useDeliverOrderMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => updateOrderStatus(id, "deliver"),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
      qClient.invalidateQueries({ queryKey: [CLOTHES_KEY] });
    },
  });
};

export const useFinishOrderMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => updateOrderStatus(id, "finish"),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
    },
  });
};

export const useCancelOrderMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => updateOrderStatus(id, "cancel"),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
    },
  });
};

export const useReturnOrderItemMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({
      data,
      item_id,
      order_id,
    }: {
      order_id: number;
      item_id: number;
      data: TReturnOrderItemRequest;
    }) => returnOrderItem(order_id, item_id, data),
    onSettled: (_, __, { order_id }) => {
      qClient.invalidateQueries({ queryKey: [ORDERS_KEY, order_id] });
    },
  });
};

export const useExportOrdersToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (filters?: Parameters<typeof exportOrdersToCSV>[0]) =>
      exportOrdersToCSV(filters),
  });
};

export const useDeleteOrderMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteOrder(id),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
    },
  });
};

export const useAddOrderPaymentMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: number; data: TAddPaymentRequest }) =>
      addOrderPayment(id, data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
    },
  });
};

export const useMarkAsPaidMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => updateOrderStatus(id, "paid"),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
    },
  });
};
