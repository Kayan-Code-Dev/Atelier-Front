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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { useForm, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useEffect } from "react";
import { ScrollArea } from "@/components/ui/scroll-area";
import { TAdmin } from "@/api/hr/admins/admins.types";
import { useGetAdminRolesQueryOptions, useUpdateAdminMutationOptions } from "@/api/hr/admins/admins.hooks";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { CountriesSelectByModule } from "@/components/custom/CountriesSelectByModule";
import { CitiesSelectByModule } from "@/components/custom/CitiesSelectByModule";

// Schema (we don't validate the image file on edit unless it changes)
const formSchema = z.object({
  role_id: z.string({ required_error: "الدور مطلوب" }),
  first_name: z.string().min(2, { message: "الاسم الأول مطلوب" }),
  last_name: z.string().min(2, { message: "الاسم الأخير مطلوب" }),
  email: z.string().email({ message: "بريد إلكتروني غير صالح" }),
  phone: z.string().min(8, { message: "رقم الهاتف مطلوب" }),
  id_number: z.string().min(5, { message: "رقم الهوية مطلوب" }),
  country_id: z.string({ required_error: "الدولة مطلوبة" }),
  city_id: z.string({ required_error: "المدينة مطلوبة" }),
  image: z.any().optional(), // Allow file or undefined
});

type Props = {
  admin: TAdmin | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function EditAdminModal({ admin, open, onOpenChange }: Props) {
  const { mutate: updateAdmin, isPending } = useMutation(useUpdateAdminMutationOptions());

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
  });

  // Watch the selected country to trigger the cities query
  const selectedCountryId = useWatch({
    control: form.control,
    name: "country_id",
  });

  // Fetch admin roles
  const { data: adminRoles, isLoading: isLoadingRoles } = useQuery(useGetAdminRolesQueryOptions());

  // Load admin data into form when modal opens
  useEffect(() => {
    if (admin && open) {
      // TAdmin has `name`, but form needs `first_name` and `last_name`
      const [firstName, ...lastName] = admin.name.split(" ");

      form.reset({
        role_id: (admin as any).role_id?.toString() || "", // Assuming admin object has role_id
        first_name: firstName,
        last_name: lastName.join(" "),
        email: admin.email,
        phone: admin.phone,
        id_number: admin.id_number,
        country_id: admin.country, // Assuming country is the ID
        city_id: admin.city, // Assuming city is the ID
        image: undefined, // Don't pre-fill file input
      });
    }
  }, [admin, form, open]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!admin) return;

    const formData = new FormData();
    // Append all form fields
    Object.keys(values).forEach((key) => {
      const formKey = key as keyof typeof values;
      // Only append if value is not undefined (don't send empty image)
      if (values[formKey] !== undefined) {
        // Handle file
        if (formKey === "image" && values[formKey] instanceof File) {
          formData.append(formKey, values[formKey]);
        } else if (formKey !== "image") {
          formData.append(formKey, values[formKey] as string);
        }
      }
    });
    // Add _method for Laravel
    formData.append("_method", "PUT");

    updateAdmin(
      { id: admin.uuid, data: formData },
      {
        onSuccess: () => {
          toast.success("تم تحديث المشرف بنجاح");
          form.reset();
          onOpenChange(false);
        },
        onError: () => {
          toast.error("حدث خطأ أثناء تحديث المشرف");
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="text-center">
            تعديل مشرف: {admin?.name}
          </DialogTitle>
          <DialogDescription className="text-center">
            قم بتعديل البيانات وانقر "حفظ" لحفظ التغييرات.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} dir="rtl">
            <ScrollArea className="h-[60vh] p-4">
              <div className="space-y-4" dir="rtl">
                {/* Role Select */}
                <FormField
                  control={form.control}
                  name="role_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>الدور</FormLabel>
                      <Select
                        onValueChange={field.onChange}
                        value={field.value}
                        disabled={isLoadingRoles}
                      >
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="اختر دور المشرف..." />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {adminRoles?.map((role) => (
                            <SelectItem
                              key={role.id}
                              value={role.id.toString()}
                            >
                              {role.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* First/Last Name */}
                <div className="flex gap-4">
                  <FormField
                    control={form.control}
                    name="first_name"
                    render={({ field }) => (
                      <FormItem className="flex-1">
                        <FormLabel>الاسم الأول</FormLabel>
                        <FormControl>
                          <Input {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name="last_name"
                    render={({ field }) => (
                      <FormItem className="flex-1">
                        <FormLabel>الاسم الأخير</FormLabel>
                        <FormControl>
                          <Input {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </div>

                {/* Email/Phone */}
                <div className="flex gap-4">
                  <FormField
                    control={form.control}
                    name="email"
                    render={({ field }) => (
                      <FormItem className="flex-1">
                        <FormLabel>البريد الإلكتروني</FormLabel>
                        <FormControl>
                          <Input type="email" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name="phone"
                    render={({ field }) => (
                      <FormItem className="flex-1">
                        <FormLabel>رقم الهاتف</FormLabel>
                        <FormControl>
                          <Input type="tel" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </div>

                {/* ID Number */}
                <FormField
                  control={form.control}
                  name="id_number"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>رقم الهوية</FormLabel>
                      <FormControl>
                        <Input {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Country Select (Reusable) */}
                <FormField
                  control={form.control}
                  name="country_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>الدولة</FormLabel>
                      <FormControl>
                        <CountriesSelectByModule
                          value={field.value}
                          onChange={(value) => {
                            field.onChange(value);
                            // Reset city when country changes
                            form.setValue("city_id", "");
                          }}
                          switchKey="admin"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* City Select (Dependent) */}
                <FormField
                  control={form.control}
                  name="city_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>المدينة</FormLabel>
                      <FormControl>
                        <CitiesSelectByModule
                          onChange={field.onChange}
                          value={field.value}
                          switchKey="admin"
                          selectedCountryId={Number(selectedCountryId)}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Image Upload */}
                <FormField
                  control={form.control}
                  name="image"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>تغيير الصورة الشخصية</FormLabel>
                      <FormControl>
                        <Input
                          type="file"
                          accept="image/*"
                          onChange={(e) =>
                            field.onChange(e.target.files?.[0] ?? undefined)
                          }
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                {admin?.image && (
                  <div className="text-sm">
                    <span className="font-medium">الصورة الحالية:</span>
                    <img
                      src={admin.image}
                      alt={admin.name}
                      className="mt-2 h-16 w-16 rounded-full border object-cover"
                    />
                  </div>
                )}
              </div>
            </ScrollArea>
            <DialogFooter className="gap-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => onOpenChange(false)}
              >
                إلغاء
              </Button>
              <Button type="submit" disabled={isPending} className="bg-main-gold hover:bg-main-gold/90">
                {isPending ? "جاري الحفظ..." : "حفظ التغييرات"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
