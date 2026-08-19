import { useState } from "react";

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
import { Skeleton } from "@/components/ui/skeleton";
import { Download, Pencil, Trash2, Plus } from "lucide-react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { TCurrency } from "@/api/v2/content-managment/currency/currency.types";
import {
  useCurrenciesQueryOptions,
  useExportCurrenciesToCSVMutationOptions,
} from "@/api/v2/content-managment/currency/currency.hooks";
import {
  parseFilenameFromContentDisposition,
  downloadBlob,
} from "@/api/api.utils";
import { CreateCurrencyModal } from "./CreateCurrencyModal";
import { EditCurrencyModal } from "./EditCurrencyModal";
import { DeleteCurrencyModal } from "./DeleteCurrencyModal";

function Currencies() {
  const [page, setPage] = useState(1);

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [selectedCurrency, setSelectedCurrency] = useState<TCurrency | null>(
    null
  );

  const per_page = 10;
  const { data, isPending, isError, error } = useQuery(
    useCurrenciesQueryOptions(page, per_page)
  );

  const { mutate: exportCurrenciesToCSV, isPending: isExporting } = useMutation(
    useExportCurrenciesToCSVMutationOptions()
  );

  const handlePreviousPage = () => {
    setPage((prev) => Math.max(1, prev - 1));
  };
  const handleNextPage = () => {
    setPage((prev) => Math.min(prev + 1, data?.total_pages || 1));
  };

  const handleOpenEdit = (currency: TCurrency) => {
    setSelectedCurrency(currency);
    setIsEditModalOpen(true);
  };
  const handleOpenDelete = (currency: TCurrency) => {
    setSelectedCurrency(currency);
    setIsDeleteModalOpen(true);
  };

  const handleExport = () => {
    exportCurrenciesToCSV(undefined, {
      onSuccess: (result) => {
        if (!result) return;
        const filename =
          parseFilenameFromContentDisposition(result.headers) || "currencies.xlsx";
        downloadBlob(result.data, filename);
        toast.success("تم تصدير العملات بنجاح");
      },
      onError: (err: any) => {
        toast.error("خطأ أثناء تصدير العملات. الرجاء المحاولة مرة أخرى.", {
          description: err.message,
        });
      },
    });
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>إدارة العملات</CardTitle>
            <CardDescription>
              عرض وتعديل وإنشاء العملات المستخدمة في النظام.
            </CardDescription>
          </div>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              onClick={handleExport}
              disabled={isExporting}
              className="cursor-pointer"
            >
              <Download className="ml-2 h-4 w-4" />
              {isExporting ? "جاري التصدير..." : "تصدير إلى Excel"}
            </Button>
            <Button
              onClick={() => setIsCreateModalOpen(true)}
              className="cursor-pointer"
            >
              <Plus className="ml-2 h-4 w-4" />
              إضافة عملة جديدة
            </Button>
          </div>
        </CardHeader>

        {isError && (
          <CardContent>
            <div className="flex items-center justify-center">
              <p className="text-red-500">
                حدث خطأ أثناء تحميل البيانات. الرجاء المحاولة مرة أخرى.
              </p>
              <p className="text-red-500">{(error as any)?.message}</p>
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
                    <TableHead className="text-center">اسم العملة</TableHead>
                    <TableHead className="text-center">الكود</TableHead>
                    <TableHead className="text-center">الرمز</TableHead>
                    <TableHead className="text-center">إجراءات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {isPending ? (
                    <CurrenciesTableSkeleton rows={5} />
                  ) : data && data.data.length > 0 ? (
                    data.data.map((currency) => (
                      <TableRow key={currency.id}>
                        <TableCell className="text-center">
                          {currency.id}
                        </TableCell>
                        <TableCell className="font-medium text-center">
                          {currency.name}
                        </TableCell>
                        <TableCell className="text-center">
                          {currency.code}
                        </TableCell>
                        <TableCell className="text-center">
                          {currency.symbol}
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2 justify-center">
                            <Button
                              variant="ghost"
                              size="icon"
                              title="تعديل"
                              onClick={() => handleOpenEdit(currency)}
                              className="cursor-pointer"
                            >
                              <Pencil className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="destructive"
                              size="icon"
                              title="حذف"
                              onClick={() => handleOpenDelete(currency)}
                              className="cursor-pointer"
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell
                        colSpan={5}
                        className="py-10 text-center text-muted-foreground"
                      >
                        لا توجد عملات لعرضها.
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
            إجمالي العملات: {data?.total || 0}
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
                صفحة {page} من {data?.total_pages || 1}
              </PaginationItem>
              <PaginationItem>
                <PaginationPrevious
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

      <CreateCurrencyModal
        open={isCreateModalOpen}
        onOpenChange={setIsCreateModalOpen}
      />
      <EditCurrencyModal
        currency={selectedCurrency}
        open={isEditModalOpen}
        onOpenChange={setIsEditModalOpen}
      />
      <DeleteCurrencyModal
        currency={selectedCurrency}
        open={isDeleteModalOpen}
        onOpenChange={setIsDeleteModalOpen}
      />
    </div>
  );
}

function CurrenciesTableSkeleton({ rows = 5 }: { rows?: number }) {
  return (
    <TableBody>
      {Array.from({ length: rows }).map((_, idx) => (
        <TableRow key={idx}>
          <TableCell>
            <Skeleton className="h-4 w-8 mx-auto" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-24 mx-auto" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-16 mx-auto" />
          </TableCell>
          <TableCell>
            <Skeleton className="h-4 w-12 mx-auto" />
          </TableCell>
          <TableCell>
            <div className="flex items-center justify-center gap-2">
              <Skeleton className="h-8 w-8 rounded" />
              <Skeleton className="h-8 w-8 rounded" />
            </div>
          </TableCell>
        </TableRow>
      ))}
    </TableBody>
  );
}

export default Currencies;

