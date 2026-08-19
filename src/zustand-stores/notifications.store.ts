import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export interface NotificationData {
  priority?: 'low' | 'normal' | 'high' | 'urgent';
  reference_type?: string;
  reference_id?: number;
  action_url?: string;
  metadata?: Record<string, any>;
}

export interface Notification {
  id: string;
  title: string;
  message: string;
  type: string;
  data?: NotificationData;
  timestamp: string;
  read: boolean;
}

interface NotificationsState {
  notifications: Notification[];
  unreadCount: number;
  addNotification: (notification: Omit<Notification, 'id' | 'read'>) => void;
  markAsRead: (id: string) => void;
  markAllAsRead: () => void;
  removeNotification: (id: string) => void;
  clearAll: () => void;
}

export const useNotificationsStore = create<NotificationsState>()(
  persist(
    (set) => ({
      notifications: [],
      unreadCount: 0,
      addNotification: (notification) =>
        set((state) => {
          // Use performance.now() for better ID generation
          const newNotification: Notification = {
            ...notification,
            id: `ws-${performance.now()}-${Math.random().toString(36).substr(2, 9)}`,
            read: false,
          };
          // Limit notifications array to prevent memory issues (keep last 50)
          const updatedNotifications = [newNotification, ...state.notifications].slice(0, 50);
          return {
            notifications: updatedNotifications,
            unreadCount: state.unreadCount + 1,
          };
        }),
      markAsRead: (id) =>
        set((state) => {
          const updated = state.notifications.map((n) =>
            n.id === id ? { ...n, read: true } : n
          );
          return {
            notifications: updated,
            unreadCount: updated.filter((n) => !n.read).length,
          };
        }),
      markAllAsRead: () =>
        set((state) => ({
          notifications: state.notifications.map((n) => ({ ...n, read: true })),
          unreadCount: 0,
        })),
      removeNotification: (id) =>
        set((state) => {
          const removed = state.notifications.find((n) => n.id === id);
          const updated = state.notifications.filter((n) => n.id !== id);
          return {
            notifications: updated,
            unreadCount: removed && !removed.read
              ? state.unreadCount - 1
              : state.unreadCount,
          };
        }),
      clearAll: () =>
        set({
          notifications: [],
          unreadCount: 0,
        }),
    }),
    {
      name: 'notifications-storage',
    }
  )
);
