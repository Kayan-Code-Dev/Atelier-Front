import { useState } from "react";
import { useParams, Link } from "react-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardDescription,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { ArrowLeft, Calendar, RefreshCw } from "lucide-react";
import {
  useGetCashboxQueryOptions,
  useRecalculateCashboxMutationOptions,
  CASHBOXES_KEY,
} from "@/api/v2/cashboxes/cashboxes.hooks";
import { CashboxDailySummaryModal } from "./CashboxDailySummaryModal";
import { toast } from "sonner";
import { formatDate } from "@/utils/formatDate";

function CashboxDetailsSkeleton() {
  return (
    <div className="space-y-4">
      <Skeleton className="h-10 w-64" />
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {Array.from({ length: 8 }).map((_, i) => (
          <div key={i} className="space-y-2">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-6 w-full" />
          </div>
        ))}
      </div>
    </div>
  );
}

function CashboxDetails() {
  const { id } = useParams<{ id: string }>();
  const cashboxId = id ? parseInt(id, 10) : 0;
  const queryClient = useQueryClient();

  const [isDailySummaryModalOpen, setIsDailySummaryModalOpen] = useState(false);

  const { data: cashbox, isPending, isError, error } = useQuery({
    ...useGetCashboxQueryOptions(cashboxId),
    enabled: !!cashboxId,
  });

  const { mutate: recalculateCashbox, isPending: isRecalculating } = useMutation(
    useRecalculateCashboxMutationOptions()
  );

  const handleRecalculate = () => {
    if (cashboxId) {
      recalculateCashbox(cashboxId, {
        onSuccess: (data) => {
          if (data) {
            toast.success("تم إعادة حساب الصندوق بنجاح", {
              description: `الفرق: ${data.difference.toLocaleString()} - الرصيد السابق: ${data.previous_balance.toLocaleString()} - الرصيد المحسوب: ${data.calculated_balance.toLocaleString()}`,
            });
          } else {
            toast.success("تم إعادة حساب الصندوق بنجاح");
          }
          queryClient.invalidateQueries({ queryKey: [CASHBOXES_KEY, cashboxId] });
          queryClient.invalidateQueries({ queryKey: [CASHBOXES_KEY] });
        },
        onError: (error: Error) => {
          toast.error("حدث خطأ أثناء إعادة حساب الصندوق", {
            description: error.message,
          });
        },
      });
    }
  };

  if (isError) {
    return (
      <div dir="rtl" className="space-y-4">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon">
            <Link to="/cashboxes">
              <ArrowLeft className="h-4 w-4" />
            </Link>
          </Button>
          <h1 className="text-2xl font-bold">تفاصيل الصندوق</h1>
        </div>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-center">
              <p className="text-red-500">
                حدث خطأ أثناء تحميل البيانات. الرجاء المحاولة مرة أخرى.
              </p>
              <p className="text-red-500">{error?.message}</p>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div dir="rtl" className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon">
            <Link to="/cashboxes">
              <ArrowLeft className="h-4 w-4" />
            </Link>
          </Button>
          <div>
            <h1 className="text-2xl font-bold">تفاصيل الصندوق</h1>
            <p className="text-muted-foreground">
              عرض جميع المعلومات المتعلقة بالصندوق
            </p>
          </div>
        </div>
        {cashbox && (
          <div className="flex gap-2">
            <Button
              variant="outline"
              onClick={() => setIsDailySummaryModalOpen(true)}
              disabled={isPending}
            >
              <Calendar className="ml-2 h-4 w-4" />
              الملخص اليومي
            </Button>
            <Button
              variant="outline"
              onClick={handleRecalculate}
              disabled={isPending || isRecalculating}
            >
              <RefreshCw
                className={`ml-2 h-4 w-4 ${isRecalculating ? "animate-spin" : ""}`}
              />
              {isRecalculating ? "جاري إعادة الحساب..." : "إعادة حساب"}
            </Button>
          </div>
        )}
      </div>

      {/* Cashbox Details Card */}
      <Card>
        <CardHeader>
          <CardTitle>معلومات الصندوق</CardTitle>
          <CardDescription>عرض تفاصيل الصندوق</CardDescription>
        </CardHeader>
        <CardContent>
          {isPending ? (
            <CashboxDetailsSkeleton />
          ) : cashbox ? (
            <div className="space-y-6">
              {/* Basic Information */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label className="text-muted-foreground">معرف الصندوق</Label>
                  <p className="font-medium">{cashbox.id}</p>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">اسم الصندوق</Label>
                  <p className="font-medium">{cashbox.name}</p>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">الفرع</Label>
                  <p className="font-medium">{cashbox.branch.name}</p>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">كود الفرع</Label>
                  <p className="font-medium">{cashbox.branch.branch_code}</p>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">الرصيد الأولي</Label>
                  <p className="font-medium text-lg">
                    {cashbox.initial_balance.toLocaleString()} ج.م
                  </p>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">الرصيد الحالي</Label>
                  <p className="font-medium text-lg text-primary">
                    {cashbox.current_balance.toLocaleString()} ج.م
                  </p>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">إيرادات اليوم</Label>
                  <p className="font-medium text-lg text-green-600">
                    {cashbox.today_summary?.income.toLocaleString()} ج.م
                  </p>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">مصروفات اليوم</Label>
                  <p className="font-medium text-lg text-red-600">
                    {cashbox.today_summary?.expense.toLocaleString()} ج.م
                  </p>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">الحالة</Label>
                  <div>
                    <Badge variant={cashbox.is_active ? "default" : "secondary"}>
                      {cashbox.is_active ? "نشط" : "غير نشط"}
                    </Badge>
                  </div>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">الوصف</Label>
                  <p className="font-medium">{cashbox.description || "-"}</p>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">تاريخ الإنشاء</Label>
                  <p className="font-medium">{formatDate(cashbox.created_at)}</p>
                </div>

                <div className="space-y-2">
                  <Label className="text-muted-foreground">آخر تحديث</Label>
                  <p className="font-medium">{formatDate(cashbox.updated_at)}</p>
                </div>
              </div>
            </div>
          ) : (
            <div className="flex items-center justify-center py-10">
              <p className="text-muted-foreground">لا توجد بيانات لعرضها</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Daily Summary Modal */}
      {cashbox && (
        <CashboxDailySummaryModal
          cashbox={cashbox}
          open={isDailySummaryModalOpen}
          onOpenChange={setIsDailySummaryModalOpen}
        />
      )}
    </div>
  );
}

export default CashboxDetails;
