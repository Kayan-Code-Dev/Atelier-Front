import { Route } from "react-router";
import Suppliers from "@/pages/suppliers/Suppliers";
import SupplierAccounts from "@/pages/suppliers/accounts/SupplierAccounts";
import CreateSupplierOrder from "@/pages/suppliers/CreateSupplierOrder";
import SupplierOrders from "@/pages/suppliers/SupplierOrders";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const suppliersRoutes = () => {
  return (
    <Route
      path="/suppliers"
      element={
        <PermissionProtectedRoute
          permission={[
            "suppliers.view",
            "suppliers.create",
            "supplier-orders.view",
            "supplier-orders.create",
          ]}
        />
      }
    >
      <Route index element={<Suppliers />} />
      <Route path="accounts" element={<SupplierAccounts />} />
      <Route path="orders" element={<SupplierOrders />} />
      <Route path="orders/add" element={<CreateSupplierOrder />} />
    </Route>
  );
};





