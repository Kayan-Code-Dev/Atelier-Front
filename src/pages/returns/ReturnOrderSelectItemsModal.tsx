import { useEffect, useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
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
import { useMutation, useQuery } from "@tanstack/react-query";
import {
  useReturnOrderItemMutationOptions,
  useGetOrderDetailsQueryOptions,
} from "@/api/v2/orders/orders.hooks";
import {
  TReturnOrderItemRequest,
  TOrder,
  TOrderItem,
} from "@/api/v2/orders/orders.types";
import { toast } from "sonner";
import { UploadFileField } from "@/components/custom/UploadFile";
import { EntitySelect } from "@/components/custom/EntitySelect";
import { getItemListDisplay } from "@/api/v2/orders/order.utils";

const formSchema = z.object({
  entity_type: z
    .string()
    .min(1, { message: "نوع المكان مطلوب" })
    .refine((val) => ["branch", "factory", "workshop"].includes(val), {
      message: "نوع المكان مطلوب",
    }),
  entity_id: z.string().min(1, { message: "المكان مطلوب" }),
  note: z.string().min(1, { message: "الملاحظة مطلوبة" }),
  photos: z
    .array(z.instanceof(File))
    .min(1, { message: "يجب إضافة صورة واحدة على الأقل" })
    .max(10, { message: "الحد الأقصى 10 صور" }),
});

type FormValues = z.infer<typeof formSchema>;

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  order: TOrder | null;
  onSuccess?: () => void;
};

const itemTypeColors: Record<string, string> = {
  rent: "bg-blue-50 text-blue-600 border-blue-200",
  buy: "bg-green-50 text-green-600 border-green-200",
  tailoring: "bg-purple-50 text-purple-600 border-purple-200",
  mixed: "bg-amber-50 text-amber-600 border-amber-200",
};

const itemTypeLabels: Record<string, string> = {
  rent: "إيجار",
  buy: "بيع",
  tailoring: "تفصيل",
  mixed: "مختلط",
};

