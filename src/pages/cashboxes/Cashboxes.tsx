import { useState, useMemo } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationPrevious,
  PaginationNext,
} from "@/components/ui/pagination";
import {
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardFooter,
  CardDescription,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import { Download, Eye, Filter, Pencil, X } from "lucide-react";
import { Link } from "react-router";
import {
  useGetCashboxesQueryOptions,
  useExportCashboxesToExcelMutationOptions,
} from "@/api/v2/cashboxes/cashboxes.hooks";
import { TCashboxesParams, TCashbox } from "@/api/v2/cashboxes/cashboxes.types";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { EditCashboxModal } from "./EditCashboxModal";
import { CreateCashboxModal } from "./CreateCashboxModal";
import { Skeleton } from "@/components/ui/skeleton";
import { useSearchParams } from "react-router";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import { toast } from "sonner";

function CashboxesTableSkeleton({ rows = 5 }: { rows?: number }) {
  return (
    <>
      {Array.from({ length: rows }).map((_, i) => (
        <TableRow key={i}>
          <TableCell>
            <Skeleton className="h-4 w-8" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-32" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-32" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-24" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-24" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-24" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-24" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-16" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-20" />
          </TableCell>
          <TableCell>
            <div className="flex items-center gap-2 justify-center">
              <Skeleton className="h-8 w-8" />
              <Skeleton className="h-8 w-8" />
            </div>
          </TableCell>
        </TableRow>
      ))}
    </>
  );
}

function Cashboxes() {
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Number(searchParams.get("page")) || 1;
  const search = searchParams.get("search") || undefined;
  const [filters, setFilters] = useState<TCashboxesParams>({
    page: 1,
    per_page: 10,
  });

  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [selectedCashbox, setSelectedCashbox] = useState<TCashbox | null>(
    null
  );

  const [branchId, setBranchId] = useState<string>("");
  const [isActive, setIsActive] = useState<string>("");
  const [initialBalanceMin, setInitialBalanceMin] = useState<string>("");
  const [initialBalanceMax, setInitialBalanceMax] = useState<string>("");
  const [currentBalanceMin, setCurrentBalanceMin] = useState<string>("");
  const [currentBalanceMax, setCurrentBalanceMax] = useState<string>("");
  const [showFilters, setShowFilters] = useState(false);

  const queryParams: TCashboxesParams = useMemo(() => {
    const params: TCashboxesParams = {
      ...filters,
      page,
      per_page: 10,
    };
    if (branchId) params.branch_id = Number(branchId);
    if (isActive && isActive !== "all") params.is_active = isActive === "true";
    if (search?.trim()) params.search = search.trim();
    const ibMin = initialBalanceMin?.trim();
    const ibMax = initialBalanceMax?.trim();
    const cbMin = currentBalanceMin?.trim();
    const cbMax = currentBalanceMax?.trim();
    if (ibMin) params.initial_balance_min = Number(ibMin);
    if (ibMax) params.initial_balance_max = Number(ibMax);
    if (cbMin) params.current_balance_min = Number(cbMin);
    if (cbMax) params.current_balance_max = Number(cbMax);
    return params;
  }, [
    filters,
    page,
    branchId,
    isActive,
    search,
    initialBalanceMin,
    initialBalanceMax,
    currentBalanceMin,
    currentBalanceMax,
  ]);


  const { data, isPending, isError, error } = useQuery(
    useGetCashboxesQueryOptions(queryParams)
  );

  const { mutate: exportCashboxesToExcel, isPending: isExporting } =
    useMutation(useExportCashboxesToExcelMutationOptions());

  const handleExport = () => {
    exportCashboxesToExcel(queryParams, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "cashboxes.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير الصناديق بنجاح");
      },
      onError: (error: any) => {
        toast.error("خطأ أثناء تصدير الصناديق", {
          description: error.message,
        });
      },
    });
  };

  const handlePreviousPage = () => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.set("page", String(Math.max(1, page - 1)));
      return next;
    });
  };
  const handleNextPage = () => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.set("page", String(Math.min(page + 1, data?.total_pages || 1)));
      return next;
    });
  };

  const handleFilterChange = () => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.set("page", "1");
      return next;
    });
  };

  const handleClearFilters = () => {
    setBranchId("");
    setIsActive("");
    setInitialBalanceMin("");
    setInitialBalanceMax("");
    setCurrentBalanceMin("");
    setCurrentBalanceMax("");
    setFilters({ page: 1, per_page: 10 });
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.set("page", "1");
      next.delete("search");
      return next;
    });
  };

  // --- Modal Action Handlers ---
  const handleOpenEdit = (cashbox: TCashbox) => {
    setSelectedCashbox(cashbox);
    setIsEditModalOpen(true);
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle data-onboarding-target="balances_review">إدارة الصناديق</CardTitle>
            <CardDescription>
              عرض وتعديل وإدارة الصناديق في النظام.
            </CardDescription>
          </div>
          <div className="flex items-center gap-2">
            <Button
              data-onboarding-target="cashbox_setup"
              onClick={() => setIsCreateModalOpen(true)}
            >
              خزنة جديدة
            </Button>
            <Button
              variant="outline"
              onClick={() => setShowFilters((prev) => !prev)}
            >
              <Filter className="ml-2 h-4 w-4" />
              {showFilters ? "إخفاء الفلاتر" : "عرض الفلاتر"}
            </Button>
            <Button
              variant="outline"
              onClick={handleExport}
              disabled={isExporting}
            >
              <Download className="ml-2 h-4 w-4" />
              {isExporting ? "جاري التصدير..." : "تصدير إلى Excel"}
            </Button>
          </div>
        </CardHeader>

        {showFilters && (
        <CardContent className="space-y-4 border-b pb-4">
          <div className="flex flex-wrap items-center gap-4">
            {/* Branch Filter */}
            <div className="flex items-center gap-2">
              <Label htmlFor="branch_filter">الفرع:</Label>
              <BranchesSelect
                value={branchId}
                onChange={(value) => {
                  setBranchId(value || "");
                  handleFilterChange();
                }}
                disabled={isPending}
              />
            </div>

            {/* Is Active Filter */}
            <div className="flex items-center gap-2">
              <Label htmlFor="is_active_filter">الحالة:</Label>
              <Select
                value={isActive}
                onValueChange={(value) => {
                  setIsActive(value);
                  handleFilterChange();
                }}
                disabled={isPending}
              >
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="جميع الحالات" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">جميع الحالات</SelectItem>
                  <SelectItem value="true">نشط</SelectItem>
                  <SelectItem value="false">غير نشط</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {/* Balance range filters */}
            <div className="flex items-center gap-2">
              <Label>الرصيد الأولي من:</Label>
              <Input
                type="number"
                step={0.01}
                placeholder="من"
                value={initialBalanceMin}
                onChange={(e) => {
                  setInitialBalanceMin(e.target.value);
                  handleFilterChange();
                }}
                className="w-[100px]"
              />
              <Label>إلى:</Label>
              <Input
                type="number"
                step={0.01}
                placeholder="إلى"
                value={initialBalanceMax}
                onChange={(e) => {
                  setInitialBalanceMax(e.target.value);
                  handleFilterChange();
                }}
                className="w-[100px]"
              />
            </div>
            <div className="flex items-center gap-2">
              <Label>الرصيد الحالي من:</Label>
              <Input
                type="number"
                step={0.01}
                placeholder="من"
                value={currentBalanceMin}
                onChange={(e) => {
                  setCurrentBalanceMin(e.target.value);
                  handleFilterChange();
                }}
                className="w-[100px]"
              />
              <Label>إلى:</Label>
              <Input
                type="number"
                step={0.01}
                placeholder="إلى"
                value={currentBalanceMax}
                onChange={(e) => {
                  setCurrentBalanceMax(e.target.value);
                  handleFilterChange();
                }}
                className="w-[100px]"
              />
            </div>

            <Button variant="outline" size="sm" onClick={handleClearFilters}>
              <X className="ml-2 h-4 w-4" />
              مسح الفلاتر
            </Button>
          </div>
        </CardContent>
        )}

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
        <CardContent>
          {!isError && (
            <div className="table-responsive-wrapper">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="text-center">#</TableHead>
                    <TableHead className="text-center">اسم الصندوق</TableHead>
                    <TableHead className="text-center">الفرع</TableHead>
                    <TableHead className="text-center">
                      الرصيد الأولي
                    </TableHead>
                    <TableHead className="text-center">
                      الرصيد الحالي
                    </TableHead>
                    <TableHead className="text-center">إيرادات اليوم</TableHead>
                    <TableHead className="text-center">مصروفات اليوم</TableHead>
                    <TableHead className="text-center">الحالة</TableHead>
                    <TableHead className="text-center">الوصف</TableHead>
                    <TableHead className="text-center">إجراءات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {isPending ? (
                    <CashboxesTableSkeleton rows={7} />
                  ) : data && data.data.length > 0 ? (
                    data.data.map((cashbox) => (
                      <TableRow key={cashbox.id}>
                        <TableCell className="text-center">
                          {cashbox.id}
                        </TableCell>
                        <TableCell className="font-medium text-center">
                          {cashbox.name}
                        </TableCell>
                        <TableCell className="text-center">
                          {cashbox.branch?.name ?? "-"}
                        </TableCell>
                        <TableCell className="text-center">
                          {cashbox.initial_balance.toLocaleString()}
                        </TableCell>
                        <TableCell className="text-center">
                          {cashbox.current_balance.toLocaleString()}
                        </TableCell>
                        <TableCell className="text-center text-green-600">
                          {cashbox.today_income.toLocaleString()}
                        </TableCell>
                        <TableCell className="text-center text-red-600">
                          {cashbox.today_expense.toLocaleString()}
                        </TableCell>
                        <TableCell className="text-center">
                          <Badge
                            variant={
                              cashbox.is_active ? "default" : "secondary"
                            }
                          >
                            {cashbox.is_active ? "نشط" : "غير نشط"}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-center">
                          {cashbox.description || "-"}
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2 justify-center">
                            <Button
                              variant="ghost"
                              size="icon"
                              title="عرض التفاصيل"
                            >
                              <Link to={`/cashboxes/${cashbox.id}`}>
                                <Eye className="h-4 w-4" />
                              </Link>
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              title="تعديل الصندوق"
                              onClick={() => handleOpenEdit(cashbox)}
                            >
                              <Pencil className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell
                        colSpan={10}
                        className="py-10 text-center text-muted-foreground"
                      >
                        لا يوجد صناديق لعرضها.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>

        <CardFooter className="flex items-center justify-between">
          <div className="text-sm text-muted-foreground">
            إجمالي الصناديق: {data?.total || 0}
          </div>
          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationNext // RTL: Next arrow for previous page
                  href="#"
                  onClick={(e) => {
                    e.preventDefault();
                    handlePreviousPage();
                  }}
                  aria-disabled={page === 1}
                  className={page === 1 ? "pointer-events-none opacity-50" : ""}
                />
              </PaginationItem>
              <PaginationItem className="font-medium">
                صفحة {page} من {data?.total_pages || 1}
              </PaginationItem>
              <PaginationItem>
                <PaginationPrevious // RTL: Previous arrow for next page
                  href="#"
                  onClick={(e) => {
                    e.preventDefault();
                    handleNextPage();
                  }}
                  aria-disabled={page === data?.total_pages || isPending}
                  className={
                    page === data?.total_pages || isPending
                      ? "pointer-events-none opacity-50"
                      : ""
                  }
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </CardFooter>
      </Card>

      {/* --- Modals --- */}
      <EditCashboxModal
        cashbox={selectedCashbox}
        open={isEditModalOpen}
        onOpenChange={setIsEditModalOpen}
      />
      <CreateCashboxModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
    </div>
  );
}

export default Cashboxes;
