import { Route } from "react-router";
import DeliveriesList from "@/pages/deliveries/DeliveriesList";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const deliveriesRoutes = () => {
  return (
    <Route
      path="/deliveries"
      element={
        <PermissionProtectedRoute permission={["orders.deliver"]} />
      }
    >
      <Route index element={<DeliveriesList />} />
    </Route>
  );
};





