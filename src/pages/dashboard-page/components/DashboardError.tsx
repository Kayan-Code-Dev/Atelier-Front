import { Card, CardContent } from "@/components/ui/card";

type DashboardErrorProps = {
  message?: string;
};

export function DashboardError({ message }: DashboardErrorProps) {
  return (
    <Card className="rounded-2xl border-destructive/30 bg-destructive/5">
      <CardContent className="py-8 text-center">
        <p className="font-medium text-destructive">
          حدث خطأ أثناء تحميل بيانات لوحة التحكم
        </p>
        {message && <p className="mt-2 text-sm text-muted-foreground">{message}</p>}
      </CardContent>
    </Card>
  );
}
