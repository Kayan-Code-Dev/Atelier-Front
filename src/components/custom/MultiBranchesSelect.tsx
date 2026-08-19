import { useMemo } from "react";
import { Loader2 } from "lucide-react";
import { useGetInfiniteBranchesQueryOptions } from "@/api/v2/branches/branches.hooks";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Combobox, ComboboxOption } from "@/components/ui/combobox";
import { Button } from "@/components/ui/button";
import { useAuthStore } from "@/zustand-stores/auth.store";
import { useHasPermission } from "@/api/auth/auth.hooks";

// Single select props
export interface BranchesSelectPropsSingle {
  /** Current selected value (single mode) */
  value?: string;
  /** Callback when value changes (single mode) */
  onChange?: (value: string) => void;
  /** Whether the component is disabled */
  disabled?: boolean;
  /** Placeholder text when no branch is selected */
  placeholder?: string;
  /** Placeholder text for the search input */
  searchPlaceholder?: string;
  /** Number of branches per page for infinite query */
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
export interface BranchesSelectPropsMulti {
  /** Current selected values (multi mode) */
  value?: string[];
  /** Callback when value changes (multi mode) */
  onChange?: (value: string[]) => void;
  /** Whether the component is disabled */
  disabled?: boolean;
  /** Placeholder text when no branches are selected */
  placeholder?: string;
  /** Placeholder text for the search input */
  searchPlaceholder?: string;
  /** Number of branches per page for infinite query */
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

export type BranchesSelectProps = BranchesSelectPropsSingle | BranchesSelectPropsMulti;

/** Permission/role names that grant access to view branches */
const BRANCH_ACCESS_ROLES = [
  "branches_manager",
  "branches_basic_view_create",
] as const;
const BRANCH_ACCESS_PERMISSIONS = [
  "branches.view",
  "branches.create",
  "branches.update",
  "branches.delete",
  "branches.export",
  "Read-Branches",
] as const;

function getPermissionStrings(perms: unknown): string[] {
  if (!Array.isArray(perms)) return [];
  return perms
    .map((p) => (typeof p === "string" ? p : (p as { name?: string })?.name))
    .filter((s): s is string => typeof s === "string" && s.length > 0);
}

function BranchesSelectContent({
  value,
  onChange,
  disabled = false,
  placeholder,
  searchPlaceholder = "ابحث عن فرع...",
  perPage = 10,
  className,
  popoverClassName,
  allowClear = false,
  multi = false,
}: BranchesSelectProps) {
  const loginData = useAuthStore((s) => s.loginData);
  const roles = loginData?.roles ?? [];
  const permissions = getPermissionStrings(
    loginData?.permissions ?? (loginData as { data?: { permissions?: unknown } })?.data?.permissions
  );
  const hasBranchRole =
    roles.some(
      (role) =>
        BRANCH_ACCESS_ROLES.includes(role as (typeof BRANCH_ACCESS_ROLES)[number]) ||
        role.startsWith("branches_")
    ) ?? false;
  const hasBranchPermission =
    permissions.some((p) =>
      BRANCH_ACCESS_PERMISSIONS.some((perm) => p === perm) ||
      p.startsWith("branches.")
    ) ?? false;
  const { hasPermission } = useHasPermission("branches.view");
  const canViewBranches = hasBranchRole || hasBranchPermission || hasPermission;

  const {
    data,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isFetching,
    isLoading,
  } = useInfiniteQuery({
    ...useGetInfiniteBranchesQueryOptions(perPage),
    enabled: canViewBranches,
  });

  // Flatten all branches from all pages into options
  const options: ComboboxOption[] = useMemo(() => {
    if (!data) return [];
    return data.pages.flatMap((page) =>
      (page?.data || []).map((branch) => ({
        value: branch.id.toString(),
        label: branch.name,
      }))
    );
  }, [data?.pages]);

  // Show loader on first load
  if (canViewBranches && isLoading) {
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
    const defaultPlaceholder =
      placeholder || (canViewBranches ? "اختر الفرع..." : "لا تملك صلاحية عرض الفروع");

    return (
      <div className="space-y-2">
        <Combobox
          multi={false}
          options={options}
          value={singleValue}
          onChange={onChange as (value: string) => void}
          placeholder={defaultPlaceholder}
          searchPlaceholder={searchPlaceholder}
          emptyText={canViewBranches ? "لا توجد فروع." : "لا تملك صلاحية عرض الفروع."}
          disabled={disabled || !canViewBranches}
          className={className}
          popoverClassName={popoverClassName}
          allowClear={allowClear}
        />
        
        {/* Load More Button - shown when there are more pages */}
        {canViewBranches && hasNextPage && (
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
        {canViewBranches && isFetching && !isFetchingNextPage && (
          <div className="flex items-center justify-center p-2">
            <Loader2 className="h-4 w-4 animate-spin" />
          </div>
        )}
      </div>
    );
  }

  // Multi select mode
  const multiValue = (value as string[]) || [];
  const defaultPlaceholder = placeholder || "اختر الفروع...";

  return (
    <div className="space-y-2">
      <Combobox
        multi={true}
        options={options}
        value={multiValue}
        onChange={onChange as (value: string[]) => void}
        placeholder={
          canViewBranches ? defaultPlaceholder : "لا تملك صلاحية عرض الفروع"
        }
        searchPlaceholder={searchPlaceholder}
        emptyText={canViewBranches ? "لا توجد فروع." : "لا تملك صلاحية عرض الفروع."}
        disabled={disabled || !canViewBranches}
        className={className}
        popoverClassName={popoverClassName}
        allowClear={allowClear}
      />
      
      {/* Load More Button - shown when there are more pages */}
      {canViewBranches && hasNextPage && (
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
      {canViewBranches && isFetching && !isFetchingNextPage && (
        <div className="flex items-center justify-center p-2">
          <Loader2 className="h-4 w-4 animate-spin" />
        </div>
      )}
    </div>
  );
}

/**
 * Combobox component for selecting branches (single or multi-select).
 * Uses infinite query to load branches in pages.
 * 
 * @example
 * // Single select - Standalone usage
 * <BranchesSelect
 *   value={selectedBranchId}
 *   onChange={setSelectedBranchId}
 * />
 * 
 * @example
 * // Single select - With react-hook-form
 * <FormField
 *   control={form.control}
 *   name="branchId"
 *   render={({ field }) => (
 *     <FormItem>
 *       <FormLabel>Branch</FormLabel>
 *       <FormControl>
 *         <BranchesSelect
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
 * <BranchesSelect
 *   multi={true}
 *   value={selectedBranchIds}
 *   onChange={setSelectedBranchIds}
 * />
 * 
 * @example
 * // Multi select - With react-hook-form
 * <FormField
 *   control={form.control}
 *   name="branchIds"
 *   render={({ field }) => (
 *     <FormItem>
 *       <FormLabel>Branches</FormLabel>
 *       <FormControl>
 *         <BranchesSelect
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
export function BranchesSelect(props: BranchesSelectProps) {
  return <BranchesSelectContent {...props} />;
}

// Export with the old name for backward compatibility
export const MultiBranchesSelect = BranchesSelect;

