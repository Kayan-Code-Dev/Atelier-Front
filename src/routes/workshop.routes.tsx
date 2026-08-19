import Workshop from "@/pages/workshop/Workshop";
import WorkshopDetails from "@/pages/workshop/workshop-management/WorkshopDetails";
import WorkshopTransfers from "@/pages/workshop/workshop-management/WorkshopTransfers";
import { Route } from "react-router";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const workshopRoutes = () => {
  return (
    <Route
      path="/workshop"
      element={
        <PermissionProtectedRoute
          permission={[
            "workshops.view",
            "workshops.create",
            "workshops.update",
            "workshops.delete",
            "workshops.manage-clothes",
            "workshops.approve-transfers",
          ]}
        />
      }
    >
      <Route index element={<Workshop />} />
      <Route path=":id" element={<WorkshopDetails />} />
      <Route path=":id/transfers" element={<WorkshopTransfers />} />
    </Route>
  );
};
