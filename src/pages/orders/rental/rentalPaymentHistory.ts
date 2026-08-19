import { formatDateTime } from "@/utils/formatDate";


const METHOD_EN_TO_AR: Record<string, string> = {
  cash: "نقدي",
  نقدي: "نقدي",
  card: "بطاقة",
  بطاقة: "بطاقة",
  "bank transfer": "تحويل بنكي",
  bank_transfer: "تحويل بنكي",
  transfer: "تحويل بنكي",
  "تحويل بنكي": "تحويل بنكي",
  wallet: "محفظة إلكترونية",
  "محفظة إلكترونية": "محفظة إلكترونية",
};

const PAYMENT_TYPE_AR: Record<string, string> = {
  initial: "دفعة مبدئية",
  fee: "رسوم",
  normal: "دفعة عادية",
};

function pickString(...vals: unknown[]): string {
  for (const v of vals) {
    if (typeof v === "string" && v.trim()) return v.trim();
  }
  return "";
}

function paymentSortTime(p: unknown): number {
  if (!p || typeof p !== "object") return 0;
  const r = p as Record<string, unknown>;
  const s = pickString(r.paid_at, r.payment_date, r.created_at);
  if (!s) return 0;
  const t = new Date(s).getTime();
  return Number.isFinite(t) ? t : 0;
}

export function formatPaymentMethodForRentalUi(
  paymentMethod?: string | null,
  paymentType?: string | null
): string {
  const raw = (paymentMethod ?? "").trim();
  if (raw) {
    const lower = raw.toLowerCase();
    return METHOD_EN_TO_AR[lower] ?? METHOD_EN_TO_AR[raw] ?? raw;
  }
  const t = (paymentType ?? "").trim().toLowerCase();
  if (t && PAYMENT_TYPE_AR[t]) return PAYMENT_TYPE_AR[t];
  return "—";
}

export type RentalPaymentHistoryRow = {
  id: string | number;
  date: string;
  amount: number;
  method: string;
  note: string;
  
  receivedBy: string;
};


export function buildRentalPaymentHistoryRows(
  rawList: unknown[] | null | undefined,
  fallbackReceivedBy: string
): RentalPaymentHistoryRow[] {
  if (!rawList?.length) return [];

  const sorted = [...rawList].sort(
    (a, b) => paymentSortTime(a) - paymentSortTime(b)
  );

  const rows: RentalPaymentHistoryRow[] = [];
  for (const p of sorted) {
    if (!p || typeof p !== "object") continue;
    const r = p as Record<string, unknown>;
    const id = (r.id as string | number | undefined) ?? `row-${rows.length}`;
    const amountRaw = r.amount;
    const amount =
      typeof amountRaw === "number"
        ? amountRaw
        : parseFloat(String(amountRaw ?? "").replace(/,/g, ""));
    if (!Number.isFinite(amount) || amount < 0) continue;

    const dateSrc = pickString(r.paid_at, r.payment_date, r.created_at);
    const userObj = r.user as Record<string, unknown> | undefined;
    const fromUser = pickString(userObj?.name, userObj?.email);
    const receivedBy = fromUser || fallbackReceivedBy;

    const method = formatPaymentMethodForRentalUi(
      pickString(r.payment_method) || null,
      pickString(r.payment_type) || null
    );

    const notesRaw = r.notes;
    const note =
      notesRaw == null
        ? ""
        : typeof notesRaw === "string"
          ? notesRaw.trim()
          : String(notesRaw).trim();

    rows.push({
      id,
      date: dateSrc ? formatDateTime(dateSrc) : "—",
      amount,
      method,
      note,
      receivedBy,
    });
  }

  return rows;
}
