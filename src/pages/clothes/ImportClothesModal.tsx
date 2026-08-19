import { useState, useRef } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { useImportClothesMutationOptions } from "@/api/v2/clothes/clothes.hooks";
import { useMutation } from "@tanstack/react-query";
import { toast } from "sonner";
import { Upload, FileSpreadsheet } from "lucide-react";

const ACCEPTED_TYPES = ".csv,.xlsx,.xls";
const MAX_SIZE_MB = 50;

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function ImportClothesModal({ open, onOpenChange }: Props) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [file, setFile] = useState<File | null>(null);

  const { mutate: importClothes, isPending } = useMutation(
    useImportClothesMutationOptions()
  );

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selected = e.target.files?.[0];
    if (!selected) {
      setFile(null);
      return;
    }
    const ext = selected.name.split(".").pop()?.toLowerCase();
    if (!["csv", "xlsx", "xls"].includes(ext || "")) {
      toast.error("نوع الملف غير مدعوم", {
        description: "يرجى اختيار ملف CSV أو Excel (xlsx, xls)",
      });
      setFile(null);
      e.target.value = "";
      return;
    }
    if (selected.size > MAX_SIZE_MB * 1024 * 1024) {
      toast.error("حجم الملف كبير جداً", {
        description: `الحد الأقصى ${MAX_SIZE_MB} ميجابايت`,
      });
      setFile(null);
      e.target.value = "";
      return;
    }
    setFile(selected);
  };

  const handleSubmit = () => {
    if (!file) {
      toast.error("يرجى اختيار ملف للاستيراد");
      return;
    }

    importClothes(file,
      {
        onSuccess: () => {
          toast.success("تم استيراد المنتجات بنجاح");
          handleClose();
        },
        onError: (error: any) => {
          toast.error("خطأ أثناء استيراد المنتجات", {
            description: error?.message,
          });
        },
      }
    );
  };

  const handleClose = () => {
    setFile(null);
    if (fileInputRef.current) fileInputRef.current.value = "";
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md" dir="rtl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Upload className="h-5 w-5" />
            استيراد منتجات
          </DialogTitle>
          <DialogDescription>
            قم برفع ملف CSV أو Excel (xlsx, xls) لاستيراد المنتجات. الحد الأقصى 50 ميجابايت.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          <div className="space-y-2">
            <label className="text-sm font-medium">الملف (مطلوب)</label>
            <div
              className="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-muted-foreground/25 bg-muted/20 p-6 transition-colors hover:border-muted-foreground/40"
              onClick={() => fileInputRef.current?.click()}
            >
              <input
                ref={fileInputRef}
                type="file"
                accept={ACCEPTED_TYPES}
                onChange={handleFileChange}
                className="hidden"
              />
              {file ? (
                <>
                  <FileSpreadsheet className="h-10 w-10 text-primary" />
                  <p className="text-sm font-medium text-foreground">{file.name}</p>
                  <p className="text-xs text-muted-foreground">
                    {(file.size / 1024).toFixed(1)} ك.ب
                  </p>
                </>
              ) : (
                <>
                  <Upload className="h-10 w-10 text-muted-foreground" />
                  <p className="text-sm text-muted-foreground">
                    انقر لاختيار ملف أو اسحب الملف هنا
                  </p>
                  <p className="text-xs text-muted-foreground">
                    CSV, XLSX, XLS — حتى 50 م.ب
                  </p>
                </>
              )}
            </div>
          </div>
        </div>

        <DialogFooter className="gap-2 sm:gap-0">
          <Button variant="outline" onClick={handleClose} disabled={isPending}>
            إلغاء
          </Button>
          <Button onClick={handleSubmit} disabled={!file || isPending}>
            {isPending ? "جاري الاستيراد..." : "استيراد"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
