import {
  TworkshopCloth,
  TWorkshopClothStatus,
} from "@/api/v2/workshop/workshop.types";
import { useGetWorkshopClothsQueryOptions } from "@/api/v2/workshop/workshops.hooks";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { formatDate } from "@/utils/formatDate";
import { useQuery } from "@tanstack/react-query";
import { History, MoreHorizontal, RotateCcw } from "lucide-react";
import { useState } from "react";
import { Link, useParams } from "react-router";
import { UpdateWorkshopClothStatusModal } from "./UpdateWorkshopClothStatusModal";
import { ReturnWorkshopClothModal } from "./ReturnWorkshopClothModal";
import { WorkshopClothHistoryModal } from "./WorkshopClothHistoryModal";

const getStatusLabel = (status: TworkshopCloth["workshop_status"]) => {
  const labels: Record<TworkshopCloth["workshop_status"], string> = {
    processing: "قيد المعالجة",
    received: "تم الاستلام",
    ready_for_delivery: "جاهز للتسليم",
  };
  return labels[status] || status;
};

const getStatusVariant = (status: TworkshopCloth["workshop_status"]) => {
  switch (status) {
    case "processing":
      return "bg-yellow-500 text-white hover:bg-yellow-500/80";
    case "received":
      return "bg-blue-500 text-white hover:bg-blue-500/80";
    case "ready_for_delivery":
      return "bg-green-600 text-white hover:bg-green-600/80";
    default:
      return "bg-gray-500 text-white hover:bg-gray-500/80";
  }
};

function WorkshopClothsTableSkeleton({ rows = 5 }: { rows?: number }) {
  return Array.from({ length: rows }).map((_, index) => (
    <TableRow key={index}>
      <TableCell>
        <Skeleton className="h-5 w-8" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-24" />
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
        <Skeleton className="h-5 w-32" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-8 w-8" />
      </TableCell>
    </TableRow>
  ));
}

function WorkshopDetails() {
  const { id } = useParams<{ id: string }>();
  const workshopId = id ? parseInt(id, 10) : 0;
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState<
    TWorkshopClothStatus | undefined
  >(undefined);
  const [selectedCloth, setSelectedCloth] = useState<TworkshopCloth | null>(
    null
  );
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedReturnCloth, setSelectedReturnCloth] =
    useState<TworkshopCloth | null>(null);
  const [isReturnModalOpen, setIsReturnModalOpen] = useState(false);
  const [selectedHistoryCloth, setSelectedHistoryCloth] =
    useState<TworkshopCloth | null>(null);
  const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);

  const { data, isPending, isError, error } = useQuery({
    ...useGetWorkshopClothsQueryOptions(page, 10, workshopId, statusFilter),
    enabled: !!workshopId,
  });

  const handlePreviousPage = () => {
    setPage((prev) => Math.max(1, prev - 1));
  };

  const handleNextPage = () => {
    setPage((prev) => Math.min(prev + 1, data?.total_pages || 1));
  };

  const handleStatusFilterChange = (value: string) => {
    if (value === "all") {
      setStatusFilter(undefined);
    } else {
      setStatusFilter(value as TWorkshopClothStatus);
    }
    setPage(1); // Reset to first page when filter changes
  };

  const handleOpenModal = (cloth: TworkshopCloth) => {
    setSelectedCloth(cloth);
    setIsModalOpen(true);
  };

  const handleOpenReturnModal = (cloth: TworkshopCloth) => {
    setSelectedReturnCloth(cloth);
    setIsReturnModalOpen(true);
  };

  const handleOpenHistoryModal = (cloth: TworkshopCloth) => {
    setSelectedHistoryCloth(cloth);
    setIsHistoryModalOpen(true);
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>منتجات الورشة رقم {workshopId}</CardTitle>
            <CardDescription>عرض وتحديث حالة منتجات الورشة.</CardDescription>
          </div>
          <Button variant="outline">
            <Link
              to={`/workshop/${workshopId}/transfers`}
              className="flex items-center gap-2"
            >
              عرض طلبات النقل الواردة للورشة
            </Link>
          </Button>
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
            {/* Status Filter */}
            <div className="mb-4 flex items-center gap-4">
              <label className="text-sm font-medium">تصفية حسب الحالة:</label>
              <Select
                value={statusFilter || "all"}
                onValueChange={handleStatusFilterChange}
              >
                <SelectTrigger className="w-48">
                  <SelectValue placeholder="جميع الحالات" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">جميع الحالات</SelectItem>
                  <SelectItem value="processing">قيد المعالجة</SelectItem>
                  <SelectItem value="received">تم الاستلام</SelectItem>
                  <SelectItem value="ready_for_delivery">
                    جاهز للتسليم
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="table-responsive-wrapper">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="text-center">#</TableHead>
                    <TableHead className="text-center">الكود</TableHead>
                    <TableHead className="text-center">الاسم</TableHead>
                    <TableHead className="text-center">الحالة</TableHead>
                    <TableHead className="text-center">الملاحظات</TableHead>
                    <TableHead className="text-center">
                      تاريخ الاستلام
                    </TableHead>
                    <TableHead className="text-center">إجراءات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {isPending ? (
                    <WorkshopClothsTableSkeleton rows={5} />
                  ) : data?.data && data.data.length > 0 ? (
                    data.data.map((cloth: TworkshopCloth, index: number) => (
                      <TableRow key={cloth.id}>
                        <TableCell className="text-center font-medium">
                          {(page - 1) * 10 + index + 1}
                        </TableCell>
                        <TableCell className="text-center">
                          {cloth.code}
                        </TableCell>
                        <TableCell className="text-center">
                          {cloth.name ?? cloth.code}
                        </TableCell>
                        <TableCell className="text-center">
                          <Badge
                            variant="secondary"
                            className={getStatusVariant(cloth.workshop_status)}
                          >
                            {getStatusLabel(cloth.workshop_status)}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-center">
                          <div className="max-w-[200px] mx-auto text-wrap">
                            {cloth.workshop_notes || "-"}
                          </div>
                        </TableCell>
                        <TableCell className="text-center">
                          {formatDate(cloth.received_at)}
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2 justify-center">
                            <Button
                              variant="ghost"
                              size="icon"
                              title="تحديث الحالة"
                              onClick={() => handleOpenModal(cloth)}
                            >
                              <MoreHorizontal className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              title="إرجاع المنتج"
                              onClick={() => handleOpenReturnModal(cloth)}
                            >
                              <RotateCcw className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              title="عرض تاريخ المنتج"
                              onClick={() => handleOpenHistoryModal(cloth)}
                            >
                              <History className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell
                        colSpan={7}
                        className="py-10 text-center text-muted-foreground"
                      >
                        لا توجد منتجات لعرضها.
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
              إجمالي المنتجات: {data.total || 0}
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

      {/* Update Cloth Status Modal */}
      <UpdateWorkshopClothStatusModal
        open={isModalOpen}
        onOpenChange={setIsModalOpen}
        cloth={selectedCloth}
        workshopId={workshopId}
      />

      {/* Return Cloth Modal */}
      <ReturnWorkshopClothModal
        open={isReturnModalOpen}
        onOpenChange={setIsReturnModalOpen}
        cloth={selectedReturnCloth}
        workshopId={workshopId}
      />

      {/* History Modal */}
      <WorkshopClothHistoryModal
        open={isHistoryModalOpen}
        onOpenChange={setIsHistoryModalOpen}
        cloth={selectedHistoryCloth}
        workshopId={workshopId}
      />
    </div>
  );
}

export default WorkshopDetails;
