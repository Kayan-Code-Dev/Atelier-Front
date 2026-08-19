export type TLoginGuard =
  | "admin-api"
  | "branchManager-api"
  | "employee-api"
  | "branch-api";

export type TForgetPasswordRequest = {
  emOrMb: string;
  guard: TLoginGuard;
};

export type TVerifyOtpRequest = {
  guard: TLoginGuard;
  emOrMb: string;
  code: string;
};

export type TResetPasswordRequest = {
  guard: TLoginGuard;
  emOrMb: string;
  password: string;
  password_confirmation: string;
};
