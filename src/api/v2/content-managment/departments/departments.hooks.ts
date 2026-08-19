import {
  infiniteQueryOptions,
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createDepartment,
  deleteDepartment,
  getDepartments,
  updateDepartment,
} from "./departments.service";
import {
  TCreateDepartmentRequest,
  TGetDepartmentsParams,
  TUpdateDepartmentRequest,
} from "./departments.types";

export const DEPARTMENTS_KEY = "departments";

export const useDepartmentsQueryOptions = (params: TGetDepartmentsParams) => {
  return queryOptions({
    queryKey: [DEPARTMENTS_KEY, params],
    queryFn: () => getDepartments(params),
    staleTime: 1000 * 60 * 60, // Cache for 1 hour
  });
};

export const useCreateDepartmentMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: TCreateDepartmentRequest) => createDepartment(data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [DEPARTMENTS_KEY] });
    },
  });
};

export const useUpdateDepartmentMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({
      id,
      data,
    }: {
      id: number;
      data: TUpdateDepartmentRequest;
    }) => updateDepartment(id, data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [DEPARTMENTS_KEY] });
    },
  });
};

export const useDeleteDepartmentMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteDepartment(id),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [DEPARTMENTS_KEY] });
    },
  });
};

// infinite query options, will be used with shadcn compobox to get departments and search by name
export const useGetInfiniteDepartmentsQueryOptions = (per_page: number) => {
  return infiniteQueryOptions({
    queryKey: [DEPARTMENTS_KEY, "infinite"],
    queryFn: ({ pageParam = 1 }) =>
      getDepartments({ page: pageParam, per_page, is_active: true }),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      if (!lastPage) return undefined;
      if (lastPage.current_page < lastPage.total_pages) {
        return lastPage.current_page + 1;
      }
      return undefined;
    },
    staleTime: 1000 * 60 * 5,
  });
};
