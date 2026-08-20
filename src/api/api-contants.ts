import { useAuthStore } from "@/zustand-stores/auth.store";
import { api } from "./api-instance";
import { normalizeAtelierEnvelope, resolveError } from "./api.utils";

export { api, applyTenantApiBaseUrl, resetTenantApiBaseUrl } from "./api-instance";

api.interceptors.request.use((config) => {
  const { isAuthenticated, loginData } = useAuthStore.getState();
  const token = loginData?.token;
  if (isAuthenticated && token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  const tenantSlug = loginData?.tenant?.slug?.trim();
  if (tenantSlug) {
    config.headers["X-Tenant"] = tenantSlug;
  }

  if (config.data instanceof FormData) {
    delete config.headers["Content-Type"];
  }
  return config;
});

api.interceptors.response.use(
  (response) => {
    const url = String(response.config.url ?? "");
    const isAuthLogin =
      /\/login(?:\?|$)/.test(url) ||
      /\/login\/google(?:\?|$)/.test(url);
    if (response.config.responseType !== "blob" && !isAuthLogin) {
      response.data = normalizeAtelierEnvelope(response.data);
    } else if (isAuthLogin) {
      // Always expose the inner login payload (token/user/tenant/endpoints).
      const payload = response.data;
      if (
        payload &&
        typeof payload === "object" &&
        (payload as { success?: boolean }).success === true &&
        "data" in (payload as object)
      ) {
        response.data = (payload as { data: unknown }).data;
      }
    }
    if (import.meta.env.MODE === "development") {
      console.log(
        " api response ",
        ` ${response.config.method} : ${response.config.url}`,
        response
      );
    }
    return response;
  },
  (error) => {
    const { logout } = useAuthStore.getState();
    if (error.response?.status === 401) {
      logout();
    }
    if (import.meta.env.MODE === "development") {
      console.log("error api", error);
    }
    const handledError = resolveError(error);
    if (import.meta.env.MODE === "development") {
      console.log("handledError", handledError);
    }
    return Promise.reject(new Error(handledError));
  }
);
