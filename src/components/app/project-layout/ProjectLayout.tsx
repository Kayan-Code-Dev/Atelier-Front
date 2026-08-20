import { useState, useEffect } from "react";
import { Outlet, useLocation } from "react-router";
import ProjectSidebar from "./ProjectSidebar";
import ProjectTopBar from "./ProjectTopBar";

export default function ProjectLayout() {
  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  const location = useLocation();

  useEffect(() => {
    const check = () => {
      const w = window.innerWidth;
      const mobile = w < 1024;
      const tablet = w >= 768 && w < 1280;
      setIsMobile(mobile);
      if (!mobile) setMobileOpen(false);
      if (tablet && !mobile) setCollapsed(true);
      if (w >= 1280) setCollapsed(false);
    };
    check();
    window.addEventListener("resize", check);
    return () => window.removeEventListener("resize", check);
  }, []);

  const effectiveCollapsed = isMobile ? false : collapsed;
  const sidebarWidth = isMobile ? 0 : effectiveCollapsed ? 70 : 260;

  return (
    <div
      className="min-h-screen app-shell-bg"
      style={{
        direction: "rtl",
      }}
    >
      <ProjectSidebar
        collapsed={effectiveCollapsed}
        onToggle={() => setCollapsed((c) => !c)}
        mobileOpen={mobileOpen}
        onMobileClose={() => setMobileOpen(false)}
      />

      <ProjectTopBar
        sidebarWidth={sidebarWidth}
        onMobileMenuToggle={() => setMobileOpen((o) => !o)}
      />

      <main
        className="app-print-main transition-all duration-300 ease-out"
        style={{
          marginRight: `${sidebarWidth}px`,
          paddingTop: "var(--topbar-height)",
          minHeight: "100vh",
        }}
      >
        <div
          className="app-print-main-inner min-h-full"
          style={{ padding: "var(--page-padding)" }}
        >
          <style>{`
            @media (max-width: 1023px) { main > div { padding: var(--page-padding-md) !important; } }
            @media (max-width: 640px)  { main > div { padding: var(--page-padding-sm) !important; } }
          `}</style>
          <div key={location.pathname} className="page-enter">
            <Outlet />
          </div>
        </div>
      </main>
    </div>
  );
}
