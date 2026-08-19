import { useMemo } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Combobox, ComboboxOption } from "@/components/ui/combobox";
import { useGetInfiniteDepartmentsQueryOptions } from "@/api/v2/content-managment/departments/departments.hooks";
import { Loader2 } from "lucide-react";
import { useHasPermission } from "@/api/auth/auth.hooks";

export interface DepartmentsSelectProps {
  value?: string;
  onChange?: (value: string) => void;
  placeholder?: string;
  disabled?: boolean;
  className?: string;
  /** Exclude specific department IDs from the list */
  excludeIds?: number[];
  /** Whether to allow clearing the selection */
  allowClear?: boolean;
}

export function DepartmentsSelect({
  value,
  onChange,
  placeholder = "اختر القسم...",
  disabled = false,
  className,
  excludeIds = [],
  allowClear = true,
}: DepartmentsSelectProps) {
  const { hasPermission, isPending } = useHasPermission("hr.employees.view");
  const {
    data: departmentsData,
    isLoading,
  } = useInfiniteQuery({
    ...useGetInfiniteDepartmentsQueryOptions(100),
    enabled: hasPermission,
  });

  // Flatten all pages into a single array and filter excluded IDs
  const allDepartments = useMemo(() => {
    const departments =
      departmentsData?.pages.flatMap((page) => page?.data || []) || [];
    return departments.filter(
      (dept) => !excludeIds.includes(dept.id)
    );
  }, [departmentsData, excludeIds]);

  // Convert departments to ComboboxOption format
  const options: ComboboxOption[] = useMemo(
    () =>
      allDepartments.map((dept) => ({
        value: String(dept.id),
        label: dept.name,
      })),
    [allDepartments]
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
        لا تملك صلاحية عرض الأقسام
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

  return (
    <Combobox
      options={options}
      value={value}
      onChange={onChange}
      placeholder={placeholder}
      searchPlaceholder="ابحث عن قسم..."
      emptyText="لا يوجد أقسام."
      className={className}
      disabled={disabled}
      allowClear={allowClear}
    />
  );
}

