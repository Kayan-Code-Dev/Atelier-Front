import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { BranchesSelect } from "./BranchesSelect";
import { FactoriesSelect } from "./FactoriesSelect";
import { WorkshopsSelect } from "./WorkshopsSelect";
import { TEntity } from "@/lib/types/entity.types";
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import {
  Control,
  FieldPath,
  FieldValues,
  useFormContext,
  useWatch,
} from "react-hook-form";

// Standalone props (for filters)
type StandaloneProps = {
  entityType?: TEntity;
  entityId?: string;
  onEntityTypeChange?: (value: TEntity | undefined) => void;
  onEntityIdChange?: (value: string) => void;
  disabled?: boolean;
  entityTypeLabel?: string;
  entityIdLabel?: string;
  required?: boolean;
  className?: string; // Custom className
};

// Form props (for react-hook-form)
type FormProps<TFieldValues extends FieldValues = FieldValues> = {
  control: Control<TFieldValues>;
  entityTypeName: FieldPath<TFieldValues>;
  entityIdName: FieldPath<TFieldValues>;
  entityTypeLabel?: string;
  entityIdLabel?: string;
  disabled?: boolean;
  className?: string; // Custom className
};

type EntitySelectProps<TFieldValues extends FieldValues = FieldValues> =
  | ({ mode: "form" } & FormProps<TFieldValues>)
  | ({ mode?: "standalone" } & StandaloneProps);

const ENTITY_OPTIONS: { value: TEntity; label: string }[] = [
  { value: "branch", label: "فرع" },
  { value: "factory", label: "مصنع" },
  { value: "workshop", label: "ورشة" },
];

export function EntitySelect<TFieldValues extends FieldValues = FieldValues>(
  props: EntitySelectProps<TFieldValues>
) {
  if (props.mode === "form") {
    return <EntitySelectForm {...props} />;
  }

  return <EntitySelectStandalone {...props} />;
}

function EntitySelectForm<TFieldValues extends FieldValues = FieldValues>({
  control,
  entityTypeName,
  entityIdName,
  entityTypeLabel = "نوع المكان",
  entityIdLabel = "المكان",
  disabled,
  className = "",
}: FormProps<TFieldValues>) {
  const { setValue } = useFormContext<TFieldValues>();

  return (
    <div className={`w-full space-y-4 ${className}`}>
      <FormField
        control={control}
        name={entityTypeName}
        render={({ field }) => (
          <FormItem className="w-full">
            <FormLabel>{entityTypeLabel}</FormLabel>
            <FormControl>
              <Select
                onValueChange={(value) => {
                  field.onChange(value);
                  // Reset entityId when entityType changes
                  setValue(entityIdName, "" as any);
                }}
                value={field.value || ""}
                disabled={disabled}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="اختر نوع المكان..." />
                </SelectTrigger>
                <SelectContent>
                  {ENTITY_OPTIONS.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </FormControl>
            <FormMessage />
          </FormItem>
        )}
      />

      <FormField
        control={control}
        name={entityIdName}
        render={({ field }) => {
          const entityType = useWatch({
            control,
            name: entityTypeName,
          }) as TEntity | undefined;

          return (
            <FormItem className="w-full">
              <FormLabel>{entityIdLabel}</FormLabel>
              <FormControl>
                {entityType === "branch" ? (
                  <BranchesSelect
                    value={field.value || ""}
                    onChange={field.onChange}
                    disabled={disabled || !entityType}
                    className="w-full"
                  />
                ) : entityType === "factory" ? (
                  <FactoriesSelect
                    value={field.value || ""}
                    onChange={field.onChange}
                    disabled={disabled || !entityType}
                    className="w-full"
                  />
                ) : entityType === "workshop" ? (
                  <WorkshopsSelect
                    value={field.value || ""}
                    onChange={field.onChange}
                    disabled={disabled || !entityType}
                    className="w-full"
                  />
                ) : (
                  <Select disabled>
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="اختر نوع المكان أولاً..." />
                    </SelectTrigger>
                  </Select>
                )}
              </FormControl>
              <FormMessage />
            </FormItem>
          );
        }}
      />
    </div>
  );
}

function EntitySelectStandalone({
  entityType,
  entityId,
  onEntityTypeChange,
  onEntityIdChange,
  disabled,
  entityTypeLabel = "نوع المكان",
  entityIdLabel = "المكان",
  required,
  className = "",
}: StandaloneProps) {
  const handleEntityTypeChange = (value: string) => {
    const typedValue = value as TEntity;
    onEntityTypeChange?.(typedValue);
    // Reset entityId when entityType changes
    onEntityIdChange?.("");
  };

  return (
    <div className={`w-full space-y-4 ${className}`}>
      <div className="space-y-2 w-full">
        {entityTypeLabel && (
          <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            {entityTypeLabel}
            {required && <span className="text-destructive"> *</span>}
          </label>
        )}
        <Select 
          onValueChange={handleEntityTypeChange}
          value={entityType || ""}
          disabled={disabled}
        >
          <SelectTrigger className="w-full">
            <SelectValue placeholder="اختر نوع المكان..." />
          </SelectTrigger>
          <SelectContent>
            {ENTITY_OPTIONS.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="space-y-2 w-full">
        {entityIdLabel && (
          <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            {entityIdLabel}
            {required && <span className="text-destructive"> *</span>}
          </label>
        )}
        {entityType === "branch" ? (
          <BranchesSelect
            value={entityId || ""}
            onChange={onEntityIdChange || (() => {})}
            disabled={disabled || !entityType}
            className="w-full"
          />
        ) : entityType === "factory" ? (
          <FactoriesSelect
            value={entityId || ""}
            onChange={onEntityIdChange || (() => {})}
            disabled={disabled || !entityType}
            className="w-full"
          />
        ) : entityType === "workshop" ? (
          <WorkshopsSelect
            value={entityId || ""}
            onChange={onEntityIdChange || (() => {})}
            disabled={disabled || !entityType}
            className="w-full"
          />
        ) : (
          <Select disabled>
            <SelectTrigger className="w-full">
              <SelectValue placeholder="اختر نوع المكان أولاً..." />
            </SelectTrigger>
          </Select>
        )}
      </div>
    </div>
  );
}