import { Link, useLocation } from 'react-router-dom'
import { conversationDetail, customerProfile } from '@/mock/data'
import { Badge, StatusDot } from '@/shared/ui'

export function ContextPanel() {
  const location = useLocation()
  const crumbs = location.pathname === '/' ? ['Workspace', 'Dashboard'] : ['Workspace', ...location.pathname.split('/').filter(Boolean)]

  return (
    <aside className="hidden h-full w-72 shrink-0 flex-col border-l border-[color:var(--color-border)] xl:flex">
      <div className="border-b border-[color:var(--color-border)] px-4 py-3">
        <p className="text-[11px] uppercase tracking-wide text-muted">Breadcrumbs</p>
        <p className="mt-1 text-sm">
          {crumbs.map((crumb, index) => (
            <span key={`${crumb}-${index}`}>
              {index > 0 ? <span className="text-muted"> / </span> : null}
              <span className="capitalize">{crumb.replaceAll('-', ' ')}</span>
            </span>
          ))}
        </p>
      </div>
      <div className="flex-1 space-y-4 overflow-y-auto p-4">
        <section>
          <h3 className="font-display text-sm font-semibold">Active Conversation</h3>
          <p className="mt-1 text-sm">{conversationDetail.customer}</p>
          <div className="mt-2 flex flex-wrap gap-1.5">
            <Badge tone="accent">{conversationDetail.mode}</Badge>
            <Badge tone="info">{conversationDetail.intent}</Badge>
          </div>
        </section>
        <section>
          <h3 className="font-display text-sm font-semibold">Customer Snapshot</h3>
          <p className="mt-1 text-sm text-muted">{customerProfile.tier} · {customerProfile.city}</p>
          <div className="mt-2 flex flex-wrap gap-1.5">
            {customerProfile.tags.map((tag) => (
              <Badge key={tag}>{tag}</Badge>
            ))}
          </div>
        </section>
        <section>
          <h3 className="font-display text-sm font-semibold">Workforce Pulse</h3>
          <div className="mt-2 space-y-2 text-sm">
            <StatusDot status="online" label="Lina · Hybrid" />
            <StatusDot status="busy" label="Omar · Full Auto" />
            <StatusDot status="online" label="Maya · Assistant" />
          </div>
        </section>
        <Link to="/conversation" className="text-sm text-[color:var(--color-accent)] underline-offset-2 hover:underline">
          Open Conversation Workspace →
        </Link>
      </div>
    </aside>
  )
}
