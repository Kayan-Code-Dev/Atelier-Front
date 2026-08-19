import { useEffect } from "react";
import { useNavigate } from "react-router";
import { Loader2 } from "lucide-react";
import { useMyPermissions } from "@/api/auth/auth.hooks";

/** ترتيب التوجيه الافتراضي لـ `/content` — يتبع تسلسل الإعدادات */
const ORDER: { segment: string; permissions: string[] }[] = [
  { segment: "profile", permissions: [] },
  { segment: "branches", permissions: ["branches.view"] },
  { segment: "currencies", permissions: ["currencies.view"] },
  {
    segment: "product-taxonomy",
    permissions: ["categories.view", "subcategories.view"],
  },
  { segment: "invoice-rules", permissions: [] },
  { segment: "quotas", permissions: [] },
  { segment: "subscription", permissions: [] },
];

function canAccess(entry: (typeof ORDER)[0], perms: string[]): boolean {
  if (!entry.permissions.length) return true;
  return entry.permissions.some((p) => perms.includes(p));
}

export function ContentSettingsIndexRedirect() {
  const navigate = useNavigate();
  const { data, isSuccess } = useMyPermissions();

  useEffect(() => {
    if (!isSuccess || !data) return;
    const next = ORDER.find((o) => canAccess(o, data));
    if (next) navigate(`/content/${next.segment}`, { replace: true });
    else navigate("/dashboard", { replace: true });
  }, [data, isSuccess, navigate]);

  return (
    <div
      className="flex min-h-[40vh] items-center justify-center gap-2 text-slate-400"
      dir="rtl"
    >
      <Loader2 className="h-8 w-8 animate-spin" />
      <span className="text-sm">جاري التوجيه...</span>
    </div>
  );
}
