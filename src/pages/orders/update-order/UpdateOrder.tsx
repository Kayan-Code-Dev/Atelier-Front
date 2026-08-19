import { useGetClothesUnavailableDaysRangesbyIdsQueryOptions } from "@/api/v2/clothes/clothes.hooks";
import {
  useGetOrderDetailsQueryOptions,
  useUpdateOrderMutationOptions,
} from "@/api/v2/orders/orders.hooks";
import { getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";
import { TOrder, TUpdateOrderRequest } from "@/api/v2/orders/orders.types";
import { SimpleDateTimePicker } from "@/components/custom/SimpleDateTimePicker";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { format, parseISO } from "date-fns";
import { useEffect, useMemo } from "react";
import { useFieldArray, useForm, useWatch } from "react-hook-form";
import type { Control } from "react-hook-form";
import { Link, useLocation, useNavigate } from "react-router";
import { toast } from "sonner";
import * as z from "zod";
import OrderDetailsSkeleton from "../OrderDetailsSkeleton";
import {
  UpdateOrderItemCard,
  type UpdateOrderFormValues,
} from "./UpdateOrderItemCard";

type SelectedCloth = {
  id: number;
  code: string;
  name: string;
  price?: number;
};

type RemovedItemInfo = {
  originalItemId: number;
  replacementId: number | null;
  originalPrice?: number; // Original price of the removed item
};

type LocationState = {
  order: TOrder;
  clothes: SelectedCloth[];
  delivery_date: string;
  removedItems: RemovedItemInfo[];
};

const EMPTY_CLOTH_LIST: SelectedCloth[] = [];
const EMPTY_REMOVED_ITEMS: RemovedItemInfo[] = [];

// Zod schema for order item
const orderItemSchema = z
  .object({
    cloth_id: z.number(),
    price: z.number().min(0, "السعر يجب أن يكون أكبر من أو يساوي صفر"),
    type: z.enum(["rent", "buy"], {
      required_error: "يجب اختيار نوع الطلب",
    }),
    days_of_rent: z
      .number()
      .min(1, "عدد أيام الإيجار يجب أن يكون على الأقل يوم واحد")
      .optional(),
    occasion_datetime: z.date({
      required_error: "يجب اختيار تاريخ المناسبة",
    }),
    delivery_date: z.date({
      required_error: "يجب اختيار تاريخ التسليم",
    }),
    has_discount: z.boolean().default(false),
    discount_type: z.enum(["none", "percentage", "fixed"]).optional(),
    discount_value: z.number().min(0).optional(),
    minPrice: z.number().optional(), // Minimum price for validation
    notes: z.string().optional(),
  })
  .refine(
    (data) => {
      if (data.type === "rent" && !data.days_of_rent) {
        return false;
      }
      return true;
    },
    {
      message: "عدد أيام الإيجار مطلوب لنوع الإيجار",
      path: ["days_of_rent"],
    }
  )
  .refine(
    (data) => {
      if (data.has_discount) {
        return (
          data.discount_type &&
          data.discount_type !== "none" &&
          data.discount_value !== undefined
        );
      }
      return true;
    },
    {
      message: "يجب اختيار نوع الخصم وقيمة الخصم",
      path: ["discount_value"],
    }
  )
  .superRefine((data, ctx) => {
    if (data.minPrice !== undefined && data.price < data.minPrice) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: `السعر يجب أن يكون أكبر من أو يساوي ${data.minPrice} ج.م`,
        path: ["price"],
      });
    }
  });

