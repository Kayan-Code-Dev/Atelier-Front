import { useState, useMemo } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import { DatePicker } from "@/components/custom/DatePicker";
import { useQuery } from "@tanstack/react-query";
import { TCashbox } from "@/api/v2/cashboxes/cashboxes.types";
import { useGetCashboxDailySummaryQueryOptions } from "@/api/v2/cashboxes/cashboxes.hooks";

type Props = {
  cashbox: TCashbox;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

function DailySummarySkeleton() {
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-4">
        {Array.from({ length: 8 }).map((_, i) => (
          <div key={i} className="space-y-2">
            <Skeleton className="h-4 w-32" />
            <Skeleton className="h-6 w-full" />
          </div>
        ))}
      </div>
    </div>
  );
}

export function CashboxDailySummaryModal({
  cashbox,
  open,
  onOpenChange,
}: Props) {
  const [selectedDate, setSelectedDate] = useState<Date | undefined>(
    new Date() // Default to today
  );

  // Format date as YYYY-MM-DD for API
  const formattedDate = useMemo(() => {
    if (!selectedDate) return "";
    const year = selectedDate.getFullYear();
    const month = String(selectedDate.getMonth() + 1).padStart(2, "0");
    const day = String(selectedDate.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }, [selectedDate]);

  const {
    data: summary,
    isPending,
    isError,
    error,
  } = useQuery({
    ...useGetCashboxDailySummaryQueryOptions(cashbox.id, formattedDate),
    enabled: open && !!formattedDate && !!cashbox.id,
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle className="text-center">
            الملخص اليومي للصندوق
          </DialogTitle>
          <DialogDescription className="text-center">
            عرض الملخص اليومي للصندوق "{cashbox.name}" لتاريخ محدد
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 mt-4">
          {/* Date Picker */}
          <div>
            <DatePicker
              value={selectedDate}
              onChange={(date) => setSelectedDate(date)}
              label="اختر التاريخ"
              placeholder="اختر التاريخ"
              showLabel={true}
              allowFutureDates={false}
              allowPastDates={true}
            />
          </div>

          {/* Summary Data */}
          {!selectedDate ? (
            <div className="flex items-center justify-center py-10 border rounded-md">
              <p className="text-muted-foreground">يرجى اختيار التاريخ أولاً</p>
            </div>
          ) : isPending ? (
            <DailySummarySkeleton />
          ) : isError ? (
            <div className="flex items-center justify-center py-10 border rounded-md">
              <p className="text-red-500">
                حدث خطأ أثناء تحميل الملخص. {error?.message}
              </p>
            </div>
          ) : summary ? (
            <div className="space-y-6">
              {/* Summary Cards */}
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="border rounded-lg p-4 space-y-2">
                  <Label className="text-muted-foreground text-sm">
                    الرصيد الافتتاحي
                  </Label>
                  <p className="font-semibold text-lg">
                    {summary.opening_balance.toLocaleString()} ج.م
                  </p>
                </div>

                <div className="border rounded-lg p-4 space-y-2 bg-green-50 dark:bg-green-950/20">
                  <Label className="text-muted-foreground text-sm">
                    إجمالي الإيرادات
                  </Label>
                  <p className="font-semibold text-lg text-green-600">
                    {summary.total_income.toLocaleString()} ج.م
                  </p>
                </div>

                <div className="border rounded-lg p-4 space-y-2 bg-red-50 dark:bg-red-950/20">
                  <Label className="text-muted-foreground text-sm">
                    إجمالي المصروفات
                  </Label>
                  <p className="font-semibold text-lg text-red-600">
                    {summary.total_expense.toLocaleString()} ج.م
                  </p>
                </div>

                <div className="border rounded-lg p-4 space-y-2 bg-primary/10">
                  <Label className="text-muted-foreground text-sm">
                    الرصيد الختامي
                  </Label>
                  <p className="font-semibold text-lg text-primary">
                    {summary.closing_balance.toLocaleString()} ج.م
                  </p>
                </div>
              </div>

              {/* Additional Information */}
              <div className="border rounded-lg p-4 space-y-4">
                <h3 className="font-semibold text-lg">تفاصيل إضافية</h3>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="space-y-1">
                    <Label className="text-muted-foreground text-sm">
                      التغيير الصافي
                    </Label>
                    <p
                      className={`font-medium ${
                        summary.net_change >= 0
                          ? "text-green-600"
                          : "text-red-600"
                      }`}
                    >
                      {summary.net_change >= 0 ? "+" : ""}
                      {summary.net_change.toLocaleString()} ج.م
                    </p>
                  </div>

                  <div className="space-y-1">
                    <Label className="text-muted-foreground text-sm">
                      عدد المعاملات
                    </Label>
                    <p className="font-medium">{summary.transaction_count}</p>
                  </div>

                  <div className="space-y-1">
                    <Label className="text-muted-foreground text-sm">
                      عدد المرتجعات
                    </Label>
                    <p className="font-medium">{summary.reversal_count}</p>
                  </div>

                  <div className="space-y-1">
                    <Label className="text-muted-foreground text-sm">
                      التاريخ
                    </Label>
                    <p className="font-medium">{summary.date}</p>
                  </div>
                </div>
              </div>

              {/* Summary Table */}
              <div className="border rounded-lg overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="text-center">المعلومات</TableHead>
                      <TableHead className="text-center">القيمة</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow>
                      <TableCell className="font-medium text-center">
                        اسم الصندوق
                      </TableCell>
                      <TableCell className="text-center">
                        {summary.cashbox_name}
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium text-center">
                        الرصيد الافتتاحي
                      </TableCell>
                      <TableCell className="text-center">
                        {summary.opening_balance.toLocaleString()} ج.م
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium text-center">
                        إجمالي الإيرادات
                      </TableCell>
                      <TableCell className="text-center text-green-600">
                        {summary.total_income.toLocaleString()} ج.م
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium text-center">
                        إجمالي المصروفات
                      </TableCell>
                      <TableCell className="text-center text-red-600">
                        {summary.total_expense.toLocaleString()} ج.م
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium text-center">
                        التغيير الصافي
                      </TableCell>
                      <TableCell
                        className={`text-center font-semibold ${
                          summary.net_change >= 0
                            ? "text-green-600"
                            : "text-red-600"
                        }`}
                      >
                        {summary.net_change >= 0 ? "+" : ""}
                        {summary.net_change.toLocaleString()} ج.م
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium text-center">
                        الرصيد الختامي
                      </TableCell>
                      <TableCell className="text-center font-semibold text-primary">
                        {summary.closing_balance.toLocaleString()} ج.م
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium text-center">
                        عدد المعاملات
                      </TableCell>
                      <TableCell className="text-center">
                        {summary.transaction_count}
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium text-center">
                        عدد المرتجعات
                      </TableCell>
                      <TableCell className="text-center">
                        {summary.reversal_count}
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </div>
          ) : (
            <div className="flex items-center justify-center py-10 border rounded-md">
              <p className="text-muted-foreground">
                لا يوجد ملخص متاح لهذا التاريخ
              </p>
            </div>
          )}
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            إغلاق
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
