import { format } from "date-fns";
import { ChevronDownIcon } from "lucide-react";
import * as React from "react";

import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";

export interface SimpleDateTimePickerProps {
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
  /** ID for the input element */
  id?: string;
}

export function SimpleDateTimePicker({
  value,
  onChange,
  label,
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
  id,
}: SimpleDateTimePickerProps) {
  const [open, setOpen] = React.useState(false);
  const [internalDate, setInternalDate] = React.useState<Date | undefined>(
    value
  );
  const [timeValue, setTimeValue] = React.useState<string>("");

  // Sync internal state with controlled value
  React.useEffect(() => {
    if (value) {
      setInternalDate(value);
      const hours = value.getHours().toString().padStart(2, "0");
      const minutes = value.getMinutes().toString().padStart(2, "0");
      setTimeValue(`${hours}:${minutes}`);
    } else {
      setInternalDate(undefined);
      setTimeValue("");
    }
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
      today.setHours(23, 59, 59, 999);
      return today;
    }
    // Default: allow dates up to 2100
    const defaultMaxDate = new Date(2100, 11, 31);
    defaultMaxDate.setHours(23, 59, 59, 999);
    return defaultMaxDate;
  }, [maxDate, toDate, allowFutureDates]);

  // Function to disable dates outside the minDate/maxDate range
  const isDateDisabled = React.useCallback(
    (date: Date) => {
      const dateOnly = new Date(date);
      dateOnly.setHours(0, 0, 0, 0);

      if (calculatedMinDate) {
        const minDateOnly = new Date(calculatedMinDate);
        minDateOnly.setHours(0, 0, 0, 0);
        if (dateOnly < minDateOnly) {
          return true;
        }
      }

      if (calculatedMaxDate) {
        const maxDateOnly = new Date(calculatedMaxDate);
        maxDateOnly.setHours(0, 0, 0, 0);
        if (dateOnly > maxDateOnly) {
          return true;
        }
      }

      return false;
    },
    [calculatedMinDate, calculatedMaxDate]
  );

  const handleDateSelect = (selectedDate: Date | undefined) => {
    if (selectedDate) {
      // Preserve time if it exists, otherwise set to current time
      const newDate = new Date(selectedDate);
      if (timeValue) {
        const [hours, minutes] = timeValue.split(":").map(Number);
        newDate.setHours(hours || 0, minutes || 0, 0, 0);
      } else {
        const now = new Date();
        newDate.setHours(now.getHours(), now.getMinutes(), 0, 0);
      }
      setInternalDate(newDate);
      onChange?.(newDate);
    } else {
      setInternalDate(undefined);
      onChange?.(undefined);
    }
  };

  const handleTimeChange = (time: string) => {
    setTimeValue(time);
    if (internalDate && time) {
      const [hours, minutes] = time.split(":").map(Number);
      const newDate = new Date(internalDate);
      newDate.setHours(hours || 0, minutes || 0, 0, 0);
      setInternalDate(newDate);
      onChange?.(newDate);
    }
  };

  return (
    <div className={cn("flex flex-col gap-3", className)}>
      {showLabel && label && (
        <Label htmlFor={id || "datetime-picker"} className="px-1">
          {label}
        </Label>
      )}
      <div className="flex gap-2">
        <Popover open={open} onOpenChange={setOpen}>
          <PopoverTrigger asChild>
            <Button
              variant="outline"
              id={id || "datetime-picker"}
              disabled={disabled}
              className={cn(
                "flex-1 justify-between font-normal",
                buttonClassName
              )}
            >
              <span className="truncate">
                {internalDate
                  ? format(internalDate, "yyyy-MM-dd")
                  : "اختر التاريخ"}
              </span>
              <ChevronDownIcon className="h-4 w-4" />
            </Button>
          </PopoverTrigger>
          <PopoverContent className="w-auto overflow-hidden p-0" align="start">
            <Calendar
              mode="single"
              selected={internalDate}
              captionLayout="dropdown"
              fromDate={calculatedMinDate}
              toDate={calculatedMaxDate}
              fromYear={calculatedMinDate?.getFullYear() || 1970}
              toYear={calculatedMaxDate?.getFullYear() || 2100}
              disabled={isDateDisabled}
              onSelect={handleDateSelect}
            />
          </PopoverContent>
        </Popover>
        <Input
          type="time"
          value={timeValue}
          onChange={(e) => handleTimeChange(e.target.value)}
          disabled={disabled || !internalDate}
          className="w-32"
          placeholder="الوقت"
        />
      </div>
    </div>
  );
}
