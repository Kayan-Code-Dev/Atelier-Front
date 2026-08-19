import { TOrder } from "@/api/v2/orders/orders.types";
import {
  getOrderCurrencyInfo,
  getOrderTotalsWithVat,
  getOrderTypeLabel,
  getItemSubcategoryDisplay,
  getStatusLabel,
  getStatusVariant,
} from "@/api/v2/orders/order.utils";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
  TableFooter,
} from "@/components/ui/table";
import { formatDate } from "@/utils/formatDate";
import { Link } from "react-router";
import { OrderCustodiesTable } from "./OrderCustodiesTable";
import { OrderPaymentsTable } from "./OrderPaymentsTable";
import type { ReactNode } from "react";

const getDiscountTypeLabel = (type?: TOrder["discount_type"]) => {
  if (!type || type === "none") return "لا يوجد";
  const labels: Record<"percentage" | "fixed", string> = {
    percentage: "نسبة مئوية",
    fixed: "مبلغ ثابت",
  };
  if (type === "percentage" || type === "fixed") {
    return labels[type];
  }
  return type;
};

export type OrderDetailsNonRentalViewProps = {
  orderData: TOrder;
  orderToolbar: ReactNode;
  onReturnItem: (itemId: number) => void;
};


export function OrderDetailsNonRentalView({
  orderData,
  orderToolbar,
  onReturnItem,
}: OrderDetailsNonRentalViewProps) {
  return (
    <>
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">تفاصيل الطلب</h1>
        {orderToolbar}
      </div>

      {(() => {
        const { currency_symbol } = getOrderCurrencyInfo(orderData);
        const {
          subtotal,
          vatAmount,
          totalWithVat,
          vatEnabled,
          vatType,
          vatValue,
        } = getOrderTotalsWithVat(orderData);
        const paid = Number(orderData.paid ?? 0);
        const remaining = Number(orderData.remaining ?? 0);

        const vatLabel =
          vatEnabled && vatValue
            ? vatType === "percentage"
              ? `${vatValue}%`
              : `${vatValue}`
            : null;

        return (
          <Card>
            <CardHeader>
              <CardTitle>معلومات الطلب</CardTitle>
              <CardDescription>البيانات الأساسية للطلب والمدفوعات</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">رقم الطلب</p>
                  <p className="text-lg font-semibold">#{orderData.id}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">نوع الطلب</p>
                  <p className="text-lg font-semibold">
                    {getOrderTypeLabel(orderData.order_type)}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">الحالة</p>
                  <Badge
                    variant="secondary"
                    className={getStatusVariant(orderData.status)}
                  >
                    {getStatusLabel(orderData.status)}
                  </Badge>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">السعر الإجمالي</p>
                  <p className="text-lg font-semibold">
                    <span className="inline-flex items-baseline gap-1 tabular-nums">
                      <span>{subtotal.toLocaleString()}</span>
                      <span>{currency_symbol}</span>
                    </span>
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">قيمة الضريبة</p>
                  <p className="text-lg font-semibold">
                    {vatEnabled && vatAmount > 0 ? (
                      <span className="inline-flex items-baseline gap-1 tabular-nums">
                        <span>{vatAmount.toLocaleString()}</span>
                        <span>{currency_symbol}</span>
                      </span>
                    ) : (
                      "لا يوجد"
                    )}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">السعر مع الضريبة</p>
                  <p className="text-lg font-semibold">
                    <span className="inline-flex items-baseline gap-1 tabular-nums">
                      <span>{totalWithVat.toLocaleString()}</span>
                      <span>{currency_symbol}</span>
                    </span>
                  </p>
                  {vatEnabled && vatLabel && (
                    <p className="mt-0.5 text-xs text-muted-foreground">
                      (قيمة الضريبة: {vatLabel}
                      {vatType === "percentage" ? "%" : ""})
                    </p>
                  )}
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">المدفوع</p>
                  <p className="text-lg font-semibold">
                    <span className="inline-flex items-baseline gap-1 tabular-nums">
                      <span>{paid.toLocaleString()}</span>
                      <span>{currency_symbol}</span>
                    </span>
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">المتبقي</p>
                  <p className="text-lg font-semibold">
                    <span className="inline-flex items-baseline gap-1 tabular-nums">
                      <span>{remaining.toLocaleString()}</span>
                      <span>{currency_symbol}</span>
                    </span>
                  </p>
                </div>
                {orderData.discount_type && orderData.discount_type !== "none" && (
                  <>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground">
                        نوع الخصم (على الطلب)
                      </p>
                      <p className="text-lg">{getDiscountTypeLabel(orderData.discount_type)}</p>
                    </div>
                    {orderData.discount_value && (
                      <div>
                        <p className="text-sm font-medium text-muted-foreground">
                          قيمة الخصم (على الطلب)
                        </p>
                        <p className="text-lg">
                          {orderData.discount_type === "percentage" ? (
                            `${orderData.discount_value ?? ""}%`
                          ) : (
                            <span className="inline-flex items-baseline gap-1 tabular-nums">
                              <span>{orderData.discount_value ?? ""}</span>
                              <span>{currency_symbol}</span>
                            </span>
                          )}
                        </p>
                      </div>
                    )}
                  </>
                )}
                <div>
                  <p className="text-sm font-medium text-muted-foreground">موعد الاستلام</p>
                  <p className="text-lg">
                    {orderData.delivery_date ? formatDate(orderData.delivery_date) : "-"}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">موعد الاسترجاع</p>
                  <p className="text-lg">
                    {orderData.visit_datetime ? formatDate(orderData.visit_datetime) : "-"}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">موعد الفرح</p>
                  <p className="text-lg">
                    {orderData.occasion_datetime
                      ? formatDate(orderData.occasion_datetime)
                      : "-"}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">تاريخ إنشاء الفاتورة</p>
                  <p className="text-lg">{formatDate(orderData.created_at)}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">عدد أيام الإيجار</p>
                  <p className="text-lg">
                    {orderData.days_of_rent != null ? orderData.days_of_rent : "-"}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        );
      })()}

      {orderData.inventory && (
        <Card>
          <CardHeader>
            <CardTitle>المخزن / الفرع</CardTitle>
            <CardDescription>معلومات المخزن المرتبط بالطلب</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
              <div>
                <p className="text-sm font-medium text-muted-foreground">اسم المخزن</p>
                <p className="text-lg font-medium">{orderData.inventory.name}</p>
              </div>
              {orderData.inventory.inventoriable && (
                <>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">كود الفرع</p>
                    <p className="text-lg">
                      {orderData.inventory.inventoriable.branch_code ?? "-"}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">اسم الفرع</p>
                    <p className="text-lg">{orderData.inventory.inventoriable.name}</p>
                  </div>
                </>
              )}
            </div>
          </CardContent>
        </Card>
      )}

      {orderData.client && (
        <Card>
          <CardHeader>
            <CardTitle>معلومات العميل</CardTitle>
            <CardDescription>بيانات العميل والعنوان</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-sm font-medium text-muted-foreground">الاسم</p>
                <p className="text-lg">{orderData.client.name}</p>
              </div>
              <div>
                <p className="text-sm font-medium text-muted-foreground">رقم العميل</p>
                <p className="text-lg">#{orderData.client.id}</p>
              </div>
              {orderData.client.address != null && (
                <>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">الشارع</p>
                    <p className="text-lg">{orderData.client.address.street}</p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">المبنى</p>
                    <p className="text-lg">{orderData.client.address.building}</p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">المدينة</p>
                    <p className="text-lg">{orderData.client.address.city_name}</p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">الدولة</p>
                    <p className="text-lg">{orderData.client.address.country_name}</p>
                  </div>
                  {orderData.client.address.notes && (
                    <div className="col-span-2">
                      <p className="text-sm font-medium text-muted-foreground">ملاحظات العنوان</p>
                      <p className="text-lg">{orderData.client.address.notes}</p>
                    </div>
                  )}
                </>
              )}
            </div>
          </CardContent>
        </Card>
      )}

      {orderData.items && orderData.items.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>عناصر الطلب</CardTitle>
            <CardDescription>المنتجات والخدمات المدرجة في الطلب</CardDescription>
          </CardHeader>
          <CardContent>
            {(() => {
              const { currency_symbol } = getOrderCurrencyInfo(orderData);
              const { totalWithVat } = getOrderTotalsWithVat(orderData);
              const paidTotal = Number(orderData.paid ?? 0);
              const remainingTotal = Number(orderData.remaining ?? 0);

              return (
                <div className="table-responsive-wrapper">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="text-center">#</TableHead>
                        <TableHead className="text-center">الكود</TableHead>
                        <TableHead className="text-center">الاسم</TableHead>
                        <TableHead className="text-center">المنتج الفرعي</TableHead>
                        <TableHead className="text-center">الكمية</TableHead>
                        <TableHead className="text-center">السعر (شامل الضريبة)</TableHead>
                        <TableHead className="text-center">المدفوع</TableHead>
                        <TableHead className="text-center">المتبقي</TableHead>
                        <TableHead className="text-center">الحالة</TableHead>
                        <TableHead className="text-center">تفاصيل المنتج</TableHead>
                        <TableHead className="text-center">قابل للإرجاع</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {orderData.items.map((item, index) => (
                        <TableRow key={item.id}>
                          <TableCell className="text-center font-medium">
                            {index + 1}
                          </TableCell>
                          <TableCell className="text-center">{item.code}</TableCell>
                          <TableCell className="text-center">
                            {item.name ?? item.code}
                          </TableCell>
                          <TableCell className="text-center">
                            {getItemSubcategoryDisplay(item as Record<string, unknown>) || "-"}
                          </TableCell>
                          <TableCell className="text-center">{item.quantity}</TableCell>
                          <TableCell className="text-center" dir="ltr">
                            {currency_symbol} {item.price}
                          </TableCell>
                          <TableCell className="text-center" dir="ltr">
                            {currency_symbol} {item.item_paid}
                          </TableCell>
                          <TableCell className="text-center" dir="ltr">
                            {currency_symbol} {item.item_remaining}
                          </TableCell>
                          <TableCell className="text-center">
                            {getStatusLabel(item.status)}
                          </TableCell>
                          <TableCell className="text-center">
                            <Button variant="outline" size="sm" asChild>
                              <Link
                                to={`/orders/${orderData.id}/items/${item.id}`}
                                title="عرض كل تفاصيل المنتج والقياسات"
                              >
                                عرض التفاصيل
                              </Link>
                            </Button>
                          </TableCell>
                          <TableCell className="text-center">
                            <Button
                              variant="outline"
                              title="إرجاع المنتج"
                              disabled={item.returnable === 0}
                              onClick={() => onReturnItem(item.id)}
                            >
                              إرجاع
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                    <TableFooter>
                      <TableRow>
                        <TableCell colSpan={5} />
                        <TableCell className="text-center font-semibold" dir="ltr">
                          {currency_symbol} {totalWithVat.toLocaleString()}
                        </TableCell>
                        <TableCell className="text-center font-semibold" dir="ltr">
                          {currency_symbol} {paidTotal.toLocaleString()}
                        </TableCell>
                        <TableCell className="text-center font-semibold" dir="ltr">
                          {currency_symbol} {remainingTotal.toLocaleString()}
                        </TableCell>
                        <TableCell colSpan={3} />
                      </TableRow>
                    </TableFooter>
                  </Table>
                </div>
              );
            })()}
          </CardContent>
        </Card>
      )}

      {orderData.order_notes && (
        <Card>
          <CardHeader>
            <CardTitle>ملاحظات الطلب</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-muted-foreground">{orderData.order_notes}</p>
          </CardContent>
        </Card>
      )}

      <OrderCustodiesTable
        orderId={orderData.id}
        clientId={orderData.client_id}
        currencySymbol={getOrderCurrencyInfo(orderData).currency_symbol}
      />

      <OrderPaymentsTable orderId={orderData.id} order={orderData} />
    </>
  );
}
