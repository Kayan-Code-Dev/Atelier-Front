import { TPaginationResponse } from "@/api/api-common.types";
import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createEmployeeCustody,
  deleteEmployeeCustody,
  getAllEmployeeCustodies,
  getEmployeeCustodyById,
  getEmployeeCustodyTypes,
  markEmployeeCustodyAsDamaged,
  markEmployeeCustodyAsLost,
  markEmployeeCustodyAsReturned,
  updateEmployeeCustody,
} from "./employee-custodies.service";
import {
  TEmployeeCustody,
  TEmployeeCustodyConditionOnAssignment,
  TGetEmployeeCustodiesParams,
  TUpdateEmployeeCustody
} from "./employee-custodies.types";

export const EMPLOYEE_CUSTODIES_KEY = "EMPLOYEE_CUSTODIES_KEY";

export const useCreateEmployeeCustodyMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: createEmployeeCustody,
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });
    },
  });
};

export const useGetAllEmployeeCustodiesQueryOptions = (
  params: TGetEmployeeCustodiesParams
) => {
  return queryOptions({
    queryKey: [EMPLOYEE_CUSTODIES_KEY, params],
    queryFn: () => getAllEmployeeCustodies(params),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetEmployeeCustodyByIdQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [EMPLOYEE_CUSTODIES_KEY, id],
    queryFn: () => getEmployeeCustodyById(id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useUpdateEmployeeCustodyMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: number; data: TUpdateEmployeeCustody }) =>
      updateEmployeeCustody(id, data),
    onMutate: async ({ id, data }) => {
      // Cancel any outgoing refetches
      await qClient.cancelQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });

      // Snapshot the previous value
      const previousQueries = qClient.getQueriesData<
        TPaginationResponse<TEmployeeCustody>
      >({
        queryKey: [EMPLOYEE_CUSTODIES_KEY],
      });
      const previousEmployeeCustody = qClient.getQueryData<TEmployeeCustody>([
        EMPLOYEE_CUSTODIES_KEY,
        id,
      ]);

      // Optimistically update the employee custody in all paginated lists
      qClient.setQueriesData<TPaginationResponse<TEmployeeCustody>>(
        { queryKey: [EMPLOYEE_CUSTODIES_KEY], exact: false },
        (oldData) => {
          // Only process if it's a paginated response structure
          if (
            !oldData ||
            !Array.isArray(oldData.data) ||
            typeof oldData.total !== "number"
          ) {
            return oldData;
          }

          return {
            ...oldData,
            data: oldData.data.map((custody) =>
              custody.id === id
                ? { ...custody, ...data, updated_at: new Date().toISOString() }
                : custody
            ),
          };
        }
      );

      // Optimistically update the single employee custody query if it exists
      if (previousEmployeeCustody) {
        qClient.setQueryData<TEmployeeCustody>(
          [EMPLOYEE_CUSTODIES_KEY, id],
          (oldData) => {
            if (!oldData) return oldData;
            return { ...oldData, ...data, updated_at: new Date().toISOString() };
          }
        );
      }

      // Return context with previous data for rollback
      return { previousQueries, previousEmployeeCustody };
    },
    onError: (_, __, context) => {
      // Rollback on error
      if (context?.previousQueries) {
        context.previousQueries.forEach(([queryKey, data]) => {
          qClient.setQueryData(queryKey, data);
        });
      }
      if (context?.previousEmployeeCustody) {
        qClient.setQueryData(
          [EMPLOYEE_CUSTODIES_KEY, context.previousEmployeeCustody.id],
          context.previousEmployeeCustody
        );
      }
    },
    onSuccess: (updatedEmployeeCustody) => {
      if (!updatedEmployeeCustody) return;

      // Update with the actual server response
      qClient.setQueriesData<TPaginationResponse<TEmployeeCustody>>(
        { queryKey: [EMPLOYEE_CUSTODIES_KEY], exact: false },
        (oldData) => {
          // Only process if it's a paginated response structure
          if (
            !oldData ||
            !Array.isArray(oldData.data) ||
            typeof oldData.total !== "number"
          ) {
            return oldData;
          }

          return {
            ...oldData,
            data: oldData.data.map((custody) =>
              custody.id === updatedEmployeeCustody.id
                ? updatedEmployeeCustody
                : custody
            ),
          };
        }
      );

      // Update the single employee custody query
      qClient.setQueryData(
        [EMPLOYEE_CUSTODIES_KEY, updatedEmployeeCustody.id],
        updatedEmployeeCustody
      );
    },
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });
    },
  });
};

export const useDeleteEmployeeCustodyMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: deleteEmployeeCustody,
    onMutate: async (id: number) => {
      // Cancel any outgoing refetches
      await qClient.cancelQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });

      // Snapshot the previous value
      const previousQueries = qClient.getQueriesData<
        TPaginationResponse<TEmployeeCustody>
      >({
        queryKey: [EMPLOYEE_CUSTODIES_KEY],
      });
      const previousEmployeeCustody = qClient.getQueryData<TEmployeeCustody>([
        EMPLOYEE_CUSTODIES_KEY,
        id,
      ]);

      // Optimistically remove the employee custody from all paginated lists
      qClient.setQueriesData<TPaginationResponse<TEmployeeCustody>>(
        { queryKey: [EMPLOYEE_CUSTODIES_KEY], exact: false },
        (oldData) => {
          // Only process if it's a paginated response structure
          if (
            !oldData ||
            !Array.isArray(oldData.data) ||
            typeof oldData.total !== "number"
          ) {
            return oldData;
          }

          return {
            ...oldData,
            data: oldData.data.filter((custody) => custody.id !== id),
            total: Math.max(0, oldData.total - 1),
          };
        }
      );

      // Remove the single employee custody query
      qClient.removeQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY, id] });

      // Return context with previous data for rollback
      return { previousQueries, previousEmployeeCustody };
    },
    onError: (_, id, context) => {
      // Rollback on error
      if (context?.previousQueries) {
        context.previousQueries.forEach(([queryKey, data]) => {
          qClient.setQueryData(queryKey, data);
        });
      }
      if (context?.previousEmployeeCustody) {
        qClient.setQueryData(
          [EMPLOYEE_CUSTODIES_KEY, id],
          context.previousEmployeeCustody
        );
      }
    },
    onSuccess: () => {
      // No additional updates needed on success, cache was already updated optimistically
    },
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });
    },
  });
};

export const useGetEmployeeCustodyTypesQueryOptions = () => {
  return queryOptions({
    queryKey: [EMPLOYEE_CUSTODIES_KEY, "types"],
    queryFn: getEmployeeCustodyTypes,
    staleTime: 1000 * 60 * 5,
  });
};

export const useMarkEmployeeCustodyAsReturnedMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({
      id,
      data,
    }: {
      id: number;
      data: {
        condition_on_return: TEmployeeCustodyConditionOnAssignment;
        return_notes: string;
      };
    }) => markEmployeeCustodyAsReturned(id, data),
    onMutate: async ({ id }) => {
      // Cancel any outgoing refetches
      await qClient.cancelQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });

      // Snapshot the previous value
      const previousQueries = qClient.getQueriesData<
        TPaginationResponse<TEmployeeCustody>
      >({
        queryKey: [EMPLOYEE_CUSTODIES_KEY],
      });
      const previousEmployeeCustody = qClient.getQueryData<TEmployeeCustody>([
        EMPLOYEE_CUSTODIES_KEY,
        id,
      ]);

      // Optimistically update the employee custody status to "returned"
      qClient.setQueriesData<TPaginationResponse<TEmployeeCustody>>(
        { queryKey: [EMPLOYEE_CUSTODIES_KEY], exact: false },
        (oldData) => {
          // Only process if it's a paginated response structure
          if (
            !oldData ||
            !Array.isArray(oldData.data) ||
            typeof oldData.total !== "number"
          ) {
            return oldData;
          }

          return {
            ...oldData,
            data: oldData.data.map((custody) =>
              custody.id === id
                ? {
                    ...custody,
                    status: "returned" as const,
                    updated_at: new Date().toISOString(),
                  }
                : custody
            ),
          };
        }
      );

      // Optimistically update the single employee custody query if it exists
      if (previousEmployeeCustody) {
        qClient.setQueryData<TEmployeeCustody>(
          [EMPLOYEE_CUSTODIES_KEY, id],
          (oldData) => {
            if (!oldData) return oldData;
            return {
              ...oldData,
              status: "returned" as const,
              updated_at: new Date().toISOString(),
            };
          }
        );
      }

      // Return context with previous data for rollback
      return { previousQueries, previousEmployeeCustody };
    },
    onError: (_, __, context) => {
      // Rollback on error
      if (context?.previousQueries) {
        context.previousQueries.forEach(([queryKey, data]) => {
          qClient.setQueryData(queryKey, data);
        });
      }
      if (context?.previousEmployeeCustody) {
        qClient.setQueryData(
          [EMPLOYEE_CUSTODIES_KEY, context.previousEmployeeCustody.id],
          context.previousEmployeeCustody
        );
      }
    },
    onSuccess: () => {
      // Cache will be invalidated on settled to ensure consistency
    },
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });
    },
  });
};

export const useMarkEmployeeCustodyAsLostMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, notes }: { id: number; notes: string }) =>
      markEmployeeCustodyAsLost(id, notes),
    onMutate: async ({ id }) => {
      // Cancel any outgoing refetches
      await qClient.cancelQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });

      // Snapshot the previous value
      const previousQueries = qClient.getQueriesData<
        TPaginationResponse<TEmployeeCustody>
      >({
        queryKey: [EMPLOYEE_CUSTODIES_KEY],
      });
      const previousEmployeeCustody = qClient.getQueryData<TEmployeeCustody>([
        EMPLOYEE_CUSTODIES_KEY,
        id,
      ]);

      // Optimistically update the employee custody status to "lost"
      qClient.setQueriesData<TPaginationResponse<TEmployeeCustody>>(
        { queryKey: [EMPLOYEE_CUSTODIES_KEY], exact: false },
        (oldData) => {
          // Only process if it's a paginated response structure
          if (
            !oldData ||
            !Array.isArray(oldData.data) ||
            typeof oldData.total !== "number"
          ) {
            return oldData;
          }

          return {
            ...oldData,
            data: oldData.data.map((custody) =>
              custody.id === id
                ? {
                    ...custody,
                    status: "lost" as const,
                    updated_at: new Date().toISOString(),
                  }
                : custody
            ),
          };
        }
      );

      // Optimistically update the single employee custody query if it exists
      if (previousEmployeeCustody) {
        qClient.setQueryData<TEmployeeCustody>(
          [EMPLOYEE_CUSTODIES_KEY, id],
          (oldData) => {
            if (!oldData) return oldData;
            return {
              ...oldData,
              status: "lost" as const,
              updated_at: new Date().toISOString(),
            };
          }
        );
      }

      // Return context with previous data for rollback
      return { previousQueries, previousEmployeeCustody };
    },
    onError: (_, __, context) => {
      // Rollback on error
      if (context?.previousQueries) {
        context.previousQueries.forEach(([queryKey, data]) => {
          qClient.setQueryData(queryKey, data);
        });
      }
      if (context?.previousEmployeeCustody) {
        qClient.setQueryData(
          [EMPLOYEE_CUSTODIES_KEY, context.previousEmployeeCustody.id],
          context.previousEmployeeCustody
        );
      }
    },
    onSuccess: () => {
      // Cache will be invalidated on settled to ensure consistency
    },
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });
    },
  });
};

export const useMarkEmployeeCustodyAsDamagedMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, notes }: { id: number; notes: string }) =>
      markEmployeeCustodyAsDamaged(id, notes),
    onMutate: async ({ id }) => {
      // Cancel any outgoing refetches
      await qClient.cancelQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });

      // Snapshot the previous value
      const previousQueries = qClient.getQueriesData<
        TPaginationResponse<TEmployeeCustody>
      >({
        queryKey: [EMPLOYEE_CUSTODIES_KEY],
      });
      const previousEmployeeCustody = qClient.getQueryData<TEmployeeCustody>([
        EMPLOYEE_CUSTODIES_KEY,
        id,
      ]);

      // Optimistically update the employee custody status to "damaged"
      qClient.setQueriesData<TPaginationResponse<TEmployeeCustody>>(
        { queryKey: [EMPLOYEE_CUSTODIES_KEY], exact: false },
        (oldData) => {
          // Only process if it's a paginated response structure
          if (
            !oldData ||
            !Array.isArray(oldData.data) ||
            typeof oldData.total !== "number"
          ) {
            return oldData;
          }

          return {
            ...oldData,
            data: oldData.data.map((custody) =>
              custody.id === id
                ? {
                    ...custody,
                    status: "damaged" as const,
                    updated_at: new Date().toISOString(),
                  }
                : custody
            ),
          };
        }
      );

      // Optimistically update the single employee custody query if it exists
      if (previousEmployeeCustody) {
        qClient.setQueryData<TEmployeeCustody>(
          [EMPLOYEE_CUSTODIES_KEY, id],
          (oldData) => {
            if (!oldData) return oldData;
            return {
              ...oldData,
              status: "damaged" as const,
              updated_at: new Date().toISOString(),
            };
          }
        );
      }

      // Return context with previous data for rollback
      return { previousQueries, previousEmployeeCustody };
    },
    onError: (_, __, context) => {
      // Rollback on error
      if (context?.previousQueries) {
        context.previousQueries.forEach(([queryKey, data]) => {
          qClient.setQueryData(queryKey, data);
        });
      }
      if (context?.previousEmployeeCustody) {
        qClient.setQueryData(
          [EMPLOYEE_CUSTODIES_KEY, context.previousEmployeeCustody.id],
          context.previousEmployeeCustody
        );
      }
    },
    onSuccess: () => {
      // Cache will be invalidated on settled to ensure consistency
    },
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [EMPLOYEE_CUSTODIES_KEY] });
    },
  });
};
