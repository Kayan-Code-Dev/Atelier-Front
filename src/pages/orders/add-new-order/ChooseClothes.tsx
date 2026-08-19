import { TClientResponse } from "@/api/v2/clients/clients.types";
import { formatPhone } from "@/utils/formatPhone";
import { useEffect, useState, useMemo, useRef } from "react";
import { Link, useLocation, useNavigate } from "react-router";
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
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { X, ArrowRight, User, MapPin, Calendar, ShoppingBag, Shirt } from "lucide-react";
import { OrderStepsStepper } from "@/components/custom/OrderStepsStepper";

type SelectedCloth = {
  id: number;
  code: string;
  name?: string;
};

type ChooseClothesLocationState = {
  client: TClientResponse | null;
  entityType?: TEntity;
  entityId?: string;
  deliveryDate?: string;
};

function ChooseClothes() {
  const locationState =
    useLocation().state ||
    ({ client: null } as ChooseClothesLocationState);
  const {
    client,
    entityType: passedEntityType,
    entityId: passedEntityId,
    deliveryDate: passedDeliveryDate,
  } = locationState as ChooseClothesLocationState;
  const navigate = useNavigate();

  const [entityType, setEntityType] = useState<TEntity | undefined>(
    passedEntityType ?? "branch"
  );
  const [entityId, setEntityId] = useState<string>(passedEntityId ?? "");
  const [selectedDate, setSelectedDate] = useState<Date | undefined>();
  const [selectedClothes, setSelectedClothes] = useState<SelectedCloth[]>([]);

  useEffect(() => {
    if (passedDeliveryDate) {
      const d = new Date(passedDeliveryDate);
      if (!isNaN(d.getTime())) setSelectedDate(d);
    }
  }, [passedDeliveryDate]);

  const formattedDate = useMemo(() => {
    if (!selectedDate) return "";
    return selectedDate.toISOString().split("T")[0];
  }, [selectedDate]);

  const canFetchClothes = Boolean(
    formattedDate && entityType && entityId && !isNaN(Number(entityId))
  );

  const queryOptions = useGetClothesAvialbelByDateQueryOptions(
    formattedDate || "",
    (entityType || "branch") as TEntity,
    Number(entityId) || 0
  );

  const { data, isPending } = useQuery({
    ...queryOptions,
    enabled: canFetchClothes,
  });

  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  useEffect(() => {
    if (!client) {
      setTimeout(() => {
        navigate("/orders/choose-client");
        toast.error("يجب عليك اختيار عميل");
      }, 0);
    }
  }, [client, navigate]);

  const canGoToReview =
    Boolean(client) &&
    Boolean(entityType) &&
    Boolean(entityId) &&
    Boolean(selectedDate) &&
    selectedClothes.length >= 1;

  const hasNavigatedToReview = useRef(false);
  useEffect(() => {
    if (!canGoToReview) {
      hasNavigatedToReview.current = false;
      return;
    }
    if (hasNavigatedToReview.current || !client) return;
    hasNavigatedToReview.current = true;
    navigate("/orders/create-order", {
      state: {
        client_id: client.id,
        entity_type: entityType,
        entity_id: Number(entityId),
        delivery_date: formattedDate,
        selected_clothes: selectedClothes,
        client: client,
      },
    });
  }, [
    canGoToReview,
    client,
    entityType,
    entityId,
    formattedDate,
    selectedClothes,
    navigate,
  ]);

  useEffect(() => {
    setSelectedClothes([]);
  }, [entityId, entityType]);

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
    if (!client) {
      toast.error("يجب عليك اختيار عميل");
      return;
    }
    if (!entityType || !entityId) {
      toast.error("يجب عليك اختيار نوع المكان والمكان");
      return;
    }
    if (!selectedDate) {
      toast.error("يجب عليك اختيار تاريخ التسليم");
      return;
    }
    if (selectedClothes.length === 0) {
      toast.error("يجب عليك اختيار منتج واحد على الأقل");
      return;
    }

    navigate("/orders/create-order", {
      state: {
        client_id: client.id,
        entity_type: entityType,
        entity_id: Number(entityId),
        delivery_date: formattedDate,
        selected_clothes: selectedClothes,
        client: client,
      },
    });
  };

  const availableClothes = data?.available_clothes || [];

  const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
      ready_for_rent: "متوفر",
      rented: "محجوز",
      repairing: "قيد الصيانة",
      damaged: "تالف",
      burned: "محروق",
      scratched: "مخدوش",
      die: "ميت",
    };
    return labels[status] || status;
  };

  return (
    <div
      dir="rtl"
      className="min-h-screen bg-[linear-gradient(180deg,hsl(220_20%_98%)_0%,hsl(220_14%_96%)_100%)] dark:bg-slate-950"
    >
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
            إنشاء طلب إيجار — اختيار المنتجات
          </span>
        </nav>

        {/* Steps */}
        <div className="sticky top-0 z-20 -mx-4 -mt-8 px-4 pt-8 pb-4 lg:-mx-8 lg:px-8 lg:pt-8 lg:pb-4 bg-[linear-gradient(180deg,hsl(220_20%_98%)_0%,hsl(220_14%_96%)_100%)] dark:bg-slate-950">
          <OrderStepsStepper
            currentStep={2}
            stepState={{
              1: client ? { client } : undefined,
              2: client ? { client } : undefined,
              3:
                client &&
                entityType &&
                entityId &&
                selectedClothes.length >= 1 &&
                selectedDate
                  ? {
                      client_id: client.id,
                      entity_type: entityType,
                      entity_id: Number(entityId),
                      delivery_date: formattedDate,
                      selected_clothes: selectedClothes,
                      client,
                    }
                  : undefined,
            }}
            allowNextStep={
              Boolean(
                client &&
                  entityType &&
                  entityId &&
                  selectedDate &&
                  selectedClothes.length >= 1
              )
            }
          />
        </div>

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_420px]">
          {/* Summary panel */}
          <aside className="w-full order-first lg:order-none lg:col-start-2 lg:row-start-1">
            <div className="sticky top-24 overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-black/30 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
              {/* Panel header */}
              <div className="relative overflow-hidden bg-gradient-to-br from-[#5170ff] to-[#3d5ae0] px-8 py-8">
                <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50" />
                <div className="relative flex items-center gap-5">
                  <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-md ring-1 ring-white/30">
                    <Shirt className="h-8 w-8 text-white" />
                  </div>
                  <div>
                    <h2 className="text-2xl font-bold tracking-tight text-white">
                      المنتجات المختارة
                    </h2>
                    <p className="mt-1 text-sm text-white/90">
                      {selectedClothes.length} قطعة · الخطوة التالية: المراجعة
                    </p>
                  </div>
                </div>
              </div>

              {/* Selected products list */}
              <div className="border-b border-slate-200 dark:border-slate-700">
                <div className="flex items-center justify-between gap-4 px-6 py-4 bg-slate-50 dark:bg-slate-800/50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                  <span>الكود</span>
                  <span>إجراء</span>
                </div>
                <div className="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                  {selectedClothes.length === 0 ? (
                    <div className="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                      لم تختر أي منتجات بعد
                    </div>
                  ) : (
                    selectedClothes.map((cloth) => (
                      <div
                        key={cloth.id}
                        className="flex items-center justify-between gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                      >
                        <span className="font-mono font-semibold text-slate-900 dark:text-slate-100">
                          {cloth.code}
                        </span>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="h-8 w-8 p-0 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30"
                          onClick={() => handleRemoveCloth(cloth.id)}
                        >
                          <X className="h-4 w-4" />
                        </Button>
                      </div>
                    ))
                  )}
                </div>
              </div>

              {/* Continue button */}
              <div className="p-6">
                <Button
                  onClick={handleContinue}
                  disabled={selectedClothes.length === 0}
                  className="w-full h-12 rounded-xl bg-[#5170ff] hover:bg-[#4560e6] text-white font-semibold shadow-lg shadow-[#5170ff]/30 transition-all hover:shadow-xl hover:shadow-[#5170ff]/40 active:scale-[0.98] disabled:opacity-50"
                >
                  المتابعة إلى المراجعة
                  <ArrowRight className="mr-2 h-4 w-4" />
                </Button>
              </div>
            </div>
          </aside>

          {/* Main content */}
          <div className="min-w-0 lg:col-start-1 lg:row-start-1 space-y-6">
            {/* Client info */}
            {client && (
              <div className="overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-black/30 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                <div className="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-6 py-4">
                  <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                      <User className="h-5 w-5 text-[#5170ff]" />
                    </div>
                    <div>
                      <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">
                        معلومات العميل
                      </h2>
                      <p className="text-xs text-slate-500 dark:text-slate-400">
                        العميل المختار لهذا الطلب
                      </p>
                    </div>
                  </div>
                </div>
                <div className="p-6">
                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-4 ring-1 ring-slate-200/40 dark:ring-slate-700/40">
                      <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        الاسم الكامل
                      </p>
                      <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                        {client.first_name} {client.middle_name} {client.last_name}
                      </p>
                    </div>
                    <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-4 ring-1 ring-slate-200/40 dark:ring-slate-700/40">
                      <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        الرقم القومي
                      </p>
                      <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                        {client.national_id || "—"}
                      </p>
                    </div>
                    <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-4 ring-1 ring-slate-200/40 dark:ring-slate-700/40 sm:col-span-2 md:col-span-1">
                      <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        أرقام الهاتف
                      </p>
                      <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100" dir="ltr">
                        {client.phones
                          ?.map((p) => formatPhone(p.phone, ""))
                          .filter(Boolean)
                          .join("، ") || "—"}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Location and date */}
            <div className="overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-black/30 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
              <div className="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-6 py-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                    <MapPin className="h-5 w-5 text-[#5170ff]" />
                  </div>
                  <div>
                    <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">
                      المكان والتاريخ
                    </h2>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                      اختر نوع المكان والمكان وتاريخ التسليم لعرض المنتجات المتاحة
                    </p>
                  </div>
                </div>
              </div>
              <div className="p-6">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <EntitySelect
                    mode="standalone"
                    entityType={entityType}
                    entityId={entityId}
                    onEntityTypeChange={setEntityType}
                    onEntityIdChange={setEntityId}
                    entityTypeLabel="نوع المكان"
                    entityIdLabel="المكان"
                    required
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
              </div>
            </div>

            {/* Available products table */}
            <div className="overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-black/30 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
              <div className="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-6 py-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                    <ShoppingBag className="h-5 w-5 text-[#5170ff]" />
                  </div>
                  <div>
                    <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">
                      المنتجات المتاحة
                    </h2>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                      {canFetchClothes
                        ? `إجمالي المنتجات المتاحة: ${data?.total_available || 0}`
                        : "اختر المكان والتاريخ لعرض المنتجات المتاحة"}
                    </p>
                  </div>
                </div>
              </div>
              <div className="p-6">
                {!canFetchClothes ? (
                  <div className="flex flex-col items-center justify-center py-16 text-center">
                    <Calendar className="h-12 w-12 text-slate-300 dark:text-slate-600 mb-4" />
                    <p className="text-slate-500 dark:text-slate-400">
                      يرجى اختيار نوع المكان والمكان وتاريخ التسليم لعرض المنتجات
                    </p>
                  </div>
                ) : isPending ? (
                  <div className="overflow-hidden rounded-xl ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                    <Table>
                      <TableHeader>
                        <TableRow className="bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                          <TableHead className="w-12"></TableHead>
                          <TableHead className="text-center">الكود</TableHead>
                          <TableHead className="text-center">الوصف</TableHead>
                          <TableHead className="text-center">الحالة</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {Array.from({ length: 5 }).map((_, idx) => (
                          <TableRow key={idx}>
                            <TableCell>
                              <Skeleton className="h-4 w-4 rounded" />
                            </TableCell>
                            <TableCell>
                              <Skeleton className="h-4 w-20 rounded" />
                            </TableCell>
                            <TableCell>
                              <Skeleton className="h-4 w-40 rounded" />
                            </TableCell>
                            <TableCell>
                              <Skeleton className="h-4 w-20 rounded" />
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                ) : availableClothes.length === 0 ? (
                  <div className="flex flex-col items-center justify-center py-16 text-center">
                    <Shirt className="h-12 w-12 text-slate-300 dark:text-slate-600 mb-4" />
                    <p className="text-slate-500 dark:text-slate-400">
                      لا توجد منتجات متاحة للتاريخ المحدد
                    </p>
                  </div>
                ) : (
                  <div className="overflow-hidden rounded-xl ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                    <Table>
                      <TableHeader>
                        <TableRow className="bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                          <TableHead className="w-12"></TableHead>
                          <TableHead className="text-center">الكود</TableHead>
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
                          };
                          return (
                            <TableRow
                              key={cloth.id}
                              className={`cursor-pointer transition-colors ${
                                isSelected
                                  ? "bg-[#5170ff]/5 dark:bg-[#5170ff]/10"
                                  : "hover:bg-slate-50 dark:hover:bg-slate-800/50"
                              }`}
                              onClick={() => handleClothToggle(clothData)}
                            >
                              <TableCell onClick={(e) => e.stopPropagation()}>
                                <Checkbox
                                  checked={isSelected}
                                  onCheckedChange={() => handleClothToggle(clothData)}
                                  className="border-slate-300 data-[state=checked]:bg-[#5170ff] data-[state=checked]:border-[#5170ff]"
                                />
                              </TableCell>
                              <TableCell className="text-center font-mono font-semibold">
                                {cloth.code}
                              </TableCell>
                              <TableCell className="text-center text-slate-600 dark:text-slate-400">
                                {cloth.description || "—"}
                              </TableCell>
                              <TableCell className="text-center">
                                <Badge
                                  variant={
                                    cloth.status === "ready_for_rent"
                                      ? "default"
                                      : "secondary"
                                  }
                                  className={
                                    cloth.status === "ready_for_rent"
                                      ? "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                      : ""
                                  }
                                >
                                  {getStatusLabel(cloth.status)}
                                </Badge>
                              </TableCell>
                            </TableRow>
                          );
                        })}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default ChooseClothes;
