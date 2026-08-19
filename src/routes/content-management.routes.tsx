import ContentManagementPage from "@/pages/content-management-page/ContentManagementPage";
import { ContentSettingsIndexRedirect } from "@/pages/content-management-page/ContentSettingsIndexRedirect";
import Currencies from "@/pages/content-management-page/currencies/Currencies";
import SettingsProfileTab from "@/pages/content-management-page/settings-tabs/SettingsProfileTab";
import BranchSettingsTab from "@/pages/content-management-page/settings-tabs/BranchSettingsTab";
import ProductTaxonomySettingsTab from "@/pages/content-management-page/settings-tabs/ProductTaxonomySettingsTab";
import InvoiceRulesSettingsTab from "@/pages/content-management-page/settings-tabs/InvoiceRulesSettingsTab";
import QuotasSettingsTab from "@/pages/content-management-page/settings-tabs/QuotasSettingsTab";
import SubscriptionSettingsTab from "@/pages/content-management-page/settings-tabs/SubscriptionSettingsTab";
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
          ]}
        />
      }
    >
      <Route element={<ContentManagementPage />}>
        <Route index element={<ContentSettingsIndexRedirect />} />
        <Route path="profile" element={<SettingsProfileTab />} />
        <Route path="branches" element={<BranchSettingsTab />} />
        <Route path="currencies" element={<Currencies />} />
        <Route
          path="product-taxonomy"
          element={<ProductTaxonomySettingsTab />}
        />
        <Route path="invoice-rules" element={<InvoiceRulesSettingsTab />} />
        <Route path="quotas" element={<QuotasSettingsTab />} />
        <Route path="subscription" element={<SubscriptionSettingsTab />} />
        <Route path="*" element={<Navigate to="/content" replace />} />
      </Route>
    </Route>
  );
};
