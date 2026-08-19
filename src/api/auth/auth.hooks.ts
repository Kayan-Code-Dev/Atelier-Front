import { mutationOptions, queryOptions } from "@tanstack/react-query";
import {
  forgetPasswordApi,
  getMyPermissionsApi,
  resendOtpApi,
  resetPasswordApi,
  verifyOtpApi,
} from "./auth.service";
import { useQuery } from "@tanstack/react-query";

export const MY_PERMISSIONS_KEY = "my-permissions";

export const useForgetPasswordMutationOptions = () =>
  mutationOptions({ mutationFn: forgetPasswordApi });

export const useResendOtpMutationOptions = () =>
  mutationOptions({ mutationFn: resendOtpApi });

export const useVerifyOtpMutationOptions = () =>
  mutationOptions({ mutationFn: verifyOtpApi });

export const useResetPasswordMutationOptions = () =>
  mutationOptions({ mutationFn: resetPasswordApi });

export const useGetMyPermissionsQueryOptions = () =>
  queryOptions({
    queryKey: [MY_PERMISSIONS_KEY],
    queryFn: getMyPermissionsApi,
    refetchInterval: 15 * 60 * 1000,
    refetchIntervalInBackground: true,
    refetchOnWindowFocus: false,
  });

export const useMyPermissions = () => {
  return useQuery(useGetMyPermissionsQueryOptions());
};

export const useHasPermission = (
  permission: string | string[]
): { hasPermission: boolean; isPending: boolean } => {
  const { data, isSuccess, isPending } = useMyPermissions();
  const perms = Array.isArray(permission) ? permission : [permission];
  const hasPermission =
    isSuccess &&
    Array.isArray(data) &&
    perms.some((p) => p === "" || data.includes(p));
  return { hasPermission: !!hasPermission, isPending };
};
