import { conversationDetail, customerProfile } from '@/mock/data'
import { Badge, Card, CardHeader, PageHeader, Stat } from '@/shared/ui'

export function ConversationPage() {
  return (
    <div>
      <PageHeader
        title="Conversation Workspace"
        description={`${conversationDetail.customer} · ${conversationDetail.channel}`}
      />
      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Stat label="Current Intent" value={conversationDetail.intent} />
        <Stat label="Confidence" value={`${Math.round(conversationDetail.confidence * 100)}%`} />
        <Stat label="Current Mode" value={conversationDetail.mode} />
        <Stat label="Approvals" value="1 pending" hint="Human handover ready" />
      </div>

      <div className="mt-4 grid gap-4 xl:grid-cols-3">
        <Card className="xl:col-span-2">
          <CardHeader title="Conversation Timeline" />
          <div className="space-y-3">
            {conversationDetail.messages.map((message, index) => (
              <div
                key={index}
                className={`max-w-[85%] rounded-[var(--radius-md)] px-3 py-2 text-sm ${
                  message.from === 'ai'
                    ? 'ml-auto bg-[color:var(--color-accent-soft)]'
                    : 'bg-[color:var(--color-surface-muted)]'
                }`}
              >
                <p className="text-[11px] uppercase tracking-wide text-muted">{message.from} · {message.at}</p>
                <p className="mt-1">{message.text}</p>
              </div>
            ))}
          </div>
        </Card>

        <div className="space-y-4">
          <Card>
            <CardHeader title="AI Reasoning Summary" description="Conceptual" />
            <p className="text-sm text-muted">{conversationDetail.reasoning}</p>
          </Card>
          <Card>
            <CardHeader title="Execution Plan" />
            <ol className="list-decimal space-y-1 pl-4 text-sm">
              {conversationDetail.plan.map((step) => (
                <li key={step}>{step}</li>
              ))}
            </ol>
          </Card>
        </div>
      </div>

      <div className="mt-4 grid gap-4 lg:grid-cols-3">
        <Card>
          <CardHeader title="Tool Calls Timeline" />
          <div className="space-y-2">
            {conversationDetail.tools.map((tool) => (
              <div key={tool.name} className="flex items-center justify-between text-sm">
                <span>{tool.name}</span>
                <Badge tone={tool.status === 'completed' ? 'success' : 'warning'}>{tool.status}</Badge>
              </div>
            ))}
          </div>
        </Card>
        <Card>
          <CardHeader title="Memory Used" />
          <ul className="space-y-1 text-sm text-muted">
            {conversationDetail.memoryUsed.map((item) => (
              <li key={item}>• {item}</li>
            ))}
          </ul>
        </Card>
        <Card>
          <CardHeader title="Knowledge Used" />
          <ul className="space-y-1 text-sm text-muted">
            {conversationDetail.knowledgeUsed.map((item) => (
              <li key={item}>• {item}</li>
            ))}
          </ul>
        </Card>
      </div>

      <Card className="mt-4">
        <CardHeader title="Customer Timeline" description={`${customerProfile.name} · ${customerProfile.tier}`} />
        <div className="space-y-2">
          {customerProfile.timeline.map((item) => (
            <div key={item.event} className="flex gap-3 text-sm">
              <span className="w-28 shrink-0 text-muted">{item.at}</span>
              <span>{item.event}</span>
            </div>
          ))}
        </div>
      </Card>
    </div>
  )
}

export function CustomerProfilePage() {
  return (
    <div>
      <PageHeader title="Customer Profile" description={customerProfile.name} />
      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-1">
          <CardHeader title="Profile" />
          <dl className="space-y-2 text-sm">
            <div className="flex justify-between"><dt className="text-muted">Tier</dt><dd>{customerProfile.tier}</dd></div>
            <div className="flex justify-between"><dt className="text-muted">City</dt><dd>{customerProfile.city}</dd></div>
            <div className="flex justify-between"><dt className="text-muted">Language</dt><dd>{customerProfile.language}</dd></div>
          </dl>
          <div className="mt-3 flex flex-wrap gap-1.5">
            {customerProfile.tags.map((tag) => (
              <Badge key={tag} tone="accent">{tag}</Badge>
            ))}
          </div>
        </Card>
        <Card className="lg:col-span-2">
          <CardHeader title="Timeline" />
          {customerProfile.timeline.map((item) => (
            <div key={item.event} className="border-b border-[color:var(--color-border)] py-2 text-sm last:border-0">
              <p className="text-muted">{item.at}</p>
              <p>{item.event}</p>
            </div>
          ))}
        </Card>
      </div>
    </div>
  )
}
