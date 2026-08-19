import {
  approveWorkshopTransfer,
  createWorkshop,
  deleteWorkshop,
  getWorkshop,
  getWorkshopCloths,
  getWorkshops,
  getWorkshopTransfers,
  updateWorkshop,
  updateWorkshopClothStatus,
  returnWorkshopCloth,
  getWorkshopClothHistory,
  exportWorkshopsToCSV,
} from "./workshop.service";
import {
  infiniteQueryOptions,
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  TCreateWorkshopRequest,
  TUpdateWorkshopRequest,
  TWorkshopClothStatus,
  TWorkshopResponse,
} from "./workshop.types";
import { TPaginationResponse } from "@/api/api-common.types";

export const WORKSHOPS_KEY = "WORKSHOPS";

export const useGetWorkshopsQueryOptions = (page: number, per_page: number, status?: TWorkshopClothStatus) => {
  return queryOptions({
    queryKey: [WORKSHOPS_KEY, page, per_page, status],
    queryFn: () => getWorkshops(page, per_page, status),
  });
};

export const useCreateWorkshopMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: TCreateWorkshopRequest) => createWorkshop(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [WORKSHOPS_KEY] });
    },
  });
};

export const useUpdateWorkshopMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (payload: { id: number; data: TUpdateWorkshopRequest }) =>
      updateWorkshop(payload.id, payload.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [WORKSHOPS_KEY] });
    },
  });
};

export const useDeleteWorkshopMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteWorkshop(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [WORKSHOPS_KEY] });
    },
  });
};

export const useGetWorkshopQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [WORKSHOPS_KEY, id],
    queryFn: () => getWorkshop(id),
  });
};

export const useGetInfiniteWorkshopsQueryOptions = (per_page: number) => {
  return infiniteQueryOptions({
    queryKey: [WORKSHOPS_KEY, "infinite"],
    queryFn: ({ pageParam = 1 }) => getWorkshops(pageParam, per_page),
    initialPageParam: 1,
    staleTime: 1000 * 60 * 5,
    getNextPageParam: (
      lastPage: TPaginationResponse<TWorkshopResponse> | undefined
    ) => {
      if (!lastPage) return undefined;
      if (lastPage.current_page < lastPage.total_pages) {
        return lastPage.current_page + 1;
      }
      return undefined;
    },
  });
};

// Workshop Cloths management

export const useGetWorkshopClothsQueryOptions = (page: number, per_page: number, workshop_id: number, status?: TWorkshopClothStatus) => {
  return queryOptions({
    queryKey: [WORKSHOPS_KEY, "cloths", page, per_page, workshop_id, status],
    queryFn: () => getWorkshopCloths(workshop_id, page, per_page, status),
  });
};

export const useGetWorkshopTransfersQueryOptions = (page: number, per_page: number, workshop_id: number) => {
  return queryOptions({
    queryKey: [WORKSHOPS_KEY, "transfers", page, per_page, workshop_id],
    queryFn: () => getWorkshopTransfers(workshop_id, page, per_page),
  });
};

export const useApproveWorkshopTransferMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (payload: {
      workshop_id: number;
      transfer_id: number;
      items: number[];
    }) =>
      approveWorkshopTransfer(
        payload.workshop_id,
        payload.transfer_id,
        payload.items
      ),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [WORKSHOPS_KEY, "transfers"] });
      queryClient.invalidateQueries({ queryKey: [WORKSHOPS_KEY, "cloths"] });
    },
  });
};

export const useUpdateWorkshopClothStatusMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (payload: {
      workshop_id: number;
      cloth_id: number;
      status: TWorkshopClothStatus;
      notes: string;
    }) =>
      updateWorkshopClothStatus(
        payload.workshop_id,
        payload.cloth_id,
        payload.status,
        payload.notes
      ),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [WORKSHOPS_KEY, "cloths"] });
    },
  });
};

export const useReturnWorkshopClothMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (payload: {
      workshop_id: number;
      cloth_id: number;
      notes: string;
    }) =>
      returnWorkshopCloth(payload.workshop_id, payload.cloth_id, payload.notes),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [WORKSHOPS_KEY, "cloths"] });
    },
  });
};

export const useGetWorkshopClothHistoryQueryOptions = (
  workshop_id: number,
  cloth_id: number
) => {
  return queryOptions({
    queryKey: [WORKSHOPS_KEY, "cloth-history", workshop_id, cloth_id],
    queryFn: () => getWorkshopClothHistory(workshop_id, cloth_id),
  });
};

export const useExportWorkshopsToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportWorkshopsToCSV>[0]) =>
      exportWorkshopsToCSV(params),
  });
};