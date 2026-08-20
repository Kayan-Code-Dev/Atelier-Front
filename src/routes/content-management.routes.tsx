import ContentManagementPage from "@/pages/content-management-page/ContentManagementPage";
import { ContentSettingsIndexRedirect } from "@/pages/content-management-page/ContentSettingsIndexRedirect";
import Currencies from "@/pages/content-management-page/currencies/Currencies";
import SettingsProfileTab from "@/pages/content-management-page/settings-tabs/SettingsProfileTab";
import CompanySettingsTab from "@/pages/content-management-page/settings-tabs/CompanySettingsTab";
import BranchSettingsTab from "@/pages/content-management-page/settings-tabs/BranchSettingsTab";
import ProductTaxonomySettingsTab from "@/pages/content-management-page/settings-tabs/ProductTaxonomySettingsTab";
import InvoiceRulesSettingsTab from "@/pages/content-management-page/settings-tabs/InvoiceRulesSettingsTab";
import SystemSettingsTab from "@/pages/content-management-page/settings-tabs/SystemSettingsTab";
import {
  FinancialSettingsTab,
  NotificationsSettingsTab,
  UsersRolesSettingsTab,
} from "@/pages/content-management-page/settings-tabs/ModuleLinkTabs";
import { Navigate, Route } from "react-router";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const contentManagementRouts = () => {
  return (
    <Route
      path="content"
      element={
        <PermissionProtectedRoute
          permission={[
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
          ]}
        />
      }
    >
      <Route element={<ContentManagementPage />}>
        <Route index element={<ContentSettingsIndexRedirect />} />
        <Route path="company" element={<CompanySettingsTab />} />
        <Route path="profile" element={<SettingsProfileTab />} />
        <Route path="branches" element={<BranchSettingsTab />} />
        <Route path="financial" element={<FinancialSettingsTab />} />
        <Route path="currencies" element={<Currencies />} />
        <Route
          path="product-taxonomy"
          element={<ProductTaxonomySettingsTab />}
        />
        <Route path="invoice-rules" element={<InvoiceRulesSettingsTab />} />
        <Route path="notifications" element={<NotificationsSettingsTab />} />
        <Route path="users" element={<UsersRolesSettingsTab />} />
        <Route path="system" element={<SystemSettingsTab />} />
        {/* Legacy SaaS leftovers — permanent redirects away from quotas/subscription */}
        <Route path="quotas" element={<Navigate to="/content/system" replace />} />
        <Route
          path="subscription"
          element={<Navigate to="/content/company" replace />}
        />
        <Route path="*" element={<Navigate to="/content" replace />} />
      </Route>
    </Route>
  );
};
