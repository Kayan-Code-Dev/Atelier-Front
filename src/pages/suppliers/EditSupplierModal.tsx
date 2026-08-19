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
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useMutation } from "@tanstack/react-query";
import { TSupplierResponse } from "@/api/v2/suppliers/suppliers.types";
import { useUpdateSupplierMutationOptions } from "@/api/v2/suppliers/suppliers.hooks";
import { TUpdateSupplierRequest } from "@/api/v2/suppliers/suppliers.types";
import { toast } from "sonner";
import { useEffect } from "react";

const formSchema = z.object({
  name: z.string().min(1, { message: "اسم المورد مطلوب" }),
  code: z.string().min(1, { message: "كود المورد مطلوب" }),
  phone: z.string().optional(),
  address: z.string().optional(),
});

type Props = {
  supplier: TSupplierResponse | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function EditSupplierModal({ supplier, open, onOpenChange }: Props) {
  const { mutate: updateSupplier, isPending } = useMutation(
    useUpdateSupplierMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: { name: "", code: "", phone: "", address: "" },
  });

  useEffect(() => {
    if (supplier && open) {
      form.reset({
        name: supplier.name,
        code: supplier.code,
        phone: supplier.phone ?? "",
        address: supplier.address ?? "",
      });
    }
  }, [supplier, open, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!supplier) return;
    const requestData: TUpdateSupplierRequest = {
      name: values.name,
      code: values.code,
      ...(values.phone !== undefined && { phone: values.phone?.trim() || undefined }),
      ...(values.address !== undefined && { address: values.address?.trim() || undefined }),
    };
    updateSupplier(
      { id: supplier.id, data: requestData },
      {
        onSuccess: () => {
          toast.success("تم تحديث المورد بنجاح");
          onOpenChange(false);
        },
        onError: (error: { message?: string }) => {
          toast.error("حدث خطأ أثناء تحديث المورد", { description: error?.message });
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md" dir="rtl">
        <DialogHeader>
          <DialogTitle className="text-center">تعديل مورد</DialogTitle>
          <DialogDescription className="text-center">
            تعديل بيانات المورد (الاسم، الكود، الرقم، الموقع).
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>اسم المورد</FormLabel>
                  <FormControl>
                    <Input placeholder="اسم المورد" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="code"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>كود المورد</FormLabel>
                  <FormControl>
                    <Input placeholder="كود المورد" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="phone"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>رقم المورد</FormLabel>
                  <FormControl>
                    <Input placeholder="رقم الهاتف" {...field} value={field.value ?? ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="address"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الموقع</FormLabel>
                  <FormControl>
                    <Input placeholder="عنوان / موقع المورد" {...field} value={field.value ?? ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <DialogFooter className="gap-2">
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                إلغاء
              </Button>
              <Button type="submit" disabled={isPending}>
                {isPending ? "جاري التحديث..." : "تحديث"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
