import { workflows, workflowExecutions } from '@/mock/data'
import { Badge, Button, Card, CardHeader, DataTable, PageHeader, Stat } from '@/shared/ui'

export function WorkflowStudioPage() {
  return (
    <div>
      <PageHeader
        title="Workflow Studio"
        description="Design and version automation flows without touching business implementations."
        actions={<Button>New Workflow</Button>}
      />
      <Card>
        <DataTable
          columns={['Workflow', 'Trigger', 'Status', 'Runs']}
          rows={workflows.map((workflow) => [
            workflow.name,
            workflow.trigger,
            <Badge key={workflow.id} tone={workflow.status === 'Published' ? 'success' : 'warning'}>
              {workflow.status}
            </Badge>,
            String(workflow.runs),
          ])}
        />
      </Card>
    </div>
  )
}

export function WorkflowExecutionsPage() {
  return (
    <div>
      <PageHeader title="Workflow Executions" description="Monitor runs, failures, and retries." />
      <div className="mb-4 grid gap-3 sm:grid-cols-3">
        <Stat label="Completed" value="1" />
        <Stat label="Failed" value="1" />
        <Stat label="Running" value="1" />
      </div>
      <Card>
        <DataTable
          columns={['Execution', 'Workflow', 'Status', 'Duration', 'Retries']}
          rows={workflowExecutions.map((execution) => [
            execution.id,
            execution.workflow,
            <Badge
              key={execution.id}
              tone={
                execution.status === 'Completed'
                  ? 'success'
                  : execution.status === 'Failed'
                    ? 'danger'
                    : 'info'
              }
            >
              {execution.status}
            </Badge>,
            execution.duration,
            String(execution.retries),
          ])}
        />
      </Card>
    </div>
  )
}

export function AutomationCenterPage() {
  return (
    <div>
      <PageHeader title="Automation Center" description="Workflows, triggers, executions, failures, retries, schedules." />
      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader title="Workflows & Triggers" />
          <DataTable
            columns={['Workflow', 'Trigger', 'Status']}
            rows={workflows.map((workflow) => [workflow.name, workflow.trigger, workflow.status])}
          />
        </Card>
        <Card>
          <CardHeader title="Failures & Retries" />
          <DataTable
            columns={['Execution', 'Status', 'Retries']}
            rows={workflowExecutions.map((execution) => [
              execution.id,
              execution.status,
              String(execution.retries),
            ])}
          />
        </Card>
      </div>
      <Card className="mt-4">
        <CardHeader title="Schedules" description="Conceptual cron and delayed tasks" />
        <DataTable
          columns={['Schedule', 'Cadence', 'Next Run', 'Status']}
          rows={[
            ['Invoice Reminder', 'Daily 09:00', 'Tomorrow 09:00', 'Paused'],
            ['VIP Nudge', 'Every Mon', 'Mon 11:00', 'Active'],
          ]}
        />
      </Card>
    </div>
  )
}
