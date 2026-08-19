export type TAddressResponse = {
  id: number;
  street: string;
  building: string;
  notes: string;
  city_id: number;
  deleted_at: string;
  created_at: string;
  updated_at: string;
  city: {
    name: string;
    country: {
      name: string;
    };
  };
};
