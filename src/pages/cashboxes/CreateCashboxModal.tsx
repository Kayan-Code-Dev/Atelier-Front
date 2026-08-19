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
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { CASHBOXES_KEY, useCreateCashboxMutationOptions } from "@/api/v2/cashboxes/cashboxes.hooks";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { toast } from "sonner";

const formSchema = z.object({
  name: z.string().min(1, { message: "اسم الخزنة مطلوب" }),
  branch_id: z.string().optional(),
  initial_balance: z.string().optional(),
});

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function CreateCashboxModal({ open, onOpenChange }: Props) {
  const queryClient = useQueryClient();
  const { mutate, isPending } = useMutation(useCreateCashboxMutationOptions());
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: { name: "", branch_id: "", initial_balance: "0" },
  });

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    mutate(
      {
        name: values.name,
        branch_id: values.branch_id ? Number(values.branch_id) : undefined,
        initial_balance: values.initial_balance ? Number(values.initial_balance) : 0,
        is_active: true,
      },
      {
        onSuccess: () => {
          toast.success("تم إنشاء الخزنة");
          queryClient.invalidateQueries({ queryKey: [CASHBOXES_KEY] });
          form.reset();
          onOpenChange(false);
        },
        onError: (error: Error) => {
          toast.error("تعذر إنشاء الخزنة", { description: error.message });
        },
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>خزنة جديدة</DialogTitle>
          <DialogDescription>أنشئ خزنة للفرع لتسجيل المقبوضات والمدفوعات.</DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>اسم الخزنة</FormLabel>
                  <FormControl>
                    <Input {...field} placeholder="خزنة الفرع الرئيسي" />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="branch_id"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الفرع</FormLabel>
                  <BranchesSelect value={field.value ?? ""} onChange={(v) => field.onChange(v || "")} />
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="initial_balance"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الرصيد الافتتاحي</FormLabel>
                  <FormControl>
                    <Input type="number" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <DialogFooter>
              <Button type="submit" disabled={isPending}>
                {isPending ? "جاري الإنشاء..." : "إنشاء الخزنة"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
