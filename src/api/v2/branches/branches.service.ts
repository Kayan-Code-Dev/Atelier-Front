import { api } from "@/api/api-contants";
import {
  TCreateBranchRequest,
  TBranchResponse,
  TUpdateBranchRequest,
} from "./branches.types";
import { populateError } from "@/api/api.utils";
import { TPaginationResponse } from "@/api/api-common.types";

const toBranchFormData = (
  data: TCreateBranchRequest | TUpdateBranchRequest
): FormData => {
  const formData = new FormData();
  if (data.branch_code != null) formData.append("branch_code", data.branch_code);
  if (data.name != null) formData.append("name", data.name);
  if (data.inventory_name != null)
    formData.append("inventory_name", data.inventory_name);
  if (data.phone != null) formData.append("phone", data.phone);
  if (typeof data.vat_enabled === "boolean") {
    formData.append("vat_enabled", data.vat_enabled ? "1" : "0");
  }
  if (data.vat_type != null) formData.append("vat_type", data.vat_type);
  if (data.vat_value != null)
    formData.append("vat_value", String(data.vat_value));
  if (data.currency_id != null) {
    formData.append("currency_id", String(data.currency_id));
  }
  if (data.address) {
    formData.append("address[street]", data.address.street);
    formData.append("address[building]", data.address.building);
    formData.append("address[city_id]", String(data.address.city_id));
    formData.append("address[notes]", data.address.notes ?? "");
  }
  if (data.image instanceof File) {
    formData.append("image", data.image);
  }
  return formData;
};

export const createBranch = async (data: TCreateBranchRequest) => {
  try {
    const hasFile = data.image instanceof File;
    if (hasFile) {
      const formData = toBranchFormData(data);
      const { data: response } = await api.post<TBranchResponse>(
        "/branches",
        formData
      );
      return response;
    }
    const { image: _, ...jsonData } = data;
    const { data: response } = await api.post<TBranchResponse>(
      "/branches",
      jsonData
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export const updateBranch = async (id: number, data: TUpdateBranchRequest) => {
  try {
    const hasFile = data.image instanceof File;
    if (hasFile) {
      const formData = toBranchFormData(data);
      formData.append("_method", "PUT");
      const { data: response } = await api.post<TBranchResponse>(
        `/branches/${id}`,
        formData
      );
      return response;
    }
    const { image: _, ...jsonData } = data;
    const { data: response } = await api.put<TBranchResponse>(
      `/branches/${id}`,
      jsonData
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى تحديث الفرع");
  }
};

/** Optional filters for GET /api/v1/branches (index) */
export type TGetBranchesParams = {
  search?: string;
  id?: number | string;
  address_id?: number;
  vat_enabled?: boolean;
};

export const getBranches = async (
  page: number,
  per_page: number,
  params?: TGetBranchesParams
) => {
  try {
    const query: Record<string, string | number | boolean | undefined> = {
      page,
      per_page,
      ...(params?.search != null && params.search !== ""
        ? { search: params.search }
        : {}),
      ...(params?.id != null ? { id: params.id } : {}),
      ...(params?.address_id != null ? { address_id: params.address_id } : {}),
      ...(params?.vat_enabled !== undefined
        ? { vat_enabled: params.vat_enabled }
        : {}),
    };
    const { data: response } = await api.get<
      TPaginationResponse<TBranchResponse>
    >("/branches", {
      params: query,
    });
    return response;
  } catch (error) {
    populateError(error, "خطأ فى احضار الفروع");
  }
};

export const getBranch = async (id: number) => {
  try {
    const { data: response } = await api.get<TBranchResponse>(
      `/branches/${id}`
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى احضار الفرع");
  }
};

export const deleteBranch = async (id: number) => {
  try {
    const { data: response } = await api.delete<TBranchResponse>(
      `/branches/${id}`
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى حذف الفرع");
  }
};

export const exportBranchesToCSV = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>(`/branches/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير الفروع");
  }
};
