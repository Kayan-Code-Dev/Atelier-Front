import * as z from "zod";
import { PAYMENT_METHODS } from "@/api/simple-salary/simple-salary.types";
import { DATE_REGEX } from "./constants";

/** Add deduction form validation */
export const addDeductionSchema = z.object({
  amount: z
    .number({ required_error: "المبلغ مطلوب" })
    .min(0.01, "المبلغ يجب أن يكون أكبر من صفر"),
  reason: z.string().min(1, "سبب الخصم مطلوب").max(255, "الحد الأقصى 255 حرفاً"),
  date: z
    .string()
    .min(1, "التاريخ مطلوب")
    .regex(DATE_REGEX, "صيغة التاريخ: YYYY-MM-DD"),
  notes: z.string().max(500, "الحد الأقصى 500 حرف").optional(),
});

export type AddDeductionFormValues = z.infer<typeof addDeductionSchema>;

export function getAddDeductionDefaultValues(): AddDeductionFormValues {
  return {
    amount: 0,
    reason: "",
    date: new Date().toISOString().slice(0, 10),
    notes: "",
  };
}

/** Add addition form validation */
export const addAdditionSchema = z.object({
  amount: z
    .number({ required_error: "المبلغ مطلوب" })
    .min(0.01, "المبلغ يجب أن يكون أكبر من صفر"),
  reason: z.string().min(1, "سبب المكافأة مطلوب").max(255, "الحد الأقصى 255 حرفاً"),
  date: z
    .string()
    .min(1, "التاريخ مطلوب")
    .regex(DATE_REGEX, "صيغة التاريخ: YYYY-MM-DD"),
  notes: z.string().max(500, "الحد الأقصى 500 حرف").optional(),
});

export type AddAdditionFormValues = z.infer<typeof addAdditionSchema>;

export function getAddAdditionDefaultValues(): AddAdditionFormValues {
  return {
    amount: 0,
    reason: "",
    date: new Date().toISOString().slice(0, 10),
    notes: "",
  };
}

/** Pay salary / partial payment form validation */
export const payFormSchema = z.object({
  payment_method: z.enum(PAYMENT_METHODS, {
    required_error: "طريقة الدفع مطلوبة",
  }),
  amount: z
    .number()
    .min(0.01, "المبلغ يجب أن يكون أكبر من صفر")
    .optional()
    .nullable(),
  payment_reference: z.string().max(100).optional(),
  notes: z.string().max(500).optional(),
});

export type PayFormValues = z.infer<typeof payFormSchema>;

export function getPayFormDefaultValues(): PayFormValues {
  return {
    payment_method: "cash",
    amount: null,
    payment_reference: "",
    notes: "",
  };
}
