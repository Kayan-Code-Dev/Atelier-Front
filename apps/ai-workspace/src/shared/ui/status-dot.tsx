import { cn } from '@/shared/lib/utils'

const toneMap = {
  online: 'bg-[color:var(--color-success)]',
  busy: 'bg-[color:var(--color-warning)]',
  offline: 'bg-[color:var(--color-ink-muted)]',
  error: 'bg-[color:var(--color-danger)]',
} as const

export function StatusDot({
  status,
  label,
  className,
}: {
  status: keyof typeof toneMap
  label?: string
  className?: string
}) {
  return (
    <span className={cn('inline-flex items-center gap-2 text-xs text-muted', className)}>
      <span className={cn('h-2 w-2 rounded-full', toneMap[status])} />
      {label}
    </span>
  )
}
