import React from "react";
import { SOLD_PROCESS_TYPE } from "@/lib/salesOrderConstants";
import {
  LayoutDashboard,
  Wallet,
  Receipt,
  Wallet2,
  FileText,
  List,
  PlusCircle,
  Truck,
  RotateCcw,
  Clock,
  Banknote,
  Users,
  UserCircle,
  Building2,
  Factory,
  Package,
  Wrench,
  Settings,
  ShieldEllipsis,
  FileBarChart,
  ArrowRightLeft,
  ShoppingCart,
  Scissors,
  Bell,
  Search,
  KeyRound,
  Gift,
  CircleHelp,
} from "lucide-react";

const iconSize = 20;
const createIcon = (Icon: React.ComponentType<{ size?: number; className?: string }>, size = iconSize) =>
  React.createElement(Icon, { size, className: "shrink-0 text-current" });

export type SidebarActiveMatch = {
  pathname: string;
  search?: Record<string, string>;
};

export type SidebarLabel = {
  icon: string | null;
  label: string;
  path: string;
  level: number;
  subItems?: SidebarLabel[];
  /** @deprecated use permissions */
  permission?: string;
  permissions?: string[];
  iconComponent?: React.ReactNode | null;
  /** Remix icon class (e.g. "ri-dashboard-3-line") for project-style sidebar */
  riIcon?: string;
  
  section?: string;
  /** Badge count (e.g. unread notifications) */
  badge?: number;
  activeMatch?: SidebarActiveMatch;
  activeExclude?: SidebarActiveMatch;
};

