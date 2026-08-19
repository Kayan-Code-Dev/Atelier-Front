export type TailoringStageDef = {
  key: string;
  label: string;
  icon: string;
  color: string;
  bg: string;
  text: string;
  border: string;
};

export const tailoringStages: TailoringStageDef[] = [
  { key: "new", label: "طلب جديد", icon: "ri-file-add-line", color: "#64748b", bg: "bg-slate-100", text: "text-slate-600", border: "border-slate-200" },
  { key: "fabric", label: "استلام القماش", icon: "ri-shopping-bag-line", color: "#f59e0b", bg: "bg-amber-50", text: "text-amber-700", border: "border-amber-200" },
  { key: "cutting", label: "القص والتحضير", icon: "ri-scissors-cut-line", color: "#8b5cf6", bg: "bg-violet-50", text: "text-violet-700", border: "border-violet-200" },
  { key: "sewing", label: "الخياطة", icon: "ri-tools-line", color: "#3b82f6", bg: "bg-sky-50", text: "text-sky-700", border: "border-sky-200" },
  { key: "finishing", label: "التشطيب والتطريز", icon: "ri-magic-line", color: "#ec4899", bg: "bg-pink-50", text: "text-pink-700", border: "border-pink-200" },
  { key: "quality", label: "مراجعة الجودة", icon: "ri-checkbox-circle-line", color: "#10b981", bg: "bg-emerald-50", text: "text-emerald-700", border: "border-emerald-200" },
  { key: "ready", label: "جاهز للتسليم", icon: "ri-gift-line", color: "#f97316", bg: "bg-orange-50", text: "text-orange-700", border: "border-orange-200" },
  { key: "delivered", label: "تم التسليم", icon: "ri-check-double-line", color: "#6b7280", bg: "bg-gray-100", text: "text-gray-600", border: "border-gray-200" },
];

export interface Measurements {
  height: number;
  shoulder: number;
  chest: number;
  waist: number;
  hips: number;
  sleeveLength: number;
  sleeveWidth: number;
  dressLength: number;
  neckline: number;
  notes: string;
}

export interface StageLog {
  stage: string;
  completedAt: string;
  notes: string;
  doneBy: string;
}

export interface PaymentRecord {
  id: string;
  amount: number;
  date: string;
  method: "كاش" | "تحويل" | "بطاقة";
  notes: string;
}

export interface TailoringOrder {
  id: string;
  orderNumber: string;
  orderDate: string;
  dueDate: string;
  eventDate: string;
  branchName: string;
  tailorId: string;
  tailorName: string;
  priority: "عادي" | "عاجل" | "VIP";
  customer: {
    name: string;
    phone: string;
    whatsapp: string;
    address: string;
  };
  garmentType: string;
  fabric: {
    type: string;
    color: string;
    quantity: string;
    supplier: string;
    notes: string;
  };
  design: {
    description: string;
    style: string;
    hasEmbroidery: boolean;
    embroideryNotes: string;
  };
  measurements: Measurements;
  stageLog: StageLog[];
  currentStage: string;
  pricing: {
    price: number;
    deposit: number;
    remaining: number;
  };
  paymentRecords: PaymentRecord[];
  paymentStatus: "مدفوع بالكامل" | "مدفوع جزئياً" | "غير مدفوع";
  status: "نشط" | "منجز" | "متأخر" | "ملغي";
  notes: string;
}

export const garmentTypes = [
  "فستان سواريه",
  "فستان زفاف",
  "فستان خطوبة",
  "فستان كوكتيل",
  "عباية مطرزة",
  "عباية رسمية",
  "جلابية مطرزة",
  "قفطان مغربي",
  "بدلة نسائية",
  "تنورة",
  "بلوزة فاخرة",
  "أخرى",
];

export const fabricTypes = [
  "ستان حرير",
  "كريب مزدوج",
  "شيفون مزدوج",
  "تول مع ستان",
  "قطن مصري",
  "صوف إيطالي",
  "دانتيل فرنسي",
  "جاكار",
  "مخمل",
  "كريب جورجيت",
  "أخرى",
];

export const statusColors: Record<string, string> = {
  نشط: "bg-sky-50 text-sky-700 border border-sky-200",
  منجز: "bg-emerald-50 text-emerald-700 border border-emerald-200",
  متأخر: "bg-red-50 text-red-700 border border-red-200",
  ملغي: "bg-gray-100 text-gray-500 border border-gray-200",
};

export const priorityColors: Record<string, string> = {
  VIP: "bg-amber-50 text-amber-700 border border-amber-200",
  عاجل: "bg-rose-50 text-rose-700 border border-rose-200",
  عادي: "bg-slate-50 text-slate-600 border border-slate-200",
};

export const paymentColors: Record<string, string> = {
  "مدفوع بالكامل": "bg-emerald-50 text-emerald-700 border border-emerald-200",
  "مدفوع جزئياً": "bg-amber-50 text-amber-700 border border-amber-200",
  "غير مدفوع": "bg-red-50 text-red-700 border border-red-200",
};

export function projectOrderRouteId(orderId: string): number {
  return /^\d+$/.test(orderId) ? Number(orderId) : 0;
}
