import { Navigate, Outlet, Route } from "react-router";
import TailoringOrdersList from "@/pages/tailoring/TailoringOrdersList";
import TailoringOrderDetails from "@/pages/tailoring/TailoringOrderDetails";
import TailoringChooseClient from "@/pages/tailoring/TailoringChooseClient";
import TailoringDeliveries from "@/pages/tailoring/TailoringDeliveries";
import EditTailoringMeasurements from "@/pages/tailoring/EditTailoringMeasurements";

export const tailoringRoutes = () => (
  <Route path="tailoring" element={<Outlet />}>
    <Route index element={<Navigate to="/tailoring/orders" replace />} />
    <Route path="choose-client" element={<TailoringChooseClient />} />
    <Route path="create" element={<Navigate to="/tailoring/choose-client" replace />} />
    <Route path="piece-details" element={<Navigate to="/tailoring/choose-client" replace />} />
    <Route path="create-invoice" element={<Navigate to="/tailoring/choose-client" replace />} />
    <Route path="invoices" element={<Navigate to="/tailoring/orders" replace />} />
    <Route path="invoices/:id" element={<Navigate to="/tailoring/orders" replace />} />
    <Route path="invoices/:id/edit" element={<Navigate to="/tailoring/orders" replace />} />
    <Route path="invoices/:id/print" element={<Navigate to="/tailoring/orders" replace />} />
    <Route path="reports" element={<Navigate to="/tailoring/orders" replace />} />
    <Route path="orders" element={<TailoringOrdersList />} />
    <Route path="deliveries" element={<TailoringDeliveries />} />
    <Route path="orders/:id" element={<TailoringOrderDetails />} />
    <Route path="orders/:id/change-status" element={<TailoringOrderDetails />} />
    <Route path="orders/:id/edit-measurements" element={<EditTailoringMeasurements />} />
  </Route>
);
