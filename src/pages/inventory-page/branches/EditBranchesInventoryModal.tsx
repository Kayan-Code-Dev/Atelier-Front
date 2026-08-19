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
import { Loader2 } from "lucide-react";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
  TBranchInventoryItem,
  TInventoryItemType,
} from "@/api/inventory/inventory.types"; // Adjust path
import { useEffect } from "react";
import {
  useGetBranchesInventoryCategoriesQueryOptions,
  useGetBranchesInventorySubCategoriesByCategoryQueryOptions,
  useUpdateBranchesInventoryMutationOptions,
} from "@/api/inventory/branches/inventory.hooks";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";

// Example types. Update this as needed.
const ITEM_TYPES: TInventoryItemType[] = ["raw", "product"];

// Schema for the form
const formSchema = z.object({
  code: z.string().min(1, { message: "الكود مطلوب" }), // Required for update
  name: z.string().min(2, { message: "الاسم مطلوب" }),
  category_id: z.coerce.number({ required_error: "قسم المنتجات مطلوب" }),
  subCategories_id: z.coerce.number({ required_error: "قسم المنتجات الفرعي مطلوب" }),
  price: z.coerce.number().min(1, { message: "السعر مطلوب" }),
  quantity: z.coerce.number().min(1, { message: "الكمية مطلوبة" }),
  type: z.string({ required_error: "النوع مطلوب" }),
  notes: z.string().optional(),
});

type Props = {
  item: TBranchInventoryItem | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function EditBranchesInventoryModal({
  item,
  open,
  onOpenChange,
}: Props) {
  const { mutate: updateItem, isPending } = useMutation(useUpdateBranchesInventoryMutationOptions());

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      code: item?.code || "",
      name: item?.name || "",
      price: parseFloat(item?.price ?? "0"),
      quantity: Number(item?.quantity) || 1,
      type: item?.type || "",
      category_id: item?.category_id || undefined,
      subCategories_id: item?.sub_category_id || undefined,
    },
  });

  // --- Dependent Dropdown Logic ---
  const categoryId = useWatch({ control: form.control, name: "category_id" });
  const { data: categories, isLoading: isLoadingCategories } =
    useQuery(useGetBranchesInventoryCategoriesQueryOptions());
  const { data: subCategories, isLoading: isLoadingSubCategories } =
    useQuery(useGetBranchesInventorySubCategoriesByCategoryQueryOptions(categoryId));
  // ---

  // Pre-fill form when `item` changes
  useEffect(() => {
    if (item && open) {
      // Assuming 'item' has all these properties
      const itemData = item;
      form.reset({
        code: itemData.code || "",
        name: itemData.name || "",
        price: parseFloat(itemData.price ?? "0"),
        quantity: Number(itemData.quantity) || 1,
        type: itemData.type || "",
        category_id: itemData.category_id || undefined,
        subCategories_id: itemData.sub_category_id || undefined,
      });
    }
  }, [item, open, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    if (!item) return;

    updateItem(
      { data: values, id: item.id },
      {
        onSuccess: () => {
          toast.success("تم تحديث الصنف بنجاح");
          form.reset();
          onOpenChange(false);
        },
        onError: (error) => {
          toast.error("حدث خطأ أثناء تحديث الصنف", {
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
          <DialogTitle className="text-center">
            تعديل الصنف: {item?.name}
          </DialogTitle>
          <DialogDescription className="text-center">
            قم بتعديل البيانات وانقر "حفظ" لحفظ التغييرات.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)}>
            <ScrollArea className="h-[65vh] p-4">
              <div className="space-y-4" dir="rtl">
                <FormField
                  control={form.control}
                  name="code"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>الكود</FormLabel>
                      <FormControl>
                        <Input disabled placeholder="كود الصنف..." {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

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
                      <Select
                        onValueChange={(value) => {
                          field.onChange(Number(value));
                          form.resetField("subCategories_id");
                        }}
                        value={String(field.value)}
                        disabled={isLoadingCategories}
                      >
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="اختر قسم المنتجات..." />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {categories?.map((cat) => (
                            <SelectItem key={cat.id} value={String(cat.id)}>
                              {cat.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
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
                      <Select
                        onValueChange={(value) => field.onChange(Number(value))}
                        value={String(field.value)}
                        disabled={!categoryId || isLoadingSubCategories}
                      >
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="اختر قسم المنتجات الفرعي..." />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {isLoadingSubCategories && (
                            <div className="flex items-center justify-center p-2">
                              <Loader2 className="h-4 w-4 animate-spin" />
                            </div>
                          )}
                          {subCategories?.map((subCat) => (
                            <SelectItem
                              key={subCat.id}
                              value={String(subCat.id)}
                            >
                              {subCat.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Price & Quantity */}
                <div className="flex gap-4">
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
                        value={field.value}
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
