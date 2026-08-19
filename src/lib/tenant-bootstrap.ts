import {
  applyTenantApiBaseUrl,
  resetTenantApiBaseUrl,
} from "@/api/api-instance";
import type { TLoginResponse } from "@/api/v2/auth/auth.types";
import { loadAuthenticatedLayoutRoutesModule } from "@/routes/authenticated-layout-routes.loader";
import { useAuthStore } from "@/zustand-stores/auth.store";

export const TENANT_BOOTSTRAP_HASH_PREFIX = "#bootstrap=";

const HASH_PREFIX = TENANT_BOOTSTRAP_HASH_PREFIX;

function utf8ToBase64Json(obj: unknown): string {
  const json = JSON.stringify(obj);
  const bytes = new TextEncoder().encode(json);
  let binary = "";
  for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]!);
  return btoa(binary);
}

function base64Utf8ToString(b64: string): string {
  const binary = atob(b64);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
  return new TextDecoder().decode(bytes);
}

/** Build hash payload for cross-subdomain handoff after login on hub */
export function buildTenantAuthBootstrapHash(data: TLoginResponse): string {
  return `${HASH_PREFIX}${encodeURIComponent(utf8ToBase64Json(data))}`;
}

/**
 * رابط الواجهة من الـ API قد ينتهي بـ /login أو /admin/login؛ إزالتها ضرورية
 * حتى لا يصبح التحويل …/login/dashboard (فيُعاد المستخدم لصفحة الدخول).
 */
export function normalizeTenantFrontendAppUrl(frontend: string): string {
  let s = frontend.trim().replace(/\/+$/, "");
  if (!/^https?:\/\//i.test(s)) {
    s = `https://${s}`;
  }
  s = s.replace(/\/(?:admin\/)?login$/i, "");
  return s.replace(/\/+$/, "");
}

/**
 * يُستدعى قبل `persist.rehydrate()`: عند التحويل من اللاندينغ مع `#bootstrap=…`
 * نزيل الجلسة المخزّنة حتى لا تُدمج فوق التوكن القادم من الهاش.
 */
export function discardPersistedAuthIfBootstrapRedirect(): void {
  if (typeof window === "undefined") return;
  if (!window.location.hash.startsWith(HASH_PREFIX)) return;
  try {
    localStorage.removeItem("auth-storage");
  } catch {
    /* private mode / blocked storage */
  }
}

/** Read #bootstrap=… from URL, persist session on this origin, strip hash */
export function consumeTenantBootstrapFromHash(): boolean {
  if (typeof window === "undefined") return false;
  const hash = window.location.hash;
  if (!hash.startsWith(HASH_PREFIX)) return false;
  try {
    const raw = decodeURIComponent(hash.slice(HASH_PREFIX.length));
    const parsed = JSON.parse(base64Utf8ToString(raw)) as TLoginResponse;
    if (!parsed?.token || !parsed?.user?.id) return false;
    useAuthStore.getState().login(parsed);
    window.history.replaceState(
      null,
      document.title,
      `${window.location.pathname}${window.location.search}`,
    );
    void loadAuthenticatedLayoutRoutesModule();
    return true;
  } catch {
    return false;
  }
}

/** After persist rehydrate: align axios base URL with stored session */
export function syncApiBaseUrlWithAuthState() {
  const { isAuthenticated, loginData } = useAuthStore.getState();
  if (isAuthenticated && loginData) {
    applyTenantApiBaseUrl(loginData.endpoints?.backend_api_url);
  } else {
    resetTenantApiBaseUrl();
  }
}