const formSchema = z
  .object({
    paid: z.number().min(0, "المبلغ المدفوع يجب أن يكون أكبر من أو يساوي صفر"),
    visit_datetime: z.date({
      required_error: "يجب اختيار تاريخ ووقت الزيارة",
    }),
    has_order_discount: z.boolean().default(false),
    order_discount_type: z.enum(["none", "percentage", "fixed"]).optional(),
    order_discount_value: z.number().min(0).optional(),
    order_notes: z.string().optional(),
    items: z.array(orderItemSchema).min(1, "يجب اختيار منتج واحد على الأقل"),
    minPaid: z.number().optional(), // Minimum paid amount
  })
  .refine(
    (data) => {
      if (data.has_order_discount) {
        return (
          data.order_discount_type &&
          data.order_discount_type !== "none" &&
          data.order_discount_value !== undefined
        );
      }
      return true;
    },
    {
      message: "يجب اختيار نوع الخصم وقيمة الخصم",
      path: ["order_discount_value"],
    }
  )
  .superRefine((data, ctx) => {
    // Check minimum paid amount (for existing orders)
    if (data.minPaid !== undefined && data.paid < data.minPaid) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: `المبلغ المدفوع يجب أن يكون أكبر من أو يساوي ${data.minPaid} ج.م`,
        path: ["paid"],
      });
    }

    // Check if any item is buy type
    const hasBuyItem = data.items.some((item) => item.type === "buy");

    // If buy order, must have exactly 1 item
    if (hasBuyItem && data.items.length !== 1) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "طلبات الشراء يجب أن تحتوي على قطعة واحدة فقط",
        path: ["items"],
      });
    }
  });

type FormValues = z.infer<typeof formSchema>;

const fieldLabelClass =
  "text-[11px] font-semibold uppercase tracking-wide text-slate-500";

const controlClass =
  "border-slate-200 bg-white shadow-none focus-visible:border-slate-300 focus-visible:ring-[3px] focus-visible:ring-emerald-500/15";

