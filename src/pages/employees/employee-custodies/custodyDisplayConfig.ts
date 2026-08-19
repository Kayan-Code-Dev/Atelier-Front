import { TEmployeeCustody } from "@/api/v2/employees/employee-custodies/employee-custodies.types";

export type CustodyDisplayStatus =
  | "active"
  | "expiring_soon"
  | "expired"
  | "returned"
  | "damaged"
  | "lost";

export function daysUntil(dateStr: string): number {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const end = new Date(dateStr);
  end.setHours(0, 0, 0, 0);
  return Math.ceil((end.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
}

export function getCustodyDisplayStatus(c: TEmployeeCustody): CustodyDisplayStatus {
  if (c.status === "returned") return "returned";
  if (c.status === "damaged") return "damaged";
  if (c.status === "lost") return "lost";
  const d = daysUntil(c.expected_return_date);
  if (d < 0) return "expired";
  if (d <= 45) return "expiring_soon";
  return "active";
}

export const CUSTODY_DISPLAY_STATUS_CONFIG: Record<
  CustodyDisplayStatus,
  { label: string; bg: string; color: string; dot: string }
> = {
  active: {
    label: "نشطة",
    bg: "bg-emerald-50",
    color: "text-emerald-700",
    dot: "bg-emerald-500",
  },
  expiring_soon: {
    label: "تنتهي قريباً",
    bg: "bg-amber-50",
    color: "text-amber-800",
    dot: "bg-amber-500",
  },
  expired: {
    label: "منتهية",
    bg: "bg-red-50",
    color: "text-red-700",
    dot: "bg-red-500",
  },
  returned: {
    label: "مُعادة",
    bg: "bg-sky-50",
    color: "text-sky-700",
    dot: "bg-sky-500",
  },
  damaged: {
    label: "تالف",
    bg: "bg-orange-50",
    color: "text-orange-800",
    dot: "bg-orange-500",
  },
  lost: {
    label: "مفقود",
    bg: "bg-gray-100",
    color: "text-gray-700",
    dot: "bg-gray-500",
  },
};

const TYPE_ICON_PRESETS: Record<string, { icon: string; color: string; bg: string }> = {
  laptop: { icon: "ri-computer-line", color: "text-violet-600", bg: "bg-violet-50" },
  computer: { icon: "ri-computer-line", color: "text-violet-600", bg: "bg-violet-50" },
  phone: { icon: "ri-smartphone-line", color: "text-blue-600", bg: "bg-blue-50" },
  mobile: { icon: "ri-smartphone-line", color: "text-blue-600", bg: "bg-blue-50" },
  tablet: { icon: "ri-tablet-line", color: "text-cyan-600", bg: "bg-cyan-50" },
  vehicle: { icon: "ri-car-line", color: "text-indigo-600", bg: "bg-indigo-50" },
  car: { icon: "ri-car-line", color: "text-indigo-600", bg: "bg-indigo-50" },
  key: { icon: "ri-key-line", color: "text-amber-600", bg: "bg-amber-50" },
  card: { icon: "ri-bank-card-line", color: "text-emerald-600", bg: "bg-emerald-50" },
  id: { icon: "ri-bank-card-line", color: "text-emerald-600", bg: "bg-emerald-50" },
  tool: { icon: "ri-tools-line", color: "text-orange-600", bg: "bg-orange-50" },
  uniform: { icon: "ri-shirt-line", color: "text-pink-600", bg: "bg-pink-50" },
  cash: { icon: "ri-money-dollar-circle-line", color: "text-emerald-600", bg: "bg-emerald-50" },
  document: { icon: "ri-file-text-line", color: "text-gray-700", bg: "bg-gray-100" },
};

const FALLBACK_PALETTES = [
  { color: "text-violet-600", bg: "bg-violet-50" },
  { color: "text-blue-600", bg: "bg-blue-50" },
  { color: "text-indigo-600", bg: "bg-indigo-50" },
  { color: "text-teal-600", bg: "bg-teal-50" },
  { color: "text-rose-600", bg: "bg-rose-50" },
];

export function getCustodyTypeVisual(typeKey: string): {
  icon: string;
  color: string;
  bg: string;
} {
  const k = typeKey.toLowerCase().replace(/\s+/g, "_");
  for (const [key, v] of Object.entries(TYPE_ICON_PRESETS)) {
    if (k.includes(key)) {
      return { icon: v.icon, color: v.color, bg: v.bg };
    }
  }
  let h = 0;
  for (let i = 0; i < typeKey.length; i++) {
    h = (h + typeKey.charCodeAt(i) * (i + 1)) % 9999;
  }
  const p = FALLBACK_PALETTES[h % FALLBACK_PALETTES.length];
  return { icon: "ri-file-shield-2-line", color: p.color, bg: p.bg };
}

export function custodyTypeLabel(
  typeKey: string,
  typesList?: { key: string; name: string }[]
): string {
  return typesList?.find((t) => t.key === typeKey)?.name ?? typeKey;
}
