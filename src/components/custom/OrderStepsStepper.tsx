import { Link } from "react-router";
import { Check } from "lucide-react";
import { cn } from "@/lib/utils";

const STEPS = [
  { id: 1, label: "الأساسيات", path: "/orders/choose-client" },
  { id: 2, label: "اختيار المنتجات", path: "/orders/choose-clothes" },
  { id: 3, label: "المراجعة", path: "/orders/create-order" },
] as const;

type Props = {
  currentStep: 1 | 2 | 3;
  /** Optional state to pass when navigating to a step (e.g. { 2: { client } } for choose-clothes) */
  stepState?: Partial<Record<1 | 2 | 3, Record<string, unknown>>>;
  /** When true, the next step becomes clickable (e.g. on step 2 with products selected, step 3 becomes clickable) */
  allowNextStep?: boolean;
  /** When true, the current step is clickable (e.g. on choose-client with products, step 3 links to create-order) */
  allowCurrentStepClick?: boolean;
};

export function OrderStepsStepper({ currentStep, stepState, allowNextStep, allowCurrentStepClick }: Props) {
  return (
    <nav
      aria-label="خطوات إنشاء الفاتورة"
      className="w-full"
      dir="rtl"
    >
      <ol className="flex items-center justify-center gap-0 sm:gap-1 flex-nowrap overflow-x-auto pb-1">
        {STEPS.map((step, index) => {
          const isActive = step.id === currentStep;
          const isCompleted = step.id < currentStep;
          const isClickable =
            step.id < currentStep ||
            (allowNextStep && step.id === currentStep + 1) ||
            (allowCurrentStepClick && step.id === currentStep);

          return (
            <li key={step.id} className="flex items-center shrink-0">
              {index > 0 && (
                <i
                  className={cn(
                    "ri-arrow-left-s-line mx-0.5 shrink-0 text-base text-slate-300 transition-colors sm:mx-1 dark:text-slate-600",
                    isCompleted && "text-[#5170ff]"
                  )}
                  aria-hidden
                />
              )}
              <span
                className={cn(
                  "inline-flex flex-row items-center gap-2 text-sm font-medium px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl transition-all duration-200",
                  isActive &&
                    "text-[#5170ff] bg-[#5170ff]/10 ring-1 ring-[#5170ff]/20 shadow-sm",
                  isCompleted && "text-slate-700 dark:text-slate-300",
                  !isActive && !isCompleted && "text-slate-400 dark:text-slate-500"
                )}
              >
                <span
                  className={cn(
                    "inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-xs font-bold transition-all",
                    isCompleted && "bg-[#5170ff] text-white",
                    isActive && "bg-[#5170ff]/20 text-[#5170ff] ring-1 ring-[#5170ff]/30",
                    !isActive && !isCompleted && "bg-slate-100 dark:bg-slate-800 text-slate-400"
                  )}
                >
                  {isCompleted ? <Check className="h-3.5 w-3.5" /> : step.id}
                </span>
                {isClickable ? (
                  <Link
                    to={step.path}
                    state={stepState?.[step.id]}
                    className="hover:text-[#5170ff] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#5170ff]/50 focus-visible:ring-offset-2 rounded-md"
                  >
                    {step.label}
                  </Link>
                ) : (
                  <span>{step.label}</span>
                )}
              </span>
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
