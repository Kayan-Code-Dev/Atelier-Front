import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  getAllManualCashboxPayments,
  createPayment,
  exportPaymentsToCSV,
  getManualCashboxPayments,
  getPaymentById,
  getPayments,
  getTailoringPayments,
  markPaymentAsCanceled,
  markPaymentAsPaid,
} from "./payments.service";
import { TGetPaymentsParams } from "./payments.types";
import { ORDERS_KEY } from "../orders/orders.hooks";
import { TRANSACTIONS_KEY } from "../transactions/transactions.hooks";

export const PAYMENTS_KEY = "payments";

export const useGetPaymentsQueryOptions = (params: TGetPaymentsParams) => {
  return queryOptions({
    queryKey: [PAYMENTS_KEY, params],
    queryFn: () => getPayments(params),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetTailoringPaymentsQueryOptions = (
  params: Parameters<typeof getTailoringPayments>[0]
) =>
  queryOptions({
    queryKey: [PAYMENTS_KEY, "tailoring", params],
    queryFn: () => getTailoringPayments(params),
    staleTime: 1000 * 60 * 5,
  });

export const useGetManualCashboxPaymentsQueryOptions = (
  cashboxId: number | null,
  params?: Parameters<typeof getManualCashboxPayments>[1]
) =>
  queryOptions({
    queryKey: [PAYMENTS_KEY, "manual", cashboxId, params],
    queryFn: () => getManualCashboxPayments(cashboxId!, params),
    enabled: cashboxId != null && cashboxId > 0,
    staleTime: 1000 * 60 * 5,
  });

export const useGetAllManualCashboxPaymentsQueryOptions = (
  params?: Parameters<typeof getAllManualCashboxPayments>[0]
) =>
  queryOptions({
    queryKey: [PAYMENTS_KEY, "manual", "all", params],
    queryFn: () => getAllManualCashboxPayments(params),
    staleTime: 1000 * 60 * 5,
  });

export const useGetPaymentByIdQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [PAYMENTS_KEY, id],
    queryFn: () => getPaymentById(id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useCreatePaymentMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: createPayment,
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [PAYMENTS_KEY] });
      queryClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
      queryClient.invalidateQueries({ queryKey: [TRANSACTIONS_KEY] });
    },
  });
};

export const useMarkPaymentAsPaidMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: markPaymentAsPaid,
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [PAYMENTS_KEY] });
      queryClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
      queryClient.invalidateQueries({ queryKey: [TRANSACTIONS_KEY] });
    },
  });
};

export const useMarkPaymentAsCanceledMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: markPaymentAsCanceled,
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [PAYMENTS_KEY] });
      queryClient.invalidateQueries({ queryKey: [ORDERS_KEY] });
      queryClient.invalidateQueries({ queryKey: [TRANSACTIONS_KEY] });
    },
  });
};

export const useExportPaymentsToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportPaymentsToCSV>[0]) =>
      exportPaymentsToCSV(params),
  });
};