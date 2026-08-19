import * as React from "react";
import { ChevronDownIcon } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { Label } from "@/components/ui/label";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";

export interface DateRange {
  /** Start date of the disabled range (inclusive) */
  from: Date;
  /** End date of the disabled range (inclusive) */
  to: Date;
}

export interface DatePickerProps {
  /** The selected date value */
  value?: Date;
  /** Callback when date changes */
  onChange?: (date: Date | undefined) => void;
  /** Label text displayed above the date picker */
  label?: string;
  /** Placeholder text when no date is selected */
  placeholder?: string;
  /** Whether to show the label */
  showLabel?: boolean;
  /** Whether the component is disabled */
  disabled?: boolean;
  /** Custom className for the container */
  className?: string;
  /** Custom className for the button */
  buttonClassName?: string;
  /** Whether to allow future dates */
  allowFutureDates?: boolean;
  /** Whether to allow past dates */
  allowPastDates?: boolean;
  /** Minimum selectable date */
  fromDate?: Date;
  /** Maximum selectable date */
  toDate?: Date;
  /** Minimum selectable date (alias for fromDate, takes precedence) */
  minDate?: Date;
  /** Maximum selectable date (alias for toDate, takes precedence) */
  maxDate?: Date;
  /** Minimum selectable year */
  fromYear?: number;
  /** Maximum selectable year */
  toYear?: number;
  /** ID for the input element */
  id?: string;
  /** Array of date ranges that should be disabled */
  disabledRanges?: DateRange[];
}

export function DatePicker({
  value,
  onChange,
  label,
  placeholder = "Select date",
  showLabel = true,
  disabled = false,
  className,
  buttonClassName,
  allowFutureDates = true,
  allowPastDates = true,
  fromDate,
  toDate,
  minDate,
  maxDate,
  fromYear,
  toYear,
  id,
  disabledRanges,
}: DatePickerProps) {
  const [open, setOpen] = React.useState(false);
  const [internalDate, setInternalDate] = React.useState<Date | undefined>(
    value
  );

  // Sync internal state with controlled value
  React.useEffect(() => {
    setInternalDate(value);
  }, [value]);

  // Calculate date restrictions based on props
  const calculatedMinDate = React.useMemo(() => {
    if (minDate) return minDate;
    if (fromDate) return fromDate;
    if (!allowPastDates) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      return today;
    }
    // Default: allow dates from 1970
    const defaultMinDate = new Date(1970, 0, 1);
    defaultMinDate.setHours(0, 0, 0, 0);
    return defaultMinDate;
  }, [minDate, fromDate, allowPastDates]);

  const calculatedMaxDate = React.useMemo(() => {
    if (maxDate) return maxDate;
    if (toDate) return toDate;
    if (!allowFutureDates) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      return today;
    }
    // Default: allow dates up to 2100
    const defaultMaxDate = new Date(2100, 11, 31);
    defaultMaxDate.setHours(23, 59, 59, 999);
    return defaultMaxDate;
  }, [maxDate, toDate, allowFutureDates]);

  // Function to disable dates outside the minDate/maxDate range and within disabled ranges
  const isDateDisabled = React.useCallback(
    (date: Date) => {
      const dateOnly = new Date(date);
      dateOnly.setHours(0, 0, 0, 0);

      // Check if date is before minimum date
      if (calculatedMinDate) {
        const minDateOnly = new Date(calculatedMinDate);
        minDateOnly.setHours(0, 0, 0, 0);
        if (dateOnly < minDateOnly) {
          return true;
        }
      }

      // Check if date is after maximum date
      if (calculatedMaxDate) {
        const maxDateOnly = new Date(calculatedMaxDate);
        maxDateOnly.setHours(0, 0, 0, 0);
        if (dateOnly > maxDateOnly) {
          return true;
        }
      }

      // Check if date falls within any disabled range
      if (disabledRanges && disabledRanges.length > 0) {
        for (const range of disabledRanges) {
          const rangeFrom = new Date(range.from);
          rangeFrom.setHours(0, 0, 0, 0);
          const rangeTo = new Date(range.to);
          rangeTo.setHours(23, 59, 59, 999);

          if (dateOnly >= rangeFrom && dateOnly <= rangeTo) {
            return true;
          }
        }
      }

      return false;
    },
    [calculatedMinDate, calculatedMaxDate, disabledRanges]
  );

  const handleDateSelect = (selectedDate: Date | undefined) => {
    setInternalDate(selectedDate);
    onChange?.(selectedDate);
    if (selectedDate) {
      setOpen(false);
    }
  };

  return (
    <div className={cn("flex flex-col gap-3", className)}>
      {showLabel && label && (
        <Label htmlFor={id || "date-picker"} className="px-1">
          {label}
        </Label>
      )}
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            id={id || "date-picker"}
            disabled={disabled}
            className={cn(" justify-between font-normal", buttonClassName)}
          >
            {internalDate ? internalDate.toLocaleDateString() : placeholder}
            <ChevronDownIcon />
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto overflow-hidden p-0" align="start">
          <Calendar
            mode="single"
            selected={internalDate}
            captionLayout="dropdown"
            fromDate={calculatedMinDate}
            toDate={calculatedMaxDate}
            fromYear={fromYear || calculatedMinDate?.getFullYear() || 1970}
            toYear={toYear || calculatedMaxDate?.getFullYear() || 2100}
            disabled={isDateDisabled}
            onSelect={handleDateSelect}
          />
        </PopoverContent>
      </Popover>
    </div>
  );
}
