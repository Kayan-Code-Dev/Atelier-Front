<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\CrmLeadResource;
use App\Models\Central\CrmCampaign;
use App\Models\Central\CrmDeal;
use App\Models\Central\CrmFollowUp;
use App\Models\Central\CrmLead;
use App\Models\Central\CrmLeadAttachment;
use App\Models\Central\CrmLookup;
use App\Models\Central\CrmQuotation;
use App\Models\Central\CrmSetting;
use App\Models\Central\SuperAdmin;
use App\Services\Platform\CrmService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmController extends Controller
{
    public function __construct(private readonly CrmService $crm) {}

    public function dashboard(): JsonResponse
    {
        $this->crm->ensureDefaults();

        return ApiResponse::success($this->crm->dashboardSummary());
    }

    public function leads(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 20)));
        $paginator = $this->crm->paginateLeads($request->only([
            'search', 'status', 'temperature', 'importance', 'source', 'governorate', 'assigned_to',
        ]), $perPage);

        return ApiResponse::paginated(
            $paginator,
            CrmLeadResource::collection($paginator->items())->resolve(),
        );
    }

    public function storeLead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'atelier_name' => ['nullable', 'string', 'max:255'],
            'governorate' => ['nullable', 'string', 'max:100'],
            'activity' => ['nullable', 'string', 'max:100'],
            'branches_count' => ['nullable', 'integer', 'min:0'],
            'employees_count' => ['nullable', 'integer', 'min:0'],
            'source' => ['nullable', 'string', 'max:100'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'temperature' => ['nullable', Rule::in(['cold', 'warm', 'hot'])],
            'importance' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'expected_plan' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:40'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('super_admins', 'id')],
            'last_message' => ['nullable', 'string'],
            'offer_value' => ['nullable', 'numeric', 'min:0'],
            'close_probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (empty($data['whatsapp']) && ! empty($data['phone'])) {
            $data['whatsapp'] = $data['phone'];
        }
        $data['status'] = $data['status'] ?? 'new';
        $data['importance'] = $data['importance'] ?? 'medium';

        $actorId = $request->user()?->id;
        $lead = $this->crm->createLead($data, $actorId);

        return ApiResponse::success(new CrmLeadResource($lead), 'تم إنشاء الـ Lead', 201);
    }

    public function showLead(int $id): JsonResponse
    {
        $lead = CrmLead::query()
            ->with(['assignee', 'events.author', 'leadNotes.author', 'attachments', 'followUps.assignee'])
            ->findOrFail($id);

        return ApiResponse::success(new CrmLeadResource($lead));
    }

    public function updateLead(Request $request, int $id): JsonResponse
    {
        $lead = CrmLead::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'atelier_name' => ['nullable', 'string', 'max:255'],
            'governorate' => ['nullable', 'string', 'max:100'],
            'activity' => ['nullable', 'string', 'max:100'],
            'branches_count' => ['nullable', 'integer', 'min:0'],
            'employees_count' => ['nullable', 'integer', 'min:0'],
            'source' => ['nullable', 'string', 'max:100'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'temperature' => ['nullable', Rule::in(['cold', 'warm', 'hot'])],
            'importance' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'expected_plan' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:40'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('super_admins', 'id')],
            'last_message' => ['nullable', 'string'],
            'offer_value' => ['nullable', 'numeric', 'min:0'],
            'close_probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'next_follow_up_at' => ['nullable', 'date'],
            'last_contact_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $lead = $this->crm->updateLead($lead, $data, $request->user()?->id);

        return ApiResponse::success(new CrmLeadResource($lead), 'تم التحديث');
    }

    public function destroyLead(int $id): JsonResponse
    {
        $lead = CrmLead::query()->findOrFail($id);
        $lead->delete();

        return ApiResponse::success(null, 'تم الحذف');
    }

    public function addNote(Request $request, int $id): JsonResponse
    {
        $lead = CrmLead::query()->findOrFail($id);
        $data = $request->validate(['body' => ['required', 'string']]);
        $note = $this->crm->addNote($lead, $data['body'], $request->user()?->id);

        return ApiResponse::success($note, 'تمت إضافة الملاحظة', 201);
    }

    public function addEvent(Request $request, int $id): JsonResponse
    {
        $lead = CrmLead::query()->findOrFail($id);
        $data = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ]);
        $event = $this->crm->addEvent($lead, $data['type'], $data['title'], $data['body'] ?? null, $request->user()?->id);
        $lead->last_contact_at = now();
        $lead->save();

        return ApiResponse::success($event, 'تمت إضافة النشاط', 201);
    }

    public function addAttachment(Request $request, int $id): JsonResponse
    {
        $lead = CrmLead::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:1000'],
            'mime' => ['nullable', 'string', 'max:100'],
        ]);
        $att = CrmLeadAttachment::query()->create([
            ...$data,
            'lead_id' => $lead->id,
            'created_by' => $request->user()?->id,
        ]);

        return ApiResponse::success($att, 'تم رفع المرفق', 201);
    }

    public function followUps(Request $request): JsonResponse
    {
        $q = CrmFollowUp::query()->with(['lead', 'assignee'])->orderBy('due_at');
        $filter = $request->query('filter', 'today');
        if ($filter === 'today') {
            $q->whereDate('due_at', today())->where('status', 'pending');
        } elseif ($filter === 'tomorrow') {
            $q->whereDate('due_at', today()->addDay())->where('status', 'pending');
        } elseif ($filter === 'week') {
            $q->whereBetween('due_at', [today(), today()->addDays(7)])->where('status', 'pending');
        } elseif ($filter === 'overdue') {
            $q->where('due_at', '<', now())->where('status', 'pending');
        } elseif ($filter === 'pending') {
            $q->where('status', 'pending');
        }

        $items = $q->limit(200)->get()->map(fn (CrmFollowUp $f) => [
            'id' => $f->id,
            'lead_id' => $f->lead_id,
            'lead_name' => $f->lead?->name,
            'atelier_name' => $f->lead?->atelier_name,
            'kind' => $f->kind ?? 'follow_up',
            'priority' => $f->priority,
            'due_at' => optional($f->due_at)?->toIso8601String(),
            'reason' => $f->reason,
            'assigned_to' => $f->assigned_to,
            'assigned_name' => $f->assignee?->name,
            'status' => $f->status,
            'phone' => $f->lead?->phone,
            'whatsapp' => $f->lead?->whatsapp,
            'lead_status' => $f->lead?->status,
        ]);

        return ApiResponse::success($items);
    }

    public function storeFollowUp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => ['required', 'integer', Rule::exists('crm_leads', 'id')],
            'kind' => ['nullable', Rule::in(['call', 'follow_up'])],
            'priority' => ['nullable', Rule::in(['urgent', 'high', 'normal', 'low'])],
            'due_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('super_admins', 'id')],
        ]);
        $data['kind'] = $data['kind'] ?? 'follow_up';
        $data['priority'] = $data['priority'] ?? 'normal';
        $data['status'] = 'pending';
        $data['assigned_to'] = $data['assigned_to'] ?? $request->user()?->id;
        $fu = CrmFollowUp::query()->create($data);

        return ApiResponse::success($fu->load(['lead', 'assignee']), 'تم إنشاء المتابعة', 201);
    }

    public function updateFollowUp(Request $request, int $id): JsonResponse
    {
        $fu = CrmFollowUp::query()->findOrFail($id);
        $data = $request->validate([
            'priority' => ['nullable', Rule::in(['urgent', 'high', 'normal', 'low'])],
            'due_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('super_admins', 'id')],
            'status' => ['nullable', Rule::in(['pending', 'done', 'cancelled'])],
        ]);
        if (($data['status'] ?? null) === 'done') {
            $data['completed_at'] = now();
        }
        $fu->update($data);

        return ApiResponse::success($fu->fresh(['lead', 'assignee']));
    }

    public function deals(): JsonResponse
    {
        $deals = CrmDeal::query()->with('assignee')->orderByDesc('id')->get()->map(fn (CrmDeal $d) => [
            'id' => $d->id,
            'lead_id' => $d->lead_id,
            'title' => $d->title,
            'lead_name' => $d->lead_name,
            'value' => (float) $d->value,
            'probability' => $d->probability,
            'temperature' => $d->temperature,
            'score' => $d->score,
            'stage' => $d->stage,
            'assigned_to' => $d->assigned_to,
            'assigned_name' => $d->assignee?->name,
            'next_follow_up_at' => optional($d->next_follow_up_at)?->toIso8601String(),
        ]);

        return ApiResponse::success($deals);
    }

    public function storeDeal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => ['required', 'integer', Rule::exists('crm_leads', 'id')],
            'stage' => ['nullable', Rule::in(['new', 'qualified', 'negotiation', 'won', 'lost'])],
        ]);

        $lead = CrmLead::query()->findOrFail($data['lead_id']);
        $deal = $this->crm->openDealFromLead($lead, $request->user()?->id, $data['stage'] ?? 'new');

        return ApiResponse::success([
            'id' => $deal->id,
            'lead_id' => $deal->lead_id,
            'title' => $deal->title,
            'lead_name' => $deal->lead_name,
            'value' => (float) $deal->value,
            'probability' => $deal->probability,
            'temperature' => $deal->temperature,
            'score' => $deal->score,
            'stage' => $deal->stage,
            'assigned_to' => $deal->assigned_to,
            'assigned_name' => $deal->assignee?->name,
            'next_follow_up_at' => optional($deal->next_follow_up_at)?->toIso8601String(),
        ], 'تم فتح الصفقة', 201);
    }

    public function scheduleLeadAppointment(Request $request, int $id): JsonResponse
    {
        $lead = CrmLead::query()->findOrFail($id);
        $data = $request->validate([
            'kind' => ['required', Rule::in(['call', 'follow_up'])],
            'due_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(['urgent', 'high', 'normal', 'low'])],
        ]);

        $fu = $this->crm->scheduleAppointment(
            $lead,
            $data['kind'],
            $data['due_at'],
            $data['reason'] ?? null,
            $data['priority'] ?? null,
            $request->user()?->id,
        );

        return ApiResponse::success($fu, 'تم إنشاء الموعد', 201);
    }

    public function openLeadDeal(Request $request, int $id): JsonResponse
    {
        $lead = CrmLead::query()->findOrFail($id);
        $deal = $this->crm->openDealFromLead($lead, $request->user()?->id);

        return ApiResponse::success([
            'id' => $deal->id,
            'lead_id' => $deal->lead_id,
            'title' => $deal->title,
            'stage' => $deal->stage,
        ], 'تم فتح الصفقة', 201);
    }

    public function updateDeal(Request $request, int $id): JsonResponse
    {
        $deal = CrmDeal::query()->findOrFail($id);
        $data = $request->validate([
            'stage' => ['nullable', Rule::in(['new', 'qualified', 'negotiation', 'won', 'lost'])],
            'value' => ['nullable', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('super_admins', 'id')],
        ]);
        $deal->update($data);

        if (isset($data['stage']) && $deal->lead_id) {
            $statusMap = [
                'new' => 'new',
                'qualified' => 'qualified',
                'negotiation' => 'negotiation',
                'won' => 'won',
                'lost' => 'lost',
            ];
            $lead = CrmLead::query()->find($deal->lead_id);
            if ($lead) {
                $lead->status = $statusMap[$data['stage']] ?? $lead->status;
                if ($data['stage'] === 'won') {
                    $lead->close_probability = 100;
                }
                $lead->save();
                $this->crm->addEvent($lead, 'deal_stage', 'نقل الصفقة إلى '.$data['stage'], null, $request->user()?->id);
            }
        }

        return ApiResponse::success($deal->fresh('assignee'));
    }

    public function quotations(): JsonResponse
    {
        $rows = CrmQuotation::query()->with('items')->orderByDesc('id')->get()->map(fn (CrmQuotation $q) => [
            'id' => $q->id,
            'lead_id' => $q->lead_id,
            'number' => $q->number,
            'lead_name' => $q->lead_name,
            'atelier_name' => $q->atelier_name,
            'plan_name' => $q->plan_name,
            'amount' => (float) $q->amount,
            'discount' => (float) $q->discount,
            'status' => $q->status,
            'valid_until' => optional($q->valid_until)?->toDateString(),
            'created_at' => optional($q->created_at)?->toIso8601String(),
            'items' => $q->items->map(fn ($i) => [
                'id' => $i->id,
                'label' => $i->label,
                'price' => (float) $i->price,
            ])->values(),
        ]);

        return ApiResponse::success($rows);
    }

    public function storeQuotation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => ['nullable', 'integer', Rule::exists('crm_leads', 'id')],
            'lead_name' => ['nullable', 'string', 'max:255'],
            'atelier_name' => ['nullable', 'string', 'max:255'],
            'plan_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:30'],
            'valid_until' => ['nullable', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.label' => ['required_with:items', 'string'],
            'items.*.price' => ['required_with:items', 'numeric'],
        ]);

        $number = 'QT-'.now()->format('Y').'-'.str_pad((string) (CrmQuotation::withTrashed()->count() + 1), 3, '0', STR_PAD_LEFT);
        $q = CrmQuotation::query()->create([
            'lead_id' => $data['lead_id'] ?? null,
            'number' => $number,
            'lead_name' => $data['lead_name'] ?? null,
            'atelier_name' => $data['atelier_name'] ?? null,
            'plan_name' => $data['plan_name'] ?? null,
            'amount' => $data['amount'] ?? 0,
            'discount' => $data['discount'] ?? 0,
            'status' => $data['status'] ?? 'draft',
            'valid_until' => $data['valid_until'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        foreach ($data['items'] ?? [] as $i => $item) {
            $q->items()->create([
                'label' => $item['label'],
                'price' => $item['price'],
                'sort_order' => $i + 1,
            ]);
        }

        if (! empty($data['lead_id'])) {
            $lead = CrmLead::query()->find($data['lead_id']);
            if ($lead) {
                $lead->status = 'quotation';
                $lead->save();
                $this->crm->syncDealFromLead($lead);
                $this->crm->addEvent($lead, 'quotation', 'تم إنشاء عرض سعر '.$number, null, $request->user()?->id);
            }
        }

        return ApiResponse::success($q->load('items'), 'تم إنشاء العرض', 201);
    }

    public function updateQuotation(Request $request, int $id): JsonResponse
    {
        $q = CrmQuotation::query()->findOrFail($id);
        $data = $request->validate([
            'plan_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['draft', 'sent', 'opened', 'accepted', 'rejected', 'expired'])],
            'valid_until' => ['nullable', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.label' => ['required_with:items', 'string'],
            'items.*.price' => ['required_with:items', 'numeric'],
        ]);

        $q->fill(collect($data)->except('items')->all());
        $q->save();

        if (array_key_exists('items', $data)) {
            $q->items()->delete();
            foreach ($data['items'] ?? [] as $i => $item) {
                $q->items()->create([
                    'label' => $item['label'],
                    'price' => $item['price'],
                    'sort_order' => $i + 1,
                ]);
            }
        }

        return ApiResponse::success($q->fresh('items'));
    }

    public function campaigns(): JsonResponse
    {
        $rows = CrmCampaign::query()->orderByDesc('id')->get()->map(fn (CrmCampaign $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'channel' => $c->channel,
            'budget' => (float) $c->budget,
            'spent' => (float) $c->spent,
            'messages' => $c->messages,
            'qualified_leads' => $c->qualified_leads,
            'sales' => $c->sales,
            'revenue' => (float) $c->revenue,
            'cpl' => $c->cpl,
            'cps' => $c->cps,
            'roi' => $c->roi,
            'is_active' => $c->is_active,
        ]);

        return ApiResponse::success($rows);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['nullable', 'string', 'max:50'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'spent' => ['nullable', 'numeric', 'min:0'],
            'messages' => ['nullable', 'integer', 'min:0'],
            'qualified_leads' => ['nullable', 'integer', 'min:0'],
            'sales' => ['nullable', 'integer', 'min:0'],
            'revenue' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $c = CrmCampaign::query()->create($data);

        return ApiResponse::success($c, 'تم إنشاء الحملة', 201);
    }

    public function updateCampaign(Request $request, int $id): JsonResponse
    {
        $c = CrmCampaign::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'channel' => ['nullable', 'string', 'max:50'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'spent' => ['nullable', 'numeric', 'min:0'],
            'messages' => ['nullable', 'integer', 'min:0'],
            'qualified_leads' => ['nullable', 'integer', 'min:0'],
            'sales' => ['nullable', 'integer', 'min:0'],
            'revenue' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $c->update($data);

        return ApiResponse::success($c->fresh());
    }

    public function destroyCampaign(int $id): JsonResponse
    {
        CrmCampaign::query()->findOrFail($id)->delete();

        return ApiResponse::success(null, 'تم الحذف');
    }

    public function destroyFollowUp(int $id): JsonResponse
    {
        CrmFollowUp::query()->findOrFail($id)->delete();

        return ApiResponse::success(null, 'تم حذف المتابعة');
    }

    public function destroyDeal(int $id): JsonResponse
    {
        CrmDeal::query()->findOrFail($id)->delete();

        return ApiResponse::success(null, 'تم حذف الصفقة');
    }

    public function destroyQuotation(int $id): JsonResponse
    {
        CrmQuotation::query()->findOrFail($id)->delete();

        return ApiResponse::success(null, 'تم حذف العرض');
    }

    public function team(): JsonResponse
    {
        return ApiResponse::success($this->crm->teamPerformance());
    }

    public function reports(): JsonResponse
    {
        $summary = $this->crm->dashboardSummary();

        return ApiResponse::success([
            'monthly_sales' => $summary['monthly_sales'],
            'top_sources' => $summary['top_sources'],
            'top_governorates' => $summary['top_governorates'],
            'funnel' => $summary['funnel'],
            'team' => $summary['team'],
            'kpis' => $summary['kpis'],
        ]);
    }

    public function settings(): JsonResponse
    {
        $this->crm->ensureDefaults();
        $lookups = CrmLookup::query()->orderBy('type')->orderBy('sort_order')->get()
            ->groupBy('type')
            ->map(fn ($g) => $g->values())
            ->all();

        return ApiResponse::success([
            'lookups' => $lookups,
            'score_weights' => CrmSetting::query()->where('key', 'score_weights')->value('value'),
            'temperature_rules' => CrmSetting::query()->where('key', 'temperature_rules')->value('value'),
            'importance_rules' => CrmSetting::query()->where('key', 'importance_rules')->value('value'),
            'admins' => SuperAdmin::query()->where('status', 'active')->get(['id', 'name', 'email']),
            'permissions' => [
                ['key' => 'view_crm', 'name' => 'عرض CRM والمبيعات'],
                ['key' => 'manage_crm', 'name' => 'إدارة كاملة لـ CRM'],
                ['key' => 'manage_crm_leads', 'name' => 'إدارة New Lead'],
                ['key' => 'manage_crm_follow_ups', 'name' => 'إدارة المتابعات'],
                ['key' => 'manage_crm_deals', 'name' => 'إدارة الصفقات'],
                ['key' => 'manage_crm_quotations', 'name' => 'إدارة عروض الأسعار'],
                ['key' => 'manage_crm_campaigns', 'name' => 'إدارة الحملات'],
                ['key' => 'view_crm_team', 'name' => 'عرض فريق المبيعات'],
                ['key' => 'view_crm_reports', 'name' => 'عرض تقارير CRM'],
                ['key' => 'manage_crm_settings', 'name' => 'إعدادات CRM'],
            ],
        ]);
    }

    public function storeLookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['source', 'status', 'activity', 'governorate', 'temperature', 'interest', 'importance'])],
            'label' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $row = CrmLookup::query()->create([
            ...$data,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return ApiResponse::success($row, 'تمت الإضافة', 201);
    }

    public function updateLookup(Request $request, int $id): JsonResponse
    {
        $row = CrmLookup::query()->findOrFail($id);
        $data = $request->validate([
            'label' => ['sometimes', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $row->update($data);

        return ApiResponse::success($row);
    }

    public function destroyLookup(int $id): JsonResponse
    {
        CrmLookup::query()->findOrFail($id)->delete();

        return ApiResponse::success(null, 'تم الحذف');
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'score_weights' => ['nullable', 'array'],
            'temperature_rules' => ['nullable', 'array'],
            'importance_rules' => ['nullable', 'array'],
        ]);
        if (isset($data['score_weights'])) {
            CrmSetting::query()->updateOrCreate(['key' => 'score_weights'], ['value' => $data['score_weights']]);
        }
        if (isset($data['temperature_rules'])) {
            CrmSetting::query()->updateOrCreate(['key' => 'temperature_rules'], ['value' => $data['temperature_rules']]);
        }
        if (isset($data['importance_rules'])) {
            CrmSetting::query()->updateOrCreate(['key' => 'importance_rules'], ['value' => $data['importance_rules']]);
        }

        return ApiResponse::success(null, 'تم حفظ الإعدادات');
    }

    public function bootstrap(): JsonResponse
    {
        $this->crm->ensureDefaults();
        $this->crm->seedSampleIfEmpty();

        return ApiResponse::success(['ok' => true], 'تم تهيئة بيانات CRM');
    }
}
