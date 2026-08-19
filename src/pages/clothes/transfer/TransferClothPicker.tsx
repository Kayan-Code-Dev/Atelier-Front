import { useEffect, useMemo, useRef, useState } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
import { ChevronsUpDown, Loader2 } from "lucide-react";

import { useGetInfiniteClothesQueryOptions } from "@/api/v2/clothes/clothes.hooks";
import type { TClothResponse } from "@/api/v2/clothes/clothes.types";
import { Button } from "@/components/ui/button";
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";
import useDebounce from "@/hooks/useDebounce";

type Props = {
  /** نافذة النقل مفتوحة */
  modalOpen: boolean;
  value: TClothResponse | null;
  onChange: (cloth: TClothResponse | null) => void;
  disabled?: boolean;
  placeholder?: string;
};

/**
 * اختيار منتج لطلب النقل: زر يفتح قائمة منبثقة مع حقل بحث يطلب الـ API (صفحات).
 * عند الفتح تُحمَّل أول دفعة بدون اشتراط كتابة نص؛ البحث بالخادم عند حرفين أو أكثر.
 */
export function TransferClothPicker({
  modalOpen,
  value,
  onChange,
  disabled = false,
  placeholder = "— اختر المنتج —",
}: Props) {
  const [popoverOpen, setPopoverOpen] = useState(false);
  const [search, setSearch] = useState("");
  const triggerRef = useRef<HTMLButtonElement>(null);
  const [popoverWidth, setPopoverWidth] = useState<number | undefined>(
    undefined
  );

  const debouncedSearch = useDebounce({
    value: search.trim(),
    delay: 350,
  });

  const searchParam =
    debouncedSearch.length >= 2 ? debouncedSearch : undefined;

  const {
    data,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isLoading,
    isFetching,
  } = useInfiniteQuery({
    ...useGetInfiniteClothesQueryOptions({
      per_page: 40,
      ...(searchParam ? { search: searchParam } : {}),
    }),
    enabled: modalOpen && popoverOpen,
  });

  const clothes = useMemo(() => {
    if (!data?.pages?.length) return [];
    const map = new Map<number, TClothResponse>();
    for (const page of data.pages) {
      for (const c of page?.data ?? []) {
        map.set(c.id, c);
      }
    }
    return [...map.values()];
  }, [data?.pages]);

  useEffect(() => {
    if (!popoverOpen) {
      setSearch("");
    }
  }, [popoverOpen]);

  useEffect(() => {
    if (popoverOpen && triggerRef.current) {
      setPopoverWidth(triggerRef.current.offsetWidth);
    }
  }, [popoverOpen]);

  const triggerLabel = value
    ? `${value.code}${
        value.category_name ? ` · ${value.category_name}` : ""
      }`
    : placeholder;

  return (
    <Popover open={popoverOpen} onOpenChange={setPopoverOpen}>
      <PopoverTrigger asChild>
        <Button
          ref={triggerRef}
          type="button"
          variant="outline"
          role="combobox"
          aria-expanded={popoverOpen}
          disabled={disabled}
          className={cn(
            "w-full min-h-[42px] justify-between rounded-xl border-slate-200 bg-white py-2.5 px-3 text-sm font-normal text-slate-800 shadow-none hover:bg-slate-50/80"
          )}
        >
          <span className="truncate text-right font-mono font-bold">
            {triggerLabel}
          </span>
          <ChevronsUpDown className="mr-2 h-4 w-4 shrink-0 opacity-40" />
        </Button>
      </PopoverTrigger>
      <PopoverContent
        className="p-0 z-[100]"
        style={{ width: popoverWidth ? Math.max(popoverWidth, 300) : 320 }}
        align="start"
        side="bottom"
        sideOffset={4}
        collisionPadding={16}
        onOpenAutoFocus={(e) => e.preventDefault()}
        dir="rtl"
      >
        <Command shouldFilter={false}>
          <CommandInput
            placeholder="ابحث بالكود أو الاسم (حرفان للبحث في الخادم)..."
            value={search}
            onValueChange={setSearch}
            className="h-10"
          />
          <CommandList className="max-h-[min(320px,50vh)]">
            {isLoading ? (
              <div className="flex justify-center py-10 text-slate-400">
                <Loader2 className="h-7 w-7 animate-spin" />
              </div>
            ) : (
              <>
                <CommandEmpty className="py-6 text-center text-sm text-slate-500">
                  {isFetching && !clothes.length
                    ? "جاري البحث..."
                    : "لا توجد منتجات."}
                </CommandEmpty>
                <CommandGroup>
                  {clothes.map((c) => (
                      <CommandItem
                        key={c.id}
                        value={String(c.id)}
                        onSelect={() => {
                          onChange(c);
                          setPopoverOpen(false);
                        }}
                        className="cursor-pointer py-2.5 text-right"
                      >
                        <span className="flex flex-col gap-0.5 w-full min-w-0">
                          <span className="font-mono font-black text-slate-800 text-sm truncate">
                            {c.code}
                          </span>
                          <span className="text-xs text-slate-500 truncate">
                            {c.category_name || c.cloth_type_name || "—"}
                          </span>
                        </span>
                      </CommandItem>
                    ))}
                </CommandGroup>
                {hasNextPage ? (
                  <div className="border-t border-slate-100 p-2">
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      className="w-full h-9 text-xs font-bold text-slate-600"
                      disabled={isFetchingNextPage}
                      onClick={(e) => {
                        e.preventDefault();
                        fetchNextPage();
                      }}
                    >
                      {isFetchingNextPage ? (
                        <>
                          <Loader2 className="ml-2 h-4 w-4 animate-spin" />
                          جاري التحميل...
                        </>
                      ) : (
                        "تحميل المزيد"
                      )}
                    </Button>
                  </div>
                ) : null}
              </>
            )}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
