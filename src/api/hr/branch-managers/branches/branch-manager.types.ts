export type TBranchManager = {
  uuid: string;
  name: string;
  email: string;
  phone: string;
  location: string;
  latitude: string;
  longitude: string;
  status: "active" | "inactive" | string;
  blocked: boolean;
  created_at: string;
  branch_manager_id: string;
};
