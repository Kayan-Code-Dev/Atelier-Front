import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
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
import { useReturnOrderItemMutationOptions } from "@/api/v2/orders/orders.hooks";
import { TReturnOrderItemRequest } from "@/api/v2/orders/orders.types";
import { toast } from "sonner";
import { UploadFileField } from "@/components/custom/UploadFile";
import { EntitySelect } from "@/components/custom/EntitySelect";

// Schema for the form
const formSchema = z.object({
  entity_type: z
    .string()
    .min(1, { message: "نوع المكان مطلوب" })
    .refine(
      (val) => ["branch", "factory", "workshop"].includes(val),
      { message: "نوع المكان مطلوب" }
    ),
  entity_id: z.string().min(1, { message: "المكان مطلوب" }),
  note: z.string().min(1, { message: "الملاحظة مطلوبة" }),
  photos: z
    .array(z.instanceof(File))
    .min(1, { message: "يجب إضافة صورة واحدة على الأقل" })
    .max(10, { message: "الحد الأقصى 10 صور" }),
});

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  orderId: number;
  itemId: number;
  itemName?: string;
  /** Called after successful return (e.g. to close parent modal and refetch) */
  onSuccess?: () => void;
};

export function ReturnOrderItemModal({
  open,
  onOpenChange,
  orderId,
  itemId,
  itemName,
  onSuccess,
}: Props) {
  const { mutate: returnOrderItem, isPending } = useMutation(
    useReturnOrderItemMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      entity_type: "",
      entity_id: "",
      note: "",
      photos: undefined,
    },
  });

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    const requestData: TReturnOrderItemRequest = {
      entity_type: values.entity_type as "branch" | "factory" | "workshop",
      entity_id: parseInt(values.entity_id, 10),
      note: values.note,
      photos: values.photos,
    };

    returnOrderItem(
      {
        order_id: orderId,
        item_id: itemId,
        data: requestData,
      },
      {
        onSuccess: () => {
          toast.success("تم إرجاع العنصر بنجاح", {
            description: "تمت إرجاع العنصر بنجاح للنظام.",
          });
          form.reset();
          onOpenChange(false);
          onSuccess?.();
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء إرجاع العنصر", {
            description: error.message,
          });
        },
      }
    );
  };

  const handleOpenChange = (newOpen: boolean) => {
    if (!newOpen) {
      form.reset();
    }
    onOpenChange(newOpen);
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle className="text-center">إرجاع عنصر الطلب</DialogTitle>
          <DialogDescription className="text-center">
            {itemName
              ? `إرجاع العنصر: ${itemName}`
              : "املأ البيانات لإرجاع عنصر الطلب."}
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="space-y-4"
            dir="rtl"
          >
            {/* Entity Select */}
            <EntitySelect
              mode="form"
              control={form.control}
              entityTypeName="entity_type"
              entityIdName="entity_id"
              entityTypeLabel="نوع المكان المراد الإرجاع إليه"
              entityIdLabel="المكان المراد الإرجاع إليه"
            />

            {/* Note */}
            <FormField
              control={form.control}
              name="note"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الملاحظة</FormLabel>
                  <FormControl>
                    <Textarea
                      placeholder="أدخل ملاحظة حول إرجاع العنصر"
                      {...field}
                      rows={4}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Photos */}
            <FormField
              control={form.control}
              name="photos"
              render={() => (
                <FormItem>
                  <FormLabel>الصور (1-10 صور)</FormLabel>
                  <FormControl>
                    <UploadFileField
                      name="photos"
                      multiple
                      maxFiles={10}
                      accept="image/*"
                      placeholder="اختر الصور"
                      showPreview
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => handleOpenChange(false)}
                disabled={isPending}
              >
                إلغاء
              </Button>
              <Button type="submit" disabled={isPending} isLoading={isPending}>
                إرجاع العنصر
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}

