import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { toast } from "sonner";
import ClothesTableContent from "./components/ClothesTableContent";
import { CreateClothModal } from "./CreateClothModal";
import { ImportClothesModal } from "./ImportClothesModal";
import { useExportClothesToCSVMutationOptions } from "@/api/v2/clothes/clothes.hooks";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";

function Clothes() {
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isImportModalOpen, setIsImportModalOpen] = useState(false);

  const { mutate: exportClothesToCSV, isPending: isExporting } = useMutation(
    useExportClothesToCSVMutationOptions()
  );

  const handleExport = () => {
    exportClothesToCSV(undefined, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "clothes.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير المنتجات بنجاح");
      },
      onError: (error: Error & { message?: string }) => {
        toast.error("خطأ أثناء تصدير المنتجات. الرجاء المحاولة مرة أخرى.", {
          description: error.message,
        });
      },
    });
  };

  return (
    <div dir="rtl" className="space-y-5 min-h-screen pb-8" data-onboarding-target="inventory_verified">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-black text-slate-800">إدارة المنتجات</h1>
          <p className="text-xs text-slate-400 mt-0.5">
            متابعة وإدارة جميع المنتجات عبر الفروع والمواقع
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={handleExport}
            disabled={isExporting}
            className="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors disabled:opacity-50"
            style={{
              background: "#F0FDF4",
              color: "#065F46",
              border: "1px solid #D1FAE5",
            }}
          >
            <i className="ri-file-excel-2-line" />
            {isExporting ? "جاري التصدير..." : "تصدير Excel"}
          </button>
          <button
            type="button"
            onClick={() =>
              toast.info("تصدير PDF غير مفعّل حالياً", {
                description: "سيُتاح لاحقاً من لوحة التحكم.",
              })
            }
            className="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors"
            style={{
              background: "#FEF2F2",
              color: "#991B1B",
              border: "1px solid #FECACA",
            }}
          >
            <i className="ri-file-pdf-line" /> تصدير PDF
          </button>
          <button
            type="button"
            onClick={() => setIsImportModalOpen(true)}
            className="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
          >
            <i className="ri-upload-2-line" /> استيراد
          </button>
          <button
            type="button"
            onClick={() => setIsCreateModalOpen(true)}
            className="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-black whitespace-nowrap transition-all"
            style={{
              background: "linear-gradient(135deg, #0C1A3E, #1E3A7B)",
              color: "white",
            }}
          >
            <i className="ri-add-line text-base" />
            منتج جديد
          </button>
        </div>
      </div>

      <ClothesTableContent />

      <CreateClothModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
      <ImportClothesModal
        open={isImportModalOpen}
        onOpenChange={setIsImportModalOpen}
      />
    </div>
  );
}

export default Clothes;
