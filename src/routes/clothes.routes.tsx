import Clothes from "@/pages/clothes/Clothes";
import ClothDetails from "@/pages/clothes/ClothDetails";
import ProductTransferPage from "@/pages/clothes/transfer/ProductTransferPage";
import { Navigate, Route } from "react-router";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const clothesRoutes = () => {
  return (
    <Route
      path="/clothes"
      element={
        <PermissionProtectedRoute
          permission={[
            "clothes.view",
            "clothes.export",
            "transfers.view",
            "transfers.create",
            "transfers.update",
            "transfers.approve",
            "transfers.reject",
          ]}
        />
      }
    >
      <Route path="list" element={<Clothes />} />
      <Route path="details/:id" element={<ClothDetails />} />
      <Route path="transfer" element={<ProductTransferPage />} />
      <Route
        path="transfer-clothes/actions"
        element={<Navigate to="/clothes/transfer" replace />}
      />
      <Route
        path="transfer-clothes/requests"
        element={<Navigate to="/clothes/transfer" replace />}
      />
    </Route>
  );
};
