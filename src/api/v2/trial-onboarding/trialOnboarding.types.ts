export type TrialOnboardingStatus = "not_started" | "in_progress" | "completed";

export type TrialOnboardingStep = {
  key: string;
  order: number;
  title: string;
  description: string;
  required_action: string;
  completion_condition: string;
  route: string;
  event: string;
  target: string;
  success_copy: string;
  completed: boolean;
  current: boolean;
  metadata?: {
    source?: string;
    view_step?: boolean;
  };
};

export type TrialOnboardingSummary = {
  branches: number;
  cashboxes: number;
  suppliers: number;
  purchase_orders: number;
  inventory_items: number;
  customers: number;
  reservations: number;
};

export type TrialOnboardingSnapshot = {
  eligible: boolean;
  tenant_id?: number;
  user_id?: number;
  status: TrialOnboardingStatus;
  current_step?: string;
  completed_steps?: string[];
  started_at?: string | null;
  completed_at?: string | null;
  last_activity_at?: string | null;
  completion_acknowledged?: boolean;
  progress: {
    completed: number;
    total: number;
    percent: number;
  };
  next_step?: TrialOnboardingStep | null;
  steps: TrialOnboardingStep[];
  summary?: TrialOnboardingSummary | null;
  resume?: boolean;
};
