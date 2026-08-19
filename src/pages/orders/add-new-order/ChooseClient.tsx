import { useState, useEffect, useMemo } from "react";
import { useNavigate, useLocation, Link } from "react-router";
import { toast } from "sonner";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { DatePicker } from "@/components/custom/DatePicker";
import { Badge } from "@/components/ui/badge";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { OrderStepsStepper } from "@/components/custom/OrderStepsStepper";
import { ClientsSelect } from "@/components/custom/ClientsSelect";
import { CitiesSelect } from "@/components/custom/CitiesSelect";
import { TEntity } from "@/lib/types/entity.types";
import {
  TClientResponse,
  TCreateClientRequest,
  CLIENT_SOURCES,
  CLIENT_SOURCE_LABELS,
} from "@/api/v2/clients/clients.types";
import { useGetClientQueryOptions } from "@/api/v2/clients/clients.hooks";
import { formatPhone } from "@/utils/formatPhone";
import { useGetClothesQueryOptions } from "@/api/v2/clothes/clothes.hooks";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useCreateOrderMutationOptions } from "@/api/v2/orders/orders.hooks";
import {
  TCreateOrderRequest,
  TCreateOrderWithNewClientRequest,
} from "@/api/v2/orders/orders.types";
import { format } from "date-fns";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import {
  Plus,
  User,
  ArrowRight,
  ArrowLeft,
  CheckCircle,
  Calendar,
  X,
  Loader2,
  ShoppingBag,
  Ruler,
  Filter,
  RotateCcw,
  Phone,
  MapPin,
  UserCircle,
  Banknote,
  Percent,
  StickyNote,
} from "lucide-react";
import { CategoriesSelect } from "@/components/custom/CategoriesSelect";
import { SubcategoriesSelect } from "@/components/custom/SubcategoriesSelect";
import useDebounce from "@/hooks/useDebounce";
import { EmployeesSelect } from "@/components/custom/EmployeesSelect";
import { TGetEmployeesParams } from "@/api/v2/employees/employees.types";
import {
  employeesParamsForOrderEntity,
  processTypeForOrderLine,
} from "@/pages/orders/lib/orderCreationShared";

const newClientFormSchema = z.object({
  name: z.string().min(1, { message: "الاسم مطلوب" }),
  date_of_birth: z.string().optional(),
  national_id: z.string().optional(),
  source: z.enum(CLIENT_SOURCES),
  phone: z.string().min(1, { message: "رقم الهاتف مطلوب" }),
  address: z.string().min(1, { message: "العنوان مطلوب" }),
  city_id: z.string({ required_error: "المدينة مطلوبة" }),
  notes: z.string().optional(),
  phone2: z.string().optional(),
});

type NewClientFormValues = z.infer<typeof newClientFormSchema>;

const defaultNewClientValues: NewClientFormValues = {
  name: "",
  date_of_birth: "",
  national_id: "",
  source: "other",
  address: "",
  city_id: "",
  notes: "",
  phone: "",
  phone2: "",
};

const ORDER_DRAFT_KEY = "order-choose-client-draft";

