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
import { useInfiniteCategoriesQueryOptions } from "@/api/v2/content-managment/category/category.hooks";
import { useSuspenseInfiniteQuery } from "@tanstack/react-query";
import { cn } from "@/lib/utils";

type Props = {
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
  className?: string;
};

function CategoriesSelectContent({
  value,
  onChange,
  disabled,
  className,
}: Props) {
  const { data, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useSuspenseInfiniteQuery(useInfiniteCategoriesQueryOptions(10));

  return (
    <Select onValueChange={onChange} value={value} disabled={disabled}>
      <SelectTrigger className={cn("w-full", className)}>
        <SelectValue placeholder="اختر قسم المنتجات..." />
      </SelectTrigger>
      <SelectContent>
        <ScrollArea className="h-[250px]">
          {/* Map over the pages, then map over the data in each page */}
          {data.pages.map((page, i) => (
            <Fragment key={i}>
              {page?.data.map((category) => (
                <SelectItem key={category.id} value={category.id.toString()}>
                  {category.name}
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

function CategoriesSelectSkeleton() {
  return (
    <Select disabled>
      <SelectTrigger className="w-full">
        <Loader2 className="h-4 w-4 animate-spin" />
      </SelectTrigger>
    </Select>
  );
}

export function CategoriesSelect(props: Props) {
  return (
    <Suspense fallback={<CategoriesSelectSkeleton />}>
      <CategoriesSelectContent {...props} />
    </Suspense>
  );
}

