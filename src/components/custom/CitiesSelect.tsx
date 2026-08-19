import { Fragment, Suspense } from "react";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Loader2 } from "lucide-react";
import { ScrollArea } from "@/components/ui/scroll-area";
import { useInfiniteCitiesQueryOptions } from "@/api/v2/content-managment/city/city.hooks";
import { useSuspenseInfiniteQuery } from "@tanstack/react-query";

type Props = {
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
  className?: string;
};

function CitiesSelectContent({ value, onChange, disabled, className }: Props) {
  const { data, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useSuspenseInfiniteQuery(useInfiniteCitiesQueryOptions(10));

  return (
    <Select onValueChange={onChange} value={value} disabled={disabled}>
      <SelectTrigger className={className}>
        <SelectValue placeholder="اختر المدينة..." />
      </SelectTrigger>
      <SelectContent>
        <ScrollArea className="h-[250px]">
          {/* Map over the pages, then map over the data in each page */}
          {data.pages.map((page, i) => (
            <Fragment key={i}>
              {page?.data.map((city) => (
                <SelectItem key={city.id} value={city.id.toString()}>
                  {city.name}
                </SelectItem>
              ))}
            </Fragment>
          ))}

          {/* "Load More" Button */}
          {hasNextPage && (
            <div className="sticky bottom-0 bg-background p-2">
              <Button
                variant="outline"
                className="w-full"
                disabled={isFetchingNextPage}
                // Use onMouseDown to prevent the Select from closing
                onMouseDown={(e) => {
                  e.preventDefault();
                  fetchNextPage();
                }}
              >
                {isFetchingNextPage ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  "تحميل المزيد"
                )}
              </Button>
            </div>
          )}
        </ScrollArea>
      </SelectContent>
    </Select>
  );
}

function CitiesSelectSkeleton() {
  return (
    <Select disabled>
      <SelectTrigger>
        <Loader2 className="h-4 w-4 animate-spin" />
      </SelectTrigger>
    </Select>
  );
}

export function CitiesSelect(props: Props) {
  return (
    <Suspense fallback={<CitiesSelectSkeleton />}>
      <CitiesSelectContent {...props} />
    </Suspense>
  );
}