export const sidebarLabels: SidebarLabel[] = [
  // 1. Dashboard
  {
    icon: null,
    label: "لوحة التحكم",
    path: "/dashboard",
    level: 1,
    section: "الرئيسية",
    riIcon: "ri-dashboard-3-line",
    permissions: ["dashboard.view", "dashboard.activity.view", "dashboard.business.view", "dashboard.hr.view"],
    iconComponent: createIcon(LayoutDashboard),
  },
  {
    icon: null,
    label: "إدارة الحسابات",
    path: "/payments",
    level: 1,
    section: "المالية",
    riIcon: "ri-money-dollar-circle-line",
    permissions: ["payments.view", "expenses.view"],
    iconComponent: createIcon(Wallet),
    subItems: [
      {
        icon: null,
        label: "قائمة المدفوعات",
        path: "/payments",
        level: 2,
        riIcon: "ri-bank-card-line",
        permissions: ["payments.view", "payments.create", "payments.pay", "payments.cancel", "payments.export"],
        iconComponent: createIcon(Receipt),
      },
      {
        icon: null,
        label: "قائمة المصروفات",
        path: "/expenses",
        level: 2,
        riIcon: "ri-shopping-bag-3-line",
        permissions: ["expenses.view", "expenses.create", "expenses.update", "expenses.delete", "expenses.approve", "expenses.pay", "expenses.export"],
        iconComponent: createIcon(Wallet2),
      },
    ],
  },
  
  {
    icon: null,
    label: "الخزنة",
    path: "/cashboxes",
    level: 1,
    section: "المالية",
    riIcon: "ri-safe-2-line",
    permissions: ["cashboxes.view", "cashboxes.create", "cashboxes.update", "cashboxes.recalculate", "cash_movements.view"],
    iconComponent: createIcon(Banknote),
    subItems: [
      {
        icon: null,
        label: "الخزنة",
        path: "/cashboxes",
        level: 2,
        riIcon: "ri-banknote-line",
        permissions: ["cashboxes.view", "cashboxes.create", "cashboxes.update", "cashboxes.recalculate"],
        iconComponent: createIcon(Banknote, 18),
      },
      {
        icon: null,
        label: "كشف المعاملات",
        path: "/cashboxes/transactions",
        level: 2,
        riIcon: "ri-exchange-line",
        permissions: ["cash_movements.view", "cashboxes.view"],
        iconComponent: createIcon(ArrowRightLeft, 18),
      },
      {
        icon: null,
        label: "القيود المحاسبية",
        path: "/treasury/entries",
        level: 2,
        riIcon: "ri-file-list-2-line",
        permissions: ["accounting.journal_entries.view", "accounting.entries.view", "accounting.view"],
        iconComponent: createIcon(FileText, 18),
      },
    ],
  },
  
  {
    icon: null,
    label: "قسم البيع",
    path: "/sales",
    level: 1,
    section: "العمليات",
    riIcon: "ri-store-3-line",
    permissions: undefined,
    iconComponent: createIcon(ShoppingCart),
    subItems: [
      {
        icon: null,
        label: "فواتير البيع",
        path: "/sales/invoices",
        level: 2,
        riIcon: "ri-file-list-3-line",
        permissions: undefined,
        iconComponent: createIcon(FileText),
        activeMatch: {
          pathname: "/orders/list",
          search: { process_type: SOLD_PROCESS_TYPE },
        },
      },
      {
        icon: null,
        label: "إنشاء فاتورة بيع",
        path: "/sales/create",
        level: 2,
        riIcon: "ri-add-circle-line",
        permissions: undefined,
        iconComponent: createIcon(PlusCircle),
        activeMatch: { pathname: "/sales/create" },
      },
      {
        icon: null,
        label: "تقارير المبيعات",
        path: "/sales/reports",
        level: 2,
        riIcon: "ri-file-bar-chart-line",
        permissions: undefined,
        iconComponent: createIcon(FileBarChart),
      },
    ],
  },
  
  {
    icon: null,
    label: "قسم التفصيل",
    path: "/tailoring",
    level: 1,
    section: "العمليات",
    riIcon: "ri-scissors-cut-line",
    permissions: undefined,
    iconComponent: createIcon(Scissors),
    subItems: [
      {
        icon: null,
        label: "أوامر التفصيل",
        path: "/tailoring/orders",
        level: 2,
        riIcon: "ri-scissors-cut-line",
        permissions: undefined,
        iconComponent: createIcon(Scissors),
      },
      {
        icon: null,
        label: "تسليمات التفصيل",
        path: "/tailoring/deliveries",
        level: 2,
        riIcon: "ri-gift-2-line",
        permissions: undefined,
        iconComponent: createIcon(Gift),
      },
    ],
  },
  
  {
    icon: null,
    label: "قسم الإيجار",
    path: "/orders",
    level: 1,
    section: "العمليات",
    riIcon: "ri-key-2-line",
    permissions: ["invoices.view", "invoices.create", "invoices.update", "invoices.delete", "invoices.export"],
    iconComponent: createIcon(KeyRound),
    activeExclude: {
      pathname: "/orders/list",
      search: { process_type: SOLD_PROCESS_TYPE },
    },
  },
  // 5. التسليمات (ترتيب موحّد: تسليمات الفواتير → بحث → إرجاع → متأخرات)
  {
    icon: null,
    label: "التسليمات",
    path: "/deliveries",
    level: 1,
    section: "العمليات",
    riIcon: "ri-truck-line",
    permissions: ["invoice_delivery.view", "invoice_delivery.deliver", "invoice_delivery.return", "invoices.view", "invoices.cancel"],
    iconComponent: createIcon(Truck),
    subItems: [
      {
        icon: null,
        label: "تسليمات الفواتير",
        path: "/deliveries",
        level: 2,
        riIcon: "ri-truck-line",
        permissions: ["invoice_delivery.deliver", "invoice_delivery.view"],
        iconComponent: createIcon(Truck),
      },
      {
        icon: null,
        label: "بحث التسليمات والإرجاعات",
        path: "/orders/search-deliveries-returns",
        level: 2,
        riIcon: "ri-search-2-line",
        permissions: ["invoices.view", "invoices.export", "invoice_delivery.deliver", "invoice_delivery.return"],
        iconComponent: createIcon(Search),
      },
      {
        icon: null,
        label: "إرجاعات الفواتير",
        path: "/returns",
        level: 2,
        riIcon: "ri-arrow-go-back-line",
        permissions: ["invoice_delivery.return", "invoice_delivery.view"],
        iconComponent: createIcon(RotateCcw),
      },
      {
        icon: null,
        label: "الإرجاعات المتأخرة",
        path: "/overdue-returns",
        level: 2,
        riIcon: "ri-time-line",
        permissions: ["invoice_delivery.return", "invoices.view"],
        iconComponent: createIcon(Clock),
      },
    ],
  },
  // الإدارة (موظفون: مسارات موحّدة مع project/src/router/config — employees / add / guarantees / salaries)
  {
    icon: null,
    label: "العملاء",
    path: "/clients",
    level: 1,
    section: "الإدارة",
    riIcon: "ri-group-line",
    permissions: ["customers.view", "customers.create", "customers.update", "customers.delete", "customers.export"],
    iconComponent: createIcon(Users),
  },
  {
    icon: null,
    label: "الموظفين",
    path: "/employees",
    level: 1,
    section: "الإدارة",
    riIcon: "ri-user-star-line",
    permissions: ["hr.employees.view", "hr.employees.create", "hr.employees.update", "hr.employees.delete", "hr.employees.manage-branches", "hr.employees.manage-entities", "hr.employees.terminate", "hr.custody.view", "hr.payroll.view"],
    iconComponent: createIcon(UserCircle),
    subItems: [
      {
        icon: null,
        label: "قائمة الموظفين",
        path: "/employees",
        level: 2,
        riIcon: "ri-team-line",
        permissions: ["hr.employees.view"],
        iconComponent: createIcon(List),
      },
      {
        icon: null,
        label: "إضافة موظف جديد",
        path: "/employees/add",
        level: 2,
        riIcon: "ri-user-add-line",
        permissions: ["hr.employees.create"],
        iconComponent: createIcon(PlusCircle),
      },
      {
        icon: null,
        label: "ضمانات الموظفين",
        path: "/employees/guarantees",
        level: 2,
        riIcon: "ri-shield-user-line",
        permissions: ["hr.custody.view", "hr.custody.assign", "hr.custody.return"],
        iconComponent: createIcon(ShieldEllipsis),
      },
      {
        icon: null,
        label: "كشوفات الرواتب",
        path: "/employees/salaries",
        level: 2,
        riIcon: "ri-money-dollar-circle-line",
        permissions: ["hr.employees.view", "hr.payroll.view"],
        iconComponent: createIcon(FileBarChart),
      },
    ],
  },
  {
    icon: null,
    label: "الموردين",
    path: "/suppliers",
    level: 1,
    section: "الإدارة",
    riIcon: "ri-shopping-cart-2-line",
    permissions: ["suppliers.view", "suppliers.create", "suppliers.update", "suppliers.delete", "suppliers.export", "purchase_orders.view", "purchase_orders.create", "purchase_orders.update", "purchase_orders.delete", "purchase_orders.export"],
    iconComponent: createIcon(ShoppingCart),
    subItems: [
      {
        icon: null,
        label: "قائمة الموردين",
        path: "/suppliers",
        level: 2,
        riIcon: "ri-list-check",
        permissions: ["suppliers.view"],
        iconComponent: createIcon(List),
      },
      {
        icon: null,
        label: "طلبيات الموردين",
        path: "/suppliers/orders",
        level: 2,
        riIcon: "ri-file-text-line",
        permissions: ["purchase_orders.view", "purchase_orders.create", "purchase_orders.update", "purchase_orders.delete"],
        iconComponent: createIcon(FileText),
      },
      {
        icon: null,
        label: "حسابات الموردين",
        path: "/suppliers/accounts",
        level: 2,
        riIcon: "ri-file-chart-line",
        permissions: ["suppliers.view", "purchase_orders.view"],
        iconComponent: createIcon(FileBarChart),
      },
    ],
  },
  {
    icon: null,
    label: "الإشعارات",
    path: "/notifications",
    level: 1,
    section: "الإدارة",
    riIcon: "ri-notification-3-line",
    permissions: ["notifications.view", "notifications.manage"],
    iconComponent: createIcon(Bell),
  },
  // الفروع والمرافق — مباشرة أسفل الإدارة (مثل project/src/components/feature/Sidebar.tsx)
  {
    icon: null,
    label: "الفروع",
    path: "/branch",
    level: 1,
    section: "الفروع والمرافق",
    riIcon: "ri-map-pin-2-line",
    permissions: ["branches.view", "branches.create", "branches.update", "branches.delete", "branches.export"],
    iconComponent: createIcon(Building2),
  },
  {
    icon: null,
    label: "الورشة",
    path: "/workshop",
    level: 1,
    riIcon: "ri-tools-line",
    permissions: ["workshops.view", "workshops.create", "workshops.update", "workshops.delete", "workshops.export", "workshops.manage-clothes", "workshops.approve-transfers", "workshops.update-status", "workshops.return-cloth", "workshops.view-logs", "settings.manage"],
    iconComponent: createIcon(Wrench),
  },
  {
    icon: null,
    label: "المصنع",
    path: "/factory",
    level: 1,
    riIcon: "ri-ancient-gate-line",
    permissions: ["factories.view", "factories.create", "factories.update", "factories.delete", "factories.export", "factories.manage", "factories.orders.view", "factories.orders.accept", "factories.orders.reject", "factories.orders.update-status", "factories.orders.add-notes", "factories.orders.set-delivery-date", "factories.orders.deliver", "factories.reports.view", "factories.dashboard.view", "settings.manage"],
    iconComponent: createIcon(Factory),
  },
  // 10. Permissions and Roles
  // {
  //   icon: null,
  //   label: "الصلاحيات والأذونات",
  //   path: "/permissions-roles",
  //   level: 1,
  //   section: "الصلاحيات والأذونات",
  //   riIcon: "ri-shield-user-line",
  //   permissions: ["roles.view", "roles.create", "roles.update", "roles.delete", "roles.export", "roles.assign-permissions", "users.view", "users.create", "users.update", "users.delete", "users.export"],
  //   iconComponent: createIcon(Shield),
  //   subItems: [
  //     {
  //       icon: null,
  //       label: "المشرف",
  //       path: "/permissions-roles/admins",
  //       level: 2,
  //       permissions: ["users.view", "roles.view", "roles.create", "roles.update", "roles.delete"],
  //       iconComponent: createIcon(UserCircle),
  //       subItems: [
  //         {
  //           icon: null,
  //           label: "الأذونات",
  //           path: "/permissions-roles/admins/permissions",
  //           level: 3,
  //           permissions: ["roles.view", "roles.assign-permissions"],
  //           iconComponent: createIcon(ShieldEllipsis),
  //         },
  //         {
  //           icon: null,
  //           label: "قائمة الصلاحيات",
  //           path: "/permissions-roles/admins/roles/list-roles",
  //           level: 3,
  //           permissions: ["roles.view"],
  //           iconComponent: createIcon(List),
  //         },
  //         {
  //           icon: null,
  //           label: "إضافة صلاحية جديدة",
  //           path: "/permissions-roles/admins/roles/create",
  //           level: 3,
  //           permissions: ["roles.create"],
  //           iconComponent: createIcon(PlusCircle),
  //         },
  //       ],
  //     },
  //     {
  //       icon: null,
  //       label: "مدير الفروع",
  //       path: "/permissions-roles/branches-managers",
  //       level: 2,
  //       permissions: ["roles.view"],
  //       iconComponent: createIcon(Building2),
  //       subItems: [
  //         {
  //           icon: null,
  //           label: "صلاحيات مديري الفروع",
  //           path: "/permissions-roles/branches-managers/roles",
  //           level: 3,
  //           permissions: ["roles.view"],
  //           iconComponent: createIcon(Shield),
  //         },
  //       ],
  //     },
  //     {
  //       icon: null,
  //       label: "الفروع",
  //       path: "/permissions-roles/branches",
  //       level: 2,
  //       permissions: ["roles.view", "branches.view"],
  //       iconComponent: createIcon(Building),
  //       subItems: [
  //         {
  //           icon: null,
  //           label: "صلاحيات الفروع",
  //           path: "/permissions-roles/branches/roles",
  //           level: 3,
  //           permissions: ["roles.view"],
  //           iconComponent: createIcon(Shield),
  //         },
  //       ],
  //     },
  //   ],
  // },
  // Disabled: Orders and sales management menu section
  // {
  //   icon: orders,
  //   path: "/orders",
  //   level: 1,
  //   subItems: [
  //     {
  //       icon: null,
  //       path: "/orders/branches",
  //       level: 2,
  //       subItems: [
  //         {
  //           icon: null,
  //           path: "/orders/branches/add-new-order",
  //           level: 3,
  //           permission: CREATE_ORDER,
  //         },
  //         {
  //           icon: null,
  //           path: "/orders/branches",
  //           level: 3,
  //           permission: READ_ORDERS,
  //         },
  //       ],
  //     },
  //     {
  //       icon: null,
  //       path: "/orders/employees",
  //       level: 2,
  //       subItems: [
  //         {
  //           icon: null,
  //           path: "/orders/employees/add-new-order",
  //           level: 3,
  //           permission: CREATE_ORDER,
  //         },
  //         {
  //           icon: null,
  //           path: "/orders/employees",
  //           level: 3,
  //           permission: READ_ORDERS,
  //         },
  //       ],
  //     },
  //   ],
  // },
  // Disabled: Human resources management menu section
  // {
  //   icon: hr,
  //   path: "/hr",
  //   level: 1,
  //   subItems: [
  //     {
  //       icon: null,
  //       path: "/hr/admins",
  //       level: 2,
  //       permission: READ_ADMINS,
  //       subItems: [
  //         {
  //           icon: null,
  //           path: "/hr/admins/list",
  //           level: 3,
  //           permission: READ_ADMINS,
  //         },
  //         {
  //           icon: null,
  //           path: "/hr/admins/recycled-bin",
  //           level: 3,
  //           permission: READ_DELETEDADMINS,
  //         },
  //       ],
  //     },
  //     {
  //       icon: null,
  //       level: 2,
  //       path: "/hr/branch-managers",
  //       permission: READ_BRANCHMANAGERS,
  //       subItems: [
  //         {
  //           icon: null,
  //           path: "/hr/branch-managers/all-branches-managers",
  //           level: 3,
  //           permission: READ_BRANCHMANAGERS,
  //         },
  //         {
  //           icon: null,
  //           path: "/hr/branch-managers/recycled-bin-all-branches-managers",
  //           level: 3,
  //           permission: READ_DELETEDBRANCHMANAGERS,
  //         },
  //         {
  //           icon: null,
  //           path: "/hr/branch-managers/branches",
  //           level: 3,
  //           permission: READ_BRANCHES,
  //         },
  //         {
  //           icon: null,
  //           path: "/hr/branch-managers/recycled-bin-branches",
  //           level: 3,
  //           permission: READ_DELETEDBRANCHES,
  //         },
  //       ],
  //     },
  //   ],
  // },

  // Disabled: Inventory management menu section
  // {
  //   icon: inventory,
  //   path: "/inventory",
  //   level: 1,
  //   permission: READ_INVENTORIES,
  //   subItems: [
  //     {
  //       icon: null,
  //       path: "/inventory/branches-managers/",
  //       level: 2,
  //       permission: READ_INVENTORIES,
  //       subItems: [
  //         {
  //           icon: null,
  //           path: "/inventory/branches-managers/",
  //           level: 2,
  //           permission: READ_INVENTORIES,
  //         },
  //         {
  //           icon: null,
  //           path: "/inventory/branches-managers/transfer-operations",
  //           level: 2,
  //           permission: READ_INVENTORYTRANSFERS,
  //         },
  //       ],
  //     },
  //     {
  //       icon: null,
  //       path: "/inventory/branches",
  //       level: 2,
  //       subItems: [
  //         {
  //           icon: null,
  //           path: "/inventory/branches/",
  //           level: 3,
  //           permission: READ_INVENTORIES,
  //         },
  //         {
  //           icon: null,
  //           path: "/inventory/branches/transfer-operations",
  //           permission: READ_INVENTORYTRANSFERS,
  //           level: 3,
  //         },
  //       ],
  //     },
  //     {
  //       icon: null,
  //       path: "/inventory/employees",
  //       level: 2,
  //       subItems: [
  //         {
  //           icon: null,
  //           path: "/inventory/employees/",
  //           level: 3,
  //         },
  //         {
  //           icon: null,
  //           path: "/inventory/employees/transfer-operations",
  //           level: 3,
  //         },
  //       ],
  //     },
  //   ],
  // },
  // Product management
  {
    icon: null,
    label: "إدارة المنتجات",
    path: "/clothes",
    level: 1,
    section: "المخزون",
    riIcon: "ri-price-tag-3-line",
    permissions: ["dresses.view", "dresses.create", "dresses.update", "dresses.delete", "dresses.export", "inventory.view", "inventory.manage"],
    iconComponent: createIcon(Package),
    subItems: [
      {
        icon: null,
        label: "قائمة المنتجات",
        path: "/clothes/list",
        level: 2,
        riIcon: "ri-list-check-2",
        permissions: ["dresses.view", "dresses.export"],
        iconComponent: createIcon(List),
      },
      {
        icon: null,
        label: "نقل المنتجات",
        path: "/clothes/transfer",
        level: 2,
        riIcon: "ri-arrow-left-right-line",
        permissions: [
          "inventory.view",
          "inventory.manage",
          "dresses.update",
        ],
        iconComponent: createIcon(ArrowRightLeft),
      },
    ],
  },
  // Disabled: Permissions and roles management menu section
  // {
  //   icon: permissions,
  //   path: "/permissions-roles",
  //   level: 1,
  //   subItems: [
  //     {
  //       icon: null,
  //       path: "/permissions-roles/admins",
  //       level: 2,
  //       subItems: [
  //         {
  //           icon: null,
  //           path: "/permissions-roles/admins/permissions",
  //           level: 2,
  //           permission: READ_PERMISSIONS,
  //         },
  //         {
  //           icon: null,
  //           path: "/permissions-roles/admins/roles",
  //           level: 2,
  //           subItems: [
  //             {
  //               icon: null,
  //               path: "/permissions-roles/admins/roles/create",
  //               level: 3,
  //               permission: CREATE_ROLE,
  //             },
  //             {
  //               icon: null,
  //               path: "/permissions-roles/admins/roles/list-roles",
  //               level: 3,
  //               permission: READ_ROLES,
  //             },
  //           ],
  //         },
  //       ],
  //     },
  //     {
  //       icon: null,
  //       path: "/permissions-roles/branches-managers/roles",
  //       level: 2,
  //       permission: READ_ROLES,
  //     },
  //     {
  //       icon: null,
  //       path: "/permissions-roles/branches/roles",
  //       level: 2,
  //       permission: READ_ROLES,
  //     },
  //   ],
  // },
  {
    icon: null,
    label: "الإعدادات",
    path: "/content",
    level: 1,
    section: "النظام",
    riIcon: "ri-settings-3-line",
    permissions: [
      "dashboard.view",
      "branches.view",
      "categories.view",
      "subcategories.view",
      "currencies.view",
      "settings.view",
      "settings.manage",
      "cashboxes.view",
      "notifications.view",
      "hr.employees.view",
    ],
    iconComponent: createIcon(Settings),
  },
  {
    icon: null,
    label: "كيف يعمل النظام",
    path: "/how-it-works",
    level: 1,
    section: "النظام",
    riIcon: "ri-question-line",
    iconComponent: createIcon(CircleHelp),
  },
];
