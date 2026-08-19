import { queryOptions } from "@tanstack/react-query";
import { getPermissionsList } from "./permissions.service";

export const PERMISSIONS_KEY = "permissions";

export const usePermissionsListQueryOptions = (current_page = 1) => {
  return queryOptions({
    queryKey: [PERMISSIONS_KEY, current_page],
    queryFn: () => getPermissionsList({ current_page }),
    staleTime: 1000 * 60 * 5,
  });
};
