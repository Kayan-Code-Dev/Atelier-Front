import { format, parseISO } from "date-fns";
import { ar } from "date-fns/locale";

const ARABIC_NUMERALS = "٠١٢٣٤٥٦٧٨٩";
const ENGLISH_NUMERALS = "0123456789";

/** Converts Arabic-Indic digits (U+0660–U+0669) to ASCII digits (0-9) in a string */
export function toEnglishNumerals(value: string | number | null | undefined): string {
  if (value === null || value === undefined) return "";
  const s = String(value);
  return s.replace(/[٠-٩]/g, (d) => ENGLISH_NUMERALS[ARABIC_NUMERALS.indexOf(d)] ?? d);
}

export const formatDate = (dateString?: string) => {
    if (!dateString) return "-";
    try {
        const parsedDate = parseISO(dateString);
        return format(parsedDate, "yyyy/MM/dd");
    } catch {
        return dateString;
    }
};

export const formatDateTime = (dateString?: string) => {
    if (!dateString) return "-";
    try {
        const parsedDate = parseISO(dateString);
        return format(parsedDate, "yyyy/MM/dd - h:mm a", { locale: ar });
    } catch {
        return dateString;
    }
};

/** Format a number with ar-EG locale (no decimals) */
export const fmtNumber = (n: number) =>
  new Intl.NumberFormat("ar-EG").format(n);

/** Format a number with ar-EG locale (2 decimal places) */
export const fmtCurrency = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);