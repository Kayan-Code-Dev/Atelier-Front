import { useMemo, useState } from "react";
import { useNavigate } from "react-router";
import { format, parse } from "date-fns";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { EmployeesSelect } from "@/components/custom/EmployeesSelect";
import { ClientsSelect } from "@/components/custom/ClientsSelect";
import { CitiesSelect } from "@/components/custom/CitiesSelect";
import { useGetBranchQueryOptions } from "@/api/v2/branches/branches.hooks";
import { useCreateOrderMutationOptions } from "@/api/v2/orders/orders.hooks";
import { useGetClothesQueryOptions } from "@/api/v2/clothes/clothes.hooks";
import type { TClothResponse } from "@/api/v2/clothes/clothes.types";
import type { TCreateOrderRequest, TCreateOrderWithNewClientRequest } from "@/api/v2/orders/orders.types";
import type { TCreateClientRequest } from "@/api/v2/clients/clients.types";
import { SOLD_PROCESS_TYPE } from "@/lib/salesOrderConstants";
import { cn } from "@/lib/utils";
import { employeesParamsForOrderEntity } from "@/pages/orders/lib/orderCreationShared";
import { soldOrdersListPath } from "@/pages/sales/salesOrderPaths";
import {
  saleClientModeBarClass,
  saleCreatePageGridClass,
  saleCreatePageMainColClass,
  saleCreatePageSidebarClass,
  saleDateInputClass,
  saleInputClass,
  saleLabelClass,
  saleProjectSelectTriggerClass,
  salePrimaryAddBtnClass,
  saleProductGridHeaderClass,
  saleProductGridRowClass,
  saleProductGridTemplate,
  saleSectionCardClass,
  saleSectionNotesTitleClass,
  saleSectionTitleClass,
  saleSelectClass,
  saleTextareaClass,
} from "@/pages/sales/createSaleInvoiceProjectStyles";

function dateStrToApi(s: string): string {
  const d = parse(s, "yyyy-MM-dd", new Date());
  return format(d, "yyyy-MM-dd HH:mm:ss");
}

type TClothListRow = TClothResponse & {
  price?: number | string;
  name?: string;
};

type CartLine = {
  clothId: number;
  code: string;
  name: string;
  categoryLabel: string;
  quantity: number;
  unitPrice: number;
};

type FormState = {
  discount: number;
  discountReason: string;
  taxRate: number;
  notes: string;
  depositAmount: number;
  depositMethod: string;
};

const defaultForm: FormState = {
  discount: 0,
  discountReason: "",
  taxRate: 15,
  notes: "",
  depositAmount: 0,
  depositMethod: "نقدي",
};

