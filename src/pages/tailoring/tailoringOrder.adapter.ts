import type {
  TTailoringOrderResource,
  TTailoringWorkflowStatusesResponse,
} from "@/api/v2/tailoring-orders/tailoringOrders.types";
import type { Measurements, PaymentRecord, TailoringOrder, TailoringStageDef } from "@/pages/tailoring/tailoring.ui";
import { tailoringStages } from "@/pages/tailoring/tailoring.ui";
import { ymdToDisplaySlashes } from "./tailoringDatetime";

const WORKFLOW_STAGE_META: Omit<TailoringStageDef, "key" | "label">[] = [
  { icon: "ri-shopping-bag-line", color: "#f59e0b", bg: "bg-amber-50", text: "text-amber-700", border: "border-amber-200" },
  { icon: "ri-scissors-cut-line", color: "#8b5cf6", bg: "bg-violet-50", text: "text-violet-700", border: "border-violet-200" },
  { icon: "ri-tools-line", color: "#3b82f6", bg: "bg-sky-50", text: "text-sky-700", border: "border-sky-200" },
  { icon: "ri-magic-line", color: "#ec4899", bg: "bg-pink-50", text: "text-pink-700", border: "border-pink-200" },
  { icon: "ri-gift-line", color: "#f97316", bg: "bg-orange-50", text: "text-orange-700", border: "border-orange-200" },
  { icon: "ri-check-double-line", color: "#6b7280", bg: "bg-gray-100", text: "text-gray-600", border: "border-gray-200" },
];

export function buildWorkflowStageDefs(
  workflow: TTailoringWorkflowStatusesResponse | undefined,
): TailoringStageDef[] {
  if (!workflow?.statuses?.length) return tailoringStages;
  return workflow.statuses.map((key, i) => ({
    key,
    label: workflow.labels_ar[key] ?? key,
    ...WORKFLOW_STAGE_META[i % WORKFLOW_STAGE_META.length],
  }));
}

function deriveUiStatus(r: TTailoringOrderResource): TailoringOrder["status"] {
  if (r.status === "delivered" || r.delivered_at) return "منجز";
  if (r.deleted_at) return "ملغي";
  const due = r.delivery_date ? new Date(r.delivery_date + "T12:00:00") : null;
  if (due && Date.now() > due.getTime() && r.status !== "delivered") return "متأخر";
  return "نشط";
}

function derivePaymentStatus(r: TTailoringOrderResource): TailoringOrder["paymentStatus"] {
  if (r.remaining <= 0) return "مدفوع بالكامل";
  if (r.paid > 0) return "مدفوع جزئياً";
  return "غير مدفوع";
}

function mapMeasurements(raw: Record<string, unknown> | null | undefined): Measurements {
  const n = (v: unknown) => {
    if (typeof v === "number" && !Number.isNaN(v)) return v;
    if (typeof v === "string" && v.trim() !== "") return Number(v) || 0;
    return 0;
  };
  const o = raw ?? {};
  const measurementNotes =
    typeof o.measurement_notes === "string"
      ? o.measurement_notes
      : typeof o.notes === "string"
        ? o.notes
        : "";
  return {
    height: n(o.total_length ?? o.height ?? o.Height),
    shoulder: n(o.shoulder_width ?? o.shoulder ?? o.Shoulder),
    chest: n(o.chest_length ?? o.chest ?? o.Chest),
    waist: n(o.waist ?? o.Waist),
    hips: n(o.hinch ?? o.hips ?? o.Hips),
    sleeveLength: n(o.sleeve_length ?? o.sleeveLength),
    sleeveWidth: n(o.cuffs ?? o.sleeve_width ?? o.sleeveWidth),
    dressLength: n(o.dress_size ?? o.dress_length ?? o.dressLength),
    neckline: n(o.neckline ?? o.Neckline),
    notes: measurementNotes,
  };
}

function mapPayments(
  rows: TTailoringOrderResource["payments"],
  fallbackPaid: number,
  createdAt: string,
): PaymentRecord[] {
  if (rows?.length) {
    return rows.map((p, i) => ({
      id: String(p.id ?? `pay-${i}`),
      amount: Number(p.amount) || 0,
      date: p.payment_date?.slice(0, 10).replace(/-/g, "/") ?? "",
      method: "كاش" as const,
      notes: p.notes ?? "",
    }));
  }
  if (fallbackPaid > 0) {
    return [
      {
        id: "paid-total",
        amount: fallbackPaid,
        date: createdAt.slice(0, 10).replace(/-/g, "/"),
        method: "كاش",
        notes: "",
      },
    ];
  }
  return [];
}

export function mapTailoringResourceToOrder(r: TTailoringOrderResource): TailoringOrder {
  const f = r.fabric ?? {};
  const client = r.client;
  const phone = client?.phones?.[0]?.phone ?? "";
  const addr =
    [client?.address?.city?.name, client?.address?.address].filter(Boolean).join(" — ") ||
    "—";
  const tailorName =
    r.employee?.user?.name ?? r.employee?.name ?? "—";
  const supplierName = f.supplier?.name ?? "—";
  const created = r.created_at?.slice(0, 10) ?? "";

  return {
    id: String(r.id),
    orderNumber: `#T${String(r.id).padStart(4, "0")}`,
    orderDate: ymdToDisplaySlashes(created.replace(/\//g, "-")) || created,
    dueDate: ymdToDisplaySlashes(r.delivery_date),
    eventDate: ymdToDisplaySlashes(r.occasion_datetime?.slice(0, 10) ?? ""),
    branchName: r.branch?.name ?? "—",
    tailorId: r.employee_id != null ? String(r.employee_id) : "",
    tailorName,
    priority: "عادي",
    customer: {
      name: client?.name ?? "—",
      phone,
      whatsapp: phone.replace(/^0/, ""),
      address: addr,
    },
    garmentType: f.garment_type ?? "—",
    fabric: {
      type: f.fabric_type ?? "—",
      color: (f.color as string) ?? "—",
      quantity: f.quantity != null ? String(f.quantity) : "",
      supplier: supplierName,
      notes: f.notes ?? "",
    },
    design: {
      description: "",
      style: "",
      hasEmbroidery: Boolean(f.includes_embroidery),
      embroideryNotes: "",
    },
    measurements: mapMeasurements(r.measurements as Record<string, unknown> | null),
    stageLog: [],
    currentStage: r.status,
    pricing: {
      price: Number(r.total_price) || 0,
      deposit: Number(r.paid) || 0,
      remaining: Number(r.remaining) || 0,
    },
    paymentRecords: mapPayments(r.payments, Number(r.paid) || 0, r.created_at),
    paymentStatus: derivePaymentStatus(r),
    status: deriveUiStatus(r),
    notes: "",
  };
}

export function isApiTailoringOrderId(id: string): boolean {
  return /^\d+$/.test(id);
}
