import { useEffect, useMemo } from "react";
import { Link, useLocation, useNavigate } from "react-router";
import { toast } from "sonner";
import { TClientResponse } from "@/api/v2/clients/clients.types";
import { TEntity } from "@/lib/types/entity.types";
import {
  Loader2,
  User,
  FileText,
  CalendarClock,
  Percent,
  Shirt,
  Banknote,
  StickyNote,
} from "lucide-react";
import { useForm, useFieldArray, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { format } from "date-fns";
import { processTypeForOrderLine } from "@/pages/orders/lib/orderCreationShared";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  CardDescription,
} from "@/components/ui/card";
import { DatePicker } from "@/components/custom/DatePicker";
import { SimpleDateTimePicker } from "@/components/custom/SimpleDateTimePicker";
import { useCreateOrderMutationOptions } from "@/api/v2/orders/orders.hooks";
import { TCreateOrderRequest } from "@/api/v2/orders/orders.types";
import { useMutation } from "@tanstack/react-query";
import { formatPhone } from "@/utils/formatPhone";
import { OrderStepsStepper } from "@/components/custom/OrderStepsStepper";

type SelectedCloth = {
  id: number;
  code: string;
  name: string;
  price: number;
};

type LocationState = {
  client_id: number;
  entity_type: TEntity;
  entity_id: number;
  delivery_date: string;
  selected_clothes: SelectedCloth[];
  client?: TClientResponse;
};

const ORDER_DRAFT_KEY = "order-create-order-draft";

// Zod schema for order form (rental dates at order level, not item level)
const orderItemSchema = z
  .object({
    cloth_id: z.number(),
    price: z.number().min(1, "السعر يجب أن يكون أكبر من أو يساوي صفر"),
    type: z.enum(["rent", "buy"], {
      required_error: "يجب اختيار نوع الطلب",
    }),
    days_of_rent: z.number().min(1).optional(),
    occasion_datetime: z.date().optional(),
    delivery_date: z.date().optional(),
    has_discount: z.boolean().default(false),
    discount_type: z.enum(["none", "percentage", "fixed"]).optional(),
    discount_value: z.number().min(0).optional(),
    notes: z.string().optional(),
  })
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
  );


const formSchema = z
  .object({
    paid: z.number().min(0, "المبلغ المدفوع يجب أن يكون أكبر من أو يساوي صفر"),
    delivery_date: z.date({
      required_error: "يجب اختيار تاريخ التسليم",
    }),
    occasion_datetime: z.date().optional(),
    days_of_rent: z.number().min(1, "عدد أيام الإيجار على الأقل يوم واحد").optional(),
    has_order_discount: z.boolean().default(false),
    order_discount_type: z.enum(["none", "percentage", "fixed"]).optional(),
    order_discount_value: z.number().min(0).optional(),
    order_notes: z.string().optional(),
    items: z.array(orderItemSchema).min(1, "يجب اختيار منتج واحد على الأقل"),
  })
  .refine(
    (data) => {
      const hasRent = data.items.some((i) => i.type === "rent");
      if (hasRent && (!data.days_of_rent || data.days_of_rent < 1)) return false;
      return true;
    },
    { message: "عدد أيام الإيجار مطلوب عند وجود قطع إيجار", path: ["days_of_rent"] }
  )
  .refine(
    (data) => {
      const hasRent = data.items.some((i) => i.type === "rent");
      if (hasRent && !data.occasion_datetime) return false;
      return true;
    },
    { message: "تاريخ المناسبة مطلوب للإيجار", path: ["occasion_datetime"] }
  )
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

