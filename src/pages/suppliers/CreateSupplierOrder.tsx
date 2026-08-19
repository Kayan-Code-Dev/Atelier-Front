import { Suspense } from "react";
import { CreateSupplierOrderForm } from "./CreateSupplierOrderForm";

export default function CreateSupplierOrder() {
  return (
    <Suspense fallback={<div>جاري التحميل...</div>}>
      <CreateSupplierOrderForm mode="page" />
    </Suspense>
  );
}
