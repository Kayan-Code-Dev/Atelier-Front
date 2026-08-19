import { TOrder } from "@/api/v2/orders/orders.types";
import { useEffect, useState, useMemo } from "react";
import { useLocation, useNavigate, Link } from "react-router";
import { toast } from "sonner";
import { useQuery } from "@tanstack/react-query";
import { useGetClothesAvialbelByDateQueryOptions } from "@/api/v2/clothes/clothes.hooks";
import { EntitySelect } from "@/components/custom/EntitySelect";
import { DatePicker } from "@/components/custom/DatePicker";
import { TEntity } from "@/lib/types/entity.types";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { ChevronRight, X, ArrowRight, AlertCircle } from "lucide-react";
import { useGetOrderDetailsQueryOptions } from "@/api/v2/orders/orders.hooks";
import OrderDetailsSkeleton from "../OrderDetailsSkeleton";

type SelectedCloth = {
  id: number;
  code: string;
  name?: string;
  price?: number; // Price for new items
};

type OrderItemWithPrice = {
  id: number;
  cloth_id: number;
  code: string;
  name?: string;
  description: string;
  price: number; // Original price from order
};

type RemovedItem = {
  originalItem: OrderItemWithPrice;
  replacementId: number | null; // ID of replacement cloth, null if not replaced yet
};

