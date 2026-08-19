import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";

import {
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardFooter,
  CardDescription,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

import { TInventoryItem } from "@/api/inventory/inventory.types"; // Adjust path
import { useGetInventoryItemsQueryOptions } from "@/api/inventory/branches-managers/inventory.hooks"; // Adjust path
import { useQuery } from "@tanstack/react-query";
import { InventoryTableSkeleton } from "../InventoryTableSkeleton"; // Adjust path
import { useSearchParams } from "react-router";
import CustomPagination from "@/components/custom/CustomPagination";
import { format, parse } from "date-fns";

const getStatusVariant = (status: TInventoryItem["status"]) => {
  const normalizedStatus = status.trim(); // Handles trimmed status (e.g. pending)
  switch (normalizedStatus) {
    case "كافية":
      return "bg-green-600 text-white hover:bg-green-600/80";
    case "منحفضة":
      return "bg-destructive text-destructive-foreground hover:bg-destructive/90";
    default:
      return "bg-yellow-500 text-white hover:bg-yellow-500/80";
  }
};

function Inventory() {
  const [searchParams] = useSearchParams();
  const page = Number(searchParams.get("page"));

  // Data Fetching
  const { data, isPending } = useQuery(useGetInventoryItemsQueryOptions(page));

  return (
    <div dir="rtl" className="w-full">
      <Card className="w-full">
        <CardHeader>
          <CardTitle>مخزون الفرع</CardTitle>
          <CardDescription>
            عرض الأصناف الموجودة في المخزن الخاص بالفرع.
          </CardDescription>
        </CardHeader>

        <CardContent>
          <div className="table-responsive-wrapper">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="text-center">اسم الصنف</TableHead>
                  <TableHead className="text-center">قسم المنتجات</TableHead>
                  <TableHead className="text-center">قسم المنتجات الفرعي</TableHead>
                  <TableHead className="text-center">الكمية</TableHead>
                  <TableHead className="text-center">الفرع</TableHead>
                  <TableHead className="text-center">الحالة</TableHead>
                  <TableHead className="text-center">آخر تحديث</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isPending ? (
                  <InventoryTableSkeleton rows={7} />
                ) : data && data.data.length > 0 ? (
                  data.data.map((item) => (
                    <TableRow key={item.id}>
                      <TableCell className="font-medium text-center">
                        {item.name}
                      </TableCell>
                      <TableCell className="text-center">
                        {item.category_name}
                      </TableCell>
                      <TableCell className="text-center">
                        {item.sub_category_name}
                      </TableCell>
                      <TableCell className="text-center">
                        {item.quantity}
                      </TableCell>
                      <TableCell className="text-center">
                        {item.branch_name}
                      </TableCell>
                      <TableCell className="text-center">
                        <Badge className={getStatusVariant(item.status)}>
                          {item.status.trim()}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-center">
                        {format(
                          // 2. Use parse() to read the "dd-MM-yyyy" format
                          parse(item.updated_at, "dd-MM-yyyy", new Date()),
                          // 3. Format it however you want
                          "yyyy/MM/dd"
                        )}
                      </TableCell>
                    </TableRow>
                  ))
                ) : (
                  <TableRow>
                    <TableCell
                      colSpan={7}
                      className="py-10 text-center text-muted-foreground"
                    >
                      لا توجد أصناف لعرضها.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>

        <CardFooter className="flex items-center justify-between">
          <CustomPagination
            totalElementsLabel="اجمالى المخزن"
            totalElements={data?.total}
            totalPages={data?.total_pages}
            isLoading={isPending}
          />
        </CardFooter>
      </Card>

      
    </div>
  );
}

export default Inventory;
