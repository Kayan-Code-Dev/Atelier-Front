import {
  infiniteQueryOptions,
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createManualCashboxPayment,
  getCashbox,
  getCashboxByBranchId,
  getCashboxDailySummary,
  getCashboxes,
  recalculateCashbox,
  updateCashbox,
  createCashbox,
  exportCashboxesToExcel,
  closeCashboxPeriod,
  getCashboxClosures,
  getClosureArchivedData,
} from "./cashboxes.service";
import {
  TCashboxesParams,
  TManualCashboxPaymentRequest,
  TUpdateCashboxRequest,
  TCreateCashboxRequest,
  TClosePeriodRequest,
  TClosuresListParams,
  TClosureArchivedDataParams,
} from "./cashboxes.types";
import { TRANSACTIONS_KEY } from "../transactions/transactions.hooks";

export const CASHBOXES_KEY = "CASHBOXES";

export const useGetCashboxesQueryOptions = (params: TCashboxesParams) => {
  return queryOptions({
    queryKey: [CASHBOXES_KEY, params],
    queryFn: () => getCashboxes(params),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetCashboxQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [CASHBOXES_KEY, id],
    queryFn: () => getCashbox(id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useCreateCashboxMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: TCreateCashboxRequest) => createCashbox(data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [CASHBOXES_KEY] });
    },
  });
};

export const useUpdateCashboxMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: number; data: TUpdateCashboxRequest }) =>
      updateCashbox(id, data),
    onSettled: (_, __, { id }) => {
      queryClient.invalidateQueries({ queryKey: [CASHBOXES_KEY, id] });
    },
  });
};

export const useGetCashboxDailySummaryQueryOptions = (
  id: number,
  date: string
) => {
  return queryOptions({
    queryKey: [CASHBOXES_KEY, id, date],
    queryFn: () => getCashboxDailySummary(id, date),
    staleTime: 1000 * 60 * 5,
  });
};

export const useRecalculateCashboxMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: recalculateCashbox,
    onSettled: (_, __, id) => {
      queryClient.invalidateQueries({ queryKey: [CASHBOXES_KEY, id] });
      queryClient.invalidateQueries({ queryKey: [TRANSACTIONS_KEY] });
    },
  });
};

export const useGetCashboxByBranchIdQueryOptions = (branchId: number) => {
  return queryOptions({
    queryKey: [CASHBOXES_KEY, "by-branch", branchId],
    queryFn: () => getCashboxByBranchId(branchId),
    staleTime: 1000 * 60 * 5,
  });
};

// infinite query options used in selecting cashboxes combobox

export const useGetInfiniteCashboxesQueryOptions = (per_page: number) => {
  return infiniteQueryOptions({
    queryKey: [CASHBOXES_KEY, "infinite"],
    queryFn: ({ pageParam = 1 }) => getCashboxes({ page: pageParam, per_page }),
    initialPageParam: 1,
    staleTime: 1000 * 60 * 5,
    getNextPageParam: (lastPage) => {
      if (!lastPage) return undefined;
      if (lastPage.current_page < lastPage.total_pages) {
        return lastPage.current_page + 1;
      }
    },
  });
};

export const useExportCashboxesToExcelMutationOptions = () =>
  mutationOptions({
    mutationFn: (params?: Parameters<typeof exportCashboxesToExcel>[0]) =>
      exportCashboxesToExcel(params),
  });

export const useManualCashboxPaymentMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({
      cashboxId,
      data,
    }: {
      cashboxId: number;
      data: TManualCashboxPaymentRequest;
    }) => createManualCashboxPayment(cashboxId, data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [CASHBOXES_KEY] });
      queryClient.invalidateQueries({ queryKey: [TRANSACTIONS_KEY] });
    },
  });
};

// ── Cashbox Closure ──

export const CLOSURES_KEY = "CASHBOX_CLOSURES";

export const useCloseCashboxPeriodMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({
      cashboxId,
      data,
    }: {
      cashboxId: number;
      data?: TClosePeriodRequest;
    }) => closeCashboxPeriod(cashboxId, data),
    onSettled: (_, __, { cashboxId }) => {
      queryClient.invalidateQueries({ queryKey: [CASHBOXES_KEY] });
      queryClient.invalidateQueries({ queryKey: [CASHBOXES_KEY, cashboxId] });
      queryClient.invalidateQueries({ queryKey: [TRANSACTIONS_KEY] });
      queryClient.invalidateQueries({ queryKey: [CLOSURES_KEY] });
    },
  });
};

export const useGetCashboxClosuresQueryOptions = (
  cashboxId: number,
  params?: TClosuresListParams
) => {
  return queryOptions({
    queryKey: [CLOSURES_KEY, cashboxId, params],
    queryFn: () => getCashboxClosures(cashboxId, params),
    staleTime: 1000 * 60 * 5,
  });
};

export const CLOSURE_ARCHIVED_KEY = "CASHBOX_CLOSURE_ARCHIVED";

export const useGetClosureArchivedDataQueryOptions = (
  closureId: number,
  params: TClosureArchivedDataParams,
  enabled = true
) => {
  return queryOptions({
    queryKey: [CLOSURE_ARCHIVED_KEY, closureId, params],
    queryFn: () => getClosureArchivedData(closureId, params),
    staleTime: 1000 * 60 * 2,
    enabled: enabled && closureId > 0 && !!params.type,
  });
};
