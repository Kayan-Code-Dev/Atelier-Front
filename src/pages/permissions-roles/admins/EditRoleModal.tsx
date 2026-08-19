import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
// Import Select components
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useEditRoleMutationOptions } from "@/api/permissions-roles/admins/roles/roles.hooks";
import { useMutation } from "@tanstack/react-query";
import { useEffect } from "react";
import { ROLES, TRoleItem } from "@/api/permissions-roles/admins/roles/roles.types";

// Create a Zod enum from the ROLES object keys
const roleGuardEnum = z.enum(Object.keys(ROLES) as [string, ...string[]]);

// Form Schema
const formSchema = z.object({
  name: z.string().min(3, { message: "يجب أن يكون الاسم 3 أحرف على الأقل" }),
  guard_name: roleGuardEnum, // Add guard_name to the schema
});

type Props = {
  role: TRoleItem | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function EditRoleModal({ role, open, onOpenChange }: Props) {
  const { mutate: editRole, isPending } = useMutation(useEditRoleMutationOptions());

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      guard_name: "admin-api", // Add default
    },
  });

  // Load role data into form when modal opens
  useEffect(() => {
    if (role) {
      form.reset({
        name: role.name,
        guard_name: role.guard_name, // Add guard_name to reset
      });
    }
  }, [role, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!role) return;

    // 'values' now includes both 'name' and 'guard_name'
    editRole(
      { id: role.id, guard_name: values.guard_name as any, name: values.name },
      {
        onSuccess: () => {
          console.log("Role updated");
          onOpenChange(false);
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle className="text-center">تعديل الدور</DialogTitle>
          <DialogDescription className="text-center">
            تعديل بيانات الدور. انقر على "حفظ" عند الانتهاء.
          </DialogDescription>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            {/* --- THIS IS THE UPDATED BLOCK --- */}
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
                      {/* Iterate over the ROLES object */}
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
            {/* --- END OF UPDATED BLOCK --- */}

            {/* Role Name (Editable) */}
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

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => onOpenChange(false)}
              >
                إلغاء
              </Button>
              <Button type="submit" disabled={isPending}>
                {isPending ? "جاري الحفظ..." : "حفظ التغييرات"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
