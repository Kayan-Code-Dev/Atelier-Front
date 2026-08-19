export type ChannelKind =
  | 'whatsapp'
  | 'messenger'
  | 'instagram'
  | 'facebook_comments'
  | 'instagram_comments'
  | 'email'
  | 'web_chat'
  | 'app_chat'
  | 'telegram'

export const inboxMessages = [
  {
    id: 'm1',
    channel: 'whatsapp' as ChannelKind,
    customer: 'Sara Alami',
    preview: 'هل يمكن تعديل موعد التركيب؟',
    time: '2m',
    unread: true,
    status: 'ai_handling',
  },
  {
    id: 'm2',
    channel: 'instagram' as ChannelKind,
    customer: 'Noura',
    preview: 'سعر فستان السهرة؟',
    time: '8m',
    unread: true,
    status: 'needs_approval',
  },
  {
    id: 'm3',
    channel: 'facebook_comments' as ChannelKind,
    customer: 'Khaled',
    preview: 'هل يوجد توصيل للرياض؟',
    time: '14m',
    unread: false,
    status: 'queued',
  },
  {
    id: 'm4',
    channel: 'email' as ChannelKind,
    customer: 'ops@atelier.co',
    preview: 'Invoice clarification for order #8821',
    time: '31m',
    unread: false,
    status: 'human',
  },
  {
    id: 'm5',
    channel: 'messenger' as ChannelKind,
    customer: 'Maha',
    preview: 'أريد حجز تجربة قياس',
    time: '1h',
    unread: true,
    status: 'ai_handling',
  },
  {
    id: 'm6',
    channel: 'web_chat' as ChannelKind,
    customer: 'Visitor 204',
    preview: 'Do you ship internationally?',
    time: '2h',
    unread: false,
    status: 'resolved',
  },
  {
    id: 'm7',
    channel: 'app_chat' as ChannelKind,
    customer: 'Layla',
    preview: 'تم استلام الطلب، متى الشحن؟',
    time: '3h',
    unread: false,
    status: 'ai_handling',
  },
  {
    id: 'm8',
    channel: 'instagram_comments' as ChannelKind,
    customer: 'Reem',
    preview: 'DM me please',
    time: '4h',
    unread: true,
    status: 'needs_approval',
  },
]

export const conversationDetail = {
  id: 'conv-8821',
  customer: 'Sara Alami',
  channel: 'whatsapp' as ChannelKind,
  intent: 'reschedule_installation',
  confidence: 0.91,
  mode: 'Hybrid',
  reasoning:
    'Customer requested date change; calendar availability checked; pending human confirmation for weekend slot.',
  plan: ['Clarify preferred date', 'Check technician capacity', 'Propose two slots', 'Confirm & update order'],
  tools: [
    { name: 'get_order', status: 'completed', at: '10:41' },
    { name: 'check_availability', status: 'completed', at: '10:41' },
    { name: 'update_reservation', status: 'awaiting_approval', at: '10:42' },
  ],
  memoryUsed: ['Prefers evening appointments', 'VIP customer', 'Previous complaint on delay'],
  knowledgeUsed: ['Installation SLA', 'Weekend surcharge policy'],
  messages: [
    { from: 'customer', text: 'هل يمكن تعديل موعد التركيب؟', at: '10:40' },
    { from: 'ai', text: 'بالتأكيد. هل تفضّلين يوم الخميس مساءً أم الجمعة؟', at: '10:41' },
    { from: 'customer', text: 'الجمعة أفضل.', at: '10:42' },
  ],
}

export const plannerMonitor = {
  intent: 'Create quotation for bridal package',
  confidence: 0.87,
  goals: ['Collect measurements', 'Recommend package', 'Generate quotation', 'Request approval'],
  risk: 'Medium — pricing override required',
  approvals: ['Discount > 10%', 'Custom embroidery'],
  plannedTools: ['customer.get', 'catalog.search', 'quotation.create', 'notify.staff'],
  decisionPath: ['Intent classified', 'Policy check', 'Tool plan', 'Approval gate'],
  graph: [
    { id: 'start', label: 'Intent' },
    { id: 'policy', label: 'Policy' },
    { id: 'tools', label: 'Tools' },
    { id: 'approval', label: 'Approval' },
    { id: 'reply', label: 'Reply' },
  ],
}

export const memoryRecords = [
  { id: 'mem1', type: 'Working', content: 'Current order #8821 open', score: 0.98 },
  { id: 'mem2', type: 'Short-Term', content: 'Asked about Friday installation', score: 0.9 },
  { id: 'mem3', type: 'Long-Term', content: 'Prefers WhatsApp over phone', score: 0.84 },
  { id: 'mem4', type: 'Customer', content: 'VIP since 2023', score: 0.93 },
  { id: 'mem5', type: 'Business', content: 'Weekend installation surcharge 15%', score: 0.88 },
  { id: 'mem6', type: 'Summary', content: 'Customer negotiating reschedule for bridal order', score: 0.86 },
]

export const knowledgeSpaces = [
  { id: 'ks1', name: 'Global Policies', collections: 12, packs: 4, status: 'Published' },
  { id: 'ks2', name: 'Atelier Catalog', collections: 28, packs: 9, status: 'Published' },
  { id: 'ks3', name: 'Support Playbooks', collections: 17, packs: 6, status: 'Review' },
]

export const knowledgeDocuments = [
  { id: 'kd1', title: 'Return Policy AR', version: 'v3', status: 'Published', space: 'Global Policies' },
  { id: 'kd2', title: 'Bridal Fitting Guide', version: 'v2', status: 'Draft', space: 'Atelier Catalog' },
  { id: 'kd3', title: 'Escalation Matrix', version: 'v5', status: 'Published', space: 'Support Playbooks' },
]

