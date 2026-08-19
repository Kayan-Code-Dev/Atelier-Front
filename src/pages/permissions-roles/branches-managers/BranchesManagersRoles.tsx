import { TRoleItem } from "@/api/permissions-roles/admins/roles/roles.types";
import { useBranchesManagersRolesListQueryOptions } from "@/api/permissions-roles/branches-managers/branches-managers.hooks";
import { useQuery } from "@tanstack/react-query";
import { useMemo, useState } from "react";
import { ViewBranchesManagersPermissionsModal } from "./ViewBranchesManagersPermissionsModal";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Search } from "lucide-react";
import { Button } from "@/components/ui/button";

function BranchesManagersRoles() {
  const [searchQuery, setSearchQuery] = useState("");

  const [selectedRole, setSelectedRole] = useState<TRoleItem | null>(null);
  const [isViewModalOpen, setIsViewModalOpen] = useState(false);
  const { data: rolesData, isPending } = useQuery(useBranchesManagersRolesListQueryOptions());

  const filteredRoles = useMemo(() => {
    if (!rolesData) {
      return [];
    }
    if (!searchQuery) {
      return rolesData; // No filter, return all
    }
    return rolesData.filter((role: TRoleItem) =>
      role.name.toLowerCase().includes(searchQuery.toLowerCase())
    );
  }, [rolesData, searchQuery]);

  const handleViewPermissions = (role: TRoleItem) => {
    setSelectedRole(role);
    setIsViewModalOpen(true);
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>إدارة الصلاحيات</CardTitle>
            <CardDescription>
              عرض وتعديل الأدوار والصلاحيات المرتبطة بها.
            </CardDescription>
          </div>
          <div className="relative w-full max-w-sm">
            <Input
              placeholder="ابحث باسم الدور..."
              className="pr-10"
              value={searchQuery}
              onChange={(e) => {
                setSearchQuery(e.target.value);
              }}
            />
            <Search className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          </div>
        </CardHeader>

        <CardContent>
          <div className="table-responsive-wrapper">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="text-center">اسم الدور</TableHead>
                  <TableHead className="text-center">عدد الصلاحيات</TableHead>
                  <TableHead className="text-center">تاريخ الإنشاء</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isPending ? (
                  <RolesTableSkeleton rows={5} />
                ) : filteredRoles ? (
                  // 5. Render only the roles for the current page
                  filteredRoles.map((role) => (
                    <TableRow key={role.id}>
                      <TableCell className="font-medium text-center">
                        {role.name}
                      </TableCell>

                      <TableCell className="text-center">
                        <Button
                          className="bg-green-500 cursor-pointer"
                          onClick={() => handleViewPermissions(role)}
                        >
                          {role.permissions_count}
                        </Button>
                      </TableCell>
                      <TableCell className="text-center">
                        {new Date(role.created_at).toLocaleDateString("ar-EG")}
                      </TableCell>
                    </TableRow>
                  ))
                ) : (
                  <TableRow>
                    <TableCell
                      colSpan={5}
                      className="py-10 text-center text-muted-foreground"
                    >
                      {searchQuery
                        ? "لم يتم العثور على نتائج للبحث"
                        : "لا توجد أدوار لعرضها"}
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>

        <CardFooter className="flex items-center justify-between">
          <div>اجمالى عدد الصلاحيات : {filteredRoles.length}</div>
          <div></div>
        </CardFooter>
      </Card>

      <ViewBranchesManagersPermissionsModal
        role={selectedRole}
        open={isViewModalOpen}
        onOpenChange={setIsViewModalOpen}
      />
    </div>
  );
}

/**
 * A helper component to show a loading skeleton for the table
 */
function RolesTableSkeleton({ rows = 5 }: { rows?: number }) {
  return Array.from({ length: rows }).map((_, index) => (
    <TableRow key={index}>
      <TableCell>
        <Skeleton className="h-5 w-3/4" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-1/2" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-1/4" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-1/2" />
      </TableCell>
    </TableRow>
  ));
}

export default BranchesManagersRoles;
