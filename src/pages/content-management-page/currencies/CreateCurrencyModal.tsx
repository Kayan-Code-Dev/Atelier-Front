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
import { useCreateCurrencyMutationOptions } from "@/api/v2/content-managment/currency/currency.hooks";
import { TCreateCurrencyRequest } from "@/api/v2/content-managment/currency/currency.types";
import { toast } from "sonner";

const formSchema = z.object({
  name: z.string().min(2, { message: "الاسم مطلوب" }),
  code: z.string().min(1, { message: "الكود مطلوب" }),
  symbol: z.string().min(1, { message: "الرمز مطلوب" }),
});

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function CreateCurrencyModal({ open, onOpenChange }: Props) {
  const { mutate: createCurrency, isPending } = useMutation(
    useCreateCurrencyMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      code: "",
      symbol: "",
    },
  });

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    const requestData: TCreateCurrencyRequest = {
      name: values.name,
      code: values.code,
      symbol: values.symbol,
    };

    createCurrency(requestData, {
      onSuccess: () => {
        toast.success("تم إنشاء العملة بنجاح", {
          description: "تمت إضافة العملة بنجاح للنظام.",
        });
        form.reset();
        onOpenChange(false);
      },
      onError: (error) => {
        toast.error("حدث خطأ أثناء إنشاء العملة", {
          description: (error as any).message,
        });
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="text-center">إضافة عملة جديدة</DialogTitle>
          <DialogDescription className="text-center">
            املأ البيانات لإضافة عملة جديدة للنظام.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="space-y-4"
            dir="rtl"
          >
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>اسم العملة</FormLabel>
                  <FormControl>
                    <Input placeholder="ريال سعودي" {...field} />
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
                  <FormLabel>الكود (ISO)</FormLabel>
                  <FormControl>
                    <Input placeholder="SAR" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="symbol"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>الرمز</FormLabel>
                  <FormControl>
                    <Input placeholder="﷼" {...field} />
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
                {isPending ? "جاري الإنشاء..." : "إنشاء"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}

