/**
 * Parse filename from Content-Disposition header (e.g. attachment; filename="orders_2026-03-08.xlsx").
 * Used for export endpoints that return binary XLSX.
 */
export function parseFilenameFromContentDisposition(headers: unknown): string | undefined {
  if (!headers || typeof headers !== "object") return undefined;
  const raw =
    "content-disposition" in headers && typeof (headers as Record<string, string>)["content-disposition"] === "string"
      ? (headers as Record<string, string>)["content-disposition"]
      : "get" in headers && typeof (headers as { get: (n: string) => unknown }).get === "function"
        ? (headers as { get: (n: string) => unknown }).get("content-disposition")
        : undefined;
  const str = typeof raw === "string" ? raw : undefined;
  if (!str) return undefined;
  const match = str.split("filename=")?.[1]?.replace(/^["']|["']$/g, "")?.trim();
  return match || undefined;
}

/**
 * Trigger browser download of a blob (e.g. Excel export).
 * Uses optional filename from API Content-Disposition, or fallback.
 */
export function downloadBlob(blob: Blob, filename?: string): void {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename?.trim() || "export.xlsx";
  document.body.appendChild(a);
  a.click();
  a.remove();
  window.URL.revokeObjectURL(url);
}

type AtelierMeta = Record<string, unknown>;

type AtelierEnvelope = {
  success?: boolean;
  message?: string;
  data?: unknown;
  meta?: AtelierMeta | unknown;
};

/**
 * Normalize Atelier `{ success, data, meta }` envelopes into the shapes the
 * DressnMore-era FE services expect (paginated `{ data, current_page, ... }`
 * or a bare resource object).
 */
export function normalizeAtelierEnvelope(payload: unknown): unknown {
  if (!payload || typeof payload !== "object") return payload;
  const envelope = payload as AtelierEnvelope;
  if (envelope.success !== true || !("data" in envelope)) return payload;

  const inner = envelope.data;
  const meta =
    envelope.meta && typeof envelope.meta === "object" && !Array.isArray(envelope.meta)
      ? (envelope.meta as AtelierMeta)
      : {};

  if (Array.isArray(inner)) {
    const current_page = Number(meta.current_page ?? 1) || 1;
    const per_page = Number(meta.per_page ?? inner.length) || inner.length || 10;
    const total = Number(meta.total ?? inner.length) || inner.length;
    const total_pages =
      Number(meta.last_page ?? meta.total_pages ?? 1) || 1;
    const stats =
      meta.stats && typeof meta.stats === "object"
        ? (meta.stats as AtelierMeta)
        : undefined;

    return {
      data: inner,
      current_page,
      per_page,
      total,
      total_pages,
      message: envelope.message,
      success: true,
      meta: stats
        ? {
            total: Number(stats.total ?? total) || total,
            read: Number(stats.read ?? 0) || 0,
            unread: Number(stats.unread ?? 0) || 0,
          }
        : Object.keys(meta).length
          ? meta
          : {
              total,
              read: 0,
              unread: 0,
            },
    };
  }

  return inner;
}

export const resolveError = (error: any) => {
  if (error.response) {
    let msg =
      error.response.data.message || "حدث خطأ. الرجاء المحاولة مرة أخرى.";
    if (error.response.data.errors) {
      for (const key in error.response.data.errors) {
        if (error.response.data.errors[key]) {
          msg += error.response.data.errors[key][0] + " ";
          break;
        }
      }
    }
    return msg;
  } else if (error.request) {
    // The request was made but no response was received
    return "No response from server. Please check your network connection.";
  } else {
    // Something happened in setting up the request that triggered an Error
    return error.message || "An unexpected error occurred.";
  }
};

export const populateError = (error: any, msg: string) => {
  throw new Error(
    error instanceof Error ? error.message : error.message || msg
  );
};
