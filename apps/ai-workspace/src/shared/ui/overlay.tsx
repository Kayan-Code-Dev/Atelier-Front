import type { ReactNode } from 'react'
import { cn } from '@/shared/lib/utils'

export function Dialog({
  open,
  title,
  children,
  onClose,
}: {
  open: boolean
  title: string
  children: ReactNode
  onClose: () => void
}) {
  if (!open) return null
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <button
        className="absolute inset-0 bg-black/50"
        aria-label="Close dialog backdrop"
        onClick={onClose}
      />
      <div
        role="dialog"
        aria-modal="true"
        className="panel relative z-10 w-full max-w-lg rounded-[var(--radius-lg)] p-5"
      >
        <div className="mb-4 flex items-center justify-between">
          <h2 className="font-display text-lg font-semibold">{title}</h2>
          <button className="text-sm text-muted ring-focus" onClick={onClose}>
            Close
          </button>
        </div>
        {children}
      </div>
    </div>
  )
}

export function Drawer({
  open,
  title,
  children,
  onClose,
  side = 'right',
}: {
  open: boolean
  title: string
  children: ReactNode
  onClose: () => void
  side?: 'right' | 'left'
}) {
  return (
    <div
      className={cn(
        'fixed inset-0 z-50 transition',
        open ? 'pointer-events-auto' : 'pointer-events-none',
      )}
    >
      <button
        className={cn('absolute inset-0 bg-black/40 transition', open ? 'opacity-100' : 'opacity-0')}
        onClick={onClose}
        aria-label="Close drawer"
      />
      <aside
        className={cn(
          'panel absolute top-0 h-full w-full max-w-md p-5 transition-transform duration-300',
          side === 'right' ? 'right-0' : 'left-0',
          open
            ? 'translate-x-0'
            : side === 'right'
              ? 'translate-x-full'
              : '-translate-x-full',
        )}
      >
        <div className="mb-4 flex items-center justify-between">
          <h2 className="font-display text-lg font-semibold">{title}</h2>
          <button className="text-sm text-muted ring-focus" onClick={onClose}>
            Close
          </button>
        </div>
        {children}
      </aside>
    </div>
  )
}
