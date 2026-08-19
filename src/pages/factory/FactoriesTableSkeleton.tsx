import { Skeleton } from "@/components/ui/skeleton";

type Props = {
  rows?: number;
};

export function FactoriesTableSkeleton({ rows = 5 }: Props) {
  return Array.from({ length: rows }).map((_, index) => (
    <tr key={index} className="border-b border-gray-50">
      <td className="px-5 py-4">
        <Skeleton className="h-6 w-16 rounded-md" />
      </td>
      <td className="px-4 py-4">
        <div className="flex items-center gap-2.5">
          <Skeleton className="h-8 w-8 rounded-lg shrink-0" />
          <div className="space-y-1">
            <Skeleton className="h-4 w-32" />
            <Skeleton className="h-3 w-24" />
          </div>
        </div>
      </td>
      <td className="px-4 py-4">
        <Skeleton className="h-5 w-20 rounded-full" />
      </td>
      <td className="px-4 py-4">
        <Skeleton className="h-4 w-28" />
      </td>
      <td className="px-4 py-4">
        <Skeleton className="h-4 w-24" />
        <Skeleton className="h-3 w-20 mt-1" />
      </td>
      <td className="px-4 py-4 text-center">
        <Skeleton className="h-6 w-16 rounded-full mx-auto" />
      </td>
      <td className="px-4 py-4">
        <Skeleton className="h-3 w-20" />
      </td>
      <td className="px-4 py-4">
        <div className="flex justify-center gap-1">
          <Skeleton className="h-8 w-8 rounded-lg" />
          <Skeleton className="h-8 w-8 rounded-lg" />
        </div>
      </td>
    </tr>
  ));
}
