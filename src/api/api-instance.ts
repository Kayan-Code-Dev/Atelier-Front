import axios from "axios";
import { inferDressnTenantApiBaseUrl } from "@/lib/dressn-tenant-api-base";

/**
 * Priority:
 * 1) VITE_TENANT_RELATIVE_API=true → same origin + /api/v1
 * 2) VITE_BACKEND_URL → explicit backend URL
 * 3) inferDressnTenantApiBaseUrl() → subdomain inference
 * 4) fallback → /api/v1 (same origin)
 */
export function getDefaultApiBaseUrl(): string {
  // 1) Relative API (HIGHEST priority - for nginx proxy)
  const tenantRelative =
    String(import.meta.env.VITE_TENANT_RELATIVE_API ?? "")
      .toLowerCase()
      .trim() === "true";
  if (tenantRelative && typeof window !== "undefined" && window.location?.origin) {
    return `${window.location.origin}/api/v1`.replace(/\/+$/, "");
  }

  // 2) Explicit backend URL
  const envUrl = String(import.meta.env.VITE_BACKEND_URL ?? "").replace(
    /\/+$/,
    "",
  );
  if (envUrl) return envUrl;

  // 3) Subdomain inference
  const inferred = inferDressnTenantApiBaseUrl();
  if (inferred) return inferred;

  // 4) Fallback
  return "/api/v1";
}

export const api = axios.create({
  baseURL: getDefaultApiBaseUrl() || undefined,
  withCredentials: true,
});

export function applyTenantApiBaseUrl(url?: string | null) {
  const next = (url?.trim() || getDefaultApiBaseUrl()).replace(/\/+$/, "");
  api.defaults.baseURL = next || undefined;
}

export function resetTenantApiBaseUrl() {
  api.defaults.baseURL = getDefaultApiBaseUrl() || undefined;
}
