import { Route } from "react-router";
import OverdueReturnsList from "@/pages/overduereturns/OverdueReturnsList";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const overduereturnsRoutes = () => {
  return (
    <Route
      path="/overdue-returns"
      element={
        <PermissionProtectedRoute
          permission={["orders.return", "orders.view"]}
        />
      }
    >
      <Route index element={<OverdueReturnsList />} />
    </Route>
  );
};

