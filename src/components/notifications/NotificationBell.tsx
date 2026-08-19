import { useMemo, useCallback } from 'react';
import { Bell } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ScrollArea } from '@/components/ui/scroll-area';
import { NotificationItem } from './NotificationItem';
import { Separator } from '@/components/ui/separator';
import { useNavigate } from 'react-router';
import { useQuery, useMutation } from '@tanstack/react-query';
import { normalizeNotificationActionUrl } from '@/utils/notificationActionUrl';
import {
  useGetNotificationsQueryOptions,
  useGetUnreadCountQueryOptions,
  useMarkAllNotificationsAsReadMutationOptions,
} from '@/api/v2/notifications/notifications.hooks';
import { toast } from 'sonner';
import { useHasPermission } from '@/api/auth/auth.hooks';

export function NotificationBell() {
  const navigate = useNavigate();
  const { hasPermission } = useHasPermission(["notifications.view", "notifications.manage"]);

  const { data: apiNotifications } = useQuery({
    ...useGetNotificationsQueryOptions({
      page: 1,
      per_page: 10,
      unread_only: false,
    }),
    enabled: hasPermission,
  });

  const { data: unreadCountData } = useQuery({
    ...useGetUnreadCountQueryOptions(),
    enabled: hasPermission,
  });

  const markAllAsReadMutation = useMutation(
    useMarkAllNotificationsAsReadMutationOptions()
  );

  const allNotifications = useMemo(() => apiNotifications?.data || [], [apiNotifications?.data]);
  const unreadCount = useMemo(() => unreadCountData?.unread_count || 0, [unreadCountData?.unread_count]);

  const handleMarkAllAsRead = useCallback(() => {
    markAllAsReadMutation.mutate(undefined, {
      onSuccess: () => {
        toast.success('تم تحديد جميع الإشعارات كمقروءة');
      },
    });
  }, [markAllAsReadMutation]);

  const unreadNotifications = useMemo(() => 
    allNotifications.filter((n) => !n.read_at),
    [allNotifications]
  );
  
  const readNotifications = useMemo(() => 
    allNotifications.filter((n) => n.read_at),
    [allNotifications]
  );

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          size="icon"
          className="relative"
          aria-label="الإشعارات"
        >
          <Bell className="h-5 w-5 text-[#7a7a7a]" />
          {unreadCount > 0 && (
            <span className="absolute -top-1 -right-1 bg-[#cf0c0c] rounded-full w-5 h-5 flex items-center justify-center text-[10px] text-white font-medium">
              {unreadCount > 99 ? '99+' : unreadCount}
            </span>
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent
        align="end"
        className="w-[400px] sm:w-[440px] min-w-[400px] p-0 overflow-x-visible"
        sideOffset={8}
      >
        <div className="flex flex-col" dir="rtl">
        <div className="flex items-center justify-between px-4 py-3 border-b">
          <h3 className="font-semibold text-sm">الإشعارات</h3>
          {unreadCount > 0 && (
            <Button
              variant="ghost"
              size="sm"
              className="h-auto py-1 px-2 text-xs"
              onClick={handleMarkAllAsRead}
            >
              تحديد الكل كمقروء
            </Button>
          )}
        </div>

        <ScrollArea className="h-[400px]">
          {allNotifications.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 px-4 text-center">
              <Bell className="h-12 w-12 text-muted-foreground/40 mb-3" />
              <p className="text-sm text-muted-foreground">
                لا توجد إشعارات
              </p>
            </div>
          ) : (
            <div className="py-2">
              {unreadNotifications.length > 0 && (
                <>
                  {unreadNotifications.map((notification) => (
                    <NotificationItem
                      key={notification.id}
                      notification={{
                        id: String(notification.id),
                        title: notification.title,
                        message: notification.message,
                        type: notification.type,
                        data: {
                          priority: notification.priority,
                          reference_type: notification.reference_type,
                          reference_id: notification.reference_id,
                          action_url: notification.action_url || undefined,
                          metadata: notification.metadata,
                        },
                        timestamp: notification.created_at,
                        read: !!notification.read_at,
                      }}
                      onActionClick={() => {
                        if (notification.action_url || notification.metadata?.supplier_id != null) {
                          navigate(normalizeNotificationActionUrl(notification.action_url, notification.metadata));
                        }
                      }}
                    />
                  ))}
                  {readNotifications.length > 0 && (
                    <Separator className="my-2" />
                  )}
                </>
              )}

              {readNotifications.map((notification) => (
                <NotificationItem
                  key={notification.id}
                  notification={{
                    id: String(notification.id),
                    title: notification.title,
                    message: notification.message,
                    type: notification.type,
                    data: {
                      priority: notification.priority,
                      reference_type: notification.reference_type,
                      reference_id: notification.reference_id,
                      action_url: notification.action_url || undefined,
                      metadata: notification.metadata,
                    },
                    timestamp: notification.created_at,
                    read: !!notification.read_at,
                  }}
                  onActionClick={() => {
                    if (notification.action_url || notification.metadata?.supplier_id != null) {
                      navigate(normalizeNotificationActionUrl(notification.action_url, notification.metadata));
                    }
                  }}
                />
              ))}
            </div>
          )}
        </ScrollArea>

        {allNotifications.length > 0 && (
          <div className="border-t px-4 py-2">
            <Button
              variant="ghost"
              size="sm"
              className="w-full text-xs"
              onClick={() => navigate('/notifications')}
            >
              عرض جميع الإشعارات
            </Button>
          </div>
        )}
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
