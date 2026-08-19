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
  useCreateBranchInventoryTransferMutationOptions,
  useGetBranchesInventoryCategoriesQueryOptions,
  useGetBranchesInventorySubCategoriesByCategoryQueryOptions,
  useGetBranchesInventoryTransferBranchesQueryOptions,
} from "@/api/inventory/branches/inventory.hooks";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";

const formSchema = z.object({
  to_branch_id: z.coerce.number({ required_error: "يجب اختيار فرع الاستقبال" }),
  category_id: z.coerce.number({ required_error: "يجب اختيار قسم المنتجات" }),
  subCategories_id: z.coerce.number({
    required_error: "يجب اختيار قسم المنتجات الفرعي",
  }),
  quantity: z.coerce.number().min(1, { message: "الكمية مطلوبة" }),
  notes: z.string().optional(),
});

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function CreateBranchTransferModal({ open, onOpenChange }: Props) {
  const { mutate: createTransfer, isPending } =
    useMutation(useCreateBranchInventoryTransferMutationOptions());

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: { quantity: 1, notes: "" },
  });

  const categoryId = useWatch({ control: form.control, name: "category_id" });

  const { data: branches, isLoading: isLoadingBranches } =
    useQuery(useGetBranchesInventoryTransferBranchesQueryOptions());

  const { data: categories, isLoading: isLoadingCategories } =
    useQuery(useGetBranchesInventoryCategoriesQueryOptions());
  const { data: subCategories, isLoading: isLoadingSubCategories } =
    useQuery(useGetBranchesInventorySubCategoriesByCategoryQueryOptions(categoryId));

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    createTransfer(values, {
      onSuccess: () => {
        form.reset();
        onOpenChange(false);
      },
      onError: (error) => {
        toast.error("حدث خطأ أثناء إنشاء الطلب", {
          description: error.message,
        });
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
                      <Select
                        onValueChange={(value) => field.onChange(Number(value))}
                        defaultValue={String(field.value)}
                        disabled={isLoadingBranches}
                      >
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="اختر فرع الاستقبال..." />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {branches?.map((branch) => (
                            <SelectItem
                              key={branch.id}
                              value={String(branch.id)}
                            >
                              {branch.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
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
                        defaultValue={String(field.value)}
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
              <Button type="submit" disabled={isPending}>
                {isPending ? "جاري الإرسال..." : "إرسال الطلب"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
