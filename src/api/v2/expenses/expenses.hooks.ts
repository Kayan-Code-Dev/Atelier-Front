import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  TCreateExpenseRequest,
  TExpense,
  TGetExpensesParams,
  TGetExpenseSummaryParams,
  TUpdateExpenseRequest,
} from "./expenses.types";
import {
  cancelExpense,
  createExpense,
  deleteExpense,
  getExpenseCategories,
  getExpenses,
  getExpenseById,
  getExpenseSummary,
  payExpense,
  updateExpense,
  exportExpensesToExcel,
} from "./expenses.service";
import { TPaginationResponse } from "@/api/api-common.types";
import { TRANSACTIONS_KEY } from "../transactions/transactions.hooks";

export const EXPENSES_KEY = "EXPENSES_KEY";
export const EXPENSE_CATEGORIES_API_KEY = "EXPENSE_CATEGORIES_API";

export function useGetExpenseCategoriesQueryOptions() {
  return queryOptions({
    queryKey: [EXPENSE_CATEGORIES_API_KEY],
    queryFn: () => getExpenseCategories(),
    staleTime: 1000 * 60 * 30,
  });
}

export const useGetExpensesQueryOptions = (params: TGetExpensesParams) => {
  return queryOptions({
    queryKey: [EXPENSES_KEY, params],
    queryFn: () => getExpenses(params),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetExpenseByIdQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [EXPENSES_KEY, id],
    queryFn: () => getExpenseById(id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useCreateExpenseMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: TCreateExpenseRequest) => createExpense(data),
    onMutate: async (data: TCreateExpenseRequest) => {
      await queryClient.cancelQueries({ queryKey: [EXPENSES_KEY] });
      const previousExpenses = queryClient.getQueryData<
        TPaginationResponse<TExpense>
      >([EXPENSES_KEY]);
      queryClient.setQueryData(
        [EXPENSES_KEY],
        (oldData: TPaginationResponse<TExpense> | undefined) => {
          if (!oldData) return oldData;
          return {
            ...oldData,
            data: [...oldData.data, data],
          };
        }
      );
      return { previousExpenses };
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [EXPENSES_KEY] });
      queryClient.invalidateQueries({ queryKey: [TRANSACTIONS_KEY] });
    },
  });
};

// only pending expenses can be updated
export const useUpdateExpenseMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: number; data: TUpdateExpenseRequest }) =>
      updateExpense(id, data),
    onMutate: async ({
      id,
      data,
    }: {
      id: number;
      data: TUpdateExpenseRequest;
    }) => {
      await queryClient.cancelQueries({ queryKey: [EXPENSES_KEY] });
      const previousExpenses = queryClient.getQueryData<
        TPaginationResponse<TExpense>
      >([EXPENSES_KEY]);
      queryClient.setQueryData(
        [EXPENSES_KEY],
        (oldData: TPaginationResponse<TExpense> | undefined) => {
          if (!oldData) return oldData;
          return {
            ...oldData,
            data: oldData.data.map((expense) =>
              expense.id === id ? { ...expense, ...data } : expense
            ),
          };
        }
      );
      return { previousExpenses };
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [EXPENSES_KEY] });
    },
  });
};

// only pending expenses can be deleted
export const useDeleteExpenseMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteExpense(id),
    onMutate: async (id: number) => {
      await queryClient.cancelQueries({ queryKey: [EXPENSES_KEY] });
      const previousExpenses = queryClient.getQueryData<
        TPaginationResponse<TExpense>
      >([EXPENSES_KEY]);
      queryClient.setQueryData(
        [EXPENSES_KEY],
        (oldData: TPaginationResponse<TExpense> | undefined) => {
          if (!oldData) return oldData;
          return {
            ...oldData,
            data: oldData.data.filter((expense) => expense.id !== id),
          };
        }
      );
      return { previousExpenses };
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [EXPENSES_KEY] });
    },
  });
};

export const useGetExpenseSummaryQueryOptions = (
  params: TGetExpenseSummaryParams
) => {
  return queryOptions({
    queryKey: [EXPENSES_KEY, "summary", params],
    queryFn: () => getExpenseSummary(params),
    staleTime: 1000 * 60 * 5,
  });
};

// only pending expenses can be cancelled
export const useCancelExpenseMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => cancelExpense(id),
    onMutate: async (id: number) => {
      await queryClient.cancelQueries({ queryKey: [EXPENSES_KEY] });
      const previousExpenses = queryClient.getQueryData<
        TPaginationResponse<TExpense>
      >([EXPENSES_KEY]);
      queryClient.setQueryData(
        [EXPENSES_KEY],
        (oldData: TPaginationResponse<TExpense> | undefined) => {
          if (!oldData) return oldData;
          return {
            ...oldData,
            data: oldData.data.map((expense) =>
              expense.id === id ? { ...expense, status: "cancelled" } : expense
            ),
          };
        }
      );
      return { previousExpenses };
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [EXPENSES_KEY] });
      queryClient.invalidateQueries({ queryKey: [TRANSACTIONS_KEY] });
    },
  });
};

// only approved expenses can be paid
export const usePayExpenseMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => payExpense(id),
    onMutate: async (id: number) => {
      await queryClient.cancelQueries({ queryKey: [EXPENSES_KEY] });
      const previousExpenses = queryClient.getQueryData<
        TPaginationResponse<TExpense>
      >([EXPENSES_KEY]);
      queryClient.setQueryData(
        [EXPENSES_KEY],
        (oldData: TPaginationResponse<TExpense> | undefined) => {
          if (!oldData) return oldData;
          return {
            ...oldData,
            data: oldData.data.map((expense) =>
              expense.id === id ? { ...expense, status: "paid" } : expense
            ),
          };
        }
      );
      return { previousExpenses };
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [EXPENSES_KEY] });
      queryClient.invalidateQueries({ queryKey: [TRANSACTIONS_KEY] });
    },
  });
};

export const useExportExpensesToExcelMutationOptions = () =>
  mutationOptions({
    mutationFn: (params?: Parameters<typeof exportExpensesToExcel>[0]) =>
      exportExpensesToExcel(params),
  });
