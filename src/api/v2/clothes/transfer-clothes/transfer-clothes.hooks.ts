import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  approveTransferClothes,
  createTransferClothes,
  deleteTransferClothes,
  getTransferClotheById,
  getTransferClothes,
  approvePartialTransferClothes,
  rejectTransferClothes,
  updateTransferClothes,
  rejectPartialTransferClothes,
  exportTransferClothesToCSV,
} from "./transfer-clothes.service";
import {
  TGetTransferClothesQuery,
  TUpdateTransferClothesRequest,
} from "./transfer-clothes.types";

export const TRANSFER_CLOTHES_KEY = "transfer-clothes";

export const useCreateTransferClothesMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: createTransferClothes,
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [TRANSFER_CLOTHES_KEY] });
    },
  });
};

export const useGetTransferClothesQueryOptions = (
  query: TGetTransferClothesQuery
) => {
  return queryOptions({
    queryKey: [TRANSFER_CLOTHES_KEY, query],
    queryFn: () => getTransferClothes(query),
  });
};

// ONLY FOR PENDING TRANSFERS

export const useUpdateTransferClothesMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({
      id,
      data,
    }: {
      id: number;
      data: TUpdateTransferClothesRequest;
    }) => updateTransferClothes(id, data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [TRANSFER_CLOTHES_KEY] });
    },
  });
};

// ONLY FOR PENDING TRANSFERS
export const useDeleteTransferClothesMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: deleteTransferClothes,
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [TRANSFER_CLOTHES_KEY] });
    },
  });
};

export const useApproveTransferClothesMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: approveTransferClothes,
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [TRANSFER_CLOTHES_KEY] });
    },
  });
};

export const useRejectTransferClothesMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: rejectTransferClothes,
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [TRANSFER_CLOTHES_KEY] });
    },
  });
};

export const useGetTransferClotheByIdQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [TRANSFER_CLOTHES_KEY, "single", id],
    queryFn: () => getTransferClotheById(id),
  });
};

export const useApprovePartialTransferClothesMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, cloth_ids }: { id: number; cloth_ids: number[] }) =>
      approvePartialTransferClothes(id, cloth_ids),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [TRANSFER_CLOTHES_KEY] });
    },
  });
};

export const useRejectPartialTransferClothesMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, cloth_ids }: { id: number; cloth_ids: number[] }) =>
      rejectPartialTransferClothes(id, cloth_ids),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [TRANSFER_CLOTHES_KEY] });
    },
  });
};

export const useExportTransferClothesToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportTransferClothesToCSV>[0]) =>
      exportTransferClothesToCSV(params),
  });
};