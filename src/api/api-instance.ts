import axios from "axios";
import { inferDressnTenantApiBaseUrl } from "@/lib/dressn-tenant-api-base";

/**
 * Priority:
 * 1) VITE_BACKEND_URL → explicit backend URL (wins over relative mode)
 * 2) VITE_TENANT_RELATIVE_API=true → same origin + /api/v1 (nginx proxy)
 * 3) inferDressnTenantApiBaseUrl() → subdomain inference
 * 4) fallback → /api/v1 (same origin)
 */
export function getDefaultApiBaseUrl(): string {
  // 1) Explicit backend URL (wins when set — avoids Vercel/dashboard overrides)
  const envUrl = String(import.meta.env.VITE_BACKEND_URL ?? "").replace(
    /\/+$/,
    "",
  );
  if (envUrl) return envUrl;

  // 2) Relative API (same-origin nginx proxy when no explicit backend URL)
  const tenantRelative =
    String(import.meta.env.VITE_TENANT_RELATIVE_API ?? "")
      .toLowerCase()
      .trim() === "true";
  if (tenantRelative && typeof window !== "undefined" && window.location?.origin) {
    return `${window.location.origin}/api/v1`.replace(/\/+$/, "");
  }

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
