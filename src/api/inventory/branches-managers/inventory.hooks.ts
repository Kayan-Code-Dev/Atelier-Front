import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  approveInventoryTransfer,
  createInventoryTransfer,
  getInventoryBranches,
  getInventoryCategoriesByBranch,
  getInventoryItems,
  getInventorySubCategoriesByCategory,
  getInventoryTransfers,
  rejectInventoryTransfer,
} from "./inventory.service";

export const INVENTORY_KEY = "inventory";
export const INVENTORY_TRANSFERS_KEY = "inventory-transfers";
export const INVENTORY_BRANCHES_KEY = "inventory-branches";
export const INVENTORY_CATEGORIES_KEY = "inventory-categories";
export const INVENTORY_SUB_CATEGORIES_KEY = "inventory-sub-categories";

export const useGetInventoryItemsQueryOptions = (page: number) => {
  return queryOptions({
    queryKey: [INVENTORY_KEY, page],
    queryFn: () => getInventoryItems(page),
  });
};

export const useGetInventoryTransfersQueryOptions = (page: number) => {
  return queryOptions({
    queryKey: [INVENTORY_TRANSFERS_KEY, page],
    queryFn: () => getInventoryTransfers(page),
  });
};

export const useGetInventoryBranchesQueryOptions = () => {
  return queryOptions({
    queryKey: [INVENTORY_BRANCHES_KEY],
    queryFn: getInventoryBranches,
  });
};

export const useGetInventoryCategoriesByBranchQueryOptions = (
  branchId: number
) => {
  return queryOptions({
    queryKey: [INVENTORY_CATEGORIES_KEY, branchId],
    queryFn: () => getInventoryCategoriesByBranch(branchId),
    enabled: !!branchId,
  });
};

export const useGetInventorySubCategoriesByCategoryQueryOptions = (
  categoryId: number
) => {
  return queryOptions({
    queryKey: [INVENTORY_SUB_CATEGORIES_KEY, categoryId],
    queryFn: () => getInventorySubCategoriesByCategory(categoryId),
    enabled: !!categoryId,
  });
};

export const useCreateInventoryTransferMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: createInventoryTransfer,
    onSettled: () => {
      queryClient.invalidateQueries({
        queryKey: [INVENTORY_TRANSFERS_KEY],
      });
    },
  });
};

export const useApproveInventoryTransferMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: approveInventoryTransfer,
    onSettled: () => {
      queryClient.invalidateQueries({
        queryKey: [INVENTORY_TRANSFERS_KEY],
      });
    },
  });
};

export const useRejectInventoryTransferMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: rejectInventoryTransfer,
    onSettled: () => {
      queryClient.invalidateQueries({
        queryKey: [INVENTORY_TRANSFERS_KEY],
      });
    },
  });
};
