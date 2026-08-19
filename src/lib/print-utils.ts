/**
 * Sanitize HTML string before writing to print window to reduce XSS risk
 * when content might include user-supplied data. Removes script tags and
 * inline event handlers; keeps structure and styling.
 */
export function sanitizePrintHtml(html: string): string {
  if (!html || typeof html !== "string") return "";
  let out = html
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, "")
    .replace(/\s on\w+\s*=\s*["'][^"']*["']/gi, " ")
    .replace(/\s on\w+\s*=\s*[^\s>]*/gi, " ");
  return out;
}
