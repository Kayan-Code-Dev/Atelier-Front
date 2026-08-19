import { CalendarIcon } from "lucide-react"
import { useState, useEffect } from "react"

import { Button } from "@/components/ui/button"
import { Calendar } from "@/components/ui/calendar"
import { Input } from "@/components/ui/input"
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover"

function formatDate(date?: Date) {
    if (!date) return ""
    return date.toLocaleDateString("en-US", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    })
}

function isValidDate(date?: Date) {
    return !!date && !isNaN(date.getTime())
}

type CustomCalendarProps = {
    value?: string;
    onChange?: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
}

export function CustomCalendar({ value, onChange, placeholder, disabled }: CustomCalendarProps) {
    const [open, setOpen] = useState(false)
    const [date, setDate] = useState<Date | undefined>(
        value ? new Date(value) : undefined
    )
    const [month, setMonth] = useState<Date | undefined>(date)
    const [inputValue, setInputValue] = useState(formatDate(date))

    // keep input synced with form value
    useEffect(() => {
        if (value) {
            const newDate = new Date(value)
            setDate(newDate)
            setInputValue(formatDate(newDate))
        }
    }, [value])

    return (
        <div className="relative flex gap-2">
            <Input
                value={inputValue}
                placeholder={placeholder ?? "حدد التاريخ"}
                className="bg-background pr-10"
                onChange={(e) => {
                    const str = e.target.value
                    setInputValue(str)
                    const parsed = new Date(str)
                    if (isValidDate(parsed)) {
                        setDate(parsed)
                        setMonth(parsed)
                        onChange?.(parsed.toISOString()) // send ISO string to form
                    }
                }}
                onKeyDown={(e) => {
                    if (e.key === "ArrowDown") {
                        e.preventDefault()
                        setOpen(true)
                    }
                }}
                disabled={disabled}
            />

            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        variant="ghost"
                        className="absolute top-1/2 right-2 size-6 -translate-y-1/2"
                    >
                        <CalendarIcon className="size-3.5" />
                        <span className="sr-only">Select date</span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    className="w-auto overflow-hidden p-0"
                    align="end"
                    alignOffset={-8}
                    sideOffset={10}
                >
                    <Calendar
                        mode="single"
                        selected={date}
                        captionLayout="dropdown"
                        fromYear={1900}
                        toYear={2100}
                        month={month}
                        onMonthChange={setMonth}
                        onSelect={(selectedDate) => {
                            if (selectedDate) {
                                setDate(selectedDate)
                                setInputValue(formatDate(selectedDate))
                                onChange?.(selectedDate.toISOString()) // update form
                                setOpen(false)
                            }
                        }}
                    />
                </PopoverContent>
            </Popover>
        </div>
    )
}
