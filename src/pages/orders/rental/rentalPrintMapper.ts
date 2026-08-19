import type { TOrder } from "@/api/v2/orders/orders.types";
import { formatDate } from "@/utils/formatDate";
import { getRentalUiPayment, getRentalUiStatus } from "./rentalUi";
import { buildRentalPaymentHistoryRows } from "./rentalPaymentHistory";

export type RentalPrintProductRow = {
  productId: string;
  name: string;
  category: string;
  quantity: number;
  unitPrice: number;
};

export type RentalPrintPaymentRow = {
  id: string | number;
  date: string;
  amount: number;
  method: string;
  note: string;
  receivedBy: string;
};

export type RentalPrintView = {
  invoiceNumber: string;
  invoiceDate: string;
  branchName: string;
  employeeName: string;
  customer: {
    name: string;
    nationalId: string;
    phone: string;
    whatsapp: string;
    address: string;
  };
  dates: { delivery: string; event: string; returnDate: string };
  products: RentalPrintProductRow[];
  pricing: {
    subtotal: number;
    taxRate: number;
    taxAmount: number;
    totalWithTax: number;
    discount: number;
    discountReason: string;
  };
  paymentHistory: RentalPrintPaymentRow[];
  paidTotal: number;
  remainingTotal: number;
  notes: string;
  statusLabel: string;
  paymentStatusLabel: string;
};

function clientAddress(client: TOrder["client"]): string {
  const a = client?.address;
  if (!a) return "—";
  return [a.country_name, a.city_name, a.street, a.building].filter(Boolean).join(" - ");
}

export function mapOrderToRentalPrintView(
  order: TOrder,
  opts?: {
    employeeDisplayName?: string;
    
    paymentsList?: unknown[] | null;
  }
): RentalPrintView {
  const phones = order.client?.phones ?? [];
  const products: RentalPrintProductRow[] = (order.items ?? []).map((it, idx) => ({
    productId: String(it.id ?? idx),
    name: it.name ?? it.description ?? it.code ?? "—",
    category:
      it.category_name ??
      it.category?.name ??
      it.subcategory_name ??
      it.subcategory?.name ??
      "—",
    quantity: it.quantity ?? 1,
    unitPrice: Number(it.price ?? 0),
  }));

  const lineSubtotal = products.reduce((s, p) => s + p.unitPrice * p.quantity, 0);
  const totalWithTax = Number(order.total_price ?? 0);
  let taxRate = 0;
  let taxAmount = 0;
  if (order.vat_enabled && order.vat_type === "percentage" && order.vat_value != null) {
    taxRate = Number(order.vat_value) / 100;
    const disc = Number(order.discount_value ?? 0);
    const approxBeforeTax = lineSubtotal - disc;
    taxAmount = Math.max(0, totalWithTax - approxBeforeTax);
  }
  const discount = Number(order.discount_value ?? 0);
  const discountReason =
    order.discount_type && order.discount_type !== "none" ? String(order.discount_type) : "";

  const employeeName =
    opts?.employeeDisplayName ??
    (typeof order.employee_name === "string" && order.employee_name.trim()
      ? order.employee_name.trim()
      : null) ??
    order.employee?.user?.name?.trim() ??
    order.employee?.user?.email?.trim() ??
    (order.employee_id && order.employee_id > 0 ? `#${order.employee_id}` : "—");

  const embeddedPayments = (order as TOrder & { payments?: unknown[] }).payments;
  const rawPayments =
    opts?.paymentsList !== undefined && opts?.paymentsList !== null
      ? opts.paymentsList
      : embeddedPayments;

  let paymentHistory: RentalPrintPaymentRow[] = buildRentalPaymentHistoryRows(
    rawPayments,
    employeeName
  );
  if (paymentHistory.length === 0 && Number(order.paid) > 0) {
    paymentHistory = [
      {
        id: "aggregate",
        date: formatDate(order.updated_at),
        amount: Number(order.paid),
        method: "إجمالي المسجّل",
        note: "",
        receivedBy: employeeName,
      },
    ];
  }

  return {
    invoiceNumber: `#${order.id}`,
    invoiceDate: formatDate(order.created_at),
    branchName: order.branch?.name ?? "—",
    employeeName,
    customer: {
      name: order.client?.name ?? "—",
      nationalId: String(order.client?.national_id ?? "—"),
      phone: phones[0]?.phone ?? "—",
      whatsapp: phones[1]?.phone ?? "—",
      address: clientAddress(order.client),
    },
    dates: {
      delivery: order.delivery_date ? formatDate(order.delivery_date) : "—",
      event: order.occasion_datetime ? formatDate(order.occasion_datetime) : "—",
      returnDate: order.visit_datetime ? formatDate(order.visit_datetime) : "—",
    },
    products,
    pricing: {
      subtotal: lineSubtotal > 0 ? lineSubtotal : totalWithTax,
      taxRate,
      taxAmount,
      totalWithTax,
      discount,
      discountReason,
    },
    paymentHistory,
    paidTotal: Number(order.paid ?? 0),
    remainingTotal: Number(order.remaining ?? 0),
    notes: order.order_notes ?? "",
    statusLabel: getRentalUiStatus(order),
    paymentStatusLabel: getRentalUiPayment(order),
  };
}
