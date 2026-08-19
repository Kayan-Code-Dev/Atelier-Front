import { TDepartment } from "../departments/departments.types";
import { TRole } from "../roles/roles.types";

export type TCreateJobTitleRequest = {
  code: string;
  name: string;
  description: string;
  department_id: number;
  level: string; // key
  min_salary: number;
  max_salary: number;
  is_active: boolean;
  role_ids: number[];
};

export type TUpdateJobTitleRequest = Partial<TCreateJobTitleRequest>; // without the roles select 

export type TJobTitleLevel = {
  name: string;
  key: string;
};

export type TJobTitle = {
  code: string;
  name: string;
  description: string;
  department_id: number;
  level: string;
  min_salary: number;
  max_salary: number;
  is_active: boolean;
  updated_at: string;
  created_at: string;
  id: number;
  department: TDepartment;
  roles: TRole[];
};

export type TGetJobTitlesParams = {
  page?: number;
  per_page?: number;
  department_id?: number;
  level?: string;
  is_active?: boolean;
};
