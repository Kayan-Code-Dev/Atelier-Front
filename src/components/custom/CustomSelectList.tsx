import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Loader2 } from "lucide-react";
import { ScrollArea } from "@/components/ui/scroll-area";
import { useQuery } from "@tanstack/react-query";
import { useListByModuleQueryOptions } from "@/api/branches-manager/employees/employees.hooks";

type Props = {
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
    switchKey: string;
    selectedId?: number;
    placeholder?: string
};

export function CustomSelectList({ value, onChange, disabled, switchKey, selectedId, placeholder }: Props) {
    const { data, isLoading } =
        useQuery(useListByModuleQueryOptions(switchKey, selectedId));

    return (
        <Select
            onValueChange={onChange}
            value={value}
            disabled={disabled || isLoading}
        >
            <SelectTrigger className="w-full [direction:rtl]">
                <SelectValue placeholder={placeholder || ""} />
            </SelectTrigger>
            <SelectContent>
                <ScrollArea className="h-[250px]">
                    {isLoading && !data && (
                        <div className="flex items-center justify-center p-2">
                            <Loader2 className="h-4 w-4 animate-spin" />
                        </div>
                    )}

                    {data?.map((li) => (
                        <SelectItem key={li.id} value={li.id.toString()}>
                            {li.name}
                        </SelectItem>
                    ))}
                </ScrollArea>
            </SelectContent>
        </Select>
    );
}
