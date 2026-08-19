import { NavLink, useLocation, useNavigate } from "react-router";
import { useState, useEffect, useLayoutEffect, useRef } from "react";
import { sidebarLabels } from "@/components/app/sidebar/constants";
import useSidebarLabel, { useSidebarPermissions } from "@/components/app/sidebar/useSidebarLabel";
import { useQuery } from "@tanstack/react-query";
import { useGetProfileQueryOptions } from "@/api/v2/account/account.hooks";
import { useAuthStore } from "@/zustand-stores/auth.store";
import type { SidebarLabel } from "@/components/app/sidebar/constants";
import { matchesLocationSearch } from "@/lib/matchLocationSearch";

function matchesSidebarMatch(
  loc: { pathname: string; search: string },
  m: { pathname: string; search?: Record<string, string> }
): boolean {
  return matchesLocationSearch(loc.pathname, loc.search, m);
}

function navEntryIsActive(
  config: Pick<SidebarLabel, "path" | "activeMatch" | "activeExclude">,
  loc: { pathname: string; search: string }
): boolean {
  if (config.activeExclude && matchesSidebarMatch(loc, config.activeExclude)) {
    return false;
  }
  if (config.activeMatch) {
    return matchesSidebarMatch(loc, config.activeMatch);
  }
  if (config.path === "/dashboard") return loc.pathname === config.path;
  return loc.pathname === config.path || loc.pathname.startsWith(config.path + "/");
}

interface ProjectSidebarProps {
  collapsed: boolean;
  onToggle: () => void;
  mobileOpen?: boolean;
  onMobileClose?: () => void;
}

/** Flatten nested subItems for project-style single-level submenu */
function flattenSubItems(item: SidebarLabel): Pick<
  SidebarLabel,
  "path" | "label" | "riIcon" | "activeMatch" | "activeExclude"
>[] {
  if (!item.subItems?.length) return [];
  return item.subItems.flatMap((sub) => {
    if (sub.subItems?.length) {
      return sub.subItems!.map((s) => ({
        path: s.path,
        label: s.label,
        riIcon: s.riIcon,
        activeMatch: s.activeMatch,
        activeExclude: s.activeExclude,
      }));
    }
    return [
      {
        path: sub.path,
        label: sub.label,
        riIcon: sub.riIcon,
        activeMatch: sub.activeMatch,
        activeExclude: sub.activeExclude,
      },
    ];
  });
}

const SIDEBAR_SCROLL_KEY = "dressnmore.project-sidebar.scrollTop";

