import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";

function OrderDetailsSkeleton() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <Skeleton className="h-8 w-32" />
        <div className="flex gap-2">
          <Skeleton className="h-9 w-20" />
          <Skeleton className="h-9 w-20" />
          <Skeleton className="h-9 w-20" />
        </div>
      </div>

      {/* Order Basic Info */}
      <div className="bg-white rounded-lg p-6 shadow-sm">
        <Skeleton className="h-6 w-32 mb-4" />
        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-2">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-6 w-20" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-16" />
            <Skeleton className="h-6 w-24" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-28" />
            <Skeleton className="h-6 w-32" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-20" />
            <Skeleton className="h-6 w-28" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-20" />
            <Skeleton className="h-6 w-28" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-32" />
            <Skeleton className="h-6 w-24" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-6 w-36" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-28" />
            <Skeleton className="h-6 w-36" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-6 w-36" />
          </div>
        </div>
      </div>

      {/* Client Info */}
      <div className="bg-white rounded-lg p-6 shadow-sm border-t">
        <Skeleton className="h-6 w-32 mb-3" />
        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-2">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-6 w-20" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-16" />
            <Skeleton className="h-6 w-32" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-16" />
            <Skeleton className="h-6 w-28" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-20" />
            <Skeleton className="h-6 w-24" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-16" />
            <Skeleton className="h-6 w-24" />
          </div>
        </div>
      </div>

      {/* Order Items */}
      <div className="bg-white rounded-lg p-6 shadow-sm border-t">
        <Skeleton className="h-6 w-32 mb-3" />
        <div className="table-responsive-wrapper">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="text-center">
                  <Skeleton className="h-4 w-4 mx-auto" />
                </TableHead>
                <TableHead className="text-center">
                  <Skeleton className="h-4 w-16 mx-auto" />
                </TableHead>
                <TableHead className="text-center">
                  <Skeleton className="h-4 w-16 mx-auto" />
                </TableHead>
                <TableHead className="text-center">
                  <Skeleton className="h-4 w-16 mx-auto" />
                </TableHead>
                <TableHead className="text-center">
                  <Skeleton className="h-4 w-20 mx-auto" />
                </TableHead>
                <TableHead className="text-center">
                  <Skeleton className="h-4 w-16 mx-auto" />
                </TableHead>
                <TableHead className="text-center">
                  <Skeleton className="h-4 w-16 mx-auto" />
                </TableHead>
                <TableHead className="text-center">
                  <Skeleton className="h-4 w-20 mx-auto" />
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {Array.from({ length: 3 }).map((_, index) => (
                <TableRow key={index}>
                  <TableCell className="text-center">
                    <Skeleton className="h-4 w-4 mx-auto" />
                  </TableCell>
                  <TableCell className="text-center">
                    <Skeleton className="h-4 w-16 mx-auto" />
                  </TableCell>
                  <TableCell className="text-center">
                    <Skeleton className="h-4 w-24 mx-auto" />
                  </TableCell>
                  <TableCell className="text-center">
                    <Skeleton className="h-4 w-32 mx-auto" />
                  </TableCell>
                  <TableCell className="text-center">
                    <Skeleton className="h-4 w-28 mx-auto" />
                  </TableCell>
                  <TableCell className="text-center">
                    <Skeleton className="h-4 w-20 mx-auto" />
                  </TableCell>
                  <TableCell className="text-center">
                    <Skeleton className="h-4 w-20 mx-auto" />
                  </TableCell>
                  <TableCell className="text-center">
                    <Skeleton className="h-4 w-24 mx-auto" />
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      </div>

      {/* Order Notes */}
      <div className="bg-white rounded-lg p-6 shadow-sm border-t">
        <Skeleton className="h-6 w-32 mb-3" />
        <Skeleton className="h-20 w-full" />
      </div>
    </div>
  );
}

export default OrderDetailsSkeleton;
