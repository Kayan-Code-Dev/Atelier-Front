export type TUpdateProfileRequest = {
  name: string;
  email: string;
  avatar?: File | null;
  avatar_remove?: boolean;
  logo?: File | null;
  logo_remove?: boolean;
};

export type TChangePasswordRequest = {
  current_password: string;
  new_password: string;
  new_password_confirmation: string;
};

export type TAccountProfile = {
  id: number;
  name: string;
  email: string;
  avatar?: string | null;
  avatar_url?: string | null;
  logo?: string | null;
  logo_url?: string | null;
  employee?: unknown;
  roles?: string[];
};
