import Expenses from "@/pages/expenses/Expenses";
import { Route } from "react-router";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const expensesRoutes = () => {
  return (
    <Route
      path="/expenses"
      element={
        <PermissionProtectedRoute
          permission={[
            "expenses.view",
            "expenses.create",
            "expenses.update",
            "expenses.delete",
            "expenses.approve",
            "expenses.pay",
            "expenses.export",
          ]}
        />
      }
    >
      <Route index element={<Expenses />} />
    </Route>
  );
};
