import { useQuery } from "@tanstack/react-query";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Skeleton } from "@/components/ui/skeleton";
import { TOrder } from "@/api/v2/orders/orders.types";
import { useGetOrderDetailsQueryOptions } from "@/api/v2/orders/orders.hooks";
import { formatDate, fmtCurrency } from "@/utils/formatDate";
import {
  getOrderCurrencyInfo,
  getOrderTotalsWithVat,
  getOrderTypeLabel,
  getItemSubcategoryDisplay,
  getStatusLabel,
} from "@/api/v2/orders/order.utils";

type Props = {
  order: TOrder | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

const statusColors: Record<string, string> = {
  created: "bg-gray-100 text-gray-600",
  paid: "bg-green-100 text-green-700",
  partially_paid: "bg-amber-100 text-amber-700",
  finished: "bg-blue-100 text-blue-700",
  canceled: "bg-red-100 text-red-600",
  delivered: "bg-purple-100 text-purple-700",
};

const statusIcons: Record<string, string> = {
  created: "ri-draft-line",
  paid: "ri-check-double-line",
  partially_paid: "ri-coin-line",
  finished: "ri-checkbox-circle-line",
  canceled: "ri-close-circle-line",
  delivered: "ri-truck-line",
};

const discountLabel = (type?: TOrder["discount_type"]) => {
  if (!type || type === "none") return "لا يوجد";
  return type === "percentage" ? "نسبة مئوية" : "مبلغ ثابت";
};


export function OrderDetailsModal({ order, open, onOpenChange }: Props) {
  const { data, isPending } = useQuery({
    ...useGetOrderDetailsQueryOptions(order?.id || 0),
    enabled: open && !!order?.id,
  });

  const orderData = data || order;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-4xl max-h-[90vh] overflow-y-auto p-0">
        <DialogHeader className="sr-only">
          <DialogTitle>تفاصيل الطلب</DialogTitle>
          <DialogDescription>عرض جميع المعلومات المتعلقة بالطلب</DialogDescription>
        </DialogHeader>

        <div className="space-y-0" dir="rtl">
          {isPending ? (
            <div className="p-6 space-y-4">
              <Skeleton className="h-20 w-full rounded-xl" />
              <Skeleton className="h-32 w-full rounded-xl" />
              <Skeleton className="h-32 w-full rounded-xl" />
            </div>
          ) : orderData ? (
            <>
              {/* Top Header */}
              <div className="bg-blue-900 text-white p-5 rounded-t-lg">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-white/10">
                      <i className="ri-file-list-3-line text-xl" />
                    </div>
                    <div>
                      <h2 className="text-lg font-bold">
                        تفاصيل الطلب #{orderData.id}
                      </h2>
                      <p className="text-blue-200 text-xs mt-0.5">
                        {getOrderTypeLabel(orderData.order_type)} —{" "}
                        {formatDate(orderData.created_at)}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <span
                      className={`text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5 ${statusColors[orderData.status] ?? "bg-gray-100 text-gray-600"}`}
                    >
                      <i
                        className={
                          statusIcons[orderData.status] ?? "ri-question-line"
                        }
                      />
                      {getStatusLabel(orderData.status)}
                    </span>
                  </div>
                </div>
              </div>

              <div className="p-5 space-y-4">
                {/* Financial Summary Cards */}
                {(() => {
                  const { currency_symbol } = getOrderCurrencyInfo(
                    orderData as any,
                  );
                  const { subtotal, totalWithVat, vatEnabled, vatType, vatValue } =
                    getOrderTotalsWithVat(orderData as any);
                  const paid = Number(orderData.paid ?? 0);
                  const remaining = Number(orderData.remaining ?? 0);

                  return (
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                      <div className="bg-blue-50 rounded-xl border border-blue-100 p-3">
                        <div className="flex items-center gap-2 mb-2">
                          <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                            <i className="ri-money-dollar-circle-line text-sm" />
                          </div>
                          <span className="text-xs text-blue-500">
                            الإجمالي
                          </span>
                        </div>
                        <p className="text-lg font-bold text-blue-800">
                          {fmtCurrency(totalWithVat)}{" "}
                          <span className="text-xs font-normal">
                            {currency_symbol}
                          </span>
                        </p>
                        {vatEnabled && vatValue > 0 && (
                          <p className="text-xs text-blue-400 mt-0.5">
                            قبل الضريبة: {fmtCurrency(subtotal)} {currency_symbol}
                            {vatType === "percentage"
                              ? ` (${vatValue}%)`
                              : ` (+${vatValue})`}
                          </p>
                        )}
                      </div>

                      <div className="bg-green-50 rounded-xl border border-green-100 p-3">
                        <div className="flex items-center gap-2 mb-2">
                          <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-green-100 text-green-600">
                            <i className="ri-check-line text-sm" />
                          </div>
                          <span className="text-xs text-green-500">
                            المدفوع
                          </span>
                        </div>
                        <p className="text-lg font-bold text-green-700">
                          {fmtCurrency(paid)}{" "}
                          <span className="text-xs font-normal">
                            {currency_symbol}
                          </span>
                        </p>
                      </div>

                      <div
                        className={`rounded-xl border p-3 ${remaining > 0 ? "bg-red-50 border-red-100" : "bg-gray-50 border-gray-100"}`}
                      >
                        <div className="flex items-center gap-2 mb-2">
                          <div
                            className={`w-7 h-7 flex items-center justify-center rounded-lg ${remaining > 0 ? "bg-red-100 text-red-500" : "bg-gray-100 text-gray-400"}`}
                          >
                            <i className="ri-time-line text-sm" />
                          </div>
                          <span
                            className={`text-xs ${remaining > 0 ? "text-red-500" : "text-gray-400"}`}
                          >
                            المتبقي
                          </span>
                        </div>
                        <p
                          className={`text-lg font-bold ${remaining > 0 ? "text-red-600" : "text-gray-500"}`}
                        >
                          {fmtCurrency(remaining)}{" "}
                          <span className="text-xs font-normal">
                            {currency_symbol}
                          </span>
                        </p>
                      </div>

                      {orderData.discount_type &&
                        orderData.discount_type !== "none" && (
                          <div className="bg-purple-50 rounded-xl border border-purple-100 p-3">
                            <div className="flex items-center gap-2 mb-2">
                              <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                <i className="ri-discount-percent-line text-sm" />
                              </div>
                              <span className="text-xs text-purple-500">
                                الخصم
                              </span>
                            </div>
                            <p className="text-lg font-bold text-purple-700">
                              {orderData.discount_type === "percentage"
                                ? `${orderData.discount_value ?? "0"}%`
                                : `${fmtCurrency(Number(orderData.discount_value ?? 0))} ${currency_symbol}`}
                            </p>
                            <p className="text-xs text-purple-400 mt-0.5">
                              {discountLabel(orderData.discount_type)}
                            </p>
                          </div>
                        )}
                    </div>
                  );
                })()}

                {/* Dates Section */}
                <div className="bg-white rounded-xl border border-blue-100 p-4">
                  <div className="flex items-center gap-2 mb-3">
                    <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                      <i className="ri-calendar-line text-sm" />
                    </div>
                    <p className="text-sm font-semibold text-gray-700">
                      التواريخ
                    </p>
                  </div>
                  <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    {[
                      {
                        label: "تاريخ الفاتورة",
                        value: formatDate(orderData.created_at),
                        icon: "ri-file-text-line",
                        color: "text-gray-600",
                      },
                      {
                        label: "موعد الاستلام",
                        value: formatDate(orderData.delivery_date ?? undefined),
                        icon: "ri-truck-line",
                        color: "text-blue-600",
                      },
                      {
                        label: "موعد الفرح",
                        value: formatDate(
                          orderData.occasion_datetime ?? undefined,
                        ),
                        icon: "ri-heart-line",
                        color: "text-pink-600",
                      },
                      {
                        label: "موعد الاسترجاع",
                        value: formatDate(orderData.visit_datetime),
                        icon: "ri-arrow-go-back-line",
                        color: "text-purple-600",
                      },
                    ].map((d) => (
                      <div key={d.label} className="text-center">
                        <p className="text-xs text-gray-400 mb-1">{d.label}</p>
                        <div
                          className={`flex items-center justify-center gap-1.5 ${d.color}`}
                        >
                          <i className={`${d.icon} text-sm`} />
                          <span className="text-sm font-mono font-semibold">
                            {d.value}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                  {orderData.days_of_rent != null && (
                    <div className="mt-3 pt-3 border-t border-gray-100 text-center">
                      <span className="text-xs text-gray-400">
                        عدد أيام الإيجار:{" "}
                      </span>
                      <span className="text-sm font-bold text-blue-600">
                        {orderData.days_of_rent} يوم
                      </span>
                    </div>
                  )}
                </div>

                {/* Customer Info */}
                {orderData.client && (
                  <div className="bg-white rounded-xl border border-blue-100 p-4">
                    <div className="flex items-center gap-2 mb-3">
                      <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-green-50 text-green-600">
                        <i className="ri-user-line text-sm" />
                      </div>
                      <p className="text-sm font-semibold text-gray-700">
                        معلومات العميل
                      </p>
                    </div>
                    <div className="grid grid-cols-2 gap-x-6 gap-y-3">
                      <div className="flex items-center gap-2">
                        <span className="text-xs text-gray-400 min-w-[60px]">
                          الاسم:
                        </span>
                        <span className="text-sm font-semibold text-gray-800">
                          {orderData.client.name}
                        </span>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="text-xs text-gray-400 min-w-[60px]">
                          الرقم:
                        </span>
                        <span className="text-sm text-gray-600 font-mono">
                          #{orderData.client.id}
                        </span>
                      </div>
                      {orderData.client.national_id && (
                        <div className="flex items-center gap-2">
                          <span className="text-xs text-gray-400 min-w-[60px]">
                            الرقم القومي:
                          </span>
                          <span className="text-sm text-gray-600 font-mono">
                            {orderData.client.national_id}
                          </span>
                        </div>
                      )}
                      {orderData.client.phones &&
                        orderData.client.phones.length > 0 && (
                          <div className="flex items-center gap-2">
                            <span className="text-xs text-gray-400 min-w-[60px]">
                              الهاتف:
                            </span>
                            <span
                              dir="ltr"
                              className="text-sm text-gray-600 font-mono"
                            >
                              {orderData.client.phones
                                .map((p) => p.phone)
                                .join(" / ")}
                            </span>
                          </div>
                        )}
                      {orderData.client.address != null && (
                        <>
                          {orderData.client.address.city_name && (
                            <div className="flex items-center gap-2">
                              <span className="text-xs text-gray-400 min-w-[60px]">
                                المدينة:
                              </span>
                              <span className="text-sm text-gray-600">
                                {orderData.client.address.city_name}
                                {orderData.client.address.country_name
                                  ? ` — ${orderData.client.address.country_name}`
                                  : ""}
                              </span>
                            </div>
                          )}
                          {(orderData.client.address.street ||
                            orderData.client.address.building) && (
                            <div className="flex items-center gap-2 col-span-2">
                              <span className="text-xs text-gray-400 min-w-[60px]">
                                العنوان:
                              </span>
                              <span className="text-sm text-gray-600">
                                {[
                                  orderData.client.address.street,
                                  orderData.client.address.building,
                                ]
                                  .filter(Boolean)
                                  .join(" — ")}
                              </span>
                            </div>
                          )}
                          {orderData.client.address.notes && (
                            <div className="flex items-center gap-2 col-span-2">
                              <span className="text-xs text-gray-400 min-w-[60px]">
                                ملاحظات:
                              </span>
                              <span className="text-sm text-gray-500 italic">
                                {orderData.client.address.notes}
                              </span>
                            </div>
                          )}
                        </>
                      )}
                    </div>
                  </div>
                )}

                {/* Branch / Inventory */}
                {orderData.inventory && (
                  <div className="bg-white rounded-xl border border-blue-100 p-4">
                    <div className="flex items-center gap-2">
                      <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                        <i className="ri-store-2-line text-sm" />
                      </div>
                      <p className="text-sm font-semibold text-gray-700">
                        المخزن / الفرع
                      </p>
                    </div>
                    <p className="text-sm text-gray-600 mr-9 mt-1">
                      {orderData.inventory.name}
                      {orderData.inventory.inventoriable && (
                        <span className="text-gray-400">
                          {" "}
                          — {orderData.inventory.inventoriable.name}
                        </span>
                      )}
                    </p>
                  </div>
                )}

                {/* Order Items Table */}
                {orderData.items && orderData.items.length > 0 && (
                  <div className="bg-white rounded-xl border border-blue-100 overflow-hidden">
                    <div className="flex items-center gap-2 p-4 pb-3">
                      <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                        <i className="ri-t-shirt-2-line text-sm" />
                      </div>
                      <p className="text-sm font-semibold text-gray-700">
                        عناصر الطلب
                      </p>
                      <span className="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full mr-auto">
                        {orderData.items.length} عنصر
                      </span>
                    </div>
                    <div className="overflow-x-auto">
                      <table className="w-full text-sm">
                        <thead>
                          <tr className="bg-blue-900 text-white text-xs">
                            <th className="px-3 py-2.5 text-center font-medium">
                              #
                            </th>
                            <th className="px-3 py-2.5 text-center font-medium">
                              الكود
                            </th>
                            <th className="px-3 py-2.5 text-center font-medium">
                              الاسم
                            </th>
                            <th className="px-3 py-2.5 text-center font-medium">
                              المنتج الفرعي
                            </th>
                            <th className="px-3 py-2.5 text-center font-medium">
                              الكمية
                            </th>
                            <th className="px-3 py-2.5 text-center font-medium">
                              السعر
                            </th>
                            <th className="px-3 py-2.5 text-center font-medium">
                              المدفوع
                            </th>
                            <th className="px-3 py-2.5 text-center font-medium">
                              المتبقي
                            </th>
                            <th className="px-3 py-2.5 text-center font-medium">
                              الحالة
                            </th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                          {orderData.items.map((item, index) => (
                            <tr
                              key={item.id}
                              className="hover:bg-blue-50/30 transition-colors"
                            >
                              <td className="px-3 py-2.5 text-center font-bold text-blue-600">
                                {index + 1}
                              </td>
                              <td className="px-3 py-2.5 text-center font-mono text-xs text-gray-600">
                                {item.code}
                              </td>
                              <td className="px-3 py-2.5 text-center text-gray-800">
                                {item.name ?? item.code}
                              </td>
                              <td className="px-3 py-2.5 text-center text-gray-500 text-xs">
                                {getItemSubcategoryDisplay(
                                  item as Record<string, unknown>,
                                ) || "—"}
                              </td>
                              <td className="px-3 py-2.5 text-center font-medium">
                                {item.quantity}
                              </td>
                              <td className="px-3 py-2.5 text-center text-gray-700">
                                {item.price} ج.م
                              </td>
                              <td className="px-3 py-2.5 text-center text-green-600 font-medium">
                                {item.item_paid} ج.م
                              </td>
                              <td className="px-3 py-2.5 text-center text-red-500 font-medium">
                                {item.item_remaining} ج.م
                              </td>
                              <td className="px-3 py-2.5 text-center">
                                <span
                                  className={`text-xs px-2 py-0.5 rounded-full font-medium ${statusColors[item.status] ?? "bg-gray-100 text-gray-600"}`}
                                >
                                  {getStatusLabel(item.status)}
                                </span>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                )}

                {/* Notes */}
                {orderData.order_notes && (
                  <div className="bg-amber-50 rounded-xl border border-amber-100 p-4">
                    <div className="flex items-center gap-2 mb-2">
                      <div className="w-7 h-7 flex items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                        <i className="ri-sticky-note-line text-sm" />
                      </div>
                      <p className="text-sm font-semibold text-amber-700">
                        ملاحظات الطلب
                      </p>
                    </div>
                    <p className="text-sm text-amber-700/80 mr-9 leading-relaxed">
                      {orderData.order_notes}
                    </p>
                  </div>
                )}
              </div>
            </>
          ) : (
            <div className="p-12 text-center">
              <div className="w-16 h-16 flex items-center justify-center mx-auto rounded-full bg-gray-50 text-gray-300 mb-4">
                <i className="ri-file-unknow-line text-3xl" />
              </div>
              <p className="text-gray-400 text-sm">لا توجد بيانات لعرضها.</p>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
