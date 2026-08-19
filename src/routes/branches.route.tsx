import BranchManager from "@/pages/branches-manager/BranchManger";
import BranchReportsPage from "@/pages/branches-manager/BranchReportsPage";
import BranchDetailPage from "@/pages/branches-manager/BranchDetailPage";
import { Route } from "react-router";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

export const branchesRoutes = () => {
  return (
    <Route element={<PermissionProtectedRoute permission="branches.view" />}>
      <Route path="/branch" element={<BranchManager />} />
      <Route path="/branch-reports" element={<BranchReportsPage />} />
      <Route path="/branch-reports/:branchId" element={<BranchDetailPage />} />
    </Route>
  );
};
