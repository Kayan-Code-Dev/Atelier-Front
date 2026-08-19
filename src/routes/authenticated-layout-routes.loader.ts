/**
 * Single flight for the authenticated route tree chunk so login, rehydration, and App
 * share one network request and the same parsed module.
 */
let modulePromise: Promise<
  typeof import("@/routes/authenticated-layout-routes")
> | null = null;

export function loadAuthenticatedLayoutRoutesModule() {
  modulePromise ??= import("@/routes/authenticated-layout-routes");
  return modulePromise;
}
