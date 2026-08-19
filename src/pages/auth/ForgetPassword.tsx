import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import * as z from "zod";
import { Mail, KeyRound } from "lucide-react"; // Icons

import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useMutation } from "@tanstack/react-query";
import { useForgetPasswordMutationOptions } from "@/api/auth/auth.hooks";
import { TLoginGuard } from "@/api/auth/auth.types";
import { toast } from "sonner";
import { useNavigate } from "react-router";

// Your provided ROLES constant
const ROLES = [
  {
    label: "Admin",
    value: "admin-api",
  },
  {
    label: "Branch Manager",
    value: "branchManager-api",
  },
  {
    label: "Employee",
    value: "employee-api",
  },
  {
    label: "Branch",
    value: "branch-api",
  },
];

// 1. Define the validation schema
const formSchema = z.object({
  email: z
    .string()
    .min(1, { message: "البريد الإلكتروني مطلوب." })
    .email({ message: "الرجاء إدخال بريد إلكتروني صالح." }),
  role: z.string().min(1, { message: "الرجاء اختيار الدور." }),
});

export default function ForgetPassword() {
  const { mutate, isPending } = useMutation(useForgetPasswordMutationOptions());
  // 2. Define the form
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      email: "",
      role: "",
    },
  });

  const navigate = useNavigate();

  // 3. Define the submit handler
  function onSubmit(values: z.infer<typeof formSchema>) {
    mutate(
      { emOrMb: values.email, guard: values.role as TLoginGuard },
      {
        onSuccess: () => {
          toast.success("تم ارسال OTP بنجاح   ");
          navigate("/verify-otp", {
            state: { email: values.email, role: values.role },
          });
        },
        onError: (error) => {
          toast.error("حدث خطأ ما   ", {
            description: error.message,
          });
        },
      }
    );
  }

  return (
    <div className="w-full max-w-md mx-auto p-4">
      <div className="text-center mb-8">
        <h2 className="text-3xl font-bold">نسيت كلمة السر؟</h2>
        <p className="text-gray-500 mt-2">
          أدخل بريدك الإلكتروني ودورك لإرسال رابط إعادة التعيين.
        </p>
      </div>

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          {/* --- Role Select Field --- */}
          <FormField
            control={form.control}
            name="role"
            render={({ field }) => (
              <FormItem>
                <FormLabel className="text-right text-gray-700 font-medium">
                  الدور
                </FormLabel>
                <Select
                  onValueChange={field.onChange}
                  defaultValue={field.value}
                  dir="rtl" // Set direction for RTL
                >
                  <FormControl>
                    <SelectTrigger className="pl-10 text-right">
                      <SelectValue placeholder="اختر دورك" />
                      {/* Icon inside the trigger */}
                      <KeyRound className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    {ROLES.map((role) => (
                      <SelectItem
                        key={role.value}
                        value={role.value}
                        className="text-right" // Align items right
                      >
                        {role.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <FormMessage className="text-right" />
              </FormItem>
            )}
          />

          {/* --- Email Input Field --- */}
          <FormField
            control={form.control}
            name="email"
            render={({ field }) => (
              <FormItem>
                <FormLabel className="text-right text-gray-700 font-medium">
                  البريد الإلكتروني
                </FormLabel>
                <FormControl>
                  <div className="relative">
                    <Input
                      placeholder="yousef@gmail.com"
                      className="pl-10 text-right"
                      {...field}
                    />
                    <Mail className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
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
            isLoading={isPending}
          >
            إرسال رابط إعادة التعيين
          </Button>
        </form>
      </Form>
    </div>
  );
}
