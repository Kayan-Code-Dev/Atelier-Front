export type TAdmin = {
  uuid: string;
  name: string;
  email: string;
  phone: string;
  id_number: string;
  country: string;
  city: string;
  image: string;
  status: "active" | string;
  blocked: boolean;
  created_at: string;
};