export default function CreateSaleInvoicePage() {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState<"existing" | "new">("existing");
  const [selectedClientId, setSelectedClientId] = useState("");
  const [newClient, setNewClient] = useState({
    name: "",
    nationalId: "",
    phone: "",
    whatsapp: "",
    address: "",
    cityId: "",
  });

  const [entityId, setEntityId] = useState("");
  const [employeeId, setEmployeeId] = useState("");
  const [receiveDateStr, setReceiveDateStr] = useState(() =>
    new Date().toISOString().slice(0, 10)
  );
  const [branchDateStr, setBranchDateStr] = useState(() =>
    new Date().toISOString().slice(0, 10)
  );
  const [form, setForm] = useState<FormState>({ ...defaultForm });

  const [products, setProducts] = useState<CartLine[]>([]);
  const [selectedProductId, setSelectedProductId] = useState<string>("");
  const [showSuccess, setShowSuccess] = useState(false);

  const setField = (k: keyof FormState, v: string | number) =>
    setForm((f) => ({ ...f, [k]: v }));

  const clothesParams = useMemo(
    () => ({
      page: 1,
      per_page: 80,
      entity_type: "branch" as const,
      entity_id: Number(entityId),
      ...(receiveDateStr && {
        delivery_date: `${receiveDateStr} 00:00:00`,
      }),
      ...(branchDateStr && {
        occasion_datetime: `${branchDateStr} 00:00:00`,
      }),
    }),
    [entityId, receiveDateStr, branchDateStr]
  );

  const canFetchProducts = Boolean(entityId && receiveDateStr && branchDateStr);

  const { data: clothesData, isPending: clothesLoading } = useQuery({
    ...useGetClothesQueryOptions(clothesParams),
    enabled: canFetchProducts,
  });

  const { data: branchDetail } = useQuery({
    ...useGetBranchQueryOptions(Number(entityId)),
    enabled: Boolean(entityId),
  });

  const availableClothes = clothesData?.data ?? [];

  const { mutate: createOrder, isPending: isSubmitting } = useMutation(
    useCreateOrderMutationOptions()
  );

  const pricing = useMemo(() => {
    const subtotal = products.reduce((s, p) => s + p.unitPrice * p.quantity, 0);
    const taxAmount = subtotal * (form.taxRate / 100);
    const totalWithTax = subtotal + taxAmount - form.discount;
    const remaining = totalWithTax - form.depositAmount;
    return { subtotal, taxAmount, totalWithTax, remaining };
  }, [products, form.taxRate, form.discount, form.depositAmount]);

  const addProduct = () => {
    const id = Number(selectedProductId);
    if (!id) return;
    const cloth = availableClothes.find((c) => c.id === id) as
      | TClothListRow
      | undefined;
    if (!cloth) return;
    const unit = Number(cloth.price ?? 0);
    const name = cloth.name ?? cloth.code;
    const categoryLabel = cloth.category_name ?? "—";
    const existing = products.find((p) => p.clothId === id);
    if (existing) {
      setProducts((prev) =>
        prev.map((p) =>
          p.clothId === id ? { ...p, quantity: p.quantity + 1 } : p
        )
      );
    } else {
      setProducts((prev) => [
        ...prev,
        {
          clothId: id,
          code: cloth.code,
          name,
          categoryLabel,
          quantity: 1,
          unitPrice: unit,
        },
      ]);
    }
    setSelectedProductId("");
  };

  const removeProduct = (clothId: number) =>
    setProducts((prev) => prev.filter((p) => p.clothId !== clothId));

  const updateQty = (clothId: number, qty: number) => {
    if (qty < 1) return;
    setProducts((prev) =>
      prev.map((p) => (p.clothId === clothId ? { ...p, quantity: qty } : p))
    );
  };

  const updatePrice = (clothId: number, price: number) => {
    setProducts((prev) =>
      prev.map((p) => (p.clothId === clothId ? { ...p, unitPrice: price } : p))
    );
  };

  const employeeParams = useMemo(
    () => employeesParamsForOrderEntity("branch", entityId),
    [entityId]
  );

  const buildItemsPayloadFixed = () => {
    let remainingDeposit = Math.max(0, form.depositAmount);
    return products.map((line) => {
      const lineTotal = line.unitPrice * line.quantity;
      const paid = Math.min(remainingDeposit, lineTotal);
      remainingDeposit -= paid;
      return {
        cloth_id: line.clothId,
        price: line.unitPrice,
        quantity: line.quantity,
        paid,
        type: "buy" as const,
        process_type: SOLD_PROCESS_TYPE,
      };
    });
  };

  const handleSubmit = () => {
    if (!entityId || !employeeId || !receiveDateStr || !branchDateStr) {
      toast.error("أكمل الفرع والموظف وتواريخ الفاتورة والمناسبة");
      return;
    }
    if (products.length === 0) {
      toast.error("أضف منتجاً واحداً على الأقل");
      return;
    }

    if (activeTab === "existing") {
      if (!selectedClientId) {
        toast.error("اختر عميلاً");
        return;
      }
    } else {
      if (!newClient.name.trim() || !newClient.phone.trim() || !newClient.cityId || !newClient.address.trim()) {
        toast.error("أكمل بيانات العميل الجديد (الاسم، الهاتف، المدينة، العنوان)");
        return;
      }
    }

    const deliveryDateStr = dateStrToApi(receiveDateStr);
    const occasionStr = dateStrToApi(branchDateStr);

    const hasOrderDiscount =
      form.discount > 0;

    const items = buildItemsPayloadFixed();

    const basePayload = {
      process_type: SOLD_PROCESS_TYPE,
      employee_id: Number(employeeId),
      entity_type: "branch" as const,
      entity_id: Number(entityId),
      delivery_date: deliveryDateStr,
      visit_datetime: deliveryDateStr,
      occasion_datetime: occasionStr,
      order_notes: form.notes.trim() || undefined,
      ...(hasOrderDiscount
        ? {
            discount_type: "fixed" as const,
            discount_value: form.discount,
          }
        : {}),
      items,
    };

    if (activeTab === "existing") {
      const payload: TCreateOrderRequest = {
        existing_client: true,
        client_id: Number(selectedClientId),
        ...basePayload,
      };
      createOrder(payload, {
        onSuccess: () => {
          setShowSuccess(true);
          setTimeout(() => {
            navigate(soldOrdersListPath());
          }, 1800);
        },
        onError: (e: { message?: string }) => {
          toast.error("فشل إنشاء الفاتورة", { description: e?.message });
        },
      });
    } else {
      const phones: { phone: string; type: string }[] = [];
      if (newClient.phone.trim()) phones.push({ phone: newClient.phone.trim(), type: "mobile" });
      if (newClient.whatsapp.trim()) phones.push({ phone: newClient.whatsapp.trim(), type: "whatsapp" });
      const client: TCreateClientRequest = {
        name: newClient.name.trim(),
        national_id: newClient.nationalId.trim() || undefined,
        source: "other",
        address: {
          city_id: Number(newClient.cityId),
          address: newClient.address.trim(),
        },
        phones: phones.length ? phones : [{ phone: newClient.phone.trim(), type: "mobile" }],
      };
      const payload: TCreateOrderWithNewClientRequest = {
        existing_client: false,
        client,
        ...basePayload,
      };
      createOrder(payload, {
        onSuccess: () => {
          setShowSuccess(true);
          setTimeout(() => {
            navigate(soldOrdersListPath());
          }, 1800);
        },
        onError: (e: { message?: string }) => {
          toast.error("فشل إنشاء الفاتورة", { description: e?.message });
        },
      });
    }
  };

  const canSubmit =
    entityId &&
    employeeId &&
    receiveDateStr &&
    branchDateStr &&
    products.length > 0 &&
    (activeTab === "existing"
      ? !!selectedClientId
      : newClient.name.trim() && newClient.phone.trim() && newClient.cityId && newClient.address.trim());

  return (
    <div className="p-6 min-h-screen bg-slate-50" dir="rtl">
      <div className="flex items-center gap-3 mb-6">
        <button
          type="button"
          onClick={() => navigate(soldOrdersListPath())}
          className="w-9 h-9 rounded-xl flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 cursor-pointer transition-colors"
        >
          <i className="ri-arrow-right-line" />
        </button>
        <div>
          <h1 className="text-xl font-bold text-slate-800">إنشاء فاتورة بيع جديدة</h1>
          <p className="text-xs text-slate-400">أدخل بيانات العميلة والمنتجات لإنشاء الفاتورة</p>
        </div>
      </div>

      <div className={saleCreatePageGridClass}>
        <div className={saleCreatePageMainColClass}>
          <div className={saleSectionCardClass}>
            <h2 className={saleSectionTitleClass}>
              <i className="ri-user-3-line text-indigo-500" /> بيانات العميلة
            </h2>
            <div className={saleClientModeBarClass}>
              <button
                type="button"
                onClick={() => setActiveTab("existing")}
                className={cn(
                  "flex-1 rounded-md py-2 text-sm font-medium transition-colors",
                  activeTab === "existing"
                    ? "bg-white text-indigo-700 shadow-sm"
                    : "text-slate-600 hover:text-slate-800"
                )}
              >
                عميل مسجل
              </button>
              <button
                type="button"
                onClick={() => setActiveTab("new")}
                className={cn(
                  "flex-1 rounded-md py-2 text-sm font-medium transition-colors",
                  activeTab === "new"
                    ? "bg-white text-indigo-700 shadow-sm"
                    : "text-slate-600 hover:text-slate-800"
                )}
              >
                عميل جديد
              </button>
            </div>
            {activeTab === "existing" ? (
              <div className="space-y-3">
                <label className={saleLabelClass}>اختر العميلة *</label>
                <ClientsSelect
                  value={selectedClientId}
                  onChange={setSelectedClientId}
                  className={saleProjectSelectTriggerClass}
                />
              </div>
            ) : (
              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className={saleLabelClass}>اسم العميلة *</label>
                    <input
                      className={saleInputClass}
                      placeholder="الاسم كاملاً"
                      value={newClient.name}
                      onChange={(e) => setNewClient((c) => ({ ...c, name: e.target.value }))}
                    />
                  </div>
                  <div>
                    <label className={saleLabelClass}>رقم الهوية</label>
                    <input
                      className={saleInputClass}
                      placeholder="رقم الهوية الوطنية"
                      value={newClient.nationalId}
                      onChange={(e) => setNewClient((c) => ({ ...c, nationalId: e.target.value }))}
                    />
                  </div>
                  <div>
                    <label className={saleLabelClass}>رقم الهاتف *</label>
                    <input
                      className={saleInputClass}
                      placeholder="05xxxxxxxx"
                      value={newClient.phone}
                      onChange={(e) => setNewClient((c) => ({ ...c, phone: e.target.value }))}
                    />
                  </div>
                  <div>
                    <label className={saleLabelClass}>واتساب</label>
                    <input
                      className={saleInputClass}
                      placeholder="05xxxxxxxx"
                      value={newClient.whatsapp}
                      onChange={(e) => setNewClient((c) => ({ ...c, whatsapp: e.target.value }))}
                    />
                  </div>
                  <div className="col-span-2">
                    <label className={saleLabelClass}>المدينة *</label>
                    <CitiesSelect
                      value={newClient.cityId}
                      onChange={(v) => setNewClient((c) => ({ ...c, cityId: v }))}
                      className={saleProjectSelectTriggerClass}
                    />
                  </div>
                  <div className="col-span-2">
                    <label className={saleLabelClass}>العنوان *</label>
                    <input
                      className={saleInputClass}
                      placeholder="المدينة - الحي - الشارع"
                      value={newClient.address}
                      onChange={(e) => setNewClient((c) => ({ ...c, address: e.target.value }))}
                    />
                  </div>
                </div>
              </div>
            )}
          </div>

          <div className={saleSectionCardClass}>
            <h2 className={saleSectionTitleClass}>
              <i className="ri-file-list-3-line text-indigo-500" /> بيانات الفاتورة
            </h2>
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className={saleLabelClass}>الفرع *</label>
                <BranchesSelect
                  value={entityId}
                  onChange={setEntityId}
                  className={saleProjectSelectTriggerClass}
                />
              </div>
              <div>
                <label className={saleLabelClass}>الموظفة *</label>
                <EmployeesSelect
                  params={employeeParams}
                  value={employeeId}
                  onChange={setEmployeeId}
                  disabled={!entityId}
                  placeholder="اختر الموظفة..."
                  className={saleProjectSelectTriggerClass}
                />
              </div>
              <div>
                <label className={saleLabelClass}>تاريخ الفاتورة *</label>
                <input
                  type="date"
                  className={saleDateInputClass}
                  value={receiveDateStr}
                  onChange={(e) => setReceiveDateStr(e.target.value)}
                />
              </div>
            </div>
            <div className="mt-4 grid grid-cols-3 gap-4">
              <div>
                <label className={saleLabelClass}>تاريخ المناسبة (الفرح) *</label>
                <input
                  type="date"
                  className={saleDateInputClass}
                  value={branchDateStr}
                  onChange={(e) => setBranchDateStr(e.target.value)}
                />
              </div>
            </div>
          </div>

          <div className={saleSectionCardClass}>
            <h2 className={saleSectionTitleClass}>
              <i className="ri-shopping-bag-3-line text-indigo-500" /> المنتجات
            </h2>
            <div className="mb-4 flex gap-2">
              <select
                className={`${saleSelectClass} flex-1 disabled:opacity-50`}
                value={selectedProductId}
                onChange={(e) => setSelectedProductId(e.target.value)}
                disabled={!canFetchProducts || clothesLoading}
              >
                <option value="">
                  {clothesLoading ? "جاري التحميل..." : "— اختيار منتج —"}
                </option>
                {availableClothes.map((c: TClothListRow) => (
                  <option key={c.id} value={String(c.id)}>
                    {c.code} — {Number(c.price ?? 0).toLocaleString("ar-SA")} ﷼
                  </option>
                ))}
              </select>
              <button
                type="button"
                onClick={addProduct}
                disabled={!selectedProductId}
                className={salePrimaryAddBtnClass}
                style={{ background: "#6366F1" }}
              >
                <i className="ri-add-line ml-1" />
                إضافة
              </button>
            </div>

            {products.length === 0 ? (
              <div className="py-8 text-center text-slate-400 text-sm border border-dashed border-slate-200 rounded-lg">
                <i className="ri-shopping-bag-line text-2xl block mb-2" />
                لم يتم إضافة أي منتجات بعد
              </div>
            ) : (
              <div className="overflow-hidden rounded-lg border border-slate-100">
                <div
                  className={saleProductGridHeaderClass}
                  style={saleProductGridTemplate}
                >
                  <span>المنتج</span>
                  <span>الكمية</span>
                  <span>السعر</span>
                  <span>الإجمالي</span>
                  <span />
                </div>
                {products.map((p) => (
                  <div
                    key={p.clothId}
                    className={saleProductGridRowClass}
                    style={saleProductGridTemplate}
                  >
                    <div>
                      <div className="text-sm font-semibold text-slate-800">{p.name}</div>
                      <div className="text-xs text-slate-400">{p.categoryLabel}</div>
                    </div>
                    <input
                      type="number"
                      min={1}
                      className="w-20 rounded-lg border border-slate-200 px-2 py-1.5 text-center text-sm outline-none focus:border-indigo-400"
                      value={p.quantity}
                      onChange={(e) =>
                        updateQty(p.clothId, parseInt(e.target.value, 10) || 1)
                      }
                    />
                    <input
                      type="number"
                      min={0}
                      step="0.01"
                      className="w-28 rounded-lg border border-slate-200 px-2 py-1.5 text-center text-sm outline-none focus:border-indigo-400"
                      value={p.unitPrice}
                      onChange={(e) =>
                        updatePrice(p.clothId, parseFloat(e.target.value) || 0)
                      }
                    />
                    <span className="text-sm font-bold text-slate-700">
                      {(p.unitPrice * p.quantity).toLocaleString("ar-SA")} ﷼
                    </span>
                    <button
                      type="button"
                      onClick={() => removeProduct(p.clothId)}
                      className="w-7 h-7 flex items-center justify-center rounded-lg text-red-400 hover:bg-red-50 cursor-pointer transition-colors"
                    >
                      <i className="ri-delete-bin-line text-sm" />
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className={saleSectionCardClass}>
            <h2 className={saleSectionTitleClass}>
              <i className="ri-price-tag-3-line text-indigo-500" /> التسعير والخصومات
            </h2>
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className={saleLabelClass}>نسبة الضريبة (%)</label>
                <input
                  type="number"
                  className={saleInputClass}
                  value={form.taxRate}
                  onChange={(e) => setField("taxRate", parseFloat(e.target.value) || 0)}
                />
              </div>
              <div>
                <label className={saleLabelClass}>مبلغ الخصم (﷼)</label>
                <input
                  type="number"
                  className={saleInputClass}
                  value={form.discount}
                  onChange={(e) => setField("discount", parseFloat(e.target.value) || 0)}
                />
              </div>
              <div>
                <label className={saleLabelClass}>سبب الخصم</label>
                <input
                  className={saleInputClass}
                  placeholder="مثال: خصم VIP"
                  value={form.discountReason}
                  onChange={(e) => setField("discountReason", e.target.value)}
                />
              </div>
            </div>
          </div>

          <div className={saleSectionCardClass}>
            <h2 className={saleSectionTitleClass}>
              <i className="ri-bank-card-line text-indigo-500" /> الدفعة المقدمة
            </h2>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={saleLabelClass}>مبلغ العربون (﷼)</label>
                <input
                  type="number"
                  className={saleInputClass}
                  value={form.depositAmount}
                  onChange={(e) => setField("depositAmount", parseFloat(e.target.value) || 0)}
                />
              </div>
              <div>
                <label className={saleLabelClass}>طريقة الدفع</label>
                <select
                  className={saleSelectClass}
                  value={form.depositMethod}
                  onChange={(e) => setField("depositMethod", e.target.value)}
                >
                  <option>نقدي</option>
                  <option>بطاقة</option>
                  <option>تحويل بنكي</option>
                  <option>محفظة إلكترونية</option>
                </select>
              </div>
            </div>
          </div>

          <div className={saleSectionCardClass}>
            <h2 className={saleSectionNotesTitleClass}>
              <i className="ri-sticky-note-line text-indigo-500" /> ملاحظات
            </h2>
            <textarea
              className={saleTextareaClass}
              rows={3}
              placeholder="ملاحظات الفاتورة..."
              value={form.notes}
              onChange={(e) => setField("notes", e.target.value)}
            />
          </div>
        </div>

        <div className={saleCreatePageSidebarClass}>
          <div className={saleSectionCardClass}>
            <h2 className="mb-4 text-sm font-bold text-slate-700">ملخص الفاتورة</h2>
            <div className="space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-slate-500">المبلغ الفرعي</span>
                <span className="font-semibold text-slate-800">
                  {pricing.subtotal.toLocaleString("ar-SA")} ﷼
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-slate-500">ضريبة {form.taxRate}%</span>
                <span className="font-semibold text-slate-800">
                  +{pricing.taxAmount.toLocaleString("ar-SA")} ﷼
                </span>
              </div>
              {form.discount > 0 && (
                <div className="flex justify-between text-sm">
                  <span className="text-emerald-600">خصم</span>
                  <span className="font-semibold text-emerald-600">
                    -{Number(form.discount).toLocaleString("ar-SA")} ﷼
                  </span>
                </div>
              )}
              <div className="border-t border-slate-100 pt-3 flex justify-between">
                <span className="font-bold text-slate-800">الإجمالي</span>
                <span className="font-bold text-lg text-indigo-600">
                  {pricing.totalWithTax.toLocaleString("ar-SA")} ﷼
                </span>
              </div>
              {form.depositAmount > 0 && (
                <>
                  <div className="flex justify-between text-sm">
                    <span className="text-slate-500">العربون المدفوع</span>
                    <span className="font-semibold text-emerald-600">
                      -{Number(form.depositAmount).toLocaleString("ar-SA")} ﷼
                    </span>
                  </div>
                  <div
                    className="flex justify-between p-3 rounded-lg"
                    style={{ background: "#FEF3C7" }}
                  >
                    <span className="font-bold text-amber-800">المتبقي</span>
                    <span className="font-bold text-amber-700">
                      {pricing.remaining.toLocaleString("ar-SA")} ﷼
                    </span>
                  </div>
                </>
              )}
            </div>
          </div>

          {(activeTab === "existing" && selectedClientId) || (activeTab === "new" && newClient.name) ? (
            <div className="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
              <h3 className="text-xs font-bold text-indigo-700 mb-2">العميلة</h3>
              <p className="font-semibold text-sm text-slate-800">
                {activeTab === "new" ? newClient.name : `عميل مسجل #${selectedClientId}`}
              </p>
              {(activeTab === "new" ? newClient.phone : true) && (
                <p className="text-xs text-slate-500 mt-0.5">
                  {activeTab === "new" ? newClient.phone : ""}
                </p>
              )}
              {entityId && (
                <p className="text-xs text-slate-500 mt-0.5">
                  {branchDetail?.name
                    ? `الفرع: ${branchDetail.name}`
                    : "جاري تحميل الفرع…"}
                </p>
              )}
            </div>
          ) : null}

          {products.length > 0 && (
            <div className="bg-slate-50 rounded-xl p-4 border border-slate-100">
              <h3 className="text-xs font-bold text-slate-600 mb-2">المنتجات المضافة</h3>
              <div className="space-y-1.5">
                {products.map((p) => (
                  <div key={p.clothId} className="flex justify-between text-xs">
                    <span className="text-slate-600 truncate">{p.name}</span>
                    <span className="font-medium text-slate-800 mr-2">×{p.quantity}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          <button
            type="button"
            onClick={handleSubmit}
            disabled={!canSubmit || isSubmitting}
            className="w-full py-3 rounded-xl text-sm font-bold text-white cursor-pointer transition-all disabled:opacity-40 disabled:cursor-not-allowed"
            style={{ background: "linear-gradient(135deg, #6366F1, #4F46E5)" }}
          >
            <i className="ri-save-line ml-2" />
            {isSubmitting ? "جاري الحفظ..." : "إنشاء الفاتورة"}
          </button>
          <button
            type="button"
            onClick={() => navigate(soldOrdersListPath())}
            className="w-full py-2.5 rounded-xl text-sm font-semibold text-slate-500 border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors"
          >
            إلغاء
          </button>
        </div>
      </div>

      {showSuccess && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-white rounded-2xl p-8 text-center max-w-sm mx-4">
            <div
              className="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
              style={{ background: "#ECFDF5" }}
            >
              <i className="ri-checkbox-circle-fill text-3xl" style={{ color: "#10B981" }} />
            </div>
            <h3 className="text-lg font-bold text-slate-800 mb-2">تم إنشاء الفاتورة بنجاح!</h3>
            <p className="text-sm text-slate-500">جاري التحويل إلى قائمة المبيعات...</p>
          </div>
        </div>
      )}
    </div>
  );
}
