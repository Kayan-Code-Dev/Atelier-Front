import { lazy } from "react";
import PermissionProtectedRoute from "@/routes/PermissionProtectedRoute";
import { Route } from "react-router";
import { accountingEntriesRoutes } from "@/routes/accounting-entries.routes";
import { branchesRoutes } from "@/routes/branches.route";
import { cashboxesRoutes } from "@/routes/cashboxes.routes";
import { clothesRoutes } from "@/routes/clothes.routes";
import { contentManagementRouts } from "@/routes/content-management.routes";
import { deliveriesRoutes } from "@/routes/deliveries.routes";
import { employeesRoutes } from "@/routes/employees.routes";
import { expensesRoutes } from "@/routes/expenses.routes";
import { hrRoutes } from "@/routes/hr.routes";
import { inventoryRoutes } from "@/routes/inventory.routes";
import { ordersRoutes } from "@/routes/orders.routes";
import { overduereturnsRoutes } from "@/routes/overdureturns.routes";
import { paymentsRoutes } from "@/routes/payments.route";
import { permissionsRoutes } from "@/routes/permissions.routes";
import { returnsRoutes } from "@/routes/returns.routes";
import { salesRoutes } from "@/routes/sales.routes";
import { suppliersRoutes } from "@/routes/suppliers.routes";
import { tailoringRoutes } from "@/routes/tailoring.routes";
import { workshopRoutes } from "@/routes/workshop.routes";

const DashboardPage = lazy(
  () => import("@/pages/dashboard-page/dashboard-page"),
);
const Clients = lazy(() => import("@/pages/clients/Clients"));
const Factory = lazy(() => import("@/pages/factory/Factory"));
const Notifications = lazy(() => import("@/pages/notifications/Notifications"));
const AccountSettings = lazy(() => import("@/pages/account/AccountSettings"));
const HowItWorksPage = lazy(
  () => import("@/pages/how-it-works/HowItWorksPage"),
);

export function getAuthenticatedLayoutRouteElements() {
  return (
    <>
      <Route
        path="/dashboard"
        element={
          <PermissionProtectedRoute
            permission={[
              "dashboard.view",
              "dashboard.activity.view",
              "dashboard.business.view",
              "dashboard.hr.view",
            ]}
          />
        }
      >
        <Route index element={<DashboardPage />} />
      </Route>
      {branchesRoutes()}
      <Route
        path="/clients"
        element={
          <PermissionProtectedRoute
            permission={[
              "clients.view",
              "clients.create",
              "clients.update",
              "clients.delete",
              "clients.export",
            ]}
          />
        }
      >
        <Route index element={<Clients />} />
      </Route>
      {clothesRoutes()}
      {ordersRoutes()}
      {paymentsRoutes()}
      {hrRoutes()}
      {inventoryRoutes()}
      <Route
        path="/factory"
        element={
          <PermissionProtectedRoute
            permission={[
              "factories.view",
              "factories.create",
              "factories.update",
              "factories.delete",
              "factories.manage",
            ]}
          />
        }
      >
        <Route index element={<Factory />} />
      </Route>
      {workshopRoutes()}
      {permissionsRoutes()}
      {contentManagementRouts()}
      {employeesRoutes()}
      {cashboxesRoutes()}
      {accountingEntriesRoutes()}
      {expensesRoutes()}
      {suppliersRoutes()}
      {overduereturnsRoutes()}
      {deliveriesRoutes()}
      {returnsRoutes()}
      {salesRoutes()}
      {tailoringRoutes()}
      <Route
        path="/notifications"
        element={
          <PermissionProtectedRoute
            permission={["notifications.view", "notifications.manage"]}
          />
        }
      >
        <Route index element={<Notifications />} />
      </Route>
      <Route path="/account" element={<AccountSettings />} />
      <Route path="/how-it-works" element={<HowItWorksPage />} />
    </>
  );
}
