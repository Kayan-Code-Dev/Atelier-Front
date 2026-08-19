import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useEffect } from "react";
import { ShieldCheck } from "lucide-react";
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
import {
  useResendOtpMutationOptions,
  useVerifyOtpMutationOptions,
} from "@/api/auth/auth.hooks";
import { toast } from "sonner";
// --- End of Imports ---

// 1. Define the validation schema
const formSchema = z.object({
  code: z
    .string()
    .min(6, { message: "الرمز يجب أن يكون 6 أرقام." })
    .max(6, { message: "الرمز يجب أن يكون 6 أرقام." }),
});

type FormValues = z.infer<typeof formSchema>;

const VerifyCode = () => {
  const location = useLocation();
  const navigate = useNavigate();

  // Added resend mutation
  const { mutate: resendMutate, isPending: isResending } =
    useMutation(useResendOtpMutationOptions());
  const { mutate: verifyMutate, isPending: isVerifying } =
    useMutation(useVerifyOtpMutationOptions());

  // 2. Get state from location
  const { email, role } =
    (location.state as { email: string; role: string }) || {};

  // 3. Guard Effect: Redirect if state is missing
  useEffect(() => {
    if (!email || !role) {
      console.error("Missing email or role in state. Redirecting...");
      navigate("/forget-password");
    }
  }, [email, role, navigate]);

  // 4. Define the form
  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      code: "",
    },
  });

  // 5. Define the submit handler
  const onSubmit = (data: FormValues) => {
    verifyMutate(
      { emOrMb: email, guard: role as any, code: data.code },
      {
        onSuccess: () => {
          toast.success("تم تحقق الرمز بنجاح");
          navigate("/reset-password", { state: { email, role } });
        },
        onError: (error: any) => {
          toast.error("فشل تحقق الرمز", {
            description: error.message,
          });
        },
      }
    );
  };

  // --- ADDED: Resend OTP Handler ---
  const handleResendOtp = () => {
    resendMutate(
      { emOrMb: email, guard: role as any },
      {
        onSuccess: () => {
          toast.success("OTP Resent Successfully!");
        },
        onError: (error: any) => {
          toast.error("Resend OTP failed", {
            description: error.message,
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
        <h2 className="text-3xl font-bold">تحقق من الرمز</h2>
        <p className="text-gray-500 mt-2 mb-8">
          لقد أرسلنا رمزًا مكونًا من 6 أرقام إلى <br />
          <span className="font-medium text-gray-900">{email}</span>
        </p>
      </div>

      <Card className="w-full max-w-md bg-white shadow-lg border-none">
        <CardContent className="pt-6">
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
              <FormField
                control={form.control}
                name="code"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-right text-gray-700 font-medium">
                      رمز التحقق
                    </FormLabel>
                    <FormControl>
                      <div className="relative">
                        <Input
                          placeholder="123456"
                          className="pl-10 text-right tracking-[0.3em] text-lg" // Added tracking for code feel
                          inputMode="numeric"
                          maxLength={6}
                          {...field}
                        />
                        <ShieldCheck className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                      </div>
                    </FormControl>
                    <FormMessage className="text-right" />
                  </FormItem>
                )}
              />

              <Button
                type="submit"
                className="w-full font-medium text-lg py-6 bg-[#64A5FF] cursor-pointer hover:bg-[#64a5ffee]"
                variant={"default"}
                isLoading={isVerifying}
              >
                تأكيد الرمز
              </Button>
            </form>
          </Form>

          {/* --- UPDATED: Added Resend Button --- */}
          <div className="mt-6 text-center space-y-4">
            <Button
              variant="link"
              className="p-0 h-auto text-sm text-gray-600 hover:text-primary"
              onClick={handleResendOtp}
              isLoading={isResending} // Use isResending state
              disabled={isResending}
            >
              لم تستلم الرمز؟ إعادة إرسال
            </Button>
            <div>
              <Button
                variant="link"
                className="p-0 h-auto text-sm text-gray-600 hover:text-primary"
              >
                <Link to="/forget-password">العودة لصفحة نسيت كلمة السر</Link>
              </Button>
            </div>
          </div>
          {/* --- END OF UPDATE --- */}
        </CardContent>
      </Card>
    </div>
  );
};

export default VerifyCode;
