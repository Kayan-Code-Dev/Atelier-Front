export type TDepartment = {
  id: number;
  code: string;
  name: string;
  description: string;
  parent_id: number | null;
  manager_id: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  parent: TDepartment | null;
  manager: {
    id: number;
    name: string;
    email: string;
  };
  children: TDepartment[];
};

export type TCreateDepartmentRequest = {
  code: string;
  name: string;
  description: string;
  manager_id: number;
  is_active: boolean;
  parent_id: number | null;
};

export type TUpdateDepartmentRequest = Partial<TCreateDepartmentRequest>;

export type TGetDepartmentsParams = {
  page?: number;
  per_page?: number;
  parent_id?: number;
  is_active?: boolean;
  with_children?: boolean;
};
