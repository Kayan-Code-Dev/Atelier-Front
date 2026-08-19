import { useAuthStore } from "@/zustand-stores/auth.store";
import { Navigate } from "react-router";
import { useNotifications } from "@/hooks/useNotifications";
import ProjectLayout from "@/components/app/project-layout/ProjectLayout";

function AppLayout() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  useNotifications();

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return <ProjectLayout />;
}

export default AppLayout;
