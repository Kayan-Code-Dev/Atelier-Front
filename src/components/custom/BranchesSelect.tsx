import { Fragment, Suspense } from "react";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Loader2 } from "lucide-react";
import { ScrollArea } from "@/components/ui/scroll-area";
import { useGetInfiniteBranchesQueryOptions } from "@/api/v2/branches/branches.hooks";
import { useSuspenseInfiniteQuery } from "@tanstack/react-query";
import { useAuthStore } from "@/zustand-stores/auth.store";

type Props = {
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
  className?: string;
  placeholder?: string;
};

function BranchesSelectWithAccess({
  value,
  onChange,
  disabled,
  placeholder = "اختر الفرع...",
  className,
}: Props) {
  const { data, fetchNextPage, hasNextPage, isFetchingNextPage, isFetching } =
    useSuspenseInfiniteQuery(useGetInfiniteBranchesQueryOptions(10));

  return (
    <Select onValueChange={onChange} value={value} disabled={disabled}>
      <SelectTrigger className={className ?? "w-full"}>
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent>
        <ScrollArea className="h-[250px]">
          {/* Loading indicator for refetching */}
          {isFetching && !isFetchingNextPage && (
            <div className="flex items-center justify-center p-4">
              <Loader2 className="h-4 w-4 animate-spin" />
            </div>
          )}

          {/* Map over the pages, then map over the data in each page */}
          {data.pages.map((page, i) => (
            <Fragment key={i}>
              {page?.data.map((branch) => (
                <SelectItem key={branch.id} value={branch.id.toString()}>
                  {branch.name}
                </SelectItem>
              ))}
            </Fragment>
          ))}

          {/* "Load More" Button */}
          {hasNextPage && (
            <div className="sticky bottom-0 bg-background p-2">
              <Button
                variant="outline"
                className="w-full"
                disabled={isFetchingNextPage}
                // Use onMouseDown to prevent the Select from closing
                onMouseDown={(e) => {
                  e.preventDefault();
                  fetchNextPage();
                }}
              >
                {isFetchingNextPage ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  "تحميل المزيد"
                )}
              </Button>
            </div>
          )}
        </ScrollArea>
      </SelectContent>
    </Select>
  );
}

function BranchesSelectNoAccess({ value }: Props) {
  return (
    <Select disabled value={value}>
      <SelectTrigger className="w-full">
        <SelectValue placeholder="لا تملك صلاحية عرض الفروع" />
      </SelectTrigger>
    </Select>
  );
}

/** Permission/role names that grant access to view branches */
const BRANCH_ACCESS_ROLES = [
  "branches_manager",
  "branches_basic_view_create",
] as const;
const BRANCH_ACCESS_PERMISSIONS = [
  "branches.view",
  "branches.create",
  "branches.update",
  "branches.delete",
  "branches.export",
  "Read-Branches",
] as const;

function getPermissionStrings(perms: unknown): string[] {
  if (!Array.isArray(perms)) return [];
  return perms
    .map((p) => (typeof p === "string" ? p : (p as { name?: string })?.name))
    .filter((s): s is string => typeof s === "string" && s.length > 0);
}

function BranchesSelectContent(props: Props) {
  const loginData = useAuthStore((s) => s.loginData);
  const roles = loginData?.roles ?? [];
  const permissions = getPermissionStrings(
    loginData?.permissions ?? (loginData as { data?: { permissions?: unknown } })?.data?.permissions
  );
  const hasBranchRole =
    roles.some(
      (role) =>
        BRANCH_ACCESS_ROLES.includes(role as (typeof BRANCH_ACCESS_ROLES)[number]) ||
        role.startsWith("branches_")
    ) ?? false;
  const hasBranchPermission =
    permissions.some((p) =>
      BRANCH_ACCESS_PERMISSIONS.some((perm) => p === perm) ||
      p.startsWith("branches.")
    ) ?? false;
  const canViewBranches = hasBranchRole || hasBranchPermission;

  if (!canViewBranches) {
    return <BranchesSelectNoAccess {...props} />;
  }

  return <BranchesSelectWithAccess {...props} />;
}

function BranchesSelectSkeleton() {
  return (
    <Select disabled>
      <SelectTrigger className="w-full">
        <Loader2 className="h-4 w-4 animate-spin" />
      </SelectTrigger>
    </Select>
  );
}

export function BranchesSelect(props: Props) {
  return (
    <Suspense fallback={<BranchesSelectSkeleton />}>
      <BranchesSelectContent {...props} />
    </Suspense>
  );
}

