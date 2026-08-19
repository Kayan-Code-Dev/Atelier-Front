import { DatePicker } from "@/components/custom/DatePicker";
import { SimpleDateTimePicker } from "@/components/custom/SimpleDateTimePicker";
import { Badge } from "@/components/ui/badge";
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
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
import { cn } from "@/lib/utils";
import type { TOrder } from "@/api/v2/orders/orders.types";
import { Loader2Icon } from "lucide-react";
import type { Control, FieldArrayWithId } from "react-hook-form";
import { useWatch } from "react-hook-form";

const fieldLabelClass =
  "text-[11px] font-semibold uppercase tracking-wide text-slate-500";

const controlClass =
  "border-slate-200 bg-white shadow-none focus-visible:border-slate-300 focus-visible:ring-[3px] focus-visible:ring-emerald-500/15";

type SelectedCloth = {
  id: number;
  code: string;
  name: string;
  price?: number;
};


export type UpdateOrderFormValues = {
  paid: number;
  visit_datetime: Date;
  has_order_discount: boolean;
  order_discount_type?: "none" | "percentage" | "fixed";
  order_discount_value?: number;
  order_notes?: string;
  items: {
    cloth_id: number;
    price: number;
    type: "rent" | "buy";
    days_of_rent?: number;
    occasion_datetime: Date;
    delivery_date: Date;
    has_discount: boolean;
    discount_type?: "none" | "percentage" | "fixed";
    discount_value?: number;
    minPrice?: number;
    notes?: string;
  }[];
  minPaid?: number;
};

type Props = {
  index: number;
  field: FieldArrayWithId<UpdateOrderFormValues, "items", "id">;
  clothes: SelectedCloth[];
  control: Control<UpdateOrderFormValues>;
  orderItemsMap: Map<number, TOrder["items"][0]>;
  removedItemsMap: Map<number, number>;
  getClothesUnavailableDaysRanges: (
    cloth_id: number
  ) => { from: Date; to: Date }[] | undefined;
  isLoadingUnavailableDaysRanges: boolean;
  currencySymbol: string;
};

