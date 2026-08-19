/** Role names mapped to Arabic labels for display (e.g. employee form/details). */
export const ROLE_NAME_TO_ARABIC: Record<string, string> = {
  general_manager: "المدير العام",
  dashboard_manager: "إدارة لوحة التحكم",
  notifications_manager: "إدارة الإشعارات",
  orders_manager: "إدارة الطلبات",
  orders_basic_view: "الطلبات (عرض وإنشاء)",
  orders_delivery_return_finish_cancel: "الطلبات (تسليم / إرجاع / إنهاء / إلغاء)",
  branches_manager: "إدارة الفروع",
  branches_basic_view_create: "الفروع (عرض وإنشاء)",
  hr_employees_manager: "إدارة موظفي الموارد البشرية",
  clients_manager: "إدارة العملاء",
  workshops_manager_basic: "إدارة الورش (أساسي)",
  inventories_manager: "إدارة المخازن",
  clothes_manager: "إدارة الملابس",
  transfers_manager: "إدارة النقل",
  categories_manager: "إدارة الأقسام",
  subcategories_manager: "إدارة الأقسام الفرعية",
  cities_manager: "إدارة المدن",
  countries_manager: "إدارة الدول",
  suppliers_manager: "إدارة الموردين",
  supplier_orders_manager: "إدارة أوامر الموردين",
  cashbox_manager: "إدارة الصندوق",
  transactions_manager: "إدارة المعاملات",
  payments_manager: "إدارة المدفوعات",
  expenses_manager: "إدارة المصروفات",
  hr_deductions_manager: "إدارة خصومات الموظفين",
  hr_deductions_viewer: "مشاهد خصومات الموظفين",
  factories_manager_basic: "إدارة المصانع (أساسي)",
};

/** Returns Arabic label for role if found, otherwise the original name. */
export function getRoleLabel(name: string | undefined | null): string {
  if (name == null || name === "") return "";
  const key = name.trim().toLowerCase();
  return ROLE_NAME_TO_ARABIC[key] ?? name;
}
