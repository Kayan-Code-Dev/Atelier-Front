import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useState, useEffect } from "react";
import { Eye, EyeOff } from "lucide-react";
import { useLocation, useNavigate, Link } from "react-router";

// --- Real shadcn/ui Imports ---
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent } from "@/components/ui/card";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { useMutation } from "@tanstack/react-query";
import { useResetPasswordMutationOptions } from "@/api/auth/auth.hooks";
import { toast } from "sonner";

const formSchema = z
  .object({
    password: z
      .string()
      .min(6, { message: "كلمة السر يجب أن تحتوي على 6 أحرف على الأقل." }),
    confirmPassword: z
      .string()
      .min(6, { message: "كلمة السر يجب أن تحتوي على 6 أحرف على الأقل." }),
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: "كلمتا السر غير متطابقتين.",
    path: ["confirmPassword"], // Attach error to the confirmPassword field
  });

type FormValues = z.infer<typeof formSchema>;

const ResetPassword = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { mutate, isPending } = useMutation(useResetPasswordMutationOptions());

  // State for password visibility
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  // 2. Get state from location (email, role, and code from VerifyCode)
  const { email, role } =
    (location.state as { email: string; role: string }) || {};

  // 3. Guard Effect: Redirect if state is missing
  useEffect(() => {
    // We need all three to securely reset the password
    if (!email || !role) {
      toast.error("Missing email, role in state. Redirecting...");
      navigate("/forget-password");
    }
  }, [email, role, navigate]);

  // 4. Define the form
  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      password: "",
      confirmPassword: "",
    },
  });

  // 5. Define the submit handler
  const onSubmit = (data: FormValues) => {
    mutate(
      {
        emOrMb: email,
        guard: role as any,
        password: data.password,
        password_confirmation: data.confirmPassword,
      },
      {
        onSuccess: () => {
          navigate("/login", {
            state: { passwordResetSuccess: true },
          });
        },
        onError: (error: any) => {
          toast.error("Reset password failed", {
            description: error.message,
          });
          form.setError("root", {
            type: "manual",
            message: "فشل تحديث كلمة السر. حاول مرة أخرى.",
          });
        },
      }
    );
  };

  // 6. Render null while redirecting
  if (!email || !role) {
    return null;
  }

  // 7. Render the component
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-gray-100 p-4">
      <div className="w-full max-w-md text-center">
        <h2 className="text-3xl font-bold">إعادة تعيين كلمة السر</h2>
        <p className="text-gray-500 mt-2 mb-8">
          أدخل كلمة سر جديدة لحسابك:
          <br />
          <span className="font-medium text-gray-900">{email}</span>
        </p>
      </div>

      <Card className="w-full max-w-md bg-white shadow-lg border-none">
        <CardContent className="pt-6">
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
              {/* --- Password Field --- */}
              <FormField
                control={form.control}
                name="password"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-right text-gray-700 font-medium">
                      كلمة السر الجديدة
                    </FormLabel>
                    <FormControl>
                      <div className="relative">
                        <Input
                          type={showPassword ? "text" : "password"}
                          placeholder="••••••••"
                          className="pl-10 text-right"
                          {...field}
                        />
                        <button
                          type="button"
                          onClick={() => setShowPassword(!showPassword)}
                          className="absolute left-3 top-2.5 text-gray-400"
                          aria-label={showPassword ? "إخفاء" : "إظهار"}
                        >
                          {showPassword ? (
                            <EyeOff className="h-5 w-5" />
                          ) : (
                            <Eye className="h-5 w-5" />
                          )}
                        </button>
                      </div>
                    </FormControl>
                    <FormMessage className="text-right" />
                  </FormItem>
                )}
              />

              {/* --- Confirm Password Field --- */}
              <FormField
                control={form.control}
                name="confirmPassword"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-right text-gray-700 font-medium">
                      تأكيد كلمة السر
                    </FormLabel>
                    <FormControl>
                      <div className="relative">
                        <Input
                          type={showConfirm ? "text" : "password"}
                          placeholder="••••••••"
                          className="pl-10 text-right"
                          {...field}
                        />
                        <button
                          type="button"
                          onClick={() => setShowConfirm(!showConfirm)}
                          className="absolute left-3 top-2.5 text-gray-400"
                          aria-label={showConfirm ? "إخفاء" : "إظهار"}
                        >
                          {showConfirm ? (
                            <EyeOff className="h-5 w-5" />
                          ) : (
                            <Eye className="h-5 w-5" />
                          )}
                        </button>
                      </div>
                    </FormControl>
                    <FormMessage className="text-right" />
                  </FormItem>
                )}
              />

              {/* --- Root Form Error --- */}
              {form.formState.errors.root && (
                <FormMessage className="text-right text-destructive">
                  {form.formState.errors.root.message}
                </FormMessage>
              )}

              <Button
                type="submit"
                className="w-full font-medium text-lg py-6 bg-[#64A5FF] cursor-pointer hover:bg-[#64a5ffee]"
                variant={"default"}
                isLoading={isPending}
              >
                تحديث كلمة السر
              </Button>
            </form>
          </Form>

          <div className="mt-6 text-center">
            <Button
              variant="link"
              className="p-0 h-auto text-sm text-gray-600 hover:text-primary"
            >
              <Link to="/login">العودة لتسجيل الدخول</Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default ResetPassword;
