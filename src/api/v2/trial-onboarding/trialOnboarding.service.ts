import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import type { TrialOnboardingSnapshot } from "./trialOnboarding.types";

type Envelope<T> = {
  success?: boolean;
  data?: T;
};

export async function getTrialOnboarding(): Promise<TrialOnboardingSnapshot | undefined> {
  try {
    const { data } = await api.get<Envelope<TrialOnboardingSnapshot>>("/trial-onboarding");
    return data.data;
  } catch (error) {
    populateError(error, "تعذر تحميل رحلة التجربة");
  }
}

export async function startTrialOnboarding(): Promise<TrialOnboardingSnapshot | undefined> {
  try {
    const { data } = await api.post<Envelope<TrialOnboardingSnapshot>>("/trial-onboarding/start");
    return data.data;
  } catch (error) {
    populateError(error, "تعذر بدء رحلة التجربة");
    throw error;
  }
}

export async function recordTrialOnboardingView(
  step: string,
): Promise<TrialOnboardingSnapshot | undefined> {
  try {
    const { data } = await api.post<Envelope<TrialOnboardingSnapshot>>(
      "/trial-onboarding/views",
      { step },
    );
    return data.data;
  } catch (error) {
    populateError(error, "تعذر تسجيل مشاهدة الخطوة");
    throw error;
  }
}

export async function acknowledgeTrialOnboardingCompletion(): Promise<
  TrialOnboardingSnapshot | undefined
> {
  try {
    const { data } = await api.post<Envelope<TrialOnboardingSnapshot>>(
      "/trial-onboarding/acknowledge-completion",
    );
    return data.data;
  } catch (error) {
    populateError(error, "تعذر إغلاق شاشة الإكمال");
    throw error;
  }
}

export async function recordTrialCommercialSignal(
  signal: "pricing_viewed" | "upgrade_clicked" | "checkout_started",
): Promise<void> {
  try {
    await api.post("/trial-onboarding/signals", { signal });
  } catch {
    // Commercial intent must never block the subscription UI.
  }
}
