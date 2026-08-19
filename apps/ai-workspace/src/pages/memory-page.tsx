import { memoryRecords } from '@/mock/data'
import { Badge, Card, DataTable, PageHeader } from '@/shared/ui'

export function MemoryPage() {
  return (
    <div>
      <PageHeader
        title="Memory Explorer"
        description="Working, short-term, long-term, customer, business, and summary memory."
      />
      <div className="mb-4 flex flex-wrap gap-2">
        {['Working', 'Short-Term', 'Long-Term', 'Customer', 'Business', 'Summary'].map((type) => (
          <Badge key={type} tone="accent">
            {type}
          </Badge>
        ))}
      </div>
      <Card>
        <DataTable
          columns={['Type', 'Content', 'Importance Score']}
          rows={memoryRecords.map((record) => [
            <Badge key={record.id}>{record.type}</Badge>,
            record.content,
            `${Math.round(record.score * 100)}%`,
          ])}
        />
      </Card>
    </div>
  )
}
