import type { LucideIcon } from 'lucide-react'
import {
  Activity,
  BarChart3,
  BookOpen,
  Bot,
  Boxes,
  Cable,
  CheckSquare,
  ClipboardList,
  FileText,
  GitBranch,
  Inbox,
  LayoutDashboard,
  MemoryStick,
  MessageSquare,
  Settings,
  Sparkles,
  Users,
  Workflow,
  Shield,
  Megaphone,
  UserRound,
} from 'lucide-react'

export type NavItem = {
  label: string
  to: string
  icon: LucideIcon
  group: string
}

export const navItems: NavItem[] = [
  { label: 'Dashboard', to: '/', icon: LayoutDashboard, group: 'Operate' },
  { label: 'Universal Inbox', to: '/inbox', icon: Inbox, group: 'Operate' },
  { label: 'Conversation', to: '/conversation', icon: MessageSquare, group: 'Operate' },
  { label: 'Approvals', to: '/approvals', icon: CheckSquare, group: 'Operate' },
  { label: 'AI Planner Monitor', to: '/planner', icon: Sparkles, group: 'Intelligence' },
  { label: 'Memory Explorer', to: '/memory', icon: MemoryStick, group: 'Intelligence' },
  { label: 'Knowledge Center', to: '/knowledge', icon: BookOpen, group: 'Intelligence' },
  { label: 'Knowledge Spaces', to: '/knowledge/spaces', icon: Boxes, group: 'Intelligence' },
  { label: 'Knowledge Packs', to: '/knowledge/packs', icon: FileText, group: 'Intelligence' },
  { label: 'AI Employees', to: '/employees', icon: Bot, group: 'Workforce' },
  { label: 'AI Personas', to: '/personas', icon: UserRound, group: 'Workforce' },
  { label: 'Prompt Profiles', to: '/prompts', icon: ClipboardList, group: 'Workforce' },
  { label: 'Workflow Studio', to: '/workflows', icon: Workflow, group: 'Automation' },
  { label: 'Workflow Executions', to: '/workflows/executions', icon: GitBranch, group: 'Automation' },
  { label: 'Automation Center', to: '/automation', icon: Activity, group: 'Automation' },
  { label: 'Campaign Center', to: '/campaigns', icon: Megaphone, group: 'Automation' },
  { label: 'Analytics', to: '/analytics', icon: BarChart3, group: 'Insights' },
  { label: 'Reports', to: '/reports', icon: FileText, group: 'Insights' },
  { label: 'Audit Trail', to: '/audit', icon: Shield, group: 'Insights' },
  { label: 'Channel Connections', to: '/channels', icon: Cable, group: 'Platform' },
  { label: 'Tenant Settings', to: '/settings/tenant', icon: Users, group: 'Platform' },
  { label: 'Workspace Settings', to: '/settings/workspace', icon: Settings, group: 'Platform' },
]

export const commandItems = navItems.map((item) => ({
  id: item.to,
  label: item.label,
  to: item.to,
  group: item.group,
}))
