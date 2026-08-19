import { memo, useMemo, useCallback } from 'react';
import { formatDistanceToNow } from 'date-fns';
import { ar } from 'date-fns/locale';
import { CheckCircle2, Circle, ExternalLink } from 'lucide-react';
import { useNotificationsStore } from '@/zustand-stores/notifications.store';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { getOrderTypeLabel, getStatusLabel } from '@/api/v2/orders/order.utils';

interface NotificationItemProps {
  notification: {
    id: string;
    title: string;
    message: string;
    type: string;
    data?: {
      priority?: 'low' | 'normal' | 'high' | 'urgent';
      reference_type?: string;
      reference_id?: number;
      action_url?: string;
      metadata?: Record<string, any>;
    };
    timestamp: string;
    read: boolean;
  };
  onActionClick?: () => void;
}

const priorityColors = {
  low: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  normal: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  urgent: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

const getNotificationTypeLabel = (type: string): string => {
  const typeMap: Record<string, string> = {
    'order_status': 'حالة الطلب',
    'order_created': 'طلب جديد',
    'order_updated': 'تحديث الطلب',
    'payment_received': 'استلام دفعة',
    'delivery_scheduled': 'جدولة التسليم',
    'return_processed': 'معالجة الإرجاع',
    'expense_approved': 'موافقة على مصروف',
    'expense_rejected': 'رفض مصروف',
    'SupplierOrder': 'أمر توريد',
    'supplier_order': 'أمر توريد',
    'Order': 'الطلب',
    'order': 'الطلب',
    'Payment': 'دفعة',
    'payment': 'دفعة',
    'Expense': 'مصروف',
    'expense': 'مصروف',
    'Return': 'إرجاع',
    'return': 'إرجاع',
    'system': 'نظام',
    'alert': 'تنبيه',
    'info': 'معلومة',
    'warning': 'تحذير',
  };
  return typeMap[type] || type;
};

const amountKeys = new Set(['amount', 'order_paid', 'order_remaining', 'total_price', 'total_amount', 'payment_amount']);

function formatMetadataValue(key: string, value: unknown): string {
  if (value === null || value === undefined) return '-';
  if (key === 'order_type') return getOrderTypeLabel(value as 'rent' | 'buy' | 'tailoring' | 'mixed' | 'unknown');
  if (amountKeys.has(key)) {
    const n = Number(value);
    if (!Number.isNaN(n)) return `${n.toLocaleString('ar-EG', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} ج.م`;
  }
  return String(value);
}

const getStatusVariant = (status: string): string => {
  switch (status) {
    case 'paid':
      return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
    case 'partially_paid':
      return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
    case 'canceled':
      return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    case 'returned':
      return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
    case 'overdue':
      return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
    case 'created':
    case 'delivered':
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
  }
};

export const NotificationItem = memo(function NotificationItem({
  notification,
  onActionClick,
}: NotificationItemProps) {
  const { markAsRead } = useNotificationsStore();

  const handleMarkAsRead = useCallback((e: React.MouseEvent) => {
    e.stopPropagation();
    markAsRead(notification.id);
  }, [notification.id, markAsRead]);

  const handleClick = useCallback(() => {
    if (!notification.read) {
      markAsRead(notification.id);
    }
    const hasAction = notification.data?.action_url || notification.data?.metadata?.supplier_id != null;
    if (onActionClick && hasAction) {
      onActionClick();
    }
  }, [notification.read, notification.id, notification.data?.action_url, notification.data?.metadata?.supplier_id, markAsRead, onActionClick]);

  const priority = notification.data?.priority || 'normal';
  
  const timeAgo = useMemo(() => {
    return formatDistanceToNow(new Date(notification.timestamp), {
      addSuffix: true,
      locale: ar,
    });
  }, [notification.timestamp]);

  const orderStatus = useMemo(() => {
    const metadata = notification.data?.metadata as Record<string, any> | undefined;
    if (!metadata) return null;
    return (metadata.status || metadata.new_status || metadata.newStatus || null) as string | null;
  }, [notification.data?.metadata]);

  const orderType = useMemo(() => {
    const metadata = notification.data?.metadata as Record<string, any> | undefined;
    return (metadata?.order_type ?? null) as string | null;
  }, [notification.data?.metadata]);

  const metadataEntries = useMemo(() => {
    if (!notification.data?.metadata) return [];
    const filtered = Object.entries(notification.data.metadata).filter(
      ([key]) =>
        key !== 'order_id' &&
        key !== 'client_id' &&
        key !== 'status' &&
        key !== 'new_status' &&
        key !== 'old_status'
    );
    return filtered;
  }, [notification.data?.metadata]);

  const isPaymentReference = useMemo(() => {
    const rt = notification.data?.reference_type ?? '';
    return rt.includes('Payment') || rt === 'payment';
  }, [notification.data?.reference_type]);

  const typeLabel = useMemo(() => {
    return getNotificationTypeLabel(notification.type);
  }, [notification.type]);

  const referenceTypeDisplay = useMemo(() => {
    const rt = notification.data?.reference_type ?? '';
    return rt.replace(/^App\\Models\\/i, '') || '';
  }, [notification.data?.reference_type]);

  return (
    <div
      dir="rtl"
      className={cn(
        'relative group px-4 py-3 hover:bg-accent/50 transition-colors cursor-pointer',
        !notification.read && 'bg-accent/30'
      )}
      onClick={handleClick}
    >
      <div className="flex items-start gap-3">
        <button
          onClick={handleMarkAsRead}
          className="mt-1 shrink-0"
          aria-label={notification.read ? 'تم القراءة' : 'تحديد كمقروء'}
        >
          {notification.read ? (
            <CheckCircle2 className="h-4 w-4 text-muted-foreground" />
          ) : (
            <Circle className="h-4 w-4 text-primary" />
          )}
        </button>

        <div className="flex-1 min-w-0 overflow-visible">
          <div className="flex items-start justify-between gap-2 mb-1">
            <h4
              className={cn(
                'text-sm font-medium line-clamp-1',
                !notification.read && 'font-semibold'
              )}
            >
              {notification.title?.trim()
                ? notification.title
                : (notification.data?.reference_type?.includes('SupplierOrder') || notification.type?.toLowerCase().includes('supplier'))
                  ? 'أمر توريد جديد'
                  : 'إشعار'}
            </h4>
          </div>

          {orderType && (
            <div className="mb-2 w-full py-2 px-3 rounded-lg bg-sky-100 dark:bg-sky-950/50 border border-sky-300/60 dark:border-sky-700/50 flex items-center justify-between gap-2">
              <span className="text-sm font-medium text-sky-700 dark:text-sky-300">النوع</span>
              <span className="text-sm font-bold text-sky-800 dark:text-sky-200 shrink-0">
                {getOrderTypeLabel(orderType as 'rent' | 'buy' | 'tailoring' | 'mixed' | 'unknown')}
              </span>
            </div>
          )}

          <p className="text-sm text-muted-foreground line-clamp-2 mb-2">
            {notification.message}
          </p>

          {metadataEntries.length > 0 && (
            <div className="mb-2 flex flex-wrap gap-1.5">
              {metadataEntries.map(([key, value]) => {
                const keyLabels: Record<string, string> = {
                  total_price: 'المبلغ الإجمالي',
                  amount: 'المبلغ',
                  order_paid: 'المدفوع',
                  order_remaining: 'المتبقي',
                  order_type: 'النوع',
                  payment_id: 'رقم الدفعة',
                  reference_id: 'رقم المرجع',
                  supplier_id: 'رقم المورد',
                  total_amount: 'الإجمالي',
                  clothes_count: 'عدد الأصناف',
                  payment_amount: 'المدفوع',
                  supplier_order_id: 'رقم أمر التوريد',
                };
                const label = keyLabels[key] || key;
                const displayValue = formatMetadataValue(key, value);

                return (
                  <span
                    key={key}
                    className="text-xs px-2 py-0.5 rounded bg-muted text-muted-foreground"
                  >
                    <span className="font-medium">{label}:</span> {displayValue}
                  </span>
                );
              })}
            </div>
          )}

          {(notification.data?.reference_type || notification.data?.reference_id) && (
            <div className="mb-2 flex flex-wrap items-center gap-2">
              {isPaymentReference && notification.data?.reference_id ? (
                <div className="py-1.5 px-3 rounded-lg bg-emerald-100 dark:bg-emerald-950/50 border border-emerald-300/60 dark:border-emerald-700/50">
                  <span className="text-sm font-bold text-emerald-800 dark:text-emerald-200">
                    دفعة #{notification.data?.reference_id}
                  </span>
                </div>
              ) : (
                <>
                  {referenceTypeDisplay && (
                    <div className="py-1.5 px-3 rounded-lg bg-violet-100 dark:bg-violet-950/50 border border-violet-300/60 dark:border-violet-700/50">
                      <span className="text-sm font-bold text-violet-800 dark:text-violet-200">
                        نوع المرجع: {getNotificationTypeLabel(referenceTypeDisplay) || referenceTypeDisplay}
                      </span>
                    </div>
                  )}
                  {notification.data?.reference_id != null && (
                    <div className="py-1.5 px-3 rounded-lg bg-slate-100 dark:bg-slate-800/50 border border-slate-300/60 dark:border-slate-600/50">
                      <span className="text-sm font-bold text-slate-800 dark:text-slate-200">
                        الرقم: {notification.data?.reference_id}
                      </span>
                    </div>
                  )}
                </>
              )}
            </div>
          )}

          <div className="flex items-center justify-between gap-2">
            <div className="flex items-center gap-2 flex-wrap">
              {orderStatus && (
                <span
                  className={cn(
                    'text-xs px-2 py-0.5 rounded-full font-medium',
                    getStatusVariant(orderStatus)
                  )}
                >
                  {getStatusLabel(orderStatus)}
                </span>
              )}

              {priority && (
                <span
                  className={cn(
                    'text-xs px-2 py-0.5 rounded-full',
                    priorityColors[priority as keyof typeof priorityColors] || priorityColors.normal
                  )}
                >
                  {priority === 'urgent'
                    ? 'عاجل'
                    : priority === 'high'
                    ? 'مهم'
                    : priority === 'low'
                    ? 'منخفض'
                    : 'عادي'}
                </span>
              )}

              {notification.type && (
                <span className="text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary">
                  {typeLabel}
                </span>
              )}

              <span className="text-xs text-muted-foreground" title={new Date(notification.timestamp).toLocaleString('ar-SA')}>
                {timeAgo}
              </span>
            </div>

            {(notification.data?.action_url || notification.data?.metadata?.supplier_id != null) && (
              <Button
                variant="ghost"
                size="sm"
                className="h-6 px-2 text-xs shrink-0"
                onClick={(e) => {
                  e.stopPropagation();
                  onActionClick?.();
                }}
              >
                <ExternalLink className="h-3 w-3 me-1" />
                عرض
              </Button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
});