export const workflows = [
  { id: 'wf1', name: 'Incoming Message Router', trigger: 'Incoming Message', status: 'Published', runs: 1284 },
  { id: 'wf2', name: 'Comment → Private DM', trigger: 'Comment', status: 'Published', runs: 412 },
  { id: 'wf3', name: 'Invoice Reminder', trigger: 'Cron', status: 'Paused', runs: 90 },
]

export const workflowExecutions = [
  { id: 'ex1', workflow: 'Incoming Message Router', status: 'Completed', duration: '1.2s', retries: 0 },
  { id: 'ex2', workflow: 'Comment → Private DM', status: 'Failed', duration: '0.8s', retries: 2 },
  { id: 'ex3', workflow: 'Invoice Reminder', status: 'Running', duration: '—', retries: 0 },
]

export const aiEmployees = [
  {
    id: 'emp1',
    name: 'Lina',
    persona: 'Sales Agent',
    mode: 'Hybrid',
    status: 'online',
    conversations: 18,
    performance: 0.94,
    promptProfile: 'Sales-v4',
  },
  {
    id: 'emp2',
    name: 'Omar',
    persona: 'Support Agent',
    mode: 'Full Auto',
    status: 'busy',
    conversations: 26,
    performance: 0.89,
    promptProfile: 'Support-v3',
  },
  {
    id: 'emp3',
    name: 'Maya',
    persona: 'Reception Agent',
    mode: 'Assistant',
    status: 'online',
    conversations: 9,
    performance: 0.91,
    promptProfile: 'Reception-v2',
  },
]

export const personas = [
  { id: 'p1', name: 'Sales Agent', tone: 'Confident & warm', style: 'Consultative' },
  { id: 'p2', name: 'Support Agent', tone: 'Calm & precise', style: 'Problem-solving' },
  { id: 'p3', name: 'Reception Agent', tone: 'Welcoming', style: 'Guided' },
]

export const promptProfiles = [
  { id: 'pp1', name: 'Sales-v4', version: '4.2', status: 'Active' },
  { id: 'pp2', name: 'Support-v3', version: '3.8', status: 'Active' },
  { id: 'pp3', name: 'Escalation-v1', version: '1.4', status: 'Testing' },
]

export const analyticsSeries = [
  { day: 'Mon', conversations: 120, automation: 78, escalation: 14 },
  { day: 'Tue', conversations: 142, automation: 88, escalation: 18 },
  { day: 'Wed', conversations: 133, automation: 91, escalation: 12 },
  { day: 'Thu', conversations: 158, automation: 102, escalation: 20 },
  { day: 'Fri', conversations: 171, automation: 110, escalation: 22 },
  { day: 'Sat', conversations: 98, automation: 70, escalation: 9 },
  { day: 'Sun', conversations: 84, automation: 61, escalation: 7 },
]

export const analyticsKpis = {
  conversations: 906,
  responseTime: '18s',
  automationRate: 0.72,
  aiResolution: 0.64,
  humanEscalation: 0.11,
  csat: 4.6,
  tokenUsage: '2.4M',
  cost: 184.2,
  providerUsage: { openai: 42, anthropic: 31, gemini: 17, ollama: 10 },
  roi: 3.8,
}

export const channels = [
  { id: 'c1', name: 'WhatsApp', status: 'Connected', health: 'Healthy' },
  { id: 'c2', name: 'Messenger', status: 'Connected', health: 'Healthy' },
  { id: 'c3', name: 'Instagram', status: 'Degraded', health: 'Degraded' },
  { id: 'c4', name: 'Facebook', status: 'Connected', health: 'Healthy' },
  { id: 'c5', name: 'Telegram', status: 'Disconnected', health: 'Offline' },
  { id: 'c6', name: 'Email', status: 'Connected', health: 'Healthy' },
  { id: 'c7', name: 'Web Chat', status: 'Connected', health: 'Healthy' },
]

export const approvals = [
  { id: 'a1', title: 'Discount 15% for Sara', owner: 'Lina', risk: 'Medium', status: 'Pending' },
  { id: 'a2', title: 'Publish Knowledge Pack: Fitting', owner: 'Maya', risk: 'Low', status: 'Pending' },
  { id: 'a3', title: 'Override SLA reply delay', owner: 'Omar', risk: 'High', status: 'Review' },
]

export const auditTrail = [
  { id: 'au1', actor: 'Lina', action: 'Tool call: update_reservation', at: '10:42' },
  { id: 'au2', actor: 'System', action: 'Approval requested', at: '10:42' },
  { id: 'au3', actor: 'Admin', action: 'Workflow published: Invoice Reminder', at: '09:18' },
]

export const campaigns = [
  { id: 'cp1', name: 'Ramadan Follow-up', status: 'Running', reach: 2400, conversion: 0.08 },
  { id: 'cp2', name: 'VIP Soft Launch', status: 'Scheduled', reach: 320, conversion: 0 },
]

export const customerProfile = {
  name: 'Sara Alami',
  tier: 'VIP',
  city: 'Riyadh',
  language: 'Arabic',
  tags: ['Bridal', 'WhatsApp Preferred', 'High LTV'],
  timeline: [
    { at: 'Today', event: 'Requested installation reschedule' },
    { at: '3 days ago', event: 'Paid invoice #8821' },
    { at: '2 weeks ago', event: 'Fitting appointment completed' },
  ],
}

export const notifications = [
  { id: 'n1', title: 'Approval needed', body: 'Discount override for Sara', time: '2m' },
  { id: 'n2', title: 'Channel health', body: 'Instagram degraded', time: '18m' },
  { id: 'n3', title: 'Workflow failure', body: 'Comment → Private DM retried twice', time: '41m' },
]
