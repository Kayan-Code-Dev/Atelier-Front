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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
  useCreateBranchManagerMutationOptions,
  useGetBranchesManagersIdsQueryOptions,
} from "@/api/hr/branch-managers/branches/branch-manager.hooks";
import { useQuery, useMutation } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";

// Schema for the form
const formSchema = z.object({
  branch_manager_id: z.string({ required_error: "يجب اختيار معرف المدير" }),
  name: z.string().min(2, { message: "الاسم مطلوب" }),
  email: z.string().email({ message: "بريد إلكتروني غير صالح" }),
  phone: z.string().min(8, { message: "رقم الهاتف مطلوب" }),
  location: z.string().min(3, { message: "الموقع مطلوب" }),
});

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function CreateBranchManagerModal({ open, onOpenChange }: Props) {
  const { mutate: createManager, isPending } = useMutation(useCreateBranchManagerMutationOptions());
  const { data: managersIds, isLoading: isLoadingManagers } =
    useQuery(useGetBranchesManagersIdsQueryOptions());


  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      email: "",
      phone: "",
      location: "",
    },
  });

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    const formData = new FormData();
    formData.append("branch_manager_id", values.branch_manager_id);
    formData.append("name", values.name);
    formData.append("email", values.email);
    formData.append("phone", values.phone);
    formData.append("location", values.location);
    // Add other fields if necessary (e.g., password)

    createManager(formData, {
      onSuccess: () => {
        form.reset();
        onOpenChange(false);
      },
      onError: (error) => {
        toast.error("حدث خطأ أثناء إنشاء المدير. الرجاء المحاولة مرة أخرى.", {
          description: error.message,
        });
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="text-center">إنشاء مدير فرع جديد</DialogTitle>
          <DialogDescription className="text-center">
            املأ البيانات لإنشاء حساب مدير فرع جديد.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)}>
            <ScrollArea className="h-[65vh] p-4">
              <div className="space-y-4" dir="rtl">
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>الاسم الكامل</FormLabel>
                      <FormControl>
                        <Input placeholder="الاسم..." {...field} />
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
                        <Input
                          type="email"
                          placeholder="email@example.com"
                          {...field}
                        />
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
                        <Input type="tel" placeholder="0123456789" {...field} />
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
                        <Input placeholder="العنوان..." {...field} />
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
                        defaultValue={field.value}
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
                {isPending ? "جاري الإنشاء..." : "إنشاء"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
