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
import { useMutation } from "@tanstack/react-query";
import { useUpdateWorkshopClothStatusMutationOptions } from "@/api/v2/workshop/workshops.hooks";
import { TWorkshopClothStatus, TworkshopCloth } from "@/api/v2/workshop/workshop.types";
import { toast } from "sonner";
import { useEffect } from "react";

const workshopClothStatuses: TWorkshopClothStatus[] = [
  "processing",
  "received",
  "ready_for_delivery",
];

const workshopClothStatusLabels: Record<TWorkshopClothStatus, string> = {
  processing: "قيد المعالجة",
  received: "تم الاستلام",
  ready_for_delivery: "جاهز للتسليم",
};

// Schema for the form
const formSchema = z.object({
  status: z.enum(["processing", "received", "ready_for_delivery"], {
    required_error: "الحالة مطلوبة",
  }),
  notes: z.string().optional(),
});

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  cloth: TworkshopCloth | null;
  workshopId: number;
};

export function UpdateWorkshopClothStatusModal({
  open,
  onOpenChange,
  cloth,
  workshopId,
}: Props) {
  const { mutate: updateClothStatus, isPending } = useMutation(
    useUpdateWorkshopClothStatusMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      status: cloth?.workshop_status || "processing",
      notes: cloth?.workshop_notes || "",
    },
  });

  // Update form when cloth changes
  useEffect(() => {
    if (cloth) {
      form.reset({
        status: cloth.workshop_status,
        notes: cloth.workshop_notes || "",
      });
    }
  }, [cloth, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!cloth) return;

    updateClothStatus(
      {
        workshop_id: workshopId,
        cloth_id: cloth.id,
        status: values.status,
        notes: values.notes || "",
      },
      {
        onSuccess: () => {
          toast.success("تم تحديث حالة المنتج بنجاح", {
            description: "تم تحديث حالة المنتج في النظام.",
          });
          form.reset();
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء تحديث حالة المنتج", {
            description: error.message,
          });
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle className="text-center">تحديث حالة المنتج</DialogTitle>
          <DialogDescription className="text-center">
            قم بتحديث حالة المنتج وإضافة ملاحظات.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="space-y-4"
            dir="rtl"
          >
            {/* Status */}
            <FormField
              control={form.control}
              name="status"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الحالة</FormLabel>
                  <Select
                    onValueChange={field.onChange}
                    defaultValue={field.value}
                  >
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue placeholder="اختر الحالة" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      {workshopClothStatuses.map((status) => (
                        <SelectItem key={status} value={status}>
                          {workshopClothStatusLabels[status]}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Notes */}
            <FormField
              control={form.control}
              name="notes"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الملاحظات</FormLabel>
                  <FormControl>
                    <Textarea
                      placeholder="أضف ملاحظات..."
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
                onClick={() => onOpenChange(false)}
                disabled={isPending}
              >
                إلغاء
              </Button>
              <Button type="submit" disabled={isPending}>
                {isPending ? "جاري الحفظ..." : "حفظ"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}

