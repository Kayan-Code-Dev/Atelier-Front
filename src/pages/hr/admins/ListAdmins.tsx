import { useState } from "react";
// Shadcn Components
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
import { Pencil, Trash2, Plus, CheckCircle, Ban } from "lucide-react";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import { EditAdminModal } from "./EditAdminModal";
import { CreateAdminModal } from "./CreateAdminModal";
import { TAdmin } from "@/api/hr/admins/admins.types";
import { useBlockAdminMutationOptions, useDeleteAdminMutationOptions, useGetAdminsQueryOptions } from "@/api/hr/admins/admins.hooks";
import { useMyPermissions } from "@/api/auth/auth.hooks";
import { useMutation, useQuery } from "@tanstack/react-query";
import TableSkeleton from "@/components/custom/TableSkeleton";
import CustomPagination from "@/components/custom/CustomPagination";
import { useSearchParams } from "react-router";
import { toast } from "sonner";
import { ConfirmationModal } from "@/components/custom/ConfirmationModal";
import { formatPhone } from "@/utils/formatPhone";

const headerText = [
  "المشرف",
  "رقم الهاتف",
  "رقم الهوية",
  "الموقع",
  "الحالة",
  "إجراءات",
]

function ListAdmins() {
  const [searchParams] = useSearchParams();
  const page = Number(searchParams.get("page"));  // Modal State
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [selectedAdmin, setSelectedAdmin] = useState<TAdmin | null>(null);

  const { data: permissions } = useMyPermissions();
  const { mutate: deleteAdmin, isPending: isDeleting } = useMutation(useDeleteAdminMutationOptions());

  // Data Fetching
  const { data, isLoading } = useQuery(useGetAdminsQueryOptions(page));
  const { mutate: blockManager, isPending: isBlocking } = useMutation(useBlockAdminMutationOptions());

  const handleBlock = (onClose: () => void, admin: TAdmin) => {
    if (!admin) return;
    blockManager(admin.uuid, {
      onSuccess: () => {
        toast.success(
          `تم ${admin.blocked ? "إلغاء" : "حظر"} المشرف ${admin.name
          } بنجاح.`
        );
        onClose();
      },
      onError: () => {
        toast.error("حدث خطأ ما أثناء حظر المشرف. الرجاء المحاولة مرة أخرى.");
      },
    });
  };

  // --- Modal Action Handlers ---
  const handleOpenEdit = (admin: TAdmin) => {
    setSelectedAdmin(admin);
    setIsEditModalOpen(true);
  };

  const handleDelete = (onCloseModal: () => void, id: string) => {
    deleteAdmin(id, {
      onSuccess: () => {
        toast.success("تم حذف المشرف بنجاح");
        onCloseModal?.();
      },
      onError: () => {
        toast.error("حدث خطأ أثناء حذف المشرف");
      },
    });
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>إدارة المشرفين</CardTitle>
            <CardDescription>
              عرض وتعديل وإنشاء حسابات المشرفين في النظام.
            </CardDescription>
          </div>
          <Button onClick={() => setIsCreateModalOpen(true)} disabled={!permissions?.includes("Create-Admin")} className="bg-main-gold hover:bg-main-gold/90">
            <Plus className="ml-2 h-4 w-4" />
            إنشاء مشرف جديد
          </Button>
        </CardHeader>

        <CardContent>
          <div className="table-responsive-wrapper">
            <Table>
              <TableHeader>
                <TableRow>
                  {headerText.map((text) => <TableHead key={text} className="text-start">{text}</TableHead>)}
                </TableRow>
              </TableHeader>
              <TableBody>
                {isLoading ? (
                  <TableSkeleton rows={5} cols={4} />
                ) : data && data.data.length > 0 ? (
                  data.data.map((admin) => (
                    <TableRow key={admin.uuid} className="border-b border-[#e1e2e9]">
                      <TableCell >
                        <div className="flex items-center gap-3">
                          <Avatar>
                            <AvatarImage src={admin.image} alt={admin.name} />
                            <AvatarFallback>
                              {admin.name.substring(0, 2)}
                            </AvatarFallback>
                          </Avatar>
                          <div className="font-medium">
                            <div>{admin.name}</div>
                            <div className="text-sm text-muted-foreground">
                              {admin.email}
                            </div>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell className="[direction:ltr] text-right">{formatPhone(admin.phone, "-")}</TableCell>
                      <TableCell className="text-start">{admin.id_number}</TableCell>
                      <TableCell className="text-start">
                        {admin.city}, {admin.country}
                      </TableCell>
                      <TableCell className="text-start">
                        <Badge
                          variant={admin.blocked ? "destructive" : "default"}
                          className={
                            admin.status === "active" && !admin.blocked
                              ? "bg-green-600 text-white"
                              : ""
                          }
                        >
                          {admin.blocked
                            ? "محظور"
                            : admin.status === "active"
                              ? "نشط"
                              : "غير نشط"}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Button
                            variant="ghost"
                            size="icon"
                            title="تعديل"
                            onClick={() => handleOpenEdit(admin)}
                            disabled={!permissions?.includes("Update-Admin")}
                          >
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <ConfirmationModal
                            alertTitle={admin.blocked ? "إلغاء حظر المشرف" : "حظر المشرف"}
                            alertMessage={<>
                              هل أنت متأكد أنك تريد {admin.blocked ? "إلغاء حظر" : "حظر"} المشرف{" "}
                              <strong>{admin?.name}</strong>؟
                            </>}
                            pendingLabel={admin.blocked ? "جاري الإلغاء..." : "جاري الحظر..."}
                            confirmLabel={admin.blocked ? "تأكيد الإلغاء" : "تأكيد الحظر"}
                            handleConfirmation={(onCloseModal) => handleBlock(onCloseModal, admin)}
                            isPending={isBlocking}
                          >
                            <Button
                              variant={
                                admin.blocked ? "default" : "destructive"
                              }
                              size="icon"
                              title={admin.blocked ? "إلغاء الحظر" : "حظر"}
                            >
                              {admin.blocked ? (
                                <CheckCircle className="h-4 w-4" />
                              ) : (
                                <Ban className="h-4 w-4" />
                              )}
                            </Button>
                          </ConfirmationModal>
                          <ConfirmationModal
                            alertTitle="هل أنت متأكد من حذف هذا المشرف؟"
                            alertMessage={<>سيتم حذف المشرف {<strong>{admin?.name}</strong>}.</>}
                            confirmLabel="تأكيد الحذف"
                            pendingLabel="جاري الحذف..."
                            handleConfirmation={(onCloseModal) => handleDelete(onCloseModal, admin.uuid)}
                            isPending={isDeleting}
                          >
                            <Button
                              variant="destructive"
                              size="icon"
                              title="حذف"
                              disabled={!permissions?.includes("Delete-Admin")}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </ConfirmationModal>
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
                      لا يوجد مشرفين لعرضهم.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>

        <CardFooter>
          <CustomPagination totalElementsLabel="إجمالي المشرفين" totalElements={data?.total} totalPages={data?.total_pages} isLoading={isLoading} />
        </CardFooter>
      </Card>

      {/* --- Modals --- */}
      <CreateAdminModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
      <EditAdminModal
        admin={selectedAdmin}
        open={isEditModalOpen}
        onOpenChange={setIsEditModalOpen}
      />
    </div>
  );
}

export default ListAdmins;
