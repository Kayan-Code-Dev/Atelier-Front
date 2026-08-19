import * as React from "react"
import { Check, ChevronsUpDown, X } from "lucide-react"

import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover"

export type ComboboxOption = {
  value: string
  label: string
}

// Single select props
export interface ComboboxPropsSingle {
  /** Array of options to display in the combobox */
  options: ComboboxOption[]
  /** Current selected value (single mode) */
  value?: string
  /** Callback when value changes (single mode) */
  onChange?: (value: string) => void
  /** Placeholder text when no option is selected */
  placeholder?: string
  /** Placeholder text for the search input */
  searchPlaceholder?: string
  /** Text to show when no options match the search */
  emptyText?: string
  /** Additional CSS classes for the trigger button */
  className?: string
  /** Additional CSS classes for the popover content */
  popoverClassName?: string
  /** Whether the combobox is disabled */
  disabled?: boolean
  /** Whether clicking the selected option clears the selection */
  allowClear?: boolean
  /** Whether to allow multiple selections */
  multi?: false
}

// Multi select props
export interface ComboboxPropsMulti {
  /** Array of options to display in the combobox */
  options: ComboboxOption[]
  /** Current selected values (multi mode) */
  value?: string[]
  /** Callback when value changes (multi mode) */
  onChange?: (value: string[]) => void
  /** Placeholder text when no option is selected */
  placeholder?: string
  /** Placeholder text for the search input */
  searchPlaceholder?: string
  /** Text to show when no options match the search */
  emptyText?: string
  /** Additional CSS classes for the trigger button */
  className?: string
  /** Additional CSS classes for the popover content */
  popoverClassName?: string
  /** Whether the combobox is disabled */
  disabled?: boolean
  /** Whether clicking the selected option clears the selection */
  allowClear?: boolean
  /** Whether to allow multiple selections */
  multi: true
}

export type ComboboxProps = ComboboxPropsSingle | ComboboxPropsMulti

/**
 * Reusable Combobox component compatible with react-hook-form.
 * Supports both single and multi-select modes.
 * 
 * @example
 * // Single select - Standalone usage
 * <Combobox
 *   options={[
 *     { value: "1", label: "Option 1" },
 *     { value: "2", label: "Option 2" }
 *   ]}
 *   value={selectedValue}
 *   onChange={setSelectedValue}
 * />
 * 
 * @example
 * // Single select - With react-hook-form
 * <FormField
 *   control={form.control}
 *   name="fieldName"
 *   render={({ field }) => (
 *     <FormItem>
 *       <FormLabel>Label</FormLabel>
 *       <FormControl>
 *         <Combobox
 *           options={options}
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
 * <Combobox
 *   multi={true}
 *   options={[
 *     { value: "1", label: "Option 1" },
 *     { value: "2", label: "Option 2" }
 *   ]}
 *   value={selectedValues}
 *   onChange={setSelectedValues}
 * />
 * 
 * @example
 * // Multi select - With react-hook-form
 * <FormField
 *   control={form.control}
 *   name="fieldName"
 *   render={({ field }) => (
 *     <FormItem>
 *       <FormLabel>Label</FormLabel>
 *       <FormControl>
 *         <Combobox
 *           multi={true}
 *           options={options}
 *           value={field.value || []}
 *           onChange={field.onChange}
 *         />
 *       </FormControl>
 *       <FormMessage />
 *     </FormItem>
 *   )}
 * />
 */

