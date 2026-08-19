import { useEffect, useRef } from 'react';
import { useAuthStore } from '@/zustand-stores/auth.store';
import { useNotificationsStore } from '@/zustand-stores/notifications.store';
import { initializeEcho, disconnectEcho } from '@/lib/echo';
import { useQueryClient } from '@tanstack/react-query';
import { NOTIFICATIONS_KEY, UNREAD_COUNT_KEY } from '@/api/v2/notifications/notifications.hooks';

export function useNotifications() {
  const { isAuthenticated, loginData } = useAuthStore();
  const { addNotification } = useNotificationsStore();
  const queryClient = useQueryClient();
  const channelRef = useRef<any>(null);
  const echoRef = useRef<any>(null);
  const setupRef = useRef(false);
  const invalidationTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    if (!isAuthenticated || !loginData?.token || !loginData?.user?.id) {
      cleanup();
      return;
    }

    if (setupRef.current) return;

    let mounted = true;
    setupRef.current = true;

    const setupNotifications = async () => {
      try {
        await new Promise(resolve => setTimeout(resolve, 100));
        if (!mounted) return;

        if (import.meta.env.VITE_ENABLE_WEBSOCKET === 'false') return;

        const echo = initializeEcho(loginData.token, loginData.endpoints);
        echoRef.current = echo;

        const channelName = `admin.${loginData.user.id}`;
        const channel = echo.private(channelName);

        channel
          .subscribed(() => {
            if (!mounted) return;
          })
          .error((error: any) => {
            if (!mounted) return;
            console.error('WebSocket: channel subscription failed', error?.error || error);
          });

        const handleNotification = (data: any) => {
          if (!mounted) return;
          try {
            addNotification({
              title: data.title || 'إشعار جديد',
              message: data.message || '',
              type: data.type || 'system',
              data: data.data || {},
              timestamp: data.timestamp || new Date().toISOString(),
            });

            if (invalidationTimeoutRef.current) {
              clearTimeout(invalidationTimeoutRef.current);
            }
            invalidationTimeoutRef.current = setTimeout(() => {
              if (!mounted) return;
              try {
                queryClient.invalidateQueries({ queryKey: [NOTIFICATIONS_KEY] });
                queryClient.invalidateQueries({ queryKey: [UNREAD_COUNT_KEY] });
              } catch { /* noop */ } finally {
                invalidationTimeoutRef.current = null;
              }
            }, 300);
          } catch { /* noop */ }
        };

        channel.listen('.admin.notification', handleNotification);
        channel.listen('admin.notification', handleNotification);

        channelRef.current = channel;
      } catch { /* noop */ }
    };

    setupNotifications();

    return () => {
      mounted = false;
      setupRef.current = false;
      if (invalidationTimeoutRef.current) {
        clearTimeout(invalidationTimeoutRef.current);
        invalidationTimeoutRef.current = null;
      }
      if (channelRef.current) {
        try { channelRef.current.stopListening('.admin.notification'); } catch { /* noop */ }
        try { channelRef.current.stopListening('admin.notification'); } catch { /* noop */ }
        channelRef.current = null;
      }
    };
  }, [isAuthenticated, loginData?.token, loginData?.user?.id, addNotification, queryClient]);

  useEffect(() => {
    if (!isAuthenticated) cleanup();
  }, [isAuthenticated]);

  function cleanup() {
    setupRef.current = false;
    if (invalidationTimeoutRef.current) {
      clearTimeout(invalidationTimeoutRef.current);
      invalidationTimeoutRef.current = null;
    }
    if (channelRef.current) {
      try { channelRef.current.stopListening('.admin.notification'); } catch { /* noop */ }
      try { channelRef.current.stopListening('admin.notification'); } catch { /* noop */ }
      channelRef.current = null;
    }
    disconnectEcho();
    echoRef.current = null;
  }
}
