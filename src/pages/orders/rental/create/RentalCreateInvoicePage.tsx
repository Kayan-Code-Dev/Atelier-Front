import { useCallback, useMemo, useState } from "react";
import { useNavigate } from "react-router";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { format } from "date-fns";
import { useCreateOrderMutationOptions } from "@/api/v2/orders/orders.hooks";
import type {
  TCreateOrderRequest,
  TCreateOrderWithNewClientRequest,
} from "@/api/v2/orders/orders.types";
import type { TCreateClientRequest } from "@/api/v2/clients/clients.types";
import { useGetClientQueryOptions } from "@/api/v2/clients/clients.hooks";
import { useGetClothesAvialbelByDateQueryOptions } from "@/api/v2/clothes/clothes.hooks";
import { useGetBranchQueryOptions } from "@/api/v2/branches/branches.hooks";
import { useGetEmployeeQueryOptions } from "@/api/v2/employees/employees.hooks";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { EmployeesSelect } from "@/components/custom/EmployeesSelect";
import { CitiesSelect } from "@/components/custom/CitiesSelect";
import { ClientsSelect } from "@/components/custom/ClientsSelect";
import {
  buildRentLineItemsForOrderApi,
  computeDaysOfRentFromYmd,
  computeRentalVatPreviewFromBranch,
  displayClientLabel,
  employeesParamsForOrderEntity,
  formatDateInputToOrderApi,
  isReturnYmdBeforeReceiveYmd,
} from "@/pages/orders/lib/orderCreationShared";
import {
  RentalCreateInvoiceSummary,
  type SummaryData,
} from "./RentalCreateInvoiceSummary";

interface SelectedProduct {
  productId: string;
  cloth_id: number;
  name: string;
  category: string;
  quantity: number;
  unitPrice: number;
}

type PopupStatus = "success" | "error" | null;

type ClientMode = "existing" | "new";