function UpdateClothesInOrder() {
  const location = useLocation();
  const { order } = (location.state ?? {}) as { order: TOrder | null };
  const navigate = useNavigate();

  // Fetch full order details to get prices
  const { data: orderDetails, isPending: isLoadingOrder } = useQuery({
    ...useGetOrderDetailsQueryOptions(order?.id || 0),
    enabled: !!order?.id,
  });

  const fullOrder = orderDetails || order;

  const [selectedDate, setSelectedDate] = useState<Date | undefined>();
  const [selectedClothes, setSelectedClothes] = useState<SelectedCloth[]>([]);
  const [removedItems, setRemovedItems] = useState<RemovedItem[]>([]);
  const [originalItems, setOriginalItems] = useState<OrderItemWithPrice[]>([]);

  // Initialize from order
  useEffect(() => {
    if (!fullOrder) return;

    // Set entity type and ID from order (these are fixed)
    // Set initial date from order delivery date (if available)
    // We need to find delivery_date from items or order
    if (fullOrder.items && fullOrder.items.length > 0) {
      // Try to get delivery date from first item or use today
      // Note: delivery_date might not be in the order response, so we'll use today as default
      setSelectedDate(new Date());
    }

    // Initialize original items with prices from order
    const items: OrderItemWithPrice[] = (fullOrder.items || []).map((item) => ({
      id: item.id,
      cloth_id: item.id,
      code: item.code,
      name: item.name ?? item.code,
      description: item.description || "",
      price: (item as any).price || 0,
    }));
    setOriginalItems(items);
  }, [fullOrder]);

  useEffect(() => {
    if (!order) {
      setTimeout(() => {
        navigate("/orders/list");
        toast.error("يجب عليك اختيار طلب");
      }, 0);
    }
  }, [order, navigate]);

  // Format date as YYYY-MM-DD for API
  const formattedDate = useMemo(() => {
    if (!selectedDate) return "";
    return selectedDate.toISOString().split("T")[0];
  }, [selectedDate]);

  // Check if we can fetch clothes
  const canFetchClothes = Boolean(
    formattedDate &&
      fullOrder?.entity_type &&
      fullOrder?.entity_id &&
      !isNaN(Number(fullOrder.entity_id))
  );

  const queryOptions = useGetClothesAvialbelByDateQueryOptions(
    formattedDate || "",
    (fullOrder?.entity_type || "branch") as TEntity,
    Number(fullOrder?.entity_id) || 0
  );

  const { data, isPending } = useQuery({
    ...queryOptions,
    enabled: canFetchClothes,
  });

  // Filter available clothes to exclude ALL items from the order and already selected ones
  const availableClothes = useMemo(() => {
    if (!data?.available_clothes) return [];
    
    // Get ALL cloth IDs from the original order items (including removed ones)
    const allOrderClothIds = new Set(
      originalItems.map((item) => item.cloth_id)
    );

    // Get replacement cloth IDs (for removed items that have been replaced)
    const replacementClothIds = new Set(
      removedItems
        .filter((r) => r.replacementId !== null)
        .map((r) => r.replacementId!)
    );

    // Get newly selected cloth IDs
    const selectedClothIds = new Set(
      selectedClothes.map((cloth) => cloth.id)
    );

    // Combine all excluded IDs
    const excludedIds = new Set([
      ...allOrderClothIds,
      ...replacementClothIds,
      ...selectedClothIds,
    ]);

    return data.available_clothes.filter((cloth) => !excludedIds.has(cloth.id));
  }, [data?.available_clothes, selectedClothes, originalItems, removedItems]);

  // Get minimum price for replacement (from removed items that need replacement)
  const minReplacementPrice = useMemo(() => {
    const itemsNeedingReplacement = removedItems.filter(
      (r) => r.replacementId === null
    );
    if (itemsNeedingReplacement.length === 0) return 0;
    return Math.min(
      ...itemsNeedingReplacement.map((r) => r.originalItem.price)
    );
  }, [removedItems]);

  // Filter available clothes by price for replacement
  const availableClothesForReplacement = useMemo(() => {
    if (minReplacementPrice === 0) return availableClothes;
    // For now, we don't have price in available_clothes response
    // This would need to be added to the API or we'd need another way to get prices
    // For now, we'll show all available clothes and validate on selection
    return availableClothes;
  }, [availableClothes, minReplacementPrice]);

  const handleRemoveItem = (item: OrderItemWithPrice) => {
    // Check if already removed
    if (removedItems.some((r) => r.originalItem.id === item.id)) {
      return;
    }

    setRemovedItems((prev) => [
      ...prev,
      {
        originalItem: item,
        replacementId: null,
      },
    ]);

    // Remove from original items
    setOriginalItems((prev) => prev.filter((i) => i.id !== item.id));
  };

  const handleRestoreItem = (removedItem: RemovedItem) => {
    setRemovedItems((prev) =>
      prev.filter((r) => r.originalItem.id !== removedItem.originalItem.id)
    );
    setOriginalItems((prev) => [...prev, removedItem.originalItem]);
  };

  const handleSelectReplacement = (
    removedItem: RemovedItem,
    clothId: number
  ) => {
    // TODO: Validate price - replacement must have same or higher price
    // For now, we'll just set it and validate on submit
    setRemovedItems((prev) =>
      prev.map((r) =>
        r.originalItem.id === removedItem.originalItem.id
          ? { ...r, replacementId: clothId }
          : r
      )
    );
  };

  const handleClothToggle = (cloth: SelectedCloth) => {
    setSelectedClothes((prev) => {
      const isSelected = prev.some((c) => c.id === cloth.id);
      if (isSelected) {
        return prev.filter((c) => c.id !== cloth.id);
      } else {
        return [...prev, cloth];
      }
    });
  };

  const handleRemoveCloth = (clothId: number) => {
    setSelectedClothes((prev) => prev.filter((c) => c.id !== clothId));
  };

  const handleContinue = () => {
    if (!fullOrder) {
      toast.error("يجب عليك اختيار طلب");
      return;
    }
    if (!selectedDate) {
      toast.error("يجب عليك اختيار تاريخ التسليم");
      return;
    }

    // Validate that all removed items have replacements
    const itemsNeedingReplacement = removedItems.filter(
      (r) => r.replacementId === null
    );
    if (itemsNeedingReplacement.length > 0) {
      toast.error(
        `يجب استبدال ${itemsNeedingReplacement.length} قطعة منتج تم حذفها`
      );
      return;
    }

    // Prepare the final clothes list:
    // - Current items (not removed)
    // - Replacement items (for removed items)
    // - New selected items
    // Use a Map to ensure no duplicates by cloth ID
    const clothesMap = new Map<number, SelectedCloth>();

    // Add current items that weren't removed
    // Use the price from the original order item
    currentItems.forEach((item) => {
      // Find the original order item to get its price
      const originalOrderItem = fullOrder.items?.find(
        (oi) => oi.id === item.id
      );
      const itemPrice = originalOrderItem
        ? (originalOrderItem as any).price || item.price || 0
        : item.price || 0;

      clothesMap.set(item.cloth_id, {
        id: item.cloth_id,
        code: item.code,
        name: item.name ?? item.code,
        price: itemPrice,
      });
    });

    // Add replacement items (for removed items)
    removedItems
      .filter((r) => r.replacementId !== null)
      .forEach((r) => {
        const replacementCloth = data?.available_clothes.find(
          (c) => c.id === r.replacementId!
        );
        if (replacementCloth && !clothesMap.has(replacementCloth.id)) {
          clothesMap.set(replacementCloth.id, {
            id: replacementCloth.id,
            code: replacementCloth.code,
            name: replacementCloth.name ?? replacementCloth.code,
            price: r.originalItem.price,
          });
        }
      });

    // Add new selected items (ensure no duplicates with replacements)
    selectedClothes.forEach((cloth) => {
      if (!clothesMap.has(cloth.id)) {
        clothesMap.set(cloth.id, {
          id: cloth.id,
          code: cloth.code,
          name: cloth.name,
          price: cloth.price || 0,
        });
      }
    });

    // Convert map to array
    const finalClothes: SelectedCloth[] = Array.from(clothesMap.values());

    navigate("/orders/update-order", {
      state: {
        order: fullOrder,
        clothes: finalClothes,
        delivery_date: formattedDate,
        removedItems: removedItems.map((r) => ({
          originalItemId: r.originalItem.id,
          replacementId: r.replacementId,
        })),
      },
    });
  };

  if (isLoadingOrder) {
    return (
      <OrderDetailsSkeleton />
    );
  }

  if (!fullOrder) {
    return null;
  }

  const currentItems = originalItems.filter(
    (item) => !removedItems.some((r) => r.originalItem.id === item.id)
  );

  return (
    <div dir="rtl" className="w-full space-y-6">
      {/* Breadcrumbs */}
      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <Link to="/orders/list" className="hover:text-foreground transition-colors">
          قائمة الطلبات
        </Link>
        <ChevronRight className="h-4 w-4" />
        <span className="text-foreground font-medium">تحديث منتجات الطلب</span>
      </div>

      {/* Order Info */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">معلومات الطلب</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p className="text-sm text-muted-foreground">رقم الطلب</p>
              <p className="font-medium">#{fullOrder.id}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">السعر الإجمالي</p>
              <p className="font-medium">{fullOrder.total_price} ج.م</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">الحالة</p>
              <Badge variant="outline">
                {fullOrder.status === "paid"
                  ? "مدفوع"
                  : fullOrder.status === "partially_paid"
                  ? "مدفوع جزئياً"
                  : fullOrder.status === "canceled"
                  ? "ملغي"
                  : "تم الإنشاء"}
              </Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Filters Card */}
      <Card>
        <CardHeader>
          <CardTitle>المكان والتاريخ</CardTitle>
          <CardDescription>
            المكان ثابت ولا يمكن تغييره. يمكنك تغيير تاريخ التسليم
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <EntitySelect
              mode="standalone"
              entityType={fullOrder.entity_type}
              entityId={fullOrder.entity_id.toString()}
              onEntityTypeChange={() => {}} // Disabled
              onEntityIdChange={() => {}} // Disabled
              entityTypeLabel="نوع المكان"
              entityIdLabel="المكان"
              disabled
            />
            <DatePicker
              label="تاريخ التسليم"
              value={selectedDate}
              onChange={setSelectedDate}
              placeholder="اختر تاريخ التسليم"
              allowPastDates={false}
              minDate={new Date()}
              fromYear={new Date().getFullYear()}
              toYear={2050}
            />
          </div>
        </CardContent>
      </Card>

      {/* Current Order Items */}
      {currentItems.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>منتجات الطلب الحالية ({currentItems.length})</CardTitle>
            <CardDescription>
              المنتجات الموجودة في الطلب. يمكنك حذف أي قطعة ولكن يجب استبدالها
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="table-responsive-wrapper">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="text-center">الكود</TableHead>
                    <TableHead className="text-center">الاسم</TableHead>
                    <TableHead className="text-center">الوصف</TableHead>
                    <TableHead className="text-center">السعر</TableHead>
                    <TableHead className="text-center">إجراءات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {currentItems.map((item) => (
                    <TableRow key={item.id}>
                      <TableCell className="text-center font-medium">
                        {item.code}
                      </TableCell>
                      <TableCell className="text-center">{item.name ?? item.code}</TableCell>
                      <TableCell className="text-center">
                        {item.description || "-"}
                      </TableCell>
                      <TableCell className="text-center">
                        {item.price} ج.م
                      </TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="destructive"
                          size="sm"
                          onClick={() => handleRemoveItem(item)}
                        >
                          <X className="h-4 w-4 mr-1" />
                          حذف
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Removed Items Needing Replacement */}
      {removedItems.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <AlertCircle className="h-5 w-5 text-destructive" />
              المنتجات المحذوفة التي تحتاج استبدال ({removedItems.length})
            </CardTitle>
            <CardDescription>
              يجب استبدال كل قطعة منتج محذوفة بقطعة أخرى بنفس السعر أو أعلى
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {removedItems.map((removedItem) => (
              <Card key={removedItem.originalItem.id} className="border-2">
                <CardHeader>
                  <CardTitle className="text-base">
                    {removedItem.originalItem.code}
                  </CardTitle>
                  <CardDescription>
                    السعر الأصلي: {removedItem.originalItem.price} ج.م
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  {removedItem.replacementId ? (
                    <div className="flex items-center justify-between p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                      <span className="text-sm">
                        تم اختيار استبدال (ID: {removedItem.replacementId})
                      </span>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                          setRemovedItems((prev) =>
                            prev.map((r) =>
                              r.originalItem.id === removedItem.originalItem.id
                                ? { ...r, replacementId: null }
                                : r
                            )
                          )
                        }
                      >
                        تغيير
                      </Button>
                    </div>
                  ) : (
                    <div className="space-y-2">
                      <p className="text-sm font-medium">
                        اختر استبدالاً (يجب أن يكون السعر ≥{" "}
                        {removedItem.originalItem.price} ج.م)
                      </p>
                      {canFetchClothes && !isPending ? (
                        <div className="max-h-48 overflow-y-auto border rounded-md">
                          <Table>
                            <TableHeader>
                              <TableRow>
                                <TableHead className="w-12"></TableHead>
                                <TableHead className="text-center">
                                  الكود
                                </TableHead>
                                <TableHead className="text-center">
                                  الاسم
                                </TableHead>
                                <TableHead className="text-center">
                                  الوصف
                                </TableHead>
                              </TableRow>
                            </TableHeader>
                            <TableBody>
                              {availableClothesForReplacement.map((cloth) => (
                                <TableRow
                                  key={cloth.id}
                                  className="cursor-pointer hover:bg-muted/50"
                                  onClick={() =>
                                    handleSelectReplacement(
                                      removedItem,
                                      cloth.id
                                    )
                                  }
                                >
                                  <TableCell
                                    onClick={(e) => e.stopPropagation()}
                                  >
                                    <Checkbox
                                      checked={
                                        removedItem.replacementId === cloth.id
                                      }
                                      onCheckedChange={() =>
                                        handleSelectReplacement(
                                          removedItem,
                                          cloth.id
                                        )
                                      }
                                    />
                                  </TableCell>
                                  <TableCell className="text-center font-medium">
                                    {cloth.code}
                                  </TableCell>
                                  <TableCell className="text-center">
                                    {cloth.name ?? cloth.code}
                                  </TableCell>
                                  <TableCell className="text-center">
                                    {cloth.description || "-"}
                                  </TableCell>
                                </TableRow>
                              ))}
                            </TableBody>
                          </Table>
                        </div>
                      ) : (
                        <p className="text-sm text-muted-foreground">
                          اختر تاريخ التسليم لعرض المنتجات المتاحة
                        </p>
                      )}
                    </div>
                  )}
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleRestoreItem(removedItem)}
                  >
                    إلغاء الحذف
                  </Button>
                </CardContent>
              </Card>
            ))}
          </CardContent>
        </Card>
      )}

      {/* Selected New Clothes */}
      {selectedClothes.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>
              المنتجات الجديدة المختارة ({selectedClothes.length})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex flex-wrap gap-2">
              {selectedClothes.map((cloth) => (
                <Badge
                  key={cloth.id}
                  variant="secondary"
                  className="text-sm px-3 py-1.5 flex items-center gap-2"
                >
                  <span>
                    {cloth.code}
                  </span>
                  <button
                    onClick={() => handleRemoveCloth(cloth.id)}
                    className="hover:bg-destructive/20 rounded-full p-0.5 transition-colors"
                  >
                    <X className="h-3 w-3" />
                  </button>
                </Badge>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Available Clothes Table */}
      <Card>
        <CardHeader>
            <CardTitle>المنتجات المتاحة</CardTitle>
          <CardDescription>
            {canFetchClothes
              ? `إجمالي المنتجات المتاحة: ${availableClothes.length || 0}`
              : "اختر تاريخ التسليم لعرض المنتجات المتاحة"}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {!canFetchClothes ? (
            <div className="text-center py-10 text-muted-foreground">
              يرجى اختيار تاريخ التسليم لعرض المنتجات
            </div>
          ) : isPending ? (
            <div className="table-responsive-wrapper">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-12"></TableHead>
                    <TableHead className="text-center">الكود</TableHead>
                    <TableHead className="text-center">الاسم</TableHead>
                    <TableHead className="text-center">الوصف</TableHead>
                    <TableHead className="text-center">الحالة</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {Array.from({ length: 5 }).map((_, idx) => (
                    <TableRow key={idx}>
                      <TableCell>
                        <Skeleton className="h-4 w-4" />
                      </TableCell>
                      <TableCell>
                        <Skeleton className="h-4 w-20" />
                      </TableCell>
                      <TableCell>
                        <Skeleton className="h-4 w-32" />
                      </TableCell>
                      <TableCell>
                        <Skeleton className="h-4 w-40" />
                      </TableCell>
                      <TableCell>
                        <Skeleton className="h-4 w-20" />
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          ) : availableClothes.length === 0 ? (
            <div className="text-center py-10 text-muted-foreground">
              لا توجد منتجات متاحة للتاريخ المحدد
            </div>
          ) : (
            <div className="table-responsive-wrapper">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-12"></TableHead>
                    <TableHead className="text-center">الكود</TableHead>
                    <TableHead className="text-center">الاسم</TableHead>
                    <TableHead className="text-center">الوصف</TableHead>
                    <TableHead className="text-center">الحالة</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {availableClothes.map((cloth) => {
                    const isSelected = selectedClothes.some(
                      (c) => c.id === cloth.id
                    );
                    const clothData = {
                      id: cloth.id,
                      code: cloth.code,
                      name: cloth.name ?? cloth.code,
                    };
                    return (
                      <TableRow
                        key={cloth.id}
                        className={`cursor-pointer hover:bg-muted/50 ${
                          isSelected ? "bg-muted/50" : ""
                        }`}
                        onClick={() => handleClothToggle(clothData)}
                      >
                        <TableCell onClick={(e) => e.stopPropagation()}>
                          <Checkbox
                            checked={isSelected}
                            onCheckedChange={() => handleClothToggle(clothData)}
                          />
                        </TableCell>
                        <TableCell className="text-center font-medium">
                          {cloth.code}
                        </TableCell>
                        <TableCell className="text-center">
                          {cloth.name ?? cloth.code}
                        </TableCell>
                        <TableCell className="text-center">
                          {cloth.description || "-"}
                        </TableCell>
                        <TableCell className="text-center">
                          <Badge variant="outline">
                            {cloth.status === "ready_for_rent"
                              ? "متوفر"
                              : cloth.status === "rented"
                              ? "محجوز"
                              : cloth.status === "repairing"
                              ? "قيد الصيانة"
                              : cloth.status === "damaged"
                              ? "تالف"
                              : cloth.status === "burned"
                              ? "محروق"
                              : cloth.status === "scratched"
                              ? "مخدوش"
                              : "ميت"}
                          </Badge>
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
        {canFetchClothes && !isPending && (
          <CardFooter className="flex justify-end">
            <Button onClick={handleContinue} size="lg">
              حفظ التغييرات
              <ArrowRight className="mr-2 h-4 w-4" />
            </Button>
          </CardFooter>
        )}
      </Card>
    </div>
  );
}

export default UpdateClothesInOrder;
