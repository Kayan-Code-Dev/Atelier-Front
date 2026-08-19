import {
  infiniteQueryOptions,
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createEmployee,
  getEmployees,
  getEmployee,
  updateEmployee,
  getEmployeeAssignments,
  terminateEmployee,
  exportEmployeesToExcel,
} from "./employees.service";
import {
  TGetEmployeesParams,
  TTerminateEmployeeRequest,
  TUpdateEmployeeRequest,
} from "./employees.types";

export const EMPLOYEES_KEY = "EMPLOYEES_KEY";

export const useCreateEmployeeQueryOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: createEmployee,
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [EMPLOYEES_KEY] });
    },
  });
};

export const useGetEmployeesQueryOptions = (params: TGetEmployeesParams) => {
  return queryOptions({
    queryKey: [EMPLOYEES_KEY, params],
    queryFn: () => getEmployees(params),
    staleTime: 1000 * 60 * 5,
  });
};

// infinite query used in selecting employees (multi select) combobox
export const useGetInfiniteEmployeesQueryOptions = (
  params: TGetEmployeesParams
) => {
  return infiniteQueryOptions({
    queryKey: [EMPLOYEES_KEY, "infinite", params],
    queryFn: ({ pageParam = 1 }) =>
      getEmployees({ ...params, page: pageParam, per_page: params.per_page }),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      if (!lastPage) return undefined;
      if (lastPage.current_page < lastPage.total_pages) {
        return lastPage.current_page + 1;
      }
      return undefined;
    },
  });
};

export const useGetEmployeeQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [EMPLOYEES_KEY, id],
    queryFn: () => getEmployee(id),
    enabled: id > 0,
    staleTime: 1000 * 60 * 5,
  });
};

export const useUpdateEmployeeQueryOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: number; data: TUpdateEmployeeRequest }) =>
      updateEmployee(id, data),
    onSettled: (_, __, { id }) => {
      queryClient.invalidateQueries({ queryKey: [EMPLOYEES_KEY] });
      queryClient.invalidateQueries({ queryKey: [EMPLOYEES_KEY, id] });
      queryClient.invalidateQueries({
        queryKey: [EMPLOYEES_KEY, id, "entities"],
      });
    },
  });
};

export const useGetEmployeeAssignmentsQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [EMPLOYEES_KEY, id, "entities"],
    queryFn: () => getEmployeeAssignments(id),
    enabled: id > 0,
    staleTime: 1000 * 60 * 5,
  });
};

export const useTerminateEmployeeQueryOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({
      id,
      data,
    }: {
      id: number;
      data: TTerminateEmployeeRequest;
    }) => terminateEmployee(id, data),
    onSettled: (_, __, { id }) => {
      queryClient.invalidateQueries({ queryKey: [EMPLOYEES_KEY] });
      queryClient.invalidateQueries({ queryKey: [EMPLOYEES_KEY, id] });
    },
  });
};

export const useExportEmployeesToExcelMutationOptions = () =>
  mutationOptions({
    mutationFn: (params?: Parameters<typeof exportEmployeesToExcel>[0]) =>
      exportEmployeesToExcel(params),
  });
