import { useParams, Link } from "react-router";
import { useQuery } from "@tanstack/react-query";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import {
  ArrowLeft,
  Package,
  Hash,
  Building2,
  Ruler,
  Banknote,
  FileText,
  Tag,
  MapPin,
  ShoppingBag,
  ExternalLink,
  Shirt,
  Scissors,
  CreditCard,
} from "lucide-react";
import {
  useGetClothByIdQueryOptions,
  useGetClothOrdersQueryOptions,
} from "@/api/v2/clothes/clothes.hooks";
import { TClothResponse } from "@/api/v2/clothes/clothes.types";
import { formatDate } from "@/utils/formatDate";
import { cn } from "@/lib/utils";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { getOrderTypeLabel, getStatusLabel } from "@/api/v2/orders/order.utils";

function getProductName(cloth: TClothResponse): string {
  const categoryName =
    cloth.category_name ??
    (cloth as { category?: { name?: string } }).category?.name ??
    "";
  const subNames = cloth.subcategory_names?.length
    ? cloth.subcategory_names.join("، ")
    : (cloth as { subcategories?: { name?: string }[] }).subcategories
        ?.map((s) => s.name ?? "")
        .filter(Boolean)
        .join("، ") ?? "";
  if (categoryName && subNames) return `${categoryName} (${subNames})`;
  if (categoryName) return categoryName;
  if (subNames) return subNames;
  return "—";
}

function getEntityDisplay(cloth: TClothResponse): string {
  if (cloth.entity_name?.trim()) return cloth.entity_name;
  const typeLabel =
    cloth.entity_type === "branch"
      ? "فرع"
      : cloth.entity_type === "factory"
        ? "مصنع"
        : "ورشة";
  return `${typeLabel} (${cloth.entity_id})`;
}

const STATUS_LABELS: Record<string, string> = {
  ready_for_rent: "متوفر",
  rented: "محجوز",
  damaged: "تالف",
  burned: "محترق",
  scratched: "مخدوش",
  repairing: "قيد الصيانة",
  die: "ميت",
};

function getClothStatusLabel(status: string): string {
  return STATUS_LABELS[status] ?? status;
}

function getStatusVariant(
  status: string
): "success" | "info" | "warning" | "destructive" | "secondary" {
  switch (status) {
    case "ready_for_rent":
      return "success";
    case "rented":
      return "info";
    case "damaged":
    case "burned":
    case "scratched":
    case "repairing":
      return "warning";
    case "die":
      return "destructive";
    default:
      return "secondary";
  }
}

function StatBox({
  icon: Icon,
  label,
  value,
  accent,
}: {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  value: React.ReactNode;
  accent?: boolean;
}) {
  return (
    <div
      className={cn(
        "rounded-xl border bg-card p-4 transition-colors",
        accent && "border-primary/20 bg-primary/5"
      )}
    >
      <div className="flex items-center gap-2 text-muted-foreground mb-1.5">
        <Icon className="h-4 w-4 shrink-0" />
        <span className="text-xs font-medium uppercase tracking-wider">{label}</span>
      </div>
      <div className={cn("text-sm font-semibold text-foreground", accent && "text-primary text-base")}>
        {value}
      </div>
    </div>
  );
}

function DetailRow({
  icon: Icon,
  label,
  value,
  mono,
}: {
  icon?: React.ComponentType<{ className?: string }>;
  label: string;
  value: React.ReactNode;
  mono?: boolean;
}) {
  return (
    <div className="flex flex-col gap-1 py-3 border-b border-border/50 last:border-0 sm:flex-row sm:items-center sm:justify-between">
      <div className="flex items-center gap-2 text-muted-foreground">
        {Icon && <Icon className="h-4 w-4 shrink-0 opacity-80" />}
        <span className="text-sm font-medium">{label}</span>
      </div>
      <div className={cn("text-sm font-medium text-foreground sm:text-end", mono && "font-mono")}>
        {value}
      </div>
    </div>
  );
}

