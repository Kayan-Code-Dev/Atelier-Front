import Inventory from "@/pages/inventory-page/branches-managers/Inventory";
import TransferOperations from "@/pages/inventory-page/branches-managers/transfer-operations/TransferOperations";
import BranchesTransferOperations from "@/pages/inventory-page/branches/branches-transfer-operations/BranchesTransferOperations";
import BranchesInventory from "@/pages/inventory-page/branches/BranchesInventory";
import EmployeesTransferOperations from "@/pages/inventory-page/employees/employees-transfer-operations/EmployeesTransferOperations";
import EmployeesInventory from "@/pages/inventory-page/employees/EmployeesInventory";
import InventoryWelcomePage from "@/pages/inventory-page/InventoryWelcomePage";
import { Route } from "react-router";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const inventoryRoutes = () => {
  return (
    <Route
      path="/inventory"
      element={
        <PermissionProtectedRoute
          permission={["inventories.view", "transfers.view"]}
        />
      }
    >
      <Route element={<InventoryWelcomePage />}>
      <Route path="branches-managers">
        <Route index element={<Inventory />} />
        <Route path="transfer-operations" element={<TransferOperations />} />
      </Route>
      <Route path="branches">
        <Route index element={<BranchesInventory />} />
        <Route
          path="transfer-operations"
          element={<BranchesTransferOperations />}
        />
      </Route>
      <Route path="employees">
        <Route index element={<EmployeesInventory />} />
        <Route
          path="transfer-operations"
          element={<EmployeesTransferOperations />}
        />
      </Route>
      </Route>
    </Route>
  );
};
