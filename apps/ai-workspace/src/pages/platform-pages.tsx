import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { Badge, Button, Card, CardHeader, DataTable, PageHeader, StatusDot } from '@/shared/ui'
import { Input } from '@/shared/ui/input'
import { approvals, auditTrail, campaigns, channels } from '@/mock/data'

const workspaceSchema = z.object({
  displayName: z.string().min(2, 'Name is required'),
  density: z.enum(['comfortable', 'compact']),
})

type WorkspaceForm = z.infer<typeof workspaceSchema>

export function ApprovalsPage() {
  return (
    <div>
      <PageHeader title="Approvals" description="Human-in-the-loop gates for risky digital employee actions." />
      <Card>
        <DataTable
          columns={['Request', 'Owner', 'Risk', 'Status', 'Action']}
          rows={approvals.map((item) => [
            item.title,
            item.owner,
            <Badge key={item.id} tone={item.risk === 'High' ? 'danger' : item.risk === 'Medium' ? 'warning' : 'neutral'}>
              {item.risk}
            </Badge>,
            item.status,
            <Button key={`${item.id}-act`} size="sm" variant="secondary">
              Review
            </Button>,
          ])}
        />
      </Card>
    </div>
  )
}

export function AuditTrailPage() {
  return (
    <div>
      <PageHeader title="Audit Trail" description="Immutable operational history for trust and compliance." />
      <Card>
        <DataTable
          columns={['When', 'Actor', 'Action']}
          rows={auditTrail.map((item) => [item.at, item.actor, item.action])}
        />
      </Card>
    </div>
  )
}

export function CampaignsPage() {
  return (
    <div>
      <PageHeader title="Campaign Center" description="Outbound automation campaigns across channels." />
      <div className="grid gap-3 md:grid-cols-2">
        {campaigns.map((campaign) => (
          <Card key={campaign.id}>
            <CardHeader title={campaign.name} action={<Badge tone="accent">{campaign.status}</Badge>} />
            <p className="text-sm text-muted">Reach {campaign.reach}</p>
            <p className="text-sm text-muted">Conversion {Math.round(campaign.conversion * 100)}%</p>
          </Card>
        ))}
      </div>
    </div>
  )
}

export function ChannelsPage() {
  return (
    <div>
      <PageHeader title="Channel Connections" description="WhatsApp, Messenger, Instagram, Facebook, Telegram, Email, Web Chat." />
      <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        {channels.map((channel) => (
          <Card key={channel.id}>
            <CardHeader
              title={channel.name}
              action={
                <StatusDot
                  status={
                    channel.health === 'Healthy'
                      ? 'online'
                      : channel.health === 'Degraded'
                        ? 'busy'
                        : 'offline'
                  }
                  label={channel.health}
                />
              }
            />
            <div className="flex items-center justify-between">
              <Badge tone={channel.status === 'Connected' ? 'success' : 'danger'}>{channel.status}</Badge>
              <Button size="sm" variant="outline">
                Reconnect
              </Button>
            </div>
          </Card>
        ))}
      </div>
    </div>
  )
}

export function TenantSettingsPage() {
  return (
    <div>
      <PageHeader title="Tenant Settings" description="Isolation, branding, and operating constraints." />
      <Card>
        <CardHeader title="Tenant" description="DressnMore Atelier · Riyadh" />
        <DataTable
          columns={['Setting', 'Value']}
          rows={[
            ['Timezone', 'Asia/Riyadh'],
            ['Default Language', 'Arabic'],
            ['Operating Mode Default', 'Hybrid'],
            ['Approval Policy', 'Discount > 10%'],
          ]}
        />
      </Card>
    </div>
  )
}

export function WorkspaceSettingsPage() {
  const form = useForm<WorkspaceForm>({
    resolver: zodResolver(workspaceSchema),
    defaultValues: { displayName: 'AOS Operator', density: 'comfortable' },
  })

  return (
    <div>
      <PageHeader title="Workspace Settings" description="Theme, density, notifications, and operator preferences." />
      <Card className="mb-4">
        <CardHeader title="Operator Preferences" description="Validated with React Hook Form + Zod" />
        <form
          className="grid gap-3 md:grid-cols-2"
          onSubmit={form.handleSubmit(() => undefined)}
        >
          <label className="text-sm">
            <span className="mb-1 block text-muted">Display name</span>
            <Input {...form.register('displayName')} />
            {form.formState.errors.displayName ? (
              <span className="mt-1 block text-xs text-[color:var(--color-danger)]">
                {form.formState.errors.displayName.message}
              </span>
            ) : null}
          </label>
          <label className="text-sm">
            <span className="mb-1 block text-muted">Density</span>
            <select
              className="h-9 w-full rounded-[var(--radius-sm)] border border-[color:var(--color-border)] bg-[color:var(--color-surface)] px-3 text-sm"
              {...form.register('density')}
            >
              <option value="comfortable">Comfortable</option>
              <option value="compact">Compact</option>
            </select>
          </label>
          <div className="md:col-span-2">
            <Button type="submit">Save preferences</Button>
          </div>
        </form>
      </Card>
      <Card>
        <CardHeader title="Preferences" />
        <DataTable
          columns={['Preference', 'Value']}
          rows={[
            ['Theme', 'System / Toggle'],
            ['Sidebar Density', 'Comfortable'],
            ['Command Palette', '⌘K'],
            ['Notification Center', 'Enabled'],
          ]}
        />
      </Card>
    </div>
  )
}
