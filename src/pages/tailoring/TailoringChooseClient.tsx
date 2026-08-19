import { useState, useMemo, useCallback } from "react";
import { useNavigate } from "react-router";
import { useQuery } from "@tanstack/react-query";
import { getSuppliersList } from "@/api/v2/suppliers/suppliers.service";
import { useCreateTailoringOrderMutation } from "@/api/v2/tailoring-orders/tailoringOrders.hooks";
import { useGetBranchQueryOptions } from "@/api/v2/branches/branches.hooks";
import { useGetEmployeeQueryOptions } from "@/api/v2/employees/employees.hooks";
import { useGetClientQueryOptions } from "@/api/v2/clients/clients.hooks";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { EmployeesSelect } from "@/components/custom/EmployeesSelect";
import { ClientsSelect } from "@/components/custom/ClientsSelect";
import { CitiesSelect } from "@/components/custom/CitiesSelect";
import {
  displayClientLabel,
  employeesParamsForOrderEntity,
} from "@/pages/orders/lib/orderCreationShared";
import { garmentTypes, fabricTypes } from "@/pages/tailoring/tailoring.ui";
import { toMysqlDateTime } from "./tailoringDatetime";
import { uiMeasurementsToApiPayload } from "./tailoringMeasurementsApi";

type PopupStatus = "success" | "error" | null;
type ClientMode = "existing" | "new";

const sourceOptions = ["إنستجرام", "واتساب", "توصية شخصية", "زبون قديم", "إعلان ممول", "تيك توك", "أخرى"];

