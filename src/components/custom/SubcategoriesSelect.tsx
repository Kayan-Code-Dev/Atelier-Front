import { useGetInifiniteSubcategoriesQueryOptions } from "@/api/v2/content-managment/subcategory/subcategory.hooks";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useSuspenseInfiniteQuery } from "@tanstack/react-query";
import { Loader2, X } from "lucide-react";
import { Fragment, Suspense, useState } from "react";
import { cn } from "@/lib/utils";

type SingleSelectProps = {
  multiple?: false;
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
  category_id?: number;
  className?: string;
};

type MultipleSelectProps = {
  multiple: true;
  value: string[];
  onChange: (value: string[]) => void;
  disabled?: boolean;
  category_id?: number;
  className?: string;
};

type Props = SingleSelectProps | MultipleSelectProps;

function SingleSubcategoriesSelectContent({
  value,
  onChange,
  disabled,
  category_id,
  className,
}: SingleSelectProps) {
  const { data, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useSuspenseInfiniteQuery(
      useGetInifiniteSubcategoriesQueryOptions(category_id, 10)
    );

  return (
    <Select onValueChange={onChange} value={value} disabled={disabled}>
      <SelectTrigger className={cn("w-full", className)}>
        <SelectValue placeholder="اختر قسم المنتجات الفرعي..." />
      </SelectTrigger>
      <SelectContent>
        <ScrollArea className="h-[250px]">
          {/* Map over the pages, then map over the data in each page */}
          {data.pages.map((page, i) => (
            <Fragment key={i}>
              {page?.data.map((subcategory) => (
                <SelectItem
                  key={subcategory.id}
                  value={subcategory.id.toString()}
                >
                  {subcategory.name}
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

function MultipleSubcategoriesSelectContent({
  value,
  onChange,
  disabled,
  category_id,
}: MultipleSelectProps) {
  const { data, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useSuspenseInfiniteQuery(
      useGetInifiniteSubcategoriesQueryOptions(category_id, 10)
    );

  const [open, setOpen] = useState(false);

  const toggleSubcategory = (subcategoryId: string) => {
    const newValue = value.includes(subcategoryId)
      ? value.filter((id) => id !== subcategoryId)
      : [...value, subcategoryId];
    onChange(newValue);
  };

  const removeSubcategory = (subcategoryId: string, e: React.MouseEvent) => {
    e.stopPropagation();
    onChange(value.filter((id) => id !== subcategoryId));
  };

  // Get all subcategories from pages
  const allSubcategories =
    data.pages.flatMap((page) => page?.data || []) || [];

  // Get selected subcategories data
  const selectedSubcategories = allSubcategories.filter((sub) =>
    value.includes(sub.id.toString())
  );

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          disabled={disabled}
          className="w-full justify-between min-h-9 h-auto"
        >
          <div className="flex flex-wrap gap-1 flex-1">
            {selectedSubcategories.length === 0 ? (
              <span className="text-muted-foreground">
                اختر أقسام المنتجات الفرعية...
              </span>
            ) : (
              selectedSubcategories.map((sub) => (
                <Badge
                  key={sub.id}
                  variant="secondary"
                  className="mr-1 mb-1"
                  onClick={(e) => {
                    e.stopPropagation();
                  }}
                >
                  {sub.name}
                  <button
                    className="mr-1 rtl:ml-1 rtl:mr-0 rounded-full outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    onKeyDown={(e) => {
                      if (e.key === "Enter") {
                        removeSubcategory(sub.id.toString(), e as any);
                      }
                    }}
                    onMouseDown={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                    }}
                    onClick={(e) => removeSubcategory(sub.id.toString(), e)}
                  >
                    <X className="h-3 w-3 text-muted-foreground hover:text-foreground" />
                  </button>
                </Badge>
              ))
            )}
          </div>
        </Button>
      </PopoverTrigger>
      <PopoverContent
        className="w-[var(--radix-popover-trigger-width)] p-0"
        align="start"
      >
        <ScrollArea className="h-[250px]">
          <div className="p-1">
            {data.pages
              .flatMap((page) => page?.data || [])
              .map((subcategory, i) => (
                <Fragment key={i}>
                  <div
                    key={subcategory.id}
                    className="flex items-center space-x-2 space-x-reverse p-2 hover:bg-accent rounded-sm cursor-pointer"
                    onClick={() => toggleSubcategory(subcategory.id.toString())}
                  >
                    <Checkbox
                      checked={value.includes(subcategory.id.toString())}
                      onCheckedChange={() =>
                        toggleSubcategory(subcategory.id.toString())
                      }
                    />
                    <label className="flex-1 cursor-pointer text-sm">
                      {subcategory.name}
                    </label>
                  </div>
                </Fragment>
              ))}
          </div>

          {/* "Load More" Button */}
          {hasNextPage && (
            <div className="sticky bottom-0 bg-background p-2 border-t">
              <Button
                variant="outline"
                className="w-full"
                disabled={isFetchingNextPage}
                onClick={(e) => {
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
      </PopoverContent>
    </Popover>
  );
}

function SubcategoriesSelectSkeleton() {
  return (
    <Select disabled>
      <SelectTrigger className="w-full">
        <Loader2 className="h-4 w-4 animate-spin" />
      </SelectTrigger>
    </Select>
  );
}

function MultipleSubcategoriesSelectSkeleton() {
  return (
    <Button variant="outline" disabled className="w-full justify-between min-h-9 h-auto">
      <Loader2 className="h-4 w-4 animate-spin" />
    </Button>
  );
}

export function SubcategoriesSelect(props: Props) {
  if (props.multiple) {
    return (
      <Suspense fallback={<MultipleSubcategoriesSelectSkeleton />}>
        <MultipleSubcategoriesSelectContent {...props} />
      </Suspense>
    );
  }

  return (
    <Suspense fallback={<SubcategoriesSelectSkeleton />}>
      <SingleSubcategoriesSelectContent {...props} />
    </Suspense>
  );
}
