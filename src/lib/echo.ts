import type { TLoginEndpoints } from "@/api/v2/auth/auth.types";
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

(window as any).Pusher = Pusher;

let echoInstance: Echo<any> | null = null;
let currentToken: string | null = null;
let currentEndpointsKey = "";

function parseTenantEchoConfig(
  endpoints: TLoginEndpoints | null | undefined,
): {
  wsHost: string;
  wsPort: number;
  scheme: "https" | "http";
  authEndpoint: string;
} | null {
  if (!endpoints?.reverb_public_url?.trim() || !endpoints.backend_api_origin?.trim()) {
    return null;
  }
  try {
    const u = new URL(endpoints.reverb_public_url);
    const scheme = u.protocol === "https:" ? "https" : "http";
    const wsPort = u.port ? Number(u.port) : scheme === "https" ? 443 : 80;
    const origin = endpoints.backend_api_origin.replace(/\/+$/, "");
    const authEndpoint = `${origin}/broadcasting/custom-auth`;
    return { wsHost: u.hostname, wsPort, scheme, authEndpoint };
  } catch {
    return null;
  }
}

export const initializeEcho = (
  token: string,
  endpoints?: TLoginEndpoints | null,
): Echo<any> => {
  const endpointsKey = JSON.stringify(endpoints ?? null);
  if (echoInstance && currentToken === token && currentEndpointsKey === endpointsKey) {
    return echoInstance;
  }

  if (echoInstance && (currentToken !== token || currentEndpointsKey !== endpointsKey)) {
    try { echoInstance.disconnect(); } catch { /* noop */ }
    echoInstance = null;
  }

  const appKey = import.meta.env.VITE_REVERB_APP_KEY;
  if (!appKey) {
    throw new Error('VITE_REVERB_APP_KEY is not defined.');
  }

  const tenantEcho = parseTenantEchoConfig(endpoints);

  const wsHost =
    tenantEcho?.wsHost ||
    import.meta.env.VITE_REVERB_HOST ||
    "api.dressnmore.it.com";
  const wsPort =
    tenantEcho?.wsPort ||
    Number(import.meta.env.VITE_REVERB_PORT) ||
    443;
  const scheme =
    tenantEcho?.scheme ||
    (import.meta.env.VITE_REVERB_SCHEME as "https" | "http") ||
    "https";
  const isSecure = scheme === "https";

  const fallbackAuth =
    import.meta.env.VITE_REVERB_AUTH_ENDPOINT ||
    "https://api.dressnmore.it.com/broadcasting/custom-auth";
  const authEndpoint = tenantEcho?.authEndpoint
    ? tenantEcho.authEndpoint
    : import.meta.env.DEV
      ? "/broadcasting/custom-auth"
      : fallbackAuth;

  currentToken = token;
  currentEndpointsKey = endpointsKey;

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: appKey,
    wsHost,
    wsPort,
    wssPort: wsPort,
    forceTLS: isSecure,
    enabledTransports: isSecure ? ['wss', 'ws'] : ['ws', 'wss'],
    disableStats: true,
    authEndpoint,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  });

  return echoInstance;
};

export const getEcho = (): Echo<any> | null => echoInstance;

export const disconnectEcho = (): void => {
  if (echoInstance) {
    try { echoInstance.disconnect(); } catch { /* noop */ }
    echoInstance = null;
  }
  currentToken = null;
  currentEndpointsKey = "";
};