export default function TailoringChooseClient() {
  const navigate = useNavigate();
  const createOrder = useCreateTailoringOrderMutation();

  const [branchId, setBranchId] = useState("");
  const [employeeId, setEmployeeId] = useState("");

  const [clientMode, setClientMode] = useState<ClientMode>("existing");
  const [selectedClientId, setSelectedClientId] = useState("");
  const [cityId, setCityId] = useState("");

  const [customerName, setCustomerName] = useState("");
  const [nationalId, setNationalId] = useState("");
  const [phone, setPhone] = useState("");
  const [whatsapp, setWhatsapp] = useState("");
  const [address, setAddress] = useState("");
  const [source, setSource] = useState("");

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
    selectedClientId && clientMode === "existing" ? Number(selectedClientId) : 0;
  const { data: selectedClientData } = useQuery({
    ...useGetClientQueryOptions(selectedClientNum),
    enabled: selectedClientNum > 0,
  });
  const existingClientDisplayName = displayClientLabel(selectedClientData);

  const employeeParams = useMemo(
    () => employeesParamsForOrderEntity("branch", branchId),
    [branchId],
  );

  const handleBranchChange = useCallback((value: string) => {
    setBranchId(value);
    setEmployeeId("");
  }, []);

  const { data: suppliersList } = useQuery({
    queryKey: ["suppliers", "tailoring-form"],
    queryFn: () => getSuppliersList(),
  });

  const [garmentType, setGarmentType] = useState("");
  const [fabricType, setFabricType] = useState("");
  const [fabricColor, setFabricColor] = useState("");
  const [fabricQty, setFabricQty] = useState("");
  const [fabricSupplierId, setFabricSupplierId] = useState("");
  const [fabricNotes, setFabricNotes] = useState("");

  const [designDesc, setDesignDesc] = useState("");
  const [designStyle, setDesignStyle] = useState("");
  const [hasEmbroidery, setHasEmbroidery] = useState(false);
  const [embNotes, setEmbNotes] = useState("");

  const [m, setM] = useState({ height: "", shoulder: "", chest: "", waist: "", hips: "", sleeveLength: "", sleeveWidth: "", dressLength: "", neckline: "", notes: "" });
  const setMeasure = (key: string, val: string) => setM((prev) => ({ ...prev, [key]: val }));

  const [dueDate, setDueDate] = useState("");
  const [eventDate, setEventDate] = useState("");
  const [price, setPrice] = useState(0);
  const [deposit, setDeposit] = useState(0);
  const [notes, setNotes] = useState("");

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [popup, setPopup] = useState<PopupStatus>(null);
  const [createdOrderId, setCreatedOrderId] = useState<number | null>(null);

  const remaining = Math.max(0, price - deposit);
  const selectedSupplier = suppliersList?.find((s) => String(s.id) === fabricSupplierId);

  const measurementRows = [
    { key: "height", label: "الطول الكلي" },
    { key: "shoulder", label: "الكتف" },
    { key: "chest", label: "الصدر" },
    { key: "waist", label: "الخصر" },
    { key: "hips", label: "الأرداف" },
    { key: "sleeveLength", label: "طول الكمام" },
    { key: "sleeveWidth", label: "اتساع الكمام" },
    { key: "dressLength", label: "طول الثوب" },
    { key: "neckline", label: "فتحة الرقبة" },
  ];

  const validate = () => {
    const e: Record<string, string> = {};
    if (!branchId) e.branch = "الرجاء اختيار الفرع";
    if (!employeeId) e.employee = "الرجاء اختيار الموظف المسؤول";
    if (clientMode === "existing") {
      if (!selectedClientId) e.selectedClient = "الرجاء اختيار عميلة من القائمة";
    } else {
      if (!customerName.trim()) e.customerName = "الرجاء إدخال اسم العميلة";
      if (!nationalId.trim()) e.nationalId = "الرجاء إدخال الرقم القومي / الهوية";
      if (!phone.trim()) e.phone = "الرجاء إدخال رقم الهاتف";
      if (!address.trim()) e.address = "الرجاء إدخال العنوان";
      if (!cityId) e.city = "الرجاء اختيار المدينة";
    }
    if (!garmentType) e.garmentType = "الرجاء اختيار نوع الثوب";
    if (!fabricType) e.fabricType = "الرجاء اختيار نوع القماش";
    if (!dueDate) e.dueDate = "الرجاء تحديد موعد التسليم";
    if (!eventDate) e.eventDate = "الرجاء تحديد تاريخ المناسبة";
    if (!price || price <= 0) e.price = "الرجاء إدخال سعر التفصيل";
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const handleSubmit = async () => {
    if (!validate()) {
      setPopup("error");
      return;
    }
    if (!dueDate || !eventDate) {
      setPopup("error");
      return;
    }
    const occasionDatetime = toMysqlDateTime(eventDate, "18:00:00");
    const deliveryDate = dueDate.slice(0, 10);
    const fabricNotesJoined = [fabricNotes, designDesc, designStyle, embNotes, notes].filter(Boolean).join("\n");

    const measurements = uiMeasurementsToApiPayload(m);

    const phonesNewClient: { phone: string; type: string }[] = [];
    if (clientMode === "new") {
      if (phone.trim()) phonesNewClient.push({ phone: phone.trim(), type: "mobile" });
      if (whatsapp.trim()) phonesNewClient.push({ phone: whatsapp.trim(), type: "whatsapp" });
    }

    try {
      const created = await createOrder.mutateAsync({
        existing_client: clientMode === "existing",
        ...(clientMode === "existing"
          ? { client_id: Number(selectedClientId) }
          : {
              client: {
                name: customerName.trim(),
                national_id: nationalId.trim() || null,
                address: {
                  city_id: Number(cityId),
                  address: address.trim() || "—",
                },
                phones: phonesNewClient,
              },
            }),
        branch_id: Number(branchId),
        employee_id: employeeId ? Number(employeeId) : undefined,
        total_price: price,
        initial_paid: deposit || 0,
        occasion_datetime: occasionDatetime,
        delivery_date: deliveryDate,
        fabric: {
          garment_type: garmentType,
          fabric_type: fabricType,
          color: fabricColor || null,
          quantity: fabricQty || null,
          supplier_id: fabricSupplierId ? Number(fabricSupplierId) : null,
          includes_embroidery: hasEmbroidery,
          notes: fabricNotesJoined || null,
        },
        measurements:
          measurements && Object.keys(measurements).length > 0 ? measurements : undefined,
      });
      if (created?.id) {
        setCreatedOrderId(created.id);
        setPopup("success");
      }
    } catch {
      setPopup("error");
    }
  };

  return (
    <div className="p-6" dir="rtl">
      <div className="flex items-center gap-3 mb-6">
        <button
          onClick={() => navigate("/tailoring/orders")}
          className="w-8 h-8 flex items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-50 cursor-pointer transition-colors"
        >
          <i className="ri-arrow-right-line" />
        </button>
        <div>
          <h1 className="text-xl font-bold text-slate-800">إنشاء أمر تفصيل جديد</h1>
          <p className="text-sm text-slate-500 mt-0.5">أدخل بيانات الأمر وسيتحدث الملخص تلقائياً</p>
        </div>
      </div>

      <div className="flex gap-6 items-start">
        <div className="flex-1 space-y-5">
          <div className="bg-white rounded-lg border border-slate-200 p-5">
            <h2 className="font-semibold text-slate-700 text-sm flex items-center gap-2 mb-4">
              <i className="ri-map-pin-line text-slate-400" /> الفرع والموظف المسؤول
            </h2>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">
                  الفرع <span className="text-red-500">*</span>
                </label>
                <BranchesSelect
                  value={branchId}
                  onChange={handleBranchChange}
                  className={errors.branch ? "border border-red-300 rounded-md" : ""}
                  placeholder="-- اختر الفرع --"
                />
                {errors.branch && <p className="text-xs text-red-500 mt-1">{errors.branch}</p>}
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">
                  الموظف المسؤول (الخياط) <span className="text-red-500">*</span>
                </label>
                <EmployeesSelect
                  params={employeeParams}
                  value={employeeId}
                  onChange={setEmployeeId}
                  disabled={!branchId}
                  placeholder="-- اختر الموظف --"
                  className={errors.employee ? "border border-red-300 rounded-md" : ""}
                />
                {errors.employee && <p className="text-xs text-red-500 mt-1">{errors.employee}</p>}
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg border border-slate-200 p-5">
            <h2 className="font-semibold text-slate-700 text-sm flex items-center gap-2 mb-4">
              <i className="ri-user-line text-slate-400" /> بيانات العميلة
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
                <label className="block text-xs font-medium text-slate-600 mb-1.5">
                  اختر العميلة <span className="text-red-500">*</span>
                </label>
                <ClientsSelect
                  value={selectedClientId}
                  onChange={setSelectedClientId}
                  placeholder="ابحثي واختاري عميلة..."
                  className={errors.selectedClient ? "border border-red-300 rounded-md" : ""}
                />
                {errors.selectedClient && (
                  <p className="text-xs text-red-500 mt-1">{errors.selectedClient}</p>
                )}
              </div>
            ) : (
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-medium text-slate-600 mb-1.5">
                    الاسم الكامل <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={customerName}
                    onChange={(e) => setCustomerName(e.target.value)}
                    placeholder="اسم العميلة"
                    className={`w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100 ${errors.customerName ? "border-red-300" : "border-slate-200"}`}
                  />
                  {errors.customerName && <p className="text-xs text-red-500 mt-1">{errors.customerName}</p>}
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-600 mb-1.5">
                    الرقم القومي / الهوية <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={nationalId}
                    onChange={(e) => setNationalId(e.target.value)}
                    placeholder="رقم الهوية"
                    dir="ltr"
                    className={`w-full border rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-sky-100 ${errors.nationalId ? "border-red-300" : "border-slate-200"}`}
                  />
                  {errors.nationalId && <p className="text-xs text-red-500 mt-1">{errors.nationalId}</p>}
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-600 mb-1.5">
                    رقم الهاتف <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="tel"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    placeholder="05XXXXXXXX"
                    dir="ltr"
                    className={`w-full border rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-sky-100 ${errors.phone ? "border-red-300" : "border-slate-200"}`}
                  />
                  {errors.phone && <p className="text-xs text-red-500 mt-1">{errors.phone}</p>}
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-600 mb-1.5">هاتف الواتساب</label>
                  <input
                    type="tel"
                    value={whatsapp}
                    onChange={(e) => setWhatsapp(e.target.value)}
                    placeholder="اختياري — نفس الهاتف إن وُجد"
                    dir="ltr"
                    className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-sky-100"
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-xs font-medium text-slate-600 mb-1.5">
                    المدينة <span className="text-red-500">*</span>
                  </label>
                  <CitiesSelect
                    value={cityId}
                    onChange={setCityId}
                    className={errors.city ? "border border-red-300 rounded-md" : ""}
                  />
                  {errors.city && <p className="text-xs text-red-500 mt-1">{errors.city}</p>}
                </div>
                <div className="col-span-2">
                  <label className="block text-xs font-medium text-slate-600 mb-1.5">
                    العنوان التفصيلي <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    placeholder="الحي، الشارع، رقم المبنى..."
                    className={`w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100 ${errors.address ? "border-red-300" : "border-slate-200"}`}
                  />
                  {errors.address && <p className="text-xs text-red-500 mt-1">{errors.address}</p>}
                </div>
                <div className="col-span-2">
                  <label className="block text-xs font-medium text-slate-600 mb-1.5">المصدر</label>
                  <select
                    value={source}
                    onChange={(e) => setSource(e.target.value)}
                    className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-sky-100 cursor-pointer"
                  >
                    <option value="">-- كيف وصلت إلينا؟ --</option>
                    {sourceOptions.map((s) => (
                      <option key={s} value={s}>
                        {s}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            )}
          </div>

          <div className="bg-white rounded-lg border border-slate-200 p-5">
            <h2 className="font-semibold text-slate-700 text-sm flex items-center gap-2 mb-4">
              <i className="ri-scissors-cut-line text-slate-400" /> القماش والتصميم
            </h2>
            <div className="grid grid-cols-3 gap-4 mb-4">
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">
                  نوع الثوب <span className="text-red-500">*</span>
                </label>
                <select
                  value={garmentType}
                  onChange={(e) => setGarmentType(e.target.value)}
                  className={`w-full border rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-sky-100 cursor-pointer ${errors.garmentType ? "border-red-300" : "border-slate-200"}`}
                >
                  <option value="">-- اختر --</option>
                  {garmentTypes.map((t) => (
                    <option key={t} value={t}>
                      {t}
                    </option>
                  ))}
                </select>
                {errors.garmentType && <p className="text-xs text-red-500 mt-1">{errors.garmentType}</p>}
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">
                  نوع القماش <span className="text-red-500">*</span>
                </label>
                <select
                  value={fabricType}
                  onChange={(e) => setFabricType(e.target.value)}
                  className={`w-full border rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-sky-100 cursor-pointer ${errors.fabricType ? "border-red-300" : "border-slate-200"}`}
                >
                  <option value="">-- اختر --</option>
                  {fabricTypes.map((t) => (
                    <option key={t} value={t}>
                      {t}
                    </option>
                  ))}
                </select>
                {errors.fabricType && <p className="text-xs text-red-500 mt-1">{errors.fabricType}</p>}
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">اللون</label>
                <input
                  type="text"
                  value={fabricColor}
                  onChange={(e) => setFabricColor(e.target.value)}
                  placeholder="مثل: ذهبي، أسود، عاجي"
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">الكمية (متر)</label>
                <input
                  type="text"
                  value={fabricQty}
                  onChange={(e) => setFabricQty(e.target.value)}
                  placeholder="مثل: 4 أمتار"
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100"
                />
              </div>
              <div className="col-span-2">
                <label className="block text-xs font-medium text-slate-600 mb-1.5">المورد</label>
                <select
                  value={fabricSupplierId}
                  onChange={(e) => setFabricSupplierId(e.target.value)}
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-sky-100 cursor-pointer"
                >
                  <option value="">-- اختر المورد --</option>
                  {suppliersList?.map((s) => (
                    <option key={s.id} value={String(s.id)}>
                      {s.name}
                    </option>
                  ))}
                </select>
                {selectedSupplier && (
                  <p className="text-xs text-sky-600 mt-1 flex items-center gap-1">
                    <i className="ri-map-pin-line" /> {selectedSupplier.address ?? "—"}
                  </p>
                )}
              </div>
              <div className="col-span-3">
                <label className="block text-xs font-medium text-slate-600 mb-1.5">ملاحظات القماش</label>
                <input
                  type="text"
                  value={fabricNotes}
                  onChange={(e) => setFabricNotes(e.target.value)}
                  placeholder="مثل: مستورد، خاص..."
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100"
                />
              </div>
            </div>
            <div className="space-y-3">
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">وصف التصميم</label>
                <textarea
                  value={designDesc}
                  onChange={(e) => setDesignDesc(e.target.value)}
                  placeholder="صف التصميم المطلوب بالتفصيل..."
                  rows={3}
                  maxLength={500}
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100 resize-none"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-medium text-slate-600 mb-1.5">الستايل</label>
                  <input
                    type="text"
                    value={designStyle}
                    onChange={(e) => setDesignStyle(e.target.value)}
                    placeholder="مثل: كلاسيك فاخر، رومانسي"
                    className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100"
                  />
                </div>
                <div>
                  <label className="flex items-center gap-2 cursor-pointer mt-6">
                    <div
                      onClick={() => setHasEmbroidery(!hasEmbroidery)}
                      className={`w-10 h-5 rounded-full transition-colors cursor-pointer relative ${hasEmbroidery ? "bg-pink-500" : "bg-slate-200"}`}
                    >
                      <div
                        className={`absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform ${hasEmbroidery ? "translate-x-5" : "translate-x-0.5"}`}
                      />
                    </div>
                    <span className="text-sm font-medium text-slate-700">يتضمن تطريز</span>
                  </label>
                </div>
              </div>
              {hasEmbroidery && (
                <div>
                  <label className="block text-xs font-medium text-slate-600 mb-1.5">تفاصيل التطريز</label>
                  <input
                    type="text"
                    value={embNotes}
                    onChange={(e) => setEmbNotes(e.target.value)}
                    placeholder="نوع التطريز، الألوان، الموضع..."
                    className="w-full border border-pink-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-100 bg-pink-50"
                  />
                </div>
              )}
            </div>
          </div>

          <div className="bg-white rounded-lg border border-slate-200 p-5">
            <h2 className="font-semibold text-slate-700 text-sm flex items-center gap-2 mb-4">
              <i className="ri-ruler-line text-slate-400" /> القياسات <span className="text-xs font-normal text-slate-400">(بالسنتيمتر)</span>
            </h2>
            <div className="grid grid-cols-3 gap-4 mb-3">
              {measurementRows.map((r) => (
                <div key={r.key}>
                  <label className="block text-xs font-medium text-slate-600 mb-1.5">
                    {r.label} <span className="text-slate-400 font-normal">سم</span>
                  </label>
                  <input
                    type="number"
                    min={0}
                    value={(m as Record<string, string>)[r.key]}
                    onChange={(e) => setMeasure(r.key, e.target.value)}
                    placeholder="0"
                    className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100"
                  />
                </div>
              ))}
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1.5">ملاحظات القياسات</label>
              <input
                type="text"
                value={m.notes}
                onChange={(e) => setMeasure("notes", e.target.value)}
                placeholder="مثل: الكتف الأيسر أعلى، فرق في الورك..."
                className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100"
              />
            </div>
          </div>

          <div className="bg-white rounded-lg border border-slate-200 p-5">
            <h2 className="font-semibold text-slate-700 text-sm flex items-center gap-2 mb-4">
              <i className="ri-calendar-line text-slate-400" /> التواريخ والتسعير
            </h2>
            <div className="grid grid-cols-2 gap-4 mb-4">
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">
                  موعد التسليم <span className="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  value={dueDate}
                  onChange={(e) => setDueDate(e.target.value)}
                  className={`w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100 cursor-pointer ${errors.dueDate ? "border-red-300" : "border-slate-200"}`}
                />
                {errors.dueDate && <p className="text-xs text-red-500 mt-1">{errors.dueDate}</p>}
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">
                  تاريخ المناسبة <span className="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  value={eventDate}
                  onChange={(e) => setEventDate(e.target.value)}
                  className={`w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100 cursor-pointer ${errors.eventDate ? "border-red-300" : "border-slate-200"}`}
                />
                {errors.eventDate && <p className="text-xs text-red-500 mt-1">{errors.eventDate}</p>}
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">
                  سعر التفصيل <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  min={0}
                  value={price || ""}
                  onChange={(e) => setPrice(Number(e.target.value))}
                  placeholder="0"
                  className={`w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100 ${errors.price ? "border-red-300" : "border-slate-200"}`}
                />
                {errors.price && <p className="text-xs text-red-500 mt-1">{errors.price}</p>}
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-600 mb-1.5">العربون / المقدم</label>
                <input
                  type="number"
                  min={0}
                  max={price}
                  value={deposit || ""}
                  onChange={(e) => setDeposit(Number(e.target.value))}
                  placeholder="0"
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100"
                />
              </div>
            </div>
            <div className="flex items-center justify-between bg-rose-50 border border-rose-100 rounded-lg px-4 py-2.5">
              <span className="text-sm font-bold text-rose-700">المتبقي</span>
              <span className="text-base font-black text-rose-700">{remaining.toLocaleString()} ج.م</span>
            </div>
          </div>

          <div className="bg-white rounded-lg border border-slate-200 p-5">
            <h2 className="font-semibold text-slate-700 text-sm flex items-center gap-2 mb-3">
              <i className="ri-sticky-note-line text-slate-400" /> ملاحظات
            </h2>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="أي ملاحظات إضافية..."
              rows={3}
              maxLength={500}
              className="w-full border border-slate-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-100 resize-none"
            />
          </div>

          <div className="flex items-center justify-end gap-3 pb-6">
            <button
              onClick={() => navigate("/tailoring/orders")}
              className="px-6 py-2.5 rounded-md border border-slate-200 text-slate-600 text-sm hover:bg-slate-50 cursor-pointer whitespace-nowrap"
            >
              إلغاء
            </button>
            <button
              type="button"
              disabled={createOrder.isPending}
              onClick={handleSubmit}
              className="px-8 py-2.5 rounded-md bg-sky-500 text-white text-sm font-medium hover:bg-sky-600 cursor-pointer flex items-center gap-2 whitespace-nowrap transition-colors disabled:opacity-60"
            >
              <i className={`ri-check-line ${createOrder.isPending ? "animate-pulse" : ""}`} />{" "}
              {createOrder.isPending ? "جاري الحفظ..." : "حفظ الأمر"}
            </button>
          </div>
        </div>

        <div className="w-64 shrink-0 sticky top-6">
          <div className="bg-white rounded-lg border border-slate-200 p-5">
            <div className="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
              <div className="w-8 h-8 flex items-center justify-center bg-amber-50 rounded-md">
                <i className="ri-scissors-cut-line text-amber-600" />
              </div>
              <h3 className="font-bold text-slate-800 text-sm">ملخص الأمر</h3>
            </div>

            {!branchId &&
            !garmentType &&
            (clientMode === "existing" ? !selectedClientId : !customerName.trim()) ? (
              <div className="text-center py-6">
                <i className="ri-file-add-line text-3xl text-slate-200 block mb-2" />
                <p className="text-xs text-slate-400">ابدأ بإدخال البيانات</p>
              </div>
            ) : (
              <div className="space-y-3 text-xs">
                {branchLabel && (
                  <div className="flex justify-between">
                    <span className="text-slate-400">الفرع</span>
                    <span className="font-medium text-slate-700">{branchLabel}</span>
                  </div>
                )}
                {(clientMode === "existing" ? existingClientDisplayName : customerName) && (
                  <div className="bg-slate-50 rounded-md p-2.5">
                    <span className="text-slate-400 block mb-0.5">العميلة</span>
                    <span className="font-bold text-slate-800">
                      {clientMode === "existing" ? existingClientDisplayName || "—" : customerName}
                    </span>
                    {clientMode === "existing" && selectedClientData?.phones?.[0]?.phone && (
                      <span className="text-slate-400 block mt-0.5">
                        {selectedClientData.phones[0].phone}
                      </span>
                    )}
                    {clientMode === "new" && phone && (
                      <span className="text-slate-400 block mt-0.5">{phone}</span>
                    )}
                    {clientMode === "new" && source && (
                      <span className="text-sky-600 block mt-0.5">
                        <i className="ri-link-m" /> {source}
                      </span>
                    )}
                  </div>
                )}
                {garmentType && (
                  <div className="flex justify-between">
                    <span className="text-slate-400">الثوب</span>
                    <span className="font-medium text-slate-700">{garmentType}</span>
                  </div>
                )}
                {fabricType && (
                  <div className="flex justify-between">
                    <span className="text-slate-400">القماش</span>
                    <span className="font-medium text-slate-700">
                      {fabricType}
                      {fabricColor ? ` — ${fabricColor}` : ""}
                    </span>
                  </div>
                )}
                {selectedSupplier && (
                  <div className="flex justify-between">
                    <span className="text-slate-400">المورد</span>
                    <span className="font-medium text-slate-700 text-left">{selectedSupplier.name}</span>
                  </div>
                )}
                {employeeLabel && (
                  <div className="flex justify-between">
                    <span className="text-slate-400">الموظف المسؤول</span>
                    <span className="font-medium text-slate-700">{employeeLabel}</span>
                  </div>
                )}
                {hasEmbroidery && (
                  <div className="flex items-center gap-1 text-pink-600">
                    <i className="ri-magic-line" />
                    <span>يتضمن تطريز</span>
                  </div>
                )}
                {dueDate && (
                  <div className="flex justify-between">
                    <span className="text-slate-400">التسليم</span>
                    <span className="font-medium text-slate-700">{dueDate}</span>
                  </div>
                )}
                {price > 0 && (
                  <div className="border-t border-slate-100 pt-3 space-y-2">
                    <div className="flex justify-between">
                      <span className="text-slate-400">السعر</span>
                      <span className="font-bold text-slate-700">{price.toLocaleString()} ج.م</span>
                    </div>
                    {deposit > 0 && (
                      <div className="flex justify-between">
                        <span className="text-emerald-600">العربون</span>
                        <span className="font-semibold text-emerald-700">{deposit.toLocaleString()} ج.م</span>
                      </div>
                    )}
                    <div className="flex justify-between bg-rose-50 rounded px-2 py-1.5">
                      <span className="text-rose-700 font-bold">المتبقي</span>
                      <span className="font-bold text-rose-700">{remaining.toLocaleString()} ج.م</span>
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      </div>

      {popup === "success" && (
        <div className="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl w-96 p-8 text-center">
            <div className="w-16 h-16 flex items-center justify-center bg-emerald-50 rounded-full mx-auto mb-4">
              <i className="ri-checkbox-circle-fill text-4xl text-emerald-500" />
            </div>
            <h3 className="text-xl font-bold text-slate-800 mb-2">تم حفظ الأمر بنجاح!</h3>
            <p className="text-sm text-slate-500 mb-6">تم إنشاء أمر التفصيل وبدأ في مرحلة الطلب الجديد.</p>
            <div className="flex gap-3">
              <button
                type="button"
                onClick={() => navigate("/tailoring/orders")}
                className="flex-1 py-2.5 rounded-md border border-slate-200 text-slate-600 text-sm cursor-pointer whitespace-nowrap"
              >
                العودة للقائمة
              </button>
              <button
                type="button"
                onClick={() => {
                  setPopup(null);
                  if (createdOrderId) navigate(`/tailoring/orders/${createdOrderId}`);
                }}
                className="flex-1 py-2.5 rounded-md bg-sky-500 text-white text-sm font-medium cursor-pointer whitespace-nowrap"
              >
                عرض الأمر
              </button>
            </div>
          </div>
        </div>
      )}

      {popup === "error" && (
        <div className="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl w-96 p-8 text-center">
            <div className="w-16 h-16 flex items-center justify-center bg-red-50 rounded-full mx-auto mb-4">
              <i className="ri-close-circle-fill text-4xl text-red-500" />
            </div>
            <h3 className="text-xl font-bold text-slate-800 mb-2">بيانات ناقصة</h3>
            <div className="bg-red-50 rounded-md p-3 text-right mb-5 space-y-1">
              {Object.values(errors).map((err, i) => (
                <div key={i} className="flex items-center gap-2 text-xs text-red-600">
                  <i className="ri-error-warning-line shrink-0" />
                  {err}
                </div>
              ))}
            </div>
            <button
              onClick={() => setPopup(null)}
              className="w-full py-2.5 rounded-md bg-slate-800 text-white text-sm font-medium cursor-pointer whitespace-nowrap"
            >
              مراجعة البيانات
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
