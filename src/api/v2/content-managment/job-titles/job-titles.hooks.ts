import {
  infiniteQueryOptions,
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  TCreateJobTitleRequest,
  TGetJobTitlesParams,
  TJobTitle,
  TUpdateJobTitleRequest,
} from "./job-titles.types";
import {
  createJobTitle,
  deleteJobTitle,
  getJobTitle,
  getJobTitleLevels,
  getJobTitleRoles,
  getJobTitles,
  syncJobTitleRoles,
  updateJobTitle,
} from "./job-titles.service";
import { TPaginationResponse } from "@/api/api-common.types";

export const JOB_TITLES_KEY = "job-titles";
export const JOB_TITLE_LEVELS_KEY = "job-title-levels";
export const JOB_TITLE_ROLES_KEY = "job-title-roles";

export const useGetJobTitlesQueryOptions = (params: TGetJobTitlesParams) => {
  return queryOptions({
    queryKey: [JOB_TITLES_KEY, params],
    queryFn: () => getJobTitles(params),
    staleTime: 1000 * 60 * 5,
  });
};

export const useCreateJobTitleMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: createJobTitle,
    onMutate: async (newJobTitle: TCreateJobTitleRequest) => {
      // Cancel any outgoing refetches
      await queryClient.cancelQueries({ queryKey: [JOB_TITLES_KEY] });

      // Snapshot the previous value
      const previousQueries = queryClient.getQueriesData<
        TPaginationResponse<TJobTitle>
      >({
        queryKey: [JOB_TITLES_KEY],
      });

      // Generate a temporary optimistic ID
      const optimisticId = Date.now();

      // Optimistically add the new job title to all paginated lists
      // Only update queries that have the paginated response structure
      queryClient.setQueriesData<TPaginationResponse<TJobTitle>>(
        { queryKey: [JOB_TITLES_KEY], exact: false },
        (oldData) => {
          // Only process if it's a paginated response structure
          if (
            !oldData ||
            !Array.isArray(oldData.data) ||
            typeof oldData.total !== "number"
          ) {
            return oldData;
          }

          // Create a temporary optimistic job title
          const optimisticJobTitle: TJobTitle = {
            id: optimisticId, // Temporary ID
            code: newJobTitle.code,
            name: newJobTitle.name,
            description: newJobTitle.description,
            department_id: newJobTitle.department_id,
            level: newJobTitle.level,
            min_salary: newJobTitle.min_salary,
            max_salary: newJobTitle.max_salary,
            is_active: newJobTitle.is_active,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            department: {} as any, // Will be filled by server response
            roles: [],
          };

          return {
            ...oldData,
            data: [optimisticJobTitle, ...oldData.data],
            total: oldData.total + 1,
          };
        }
      );

      // Return context with previous queries and optimistic ID for rollback/replacement
      return { previousQueries, optimisticId };
    },
    onError: (_, __, context) => {
      // Rollback on error
      if (context?.previousQueries) {
        context.previousQueries.forEach(([queryKey, data]) => {
          queryClient.setQueryData(queryKey, data);
        });
      }
    },
    onSuccess: (newJobTitle, _, context) => {
      if (!newJobTitle || !context?.optimisticId) return;

      // Update the cache with the actual server response
      // Only update queries that have the paginated response structure
      queryClient.setQueriesData<TPaginationResponse<TJobTitle>>(
        { queryKey: [JOB_TITLES_KEY], exact: false },
        (oldData) => {
          // Only process if it's a paginated response structure
          if (
            !oldData ||
            !Array.isArray(oldData.data) ||
            typeof oldData.total !== "number"
          ) {
            return oldData;
          }

          // Replace the optimistic entry with the real one
          const dataWithoutOptimistic = oldData.data.filter(
            (item) => item.id !== context.optimisticId
          );

          return {
            ...oldData,
            data: [newJobTitle, ...dataWithoutOptimistic],
          };
        }
      );
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [JOB_TITLES_KEY] });
    },
  });
};

