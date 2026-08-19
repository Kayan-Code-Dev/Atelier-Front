import { TAddressResponse } from "../address/address.types";

export const CLIENT_SOURCES = [
  "website",
  "facebook",
  "instagram",
  "twitter",
  "referral",
  "other",
] as const;

export const CLIENT_SOURCE_LABELS = {
  website: "موقع إلكتروني",
  facebook: "فيسبوك",
  instagram: "انستقرام",
  twitter: "تويتر",
  referral: "مرجع من عميل",
  other: "أخرى",
} as const;

export type TClientSource = (typeof CLIENT_SOURCES)[number];

/** Phone entry with type (e.g. mobile, whatsapp) */
export type TClientPhone = {
  phone: string;
  type: string;
};

export type TCreateClientRequest = {
  name: string;
  date_of_birth?: string;
  national_id?: string;
  source?: TClientSource;
  address: {
    city_id: number;
    address: string;
  };
  phones: TClientPhone[];
  breast_size?: string;
  waist_size?: string;
  sleeve_size?: string;
  hip_size?: string;
  shoulder_size?: string;
  length_size?: string;
  measurement_notes?: string;
};

export type TClientResponse = {
  id: number;
  name?: string;
  first_name?: string;
  middle_name?: string;
  last_name?: string;
  date_of_birth?: string;
  national_id?: string;
  source: string;
  address_id: number;
  updated_at: string;
  created_at: string;
  phones: { phone: string; type?: string }[];
  address: TAddressResponse;
};

export type TUpdateClientRequest = Partial<TCreateClientRequest>;
