export type TDashboardOverviewParams = {
  period?: "today" | "week" | "month" | "year" | "last_week" | "last_month";
  date_from?: string;
  date_to?: string;
  branch_id?: number;
  department_id?: number;
  
  growth_compare_period?: "previous_month";
};

export type TDashboardPeriod = {
  from: string;
  to: string;
};

// --- Activity ---
export type TDashboardActivity = {
  total_activities: number;
  by_action: Record<string, number>;
  by_entity_type: Record<string, number>;
  period: TDashboardPeriod;
};

// --- Business: Sales ---
export type TDashboardSales = {
  total_revenue: number;
  order_count: number;
  average_order_value: number;
  by_status: Record<string, { count: number; revenue: number }>;
  period: TDashboardPeriod;
};

// --- Business: Clients ---
export type TDashboardClients = {
  new_clients: number;
  total_clients: number;
  active_clients: number;
  growth_rate: number;
  period: TDashboardPeriod;
};

// --- Business: Payments ---
export type TDashboardPayments = {
  total_payments: number;
  payment_count: number;
  by_method: Record<string, { count: number; total: number }>;
  period: TDashboardPeriod;
};

// --- Business: Inventory ---
export type TDashboardInventory = {
  total_items: number;
  available: number;
  out_of_branch: number;
  utilization_rate: number;
};

// --- Business: Financial ---
export type TDashboardFinancial = {
  total_income: number;
  total_expenses: number;
  profit: number;
  profit_margin: number;
  cashbox_balances: Array<{
    cashbox_id: number;
    name: string;
    balance: number;
  }>;
  period: TDashboardPeriod;
};


export type TDashboardGrowthRates = {
  revenue?: number;
  sales?: number;
  rental?: number;
  tailoring?: number;
};

export type TDashboardBusiness = {
  sales: TDashboardSales;
  clients: TDashboardClients;
  payments: TDashboardPayments;
  inventory: TDashboardInventory;
  financial: TDashboardFinancial;
  
  growth_rates?: TDashboardGrowthRates;
};

// --- HR: Attendance ---
export type TDashboardAttendance = {
  total_records: number;
  present_days: number;
  absent_days: number;
  late_arrivals: number;
  leave_days: number;
  attendance_rate: number;
  period: TDashboardPeriod;
};

// --- HR: Payroll ---
export type TDashboardPayroll = {
  total_payroll: number;
  payroll_count: number;
  average_salary: number;
  by_status: Record<string, { count: number; total: number }>;
  period: TDashboardPeriod;
};

// --- HR: Employee activity ---
export type TMostActiveEmployee = {
  user_id: number;
  employee_id: number;
  employee_name: string;
  activity_count: number;
};

export type TDashboardEmployeeActivity = {
  most_active_employees: TMostActiveEmployee[];
  period: TDashboardPeriod;
};

// --- HR: Trends ---
export type TAttendanceTrendItem = {
  date: string;
  total: number;
  present: number;
  attendance_rate: number;
};

export type TPayrollTrendItem = {
  date: string;
  total_payroll: number;
  payroll_count: number;
};

export type TDashboardTrends = {
  attendance_trends: TAttendanceTrendItem[];
  payroll_trends: TPayrollTrendItem[];
  period: TDashboardPeriod;
};

export type TDashboardHR = {
  attendance: TDashboardAttendance;
  payroll: TDashboardPayroll;
  employee_activity: TDashboardEmployeeActivity;
  trends: TDashboardTrends;
};

export type TDashboardOverviewResponse = {
  activity: TDashboardActivity;
  business: TDashboardBusiness;
  hr: TDashboardHR;
  generated_at: string;
};
