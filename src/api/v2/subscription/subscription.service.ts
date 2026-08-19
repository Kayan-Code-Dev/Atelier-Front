import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";

export type QuotaItem = {
  key: string;
  label: string;
  group: string;
  used: number;
  limit: number;
  remaining: number | null;
  unlimited: boolean;
  percent: number;
  unit: string;
  period: "monthly" | "lifetime" | string;
  exhausted: boolean;
};

export type QuotaUsageResponse = {
  period: {
    label: string;
    from: string;
    to: string;
    month: string;
    year: number;
  };
  items: QuotaItem[];
  summary?: {
    monthly_invoice_used: number;
    chat_used: number;
  };
};

export type SubscriptionOverviewResponse = {
  subscription: {
    plan_name?: string;
    plan_code?: string;
    days_remaining?: number | null;
    expires_at?: string | null;
    lifecycle_status?: string;
    currency_symbol?: string;
    price?: number;
    billing_cycle?: string;
    features?: Record<string, string>;
    is_demo?: boolean;
  };
  tenant?: { id: string; name: string; slug: string };
  available_plans?: Array<{
    id?: number;
    code?: string;
    name?: string;
    price?: number;
    currency_symbol?: string;
    is_current?: boolean;
    features?: string[];
  }>;
  usage?: QuotaUsageResponse;
};

type Envelope<T> = {
  success?: boolean;
  message?: string;
  data?: T;
};

export async function getSubscriptionOverview() {
  try {
    const { data } = await api.get<Envelope<SubscriptionOverviewResponse>>(
      "/subscription/overview"
    );
    return data.data ?? (data as unknown as SubscriptionOverviewResponse);
  } catch (error) {
    populateError(error, "تعذر تحميل بيانات الاشتراك");
    throw error;
  }
}

export async function getSubscriptionUsage() {
  try {
    const { data } = await api.get<Envelope<QuotaUsageResponse>>(
      "/subscription/usage"
    );
    return data.data ?? (data as unknown as QuotaUsageResponse);
  } catch (error) {
    populateError(error, "تعذر تحميل بيانات الكوتة");
    throw error;
  }
}
