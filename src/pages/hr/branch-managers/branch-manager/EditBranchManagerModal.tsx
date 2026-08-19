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
import { useEffect } from "react";
import { ScrollArea } from "@/components/ui/scroll-area";
import { TBranchManager } from "@/api/hr/branch-managers/branches/branch-manager.types";
import {
  useGetBranchesManagersIdsQueryOptions,
  useUpdateBranchManagerMutationOptions,
} from "@/api/hr/branch-managers/branches/branch-manager.hooks";
import { useQuery, useMutation } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";

// Schema (same as create)
const formSchema = z.object({
  branch_manager_id: z.string({ required_error: "يجب اختيار معرف المدير" }),
  name: z.string().min(2, { message: "الاسم مطلوب" }),
  email: z.string().email({ message: "بريد إلكتروني غير صالح" }),
  phone: z.string().min(8, { message: "رقم الهاتف مطلوب" }),
  location: z.string().min(3, { message: "الموقع مطلوب" }),
});

type Props = {
  manager: TBranchManager | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function EditBranchManagerModal({ manager, open, onOpenChange }: Props) {
  // *** Assuming hook takes string UUID, not number ***
  const { mutate: updateManager, isPending } = useMutation(useUpdateBranchManagerMutationOptions());
  const { data: managersIds, isPending: isLoadingManagers } =
    useQuery(useGetBranchesManagersIdsQueryOptions());

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
  });

  // Load manager data into form when modal opens
  useEffect(() => {
    if (manager && open) {
      form.reset({
        branch_manager_id: manager.branch_manager_id,
        name: manager.name,
        email: manager.email,
        phone: manager.phone,
        location: manager.location,
      });
    }
  }, [manager, form, open]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!manager) return;

    const formData = new FormData();
    formData.append("branch_manager_id", values.branch_manager_id);
    formData.append("name", values.name);
    formData.append("email", values.email);
    formData.append("phone", values.phone);
    formData.append("location", values.location);
    formData.append("_method", "PUT"); // For Laravel

    updateManager(
      // *** Using manager.uuid (string) ***
      { id: manager.uuid, data: formData },
      {
        onSuccess: () => {
          form.reset();
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error(
            "حدث خطأ أثناء تحديث بيانات المدير. الرجاء المحاولة مرة أخرى.",
            {
              description: error.message,
            }
          );
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>تعديل مدير الفرع: {manager?.name}</DialogTitle>
          <DialogDescription>
            قم بتعديل البيانات وانقر "حفظ" لحفظ التغييرات.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)}>
            <ScrollArea className="h-[65vh] p-4" dir="rtl">
              <div className="space-y-4">
                {/* Form fields (same as create) */}
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>الاسم الكامل</FormLabel>
                      <FormControl>
                        <Input {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="email"
                  render={({ field }) => (
                    <FormItem>
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
                    <FormItem>
                      <FormLabel>رقم الهاتف</FormLabel>
                      <FormControl>
                        <Input type="tel" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="location"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>الموقع (العنوان)</FormLabel>
                      <FormControl>
                        <Input {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="branch_manager_id"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>معرف المدير</FormLabel>
                      <Select
                        onValueChange={field.onChange}
                        value={field.value} // Use value for controlled component
                        disabled={isLoadingManagers}
                      >
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="اختر معرف المدير..." />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {isLoadingManagers ? (
                            <div className="flex items-center justify-center p-2">
                              <Loader2 className="h-4 w-4 animate-spin" />
                            </div>
                          ) : (
                            managersIds?.map(
                              (manager: { id: number; name: string }) => (
                                <SelectItem
                                  key={manager.id}
                                  value={manager.id.toString()}
                                >
                                  {manager.name}
                                </SelectItem>
                              )
                            )
                          )}
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />
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
