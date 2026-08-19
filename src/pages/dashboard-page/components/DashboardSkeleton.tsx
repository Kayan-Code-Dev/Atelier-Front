import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

export function DashboardSkeleton() {
  return (
    <div className="space-y-8">
      <div className="space-y-4">
        <Skeleton className="h-4 w-32 rounded" />
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <Card key={i} className="overflow-hidden rounded-2xl border">
              <CardContent className="space-y-4 p-5">
                <div className="flex justify-between">
                  <Skeleton className="h-4 w-20 rounded" />
                  <Skeleton className="h-10 w-10 rounded-xl" />
                </div>
                <Skeleton className="ml-auto h-8 w-24 rounded" />
                <Skeleton className="h-3 w-full rounded" />
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <Card className="overflow-hidden rounded-2xl lg:col-span-2">
          <CardHeader>
            <Skeleton className="h-5 w-40 rounded" />
            <Skeleton className="mt-2 h-4 w-64 rounded" />
          </CardHeader>
          <CardContent>
            <Skeleton className="h-[280px] w-full rounded-xl" />
          </CardContent>
        </Card>
        <Card className="overflow-hidden rounded-2xl">
          <CardHeader>
            <Skeleton className="h-5 w-36 rounded" />
            <Skeleton className="mt-2 h-4 w-44 rounded" />
          </CardHeader>
          <CardContent className="space-y-4">
            <Skeleton className="h-12 w-full rounded-lg" />
            <Skeleton className="h-12 w-full rounded-lg" />
            <Skeleton className="h-[120px] w-full rounded-xl" />
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
