import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import {
  TGetNotificationsParams,
  TNotificationsResponse,
  TUnreadCountResponse,
} from "./notifications.types";

export const getNotifications = async (params: TGetNotificationsParams) => {
  try {
    const { data } = await api.get<TNotificationsResponse>("/notifications", {
      params,
    });
    return data;
  } catch (error) {
    populateError(error, "خطأ في جلب الإشعارات");
  }
};

export const getUnreadCount = async () => {
  try {
    const { data } = await api.get<TUnreadCountResponse>(
      "/notifications/unread-count"
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ في جلب عدد الإشعارات غير المقروءة");
  }
};

export const markNotificationAsRead = async (id: number) => {
  try {
    await api.post(`/notifications/${id}/read`);
  } catch (error) {
    populateError(error, "خطأ في تحديد الإشعار كمقروء");
  }
};

export const markAllNotificationsAsRead = async () => {
  try {
    await api.post("/notifications/mark-all-read");
  } catch (error) {
    populateError(error, "خطأ في تحديد جميع الإشعارات كمقروءة");
  }
};

export const deleteNotification = async (id: number) => {
  try {
    await api.delete(`/notifications/${id}`);
  } catch (error) {
    populateError(error, "خطأ في حذف الإشعار");
  }
};
