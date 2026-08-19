import { Suspense, useEffect, useState, type ReactElement } from "react";
import AppLayout from "@/components/layout/app-layout";
import { ErrorBoundary } from "@/components/ErrorBoundary";
import { Navigate, Route, Routes } from "react-router";
import { loadAuthenticatedLayoutRoutesModule } from "@/routes/authenticated-layout-routes.loader";
import { Toaster } from "sonner";
import { useAuthStore } from "@/zustand-stores/auth.store";
import getAuthRoutes from "./routes/auth.routes";

function AuthenticatedAppBootSpinner() {
  return (
    <div className="flex min-h-screen items-center justify-center">
      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
    </div>
  );
}

function App() {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const [authenticatedRouteTree, setAuthenticatedRouteTree] =
    useState<ReactElement | null>(null);

  useEffect(() => {
    if (!isAuthenticated) {
      setAuthenticatedRouteTree(null);
      return;
    }
    let cancelled = false;
    void loadAuthenticatedLayoutRoutesModule().then((m) => {
      if (!cancelled) {
        setAuthenticatedRouteTree(m.getAuthenticatedLayoutRouteElements());
      }
    });
    return () => {
      cancelled = true;
    };
  }, [isAuthenticated]);

  return (
    <ErrorBoundary>
      <Suspense fallback={<div className="flex min-h-screen items-center justify-center"><div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" /></div>}>
      <Routes>
        <Route
          path="/"
          element={
            isAuthenticated ? (
              <Navigate to="/dashboard" replace />
            ) : (
              <Navigate to="/login" replace />
            )
          }
        />

        <Route element={<AppLayout />}>
          {authenticatedRouteTree ?? (
            <Route path="*" element={<AuthenticatedAppBootSpinner />} />
          )}
        </Route>
        <Route
          path="*"
          element={
            <h1 className="text-4xl font-bold text-center">Not Found</h1>
          }
        />
        {getAuthRoutes()}
      </Routes>
      </Suspense>
      <Toaster className="no-print" />
    </ErrorBoundary>
  );
}

export default App;
