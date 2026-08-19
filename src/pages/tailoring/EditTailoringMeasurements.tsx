import { useEffect, useState } from "react";
import { useParams, useNavigate, Link } from "react-router";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Ruler, ChevronRight, ArrowLeft } from "lucide-react";
import { Skeleton } from "@/components/ui/skeleton";
import {
  usePatchTailoringOrderMeasurementsMutation,
  useTailoringOrderQuery,
} from "@/api/v2/tailoring-orders/tailoringOrders.hooks";

export default function EditTailoringMeasurements() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const orderId = id && /^\d+$/.test(id) && Number(id) > 0 ? Number(id) : null;

  const { data: resource, isPending } = useTailoringOrderQuery(orderId);
  const patchMut = usePatchTailoringOrderMeasurementsMutation();
  const [measurementsText, setMeasurementsText] = useState("");

  useEffect(() => {
    const m = resource?.measurements;
    if (m && typeof m === "object" && Object.keys(m).length > 0) {
      const lines = Object.entries(m).map(([k, v]) => {
        const s = v === null || v === undefined ? "" : String(v);
        return `${k}: ${s}`;
      });
      setMeasurementsText(lines.join("\n"));
    } else {
      setMeasurementsText("");
    }
  }, [resource]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!orderId) return;
    const measurements: Record<string, string> = {};
    measurementsText
      .trim()
      .split("\n")
      .forEach((line) => {
        const [key, ...rest] = line.split(/[:\-=]/);
        const val = rest.join(":").trim();
        if (key?.trim() && val) measurements[key.trim()] = val;
      });
    patchMut.mutate(
      { id: orderId, body: { measurements } },
      {
        onSuccess: () => {
          toast.success("تم تحديث المقاسات");
          navigate(`/tailoring/orders/${orderId}`);
        },
      },
    );
  };

  if (!orderId) {
    return (
      <div className="p-6 text-center text-muted-foreground">
        معرّف الطلب غير صالح
      </div>
    );
  }

  if (isPending || !resource) {
    return (
      <div dir="rtl" className="min-h-0 w-full flex-1">
        <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
          <Skeleton className="h-32 w-full rounded-xl" />
        </div>
      </div>
    );
  }

  return (
    <div dir="rtl" className="min-h-0 w-full flex-1">
      <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <nav className="flex items-center gap-2 text-sm">
          <Link
            to="/tailoring/orders"
            className="text-slate-500 dark:text-slate-400 hover:text-emerald-600 transition-colors font-medium"
          >
            طلبات التفصيل
          </Link>
          <ChevronRight className="h-4 w-4 text-slate-400" />
          <Link
            to={`/tailoring/orders/${orderId}`}
            className="text-slate-500 dark:text-slate-400 hover:text-emerald-600 transition-colors font-medium"
          >
            الطلب #{orderId}
          </Link>
          <ChevronRight className="h-4 w-4 text-slate-400" />
          <span className="text-slate-900 dark:text-slate-100 font-semibold">
            تعديل المقاسات
          </span>
        </nav>

        <Card className="overflow-hidden rounded-xl border shadow-sm max-w-2xl">
          <CardHeader className="border-b bg-muted/20 px-6 py-5">
            <div className="flex items-center gap-3">
              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10">
                <Ruler className="h-6 w-6 text-emerald-600" />
              </div>
              <div>
                <CardTitle className="text-lg">تعديل المقاسات</CardTitle>
                <CardDescription>
                  أدخل المقاسات بصيغة: الاسم: القيمة (سطر لكل مقاس). القيم تُرسل كنصوص كما يتوقع الـ API.
                </CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent className="p-6">
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>المقاسات</Label>
                <Textarea
                  value={measurementsText}
                  onChange={(e) => setMeasurementsText(e.target.value)}
                  placeholder={"مثال:\nالصدر: 100\nالوسط: 85\nالكم: 60"}
                  rows={10}
                  className="font-mono"
                />
              </div>
              <div className="flex gap-3">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => navigate(`/tailoring/orders/${orderId}`)}
                  className="gap-2"
                >
                  <ArrowLeft className="h-4 w-4" />
                  إلغاء
                </Button>
                <Button
                  type="submit"
                  disabled={patchMut.isPending}
                  className="gap-2 bg-emerald-500 hover:bg-emerald-600"
                >
                  حفظ المقاسات
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
