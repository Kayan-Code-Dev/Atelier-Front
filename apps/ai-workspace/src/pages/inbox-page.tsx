import { Link } from 'react-router-dom'
import { inboxMessages, type ChannelKind } from '@/mock/data'
import { Badge, Card, PageHeader } from '@/shared/ui'

const channelLabel: Record<ChannelKind, string> = {
  whatsapp: 'WhatsApp',
  messenger: 'Messenger',
  instagram: 'Instagram',
  facebook_comments: 'Facebook Comments',
  instagram_comments: 'Instagram Comments',
  email: 'Email',
  web_chat: 'Web Chat',
  app_chat: 'App Chat',
  telegram: 'Telegram',
}

export function InboxPage() {
  return (
    <div>
      <PageHeader
        title="Universal Inbox"
        description="One timeline across WhatsApp, Messenger, Instagram, comments, email, and chat."
      />
      <div className="mb-4 flex flex-wrap gap-2">
        {Object.values(channelLabel).map((label) => (
          <Badge key={label} tone="accent">
            {label}
          </Badge>
        ))}
      </div>
      <Card className="space-y-2 p-2">
        {inboxMessages.map((message) => (
          <Link
            key={message.id}
            to="/conversation"
            className="flex items-start justify-between gap-3 rounded-[var(--radius-sm)] px-3 py-3 hover:bg-[color:var(--color-surface-muted)]"
          >
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <p className="font-medium">{message.customer}</p>
                <Badge>{channelLabel[message.channel]}</Badge>
                {message.unread ? <Badge tone="info">Unread</Badge> : null}
              </div>
              <p className="mt-1 truncate text-sm text-muted">{message.preview}</p>
            </div>
            <div className="shrink-0 text-right">
              <p className="text-xs text-muted">{message.time}</p>
              <Badge
                className="mt-1"
                tone={
                  message.status === 'needs_approval'
                    ? 'warning'
                    : message.status === 'resolved'
                      ? 'success'
                      : 'neutral'
                }
              >
                {message.status}
              </Badge>
            </div>
          </Link>
        ))}
      </Card>
    </div>
  )
}
