import { useLoginMutationOptions } from "@/api/v2/auth/auth.hooks";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { loadAuthenticatedLayoutRoutesModule } from "@/routes/authenticated-layout-routes.loader";
import {
  buildTenantAuthBootstrapHash,
  normalizeTenantFrontendAppUrl,
} from "@/lib/tenant-bootstrap";
import { useAuthStore } from "@/zustand-stores/auth.store";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation } from "@tanstack/react-query";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { Link, useNavigate } from "react-router";
import * as z from "zod";
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

const formSchema = z.object({
  email: z.string().email({
    message: "البريد الإلكتروني غير صحيح",
  }),
  password: z.string().min(6, {
    message: "كلمة السر يجب أن تحتوي على 6 أحرف على الأقل",
  }),
});

type FormValues = z.infer<typeof formSchema>;

const Login = () => {
  const [showPassword, setShowPassword] = useState(false);
  const [loginError, setLoginError] = useState(false);
  const navigate = useNavigate();
  const login = useAuthStore((state) => state.login);
  const { mutate, isPending } = useMutation(useLoginMutationOptions());

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      email: "",
      password: "",
    },
  });

  const onSubmit = (data: FormValues) => {
    mutate(
      {
        email: data.email,
        password: data.password,
      },
      {
        onSuccess: (res) => {
          if (!res) return;
          const frontend = res.endpoints?.frontend_app_url?.trim();
          if (frontend) {
            try {
              const base = normalizeTenantFrontendAppUrl(frontend);
              const targetOrigin = new URL(base).origin;
              if (targetOrigin !== window.location.origin) {
                window.location.replace(
                  `${base}/dashboard${buildTenantAuthBootstrapHash(res)}`,
                );
                return;
              }
            } catch {
              /* same-origin or invalid URL — continue with local session */
            }
          }
          void loadAuthenticatedLayoutRoutesModule();
          login(res);
          navigate("/dashboard");
        },
        onError: () => {
          setLoginError(true);
        },
      }
    );
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-blue-950 to-slate-900 relative overflow-hidden flex items-center justify-center px-4 py-12" dir="rtl">
      <style>{`
        @keyframes shimmer { 0%{background-position:200% center} 100%{background-position:-200% center} }
        @keyframes blob { 0%,100%{border-radius:60% 40% 30% 70%/60% 30% 70% 40%} 50%{border-radius:30% 60% 70% 40%/50% 60% 30% 60%} }
        .shimmer-text { background: linear-gradient(90deg, #1e40af, #0ea5e9, #1e40af); background-size: 200% auto; -webkit-background-clip: text; -webkit-text-fill-color: transparent; animation: shimmer 3s linear infinite; }
        .blob { animation: blob 8s ease-in-out infinite; }
        .glass { background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.15); }
        .glass-card { background: rgba(255,255,255,0.95); backdrop-filter: blur(24px); border: 1px solid rgba(255,255,255,0.2); }
        .text-gradient { background: linear-gradient(135deg, #1e3a8a, #0ea5e9, #38bdf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
      `}</style>

      {/* Background effects */}
      <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(circle at 20% 50%, rgba(59,130,246,0.5) 0%, transparent 50%), radial-gradient(circle at 80% 80%, rgba(14,165,233,0.3) 0%, transparent 50%)' }} />
      <div className="absolute inset-0 opacity-5" style={{ backgroundImage: 'linear-gradient(rgba(255,255,255,0.05) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.05) 1px,transparent 1px)', backgroundSize: '40px 40px' }} />
      <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500/10 blob" style={{ filter: 'blur(60px)' }} />
      <div className="absolute bottom-1/4 right-1/4 w-80 h-80 bg-cyan-400/10 blob" style={{ filter: 'blur(50px)', animationDelay: '3s' }} />

      <div className="relative z-10 w-full max-w-md">
        {/* Back to home */}
        <Link
          to="/"
          className="inline-flex items-center gap-2 text-white/70 hover:text-white text-sm font-medium mb-8 transition-colors duration-300"
        >
          <i className="ri-arrow-right-line text-lg" />
          العودة للرئيسية
        </Link>

        <div className="glass-card rounded-3xl shadow-2xl overflow-hidden">
          {/* Header */}
          <div className="bg-gradient-to-l from-blue-900 to-blue-600 px-8 py-8 text-center">
            <Link to="/" className="inline-flex items-center gap-3 mb-6">
              <div className="w-12 h-12 flex items-center justify-center rounded-xl overflow-hidden bg-white shadow-md">
                <img src="/dressnmore-logo.jpg" alt="DressnMore" className="w-full h-full object-cover" />
              </div>
              <span className="text-2xl font-black text-white tracking-tight">DressnMore</span>
            </Link>
            <h1 className="text-xl font-bold text-white mb-2">تسجيل الدخول</h1>
            <p className="text-white/80 text-sm">أدخل بياناتك للوصول إلى لوحة التحكم</p>
          </div>

          {/* Form */}
          <div className="p-8">
            <Form {...form}>
              <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5" dir="rtl">
                <FormField
                  control={form.control}
                  name="email"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-right text-sm font-medium text-gray-700">
                        البريد الإلكتروني
                      </FormLabel>
                      <FormControl>
                        <div className="relative">
                          <Input
                            placeholder="example@dressnmore.com"
                            className="pr-4 pl-11 text-right h-12 rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                            {...field}
                          />
                          <i className="ri-user-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg" />
                        </div>
                      </FormControl>
                      <FormMessage className="text-right text-xs text-red-500" />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="password"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-right text-sm font-medium text-gray-700">
                        كلمة المرور
                      </FormLabel>
                      <FormControl>
                        <div className="relative">
                          <Input
                            type={showPassword ? "text" : "password"}
                            placeholder="••••••••"
                            className="pr-4 pl-11 h-12 rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                            {...field}
                          />
                          <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            aria-label={showPassword ? "إخفاء كلمة السر" : "إظهار كلمة السر"}
                          >
                            <i className={`text-lg ${showPassword ? "ri-eye-off-line" : "ri-eye-line"}`} />
                          </button>
                        </div>
                      </FormControl>
                      <FormMessage className="text-right text-xs text-red-500" />
                    </FormItem>
                  )}
                />

                <div className="flex items-center justify-between gap-2 text-xs">
                  <span className="text-gray-500">جلسات آمنة ومشفرة</span>
                  <Link
                    to="/forget-password"
                    className="text-blue-600 hover:text-blue-700 font-medium"
                  >
                    نسيت كلمة المرور؟
                  </Link>
                </div>

                <button
                  type="submit"
                  disabled={isPending}
                  className="w-full h-12 rounded-xl bg-gradient-to-l from-blue-900 to-blue-500 text-white font-bold text-base hover:shadow-lg hover:shadow-blue-500/40 hover:scale-[1.02] transition-all duration-300 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                  {isPending ? (
                    <>
                      <i className="ri-loader-4-line animate-spin text-xl" />
                      جاري التحقق...
                    </>
                  ) : (
                    <>
                      <i className="ri-login-box-line" />
                      تسجيل الدخول
                    </>
                  )}
                </button>
              </form>
            </Form>

            <p className="mt-6 text-xs text-gray-500 text-center">
              يتم تأمين البيانات وتشفيرها لحماية معلوماتك
            </p>
          </div>
        </div>
      </div>

      {/* Login Error Dialog */}
      <Dialog open={loginError} onOpenChange={setLoginError}>
        <DialogContent className="max-w-md rounded-2xl p-6 text-center border-0 shadow-2xl" dir="rtl">
          <div className="w-16 h-16 flex items-center justify-center bg-red-50 rounded-full mx-auto mb-4">
            <i className="ri-error-warning-line text-red-500 text-3xl" />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">فشل تسجيل الدخول</h2>
          <p className="mb-6 text-sm text-gray-500">
            تأكد من صحة البريد الإلكتروني وكلمة المرور ثم حاول مرة أخرى.
          </p>
          <div className="flex gap-3">
            <Button
              variant="outline"
              onClick={() => setLoginError(false)}
              className="flex-1 rounded-xl"
            >
              إلغاء
            </Button>
            <Button
              onClick={() => {
                form.reset();
                setLoginError(false);
              }}
              className="flex-1 rounded-xl bg-gradient-to-l from-blue-900 to-blue-500"
            >
              إعادة المحاولة
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default Login;
