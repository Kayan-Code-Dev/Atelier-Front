
import type { TEntity } from "@/lib/types/entity.types";
import type { TGetEmployeesParams } from "@/api/v2/employees/employees.types";
import type {
  TCreateOrderItemRequest,
  TOrderType,
} from "@/api/v2/orders/orders.types";


export function processTypeForOrderLine(
  type: TOrderType
): "rent" | "sold" | undefined {
  if (type === "rent") return "rent";
  if (type === "buy") return "sold";
  return undefined;
}

export const ORDER_ACTIVE_EMPLOYEES_PER_PAGE = 20;


export function employeesParamsForOrderEntity(
  entityType: TEntity | undefined,
  entityId: string
): TGetEmployeesParams {
  const base: TGetEmployeesParams = {
    per_page: ORDER_ACTIVE_EMPLOYEES_PER_PAGE,
    employment_status: "active",
  };
  if (entityType === "branch" && entityId) {
    return { ...base, branch_id: Number(entityId) };
  }
  return base;
}


export function formatDateInputToOrderApi(ymd: string): string {
  return `${ymd} 12:00:00`;
}

export function computeDaysOfRentFromYmd(
  receiveYmd: string,
  returnYmd: string
): number {
  if (!receiveYmd || !returnYmd) return 1;
  const d = new Date(`${receiveYmd}T12:00:00`);
  const r = new Date(`${returnYmd}T12:00:00`);
  const diff = Math.ceil((r.getTime() - d.getTime()) / 86400000);
  return Math.max(1, diff);
}

export function isReturnYmdBeforeReceiveYmd(
  receiveYmd: string,
  returnYmd: string
): boolean {
  if (!receiveYmd || !returnYmd) return false;
  return (
    new Date(`${returnYmd}T12:00:00`) < new Date(`${receiveYmd}T12:00:00`)
  );
}

export function displayClientLabel(
  c: {
    name?: string;
    first_name?: string;
    middle_name?: string;
    last_name?: string;
  } | undefined
): string {
  if (!c) return "";
  if (c.name?.trim()) return c.name.trim();
  const parts = [c.first_name, c.middle_name, c.last_name].filter(Boolean);
  return parts.join(" ").trim();
}

export type RentLineInput = {
  cloth_id: number;
  price: number;
  quantity: number;
};


export function buildRentLineItemsForOrderApi(input: {
  lines: RentLineInput[];
  daysOfRent: number;
  occasionApi?: string;
  returnVisitApi?: string;
  paidDepositOnFirst: number;
}): TCreateOrderItemRequest[] {
  return input.lines.map((p, idx) => ({
    cloth_id: p.cloth_id,
    price: p.price,
    quantity: p.quantity,
    paid: idx === 0 ? input.paidDepositOnFirst : 0,
    type: "rent",
    process_type: "rent",
    days_of_rent: input.daysOfRent,
    ...(input.occasionApi && { occasion_datetime: input.occasionApi }),
    ...(input.returnVisitApi && { delivery_date: input.returnVisitApi }),
  }));
}

export type BranchVatLike = {
  vat_enabled?: boolean | null;
  vat_type?: "fixed" | "percentage" | null;
  vat_value?: number | null;
};


export function computeRentalVatPreviewFromBranch(
  subtotalBeforeTax: number,
  branch: BranchVatLike | null | undefined
): {
  mode: "none" | "percentage" | "fixed";
  taxAmount: number;
  
  percentagePoints: number | null;
} {
  if (
    !branch?.vat_enabled ||
    branch.vat_value == null ||
    Number(branch.vat_value) <= 0
  ) {
    return { mode: "none", taxAmount: 0, percentagePoints: null };
  }
  const val = Number(branch.vat_value);
  if (branch.vat_type === "percentage") {
    return {
      mode: "percentage",
      taxAmount: subtotalBeforeTax * (val / 100),
      percentagePoints: val,
    };
  }
  if (branch.vat_type === "fixed") {
    return { mode: "fixed", taxAmount: val, percentagePoints: null };
  }
  return { mode: "none", taxAmount: 0, percentagePoints: null };
}