function ChooseClient() {
  const location = useLocation();
  const navigate = useNavigate();

  const locationState = location.state || { client: null };
  const { client: selectedClient } = locationState as { client: TClientResponse | null };
  const preferSoldFlow =
    (location.state as { defaultProductType?: string } | null)?.defaultProductType ===
    "buy";
  const defaultLineType: "rent" | "buy" = preferSoldFlow ? "buy" : "rent";

  const [activeTab, setActiveTab] = useState(selectedClient ? "existing" : "new");

  const newClientForm = useForm<NewClientFormValues>({
    resolver: zodResolver(newClientFormSchema),
    defaultValues: defaultNewClientValues,
  });

  const [selectedClientId, setSelectedClientId] = useState<string>(
    selectedClient?.id?.toString() || ""
  );
  const [selectedClientFromList, setSelectedClientFromList] =
    useState<TClientResponse | null>(selectedClient);

  const [entityType, setEntityType] = useState<TEntity | undefined>("branch");
  const [entityId, setEntityId] = useState<string>("");
  const [deliveryDate, setDeliveryDate] = useState<Date | undefined>(); 
  const [receiveDate, setReceiveDate] = useState<Date | undefined>(); 
  const [branchDate, setBranchDate] = useState<Date | undefined>(); // Occasion/wedding date
  const [employeeId, setEmployeeId] = useState<string>(""); // Employee who created the invoice

  const [productCodeFilter, setProductCodeFilter] = useState("");
  const [categoryId, setCategoryId] = useState("");
  const [subcategoryIds, setSubcategoryIds] = useState<string[]>([]);
  const [selectedProduct, setSelectedProduct] = useState<any>(null);
  const [productDetails, setProductDetails] = useState(() => ({
    quantity: "1",
    price: "",
    paid: "",
    date: new Date(),
    weddingDate: undefined as Date | undefined,
    notes: "",
    days_of_rent: "1",
    type: defaultLineType as "rent" | "buy" | "tailoring",
    discount_type: "none" as "none" | "percentage" | "fixed",
    discount_value: "0",
  }));

  const [measurements, setMeasurements] = useState({
    sleeveLength: "",
    forearm: "",
    shoulderWidth: "",
    cuffs: "",
    waist: "",
    chestLength: "",
    totalLength: "",
    hinch: "",
    dressSize: "",
  });

  const [selectedProducts, setSelectedProducts] = useState<any[]>([]);
  const [showFilters, setShowFilters] = useState(false);
  const [orderDiscount, setOrderDiscount] = useState<{
    type: "none" | "percentage" | "fixed";
    value: number;
  }>({ type: "none", value: 0 });

  const debouncedProductCodeFilter = useDebounce({ value: productCodeFilter, delay: 500 });
  const debouncedCategoryId = useDebounce({ value: categoryId, delay: 300 });
  const debouncedSubcategoryIds = useDebounce({
    value: subcategoryIds,
    delay: 300,
  });
  const debouncedEntityType = useDebounce({ value: entityType, delay: 300 });
  const debouncedEntityId = useDebounce({ value: entityId, delay: 300 });

  const queryParams = useMemo(() => {
    const params: Record<string, unknown> = {
      page: 1,
      per_page: 10,
    };
    if (debouncedProductCodeFilter) params.code = debouncedProductCodeFilter;
    if (debouncedCategoryId) params.category_id = Number(debouncedCategoryId);
    if (debouncedSubcategoryIds.length > 0) {
      params.subcat_id = debouncedSubcategoryIds.map(Number);
    }
    if (debouncedEntityType) params.entity_type = debouncedEntityType;
    if (debouncedEntityId) params.entity_id = Number(debouncedEntityId);
    
    if (receiveDate) params.delivery_date = format(receiveDate, "yyyy-MM-dd HH:mm:ss");
    if (deliveryDate) params.visit_datetime = format(deliveryDate, "yyyy-MM-dd HH:mm:ss");
    if (branchDate) params.occasion_datetime = format(branchDate, "yyyy-MM-dd HH:mm:ss");
    const daysNum = productDetails.days_of_rent ? parseInt(productDetails.days_of_rent, 10) : NaN;
    if (!isNaN(daysNum) && daysNum > 0) params.days_of_rent = daysNum;
    return params;
  }, [
    debouncedProductCodeFilter,
    debouncedCategoryId,
    debouncedSubcategoryIds,
    debouncedEntityType,
    debouncedEntityId,
    receiveDate,
    deliveryDate,
    branchDate,
    productDetails.days_of_rent,
  ]);

  const hasRentContext =
    selectedProducts.some((p) => p.type === "rent") ||
    productDetails.type === "rent";

  const canFetchClothes = Boolean(
    entityType &&
      entityId &&
      receiveDate &&
      branchDate &&
      (hasRentContext ? !!deliveryDate : true)
  );

  const { data: clothesData, isPending: isClothesPending } = useQuery({
    ...useGetClothesQueryOptions(queryParams),
    enabled: canFetchClothes,
  });

  const clientIdNum = selectedClientId ? Number(selectedClientId) : 0;
  const { data: clientData } = useQuery({
    ...useGetClientQueryOptions(clientIdNum),
    enabled: clientIdNum > 0,
  });

  const availableClothes = clothesData?.data || [];

  const totalAmount = selectedProducts.reduce(
    (sum, product) => sum + product.price * product.quantity,
    0
  );
  const totalPaid = selectedProducts.reduce((sum, product) => sum + (product.paid || 0), 0);
  const remainingAmount = totalAmount - totalPaid;

  const createOrderMutation = useMutation(useCreateOrderMutationOptions());
  const { mutate: createOrder, isPending: isCreatingOrder } = createOrderMutation;

  useEffect(() => {
    if (selectedClient) {
      const fullName =
        selectedClient.name?.trim() ||
        [selectedClient.first_name, selectedClient.middle_name, selectedClient.last_name]
          .filter(Boolean)
          .join(" ")
          .trim();
      newClientForm.reset({
        ...defaultNewClientValues,
        name: fullName || "",
        phone: selectedClient.phones?.[0]?.phone || "",
        national_id: selectedClient.national_id || "",
      });
      setSelectedClientId(selectedClient.id?.toString() || "");
      setSelectedClientFromList(selectedClient);
      setActiveTab("existing");
    }
  }, [selectedClient, newClientForm]);

  useEffect(() => {
    if (clientData) {
      setSelectedClientFromList(clientData);
    }
  }, [clientData]);

  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  useEffect(() => {
    try {
      const raw = localStorage.getItem(ORDER_DRAFT_KEY);
      if (!raw) return;
      const draft = JSON.parse(raw) as {
        entityType?: TEntity;
        entityId?: string;
        deliveryDate?: string;
        activeTab?: string;
        selectedClientId?: string;
        newClientFormValues?: NewClientFormValues;
        selectedProducts?: any[];
      };
      setEntityType("branch");
      if (draft.entityId) setEntityId(draft.entityId);
      if (draft.deliveryDate) setDeliveryDate(new Date(draft.deliveryDate));
      if (draft.activeTab) setActiveTab(draft.activeTab);
      if (draft.selectedClientId) setSelectedClientId(draft.selectedClientId);
      if (draft.newClientFormValues) newClientForm.reset(draft.newClientFormValues);
      if (draft.selectedProducts?.length) {
        const defaultDelivery = draft.deliveryDate ? new Date(draft.deliveryDate) : undefined;
        const restored = draft.selectedProducts.map((p) => ({
          ...p,
          date: p.date ? new Date(p.date) : undefined,
          deliveryDate: p.deliveryDate ? new Date(p.deliveryDate) : defaultDelivery,
          weddingDate: p.weddingDate ? new Date(p.weddingDate) : undefined,
        }));
        setSelectedProducts(restored);
      }
      localStorage.removeItem(ORDER_DRAFT_KEY);
      toast.success("تم استعادة المسودة المحفوظة");
    } catch {
      localStorage.removeItem(ORDER_DRAFT_KEY);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps -- run once on mount to restore draft
  }, []);

  const handleClientSelect = (clientId: string) => {
    setSelectedClientId(clientId);
  };

  const handleClothSelect = (cloth: any) => {
    // If cloth status is not ready for rent, don't allow selection and show rejection reason
    // Disabled code: validation for cloth status before selection
    // if (cloth.status && cloth.status !== "ready_for_rent") {
    //   });
    //   return;
    // }

    setSelectedProduct(cloth);
    setProductDetails({
      ...productDetails,
      price: cloth.price?.toString() || "",
    });
  };

  const handleAddProduct = () => {
    if (!selectedProduct) {
      toast.error("يجب اختيار منتج من القائمة");
      return;
    }
    if (!productDetails.price) {
      toast.error("يجب إدخال السعر");
      return;
    }
    const alreadyAdded = selectedProducts.some((p) => p.id === selectedProduct.id);
    if (alreadyAdded) {
      toast.error("هذا المنتج مضاف مسبقاً");
      return;
    }
    const newProduct: any = {
      id: selectedProduct.id,
      cloth_id: selectedProduct.id,
      name: selectedProduct.code,
      code: selectedProduct.code,
      quantity: parseInt(productDetails.quantity),
      price: parseFloat(productDetails.price),
      paid: parseFloat(productDetails.paid) || 0,
      date: productDetails.date,
      deliveryDate: deliveryDate,
      weddingDate: productDetails.weddingDate,
      notes: productDetails.notes,
      status: selectedProduct.status,
      type: productDetails.type,
      days_of_rent:
        productDetails.type === "rent" ? parseInt(productDetails.days_of_rent) : 0,
      discount_type: productDetails.discount_type,
      discount_value: parseFloat(productDetails.discount_value) || 0,
      subtotal: parseFloat(productDetails.price) * parseInt(productDetails.quantity),
    };
    // Attach measurements (if present) to each product, all fields are optional
    const hasMeasurements = Object.values(measurements).some(
      (value) => value != null && String(value).trim() !== ""
    );
    if (hasMeasurements) {
      newProduct.measurements = { ...measurements };
    }
    setSelectedProducts([...selectedProducts, newProduct]);
    setSelectedProduct(null);
    setProductDetails({
      quantity: "1",
      price: "",
      paid: "",
      date: new Date(),
      weddingDate: undefined,
      notes: "",
      days_of_rent: "1",
      type: defaultLineType,
      discount_type: "none",
      discount_value: "0",
    });
    // Reset measurements after adding product to avoid accidentally repeating them with another product
    setMeasurements({
      sleeveLength: "",
      forearm: "",
      shoulderWidth: "",
      cuffs: "",
      waist: "",
      chestLength: "",
      totalLength: "",
      hinch: "",
      dressSize: "",
    });
    toast.success("تم إضافة المنتج إلى الطلب");
    setTimeout(() => {
      window.scrollTo({ top: 0, behavior: "smooth" });
    }, 100);
  };

  const handleRemoveProduct = (id: number) => {
    setSelectedProducts(selectedProducts.filter((product) => product.id !== id));
    toast.success("تم إزالة المنتج من الطلب");
  };

  const resetFilters = () => {
    setProductCodeFilter("");
    setCategoryId("");
    setSubcategoryIds([]);
    setEntityType("branch");
    setEntityId("");
    setDeliveryDate(undefined);
  };

  const getStatusBadgeVariant = (status: string) => {
    switch (status) {
      case "ready_for_rent":
        return "default";
      case "rented":
        return "secondary";
      case "damaged":
      case "burned":
      case "scratched":
        return "destructive";
      case "repairing":
        return "outline";
      case "die":
        return "destructive";
      default:
        return "secondary";
    }
  };

  const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
      ready_for_rent: "متوفر",
      rented: "محجوز",
      damaged: "تالف",
      burned: "محترق",
      scratched: "مخدوش",
      repairing: "قيد الصيانة",
      die: "ميت",
    };
    return labels[status] || status;
  };

  
  const getEffectiveDisplayStatus = (cloth: {
    status?: string;
    can_select?: boolean;
  }) => {
    if (cloth.can_select === true) return "ready_for_rent";
    if (cloth.can_select === false) return "rented";
    return cloth.status ?? "rented";
  };

  const firstRentProduct = selectedProducts.find((p) => p.type === "rent");
  const hasRentLine = selectedProducts.some((p) => p.type === "rent");
  const hasBuyLine = selectedProducts.some((p) => p.type === "buy");
  const hasOnlyBuy =
    selectedProducts.length > 0 &&
    selectedProducts.every((p) => p.type === "buy");

  const needsOccasion = hasRentLine || hasOnlyBuy;
  const orderLevelOccasionDatetime =
    needsOccasion && branchDate
      ? format(branchDate, "yyyy-MM-dd HH:mm:ss")
      : undefined;

  const orderLevelDaysOfRent = hasRentLine
    ? firstRentProduct?.days_of_rent
      ? Number(firstRentProduct.days_of_rent)
      : 1
    : undefined;

  const orderProcessType: "rent" | "sold" | undefined = hasOnlyBuy
    ? "sold"
    : hasRentLine && !hasBuyLine
      ? "rent"
      : undefined;

  const buildOrderItems = () =>
    selectedProducts.map((product) => {
      const hasItemDiscount =
        product.discount_type &&
        product.discount_type !== "none" &&
        (product.discount_value ?? 0) > 0;

      const processType = processTypeForOrderLine(product.type);
      let item: any = {
        cloth_id: product.cloth_id,
        price: product.price,
        quantity: product.quantity ?? 1,
        paid: product.paid ?? 0,
        type: product.type,
        ...(processType ? { process_type: processType } : {}),
        notes: product.notes || undefined,
        ...(hasItemDiscount
          ? {
            discount_type: product.discount_type as "percentage" | "fixed",
            discount_value: Number(product.discount_value),
          }
          : {}),
      };

      // For rental orders, send occasion date and number of days also at item level
      if (product.type === "rent" && orderLevelOccasionDatetime) {
        item = {
          ...item,
          occasion_datetime: orderLevelOccasionDatetime,
          days_of_rent:
            product.days_of_rent != null
              ? Number(product.days_of_rent)
              : orderLevelDaysOfRent ?? 1,
          ...(deliveryDate && {
            delivery_date: format(deliveryDate, "yyyy-MM-dd HH:mm:ss"),
          }),
        };
      }

      if (product.measurements) {
        const m = product.measurements;
        return {
          ...item,
          ...(m.sleeveLength?.trim() && { sleeve_length: m.sleeveLength.trim() }),
          ...(m.forearm?.trim() && { forearm: m.forearm.trim() }),
          ...(m.shoulderWidth?.trim() && { shoulder_width: m.shoulderWidth.trim() }),
          ...(m.cuffs?.trim() && { cuffs: m.cuffs.trim() }),
          ...(m.waist?.trim() && { waist: m.waist.trim() }),
          ...(m.chestLength?.trim() && { chest_length: m.chestLength.trim() }),
          ...(m.totalLength?.trim() && { total_length: m.totalLength.trim() }),
          ...(m.hinch?.trim() && { hinch: m.hinch.trim() }),
          ...(m.dressSize?.trim() && { dress_size: m.dressSize.trim() }),
        };
      }

      return item;
    });

  const employeeParams: TGetEmployeesParams = useMemo(
    () => employeesParamsForOrderEntity(entityType, entityId),
    [entityType, entityId]
  );

  const buildOrderPayload = (clientId: number): TCreateOrderRequest => {
    const hasOrderDiscount =
      orderDiscount.type &&
      orderDiscount.type !== "none" &&
      orderDiscount.value > 0;
    const deliveryDateStr = receiveDate
      ? format(receiveDate, "yyyy-MM-dd HH:mm:ss")
      : format(new Date(), "yyyy-MM-dd HH:mm:ss");
    return {
      existing_client: true,
      client_id: clientId,
      ...(orderProcessType ? { process_type: orderProcessType } : {}),
      ...(employeeId && { employee_id: Number(employeeId) }),
      entity_type: entityType!,
      entity_id: Number(entityId),
      delivery_date: deliveryDateStr,
      ...(orderProcessType === "sold" && { visit_datetime: deliveryDateStr }),
      ...(hasRentLine &&
        deliveryDate && {
          visit_datetime: format(deliveryDate, "yyyy-MM-dd HH:mm:ss"),
        }),
      ...(orderLevelOccasionDatetime && { occasion_datetime: orderLevelOccasionDatetime }),
      ...(orderLevelDaysOfRent != null &&
        hasRentLine && { days_of_rent: orderLevelDaysOfRent }),
      order_notes: selectedProducts.map((p) => p.notes).filter(Boolean).join(" - ") || undefined,
      ...(hasOrderDiscount
        ? {
          discount_type: orderDiscount.type as "percentage" | "fixed",
          discount_value: orderDiscount.value,
        }
        : {}),
      items: buildOrderItems(),
    };
  };

  const buildOrderWithNewClientPayload = (
    values: NewClientFormValues
  ): TCreateOrderWithNewClientRequest => {
    const hasOrderDiscount =
      orderDiscount.type &&
      orderDiscount.type !== "none" &&
      orderDiscount.value > 0;
    const phones: { phone: string; type: string }[] = [];
    if (values.phone?.trim()) phones.push({ phone: values.phone.trim(), type: "mobile" });
    if (values.phone2?.trim()) phones.push({ phone: values.phone2.trim(), type: "whatsapp" });
    const client: TCreateClientRequest = {
      name: values.name.trim(),
      date_of_birth: values.date_of_birth || undefined,
      national_id: values.national_id?.trim() || undefined,
      source: values.source,
      address: {
        city_id: Number(values.city_id),
        address: values.address.trim(),
      },
      phones,
    };
    const deliveryDateStr = receiveDate
      ? format(receiveDate, "yyyy-MM-dd HH:mm:ss")
      : format(new Date(), "yyyy-MM-dd HH:mm:ss");
    return {
      existing_client: false,
      client,
      ...(orderProcessType ? { process_type: orderProcessType } : {}),
      ...(employeeId && { employee_id: Number(employeeId) }),
      entity_type: entityType!,
      entity_id: Number(entityId),
      delivery_date: deliveryDateStr,
      ...(orderProcessType === "sold" && { visit_datetime: deliveryDateStr }),
      ...(hasRentLine &&
        deliveryDate && {
          visit_datetime: format(deliveryDate, "yyyy-MM-dd HH:mm:ss"),
        }),
      ...(orderLevelOccasionDatetime && { occasion_datetime: orderLevelOccasionDatetime }),
      ...(orderLevelDaysOfRent != null &&
        hasRentLine && { days_of_rent: orderLevelDaysOfRent }),
      order_notes: selectedProducts.map((p) => p.notes).filter(Boolean).join(" - ") || undefined,
      ...(hasOrderDiscount
        ? {
          discount_type: orderDiscount.type as "percentage" | "fixed",
          discount_value: orderDiscount.value,
        }
        : {}),
      items: buildOrderItems(),
    };
  };

  const submitOrder = (clientId: number) => {
    createOrder(buildOrderPayload(clientId), {
      onSuccess: () => {
        toast.success("تم إنشاء الطلب بنجاح!");
        const soldOnly =
          selectedProducts.length > 0 &&
          selectedProducts.every((p) => p.type === "buy");
        navigate(
          soldOnly ? "/orders/list?process_type=sold" : "/orders/list"
        );
      },
      onError: (error: any) => {
        toast.error("خطأ في إنشاء الطلب", {
          description: error?.message || "حدث خطأ غير متوقع",
        });
      },
    });
  };

  const handleCreateOrder = async () => {
    if (activeTab === "existing" && !selectedClientId && !selectedClientFromList) {
      toast.error("يجب اختيار عميل");
      return;
    }
    if (!entityType || !entityId) {
      toast.error("يجب اختيار نوع المكان والمكان");
      return;
    }
    if (!employeeId) {
      toast.error("يجب اختيار الموظف الذي أنشأ الفاتورة");
      return;
    }
    if (!receiveDate) {
      toast.error("يجب اختيار تاريخ الاستلام");
      return;
    }
    if (selectedProducts.length === 0) {
      toast.error("يجب إضافة منتجات على الأقل");
      return;
    }
    const hasRent = selectedProducts.some((p) => p.type === "rent");
    const hasBuy = selectedProducts.some((p) => p.type === "buy");
    if (hasRent && hasBuy) {
      toast.error("لا يمكن خلط إيجار وبيع في نفس الطلب");
      return;
    }
    if (hasRent && !deliveryDate) {
      toast.error("يجب اختيار تاريخ الاسترجاع للإيجار");
      return;
    }
    if ((hasRent || hasOnlyBuy) && !branchDate) {
      toast.error("تاريخ المناسبة مطلوب");
      return;
    }

    if (activeTab === "new") {
      const valid = await newClientForm.trigger();
      if (!valid) {
        toast.error("يرجى إكمال بيانات العميل الجديد بشكل صحيح");
        return;
      }
      handleNewClientSubmitAndCreateOrder(newClientForm.getValues());
      return;
    }

    let clientId: number = 0;
    if (selectedClientFromList?.id) {
      clientId = selectedClientFromList.id;
    } else if (selectedClientId) {
      clientId = parseInt(selectedClientId);
    }
    if (!clientId || clientId <= 0) {
      toast.error("يجب اختيار عميل صالح");
      return;
    }
    submitOrder(clientId);
  };

  const handleSaveDraft = () => {
    const productsForDraft = selectedProducts.map((p) => ({
      ...p,
      date: p.date instanceof Date ? p.date.toISOString() : p.date,
      deliveryDate: p.deliveryDate instanceof Date ? p.deliveryDate.toISOString() : p.deliveryDate,
      weddingDate: p.weddingDate instanceof Date ? p.weddingDate.toISOString() : p.weddingDate,
    }));
    const draft = {
      entityType,
      entityId,
      deliveryDate: deliveryDate ? deliveryDate.toISOString() : undefined,
      activeTab,
      selectedClientId: selectedClientId || undefined,
      newClientFormValues: activeTab === "new" ? newClientForm.getValues() : undefined,
      selectedProducts: productsForDraft,
      savedAt: new Date().toISOString(),
    };
    try {
      localStorage.setItem(ORDER_DRAFT_KEY, JSON.stringify(draft));
      toast.success("تم حفظ الطلب مؤقتاً");
    } catch {
      toast.error("فشل حفظ المسودة");
    }
  };

  const handleNewClientSubmitAndCreateOrder = (values: NewClientFormValues) => {
    if (!entityType || !entityId) {
      toast.error("يجب اختيار نوع المكان والمكان");
      return;
    }
    if (!receiveDate) {
      toast.error("يجب اختيار تاريخ الاستلام");
      return;
    }
    const hasRent = selectedProducts.some((p) => p.type === "rent");
    const hasBuy = selectedProducts.some((p) => p.type === "buy");
    if (hasRent && hasBuy) {
      toast.error("لا يمكن خلط إيجار وبيع في نفس الطلب");
      return;
    }
    if (hasRent && !deliveryDate) {
      toast.error("يجب اختيار تاريخ الاسترجاع للإيجار");
      return;
    }
    if ((hasRent || hasOnlyBuy) && !branchDate) {
      toast.error("تاريخ المناسبة مطلوب");
      return;
    }
    if (selectedProducts.length === 0) {
      toast.error("يجب إضافة منتجات على الأقل");
      return;
    }
    const payload = buildOrderWithNewClientPayload(values);
    createOrder(payload, {
      onSuccess: () => {
        toast.success("تم إنشاء الطلب والعميل بنجاح!");
        const soldOnly =
          selectedProducts.length > 0 &&
          selectedProducts.every((p) => p.type === "buy");
        navigate(
          soldOnly ? "/orders/list?process_type=sold" : "/orders/list"
        );
      },
      onError: (error: any) => {
        toast.error("حدث خطأ أثناء إنشاء الطلب", {
          description: error?.message || "حدث خطأ غير متوقع",
        });
      },
    });
  };

  const handleEntityIdChangeStandalone = (value: string) => {
    if (selectedProducts.length > 0) {
      toast.error("لا يمكن تغيير المكان بعد إضافة منتجات للطلب");
      return;
    }
    setEntityId(value);
  };

  return (
    <div dir="rtl" className="min-h-screen bg-[linear-gradient(180deg,hsl(220_20%_98%)_0%,hsl(220_14%_96%)_100%)] dark:bg-slate-950">
      <div className="w-full space-y-8 py-8 px-4 lg:px-8">
        <nav className="flex items-center gap-2 text-sm">
          <Link
            to="/orders/list"
            className="text-slate-500 dark:text-slate-400 hover:text-[#5170ff] transition-colors font-medium"
          >
            قائمة الطلبات
          </Link>
          <i className="ri-arrow-left-s-line text-slate-400" aria-hidden />
          <span className="text-slate-900 dark:text-slate-100 font-semibold">
            {preferSoldFlow ? "إنشاء طلب بيع جديد" : "إنشاء طلب إيجار جديد"}
          </span>
        </nav>

        <div className="sticky top-0 z-20 -mx-4 -mt-8 px-4 pt-8 pb-4 lg:-mx-8 lg:px-8 lg:pt-8 lg:pb-4 bg-[linear-gradient(180deg,hsl(220_20%_98%)_0%,hsl(220_14%_96%)_100%)] dark:bg-slate-950">
          <OrderStepsStepper
            currentStep={
              selectedClientFromList || selectedClient
                ? selectedProducts.length >= 1
                  ? 3
                  : 2
                : 1
            }
            stepState={
              selectedClientFromList || selectedClient
                ? {
                    2: { client: selectedClientFromList ?? selectedClient ?? undefined },
                    3:
                      selectedProducts.length >= 1
                        ? {
                            client_id: (selectedClientFromList ?? selectedClient)?.id,
                            entity_type: entityType,
                            entity_id: Number(entityId),
                            delivery_date: deliveryDate?.toISOString?.(),
                            selected_clothes: selectedProducts.map((p) => ({
                              id: p.id,
                              code: p.code,
                              name: p.name ?? p.code,
                              price: p.price,
                            })),
                            client: selectedClientFromList ?? selectedClient,
                          }
                        : undefined,
                  }
                : undefined
            }
            allowCurrentStepClick={selectedProducts.length >= 1}
          />
        </div>

        <div className="space-y-1">
          <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
            {preferSoldFlow ? "إنشاء طلب بيع جديد" : "إنشاء طلب إيجار جديد"}
          </h1>
          <p className="text-slate-600 dark:text-slate-400 text-sm sm:text-base">
            أدخل بيانات العميل، اختر المنتجات المتاحة، وأدخل تفاصيل الطلب
          </p>
        </div>

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_420px]">
          {/* Selected products panel */}
          <aside className="w-full order-first lg:order-none lg:col-start-2 lg:row-start-1">
            <div className="sticky top-24 overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-black/30 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
              <div className="relative overflow-hidden bg-gradient-to-br from-[#5170ff] to-[#3d5ae0] px-8 py-8">
                <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50" />
                <div className="relative flex items-center gap-5">
                  <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-md ring-1 ring-white/30">
                    <ShoppingBag className="h-8 w-8 text-white" />
                  </div>
                  <div>
                    <h2 className="text-2xl font-bold tracking-tight text-white">
                      المنتجات المختارة
                    </h2>
                    <p className="mt-1 text-sm text-white/90">
                      {selectedProducts.length} قطعة · مراجعة قبل الإنشاء
                    </p>
                  </div>
                </div>
              </div>
              <div className="flex flex-col">
                {selectedProducts.length === 0 ? (
                  <div className="rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/50 p-10 text-center">
                    <ShoppingBag className="h-10 w-10 mx-auto text-slate-300 dark:text-slate-600 mb-3" />
                    <p className="text-sm text-slate-600 dark:text-slate-300 font-semibold">
                      لم تضف أي منتجات بعد
                    </p>
                    <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                      اختر من جدول المنتجات المتاحة أدناه
                    </p>
                  </div>
                ) : (
                  <>
                    {/* Table header */}
                    <div className="grid grid-cols-[1fr_auto_auto] gap-3 px-6 py-3 bg-slate-50 dark:bg-slate-800/50 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                      <span>المنتج</span>
                      <span className="text-center">الكمية</span>
                      <span className="text-end min-w-[4rem]">المبلغ</span>
                    </div>
                    {/* Products list */}
                    <div className="max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                      {selectedProducts.map((cloth) => {
                        const typeLabel =
                          cloth.type === "tailoring"
                            ? "تفصيل"
                            : cloth.type === "buy"
                              ? "شراء"
                              : null;
                        const subtotal = (cloth.quantity ?? 1) * cloth.price;
                        return (
                          <div
                            key={cloth.id}
                            className="grid grid-cols-[1fr_auto_auto] gap-3 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors items-center"
                          >
                            <div className="min-w-0 flex items-center gap-3">
                              <div className="flex-1 min-w-0">
                                <p className="font-mono font-bold text-slate-900 dark:text-slate-100 text-sm truncate">
                                  {cloth.code}
                                </p>
                                <div className="flex items-center gap-2 mt-0.5">
                                  {typeLabel && (
                                    <Badge
                                      variant="outline"
                                      className={`text-[10px] rounded-md px-1.5 py-0 shrink-0 ${
                                        cloth.type === "tailoring"
                                          ? "bg-purple-500/10 text-purple-600 border-purple-500/30"
                                          : "bg-amber-500/10 text-amber-600 border-amber-500/30"
                                      }`}
                                    >
                                      {typeLabel}
                                    </Badge>
                                  )}
                                  {(cloth.name && cloth.name !== cloth.code) && (
                                    <span className="text-xs text-slate-500 dark:text-slate-400 truncate">
                                      {cloth.name}
                                    </span>
                                  )}
                                </div>
                              </div>
                              <Button
                                variant="ghost"
                                size="sm"
                                className="h-8 w-8 p-0 shrink-0 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"
                                onClick={() => handleRemoveProduct(cloth.id)}
                                title="إزالة من الطلب"
                              >
                                <X className="h-4 w-4" />
                              </Button>
                            </div>
                            <span className="text-sm font-semibold text-slate-700 dark:text-slate-300 tabular-nums text-center">
                              {cloth.quantity ?? 1}
                            </span>
                            <span className="text-sm font-bold text-slate-900 dark:text-slate-100 tabular-nums min-w-[4rem] text-end">
                              {subtotal.toFixed(0)} ج.م
                            </span>
                          </div>
                        );
                      })}
                    </div>
                    {/* Totals */}
                    <div className="border-t-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-6 py-4 space-y-2.5">
                      <div className="flex justify-between items-center text-sm gap-4">
                        <span className="font-medium text-slate-600 dark:text-slate-400">الإجمالي</span>
                        <span className="font-bold text-slate-900 dark:text-slate-100 tabular-nums">{Number(totalAmount).toFixed(0)} ج.م</span>
                      </div>
                      <div className="flex justify-between items-center text-sm gap-4">
                        <span className="font-medium text-slate-600 dark:text-slate-400">المدفوع</span>
                        <span className="font-bold text-emerald-600 dark:text-emerald-500 tabular-nums">{Number(totalPaid).toFixed(0)} ج.م</span>
                      </div>
                      <div className="flex justify-between items-center pt-3 mt-1 border-t border-slate-200 dark:border-slate-600 gap-4">
                        <span className="font-bold text-slate-800 dark:text-slate-200">المتبقي</span>
                        <span className="text-base font-bold text-[#5170ff] tabular-nums">{Number(remainingAmount).toFixed(0)} ج.م</span>
                      </div>
                    </div>
                  </>
                )}
                {/* Create order / Save draft buttons */}
                <div className="flex flex-col gap-3 p-6 border-t border-slate-200 dark:border-slate-700">
                  <Button
                    variant="outline"
                    className="w-full h-12 rounded-xl border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800"
                    onClick={handleSaveDraft}
                  >
                    حفظ مؤقت
                  </Button>
                  <Button
                    onClick={handleCreateOrder}
                    disabled={selectedProducts.length === 0 || isCreatingOrder}
                    className="w-full h-12 rounded-xl bg-[#5170ff] hover:bg-[#4560e6] text-white font-semibold shadow-lg shadow-[#5170ff]/25 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-[0.98]"
                  >
                    {isCreatingOrder ? (
                      <>
                        <Loader2 className="ml-3 h-5 w-5 animate-spin" />
                        جاري إنشاء الطلب...
                      </>
                    ) : (
                      <>
                        <CheckCircle className="ml-3 h-5 w-5" />
                        إنشاء الطلب
                        <ArrowRight className="mr-3 h-5 w-5" />
                      </>
                    )}
                  </Button>
                </div>
              </div>
            </div>
          </aside>

          {/* Main card */}
        <div className="min-w-0 overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-black/30 ring-1 ring-slate-200/60 dark:ring-slate-700/50 lg:col-start-1 lg:row-start-1">
          <div className="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-8 py-6">
            <div className="flex items-center gap-5">
              <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#5170ff]/10 ring-1 ring-[#5170ff]/20">
                <ShoppingBag className="h-7 w-7 text-[#5170ff]" />
              </div>
              <div>
                <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                  {preferSoldFlow
                    ? "كل خطوات إنشاء طلب البيع"
                    : "كل خطوات إنشاء طلب الإيجار"}
                </h1>
                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                  اختر المكان والتاريخ، حدد أو أنشئ عميل، ثم اختر المنتجات وأدخل التفاصيل والمقاسات من نفس الصفحة.
                </p>
              </div>
            </div>
          </div>

          <div className="space-y-8 p-8">
            <div className="space-y-6 ">
              {/* Left column: Basics + Client data + Order discount + Selected products summary */}
              <div className="space-y-6 ">
                {/* Basics */}
                <section className="rounded-2xl bg-white dark:bg-slate-900 p-6 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                  <div className="flex items-center gap-3 mb-5">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                      <Calendar className="h-5 w-5 text-[#5170ff]" />
                    </div>
                    <div>
                      <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">الأساسيات</h2>
                      <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        اختر المكان وتاريخ التسليم قبل اختيار المنتجات
                      </p>
                    </div>
                  </div>
                  <div className="space-y-4 w-full">
                    <div className="space-y-2 w-full">
                      <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">
                        المكان <span className="text-red-500">*</span>
                      </Label>
                      <BranchesSelect
                        value={entityId}
                        onChange={handleEntityIdChangeStandalone}
                        disabled={false}
                        className="w-full"
                      />
                    </div>
                    <div className="space-y-2">
                      <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">
                        الموظف المسؤول عن الفاتورة
                      </Label>
                      <EmployeesSelect
                        params={employeeParams}
                        value={employeeId}
                        onChange={setEmployeeId}
                        disabled={!entityType || !entityId}
                        placeholder="اختر الموظف..."
                      />
                    </div>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                      <div className="space-y-2">
                        <Label htmlFor="receive-date" className="text-sm font-medium text-slate-700 dark:text-slate-300">
                          تاريخ الاستلام
                        </Label>
                        <DatePicker
                          value={receiveDate}
                          onChange={setReceiveDate}
                          placeholder="اختر تاريخ الاستلام"
                          minDate={new Date()}
                          className="h-11 rounded-xl w-full"
                        />
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="branch-date" className="text-sm font-medium text-slate-700 dark:text-slate-300">
                          تاريخ الفرح
                        </Label>
                        <DatePicker
                          value={branchDate}
                          onChange={setBranchDate}
                          placeholder="اختر تاريخ الفرح"
                          allowPastDates={true}
                          allowFutureDates={true}
                          className="h-11 rounded-xl w-full"
                        />
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="delivery-date" className="text-sm font-medium text-slate-700 dark:text-slate-300">
                          تاريخ الاسترجاع
                        </Label>
                        <DatePicker
                          value={deliveryDate}
                          onChange={setDeliveryDate}
                          placeholder="اختر تاريخ الاسترجاع"
                          minDate={new Date()}
                          className="h-11 rounded-xl w-full"
                        />
                      </div>
                    </div>
                    {canFetchClothes && (
                      <div className="rounded-xl bg-emerald-50 dark:bg-emerald-950/30 ring-1 ring-emerald-200/60 dark:ring-emerald-800/50 px-4 py-3">
                        <p className="text-sm font-medium text-emerald-700 dark:text-emerald-400">✓ جاهز لعرض المنتجات المتاحة</p>
                      </div>
                    )}
                  </div>
                </section>

                {/* Client data */}
                <section className="rounded-2xl bg-white dark:bg-slate-900 p-6 ring-1 ring-slate-200/60 dark:ring-slate-700/50" dir="rtl">
                  <div className="flex items-center gap-3 mb-5">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                      <User className="h-5 w-5 text-[#5170ff]" />
                    </div>
                    <div>
                      <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">بيانات العميل</h2>
                      <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        اختر عميلاً موجوداً أو أضف عميل جديد في نفس المكان
                      </p>
                    </div>
                  </div>

                  <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                    <TabsList className="grid w-full grid-cols-2 bg-slate-200/60 dark:bg-slate-700/30 p-1.5 rounded-xl">
                      <TabsTrigger
                        value="existing"
                        className="data-[state=active]:bg-white data-[state=active]:shadow-sm data-[state=active]:text-slate-900 dark:data-[state=active]:bg-slate-900 dark:data-[state=active]:text-slate-100 rounded-lg font-medium transition-all"
                      >
                        عميل موجود
                      </TabsTrigger>
                      <TabsTrigger
                        value="new"
                        className="data-[state=active]:bg-white data-[state=active]:shadow-sm data-[state=active]:text-slate-900 dark:data-[state=active]:bg-slate-900 dark:data-[state=active]:text-slate-100 rounded-lg font-medium transition-all"
                      >
                        عميل جديد
                      </TabsTrigger>
                    </TabsList>

                    {/* Existing client */}
                    <TabsContent value="existing" className="mt-6 space-y-4">
                      {(selectedClientFromList || selectedClient) && (
                        <div className="rounded-xl bg-emerald-50 dark:bg-emerald-950/30 ring-1 ring-emerald-200/60 dark:ring-emerald-800/50 p-4">
                          <div className="flex flex-col items-end gap-2 text-right">
                            <Badge className="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400 border-0 rounded-lg mb-2">
                              العميل المختار
                            </Badge>
                            <p className="font-bold text-slate-900 dark:text-slate-100 text-lg">
                              {(() => {
                                const c = selectedClientFromList ?? selectedClient;
                                return (
                                  c?.name ??
                                  ([c?.first_name, c?.middle_name, c?.last_name]
                                    .filter(Boolean)
                                    .join(" ")
                                    .trim() || "—")
                                );
                              })()}
                            </p>
                            <div className="mt-2 space-y-1 text-sm">
                              <p className="text-slate-600 dark:text-slate-400">
                                الرقم القومي:{" "}
                                <span className="font-medium text-slate-900 dark:text-slate-100">
                                  {selectedClientFromList?.national_id ||
                                    selectedClient?.national_id ||
                                    "—"}
                                </span>
                              </p>
                              <p className="text-slate-600 dark:text-slate-400">
                                الهاتف:{" "}
                                <span dir="ltr" className="font-medium text-slate-900 dark:text-slate-100">
                                  {formatPhone(
                                    selectedClientFromList?.phones?.[0]?.phone ||
                                      selectedClient?.phones?.[0]?.phone,
                                    "—"
                                  )}
                                </span>
                              </p>
                            </div>
                          </div>
                        </div>
                      )}
                      <div className="space-y-2">
                        <Label className="text-sm font-medium text-slate-700 dark:text-slate-300 text-right block w-full">
                          اختر عميلاً من القائمة
                        </Label>
                        <ClientsSelect
                          value={selectedClientId}
                          onChange={handleClientSelect}
                          disabled={false}
                        />
                      </div>
                    </TabsContent>

                    {/* New client */}
                    <TabsContent value="new" className="mt-6" dir="rtl">
                      <Form {...newClientForm}>
                        <form
                          onSubmit={newClientForm.handleSubmit(handleNewClientSubmitAndCreateOrder)}
                          className="space-y-6 text-right"
                          dir="rtl"
                        >
                          <p className="text-sm text-slate-500 dark:text-slate-400 text-right">
                            أدخل بيانات العميل الجديد وسيتم إنشاء الطلب باسمه بعد الحفظ.
                          </p>

                          {/* Basic information */}
                          <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-5 ring-1 ring-slate-200/60 dark:ring-slate-700/50 space-y-4">
                            <div className="flex items-center gap-2 text-right">
                              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-[#5170ff]/10">
                                <UserCircle className="h-4 w-4 text-[#5170ff]" />
                              </div>
                              <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">البيانات الأساسية</h3>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div className="space-y-4">
                                <FormField
                                  control={newClientForm.control}
                                  name="name"
                                  render={({ field }) => (
                                    <FormItem>
                                      <FormLabel className="text-right">الاسم</FormLabel>
                                      <FormControl>
                                        <Input
                                          placeholder="الاسم الكامل للعميل"
                                          className="h-11 rounded-xl text-right"
                                          {...field}
                                        />
                                      </FormControl>
                                      <FormMessage />
                                    </FormItem>
                                  )}
                                />
                                <FormField
                                  control={newClientForm.control}
                                  name="national_id"
                                  render={({ field }) => (
                                    <FormItem>
                                      <FormLabel className="text-right">الرقم القومي</FormLabel>
                                      <FormControl>
                                        <Input
                                          placeholder="14 رقم"
                                          maxLength={14}
                                          className="h-11 rounded-xl font-mono text-right"
                                          {...field}
                                        />
                                      </FormControl>
                                      <FormMessage />
                                    </FormItem>
                                  )}
                                />
                              </div>
                              <div className="space-y-4">
                                <FormField
                                  control={newClientForm.control}
                                  name="date_of_birth"
                                  render={({ field }) => (
                                    <FormItem>
                                      <FormLabel className="text-right">
                                        تاريخ الميلاد (اختياري)
                                      </FormLabel>
                                      <FormControl>
                                        <Input
                                          type="date"
                                          className="h-11 rounded-xl"
                                          {...field}
                                          value={field.value ?? ""}
                                        />
                                      </FormControl>
                                      <FormMessage />
                                    </FormItem>
                                  )}
                                />
                                <FormField
                                  control={newClientForm.control}
                                  name="source"
                                  render={({ field }) => (
                                    <FormItem>
                                      <FormLabel className="text-right">المصدر</FormLabel>
                                      <Select onValueChange={field.onChange} value={field.value}>
                                        <FormControl>
                                          <SelectTrigger className="h-11 rounded-lg">
                                            <SelectValue placeholder="اختر المصدر" />
                                          </SelectTrigger>
                                        </FormControl>
                                        <SelectContent>
                                          {CLIENT_SOURCES.map((source) => (
                                            <SelectItem key={source} value={source}>
                                              {CLIENT_SOURCE_LABELS[source]}
                                            </SelectItem>
                                          ))}
                                        </SelectContent>
                                      </Select>
                                      <FormMessage />
                                    </FormItem>
                                  )}
                                />
                              </div>
                            </div>
                          </div>

                          {/* Phone numbers */}
                          <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-5 ring-1 ring-slate-200/60 dark:ring-slate-700/50 space-y-4">
                            <div className="flex items-center gap-2 text-right">
                              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-[#5170ff]/10">
                                <Phone className="h-4 w-4 text-[#5170ff]" />
                              </div>
                              <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">أرقام الهاتف</h3>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <FormField
                                control={newClientForm.control}
                                name="phone"
                                render={({ field }) => (
                                  <FormItem dir="ltr">
                                    <FormLabel className="text-right">
                                      رقم الهاتف
                                    </FormLabel>
                                    <FormControl>
                                      <Input
                                        placeholder="أدخل رقم الهاتف"
                                        dir="ltr"
                                        className="h-11 rounded-xl"
                                        {...field}
                                        value={field.value ?? ""}
                                      />
                                    </FormControl>
                                    <FormMessage />
                                  </FormItem>
                                )}
                              />
                              <FormField
                                control={newClientForm.control}
                                name="phone2"
                                render={({ field }) => (
                                  <FormItem dir="ltr">
                                    <FormLabel className="text-right">
                                      رقم الواتس (اختياري)
                                    </FormLabel>
                                    <FormControl>
                                      <Input
                                        placeholder="أدخل رقم الواتس"
                                        dir="ltr"
                                        className="h-11 rounded-xl"
                                        {...field}
                                        value={field.value ?? ""}
                                      />
                                    </FormControl>
                                    <FormMessage />
                                  </FormItem>
                                )}
                              />
                            </div>
                          </div>

                          {/* Address */}
                          <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-5 ring-1 ring-slate-200/60 dark:ring-slate-700/50 space-y-4">
                            <div className="flex items-center gap-2 text-right">
                              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-[#5170ff]/10">
                                <MapPin className="h-4 w-4 text-[#5170ff]" />
                              </div>
                              <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">العنوان</h3>
                            </div>
                            <FormField
                              control={newClientForm.control}
                              name="address"
                              render={({ field }) => (
                                <FormItem>
                                  <FormLabel className="text-right">العنوان</FormLabel>
                                  <FormControl>
                                    <Input
                                      placeholder="الشارع، الحي، رقم المبنى"
                                      className="h-11 rounded-xl"
                                      {...field}
                                    />
                                  </FormControl>
                                  <FormMessage />
                                </FormItem>
                              )}
                            />
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <FormField
                                control={newClientForm.control}
                                name="city_id"
                                render={({ field }) => (
                                  <FormItem>
                                    <FormLabel className="text-right">المدينة</FormLabel>
                                    <FormControl>
                                      <CitiesSelect
                                        value={field.value}
                                        onChange={field.onChange}
                                        disabled={isCreatingOrder}
                                      />
                                    </FormControl>
                                    <FormMessage />
                                  </FormItem>
                                )}
                              />
                              <FormField
                                control={newClientForm.control}
                                name="notes"
                                render={({ field }) => (
                                  <FormItem>
                                    <FormLabel className="text-right">
                                      ملاحظات (اختياري)
                                    </FormLabel>
                                    <FormControl>
                                      <Textarea
                                        placeholder="ملاحظات إضافية..."
                                        className="min-h-[44px] rounded-xl resize-none"
                                        {...field}
                                      />
                                    </FormControl>
                                    <FormMessage />
                                  </FormItem>
                                )}
                              />
                            </div>
                          </div>
                        </form>
                      </Form>
                    </TabsContent>
                  </Tabs>
                </section>

              </div>

              {/* Right column: Product filters + Products table + Item details + Measurements */}
              <div className="space-y-6">
                {/* Product filters */}
                <section className="rounded-2xl bg-white dark:bg-slate-900 p-6 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                  <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                        <Filter className="h-5 w-5 text-[#5170ff]" />
                      </div>
                      <div>
                        <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">فلترة المنتجات</h2>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                          ابحث حسب الكود، قسم المنتجات وأقسام المنتجات الفرعية
                        </p>
                      </div>
                    </div>
                    <div className="flex gap-2 shrink-0">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setShowFilters(!showFilters)}
                        className="rounded-xl border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800"
                      >
                        <Filter className="ml-2 h-4 w-4" />
                        {showFilters ? "إخفاء الفلاتر" : "عرض الفلاتر"}
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={resetFilters}
                        className="rounded-xl border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800"
                      >
                        <RotateCcw className="ml-2 h-4 w-4" />
                        إعادة تعيين
                      </Button>
                    </div>
                  </div>

                  {showFilters && (
                    <div className="mt-5 pt-5 border-t border-slate-200 dark:border-slate-700 space-y-5">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">كود المنتج</Label>
                          <Input
                            placeholder="ابحث بالكود..."
                            value={productCodeFilter}
                            onChange={(e) => setProductCodeFilter(e.target.value)}
                            className="h-11 rounded-xl"
                          />
                        </div>
                      </div>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">قسم المنتجات</Label>
                          <CategoriesSelect
                            value={categoryId}
                            onChange={(id) => {
                              setCategoryId(id);
                              setSubcategoryIds([]);
                            }}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">أقسام المنتجات الفرعية</Label>
                          <SubcategoriesSelect
                            multiple
                            value={subcategoryIds}
                            onChange={setSubcategoryIds}
                            category_id={categoryId ? Number(categoryId) : undefined}
                          />
                        </div>
                      </div>
                    </div>
                  )}
                </section>

                {/* Available products table */}
                <section className="rounded-2xl bg-white dark:bg-slate-900 p-6 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                  <div className="flex items-center gap-3 mb-5">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                      <ShoppingBag className="h-5 w-5 text-[#5170ff]" />
                    </div>
                    <div>
                      <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">المنتجات المتاحة</h2>
                      <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        {canFetchClothes
                          ? `إجمالي المنتجات المتاحة: ${clothesData?.total || 0}`
                          : "اختر المكان والتاريخ لعرض المنتجات"}
                      </p>
                    </div>
                  </div>

                  <div className="pt-2">
                    {!canFetchClothes ? (
                      <div className="flex flex-col items-center justify-center py-16 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/50">
                        <Calendar className="h-14 w-14 text-slate-300 dark:text-slate-600 mb-4" />
                        <p className="text-slate-600 dark:text-slate-400 font-medium">يرجى اختيار الفرع والتواريخ الثلاثة (الاستلام، الاسترجاع، الفرح)</p>
                        <p className="text-sm text-slate-500 dark:text-slate-500 mt-1">سيتم جلب المنتجات المتاحة تلقائياً بعد إكمال الاختيار</p>
                      </div>
                    ) : isClothesPending ? (
                      <div className="overflow-hidden rounded-xl ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                        <Table>
                          <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800">
                              <TableHead className="w-12">#</TableHead>
                              <TableHead>الكود</TableHead>
                              <TableHead>الاسم</TableHead>
                              <TableHead>الحالة</TableHead>
                              <TableHead>المكان</TableHead>
                              <TableHead className="text-end">السعر</TableHead>
                              <TableHead className="w-24 text-center">إجراءات</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            {Array.from({ length: 6 }).map((_, i) => (
                              <TableRow key={i}>
                                <TableCell><div className="h-4 w-6 bg-slate-200 dark:bg-slate-700 rounded animate-pulse" /></TableCell>
                                <TableCell><div className="h-4 w-16 bg-slate-200 dark:bg-slate-700 rounded animate-pulse" /></TableCell>
                                <TableCell><div className="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded animate-pulse" /></TableCell>
                                <TableCell><div className="h-5 w-20 bg-slate-200 dark:bg-slate-700 rounded animate-pulse" /></TableCell>
                                <TableCell><div className="h-4 w-14 bg-slate-200 dark:bg-slate-700 rounded animate-pulse" /></TableCell>
                                <TableCell><div className="h-4 w-14 bg-slate-200 dark:bg-slate-700 rounded animate-pulse" /></TableCell>
                                <TableCell><div className="h-8 w-16 bg-slate-200 dark:bg-slate-700 rounded animate-pulse mx-auto" /></TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </div>
                    ) : availableClothes.length === 0 ? (
                      <div className="flex flex-col items-center justify-center py-16 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/50">
                        <ShoppingBag className="h-14 w-14 text-slate-300 dark:text-slate-600 mb-4" />
                        <p className="text-slate-600 dark:text-slate-400 font-medium">لا توجد منتجات متاحة</p>
                        <p className="text-sm text-slate-500 dark:text-slate-500 mt-1">حسب الفلاتر المحددة</p>
                      </div>
                    ) : (
                      <div className="overflow-hidden rounded-xl ring-1 ring-slate-200/60 dark:ring-slate-700/50 bg-white dark:bg-slate-900">
                        <Table>
                          <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                              <TableHead className="w-12 text-slate-600 dark:text-slate-400 font-semibold">#</TableHead>
                              <TableHead className="text-slate-600 dark:text-slate-400 font-semibold">الكود</TableHead>
                              <TableHead className="text-slate-600 dark:text-slate-400 font-semibold">الاسم</TableHead>
                              <TableHead className="text-slate-600 dark:text-slate-400 font-semibold">الحالة</TableHead>
                              <TableHead className="text-slate-600 dark:text-slate-400 font-semibold">المكان</TableHead>
                              <TableHead className="text-end text-slate-600 dark:text-slate-400 font-semibold">السعر</TableHead>
                              <TableHead className="w-28 text-center text-slate-600 dark:text-slate-400 font-semibold">إجراءات</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            {availableClothes.map((cloth: any) => {
                              const isSelected = selectedProducts.some(
                                (p) => p.id === cloth.id,
                              );
                              const isHighlighted = selectedProduct?.id === cloth.id;
                              return (
                                <TableRow
                                  key={cloth.id}
                                  className={`transition-colors border-b border-slate-100 dark:border-slate-800 last:border-0 ${
                                    isSelected
                                      ? "bg-emerald-50/80 dark:bg-emerald-950/30"
                                      : isHighlighted
                                        ? "bg-[#5170ff]/5 dark:bg-[#5170ff]/10"
                                        : "hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                  }`}
                                >
                                  <TableCell className="font-mono text-sm text-slate-500 dark:text-slate-400">
                                    {cloth.id}
                                  </TableCell>
                                  <TableCell className="font-mono font-semibold text-slate-900 dark:text-slate-100">
                                    {cloth.code}
                                  </TableCell>
                                  <TableCell className="text-slate-700 dark:text-slate-300">
                                    {cloth.name ?? cloth.code}
                                  </TableCell>
                                  <TableCell>
                                    {(() => {
                                      const effectiveStatus = getEffectiveDisplayStatus(cloth);
                                      return (
                                        <Badge
                                          variant={getStatusBadgeVariant(effectiveStatus)}
                                          className={`text-xs rounded-lg font-medium ${
                                            effectiveStatus === "ready_for_rent"
                                              ? "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border-0"
                                              : ""
                                          }`}
                                        >
                                          {getStatusLabel(effectiveStatus)}
                                        </Badge>
                                      );
                                    })()}
                                  </TableCell>
                                  <TableCell className="text-slate-600 dark:text-slate-400 text-sm">
                                    {cloth.entity_type === "branch"
                                      ? "فرع"
                                      : cloth.entity_type === "factory"
                                        ? "مصنع"
                                        : "ورشة"}{" "}
                                    <span className="font-mono">#{cloth.entity_id}</span>
                                  </TableCell>
                                  <TableCell className="text-end font-bold text-[#5170ff] tabular-nums">
                                    {cloth.price ? `${cloth.price} ج.م` : "—"}
                                  </TableCell>
                                  <TableCell>
                                    <div className="flex justify-center">
                                      {cloth.can_select === true ? (
                                        <Button
                                          variant={isSelected ? "outline" : "default"}
                                          size="sm"
                                          onClick={() => handleClothSelect(cloth)}
                                          disabled={isSelected}
                                          className={`rounded-lg font-medium ${
                                            isSelected
                                              ? "border-emerald-300 text-emerald-700 bg-emerald-50 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-400"
                                              : "bg-[#5170ff] hover:bg-[#4560e6] text-white"
                                          }`}
                                        >
                                          {isSelected ? "مضافة ✓" : "اختيار"}
                                        </Button>
                                      ) : (
                                        <span className="text-xs text-slate-400 dark:text-slate-500">—</span>
                                      )}
                                    </div>
                                  </TableCell>
                                </TableRow>
                              );
                            })}
                          </TableBody>
                        </Table>
                      </div>
                    )}
                  </div>
                </section>

                {/* Selected product details and measurements */}
                <section className="rounded-2xl bg-white dark:bg-slate-900 p-6 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                      <Ruler className="h-5 w-5 text-[#5170ff]" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">
                        تفاصيل المنتج المختار والمقاسات
                      </h2>
                      {selectedProduct ? (
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1 font-mono">
                          {selectedProduct.code}
                        </p>
                      ) : (
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                          قم باختيار منتج من الجدول لعرض التفاصيل وإدخال المقاسات
                        </p>
                      )}
                    </div>
                  </div>

                  {!selectedProduct ? (
                    <div className="flex flex-col items-center justify-center py-12 px-6 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/50 text-center">
                      <div className="rounded-full bg-slate-100 dark:bg-slate-800 p-5 mb-4">
                        <Ruler className="h-12 w-12 text-slate-400 dark:text-slate-500" />
                      </div>
                      <p className="text-base font-semibold text-slate-900 dark:text-slate-100 mb-1">
                        قم باختيار المنتج
                      </p>
                      <p className="text-sm text-slate-500 dark:text-slate-400 max-w-sm">
                        اختر قطعة من جدول المنتجات المتاحة أعلاه، ثم أدخل التفاصيل والمقاسات (اختياري)
                        واضغط إضافة المنتج.
                      </p>
                    </div>
                  ) : (
                    <div className="space-y-6">
                      {/* Price and quantity */}
                      <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-6 ring-1 ring-slate-200/50 dark:ring-slate-700/50 shadow-sm">
                        <div className="flex items-center gap-2 mb-5">
                          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#5170ff]/10">
                            <Banknote className="h-4 w-4 text-[#5170ff]" />
                          </div>
                          <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                            السعر والكمية
                          </h3>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                          <div className="space-y-2">
                            <Label htmlFor="quantity" className="text-sm font-medium text-slate-700 dark:text-slate-300 block">
                              الكمية
                            </Label>
                            <Input
                              id="quantity"
                              value={productDetails.quantity}
                              onChange={(e) => {
                                const val = e.target.value.replace(/[^0-9]/g, "");
                                setProductDetails({
                                  ...productDetails,
                                  quantity: val || "1",
                                });
                              }}
                              className="h-11 rounded-xl"
                            />
                          </div>
                          <div className="space-y-2">
                            <Label htmlFor="price" className="text-sm font-medium text-slate-700 dark:text-slate-300 block">
                              السعر (ج.م)
                            </Label>
                            <div className="relative">
                              <Input
                                id="price"
                                value={productDetails.price}
                                onChange={(e) => {
                                  const val = e.target.value.replace(/[^0-9.]/g, "");
                                  setProductDetails({
                                    ...productDetails,
                                    price: val,
                                  });
                                }}
                                className="h-11 rounded-xl pr-10"
                              />
                              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500 dark:text-slate-400">
                                ج.م
                              </span>
                            </div>
                          </div>
                          <div className="space-y-2">
                            <Label htmlFor="paid" className="text-sm font-medium text-slate-700 dark:text-slate-300 block">
                              المدفوع (ج.م)
                            </Label>
                            <div className="relative">
                              <Input
                                id="paid"
                                value={productDetails.paid}
                                onChange={(e) => {
                                  const val = e.target.value.replace(/[^0-9.]/g, "");
                                  setProductDetails({
                                    ...productDetails,
                                    paid: val,
                                  });
                                }}
                                className="h-11 rounded-xl pr-10"
                              />
                              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500 dark:text-slate-400">
                                ج.م
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>

                      {/* Discount */}
                      <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-6 ring-1 ring-slate-200/50 dark:ring-slate-700/50 shadow-sm">
                        <div className="flex items-center gap-2 mb-5">
                          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#5170ff]/10">
                            <Percent className="h-4 w-4 text-[#5170ff]" />
                          </div>
                          <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                            خصم القطعة
                          </h3>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                          <div className="space-y-2">
                            <Label htmlFor="discount_type" className="text-sm font-medium text-slate-700 dark:text-slate-300 block">
                              نوع الخصم
                            </Label>
                            <Select
                              value={productDetails.discount_type}
                              onValueChange={(value: "none" | "percentage" | "fixed") =>
                                setProductDetails({
                                  ...productDetails,
                                  discount_type: value,
                                })
                              }
                            >
                              <SelectTrigger className="h-11 rounded-xl" id="discount_type">
                                <SelectValue placeholder="اختر نوع الخصم" />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="none">لا يوجد</SelectItem>
                                <SelectItem value="percentage">نسبة مئوية</SelectItem>
                                <SelectItem value="fixed">مبلغ ثابت</SelectItem>
                              </SelectContent>
                            </Select>
                          </div>
                          {productDetails.discount_type !== "none" && (
                            <div className="space-y-2">
                              <Label htmlFor="discount_value" className="text-sm font-medium text-slate-700 dark:text-slate-300 block">
                                قيمة الخصم
                              </Label>
                              <Input
                                id="discount_value"
                                value={productDetails.discount_value}
                                onChange={(e) => {
                                  const val = e.target.value.replace(/[^0-9.]/g, "");
                                  setProductDetails({
                                    ...productDetails,
                                    discount_value: val,
                                  });
                                }}
                                className="h-11 rounded-xl"
                                placeholder={productDetails.discount_type === "percentage" ? "مثال: 10" : "مثال: 50"}
                              />
                            </div>
                          )}
                        </div>
                      </div>

                      {/* Notes */}
                      <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-6 ring-1 ring-slate-200/50 dark:ring-slate-700/50 shadow-sm">
                        <div className="flex items-center gap-2 mb-5">
                          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#5170ff]/10">
                            <StickyNote className="h-4 w-4 text-[#5170ff]" />
                          </div>
                          <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                            ملاحظات المنتج
                          </h3>
                        </div>
                        <div className="space-y-2">
                          <Label htmlFor="notes" className="text-sm font-medium text-slate-700 dark:text-slate-300 block">
                            ملاحظات اختيارية حول هذه القطعة
                          </Label>
                          <Input
                            id="notes"
                            placeholder="أدخل ملاحظاتك هنا..."
                            value={productDetails.notes}
                            onChange={(e) =>
                              setProductDetails({
                                ...productDetails,
                                notes: e.target.value,
                              })
                            }
                            className="h-11 rounded-xl"
                          />
                        </div>
                      </div>

                      {/* Measurements */}
                      <div className="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-6 ring-1 ring-slate-200/50 dark:ring-slate-700/50 shadow-sm">
                        <div className="flex items-center gap-2 mb-4">
                          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#5170ff]/10">
                            <Ruler className="h-4 w-4 text-[#5170ff]" />
                          </div>
                          <div>
                            <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                              المقاسات (اختياري)
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                              تُربط بالمنتج عند الإضافة. جميع الحقول اختيارية.
                            </p>
                          </div>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                          {[
                            { key: "sleeveLength", label: "طول الكم", placeholder: "سم" },
                            { key: "forearm", label: "الزند", placeholder: "سم" },
                            { key: "shoulderWidth", label: "عرض الكتف", placeholder: "سم" },
                            { key: "cuffs", label: "الإسوار", placeholder: "سم" },
                            { key: "waist", label: "الوسط", placeholder: "سم" },
                            { key: "chestLength", label: "طول الصدر", placeholder: "سم" },
                            { key: "totalLength", label: "الطول الكلي", placeholder: "سم" },
                            { key: "hinch", label: "الهش", placeholder: "سم" },
                            {
                              key: "dressSize",
                              label: "مقاس الفستان",
                              placeholder: "S, M, L, XL",
                            },
                          ].map(({ key, label, placeholder }) => (
                            <div key={key} className="space-y-2">
                              <Label className="text-sm font-medium text-slate-700 dark:text-slate-300 block">
                                {label}
                              </Label>
                              <Input
                                value={(measurements as any)[key]}
                                onChange={(e) =>
                                  setMeasurements({ ...measurements, [key]: e.target.value })
                                }
                                className="h-11 rounded-xl"
                                placeholder={placeholder}
                              />
                            </div>
                          ))}
                        </div>
                      </div>

                      
                      {selectedProduct?.can_select === true && (
                        <div className="flex justify-center pt-2">
                          <Button
                            onClick={handleAddProduct}
                            className="h-12 px-10 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow-lg shadow-emerald-500/25 hover:shadow-xl transition-all active:scale-[0.98]"
                          >
                            <Plus className="ml-2 h-5 w-5" />
                            إضافة المنتج إلى الطلب
                          </Button>
                        </div>
                      )}
                    </div>
                  )}
                </section>
              </div>
            </div>

            {/* Order-level discount (optional) */}
            <section className="rounded-2xl bg-white dark:bg-slate-900 p-6 ring-1 ring-slate-200/60 dark:ring-slate-700/50">
              <div className="flex items-center gap-3 mb-5">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#5170ff]/10">
                  <Percent className="h-5 w-5 text-[#5170ff]" />
                </div>
                <div>
                  <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">
                    خصم على الطلب كاملاً (اختياري)
                  </h2>
                  <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    يمكنك أيضاً إضافة خصم لكل قطعة من تفاصيل المنتج.
                  </p>
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label className="text-slate-700 dark:text-slate-300 font-medium">نوع الخصم</Label>
                  <Select
                    value={orderDiscount.type}
                    onValueChange={(v: "none" | "percentage" | "fixed") =>
                      setOrderDiscount({ ...orderDiscount, type: v })
                    }
                  >
                    <SelectTrigger className="h-11 rounded-xl">
                      <SelectValue placeholder="نوع الخصم" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">لا يوجد</SelectItem>
                      <SelectItem value="percentage">نسبة مئوية</SelectItem>
                      <SelectItem value="fixed">مبلغ ثابت</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                {orderDiscount.type !== "none" && (
                  <div className="space-y-2">
                    <Label className="text-slate-700 dark:text-slate-300 font-medium">قيمة الخصم</Label>
                    <Input
                      value={orderDiscount.value || ""}
                      onChange={(e) => {
                        const val = e.target.value.replace(/[^0-9.]/g, "");
                        const numVal = val === "" ? 0 : parseFloat(val) || 0;
                        setOrderDiscount({
                          ...orderDiscount,
                          value: numVal,
                        });
                      }}
                      className="h-11 rounded-xl"
                      placeholder={
                        orderDiscount.type === "percentage" ? "مثال: 10" : "مثال: 50"
                      }
                    />
                  </div>
                )}
              </div>
            </section>
          </div>

          <div className="flex flex-col gap-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/40 px-8 py-6">
            <Button
              variant="outline"
              onClick={() => navigate("/orders/list")}
              className="h-12 w-fit rounded-xl border-slate-200 px-6 dark:border-slate-600 hover:bg-white dark:hover:bg-slate-800"
            >
              <ArrowLeft className="ml-2 h-5 w-5" />
              رجوع للقائمة
            </Button>
          </div>
        </div>
        </div>
      </div>
    </div>
  );
}

export default ChooseClient;
