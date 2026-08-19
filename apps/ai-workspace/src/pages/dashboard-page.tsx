import { Link } from 'react-router-dom'
import {
  Area,
  AreaChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { analyticsSeries, aiEmployees, approvals, inboxMessages } from '@/mock/data'
import { Badge, Card, CardHeader, PageHeader, Stat } from '@/shared/ui'

export function DashboardPage() {
  return (
    <div>
      <PageHeader
        title="AOS Command Deck"
        description="Operate your digital employees across conversations, automation, and knowledge."
        actions={
          <>
            <Link
              to="/inbox"
              className="inline-flex h-9 items-center rounded-[var(--radius-sm)] bg-[color:var(--color-surface-muted)] px-3.5 text-sm font-medium"
            >
              Open Inbox
            </Link>
            <Link
              to="/employees"
              className="inline-flex h-9 items-center rounded-[var(--radius-sm)] bg-[color:var(--color-accent)] px-3.5 text-sm font-medium text-[color:var(--color-accent-fg)]"
            >
              Manage Employees
            </Link>
          </>
        }
      />

      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Stat label="Live Conversations" value="47" hint="+12% vs yesterday" />
        <Stat label="Automation Rate" value="72%" hint="Target 75%" />
        <Stat label="Pending Approvals" value={String(approvals.length)} hint="2 high risk" />
        <Stat label="AI Employees Online" value="2/3" hint="Omar busy" />
      </div>

      <div className="mt-4 grid gap-4 xl:grid-cols-3">
        <Card className="xl:col-span-2">
          <CardHeader title="Conversation Throughput" description="Last 7 days" />
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={analyticsSeries}>
                <defs>
                  <linearGradient id="conv" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="var(--color-accent)" stopOpacity={0.35} />
                    <stop offset="95%" stopColor="var(--color-accent)" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid stroke="var(--color-border)" strokeDasharray="3 3" />
                <XAxis dataKey="day" stroke="var(--color-ink-muted)" fontSize={12} />
                <YAxis stroke="var(--color-ink-muted)" fontSize={12} />
                <Tooltip />
                <Area type="monotone" dataKey="conversations" stroke="var(--color-accent)" fill="url(#conv)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <Card>
          <CardHeader title="Workforce" description="Digital employees" />
          <div className="space-y-3">
            {aiEmployees.map((employee) => (
              <div key={employee.id} className="flex items-center justify-between rounded-[var(--radius-sm)] bg-[color:var(--color-surface-muted)] px-3 py-2">
                <div>
                  <p className="text-sm font-medium">{employee.name}</p>
                  <p className="text-xs text-muted">{employee.persona} · {employee.mode}</p>
                </div>
                <Badge tone={employee.status === 'online' ? 'success' : 'warning'}>{employee.status}</Badge>
              </div>
            ))}
          </div>
        </Card>
      </div>

      <Card className="mt-4">
        <CardHeader title="Inbox Pulse" description="Unified channel pressure" action={<Link className="text-sm text-[color:var(--color-accent)]" to="/inbox">View all</Link>} />
        <div className="grid gap-2 md:grid-cols-2">
          {inboxMessages.slice(0, 4).map((message) => (
            <div key={message.id} className="rounded-[var(--radius-sm)] border border-[color:var(--color-border)] px-3 py-2">
              <div className="flex items-center justify-between gap-2">
                <p className="text-sm font-medium">{message.customer}</p>
                <Badge>{message.channel}</Badge>
              </div>
              <p className="mt-1 truncate text-xs text-muted">{message.preview}</p>
            </div>
          ))}
        </div>
      </Card>
    </div>
  )
}