export function Combobox({
  options,
  value,
  onChange,
  placeholder = "Select option...",
  searchPlaceholder = "Search...",
  emptyText = "No option found.",
  className,
  popoverClassName,
  disabled = false,
  allowClear = false,
  multi = false,
}: ComboboxProps) {
  const [open, setOpen] = React.useState(false)
  const triggerRef = React.useRef<HTMLButtonElement>(null)
  const [popoverWidth, setPopoverWidth] = React.useState<number | undefined>(undefined)

  // Single select mode
  if (!multi) {
    const singleValue = (value as string) || ""
    const selectedOption = React.useMemo(
      () => options.find((option) => option.value === singleValue),
      [options, singleValue]
    )

    const handleSelect = (currentValue: string) => {
      const newValue = currentValue === singleValue && allowClear ? "" : currentValue
      ;(onChange as (value: string) => void)?.(newValue)
      setOpen(false)
    }

    React.useEffect(() => {
      if (open && triggerRef.current) {
        setPopoverWidth(triggerRef.current.offsetWidth)
      }
    }, [open])

    return (
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            ref={triggerRef}
            variant="outline"
            role="combobox"
            aria-expanded={open}
            disabled={disabled}
            className={cn("w-full justify-between", className)}
          >
            <span className="truncate">
              {selectedOption ? selectedOption.label : placeholder}
            </span>
            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
          </Button>
        </PopoverTrigger>
        <PopoverContent 
          className={cn("p-0 z-[100]", popoverClassName)} 
          style={{ width: popoverWidth }}
          align="start"
          side="bottom"
          sideOffset={4}
          collisionPadding={16}
          avoidCollisions={true}
        >
          <Command>
            <CommandInput placeholder={searchPlaceholder} className="h-9" />
            <CommandList 
              className="max-h-[200px]"
              onWheel={(e) => {
                // Ensure wheel events work for scrolling
                const target = e.currentTarget
                if (target.scrollHeight > target.clientHeight) {
                  e.stopPropagation()
                }
              }}
            >
              <CommandEmpty>{emptyText}</CommandEmpty>
              <CommandGroup>
                {options.map((option) => (
                  <CommandItem
                    key={option.value}
                    value={option.value}
                    onSelect={handleSelect}
                  >
                    {option.label}
                    <Check
                      className={cn(
                        "ml-auto h-4 w-4",
                        singleValue === option.value ? "opacity-100" : "opacity-0"
                      )}
                    />
                  </CommandItem>
                ))}
              </CommandGroup>
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>
    )
  }

  // Multi select mode
  const multiValue = (value as string[]) || []
  const selectedOptions = React.useMemo(
    () => options.filter((option) => multiValue.includes(option.value)),
    [options, multiValue]
  )

  const handleMultiSelect = (currentValue: string) => {
    const newValue = multiValue.includes(currentValue)
      ? multiValue.filter((v) => v !== currentValue)
      : [...multiValue, currentValue]
    ;(onChange as (value: string[]) => void)?.(newValue)
    // Don't close popover in multi-select mode
  }

  const handleRemove = (valueToRemove: string, e: React.MouseEvent) => {
    e.stopPropagation()
    const newValue = multiValue.filter((v) => v !== valueToRemove)
    ;(onChange as (value: string[]) => void)?.(newValue)
  }

  React.useEffect(() => {
    if (open && triggerRef.current) {
      setPopoverWidth(triggerRef.current.offsetWidth)
    }
  }, [open])

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          ref={triggerRef}
          variant="outline"
          role="combobox"
          aria-expanded={open}
          disabled={disabled}
          className={cn("w-full justify-between min-h-9 h-auto", className)}
        >
          <div className="flex flex-1 flex-wrap gap-1 items-center">
            {selectedOptions.length > 0 ? (
              selectedOptions.map((option) => (
                <Badge
                  key={option.value}
                  variant="secondary"
                  className="mr-1 mb-1"
                >
                  {option.label}
                  <button
                    type="button"
                    className="ml-1 ring-offset-background rounded-full outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    onKeyDown={(e) => {
                      if (e.key === "Enter") {
                        handleRemove(option.value, e as any)
                      }
                    }}
                    onMouseDown={(e) => {
                      e.preventDefault()
                      e.stopPropagation()
                    }}
                    onClick={(e) => handleRemove(option.value, e)}
                  >
                    <X className="h-3 w-3 text-muted-foreground hover:text-foreground" />
                  </button>
                </Badge>
              ))
            ) : (
              <span className="text-muted-foreground">{placeholder}</span>
            )}
          </div>
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent 
        className={cn("p-0 z-[100]", popoverClassName)} 
        style={{ width: popoverWidth }}
        align="start"
        side="bottom"
        sideOffset={4}
        collisionPadding={16}
        avoidCollisions={true}
      >
        <Command>
          <CommandInput placeholder={searchPlaceholder} className="h-9" />
          <CommandList 
            className="max-h-[250px]"
            onWheel={(e) => {
              // Ensure wheel events work for scrolling
              const target = e.currentTarget
              if (target.scrollHeight > target.clientHeight) {
                e.stopPropagation()
              }
            }}
          >
            <CommandEmpty>{emptyText}</CommandEmpty>
            <CommandGroup>
              {options.map((option) => {
                const isSelected = multiValue.includes(option.value)
                return (
                  <CommandItem
                    key={option.value}
                    value={option.value}
                    onSelect={handleMultiSelect}
                  >
                    {option.label}
                    <Check
                      className={cn(
                        "ml-auto h-4 w-4",
                        isSelected ? "opacity-100" : "opacity-0"
                      )}
                    />
                  </CommandItem>
                )
              })}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  )
}
