import { useGetOrderDetailsQueryOptions } from "@/api/v2/orders/orders.hooks";
import type { TOrderItem } from "@/api/v2/orders/orders.types";
import {
  getItemSubcategoryDisplay,
  getOrderCurrencyInfo,
  getOrderTypeLabel,
  getStatusLabel,
  getStatusVariant,
} from "@/api/v2/orders/order.utils";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { formatDate } from "@/utils/formatDate";
import { useQuery } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { Link, useParams } from "react-router";
import OrderDetailsSkeleton from "./OrderDetailsSkeleton";

const labelClass =
  "text-[11px] font-semibold uppercase tracking-wide text-slate-500";

const getDiscountTypeLabel = (type?: string | null) => {
  if (!type || type === "none") return "—";
  if (type === "percentage") return "نسبة مئوية";
  if (type === "fixed") return "مبلغ ثابت";
  return type;
};

function parseMoney(v: string | number | null | undefined): number {
  if (v === null || v === undefined) return NaN;
  const n = typeof v === "number" ? v : parseFloat(String(v).replace(/,/g, ""));
  return Number.isFinite(n) ? n : NaN;
}

function SectionCard({
  title,
  subtitle,
  iconClass,
  children,
}: {
  title: string;
  subtitle?: string;
  iconClass: string;
  children: ReactNode;
}) {
  return (
    <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <div className="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
          <i className={cn(iconClass, "text-lg")} aria-hidden />
        </div>
        <div className="min-w-0">
          <h2 className="text-sm font-semibold text-slate-800">{title}</h2>
          {subtitle ? <p className="text-xs text-slate-500">{subtitle}</p> : null}
        </div>
      </div>
      <div className="px-5 py-4">{children}</div>
    </div>
  );
}

function FieldRow({
  label,
  value,
  showWhenEmpty = false,
}: {
  label: string;
  value: string | number | null | undefined;
  showWhenEmpty?: boolean;
}) {
  const str =
    value === null || value === undefined ? "" : String(value).trim();
  if (!showWhenEmpty && !str) return null;
  return (
    <div className="space-y-1">
      <p className={labelClass}>{label}</p>
      <p className="text-sm font-medium text-slate-800">{str || "—"}</p>
    </div>
  );
}

function MeasurementsSection({ item }: { item: TOrderItem }) {
  const fields: { key: keyof TOrderItem; label: string }[] = [
    { key: "sleeve_length", label: "طول الكم" },
    { key: "forearm", label: "الزند" },
    { key: "shoulder_width", label: "عرض الكتف" },
    { key: "cuffs", label: "الإسوار" },
    { key: "waist", label: "الوسط" },
    { key: "chest_length", label: "طول الصدر" },
    { key: "total_length", label: "الطول الكلي" },
    { key: "hinch", label: "الهش" },
    { key: "dress_size", label: "مقاس الفستان" },
  ];
  const hasAny = fields.some(
    (f) => item[f.key] != null && String(item[f.key]).trim() !== ""
  );
  if (!hasAny) return null;
  return (
    <SectionCard
      title="مقاسات الطلب / التفصيل"
      subtitle="المقاسات المرسلة مع هذا المنتج في الطلب"
      iconClass="ri-ruler-line"
    >
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
        {fields.map(({ key, label }) => (
          <FieldRow
            key={key}
            label={label}
            value={item[key] as string | null | undefined}
          />
        ))}
      </div>
    </SectionCard>
  );
}

function ProductSizesSection({ item }: { item: TOrderItem }) {
  const hasAny =
    (item.breast_size != null && String(item.breast_size).trim() !== "") ||
    (item.waist_size != null && String(item.waist_size).trim() !== "") ||
    (item.sleeve_size != null && String(item.sleeve_size).trim() !== "");
  if (!hasAny) return null;
  return (
    <SectionCard
      title="مقاسات المنتج"
      subtitle="صدر، وسط، كم — كما في بيانات القطعة"
      iconClass="ri-shirt-line"
    >
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <FieldRow label="مقاس الصدر" value={item.breast_size} />
        <FieldRow label="مقاس الوسط" value={item.waist_size} />
        <FieldRow label="مقاس الكم" value={item.sleeve_size} />
      </div>
    </SectionCard>
  );
}

