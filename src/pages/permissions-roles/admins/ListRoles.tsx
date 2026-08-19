import { useRolesListQueryOptions } from "@/api/permissions-roles/admins/roles/roles.hooks";
import { useState, useMemo } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationPrevious,
  PaginationNext,
} from "@/components/ui/pagination";
import {
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardFooter,
  CardDescription,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Search, Pencil, Trash2, Download } from "lucide-react";
import { ROLES, TRoleItem } from "@/api/permissions-roles/admins/roles/roles.types";
import { EditRoleModal } from "./EditRoleModal";
import { DeleteRoleModal } from "./DeleteRoleModal";
import { ViewPermissionsModal } from "./ViewPermissionsModal";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import { DELETE_ROLE, UPDATE_ROLE } from "@/lib/permissions.helper";
import { useExportRolesToCSVMutationOptions } from "@/api/v2/content-managment/roles/roles.hooks";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import { toast } from "sonner";

// --- Constants ---
const PAGE_SIZE = 10; // How many items to show per page

/**
 * Main component for displaying the roles list
 */
function ListRoles() {
  const [page, setPage] = useState(1);
  const [searchQuery, setSearchQuery] = useState("");

  const [selectedRole, setSelectedRole] = useState<TRoleItem | null>(null);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isViewModalOpen, setIsViewModalOpen] = useState(false);

  const { data: permissions } = useMyPermissions();
  const hasUpdatePermissions = permissions && permissions.includes(UPDATE_ROLE);
  const hasDeletePermissions = permissions && permissions.includes(DELETE_ROLE);

  // 1. Fetch ALL roles
  const { data: rolesData, isPending } = useQuery(useRolesListQueryOptions());

  // 2. Client-side search: Filter all roles based on search
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

  // 3. Client-side pagination: Slice the filtered roles for the current page
  const paginatedRoles = useMemo(() => {
    const startIndex = (page - 1) * PAGE_SIZE;
    const endIndex = startIndex + PAGE_SIZE;
    return filteredRoles.slice(startIndex, endIndex);
  }, [filteredRoles, page]);

  // 4. Calculate total pages based on the *filtered* list
  const totalPages = Math.ceil(filteredRoles.length / PAGE_SIZE);

  // --- Pagination Handlers ---
  const handlePreviousPage = () => {
    setPage((prev) => Math.max(1, prev - 1));
  };
  const handleNextPage = () => {
    setPage((prev) => Math.min(prev + 1, totalPages));
  };

  const handleEdit = (role: TRoleItem) => {
    setSelectedRole(role);
    setIsEditModalOpen(true);
  };
  const handleDelete = (role: TRoleItem) => {
    setSelectedRole(role);
    setIsDeleteModalOpen(true);
  };
  const handleViewPermissions = (role: TRoleItem) => {
    setSelectedRole(role);
    setIsViewModalOpen(true);
  };

  const { mutate: exportRolesToCSV, isPending: isExporting } = useMutation(
    useExportRolesToCSVMutationOptions()
  );
  const handleExport = () => {
    exportRolesToCSV(undefined, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "roles.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير الأدوار بنجاح");
      },
      onError: (error: any) => {
        toast.error("خطأ أثناء تصدير الأدوار", {
          description: error.message,
        });
      },
    });
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between gap-4 flex-wrap">
          <div>
            <CardTitle>إدارة الأدوار</CardTitle>
            <CardDescription>
              عرض وتعديل الأدوار والصلاحيات المرتبطة بها.
            </CardDescription>
          </div>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              onClick={handleExport}
              disabled={isExporting}
            >
              <Download className="ml-2 h-4 w-4" />
              {isExporting ? "جاري التصدير..." : "تصدير إلى Excel"}
            </Button>
            <div className="relative w-full max-w-sm">
            <Input
              placeholder="ابحث باسم الدور..."
              className="pr-10"
              value={searchQuery}
              onChange={(e) => {
                setSearchQuery(e.target.value);
                setPage(1); // Reset to first page on new search
              }}
            />
            <Search className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            </div>
          </div>
        </CardHeader>

        <CardContent>
          <div className="table-responsive-wrapper">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="text-center">اسم الدور</TableHead>
                  <TableHead className="text-center">الخدمة (Guard)</TableHead>
                  <TableHead className="text-center">عدد الصلاحيات</TableHead>
                  <TableHead className="text-center">تاريخ الإنشاء</TableHead>
                  <TableHead className="text-center">إجراءات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isPending ? (
                  <RolesTableSkeleton rows={5} />
                ) : paginatedRoles.length > 0 ? (
                  // 5. Render only the roles for the current page
                  paginatedRoles.map((role) => (
                    <TableRow key={role.id}>
                      <TableCell className="font-medium text-center">
                        {role.name}
                      </TableCell>
                      <TableCell className="text-center">
                        {ROLES[role.guard_name].label}
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
                      <TableCell className="flex items-center justify-center">
                        <div className="flex items-center gap-2">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleEdit(role)}
                            title="تعديل"
                            disabled={!hasUpdatePermissions}
                          >
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="destructive"
                            size="icon"
                            onClick={() => handleDelete(role)}
                            title="حذف"
                            disabled={!hasDeletePermissions}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
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
          <div className="text-sm text-muted-foreground">
            إجمالي الأدوار: {filteredRoles.length}
          </div>
          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationNext
                  href="#"
                  onClick={(e) => {
                    e.preventDefault();
                    handlePreviousPage();
                  }}
                  aria-disabled={page === 1}
                  className={page === 1 ? "pointer-events-none opacity-50" : ""}
                />
              </PaginationItem>
              <PaginationItem className="font-medium">
                صفحة {page} من {totalPages || 1}
              </PaginationItem>
              <PaginationItem>
                <PaginationPrevious
                  href="#"
                  onClick={(e) => {
                    e.preventDefault();
                    handleNextPage();
                  }}
                  aria-disabled={page === totalPages || isPending}
                  className={
                    page === totalPages || isPending
                      ? "pointer-events-none opacity-50"
                      : ""
                  }
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </CardFooter>
      </Card>

      <EditRoleModal
        role={selectedRole}
        open={isEditModalOpen}
        onOpenChange={setIsEditModalOpen}
      />

      <DeleteRoleModal
        role={selectedRole}
        open={isDeleteModalOpen}
        onOpenChange={setIsDeleteModalOpen}
      />

      <ViewPermissionsModal
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
      <TableCell>
        <div className="flex items-center gap-2">
          <Skeleton className="h-8 w-8 rounded-md" />
          <Skeleton className="h-8 w-8 rounded-md" />
        </div>
      </TableCell>
    </TableRow>
  ));
}

export default ListRoles;
