import type { TClothResponse } from "@/api/v2/clothes/clothes.types";
import type { TClothesStatus } from "@/api/v2/clothes/clothes.types";

export function clothPrice(c: TClothResponse): number {
  const raw = (c as { price?: number | string }).price;
  if (raw == null || raw === "") return 0;
  const n = Number(raw);
  return Number.isFinite(n) ? n : 0;
}

/** تنسيق عرض الحالة بما يقارب صفحة المنتجات في المشروع */
export const STATUS_VISUAL: Record<
  TClothesStatus,
  { label: string; color: string; bg: string; dot: string; icon: string }
> = {
  ready_for_rent: {
    label: "جاهز للإيجار",
    color: "#065F46",
    bg: "#D1FAE5",
    dot: "#10B981",
    icon: "ri-checkbox-circle-fill",
  },
  rented: {
    label: "محجوز",
    color: "#1E40AF",
    bg: "#DBEAFE",
    dot: "#3B82F6",
    icon: "ri-bookmark-fill",
  },
  damaged: {
    label: "تالف",
    color: "#991B1B",
    bg: "#FEE2E2",
    dot: "#EF4444",
    icon: "ri-error-warning-fill",
  },
  burned: {
    label: "محترق",
    color: "#991B1B",
    bg: "#FEE2E2",
    dot: "#EF4444",
    icon: "ri-fire-fill",
  },
  scratched: {
    label: "مخدوش",
    color: "#9A3412",
    bg: "#FFEDD5",
    dot: "#EA580C",
    icon: "ri-focus-3-line",
  },
  repairing: {
    label: "قيد الإصلاح",
    color: "#92400E",
    bg: "#FEF3C7",
    dot: "#F59E0B",
    icon: "ri-tools-fill",
  },
  die: {
    label: "ميت",
    color: "#374151",
    bg: "#F3F4F6",
    dot: "#9CA3AF",
    icon: "ri-close-circle-fill",
  },
};

export function statusVisual(status: string) {
  return (
    STATUS_VISUAL[status as TClothesStatus] ?? {
      label: status,
      color: "#64748B",
      bg: "#F1F5F9",
      dot: "#94A3B8",
      icon: "ri-question-line",
    }
  );
}

const CATEGORY_THEME: Record<
  string,
  { icon: string; accent: string; light: string }
> = {
  فساتين: { icon: "ri-women-line", accent: "#BE185D", light: "#FCE7F3" },
  بدل: { icon: "ri-shirt-line", accent: "#1E3A7B", light: "#EEF2FF" },
  إكسسوارات: { icon: "ri-gem-line", accent: "#C2964A", light: "#FEF3C7" },
  عبايات: { icon: "ri-user-3-line", accent: "#1E293B", light: "#F1F5F9" },
};

export function categoryTheme(categoryName: string) {
  const key = categoryName.trim();
  return (
    CATEGORY_THEME[key] ?? {
      icon: "ri-price-tag-3-line",
      accent: "#64748B",
      light: "#F1F5F9",
    }
  );
}

export const LOCATION_VISUAL: Record<
  string,
  { icon: string; color: string; bg: string; label: string }
> = {
  branch: { icon: "ri-store-2-line", color: "#1E3A7B", bg: "#EEF2FF", label: "فرع" },
  workshop: { icon: "ri-tools-line", color: "#92400E", bg: "#FEF3C7", label: "ورشة" },
  factory: {
    icon: "ri-building-4-line",
    color: "#065F46",
    bg: "#D1FAE5",
    label: "مصنع",
  },
};

export function locationVisual(entityType: string) {
  return (
    LOCATION_VISUAL[entityType] ?? {
      icon: "ri-map-pin-line",
      color: "#64748B",
      bg: "#F1F5F9",
      label: "موقع",
    }
  );
}
