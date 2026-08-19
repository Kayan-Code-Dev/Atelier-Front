import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { DatePicker } from "@/components/custom/DatePicker";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import { DepartmentsSelect } from "@/components/custom/departments-select";
import type { TDashboardOverviewParams } from "@/api/v2/dashboard/dashboard.types";
import { PERIOD_OPTIONS } from "../constants/dashboard.constants";

type DashboardFiltersProps = {
  filters: TDashboardOverviewParams;
  dateFrom: Date | undefined;
  dateTo: Date | undefined;
  onFilterChange: (key: keyof TDashboardOverviewParams, value: unknown) => void;
  onDateFromChange: (date: Date | undefined) => void;
  onDateToChange: (date: Date | undefined) => void;
  onReset: () => void;
};

const inputBase =
  "h-10 rounded-xl border border-border bg-background px-3.5 text-[13px] font-medium transition-all outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-1";

export function DashboardFilters({
  filters,
  dateFrom,
  dateTo,
  onFilterChange,
  onDateFromChange,
  onDateToChange,
  onReset,
}: DashboardFiltersProps) {
  return (
    <div
      className="rounded-2xl overflow-hidden"
      style={{
        background: "var(--color-surface)",
        border: "1px solid var(--color-border)",
        boxShadow: "var(--shadow-card)",
      }}
    >
      {/* Header */}
      <div
        className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4"
        style={{ borderBottom: "1px solid var(--color-border-light)" }}
      >
        <div className="flex items-center gap-3">
          <div
            className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
            style={{
              background: "linear-gradient(135deg, rgba(3,105,161,0.12), rgba(14,165,233,0.08))",
              border: "1px solid rgba(3,105,161,0.18)",
            }}
          >
            <i className="ri-filter-3-line text-[18px]" style={{ color: "#0369A1" }} />
          </div>
          <div>
            <h3
              className="text-[15px] font-black leading-tight"
              style={{ color: "var(--color-text-primary)" }}
            >
              فلاتر لوحة التحكم
            </h3>
            <p
              className="text-[11.5px] font-medium mt-0.5"
              style={{ color: "var(--color-text-muted)" }}
            >
              اختر الفترة والفرع والقسم لعرض بيانات مخصصة
            </p>
          </div>
        </div>
        <button
          onClick={onReset}
          className="flex items-center gap-2 px-4 py-2.5 rounded-xl text-[12.5px] font-bold cursor-pointer transition-all whitespace-nowrap self-start sm:self-center"
          style={{
            background: "rgba(220,38,38,0.06)",
            border: "1px solid rgba(220,38,38,0.2)",
            color: "#DC2626",
          }}
          onMouseEnter={(e) => {
            (e.currentTarget as HTMLElement).style.background = "rgba(220,38,38,0.1)";
            (e.currentTarget as HTMLElement).style.borderColor = "rgba(220,38,38,0.35)";
          }}
          onMouseLeave={(e) => {
            (e.currentTarget as HTMLElement).style.background = "rgba(220,38,38,0.06)";
            (e.currentTarget as HTMLElement).style.borderColor = "rgba(220,38,38,0.2)";
          }}
        >
          <i className="ri-refresh-line text-[14px]" />
          إعادة التعيين
        </button>
      </div>

      {/* Filters Grid */}
      <div className="p-5">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
          
          <div className="space-y-2">
            <label
              className="flex items-center gap-2 text-[12px] font-bold"
              style={{ color: "var(--color-text-secondary)" }}
            >
              <i className="ri-calendar-event-line text-[13px]" style={{ color: "#0369A1" }} />
              الفترة
            </label>
            <Select
              value={filters.period ?? "month"}
              onValueChange={(v) => onFilterChange("period", v === "all" ? undefined : v)}
            >
              <SelectTrigger className={`${inputBase} w-full`} dir="rtl">
                <SelectValue placeholder="اختر الفترة" />
              </SelectTrigger>
              <SelectContent>
                {PERIOD_OPTIONS.filter((o) => o.value != null).map((opt) => (
                  <SelectItem key={opt.value} value={opt.value!}>
                    {opt.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          
          <div className="space-y-2">
            <label
              className="flex items-center gap-2 text-[12px] font-bold"
              style={{ color: "var(--color-text-secondary)" }}
            >
              <i className="ri-calendar-check-line text-[13px]" style={{ color: "#0369A1" }} />
              من تاريخ
            </label>
            <DatePicker
              value={dateFrom}
              onChange={onDateFromChange}
              placeholder="اختر تاريخ البداية"
              allowPastDates
              allowFutureDates={false}
              showLabel={false}
              buttonClassName={`${inputBase} w-full justify-between`}
            />
          </div>

          
          <div className="space-y-2">
            <label
              className="flex items-center gap-2 text-[12px] font-bold"
              style={{ color: "var(--color-text-secondary)" }}
            >
              <i className="ri-calendar-2-line text-[13px]" style={{ color: "#0369A1" }} />
              إلى تاريخ
            </label>
            <DatePicker
              value={dateTo}
              onChange={onDateToChange}
              placeholder="اختر تاريخ النهاية"
              allowPastDates
              allowFutureDates={false}
              minDate={dateFrom}
              showLabel={false}
              buttonClassName={`${inputBase} w-full justify-between`}
            />
          </div>

          
          <div className="space-y-2">
            <label
              className="flex items-center gap-2 text-[12px] font-bold"
              style={{ color: "var(--color-text-secondary)" }}
            >
              <i className="ri-building-4-line text-[13px]" style={{ color: "#0369A1" }} />
              الفرع
            </label>
            <BranchesSelect
              value={filters.branch_id ? String(filters.branch_id) : ""}
              onChange={(v) => onFilterChange("branch_id", v ? Number(v) : undefined)}
              placeholder="جميع الفروع"
              className={`${inputBase} w-full`}
            />
          </div>

          
          <div className="space-y-2">
            <label
              className="flex items-center gap-2 text-[12px] font-bold"
              style={{ color: "var(--color-text-secondary)" }}
            >
              <i className="ri-group-line text-[13px]" style={{ color: "#0369A1" }} />
              القسم (للحضور)
            </label>
            <DepartmentsSelect
              value={filters.department_id ? String(filters.department_id) : ""}
              onChange={(v) => onFilterChange("department_id", v ? Number(v) : undefined)}
              placeholder="جميع الأقسام"
              allowClear
              className={`${inputBase} w-full`}
            />
          </div>
        </div>
      </div>
    </div>
  );
}
