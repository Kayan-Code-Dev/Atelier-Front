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
import { useCountriesByModuleQueryOptions } from "@/api/v2/content-managment/country/country.hooks";

type Props = {
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
    switchKey: string;
};

export function CountriesSelectByModule({ value, onChange, disabled, switchKey }: Props) {
    const { data, isLoading } = useQuery(useCountriesByModuleQueryOptions(switchKey));

    return (
        <Select
            onValueChange={onChange}
            value={value}
            disabled={disabled || isLoading}
        >
            <SelectTrigger className="w-full [direction:rtl]">
                <SelectValue placeholder="اختر الدولة..." />
            </SelectTrigger>
            <SelectContent>
                <ScrollArea className="h-[250px]">
                    {isLoading && !data && (
                        <div className="flex items-center justify-center p-2">
                            <Loader2 className="h-4 w-4 animate-spin" />
                        </div>
                    )}

                    {data?.map((country) => (
                        <SelectItem key={country.id} value={country.id.toString()}>
                            {country.name}
                        </SelectItem>
                    ))}
                </ScrollArea>
            </SelectContent>
        </Select>
    );
}
