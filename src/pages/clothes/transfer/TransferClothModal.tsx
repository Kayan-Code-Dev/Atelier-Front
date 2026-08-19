import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import type { TEntity } from "@/lib/types/entity.types";
import { EntitySelect } from "@/components/custom/EntitySelect";
import { DatePicker } from "@/components/custom/DatePicker";
import { useCreateTransferClothesMutationOptions } from "@/api/v2/clothes/transfer-clothes/transfer-clothes.hooks";
import { CLOTHES_KEY } from "@/api/v2/clothes/clothes.hooks";
import type { TClothResponse } from "@/api/v2/clothes/clothes.types";
import { Loader2 } from "lucide-react";
import { cn } from "@/lib/utils";
import { TransferClothPicker } from "./TransferClothPicker";

const schema = z.object({
  transfer_date: z.date({ required_error: "تاريخ النقل مطلوب" }),
  notes: z
    .string()
    .min(1, "سبب النقل مطلوب")
    .max(500, "الحد الأقصى 500 حرف"),
});

type FormValues = z.infer<typeof schema>;

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  initialCloth?: TClothResponse | null;
  onSuccess?: () => void;
};

const inputClass =
  "w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300";

const entitySelectShell =
  "[&_label]:block [&_label]:text-xs [&_label]:font-bold [&_label]:text-slate-600 [&_label]:mb-1.5 [&_[data-slot=select-trigger]]:rounded-xl [&_[data-slot=select-trigger]]:border-slate-200 [&_[data-slot=select-trigger]]:h-auto [&_[data-slot=select-trigger]]:min-h-[42px] [&_[data-slot=select-trigger]]:py-2.5";

