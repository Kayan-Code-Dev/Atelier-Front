import type { TClothResponse } from "@/api/v2/clothes/clothes.types";
import {
  categoryTheme,
  clothPrice,
  locationVisual,
  statusVisual,
} from "./clothesViewUtils";
import { Pencil, Trash2 } from "lucide-react";

type Props = {
  clothes: TClothResponse[];
  getEntityDisplay: (c: TClothResponse) => string;
  getSubcategoryDisplay: (c: TClothResponse) => string;
  onOpenDetails: (id: number) => void;
  onEdit: (c: TClothResponse) => void;
  onDelete: (c: TClothResponse) => void;
  onTransfer: (c: TClothResponse) => void;
};

export function ClothesGridView({
  clothes,
  getEntityDisplay,
  getSubcategoryDisplay,
  onOpenDetails,
  onEdit,
  onDelete,
  onTransfer,
}: Props) {
  if (clothes.length === 0) {
    return (
      <div
        className="rounded-2xl flex flex-col items-center justify-center py-20 gap-4"
        style={{ background: "white", border: "1px solid #F1F5F9" }}
      >
        <div
          className="w-16 h-16 rounded-2xl flex items-center justify-center"
          style={{ background: "#F8FAFC" }}
        >
          <i className="ri-search-line text-3xl text-slate-300" />
        </div>
        <p className="text-sm font-semibold text-slate-400">لا توجد نتائج</p>
        <p className="text-xs text-slate-300">
          جرّب تغيير معايير البحث أو الفلاتر
        </p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
      {clothes.map((p) => {
        const sc = statusVisual(p.status);
        const catName = p.category_name?.trim() || "—";
        const cc = categoryTheme(catName);
        const lv = locationVisual(p.entity_type);
        const sub = getSubcategoryDisplay(p);
        const price = clothPrice(p);
        const hasSizes = p.breast_size || p.waist_size || p.sleeve_size;

        return (
          <div
            key={p.id}
            role="button"
            tabIndex={0}
            onClick={() => onOpenDetails(p.id)}
            onKeyDown={(e) => {
              if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                onOpenDetails(p.id);
              }
            }}
            className="rounded-2xl overflow-hidden transition-all cursor-pointer group"
            style={{ background: "white", border: "1px solid #F1F5F9" }}
          >
            <div
              className="px-4 pt-4 pb-3 flex items-center justify-between"
              style={{ background: cc.light }}
            >
              <div
                className="w-9 h-9 rounded-xl flex items-center justify-center"
                style={{ background: "white" }}
              >
                <i
                  className={`${cc.icon} text-base`}
                  style={{ color: cc.accent }}
                />
              </div>
              <div
                className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold max-w-[58%] truncate"
                style={{ background: sc.bg, color: sc.color }}
                title={sc.label}
              >
                <span
                  className="inline-block w-1.5 h-1.5 rounded-full shrink-0"
                  style={{ background: sc.dot }}
                />
                <span className="truncate">{sc.label}</span>
              </div>
            </div>

            <div className="px-4 py-3">
              <p className="font-black text-xs text-slate-400 font-mono tracking-wider mb-1">
                {p.code}
              </p>
              <p className="text-sm font-black text-slate-800 leading-snug truncate">
                {catName}
              </p>
              <p className="text-xs text-slate-400 mt-0.5 truncate" title={sub}>
                {sub || "—"}
              </p>

              {hasSizes ? (
                <div className="flex gap-1 flex-wrap mt-2.5">
                  {p.breast_size ? (
                    <span
                      className="text-[10px] px-1.5 py-0.5 rounded font-semibold"
                      style={{ background: "#EEF2FF", color: "#3730A3" }}
                    >
                      ص {p.breast_size}
                    </span>
                  ) : null}
                  {p.waist_size ? (
                    <span
                      className="text-[10px] px-1.5 py-0.5 rounded font-semibold"
                      style={{ background: "#F0FDF4", color: "#166534" }}
                    >
                      خ {p.waist_size}
                    </span>
                  ) : null}
                  {p.sleeve_size ? (
                    <span
                      className="text-[10px] px-1.5 py-0.5 rounded font-semibold"
                      style={{ background: "#FFF7ED", color: "#9A3412" }}
                    >
                      ك {p.sleeve_size}
                    </span>
                  ) : null}
                </div>
              ) : null}

              <div className="flex items-center gap-1.5 mt-2.5 min-w-0">
                <div
                  className="w-5 h-5 rounded flex items-center justify-center shrink-0"
                  style={{ background: lv.bg }}
                >
                  <i
                    className={`${lv.icon} text-[11px]`}
                    style={{ color: lv.color }}
                  />
                </div>
                <span className="text-[11px] text-slate-500 truncate font-medium">
                  {getEntityDisplay(p)}
                </span>
              </div>
            </div>

            <div
              className="px-4 py-3 flex items-center justify-between gap-2"
              style={{ borderTop: "1px solid #F8FAFC" }}
              onClick={(e) => e.stopPropagation()}
              onKeyDown={(e) => e.stopPropagation()}
            >
              <div>
                {price > 0 ? (
                  <>
                    <span className="text-sm font-black text-slate-800">
                      {price.toLocaleString("ar-EG")}
                    </span>
                    <span className="text-[10px] text-slate-400 mr-0.5">ج.م</span>
                  </>
                ) : (
                  <span className="text-xs text-slate-300">—</span>
                )}
              </div>
              <div
                className="inline-flex items-center gap-0.5 p-0.5 rounded-xl shrink-0"
                style={{
                  background: "#F8FAFC",
                  border: "1px solid #EEF2F8",
                  boxShadow: "0 1px 2px rgba(15,23,42,0.04)",
                }}
              >
                <button
                  type="button"
                  className="w-8 h-8 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.97]"
                  style={{ background: "#EEF2FF" }}
                  title="تعديل"
                  onClick={() => onEdit(p)}
                >
                  <Pencil className="h-3.5 w-3.5" style={{ color: "#3730A3" }} />
                </button>
                <button
                  type="button"
                  className="w-8 h-8 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.97]"
                  style={{ background: "#D1FAE5" }}
                  title="طلب نقل"
                  onClick={() => onTransfer(p)}
                >
                  <i
                    className="ri-arrow-left-right-line text-xs"
                    style={{ color: "#065F46" }}
                  />
                </button>
                <button
                  type="button"
                  className="w-8 h-8 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.97]"
                  style={{ background: "#FEE2E2" }}
                  title="حذف"
                  onClick={() => onDelete(p)}
                >
                  <Trash2 className="h-3.5 w-3.5 text-red-600" />
                </button>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
