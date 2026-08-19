import { Card } from "@/components/ui/card";
import { Outlet } from "react-router";

function Roles() {
  return (
    <div className="w-full">
      <Card className="w-full rounded-lg shadow-ms my-4">
        <h1 className="text-3xl px-4">ادارة الصلاحيات</h1>
      </Card>
      <Outlet />
    </div>
  );
}

export default Roles;
