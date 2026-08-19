const MONTHS_AR = [
  "يناير",
  "فبراير",
  "مارس",
  "أبريل",
  "مايو",
  "يونيو",
  "يوليو",
  "أغسطس",
  "سبتمبر",
  "أكتوبر",
  "نوفمبر",
  "ديسمبر",
];

/** period: YYYY-MM */
export function periodToArabicLabel(period: string): string {
  const [y, m] = period.split("-");
  const mi = Number(m) - 1;
  if (!y || mi < 0 || mi > 11) return period;
  return `${MONTHS_AR[mi]} ${y}`;
}
