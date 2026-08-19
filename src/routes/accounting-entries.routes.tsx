import { Route } from "react-router";
import PermissionProtectedRoute from "./PermissionProtectedRoute";
import EntriesPage from "@/pages/treasury/entries/page";

export const accountingEntriesRoutes = () => {
  return (
    <Route
      path="/treasury/entries"
      element={
        <PermissionProtectedRoute
          permission={["transactions.view"]}
        />
      }
    >
      <Route index element={<EntriesPage />} />
    </Route>
  );
};
