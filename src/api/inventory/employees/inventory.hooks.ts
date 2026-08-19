import { mutationOptions, queryOptions, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { TInventoryItem } from "../inventory.types"; // Adjust path as needed
import { TPaginationResponse } from "@/api/api-common.types";
import {
  approveEmployeeInventoryTransfer,
  createEmployeeInventoryTransfer,
  createEmployeesInventory,
  deleteEmployeesInventory,
  getEmployeeInventoryTransfers,
  getEmployeesInventory,
  rejectEmployeeInventoryTransfer,
  TUpdateEmployeesInventoryRequest,
  updateEmployeesInventory,
} from "./inventory.service";
export const EMPLOYEES_INVENTORY_KEY = "employees-inventory";

type TPageData = TPaginationResponse<TInventoryItem>;

export const useGetEmployeesInventoryQueryOptions = (page: number) => {
  return queryOptions({
    queryKey: [EMPLOYEES_INVENTORY_KEY, page],
    queryFn: () => getEmployeesInventory(page),
  });
};

export const useCreateEmployeesInventoryMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: createEmployeesInventory,
    onSuccess: () => {
      toast.success("تم إنشاء الصنف بنجاح");
      queryClient.invalidateQueries({ queryKey: [EMPLOYEES_INVENTORY_KEY] });
    },
    onError: (err) => {
      toast.error("خطأ في إنشاء الصنف",
        {
          description: err.message
        }
      );
    },
  });
};

export const useUpdateEmployeesInventoryMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({
      data,
      id,
    }: {
      data: TUpdateEmployeesInventoryRequest;
      id: number;
    }) => updateEmployeesInventory(data, id),
    onSuccess: () => {
      toast.success("تم تحديث الصنف بنجاح");
      queryClient.invalidateQueries({ queryKey: [EMPLOYEES_INVENTORY_KEY] });
    },
    onError: (err) => {
      toast.error("خطأ في تحديث الصنف",
        {
          description: err.message
        }
      );
    },
  });
};

export const useDeleteEmployeesInventoryMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: deleteEmployeesInventory,

    onMutate: async (idToDelete: number) => {
      await queryClient.cancelQueries({ queryKey: [EMPLOYEES_INVENTORY_KEY] });
      const previousDataSnapshot = queryClient.getQueriesData<TPageData>({
        queryKey: [EMPLOYEES_INVENTORY_KEY],
      });

      queryClient.setQueriesData<TPageData>(
        { queryKey: [EMPLOYEES_INVENTORY_KEY] },
        (oldData) => {
          if (!oldData) return;
          return {
            ...oldData,
            total: oldData.total - 1,
            data: oldData.data.filter((item) => item.id !== idToDelete),
          };
        }
      );
      return { previousDataSnapshot };
    },

    onError: (_, __, context) => {
      if (context?.previousDataSnapshot) {
        context.previousDataSnapshot.forEach(([queryKey, data]) => {
          queryClient.setQueryData(queryKey, data);
        });
      }
      toast.error("حدث خطأ. تم التراجع عن الحذف");
    },

    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [EMPLOYEES_INVENTORY_KEY] });
    },

    onSuccess: () => {
      toast.success("تم حذف الصنف بنجاح");
    },
  });
};

export const EMPLOYEES_TRANSFER_OPERATIONS_KEY = "employees-transfer-operations";

export const useGetEmployeeTransferOperationsQueryOptions = (page: number) => {
  return queryOptions({
    queryKey: [EMPLOYEES_TRANSFER_OPERATIONS_KEY, page],
    queryFn: () => getEmployeeInventoryTransfers(page),
  });
};

export const useApproveEmployeeTransferOperationMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: approveEmployeeInventoryTransfer,
    onSuccess: () => {
      toast.success("تم الموافقة على الطلب بنجاح");
      queryClient.invalidateQueries({
        queryKey: [EMPLOYEES_TRANSFER_OPERATIONS_KEY],
      });
    },
    onError: () => {
      toast.error("خطأ في الموافقة على الطلب");
    },
  });
};

export const useRejectEmployeeTransferOperationMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: rejectEmployeeInventoryTransfer,
    onSuccess: () => {
      toast.success("تم رفض الطلب بنجاح");
      queryClient.invalidateQueries({
        queryKey: [EMPLOYEES_TRANSFER_OPERATIONS_KEY],
      });
    },
    onError: () => {
      toast.error("خطأ في رفض الطلب");
    },
  });
};

export const useCreateEmployeeInventoryTransferMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: createEmployeeInventoryTransfer,
    onSuccess: () => {
      toast.success("تم إنشاء الحوالة بنجاح");
      queryClient.invalidateQueries({
        queryKey: [EMPLOYEES_TRANSFER_OPERATIONS_KEY],
      });
    },
    onError: (error) => {
      toast.error("خطأ في إنشاء الحوالة", {
        description: error.message,
      });
    },
  });
};
