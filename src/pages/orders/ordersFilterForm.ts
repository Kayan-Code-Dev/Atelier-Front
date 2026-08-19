import * as z from "zod";

/** نفس حقول فلاتر الطلبات المتقدمة في قائمة الطلبات وبحث التسليمات — تعديل واحد يحدّث الصفحتين. */
export const ordersFilterSchema = z.object({
  order_id: z.string().optional(),
  client_id: z.string().optional(),
  employee_id: z.string().optional(),
  cloth_name: z.string().optional(),
  cloth_code: z.string().optional(),
  category_id: z.string().optional(),
  subcategory_id: z.string().optional(),
  visit_date_from: z.string().optional(),
  visit_date_to: z.string().optional(),
  delivery_date_from: z.string().optional(),
  delivery_date_to: z.string().optional(),
  return_date_from: z.string().optional(),
  return_date_to: z.string().optional(),
});

export type OrdersFilterFormValues = z.infer<typeof ordersFilterSchema>;

export const ORDERS_FILTER_FORM_DEFAULTS: OrdersFilterFormValues = {
  order_id: "",
  client_id: "",
  employee_id: "",
  cloth_name: "",
  cloth_code: "",
  category_id: "",
  subcategory_id: "",
  visit_date_from: "",
  visit_date_to: "",
  delivery_date_from: "",
  delivery_date_to: "",
  return_date_from: "",
  return_date_to: "",
};

export const ORDERS_FILTER_DEBOUNCE_MS = 500;
