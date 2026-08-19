import { Suspense, useMemo } from "react";
import { Loader2 } from "lucide-react";
import { useGetInfiniteJobTitlesQueryOptions } from "@/api/v2/content-managment/job-titles/job-titles.hooks";
import { useSuspenseInfiniteQuery } from "@tanstack/react-query";
import { Combobox, ComboboxOption } from "@/components/ui/combobox";
import { Button } from "@/components/ui/button";

// Single select props
export interface JobTitlesSelectPropsSingle {
  /** Current selected value (single mode) */
  value?: string;
  /** Callback when value changes (single mode) */
  onChange?: (value: string) => void;
  /** Whether the component is disabled */
  disabled?: boolean;
  /** Placeholder text when no job title is selected */
  placeholder?: string;
  /** Placeholder text for the search input */
  searchPlaceholder?: string;
  /** Number of job titles per page for infinite query */
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
export interface JobTitlesSelectPropsMulti {
  /** Current selected values (multi mode) */
  value?: string[];
  /** Callback when value changes (multi mode) */
  onChange?: (value: string[]) => void;
  /** Whether the component is disabled */
  disabled?: boolean;
  /** Placeholder text when no job titles are selected */
  placeholder?: string;
  /** Placeholder text for the search input */
  searchPlaceholder?: string;
  /** Number of job titles per page for infinite query */
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

export type JobTitlesSelectProps = JobTitlesSelectPropsSingle | JobTitlesSelectPropsMulti;

function JobTitlesSelectContent({
  value,
  onChange,
  disabled = false,
  placeholder,
  searchPlaceholder = "ابحث عن مسمى وظيفي...",
  perPage = 10,
  className,
  popoverClassName,
  allowClear = false,
  multi = false,
}: JobTitlesSelectProps) {
  const {
    data,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isFetching,
  } = useSuspenseInfiniteQuery(useGetInfiniteJobTitlesQueryOptions(perPage));

  // Flatten all job titles from all pages into options
  const options: ComboboxOption[] = useMemo(() => {
    return data.pages.flatMap((page) =>
      (page?.data || []).map((jobTitle) => ({
        value: jobTitle.id.toString(),
        label: jobTitle.name,
      }))
    );
  }, [data.pages]);

  // Single select mode
  if (!multi) {
    const singleValue = (value as string) || "";
    const defaultPlaceholder = placeholder || "اختر المسمى الوظيفي...";

    return (
      <div className="space-y-2">
        <Combobox
          multi={false}
          options={options}
          value={singleValue}
          onChange={onChange as (value: string) => void}
          placeholder={defaultPlaceholder}
          searchPlaceholder={searchPlaceholder}
          emptyText="لا توجد مسميات وظيفية."
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
  const defaultPlaceholder = placeholder || "اختر المسميات الوظيفية...";

  return (
    <div className="space-y-2">
      <Combobox
        multi={true}
        options={options}
        value={multiValue}
        onChange={onChange as (value: string[]) => void}
        placeholder={defaultPlaceholder}
        searchPlaceholder={searchPlaceholder}
        emptyText="لا توجد مسميات وظيفية."
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

function JobTitlesSelectSkeleton() {
  return (
    <div className="space-y-2">
      <div className="h-9 w-full rounded-md border bg-muted animate-pulse" />
    </div>
  );
}

/**
 * Combobox component for selecting job titles (single or multi-select).
 * Uses infinite query to load job titles in pages.
 * 
 * @example
 * // Single select - Standalone usage
 * <JobTitlesSelect
 *   value={selectedJobTitleId}
 *   onChange={setSelectedJobTitleId}
 * />
 * 
 * @example
 * // Single select - With react-hook-form
 * <FormField
 *   control={form.control}
 *   name="jobTitleId"
 *   render={({ field }) => (
 *     <FormItem>
 *       <FormLabel>Job Title</FormLabel>
 *       <FormControl>
 *         <JobTitlesSelect
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
 * <JobTitlesSelect
 *   multi={true}
 *   value={selectedJobTitleIds}
 *   onChange={setSelectedJobTitleIds}
 * />
 * 
 * @example
 * // Multi select - With react-hook-form
 * <FormField
 *   control={form.control}
 *   name="jobTitleIds"
 *   render={({ field }) => (
 *     <FormItem>
 *       <FormLabel>Job Titles</FormLabel>
 *       <FormControl>
 *         <JobTitlesSelect
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
export function JobTitlesSelect(props: JobTitlesSelectProps) {
  return (
    <Suspense fallback={<JobTitlesSelectSkeleton />}>
      <JobTitlesSelectContent {...props} />
    </Suspense>
  );
}

