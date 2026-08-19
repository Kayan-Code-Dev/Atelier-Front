import { TableRow, TableCell } from "@/components/ui/table";
import { Skeleton } from "@/components/ui/skeleton";

type Props = {
  rows?: number;
};

export function BranchesTableSkeleton({ rows = 5 }: Props) {
  return Array.from({ length: rows }).map((_, index) => (
    <TableRow key={index}>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-4 w-6" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <div className="flex items-center gap-2">
          <Skeleton className="h-9 w-9 rounded-full" />
          <div className="space-y-1">
            <Skeleton className="h-4 w-28" />
            <Skeleton className="h-3 w-16" />
          </div>
        </div>
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-4 w-20" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-4 w-24" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-4 w-16" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-5 w-14 rounded-full" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-5 w-12 rounded-lg" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <div className="flex items-center gap-1.5 justify-end">
          <Skeleton className="h-7 w-7 rounded-lg" />
          <Skeleton className="h-7 w-7 rounded-lg" />
        </div>
      </TableCell>
    </TableRow>
  ));
}
