import { Bell, Menu, Moon, Search, Sun, UserRound } from 'lucide-react'
import { Link } from 'react-router-dom'
import { notifications } from '@/mock/data'
import { useTheme } from '@/shared/hooks/use-theme'
import { Badge, Button } from '@/shared/ui'
import { useState } from 'react'

export function TopNav({
  onMenu,
  onCommand,
}: {
  onMenu: () => void
  onCommand: () => void
}) {
  const { theme, toggle } = useTheme()
  const [openNotifs, setOpenNotifs] = useState(false)

  return (
    <header className="panel sticky top-0 z-40 flex h-14 items-center justify-between gap-3 border-b px-3 md:px-4">
      <div className="flex items-center gap-2">
        <Button variant="ghost" size="icon" className="lg:hidden" onClick={onMenu} aria-label="Open menu">
          <Menu className="h-4 w-4" />
        </Button>
        <Link to="/" className="flex items-center gap-2">
          <span className="grid h-8 w-8 place-items-center rounded-lg bg-[color:var(--color-accent)] font-display text-sm font-bold text-[color:var(--color-accent-fg)]">
            AOS
          </span>
          <div className="hidden sm:block">
            <p className="font-display text-sm font-bold leading-none">AI Workspace</p>
            <p className="text-[11px] text-muted">Digital Employee Operating System</p>
          </div>
        </Link>
      </div>

      <button
        onClick={onCommand}
        className="hidden h-9 max-w-md flex-1 items-center gap-2 rounded-[var(--radius-sm)] border border-[color:var(--color-border)] bg-[color:var(--color-surface-muted)] px-3 text-left text-sm text-muted md:flex"
      >
        <Search className="h-4 w-4" />
        <span className="flex-1">Global search</span>
        <kbd className="rounded border border-[color:var(--color-border)] px-1.5 text-[10px]">⌘K</kbd>
      </button>

      <div className="flex items-center gap-1.5">
        <Button variant="ghost" size="icon" onClick={onCommand} className="md:hidden" aria-label="Search">
          <Search className="h-4 w-4" />
        </Button>
        <div className="relative">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => setOpenNotifs((value) => !value)}
            aria-label="Notifications"
          >
            <Bell className="h-4 w-4" />
          </Button>
          <Badge tone="danger" className="absolute -right-1 -top-1 h-4 min-w-4 justify-center px-1">
            {notifications.length}
          </Badge>
          {openNotifs ? (
            <div className="panel absolute right-0 mt-2 w-80 rounded-[var(--radius-md)] p-2">
              <p className="px-2 py-1 text-xs font-semibold uppercase tracking-wide text-muted">
                Notification Center
              </p>
              {notifications.map((item) => (
                <div key={item.id} className="rounded-[var(--radius-sm)] px-2 py-2 hover:bg-[color:var(--color-surface-muted)]">
                  <div className="flex items-center justify-between gap-2">
                    <p className="text-sm font-medium">{item.title}</p>
                    <span className="text-[11px] text-muted">{item.time}</span>
                  </div>
                  <p className="text-xs text-muted">{item.body}</p>
                </div>
              ))}
            </div>
          ) : null}
        </div>
        <Button variant="ghost" size="icon" onClick={toggle} aria-label="Toggle theme">
          {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
        </Button>
        <Button variant="secondary" size="sm" className="gap-2">
          <UserRound className="h-4 w-4" />
          <span className="hidden sm:inline">Admin</span>
        </Button>
      </div>
    </header>
  )
}
