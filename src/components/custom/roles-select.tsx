import { useMemo } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Combobox, ComboboxOption } from "@/components/ui/combobox";
import { useGetRolesInfiniteQueryOptions } from "@/api/v2/content-managment/roles/roles.hooks";
import { getRoleLabel } from "@/lib/roleLabels";
import { Loader2 } from "lucide-react";
import { useHasPermission } from "@/api/auth/auth.hooks";

// Single select props
export interface RolesSelectPropsSingle {
  value?: string;
  onChange?: (value: string) => void;
  placeholder?: string;
  disabled?: boolean;
  className?: string;
  /** Exclude specific role IDs from the list */
  excludeIds?: number[];
  /** Whether to allow clearing the selection */
  allowClear?: boolean;
  /** Whether to allow multiple selections */
  multi?: false;
}

// Multi select props
export interface RolesSelectPropsMulti {
  value?: string[];
  onChange?: (value: string[]) => void;
  placeholder?: string;
  disabled?: boolean;
  className?: string;
  /** Exclude specific role IDs from the list */
  excludeIds?: number[];
  /** Whether to allow clearing the selection */
  allowClear?: boolean;
  /** Whether to allow multiple selections */
  multi: true;
}

export type RolesSelectProps = RolesSelectPropsSingle | RolesSelectPropsMulti;

export function RolesSelect({
  value,
  onChange,
  placeholder = "اختر الدور...",
  disabled = false,
  className,
  excludeIds = [],
  allowClear = true,
  multi = false,
}: RolesSelectProps) {
  const { hasPermission, isPending } = useHasPermission("roles.view");
  const { data: rolesData, isLoading } = useInfiniteQuery({
    ...useGetRolesInfiniteQueryOptions(100),
    enabled: hasPermission,
  });

  // Flatten all pages into a single array and filter excluded IDs
  const allRoles = useMemo(() => {
    const roles = rolesData?.pages.flatMap((page) => page?.data || []) || [];
    return roles.filter((role) => !excludeIds.includes(role.id));
  }, [rolesData, excludeIds]);

  // Convert roles to ComboboxOption format (Arabic label for display)
  const options: ComboboxOption[] = useMemo(
    () =>
      allRoles.map((role) => ({
        value: String(role.id),
        label: getRoleLabel(role.name),
      })),
    [allRoles]
  );

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
        لا تملك صلاحية عرض الأدوار
      </div>
    );
  }

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
    return (
      <Combobox
        options={options}
        value={value as string}
        onChange={onChange as (value: string) => void}
        placeholder={placeholder}
        searchPlaceholder="ابحث عن دور..."
        emptyText="لا يوجد أدوار."
        className={className}
        disabled={disabled}
        allowClear={allowClear}
        multi={false}
      />
    );
  }

  // Multi select mode
  return (
    <Combobox
      options={options}
      value={value as string[]}
      onChange={onChange as (value: string[]) => void}
      placeholder={placeholder}
      searchPlaceholder="ابحث عن دور..."
      emptyText="لا يوجد أدوار."
      className={className}
      disabled={disabled}
      allowClear={allowClear}
      multi={true}
    />
  );
}
