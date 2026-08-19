export type TCreateCurrencyRequest = {
  name: string;
  code: string;
  symbol: string;
};

export type TCurrency = {
  id: number;
  name: string;
  code: string;
  symbol: string;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
};

export type TCreateCurrencyResponse = TCurrency;

