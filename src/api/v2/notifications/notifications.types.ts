export interface Notification {
  id: number;
  title: string;
  message: string;
  type: string;
  priority?: 'low' | 'normal' | 'high' | 'urgent';
  reference_type?: string;
  reference_id?: number;
  action_url?: string | null;
  metadata?: Record<string, any>;
  read_at: string | null;
  dismissed_at: string | null;
  scheduled_for: string | null;
  sent_at: string;
  created_at: string;
  updated_at: string;
  user_id: number;
}

export interface TGetNotificationsParams {
  page?: number;
  per_page?: number;
  unread_only?: boolean;
}

export interface TNotificationsResponse {
  data: Notification[];
  current_page: number;
  total: number;
  total_pages: number;
  per_page: number;
  meta: {
    total: number;
    read: number;
    unread: number;
  };
}

export interface TUnreadCountResponse {
  unread_count: number;
}
