import { api } from "../api-contants";
import { populateError } from "../api.utils";
import {
  TForgetPasswordRequest,
  TResetPasswordRequest,
  TVerifyOtpRequest,
} from "./auth.types";
import { useAuthStore } from "@/zustand-stores/auth.store";

export const forgetPasswordApi = async (req: TForgetPasswordRequest) => {
  try {
    await api.post("/auth/forgot-password", req);
  } catch (error) {
    populateError(error, "خطأ اثناء تسجيل نسيان كلمة السر");
  }
};

export const resendOtpApi = async (req: TForgetPasswordRequest) => {
  try {
    await api.post("/auth/send-code-forgot-password", req);
  } catch (error) {
    populateError(error, "خطأ اثناء إعادة إرسال الرمز");
  }
};

export const verifyOtpApi = async (req: TVerifyOtpRequest) => {
  try {
    await api.post("/auth/check-forgot-password", req);
  } catch (error) {
    populateError(error, "خطأ اثناء التحقق من الرمز");
  }
};

export const resetPasswordApi = async (req: TResetPasswordRequest) => {
  try {
    await api.post("/auth/reset-password", req);
  } catch (error) {
    populateError(error, "خطأ اثناء إعادة تعيين كلمة السر");
  }
};

export const getMyPermissionsApi = async () => {
  const { loginData } = useAuthStore.getState();
  return loginData?.permissions ?? [];
};
