import Payments from "@/pages/payments/Payments";
import { Route } from "react-router";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const paymentsRoutes = () => {
  return (
    <Route
      path="payments"
      element={
        <PermissionProtectedRoute
          permission={[
            "payments.view",
            "payments.create",
            "payments.pay",
            "payments.cancel",
            "payments.export",
          ]}
        />
      }
    >
      <Route index element={<Payments />} />
    </Route>
  );
};