export default function RentalCreateInvoicePage() {
  const navigate = useNavigate();

  const [branchId, setBranchId] = useState("");
  const [employeeId, setEmployeeId] = useState("");

  const [clientMode, setClientMode] = useState<ClientMode>("existing");
  const [selectedClientId, setSelectedClientId] = useState("");

  const [customerName, setCustomerName] = useState("");
  const [nationalId, setNationalId] = useState("");
  const [phone, setPhone] = useState("");
  const [whatsapp, setWhatsapp] = useState("");
  const [address, setAddress] = useState("");
  const [cityId, setCityId] = useState("");

  const [deliveryDate, setDeliveryDate] = useState("");
  const [eventDate, setEventDate] = useState("");
  const [returnDate, setReturnDate] = useState("");

  const [selectedProducts, setSelectedProducts] = useState<SelectedProduct[]>(
    []
  );
  const [showProductPicker, setShowProductPicker] = useState(false);
  const [productSearch, setProductSearch] = useState("");

  const [deposit, setDeposit] = useState(0);
  const [hasDiscount, setHasDiscount] = useState(false);
  const [discount, setDiscount] = useState(0);
  const [discountReason, setDiscountReason] = useState("");

  const [notes, setNotes] = useState("");
  const [popupStatus, setPopupStatus] = useState<PopupStatus>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const branchNum = branchId ? Number(branchId) : 0;
  const { data: branchData } = useQuery({
    ...useGetBranchQueryOptions(branchNum),
    enabled: branchNum > 0,
  });
  const branchLabel = branchData?.name ?? "";

  const employeeNum = employeeId ? Number(employeeId) : 0;
  const { data: employeeData } = useQuery({
    ...useGetEmployeeQueryOptions(employeeNum),
    enabled: employeeNum > 0,
  });
  const employeeLabel = employeeData?.user?.name ?? "";

  const selectedClientNum =
    selectedClientId && clientMode === "existing"
      ? Number(selectedClientId)
      : 0;
  const { data: selectedClientData } = useQuery({
    ...useGetClientQueryOptions(selectedClientNum),
    enabled: selectedClientNum > 0,
  });
  const existingClientDisplayName = displayClientLabel(selectedClientData);

  const employeeParams = useMemo(
    () => employeesParamsForOrderEntity("branch", branchId),
    [branchId]
  );

  const deliveryDateApi = deliveryDate
    ? formatDateInputToOrderApi(deliveryDate)
    : "";
  const clothesQuery = useQuery({
    ...useGetClothesAvialbelByDateQueryOptions(
      deliveryDateApi,
      "branch",
      branchNum || 0
    ),
    enabled: Boolean(branchId && deliveryDate && branchNum > 0),
  });

  const availableClothes = clothesQuery.data?.available_clothes ?? [];

  const subtotal = useMemo(
    () =>
      selectedProducts.reduce(
        (s, p) => s + p.unitPrice * p.quantity,
        0
      ),
    [selectedProducts]
  );
  const vatPreview = useMemo(
    () => computeRentalVatPreviewFromBranch(subtotal, branchData),
    [subtotal, branchData]
  );
  const taxAmount = vatPreview.taxAmount;
  const totalWithTax = subtotal + taxAmount;
  const afterDiscount = totalWithTax - (hasDiscount ? discount : 0);
  const remaining = Math.max(0, afterDiscount - deposit);

  const daysOfRent = useMemo(
    () => computeDaysOfRentFromYmd(deliveryDate, returnDate),
    [deliveryDate, returnDate]
  );

  const filteredProducts = useMemo(() => {
    const q = productSearch.trim();
    return availableClothes.filter((p) => {
      if (selectedProducts.some((sp) => sp.cloth_id === p.id)) return false;
      if (!q) return true;
      const name = p.name ?? p.description ?? "";
      const cat = p.cloth_type?.name ?? "";
      const code = p.code ?? "";
      return (
        name.includes(q) || cat.includes(q) || code.includes(q)
      );
    });
  }, [availableClothes, productSearch, selectedProducts]);

  const handleBranchChange = useCallback((value: string) => {
    setBranchId(value);
    setEmployeeId("");
    setSelectedProducts([]);
  }, []);

  const addProduct = useCallback(
    (cloth: (typeof availableClothes)[0]) => {
      setSelectedProducts((prev) => [
        ...prev,
        {
          productId: String(cloth.id),
          cloth_id: cloth.id,
          name: cloth.name ?? cloth.description ?? `قطعة #${cloth.id}`,
          category: cloth.cloth_type?.name ?? "—",
          quantity: 1,
          unitPrice: 0,
        },
      ]);
      setShowProductPicker(false);
      setProductSearch("");
    },
    []
  );

  const removeProduct = useCallback((productId: string) => {
    setSelectedProducts((prev) => prev.filter((p) => p.productId !== productId));
  }, []);

  const updateUnitPrice = useCallback((productId: string, price: number) => {
    setSelectedProducts((prev) =>
      prev.map((p) =>
        p.productId === productId ? { ...p, unitPrice: price } : p
      )
    );
  }, []);

  const createOrderMutation = useMutation(useCreateOrderMutationOptions());
  const { mutate: createOrder, isPending: isCreatingOrder } =
    createOrderMutation;

  const validate = () => {
    const e: Record<string, string> = {};
    if (!branchId) e.branch = "الرجاء اختيار الفرع";
    if (!employeeId) e.employee = "الرجاء اختيار الموظف";
    if (clientMode === "existing") {
      if (!selectedClientId) {
        e.selectedClient = "الرجاء اختيار عميل من القائمة";
      }
    } else {
      if (!customerName.trim()) e.customerName = "الرجاء إدخال اسم العميل";
      if (!nationalId.trim()) e.nationalId = "الرجاء إدخال الرقم القومي";
      if (!phone.trim()) e.phone = "الرجاء إدخال رقم الهاتف";
      if (!address.trim()) e.address = "الرجاء إدخال العنوان";
      if (!cityId) e.city = "الرجاء اختيار المدينة";
    }
    if (!deliveryDate) e.deliveryDate = "الرجاء تحديد تاريخ التسليم";
    if (!eventDate) e.eventDate = "الرجاء تحديد تاريخ الفرح";
    if (!returnDate) e.returnDate = "الرجاء تحديد تاريخ الاسترجاع";
    if (isReturnYmdBeforeReceiveYmd(deliveryDate, returnDate)) {
      e.returnDate = "تاريخ الاسترجاع يجب أن يكون بعد تاريخ التسليم";
    }
    if (selectedProducts.length === 0)
      e.products = "الرجاء اختيار منتج واحد على الأقل";
    if (selectedProducts.some((p) => p.unitPrice <= 0))
      e.products = "الرجاء إدخال سعر إيجار أكبر من صفر لكل صنف";
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const buildOrderPayload = ():
    | TCreateOrderRequest
    | TCreateOrderWithNewClientRequest => {
    const visitFormatted = returnDate
      ? formatDateInputToOrderApi(returnDate)
      : undefined;
    const occasionFormatted = eventDate
      ? formatDateInputToOrderApi(eventDate)
      : undefined;
    const receiveFormatted = deliveryDate
      ? formatDateInputToOrderApi(deliveryDate)
      : format(new Date(), "yyyy-MM-dd HH:mm:ss");

    const orderNotesJoined =
      [notes.trim(), discountReason.trim()].filter(Boolean).join(" | ") ||
      undefined;

    const paidDeposit = Math.min(deposit, afterDiscount);

    const items = buildRentLineItemsForOrderApi({
      lines: selectedProducts.map((p) => ({
        cloth_id: p.cloth_id,
        price: p.unitPrice,
        quantity: p.quantity,
      })),
      daysOfRent,
      occasionApi: occasionFormatted,
      returnVisitApi: visitFormatted,
      paidDepositOnFirst: paidDeposit,
    });

    const discountPart =
      hasDiscount && discount > 0
        ? ({ discount_type: "fixed" as const, discount_value: discount } as const)
        : {};

    const common = {
      process_type: "rent" as const,
      employee_id: Number(employeeId),
      entity_type: "branch" as const,
      entity_id: Number(branchId),
      delivery_date: receiveFormatted,
      ...(visitFormatted && { visit_datetime: visitFormatted }),
      ...(occasionFormatted && { occasion_datetime: occasionFormatted }),
      days_of_rent: daysOfRent,
      ...(orderNotesJoined ? { order_notes: orderNotesJoined } : {}),
      ...discountPart,
      items,
    };

    if (clientMode === "existing") {
      return {
        existing_client: true,
        client_id: Number(selectedClientId),
        ...common,
      };
    }

    const phones: { phone: string; type: string }[] = [];
    if (phone.trim()) phones.push({ phone: phone.trim(), type: "mobile" });
    if (whatsapp.trim())
      phones.push({ phone: whatsapp.trim(), type: "whatsapp" });

    const client: TCreateClientRequest = {
      name: customerName.trim(),
      national_id: nationalId.trim() || undefined,
      source: "other",
      address: {
        city_id: Number(cityId),
        address: address.trim(),
      },
      phones,
    };

    return {
      existing_client: false,
      client,
      ...common,
    };
  };

  const handleSubmit = () => {
    if (!validate()) {
      setPopupStatus("error");
      return;
    }
    createOrder(buildOrderPayload(), {
      onSuccess: () => {
        setPopupStatus("success");
      },
      onError: (error: Error & { message?: string }) => {
        toast.error("فشل إنشاء الطلب", {
          description: error?.message ?? "حدث خطأ غير متوقع",
        });
      },
    });
  };

  const summaryData: SummaryData = {
    branch: branchLabel,
    employee: employeeLabel,
    customerName:
      clientMode === "existing"
        ? existingClientDisplayName
        : customerName,
    deliveryDate,
    eventDate,
    returnDate,
    products: selectedProducts.map((p) => ({
      name: p.name,
      category: p.category,
      quantity: p.quantity,
      unitPrice: p.unitPrice,
    })),
    vatMode: vatPreview.mode,
    vatPercentagePoints: vatPreview.percentagePoints,
    vatFixedAmount:
      vatPreview.mode === "fixed" ? vatPreview.taxAmount : undefined,
    deposit,
    discount: hasDiscount ? discount : 0,
    discountReason,
  };

  return (
    <div className="p-6" dir="rtl">
      <div className="mb-6 flex items-center gap-3">
        <button
          type="button"
          onClick={() => navigate("/orders/list")}
          className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-md border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50"
        >
          <i className="ri-arrow-right-line" />
        </button>
        <div>
          <h1
            className="text-xl font-bold text-slate-800"
            data-onboarding-target="reservation_created"
          >
            إنشاء فاتورة إيجار جديدة
          </h1>
          <p className="mt-0.5 text-sm text-slate-500">
            أدخل بيانات الفاتورة وسيتم تحديث الملخص تلقائياً
          </p>
        </div>
      </div>

      <div className="flex items-start gap-6">
        <div className="flex-1 space-y-5">
          <div className="rounded-lg border border-slate-200 bg-white p-5">
            <h2 className="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
              <i className="ri-map-pin-line text-slate-400" />
              الفرع والموظف المسؤول
            </h2>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">
                  الفرع <span className="text-red-500">*</span>
                </label>
                <BranchesSelect
                  value={branchId}
                  onChange={handleBranchChange}
                  className={
                    errors.branch ? "border border-red-300 rounded-md" : ""
                  }
                  placeholder="-- اختر الفرع --"
                />
                {errors.branch && (
                  <p className="mt-1 text-xs text-red-500">{errors.branch}</p>
                )}
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">
                  الموظف المسؤول <span className="text-red-500">*</span>
                </label>
                <EmployeesSelect
                  params={employeeParams}
                  value={employeeId}
                  onChange={setEmployeeId}
                  disabled={!branchId}
                  placeholder="-- اختر الموظف --"
                  className={
                    errors.employee ? "border border-red-300 rounded-md" : ""
                  }
                />
                {errors.employee && (
                  <p className="mt-1 text-xs text-red-500">{errors.employee}</p>
                )}
              </div>
            </div>
          </div>

          <div className="rounded-lg border border-slate-200 bg-white p-5">
            <h2 className="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
              <i className="ri-user-line text-slate-400" />
              بيانات العميل
            </h2>
            <div className="mb-4 flex gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1">
              <button
                type="button"
                onClick={() => {
                  setClientMode("existing");
                  setErrors((prev) => {
                    const next = { ...prev };
                    delete next.customerName;
                    delete next.nationalId;
                    delete next.phone;
                    delete next.address;
                    delete next.city;
                    return next;
                  });
                }}
                className={`flex-1 rounded-md px-3 py-2 text-xs font-medium transition-colors ${clientMode === "existing" ? "bg-white text-slate-800 shadow-sm" : "text-slate-600 hover:text-slate-800"}`}
              >
                عميل من المنصة
              </button>
              <button
                type="button"
                onClick={() => {
                  setClientMode("new");
                  setSelectedClientId("");
                  setErrors((prev) => {
                    const next = { ...prev };
                    delete next.selectedClient;
                    return next;
                  });
                }}
                className={`flex-1 rounded-md px-3 py-2 text-xs font-medium transition-colors ${clientMode === "new" ? "bg-white text-slate-800 shadow-sm" : "text-slate-600 hover:text-slate-800"}`}
              >
                عميل جديد
              </button>
            </div>

            {clientMode === "existing" ? (
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">
                  اختر العميل <span className="text-red-500">*</span>
                </label>
                <ClientsSelect
                  value={selectedClientId}
                  onChange={setSelectedClientId}
                  placeholder="ابحث واختر عميلاً..."
                  className={
                    errors.selectedClient ? "border border-red-300 rounded-md" : ""
                  }
                />
                {errors.selectedClient && (
                  <p className="mt-1 text-xs text-red-500">
                    {errors.selectedClient}
                  </p>
                )}
              </div>
            ) : (
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">
                    الاسم الكامل <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={customerName}
                    onChange={(e) => setCustomerName(e.target.value)}
                    placeholder="اسم العميل"
                    className={`w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200 ${errors.customerName ? "border-red-300" : "border-slate-200"}`}
                  />
                  {errors.customerName && (
                    <p className="mt-1 text-xs text-red-500">
                      {errors.customerName}
                    </p>
                  )}
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">
                    الرقم القومي / الهوية{" "}
                    <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={nationalId}
                    onChange={(e) => setNationalId(e.target.value)}
                    placeholder="رقم الهوية الوطنية"
                    className={`w-full rounded-md border px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-200 ${errors.nationalId ? "border-red-300" : "border-slate-200"}`}
                  />
                  {errors.nationalId && (
                    <p className="mt-1 text-xs text-red-500">
                      {errors.nationalId}
                    </p>
                  )}
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">
                    رقم الهاتف <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="tel"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    placeholder="05XXXXXXXX"
                    dir="ltr"
                    className={`w-full rounded-md border px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-200 ${errors.phone ? "border-red-300" : "border-slate-200"}`}
                  />
                  {errors.phone && (
                    <p className="mt-1 text-xs text-red-500">{errors.phone}</p>
                  )}
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">
                    هاتف الواتساب
                  </label>
                  <input
                    type="tel"
                    value={whatsapp}
                    onChange={(e) => setWhatsapp(e.target.value)}
                    placeholder="مطابق للهاتف إن كان نفسه"
                    dir="ltr"
                    className="w-full rounded-md border border-slate-200 px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
                  />
                </div>
                <div className="col-span-2">
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">
                    المدينة <span className="text-red-500">*</span>
                  </label>
                  <CitiesSelect value={cityId} onChange={setCityId} />
                  {errors.city && (
                    <p className="mt-1 text-xs text-red-500">{errors.city}</p>
                  )}
                </div>
                <div className="col-span-2">
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">
                    العنوان <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    placeholder="الدولة - المدينة - الشارع - رقم المبنى / الشقة"
                    className={`w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200 ${errors.address ? "border-red-300" : "border-slate-200"}`}
                  />
                  {errors.address && (
                    <p className="mt-1 text-xs text-red-500">{errors.address}</p>
                  )}
                </div>
              </div>
            )}
          </div>

          <div className="rounded-lg border border-slate-200 bg-white p-5">
            <h2 className="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
              <i className="ri-calendar-line text-slate-400" />
              تواريخ الإيجار
            </h2>
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">
                  تاريخ التسليم <span className="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  value={deliveryDate}
                  onChange={(e) => {
                    setDeliveryDate(e.target.value);
                    setSelectedProducts([]);
                  }}
                  className={`w-full cursor-pointer rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200 ${errors.deliveryDate ? "border-red-300" : "border-slate-200"}`}
                />
                {errors.deliveryDate && (
                  <p className="mt-1 text-xs text-red-500">
                    {errors.deliveryDate}
                  </p>
                )}
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">
                  تاريخ الفرح / المناسبة{" "}
                  <span className="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  value={eventDate}
                  onChange={(e) => setEventDate(e.target.value)}
                  className={`w-full cursor-pointer rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200 ${errors.eventDate ? "border-red-300" : "border-slate-200"}`}
                />
                {errors.eventDate && (
                  <p className="mt-1 text-xs text-red-500">{errors.eventDate}</p>
                )}
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">
                  تاريخ الاسترجاع <span className="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  value={returnDate}
                  onChange={(e) => setReturnDate(e.target.value)}
                  className={`w-full cursor-pointer rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200 ${errors.returnDate ? "border-red-300" : "border-slate-200"}`}
                />
                {errors.returnDate && (
                  <p className="mt-1 text-xs text-red-500">{errors.returnDate}</p>
                )}
              </div>
            </div>
          </div>

          <div className="rounded-lg border border-slate-200 bg-white p-5">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <i className="ri-shopping-bag-line text-slate-400" />
                المنتجات المختارة
              </h2>
              <button
                type="button"
                onClick={() => {
                  if (!branchId || !deliveryDate) {
                    toast.error("اختر الفرع وتاريخ التسليم أولاً لعرض المتوفر");
                    return;
                  }
                  setShowProductPicker(true);
                }}
                className="flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-md bg-slate-800 px-3 py-1.5 text-xs text-white transition-colors hover:bg-slate-700"
              >
                <i className="ri-add-line" />
                إضافة منتج
              </button>
            </div>
            {errors.products && (
              <p className="mb-2 text-xs text-red-500">{errors.products}</p>
            )}
            {selectedProducts.length === 0 ? (
              <div className="rounded-lg border-2 border-dashed border-slate-200 py-8 text-center">
                <i className="ri-archive-line mb-1 block text-3xl text-slate-300" />
                <p className="text-xs text-slate-400">
                  لم يتم اختيار أي منتج بعد
                </p>
                <button
                  type="button"
                  onClick={() => {
                    if (!branchId || !deliveryDate) {
                      toast.error("اختر الفرع وتاريخ التسليم أولاً");
                      return;
                    }
                    setShowProductPicker(true);
                  }}
                  className="mt-2 cursor-pointer text-xs text-slate-600 underline"
                >
                  اضغط لاختيار منتج من القائمة
                </button>
              </div>
            ) : (
              <div className="space-y-2">
                {selectedProducts.map((p) => (
                  <div
                    key={p.productId}
                    className="flex items-center justify-between rounded-md border border-slate-100 bg-slate-50 px-3 py-2.5"
                  >
                    <div className="min-w-0 flex-1">
                      <div className="text-sm font-medium text-slate-800">
                        {p.name}
                      </div>
                      <div className="text-xs text-slate-400">{p.category}</div>
                      <div className="mt-2 flex items-center gap-2">
                        <label className="text-xs text-slate-500">
                          سعر الإيجار (ج.م)
                        </label>
                        <input
                          type="number"
                          min={0}
                          value={p.unitPrice || ""}
                          onChange={(e) =>
                            updateUnitPrice(
                              p.productId,
                              Number(e.target.value)
                            )
                          }
                          className="w-28 rounded border border-slate-200 px-2 py-1 text-sm"
                        />
                      </div>
                    </div>
                    <button
                      type="button"
                      onClick={() => removeProduct(p.productId)}
                      className="flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center rounded text-red-400 transition-colors hover:bg-red-50 hover:text-red-600"
                    >
                      <i className="ri-close-line text-sm" />
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="rounded-lg border border-slate-200 bg-white p-5">
            <h2 className="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
              <i className="ri-money-dollar-circle-line text-slate-400" />
              التسعير والمدفوعات
            </h2>
            <div className="mb-4 grid grid-cols-3 gap-4">
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">
                  {taxAmount > 0
                    ? "الإجمالي (شامل الضريبة)"
                    : "الإجمالي"}
                </label>
                <div className="w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
                  {totalWithTax.toLocaleString("ar-EG")} ج.م
                </div>
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">
                  العربون / المقدم
                </label>
                <input
                  type="number"
                  min={0}
                  max={afterDiscount}
                  value={deposit || ""}
                  onChange={(e) => setDeposit(Number(e.target.value))}
                  placeholder="0"
                  className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">
                  المتبقي (يُحسب تلقائياً)
                </label>
                <div
                  className={`w-full rounded-md border px-3 py-2 text-sm font-bold ${remaining > 0 ? "border-rose-200 bg-rose-50 text-rose-700" : "border-emerald-200 bg-emerald-50 text-emerald-700"}`}
                >
                  {remaining.toLocaleString("ar-EG")} ج.م
                </div>
              </div>
            </div>

            <div className="border-t border-slate-100 pt-4">
              <label className="mb-3 flex cursor-pointer select-none items-center gap-2">
                <button
                  type="button"
                  onClick={() => setHasDiscount(!hasDiscount)}
                  className={`relative h-5 w-10 cursor-pointer rounded-full transition-colors ${hasDiscount ? "bg-amber-500" : "bg-slate-200"}`}
                >
                  <span
                    className={`absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform ${hasDiscount ? "translate-x-5" : "translate-x-0.5"}`}
                  />
                </button>
                <span className="text-sm font-medium text-slate-700">
                  تطبيق خصم
                </span>
              </label>
              {hasDiscount && (
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="mb-1.5 block text-xs font-medium text-slate-600">
                      مبلغ الخصم (ج.م)
                    </label>
                    <input
                      type="number"
                      min={0}
                      max={totalWithTax}
                      value={discount || ""}
                      onChange={(e) => setDiscount(Number(e.target.value))}
                      placeholder="0"
                      className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
                    />
                  </div>
                  <div>
                    <label className="mb-1.5 block text-xs font-medium text-slate-600">
                      سبب الخصم
                    </label>
                    <input
                      type="text"
                      value={discountReason}
                      onChange={(e) => setDiscountReason(e.target.value)}
                      placeholder="مثل: خصم عميل VIP"
                      className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
                    />
                  </div>
                </div>
              )}
            </div>
          </div>

          <div className="rounded-lg border border-slate-200 bg-white p-5">
            <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
              <i className="ri-sticky-note-line text-slate-400" />
              ملاحظات إضافية
            </h2>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="أي ملاحظات خاصة بهذه الفاتورة..."
              rows={3}
              maxLength={500}
              className="w-full resize-none rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
            />
            <p className="mt-1 text-xs text-slate-400">{notes.length}/500</p>
          </div>

          <div className="flex items-center justify-end gap-3 pb-6">
            <button
              type="button"
              onClick={() => navigate("/orders/list")}
              className="cursor-pointer whitespace-nowrap rounded-md border border-slate-200 px-6 py-2.5 text-sm text-slate-600 transition-colors hover:bg-slate-50"
            >
              إلغاء
            </button>
            <button
              type="button"
              onClick={handleSubmit}
              disabled={isCreatingOrder}
              className="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-md bg-slate-800 px-8 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-700 disabled:opacity-60"
            >
              {isCreatingOrder ? (
                <i className="ri-loader-4-line animate-spin" />
              ) : (
                <i className="ri-check-line" />
              )}
              تأكيد الطلب
            </button>
          </div>
        </div>

        <div className="w-72 shrink-0">
          <RentalCreateInvoiceSummary data={summaryData} />
        </div>
      </div>

      {showProductPicker && (
        <div
          role="presentation"
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/30"
          onClick={() => setShowProductPicker(false)}
        >
          <div
            role="dialog"
            className="flex max-h-[80vh] w-[540px] flex-col rounded-xl bg-white"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between border-b border-slate-100 p-4">
              <h3 className="font-bold text-slate-800">اختر منتجاً للإيجار</h3>
              <button
                type="button"
                onClick={() => setShowProductPicker(false)}
                className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-md hover:bg-slate-100"
              >
                <i className="ri-close-line text-slate-500" />
              </button>
            </div>
            <div className="p-4">
              <div className="relative">
                <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" />
                <input
                  type="text"
                  value={productSearch}
                  onChange={(e) => setProductSearch(e.target.value)}
                  placeholder="ابحث بالاسم أو الفئة أو الكود..."
                  className="w-full rounded-md border border-slate-200 py-2 pl-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
              {clothesQuery.isFetching && (
                <p className="mt-2 text-center text-xs text-slate-400">
                  جاري التحميل...
                </p>
              )}
            </div>
            <div className="flex-1 space-y-2 overflow-y-auto px-4 pb-4">
              {filteredProducts.length === 0 ? (
                <div className="py-8 text-center text-sm text-slate-400">
                  لا توجد منتجات متاحة
                </div>
              ) : (
                filteredProducts.map((p) => (
                  <button
                    key={p.id}
                    type="button"
                    onClick={() => addProduct(p)}
                    className="flex w-full cursor-pointer items-center justify-between rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-right transition-colors hover:bg-slate-100"
                  >
                    <div>
                      <div className="text-sm font-medium text-slate-800">
                        {p.name ?? p.description}
                      </div>
                      <div className="mt-0.5 text-xs text-slate-400">
                        {p.cloth_type?.name ?? "—"} &bull; {p.code}
                      </div>
                    </div>
                    <div className="whitespace-nowrap text-xs font-medium text-slate-500">
                      أدخل السعر بعد الإضافة
                    </div>
                  </button>
                ))
              )}
            </div>
          </div>
        </div>
      )}

      {popupStatus === "success" && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
          <div className="w-96 rounded-xl bg-white p-8 text-center">
            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50">
              <i className="ri-checkbox-circle-fill text-4xl text-emerald-500" />
            </div>
            <h3 className="mb-2 text-xl font-bold text-slate-800">
              تم إنشاء الفاتورة بنجاح!
            </h3>
            <p className="mb-6 text-sm text-slate-500">
              تمت إضافة فاتورة الإيجار الجديدة بنجاح إلى النظام.
            </p>
            <div className="flex gap-3">
              <button
                type="button"
                onClick={() => navigate("/orders/list")}
                className="flex-1 cursor-pointer whitespace-nowrap rounded-md border border-slate-200 py-2.5 text-sm text-slate-600 transition-colors hover:bg-slate-50"
              >
                العودة للقائمة
              </button>
              <button
                type="button"
                onClick={() => {
                  setPopupStatus(null);
                  setBranchId("");
                  setEmployeeId("");
                  setClientMode("existing");
                  setSelectedClientId("");
                  setCustomerName("");
                  setNationalId("");
                  setPhone("");
                  setWhatsapp("");
                  setAddress("");
                  setCityId("");
                  setDeliveryDate("");
                  setEventDate("");
                  setReturnDate("");
                  setSelectedProducts([]);
                  setDeposit(0);
                  setHasDiscount(false);
                  setDiscount(0);
                  setDiscountReason("");
                  setNotes("");
                  setErrors({});
                }}
                className="flex-1 cursor-pointer whitespace-nowrap rounded-md bg-slate-800 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-700"
              >
                فاتورة جديدة
              </button>
            </div>
          </div>
        </div>
      )}

      {popupStatus === "error" && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
          <div className="w-96 rounded-xl bg-white p-8 text-center">
            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-50">
              <i className="ri-close-circle-fill text-4xl text-red-500" />
            </div>
            <h3 className="mb-2 text-xl font-bold text-slate-800">
              يوجد بيانات ناقصة
            </h3>
            <p className="mb-4 text-sm text-slate-500">
              الرجاء مراجعة جميع الحقول المطلوبة وتعبئتها قبل تأكيد الطلب.
            </p>
            <div className="mb-5 rounded-md bg-red-50 p-3 text-right">
              {Object.values(errors).map((err, i) => (
                <div
                  key={i}
                  className="flex items-center gap-2 py-0.5 text-xs text-red-600"
                >
                  <i className="ri-error-warning-line shrink-0" />
                  {err}
                </div>
              ))}
            </div>
            <button
              type="button"
              onClick={() => setPopupStatus(null)}
              className="w-full cursor-pointer whitespace-nowrap rounded-md bg-slate-800 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-700"
            >
              حسناً، سأراجع البيانات
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
