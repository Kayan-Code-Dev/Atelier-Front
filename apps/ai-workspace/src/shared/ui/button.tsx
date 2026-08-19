import { cva, type VariantProps } from 'class-variance-authority'
import type { ButtonHTMLAttributes } from 'react'
import { cn } from '@/shared/lib/utils'

const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 rounded-[var(--radius-sm)] text-sm font-medium transition-colors ring-focus disabled:pointer-events-none disabled:opacity-50',
  {
    variants: {
      variant: {
        primary:
          'bg-[color:var(--color-accent)] text-[color:var(--color-accent-fg)] hover:brightness-110',
        secondary:
          'bg-[color:var(--color-surface-muted)] text-[color:var(--color-ink)] hover:brightness-95',
        ghost: 'hover:bg-[color:var(--color-surface-muted)] text-[color:var(--color-ink)]',
        outline:
          'border border-[color:var(--color-border)] bg-transparent hover:bg-[color:var(--color-surface-muted)]',
        danger: 'bg-[color:var(--color-danger)] text-white hover:brightness-110',
      },
      size: {
        sm: 'h-8 px-3 text-xs',
        md: 'h-9 px-3.5',
        lg: 'h-11 px-5',
        icon: 'h-9 w-9',
      },
    },
    defaultVariants: {
      variant: 'primary',
      size: 'md',
    },
  },
)

export interface ButtonProps
  extends ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {}

export function Button({ className, variant, size, ...props }: ButtonProps) {
  return <button className={cn(buttonVariants({ variant, size }), className)} {...props} />
}