export function ReturnOrderSelectItemsModal({
  open,
  onOpenChange,
  order,
  onSuccess,
}: Props) {
  const [selectedItemIds, setSelectedItemIds] = useState<Set<number>>(
    new Set(),
  );

  const orderId = order?.id ?? 0;
  const { data: orderDetails, isPending: isOrderLoading } = useQuery({
    ...useGetOrderDetailsQueryOptions(orderId),
    enabled: open && orderId > 0,
  });

  const orderItems: TOrderItem[] = orderDetails?.items ?? order?.items ?? [];
  const returnableItems = orderItems.filter((item) => item.returnable === 1);

  const { mutateAsync: returnOrderItem, isPending: isReturning } = useMutation(
    useReturnOrderItemMutationOptions(),
  );

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      entity_type: "",
      entity_id: "",
      note: "",
      photos: undefined,
    },
  });

  useEffect(() => {
    if (!open) {
      setSelectedItemIds(new Set());
      form.reset();
    }
  }, [open, form]);

  const toggleItem = (itemId: number) => {
    setSelectedItemIds((prev) => {
      const next = new Set(prev);
      if (next.has(itemId)) next.delete(itemId);
      else next.add(itemId);
      return next;
    });
  };

  const selectAll = () => {
    if (selectedItemIds.size === returnableItems.length) {
      setSelectedItemIds(new Set());
    } else {
      setSelectedItemIds(new Set(returnableItems.map((i) => i.id)));
    }
  };

  const onSubmit = async (values: FormValues) => {
    if (!order || selectedItemIds.size === 0) {
      toast.error("اختر قطعة واحدة على الأقل للإرجاع");
      return;
    }
    const data: TReturnOrderItemRequest = {
      entity_type: values.entity_type as "branch" | "factory" | "workshop",
      entity_id: parseInt(values.entity_id, 10),
      note: values.note,
      photos: values.photos,
    };
    const ids = Array.from(selectedItemIds);
    let successCount = 0;
    for (const itemId of ids) {
      try {
        await returnOrderItem({ order_id: order.id, item_id: itemId, data });
        successCount++;
      } catch (err: any) {
        toast.error(`فشل إرجاع أحد القطع: ${err?.message ?? "خطأ"}`);
        return;
      }
    }
    toast.success(
      successCount === ids.length
        ? `تم إرجاع ${successCount} قطعة بنجاح`
        : `تم إرجاع ${successCount} من ${ids.length} قطعة`,
    );
    onOpenChange(false);
    onSuccess?.();
  };

  const handleOpenChange = (newOpen: boolean) => {
    if (!newOpen) {
      setSelectedItemIds(new Set());
      form.reset();
    }
    onOpenChange(newOpen);
  };

  const selectedCount = selectedItemIds.size;
  const canSubmit =
    selectedCount > 0 &&
    returnableItems.length > 0 &&
    !isReturning &&
    !isOrderLoading;

  if (!order) return null;

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="sm:max-w-2xl max-h-[90vh] overflow-y-auto p-0">
        <DialogHeader className="sr-only">
          <DialogTitle>اختيار القطع للإرجاع</DialogTitle>
          <DialogDescription>
            حدد القطع التي تريد إرجاعها ثم أدخل بيانات الإرجاع
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-0" dir="rtl">
          {/* Top Header */}
          <div className="bg-blue-900 text-white p-5 rounded-t-lg">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-white/10">
                <i className="ri-arrow-go-back-line text-xl" />
              </div>
              <div>
                <h2 className="text-lg font-bold">
                  إرجاع القطع — طلب #{order.id}
                </h2>
                <p className="text-blue-200 text-xs mt-0.5">
                  حدد القطع المراد إرجاعها ثم أكمل بيانات الإرجاع
                </p>
              </div>
            </div>
          </div>

          <div className="p-5 space-y-4">
            {isOrderLoading ? (
              <div className="rounded-xl border border-blue-100 bg-white py-8 text-center text-gray-500 text-sm">
                <i className="ri-loader-4-line inline-block animate-spin text-xl ml-2" />
                جاري تحميل تفاصيل الطلب...
              </div>
            ) : returnableItems.length === 0 ? (
              <div className="bg-white rounded-xl border border-blue-100 p-10 text-center">
                <div className="w-14 h-14 flex items-center justify-center mx-auto rounded-full bg-amber-50 text-amber-300 mb-4">
                  <i className="ri-information-line text-2xl" />
                </div>
                <p className="text-gray-500 text-sm mb-1">
                  لا توجد قطع قابلة للإرجاع في هذا الطلب
                </p>
                <p className="text-xs text-gray-400">
                  جميع القطع إما تم إرجاعها أو غير قابلة للإرجاع
                </p>
                <button
                  type="button"
                  onClick={() => handleOpenChange(false)}
                  className="mt-4 px-6 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                >
                  إغلاق
                </button>
              </div>
            ) : (
              <>
                {/* Items Selection */}
                <div className="bg-white rounded-xl border border-blue-100 overflow-hidden">
                  <div className="flex items-center justify-between p-4 pb-3">
                    <div className="flex items-center gap-2">
                      <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                        <i className="ri-t-shirt-2-line text-sm" />
                      </div>
                      <p className="text-sm font-semibold text-gray-700">
                        القطع القابلة للإرجاع
                      </p>
                      <span className="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">
                        {returnableItems.length} قطعة
                      </span>
                    </div>
                    <button
                      type="button"
                      onClick={selectAll}
                      className="text-xs text-blue-600 hover:text-blue-700 hover:underline cursor-pointer flex items-center gap-1"
                    >
                      <i
                        className={`${selectedItemIds.size === returnableItems.length ? "ri-checkbox-line" : "ri-checkbox-blank-line"}`}
                      />
                      {selectedItemIds.size === returnableItems.length
                        ? "إلغاء الكل"
                        : "تحديد الكل"}
                    </button>
                  </div>

                  <div className="max-h-[200px] overflow-y-auto divide-y divide-gray-100">
                    {returnableItems.map((item) => {
                      const isSelected = selectedItemIds.has(item.id);
                      return (
                        <button
                          key={item.id}
                          type="button"
                          onClick={() => toggleItem(item.id)}
                          className={`w-full flex items-center gap-3 px-4 py-3 transition-colors cursor-pointer text-right ${
                            isSelected
                              ? "bg-blue-50/50"
                              : "hover:bg-gray-50/50"
                          }`}
                        >
                          <div
                            className={`w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 transition-colors ${
                              isSelected
                                ? "bg-blue-600 border-blue-600 text-white"
                                : "border-gray-300 bg-white"
                            }`}
                          >
                            {isSelected && (
                              <i className="ri-check-line text-xs" />
                            )}
                          </div>

                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-800 truncate">
                              {getItemListDisplay(
                                item as Record<string, unknown>,
                              ) || item.code}
                            </p>
                            <div className="flex items-center gap-2 mt-0.5">
                              <span className="text-xs text-gray-400 font-mono">
                                كود: {item.code}
                              </span>
                              {item.quantity > 1 && (
                                <span className="text-xs text-gray-400">
                                  × {item.quantity}
                                </span>
                              )}
                            </div>
                          </div>

                          <div className="flex items-center gap-2 flex-shrink-0">
                            <span
                              className={`text-xs px-2 py-0.5 rounded-full border font-medium ${itemTypeColors[item.type] ?? "bg-gray-50 text-gray-600 border-gray-200"}`}
                            >
                              {itemTypeLabels[item.type] ?? item.type}
                            </span>
                            <span className="text-sm font-bold text-gray-700">
                              {item.price} ج.م
                            </span>
                          </div>
                        </button>
                      );
                    })}
                  </div>

                  {selectedCount > 0 && (
                    <div className="px-4 py-2.5 bg-blue-50 border-t border-blue-100 flex items-center justify-between">
                      <span className="text-xs text-blue-600">
                        <i className="ri-check-double-line ml-1" />
                        تم تحديد{" "}
                        <strong>
                          {selectedCount} من {returnableItems.length}
                        </strong>{" "}
                        قطعة
                      </span>
                      <span className="text-xs text-blue-500 font-bold">
                        {new Intl.NumberFormat("ar-EG").format(
                          returnableItems
                            .filter((i) => selectedItemIds.has(i.id))
                            .reduce(
                              (s, i) => s + Number(i.price) * i.quantity,
                              0,
                            ),
                        )}{" "}
                        ج.م
                      </span>
                    </div>
                  )}
                </div>

                {/* Return Form */}
                <Form {...form}>
                  <form
                    onSubmit={form.handleSubmit(onSubmit)}
                    className="space-y-4"
                  >
                    <div className="bg-white rounded-xl border border-blue-100 p-4 space-y-4">
                      <div className="flex items-center gap-2 mb-1">
                        <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-green-50 text-green-600">
                          <i className="ri-map-pin-line text-sm" />
                        </div>
                        <p className="text-sm font-semibold text-gray-700">
                          بيانات الإرجاع
                        </p>
                      </div>

                      <EntitySelect
                        mode="form"
                        control={form.control}
                        entityTypeName="entity_type"
                        entityIdName="entity_id"
                        entityTypeLabel="نوع المكان المراد الإرجاع إليه"
                        entityIdLabel="المكان المراد الإرجاع إليه"
                      />

                      <FormField
                        control={form.control}
                        name="note"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel className="text-xs text-gray-500">
                              الملاحظة
                            </FormLabel>
                            <FormControl>
                              <Textarea
                                placeholder="أدخل ملاحظة حول حالة القطع عند الإرجاع..."
                                {...field}
                                rows={3}
                                className="bg-gray-50 border-gray-200 focus:border-blue-400 text-sm"
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />

                      <FormField
                        control={form.control}
                        name="photos"
                        render={() => (
                          <FormItem>
                            <FormLabel className="text-xs text-gray-500">
                              الصور (1–10)
                            </FormLabel>
                            <FormControl>
                              <UploadFileField
                                name="photos"
                                multiple
                                maxFiles={10}
                                accept="image/*"
                                placeholder="اختر صور القطع عند الإرجاع"
                                showPreview
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center gap-2 justify-end">
                      <button
                        type="button"
                        onClick={() => handleOpenChange(false)}
                        disabled={isReturning}
                        className="flex items-center gap-2 px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer disabled:opacity-50"
                      >
                        <i className="ri-close-line" />
                        إلغاء
                      </button>
                      <button
                        type="submit"
                        disabled={!canSubmit}
                        className="flex items-center gap-2 px-5 py-2.5 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                      >
                        {isReturning ? (
                          <>
                            <i className="ri-loader-4-line animate-spin" />
                            جاري الإرجاع...
                          </>
                        ) : (
                          <>
                            <i className="ri-arrow-go-back-line" />
                            {selectedCount > 0
                              ? `إرجاع القطع المحددة (${selectedCount})`
                              : "إرجاع القطع المحددة"}
                          </>
                        )}
                      </button>
                    </div>
                  </form>
                </Form>
              </>
            )}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
