export type TCreateCountryRequest = {
  name: string;
};

export type TCountry = {
  id: number;
  name: string;
  created_at: string;
  updated_at: string;
  deleted_at: string;
};

export type TCreateCountryResponse = TCountry;
