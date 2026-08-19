import { Outlet } from 'react-router-dom'
import { useEffect, useState } from 'react'
import { motion } from 'framer-motion'
import { CommandPalette } from '@/layouts/command-palette'
import { ContextPanel } from '@/layouts/context-panel'
import { Sidebar } from '@/layouts/sidebar'
import { TopNav } from '@/layouts/top-nav'
import { cn } from '@/shared/lib/utils'

export function WorkspaceShell() {
  const [mobileOpen, setMobileOpen] = useState(false)
  const [commandOpen, setCommandOpen] = useState(false)

  useEffect(() => {
    const open = () => setCommandOpen(true)
    document.addEventListener('aos:open-command', open)
    return () => document.removeEventListener('aos:open-command', open)
  }, [])

  return (
    <div className="flex h-full min-h-0 flex-col">
      <TopNav onMenu={() => setMobileOpen(true)} onCommand={() => setCommandOpen(true)} />
      <div className="flex min-h-0 flex-1">
        <aside className="panel hidden w-64 shrink-0 border-r lg:block">
          <Sidebar />
        </aside>

        <div
          className={cn(
            'fixed inset-0 z-50 lg:hidden',
            mobileOpen ? 'pointer-events-auto' : 'pointer-events-none',
          )}
        >
          <button
            className={cn('absolute inset-0 bg-black/40 transition', mobileOpen ? 'opacity-100' : 'opacity-0')}
            onClick={() => setMobileOpen(false)}
            aria-label="Close sidebar"
          />
          <aside
            className={cn(
              'panel absolute left-0 top-0 h-full w-72 transition-transform duration-300',
              mobileOpen ? 'translate-x-0' : '-translate-x-full',
            )}
          >
            <Sidebar onNavigate={() => setMobileOpen(false)} />
          </aside>
        </div>

        <main className="min-w-0 flex-1 overflow-y-auto">
          <motion.div
            className="mx-auto max-w-7xl p-4 md:p-6"
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25 }}
          >
            <Outlet />
          </motion.div>
        </main>

        <ContextPanel />
      </div>
      <CommandPalette open={commandOpen} onClose={() => setCommandOpen(false)} />
    </div>
  )
}
