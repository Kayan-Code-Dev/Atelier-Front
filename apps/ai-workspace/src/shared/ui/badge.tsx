import { cva, type VariantProps } from 'class-variance-authority'
import type { HTMLAttributes } from 'react'
import { cn } from '@/shared/lib/utils'

const badgeVariants = cva(
  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
  {
    variants: {
      tone: {
        neutral: 'bg-[color:var(--color-surface-muted)] text-[color:var(--color-ink)]',
        accent: 'bg-[color:var(--color-accent-soft)] text-[color:var(--color-accent)]',
        success: 'bg-[color:color-mix(in_oklab,var(--color-success)_18%,transparent)] text-[color:var(--color-success)]',
        warning: 'bg-[color:color-mix(in_oklab,var(--color-warning)_18%,transparent)] text-[color:var(--color-warning)]',
        danger: 'bg-[color:color-mix(in_oklab,var(--color-danger)_18%,transparent)] text-[color:var(--color-danger)]',
        info: 'bg-[color:color-mix(in_oklab,var(--color-info)_18%,transparent)] text-[color:var(--color-info)]',
      },
    },
    defaultVariants: { tone: 'neutral' },
  },
)

export function Badge({
  className,
  tone,
  ...props
}: HTMLAttributes<HTMLSpanElement> & VariantProps<typeof badgeVariants>) {
  return <span className={cn(badgeVariants({ tone }), className)} {...props} />
}