export default function ProjectSidebar({
  collapsed,
  onToggle,
  mobileOpen = false,
  onMobileClose,
}: ProjectSidebarProps) {
  const location = useLocation();
  const navigate = useNavigate();
  const navRef = useRef<HTMLElement>(null);
  const permissions = useSidebarPermissions();
  const navItems = useSidebarLabel(sidebarLabels, permissions);
  const [openGroups, setOpenGroups] = useState<string[]>([]);
  const { data: profile } = useQuery(useGetProfileQueryOptions());
  const loginData = useAuthStore((s) => s.loginData);
  const displayName = profile?.name ?? loginData?.user?.name ?? "المستخدم";
  const userInitials = (displayName || "م")
    .split(" ")
    .map((n) => n[0])
    .join("")
    .substring(0, 2);

  useEffect(() => {
    navItems.forEach((item) => {
      const flat = flattenSubItems(item);
      if (flat.some((sub) => navEntryIsActive(sub, location))) {
        setOpenGroups((prev) => (prev.includes(item.label) ? prev : [...prev, item.label]));
      }
    });
  }, [location.pathname, location.search, navItems]);

  useLayoutEffect(() => {
    const el = navRef.current;
    if (!el) return;
    const saved = Number(sessionStorage.getItem(SIDEBAR_SCROLL_KEY) || "0");
    if (Number.isFinite(saved) && saved > 0) {
      el.scrollTop = saved;
    }
  }, [location.pathname, location.search, collapsed]);

  useEffect(() => {
    const el = navRef.current;
    if (!el) return;
    const onScroll = () => {
      sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(el.scrollTop));
    };
    el.addEventListener("scroll", onScroll, { passive: true });
    return () => el.removeEventListener("scroll", onScroll);
  }, []);

  const toggleGroup = (label: string) => {
    if (collapsed) return;
    setOpenGroups((prev) =>
      prev.includes(label) ? prev.filter((l) => l !== label) : [...prev, label]
    );
  };

  const isGroupActive = (item: SidebarLabel) => {
    const flat = flattenSubItems(item);
    return flat.some((sub) => navEntryIsActive(sub, location));
  };

  const renderedSections = new Set<string>();
  const sidebarBg = "linear-gradient(160deg, #0369A1 0%, #0284C7 35%, #0EA5E9 100%)";
  const activeItemStyle = {
    color: "#ffffff",
    background: "rgba(255,255,255,0.22)",
    fontWeight: "700" as const,
  };
  const inactiveItemStyle = {
    color: "rgba(255,255,255,0.72)",
    background: "transparent",
    fontWeight: "500" as const,
  };
  const hoverStyle = { background: "rgba(255,255,255,0.12)", color: "rgba(255,255,255,0.95)" };

  return (
    <>
      {mobileOpen && (
        <div
          className="sidebar-backdrop no-print lg:hidden"
          onClick={onMobileClose}
        />
      )}

      <aside
        className={[
          "no-print flex flex-col h-screen max-h-screen overflow-hidden fixed right-0 top-0 z-40",
          "transition-all duration-300 ease-out",
          "lg:translate-x-0",
          mobileOpen ? "sidebar-mobile-visible translate-x-0" : "sidebar-mobile-hidden lg:translate-x-0",
        ].join(" ")}
        style={{
          width: collapsed ? "70px" : "260px",
          background: sidebarBg,
          borderLeft: "1px solid rgba(255,255,255,0.05)",
        }}
      >
        {/* ── Logo & Toggle ── */}
        <div
          className={`flex items-center flex-shrink-0 ${collapsed ? "justify-center px-2" : "gap-3 px-4"}`}
          style={{
            minHeight: "var(--topbar-height)",
            borderBottom: "1px solid rgba(255,255,255,0.06)",
          }}
        >
          {!collapsed && (
            <div
              className="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center"
              style={{
                background: "linear-gradient(135deg, #B8862A 0%, #E8BF7A 50%, #B8862A 100%)",
                boxShadow: "0 2px 10px rgba(194,150,74,0.45)",
              }}
            >
              <i className="ri-scissors-cut-fill text-white text-sm" />
            </div>
          )}

          {!collapsed && (
            <div className="flex-1 min-w-0 fade-in overflow-hidden">
              <p className="text-white font-black text-[13px] leading-tight truncate">
                Atelier
              </p>
              <p
                className="text-[10px] truncate mt-0.5 font-semibold"
                style={{ color: "#C2964A", letterSpacing: "0.04em" }}
              >
                نظام إدارة الأتيليه
              </p>
            </div>
          )}

          <button
            onClick={onToggle}
            className="flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer transition-all duration-150"
            style={{
              color: "rgba(255,255,255,0.50)",
              background: "rgba(255,255,255,0.07)",
              border: "1px solid rgba(255,255,255,0.09)",
            }}
            onMouseEnter={(e) => {
              (e.currentTarget as HTMLElement).style.background = "rgba(255,255,255,0.14)";
              (e.currentTarget as HTMLElement).style.color = "rgba(255,255,255,0.90)";
            }}
            onMouseLeave={(e) => {
              (e.currentTarget as HTMLElement).style.background = "rgba(255,255,255,0.07)";
              (e.currentTarget as HTMLElement).style.color = "rgba(255,255,255,0.50)";
            }}
          >
            <i className={`ri-${collapsed ? "menu-unfold" : "menu-fold"}-line text-sm`} />
          </button>
        </div>

        {/* ── Navigation ── */}
        <nav
          ref={navRef}
          className="flex-1 min-h-0 overflow-y-auto overflow-x-hidden py-2 px-2"
          style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
        >
          <style>{`nav::-webkit-scrollbar { display: none; }`}</style>
          <ul className="space-y-px">
            {navItems.map((item) => {
              const flatSubs = flattenSubItems(item);
              const hasSubItems = flatSubs.length > 0;
              const groupActive = isGroupActive(item);
              const isOpen = openGroups.includes(item.label) || groupActive;
              const showSection = item.section && !renderedSections.has(item.section);
              if (item.section) renderedSections.add(item.section);

              return (
                <li key={item.label || item.path}>
                  {showSection && !collapsed && (
                    <div className="sidebar-section-label">{item.section}</div>
                  )}
                  {showSection && collapsed && (
                    <div className="my-2 mx-auto w-7 h-px" style={{ background: "rgba(255,255,255,0.08)" }} />
                  )}

                  {hasSubItems ? (
                    <>
                      <div className="relative sidebar-nav-item">
                        <button
                          onClick={() => toggleGroup(item.label)}
                          className="flex items-center gap-2.5 w-full px-2.5 py-2.5 rounded-xl cursor-pointer transition-all duration-150 text-right relative"
                          style={groupActive ? activeItemStyle : inactiveItemStyle}
                          onMouseEnter={(e) => {
                            if (!groupActive) Object.assign((e.currentTarget as HTMLElement).style, hoverStyle);
                          }}
                          onMouseLeave={(e) => {
                            if (!groupActive) Object.assign((e.currentTarget as HTMLElement).style, inactiveItemStyle);
                          }}
                        >
                          {groupActive && <span className="sidebar-active-bar" />}
                          <span className="w-[20px] h-[20px] flex items-center justify-center flex-shrink-0">
                            {item.riIcon ? (
                              <i className={`${item.riIcon} text-[16px]`} />
                            ) : (
                              <span className="[&>svg]:w-4 [&>svg]:h-4">{item.iconComponent}</span>
                            )}
                          </span>
                          {!collapsed && (
                            <>
                              <span className="text-[12.5px] flex-1 text-right leading-tight font-semibold">
                                {item.label}
                              </span>
                              {item.badge != null && (
                                <span className="text-[9px] px-1.5 py-0.5 rounded-full font-black bg-red-500 text-white flex-shrink-0">
                                  {item.badge}
                                </span>
                              )}
                              <i
                                className="ri-arrow-down-s-line text-xs flex-shrink-0 transition-transform duration-200"
                                style={{
                                  transform: isOpen ? "rotate(180deg)" : "rotate(0deg)",
                                  color: "rgba(255,255,255,0.22)",
                                }}
                              />
                            </>
                          )}
                        </button>
                        {collapsed && <span className="sidebar-tooltip">{item.label}</span>}
                      </div>

                      {!collapsed && isOpen && (
                        <ul
                          className="mt-0.5 mb-1 mr-[30px] space-y-px fade-in"
                          style={{ borderRight: "1.5px solid rgba(255,255,255,0.07)", paddingRight: "10px" }}
                        >
                          {flatSubs.map((sub) => {
                            const subActive = navEntryIsActive(sub, location);
                            return (
                              <li key={sub.path}>
                                <NavLink
                                  to={sub.path}
                                  preventScrollReset
                                  onClick={onMobileClose}
                                  className="flex items-center gap-2 px-2.5 py-2 rounded-lg cursor-pointer transition-all duration-150 whitespace-nowrap"
                                  style={{
                                    color: subActive ? "#ffffff" : "rgba(255,255,255,0.55)",
                                    background: subActive ? "rgba(255,255,255,0.18)" : "transparent",
                                    fontWeight: subActive ? "700" : "500",
                                  }}
                                  onMouseEnter={(e) => {
                                    if (!subActive) {
                                      (e.currentTarget as HTMLElement).style.background = "rgba(255,255,255,0.10)";
                                      (e.currentTarget as HTMLElement).style.color = "rgba(255,255,255,0.90)";
                                    }
                                  }}
                                  onMouseLeave={(e) => {
                                    if (!subActive) {
                                      (e.currentTarget as HTMLElement).style.background = "transparent";
                                      (e.currentTarget as HTMLElement).style.color = "rgba(255,255,255,0.55)";
                                    }
                                  }}
                                >
                                  {sub.riIcon && (
                                    <span className="w-[16px] h-[16px] flex items-center justify-center flex-shrink-0">
                                      <i className={`${sub.riIcon} text-[13px]`} />
                                    </span>
                                  )}
                                  <span className="text-[12px]">{sub.label}</span>
                                </NavLink>
                              </li>
                            );
                          })}
                        </ul>
                      )}
                    </>
                  ) : (
                    <div className="relative sidebar-nav-item">
                      {item.path ? (
                        <NavLink
                          to={item.path}
                          preventScrollReset
                          onClick={onMobileClose}
                          className="flex items-center gap-2.5 px-2.5 py-2.5 rounded-xl cursor-pointer transition-all duration-150 whitespace-nowrap relative"
                          style={navEntryIsActive(item, location) ? activeItemStyle : inactiveItemStyle}
                          onMouseEnter={(e) => {
                            if (!navEntryIsActive(item, location)) Object.assign((e.currentTarget as HTMLElement).style, hoverStyle);
                          }}
                          onMouseLeave={(e) => {
                            if (!navEntryIsActive(item, location)) Object.assign((e.currentTarget as HTMLElement).style, inactiveItemStyle);
                          }}
                        >
                          {navEntryIsActive(item, location) && <span className="sidebar-active-bar" />}
                          <span className="w-[20px] h-[20px] flex items-center justify-center flex-shrink-0">
                            {item.riIcon ? (
                              <i className={`${item.riIcon} text-[16px]`} />
                            ) : (
                              <span className="[&>svg]:w-4 [&>svg]:h-4">{item.iconComponent}</span>
                            )}
                          </span>
                          {!collapsed && (
                            <>
                              <span className="text-[12.5px] font-semibold">{item.label}</span>
                              {item.badge != null && (
                                <span className="text-[9px] px-1.5 py-0.5 rounded-full font-black bg-red-500 text-white flex-shrink-0">
                                  {item.badge}
                                </span>
                              )}
                            </>
                          )}
                        </NavLink>
                      ) : null}
                      {collapsed && <span className="sidebar-tooltip">{item.label}</span>}
                    </div>
                  )}
                </li>
              );
            })}
          </ul>
        </nav>

        {/* ── User footer ── */}
        <div
          className="flex-shrink-0 px-2.5 py-3 cursor-pointer"
          style={{ borderTop: "1px solid rgba(255,255,255,0.06)" }}
          onClick={() => navigate("/account", { preventScrollReset: true })}
        >
          <div
            className="flex items-center gap-2.5 px-2.5 py-2 rounded-xl transition-all duration-150"
            onMouseEnter={(e) => {
              (e.currentTarget as HTMLElement).style.background = "rgba(255,255,255,0.06)";
            }}
            onMouseLeave={(e) => {
              (e.currentTarget as HTMLElement).style.background = "transparent";
            }}
          >
            <div
              className="w-8 h-8 rounded-xl flex-shrink-0 flex items-center justify-center font-black text-sm"
              style={{
                background: "linear-gradient(135deg, #B8862A, #E8BF7A)",
                color: "white",
                boxShadow: "0 1px 5px rgba(194,150,74,0.40)",
              }}
            >
              {userInitials}
            </div>
            {!collapsed && (
              <div className="flex-1 min-w-0 fade-in">
                <p className="text-white text-[12px] font-bold truncate">{displayName}</p>
                <p className="text-[11px] truncate mt-0.5" style={{ color: "rgba(255,255,255,0.35)" }}>
                  {loginData?.user?.email ?? "الحساب"}
                </p>
              </div>
            )}
          </div>
        </div>
      </aside>
    </>
  );
}
