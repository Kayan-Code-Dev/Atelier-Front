import { Route } from "react-router";
import Cashboxes from "@/pages/cashboxes/Cashboxes";
import CashboxDetails from "@/pages/cashboxes/CashboxDetails";
import CashboxTransactions from "@/pages/cashboxes/CashboxTransactions";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const cashboxesRoutes = () => {
  return (
    <Route
      path="/cashboxes"
      element={
        <PermissionProtectedRoute
          permission={[
            "cashbox.view",
            "cashbox.manage",
            "cashbox.recalculate",
            "transactions.view",
          ]}
        />
      }
    >
      <Route index element={<Cashboxes />} />
      <Route path="transactions" element={<CashboxTransactions />} />
      <Route path=":id" element={<CashboxDetails />} />
    </Route>
  );
};
