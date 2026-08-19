import { AnimatePresence, motion } from 'framer-motion'
import { Search } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { commandItems } from '@/app/navigation'
import { Input } from '@/shared/ui'

export function CommandPalette({
  open,
  onClose,
}: {
  open: boolean
  onClose: () => void
}) {
  const [query, setQuery] = useState('')
  const navigate = useNavigate()

  const results = useMemo(() => {
    const q = query.trim().toLowerCase()
    if (!q) return commandItems
    return commandItems.filter(
      (item) => item.label.toLowerCase().includes(q) || item.group.toLowerCase().includes(q),
    )
  }, [query])

  useEffect(() => {
    if (!open) setQuery('')
  }, [open])

  useEffect(() => {
    const onKey = (event: KeyboardEvent) => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        if (open) onClose()
        else document.dispatchEvent(new CustomEvent('aos:open-command'))
      }
      if (event.key === 'Escape' && open) onClose()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, onClose])

  return (
    <AnimatePresence>
      {open ? (
        <motion.div
          className="fixed inset-0 z-[60] flex items-start justify-center bg-black/50 p-4 pt-[12vh]"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          onClick={onClose}
        >
          <motion.div
            role="dialog"
            aria-label="Command palette"
            className="panel w-full max-w-xl overflow-hidden rounded-[var(--radius-lg)]"
            initial={{ y: 12, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            exit={{ y: 8, opacity: 0 }}
            onClick={(event) => event.stopPropagation()}
          >
            <div className="flex items-center gap-2 border-b border-[color:var(--color-border)] px-3 py-2">
              <Search className="h-4 w-4 text-muted" />
              <Input
                autoFocus
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder="Jump to page, employee, workflow…"
                className="border-0 bg-transparent shadow-none focus-visible:ring-0"
              />
            </div>
            <ul className="max-h-80 overflow-y-auto p-2">
              {results.map((item) => (
                <li key={item.id}>
                  <button
                    className="flex w-full items-center justify-between rounded-[var(--radius-sm)] px-3 py-2 text-left text-sm hover:bg-[color:var(--color-surface-muted)]"
                    onClick={() => {
                      navigate(item.to)
                      onClose()
                    }}
                  >
                    <span>{item.label}</span>
                    <span className="text-xs text-muted">{item.group}</span>
                  </button>
                </li>
              ))}
              {results.length === 0 ? (
                <li className="px-3 py-6 text-center text-sm text-muted">No matches</li>
              ) : null}
            </ul>
          </motion.div>
        </motion.div>
      ) : null}
    </AnimatePresence>
  )
}
