import { Route } from "react-router";
import LoginPage from "@/pages/auth/login-page";
import ForgetPassword from "@/pages/auth/ForgetPassword";
import VerifyCode from "@/pages/auth/VerifyOtp";
import ResetPassword from "@/pages/auth/ResetPassword";

export default function getAuthRoutes() {
  return (
    <Route>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/admin/login" element={<LoginPage />} />
      <Route path="/forget-password" element={<ForgetPassword />} />
      <Route path="/verify-otp" element={<VerifyCode />} />
      <Route path="/reset-password" element={<ResetPassword />} />
    </Route>
  );
}
