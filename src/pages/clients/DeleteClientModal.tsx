import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { TClientResponse } from "@/api/v2/clients/clients.types";
import { useMutation } from "@tanstack/react-query";
import { useDeleteClientMutationOptions } from "@/api/v2/clients/clients.hooks";
import { toast } from "sonner";
import { getClientDisplayName } from "./clientsViewModel";

type Props = {
  client: TClientResponse | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function DeleteClientModal({ client, open, onOpenChange }: Props) {
  const { mutate: deleteClient, isPending } = useMutation(
    useDeleteClientMutationOptions(),
  );

  const name = client ? getClientDisplayName(client) : "";

  const handleDelete = () => {
    if (!client) return;
    deleteClient(client.id, {
      onSuccess: () => {
        toast.success(`تم حذف العميل ${name} بنجاح.`);
        onOpenChange(false);
      },
      onError: () => {
        toast.error("حدث خطأ ما أثناء حذف العميل. الرجاء المحاولة مرة أخرى.");
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        showCloseButton={false}
        bodyClassName="p-0 min-h-0"
        className="sm:max-w-md p-0 gap-0 overflow-hidden rounded-2xl border-[#F0EDE5]"
      >
        <DialogHeader className="sr-only">
          <DialogTitle>حذف العميل</DialogTitle>
          <DialogDescription>تأكيد حذف العميل من النظام</DialogDescription>
        </DialogHeader>

        <div
          className="px-5 py-4 flex items-center justify-between border-b"
          style={{ borderColor: "#F0EDE5", background: "#FEF2F2" }}
        >
          <div className="flex items-center gap-3">
            <div
              className="w-10 h-10 rounded-xl flex items-center justify-center"
              style={{ background: "#FEE2E2" }}
            >
              <i className="ri-delete-bin-line text-lg text-red-600" />
            </div>
            <div>
              <h2 className="text-lg font-bold" style={{ color: "#1A1A2E" }}>
                حذف العميل
              </h2>
              <p className="text-xs mt-0.5" style={{ color: "#9CA3AF" }}>
                لا يمكن التراجع عن هذا الإجراء
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            disabled={isPending}
            className="w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer transition-colors hover:opacity-80 disabled:opacity-50"
            style={{ background: "#FEE2E2", color: "#991B1B" }}
            aria-label="إغلاق"
          >
            <i className="ri-close-line text-lg" />
          </button>
        </div>

        <div className="px-5 py-6 space-y-4">
          <div
            className="rounded-xl p-4 text-sm leading-relaxed"
            style={{
              background: "#F8F7F4",
              border: "1px solid #F0EDE5",
              color: "#374151",
            }}
          >
            هل أنت متأكد أنك تريد حذف العميل{" "}
            <strong style={{ color: "#1A1A2E" }}>{name}</strong>؟
            <br />
            <span className="text-xs mt-2 block" style={{ color: "#9CA3AF" }}>
              سيتم إزالة السجل نهائياً من قائمة العملاء.
            </span>
          </div>

          <div className="flex flex-col-reverse sm:flex-row gap-3">
            <button
              type="button"
              onClick={() => onOpenChange(false)}
              disabled={isPending}
              className="flex-1 py-2.5 rounded-xl font-semibold text-sm cursor-pointer disabled:opacity-50"
              style={{ background: "#F5F4F0", color: "#6B7280" }}
            >
              إلغاء
            </button>
            <button
              type="button"
              onClick={handleDelete}
              disabled={isPending}
              className="flex-1 py-2.5 rounded-xl font-bold text-sm cursor-pointer text-white disabled:opacity-50 transition-shadow hover:shadow-md"
              style={{
                background: "linear-gradient(135deg, #DC2626 0%, #B91C1C 100%)",
                boxShadow: "0 2px 10px rgba(220, 38, 38, 0.25)",
              }}
            >
              {isPending ? (
                <span className="inline-flex items-center justify-center gap-2">
                  <i className="ri-loader-4-line animate-spin" />
                  جاري الحذف...
                </span>
              ) : (
                <span className="inline-flex items-center justify-center gap-2">
                  <i className="ri-delete-bin-line" />
                  تأكيد الحذف
                </span>
              )}
            </button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
