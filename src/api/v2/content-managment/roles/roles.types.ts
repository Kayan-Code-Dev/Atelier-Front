export type TRole = {
  id: number;
  name: string;
  description: string;
  created_at: string;
  updated_at: string;
  users: any[];
};

export type TCreateRoleRequest = {
  name: string;
  description: string;
};

export type TUpdateRoleRequest = Partial<TCreateRoleRequest>;
