import { queryOptions } from "@tanstack/react-query";
import { getListByModule, REQUIRES_ID } from "./employees.service";

export const useListByModuleQueryOptions = (
  switchKey: string,
  id: string | number | undefined,
) =>
  queryOptions({
    queryKey: [switchKey, id],
    queryFn: () => getListByModule(switchKey, id),
    enabled: REQUIRES_ID.has(switchKey) ? !!id : true,
  });
