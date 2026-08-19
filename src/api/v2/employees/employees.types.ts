import { TEntity } from "@/lib/types/entity.types";
import { TDepartment } from "../content-managment/departments/departments.types";
import { TJobTitle } from "../content-managment/job-titles/job-titles.types";
import { TBranchResponse } from "../branches/branches.types";
import { TRole } from "../content-managment/roles/roles.types";

export const EMPLOYMENT_TYPES = [
  "full_time",
  "part_time",
  "contract",
  "intern",
] as const;

export type TEmploymentType = (typeof EMPLOYMENT_TYPES)[number];

export type TCreateEmployeeRequest = {
  // Required fields
  name: string;
  email: string;
  password: string;
  hire_date: string; // Format: YYYY-MM-DD

  // Optional fields
  department_id?: number;
  job_title_id?: number;
  manager_id?: number;
  employment_type?: TEmploymentType;
  roles?: number[];
  /** List of branches associated with employee as in creation JSON */
  branch_ids?: number[];
  base_salary?: number;
  transport_allowance?: number;
  housing_allowance?: number;
  other_allowances?: number;
  overtime_rate?: number;
  commission_rate?: number;
  annual_vacation_days?: number;
  probation_end_date?: string; // Format: YYYY-MM-DD
  work_start_time?: string; // Format: HH:MM:SS
  work_end_time?: string; // Format: HH:MM:SS
  work_hours_per_day?: number;
  late_threshold_minutes?: number;
  bank_name?: string;
  bank_account_number?: string;
  bank_iban?: string;
  emergency_contact_name?: string;
  emergency_contact_phone?: string;
  emergency_contact_relation?: string;
  notes?: string;
  primary_branch_id?: number;
  entity_assignments?: TEntityAssignments[];
};

type TEntityAssignments = {
  entity_type: TEntity;
  entity_id: number;
  is_primary: boolean;
};

export const EMPLOYMENT_STATUS = [
  "active",
  "on_leave",
  "suspended",
  "terminated",
] as const;
export type TEmploymentStatus = (typeof EMPLOYMENT_STATUS)[number];

export type TEmployee = {
  id: number;
  user_id: number;
  employee_code: string;
  department_id: number;
  job_title_id: number;
  manager_id: number | null;
  employment_type: TEmploymentType;
  employment_status: TEmploymentStatus;
  hire_date: string;
  termination_date: string | null;
  probation_end_date: string;
  base_salary: number;
  transport_allowance: number;
  housing_allowance: number;
  other_allowances: number;
  overtime_rate: number;
  commission_rate: number;
  vacation_days_balance: number;
  vacation_days_used: number;
  annual_vacation_days: number;
  work_start_time: string;
  work_end_time: string;
  work_hours_per_day: number;
  late_threshold_minutes: number;
  bank_name: string;
  bank_account_number: string;
  bank_iban: string;
  emergency_contact_name: string;
  emergency_contact_phone: string;
  emergency_contact_relation: string;
  user: {
    id: number;
    name: string;
    email: string;
    roles: TRole[];
  };
  department: TDepartment;
  job_title: TJobTitle;

  manager: TEmployee | null;
  branches: TBranchResponse[];
};

export type TGetEmployeesParams = {
  page?: number;
  per_page?: number;
  search?: string;
  id?: number | string;
  user_id?: number;
  department_id?: number;
  job_title_id?: number;
  manager_id?: number;
  level?:
    | "master_manager"
    | "branches_manager"
    | "branch_manager"
    | "employee";
  branch_id?: number;
  employment_type?: TEmploymentType;
  employment_status?: TEmploymentStatus;
  hire_date_from?: string;
  hire_date_to?: string;
  termination_date_from?: string;
  termination_date_to?: string;
};

export type TUpdateEmployeeRequest = Partial<{
  name: string;
  email: string;
  department_id: number;
  job_title_id: number;
  base_salary: number;
  employment_status: TEmploymentStatus;
  entity_assignments: TEntityAssignments[];
  roles: number[];
}>;

export type TEmployeeAssignmentsResponse = {
  id: number;
  entity_type: TEntity;
  entity_id: number;
  entity_name: string;
  is_primary: boolean;
  assigned_at: string;
};

export type TTerminateEmployeeRequest = {
  termination_date: string;
  reason: string;
};
