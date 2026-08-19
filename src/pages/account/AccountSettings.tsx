import { useState, useRef, useEffect, useMemo } from "react";
import { useNavigate } from "react-router";
import { useQuery, useMutation } from "@tanstack/react-query";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";
import { useAuthStore } from "@/zustand-stores/auth.store";
import {
  useGetProfileQueryOptions,
  useUpdateProfileMutationOptions,
  useChangePasswordMutationOptions,
  useDeleteAccountMutationOptions,
} from "@/api/v2/account/account.hooks";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { getRoleLabel } from "@/lib/roleLabels";

type AccountSettingsProps = {
  /** عند true: يُستخدم داخل صفحة الإعدادات بدون عنوان الصفحة الخارجي */
  embedded?: boolean;
};

const inputClass =
  "w-full px-4 py-2.5 rounded-xl text-sm text-slate-700 outline-none transition-all duration-150";
const inputStyle = { background: "#F8FAFC", border: "1.5px solid #E2E8F0" } as const;

function setInputBorder(el: HTMLInputElement | HTMLTextAreaElement, color: string) {
  el.style.borderColor = color;
}

export default function AccountSettings({ embedded = false }: AccountSettingsProps) {
  const navigate = useNavigate();
  const { loginData, logout } = useAuthStore();
  const { data: profile, isLoading: profileLoading } = useQuery(
    useGetProfileQueryOptions()
  );

  const displayName = profile?.name ?? loginData?.user?.name ?? "";
  const displayEmail = profile?.email ?? loginData?.user?.email ?? "";

  const [name, setName] = useState(displayName);
  const [email, setEmail] = useState(displayEmail);

  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [showCurrent, setShowCurrent] = useState(false);
  const [showNew, setShowNew] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [pwError, setPwError] = useState("");
  const [pwSaved, setPwSaved] = useState(false);

  const [deletePassword, setDeletePassword] = useState("");
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const [avatarRemove, setAvatarRemove] = useState(false);
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [logoRemove, setLogoRemove] = useState(false);
  const [profileSaved, setProfileSaved] = useState(false);

  const avatarInputRef = useRef<HTMLInputElement>(null);
  const logoInputRef = useRef<HTMLInputElement>(null);

  const updateProfile = useMutation(useUpdateProfileMutationOptions());
  const changePassword = useMutation(useChangePasswordMutationOptions());
  const deleteAccount = useMutation(useDeleteAccountMutationOptions());

  useEffect(() => {
    if (displayName) setName(displayName);
    if (displayEmail) setEmail(displayEmail);
  }, [displayName, displayEmail]);

  const userInitials = (name || displayName || "U")
    .split(" ")
    .map((n) => n[0])
    .join("")
    .substring(0, 2)
    .toUpperCase();

  const avatarUrl = profile?.avatar_url ?? profile?.avatar ?? null;
  const avatarPreview = useMemo(
    () => (avatarFile ? URL.createObjectURL(avatarFile) : null),
    [avatarFile]
  );
  useEffect(() => {
    return () => {
      if (avatarPreview) URL.revokeObjectURL(avatarPreview);
    };
  }, [avatarPreview]);
  const defaultLogoUrl = "/dressnmore-logo.jpg";
  const displayAvatarUrl = avatarRemove
    ? null
    : (avatarPreview ?? avatarUrl ?? null);

  const logoUrl =
    (profile as { logo_url?: string | null; logo?: string | null })?.logo_url ??
    (profile as { logo_url?: string | null; logo?: string | null })?.logo ??
    null;
  const logoPreview = useMemo(
    () => (logoFile ? URL.createObjectURL(logoFile) : null),
    [logoFile]
  );
  useEffect(() => {
    return () => {
      if (logoPreview) URL.revokeObjectURL(logoPreview);
    };
  }, [logoPreview]);
  const displayLogoUrl = logoRemove
    ? null
    : (logoPreview ?? logoUrl ?? defaultLogoUrl);

  const rawRole = profile?.roles?.[0] ?? loginData?.roles?.[0];
  const roleLabel =
    rawRole != null && String(rawRole).trim() !== ""
      ? getRoleLabel(String(rawRole).trim())
      : "عضو النظام";

  const handleUpdateProfile = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile.mutate(
      {
        name,
        email,
        avatar: avatarFile ?? undefined,
        avatar_remove: avatarRemove || undefined,
        logo: logoFile ?? undefined,
        logo_remove: logoRemove || undefined,
      },
      {
        onSuccess: () => {
          toast.success("تم تحديث بيانات الحساب بنجاح");
          setAvatarFile(null);
          setAvatarRemove(false);
          setLogoFile(null);
          setLogoRemove(false);
          if (avatarInputRef.current) avatarInputRef.current.value = "";
          if (logoInputRef.current) logoInputRef.current.value = "";
          setProfileSaved(true);
          window.setTimeout(() => setProfileSaved(false), 2500);
        },
        onError: (error: Error) => toast.error(error.message),
      }
    );
  };

  const handleChangePassword = (e: React.FormEvent) => {
    e.preventDefault();
    setPwError("");
    if (!currentPassword || !newPassword || !confirmPassword) {
      setPwError("يرجى تعبئة جميع حقول كلمة المرور");
      return;
    }
    if (newPassword.length < 8) {
      setPwError("كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل");
      return;
    }
    if (newPassword !== confirmPassword) {
      setPwError("كلمة المرور الجديدة وتأكيدها غير متطابقتين");
      return;
    }
    changePassword.mutate(
      {
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      },
      {
        onSuccess: () => {
          toast.success("تم تغيير كلمة المرور بنجاح");
          setCurrentPassword("");
          setNewPassword("");
          setConfirmPassword("");
          setPwSaved(true);
          window.setTimeout(() => setPwSaved(false), 2500);
        },
        onError: (error: Error) => toast.error(error.message),
      }
    );
  };

  const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    setAvatarFile(file ?? null);
    if (file) setAvatarRemove(false);
  };

  const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    setLogoFile(file ?? null);
    if (file) setLogoRemove(false);
  };

  const handleDeleteAccount = () => {
    deleteAccount.mutate(deletePassword, {
      onSuccess: () => {
        logout();
        navigate("/login");
      },
    });
  };

  if (profileLoading) {
    return (
      <div dir="rtl" className="flex items-center justify-center min-h-[40vh]">
        <Loader2 className="h-8 w-8 animate-spin text-slate-400" />
      </div>
    );
  }

  return (
    <div
      dir="rtl"
      className={
        embedded ? "w-full space-y-6" : "min-h-screen w-full p-4 md:p-6 space-y-6"
      }
    >
      {!embedded ? (
        <div>
          <h1 className="text-2xl font-black text-slate-800">إعدادات الحساب</h1>
          <p className="text-slate-500 text-sm mt-1">
            إدارة معلومات حسابك وإعدادات الأمان
          </p>
        </div>
      ) : null}

      <div className="space-y-6">
        {/* شريط الهوية — نفس أسلوب المشروع */}
        <div
          className="rounded-2xl p-6"
          style={{ background: "linear-gradient(135deg, #0C1A3E 0%, #1E3A7B 100%)" }}
        >
          <div className="flex flex-wrap items-center gap-5">
            <div className="relative shrink-0">
              <input
                ref={avatarInputRef}
                type="file"
                accept="image/*"
                onChange={handleAvatarChange}
                className="hidden"
              />
              {displayAvatarUrl ? (
                <img
                  src={displayAvatarUrl}
                  alt=""
                  className="w-20 h-20 rounded-2xl object-cover border-2 border-white/20"
                />
              ) : (
                <div
                  className="w-20 h-20 rounded-2xl flex items-center justify-center font-black text-2xl text-white"
                  style={{
                    background: "linear-gradient(135deg, #C2964A, #E8BF7A)",
                  }}
                >
                  {userInitials}
                </div>
              )}
              <button
                type="button"
                onClick={() => avatarInputRef.current?.click()}
                className="absolute -bottom-1 -left-1 w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer transition-all duration-150"
                style={{ background: "#FFFFFF", border: "2px solid #0C1A3E" }}
                title="تغيير الصورة"
              >
                <i className="ri-camera-line text-sm" style={{ color: "#0C1A3E" }} />
              </button>
            </div>
            <div className="min-w-0 flex-1">
              <h3 className="text-white font-black text-lg truncate">{name || displayName || "—"}</h3>
              <p className="text-white/60 text-sm mt-0.5 truncate">{roleLabel}</p>
              <p className="text-white/40 text-xs mt-1 truncate">{email || displayEmail}</p>
            </div>
            <div className="mr-auto shrink-0 flex flex-col items-end gap-2">
              <span
                className="text-xs px-3 py-1.5 rounded-full font-bold inline-flex items-center gap-1"
                style={{ background: "rgba(194,150,74,0.2)", color: "#E8BF7A" }}
              >
                <i className="ri-shield-star-line" />
                {roleLabel}
              </span>
              {(avatarUrl || avatarFile) && !avatarRemove ? (
                <button
                  type="button"
                  onClick={() => {
                    setAvatarRemove(true);
                    setAvatarFile(null);
                    if (avatarInputRef.current) avatarInputRef.current.value = "";
                  }}
                  className="text-xs font-bold text-white/70 hover:text-white underline-offset-2 hover:underline"
                >
                  إزالة الصورة الشخصية
                </button>
              ) : null}
            </div>
          </div>
        </div>

        <form onSubmit={handleUpdateProfile}>
          <div className="rounded-2xl p-6 bg-white border border-slate-100">
            <h4 className="font-black text-slate-800 text-base mb-5 flex items-center gap-2">
              <span
                className="w-8 h-8 rounded-lg flex items-center justify-center"
                style={{ background: "#EFF6FF" }}
              >
                <i className="ri-user-3-line text-sm" style={{ color: "#3B82F6" }} />
              </span>
              البيانات الشخصية والشعار
            </h4>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-500 mb-1.5">
                  الاسم الكامل
                </label>
                <input
                  id="name"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="أدخل الاسم الكامل"
                  required
                  className={inputClass}
                  style={inputStyle}
                  onFocus={(e) => setInputBorder(e.target, "#C2964A")}
                  onBlur={(e) => setInputBorder(e.target, "#E2E8F0")}
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-500 mb-1.5">
                  البريد الإلكتروني
                </label>
                <input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="email@example.com"
                  required
                  className={inputClass}
                  style={inputStyle}
                  onFocus={(e) => setInputBorder(e.target, "#C2964A")}
                  onBlur={(e) => setInputBorder(e.target, "#E2E8F0")}
                />
              </div>

              <div className="sm:col-span-2 rounded-xl p-4 border border-slate-100 bg-slate-50/50">
                <label className="block text-xs font-bold text-slate-500 mb-3">
                  شعار الحساب (فواتير وتقارير)
                </label>
                <div className="flex flex-wrap items-center gap-4">
                  <div className="w-20 h-20 rounded-2xl overflow-hidden border-2 border-dashed border-slate-200 bg-white shrink-0">
                    <img
                      src={displayLogoUrl ?? defaultLogoUrl}
                      alt="شعار الحساب"
                      className="h-full w-full object-cover"
                    />
                  </div>
                  <div className="flex-1 min-w-[200px] space-y-2">
                    <p className="text-xs text-slate-400">
                      يُفضّل PNG بخلفية شفافة. يُحفظ مع الاسم والبريد عند الضغط على حفظ التغييرات.
                    </p>
                    <input
                      ref={logoInputRef}
                      type="file"
                      accept="image/*"
                      onChange={handleLogoChange}
                      className="hidden"
                    />
                    <div className="flex flex-wrap gap-2">
                      <button
                        type="button"
                        onClick={() => logoInputRef.current?.click()}
                        className="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                      >
                        <i className="ri-upload-2-line" />
                        اختيار شعار
                      </button>
                      {(logoUrl || logoFile) && !logoRemove && (
                        <button
                          type="button"
                          onClick={() => {
                            setLogoRemove(true);
                            setLogoFile(null);
                            if (logoInputRef.current) logoInputRef.current.value = "";
                          }}
                          className="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-red-50 text-red-600 border border-red-100"
                        >
                          <i className="ri-delete-bin-line" />
                          إزالة الشعار
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="flex items-center justify-between mt-5 pt-4 border-t border-slate-100 flex-wrap gap-3">
              {profileSaved ? (
                <span
                  className="text-sm font-bold flex items-center gap-1.5"
                  style={{ color: "#22C55E" }}
                >
                  <i className="ri-checkbox-circle-fill" />
                  تم الحفظ بنجاح
                </span>
              ) : (
                <span />
              )}
              <button
                type="submit"
                disabled={updateProfile.isPending}
                className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold cursor-pointer transition-all duration-150 whitespace-nowrap disabled:opacity-60"
                style={{ background: "#0C1A3E", color: "#FFFFFF" }}
                onMouseEnter={(e) => {
                  if (!updateProfile.isPending)
                    (e.currentTarget as HTMLElement).style.background = "#1E3A7B";
                }}
                onMouseLeave={(e) => {
                  (e.currentTarget as HTMLElement).style.background = "#0C1A3E";
                }}
              >
                {updateProfile.isPending ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  <i className="ri-save-line" />
                )}
                حفظ التغييرات
              </button>
            </div>
          </div>
        </form>

        <form onSubmit={handleChangePassword}>
          <div className="rounded-2xl p-6 bg-white border border-slate-100">
            <h4 className="font-black text-slate-800 text-base mb-5 flex items-center gap-2">
              <span
                className="w-8 h-8 rounded-lg flex items-center justify-center"
                style={{ background: "#FEF3C7" }}
              >
                <i className="ri-lock-password-line text-sm" style={{ color: "#D97706" }} />
              </span>
              تغيير كلمة المرور
            </h4>
            <div className="grid grid-cols-1 gap-4 max-w-lg">
              {(
                [
                  {
                    key: "current" as const,
                    label: "كلمة المرور الحالية",
                    show: showCurrent,
                    toggle: () => setShowCurrent((p) => !p),
                    value: currentPassword,
                    set: setCurrentPassword,
                  },
                  {
                    key: "newPw" as const,
                    label: "كلمة المرور الجديدة",
                    show: showNew,
                    toggle: () => setShowNew((p) => !p),
                    value: newPassword,
                    set: setNewPassword,
                  },
                  {
                    key: "confirm" as const,
                    label: "تأكيد كلمة المرور الجديدة",
                    show: showConfirm,
                    toggle: () => setShowConfirm((p) => !p),
                    value: confirmPassword,
                    set: setConfirmPassword,
                  },
                ] as const
              ).map(({ key, label, show, toggle, value, set }) => (
                <div key={key}>
                  <label className="block text-xs font-bold text-slate-500 mb-1.5">{label}</label>
                  <div className="relative">
                    <input
                      type={show ? "text" : "password"}
                      value={value}
                      onChange={(e) => set(e.target.value)}
                      placeholder="••••••••"
                      className={`${inputClass} pe-10`}
                      style={inputStyle}
                      onFocus={(e) => setInputBorder(e.target, "#C2964A")}
                      onBlur={(e) => setInputBorder(e.target, "#E2E8F0")}
                    />
                    <button
                      type="button"
                      onClick={toggle}
                      className="absolute end-3 top-1/2 -translate-y-1/2 cursor-pointer"
                      style={{ color: "#94A3B8" }}
                    >
                      <i className={`${show ? "ri-eye-off-line" : "ri-eye-line"} text-sm`} />
                    </button>
                  </div>
                </div>
              ))}
            </div>
            {pwError ? (
              <p
                className="text-xs font-semibold mt-3 flex items-center gap-1"
                style={{ color: "#EF4444" }}
              >
                <i className="ri-error-warning-line" />
                {pwError}
              </p>
            ) : null}
            <div className="flex items-center justify-between mt-5 pt-4 border-t border-slate-100 flex-wrap gap-3">
              {pwSaved ? (
                <span
                  className="text-sm font-bold flex items-center gap-1.5"
                  style={{ color: "#22C55E" }}
                >
                  <i className="ri-checkbox-circle-fill" />
                  تم تغيير كلمة المرور بنجاح
                </span>
              ) : (
                <p className="text-xs text-slate-400">يجب أن تكون كلمة المرور 8 أحرف على الأقل</p>
              )}
              <button
                type="submit"
                disabled={changePassword.isPending}
                className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold cursor-pointer transition-all duration-150 whitespace-nowrap disabled:opacity-60"
                style={{ background: "#D97706", color: "#FFFFFF" }}
                onMouseEnter={(e) => {
                  if (!changePassword.isPending)
                    (e.currentTarget as HTMLElement).style.background = "#B45309";
                }}
                onMouseLeave={(e) => {
                  (e.currentTarget as HTMLElement).style.background = "#D97706";
                }}
              >
                {changePassword.isPending ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  <i className="ri-lock-password-line" />
                )}
                تغيير كلمة المرور
              </button>
            </div>
          </div>
        </form>

        <div className="rounded-2xl p-6 bg-white border border-red-100">
          <h4 className="font-black text-red-700 text-base mb-2 flex items-center gap-2">
            <span
              className="w-8 h-8 rounded-lg flex items-center justify-center bg-red-50"
            >
              <i className="ri-delete-bin-7-line text-sm text-red-600" />
            </span>
            منطقة الخطر
          </h4>
          <p className="text-xs text-slate-500 mb-4">
            حذف الحساب نهائياً. لا يمكن التراجع عن هذا الإجراء.
          </p>
          <AlertDialog>
            <AlertDialogTrigger asChild>
              <button
                type="button"
                className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold bg-red-600 text-white hover:bg-red-700 transition-colors"
              >
                <i className="ri-delete-bin-line" />
                حذف الحساب نهائياً
              </button>
            </AlertDialogTrigger>
            <AlertDialogContent dir="rtl">
              <AlertDialogHeader>
                <AlertDialogTitle>هل أنت متأكد؟</AlertDialogTitle>
                <AlertDialogDescription>
                  سيتم حذف حسابك وجميع بياناتك نهائياً. أدخل كلمة المرور للتأكيد.
                </AlertDialogDescription>
              </AlertDialogHeader>
              <div className="py-2">
                <Label htmlFor="delete-password">كلمة المرور</Label>
                <Input
                  id="delete-password"
                  type="password"
                  value={deletePassword}
                  onChange={(e) => setDeletePassword(e.target.value)}
                  placeholder="أدخل كلمة المرور للتأكيد"
                  className="mt-2"
                />
              </div>
              <AlertDialogFooter className="flex-row-reverse gap-2">
                <AlertDialogCancel>إلغاء</AlertDialogCancel>
                <AlertDialogAction
                  onClick={handleDeleteAccount}
                  disabled={!deletePassword || deleteAccount.isPending}
                  className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                >
                  {deleteAccount.isPending ? (
                    <Loader2 className="ml-2 h-4 w-4 animate-spin inline" />
                  ) : null}
                  حذف الحساب
                </AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </div>
      </div>
    </div>
  );
}
