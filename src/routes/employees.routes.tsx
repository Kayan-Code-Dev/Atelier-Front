import CreateEmployee from "@/pages/employees/CreateEmployee";
import ShowDetailsAndEditEmployee from "@/pages/employees/ShowDetailsAndEditEmployee";
import Employees from "@/pages/employees/Employees";
import { Route, Navigate, useParams } from "react-router";
import EmployeeCustodies from "@/pages/employees/employee-custodies/EmployeeCustodies";
import SimpleSalary from "@/pages/employees/simple-salary/SimpleSalary";
import EmployeeSalaryDetail from "@/pages/employees/simple-salary/EmployeeSalaryDetail";
import PermissionProtectedRoute from "./PermissionProtectedRoute";

function RedirectEmployeeListId() {
  const { id } = useParams<{ id: string }>();
  if (!id) return <Navigate to="/employees" replace />;
  return <Navigate to={`/employees/${id}`} replace />;
}

export const employeesRoutes = () => {
  return (
    <Route
      path="employees"
      element={
        <PermissionProtectedRoute
          permission={[
            "hr.employees.view",
            "hr.employees.create",
            "hr.employees.update",
            "hr.employees.delete",
            "hr.custody.view",
            "hr.payroll.view",
          ]}
        />
      }
    >
      <Route index element={<Employees />} />
      <Route path="add" element={<CreateEmployee />} />
      <Route path="guarantees" element={<EmployeeCustodies />} />
      <Route path="salaries" element={<SimpleSalary />} />
      <Route path="salary-detail/:id" element={<EmployeeSalaryDetail />} />

      <Route path="list" element={<Navigate to="/employees" replace />} />
      <Route path="list/:id" element={<RedirectEmployeeListId />} />
      <Route
        path="custodies"
        element={<Navigate to="/employees/guarantees" replace />}
      />
      <Route
        path="custodies/overdue"
        element={<Navigate to="/employees/guarantees" replace />}
      />
      <Route
        path="payroll"
        element={<Navigate to="/employees/salaries" replace />}
      />
      <Route
        path="employee-documents"
        element={<Navigate to="/employees" replace />}
      />
      <Route
        path="employee-deductions"
        element={<Navigate to="/employees" replace />}
      />

      <Route path=":id" element={<ShowDetailsAndEditEmployee />} />
    </Route>
  );
};
