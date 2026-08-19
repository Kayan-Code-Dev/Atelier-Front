import { useSearchParams } from "react-router";

type Props = {
  totalElements: number | undefined;
  totalPages: number | undefined;
  totalElementsLabel: string;
  isLoading: boolean;
  /** Visual accent for pages that use the violet / #f8f8fb employee UI */
  tone?: "slate" | "violet";
};

const CustomPagination = ({
  totalElements,
  totalPages,
  totalElementsLabel,
  isLoading,
  tone = "slate",
}: Props) => {
  const [searchParams, setSearchParams] = useSearchParams();
  const currentPage = Number(searchParams.get("page")) || 1;
  const total = totalPages || 1;
  const canGoPrev = currentPage > 1;
  const canGoNext = currentPage < total && !isLoading;

  const handlePreviousPage = () => {
    if (!canGoPrev) return;
    const nextPage = Math.max(1, currentPage - 1);
    setSearchParams(
      (prev) => {
        const next = new URLSearchParams(prev);
        next.set("page", String(nextPage));
        return next;
      },
      { replace: true }
    );
  };

  const handleNextPage = () => {
    if (!canGoNext) return;
    const nextPage = Math.min(currentPage + 1, total);
    setSearchParams(
      (prev) => {
        const next = new URLSearchParams(prev);
        next.set("page", String(nextPage));
        return next;
      },
      { replace: true }
    );
  };

  const isViolet = tone === "violet";

  return (
    <div className="flex items-center gap-4 flex-wrap">
      <span
        className={`text-sm ${isViolet ? "text-gray-500" : "text-slate-500"}`}
      >
        {totalElementsLabel}:{" "}
        <span
          className={`font-semibold ${isViolet ? "text-gray-800" : "text-slate-700"}`}
        >
          {totalElements ?? 0}
        </span>
      </span>
      <div className="flex items-center gap-2">
        <button
          type="button"
          onClick={handlePreviousPage}
          disabled={!canGoPrev}
          aria-label="الصفحة السابقة"
          className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all min-w-[80px] justify-center disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent border ${
            isViolet
              ? "border-gray-200 text-gray-600 enabled:hover:bg-violet-50 enabled:hover:border-violet-200"
              : "enabled:hover:bg-slate-200 enabled:hover:border-slate-300"
          }`}
          style={
            isViolet
              ? { background: canGoPrev ? "#f5f3ff" : "transparent" }
              : {
                  background: canGoPrev ? "#F1F5F9" : "transparent",
                  border: "1px solid #E2E8F0",
                  color: canGoPrev ? "#334155" : "#94A3B8",
                }
          }
        >
          <i className="ri-arrow-right-s-line text-lg" />
          السابق
        </button>
        <span
          className={`px-4 py-2 text-sm font-semibold rounded-lg min-w-[100px] text-center border ${
            isViolet
              ? "bg-violet-50 text-violet-800 border-violet-200"
              : "text-slate-600"
          }`}
          style={
            isViolet
              ? undefined
              : {
                  background: "rgba(3, 105, 161, 0.08)",
                  border: "1px solid rgba(3, 105, 161, 0.2)",
                  color: "#0369A1",
                }
          }
        >
          {currentPage} / {total}
        </span>
        <button
          type="button"
          onClick={handleNextPage}
          disabled={!canGoNext}
          aria-label="الصفحة التالية"
          className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all min-w-[80px] justify-center disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent border ${
            isViolet
              ? "border-gray-200 text-gray-600 enabled:hover:bg-violet-50 enabled:hover:border-violet-200"
              : "enabled:hover:bg-slate-200 enabled:hover:border-slate-300"
          }`}
          style={
            isViolet
              ? { background: canGoNext ? "#f5f3ff" : "transparent" }
              : {
                  background: canGoNext ? "#F1F5F9" : "transparent",
                  border: "1px solid #E2E8F0",
                  color: canGoNext ? "#334155" : "#94A3B8",
                }
          }
        >
          التالي
          <i className="ri-arrow-left-s-line text-lg" />
        </button>
      </div>
    </div>
  );
};

export default CustomPagination;