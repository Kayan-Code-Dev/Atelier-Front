const DRESSN_ROOT = "dressnmore.it.com";

/**
 * من نطاق الواجهة (سب دومين التالنت) يُستنتج رابط الـ API كما في الاستجابة:
 * test.dressnmore.it.com → https://test.backend.dressnmore.it.com/api/v1
 * test.backend.dressnmore.it.com → https://test.backend.dressnmore.it.com/api/v1
 */
export function inferDressnTenantApiBaseUrl(): string | null {
  if (typeof window === "undefined") return null;
  const host = window.location.hostname.toLowerCase();

  const backendHost = host.match(
    /^([a-z0-9-]+)\.backend\.dressnmore\.it\.com$/i,
  );
  if (backendHost) {
    return `https://${host}/api/v1`.replace(/\/+$/, "");
  }

  const tenantFront = host.match(/^([a-z0-9-]+)\.dressnmore\.it\.com$/i);
  if (!tenantFront) return null;
  const label = tenantFront[1]!.toLowerCase();
  if (label === "www" || label === "api") return null;
  return `https://${label}.backend.${DRESSN_ROOT}/api/v1`.replace(/\/+$/, "");
}