function OrderItemDetails() {
  const { orderId: orderIdParam, itemId: itemIdParam } = useParams<{
    orderId: string;
    itemId: string;
  }>();
  const orderId = orderIdParam ? parseInt(orderIdParam, 10) : 0;
  const itemId = itemIdParam ? parseInt(itemIdParam, 10) : 0;

  const { data: order, isPending } = useQuery({
    ...useGetOrderDetailsQueryOptions(orderId),
    enabled: !!orderId,
  });

  const item = order?.items?.find((i) => i.id === itemId);
  const sym = order ? getOrderCurrencyInfo(order).currency_symbol : "ج.م";

  if (isPending) {
    return <OrderDetailsSkeleton />;
  }

  if (!order || !item) {
    return (
      <div className="p-6" dir="rtl">
        <nav className="mb-4 flex flex-wrap items-center gap-2 text-xs text-slate-400">
          <Link to="/orders/list" className="hover:text-slate-600">
            قائمة الطلبات
          </Link>
          {orderId ? (
            <>
              <i className="ri-arrow-left-s-line text-slate-300" aria-hidden />
              <Link to={`/orders/${orderId}`} className="hover:text-slate-600">
                الطلب #{orderId}
              </Link>
            </>
          ) : null}
        </nav>
        <div className="rounded-lg border border-slate-200 bg-white px-6 py-12 text-center">
          <i
            className="ri-error-warning-line mb-3 text-4xl text-slate-300"
            aria-hidden
          />
          <p className="text-sm text-slate-600">المنتج غير موجود أو تمت إزالته.</p>
          <Button variant="outline" className="mt-6 border-slate-200" asChild>
            <Link to={orderId ? `/orders/${orderId}` : "/orders/list"}>
              <i className="ri-arrow-right-line text-base" aria-hidden />
              {orderId ? "العودة لتفاصيل الطلب" : "قائمة الطلبات"}
            </Link>
          </Button>
        </div>
      </div>
    );
  }

  const subcategory =
    getItemSubcategoryDisplay(item as Record<string, unknown>) || null;
  const priceN = parseMoney(item.price);
  const paidN = parseMoney(item.item_paid);
  const remN = parseMoney(item.item_remaining);

  return (
    <div className="min-h-screen bg-slate-50/80" dir="rtl">
      <div className="mx-auto w-full max-w-5xl space-y-5 p-6">
        <nav className="flex flex-wrap items-center gap-2 text-xs text-slate-400">
          <Link to="/orders/list" className="hover:text-slate-600">
            قائمة الطلبات
          </Link>
          <i className="ri-arrow-left-s-line text-slate-300" aria-hidden />
          <Link
            to={`/orders/${order.id}`}
            className="hover:text-slate-600"
          >
            الطلب #{order.id}
          </Link>
          <i className="ri-arrow-left-s-line text-slate-300" aria-hidden />
          <span className="font-medium text-slate-600">
            {item.code}
            {item.name ? ` — ${item.name}` : ""}
          </span>
        </nav>

        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div className="flex items-start gap-3">
            <Link
              to={`/orders/${order.id}`}
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-50"
              aria-label="العودة لتفاصيل الطلب"
            >
              <i className="ri-arrow-right-line text-xl" aria-hidden />
            </Link>
            <div>
              <div className="flex flex-wrap items-center gap-2">
                <h1 className="text-xl font-bold text-slate-800">
                  {item.name ?? item.code}
                </h1>
                <Badge
                  variant="secondary"
                  className={cn(
                    "rounded-full px-2.5 py-0.5 text-xs font-medium",
                    getStatusVariant(item.status)
                  )}
                >
                  {getStatusLabel(item.status)}
                </Badge>
              </div>
              <p className="mt-1 text-sm text-slate-500">
                كود <span className="font-mono font-semibold text-slate-700">{item.code}</span>
                <span className="mx-2 text-slate-300">·</span>
                طلب #{order.id}
                {subcategory ? (
                  <>
                    <span className="mx-2 text-slate-300">·</span>
                    {subcategory}
                  </>
                ) : null}
              </p>
            </div>
          </div>
          <Button
            variant="outline"
            className="h-10 shrink-0 rounded-lg border-slate-200 bg-white text-slate-700 shadow-none hover:bg-slate-50"
            asChild
          >
            <Link to={`/orders/${order.id}`}>
              <i className="ri-file-list-3-line text-lg" aria-hidden />
              تفاصيل الطلب
            </Link>
          </Button>
        </div>

        <SectionCard
          title="معلومات أساسية"
          subtitle="النوع، الكمية، القابلية للإرجاع، الطوابع الزمنية"
          iconClass="ri-information-line"
        >
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <FieldRow label="رقم البند" value={item.id} showWhenEmpty />
            <FieldRow label="الكود" value={item.code} showWhenEmpty />
            <FieldRow label="الوصف" value={item.description} />
            <FieldRow
              label="نوع البند"
              value={getOrderTypeLabel(item.type)}
              showWhenEmpty
            />
            <FieldRow label="الكمية" value={item.quantity} showWhenEmpty />
            <div className="space-y-1">
              <p className={labelClass}>قابل للإرجاع</p>
              <p className="text-sm font-medium text-slate-800">
                {item.returnable === 1 ? (
                  <span className="inline-flex items-center gap-1 text-emerald-700">
                    <i className="ri-checkbox-circle-line" aria-hidden />
                    نعم
                  </span>
                ) : (
                  <span className="inline-flex items-center gap-1 text-slate-500">
                    <i className="ri-close-circle-line" aria-hidden />
                    لا
                  </span>
                )}
              </p>
            </div>
            <FieldRow label="تاريخ الإنشاء" value={formatDate(item.created_at)} />
            <FieldRow label="تاريخ التحديث" value={formatDate(item.updated_at)} />
          </div>
        </SectionCard>

        <ProductSizesSection item={item} />
        <MeasurementsSection item={item} />

        <SectionCard
          title="المبالغ والخصم"
          subtitle="السعر والمدفوع والمتبقي لهذا البند"
          iconClass="ri-money-dollar-circle-line"
        >
          <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
              <p className={labelClass}>سعر البند</p>
              <p className="mt-1 text-lg font-bold tabular-nums text-slate-800">
                {Number.isFinite(priceN)
                  ? priceN.toLocaleString("ar-EG")
                  : item.price || "—"}{" "}
                <span className="text-sm font-semibold text-slate-500">{sym}</span>
              </p>
            </div>
            <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
              <p className={labelClass}>المدفوع</p>
              <p className="mt-1 text-lg font-bold tabular-nums text-slate-800">
                {Number.isFinite(paidN)
                  ? paidN.toLocaleString("ar-EG")
                  : item.item_paid || "—"}{" "}
                <span className="text-sm font-semibold text-slate-500">{sym}</span>
              </p>
            </div>
            <div className="rounded-lg border border-emerald-100 bg-emerald-50/70 px-4 py-3">
              <p className="text-[11px] font-semibold uppercase tracking-wide text-emerald-800/90">
                المتبقي
              </p>
              <p className="mt-1 text-lg font-bold tabular-nums text-emerald-900">
                {Number.isFinite(remN)
                  ? remN.toLocaleString("ar-EG")
                  : item.item_remaining || "—"}{" "}
                <span className="text-sm font-semibold text-emerald-800">{sym}</span>
              </p>
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 sm:grid-cols-3">
            <FieldRow
              label="نوع الخصم"
              value={getDiscountTypeLabel(item.discount_type)}
            />
            <FieldRow
              label="قيمة الخصم"
              value={
                item.discount_value != null && String(item.discount_value).trim() !== ""
                  ? item.discount_type === "percentage"
                    ? `${item.discount_value}%`
                    : `${item.discount_value} ${sym}`
                  : null
              }
            />
          </div>
        </SectionCard>

        {(item.days_of_rent != null ||
          item.occasion_datetime ||
          item.delivery_date) && (
          <SectionCard
            title="تواريخ الإيجار"
            subtitle="أيام الإيجار والمواعيد المرتبطة بهذا البند"
            iconClass="ri-calendar-event-line"
          >
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
              <FieldRow label="عدد أيام الإيجار" value={item.days_of_rent} />
              <FieldRow
                label="موعد الفرح"
                value={
                  item.occasion_datetime
                    ? formatDate(item.occasion_datetime)
                    : null
                }
              />
              <FieldRow
                label="موعد الاسترجاع"
                value={
                  item.delivery_date ? formatDate(item.delivery_date) : null
                }
              />
            </div>
          </SectionCard>
        )}

        {(item.factory_status != null ||
          item.factory_rejection_reason != null ||
          item.factory_notes != null) && (
          <SectionCard
            title="المصنع / التفصيل"
            subtitle="حالة التصنيع والمواعيد"
            iconClass="ri-building-2-line"
          >
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
              <FieldRow label="حالة المصنع" value={item.factory_status} />
              <FieldRow
                label="سبب الرفض"
                value={item.factory_rejection_reason}
              />
              <FieldRow label="ملاحظات المصنع" value={item.factory_notes} />
              <FieldRow
                label="تاريخ القبول"
                value={
                  item.factory_accepted_at
                    ? formatDate(item.factory_accepted_at)
                    : null
                }
              />
              <FieldRow
                label="تاريخ الرفض"
                value={
                  item.factory_rejected_at
                    ? formatDate(item.factory_rejected_at)
                    : null
                }
              />
              <FieldRow
                label="التسليم المتوقع"
                value={
                  item.factory_expected_delivery_date
                    ? formatDate(item.factory_expected_delivery_date)
                    : null
                }
              />
              <FieldRow
                label="التسليم الفعلي"
                value={
                  item.factory_delivered_at
                    ? formatDate(item.factory_delivered_at)
                    : null
                }
              />
            </div>
          </SectionCard>
        )}

        {item.notes != null && String(item.notes).trim() !== "" && (
          <div className="flex items-start gap-3 rounded-lg border border-amber-100 bg-amber-50/90 p-4">
            <i
              className="ri-sticky-note-line mt-0.5 text-lg text-amber-600"
              aria-hidden
            />
            <div>
              <p className="mb-1 text-xs font-semibold text-amber-800">
                ملاحظات القطعة
              </p>
              <p className="whitespace-pre-wrap text-sm text-amber-950/90">
                {item.notes}
              </p>
            </div>
          </div>
        )}

        <div className="flex justify-end border-t border-slate-200/80 pt-4">
          <Button
            className="h-10 rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-emerald-800 shadow-none hover:bg-emerald-100"
            variant="outline"
            asChild
          >
            <Link to={`/orders/${order.id}`}>
              <i className="ri-arrow-right-line text-lg" aria-hidden />
              العودة لتفاصيل الطلب
            </Link>
          </Button>
        </div>
      </div>
    </div>
  );
}

export default OrderItemDetails;
