import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  deleteNotification,
  getNotifications,
  getUnreadCount,
  markAllNotificationsAsRead,
  markNotificationAsRead,
} from "./notifications.service";
import {
  TGetNotificationsParams,
  TNotificationsResponse,
  TUnreadCountResponse,
} from "./notifications.types";

export const NOTIFICATIONS_KEY = "NOTIFICATIONS";
export const UNREAD_COUNT_KEY = "UNREAD_COUNT";

export const useGetNotificationsQueryOptions = (
  params: TGetNotificationsParams
) => {
  return queryOptions({
    queryKey: [NOTIFICATIONS_KEY, params],
    queryFn: () => getNotifications(params),
    staleTime: 1000 * 30, // 30 seconds
  });
};

export const useGetUnreadCountQueryOptions = () => {
  return queryOptions({
    queryKey: [UNREAD_COUNT_KEY],
    queryFn: () => getUnreadCount(),
    staleTime: 1000 * 30, // 30 seconds
  });
};

export const useMarkNotificationAsReadMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => markNotificationAsRead(id),
    onMutate: async (id: number) => {
      // Cancel outgoing queries
      await queryClient.cancelQueries({ queryKey: [NOTIFICATIONS_KEY] });
      await queryClient.cancelQueries({ queryKey: [UNREAD_COUNT_KEY] });

      // Optimistically update notifications
      queryClient.setQueryData<TNotificationsResponse>(
        [NOTIFICATIONS_KEY],
        (oldData) => {
          if (!oldData) return oldData;
          return {
            ...oldData,
            data: oldData.data.map((notification) =>
              notification.id === id
                ? { ...notification, read_at: new Date().toISOString() }
                : notification
            ),
            meta: {
              ...oldData.meta,
              read: oldData.meta.read + 1,
              unread: Math.max(0, oldData.meta.unread - 1),
            },
          };
        }
      );

      // Optimistically update unread count
      queryClient.setQueryData<TUnreadCountResponse>(
        [UNREAD_COUNT_KEY],
        (oldData) => {
          if (!oldData) return oldData;
          return {
            unread_count: Math.max(0, oldData.unread_count - 1),
          };
        }
      );
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [NOTIFICATIONS_KEY] });
      queryClient.invalidateQueries({ queryKey: [UNREAD_COUNT_KEY] });
    },
  });
};

export const useMarkAllNotificationsAsReadMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: () => markAllNotificationsAsRead(),
    onMutate: async () => {
      await queryClient.cancelQueries({ queryKey: [NOTIFICATIONS_KEY] });
      await queryClient.cancelQueries({ queryKey: [UNREAD_COUNT_KEY] });

      // Optimistically update all notifications
      queryClient.setQueryData<TNotificationsResponse>(
        [NOTIFICATIONS_KEY],
        (oldData) => {
          if (!oldData) return oldData;
          const now = new Date().toISOString();
          return {
            ...oldData,
            data: oldData.data.map((notification) => ({
              ...notification,
              read_at: notification.read_at || now,
            })),
            meta: {
              ...oldData.meta,
              read: oldData.meta.total,
              unread: 0,
            },
          };
        }
      );

      // Optimistically update unread count
      queryClient.setQueryData<TUnreadCountResponse>(
        [UNREAD_COUNT_KEY],
        () => ({
          unread_count: 0,
        })
      );
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [NOTIFICATIONS_KEY] });
      queryClient.invalidateQueries({ queryKey: [UNREAD_COUNT_KEY] });
    },
  });
};

export const useDeleteNotificationMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteNotification(id),
    onMutate: async (id: number) => {
      await queryClient.cancelQueries({ queryKey: [NOTIFICATIONS_KEY] });
      await queryClient.cancelQueries({ queryKey: [UNREAD_COUNT_KEY] });

      const previousNotifications = queryClient.getQueryData<
        TNotificationsResponse
      >([NOTIFICATIONS_KEY]);

      // Optimistically remove notification
      queryClient.setQueryData<TNotificationsResponse>(
        [NOTIFICATIONS_KEY],
        (oldData) => {
          if (!oldData) return oldData;
          const removed = oldData.data.find((n) => n.id === id);
          return {
            ...oldData,
            data: oldData.data.filter((notification) => notification.id !== id),
            total: oldData.total - 1,
            meta: {
              ...oldData.meta,
              total: oldData.meta.total - 1,
              read: removed?.read_at
                ? oldData.meta.read - 1
                : oldData.meta.read,
              unread: removed?.read_at
                ? oldData.meta.unread
                : Math.max(0, oldData.meta.unread - 1),
            },
          };
        }
      );

      // Update unread count if notification was unread
      if (previousNotifications) {
        const removed = previousNotifications.data.find((n) => n.id === id);
        if (removed && !removed.read_at) {
          queryClient.setQueryData<TUnreadCountResponse>(
            [UNREAD_COUNT_KEY],
            (oldData) => {
              if (!oldData) return oldData;
              return {
                unread_count: Math.max(0, oldData.unread_count - 1),
              };
            }
          );
        }
      }

      return { previousNotifications };
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [NOTIFICATIONS_KEY] });
      queryClient.invalidateQueries({ queryKey: [UNREAD_COUNT_KEY] });
    },
  });
};
