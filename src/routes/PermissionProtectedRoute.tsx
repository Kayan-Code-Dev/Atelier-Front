import { useMyPermissions } from "@/api/auth/auth.hooks";
import { Loader2 } from "lucide-react";
import { useEffect } from "react";
import { Outlet, useNavigate } from "react-router";

type TProps = {
  permission: string | string[];
};

function PermissionProtectedRoute({ permission }: TProps) {
  const { data, isSuccess, isPending } = useMyPermissions();
  const permissions = Array.isArray(permission) ? permission : [permission];
  const hasPermission =
    permissions.length === 0 ||
    permissions.some((p) => p === "" || (data && data.includes(p)));
  const navigate = useNavigate();
  useEffect(() => {
    if (isSuccess && !hasPermission) {
      navigate("/dashboard");
    }
  }, [hasPermission, isSuccess]);

  if (isPending) {
    return (
      <div className="flex justify-center">
        {" "}
        <Loader2 className="animate-spin" /> Loading...
      </div>
    );
  }
  return <Outlet />;
}

export default PermissionProtectedRoute;
