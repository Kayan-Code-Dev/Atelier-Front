import { knowledgeDocuments, knowledgeSpaces } from '@/mock/data'
import { Badge, Card, CardHeader, DataTable, PageHeader, Stat } from '@/shared/ui'

export function KnowledgeCenterPage() {
  return (
    <div>
      <PageHeader title="Knowledge Center" description="Spaces, collections, packs, documents, versions, and publishing." />
      <div className="grid gap-3 sm:grid-cols-3">
        <Stat label="Spaces" value={String(knowledgeSpaces.length)} />
        <Stat label="Documents" value={String(knowledgeDocuments.length)} />
        <Stat label="Published" value="2" />
      </div>
      <Card className="mt-4">
        <CardHeader title="Documents" />
        <DataTable
          columns={['Title', 'Space', 'Version', 'Status']}
          rows={knowledgeDocuments.map((doc) => [
            doc.title,
            doc.space,
            doc.version,
            <Badge key={doc.id} tone={doc.status === 'Published' ? 'success' : 'warning'}>
              {doc.status}
            </Badge>,
          ])}
        />
      </Card>
    </div>
  )
}

export function KnowledgeSpacesPage() {
  return (
    <div>
      <PageHeader title="Knowledge Spaces" description="Tenant and global knowledge boundaries." />
      <div className="grid gap-3 md:grid-cols-3">
        {knowledgeSpaces.map((space) => (
          <Card key={space.id}>
            <CardHeader title={space.name} description={`Collections ${space.collections} · Packs ${space.packs}`} />
            <Badge tone={space.status === 'Published' ? 'success' : 'warning'}>{space.status}</Badge>
          </Card>
        ))}
      </div>
    </div>
  )
}

export function KnowledgePacksPage() {
  return (
    <div>
      <PageHeader title="Knowledge Packs" description="Publishable bundles of documents and playbooks." />
      <Card>
        <DataTable
          columns={['Pack', 'Documents', 'Status', 'Version']}
          rows={[
            ['Fitting Essentials', '8', <Badge tone="success">Published</Badge>, 'v2'],
            ['Returns AR', '5', <Badge tone="success">Published</Badge>, 'v3'],
            ['Escalation Pack', '4', <Badge tone="warning">Review</Badge>, 'v1'],
          ]}
        />
      </Card>
    </div>
  )
}
