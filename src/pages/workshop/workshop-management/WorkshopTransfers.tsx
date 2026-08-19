import { useState } from "react";
import { useParams } from "react-router";
import { useQuery } from "@tanstack/react-query";
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
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  CardFooter,
} from "@/components/ui/card";
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Check } from "lucide-react";
import { useGetWorkshopTransfersQueryOptions } from "@/api/v2/workshop/workshops.hooks";
import { TWorkshopTransfer } from "@/api/v2/workshop/workshop.types";
import { TTransferClothesStatus } from "@/api/v2/clothes/transfer-clothes/transfer-clothes.types";
import { formatDate } from "@/utils/formatDate";
import { ApproveWorkshopTransferModal } from "./ApproveWorkshopTransferModal";

const getStatusVariant = (status: TTransferClothesStatus) => {
  switch (status) {
    case "approved":
      return "bg-green-600 text-white hover:bg-green-600/80";
    case "rejected":
      return "bg-destructive text-destructive-foreground hover:bg-destructive/90";
    case "partially_approved":
      return "bg-green-600 text-white hover:bg-green-600/80";
    case "pending":
    case "partially_pending":
    default:
      return "bg-yellow-500 text-white hover:bg-yellow-500/80";
  }
};

const getStatusLabel = (status: TTransferClothesStatus) => {
  const labels: Record<TTransferClothesStatus, string> = {
    pending: "قيد الانتظار",
    approved: "تم القبول",
    rejected: "تم الرفض",
    partially_pending: "قيد الانتظار (جزئي)",
    partially_approved: "تم القبول (جزئي)",
  };
  return labels[status] || status;
};

function WorkshopTransfersTableSkeleton({ rows = 5 }: { rows?: number }) {
  return Array.from({ length: rows }).map((_, index) => (
    <TableRow key={index}>
      <TableCell>
        <Skeleton className="h-5 w-8" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-32" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-32" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-16" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-32" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-24" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-32" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-8 w-8" />
      </TableCell>
    </TableRow>
  ));
}

function WorkshopTransfers() {
  const { id } = useParams<{ id: string }>();
  const workshopId = id ? parseInt(id, 10) : 0;
  const [page, setPage] = useState(1);
  const per_page = 10;
  const [selectedTransfer, setSelectedTransfer] = useState<TWorkshopTransfer | null>(null);
  const [isApproveModalOpen, setIsApproveModalOpen] = useState(false);

  const { data, isPending, isError, error } = useQuery({
    ...useGetWorkshopTransfersQueryOptions(page, per_page, workshopId),
    enabled: !!workshopId,
  });

  const handlePreviousPage = () => {
    setPage((prev) => Math.max(1, prev - 1));
  };

  const handleNextPage = () => {
    setPage((prev) => Math.min(prev + 1, data?.total_pages || 1));
  };

  const handleOpenApprove = (transfer: TWorkshopTransfer) => {
    setSelectedTransfer(transfer);
    setIsApproveModalOpen(true);
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader>
          <CardTitle>طلبات النقل الواردة للورشة رقم {workshopId}</CardTitle>
          <CardDescription>
            عرض وإدارة طلبات النقل الواردة للورشة.
          </CardDescription>
        </CardHeader>

        {isError && (
          <CardContent>
            <div className="flex items-center justify-center">
              <p className="text-red-500">
                حدث خطأ أثناء تحميل البيانات. الرجاء المحاولة مرة أخرى.
              </p>
              <p className="text-red-500">{error?.message}</p>
            </div>
          </CardContent>
        )}

        {!isError && (
          <CardContent>
            <div className="table-responsive-wrapper">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="text-center">#</TableHead>
                    <TableHead className="text-center">من</TableHead>
                    <TableHead className="text-center">إلى</TableHead>
                    <TableHead className="text-center">عدد القطع</TableHead>
                    <TableHead className="text-center">تاريخ النقل</TableHead>
                    <TableHead className="text-center">الحالة</TableHead>
                    <TableHead className="text-center">تاريخ الإنشاء</TableHead>
                    <TableHead className="text-center">إجراءات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {isPending ? (
                    <WorkshopTransfersTableSkeleton rows={5} />
                  ) : data?.data && data.data.length > 0 ? (
                    data.data.map((transfer: TWorkshopTransfer, index: number) => (
                      <TableRow key={transfer.id}>
                        <TableCell className="text-center font-medium">
                          {(page - 1) * per_page + index + 1}
                        </TableCell>
                        <TableCell className="text-center">
                          {transfer.from_entity_name}
                        </TableCell>
                        <TableCell className="text-center">
                          {transfer.to_entity_name}
                        </TableCell>
                        <TableCell className="text-center">
                          {transfer.items.length}
                        </TableCell>
                        <TableCell className="text-center">
                          {formatDate(transfer.transfer_date)}
                        </TableCell>
                        <TableCell className="text-center">
                          <Badge className={getStatusVariant(transfer.status)}>
                            {getStatusLabel(transfer.status)}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-center">
                          {formatDate(transfer.created_at)}
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2 justify-center">
                            <Button
                              variant="default"
                              size="icon"
                              title="قبول"
                              className="bg-green-600 hover:bg-green-600/80"
                              onClick={() => handleOpenApprove(transfer)}
                            >
                              <Check className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell
                        colSpan={8}
                        className="py-10 text-center text-muted-foreground"
                      >
                        لا توجد طلبات نقل لعرضها.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        )}

        {data && (
          <CardFooter className="flex items-center justify-between">
            <div className="text-sm text-muted-foreground">
              إجمالي الطلبات: {data.total || 0}
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
                    className={
                      page === 1 ? "pointer-events-none opacity-50" : ""
                    }
                  />
                </PaginationItem>
                <PaginationItem className="font-medium">
                  صفحة {page} من {data.total_pages || 1}
                </PaginationItem>
                <PaginationItem>
                  <PaginationPrevious
                    href="#"
                    onClick={(e) => {
                      e.preventDefault();
                      handleNextPage();
                    }}
                    aria-disabled={page === data.total_pages || isPending}
                    className={
                      page === data.total_pages || isPending
                        ? "pointer-events-none opacity-50"
                        : ""
                    }
                  />
                </PaginationItem>
              </PaginationContent>
            </Pagination>
          </CardFooter>
        )}
      </Card>

      {/* Approve Modal */}
      <ApproveWorkshopTransferModal
        open={isApproveModalOpen}
        onOpenChange={setIsApproveModalOpen}
        transfer={selectedTransfer}
        workshopId={workshopId}
      />
    </div>
  );
}

export default WorkshopTransfers;
