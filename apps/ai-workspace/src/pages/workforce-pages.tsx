import { aiEmployees, personas, promptProfiles } from '@/mock/data'
import { formatPercent } from '@/shared/lib/utils'
import { Badge, Card, CardHeader, DataTable, PageHeader, StatusDot } from '@/shared/ui'

export function EmployeesPage() {
  return (
    <div>
      <PageHeader title="AI Employees" description="Operate digital staff like a real workforce." />
      <div className="grid gap-3 md:grid-cols-3">
        {aiEmployees.map((employee) => (
          <Card key={employee.id}>
            <CardHeader
              title={employee.name}
              description={employee.persona}
              action={<StatusDot status={employee.status as 'online' | 'busy'} label={employee.status} />}
            />
            <dl className="space-y-2 text-sm">
              <div className="flex justify-between"><dt className="text-muted">Mode</dt><dd>{employee.mode}</dd></div>
              <div className="flex justify-between"><dt className="text-muted">Assigned</dt><dd>{employee.conversations}</dd></div>
              <div className="flex justify-between"><dt className="text-muted">Performance</dt><dd>{formatPercent(employee.performance)}</dd></div>
              <div className="flex justify-between"><dt className="text-muted">Prompt Profile</dt><dd>{employee.promptProfile}</dd></div>
            </dl>
          </Card>
        ))}
      </div>
    </div>
  )
}

export function PersonasPage() {
  return (
    <div>
      <PageHeader title="AI Personas" description="Tone, style, and behavioral identity." />
      <div className="grid gap-3 md:grid-cols-3">
        {personas.map((persona) => (
          <Card key={persona.id}>
            <CardHeader title={persona.name} />
            <p className="text-sm"><span className="text-muted">Tone:</span> {persona.tone}</p>
            <p className="mt-1 text-sm"><span className="text-muted">Style:</span> {persona.style}</p>
          </Card>
        ))}
      </div>
    </div>
  )
}

export function PromptProfilesPage() {
  return (
    <div>
      <PageHeader title="Prompt Profiles" description="Versioned prompt operating profiles for employees." />
      <Card>
        <DataTable
          columns={['Profile', 'Version', 'Status']}
          rows={promptProfiles.map((profile) => [
            profile.name,
            profile.version,
            <Badge key={profile.id} tone={profile.status === 'Active' ? 'success' : 'warning'}>
              {profile.status}
            </Badge>,
          ])}
        />
      </Card>
    </div>
  )
}
