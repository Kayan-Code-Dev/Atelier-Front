import { useState, useMemo } from "react";
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
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Search } from "lucide-react";
import { usePermissionsListQueryOptions } from "@/api/permissions-roles/admins/permissions/permissions.hooks";
import { ROLES } from "@/api/permissions-roles/admins/roles/roles.types";
import { useQuery } from "@tanstack/react-query";

function Permissions() {
  const [page, setPage] = useState(1);
  const [searchQuery, setSearchQuery] = useState("");
  const { data, isPending } = useQuery(usePermissionsListQueryOptions(page));

  const filteredPermissions = useMemo(() => {
    if (!data?.data) {
      return [];
    }
    if (!searchQuery) {
      return data.data; // No filter, return all
    }
    return data.data.filter((permission) =>
      permission.name.toLowerCase().includes(searchQuery.toLowerCase())
    );
  }, [data, searchQuery]);

  const handlePreviousPage = () => {
    setPage((prev) => Math.max(1, prev - 1));
  };

  const handleNextPage = () => {
    // We use data?.total_pages, which comes from your class getter
    setPage((prev) => Math.min(prev + 1, data?.total_pages || 1));
  };

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>قائمة الاذونات</CardTitle>
            <CardDescription>عرض وتصفية الاذونات في النظام.</CardDescription>
          </div>
          <div className="relative w-full max-w-sm">
            <Input
              placeholder="ابحث باسم الاذن..."
              className="pr-10" // Padding right for the icon
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
            <Search className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          </div>
        </CardHeader>

        <CardContent>
          <div className="table-responsive-wrapper">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="text-center"># </TableHead>
                  <TableHead className="text-center">اسم الاذن</TableHead>
                  <TableHead className="text-center">النوع (Guard)</TableHead>
                  <TableHead className="text-center">تاريخ الإنشاء</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isPending ? (
                  // Show skeleton loader while data is fetching
                  <PermissionsTableSkeleton rows={10} />
                ) : filteredPermissions.length > 0 ? (
                  // Map over the filtered data
                  filteredPermissions.map((item) => (
                    <TableRow key={item.id}>
                      <TableCell className="font-medium text-center">
                        {item.id}
                      </TableCell>
                      <TableCell className="font-medium text-center">
                        {item.name.split("-").join(" ")}
                      </TableCell>
                      <TableCell className="text-center">
                        {ROLES[item.guard_name].label}
                      </TableCell>
                      <TableCell className="text-center">
                        {new Date(item.created_at).toLocaleDateString("ar-EG")}
                      </TableCell>
                    </TableRow>
                  ))
                ) : (
                  // Show empty state if no data or no filter results
                  <TableRow>
                    <TableCell
                      colSpan={3}
                      className="py-10 text-center text-muted-foreground"
                    >
                      {searchQuery
                        ? "لم يتم العثور على نتائج للبحث"
                        : "لا توجد اذونات لعرضها"}
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>

        <CardFooter className="flex items-center justify-between">
          <div className="text-sm text-muted-foreground">
            إجمالي الاذونات: {data?.total || 0}
          </div>{" "}
          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationNext // In RTL, "Next" arrow points right for previous page
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
                <PaginationPrevious // In RTL, "Previous" arrow points left for next page
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
    </div>
  );
}

/**
 * A helper component to show a loading skeleton for the table
 */
function PermissionsTableSkeleton({ rows = 5 }: { rows?: number }) {
  return Array.from({ length: rows }).map((_, index) => (
    <TableRow key={index}>
      <TableCell>
        <Skeleton className="h-5 w-3/4" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-1/2" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-1/2" />
      </TableCell>
    </TableRow>
  ));
}

export default Permissions;
