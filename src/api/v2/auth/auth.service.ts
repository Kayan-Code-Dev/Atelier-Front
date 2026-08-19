import { api } from "@/api/api-contants";
import { TLoginRequest, TLoginResponse } from "./auth.types";
import { populateError } from "@/api/api.utils";

type LoginEnvelope = {
  success?: boolean;
  message?: string;
  data?: TLoginResponse;
};

/** Public landing uses /api/v1; tenant auth lives under /api/tenant. */
function resolveLoginUrl(): string {
  const base = String(api.defaults.baseURL ?? "").replace(/\/+$/, "");
  if (/\/api\/v1$/i.test(base)) {
    return `${base.replace(/\/api\/v1$/i, "/api/tenant")}/login`;
  }
  return "/login";
}

function unwrapLoginPayload(payload: LoginEnvelope | TLoginResponse): TLoginResponse {
  if (payload && typeof payload === "object" && "token" in payload && payload.token) {
    return payload as TLoginResponse;
  }
  const nested = (payload as LoginEnvelope)?.data;
  if (nested && typeof nested === "object" && nested.token) {
    return nested;
  }
  throw new Error("استجابة تسجيل الدخول غير صالحة");
}

export const loginApi = async (req: TLoginRequest) => {
  try {
    const { data } = await api.post<LoginEnvelope | TLoginResponse>(
      resolveLoginUrl(),
      req,
    );
    return unwrapLoginPayload(data);
  } catch (error: any) {
    populateError(error, "خطأ فى تسجيل الدخول");
  }
};