function ClothDetailsSkeleton() {
  return (
    <div className="space-y-8">
      <div className="flex items-center gap-3">
        <Skeleton className="h-9 w-9 rounded-lg" />
        <div className="flex gap-2">
          <Skeleton className="h-4 w-20" />
          <Skeleton className="h-4 w-24" />
          <Skeleton className="h-4 w-32" />
        </div>
      </div>
      <div className="rounded-2xl border bg-card overflow-hidden">
        <div className="h-32 bg-muted/40" />
        <div className="p-6 sm:p-8 -mt-12 relative">
          <Skeleton className="h-16 w-16 rounded-2xl absolute top-0 right-6" />
          <div className="pt-4 space-y-2">
            <Skeleton className="h-8 w-3/4 max-w-md" />
            <Skeleton className="h-5 w-48" />
          </div>
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
            {[1, 2, 3, 4].map((i) => (
              <Skeleton key={i} className="h-20 rounded-xl" />
            ))}
          </div>
        </div>
      </div>
      <div className="grid gap-6 lg:grid-cols-2">
        <Skeleton className="h-64 rounded-2xl" />
        <Skeleton className="h-64 rounded-2xl" />
      </div>
    </div>
  );
}

export default function ClothDetails() {
  const { id } = useParams<{ id: string }>();
  const clothId = id ? parseInt(id, 10) : 0;

  const { data: cloth, isPending, isError, error } = useQuery({
    ...useGetClothByIdQueryOptions(clothId),
    enabled: !!clothId,
  });

  const { data: ordersData, isPending: ordersPending } = useQuery({
    ...useGetClothOrdersQueryOptions(clothId),
    enabled: !!clothId && !!cloth,
  });

  if (isError) {
    return (
      <div dir="rtl" className="min-h-[60vh] flex flex-col">
        <nav className="flex items-center gap-2 text-sm mb-8">
          <Button variant="ghost" size="sm" className="gap-1.5 -mr-2" asChild>
            <Link to="/clothes/list">
              <ArrowLeft className="h-4 w-4" />
              رجوع
            </Link>
          </Button>
          <span className="text-muted-foreground">/</span>
          <span className="text-foreground font-medium">تفاصيل المنتج</span>
        </nav>
        <Card className="flex-1 border-destructive/20 bg-destructive/5 flex flex-col items-center justify-center rounded-2xl">
          <CardContent className="py-16 px-6 text-center max-w-sm">
            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-destructive/10 mb-5">
              <Package className="h-8 w-8 text-destructive" />
            </div>
            <h2 className="font-semibold text-lg text-foreground mb-2">تعذر تحميل المنتج</h2>
            <p className="text-sm text-muted-foreground mb-6">
              {error?.message ?? "حدث خطأ أثناء جلب البيانات. الرجاء المحاولة لاحقاً."}
            </p>
            <Button asChild>
              <Link to="/clothes/list" className="gap-2">
                <ArrowLeft className="h-4 w-4" />
                العودة للقائمة
              </Link>
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  const rawPrice = cloth ? (cloth as { price?: number | string }).price : null;
  const priceNum =
    rawPrice != null && rawPrice !== "" ? Number(String(rawPrice).replace(/,/g, "")) : null;
  const priceDisplay = priceNum != null ? priceNum.toLocaleString("en-US") : null;

  return (
    <div dir="rtl" className="space-y-8 pb-4">
      {/* Breadcrumb */}
      <nav className="flex flex-wrap items-center gap-2 text-sm">
        <Button variant="ghost" size="sm" className="gap-1.5 -mr-2 text-muted-foreground hover:text-foreground" asChild>
          <Link to="/clothes/list">
            <ArrowLeft className="h-4 w-4" />
            المنتجات
          </Link>
        </Button>
        <span className="text-muted-foreground">/</span>
        <span className="text-foreground font-medium truncate max-w-[200px] sm:max-w-none" title={cloth ? getProductName(cloth) : undefined}>
          {cloth ? `${cloth.code} - ${getProductName(cloth)}` : "تفاصيل المنتج"}
        </span>
      </nav>

      {isPending ? (
        <ClothDetailsSkeleton />
      ) : !cloth ? (
        <Card className="rounded-2xl overflow-hidden">
          <CardContent className="flex flex-col items-center justify-center py-20 text-center">
            <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-muted mb-5">
              <Package className="h-10 w-10 text-muted-foreground" />
            </div>
            <h2 className="font-semibold text-lg text-foreground">لم يتم العثور على المنتج</h2>
            <p className="text-sm text-muted-foreground mt-1 max-w-sm">
              قد يكون المحتوى محذوفاً أو المعرف غير صحيح.
            </p>
            <Button variant="outline" className="mt-6 gap-2" asChild>
              <Link to="/clothes/list">
                <ArrowLeft className="h-4 w-4" />
                العودة للقائمة
              </Link>
            </Button>
          </CardContent>
        </Card>
      ) : (
        <>
          
          <Card
            className={cn(
              "rounded-2xl border-2 overflow-hidden shadow-sm",
              cloth.status === "ready_for_rent" && "border-l-4 border-l-emerald-500/60",
              cloth.status === "rented" && "border-l-4 border-l-blue-500/60",
              (cloth.status === "repairing" || cloth.status === "damaged" || cloth.status === "burned" || cloth.status === "scratched") &&
                "border-l-4 border-l-amber-500/60",
              cloth.status === "die" && "border-l-4 border-l-rose-500/60"
            )}
          >
            <div className="h-20 sm:h-24 bg-gradient-to-b from-muted/60 to-muted/20" />
            <CardContent className="px-6 sm:px-8 pb-6 sm:pb-8 pt-0">
              
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 -mt-10 relative">
                <div className="flex items-start gap-4">
                  <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-background border-2 border-border shadow-md">
                    <Package className="h-8 w-8 text-primary" />
                  </div>
                  <div className="space-y-1 min-w-0">
                    <h1 className="text-lg sm:text-xl font-bold text-foreground truncate" title={getProductName(cloth)}>
                      {getProductName(cloth)}
                    </h1>
                    <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                      <span className="font-mono font-medium text-foreground bg-muted/50 px-2 py-0.5 rounded">
                        {cloth.code}
                      </span>
                      <span>·</span>
                      <span>#{cloth.id}</span>
                    </div>
                  </div>
                </div>
                <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                  {priceDisplay != null && (
                    <div className="inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-3 py-1.5 text-primary font-bold tabular-nums text-sm">
                      <Banknote className="h-4 w-4" />
                      {priceDisplay} ج.م
                    </div>
                  )}
                  <Badge
                    variant={getStatusVariant(cloth.status)}
                    className="text-xs sm:text-sm px-3 py-1 rounded-full font-medium"
                  >
                    {getClothStatusLabel(cloth.status)}
                  </Badge>
                </div>
              </div>

              
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4 mt-6">
                <StatBox icon={MapPin} label="الفرع" value={getEntityDisplay(cloth)} />
                <StatBox
                  icon={Banknote}
                  label="السعر"
                  value={priceDisplay != null ? `${priceDisplay} ج.م` : "—"}
                  accent={priceDisplay != null}
                />
                {ordersPending ? (
                  <>
                    {[1, 2, 3, 4, 5].map((i) => (
                      <Skeleton key={i} className="h-[72px] rounded-xl" />
                    ))}
                  </>
                ) : (
                  ordersData?.meta && (
                    <>
                      <StatBox
                        icon={Shirt}
                        label="إيجار"
                        value={ordersData.meta.rent_count?.toLocaleString("en-US") ?? "0"}
                      />
                      <StatBox
                        icon={ShoppingBag}
                        label="شراء"
                        value={ordersData.meta.buy_count?.toLocaleString("en-US") ?? "0"}
                      />
                      <StatBox
                        icon={Scissors}
                        label="تفصيل"
                        value={ordersData.meta.tailoring_count?.toLocaleString("en-US") ?? "0"}
                      />
                      {ordersData.meta.payment && (
                        <>
                          <StatBox
                            icon={CreditCard}
                            label="مدفوع بالكامل"
                            value={ordersData.meta.payment.orders_fully_paid?.toLocaleString("en-US") ?? "0"}
                          />
                          <StatBox
                            icon={CreditCard}
                            label="له رصيد"
                            value={ordersData.meta.payment.orders_with_balance?.toLocaleString("en-US") ?? "0"}
                          />
                          <StatBox
                            icon={Banknote}
                            label="إجمالي المبيعات"
                            value={`${(ordersData.meta.payment.total_amount ?? 0).toLocaleString("en-US")} ج.م`}
                            accent
                          />
                          <StatBox
                            icon={Banknote}
                            label="إجمالي المدفوع"
                            value={`${(ordersData.meta.payment.total_paid ?? 0).toLocaleString("en-US")} ج.م`}
                          />
                          <StatBox
                            icon={Banknote}
                            label="إجمالي المتبقي"
                            value={`${(ordersData.meta.payment.total_remaining ?? 0).toLocaleString("en-US")} ج.م`}
                          />
                        </>
                      )}
                    </>
                  )
                )}
              </div>
            </CardContent>
          </Card>

          
          <div className="grid gap-6 lg:grid-cols-2">
            <Card className="rounded-2xl overflow-hidden border-r-4 border-r-primary/20">
              <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2">
                  <Tag className="h-4 w-4 text-primary" />
                  المعلومات الأساسية
                </CardTitle>
                <CardDescription>بيانات تعريف المنتج والموقع</CardDescription>
              </CardHeader>
              <CardContent className="space-y-0">
                <DetailRow icon={Hash} label="الكود" value={cloth.code} mono />
                <DetailRow label="اسم المنتج (الفئة)" value={getProductName(cloth)} />
                <DetailRow icon={Building2} label="الفرع / الموقع" value={getEntityDisplay(cloth)} />
                <DetailRow label="تاريخ الإنشاء" value={formatDate(cloth.created_at)} />
              </CardContent>
            </Card>

            <Card className="rounded-2xl overflow-hidden border-r-4 border-r-primary/20">
              <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2">
                  <Ruler className="h-4 w-4 text-primary" />
                  المقاسات والسعر
                </CardTitle>
                <CardDescription>مقاييس المنتج وقيمته</CardDescription>
              </CardHeader>
              <CardContent className="space-y-0">
                <DetailRow label="مقاس الصدر" value={cloth.breast_size?.trim() || "—"} />
                <DetailRow label="مقاس الخصر" value={cloth.waist_size?.trim() || "—"} />
                <DetailRow label="مقاس الكم" value={cloth.sleeve_size?.trim() || "—"} />
                {cloth.measurements?.trim() && (
                  <DetailRow label="مقاسات إضافية" value={cloth.measurements.trim()} />
                )}
                <DetailRow
                  icon={Banknote}
                  label="السعر"
                  value={
                    priceDisplay != null ? (
                      <span className="text-primary font-semibold tabular-nums">{priceDisplay} ج.م</span>
                    ) : (
                      "—"
                    )
                  }
                />
              </CardContent>
            </Card>
          </div>

          
          <Card className="rounded-2xl overflow-hidden border-r-4 border-r-primary/20">
            <CardHeader className="pb-2">
              <CardTitle className="text-base flex items-center gap-2">
                <ShoppingBag className="h-4 w-4 text-primary" />
                تأجيرات واستخدامات المنتج
              </CardTitle>
              <CardDescription>
                الطلبات التي تضمنت هذا المنتج
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              
              {(cloth.notes?.trim() || cloth.description?.trim()) && (
                <div>
                  <h4 className="text-sm font-semibold text-muted-foreground mb-3 flex items-center gap-2">
                    <FileText className="h-3.5 w-3.5" />
                    الملاحظات والوصف
                  </h4>
                  <div className="rounded-xl bg-muted/30 p-5 text-sm leading-relaxed whitespace-pre-wrap text-foreground">
                    {[cloth.notes?.trim(), cloth.description?.trim()]
                      .filter(Boolean)
                      .join("\n\n")}
                  </div>
                </div>
              )}

              
              <div>
              {ordersPending ? (
                <div className="space-y-2">
                  {[1, 2, 3].map((i) => (
                    <Skeleton key={i} className="h-12 w-full" />
                  ))}
                </div>
              ) : !ordersData?.orders?.length ? (
                <div className="rounded-xl bg-muted/30 p-8 text-center">
                  <ShoppingBag className="h-10 w-10 mx-auto text-muted-foreground mb-3" />
                  <p className="text-sm text-muted-foreground">
                    لا توجد تأجيرات أو استخدامات مسجلة لهذا المنتج
                  </p>
                </div>
              ) : (
                <div className="rounded-xl border overflow-hidden">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="text-right">#</TableHead>
                        <TableHead className="text-right">العميل</TableHead>
                        <TableHead className="text-right">المدفوع</TableHead>
                        <TableHead className="text-right">الباقي</TableHead>
                        <TableHead className="text-right">مدة الاستئجار</TableHead>
                        <TableHead className="text-right">تاريخ الاستئجار</TableHead>
                        <TableHead className="text-right">النوع</TableHead>
                        <TableHead className="text-right">الحالة</TableHead>
                        <TableHead className="text-right w-16"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {ordersData.orders.map((order) => {
                        const clientName =
                          order.client?.name?.trim() ||
                          [order.client?.first_name, order.client?.last_name]
                            .filter(Boolean)
                            .join(" ")
                            .trim() ||
                          "—";
                        const paidNum = parseFloat(order.paid) || 0;
                        const remainingNum = parseFloat(order.remaining) || 0;
                        const paidDisplay = paidNum.toLocaleString("en-US", {
                          minimumFractionDigits: 0,
                          maximumFractionDigits: 2,
                        });
                        const remainingDisplay = remainingNum.toLocaleString("en-US", {
                          minimumFractionDigits: 0,
                          maximumFractionDigits: 2,
                        });
                        return (
                          <TableRow key={order.id}>
                            <TableCell className="font-mono font-medium">
                              #{order.id}
                            </TableCell>
                            <TableCell>{clientName}</TableCell>
                            <TableCell className="tabular-nums">
                              {paidDisplay} ج.م
                            </TableCell>
                            <TableCell className="tabular-nums">
                              {remainingDisplay} ج.م
                            </TableCell>
                            <TableCell>
                              {order.days_of_rent != null
                                ? `${order.days_of_rent} يوم`
                                : "—"}
                            </TableCell>
                            <TableCell className="text-muted-foreground">
                              {order.delivery_date
                                ? formatDate(order.delivery_date)
                                : "—"}
                            </TableCell>
                            <TableCell>
                              {getOrderTypeLabel(
                                (order.pivot?.type ?? order.status) as "rent" | "buy" | "tailoring" | "mixed" | "unknown"
                              )}
                            </TableCell>
                            <TableCell>
                              <Badge
                                variant="secondary"
                                className={cn(
                                  order.status === "paid" &&
                                    "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
                                  order.status === "partially_paid" &&
                                    "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400",
                                  order.status === "canceled" &&
                                    "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                                )}
                              >
                                {getStatusLabel(order.status)}
                              </Badge>
                            </TableCell>
                            <TableCell>
                              <Button variant="ghost" size="sm" asChild>
                                <Link
                                  to={`/orders/${order.id}`}
                                  className="gap-1 text-primary hover:text-primary"
                                >
                                  <ExternalLink className="h-3.5 w-3.5" />
                                  عرض
                                </Link>
                              </Button>
                            </TableCell>
                          </TableRow>
                        );
                      })}
                    </TableBody>
                  </Table>
                  {ordersData.total > ordersData.orders.length && (
                    <div className="px-4 py-2 border-t bg-muted/20 text-sm text-muted-foreground text-center">
                      عرض {ordersData.orders.length} من {ordersData.total} طلب
                    </div>
                  )}
                </div>
              )}
              </div>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
}
