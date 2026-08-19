<?php

namespace App\Services\Platform;

use App\Models\Central\Payment;
use App\Models\Central\PlanRequest;
use App\Models\Central\Tenant;
use App\Models\Central\TrialOnboardingEvent;
use App\Models\Tenant\TrialOnboardingState;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantDatabaseManager;
use App\Services\Tenant\TrialOnboardingEvaluator;
use App\Support\TrialOnboarding\TrialOnboardingCatalog;
use App\Support\TrialOnboarding\TrialOnboardingEventName;
use App\Support\TrialOnboarding\TrialOnboardingStatus;
use App\Support\TrialOnboarding\TrialOnboardingStepKey;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

class TrialPerformanceQueryService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantDatabaseManager $tenantDatabaseManager,
        private readonly TrialOnboardingEvaluator $evaluator,
        private readonly TrialPerformanceScoring $scoring,
    ) {}

    /**
     * @param  array{category?:string, period?:string, page?:int, per_page?:int}  $filters
     * @return array<string, mixed>
     */
    public function build(Tenant $tenant, array $filters = []): array
    {
        $connected = $this->connectTenant($tenant);
        $domain = $connected ? $this->domainSnapshot() : $this->emptyDomain();
        $states = $connected ? TrialOnboardingState::query()->get() : collect();

        $eventNames = TrialOnboardingEvent::query()
            ->where('tenant_id', $tenant->id)
            ->pluck('event_name')
            ->all();
        $eventSet = array_fill_keys($eventNames, true);

        $eventsByName = TrialOnboardingEvent::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('occurred_at')
            ->get()
            ->groupBy('event_name');

        $completedSteps = $this->unionCompletedSteps($states, $domain['facts']);
        $onboardingStartedAt = $this->onboardingStartedAt($states, $eventsByName);
        $accountStartedAt = $this->accountStartedAt($tenant);
        $startedAt = $onboardingStartedAt ?? $accountStartedAt;
        $lastActivityAt = $this->lastActivityAt($states, $tenant->id);
        $onboardingPercent = $this->percent(count($completedSteps), count(TrialOnboardingStepKey::cases()));

        $journeyStarted = $onboardingStartedAt !== null
            || ($eventSet[TrialOnboardingEventName::Started->value] ?? false);
        $healthSignals = $this->healthSignals($eventSet, $domain, $journeyStarted);
        $activeToday = $lastActivityAt !== null && $lastActivityAt->isSameDay(CarbonImmutable::now());
        $health = $this->scoring->health(
            (array) config('trial_performance.health_weights', []),
            $healthSignals,
            (array) config('trial_performance.health_labels', []),
            $activeToday,
        );

        $activationFacts = $this->activationFacts($domain, $eventSet);
        $activation = $this->scoring->activation($activationFacts);
        $onboardingCompleted = count($completedSteps) === count(TrialOnboardingStepKey::cases());
        $activation['status'] = $this->scoring->fullyActivated($activation['status'], $onboardingCompleted);
        $activated = in_array($activation['status'], ['activated', 'fully_activated'], true);

        $financialsReached = $activationFacts['financial_view'];
        $coreActions = $this->coreActionCount($domain, $eventSet);
        $usedSystem = $coreActions > 0 || ($eventSet[TrialOnboardingEventName::Started->value] ?? false);
        $hoursSince = $lastActivityAt === null ? null : (int) max(0, $lastActivityAt->diffInHours(CarbonImmutable::now(), true));
        $engagement = $this->scoring->engagement(
            $activated,
            $usedSystem,
            $financialsReached || ($eventSet[TrialOnboardingEventName::PricingViewed->value] ?? false),
            $coreActions,
            $hoursSince,
            (int) config('trial_performance.inactive_after_hours', 48),
            (int) config('trial_performance.hot_recent_hours', 24),
        );

        $pricing = $this->pricingSignals($tenant, $eventSet);
        $upgradeIntent = $pricing['upgrade_clicked'] || $pricing['checkout_started'];
        $converted = $tenant->wasConvertedFromDemo();
        $expired = $this->isExpired($tenant);
        $trialStatus = $this->trialStatus($tenant, $converted, $expired);
        $reservations = (int) $domain['counts']['reservations'];

        $priority = $this->scoring->salesPriority(
            $engagement,
            $activated,
            $activeToday,
            $reservations,
            $financialsReached,
            $upgradeIntent || $converted,
        );

        $lifecycle = $this->scoring->lifecycle(
            true,
            $startedAt !== null,
            $activated,
            in_array($engagement, ['hot', 'warm'], true),
            $upgradeIntent,
            $converted,
            $expired && ! $converted,
        );

        $activityPage = $this->activityPage($tenant, $filters);
        $lastEvent = TrialOnboardingEvent::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->first();

        $durationDays = $this->durationDays($startedAt, $converted, $expired, $tenant);

        return [
            'trial' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $trialStatus,
                'tenant_status' => $tenant->status,
                'started_at' => $startedAt?->toIso8601String(),
                'last_active_at' => $lastActivityAt?->toIso8601String(),
                'duration_days' => $durationDays,
                'created_at' => $tenant->created_at?->toIso8601String(),
                'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
                'converted' => $converted,
                'expired' => $expired && ! $converted,
            ],
            'overview' => [
                'health' => $health['score'],
                'activation' => $activation['status'],
                'onboarding_percent' => $onboardingPercent,
                'last_active_at' => $lastActivityAt?->toIso8601String(),
            ],
            'health' => $health,
            'activation' => $activation,
            'engagement' => [
                'level' => $engagement,
            ],
            'onboarding' => [
                'completed' => count($completedSteps),
                'total' => count(TrialOnboardingStepKey::cases()),
                'percent' => $onboardingPercent,
                'status' => $this->onboardingStatus($states, $completedSteps),
                'steps' => $this->onboardingSteps($completedSteps, $eventsByName),
            ],
            'business_metrics' => $domain['counts'],
            'activity' => [
                'last' => $lastEvent ? $this->presentEvent($lastEvent) : null,
                'items' => array_map(fn (TrialOnboardingEvent $event): array => $this->presentEvent($event), $activityPage->items()),
                'meta' => [
                    'current_page' => $activityPage->currentPage(),
                    'per_page' => $activityPage->perPage(),
                    'total' => $activityPage->total(),
                    'last_page' => $activityPage->lastPage(),
                ],
            ],
            'purchase_signals' => $this->purchaseSignals(
                $durationDays,
                $reservations,
                $eventSet,
                $activeToday,
                $upgradeIntent,
                $converted,
                $pricing,
            ),
            'pricing' => $pricing,
            'sales_priority' => [
                'level' => $priority['level'],
                'reasons' => $this->priorityReasonLabels($priority['reasons'], $reservations),
            ],
            'lifecycle' => [
                'stages' => $lifecycle,
                'current' => $lifecycle === [] ? 'created' : $lifecycle[array_key_last($lifecycle)],
            ],
            'recommendation' => $this->recommendation($priority['level'], $converted, $expired),
        ];
    }

    private function connectTenant(Tenant $tenant): bool
    {
        try {
            $this->tenantContext->setTenant($tenant);
            $this->tenantDatabaseManager->connect($tenant);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{counts: array<string, int>, facts: array<string, bool>}
     */
    private function domainSnapshot(): array
    {
        $viewed = [];
        foreach (TrialOnboardingState::query()->get() as $state) {
            $viewed = array_values(array_unique(array_merge($viewed, $state->viewedStepKeys())));
        }

        return [
            'counts' => $this->evaluator->summaryCounts(),
            'facts' => $this->evaluator->evaluate($viewed),
        ];
    }

    /**
     * @return array{counts: array<string, int>, facts: array<string, bool>}
     */
    private function emptyDomain(): array
    {
        $facts = [];
        foreach (TrialOnboardingStepKey::cases() as $step) {
            $facts[$step->value] = false;
        }

        return [
            'counts' => [
                'branches' => 0,
                'cashboxes' => 0,
                'suppliers' => 0,
                'purchase_orders' => 0,
                'received_orders' => 0,
                'inventory_items' => 0,
                'products' => 0,
                'customers' => 0,
                'reservations' => 0,
                'financial_activities' => 0,
            ],
            'facts' => $facts,
        ];
    }

    /**
     * @param  Collection<int, TrialOnboardingState>  $states
     * @param  array<string, bool>  $facts
     * @return list<string>
     */
    private function unionCompletedSteps(Collection $states, array $facts): array
    {
        $completed = [];
        foreach (TrialOnboardingStepKey::ordered() as $step) {
            if ($facts[$step->value] ?? false) {
                $completed[] = $step->value;
            }
        }
        if ($completed !== []) {
            return $completed;
        }

        $union = [];
        foreach ($states as $state) {
            $steps = is_array($state->completed_steps) ? $state->completed_steps : [];
            foreach ($steps as $key) {
                $union[(string) $key] = true;
            }
        }

        return array_values(array_keys($union));
    }

    /**
     * @param  Collection<int, TrialOnboardingState>  $states
     * @param  Collection<string, Collection<int, TrialOnboardingEvent>>  $eventsByName
     */
    private function onboardingStartedAt(Collection $states, Collection $eventsByName): ?CarbonImmutable
    {
        $candidates = [];
        foreach ($states as $state) {
            if ($state->started_at) {
                $candidates[] = CarbonImmutable::parse($state->started_at);
            }
        }
        $startedEvent = $eventsByName->get(TrialOnboardingEventName::Started->value)?->first();
        if ($startedEvent?->occurred_at) {
            $candidates[] = CarbonImmutable::parse($startedEvent->occurred_at);
        }

        return $candidates === [] ? null : collect($candidates)->sort()->first();
    }

    private function accountStartedAt(Tenant $tenant): CarbonImmutable
    {
        if ($tenant->subscription_starts_at) {
            return CarbonImmutable::parse($tenant->subscription_starts_at);
        }
        if ($tenant->created_at) {
            return CarbonImmutable::parse($tenant->created_at);
        }

        return CarbonImmutable::now();
    }

    /**
     * @param  Collection<int, TrialOnboardingState>  $states
     */
    private function lastActivityAt(Collection $states, int $tenantId): ?CarbonImmutable
    {
        $candidates = [];
        foreach ($states as $state) {
            if ($state->last_activity_at) {
                $candidates[] = CarbonImmutable::parse($state->last_activity_at);
            }
        }
        $lastEvent = TrialOnboardingEvent::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('occurred_at')
            ->value('occurred_at');
        if ($lastEvent) {
            $candidates[] = CarbonImmutable::parse($lastEvent);
        }

        return $candidates === [] ? null : collect($candidates)->sortDesc()->first();
    }

    /**
     * @param  array<string, bool>  $eventSet
     * @param  array{counts: array<string, int>, facts: array<string, bool>}  $domain
     * @return array<string, bool>
     */
    private function healthSignals(array $eventSet, array $domain, bool $started): array
    {
        $facts = $domain['facts'];
        $counts = $domain['counts'];

        return [
            TrialOnboardingEventName::Started->value => $started
                || (bool) ($eventSet[TrialOnboardingEventName::Started->value] ?? false),
            TrialOnboardingEventName::BranchCreated->value => (bool) ($facts[TrialOnboardingStepKey::BranchSetup->value] ?? false),
            TrialOnboardingEventName::CashboxCreated->value => (bool) ($facts[TrialOnboardingStepKey::CashboxSetup->value] ?? false),
            TrialOnboardingEventName::SupplierCreated->value => (bool) ($facts[TrialOnboardingStepKey::SupplierSetup->value] ?? false),
            'product_created' => ((int) ($counts['products'] ?? 0)) > 0
                || ((int) ($counts['inventory_items'] ?? 0)) > 0,
            TrialOnboardingEventName::PurchaseOrderCreated->value => (bool) ($facts[TrialOnboardingStepKey::PurchaseOrderCreated->value] ?? false),
            TrialOnboardingEventName::PurchaseOrderReceived->value => (bool) ($facts[TrialOnboardingStepKey::PurchaseOrderReceived->value] ?? false),
            TrialOnboardingEventName::InventoryVerified->value => (bool) ($facts[TrialOnboardingStepKey::InventoryVerified->value] ?? false),
            TrialOnboardingEventName::CustomerCreated->value => (bool) ($facts[TrialOnboardingStepKey::CustomerSetup->value] ?? false),
            TrialOnboardingEventName::ReservationCreated->value => (bool) ($facts[TrialOnboardingStepKey::ReservationCreated->value] ?? false),
            TrialOnboardingEventName::BalancesViewed->value => (bool) ($facts[TrialOnboardingStepKey::BalancesReview->value] ?? false),
            TrialOnboardingEventName::StatementViewed->value => (bool) ($facts[TrialOnboardingStepKey::AccountStatement->value] ?? false),
        ];
    }

    /**
     * @param  array{counts: array<string, int>, facts: array<string, bool>}  $domain
     * @param  array<string, bool>  $eventSet
     * @return array<string, bool>
     */
    private function activationFacts(array $domain, array $eventSet): array
    {
        $facts = $domain['facts'];
        $counts = $domain['counts'];
        $financial = ($facts[TrialOnboardingStepKey::BalancesReview->value] ?? false)
            || ($facts[TrialOnboardingStepKey::AccountStatement->value] ?? false)
            || ($eventSet[TrialOnboardingEventName::BalancesViewed->value] ?? false)
            || ($eventSet[TrialOnboardingEventName::StatementViewed->value] ?? false);

        return [
            'branch' => (bool) ($facts[TrialOnboardingStepKey::BranchSetup->value] ?? false),
            'cashbox' => (bool) ($facts[TrialOnboardingStepKey::CashboxSetup->value] ?? false),
            'product' => ((int) ($counts['products'] ?? 0)) > 0
                || ((int) ($counts['inventory_items'] ?? 0)) > 0,
            'purchase_received' => (bool) ($facts[TrialOnboardingStepKey::PurchaseOrderReceived->value] ?? false),
            'customer' => (bool) ($facts[TrialOnboardingStepKey::CustomerSetup->value] ?? false),
            'reservation' => (bool) ($facts[TrialOnboardingStepKey::ReservationCreated->value] ?? false),
            'financial_view' => $financial,
        ];
    }

    /**
     * @param  array{counts: array<string, int>, facts: array<string, bool>}  $domain
     * @param  array<string, bool>  $eventSet
     */
    private function coreActionCount(array $domain, array $eventSet): int
    {
        $facts = $domain['facts'];
        $flags = [
            $facts[TrialOnboardingStepKey::BranchSetup->value] ?? false,
            $facts[TrialOnboardingStepKey::CashboxSetup->value] ?? false,
            $facts[TrialOnboardingStepKey::SupplierSetup->value] ?? false,
            ((int) ($domain['counts']['products'] ?? 0)) > 0
                || ((int) ($domain['counts']['inventory_items'] ?? 0)) > 0,
            $facts[TrialOnboardingStepKey::PurchaseOrderCreated->value] ?? false,
            $facts[TrialOnboardingStepKey::PurchaseOrderReceived->value] ?? false,
            $facts[TrialOnboardingStepKey::InventoryVerified->value] ?? false,
            $facts[TrialOnboardingStepKey::CustomerSetup->value] ?? false,
            $facts[TrialOnboardingStepKey::ReservationCreated->value] ?? false,
            $eventSet[TrialOnboardingEventName::PricingViewed->value] ?? false,
        ];

        return count(array_filter($flags));
    }

    /**
     * @param  list<string>  $completedSteps
     * @param  Collection<string, Collection<int, TrialOnboardingEvent>>  $eventsByName
     * @return list<array<string, mixed>>
     */
    private function onboardingSteps(array $completedSteps, Collection $eventsByName): array
    {
        $done = array_flip($completedSteps);
        $steps = [];
        foreach (TrialOnboardingCatalog::contracts() as $contract) {
            $key = (string) $contract['key'];
            $eventName = (string) $contract['event'];
            $event = $eventsByName->get($eventName)?->first();
            $isDone = isset($done[$key]);
            $steps[] = [
                'key' => $key,
                'title' => $contract['title'],
                'order' => $contract['order'],
                'status' => $isDone ? 'completed' : 'pending',
                'completed_at' => $isDone ? $event?->occurred_at?->toIso8601String() : null,
                'last_activity_at' => $event?->occurred_at?->toIso8601String(),
                'last_activity_label' => $isDone
                    ? ($event ? $this->eventLabel($eventName) : 'مكتملة من بيانات التشغيل')
                    : 'لم تتم بعد',
            ];
        }

        return $steps;
    }

    /**
     * @param  Collection<int, TrialOnboardingState>  $states
     * @param  list<string>  $completedSteps
     */
    private function onboardingStatus(Collection $states, array $completedSteps): string
    {
        if (count($completedSteps) === count(TrialOnboardingStepKey::cases())) {
            return TrialOnboardingStatus::Completed->value;
        }
        if ($completedSteps !== [] || $states->contains(fn (TrialOnboardingState $s): bool => $s->started_at !== null)) {
            return TrialOnboardingStatus::InProgress->value;
        }

        return TrialOnboardingStatus::NotStarted->value;
    }

    /**
     * @param  array{category?:string, period?:string, page?:int, per_page?:int}  $filters
     */
    private function activityPage(Tenant $tenant, array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? config('trial_performance.activity_per_page', 30))));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $category = trim((string) ($filters['category'] ?? 'all'));
        $period = trim((string) ($filters['period'] ?? 'all'));

        $query = TrialOnboardingEvent::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if ($category !== '' && $category !== 'all') {
            $mapped = $category === 'accounts' ? 'finance' : $category;
            $names = [];
            foreach ((array) config('trial_performance.activity_categories', []) as $eventName => $eventCategory) {
                if ($eventCategory === $mapped) {
                    $names[] = $eventName;
                }
            }
            $query->whereIn('event_name', $names === [] ? ['__none__'] : $names);
        }

        $since = match ($period) {
            'today' => CarbonImmutable::now()->startOfDay(),
            'last_3_days' => CarbonImmutable::now()->subDays(3)->startOfDay(),
            'last_7_days' => CarbonImmutable::now()->subDays(7)->startOfDay(),
            default => null,
        };
        if ($since !== null) {
            $query->where('occurred_at', '>=', $since);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentEvent(TrialOnboardingEvent $event): array
    {
        $name = (string) $event->event_name;
        $icons = (array) config('trial_performance.activity_icons', []);
        $categories = (array) config('trial_performance.activity_categories', []);

        return [
            'id' => $event->id,
            'occurred_at' => $event->occurred_at?->toIso8601String(),
            'key' => $name,
            'label' => $this->eventLabel($name),
            'category' => $categories[$name] ?? 'system',
            'icon' => $icons[$name] ?? 'ri-flashlight-line',
            'step_key' => $event->step_key,
        ];
    }

    private function eventLabel(string $eventName): string
    {
        $labels = (array) config('trial_performance.activity_labels', []);

        return (string) ($labels[$eventName] ?? 'نشاط في التجربة');
    }

    /**
     * @param  array<string, bool>  $eventSet
     * @return array{pricing_viewed:bool,upgrade_clicked:bool,checkout_started:bool,payment:bool}
     */
    private function pricingSignals(Tenant $tenant, array $eventSet): array
    {
        $checkoutFromDomain = PlanRequest::query()
            ->where(function ($query) use ($tenant): void {
                $query->where('tenant_id', $tenant->id)
                    ->orWhere('source_tenant_id', $tenant->id);
            })
            ->exists();
        $paid = $tenant->wasConvertedFromDemo()
            || Payment::query()
                ->where('tenant_id', $tenant->id)
                ->where(function ($query): void {
                    $query->where('purpose', 'demo_conversion')
                        ->orWhere('status', 'paid');
                })
                ->exists();

        return [
            'pricing_viewed' => (bool) ($eventSet[TrialOnboardingEventName::PricingViewed->value] ?? false),
            'upgrade_clicked' => (bool) ($eventSet[TrialOnboardingEventName::UpgradeClicked->value] ?? false),
            'checkout_started' => (bool) ($eventSet[TrialOnboardingEventName::CheckoutStarted->value] ?? false) || $checkoutFromDomain,
            'payment' => $paid,
        ];
    }

    /**
     * @param  array<string, bool>  $eventSet
     * @param  array{pricing_viewed:bool,upgrade_clicked:bool,checkout_started:bool,payment:bool}  $pricing
     * @return list<array<string, mixed>>
     */
    private function purchaseSignals(
        int $durationDays,
        int $reservations,
        array $eventSet,
        bool $activeToday,
        bool $upgradeIntent,
        bool $converted,
        array $pricing,
    ): array {
        return [
            [
                'key' => 'used_3_days',
                'label' => 'استخدم النظام لمدة 3 أيام',
                'done' => $durationDays >= 3,
            ],
            [
                'key' => 'reservations',
                'label' => $reservations > 0 ? "أنشأ {$reservations} حجزًا" : 'لم ينشئ حجوزات بعد',
                'done' => $reservations > 0,
                'value' => $reservations,
            ],
            [
                'key' => 'balances_viewed',
                'label' => 'شاهد الأرصدة',
                'done' => (bool) ($eventSet[TrialOnboardingEventName::BalancesViewed->value] ?? false),
            ],
            [
                'key' => 'statement_viewed',
                'label' => 'شاهد كشف الحساب',
                'done' => (bool) ($eventSet[TrialOnboardingEventName::StatementViewed->value] ?? false),
            ],
            [
                'key' => 'returned_today',
                'label' => 'عاد اليوم',
                'done' => $activeToday,
            ],
            [
                'key' => 'pricing_viewed',
                'label' => 'اطلع على الباقات',
                'done' => $pricing['pricing_viewed'],
            ],
            [
                'key' => 'upgrade_intent',
                'label' => $converted ? 'تم التحويل إلى اشتراك' : ($upgradeIntent ? 'بدأ مسار الترقية' : 'لم يبدأ الترقية بعد'),
                'done' => $upgradeIntent || $converted,
            ],
        ];
    }

    /**
     * @param  list<string>  $reasons
     * @return list<array{key:string,label:string}>
     */
    private function priorityReasonLabels(array $reasons, int $reservations): array
    {
        $map = [
            'activated' => 'الحساب مفعّل',
            'active_today' => 'نشط اليوم',
            'reservations' => $reservations > 0 ? "أنشأ {$reservations} حجزًا" : 'لا توجد حجوزات',
            'financials_viewed' => 'اطّلع على الحسابات المالية',
            'upgrade_intent' => 'أبدى رغبة في الترقية',
        ];
        $out = [];
        foreach ($reasons as $key) {
            $out[] = ['key' => $key, 'label' => $map[$key] ?? $key];
        }

        return $out;
    }

    /**
     * @return array{key:string,text:string}
     */
    private function recommendation(string $priority, bool $converted, bool $expired): array
    {
        if ($converted) {
            return [
                'key' => 'converted',
                'text' => 'تم التحويل إلى اشتراك — احتفظ بسجل الأداء للمراجعة.',
            ];
        }
        if ($expired) {
            return [
                'key' => 'expired',
                'text' => 'انتهت التجربة — راجع الأداء قبل التواصل لإعادة التفعيل.',
            ];
        }

        return match ($priority) {
            'high' => [
                'key' => 'high_intent',
                'text' => 'تجربة قوية — يُفضَّل التواصل معها للمتابعة البيعية.',
            ],
            'medium' => [
                'key' => 'nurture',
                'text' => 'تجربة متوسطة التفاعل — متابعة مبيعات اختيارية.',
            ],
            default => [
                'key' => 'low_intent',
                'text' => 'تفاعل منخفض — لا يحتاج تدخل مبيعات الآن.',
            ],
        };
    }

    private function trialStatus(Tenant $tenant, bool $converted, bool $expired): string
    {
        if ($converted) {
            return 'converted';
        }
        if ($expired) {
            return 'expired';
        }
        if ($tenant->status === 'suspended') {
            return 'suspended';
        }

        return 'active';
    }

    private function isExpired(Tenant $tenant): bool
    {
        if ($tenant->status === 'expired') {
            return true;
        }
        if ($tenant->subscription_ends_at && CarbonImmutable::parse($tenant->subscription_ends_at)->lt(CarbonImmutable::now())) {
            return true;
        }

        return false;
    }

    private function durationDays(?CarbonImmutable $startedAt, bool $converted, bool $expired, Tenant $tenant): int
    {
        $start = $startedAt ?? $this->accountStartedAt($tenant);
        $end = CarbonImmutable::now();
        if ($converted) {
            $convertedAt = is_array($tenant->metadata) ? ($tenant->metadata['converted_at'] ?? null) : null;
            if (is_string($convertedAt) && $convertedAt !== '') {
                $end = CarbonImmutable::parse($convertedAt);
            }
        } elseif ($expired && $tenant->subscription_ends_at) {
            $end = CarbonImmutable::parse($tenant->subscription_ends_at);
        }

        return max(0, (int) $start->diffInDays($end, true));
    }

    private function percent(int $completed, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($completed / $total) * 100);
    }
}
