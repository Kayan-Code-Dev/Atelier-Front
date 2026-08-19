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
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
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
import { useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { TCashbox } from "@/api/v2/cashboxes/cashboxes.types";
import { useUpdateCashboxMutationOptions } from "@/api/v2/cashboxes/cashboxes.hooks";
import { TUpdateCashboxRequest } from "@/api/v2/cashboxes/cashboxes.types";
import { toast } from "sonner";
import { CASHBOXES_KEY } from "@/api/v2/cashboxes/cashboxes.hooks";

// Schema
const formSchema = z.object({
  name: z.string().min(1, { message: "اسم الصندوق مطلوب" }),
  description: z.string().optional(),
  is_active: z.boolean().default(true),
});

type Props = {
  cashbox: TCashbox | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function EditCashboxModal({
  cashbox,
  open,
  onOpenChange,
}: Props) {
  const queryClient = useQueryClient();
  const { mutate: updateCashbox, isPending } = useMutation(
    useUpdateCashboxMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      description: "",
      is_active: true,
    },
  });

  // Load cashbox data into form when modal opens
  useEffect(() => {
    if (cashbox && open) {
      form.reset({
        name: cashbox.name,
        description: cashbox.description || "",
        is_active: cashbox.is_active,
      });
    }
  }, [cashbox, open, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!cashbox) return;

    const requestData: TUpdateCashboxRequest = {
      name: values.name,
      description: values.description,
      is_active: values.is_active,
    };

    updateCashbox(
      { id: cashbox.id, data: requestData },
      {
        onSuccess: () => {
          toast.success("تم تحديث الصندوق بنجاح", {
            description: `تم تحديث الصندوق "${cashbox.name}" بنجاح.`,
          });
          // Invalidate the cashboxes list query to refresh the table
          queryClient.invalidateQueries({ queryKey: [CASHBOXES_KEY] });
          form.reset();
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء تحديث الصندوق", {
            description: error.message,
          });
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>تعديل صندوق: {cashbox?.name}</DialogTitle>
          <DialogDescription>
            قم بتعديل البيانات وانقر "حفظ" لحفظ التغييرات.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            {/* Name */}
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>اسم الصندوق</FormLabel>
                  <FormControl>
                    <Input {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Description */}
            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الوصف (اختياري)</FormLabel>
                  <FormControl>
                    <Textarea {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Is Active */}
            <FormField
              control={form.control}
              name="is_active"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                  <div className="space-y-0.5">
                    <FormLabel className="text-base">الحالة</FormLabel>
                    <div className="text-sm text-muted-foreground">
                      تفعيل أو تعطيل الصندوق
                    </div>
                  </div>
                  <FormControl>
                    <Switch
                      dir="ltr"
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
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

