import ChooseClient from "@/pages/orders/add-new-order/ChooseClient";
import ChooseClothes from "@/pages/orders/add-new-order/ChooseClothes";
import CreateOrderForm from "@/pages/orders/add-new-order/CreateOrderForm";
import OrderDetails from "@/pages/orders/OrderDetails";
import OrderItemDetails from "@/pages/orders/OrderItemDetails";
import { OrdersList } from "@/pages/orders/OrdersList";
import DeliveriesReturnsSearch from "@/pages/orders/DeliveriesReturnsSearch";
import UpdateClothesInOrder from "@/pages/orders/update-order/UpdateClothesInOrder";
import UpdateOrder from "@/pages/orders/update-order/UpdateOrder";
import RentalCreateInvoicePage from "@/pages/orders/rental/create/RentalCreateInvoicePage";
import { Navigate, Route } from "react-router";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const ordersRoutes = () => {
  return (
    <Route path="orders" element={<PermissionProtectedRoute permission={["orders.view", "orders.create", "orders.update", "orders.export"]} />}>
      <Route index element={<Navigate to="/orders/list" replace />} />
      <Route path="list" element={<OrdersList />} />
      <Route path="search-deliveries-returns" element={<DeliveriesReturnsSearch />} />
      <Route path=":orderId/items/:itemId" element={<OrderItemDetails />} />
      <Route path=":id" element={<OrderDetails />} />
      <Route path="choose-client" element={<ChooseClient />} />
      <Route path="rental/create" element={<RentalCreateInvoicePage />} />
      <Route path="choose-clothes" element={<ChooseClothes />} />
      <Route path="create-order" element={<CreateOrderForm />} />
      <Route path="update-order" element={<UpdateOrder />} />
      <Route path="update-clothes-in-order" element={<UpdateClothesInOrder />} />
    </Route>
  );
};
