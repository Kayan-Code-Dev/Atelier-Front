import { Navigate, Outlet, Route, useParams } from "react-router";
import SalesReports from "@/pages/sales/SalesReports";
import SalesAppointmentsPlaceholder from "@/pages/sales/SalesAppointmentsPlaceholder";
import CreateSaleInvoicePage from "@/pages/sales/CreateSaleInvoicePage";

function SalesInvoiceToOrderRedirect() {
  const { id } = useParams();
  if (!id) return <Navigate to="/orders/list?process_type=sold" replace />;
  return <Navigate to={`/orders/${id}`} replace />;
}

export const salesRoutes = () => (
  <Route path="sales" element={<Outlet />}>
    <Route index element={<Navigate to="/orders/list?process_type=sold" replace />} />
    <Route path="appointments" element={<SalesAppointmentsPlaceholder />} />
    <Route path="create" element={<CreateSaleInvoicePage />} />
    <Route path="choose-client" element={<Navigate to="/sales/create" replace />} />
    <Route path="choose-products" element={<Navigate to="/sales/create" replace />} />
    <Route path="create-invoice" element={<Navigate to="/sales/create" replace />} />
    <Route path="invoices" element={<Navigate to="/orders/list?process_type=sold" replace />} />
    <Route path="invoices/:id" element={<SalesInvoiceToOrderRedirect />} />
    <Route path="invoices/:id/edit" element={<SalesInvoiceToOrderRedirect />} />
    <Route path="invoices/:id/payment" element={<SalesInvoiceToOrderRedirect />} />
    <Route path="reports" element={<SalesReports />} />
    <Route path="reports/daily" element={<SalesReports />} />
    <Route path="reports/products" element={<SalesReports />} />
    <Route path="reports/by-employee" element={<SalesReports />} />
  </Route>
);
