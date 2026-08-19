import { useState } from "react";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import {
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardFooter,
  CardDescription,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Pencil, Trash2, Plus, Ban, CheckCircle } from "lucide-react";

// Import Modals
import { CreateBranchManagerModal } from "./CreateBranchManagerModal";
import { EditBranchManagerModal } from "./EditBranchManagerModal";
import { DeleteBranchManagerModal } from "./DeleteBranchManagerModal";
import { BlockBranchManagerModal } from "./BlockBranchManagerModal";

// Import Types and Hooks
import { TBranchManager } from "@/api/hr/branch-managers/branches/branch-manager.types";
import { useGetBranchManagersQueryOptions } from "@/api/hr/branch-managers/branches/branch-manager.hooks";
import { useQuery } from "@tanstack/react-query";
import ManagersTableSkeleton from "./ManagersTableSkeleton";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import {
  CREATE_BRANCH,
  DELETE_BRANCH,
  UPDATE_BRANCH,
  BLOCKED_BRANCH,
} from "@/lib/permissions.helper";
import CustomPagination from "@/components/custom/CustomPagination";
import { useSearchParams } from "react-router";
import { formatPhone } from "@/utils/formatPhone";

function Branches() {
  const [searchParams] = useSearchParams();
  const page = Number(searchParams.get("page"));
  // Modal State
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isBlockModalOpen, setIsBlockModalOpen] = useState(false);
  const [selectedManager, setSelectedManager] = useState<TBranchManager | null>(
    null
  );

  const { data: permissions } = useMyPermissions();

  const hasCreatePermissions =
    permissions && permissions.includes(CREATE_BRANCH);
  const hasDeletePermissions =
    permissions && permissions.includes(DELETE_BRANCH);
  const hasUpdatePermissions =
    permissions && permissions.includes(UPDATE_BRANCH);
  const hasBlockPermissions =
    permissions && permissions.includes(BLOCKED_BRANCH);

  // Data Fetching
  const { data, isPending } = useQuery(useGetBranchManagersQueryOptions(page));

  // --- Modal Action Handlers ---
  const handleOpenEdit = (manager: TBranchManager) => {
    setSelectedManager(manager);
    setIsEditModalOpen(true);
  };
  const handleOpenDelete = (manager: TBranchManager) => {
    setSelectedManager(manager);
    setIsDeleteModalOpen(true);
  };
  const handleOpenBlock = (manager: TBranchManager) => {
    setSelectedManager(manager);
    setIsBlockModalOpen(true);
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>إدارة مدراء الفروع</CardTitle>
            <CardDescription>
              عرض وتعديل وإنشاء حسابات مدراء الفروع.
            </CardDescription>
          </div>
          <Button
            className="bg-main-gold"
            onClick={() => setIsCreateModalOpen(true)}
            disabled={!hasCreatePermissions}
          >
            <Plus className="ml-2 h-4 w-4" />
            إنشاء مدير فرع
          </Button>
        </CardHeader>

        <CardContent>
          <div className="table-responsive-wrapper">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="text-center">الاسم</TableHead>
                  <TableHead className="text-center">
                    البريد الإلكتروني
                  </TableHead>
                  <TableHead className="text-center">رقم الهاتف</TableHead>
                  <TableHead className="text-center">الموقع</TableHead>
                  <TableHead className="text-center">الحالة</TableHead>
                  <TableHead className="text-center">إجراءات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isPending ? (
                  <ManagersTableSkeleton rows={5} />
                ) : data && data.data.length > 0 ? (
                  data.data.map((manager) => (
                    <TableRow key={manager.uuid}>
                      <TableCell className="font-medium text-center">
                        {manager.name}
                      </TableCell>
                      <TableCell className="text-center">
                        {manager.email}
                      </TableCell>
                      <TableCell className="text-center" dir="ltr">
                        {formatPhone(manager.phone, "-")}
                      </TableCell>
                      <TableCell className="text-center">
                        {manager.location}
                      </TableCell>
                      <TableCell className="text-center">
                        <Badge
                          variant={manager.blocked ? "destructive" : "default"}
                          className={
                            manager.status === "active" && !manager.blocked
                              ? "bg-green-600 text-white"
                              : ""
                          }
                        >
                          {manager.blocked
                            ? "محظور"
                            : manager.status === "active"
                              ? "نشط"
                              : "غير نشط"}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2 justify-center">
                          {/* Edit Button */}
                          <Button
                            variant="ghost"
                            size="icon"
                            title="تعديل"
                            onClick={() => handleOpenEdit(manager)}
                            disabled={!hasUpdatePermissions}
                          >
                            <Pencil className="h-4 w-4" />
                          </Button>

                          {/* Block/Unblock Button */}
                          <Button
                            variant={
                              manager.blocked ? "default" : "destructive"
                            }
                            size="icon"
                            title={manager.blocked ? "إلغاء الحظر" : "حظر"}
                            onClick={() => handleOpenBlock(manager)}
                            disabled={!hasBlockPermissions}
                          >
                            {manager.blocked ? (
                              <CheckCircle className="h-4 w-4" />
                            ) : (
                              <Ban className="h-4 w-4" />
                            )}
                          </Button>

                          {/* Delete Button */}
                          <Button
                            variant="destructive"
                            size="icon"
                            title="حذف"
                            onClick={() => handleOpenDelete(manager)}
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
                      colSpan={6}
                      className="py-10 text-center text-muted-foreground"
                    >
                      لا يوجد مدراء فروع لعرضهم.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>

        <CardFooter>
          <CustomPagination totalElementsLabel="إجمالي المدراء" totalElements={data?.total} totalPages={data?.total_pages} isLoading={isPending} />
        </CardFooter>
      </Card>

      {/* --- Modals --- */}
      <CreateBranchManagerModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
      <EditBranchManagerModal
        manager={selectedManager}
        open={isEditModalOpen}
        onOpenChange={setIsEditModalOpen}
      />
      <BlockBranchManagerModal
        manager={selectedManager}
        open={isBlockModalOpen}
        onOpenChange={setIsBlockModalOpen}
      />
      <DeleteBranchManagerModal
        manager={selectedManager}
        open={isDeleteModalOpen}
        onOpenChange={setIsDeleteModalOpen}
      />

      
    </div>
  );
}

export default Branches;
