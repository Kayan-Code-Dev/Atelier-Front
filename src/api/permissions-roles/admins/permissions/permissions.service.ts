import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TPermissionItem } from "./permissions.types";
import { TPaginationResponse } from "@/api/api-common.types";

export const getPermissionsList = async ({
  current_page,
}: {
  current_page: number;
}) => {
  try {
    const { data } = await api.get<{
      data: TPaginationResponse<TPermissionItem>;
    }>("/admins/permissions", {
      params: {
        page : current_page,
      },
    });
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error: any) {
    populateError(error, "خطأ اثناء جلب قائمة الصلاحيات");
  }
};
