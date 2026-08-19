import {
  applyTenantApiBaseUrl,
  resetTenantApiBaseUrl,
} from "@/api/api-instance";
import { TLoginResponse } from "@/api/v2/auth/auth.types";
import { loadAuthenticatedLayoutRoutesModule } from "@/routes/authenticated-layout-routes.loader";
import { create } from "zustand";
import { persist } from "zustand/middleware";

/**
 * Auth state is persisted to localStorage by default (Zustand persist).
 * Token in localStorage is readable by any script on the page; avoid XSS
 * (e.g. no dangerouslySetInnerHTML with user content). Prefer HTTP-only
 * cookies for token if the backend supports it.
 */
type AuthState = {
  isAuthenticated: boolean;
  loginData: TLoginResponse | undefined;
  login: (loginData: TLoginResponse) => void;
  logout: () => void;
};

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      isAuthenticated: false,
      loginData: undefined,
      login: (loginData: TLoginResponse) => {
        applyTenantApiBaseUrl(loginData.endpoints?.backend_api_url);
        set({ isAuthenticated: true, loginData });
      },
      logout: () => {
        resetTenantApiBaseUrl();
        set({ isAuthenticated: false, loginData: undefined });
      },
    }),
    {
      name: "auth-storage",
      onRehydrateStorage: () => (state) => {
        if (
          typeof window !== "undefined" &&
          window.location.hash.startsWith("#bootstrap=")
        ) {
          return;
        }
        if (state?.isAuthenticated && state.loginData) {
          applyTenantApiBaseUrl(state.loginData.endpoints?.backend_api_url);
          void loadAuthenticatedLayoutRoutesModule();
        }
      },
    }
  )
);
