import { Navigate, Route, Routes } from 'react-router-dom'
import { WorkspaceShell } from '@/layouts/workspace-shell'
import { AnalyticsPage, ReportsPage } from '@/pages/analytics-pages'
import { ConversationPage, CustomerProfilePage } from '@/pages/conversation-page'
import { DashboardPage } from '@/pages/dashboard-page'
import { InboxPage } from '@/pages/inbox-page'
import {
  KnowledgeCenterPage,
  KnowledgePacksPage,
  KnowledgeSpacesPage,
} from '@/pages/knowledge-pages'
import { MemoryPage } from '@/pages/memory-page'
import { PlannerPage } from '@/pages/planner-page'
import {
  ApprovalsPage,
  AuditTrailPage,
  CampaignsPage,
  ChannelsPage,
  TenantSettingsPage,
  WorkspaceSettingsPage,
} from '@/pages/platform-pages'
import {
  AutomationCenterPage,
  WorkflowExecutionsPage,
  WorkflowStudioPage,
} from '@/pages/workflow-pages'
import { EmployeesPage, PersonasPage, PromptProfilesPage } from '@/pages/workforce-pages'

export function AppRouter() {
  return (
    <Routes>
      <Route element={<WorkspaceShell />}>
        <Route index element={<DashboardPage />} />
        <Route path="inbox" element={<InboxPage />} />
        <Route path="conversation" element={<ConversationPage />} />
        <Route path="conversation/timeline" element={<ConversationPage />} />
        <Route path="customer" element={<CustomerProfilePage />} />
        <Route path="planner" element={<PlannerPage />} />
        <Route path="memory" element={<MemoryPage />} />
        <Route path="knowledge" element={<KnowledgeCenterPage />} />
        <Route path="knowledge/spaces" element={<KnowledgeSpacesPage />} />
        <Route path="knowledge/packs" element={<KnowledgePacksPage />} />
        <Route path="workflows" element={<WorkflowStudioPage />} />
        <Route path="workflows/executions" element={<WorkflowExecutionsPage />} />
        <Route path="automation" element={<AutomationCenterPage />} />
        <Route path="campaigns" element={<CampaignsPage />} />
        <Route path="employees" element={<EmployeesPage />} />
        <Route path="personas" element={<PersonasPage />} />
        <Route path="prompts" element={<PromptProfilesPage />} />
        <Route path="analytics" element={<AnalyticsPage />} />
        <Route path="reports" element={<ReportsPage />} />
        <Route path="approvals" element={<ApprovalsPage />} />
        <Route path="audit" element={<AuditTrailPage />} />
        <Route path="channels" element={<ChannelsPage />} />
        <Route path="settings/tenant" element={<TenantSettingsPage />} />
        <Route path="settings/workspace" element={<WorkspaceSettingsPage />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  )
}
