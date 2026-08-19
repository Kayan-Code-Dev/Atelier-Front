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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
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
import { TInventoryItemType } from "@/api/inventory/inventory.types";
import { useCreateEmployeesInventoryMutationOptions } from "@/api/inventory/employees/inventory.hooks";
import { useMutation } from "@tanstack/react-query";
import { CustomSelectList } from "@/components/custom/CustomSelectList";
import { TCreateEmployeesInventoryRequest } from "@/api/inventory/employees/inventory.service";

// Example types. Update this as needed.
const ITEM_TYPES: TInventoryItemType[] = ["product", "raw"];

// Schema for the form
const formSchema = z.object({
    name: z.string().min(2, { message: "الاسم مطلوب" }),
    category_id: z.coerce.string({ required_error: "قسم المنتجات مطلوب" }),
    subCategories_id: z.coerce.string({ required_error: "قسم المنتجات الفرعي مطلوب" }),
    price: z.coerce.number().min(1, { message: "السعر مطلوب" }),
    quantity: z.coerce.number().min(1, { message: "الكمية مطلوبة" }),
    type: z.string({ required_error: "النوع مطلوب" }),
    notes: z.string().optional(),
});

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function CreateEmployeesInventoryModal({ open, onOpenChange }: Props) {
    const { mutate: createItem, isPending } = useMutation(useCreateEmployeesInventoryMutationOptions());

    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            name: "",
            price: 0,
            quantity: 1,
            notes: "",
        },
    });

    // --- Dependent Dropdown Logic ---
    const categoryId = useWatch({ control: form.control, name: "category_id" });
    // ---

    const onSubmit = (values: z.infer<typeof formSchema>) => {
        createItem(values as TCreateEmployeesInventoryRequest, {
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
                    <DialogTitle className="text-center">
                        إضافة صنف جديد للمخزن
                    </DialogTitle>
                    <DialogDescription className="text-center">
                        املأ البيانات لإضافة صنف جديد.
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
                                            <FormLabel>اسم الصنف</FormLabel>
                                            <FormControl>
                                                <Input placeholder="اسم الصنف..." {...field} />
                                            </FormControl>
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

                                <div className="flex gap-4">
                                    {/* Price */}
                                    <FormField
                                        control={form.control}
                                        name="price"
                                        render={({ field }) => (
                                            <FormItem className="flex-1">
                                                <FormLabel>السعر</FormLabel>
                                                <FormControl>
                                                    <Input value={field.value ?? ""} onChange={(e) => {
                                                      const val = e.target.value.replace(/[^0-9.]/g, "");
                                                      field.onChange(val === "" ? 0 : Number(val) || 0);
                                                    }} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                    {/* Quantity */}
                                    <FormField
                                        control={form.control}
                                        name="quantity"
                                        render={({ field }) => (
                                            <FormItem className="flex-1">
                                                <FormLabel>الكمية</FormLabel>
                                                <FormControl>
                                                    <Input value={field.value ?? ""} onChange={(e) => {
                                                      const val = e.target.value.replace(/[^0-9]/g, "");
                                                      field.onChange(val ? Number(val) : 0);
                                                    }} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </div>

                                {/* Type */}
                                <FormField
                                    control={form.control}
                                    name="type"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>نوع الصنف</FormLabel>
                                            <Select
                                                onValueChange={field.onChange}
                                                defaultValue={field.value}
                                            >
                                                <FormControl>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="اختر نوع الصنف..." />
                                                    </SelectTrigger>
                                                </FormControl>
                                                <SelectContent>
                                                    {ITEM_TYPES.map((type) => (
                                                        <SelectItem key={type} value={type}>
                                                            {type}
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
                                            <FormLabel>ملاحظات (اختياري)</FormLabel>
                                            <FormControl>
                                                <Textarea placeholder="ملاحظات..." {...field} />
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
                                {isPending ? "جاري الإنشاء..." : "إنشاء الصنف"}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
