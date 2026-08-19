import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createCustody,
  getAllCustodies,
  getCustodyDetails,
  returnCustody,
} from "./custody.service";
import {
  TCreateCustodyRequest,
  TGetAllCustodiesParams,
  TReturnCustodyRequest,
} from "./custody.types";

export const CUSTODIES_KEY = "CUSTODIES";

export const useCreateCustodyMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({
      order_id,
      data,
    }: {
      order_id: number;
      data: TCreateCustodyRequest;
    }) => createCustody(order_id, data),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CUSTODIES_KEY] });
    },
  });
};

export const useGetAllCustodiesQueryOptions = (
  params: TGetAllCustodiesParams
) => {
  return queryOptions({
    queryKey: [CUSTODIES_KEY, params],
    queryFn: () => getAllCustodies(params),
    staleTime: 1000 * 60 * 5,
  });
};

export const useReturnCustodyMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({
      custody_id,
      data,
    }: {
      custody_id: number;
      data: TReturnCustodyRequest;
    }) => returnCustody(custody_id, data),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CUSTODIES_KEY] });
    },
  });
};

export const useGetCustodyDetailsQueryOptions = (custody_id: number) => {
  return queryOptions({
    queryKey: [CUSTODIES_KEY, custody_id],
    queryFn: () => getCustodyDetails(custody_id),
    staleTime: 1000 * 60 * 5,
  });
};