export const useUpdateJobTitleMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: number; data: TUpdateJobTitleRequest }) =>
      updateJobTitle(id, data),
    onMutate: async ({ id, data }) => {
      // Cancel any outgoing refetches
      await queryClient.cancelQueries({ queryKey: [JOB_TITLES_KEY] });

      // Snapshot the previous value
      const previousQueries = queryClient.getQueriesData<
        TPaginationResponse<TJobTitle>
      >({
        queryKey: [JOB_TITLES_KEY],
      });
      const previousJobTitle = queryClient.getQueryData<TJobTitle>([
        JOB_TITLES_KEY,
        id,
      ]);

      // Optimistically update the job title in all paginated lists
      // Only update queries that have the paginated response structure
      queryClient.setQueriesData<TPaginationResponse<TJobTitle>>(
        { queryKey: [JOB_TITLES_KEY], exact: false },
        (oldData) => {
          // Only process if it's a paginated response structure (has data array)
          // This filters out single item queries like [JOB_TITLES_KEY, id]
          if (
            !oldData ||
            !Array.isArray(oldData.data) ||
            typeof oldData.total !== "number"
          ) {
            return oldData;
          }

          return {
            ...oldData,
            data: oldData.data.map((jobTitle) =>
              jobTitle.id === id
                ? { ...jobTitle, ...data, updated_at: new Date().toISOString() }
                : jobTitle
            ),
          };
        }
      );

      // Optimistically update the single job title query if it exists
      if (previousJobTitle) {
        queryClient.setQueryData<TJobTitle>([JOB_TITLES_KEY, id], (oldData) => {
          if (!oldData) return oldData;
          return { ...oldData, ...data, updated_at: new Date().toISOString() };
        });
      }

      // Return context with previous data for rollback
      return { previousQueries, previousJobTitle };
    },
    onError: (_, __, context) => {
      // Rollback on error
      if (context?.previousQueries) {
        context.previousQueries.forEach(([queryKey, data]) => {
          queryClient.setQueryData(queryKey, data);
        });
      }
      if (context?.previousJobTitle) {
        queryClient.setQueryData(
          [JOB_TITLES_KEY, context.previousJobTitle.id],
          context.previousJobTitle
        );
      }
    },
    onSuccess: (updatedJobTitle) => {
      if (!updatedJobTitle) return;

      // Update with the actual server response
      // Only update queries that have the paginated response structure
      queryClient.setQueriesData<TPaginationResponse<TJobTitle>>(
        { queryKey: [JOB_TITLES_KEY], exact: false },
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
            data: oldData.data.map((jobTitle) =>
              jobTitle.id === updatedJobTitle.id ? updatedJobTitle : jobTitle
            ),
          };
        }
      );

      // Update the single job title query
      queryClient.setQueryData(
        [JOB_TITLES_KEY, updatedJobTitle.id],
        updatedJobTitle
      );
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [JOB_TITLES_KEY] });
    },
  });
};

export const useDeleteJobTitleMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: deleteJobTitle,
    onMutate: async (id: number) => {
      // Cancel any outgoing refetches
      await queryClient.cancelQueries({ queryKey: [JOB_TITLES_KEY] });

      // Snapshot the previous value
      const previousQueries = queryClient.getQueriesData<
        TPaginationResponse<TJobTitle>
      >({
        queryKey: [JOB_TITLES_KEY],
      });
      const previousJobTitle = queryClient.getQueryData<TJobTitle>([
        JOB_TITLES_KEY,
        id,
      ]);

      // Optimistically remove the job title from all paginated lists
      // Only update queries that have the paginated response structure
      queryClient.setQueriesData<TPaginationResponse<TJobTitle>>(
        { queryKey: [JOB_TITLES_KEY], exact: false },
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
            data: oldData.data.filter((jobTitle) => jobTitle.id !== id),
            total: Math.max(0, oldData.total - 1),
          };
        }
      );

      // Remove the single job title query
      queryClient.removeQueries({ queryKey: [JOB_TITLES_KEY, id] });

      // Return context with previous data for rollback
      return { previousQueries, previousJobTitle };
    },
    onError: (_, id, context) => {
      // Rollback on error
      if (context?.previousQueries) {
        context.previousQueries.forEach(([queryKey, data]) => {
          queryClient.setQueryData(queryKey, data);
        });
      }
      if (context?.previousJobTitle) {
        queryClient.setQueryData(
          [JOB_TITLES_KEY, id],
          context.previousJobTitle
        );
      }
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [JOB_TITLES_KEY] });
    },
  });
};

export const useGetJobTitleQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [JOB_TITLES_KEY, id],
    queryFn: () => getJobTitle(id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetJobTitleLevelsQueryOptions = () => {
  return queryOptions({
    queryKey: [JOB_TITLE_LEVELS_KEY],
    queryFn: () => getJobTitleLevels(),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetJobTitleRolesQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [JOB_TITLE_ROLES_KEY, id],
    queryFn: () => getJobTitleRoles(id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useSyncJobTitleRolesMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, role_ids }: { id: number; role_ids: number[] }) =>
      syncJobTitleRoles(id, role_ids),
    onSettled: (_, __, { id }) => {
      queryClient.invalidateQueries({ queryKey: [JOB_TITLE_ROLES_KEY, id] });
      queryClient.invalidateQueries({ queryKey: [JOB_TITLES_KEY] });
    },
  });
};
// infinite query options, and use with shadcn compobox to get job titles and search by name (multi select)
export const useGetInfiniteJobTitlesQueryOptions = (per_page: number) => {
  return infiniteQueryOptions({
    queryKey: [JOB_TITLES_KEY, "infinite"],
    queryFn: ({ pageParam = 1 }) => getJobTitles({ page: pageParam, per_page }),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      if (!lastPage) return undefined;
      if (lastPage.current_page < lastPage.total_pages) {
        return lastPage.current_page + 1;
      }
    },
  });
};
