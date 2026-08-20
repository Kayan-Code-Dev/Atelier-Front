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
  shouldSkipTenantFrontendRedirect,
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
          if (frontend && !shouldSkipTenantFrontendRedirect()) {
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
    <div
      className="min-h-screen relative overflow-hidden flex items-center justify-center px-4 py-12"
      dir="rtl"
      style={{
        background:
          "radial-gradient(ellipse 70% 50% at 10% 20%, rgba(13,110,95,0.18), transparent 50%), radial-gradient(ellipse 60% 40% at 90% 80%, rgba(11,31,51,0.12), transparent 45%), #eef1f4",
      }}
    >
      <style>{`
        .login-grid {
          background-image:
            linear-gradient(rgba(11,31,51,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(11,31,51,0.04) 1px, transparent 1px);
          background-size: 32px 32px;
        }
        .login-card-enter {
          animation: pageEnter 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
      `}</style>

      <div className="absolute inset-0 login-grid pointer-events-none" />
      <div
        className="absolute inset-0 pointer-events-none opacity-40"
        style={{
          backgroundImage:
            "url(\"data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.05'/%3E%3C/svg%3E\")",
        }}
      />

      <div className="relative z-10 w-full max-w-md login-card-enter">
        <Link
          to="/"
          className="inline-flex items-center gap-2 text-sm font-medium mb-8 transition-colors duration-200"
          style={{ color: "var(--color-text-secondary)" }}
        >
          <i className="ri-arrow-right-line text-lg" />
          العودة للرئيسية
        </Link>

        <div
          className="overflow-hidden"
          style={{
            background: "white",
            border: "1px solid var(--color-border)",
            borderRadius: "12px",
            boxShadow: "0 16px 40px rgba(11,31,51,0.1)",
          }}
        >
          <div
            className="px-8 py-9 text-center"
            style={{
              background: "linear-gradient(155deg, #071521 0%, #0b1f33 55%, #143048 100%)",
            }}
          >
            <Link to="/" className="inline-flex flex-col items-center gap-3 mb-5">
              <div
                className="w-12 h-12 flex items-center justify-center rounded-md"
                style={{
                  background: "linear-gradient(145deg, #0d6e5f, #0f8a76)",
                  boxShadow: "0 4px 14px rgba(13,110,95,0.45)",
                }}
              >
                <i className="ri-scissors-cut-fill text-white text-xl" />
              </div>
              <span
                className="font-brand text-3xl font-bold tracking-tight text-white"
                style={{ letterSpacing: "-0.02em" }}
              >
                Atelier
              </span>
            </Link>
            <p
              className="text-[11px] font-semibold uppercase tracking-[0.18em] mb-4"
              style={{ color: "rgba(15,138,118,0.95)" }}
            >
              Studio Operations
            </p>
            <h1 className="text-xl font-bold text-white mb-2 font-display">تسجيل الدخول</h1>
            <p className="text-white/70 text-sm">أدخل بياناتك للوصول إلى لوحة التحكم</p>
          </div>

          <div className="p-8">
            <Form {...form}>
              <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5" dir="rtl">
                <FormField
                  control={form.control}
                  name="email"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-right text-sm font-medium" style={{ color: "var(--color-text-secondary)" }}>
                        البريد الإلكتروني
                      </FormLabel>
                      <FormControl>
                        <div className="relative">
                          <Input
                            placeholder="you@atelier.app"
                            className="pr-4 pl-11 text-right h-11 rounded-md border-[var(--color-border)] focus-visible:border-[var(--emerald)] focus-visible:ring-[var(--emerald)]/20"
                            {...field}
                          />
                          <i className="ri-user-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg" />
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
                      <FormLabel className="text-right text-sm font-medium" style={{ color: "var(--color-text-secondary)" }}>
                        كلمة المرور
                      </FormLabel>
                      <FormControl>
                        <div className="relative">
                          <Input
                            type={showPassword ? "text" : "password"}
                            placeholder="••••••••"
                            className="pr-4 pl-11 h-11 rounded-md border-[var(--color-border)] focus-visible:border-[var(--emerald)] focus-visible:ring-[var(--emerald)]/20"
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
                  <span style={{ color: "var(--color-text-muted)" }}>جلسات آمنة ومشفرة</span>
                  <Link
                    to="/forget-password"
                    className="font-medium transition-colors"
                    style={{ color: "var(--emerald)" }}
                  >
                    نسيت كلمة المرور؟
                  </Link>
                </div>

                <button
                  type="submit"
                  disabled={isPending}
                  className="w-full h-11 rounded-md text-white font-bold text-base transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                  style={{
                    background: "linear-gradient(135deg, #0d6e5f, #0f8a76)",
                    boxShadow: "0 2px 10px rgba(13,110,95,0.28)",
                  }}
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

            <p className="mt-6 text-xs text-center" style={{ color: "var(--color-text-muted)" }}>
              يتم تأمين البيانات وتشفيرها لحماية معلوماتك
            </p>
          </div>
        </div>
      </div>

      <Dialog open={loginError} onOpenChange={setLoginError}>
        <DialogContent className="max-w-md rounded-lg p-6 text-center border-0 shadow-xl" dir="rtl">
          <div className="w-16 h-16 flex items-center justify-center bg-red-50 rounded-full mx-auto mb-4">
            <i className="ri-error-warning-line text-red-500 text-3xl" />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2 font-display">فشل تسجيل الدخول</h2>
          <p className="mb-6 text-sm text-gray-500">
            تأكد من صحة البريد الإلكتروني وكلمة المرور ثم حاول مرة أخرى.
          </p>
          <div className="flex gap-3">
            <Button
              variant="outline"
              onClick={() => setLoginError(false)}
              className="flex-1 rounded-md"
            >
              إلغاء
            </Button>
            <Button
              onClick={() => {
                form.reset();
                setLoginError(false);
              }}
              className="flex-1 rounded-md"
              style={{ background: "var(--emerald)" }}
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
