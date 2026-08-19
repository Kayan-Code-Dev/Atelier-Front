import { TEmployee } from "../employees.types";

export type TEmployeeCustodyType = {
  key: string;
  name: string;
};

export type TEmployeeCustodyConditionOnAssignment =
  | "new"
  | "good"
  | "fair"
  | "poor";
export type TEmployeeCustodyStatus = "assigned" | "returned" |  "damaged" | "lost";
export type TEmployeeCustody = {
  employee_id: number;
  type: string;
  name: string;
  description: string;
  serial_number: string;
  asset_tag: string;
  value: number;
  condition_on_assignment: TEmployeeCustodyConditionOnAssignment;
  assigned_date: string;
  expected_return_date: string;
  notes: string;
  assigned_by: {
    id: number;
    name: string;
    email: string;
  };
  status: TEmployeeCustodyStatus;
  updated_at: string;
  created_at: string;
  id: number;
  employee: TEmployee;
};

export type TCreateEmployeeCustody = {
  employee_id: number;
  type: string;
  name: string;
  description: string;
  serial_number: string;
  asset_tag: string;
  value: number;
  condition_on_assignment: TEmployeeCustodyConditionOnAssignment;
  assigned_date: string;
  expected_return_date: string;
  notes: string;
};

export type TGetEmployeeCustodiesParams = {
  page?: number;
  per_page?: number;
  employee_id?: number;
  type?: string;
  status?: TEmployeeCustodyStatus;
};

export type TUpdateEmployeeCustody = Partial<{
  name: string;
  description: string;
  serial_number: string;
  value: number;
  expected_return_date: string;
  notes: string;
}>;
