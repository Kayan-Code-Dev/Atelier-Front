export type PlanId = 'starter' | 'professional' | 'enterprise';

export interface SubscriptionPlan {
  id: PlanId;
  name: string;
  nameEn: string;
  price: number;
  period: 'شهرياً' | 'سنوياً';
  branches: number | 'غير محدود';
  employees: number | 'غير محدود';
  features: string[];
  color: string;
  badge?: string;
}

export interface PaymentRecord {
  id: string;
  date: string;
  plan: string;
  amount: number;
  status: 'ناجح' | 'فاشل' | 'مُسترد';
  invoiceNo: string;
  method: string;
}

export const subscriptionPlans: SubscriptionPlan[] = [
  {
    id: 'starter',
    name: 'المبتدئ',
    nameEn: 'Starter',
    price: 199,
    period: 'شهرياً',
    branches: 1,
    employees: 5,
    features: [
      'إدارة فرع واحد',
      'حتى 5 موظفين',
      'قسم الإيجار والمبيعات',
      'تقارير أساسية',
      'دعم فني عبر البريد',
    ],
    color: '#64748B',
  },
  {
    id: 'professional',
    name: 'المحترف',
    nameEn: 'Professional',
    price: 499,
    period: 'شهرياً',
    branches: 5,
    employees: 25,
    features: [
      'حتى 5 فروع',
      'حتى 25 موظفاً',
      'جميع الأقسام الإنتاجية',
      'نظام الخزنة والتقارير المتقدمة',
      'طباعة تقارير الفروع',
      'دعم فني مباشر',
    ],
    color: '#C2964A',
    badge: 'الأكثر طلباً',
  },
  {
    id: 'enterprise',
    name: 'المؤسسي',
    nameEn: 'Enterprise',
    price: 1299,
    period: 'شهرياً',
    branches: 'غير محدود',
    employees: 'غير محدود',
    features: [
      'فروع وموظفون غير محدودون',
      'جميع الميزات المتاحة',
      'API مخصص للتكامل',
      'تقارير متقدمة وتحليلات',
      'مدير حساب مخصص',
      'SLA مضمون 99.9%',
    ],
    color: '#6366F1',
  },
];

export const currentSubscription = {
  plan: 'professional' as PlanId,
  startDate: '2026-01-01',
  endDate: '2026-04-01',
  daysRemaining: 11,
  totalDays: 90,
  autoRenew: true,
  nextBillingAmount: 499,
  nextBillingDate: '2026-04-01',
};

export const paymentHistory: PaymentRecord[] = [
  {
    id: 'PAY-001',
    date: '2026-01-01',
    plan: 'المحترف — 3 أشهر',
    amount: 1497,
    status: 'ناجح',
    invoiceNo: 'INV-2026-001',
    method: 'بطاقة ائتمانية Visa ****4521',
  },
  {
    id: 'PAY-002',
    date: '2025-10-01',
    plan: 'المحترف — 3 أشهر',
    amount: 1497,
    status: 'ناجح',
    invoiceNo: 'INV-2025-008',
    method: 'بطاقة ائتمانية Visa ****4521',
  },
  {
    id: 'PAY-003',
    date: '2025-07-01',
    plan: 'المحترف — 3 أشهر',
    amount: 1497,
    status: 'ناجح',
    invoiceNo: 'INV-2025-005',
    method: 'تحويل بنكي',
  },
  {
    id: 'PAY-004',
    date: '2025-04-01',
    plan: 'المبتدئ — 3 أشهر',
    amount: 597,
    status: 'ناجح',
    invoiceNo: 'INV-2025-002',
    method: 'بطاقة ائتمانية Mastercard ****7832',
  },
  {
    id: 'PAY-005',
    date: '2025-01-01',
    plan: 'المبتدئ — 3 أشهر',
    amount: 597,
    status: 'ناجح',
    invoiceNo: 'INV-2024-012',
    method: 'بطاقة ائتمانية Mastercard ****7832',
  },
  {
    id: 'PAY-006',
    date: '2024-10-05',
    plan: 'المبتدئ — شهرياً',
    amount: 199,
    status: 'فاشل',
    invoiceNo: 'INV-2024-009',
    method: 'بطاقة ائتمانية Mastercard ****7832',
  },
];