export function TransferClothModal({
  open,
  onOpenChange,
  initialCloth = null,
  onSuccess,
}: Props) {
  const [pickedCloth, setPickedCloth] = useState<TClothResponse | null>(null);

  const [toEntityType, setToEntityType] = useState<TEntity | undefined>();
  const [toEntityId, setToEntityId] = useState("");

  const form = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      transfer_date: new Date(),
      notes: "",
    },
  });

  const queryClient = useQueryClient();
  const { mutate: createTransfer, isPending: isSubmitting } = useMutation(
    useCreateTransferClothesMutationOptions()
  );

  useEffect(() => {
    if (!open) return;
    setPickedCloth(initialCloth ?? null);
    setToEntityType(undefined);
    setToEntityId("");
    form.reset({
      transfer_date: new Date(),
      notes: "",
    });
  }, [open, initialCloth, form]);

  const close = () => onOpenChange(false);

  const fromLabel = pickedCloth
    ? pickedCloth.entity_name?.trim() ||
      `${pickedCloth.entity_type === "branch" ? "فرع" : pickedCloth.entity_type === "factory" ? "مصنع" : "ورشة"} (${pickedCloth.entity_id})`
    : "";

  const onSubmit = form.handleSubmit((values) => {
    if (!pickedCloth) {
      toast.error("اختر منتجاً أولاً");
      return;
    }
    if (!toEntityType || !toEntityId) {
      toast.error("اختر نوع الوجهة والوجهة");
      return;
    }
    if (
      pickedCloth.entity_type === toEntityType &&
      pickedCloth.entity_id === Number(toEntityId)
    ) {
      toast.error("الوجهة يجب أن تختلف عن موقع المنتج الحالي");
      return;
    }

    const transfer_date = values.transfer_date.toISOString().split("T")[0];

    createTransfer(
      {
        from_entity_type: pickedCloth.entity_type,
        from_entity_id: pickedCloth.entity_id,
        to_entity_type: toEntityType,
        to_entity_id: Number(toEntityId),
        cloth_ids: [pickedCloth.id],
        transfer_date,
        notes: values.notes.trim(),
      },
      {
        onSuccess: () => {
          toast.success("تم تسجيل طلب النقل بنجاح");
          queryClient.invalidateQueries({ queryKey: [CLOTHES_KEY] });
          onSuccess?.();
          close();
        },
        onError: (e: Error & { message?: string }) => {
          toast.error("تعذر إنشاء طلب النقل", {
            description: e.message,
          });
        },
      }
    );
  });

  if (!open) return null;

  const notesLen = form.watch("notes")?.length ?? 0;

  return (
    <div
      className="fixed inset-0 z-[60] flex items-center justify-center p-4"
      style={{ background: "rgba(0,0,0,0.45)" }}
      dir="rtl"
      onClick={close}
      role="presentation"
    >
      <div
        className="w-full max-w-2xl rounded-2xl bg-white overflow-hidden shadow-xl flex flex-col max-h-[90vh]"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="transfer-cloth-title"
      >
        <div
          className="px-6 py-4 flex items-center justify-between shrink-0"
          style={{
            background: "linear-gradient(135deg, #0C1A3E, #1E3A7B)",
          }}
        >
          <div className="flex items-center gap-3 min-w-0">
            <div
              className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
              style={{ background: "rgba(194,150,74,0.2)" }}
            >
              <i
                className="ri-arrow-left-right-line text-base"
                style={{ color: "#E8BF7A" }}
              />
            </div>
            <div className="min-w-0">
              <h2
                id="transfer-cloth-title"
                className="text-white font-black text-base truncate"
              >
                طلب نقل منتج
              </h2>
              <p className="text-white/50 text-xs mt-0.5 truncate">
                نقل بين فرع، مصنع أو ورشة — يتطلب موافقة لاحقاً
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={close}
            className="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors"
            style={{ background: "rgba(255,255,255,0.1)" }}
          >
            <i className="ri-close-line text-white text-base" />
          </button>
        </div>

        <form
          onSubmit={onSubmit}
          className="flex flex-col flex-1 min-h-0"
        >
          <div
            className="overflow-y-auto p-6 space-y-5 flex-1"
            style={{ maxHeight: "calc(90vh - 64px - 73px)" }}
          >
            {!pickedCloth ? (
              <>
                <div>
                  <label className="block text-xs font-bold text-slate-600 mb-1.5">
                    المنتج <span className="text-red-400">*</span>
                  </label>
                  <TransferClothPicker
                    modalOpen={open}
                    value={null}
                    onChange={setPickedCloth}
                    disabled={isSubmitting}
                    placeholder="افتح القائمة واختر منتجاً..."
                  />
                </div>
                <div
                  className="rounded-xl p-3 flex items-start gap-3"
                  style={{
                    background: "#F8FAFC",
                    border: "1px solid #EEF2F8",
                  }}
                >
                  <i className="ri-information-line text-slate-400 text-sm shrink-0 mt-0.5" />
                  <p className="text-xs text-slate-500 leading-relaxed">
                    تُعرض أول مجموعة من المنتجات فور فتح القائمة. للبحث في
                    الخادم اكتب حرفين على الأقل في حقل البحث داخل القائمة، أو
                    استخدم «تحميل المزيد» لصفحات إضافية.
                  </p>
                </div>
              </>
            ) : (
              <>
                <div
                  className="rounded-xl p-3 flex items-center gap-3"
                  style={{
                    background: "#F8FAFC",
                    border: "1px solid #EEF2F8",
                  }}
                >
                  <i className="ri-shirt-line text-slate-400 text-sm shrink-0" />
                  <div className="min-w-0 flex-1">
                    <p className="text-[11px] text-slate-400 font-semibold">
                      المنتج المحدد
                    </p>
                    <p className="text-sm font-black text-slate-800 font-mono mt-0.5 break-all">
                      {pickedCloth.code}
                      {pickedCloth.category_name ? (
                        <span className="text-slate-500 font-sans font-semibold text-xs mr-2">
                          · {pickedCloth.category_name}
                        </span>
                      ) : null}
                    </p>
                  </div>
                  {!initialCloth ? (
                    <button
                      type="button"
                      className="text-xs font-bold text-slate-500 hover:text-slate-800 shrink-0 px-2 py-1 rounded-lg hover:bg-white/80 transition-colors"
                      onClick={() => setPickedCloth(null)}
                    >
                      تغيير
                    </button>
                  ) : null}
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-600 mb-1.5">
                      من <span className="text-slate-400 font-semibold">(الموقع الحالي)</span>
                    </label>
                    <input
                      readOnly
                      value={fromLabel}
                      className={cn(inputClass, "bg-slate-50 text-slate-600 cursor-default")}
                      placeholder="—"
                    />
                  </div>
                  <div className={cn("space-y-0", entitySelectShell)}>
                    <EntitySelect
                      mode="standalone"
                      entityType={toEntityType}
                      entityId={toEntityId}
                      onEntityTypeChange={(t) => {
                        setToEntityType(t);
                        setToEntityId("");
                      }}
                      onEntityIdChange={setToEntityId}
                      entityTypeLabel="نوع الوجهة"
                      entityIdLabel="الوجهة"
                      required
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-600 mb-1.5">
                    تاريخ النقل <span className="text-red-400">*</span>
                  </label>
                  <DatePicker
                    value={form.watch("transfer_date")}
                    onChange={(d) =>
                      form.setValue("transfer_date", d ?? new Date())
                    }
                    showLabel={false}
                    className="w-full gap-0"
                    buttonClassName={cn(
                      inputClass,
                      "w-full justify-between font-normal text-slate-800 shadow-none"
                    )}
                  />
                  {form.formState.errors.transfer_date ? (
                    <p className="text-xs text-red-500 mt-1">
                      {form.formState.errors.transfer_date.message}
                    </p>
                  ) : null}
                </div>

                <div>
                  <label
                    htmlFor="transfer-notes"
                    className="block text-xs font-bold text-slate-600 mb-1.5"
                  >
                    سبب النقل <span className="text-red-400">*</span>
                  </label>
                  <textarea
                    id="transfer-notes"
                    rows={3}
                    maxLength={500}
                    placeholder="مثال: إصلاح، طلب عميل، إعادة تخزين..."
                    className={cn(inputClass, "resize-none min-h-[88px]")}
                    {...form.register("notes")}
                  />
                  <div className="flex justify-between items-center mt-1">
                    {form.formState.errors.notes ? (
                      <p className="text-xs text-red-500">
                        {form.formState.errors.notes.message}
                      </p>
                    ) : (
                      <span />
                    )}
                    <p className="text-[11px] text-slate-400">{notesLen}/500</p>
                  </div>
                </div>
              </>
            )}
          </div>

          <div
            className="px-6 py-4 flex items-center justify-between gap-3 shrink-0"
            style={{
              background: "#F8FAFC",
              borderTop: "1px solid #EEF2F8",
            }}
          >
            <button
              type="button"
              onClick={close}
              disabled={isSubmitting}
              className="px-5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors disabled:opacity-50"
              style={{ background: "#F1F5F9", color: "#475569" }}
            >
              إلغاء
            </button>
            {pickedCloth ? (
              <button
                type="submit"
                disabled={isSubmitting}
                className="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap text-white disabled:opacity-60 transition-opacity"
                style={{
                  background: "linear-gradient(135deg, #0C1A3E, #1E3A7B)",
                }}
              >
                {isSubmitting ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  <i className="ri-send-plane-2-line" />
                )}
                {isSubmitting ? "جاري الإرسال..." : "تسجيل طلب النقل"}
              </button>
            ) : (
              <span className="text-xs text-slate-400 font-medium">
                اختر منتجاً للمتابعة
              </span>
            )}
          </div>
        </form>
      </div>
    </div>
  );
}
