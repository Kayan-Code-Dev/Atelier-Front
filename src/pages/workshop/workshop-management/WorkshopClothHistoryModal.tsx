import { useQuery } from "@tanstack/react-query";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import { TworkshopCloth } from "@/api/v2/workshop/workshop.types";
import {
  TWorkshopClothHistoryAction,
  TWorkshopClothStatus,
} from "@/api/v2/workshop/workshop.types";
import { useGetWorkshopClothHistoryQueryOptions } from "@/api/v2/workshop/workshops.hooks";
import { formatDate } from "@/utils/formatDate";

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  cloth: TworkshopCloth | null;
  workshopId: number;
};

const getStatusLabel = (status: TWorkshopClothStatus) => {
  const labels: Record<TWorkshopClothStatus, string> = {
    processing: "قيد المعالجة",
    received: "تم الاستلام",
    ready_for_delivery: "جاهز للتسليم",
  };
  return labels[status] || status;
};

const getStatusVariant = (status: TWorkshopClothStatus) => {
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

const getActionLabel = (action: TWorkshopClothHistoryAction) => {
  const labels: Record<TWorkshopClothHistoryAction, string> = {
    received: "تم الاستلام",
    status_changed: "تغيير الحالة",
    returned: "تم الإرجاع",
  };
  return labels[action] || action;
};

export function WorkshopClothHistoryModal({
  open,
  onOpenChange,
  cloth,
  workshopId,
}: Props) {
  const { data, isPending, isError, error } = useQuery({
    ...useGetWorkshopClothHistoryQueryOptions(
      workshopId,
      cloth?.id || 0
    ),
    enabled: open && !!cloth?.id && !!workshopId,
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-4xl">
        <DialogHeader>
          <DialogTitle className="text-center">تاريخ المنتج</DialogTitle>
          <DialogDescription className="text-center">
            {cloth
              ? `تاريخ المنتج: ${cloth.code}`
              : "عرض تاريخ المنتج"}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6 mt-4">
          {isPending ? (
            <div className="space-y-4">
              <Skeleton className="h-6 w-full" />
              <Skeleton className="h-6 w-full" />
              <Skeleton className="h-6 w-full" />
            </div>
          ) : isError ? (
            <div className="flex items-center justify-center py-8">
              <p className="text-red-500">
                حدث خطأ أثناء تحميل البيانات. الرجاء المحاولة مرة أخرى.
              </p>
              {error && <p className="text-red-500">{error.message}</p>}
            </div>
          ) : data ? (
            <>
              {/* Cloth Info */}
              <div className="grid grid-cols-2 gap-4 p-4 bg-muted rounded-lg">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">
                    الكود:
                  </p>
                  <p className="text-base font-medium">{data.cloth.code}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">
                    الكود / الاسم:
                  </p>
                  <p className="text-base font-medium">{data.cloth.code}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">
                    الحالة الحالية:
                  </p>
                  <Badge
                    variant="secondary"
                    className={getStatusVariant(data.current_status)}
                  >
                    {getStatusLabel(data.current_status)}
                  </Badge>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">
                    في الورشة:
                  </p>
                  <p className="text-base font-medium">
                    {data.is_in_workshop ? "نعم" : "لا"}
                  </p>
                </div>
              </div>

              {/* History Table */}
              <div>
                <h3 className="text-lg font-semibold mb-4">سجل الأحداث</h3>
                {data.history && data.history.length > 0 ? (
                  <div className="table-responsive-wrapper">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead className="text-center">#</TableHead>
                          <TableHead className="text-center">الإجراء</TableHead>
                          <TableHead className="text-center">الحالة</TableHead>
                          <TableHead className="text-center">الملاحظات</TableHead>
                          <TableHead className="text-center">المستخدم</TableHead>
                          <TableHead className="text-center">التاريخ</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {data.history.map((item, index) => (
                          <TableRow key={item.id}>
                            <TableCell className="text-center font-medium">
                              {data.history.length - index}
                            </TableCell>
                            <TableCell className="text-center">
                              {getActionLabel(item.action)}
                            </TableCell>
                            <TableCell className="text-center">
                              <Badge
                                variant="secondary"
                                className={getStatusVariant(item.cloth_status)}
                              >
                                {getStatusLabel(item.cloth_status)}
                              </Badge>
                            </TableCell>
                            <TableCell className="text-center">
                              <div className="max-w-[200px] mx-auto text-wrap">
                                {item.notes || "-"}
                              </div>
                            </TableCell>
                            <TableCell className="text-center">
                              {item.user?.name || "-"}
                            </TableCell>
                            <TableCell className="text-center">
                              {formatDate(item.created_at)}
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                ) : (
                  <div className="py-10 text-center text-muted-foreground">
                    لا توجد سجلات تاريخية لعرضها.
                  </div>
                )}
              </div>
            </>
          ) : (
            <div className="py-10 text-center text-muted-foreground">
              لا توجد بيانات لعرضها.
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}

