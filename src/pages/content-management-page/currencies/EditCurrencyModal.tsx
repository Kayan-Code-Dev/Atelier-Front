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
import { useEffect } from "react";
import { useMutation } from "@tanstack/react-query";
import {
  TCreateCurrencyRequest,
  TCurrency,
} from "@/api/v2/content-managment/currency/currency.types";
import { useUpdateCurrencyMutationOptions } from "@/api/v2/content-managment/currency/currency.hooks";
import { toast } from "sonner";

const formSchema = z.object({
  name: z.string().min(2, { message: "الاسم مطلوب" }),
  code: z.string().min(1, { message: "الكود مطلوب" }),
  symbol: z.string().min(1, { message: "الرمز مطلوب" }),
});

type Props = {
  currency: TCurrency | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function EditCurrencyModal({ currency, open, onOpenChange }: Props) {
  const { mutate: updateCurrency, isPending } = useMutation(
    useUpdateCurrencyMutationOptions()
  );

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      code: "",
      symbol: "",
    },
  });

  useEffect(() => {
    if (currency && open) {
      form.reset({
        name: currency.name,
        code: currency.code,
        symbol: currency.symbol,
      });
    }
  }, [currency, open, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!currency) return;

    const requestData: TCreateCurrencyRequest = {
      name: values.name,
      code: values.code,
      symbol: values.symbol,
    };

    updateCurrency(
      { id: currency.id, req: requestData },
      {
        onSuccess: () => {
          toast.success("تم تحديث العملة بنجاح", {
            description: `تم تحديث العملة "${currency.name}" بنجاح.`,
          });
          form.reset();
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء تحديث العملة", {
            description: (error as any).message,
          });
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="text-center">
            تعديل العملة: {currency?.name}
          </DialogTitle>
          <DialogDescription className="text-center">
            قم بتعديل البيانات وانقر "حفظ" لحفظ التغييرات.
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
                    <Input {...field} />
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
                    <Input {...field} />
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
                    <Input {...field} />
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
                {isPending ? "جاري الحفظ..." : "حفظ التغييرات"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}

