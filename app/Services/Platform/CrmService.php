<?php

namespace App\Services\Platform;

use App\Models\Central\CrmCampaign;
use App\Models\Central\CrmDeal;
use App\Models\Central\CrmFollowUp;
use App\Models\Central\CrmLead;
use App\Models\Central\CrmLeadEvent;
use App\Models\Central\CrmLeadNote;
use App\Models\Central\CrmLookup;
use App\Models\Central\CrmQuotation;
use App\Models\Central\CrmSetting;
use App\Models\Central\SuperAdmin;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CrmService
{
    public function dashboardSummary(): array
    {
        $todayStart = CarbonImmutable::today();
        $monthStart = CarbonImmutable::now()->startOfMonth();

        $leadsToday = CrmLead::query()->whereDate('created_at', $todayStart)->count();
        $leadsMonth = CrmLead::query()->where('created_at', '>=', $monthStart)->count();
        $hotLeads = CrmLead::query()->where('temperature', 'hot')->whereNotIn('status', ['won', 'lost', 'not_interested'])->count();
        $followUpsToday = CrmFollowUp::query()
            ->where('status', 'pending')
            ->whereDate('due_at', $todayStart)
            ->count();
        $openDeals = CrmDeal::query()->whereNotIn('stage', ['won', 'lost'])->count();
        $closedDeals = CrmDeal::query()->whereIn('stage', ['won', 'lost'])->count();
        $wonDeals = CrmDeal::query()->where('stage', 'won');
        $newSubscriptions = (clone $wonDeals)->where('updated_at', '>=', $monthStart)->count();
        $systemSales = (clone $wonDeals)->where('updated_at', '>=', $monthStart)->count();
        $revenue = (float) CrmDeal::query()->where('stage', 'won')->where('updated_at', '>=', $monthStart)->sum('value');
        $totalLeads = max(1, CrmLead::query()->count());
        $wonLeads = CrmLead::query()->where('status', 'won')->count();
        $conversionRate = round(($wonLeads / $totalLeads) * 100, 1);

        $funnelStatuses = [
            'new' => CrmLead::query()->where('status', 'new')->count(),
            'qualified' => CrmLead::query()->whereIn('status', ['qualified', 'contacted'])->count(),
            'negotiation' => CrmLead::query()->whereIn('status', ['negotiation', 'quotation'])->count(),
            'won' => $wonLeads,
        ];

        $statusPie = CrmLead::query()
            ->select('status', DB::raw('count(*) as value'))
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => ['name' => $r->status, 'value' => (int) $r->value])
            ->values()
            ->all();

        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = CarbonImmutable::now()->subMonths($i);
            $start = $m->startOfMonth();
            $end = $m->endOfMonth();
            $sales = CrmDeal::query()->where('stage', 'won')->whereBetween('updated_at', [$start, $end])->count();
            $rev = (float) CrmDeal::query()->where('stage', 'won')->whereBetween('updated_at', [$start, $end])->sum('value');
            $monthly[] = [
                'month' => $m->locale('ar')->translatedFormat('F'),
                'sales' => $sales,
                'revenue' => $rev,
            ];
        }

        $topGovs = CrmLead::query()
            ->select('governorate', DB::raw('count(*) as value'))
            ->whereNotNull('governorate')
            ->where('governorate', '!=', '')
            ->groupBy('governorate')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->governorate, 'value' => (int) $r->value])
            ->all();

        $topSources = CrmLead::query()
            ->select('source', DB::raw('count(*) as value'))
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->groupBy('source')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->source, 'value' => (int) $r->value])
            ->all();

        $team = $this->teamPerformance();

        return [
            'kpis' => [
                'leads_today' => $leadsToday,
                'leads_month' => $leadsMonth,
                'hot_leads' => $hotLeads,
                'follow_ups_today' => $followUpsToday,
                'open_deals' => $openDeals,
                'closed_deals' => $closedDeals,
                'new_subscriptions' => $newSubscriptions,
                'system_sales' => $systemSales,
                'revenue' => $revenue,
                'conversion_rate' => $conversionRate,
            ],
            'urgent' => [
                'follow_ups_today' => $followUpsToday,
                'hot_without_contact' => CrmLead::query()
                    ->where('temperature', 'hot')
                    ->where(function ($q): void {
                        $q->whereNull('last_contact_at')
                            ->orWhere('last_contact_at', '<', CarbonImmutable::now()->subDay());
                    })
                    ->whereNotIn('status', ['won', 'lost', 'not_interested'])
                    ->count(),
            ],
            'funnel' => [
                ['stage' => 'Lead', 'value' => array_sum($funnelStatuses) ?: CrmLead::query()->count()],
                ['stage' => 'Qualified', 'value' => $funnelStatuses['qualified']],
                ['stage' => 'Negotiation', 'value' => $funnelStatuses['negotiation']],
                ['stage' => 'Won', 'value' => $funnelStatuses['won']],
            ],
            'status_pie' => $statusPie,
            'monthly_sales' => $monthly,
            'top_governorates' => $topGovs,
            'top_sources' => $topSources,
            'team' => $team,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateLeads(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $q = CrmLead::query()->with('assignee')->orderByDesc('id');

        if (! empty($filters['search'])) {
            $s = '%'.trim((string) $filters['search']).'%';
            $q->where(function ($b) use ($s): void {
                $b->where('name', 'like', $s)
                    ->orWhere('phone', 'like', $s)
                    ->orWhere('whatsapp', 'like', $s)
                    ->orWhere('atelier_name', 'like', $s)
                    ->orWhere('id', 'like', trim($s, '%'));
            });
        }
        foreach (['status', 'temperature', 'importance', 'source', 'governorate'] as $col) {
            if (! empty($filters[$col])) {
                $q->where($col, $filters[$col]);
            }
        }
        if (! empty($filters['assigned_to'])) {
            $q->where('assigned_to', (int) $filters['assigned_to']);
        }

        return $q->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createLead(array $data, ?int $actorId = null): CrmLead
    {
        $data = $this->normalizeLeadImportance($data);
        $lead = CrmLead::query()->create($data);
        $this->addEvent($lead, 'created', 'تم إنشاء الـ Lead', null, $actorId);
        if (! empty($data['next_follow_up_at'])) {
            CrmFollowUp::query()->create([
                'lead_id' => $lead->id,
                'kind' => 'follow_up',
                'priority' => ($data['importance'] ?? '') === 'high' ? 'urgent' : 'normal',
                'due_at' => $data['next_follow_up_at'],
                'reason' => 'متابعة أولية',
                'assigned_to' => $data['assigned_to'] ?? $actorId,
                'status' => 'pending',
            ]);
        }

        return $lead->fresh(['assignee']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateLead(CrmLead $lead, array $data, ?int $actorId = null): CrmLead
    {
        $data = $this->normalizeLeadImportance($data);
        $beforeStatus = $lead->status;
        $lead->fill($data);
        $lead->save();

        if (isset($data['status']) && $data['status'] !== $beforeStatus) {
            $this->addEvent($lead, 'status_changed', 'تغيير الحالة إلى '.$data['status'], null, $actorId);
        }

        // Sync existing deal if any — do not auto-create
        $existing = CrmDeal::query()->where('lead_id', $lead->id)->first();
        if ($existing) {
            $this->syncDealFromLead($lead->fresh());
        }

        return $lead->fresh(['assignee', 'events.author', 'leadNotes.author', 'attachments', 'followUps']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeLeadImportance(array $data): array
    {
        if (! empty($data['importance'])) {
            $map = [
                'low' => ['temperature' => 'cold', 'score' => 30],
                'medium' => ['temperature' => 'warm', 'score' => 55],
                'high' => ['temperature' => 'hot', 'score' => 85],
            ];
            $imp = (string) $data['importance'];
            if (isset($map[$imp])) {
                $data['temperature'] = $map[$imp]['temperature'];
                $data['score'] = $map[$imp]['score'];
            }
        } elseif (! empty($data['temperature']) && empty($data['importance'])) {
            $rev = ['cold' => 'low', 'warm' => 'medium', 'hot' => 'high'];
            $data['importance'] = $rev[$data['temperature']] ?? 'medium';
        }

        return $data;
    }

    public function openDealFromLead(CrmLead $lead, ?int $actorId = null, ?string $stage = 'new'): CrmDeal
    {
        $deal = CrmDeal::query()->where('lead_id', $lead->id)->first();
        if ($deal) {
            return $deal->load('assignee');
        }

        if ($lead->status === 'new') {
            $lead->status = 'qualified';
            $lead->save();
        }

        $deal = CrmDeal::query()->create([
            'lead_id' => $lead->id,
            'title' => $lead->atelier_name ?: $lead->name,
            'lead_name' => $lead->name,
            'value' => $lead->offer_value,
            'probability' => $lead->close_probability ?: 40,
            'temperature' => $lead->temperature,
            'score' => $lead->score,
            'stage' => $stage ?: 'new',
            'assigned_to' => $lead->assigned_to ?: $actorId,
            'next_follow_up_at' => $lead->next_follow_up_at,
        ]);

        $this->addEvent($lead, 'deal_opened', 'تم فتح صفقة', $deal->title, $actorId);

        return $deal->load('assignee');
    }

    public function scheduleAppointment(
        CrmLead $lead,
        string $kind,
        string $dueAt,
        ?string $reason = null,
        ?string $priority = null,
        ?int $actorId = null,
    ): CrmFollowUp {
        $kind = in_array($kind, ['call', 'follow_up'], true) ? $kind : 'follow_up';
        $fu = CrmFollowUp::query()->create([
            'lead_id' => $lead->id,
            'kind' => $kind,
            'priority' => $priority ?: (($lead->importance ?? '') === 'high' || $lead->temperature === 'hot' ? 'high' : 'normal'),
            'due_at' => $dueAt,
            'reason' => $reason ?: ($kind === 'call' ? 'موعد اتصال' : 'موعد متابعة'),
            'assigned_to' => $lead->assigned_to ?: $actorId,
            'status' => 'pending',
        ]);

        $lead->next_follow_up_at = $dueAt;
        $lead->save();

        $this->addEvent(
            $lead,
            $kind === 'call' ? 'call_scheduled' : 'follow_up_scheduled',
            $kind === 'call' ? 'تم إنشاء موعد اتصال' : 'تم إنشاء موعد متابعة',
            $fu->reason,
            $actorId,
        );

        return $fu->load(['lead', 'assignee']);
    }

    public function addNote(CrmLead $lead, string $body, ?int $actorId = null): CrmLeadNote
    {
        $note = CrmLeadNote::query()->create([
            'lead_id' => $lead->id,
            'body' => $body,
            'created_by' => $actorId,
        ]);
        $this->addEvent($lead, 'note', 'تمت إضافة ملاحظة', $body, $actorId);

        return $note->load('author');
    }

    public function addEvent(CrmLead $lead, string $type, string $title, ?string $body = null, ?int $actorId = null, ?array $meta = null): CrmLeadEvent
    {
        return CrmLeadEvent::query()->create([
            'lead_id' => $lead->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'meta' => $meta,
            'created_by' => $actorId,
        ]);
    }

    public function syncDealFromLead(CrmLead $lead): void
    {
        $stageMap = [
            'new' => 'new',
            'contacted' => 'new',
            'qualified' => 'qualified',
            'negotiation' => 'negotiation',
            'quotation' => 'negotiation',
            'won' => 'won',
            'lost' => 'lost',
            'not_interested' => 'lost',
        ];
        $stage = $stageMap[$lead->status] ?? 'new';

        $deal = CrmDeal::query()->where('lead_id', $lead->id)->first();
        $payload = [
            'lead_id' => $lead->id,
            'title' => $lead->atelier_name ?: $lead->name,
            'lead_name' => $lead->name,
            'value' => $lead->offer_value,
            'probability' => $lead->close_probability,
            'temperature' => $lead->temperature,
            'score' => $lead->score,
            'stage' => $stage,
            'assigned_to' => $lead->assigned_to,
            'next_follow_up_at' => $lead->next_follow_up_at,
        ];

        if ($deal) {
            $deal->update($payload);
        } else {
            CrmDeal::query()->create($payload);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function teamPerformance(): array
    {
        $crmPermissionKeys = [
            'view_crm',
            'manage_crm',
            'manage_crm_leads',
            'manage_crm_follow_ups',
            'manage_crm_deals',
            'manage_crm_quotations',
            'manage_crm_campaigns',
            'view_crm_team',
            'view_crm_reports',
            'manage_crm_settings',
        ];

        $admins = SuperAdmin::query()
            ->with(['role.permissions'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->filter(function (SuperAdmin $admin) use ($crmPermissionKeys): bool {
                $slug = strtolower((string) ($admin->role?->slug ?? ''));
                $roleName = strtolower((string) ($admin->role?->name ?? ''));

                if (in_array($slug, ['sales', 'sales-agent', 'sales_agent'], true)) {
                    return true;
                }
                if (str_contains($roleName, 'sales') || str_contains($roleName, 'مبيعات')) {
                    return true;
                }

                $keys = $admin->permissionKeys();

                return count(array_intersect($keys, $crmPermissionKeys)) > 0;
            });

        $out = [];
        foreach ($admins as $admin) {
            $leads = CrmLead::query()->where('assigned_to', $admin->id)->count();
            $followUps = CrmFollowUp::query()->where('assigned_to', $admin->id)->count();
            $sales = CrmDeal::query()->where('assigned_to', $admin->id)->where('stage', 'won')->count();
            $conversion = $leads > 0 ? round(($sales / $leads) * 100, 1) : 0;
            $commission = (float) CrmDeal::query()->where('assigned_to', $admin->id)->where('stage', 'won')->sum('value') * 0.05;

            $out[] = [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role?->name ?? 'مسؤول',
                'role_slug' => $admin->role?->slug,
                'leads' => $leads,
                'follow_ups' => $followUps,
                'sales' => $sales,
                'conversion' => $conversion,
                'commission' => round($commission, 2),
                'rating' => min(5, round(3 + ($conversion / 25), 1)),
            ];
        }

        return $out;
    }

    public function ensureDefaults(): void
    {
        $lookups = [
            'source' => ['فيسبوك', 'واتساب', 'إحالة', 'إعلان ممول', 'موقع', 'معرض'],
            'status' => ['new', 'contacted', 'qualified', 'negotiation', 'quotation', 'won', 'lost', 'not_interested'],
            'activity' => ['فساتين زفاف', 'إيجار فساتين', 'تفصيل', 'سهرة', 'رجالي ونسائي', 'خياطة'],
            'interest' => ['اشتراك سنوي', 'فرع إضافي', 'إيجار', 'تفصيل', 'متجر إلكتروني'],
            'governorate' => ['القاهرة', 'الجيزة', 'الإسكندرية', 'الشرقية', 'الدقهلية', 'المنوفية', 'أسيوط'],
            'importance' => ['low', 'medium', 'high'],
            'temperature' => ['cold', 'warm', 'hot'],
        ];
        $labels = [
            'new' => 'جديد', 'contacted' => 'تواصل', 'qualified' => 'مؤهل', 'negotiation' => 'تفاوض',
            'quotation' => 'عرض سعر', 'won' => 'تم البيع', 'lost' => 'مغلق', 'not_interested' => 'غير مهتم',
            'cold' => 'بارد', 'warm' => 'دافئ', 'hot' => 'ساخن',
            'low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية',
        ];
        foreach ($lookups as $type => $items) {
            foreach ($items as $i => $item) {
                $key = in_array($type, ['status', 'temperature', 'importance'], true) ? $item : null;
                $label = $labels[$item] ?? $item;
                CrmLookup::query()->firstOrCreate(
                    ['type' => $type, 'label' => $label],
                    ['key' => $key, 'sort_order' => $i + 1, 'is_active' => true],
                );
            }
        }

        CrmSetting::query()->firstOrCreate(
            ['key' => 'score_weights'],
            ['value' => [
                'response_speed' => 25,
                'activity_size' => 20,
                'source_quality' => 20,
                'engagement' => 20,
                'offer_value' => 15,
            ]],
        );
        CrmSetting::query()->firstOrCreate(
            ['key' => 'importance_rules'],
            ['value' => [
                'high' => 'عميل ساخن — تفاعل سريع أو عرض سعر أو احتمالية عالية',
                'medium' => 'اهتمام متوسط — يحتاج متابعة منتظمة',
                'low' => 'اهتمام منخفض — رعاية طويلة الأمد',
            ]],
        );
        CrmSetting::query()->firstOrCreate(
            ['key' => 'temperature_rules'],
            ['value' => [
                'hot' => 'تفاعل خلال 24 ساعة + Score ≥ 75 أو طلب عرض سعر',
                'warm' => 'تواصل خلال أسبوع + Score بين 50 و 74',
                'cold' => 'لا تفاعل لأكثر من 7 أيام أو Score < 50',
            ]],
        );
    }

    public function seedSampleIfEmpty(): void
    {
        if (CrmLead::query()->exists()) {
            return;
        }

        $adminId = SuperAdmin::query()->value('id');
        $samples = [
            ['سارة محمود', '01012345678', 'أتيليه سارة', 'القاهرة', 'فساتين زفاف', 'فيسبوك', 'high', 'negotiation', 'باقة برو', 18000, 75],
            ['نورا حسن', '01123456789', 'نورا فاشن', 'الجيزة', 'تفصيل', 'واتساب', 'medium', 'negotiation', 'باقة احترافية', 12000, 60],
            ['مينا جرجس', '01234567890', 'مينا دريس', 'الإسكندرية', 'إيجار فساتين', 'إحالة', 'high', 'quotation', 'باقة برو', 25000, 82],
            ['هبة إبراهيم', '01555551234', 'هبة ستايل', 'الشرقية', 'خياطة', 'إعلان ممول', 'low', 'new', 'باقة أساسية', 8000, 25],
            ['دينا فؤاد', '01098765432', 'دينا كوتيور', 'القاهرة', 'سهرة', 'فيسبوك', 'medium', 'qualified', 'باقة احترافية', 15000, 45],
            ['كريم عادل', '01111112222', 'كريم للأزياء', 'الدقهلية', 'رجالي ونسائي', 'موقع', 'medium', 'contacted', 'باقة أساسية', 10000, 35],
        ];

        foreach ($samples as $i => $s) {
            $lead = $this->createLead([
                'name' => $s[0],
                'phone' => $s[1],
                'whatsapp' => $s[1],
                'atelier_name' => $s[2],
                'governorate' => $s[3],
                'activity' => $s[4],
                'source' => $s[5],
                'importance' => $s[6],
                'status' => $s[7],
                'expected_plan' => $s[8],
                'assigned_to' => $adminId,
                'last_contact_at' => now()->subHours($i + 1),
                'next_follow_up_at' => now()->addHours(2 + $i),
                'last_message' => 'رسالة تجريبية من العميل',
                'offer_value' => $s[9],
                'close_probability' => $s[10],
                'branches_count' => 1 + ($i % 3),
                'employees_count' => 3 + $i,
            ], $adminId);

            CrmFollowUp::query()->create([
                'lead_id' => $lead->id,
                'priority' => $lead->temperature === 'hot' ? 'urgent' : ($i % 2 ? 'high' : 'normal'),
                'due_at' => now()->addHours($i),
                'reason' => 'متابعة مبيعات',
                'assigned_to' => $adminId,
                'status' => 'pending',
            ]);
        }

        CrmCampaign::query()->create([
            'name' => 'حملة فيسبوك — زفاف الصيف',
            'channel' => 'فيسبوك',
            'budget' => 25000,
            'spent' => 18400,
            'messages' => 1260,
            'qualified_leads' => 148,
            'sales' => 18,
            'revenue' => 198000,
        ]);
        CrmCampaign::query()->create([
            'name' => 'واتساب إعادة تفعيل',
            'channel' => 'واتساب',
            'budget' => 8000,
            'spent' => 6200,
            'messages' => 890,
            'qualified_leads' => 76,
            'sales' => 11,
            'revenue' => 98000,
        ]);

        $lead = CrmLead::query()->where('status', 'quotation')->first();
        if ($lead) {
            $q = CrmQuotation::query()->create([
                'lead_id' => $lead->id,
                'number' => 'QT-'.now()->format('Y').'-001',
                'lead_name' => $lead->name,
                'atelier_name' => $lead->atelier_name,
                'plan_name' => 'باقة برو — سنوي',
                'amount' => $lead->offer_value,
                'discount' => 2500,
                'status' => 'sent',
                'valid_until' => now()->addDays(7)->toDateString(),
                'created_by' => $adminId,
            ]);
            $q->items()->createMany([
                ['label' => 'اشتراك برو (12 شهر)', 'price' => 22000, 'sort_order' => 1],
                ['label' => 'فرع إضافي', 'price' => 3000, 'sort_order' => 2],
                ['label' => 'خصم تفاوض', 'price' => -2500, 'sort_order' => 3],
            ]);
        }
    }
}
