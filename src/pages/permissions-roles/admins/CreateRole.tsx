import { useCreateRoleMutationOptions } from "@/api/permissions-roles/admins/roles/roles.hooks";
import { useMutation } from "@tanstack/react-query";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import * as z from "zod";

// shadcn/ui components
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
} from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { ROLES } from "@/api/permissions-roles/admins/roles/roles.types";
import { toast } from "sonner";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import { CREATE_ROLE } from "@/lib/permissions.helper";

// 1. Define the schema (same as EditRoleModal)
const roleGuardEnum = z.enum(Object.keys(ROLES) as [string, ...string[]]);
const formSchema = z.object({
  name: z.string().min(3, { message: "يجب أن يكون الاسم 3 أحرف على الأقل" }),
  guard_name: roleGuardEnum,
});

/**
 * A component to create a new role
 */
function CreateRole() {
  // 2. Get the mutation hook
  const { mutate: createRole, isPending } = useMutation(useCreateRoleMutationOptions());

  const { data: permissions } = useMyPermissions();

  const hasCreatePermissions = permissions && permissions.includes(CREATE_ROLE);
  // 3. Setup the form
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      guard_name: "admin-api", // Set a default guard
    },
  });

  // 4. Define the submit handler
  const onSubmit = (values: z.infer<typeof formSchema>) => {
    createRole(
      {
        guard_name: values.guard_name as any,
        name: values.name,
      },
      {
        onSuccess: () => {
          toast.success("تم إنشاء الصلاحية بنجاح"); // Reset the form after successful creation
          form.reset();
        },
        onError: (error) => {
          toast.error("خطأ عند إنشاء الصلاحية", {
            description: error.message,
          });
        },
      }
    );
  };

  // 5. Build the UI
  return (
    <Card dir="rtl" className="w-full">
      <CardHeader>
        <CardTitle>إنشاء دور جديد</CardTitle>
        <CardDescription>
          املأ النموذج لإنشاء دور جديد في النظام.
        </CardDescription>
      </CardHeader>

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-2">
          <CardContent className="space-y-4">
            {/* Guard Name Select */}
            <FormField
              control={form.control}
              name="guard_name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الخدمة (Guard)</FormLabel>
                  <Select
                    onValueChange={field.onChange}
                    defaultValue={field.value}
                    value={field.value}
                  >
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue placeholder="اختر الخدمة..." />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      {Object.values(ROLES).map((roleInfo) => (
                        <SelectItem key={roleInfo.value} value={roleInfo.value}>
                          {roleInfo.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Role Name Input */}
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>اسم الدور</FormLabel>
                  <FormControl>
                    <Input placeholder="اسم الدور..." {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </CardContent>
          <CardFooter>
            <Button
              type="submit"
              isLoading={isPending}
              disabled={!hasCreatePermissions}
            >
              {isPending ? "جاري الإنشاء..." : "إنشاء الدور"}
            </Button>
          </CardFooter>
        </form>
      </Form>
    </Card>
  );
}

export default CreateRole;
