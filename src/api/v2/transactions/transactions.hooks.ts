import { mutationOptions, queryOptions } from "@tanstack/react-query";
import {
  exportTransactionsToCSV,
  getTransactionsPaymentsExpenses,
} from "./transactions.service";
import { TTransactionsParams } from "./transactions.types";

export const TRANSACTIONS_KEY = "transactions";

export const useGetTransactionsQueryOptions = (params: TTransactionsParams) => {
  return queryOptions({
    queryKey: [TRANSACTIONS_KEY, params],
    queryFn: () => getTransactionsPaymentsExpenses(params),
    staleTime: 1000 * 60 * 5,
  });
};

export const useExportTransactionsToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: TTransactionsParams) => exportTransactionsToCSV(params),
  });
};

