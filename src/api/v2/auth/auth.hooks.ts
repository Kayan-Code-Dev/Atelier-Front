import { mutationOptions } from "@tanstack/react-query";
import { loginApi } from "./auth.service";

export const useLoginMutationOptions = () => {
  return mutationOptions({
    mutationFn: loginApi,
  });
};