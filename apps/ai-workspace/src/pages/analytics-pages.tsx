import {
  Bar,
  BarChart,
  CartesianGrid,
  Legend,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { analyticsKpis, analyticsSeries } from '@/mock/data'
import { formatCurrency, formatPercent } from '@/shared/lib/utils'
import { Card, CardHeader, PageHeader, Stat } from '@/shared/ui'

export function AnalyticsPage() {
  return (
    <div>
      <PageHeader title="Analytics" description="Conversations, automation, quality, cost, and ROI." />
      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <Stat label="Conversations" value={String(analyticsKpis.conversations)} />
        <Stat label="Response Time" value={analyticsKpis.responseTime} />
        <Stat label="Automation Rate" value={formatPercent(analyticsKpis.automationRate)} />
        <Stat label="AI Resolution" value={formatPercent(analyticsKpis.aiResolution)} />
        <Stat label="Human Escalation" value={formatPercent(analyticsKpis.humanEscalation)} />
        <Stat label="CSAT" value={String(analyticsKpis.csat)} />
        <Stat label="Token Usage" value={analyticsKpis.tokenUsage} />
        <Stat label="Cost" value={formatCurrency(analyticsKpis.cost)} />
        <Stat label="ROI" value={`${analyticsKpis.roi}x`} />
        <Stat
          label="Provider Mix"
          value="Multi"
          hint={`OAI ${analyticsKpis.providerUsage.openai}% · Claude ${analyticsKpis.providerUsage.anthropic}%`}
        />
      </div>
      <Card className="mt-4">
        <CardHeader title="Automation vs Escalation" />
        <div className="h-72">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={analyticsSeries}>
              <CartesianGrid stroke="var(--color-border)" strokeDasharray="3 3" />
              <XAxis dataKey="day" stroke="var(--color-ink-muted)" fontSize={12} />
              <YAxis stroke="var(--color-ink-muted)" fontSize={12} />
              <Tooltip />
              <Legend />
              <Bar dataKey="automation" fill="var(--color-accent)" radius={4} />
              <Bar dataKey="escalation" fill="var(--color-warning)" radius={4} />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </Card>
    </div>
  )
}

export function ReportsPage() {
  return (
    <div>
      <PageHeader title="Reports" description="Operational and executive report packs." />
      <div className="grid gap-3 md:grid-cols-3">
        {['Weekly Ops Digest', 'Channel Health', 'Cost & Token Burn', 'Approval Latency', 'Employee Performance', 'Knowledge Coverage'].map(
          (report) => (
            <Card key={report}>
              <CardHeader title={report} description="Ready · Mock export" />
            </Card>
          ),
        )}
      </div>
    </div>
  )
}
