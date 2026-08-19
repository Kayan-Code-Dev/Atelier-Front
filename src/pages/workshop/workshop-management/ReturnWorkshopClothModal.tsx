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
import { useReturnWorkshopClothMutationOptions } from "@/api/v2/workshop/workshops.hooks";
import { TworkshopCloth } from "@/api/v2/workshop/workshop.types";
import { toast } from "sonner";
import { useEffect } from "react";

// Schema for the form
const formSchema = z.object({
  notes: z.string().min(1, { message: "الملاحظات مطلوبة" }),
});

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  cloth: TworkshopCloth | null;
  workshopId: number;
};

export function ReturnWorkshopClothModal({
  open,
  onOpenChange,
  cloth,
  workshopId,
}: Props) {
  const { mutate: returnCloth, isPending } = useMutation(
    useReturnWorkshopClothMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      notes: "",
    },
  });

  // Reset form when modal opens/closes
  useEffect(() => {
    if (!open) {
      form.reset({ notes: "" });
    }
  }, [open, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!cloth) return;

    returnCloth(
      {
        workshop_id: workshopId,
        cloth_id: cloth.id,
        notes: values.notes,
      },
      {
        onSuccess: () => {
          toast.success("تم إرجاع المنتج بنجاح", {
            description: "تمت إرجاع المنتج بنجاح للنظام.",
          });
          form.reset();
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء إرجاع المنتج", {
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
          <DialogTitle className="text-center">إرجاع المنتجات</DialogTitle>
          <DialogDescription className="text-center">
            {cloth
              ? `إرجاع المنتج: ${cloth.name ?? cloth.code} (${cloth.code})`
              : "املأ البيانات لإرجاع المنتج."}
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="space-y-4"
            dir="rtl"
          >
            {/* Notes */}
            <FormField
              control={form.control}
              name="notes"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الملاحظات</FormLabel>
                  <FormControl>
                    <Textarea
                      placeholder="أضف ملاحظات حول إرجاع المنتج..."
                      className="min-h-[100px]"
                      {...field}
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
              <Button type="submit" disabled={isPending}>
                {isPending ? "جاري الإرجاع..." : "إرجاع"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}

