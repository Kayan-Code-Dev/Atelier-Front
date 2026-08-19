import { TOrder } from "@/api/v2/orders/orders.types";
import { useQuery } from "@tanstack/react-query";
import { useGetEmployeeQueryOptions } from "@/api/v2/employees/employees.hooks";
import { useHasPermission } from "@/api/auth/auth.hooks";

type Props = {
  order: TOrder;
  className?: string;
};

export function syncEmployeeNameFromOrder(order: TOrder): string | null {
  if (typeof order.employee_name === "string" && order.employee_name.trim().length > 0) {
    return order.employee_name.trim();
  }
  const n = order.employee?.user?.name?.trim();
  const e = order.employee?.user?.email?.trim();
  if (n) return n;
  if (e) return e;
  return null;
}

export function useOrderEmployeeResolvedName(
  order: TOrder | null,
  queryEnabled: boolean
): string {
  const employeeId = order?.employee_id && order.employee_id > 0 ? order.employee_id : 0;
  const sync = order ? syncEmployeeNameFromOrder(order) : null;
  const { hasPermission } = useHasPermission("hr.employees.view");

  const needsFetch =
    queryEnabled &&
    !!order &&
    employeeId > 0 &&
    hasPermission &&
    !sync;

  const { data: employeeData } = useQuery({
    ...useGetEmployeeQueryOptions(employeeId),
    enabled: needsFetch,
  });

  if (!order) return "—";
  if (sync) return sync;
  const fetched =
    employeeData?.user?.name?.trim() || employeeData?.user?.email?.trim();
  if (fetched) return fetched;
  if (employeeId) return `#${employeeId}`;
  return "—";
}

/**
 * Displays the name of the employee who created the order.
 * First relies on `order.employee_name` if available from the API,
 * otherwise tries to fetch employee data from `/employees/:id`
 * using `employee_id` found in the order response.
 */
export function OrderEmployeeName({ order, className }: Props) {
  const employeeId = order.employee_id && order.employee_id > 0 ? order.employee_id : null;
  const sync = syncEmployeeNameFromOrder(order);
  const { hasPermission } = useHasPermission("hr.employees.view");

  const needsFetch =
    hasPermission &&
    (employeeId ?? 0) > 0 &&
    !sync;

  const { data: employeeData, isLoading } = useQuery({
    ...useGetEmployeeQueryOptions(employeeId || 0),
    enabled: needsFetch,
  });

  const fetchedName =
    employeeData?.user?.name?.trim() || employeeData?.user?.email?.trim();

  const displayName =
    sync ||
    fetchedName ||
    (employeeId ? `#${employeeId}` : "-");

  if (isLoading && !sync && employeeId) {
    return <span className={className}>جاري التحميل...</span>;
  }

  return <span className={className}>{displayName}</span>;
}
