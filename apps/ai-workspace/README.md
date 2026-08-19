# AOS AI Workspace (`apps/ai-workspace`)

**Sprint 13** — Enterprise operating surface for the digital employee.

## Workspace Philosophy

This is not a classic admin panel. It is an **AI Workspace** for operating conversations, automation, knowledge, memory, planners, and AI employees as a coordinated workforce.

Operators should feel they are managing a team of digital employees — with approvals, monitoring, and human-in-the-loop controls — not chatting with a bot.

## Stack

- React + TypeScript
- Vite
- Tailwind CSS v4
- Shadcn-style reusable primitives
- React Router
- React Query
- Framer Motion
- Recharts
- React Hook Form + Zod (ready for forms)

Mock-first: no backend and no live APIs in this sprint.

## Navigation

Shell includes:

- Top Navigation (brand, global search, notifications, theme, profile)
- Left Sidebar (feature groups)
- Right Context Panel (conversation + customer + workforce pulse)
- Command Palette (`⌘K` / `Ctrl+K`)
- Breadcrumbs
- Responsive mobile drawer

## Component Library

Reusable primitives live in `src/shared/ui`:

- Button, Badge, Card, Input, Textarea
- Dialog, Drawer
- DataTable, Stat, PageHeader
- EmptyState, Skeleton, StatusDot

Do not duplicate components inside feature pages.

## Folder Structure

```
apps/ai-workspace/
  src/
    app/            # router, navigation
    layouts/        # workspace shell
    pages/          # route screens
    features/       # reserved for feature modules
    shared/ui       # design system primitives
    shared/lib      # helpers
    shared/hooks    # theme + shared hooks
    mock/           # full mock dataset
    styles/         # tokens + global styles
```

## Screens Delivered

Dashboard · Universal Inbox · Conversation Workspace · Customer Profile · Planner Monitor · Memory Explorer · Knowledge Center/Spaces/Packs · Workflow Studio/Executions · Automation Center · Campaign Center · AI Employees · Personas · Prompt Profiles · Analytics · Reports · Approvals · Audit Trail · Channel Connections · Tenant Settings · Workspace Settings

## Extension Strategy

1. Keep pages thin; move domain UI into `features/*` as complexity grows.
2. Replace `src/mock/data.ts` with React Query loaders bound to AOS APIs.
3. Add real form schemas with Zod + React Hook Form for settings/approvals.
4. Keep design tokens centralized in `src/styles/globals.css`.
5. Preserve shell contracts (sidebar groups, command palette, context panel).

## Local Development

```bash
cd apps/ai-workspace
npm install
npm run dev
```

App runs on `http://localhost:5174`.

## Architecture Notes

- Atomic Design for primitives
- Feature-based routing and page ownership
- Accessibility basics: labels, focus rings, dialog semantics
- Dark/Light mode tokens
- Responsive desktop / tablet / mobile shell
