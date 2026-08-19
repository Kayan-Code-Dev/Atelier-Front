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
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { useForm, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { ScrollArea } from "@/components/ui/scroll-area";
import { useCreateEmployeeInventoryTransferMutationOptions } from "@/api/inventory/employees/inventory.hooks";
import { useMutation } from "@tanstack/react-query";
import { CustomSelectList } from "@/components/custom/CustomSelectList";

const formSchema = z.object({
    to_branch_id: z.coerce.string({ required_error: "يجب اختيار فرع الاستقبال" }),
    category_id: z.coerce.string({ required_error: "يجب اختيار قسم المنتجات" }),
    subCategories_id: z.coerce.string({
        required_error: "يجب اختيار قسم المنتجات الفرعي",
    }),
    quantity: z.coerce.number().min(1, { message: "الكمية مطلوبة" }),
    notes: z.string().optional(),
});

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function CreateEmployeeTransferModal({ open, onOpenChange }: Props) {
    const { mutate: createTransfer, isPending } =
        useMutation(useCreateEmployeeInventoryTransferMutationOptions());

    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: { quantity: 1, notes: "" },
    });

    const categoryId = useWatch({ control: form.control, name: "category_id" });

    const onSubmit = (values: z.infer<typeof formSchema>) => {
        createTransfer(values, {
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="text-center">إنشاء طلب نقل مخزون</DialogTitle>
                    <DialogDescription className="text-center">
                        اختر الأصناف والكمية لإرسال طلب النقل.
                    </DialogDescription>
                </DialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)}>
                        <ScrollArea className="h-[65vh] p-4">
                            <div className="space-y-4" dir="rtl">
                                {/* To Branch */}
                                <FormField
                                    control={form.control}
                                    name="to_branch_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>إلى فرع</FormLabel>
                                            <CustomSelectList
                                                onChange={field.onChange}
                                                value={field.value}
                                                switchKey="employees-inventories-transfer-branches-list"
                                                placeholder="اختر الفرع"
                                            />
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                {/* Category */}
                                <FormField
                                    control={form.control}
                                    name="category_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>قسم المنتجات</FormLabel>
                                            <CustomSelectList
                                                onChange={(value) => {
                                                    field.onChange(value);
                                                    form.resetField("subCategories_id");
                                                }}
                                                value={field.value}
                                                switchKey="employees-inventories-categories-list"
                                                placeholder="اختر قسم المنتجات"
                                            />
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                {/* SubCategory */}
                                <FormField
                                    control={form.control}
                                    name="subCategories_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>قسم المنتجات الفرعي (الصنف)</FormLabel>
                                            <CustomSelectList
                                                onChange={field.onChange}
                                                value={field.value}
                                                switchKey="employees-inventories-sub-categories-list"
                                                selectedId={Number(categoryId)}
                                                disabled={!categoryId}
                                                placeholder="اختر قسم المنتجات الفرعي"
                                            />
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                {/* Quantity */}
                                <FormField
                                    control={form.control}
                                    name="quantity"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>الكمية</FormLabel>
                                            <FormControl>
                                                <Input
                                                    value={field.value ?? ""}
                                                    onChange={(e) => {
                                                      const val = e.target.value.replace(/[^0-9]/g, "");
                                                      field.onChange(val ? Number(val) : 0);
                                                    }}
                                                />
                                            </FormControl>
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
                                            <FormLabel>ملاحظات (اختياري)</FormLabel>
                                            <FormControl>
                                                <Textarea placeholder="ملاحظات الطلب..." {...field} />
                                            </FormControl>
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
                            <Button className="bg-main-gold hover:bg-main-gold/80" type="submit" disabled={isPending}>
                                {isPending ? "جاري الإرسال..." : "إرسال الطلب"}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
