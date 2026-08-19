import { NavLink } from 'react-router-dom'
import { navItems } from '@/app/navigation'
import { cn } from '@/shared/lib/utils'

export function Sidebar({ onNavigate }: { onNavigate?: () => void }) {
  const groups = [...new Set(navItems.map((item) => item.group))]

  return (
    <nav className="h-full overflow-y-auto p-3" aria-label="Primary">
      {groups.map((group) => (
        <div key={group} className="mb-4">
          <p className="mb-1 px-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">
            {group}
          </p>
          <ul className="space-y-0.5">
            {navItems
              .filter((item) => item.group === group)
              .map((item) => {
                const Icon = item.icon
                return (
                  <li key={item.to}>
                    <NavLink
                      to={item.to}
                      end={item.to === '/'}
                      onClick={onNavigate}
                      className={({ isActive }) =>
                        cn(
                          'flex items-center gap-2 rounded-[var(--radius-sm)] px-2.5 py-2 text-sm transition',
                          isActive
                            ? 'bg-[color:var(--color-accent-soft)] text-[color:var(--color-accent)]'
                            : 'text-[color:var(--color-ink)] hover:bg-[color:var(--color-surface-muted)]',
                        )
                      }
                    >
                      <Icon className="h-4 w-4 shrink-0" />
                      <span className="truncate">{item.label}</span>
                    </NavLink>
                  </li>
                )
              })}
          </ul>
        </div>
      ))}
    </nav>
  )
}
