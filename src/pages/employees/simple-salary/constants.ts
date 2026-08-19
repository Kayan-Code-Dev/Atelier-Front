/** Period format YYYY-MM (month 01-12) */
export const PERIOD_REGEX = /^\d{4}-(0[1-9]|1[0-2])$/;

/** Deduction date format YYYY-MM-DD */
export const DATE_REGEX = /^\d{4}-\d{2}-\d{2}$/;

export const DEFAULT_PER_PAGE_DEDUCTIONS = 10;
export const DEFAULT_PER_PAGE_PAYMENTS = 10;

/** Returns current month as YYYY-MM */
export function getDefaultPeriod(): string {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  return `${y}-${m}`;
}
