import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Banknote, Minus, Plus, Printer, Wallet } from "lucide-react";
import type { TSimpleSalarySummary } from "@/api/simple-salary/simple-salary.types";
import { PAYMENT_METHOD_LABELS } from "@/api/simple-salary/simple-salary.types";
import { formatDate } from "@/utils/formatDate";
import { formatSimpleSalaryMoney } from "./utils";

export type SimpleSalarySummaryCardProps = {
  summary: TSimpleSalarySummary;
  onAddDeduction: () => void;
  onAddAddition: () => void;
  onPay: () => void;
  onPrint?: () => void;
};

export function SimpleSalarySummaryCard({
  summary,
  onAddDeduction,
  onAddAddition,
  onPay,
  onPrint,
}: SimpleSalarySummaryCardProps) {
  return (
    <div className="space-y-5">
      <Card className="overflow-hidden rounded-xl border shadow-sm">
        <CardHeader className="border-b border-border/80 bg-muted/20 px-5 py-4">
          <CardTitle className="text-base font-semibold tracking-tight">
            {summary.employee.name}
            <span className="font-mono font-normal text-muted-foreground">
              {" "}
              — {summary.employee.employee_code}
            </span>
          </CardTitle>
          <CardDescription className="text-xs">الفترة: {summary.period}</CardDescription>
        </CardHeader>
        <CardContent className="space-y-5 px-5 py-5">
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
            <div className="rounded-lg border border-border/60 bg-muted/5 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                الراتب
              </p>
              <p className="mt-1.5 text-lg font-semibold tabular-nums text-foreground">
                {formatSimpleSalaryMoney(summary.salary)}
              </p>
            </div>
            <div className="rounded-lg border border-border/60 bg-muted/5 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                إجمالي الخصومات
              </p>
              <p className="mt-1.5 text-lg font-semibold tabular-nums text-amber-600 dark:text-amber-500">
                {formatSimpleSalaryMoney(summary.total_deductions)}
              </p>
            </div>
            <div className="rounded-lg border border-border/60 bg-muted/5 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                إجمالي المكافآت
              </p>
              <p className="mt-1.5 text-lg font-semibold tabular-nums text-emerald-600 dark:text-emerald-500">
                {formatSimpleSalaryMoney(summary.total_additions ?? 0)}
              </p>
            </div>
            <div className="rounded-lg border border-border/60 bg-muted/5 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                الصافي المستحق
              </p>
              <p className="mt-1.5 text-lg font-semibold tabular-nums text-foreground">
                {formatSimpleSalaryMoney(summary.net_to_pay)}
              </p>
            </div>
            <div className="rounded-lg border border-border/60 bg-muted/5 p-4">
              <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                المدفوع
              </p>
              <p className="mt-1.5 text-lg font-semibold tabular-nums text-emerald-600 dark:text-emerald-500">
                {formatSimpleSalaryMoney(summary.total_paid)}
              </p>
            </div>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <div className="rounded-lg border border-border/60 bg-primary/5 px-4 py-2.5">
              <span className="text-sm text-muted-foreground">المتبقي للدفع: </span>
              <span className="text-sm font-bold tabular-nums text-foreground">
                {formatSimpleSalaryMoney(summary.remaining_to_pay)}
              </span>
            </div>
            {summary.fully_paid && (
              <Badge
                variant="secondary"
                className="bg-emerald-500/15 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400"
              >
                مُدفوع بالكامل
              </Badge>
            )}
          </div>

          <div className="flex flex-wrap gap-2 border-t border-border/60 pt-4">
            <Button
              variant="outline"
              size="sm"
              onClick={onAddDeduction}
              className="gap-2 focus-visible:ring-2"
            >
              <Minus className="h-4 w-4" />
              إضافة خصم
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={onAddAddition}
              className="gap-2 focus-visible:ring-2"
            >
              <Plus className="h-4 w-4" />
              إضافة مكافأة
            </Button>
            <Button
              size="sm"
              onClick={onPay}
              disabled={summary.fully_paid}
              className="gap-2 shadow-sm focus-visible:ring-2"
            >
              <Wallet className="h-4 w-4" />
              دفع الراتب / دفعة
            </Button>
            {onPrint && (
              <Button
                variant="outline"
                size="sm"
                onClick={onPrint}
                className="gap-2 focus-visible:ring-2"
              >
                <Printer className="h-4 w-4" />
                طباعة الفاتورة
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      {(summary.additions?.length ?? 0) > 0 && (
        <Card className="overflow-hidden rounded-xl border shadow-sm">
          <CardHeader className="border-b border-border/80 bg-muted/20 px-5 py-3">
            <CardTitle className="flex items-center gap-2 text-sm font-semibold">
              <Plus className="h-4 w-4 text-muted-foreground" />
              المكافآت
              <span className="font-normal text-muted-foreground">
                ({summary.additions!.length})
              </span>
            </CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <Table className="min-w-[32rem] table-fixed">
                <TableHeader>
                  <TableRow className="border-b border-border/60 bg-muted/30 hover:bg-muted/30">
                    <TableHead className="h-10 w-[7rem] shrink-0 px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      التاريخ
                    </TableHead>
                    <TableHead className="min-w-[8rem] px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      السبب
                    </TableHead>
                    <TableHead className="w-[6rem] shrink-0 px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      المبلغ
                    </TableHead>
                    <TableHead className="min-w-[6rem] px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      ملاحظات
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {summary.additions!.map((a) => (
                    <TableRow
                      key={a.id}
                      className="border-b border-border/40 transition-colors hover:bg-muted/10"
                    >
                      <TableCell className="w-[7rem] shrink-0 px-4 py-2.5 text-sm tabular-nums">
                        {formatDate(a.date)}
                      </TableCell>
                      <TableCell className="px-4 py-2.5 text-sm">
                        <span className="line-clamp-2">{a.reason}</span>
                      </TableCell>
                      <TableCell className="w-[6rem] shrink-0 px-4 py-2.5 text-sm font-medium tabular-nums">
                        {formatSimpleSalaryMoney(a.amount)}
                      </TableCell>
                      <TableCell className="px-4 py-2.5 text-sm text-muted-foreground">
                        <span className="line-clamp-2">{a.notes ?? "—"}</span>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      )}

      {summary.deductions.length > 0 && (
        <Card className="overflow-hidden rounded-xl border shadow-sm">
          <CardHeader className="border-b border-border/80 bg-muted/20 px-5 py-3">
            <CardTitle className="flex items-center gap-2 text-sm font-semibold">
              <Minus className="h-4 w-4 text-muted-foreground" />
              الخصومات
              <span className="font-normal text-muted-foreground">
                ({summary.deductions.length})
              </span>
            </CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <Table className="min-w-[32rem] table-fixed">
                <TableHeader>
                  <TableRow className="border-b border-border/60 bg-muted/30 hover:bg-muted/30">
                    <TableHead className="h-10 w-[7rem] shrink-0 px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      التاريخ
                    </TableHead>
                    <TableHead className="min-w-[8rem] px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      السبب
                    </TableHead>
                    <TableHead className="w-[6rem] shrink-0 px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      المبلغ
                    </TableHead>
                    <TableHead className="min-w-[6rem] px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      ملاحظات
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {summary.deductions.map((d) => (
                    <TableRow
                      key={d.id}
                      className="border-b border-border/40 transition-colors hover:bg-muted/10"
                    >
                      <TableCell className="w-[7rem] shrink-0 px-4 py-2.5 text-sm tabular-nums">
                        {formatDate(d.date)}
                      </TableCell>
                      <TableCell className="px-4 py-2.5 text-sm">
                        <span className="line-clamp-2">{d.reason}</span>
                      </TableCell>
                      <TableCell className="w-[6rem] shrink-0 px-4 py-2.5 text-sm font-medium tabular-nums">
                        {formatSimpleSalaryMoney(d.amount)}
                      </TableCell>
                      <TableCell className="px-4 py-2.5 text-sm text-muted-foreground">
                        <span className="line-clamp-2">{d.notes ?? "—"}</span>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      )}

      {summary.payments.length > 0 && (
        <Card className="overflow-hidden rounded-xl border shadow-sm">
          <CardHeader className="border-b border-border/80 bg-muted/20 px-5 py-3">
            <CardTitle className="flex items-center gap-2 text-sm font-semibold">
              <Banknote className="h-4 w-4 text-muted-foreground" />
              الدفعات
              <span className="font-normal text-muted-foreground">
                ({summary.payments.length})
              </span>
            </CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <Table className="min-w-[36rem] table-fixed">
                <TableHeader>
                  <TableRow className="border-b border-border/60 bg-muted/30 hover:bg-muted/30">
                    <TableHead className="h-10 w-[7rem] shrink-0 px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      التاريخ
                    </TableHead>
                    <TableHead className="w-[6rem] shrink-0 px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      المبلغ
                    </TableHead>
                    <TableHead className="min-w-[6rem] px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      طريقة الدفع
                    </TableHead>
                    <TableHead className="min-w-[6rem] px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      الصندوق
                    </TableHead>
                    <TableHead className="min-w-[5rem] px-4 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      مرجع
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {summary.payments.map((p) => (
                    <TableRow
                      key={p.id}
                      className="border-b border-border/40 transition-colors hover:bg-muted/10"
                    >
                      <TableCell className="w-[7rem] shrink-0 px-4 py-2.5 text-sm tabular-nums">
                        {formatDate(p.paid_at)}
                      </TableCell>
                      <TableCell className="w-[6rem] shrink-0 px-4 py-2.5 text-sm font-medium tabular-nums">
                        {formatSimpleSalaryMoney(p.amount)}
                      </TableCell>
                      <TableCell className="px-4 py-2.5 text-sm">
                        {PAYMENT_METHOD_LABELS[p.payment_method] ?? p.payment_method}
                      </TableCell>
                      <TableCell className="px-4 py-2.5 text-sm">
                        {p.cashbox?.name ?? "—"}
                      </TableCell>
                      <TableCell className="px-4 py-2.5 text-sm text-muted-foreground">
                        <span className="line-clamp-2">{p.payment_reference ?? "—"}</span>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
