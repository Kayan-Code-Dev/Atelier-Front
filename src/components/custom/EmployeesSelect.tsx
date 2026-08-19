import { useMemo } from "react";
import { Loader2 } from "lucide-react";
import { useGetInfiniteEmployeesQueryOptions } from "@/api/v2/employees/employees.hooks";
import { TGetEmployeesParams } from "@/api/v2/employees/employees.types";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Combobox, ComboboxOption } from "@/components/ui/combobox";
import { Button } from "@/components/ui/button";
import { useHasPermission } from "@/api/auth/auth.hooks";

// Single select props
export interface EmployeesSelectPropsSingle {
  /** Query parameters for fetching employees */
  params: TGetEmployeesParams;
  /** Current selected value (single mode) */
  value?: string;
  /** Callback when value changes (single mode) */
  onChange?: (value: string) => void;
  /** Whether the component is disabled */
  disabled?: boolean;
  /** Placeholder text when no employee is selected */
  placeholder?: string;
  /** Placeholder text for the search input */
  searchPlaceholder?: string;
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
export interface EmployeesSelectPropsMulti {
  /** Query parameters for fetching employees */
  params: TGetEmployeesParams;
  /** Current selected values (multi mode) */
  value?: string[];
  /** Callback when value changes (multi mode) */
  onChange?: (value: string[]) => void;
  /** Whether the component is disabled */
  disabled?: boolean;
  /** Placeholder text when no employees are selected */
  placeholder?: string;
  /** Placeholder text for the search input */
  searchPlaceholder?: string;
  /** Additional CSS classes */
  className?: string;
  /** Additional CSS classes for the popover */
  popoverClassName?: string;
  /** Whether clicking the selected option clears the selection */
  allowClear?: boolean;
  /** Whether to allow multiple selections */
  multi: true;
}

export type EmployeesSelectProps = EmployeesSelectPropsSingle | EmployeesSelectPropsMulti;

const EMPLOYEES_VIEW_PERMISSION = "hr.employees.view";

function EmployeesSelectContent({
  params,
  value,
  onChange,
  disabled = false,
  placeholder,
  searchPlaceholder = "ابحث عن موظف...",
  className,
  popoverClassName,
  allowClear = false,
  multi = false,
}: EmployeesSelectProps) {
  const { hasPermission, isPending } = useHasPermission(EMPLOYEES_VIEW_PERMISSION);

  const {
    data,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isFetching,
    isLoading,
  } = useInfiniteQuery({
    ...useGetInfiniteEmployeesQueryOptions(params),
    enabled: hasPermission,
  });

  // Flatten all employees from all pages into options
  const options: ComboboxOption[] = useMemo(() => {
    if (!data) return [];
    return data.pages.flatMap((page) =>
      (page?.data || []).map((employee) => ({
        value: employee.id.toString(),
        label: employee.user.name,
      }))
    );
  }, [data?.pages]);

  // Still loading permissions
  if (isPending) {
    return (
      <div className="flex h-9 w-full items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-sm">
        <Loader2 className="h-4 w-4 animate-spin" />
        <span className="mr-2">جاري التحميل...</span>
      </div>
    );
  }

  // No permission: show disabled state
  if (!hasPermission) {
    const noAccessPlaceholder = "لا تملك صلاحية عرض الموظفين";
    return (
      <div className="flex h-9 w-full items-center rounded-md border border-input bg-muted px-3 py-2 text-sm text-muted-foreground">
        {noAccessPlaceholder}
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
    const defaultPlaceholder = placeholder || "اختر الموظف...";

    return (
      <div className="space-y-2">
        <Combobox
          multi={false}
          options={options}
          value={singleValue}
          onChange={onChange as (value: string) => void}
          placeholder={defaultPlaceholder}
          searchPlaceholder={searchPlaceholder}
          emptyText="لا يوجد موظفون."
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
  const defaultPlaceholder = placeholder || "اختر الموظفين...";

  return (
    <div className="space-y-2">
      <Combobox
        multi={true}
        options={options}
        value={multiValue}
        onChange={onChange as (value: string[]) => void}
        placeholder={defaultPlaceholder}
        searchPlaceholder={searchPlaceholder}
        emptyText="لا يوجد موظفون."
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
 * Combobox component for selecting employees (single or multi-select).
 * Uses infinite query to load employees in pages.
 * 
 * @example
 * // Single select - Standalone usage
 * <EmployeesSelect
 *   params={{ per_page: 10 }}
 *   value={selectedEmployeeId}
 *   onChange={setSelectedEmployeeId}
 * />
 * 
 * @example
 * // Single select - With react-hook-form
 * <FormField
 *   control={form.control}
 *   name="employeeId"
 *   render={({ field }) => (
 *     <FormItem>
 *       <FormLabel>Employee</FormLabel>
 *       <FormControl>
 *         <EmployeesSelect
 *           params={{ per_page: 10 }}
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
 * <EmployeesSelect
 *   params={{ per_page: 10, department_id: 1 }}
 *   multi={true}
 *   value={selectedEmployeeIds}
 *   onChange={setSelectedEmployeeIds}
 * />
 * 
 * @example
 * // Multi select - With react-hook-form
 * <FormField
 *   control={form.control}
 *   name="employeeIds"
 *   render={({ field }) => (
 *     <FormItem>
 *       <FormLabel>Employees</FormLabel>
 *       <FormControl>
 *         <EmployeesSelect
 *           params={{ per_page: 10 }}
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
export function EmployeesSelect(props: EmployeesSelectProps) {
  return <EmployeesSelectContent {...props} />;
}

// Export with the old name for backward compatibility
export const MultiEmployeesSelect = EmployeesSelect;

