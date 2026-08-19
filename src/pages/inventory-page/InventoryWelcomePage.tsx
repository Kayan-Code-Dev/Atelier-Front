import { Card } from "@/components/ui/card";
import { Outlet } from "react-router";

function InventoryWelcomePage() {
  return (
    <div>
      <Card className="px-4 my-6">
        <h1 className="text-2xl">مرحبا بك في صفحة المخزون</h1>
      </Card>
      <Outlet />
    </div>
  );
}

export default InventoryWelcomePage;
