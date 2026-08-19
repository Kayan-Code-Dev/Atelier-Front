import CreateRole from "@/pages/permissions-roles/admins/CreateRole";
import ListRoles from "@/pages/permissions-roles/admins/ListRoles";
import Permissions from "@/pages/permissions-roles/admins/Permissions";
import PermissionsAndRoles from "@/pages/permissions-roles/PermissionsAndRoles";
import Roles from "@/pages/permissions-roles/admins/Roles";
import { Route } from "react-router";
import BranchesManagersRoles from "@/pages/permissions-roles/branches-managers/BranchesManagersRoles";
import BranchesRoles from "@/pages/permissions-roles/branches/BranchesRoles";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const permissionsRoutes = () => {
  return (
    <Route
      path="permissions-roles"
      element={
        <PermissionProtectedRoute
          permission={[
            "roles.view",
            "roles.create",
            "roles.update",
            "roles.delete",
            "users.view",
          ]}
        />
      }
    >
      <Route element={<PermissionsAndRoles />}>
      <Route path="admins">
        <Route index path="permissions" element={<Permissions />} />
        <Route path="roles" element={<Roles />}>
          <Route path="list-roles" element={<ListRoles />} />
          <Route path="create" element={<CreateRole />} />
        </Route>
      </Route>
      <Route path="branches-managers">
        <Route index path="roles" element={<BranchesManagersRoles />} />
      </Route>
      <Route index path="branches/roles" element={<BranchesRoles />} />
      </Route>
    </Route>
  );
};
