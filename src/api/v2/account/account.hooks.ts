import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  getProfileApi,
  updateProfileApi,
  changePasswordApi,
  deleteAccountApi,
} from "./account.service";
import { TUpdateProfileRequest, TChangePasswordRequest } from "./account.types";

export const PROFILE_KEY = "profile";

export const useGetProfileQueryOptions = () =>
  queryOptions({
    queryKey: [PROFILE_KEY],
    queryFn: getProfileApi,
    staleTime: 1000 * 60 * 5,
  });

export const useUpdateProfileMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: TUpdateProfileRequest) => updateProfileApi(data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [PROFILE_KEY] });
    },
  });
};

export const useChangePasswordMutationOptions = () =>
  mutationOptions({
    mutationFn: (data: TChangePasswordRequest) => changePasswordApi(data),
  });

export const useDeleteAccountMutationOptions = () =>
  mutationOptions({
    mutationFn: (password: string) => deleteAccountApi(password),
  });
