import { useQuery } from "@tanstack/react-query";
import { useMemo } from "react";
import { Loader2 } from "lucide-react";
import { Combobox, ComboboxOption } from "@/components/ui/combobox";
import { useGetEmployeeCustodyTypesQueryOptions } from "@/api/v2/employees/employee-custodies/employee-custodies.hooks";
import { useHasPermission } from "@/api/auth/auth.hooks";

export type EmployeeCustodyTypesSelectProps = {
  value?: string;
  onChange?: (value: string) => void;
  placeholder?: string;
  searchPlaceholder?: string;
  emptyText?: string;
  className?: string;
  popoverClassName?: string;
  disabled?: boolean;
  allowClear?: boolean;
};

export function EmployeeCustodyTypesSelect({
  value,
  onChange,
  placeholder = "اختر نوع الضمان...",
  searchPlaceholder = "ابحث عن نوع الضمان...",
  emptyText = "لا يوجد أنواع ضمانات.",
  className,
  popoverClassName,
  disabled = false,
  allowClear = false,
}: EmployeeCustodyTypesSelectProps) {
  const { hasPermission, isPending } = useHasPermission("hr.custody.view");
  const { data, isLoading, isError } = useQuery({
    ...useGetEmployeeCustodyTypesQueryOptions(),
    enabled: hasPermission,
  });

  // Transform the data to ComboboxOption format
  const options: ComboboxOption[] = useMemo(() => {
    if (!data) return [];
    return data.map((type) => ({
      value: type.key,
      label: type.name,
    }));
  }, [data]);

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
        لا تملك صلاحية عرض أنواع الضمانات
      </div>
    );
  }

  // Show loader while loading
  if (isLoading) {
    return (
      <div className="flex h-9 w-full items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-sm">
        <Loader2 className="h-4 w-4 animate-spin" />
        <span className="mr-2">جاري التحميل...</span>
      </div>
    );
  }

  // Show error state (optional - you can customize this)
  if (isError) {
    return (
      <div className="flex h-9 w-full items-center justify-center rounded-md border border-destructive bg-destructive/10 px-3 py-2 text-sm text-destructive">
        حدث خطأ أثناء جلب أنواع الضمانات
      </div>
    );
  }

  return (
    <Combobox
      options={options}
      value={value}
      onChange={onChange}
      placeholder={placeholder}
      searchPlaceholder={searchPlaceholder}
      emptyText={emptyText}
      className={className}
      popoverClassName={popoverClassName}
      disabled={disabled}
      allowClear={allowClear}
    />
  );
}

