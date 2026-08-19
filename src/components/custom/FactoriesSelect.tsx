import { useMemo } from "react";
import { Loader2 } from "lucide-react";
import { useGetInfiniteFactoriesQueryOptions } from "@/api/v2/factories/factories.hooks";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Combobox, ComboboxOption } from "@/components/ui/combobox";
import { Button } from "@/components/ui/button";
import { useHasPermission } from "@/api/auth/auth.hooks";

// Single select props
export interface FactoriesSelectPropsSingle {
  /** Current selected value (single mode) */
  value?: string;
  /** Callback when value changes (single mode) */
  onChange?: (value: string) => void;
  /** Whether the component is disabled */
  disabled?: boolean;
  /** Placeholder text when no factory is selected */
  placeholder?: string;
  /** Placeholder text for the search input */
  searchPlaceholder?: string;
  /** Number of factories per page for infinite query */
  perPage?: number;
  /** Additional CSS classes */
  className?: string;
  /** Additional CSS classes for the popover */
  popoverClassName?: string;
  /** Whether clicking the selected option clears the selection */
  allowClear?: boolean;
  /** Whether to allow multiple selections */
  multi?: false;
}

// Multi select props
export interface FactoriesSelectPropsMulti {
  /** Current selected values (multi mode) */
  value?: string[];
  /** Callback when value changes (multi mode) */
  onChange?: (value: string[]) => void;
  /** Whether the component is disabled */
  disabled?: boolean;
  /** Placeholder text when no factories are selected */
  placeholder?: string;
  /** Placeholder text for the search input */
  searchPlaceholder?: string;
  /** Number of factories per page for infinite query */
  perPage?: number;
  /** Additional CSS classes */
  className?: string;
  /** Additional CSS classes for the popover */
  popoverClassName?: string;
  /** Whether clicking the selected option clears the selection */
  allowClear?: boolean;
  /** Whether to allow multiple selections */
  multi: true;
}

export type FactoriesSelectProps = FactoriesSelectPropsSingle | FactoriesSelectPropsMulti;

