import { Route } from "react-router";
import ReturnsList from "@/pages/returns/ReturnsList";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const returnsRoutes = () => {
  return (
    <Route
      path="/returns"
      element={
        <PermissionProtectedRoute permission={["orders.return"]} />
      }
    >
      <Route index element={<ReturnsList />} />
    </Route>
  );
};

