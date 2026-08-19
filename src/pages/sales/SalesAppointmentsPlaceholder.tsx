import { useNavigate } from "react-router";

export default function SalesAppointmentsPlaceholder() {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen space-y-6 bg-slate-50 p-6" dir="rtl">
      <div className="flex items-center gap-3">
        <button
          type="button"
          onClick={() => navigate("/orders/list?process_type=sold")}
          className="flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-50"
        >
          <i className="ri-arrow-right-line" />
        </button>
        <div>
          <h1 className="text-2xl font-bold text-slate-800">جدول المواعيد</h1>
          <p className="mt-0.5 text-sm text-slate-400">
            هذه الصفحة ستُربط لاحقاً بنظام المواعيد
          </p>
        </div>
      </div>
    </div>
  );
}