function FactoriesSelectContent({
  value,
  onChange,
  disabled = false,
  placeholder,
  searchPlaceholder = "ابحث عن مصنع...",
  perPage = 10,
  className,
  popoverClassName,
  allowClear = false,
  multi = false,
}: FactoriesSelectProps) {
  const { hasPermission, isPending } = useHasPermission("factories.view");
  const {
    data,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isFetching,
    isLoading,
  } = useInfiniteQuery({
    ...useGetInfiniteFactoriesQueryOptions(perPage),
    enabled: hasPermission,
  });

  // Flatten all factories from all pages into options
  const options: ComboboxOption[] = useMemo(() => {
    if (!data) return [];
    return data.pages.flatMap((page) =>
      (page?.data || []).map((factory) => ({
        value: factory.id.toString(),
        label: factory.name,
      }))
    );
  }, [data?.pages]);

  if (isPending) {
    return (
      <div className="flex h-9 w-full items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-sm">
        <Loader2 className="h-4 w-4 animate-spin" />
        <span className="mr-2">جاري التحميل...</span>
      </div>
    );
  }
  if (!hasPermission) {
    return (
      <div className="flex h-9 w-full items-center rounded-md border border-input bg-muted px-3 py-2 text-sm text-muted-foreground">
        لا تملك صلاحية عرض المصانع
      </div>
    );
  }

  // Show loader on first load
  if (isLoading) {
    return (
      <div className="flex h-9 w-full items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-sm">
        <Loader2 className="h-4 w-4 animate-spin" />
        <span className="mr-2">جاري التحميل...</span>
      </div>
    );
  }

  // Single select mode
  if (!multi) {
    const singleValue = (value as string) || "";
    const defaultPlaceholder = placeholder || "اختر المصنع...";

    return (
      <div className="space-y-2">
        <Combobox
          multi={false}
          options={options}
          value={singleValue}
          onChange={onChange as (value: string) => void}
          placeholder={defaultPlaceholder}
          searchPlaceholder={searchPlaceholder}
          emptyText="لا توجد مصانع."
          disabled={disabled}
          className={className}
          popoverClassName={popoverClassName}
          allowClear={allowClear}
        />
        
        {/* Load More Button - shown when there are more pages */}
        {hasNextPage && (
          <Button
            variant="outline"
            size="sm"
            className="w-full"
            disabled={isFetchingNextPage}
            onClick={(e) => {
              e.preventDefault();
              fetchNextPage();
            }}
          >
            {isFetchingNextPage ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                جاري التحميل...
              </>
            ) : (
              "تحميل المزيد"
            )}
          </Button>
        )}
        
        {/* Loading indicator for initial fetch */}
        {isFetching && !isFetchingNextPage && (
          <div className="flex items-center justify-center p-2">
            <Loader2 className="h-4 w-4 animate-spin" />
          </div>
        )}
      </div>
    );
  }

  // Multi select mode
  const multiValue = (value as string[]) || [];
  const defaultPlaceholder = placeholder || "اختر المصانع...";

  return (
    <div className="space-y-2">
      <Combobox
        multi={true}
        options={options}
        value={multiValue}
        onChange={onChange as (value: string[]) => void}
        placeholder={defaultPlaceholder}
        searchPlaceholder={searchPlaceholder}
        emptyText="لا توجد مصانع."
        disabled={disabled}
        className={className}
        popoverClassName={popoverClassName}
        allowClear={allowClear}
      />
      
      {/* Load More Button - shown when there are more pages */}
      {hasNextPage && (
        <Button
          variant="outline"
          size="sm"
          className="w-full"
          disabled={isFetchingNextPage}
          onClick={(e) => {
            e.preventDefault();
            fetchNextPage();
          }}
        >
          {isFetchingNextPage ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              جاري التحميل...
            </>
          ) : (
            "تحميل المزيد"
          )}
        </Button>
      )}
      
      {/* Loading indicator for initial fetch */}
      {isFetching && !isFetchingNextPage && (
        <div className="flex items-center justify-center p-2">
          <Loader2 className="h-4 w-4 animate-spin" />
        </div>
      )}
    </div>
  );
}

/**
 * Combobox component for selecting factories (single or multi-select).
 * Uses infinite query to load factories in pages.
 * 
 * @example
 * // Single select - Standalone usage
 * <FactoriesSelect
 *   value={selectedFactoryId}
 *   onChange={setSelectedFactoryId}
 * />
 * 
 * @example
 * // Single select - With react-hook-form
 * <FormField
 *   control={form.control}
 *   name="factoryId"
 *   render={({ field }) => (
 *     <FormItem>
 *       <FormLabel>Factory</FormLabel>
 *       <FormControl>
 *         <FactoriesSelect
 *           value={field.value}
 *           onChange={field.onChange}
 *         />
 *       </FormControl>
 *       <FormMessage />
 *     </FormItem>
 *   )}
 * />
 * 
 * @example
 * // Multi select - Standalone usage
 * <FactoriesSelect
 *   multi={true}
 *   value={selectedFactoryIds}
 *   onChange={setSelectedFactoryIds}
 * />
 * 
 * @example
 * // Multi select - With react-hook-form
 * <FormField
 *   control={form.control}
 *   name="factoryIds"
 *   render={({ field }) => (
 *     <FormItem>
 *       <FormLabel>Factories</FormLabel>
 *       <FormControl>
 *         <FactoriesSelect
 *           multi={true}
 *           value={field.value || []}
 *           onChange={field.onChange}
 *         />
 *       </FormControl>
 *       <FormMessage />
 *     </FormItem>
 *   )}
 * />
 */
export function FactoriesSelect(props: FactoriesSelectProps) {
  return <FactoriesSelectContent {...props} />;
}

// Export with the old name for backward compatibility
export const MultiFactoriesSelect = FactoriesSelect;
