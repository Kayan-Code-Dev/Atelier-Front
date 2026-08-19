import { Card } from "@/components/ui/card";
import { Outlet } from "react-router";

function Permissions() {
  return (
    <div className="w-full">
      <Card className="w-full rounded-lg shadow-ms my-2">
        <h1 className=" px-4"> ادارة الصلاحيات و الاذونات</h1>
      </Card>
      <Outlet />
    </div>
  );
}

export default Permissions;