export function UpdateOrderItemCard({
  index,
  field,
  clothes,
  control,
  orderItemsMap,
  removedItemsMap,
  getClothesUnavailableDaysRanges,
  isLoadingUnavailableDaysRanges,
  currencySymbol,
}: Props) {
  const cloth = clothes.find((c) => c.id === field.cloth_id);
  const itemHasDiscount = useWatch({
    control,
    name: `items.${index}.has_discount`,
  });
  const itemType = useWatch({
    control,
    name: `items.${index}.type`,
  });
  const minPrice = useWatch({ control, name: `items.${index}.minPrice` }) || 0;
  const isExisting = orderItemsMap.has(field.cloth_id);
  const isReplacement = removedItemsMap.has(field.cloth_id);

  return (
    <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
      <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
        <div className="flex flex-wrap items-center gap-2">
          <span className="font-semibold text-slate-800">
            {cloth?.code}
            {cloth?.name ? ` — ${cloth.name}` : ""}
          </span>
          {isExisting && (
            <Badge
              variant="outline"
              className="rounded-full border-slate-200 px-2.5 py-0.5 text-xs font-medium text-slate-600"
            >
              موجود
            </Badge>
          )}
          {isReplacement && (
            <Badge
              variant="outline"
              className="rounded-full border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800"
            >
              مستبدل
            </Badge>
          )}
          {!isExisting && !isReplacement && (
            <Badge className="rounded-full bg-sky-600 px-2.5 py-0.5 text-xs font-medium hover:bg-sky-600">
              جديد
            </Badge>
          )}
        </div>
        {minPrice > 0 && (
          <p className="text-xs text-slate-500">
            الحد الأدنى للسعر:{" "}
            <span className="font-medium tabular-nums text-slate-700">
              {minPrice} {currencySymbol}
            </span>
          </p>
        )}
      </div>
      <div className="space-y-4 p-4 sm:p-5">
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField
            control={control}
            name={`items.${index}.price`}
            render={({ field: f }) => (
              <FormItem>
                <FormLabel className={fieldLabelClass}>
                  السعر
                  {minPrice > 0 && (
                    <span className="mr-1.5 font-normal normal-case text-slate-400">
                      (الحد الأدنى: {minPrice} {currencySymbol})
                    </span>
                  )}
                </FormLabel>
                <FormControl>
                  <Input
                    placeholder="0.00"
                    className={controlClass}
                    value={f.value ?? ""}
                    onChange={(e) => {
                      const val = e.target.value.replace(/[^0-9.]/g, "");
                      const numVal = val === "" ? 0 : parseFloat(val) || 0;
                      f.onChange(numVal >= minPrice ? numVal : minPrice);
                    }}
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />

          <FormField
            control={control}
            name={`items.${index}.type`}
            render={({ field: f }) => (
              <FormItem>
                <FormLabel className={fieldLabelClass}>نوع البند</FormLabel>
                <Select onValueChange={f.onChange} value={f.value}>
                  <FormControl>
                    <SelectTrigger className={cn(controlClass, "h-10")}>
                      <SelectValue placeholder="اختر النوع" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    <SelectItem value="rent">إيجار</SelectItem>
                    <SelectItem value="buy">شراء</SelectItem>
                  </SelectContent>
                </Select>
                <FormMessage />
              </FormItem>
            )}
          />
        </div>

        {itemType === "rent" && (
          <FormField
            control={control}
            name={`items.${index}.days_of_rent`}
            render={({ field: f }) => (
              <FormItem className="max-w-xs">
                <FormLabel className={fieldLabelClass}>عدد أيام الإيجار</FormLabel>
                <FormControl>
                  <Input
                    className={controlClass}
                    placeholder="1"
                    value={f.value ?? ""}
                    onChange={(e) => {
                      const val = e.target.value.replace(/[^0-9]/g, "");
                      f.onChange(val ? parseInt(val, 10) : 1);
                    }}
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        )}

        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField
            control={control}
            name={`items.${index}.occasion_datetime`}
            render={({ field: f }) => (
              <FormItem>
                <FormLabel className={fieldLabelClass}>تاريخ ووقت المناسبة</FormLabel>
                <FormControl>
                  <SimpleDateTimePicker
                    value={f.value}
                    onChange={f.onChange}
                    placeholder="اختر التاريخ والوقت"
                    minDate={new Date()}
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />

          <FormField
            control={control}
            name={`items.${index}.delivery_date`}
            render={({ field: f }) => (
              <FormItem>
                <FormLabel className={fieldLabelClass}>موعد استرجاع الصنف</FormLabel>
                <FormControl>
                  {isLoadingUnavailableDaysRanges ? (
                    <div className="flex h-10 items-center gap-2 text-sm text-slate-500">
                      <Loader2Icon className="h-4 w-4 animate-spin" />
                      جاري التحميل…
                    </div>
                  ) : (
                    <DatePicker
                      value={f.value}
                      onChange={f.onChange}
                      placeholder="اختر الموعد"
                      allowPastDates={false}
                      disabledRanges={getClothesUnavailableDaysRanges(cloth?.id ?? 0)}
                    />
                  )}
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        </div>

        <Separator className="bg-slate-100" />

        <FormField
          control={control}
          name={`items.${index}.has_discount`}
          render={({ field: f }) => (
            <FormItem className="flex flex-row items-center justify-between rounded-lg border border-slate-200 bg-slate-50/40 p-4">
              <div className="space-y-0.5 pr-2">
                <FormLabel className="text-sm font-medium text-slate-800">
                  خصم على هذه القطعة
                </FormLabel>
                <p className="text-xs text-slate-500">يُطبَّق على سعر البند فقط</p>
              </div>
              <FormControl>
                <Switch
                  dir="ltr"
                  checked={f.value}
                  onCheckedChange={f.onChange}
                />
              </FormControl>
            </FormItem>
          )}
        />

        {itemHasDiscount && (
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <FormField
              control={control}
              name={`items.${index}.discount_type`}
              render={({ field: f }) => (
                <FormItem>
                  <FormLabel className={fieldLabelClass}>نوع الخصم</FormLabel>
                  <Select onValueChange={f.onChange} value={f.value}>
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
              control={control}
              name={`items.${index}.discount_value`}
              render={({ field: f }) => (
                <FormItem>
                  <FormLabel className={fieldLabelClass}>قيمة الخصم</FormLabel>
                  <FormControl>
                    <Input
                      className={controlClass}
                      placeholder="0.00"
                      value={f.value ?? ""}
                      onChange={(e) => {
                        const val = e.target.value.replace(/[^0-9.]/g, "");
                        f.onChange(val === "" ? 0 : parseFloat(val) || 0);
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
          control={control}
          name={`items.${index}.notes`}
          render={({ field: f }) => (
            <FormItem>
              <FormLabel className={fieldLabelClass}>ملاحظات القطعة</FormLabel>
              <FormControl>
                <Textarea
                  placeholder="ملاحظات اختيارية…"
                  className={cn(controlClass, "min-h-[88px] resize-none")}
                  rows={3}
                  {...f}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
      </div>
    </div>
  );
}