function UpdateOrder() {
  const location = useLocation();
  const navigate = useNavigate();

  const locationState = location.state as LocationState | null;

  const { mutate: updateOrder, isPending: isUpdatingOrder } = useMutation(
    useUpdateOrderMutationOptions()
  );
  // Fetch full order details to get item prices
  const { data: orderDetails, isPending: isLoadingOrder } = useQuery({
    ...useGetOrderDetailsQueryOptions(locationState?.order?.id || 0),
    enabled: !!locationState?.order?.id,
  });

  const fullOrder = orderDetails || locationState?.order;

  useEffect(() => {
    // Validate state
    if (!locationState) {
      toast.error("يجب عليك اختيار الطلب المنتجات أولاً");
      setTimeout(() => {
        navigate("/orders/list");
      }, 0);
      return;
    }

    const { order, clothes, delivery_date } = locationState;

    // Validate order
    if (!order || typeof order !== "object" || !order.id) {
      toast.error("الطلب غير صحيح");
      setTimeout(() => {
        navigate("/orders/list");
      }, 0);
      return;
    }

    // Validate products array
    if (!clothes || !Array.isArray(clothes) || clothes.length === 0) {
      toast.error("يجب اختيار منتج واحد على الأقل");
      setTimeout(() => {
        navigate("/orders/update-clothes-in-order", {
          state: { order },
        });
      }, 0);
      return;
    }

    // Validate delivery_date
    if (!delivery_date || typeof delivery_date !== "string") {
      toast.error("تاريخ التسليم غير صحيح");
      setTimeout(() => {
        navigate("/orders/update-clothes-in-order", {
          state: { order },
        });
      }, 0);
      return;
    }
  }, [locationState, navigate]);

  const clothes = locationState?.clothes ?? EMPTY_CLOTH_LIST;
  const delivery_date = locationState?.delivery_date ?? "";
  const removedItems = locationState?.removedItems ?? EMPTY_REMOVED_ITEMS;

  // Create a map of order items by id for quick lookup
  const orderItemsMap = useMemo(() => {
    const map = new Map<number, TOrder["items"][0]>();
    if (fullOrder?.items) {
      fullOrder.items.forEach((item) => {
        map.set(item.id, item);
      });
    }
    return map;
  }, [fullOrder?.items]);

  // Get removed items with their details
  const removedItemsDetails = useMemo(() => {
    return removedItems
      .map((removed) => {
        const originalItem = orderItemsMap.get(removed.originalItemId);
        if (!originalItem) return null;
        return {
          originalItemId: removed.originalItemId,
          replacementId: removed.replacementId,
          code: originalItem.code,
          name: originalItem.name,
        };
      })
      .filter(
        (
          item
        ): item is {
          originalItemId: number;
          replacementId: number | null;
          code: string;
          name: string;
        } => item !== null
      );
  }, [removedItems, orderItemsMap]);

  // Create a map of removed items by replacementId to get original prices
  const removedItemsMap = useMemo(() => {
    const map = new Map<number, number>(); // replacementId -> originalPrice
    removedItems.forEach((removed) => {
      if (removed.replacementId !== null) {
        // Use original price from removed item info, or get from clothes array
        const cloth = clothes.find((c) => c.id === removed.replacementId);
        const originalPrice =
          parseFloat(
            (removed.originalPrice || cloth?.price || 0)?.toString() ?? "0"
          ) || 0;
        map.set(removed.replacementId, originalPrice);
      }
    });
    return map;
  }, [removedItems, clothes]);

  // Initialize form with default values from location state
  const defaultValues = useMemo<FormValues>(() => {
    if (!fullOrder) {
      return {
        paid: 0,
        visit_datetime: new Date(),
        has_order_discount: false,
        order_discount_type: "none",
        order_discount_value: 0,
        order_notes: "",
        items: [],
        minPaid: 0,
      };
    }

    const deliveryDate = delivery_date ? parseISO(delivery_date) : new Date();
    const visitDate = fullOrder.visit_datetime
      ? parseISO(fullOrder.visit_datetime)
      : new Date();

    // Get minimum paid amount from order
    const minPaid = parseFloat(fullOrder.paid) || 0;

    // Map clothes to form items with minimum prices
    const items = clothes.map((cloth) => {
      // Check if this is an existing item (in order.items)
      const existingItem = Array.from(orderItemsMap.values()).find(
        (item) => item.id === cloth.id || item.code === cloth.code
      );

      // Check if this is a replacement
      const isReplacement = removedItemsMap.has(cloth.id);
      const replacementMinPrice = removedItemsMap.get(cloth.id) || 0;

      // Determine minimum price
      let minPrice = 0;
      if (existingItem) {
        // Existing item - use price from clothes array (which should be original price)
        minPrice = parseFloat(cloth.price?.toString() ?? "0") || 0;
      } else if (isReplacement) {
        // Replacement item - use original price of removed item
        minPrice = parseFloat(replacementMinPrice?.toString() ?? "0") || 0;
      } else {
        // New item - no minimum (or 0)
        minPrice = 0;
      }

      return {
        cloth_id: cloth.id,
        price: parseFloat(cloth.price?.toString() ?? "0") || minPrice, // Use provided price or minimum
        type: "rent" as const,
        days_of_rent: 1,
        occasion_datetime: new Date(),
        delivery_date: deliveryDate,
        has_discount: false,
        discount_type: "none" as const,
        discount_value: 0,
        minPrice,
        notes: "",
      };
    });

    return {
      paid: minPaid, // Start with current paid amount
      visit_datetime: visitDate,
      has_order_discount:
        fullOrder.discount_type !== undefined &&
        fullOrder.discount_type !== "none",
      order_discount_type: fullOrder.discount_type || "none",
      order_discount_value:
        parseFloat(fullOrder.discount_value?.toString() ?? "0") || 0,
      order_notes: fullOrder.order_notes || "",
      items,
      minPaid,
    };
  }, [fullOrder, clothes, delivery_date, orderItemsMap, removedItemsMap]);

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues,
  });

  const { fields } = useFieldArray({
    control: form.control,
    name: "items",
  });

  const hasOrderDiscount = useWatch({
    control: form.control,
    name: "has_order_discount",
  });

  const {
    data: clothesUnavailableDaysRanges,
    isPending: isLoadingUnavailableDaysRanges,
  } = useQuery({
    ...useGetClothesUnavailableDaysRangesbyIdsQueryOptions(
      clothes.map((cloth) => cloth.id)
    ),
    enabled: clothes.length > 0,
  });

  const getClothesUnavailableDaysRanges = (cloth_id: number) => {
    return clothesUnavailableDaysRanges?.results
      .find((result) => result.cloth_id === cloth_id)
      ?.unavailable_ranges.map((range) => ({
        from: new Date(range.start),
        to: new Date(range.end),
      }));
  };

  if (!locationState || isLoadingOrder) {
    return <OrderDetailsSkeleton />;
  }

  if (
    !fullOrder ||
    !fullOrder.id ||
    clothes.length === 0 ||
    !delivery_date
  ) {
    return null;
  }

  const onSubmit = async (_values: FormValues) => {
    try {
      // TODO: Create update order API call
      toast.success("سيتم تحديث الطلب");
      const body: TUpdateOrderRequest = {
        client_id: fullOrder.client_id,
        entity_type: fullOrder.entity_type,
        entity_id: fullOrder.entity_id,
        paid: _values.paid,
        visit_datetime: format(
          new Date(_values.visit_datetime),
          "yyyy-MM-dd HH:mm:ss"
        ),
        order_notes: _values.order_notes || "",
        ...(_values.has_order_discount && {
          discount_type: _values.order_discount_type,
          discount_value: _values.order_discount_value,
        }),
        items: _values.items.map((item) => ({
          cloth_id: item.cloth_id,
          price: item.price,
          type: item.type,
          days_of_rent: item.type === "rent" ? item.days_of_rent || 1 : 0,
          occasion_datetime: format(
            new Date(item.occasion_datetime),
            "yyyy-MM-dd HH:mm:ss"
          ),
          delivery_date: format(
            new Date(item.delivery_date),
            "yyyy-MM-dd HH:mm:ss"
          ),
          ...(item.notes ? { notes: item.notes } : {}),
          ...(item.has_discount &&
          item.discount_type &&
          item.discount_type !== "none"
            ? {
                discount_type: item.discount_type,
                discount_value: item.discount_value,
              }
            : {}),
        })),
      };
      updateOrder(
        { id: fullOrder.id, data: body },
        {
          onSuccess: () => {
            toast.success("تم تحديث الطلب بنجاح");
            navigate(`/orders/${fullOrder.id}`);
          },
          onError: (error: any) => {
            toast.error("خطأ في تحديث الطلب", {
              description: error?.message || "حدث خطأ غير متوقع",
            });
          },
        }
      );
    } catch (error: any) {
      toast.error("خطأ في تحديث الطلب", {
        description: error?.message || "حدث خطأ غير متوقع",
      });
    }
  };

  const { currency_symbol: sym } = getOrderCurrencyInfo(fullOrder);
  const itemControl = form.control as Control<UpdateOrderFormValues>;

  return (
    <div className="p-6" dir="rtl">
      <div className="mb-4 flex flex-wrap items-center gap-2 text-xs text-slate-400">
        <Link to="/orders/list" className="hover:text-slate-600">
          فواتير الإيجار
        </Link>
        <i className="ri-arrow-left-s-line" />
        <Link
          to="/orders/update-clothes-in-order"
          className="hover:text-slate-600"
          state={{ order: fullOrder }}
        >
          اختيار الأصناف
        </Link>
        <i className="ri-arrow-left-s-line" />
        <span className="font-medium text-slate-600">تعديل بيانات الطلب</span>
      </div>

      <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div className="flex items-start gap-3">
          <Link
            to={`/orders/${fullOrder.id}`}
            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50"
            title="العودة لتفاصيل الطلب"
          >
            <i className="ri-arrow-right-line text-lg" />
          </Link>
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="text-xl font-bold text-slate-800">
                تعديل طلب إيجار #{fullOrder.id}
              </h1>
              <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                {fields.length} صنف
              </span>
            </div>
            <p className="mt-1 text-sm text-slate-500">
              حدّث موعد الاسترجاع، الخصومات، والتفاصيل لكل قطعة ثم احفظ التغييرات.
            </p>
          </div>
        </div>
      </div>

      <div className="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
          <p className="text-xs font-medium text-slate-500">رقم الطلب</p>
          <p className="mt-1 text-lg font-semibold tabular-nums text-slate-800">
            #{fullOrder.id}
          </p>
        </div>
        <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
          <p className="text-xs font-medium text-slate-500">الإجمالي</p>
          <p className="mt-1 text-lg font-semibold tabular-nums text-slate-800" dir="ltr">
            {fullOrder.total_price} {sym}
          </p>
        </div>
        <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
          <p className="text-xs font-medium text-slate-500">المدفوع حالياً</p>
          <p className="mt-1 text-lg font-semibold tabular-nums text-emerald-700" dir="ltr">
            {fullOrder.paid} {sym}
          </p>
        </div>
      </div>

      {removedItemsDetails.length > 0 && (
        <div className="mb-6 overflow-hidden rounded-lg border border-amber-200 bg-amber-50/40">
          <div className="flex items-center gap-2 border-b border-amber-100/80 px-5 py-3.5">
            <i className="ri-exchange-line text-lg text-amber-600" />
            <div>
              <h2 className="text-sm font-semibold text-amber-900">
                استبدال أصناف
              </h2>
              <p className="text-xs text-amber-800/80">
                أصناف أُزيلت من الطلب واستُبدلت بأخرى
              </p>
            </div>
          </div>
          <div className="space-y-2 p-4">
            {removedItemsDetails.map((removed) => {
              const replacementCloth = clothes.find(
                (c) => c.id === removed.replacementId
              );
              return (
                <div
                  key={removed.originalItemId}
                  className="flex flex-col gap-3 rounded-lg border border-amber-100 bg-white/80 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="min-w-0 flex-1">
                      <p className="text-xs text-rose-600">أُزيل</p>
                      <p className="truncate text-sm font-medium text-slate-800">
                        {removed.code} — {removed.name}
                      </p>
                    </div>
                    <i className="ri-arrow-left-line hidden text-slate-300 sm:block" />
                    <div className="min-w-0 flex-1">
                      <p className="text-xs text-emerald-600">البديل</p>
                      {replacementCloth ? (
                        <p className="truncate text-sm font-medium text-slate-800">
                          {replacementCloth.code} — {replacementCloth.name}
                        </p>
                      ) : (
                        <p className="text-sm text-slate-500">لم يُختر بديل</p>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div className="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5">
              <i className="ri-file-list-3-line text-lg text-slate-400" />
              <div>
                <h2 className="text-sm font-semibold text-slate-800">
                  بيانات الطلب
                </h2>
                <p className="text-xs text-slate-500">
                  المدفوع، موعد الاسترجاع العام، خصم الفاتورة، والملاحظات
                </p>
              </div>
            </div>
            <div className="space-y-4 p-5">
              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <FormField
                  control={form.control}
                  name="paid"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={fieldLabelClass}>
                        المبلغ المدفوع (للعرض)
                        {(form.watch("minPaid") ?? 0) > 0 && (
                          <span className="mr-1.5 font-normal normal-case text-slate-400">
                            الحد الأدنى: {form.watch("minPaid") ?? 0} {sym}
                          </span>
                        )}
                      </FormLabel>
                      <FormControl>
                        <Input
                          className={cn(controlClass, "bg-slate-50")}
                          placeholder="0.00"
                          value={field.value ?? ""}
                          disabled
                          onChange={(e) => {
                            const val = e.target.value.replace(/[^0-9.]/g, "");
                            field.onChange(val === "" ? 0 : parseFloat(val) || 0);
                          }}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="visit_datetime"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className={fieldLabelClass}>
                        موعد الاسترجاع
                      </FormLabel>
                      <FormControl>
                        <SimpleDateTimePicker
                          value={field.value}
                          onChange={field.onChange}
                          placeholder="اختر موعد الاسترجاع"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <Separator className="bg-slate-100" />

              <FormField
                control={form.control}
                name="has_order_discount"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-center justify-between rounded-lg border border-slate-200 bg-slate-50/40 p-4">
                    <div className="space-y-0.5 pr-2">
                      <FormLabel className="text-sm font-medium text-slate-800">
                        خصم على الفاتورة
                      </FormLabel>
                      <p className="text-xs text-slate-500">
                        يُطبَّق على إجمالي الطلب بعد الأصناف
                      </p>
                    </div>
                    <FormControl>
                      <Switch
                        dir="ltr"
                        checked={field.value}
                        onCheckedChange={field.onChange}
                      />
                    </FormControl>
                  </FormItem>
                )}
              />

              {hasOrderDiscount && (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <FormField
                    control={form.control}
                    name="order_discount_type"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className={fieldLabelClass}>نوع الخصم</FormLabel>
                        <Select onValueChange={field.onChange} value={field.value}>
                          <FormControl>
                            <SelectTrigger className={cn(controlClass, "h-10")}>
                              <SelectValue placeholder="اختر نوع الخصم" />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            <SelectItem value="percentage">نسبة مئوية</SelectItem>
                            <SelectItem value="fixed">مبلغ ثابت</SelectItem>
                          </SelectContent>
                        </Select>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="order_discount_value"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className={fieldLabelClass}>قيمة الخصم</FormLabel>
                        <FormControl>
                          <Input
                            className={controlClass}
                            placeholder="0.00"
                            value={field.value ?? ""}
                            onChange={(e) => {
                              const val = e.target.value.replace(/[^0-9.]/g, "");
                              field.onChange(val === "" ? 0 : parseFloat(val) || 0);
                            }}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </div>
              )}

              <Separator className="bg-slate-100" />

              <FormField
                control={form.control}
                name="order_notes"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className={fieldLabelClass}>ملاحظات الطلب</FormLabel>
                    <FormControl>
                      <Textarea
                        placeholder="ملاحظات عامة على الفاتورة…"
                        className={cn(controlClass, "min-h-[100px] resize-none")}
                        rows={4}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
          </div>

          <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div className="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5">
              <i className="ri-shirt-line text-lg text-slate-400" />
              <div>
                <h2 className="text-sm font-semibold text-slate-800">
                  الأصناف المختارة
                </h2>
                <p className="text-xs text-slate-500">
                  السعر، نوع البند، التواريخ، والخصم لكل قطعة
                </p>
              </div>
            </div>
            <div className="space-y-4 p-5">
              {fields.map((field, index) => (
                <UpdateOrderItemCard
                  key={field.id}
                  index={index}
                  field={field}
                  clothes={clothes}
                  control={itemControl}
                  orderItemsMap={orderItemsMap}
                  removedItemsMap={removedItemsMap}
                  getClothesUnavailableDaysRanges={getClothesUnavailableDaysRanges}
                  isLoadingUnavailableDaysRanges={isLoadingUnavailableDaysRanges}
                  currencySymbol={sym}
                />
              ))}
            </div>
          </div>

          <div className="sticky bottom-4 z-10 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur-sm sm:flex-row sm:justify-end">
            <Button
              type="button"
              variant="outline"
              className="order-2 border-slate-200 sm:order-1"
              onClick={() => navigate(-1)}
            >
              إلغاء
            </Button>
            <Button
              type="submit"
              className="order-1 bg-emerald-600 hover:bg-emerald-700 sm:order-2"
              isLoading={isUpdatingOrder}
            >
              <i className="ri-save-3-line ml-2 text-base" />
              حفظ التعديلات
            </Button>
          </div>
        </form>
      </Form>
    </div>
  );
}

export default UpdateOrder;
