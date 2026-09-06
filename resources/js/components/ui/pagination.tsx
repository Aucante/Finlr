import { ChevronLeft, ChevronRight } from "lucide-react"

import { Button } from "@/components/ui/button"

interface PaginationProps {
  currentPage: number
  lastPage: number
  onPageChange: (page: number) => void
  previousLabel: string
  nextLabel: string
  pageIndicatorLabel: string
}

// Agnostic of the paginated resource itself (no business logic) — the
// caller owns fetching the new page (Inertia partial reload, plain
// client-side state, ...) via onPageChange.
function Pagination({
  currentPage,
  lastPage,
  onPageChange,
  previousLabel,
  nextLabel,
  pageIndicatorLabel,
}: PaginationProps) {
  if (lastPage <= 1) {
    return null
  }

  return (
    <nav aria-label={pageIndicatorLabel} className="flex items-center justify-center gap-3">
      <Button
        type="button"
        variant="ghost"
        size="icon-sm"
        disabled={currentPage <= 1}
        onClick={() => onPageChange(currentPage - 1)}
        aria-label={previousLabel}
      >
        <ChevronLeft aria-hidden />
      </Button>
      <span className="font-mono text-xs text-muted-foreground tabular-nums">
        {pageIndicatorLabel}
      </span>
      <Button
        type="button"
        variant="ghost"
        size="icon-sm"
        disabled={currentPage >= lastPage}
        onClick={() => onPageChange(currentPage + 1)}
        aria-label={nextLabel}
      >
        <ChevronRight aria-hidden />
      </Button>
    </nav>
  )
}

export { Pagination }
