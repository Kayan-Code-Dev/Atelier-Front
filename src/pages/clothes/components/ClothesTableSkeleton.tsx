import { TableRow, TableCell } from "@/components/ui/table";
import { Skeleton } from "@/components/ui/skeleton";

type Props = {
  rows?: number;
};

export function ClothesTableSkeleton({ rows = 5 }: Props) {
  return Array.from({ length: rows }).map((_, index) => (
    <TableRow key={index} style={{ borderColor: "#F8FAFC" }}>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-3 w-5" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-4 w-28" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-6 w-16 rounded-lg" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-4 w-24" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-4 w-20" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-4 w-14" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-4 w-24" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <Skeleton className="h-6 w-24 rounded-full" />
      </TableCell>
      <TableCell className="px-4 py-3">
        <div className="flex items-center gap-1 justify-center">
          <Skeleton className="h-7 w-7 rounded-lg" />
          <Skeleton className="h-7 w-7 rounded-lg" />
        </div>
      </TableCell>
    </TableRow>
  ));
}