function CreateOrderForm() {
  const location = useLocation();
  const navigate = useNavigate();

  const locationState = location.state as LocationState | null;

  // Initialize form with default values from location state
  const defaultValues = useMemo<FormValues>(() => {
    if (!locationState) {
      return {
        paid: 0,
        delivery_date: new Date(),
        occasion_datetime: undefined,
        days_of_rent: undefined,
        has_order_discount: false,
        order_discount_type: "none",
        order_discount_value: 0,
        order_notes: "",
        items: [],
      };
    }

    const deliveryDate = new Date(locationState.delivery_date);
    const hasRent = locationState.selected_clothes.length > 0;

    return {
      paid: 0,
      delivery_date: deliveryDate,
      occasion_datetime: hasRent ? new Date() : undefined,
      days_of_rent: hasRent ? 1 : undefined,
      has_order_discount: false,
      order_discount_type: "none",
      order_discount_value: 0,
      order_notes: "",
      items: locationState.selected_clothes.map((cloth) => ({
        cloth_id: cloth.id,
        price: cloth.price,
        type: "rent" as const,
        has_discount: false,
        discount_type: "none" as const,
        discount_value: 0,
        notes: "",
      })),
    };
  }, [locationState]);

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

  const items = useWatch({ control: form.control, name: "items" });
  const hasRentItem = (items ?? []).some((i) => i?.type === "rent");
  const orderDiscountType = useWatch({
    control: form.control,
    name: "order_discount_type",
  });
  const orderDiscountValue = useWatch({
    control: form.control,
    name: "order_discount_value",
  });
  const paid = useWatch({ control: form.control, name: "paid" }) ?? 0;

  const { orderDiscount, total } = useMemo(() => {
    const itemList = items ?? [];
    let st = 0;
    for (const item of itemList) {
      let p = Number(item?.price) || 0;
      if (item?.has_discount && item?.discount_type && item?.discount_type !== "none") {
        const dv = Number(item?.discount_value) || 0;
        if (item.discount_type === "fixed") p -= dv;
        else if (item.discount_type === "percentage") p *= 1 - dv / 100;
      }
      st += p;
    }
    let od = 0;
    if (hasOrderDiscount && orderDiscountType && orderDiscountType !== "none") {
      const dv = Number(orderDiscountValue) || 0;
      if (orderDiscountType === "fixed") od = dv;
      else if (orderDiscountType === "percentage") od = st * (dv / 100);
    }
    return {
      subtotal: st,
      orderDiscount: od,
      total: Math.max(0, st - od),
    };
  }, [items, hasOrderDiscount, orderDiscountType, orderDiscountValue]);

  const createOrderMutation = useMutation(useCreateOrderMutationOptions());
  const { mutate: createOrder, isPending: isCreatingOrder } =
    createOrderMutation;

  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  useEffect(() => {
    // Validate required state
    if (!locationState) {
      toast.error("يجب عليك اختيار العميل والمنتجات أولاً");
      navigate("/orders/choose-client");
      return;
    }

    const {
      client_id,
      entity_type,
      entity_id,
      delivery_date,
      selected_clothes,
    } = locationState;

    // Validate each required field
    if (!client_id || typeof client_id !== "number") {
      toast.error("يجب عليك اختيار عميل");
      navigate("/orders/choose-client");
      return;
    }

    if (
      !entity_type ||
      !["branch", "factory", "workshop"].includes(entity_type)
    ) {
      toast.error("يجب عليك اختيار نوع المكان");
      navigate("/orders/choose-clothes", {
        state: locationState.client ? { client: locationState.client } : null,
      });
      return;
    }

    if (!entity_id || typeof entity_id !== "number") {
      toast.error("يجب عليك اختيار المكان");
      navigate("/orders/choose-clothes", {
        state: locationState.client ? { client: locationState.client } : null,
      });
      return;
    }

    if (!delivery_date || typeof delivery_date !== "string") {
      toast.error("يجب عليك اختيار تاريخ التسليم");
      navigate("/orders/choose-clothes", {
        state: locationState.client ? { client: locationState.client } : null,
      });
      return;
    }

    if (
      !selected_clothes ||
      !Array.isArray(selected_clothes) ||
      selected_clothes.length === 0
    ) {
      toast.error("يجب عليك اختيار منتج واحد على الأقل");
      navigate("/orders/choose-clothes", {
        state: locationState.client ? { client: locationState.client } : null,
      });
      return;
    }
  }, [locationState, navigate]);

  // Don't render if state is invalid (will redirect)
  if (!locationState) {
    return null;
  }

  const {
    client_id,
    entity_type,
    entity_id,
    delivery_date,
    selected_clothes,
    client,
  } = locationState;

  // Additional validation check before rendering
  if (
    !client_id ||
    !entity_type ||
    !entity_id ||
    !delivery_date ||
    !selected_clothes ||
    selected_clothes.length === 0
  ) {
    return null;
  }

  const onSubmit = async (values: FormValues) => {
    try {
      const orderLevelOccasionDatetime = values.occasion_datetime
        ? format(values.occasion_datetime, "yyyy-MM-dd HH:mm:ss")
        : undefined;
      const orderLevelDaysOfRent = values.days_of_rent ?? undefined;

      const deliveryDateStr = format(values.delivery_date, "yyyy-MM-dd HH:mm:ss");
      const allBuyItems = values.items.every((i) => i.type === "buy");

      const requestData: TCreateOrderRequest = {
        existing_client: true,
        client_id,
        entity_type,
        entity_id,
        delivery_date: deliveryDateStr,
        ...(allBuyItems && {
          process_type: "sold" as const,
          visit_datetime: deliveryDateStr,
        }),
        ...(orderLevelOccasionDatetime && { occasion_datetime: orderLevelOccasionDatetime }),
        ...(orderLevelDaysOfRent != null && { days_of_rent: orderLevelDaysOfRent }),
        ...(values.has_order_discount &&
          values.order_discount_type &&
          values.order_discount_type !== "none" &&
          (values.order_discount_value ?? 0) > 0
          ? {
              discount_type: values.order_discount_type,
              discount_value: values.order_discount_value ?? 0,
            }
          : {}),
        ...(values.order_notes ? { order_notes: values.order_notes } : {}),
        items: values.items.map((item) => {
          const pt = processTypeForOrderLine(item.type);
          const base = {
            cloth_id: item.cloth_id,
            price: item.price,
            quantity: 1,
            paid: 0,
            type: item.type,
            ...(pt ? { process_type: pt } : {}),
            ...(item.has_discount &&
            item.discount_type &&
            item.discount_type !== "none"
              ? {
                  discount_type: item.discount_type,
                  discount_value: item.discount_value ?? 0,
                }
              : {}),
            ...(item.notes ? { notes: item.notes } : {}),
          };
          return base;
        }),
      };

      createOrder(requestData, {
        onSuccess: () => {
          toast.success("تم إنشاء الطلب بنجاح");
          navigate("/orders/list");
        },
        onError: (error: any) => {
          toast.error("خطأ في إنشاء الطلب", {
            description: error?.message || "حدث خطأ غير متوقع",
          });
        },
      });
    } catch (error: any) {
      toast.error("خطأ في إنشاء الطلب", {
        description: error?.message || "حدث خطأ غير متوقع",
      });
    }
  };

  const handleSaveDraft = () => {
    const values = form.getValues();
    const draft = {
      locationState,
      formValues: {
        ...values,
        delivery_date: values.delivery_date?.toISOString?.() ?? null,
        occasion_datetime: values.occasion_datetime?.toISOString?.() ?? null,
        items: values.items.map((item) => ({
          ...item,
          occasion_datetime: (item as { occasion_datetime?: Date })?.occasion_datetime?.toISOString?.() ?? null,
          delivery_date: (item as { delivery_date?: Date })?.delivery_date?.toISOString?.() ?? null,
        })),
      },
      savedAt: new Date().toISOString(),
    };
    try {
      localStorage.setItem(ORDER_DRAFT_KEY, JSON.stringify(draft));
      toast.success("تم حفظ الطلب كمسودة");
    } catch {
      toast.error("فشل حفظ المسودة");
    }
  };

  return (
    <div dir="rtl" className="min-h-screen bg-[linear-gradient(180deg,hsl(220_20%_98%)_0%,hsl(220_14%_96%)_100%)] dark:bg-slate-950">
      <div className="w-full space-y-8 py-8 px-4 lg:px-8">
        <nav className="flex items-center gap-2 text-sm">
          <Link
            to="/orders/list"
            className="font-medium text-slate-500 transition-colors hover:text-[#5170ff] dark:text-slate-400"
          >
            قائمة الطلبات
          </Link>
          <i className="ri-arrow-left-s-line text-slate-400" aria-hidden />
          <span className="font-semibold text-slate-900 dark:text-slate-100">
            إنشاء طلب إيجار — المراجعة والتأكيد
          </span>
        </nav>

        {/* Steps */}
        <div className="sticky top-0 z-20 -mx-4 -mt-8 px-4 pt-8 pb-4 lg:-mx-8 lg:px-8 lg:pt-8 lg:pb-4 bg-[linear-gradient(180deg,hsl(220_20%_98%)_0%,hsl(220_14%_96%)_100%)] dark:bg-slate-950">
          <OrderStepsStepper
            currentStep={3}
            stepState={
              locationState?.client
                ? { 2: { client: locationState.client } }
                : undefined
            }
          />
        </div>

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_420px]">
          {/* Invoice panel */}
          <aside className="w-full order-first lg:order-none lg:col-start-2 lg:row-start-1">
            <div className="sticky top-24 overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-black/30 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
              {/* Invoice header */}
              <div className="relative overflow-hidden bg-gradient-to-br from-[#5170ff] to-[#3d5ae0] px-8 py-8">
                <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50" />
                <div className="relative flex items-center gap-5">
                  <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-md ring-1 ring-white/30">
                    <FileText className="h-8 w-8 text-white" />
                  </div>
                  <div>
                    <h2 className="text-2xl font-bold tracking-tight text-white">
                      ملخص الفاتورة
                    </h2>
                    <p className="mt-1 text-sm text-white/90">
                      {(items ?? []).length} قطعة · مراجعة نهائية
                    </p>
                  </div>
                </div>
              </div>

              {/* Items table */}
              <div className="border-b border-slate-200 dark:border-slate-700">
                <div className="flex items-center justify-between gap-4 px-6 py-4 bg-slate-50 dark:bg-slate-800/50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                  <span>القطعة</span>
                  <span>المبلغ</span>
                </div>
                <div className="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                  {(items ?? []).map((item, index) => {
                    const cloth = selected_clothes.find((c) => c.id === item?.cloth_id);
                    let price = Number(item?.price) || 0;
                    if (item?.has_discount && item?.discount_type && item?.discount_type !== "none") {
                      const dv = Number(item?.discount_value) || 0;
                      if (item.discount_type === "fixed") price -= dv;
                      else if (item.discount_type === "percentage") price *= 1 - dv / 100;
                    }
                    return (
                      <div
                        key={index}
                        className="flex items-center justify-between gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                      >
                        <div className="min-w-0 flex-1">
                          <p className="font-semibold text-slate-900 dark:text-slate-100 truncate">
                            {cloth?.name ?? cloth?.code ?? `قطعة ${index + 1}`}
                          </p>
                          <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            <span className="font-mono">{cloth?.code ?? `#${index + 1}`}</span>
                          </p>
                        </div>
                        <span className="font-bold text-slate-900 dark:text-slate-100 shrink-0 tabular-nums">
                          {price.toFixed(0)} ج.م
                        </span>
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* Totals */}
              <div className="space-y-4 bg-slate-50 dark:bg-slate-800/50 px-6 py-6">
                {hasOrderDiscount && orderDiscount > 0 && (
                  <div className="flex justify-between text-sm">
                    <span className="text-slate-500 dark:text-slate-400">الخصم</span>
                    <span className="font-semibold text-amber-600 dark:text-amber-400 tabular-nums">
                      -{orderDiscount.toFixed(0)} ج.م
                    </span>
                  </div>
                )}
                <div className="flex justify-between items-baseline">
                  <span className="font-semibold text-slate-700 dark:text-slate-300">الإجمالي</span>
                  <span className="text-xl font-bold text-[#5170ff] tabular-nums">{total.toFixed(0)} ج.م</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-slate-500 dark:text-slate-400">المدفوع</span>
                  <span className="font-semibold text-emerald-600 dark:text-emerald-400 tabular-nums">
                    {Number(paid).toFixed(0)} ج.م
                  </span>
                </div>
                <div className="flex justify-between border-t border-slate-200 dark:border-slate-700 pt-4 font-bold">
                  <span className="text-slate-800 dark:text-slate-200">المتبقي</span>
                  <span className="text-lg text-slate-900 dark:text-slate-100 tabular-nums">
                    {Math.max(0, total - Number(paid)).toFixed(0)} ج.م
                  </span>
                </div>
              </div>

              {/* Action buttons */}
              <div className="space-y-3 p-6">
                <Button
                  type="button"
                  onClick={form.handleSubmit(onSubmit)}
                  disabled={isCreatingOrder}
                  className="w-full h-12 rounded-xl bg-[#5170ff] hover:bg-[#4560e6] text-white font-semibold shadow-lg shadow-[#5170ff]/30 transition-all hover:shadow-xl hover:shadow-[#5170ff]/40 active:scale-[0.98]"
                >
                  {isCreatingOrder ? (
                    <>
                      <Loader2 className="ml-2 h-4 w-4 animate-spin" />
                      جاري الإنشاء...
                    </>
                  ) : (
                    "إنشاء الطلب"
                  )}
                </Button>
                <div className="flex gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    className="flex-1 h-10 rounded-xl border-2"
                    onClick={handleSaveDraft}
                  >
                    حفظ كمسودة
                  </Button>
                  <Button
                    type="button"
                    variant="ghost"
                    className="h-10 rounded-xl text-slate-500 hover:text-slate-900 dark:hover:text-slate-100"
                    onClick={() => navigate(-1)}
                  >
                    إلغاء
                  </Button>
                </div>
              </div>
            </div>
          </aside>

          <div className="min-w-0 lg:col-start-1 lg:row-start-1">
          <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-8">
            {/* Order details card */}
            <div className="overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-black/30 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
              <div className="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-8 py-6">
                <div className="flex items-center gap-5">
                  <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#5170ff]/10 ring-1 ring-[#5170ff]/20">
                    <FileText className="h-7 w-7 text-[#5170ff]" />
                  </div>
                  <div>
                    <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                      تفاصيل الطلب
                    </h1>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                      مراجعة وتعديل بيانات العميل، المدفوع، التواريخ، والقطع المختارة
                    </p>
                  </div>
                </div>
              </div>

              <div className="space-y-8 p-8">
                {/* Client info */}
                {client && (
                  <section aria-label="معلومات العميل" className="rounded-2xl bg-slate-50 dark:bg-slate-800/50 p-6 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                    <div className="flex items-center gap-3 mb-5">
                      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                        <User className="h-5 w-5 text-[#5170ff]" />
                      </div>
                      <div>
                        <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">معلومات العميل</h2>
                        <p className="text-xs text-slate-500 dark:text-slate-400">العميل المختار لهذا الطلب</p>
                      </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                      <div className="rounded-xl bg-white dark:bg-slate-900 p-4 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                        <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                          الاسم الكامل
                        </p>
                        <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                          {client.first_name} {client.middle_name} {client.last_name}
                        </p>
                      </div>
                      <div className="rounded-xl bg-white dark:bg-slate-900 p-4 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                        <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                          الرقم القومي
                        </p>
                        <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                          {client.national_id || "—"}
                        </p>
                      </div>
                      <div className="rounded-xl bg-white dark:bg-slate-900 p-4 ring-1 ring-slate-200/60 dark:ring-slate-700/50 sm:col-span-2 md:col-span-1">
                        <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                          أرقام الهاتف
                        </p>
                        <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                          <span dir="ltr">{client.phones?.map((p) => formatPhone(p.phone, "")).filter(Boolean).join("، ") || "—"}</span>
                        </p>
                      </div>
                    </div>
                  </section>
                )}

                {/* Payment and delivery date */}
                <section aria-label="Order details" className="rounded-2xl bg-slate-50 dark:bg-slate-800/50 p-6 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                  <div className="space-y-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                        <Banknote className="h-5 w-5 text-[#5170ff]" />
                      </div>
                      <div>
                        <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">المدفوع وتاريخ التسليم</h3>
                        <p className="text-xs text-slate-500 dark:text-slate-400">أدخل المبلغ المدفوع وتاريخ التسليم</p>
                      </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                      <FormField
                        control={form.control}
                        name="paid"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>المبلغ المدفوع (ج.م)</FormLabel>
                            <FormControl>
                              <Input
                                placeholder="0.00"
                                className="h-10"
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
                      <FormField
                        control={form.control}
                        name="delivery_date"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>تاريخ التسليم</FormLabel>
                            <FormControl>
                              <DatePicker
                                value={field.value}
                                onChange={field.onChange}
                                placeholder="اختر تاريخ التسليم"
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                  </div>

                  {/* Rental dates at order level */}
                  {hasRentItem && (
                    <div className="rounded-xl bg-[#5170ff]/5 ring-1 ring-[#5170ff]/20 p-5">
                      <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#5170ff]/10">
                          <CalendarClock className="h-5 w-5 text-[#5170ff]" />
                        </div>
                        <div>
                          <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">تواريخ الإيجار</h3>
                          <p className="text-xs text-slate-500 dark:text-slate-400">تنطبق على الطلب بالكامل</p>
                        </div>
                      </div>
                      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <FormField
                          control={form.control}
                          name="occasion_datetime"
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>تاريخ ووقت المناسبة</FormLabel>
                              <FormControl>
                                <SimpleDateTimePicker
                                  value={field.value}
                                  onChange={field.onChange}
                                  placeholder="اختر تاريخ ووقت المناسبة"
                                  minDate={new Date()}
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                        <FormField
                          control={form.control}
                          name="days_of_rent"
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>عدد أيام الإيجار</FormLabel>
                              <FormControl>
                                <Input
                                  placeholder="1"
                                  className="h-10"
                                  value={field.value ?? ""}
                                  onChange={(e) => {
                                    const val = e.target.value.replace(/[^0-9]/g, "");
                                    field.onChange(val ? parseInt(val, 10) : undefined);
                                  }}
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                      </div>
                    </div>
                  )}
                </section>

                {/* Order notes */}
                <section aria-label="Order notes" className="rounded-2xl bg-slate-50 dark:bg-slate-800/50 p-6 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                      <StickyNote className="h-5 w-5 text-[#5170ff]" />
                    </div>
                    <div>
                      <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">ملاحظات الطلب</h3>
                      <p className="text-xs text-slate-500 dark:text-slate-400">ملاحظات اختيارية حول الطلب</p>
                    </div>
                  </div>
                  <FormField
                    control={form.control}
                    name="order_notes"
                    render={({ field }) => (
                      <FormItem>
                        <FormControl>
                          <Textarea
                            placeholder="أدخل ملاحظات حول الطلب (اختياري)..."
                            className="resize-none rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                            rows={4}
                            {...field}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </section>

                {/* Selected items */}
                <section aria-label="تفاصيل القطع المختارة" className="space-y-5">
                  <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                      <Shirt className="h-5 w-5 text-[#5170ff]" />
                    </div>
                    <div>
                      <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">
                        تفاصيل المنتج المختار
                      </h2>
                      <p className="text-xs text-slate-500 dark:text-slate-400">
                        أدخل السعر، الخصم والملاحظات لكل قطعة — {fields.length} قطعة
                      </p>
                    </div>
                  </div>

                  {fields.map((field, index) => {
                    const cloth = selected_clothes.find(
                      (c) => c.id === field.cloth_id
                    );
                    const itemHasDiscount = items?.[index]?.has_discount;

                    return (
                      <div
                        key={field.id}
                        className="rounded-2xl bg-white dark:bg-slate-900 shadow-lg shadow-slate-200/30 dark:shadow-black/20 ring-1 ring-slate-200/60 dark:ring-slate-700/50 overflow-hidden transition-all hover:ring-[#5170ff]/30 hover:shadow-xl"
                      >
                        {/* Item header */}
                        <div className="flex flex-row items-center gap-3 bg-slate-50 dark:bg-slate-800/50 px-6 py-4 border-b border-slate-200/60 dark:border-slate-700/50">
                          <span
                            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff] text-sm font-bold text-white shadow-lg shadow-[#5170ff]/30"
                            aria-hidden
                          >
                            {index + 1}
                          </span>
                          <div className="min-w-0 flex-1">
                            <p className="truncate text-base font-bold text-slate-900 dark:text-slate-100">
                              {cloth?.name ?? cloth?.code}
                            </p>
                            <p className="mt-0.5 truncate text-sm text-slate-500 dark:text-slate-400">
                              الكود: {cloth?.code}
                            </p>
                          </div>
                        </div>

                        <div className="space-y-5 p-6">
                          {/* Price */}
                          <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-4 ring-1 ring-slate-200/40 dark:ring-slate-700/40">
                            <p className="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                              <Banknote className="h-4 w-4 text-[#5170ff]" />
                              السعر
                            </p>
                            <FormField
                              control={form.control}
                              name={`items.${index}.price`}
                              render={({ field }) => (
                                <FormItem>
                                  <FormLabel>السعر (ج.م)</FormLabel>
                                  <FormControl>
                                    <Input
                                      placeholder="0.00"
                                      className="h-10"
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

                          {/* Item discount */}
                          <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-4 ring-1 ring-slate-200/40 dark:ring-slate-700/40">
                            <p className="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                              <Percent className="h-4 w-4 text-[#5170ff]" />
                              خصم القطعة
                            </p>
                            <FormField
                              control={form.control}
                              name={`items.${index}.has_discount`}
                              render={({ field }) => (
                                <FormItem className="flex flex-row items-center justify-between rounded-xl bg-white dark:bg-slate-900 ring-1 ring-slate-200/60 dark:ring-slate-700/50 p-4">
                                  <div className="space-y-0.5">
                                    <FormLabel className="text-sm font-medium">
                                      إضافة خصم على هذه القطعة
                                    </FormLabel>
                                    <CardDescription className="text-xs">
                                      نسبة مئوية أو مبلغ ثابت
                                    </CardDescription>
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
                            {itemHasDiscount && (
                              <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <FormField
                                  control={form.control}
                                  name={`items.${index}.discount_type`}
                                  render={({ field }) => (
                                    <FormItem>
                                      <FormLabel>نوع الخصم</FormLabel>
                                      <Select
                                        onValueChange={field.onChange}
                                        value={field.value}
                                      >
                                        <FormControl>
                                          <SelectTrigger className="h-10">
                                            <SelectValue placeholder="اختر نوع الخصم" />
                                          </SelectTrigger>
                                        </FormControl>
                                        <SelectContent>
                                          <SelectItem value="percentage">
                                            نسبة مئوية
                                          </SelectItem>
                                          <SelectItem value="fixed">
                                            مبلغ ثابت
                                          </SelectItem>
                                        </SelectContent>
                                      </Select>
                                      <FormMessage />
                                    </FormItem>
                                  )}
                                />
                                <FormField
                                  control={form.control}
                                  name={`items.${index}.discount_value`}
                                  render={({ field }) => (
                                    <FormItem>
                                      <FormLabel>قيمة الخصم</FormLabel>
                                      <FormControl>
                                        <Input
                                          placeholder="0.00"
                                          className="h-10"
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
                          </div>

                          {/* Item notes */}
                          <FormField
                            control={form.control}
                            name={`items.${index}.notes`}
                            render={({ field }) => (
                              <FormItem className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-4 ring-1 ring-slate-200/40 dark:ring-slate-700/40">
                                <FormLabel>
                                  <span className="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                                    <StickyNote className="h-4 w-4 text-[#5170ff]" />
                                    ملاحظات القطعة
                                  </span>
                                </FormLabel>
                                <FormControl>
                                  <Textarea
                                    placeholder="ملاحظات خاصة بهذه القطعة (اختياري)..."
                                    className="resize-none rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                                    rows={2}
                                    {...field}
                                  />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />
                        </div>
                      </div>
                    );
                  })}
                </section>

                {/* Order discount */}
                <section aria-label="خصم على الطلب" className="rounded-2xl bg-slate-50 dark:bg-slate-800/50 p-6 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                  <div className="flex items-center gap-3 mb-5">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                      <Percent className="h-5 w-5 text-[#5170ff]" />
                    </div>
                    <div>
                      <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">خصم على الطلب كاملاً</h3>
                      <p className="text-xs text-slate-500 dark:text-slate-400">اختياري — تفعيل الخصم على مستوى الطلب بالكامل</p>
                    </div>
                  </div>
                  <FormField
                    control={form.control}
                    name="has_order_discount"
                    render={({ field }) => (
                      <FormItem className="flex flex-row items-center justify-between rounded-xl bg-white dark:bg-slate-900 ring-1 ring-slate-200/60 dark:ring-slate-700/50 p-5">
                        <div className="space-y-0.5">
                          <FormLabel className="text-base font-semibold text-slate-900 dark:text-slate-100">
                            إضافة خصم على الطلب
                          </FormLabel>
                          <p className="text-sm text-slate-500 dark:text-slate-400">
                            تفعيل الخصم على مستوى الطلب بالكامل
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
                    <div className="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <FormField
                          control={form.control}
                          name="order_discount_type"
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>نوع الخصم</FormLabel>
                              <Select
                                onValueChange={field.onChange}
                                value={field.value}
                              >
                                <FormControl>
                                  <SelectTrigger className="h-10">
                                    <SelectValue placeholder="اختر نوع الخصم" />
                                  </SelectTrigger>
                                </FormControl>
                                <SelectContent>
                                  <SelectItem value="percentage">
                                    نسبة مئوية
                                  </SelectItem>
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
                              <FormLabel>قيمة الخصم</FormLabel>
                              <FormControl>
                                <Input
                                  placeholder="0.00"
                                  className="h-10"
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
                </section>
              </div>
            </div>
          </form>
        </Form>
          </div>
        </div>
      </div>
    </div>
  );
}

export default CreateOrderForm;
