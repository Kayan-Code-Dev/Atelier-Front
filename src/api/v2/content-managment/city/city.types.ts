import { TCountry } from "../country/country.types";

export type TCreateCityRequest = {
  country_id: number;
  name: string;
};

export type TCity = {
  id: number;
  name: string;
  country_id: number;

  created_at: string;
  updated_at: string;
  deleted_at: string;
  country : TCountry;
};

export type TCreateCityResponse = TCity;

export type TUpdateCityRequest = Partial<TCreateCityRequest>;
