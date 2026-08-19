import { mutationOptions, queryOptions, useQueryClient } from "@tanstack/react-query";
import {
  acknowledgeTrialOnboardingCompletion,
  getTrialOnboarding,
  recordTrialOnboardingView,
  startTrialOnboarding,
} from "./trialOnboarding.service";

export const TRIAL_ONBOARDING_KEY = "TRIAL_ONBOARDING";

export const useGetTrialOnboardingQueryOptions = (enabled = true) =>
  queryOptions({
    queryKey: [TRIAL_ONBOARDING_KEY],
    queryFn: getTrialOnboarding,
    enabled,
    staleTime: 1000 * 8,
    refetchOnWindowFocus: true,
    refetchInterval: (query) => {
      const data = query.state.data;
      if (!data?.eligible || data.status === "completed") return false;
      return 8000;
    },
  });

export const useStartTrialOnboardingMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: startTrialOnboarding,
    onSuccess: (data) => {
      queryClient.setQueryData([TRIAL_ONBOARDING_KEY], data);
    },
  });
};

export const useRecordTrialOnboardingViewMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: recordTrialOnboardingView,
    onSuccess: (data) => {
      queryClient.setQueryData([TRIAL_ONBOARDING_KEY], data);
    },
  });
};

export const useAcknowledgeTrialOnboardingMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: acknowledgeTrialOnboardingCompletion,
    onSuccess: (data) => {
      queryClient.setQueryData([TRIAL_ONBOARDING_KEY], data);
    },
  });
};
