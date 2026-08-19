import { plannerMonitor } from '@/mock/data'
import { Badge, Card, CardHeader, PageHeader, Stat } from '@/shared/ui'

export function PlannerPage() {
  return (
    <div>
      <PageHeader
        title="AI Planner Monitor"
        description="Inspect intent, goals, risk, and decision path for the active plan."
      />
      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Stat label="Intent" value={plannerMonitor.intent} />
        <Stat label="Confidence" value={`${Math.round(plannerMonitor.confidence * 100)}%`} />
        <Stat label="Risk" value={plannerMonitor.risk.split('—')[0]} hint={plannerMonitor.risk} />
        <Stat label="Approvals" value={String(plannerMonitor.approvals.length)} />
      </div>

      <div className="mt-4 grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader title="Goals" />
          <ul className="space-y-2 text-sm">
            {plannerMonitor.goals.map((goal) => (
              <li key={goal} className="rounded-[var(--radius-sm)] bg-[color:var(--color-surface-muted)] px-3 py-2">
                {goal}
              </li>
            ))}
          </ul>
        </Card>
        <Card>
          <CardHeader title="Execution Graph" />
          <div className="flex flex-wrap items-center gap-2">
            {plannerMonitor.graph.map((node, index) => (
              <div key={node.id} className="flex items-center gap-2">
                <Badge tone="accent">{node.label}</Badge>
                {index < plannerMonitor.graph.length - 1 ? <span className="text-muted">→</span> : null}
              </div>
            ))}
          </div>
          <div className="mt-4">
            <p className="text-xs uppercase tracking-wide text-muted">Decision Path</p>
            <ol className="mt-2 list-decimal space-y-1 pl-4 text-sm">
              {plannerMonitor.decisionPath.map((step) => (
                <li key={step}>{step}</li>
              ))}
            </ol>
          </div>
        </Card>
      </div>

      <div className="mt-4 grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader title="Planned Tools" />
          <div className="flex flex-wrap gap-2">
            {plannerMonitor.plannedTools.map((tool) => (
              <Badge key={tool}>{tool}</Badge>
            ))}
          </div>
        </Card>
        <Card>
          <CardHeader title="Required Approvals" />
          <ul className="space-y-2 text-sm">
            {plannerMonitor.approvals.map((item) => (
              <li key={item}>• {item}</li>
            ))}
          </ul>
        </Card>
      </div>
    </div>
  )
}
