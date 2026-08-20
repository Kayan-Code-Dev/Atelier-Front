import axios from "axios";
import { inferDressnTenantApiBaseUrl } from "@/lib/dressn-tenant-api-base";

/** Canonical Atelier tenant API — used when Vercel env overrides wipe VITE_BACKEND_URL. */
const ATELIER_TENANT_API = "https://atelier-api.dressnmore.it.com/api/tenant";

/**
 * Priority:
 * 1) VITE_BACKEND_URL → explicit backend URL (wins over relative mode)
 * 2) Vercel/preview host → canonical Atelier tenant API (SPA has no /api proxy)
 * 3) VITE_TENANT_RELATIVE_API=true → same origin + /api/v1 (nginx proxy)
 * 4) inferDressnTenantApiBaseUrl() → subdomain inference
 * 5) fallback → /api/v1 (same origin)
 */
export function getDefaultApiBaseUrl(): string {
  // 1) Explicit backend URL (wins when set — avoids Vercel/dashboard overrides)
  const envUrl = String(import.meta.env.VITE_BACKEND_URL ?? "").replace(
    /\/+$/,
    "",
  );
  if (envUrl) return envUrl;

  // 2) Preview/production FE on Vercel cannot rewrite /api to the tenant API
  if (typeof window !== "undefined") {
    const host = window.location.hostname.toLowerCase();
    if (host.endsWith(".vercel.app") || host === "vercel.app") {
      return ATELIER_TENANT_API;
    }
  } else if (import.meta.env.PROD) {
    // SSR/build-time default for production bundles without VITE_BACKEND_URL
    return ATELIER_TENANT_API;
  }

  // 3) Relative API (same-origin nginx proxy when no explicit backend URL)
  const tenantRelative =
    String(import.meta.env.VITE_TENANT_RELATIVE_API ?? "")
      .toLowerCase()
      .trim() === "true";
  if (tenantRelative && typeof window !== "undefined" && window.location?.origin) {
    return `${window.location.origin}/api/v1`.replace(/\/+$/, "");
  }

  // 4) Subdomain inference
  const inferred = inferDressnTenantApiBaseUrl();
  if (inferred) return inferred;

  // 5) Fallback
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
