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
import { useCitiesByCountryModuleQueryOptions } from "@/api/v2/content-managment/city/city.hooks";

type Props = {
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
    switchKey?: string;
    selectedCountryId: number
};

export function CitiesSelectByModule({ value, onChange, disabled, switchKey, selectedCountryId }: Props) {
    const { data, isLoading } =
        useQuery(useCitiesByCountryModuleQueryOptions(selectedCountryId, switchKey));

    return (
        <Select
            onValueChange={onChange}
            value={value}
            disabled={disabled || !selectedCountryId || isLoading}
        >
            <SelectTrigger className="w-full [direction:rtl]">
                <SelectValue placeholder="اختر المدينة..." />
            </SelectTrigger>
            <SelectContent>
                <ScrollArea className="h-[250px]">
                    {isLoading && !data && (
                        <div className="flex items-center justify-center p-2">
                            <Loader2 className="h-4 w-4 animate-spin" />
                        </div>
                    )}

                    {data?.map((city) => (
                        <SelectItem key={city.id} value={city.id.toString()}>
                            {city.name}
                        </SelectItem>
                    ))}
                </ScrollArea>
            </SelectContent>
        </Select>
    );
}
