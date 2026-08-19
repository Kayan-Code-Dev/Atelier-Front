import { Skeleton } from "../ui/skeleton";
import { TableCell, TableRow } from "../ui/table";

function TableSkeleton({ rows = 5, cols = 5 }: { rows?: number; cols?: number }) {
    return Array.from({ length: rows }).map((_, index) => (
        <TableRow key={index}>
            <TableCell>
                <div className="flex items-center gap-3">
                    <Skeleton className="h-10 w-10 rounded-full" />
                    <div>
                        <Skeleton className="h-4 w-24" />
                        <Skeleton className="mt-2 h-3 w-32" />
                    </div>
                </div>
            </TableCell>
            {Array.from({ length: cols }).map((_, index) =>
                <TableCell key={index}>
                    <Skeleton className="h-4 w-24" />
                </TableCell>
            )}
            <TableCell>
                <div className="flex items-center gap-2">
                    <Skeleton className="h-8 w-8" />
                    <Skeleton className="h-8 w-8" />
                </div>
            </TableCell>
        </TableRow>
    ));
}

export default TableSkeleton