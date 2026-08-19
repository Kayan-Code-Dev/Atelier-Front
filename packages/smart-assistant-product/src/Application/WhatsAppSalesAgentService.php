<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Central\Tenant;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Dress;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\WebsiteBookingRequest;
use App\Services\Intelligence\Providers\OpenAIIntelligenceProvider;
use App\Services\Tenant\InvoiceService;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantDatabaseManager;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantAgentSettings;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantWhatsAppConversation;
use DressnMore\SmartAssistantProduct\SalesIntelligence\Orchestrator\Application\TenantIdentityResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Hybrid WhatsApp sales agent: GPT-4o-mini for understanding/replying,
 * deterministic templates for the safety-critical transitions
 * (invoice confirmation / cancellation). Never finalizes a sale or
 * invoice without an explicit customer confirmation keyword.
 */
final class WhatsAppSalesAgentService
{
    /**
     * Preview/simulation mode (tenant settings "Test your Assistant"):
     * mutating tools return a simulated result and never touch real data,
     * and no quota is consumed. Read-only tools still hit REAL tenant data.
     */
    private bool $dryRun = false;

    /** @var array{agent_name?:?string, department_name?:?string} */
    private array $persona = [];

    private const MUTATING_TOOLS = [
        'register_customer', 'update_customer', 'create_invoice', 'create_booking',
        'create_fitting', 'cancel_reservation', 'create_support_case', 'create_followup', 'handoff_to_human',
    ];

    private const CONFIRM_WORDS = ['تأكيد', 'تاكيد', 'أكد', 'اكد', 'نعم', 'موافق', 'اوكي', 'أوكي', 'ok', 'yes'];
    private const CANCEL_WORDS = ['إلغاء', 'الغاء', 'لا', 'cancel'];

    public function __construct(
        private readonly TenantDatabaseManager $tenantDatabaseManager,
        private readonly TenantContext $tenantContext,
        private readonly InvoiceService $invoiceService,
        private readonly AiQuotaService $quotaService,
        private readonly TenantAtelierKnowledge $atelierKnowledge,
    ) {}

    /**
     * Build the reply for one inbound WhatsApp text in sales mode.
     * Returns '' when no reply should be sent.
     *
     * @param array{agent_name?:?string, department_name?:?string} $persona
     */
    public function buildReply(Tenant $tenant, string $from, string $text, ?string $fromPhone = null, array $persona = []): string
    {
        $this->persona = $persona;
        try {
            $this->tenantDatabaseManager->connect($tenant);
            $this->tenantContext->setTenant($tenant);
        } catch (Throwable $e) {
            Log::warning('whatsapp.sales_agent.tenant_connect_failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return '';
        }

        $settings = SmartAssistantAgentSettings::forTenant((int) $tenant->id);
        if (! $settings->auto_reply_enabled || ! $settings->isActive()) {
            return '';
        }

        // Business-hours gate: outside the configured window the behavior
        // decided by after_hours_behavior applies ('reply' keeps the AI on).
        if (! $settings->isWithinBusinessHours()) {
            $behavior = (string) ($settings->after_hours_behavior ?: 'reply');
            if ($behavior === 'off') {
                return '';
            }
            if ($behavior === 'away_message') {
                $convo = SmartAssistantWhatsAppConversation::query()->firstOrNew([
                    'tenant_id' => $tenant->id,
                    'phone' => $from,
                ]);
                $reply = trim((string) ($settings->away_message ?? ''));
                if ($reply === '') {
                    $reply = (string) config(
                        'smart-assistant-product.whatsapp.after_hours_template',
                        "شكرًا لتواصلك 🌙\nنحن حاليًا خارج مواعيد العمل، وسنرد عليك فور بداية الدوام بإذن الله."
                    );
                }
                // Deterministic away reply: NOT AI-processed → no quota consumed.
                $this->persist($convo, $text, $reply);

                return $reply;
            }
        }

        $convo = SmartAssistantWhatsAppConversation::query()->firstOrNew([
            'tenant_id' => $tenant->id,
            'phone' => $from,
        ]);
        $isFirstContact = ! $convo->exists;

        // Human handoff in progress: AI stays silent until explicitly resumed.
        if ($convo->exists && ($convo->handler ?? 'ai') === 'human') {
            $convo->pushHistory('user', $text);
            $convo->save();

            return '';
        }

        // Structured conversation memory: extract durable facts from this message.
        $this->extractFacts($convo, $text);
        $this->trackConversationState($convo, $text);

        $customer = $this->findCustomer($from, $fromPhone);
        if ($customer !== null) {
            $convo->customer_id = (int) $customer->id;
        }

        // FAQ exact/keyword match (tenant-configured answers beat the AI).
        $faqReply = $this->matchFaq($settings, $text);
        if ($faqReply !== null) {
            $this->persist($convo, $text, $faqReply);

            return $faqReply;
        }

        // 1) Deterministic safety gate: pending confirmation handled by templates only.
        $pending = is_array($convo->pending_action) ? $convo->pending_action : null;
        if ($pending !== null) {
            $normalized = $this->normalize($text);
            if ($this->matchesAny($normalized, self::CANCEL_WORDS)) {
                $reply = $this->cancelPending($tenant, $convo, $pending);
                $this->persist($convo, $text, $reply);

                return $reply;
            }
            if ($this->matchesAny($normalized, self::CONFIRM_WORDS)) {
                $reply = $this->confirmPending($tenant, $convo, $pending);
                $this->persist($convo, $text, $reply);

                return $reply;
            }
            // Anything else while pending: remind via template (no AI side effects).
            $reply = "لديك طلب بانتظار التأكيد 🙏\nأرسل «تأكيد» لإتمامه أو «إلغاء» للتراجع.";
            $this->persist($convo, $text, $reply);

            return $reply;
        }

        // 2) Quota gate: exhausted plan => pause AI, never consume, never fake.
        if (! $this->quotaService->canConsume($tenant)) {
            Log::info('whatsapp.sales_agent.quota_exhausted', ['tenant_id' => $tenant->id]);

            return '';
        }

        // 3) Hybrid brain: GPT-4o-mini with business tools.
        $reply = $this->aiReply($tenant, $convo, $customer, $from, $text, $settings, $fromPhone);
        if ($reply === '' && $isFirstContact && filled($settings->welcome_message)) {
            $reply = (string) $settings->welcome_message;
        }
        if ($reply === '') {
            $reply = (string) config(
                'smart-assistant-product.whatsapp.auto_reply_template',
                "مرحباً 👋\nتم استلام رسالتك وسنعاود الرد عليك في أقرب وقت."
            );
        }

        $this->persist($convo, $text, $reply);

        return mb_substr($reply, 0, 3500);
    }

    // --------------------------------------------------- preview (simulation)

    /**
     * Simulation chat for the tenant settings screen. Runs the SAME pipeline
     * (memory, facts, tools, prompt) but: never consumes quota, never binds
     * to a real customer, and mutating tools return simulated results.
     */
    public function previewReply(Tenant $tenant, string $text): string
    {
        try {
            $this->tenantDatabaseManager->connect($tenant);
            $this->tenantContext->setTenant($tenant);
        } catch (Throwable $e) {
            Log::warning('whatsapp.sales_agent.preview_connect_failed', ['error' => $e->getMessage()]);

            return '';
        }

        $settings = SmartAssistantAgentSettings::forTenant((int) $tenant->id);

        $convo = SmartAssistantWhatsAppConversation::query()->firstOrNew([
            'tenant_id' => $tenant->id,
            'phone' => 'preview:'.$tenant->id,
        ]);
        $convo->handler = 'ai';

        $this->extractFacts($convo, $text);
        $this->trackConversationState($convo, $text);

        $this->dryRun = true;
        try {
            $reply = $this->aiReply($tenant, $convo, null, 'preview:'.$tenant->id, $text, $settings);
        } finally {
            $this->dryRun = false;
        }
        if ($reply === '') {
            $reply = '(معاينة) تعذّر توليد رد الآن — تحقق من تفعيل مزود الذكاء الاصطناعي.';
        }

        $this->persist($convo, $text, $reply);

        return mb_substr($reply, 0, 3500);
    }

    /**
     * Reset the simulation conversation ("Reset Test" button).
     */
    public function resetPreview(Tenant $tenant): void
    {
        SmartAssistantWhatsAppConversation::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('phone', 'preview:'.$tenant->id)
            ->delete();
    }

    // ------------------------------------------- structured conversation state

    /**
     * Deterministic structured conversation state (never raw chatter):
     * intent, journey stage, purchase intent, sentiment, missing info and
     * next best action — recomputed from the current message + known facts.
     */
    private function trackConversationState(SmartAssistantWhatsAppConversation $convo, string $text): void
    {
        $normalized = $this->normalize($text);
        $state = $convo->stateData();
        $facts = (array) ($state['known_facts'] ?? []);

        // Intent (atelier domain, keyword-driven; sticky when unclear).
        $intent = null;
        if ($this->matchesAny($normalized, ['شكوى', 'أشتكي', 'اشتكي', 'زعلان', 'غاضب', 'أسوأ', 'سيء جدا', 'خربتوا'])) {
            $intent = 'complaint';
        } elseif ($this->matchesAny($normalized, ['موظف', 'بني آدم', 'حد حقيقي', 'انسان', 'إنسان', 'شخص حقيقي', 'اكلم حد', 'أكلم حد', 'human'])) {
            $intent = 'human_request';
        } elseif ($this->matchesAny($normalized, ['حجز', 'أحجز', 'احجز', 'عايزة أحجز', 'عايز أحجز', 'موعد', 'booking', 'book'])) {
            $intent = 'booking';
        } elseif ($this->matchesAny($normalized, ['طلبي', 'فاتورتي', 'حالة الطلب', 'حالة الفاتورة', 'order status', 'طلبي وصل'])) {
            $intent = 'order_status';
        } elseif ($this->matchesAny($normalized, ['بكام', 'بكم', 'سعر', 'أسعار', 'اسعار', 'تكلفة', 'price', 'how much', 'كم سعر'])) {
            $intent = 'price_inquiry';
        } elseif ($this->matchesAny($normalized, ['عندكم', 'عندك', 'متوفر', 'متاح', 'فستان', 'فساتين', 'available', 'do you have'])) {
            $intent = 'product_inquiry';
        }
        if ($intent !== null) {
            $state['intent'] = $intent;
        }

        // Sentiment.
        if ($this->matchesAny($normalized, ['شكوى', 'زعلان', 'غاضب', 'سيء', 'أسوأ', 'خربتوا', 'مش عاجبني'])) {
            $state['sentiment'] = 'negative';
        } elseif ($this->matchesAny($normalized, ['شكرا', 'شكرًا', 'تمام', 'ممتاز', 'جميل', 'رائع', 'تسلم'])) {
            $state['sentiment'] = 'positive';
        }

        // Purchase intent.
        $purchase = (string) ($state['purchase_intent'] ?? 'cold');
        if ($this->matchesAny($normalized, ['أحجز', 'احجز', 'تأكيد', 'أكد', 'اكد', 'عايزة أحجز', 'هاحجز', 'هاخده', 'هاخده'])) {
            $purchase = 'hot';
        } elseif ($purchase === 'cold' && $this->matchesAny($normalized, ['بكام', 'بكم', 'سعر', 'price', 'how much', 'عندكم'])) {
            $purchase = 'warm';
        }
        $state['purchase_intent'] = $purchase;

        // Journey stage from facts + pending action.
        $hasPending = is_array($convo->pending_action) && $convo->pending_action !== [];
        $journey = 'discovery';
        if (isset($facts['occasion']) || isset($facts['preferred_product'])) {
            $journey = 'consideration';
        }
        if (isset($facts['date']) || $intent === 'booking') {
            $journey = 'decision';
        }
        if ($hasPending || (($state['last_action'] ?? '') !== '')) {
            $journey = 'action';
        }
        $state['journey_stage'] = $journey;

        // Customer goal (best-effort from facts).
        if (isset($facts['preferred_product']) || isset($facts['occasion'])) {
            $state['customer_goal'] = trim(($facts['preferred_product'] ?? 'منتج').' / '.($facts['occasion'] ?? 'مناسبة'), ' /');
        }

        // Missing information for completing a booking.
        $missing = [];
        if (($convo->customer_id ?? null) === null && ! isset($facts['name'])) {
            $missing[] = 'name';
        }
        if (! isset($facts['date'])) {
            $missing[] = 'date';
        }
        if (! isset($facts['preferred_product'])) {
            $missing[] = 'product';
        }
        $state['missing_information'] = $missing;

        // Last question asked by the customer.
        if (str_contains($text, '؟') || str_contains($text, '?') || preg_match('/^(هل|بكام|بكم|كم|متى|أين|اين|إيه|ايه|مين|ليه|what|when|where|how|do you)/u', trim($normalized))) {
            $state['last_question'] = mb_substr($text, 0, 300);
        }

        // Next best action.
        $state['next_best_action'] = match ($state['intent'] ?? null) {
            'booking' => isset($facts['date']) ? 'create_booking' : 'ask_date',
            'price_inquiry' => 'get_price',
            'product_inquiry' => 'search_dresses',
            'order_status' => 'get_order_status',
            'complaint' => 'create_support_case',
            'human_request' => 'handoff_to_human',
            default => 'continue_conversation',
        };

        $convo->putState($state);
        if ($convo->exists) {
            $convo->save();
        }
    }

    // ------------------------------------------------------------------ AI

    private function aiReply(
        Tenant $tenant,
        SmartAssistantWhatsAppConversation $convo,
        ?Customer $customer,
        string $from,
        string $text,
        SmartAssistantAgentSettings $settings,
        ?string $fromPhone = null,
    ): string {
        if (! (bool) config('intelligence.external_enabled', false)) {
            return '';
        }

        try {
            $provider = new OpenAIIntelligenceProvider();
        } catch (Throwable $e) {
            Log::warning('whatsapp.sales_agent.provider_unavailable', ['error' => $e->getMessage()]);

            return '';
        }

        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($tenant, $customer, $settings, $convo)]],
            $convo->historyMessages(),
            [['role' => 'user', 'content' => $text !== '' ? $text : 'مرحبا']],
        );

        $tools = $this->toolSchemas($settings);
        $toolContext = ['tenant' => $tenant, 'convo' => $convo, 'customer' => $customer, 'from' => $from, 'from_phone' => $fromPhone, 'settings' => $settings];

        for ($i = 0; $i < 5; $i++) {
            try {
                $result = $provider->chat($messages, $tools, ['max_tokens' => 600, 'temperature' => 0.4]);
            } catch (Throwable $e) {
                Log::warning('whatsapp.sales_agent.ai_failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);

                return '';
            }

            $toolCalls = $result['tool_calls'] ?? [];
            if ($toolCalls === []) {
                return trim((string) ($result['response'] ?? ''));
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $result['response'] ?? '',
                'tool_calls' => $toolCalls,
            ];

            foreach ($toolCalls as $call) {
                $name = (string) ($call['function']['name'] ?? '');
                $args = json_decode((string) ($call['function']['arguments'] ?? '{}'), true);
                $args = is_array($args) ? $args : [];
                $output = $this->runTool($name, $args, $toolContext);
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) ($call['id'] ?? $name),
                    'content' => json_encode($output, JSON_UNESCAPED_UNICODE) ?: '{}',
                ];
            }
        }

        return '';
    }

    /**
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function runTool(string $name, array $args, array $ctx): array
    {
        /** @var SmartAssistantAgentSettings|null $settings */
        $settings = $ctx['settings'] ?? null;
        if ($settings !== null) {
            if ($name === 'register_customer' && ! $settings->can_register_customers) {
                return ['ok' => false, 'error' => 'تسجيل العملاء معطّل من إعدادات المساعد'];
            }
            if ($name === 'create_invoice' && ! $settings->can_create_invoices) {
                return ['ok' => false, 'error' => 'إنشاء الفواتير معطّل من إعدادات المساعد'];
            }
        }

        $started = microtime(true);

        // Preview/simulation mode: mutating tools never touch real data.
        if ($this->dryRun && in_array($name, self::MUTATING_TOOLS, true)) {
            $result = [
                'ok' => true,
                'simulated' => true,
                'note' => 'وضع المعاينة: لم يُنفَّذ أي تغيير حقيقي في النظام.',
            ];
            $this->auditTool($name, 'simulated', $args, '', $started);

            return $result;
        }

        try {
            $result = match ($name) {
                'register_customer' => $this->toolRegisterCustomer($args, $ctx),
                'search_dresses' => $this->toolSearchDresses($args),
                'create_invoice' => $this->toolCreateInvoice($args, $ctx),
                'get_customer' => $this->toolGetCustomer($ctx),
                'search_customer' => $this->toolSearchCustomer($args),
                'update_customer' => $this->toolUpdateCustomer($args, $ctx),
                'get_dress' => $this->toolGetDress($args),
                'get_price' => $this->toolGetPrice($args),
                'check_availability' => $this->toolCheckAvailability($args),
                'get_order' => $this->toolGetOrder($args, $ctx),
                'get_order_status' => $this->toolGetOrderStatus($args, $ctx),
                'create_booking' => $this->toolCreateBooking($args, $ctx),
                'create_fitting' => $this->toolCreateFitting($args, $ctx),
                'get_reservation' => $this->toolGetReservation($args, $ctx),
                'cancel_reservation' => $this->toolCancelReservation($args, $ctx),
                'create_support_case' => $this->toolCreateSupportCase($args, $ctx),
                'create_followup' => $this->toolCreateFollowUp($args, $ctx),
                'handoff_to_human' => $this->toolHandoffToHuman($args, $ctx),
                'get_branches' => $this->toolGetBranches(),
                'get_branch_hours' => $this->toolGetBranchHours($ctx),
                'get_price_policy' => $this->toolGetPricePolicy($ctx),
                default => ['ok' => false, 'error' => 'unknown_tool'],
            };
            $this->auditTool($name, (bool) ($result['ok'] ?? false) ? 'ok' : 'error', $args, (string) ($result['error'] ?? ''), $started);

            // Track the last successful business action in the structured state.
            if ((bool) ($result['ok'] ?? false)) {
                /** @var SmartAssistantWhatsAppConversation|null $convo */
                $convo = $ctx['convo'] ?? null;
                if ($convo instanceof SmartAssistantWhatsAppConversation) {
                    $state = $convo->stateData();
                    $state['last_action'] = $name;
                    $convo->putState($state);
                    if ($convo->exists) {
                        $convo->save();
                    }
                }
            }

            return $result;
        } catch (Throwable $e) {
            Log::warning('whatsapp.sales_agent.tool_failed', ['tool' => $name, 'error' => $e->getMessage()]);
            $this->auditTool($name, 'error', $args, $e->getMessage(), $started);

            return ['ok' => false, 'error' => 'تعذر تنفيذ العملية، حاول لاحقاً'];
        }
    }

    /**
     * Tenant-scoped audit for every business tool execution (no secrets, no
     * full message bodies — tool args are small business parameters only).
     *
     * @param array<string, mixed> $args
     */
    private function auditTool(string $tool, string $status, array $args, string $error, float $started): void
    {
        try {
            DB::connection('tenant')->table('ai_tool_executions')->insert([
                'tool_name' => mb_substr('whatsapp.'.$tool, 0, 64),
                'tool_version' => '1.0.0',
                'status' => mb_substr($status, 0, 16),
                'facts' => json_encode($args, JSON_UNESCAPED_UNICODE) ?: null,
                'error' => $error !== '' ? mb_substr($error, 0, 500) : null,
                'execution_ms' => (int) ((microtime(true) - $started) * 1000),
                'executed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('whatsapp.sales_agent.tool_audit_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Structured conversation memory: durable facts only (never raw chatter).
     */
    private function extractFacts(SmartAssistantWhatsAppConversation $convo, string $text): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $state = $convo->stateData();
        $facts = (array) $state['known_facts'];
        $normalized = $this->normalize($text);

        $occasions = [
            'wedding' => ['زفاف', 'فرح', 'زفة', 'wedding'],
            'engagement' => ['خطوبة', 'خطوبه', 'engagement'],
            'evening_party' => ['سهرة', 'سهره', 'حفلة'],
            'graduation' => ['تخرج', 'تخرّج', 'graduation'],
        ];
        foreach ($occasions as $key => $words) {
            if (! isset($facts['occasion']) && $this->matchesAny($normalized, $words)) {
                $facts['occasion'] = $key;
            }
        }

        if (! isset($facts['preferred_product'])) {
            if ($this->matchesAny($normalized, ['فستان زفاف', 'فستان فرح'])) {
                $facts['preferred_product'] = 'فستان زفاف';
            } elseif ($this->matchesAny($normalized, ['فستان سهرة', 'فستان سواريه'])) {
                $facts['preferred_product'] = 'فستان سهرة';
            }
        }

        if (! isset($facts['date']) && preg_match('/\b(20\d{2}-\d{2}-\d{2})\b/', $text, $m)) {
            $facts['date'] = $m[1];
        }

        $state['known_facts'] = $facts;
        $state['last_inbound_at'] = now()->toIso8601String();
        $convo->putState($state);
        if ($convo->exists) {
            $convo->save();
        }
    }

    // ------------------------------------------------------- tenant tools

    /**
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolGetCustomer(array $ctx): array
    {
        $customer = $ctx['customer'];
        if ($customer === null) {
            return ['ok' => true, 'registered' => false];
        }

        $invoices = Invoice::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->limit(5)
            ->get(['id', 'invoice_number', 'type', 'status', 'total', 'remaining_amount', 'rent_start_date', 'rent_end_date']);

        return [
            'ok' => true,
            'registered' => true,
            'customer' => [
                'id' => (int) $customer->id,
                'name' => (string) $customer->name,
                'phone' => (string) ($customer->phone ?? ''),
            ],
            'recent_orders' => $invoices->map(static fn (Invoice $i): array => [
                'id' => (int) $i->id,
                'number' => (string) $i->invoice_number,
                'type' => (string) $i->type,
                'status' => (string) $i->status,
                'total' => (float) $i->total,
                'remaining' => (float) $i->remaining_amount,
            ])->all(),
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function toolGetDress(array $args): array
    {
        $dress = $this->findDress($args);
        if ($dress === null) {
            return ['ok' => false, 'error' => 'الفستان غير موجود'];
        }

        return [
            'ok' => true,
            'dress' => [
                'id' => (int) $dress->id,
                'code' => (string) $dress->code,
                'name' => (string) $dress->name,
                'color' => (string) ($dress->color ?? ''),
                'size' => (string) ($dress->size ?? ''),
                'status' => (string) $dress->status,
                'rental_price' => (float) ($dress->rental_price ?? 0),
                'sale_price' => (float) ($dress->sale_price ?? 0),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function toolGetPrice(array $args): array
    {
        $dress = $this->findDress($args);
        if ($dress === null) {
            return ['ok' => false, 'error' => 'الفستان غير موجود'];
        }

        return [
            'ok' => true,
            'dress_id' => (int) $dress->id,
            'name' => (string) $dress->name,
            'rental_price' => (float) ($dress->rental_price ?? 0),
            'sale_price' => (float) ($dress->sale_price ?? 0),
            'currency' => 'EGP',
        ];
    }

    /**
     * Availability = dress not retired + no confirmed rental overlapping the date.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function toolCheckAvailability(array $args): array
    {
        $dress = Dress::query()->find((int) ($args['dress_id'] ?? 0));
        if ($dress === null) {
            return ['ok' => false, 'error' => 'الفستان غير موجود'];
        }

        $date = (string) ($args['date'] ?? '');
        if (! preg_match('/^20\d{2}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'error' => 'التاريخ بصيغة YYYY-MM-DD مطلوب'];
        }

        $available = $dress->status === Dress::STATUS_AVAILABLE;
        $conflict = null;
        if ($available) {
            $conflict = Invoice::query()
                ->where('type', Invoice::TYPE_RENT)
                ->where('status', Invoice::STATUS_CONFIRMED)
                ->whereDate('rent_start_date', '<=', $date)
                ->whereDate('rent_end_date', '>=', $date)
                ->whereHas('items', static function ($q) use ($dress): void {
                    $q->where('dress_id', $dress->id);
                })
                ->first(['id', 'invoice_number', 'rent_start_date', 'rent_end_date']);
            $available = $conflict === null;
        }

        return [
            'ok' => true,
            'dress_id' => (int) $dress->id,
            'name' => (string) $dress->name,
            'date' => $date,
            'available' => $available,
            'conflict' => $conflict !== null ? [
                'invoice_number' => (string) $conflict->invoice_number,
                'from' => (string) $conflict->rent_start_date,
                'to' => (string) $conflict->rent_end_date,
            ] : null,
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolGetOrderStatus(array $args, array $ctx): array
    {
        $customer = $ctx['customer'];
        $invoiceId = (int) ($args['invoice_id'] ?? 0);

        $query = Invoice::query();
        if ($invoiceId > 0) {
            $query->where('id', $invoiceId);
            if ($customer !== null) {
                $query->where('customer_id', $customer->id); // tenant + ownership scope
            }
        } elseif ($customer !== null) {
            $query->where('customer_id', $customer->id)->latest('id');
        } else {
            return ['ok' => false, 'error' => 'العميل غير مسجل بعد'];
        }

        $invoice = $query->first(['id', 'invoice_number', 'type', 'status', 'total', 'paid_amount', 'remaining_amount', 'rent_start_date', 'rent_end_date']);
        if ($invoice === null) {
            return ['ok' => false, 'error' => 'لا يوجد طلب مطابق'];
        }

        return [
            'ok' => true,
            'order' => [
                'id' => (int) $invoice->id,
                'number' => (string) $invoice->invoice_number,
                'type' => (string) $invoice->type,
                'status' => (string) $invoice->status,
                'total' => (float) $invoice->total,
                'paid' => (float) $invoice->paid_amount,
                'remaining' => (float) $invoice->remaining_amount,
                'rent_start_date' => $invoice->rent_start_date,
                'rent_end_date' => $invoice->rent_end_date,
            ],
        ];
    }

    /**
     * Creates a REAL booking request row (website_booking_requests) — the
     * atelier team sees it in their bookings inbox.
     *
     * @param array<string, mixed> $args
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolCreateBooking(array $args, array $ctx): array
    {
        $date = (string) ($args['preferred_date'] ?? '');
        if (! preg_match('/^20\d{2}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'error' => 'التاريخ بصيغة YYYY-MM-DD مطلوب'];
        }

        $customer = $ctx['customer'];
        $convo = $ctx['convo'];
        $name = $customer !== null ? (string) $customer->name : (string) ($convo->stateData()['known_facts']['name'] ?? 'عميل واتساب');

        $booking = WebsiteBookingRequest::query()->create([
            'kind' => 'booking',
            'name' => mb_substr($name, 0, 190),
            'phone' => $this->ctxMsisdn($ctx) ?: 'واتساب',
            'service' => isset($args['dress_id']) ? 'dress:'.(int) $args['dress_id'] : null,
            'preferred_date' => $date,
            'notes' => mb_substr((string) ($args['notes'] ?? ''), 0, 1000),
            'status' => 'new',
            'meta' => ['source' => 'whatsapp_ai', 'customer_id' => $customer?->id],
        ]);

        $state = $convo->stateData();
        $state['last_action'] = 'create_booking:'.(int) $booking->id;
        $convo->putState($state);
        $convo->save();

        return [
            'ok' => true,
            'booking_id' => (int) $booking->id,
            'preferred_date' => $date,
            'status' => 'new',
        ];
    }

    /**
     * Fitting appointment in the atelier bookings inbox (kind=fitting).
     *
     * @param array<string, mixed> $args
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolCreateFitting(array $args, array $ctx): array
    {
        $date = (string) ($args['preferred_date'] ?? '');
        if (! preg_match('/^20\d{2}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'error' => 'تاريخ البروفة بصيغة YYYY-MM-DD مطلوب'];
        }

        $time = trim((string) ($args['preferred_time'] ?? ''));
        if ($time !== '' && ! preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $time)) {
            return ['ok' => false, 'error' => 'الوقت بصيغة HH:MM'];
        }

        $customer = $ctx['customer'];
        $convo = $ctx['convo'];
        $name = $customer !== null ? (string) $customer->name : (string) ($convo->stateData()['known_facts']['name'] ?? 'عميل واتساب');

        $branchName = trim((string) ($args['branch_name'] ?? ''));
        $branchId = (int) ($args['branch_id'] ?? 0);
        if ($branchId > 0) {
            foreach ($this->atelierKnowledge->branches() as $b) {
                if ((int) $b['id'] === $branchId) {
                    $branchName = (string) $b['name'];
                    break;
                }
            }
        }

        $dressId = (int) ($args['dress_id'] ?? 0);
        $service = $dressId > 0 ? 'dress:'.$dressId : 'fitting';

        $booking = WebsiteBookingRequest::query()->create([
            'kind' => 'fitting',
            'name' => mb_substr($name, 0, 190),
            'phone' => $this->ctxMsisdn($ctx) ?: 'واتساب',
            'service' => $service,
            'preferred_date' => $date.($time !== '' ? ' '.$time : ''),
            'notes' => mb_substr((string) ($args['notes'] ?? ''), 0, 1000),
            'branch' => $branchName !== '' ? mb_substr($branchName, 0, 120) : null,
            'status' => 'new',
            'meta' => [
                'source' => 'whatsapp_ai',
                'customer_id' => $customer?->id,
                'preferred_time' => $time !== '' ? $time : null,
                'branch_id' => $branchId > 0 ? $branchId : null,
                'dress_id' => $dressId > 0 ? $dressId : null,
            ],
        ]);

        $state = $convo->stateData();
        $state['last_action'] = 'create_fitting:'.(int) $booking->id;
        $convo->putState($state);
        $convo->save();

        return [
            'ok' => true,
            'fitting_id' => (int) $booking->id,
            'preferred_date' => $date,
            'preferred_time' => $time !== '' ? $time : null,
            'branch' => $branchName !== '' ? $branchName : null,
            'status' => 'new',
        ];
    }

    /**
     * Human handoff: conversation + context move to the atelier team; the AI
     * goes silent on this thread until a staff member resumes it.
     *
     * @param array<string, mixed> $args
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolHandoffToHuman(array $args, array $ctx): array
    {
        $convo = $ctx['convo'];
        $state = $convo->stateData();
        $state['handoff_state'] = 'handed_off';
        $state['handoff'] = [
            'reason' => mb_substr((string) ($args['reason'] ?? ''), 0, 300),
            'summary' => mb_substr((string) ($args['summary'] ?? ''), 0, 1000),
            'at' => now()->toIso8601String(),
        ];
        $convo->handler = 'human';
        $convo->putState($state);
        $convo->save();

        return [
            'ok' => true,
            'handed_off' => true,
            'note' => 'المحادثة الآن مع الفريق البشري. لا ترسلي ردودًا إضافية.',
        ];
    }

    /**
     * Search registered customers by name/phone (tenant scope).
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function toolSearchCustomer(array $args): array
    {
        $query = trim((string) ($args['query'] ?? ''));
        if ($query === '') {
            return ['ok' => false, 'error' => 'كلمة البحث مطلوبة'];
        }

        $customers = Customer::query()
            ->where('name', 'like', '%'.$query.'%')
            ->orWhere('phone', 'like', '%'.$query.'%')
            ->orWhere('whatsapp', 'like', '%'.$query.'%')
            ->limit(5)
            ->get(['id', 'name', 'phone', 'status']);

        return [
            'ok' => true,
            'count' => $customers->count(),
            'customers' => $customers->map(static fn (Customer $c): array => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'phone' => (string) ($c->phone ?? ''),
            ])->all(),
        ];
    }

    /**
     * Update the current customer's profile (name/notes). Never creates a
     * new record — registration goes through register_customer.
     *
     * @param array<string, mixed> $args
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolUpdateCustomer(array $args, array $ctx): array
    {
        $customer = $ctx['customer'];
        if ($customer === null && ($ctx['convo']->customer_id ?? null)) {
            $customer = Customer::query()->find((int) $ctx['convo']->customer_id);
        }
        if ($customer === null) {
            return ['ok' => false, 'error' => 'العميل غير مسجل بعد — سجّله أولاً عبر register_customer'];
        }

        $changed = [];
        $name = trim((string) ($args['name'] ?? ''));
        if ($name !== '' && $name !== $customer->name) {
            $customer->name = mb_substr($name, 0, 190);
            $changed[] = 'name';
        }
        $notes = trim((string) ($args['notes'] ?? ''));
        if ($notes !== '') {
            $existing = trim((string) ($customer->notes ?? ''));
            $customer->notes = mb_substr($existing === '' ? $notes : $existing."\n".$notes, 0, 2000);
            $changed[] = 'notes';
        }
        if ($changed === []) {
            return ['ok' => false, 'error' => 'لا توجد بيانات جديدة للتحديث'];
        }
        $customer->save();

        return ['ok' => true, 'customer_id' => (int) $customer->id, 'updated' => $changed];
    }

    /**
     * Fetch one order (invoice) belonging to the current customer — by id or
     * the latest one. Strictly scoped to the conversation's customer.
     *
     * @param array<string, mixed> $args
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolGetOrder(array $args, array $ctx): array
    {
        $customer = $ctx['customer'];
        if ($customer === null && ($ctx['convo']->customer_id ?? null)) {
            $customer = Customer::query()->find((int) $ctx['convo']->customer_id);
        }
        if ($customer === null) {
            return ['ok' => false, 'error' => 'العميل غير مسجل'];
        }

        $invoice = Invoice::query()
            ->where('customer_id', $customer->id)
            ->when((int) ($args['invoice_id'] ?? 0) > 0, static function ($q) use ($args): void {
                $q->where('id', (int) $args['invoice_id']);
            })
            ->latest('id')
            ->first(['id', 'invoice_number', 'type', 'status', 'total', 'remaining_amount', 'rent_start_date', 'rent_end_date', 'created_at']);

        if ($invoice === null) {
            return ['ok' => false, 'error' => 'لا توجد فواتير لهذا العميل'];
        }

        return [
            'ok' => true,
            'order' => [
                'id' => (int) $invoice->id,
                'number' => (string) $invoice->invoice_number,
                'type' => (string) $invoice->type,
                'status' => (string) $invoice->status,
                'total' => (float) $invoice->total,
                'remaining' => (float) $invoice->remaining_amount,
                'rent_start_date' => $invoice->rent_start_date,
                'rent_end_date' => $invoice->rent_end_date,
            ],
        ];
    }

    /**
     * List the current customer's reservations (booking requests) or one by id.
     *
     * @param array<string, mixed> $args
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolGetReservation(array $args, array $ctx): array
    {
        $query = WebsiteBookingRequest::query()
            ->whereIn('phone', $this->ctxPhoneKeys($ctx) ?: ['__none__'])
            ->latest('id');
        if ((int) ($args['booking_id'] ?? 0) > 0) {
            $query->where('id', (int) $args['booking_id']);
        }
        $rows = $query->limit(3)->get(['id', 'kind', 'service', 'preferred_date', 'status', 'created_at']);

        if ($rows->isEmpty()) {
            return ['ok' => true, 'count' => 0, 'reservations' => []];
        }

        return [
            'ok' => true,
            'count' => $rows->count(),
            'reservations' => $rows->map(static fn (WebsiteBookingRequest $b): array => [
                'id' => (int) $b->id,
                'kind' => (string) $b->kind,
                'service' => (string) ($b->service ?? ''),
                'preferred_date' => (string) ($b->preferred_date ?? ''),
                'status' => (string) $b->status,
            ])->all(),
        ];
    }

    /**
     * Cancel one of the current customer's own reservations.
     *
     * @param array<string, mixed> $args
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolCancelReservation(array $args, array $ctx): array
    {
        $booking = WebsiteBookingRequest::query()
            ->where('id', (int) ($args['booking_id'] ?? 0))
            ->whereIn('phone', $this->ctxPhoneKeys($ctx) ?: ['__none__'])
            ->first();
        if ($booking === null) {
            return ['ok' => false, 'error' => 'الحجز غير موجود'];
        }
        if (in_array($booking->status, ['cancelled', 'done'], true)) {
            return ['ok' => false, 'error' => 'لا يمكن إلغاء هذا الحجز في حالته الحالية'];
        }

        $booking->status = 'cancelled';
        $booking->save();

        return ['ok' => true, 'booking_id' => (int) $booking->id, 'status' => 'cancelled'];
    }

    /**
     * Open a high-priority support case for the atelier team (tenant
     * notifications feed — visible to every staff member).
     *
     * @param array<string, mixed> $args
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolCreateSupportCase(array $args, array $ctx): array
    {
        $issue = trim((string) ($args['issue'] ?? ''));
        if ($issue === '') {
            return ['ok' => false, 'error' => 'وصف المشكلة مطلوب'];
        }

        $customer = $ctx['customer'];
        $caseId = DB::connection('tenant')->table('notifications')->insertGetId([
            'user_id' => null,
            'title' => 'حالة دعم من واتساب — '.$ctx['from'],
            'message' => mb_substr($issue, 0, 1000)
                ."\n— العميل: ".($customer !== null ? $customer->name.' ('.$customer->id.')' : $ctx['from']),
            'category' => 'support',
            'priority' => 'high',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['ok' => true, 'case_id' => (int) $caseId, 'status' => 'open'];
    }

    /**
     * Schedule a follow-up reminder for the team (tenant notifications feed).
     *
     * @param array<string, mixed> $args
     * @param array{tenant: Tenant, convo: SmartAssistantWhatsAppConversation, customer: ?Customer, from: string} $ctx
     * @return array<string, mixed>
     */
    private function toolCreateFollowUp(array $args, array $ctx): array
    {
        $note = trim((string) ($args['note'] ?? ''));
        $date = (string) ($args['date'] ?? '');
        if ($date !== '' && ! preg_match('/^20\d{2}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'error' => 'التاريخ بصيغة YYYY-MM-DD مطلوب'];
        }

        $customer = $ctx['customer'];
        $followUpId = DB::connection('tenant')->table('notifications')->insertGetId([
            'user_id' => null,
            'title' => 'متابعة عميل واتساب — '.$ctx['from'],
            'message' => mb_substr(($date !== '' ? 'التاريخ المطلوب: '.$date.' — ' : '').$note, 0, 1000)
                ."\n— العميل: ".($customer !== null ? $customer->name.' ('.$customer->id.')' : $ctx['from']),
            'category' => 'follow_up',
            'priority' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['ok' => true, 'followup_id' => (int) $followUpId, 'date' => $date !== '' ? $date : null];
    }

    /**
     * @param array<string, mixed> $args
     */
    private function findDress(array $args): ?Dress
    {
        $id = (int) ($args['dress_id'] ?? $args['id'] ?? 0);
        $code = trim((string) ($args['code'] ?? ''));

        if ($id > 0) {
            return Dress::query()->find($id);
        }
        if ($code !== '') {
            return Dress::query()->where('code', $code)->first();
        }

        return null;
    }

    /**
     * @param array<string, mixed> $ctx
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function toolRegisterCustomer(array $args, array $ctx): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'error' => 'الاسم مطلوب'];
        }

        $phone = $this->ctxMsisdn($ctx);
        $customer = $ctx['customer'];
        if ($customer === null) {
            $customer = Customer::query()->create([
                'name' => mb_substr($name, 0, 190),
                'phone' => $phone,
                'whatsapp' => $phone,
                'source' => 'whatsapp_sales_agent',
                'status' => 'active',
            ]);
        } else {
            $customer->name = $customer->name ?: $name;
            if ($phone !== null && (trim((string) $customer->phone) === '' || str_contains((string) $customer->phone, '@') || str_contains((string) $customer->whatsapp, '@lid'))) {
                $customer->phone = $phone;
                $customer->whatsapp = $phone;
            }
            $customer->save();
        }

        /** @var SmartAssistantWhatsAppConversation $convo */
        $convo = $ctx['convo'];
        $convo->customer_id = (int) $customer->id;

        return ['ok' => true, 'customer_id' => (int) $customer->id, 'name' => $customer->name];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function toolSearchDresses(array $args): array
    {
        $query = trim((string) ($args['query'] ?? ''));

        $dresses = Dress::query()
            ->where('status', Dress::STATUS_AVAILABLE)
            ->when($query !== '', function ($q) use ($query): void {
                $q->where(function ($qq) use ($query): void {
                    $qq->where('name', 'like', '%'.$query.'%')
                        ->orWhere('code', 'like', '%'.$query.'%')
                        ->orWhere('color', 'like', '%'.$query.'%')
                        ->orWhere('description', 'like', '%'.$query.'%');
                });
            })
            ->limit(8)
            ->get(['id', 'code', 'name', 'color', 'size', 'rental_price', 'sale_price', 'status']);

        return [
            'ok' => true,
            'count' => $dresses->count(),
            'dresses' => $dresses->map(static fn (Dress $d): array => [
                'id' => $d->id,
                'code' => $d->code,
                'name' => $d->name,
                'color' => $d->color,
                'size' => $d->size,
                'rental_price' => $d->rental_price,
                'sale_price' => $d->sale_price,
            ])->all(),
        ];
    }

    /**
     * Creates a DRAFT invoice only. Final confirmation happens via the
     * deterministic «تأكيد» keyword flow, never inside the AI loop.
     *
     * @param array<string, mixed> $ctx
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function toolCreateInvoice(array $args, array $ctx): array
    {
        /** @var SmartAssistantWhatsAppConversation $convo */
        $convo = $ctx['convo'];
        $customer = $ctx['customer'];
        if ($customer === null && $convo->customer_id) {
            $customer = Customer::query()->find((int) $convo->customer_id);
        }
        if ($customer === null) {
            return ['ok' => false, 'error' => 'سجّل العميل أولاً عبر register_customer'];
        }

        $type = (string) ($args['type'] ?? 'rent');
        $type = $type === 'sale' ? Invoice::TYPE_SELL : Invoice::TYPE_RENT;

        $dressIds = array_map('intval', (array) ($args['dress_ids'] ?? []));
        if ($dressIds === []) {
            return ['ok' => false, 'error' => 'اختر فستانًا واحدًا على الأقل'];
        }

        $dresses = Dress::query()->whereIn('id', $dressIds)->get();
        if ($dresses->isEmpty()) {
            return ['ok' => false, 'error' => 'الفساتين المحددة غير موجودة'];
        }
        foreach ($dresses as $dress) {
            if ($dress->status !== Dress::STATUS_AVAILABLE) {
                return ['ok' => false, 'error' => 'الفستان «'.$dress->name.'» غير متاح حاليًا'];
            }
        }

        $items = $dresses->map(static function (Dress $d) use ($type): array {
            return [
                'dress_id' => (int) $d->id,
                'item_type' => 'dress',
                'description' => $d->name,
                'quantity' => 1,
                'unit_price' => $type === Invoice::TYPE_SELL
                    ? (float) ($d->sale_price ?? 0)
                    : (float) ($d->rental_price ?? 0),
            ];
        })->all();

        $data = [
            'type' => $type,
            'status' => Invoice::STATUS_DRAFT,
            'customer_id' => (int) $customer->id,
            'items' => $items,
            'notes' => 'فاتورة عبر مساعد واتساب',
            'rent_start_date' => $args['rent_start_date'] ?? null,
            'rent_end_date' => $args['rent_end_date'] ?? null,
            'occasion_datetime' => $args['occasion_datetime'] ?? null,
        ];

        $invoice = $this->invoiceService->create($data, null);

        $convo->pending_action = [
            'kind' => 'confirm_invoice',
            'invoice_id' => (int) $invoice->id,
            'invoice_number' => (string) $invoice->invoice_number,
            'total' => (float) $invoice->total,
            'type' => $type,
        ];
        $convo->save();

        return [
            'ok' => true,
            'draft' => true,
            'invoice_id' => (int) $invoice->id,
            'invoice_number' => (string) $invoice->invoice_number,
            'type' => $type,
            'total' => (float) $invoice->total,
            'instruction_to_customer' => 'اطلب من العميل إرسال كلمة «تأكيد» لاعتماد الفاتورة نهائيًا',
        ];
    }

    // -------------------------------------------------- deterministic gate

    /**
     * @param array<string, mixed> $pending
     */
    private function confirmPending(Tenant $tenant, SmartAssistantWhatsAppConversation $convo, array $pending): string
    {
        if (($pending['kind'] ?? '') !== 'confirm_invoice') {
            $convo->pending_action = null;
            $convo->save();

            return 'تمت العملية ✅';
        }

        $invoice = Invoice::query()->find((int) ($pending['invoice_id'] ?? 0));
        if ($invoice === null || $invoice->status !== Invoice::STATUS_DRAFT) {
            $convo->pending_action = null;
            $convo->save();

            return 'عذرًا، الفاتورة لم تعد متاحة. يمكننا إنشاء واحدة جديدة.';
        }

        if ($invoice->type === Invoice::TYPE_RENT && ! $this->rentStillAvailable($invoice)) {
            $convo->pending_action = null;
            $convo->save();

            return 'عذرًا، الفستان لم يعد متاحًا في هذه الفترة. أخبرني بتاريخ آخر أو فستان بديل 🙏';
        }

        $invoice->status = Invoice::STATUS_CONFIRMED;
        $invoice->save();

        $convo->pending_action = null;
        $convo->save();

        $kindAr = $invoice->type === Invoice::TYPE_SELL ? 'بيع' : 'تأجير';

        try {
            $invoice->load(['customer', 'branch', 'items.dress']);
            app(TenantWhatsAppInvoiceNotifier::class)->notifyConfirmed(
                $tenant,
                $invoice,
                (string) $convo->phone,
                false,
            );
        } catch (Throwable $e) {
            Log::warning('whatsapp.sales_agent.invoice_pdf_notify_failed', [
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        return "تم تأكيد حجزك بنجاح ✅\n"
            ."رقم الفاتورة: {$invoice->invoice_number}\n"
            ."النوع: {$kindAr}\n"
            ."الإجمالي: ".number_format((float) $invoice->total, 2)."\n"
            ."أرفقنا لك الفاتورة على واتساب. شكرًا لثقتك 🌸";
    }

    /**
     * @param array<string, mixed> $pending
     */
    private function cancelPending(Tenant $tenant, SmartAssistantWhatsAppConversation $convo, array $pending): string
    {
        if (($pending['kind'] ?? '') === 'confirm_invoice') {
            $invoice = Invoice::query()->find((int) ($pending['invoice_id'] ?? 0));
            if ($invoice !== null && $invoice->status === Invoice::STATUS_DRAFT) {
                $invoice->status = 'cancelled';
                $invoice->save();
            }
        }

        $convo->pending_action = null;
        $convo->save();

        return 'تم إلغاء الطلب ✅ لا تتردد في مراسلتنا في أي وقت.';
    }

    private function rentStillAvailable(Invoice $invoice): bool
    {
        $start = $invoice->rent_start_date;
        $end = $invoice->rent_end_date;
        if ($start === null || $end === null) {
            return true;
        }

        $dressIds = $invoice->items()->pluck('dress_id')->filter()->all();
        if ($dressIds === []) {
            return true;
        }

        return ! Invoice::query()
            ->where('type', Invoice::TYPE_RENT)
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->where('id', '!=', $invoice->id)
            ->whereDate('rent_start_date', '<=', $end)
            ->whereDate('rent_end_date', '>=', $start)
            ->whereHas('items', static function ($q) use ($dressIds): void {
                $q->whereIn('dress_id', $dressIds);
            })
            ->exists();
    }

    // ------------------------------------------------------------ utilities

    private function findCustomer(string $from, ?string $fromPhone = null): ?Customer
    {
        $keys = WhatsAppCustomerPhone::lookupKeys($fromPhone, $from);
        if ($keys === []) {
            return null;
        }

        $digits = WhatsAppCustomerPhone::msisdn($fromPhone, $from);
        $query = Customer::query()->where(function ($q) use ($keys, $digits): void {
            foreach ($keys as $key) {
                $q->orWhere('phone', $key)->orWhere('whatsapp', $key);
            }
            if ($digits !== null && strlen($digits) >= 9) {
                $tail = substr($digits, -9);
                $q->orWhere('phone', 'like', '%'.$tail)
                    ->orWhere('whatsapp', 'like', '%'.$tail);
            }
        });

        return $query->first();
    }

    /**
     * @param array{from?:string, from_phone?:?string} $ctx
     */
    private function ctxMsisdn(array $ctx): ?string
    {
        return WhatsAppCustomerPhone::msisdn(
            isset($ctx['from_phone']) ? (string) $ctx['from_phone'] : null,
            (string) ($ctx['from'] ?? ''),
        );
    }

    /**
     * @param array{from?:string, from_phone?:?string} $ctx
     * @return list<string>
     */
    private function ctxPhoneKeys(array $ctx): array
    {
        return WhatsAppCustomerPhone::lookupKeys(
            isset($ctx['from_phone']) ? (string) $ctx['from_phone'] : null,
            (string) ($ctx['from'] ?? ''),
        );
    }

    private function systemPrompt(Tenant $tenant, ?Customer $customer, SmartAssistantAgentSettings $settings, ?SmartAssistantWhatsAppConversation $convo = null): string
    {
        $identity = app(TenantIdentityResolver::class)->resolve(
            (int) $tenant->id,
            filled($this->persona['agent_name'] ?? null) ? (string) $this->persona['agent_name'] : null,
            filled($this->persona['department_name'] ?? null) ? (string) $this->persona['department_name'] : null,
        );
        $customerLine = $customer !== null
            ? 'العميل مسجل لدينا باسم: '.$customer->name.' (رقم '.$customer->id.').'
            : ($settings->can_register_customers
                ? 'العميل غير مسجل بعد. إذا ذكر اسمه، سجّله فورًا عبر register_customer.'
                : 'العميل غير مسجل بعد (تسجيل العملاء معطّل — لا تسجّله).');

        $pricesLine = $settings->can_show_prices
            ? '- لا تخترعي أسعارًا أو فساتين: استخدمي دائمًا search_dresses لمعرفة المتاح والأسعار.'
            : '- استخدمي search_dresses لمعرفة المتاح فقط، ولا تذكري الأسعار إطلاقًا — قولي إن الأسعار تُؤكَّد مع الفريق.';

        $invoiceLine = $settings->can_create_invoices
            ? "- للبيع أو الحجز: استخدمي create_invoice (تُنشئ مسودة فقط)، ثم اعرضي رقم الفاتورة والإجمالي واطلبي من العميل إرسال كلمة «تأكيد» لاعتمادها، أو «إلغاء» للتراجع.\n- لا تعتمدي أي عملية بنفسك دون كلمة «تأكيد» الصريحة من العميل.\n- للتأجير اسألي عن تاريخ المناسبة/البداية والنهاية قبل إنشاء الفاتورة.\n- لموعد بروفة استخدمي create_fitting (فرع + تاريخ + ساعة + فستان اختياري). create_booking للحجز العام فقط."
            : '- إنشاء الفواتير معطّل حاليًا: خذي طلب العميل وتفاصيله وقولي إن الفريق سيتواصل لإتمام الحجز.';

        $handoff = filled($settings->handoff_message)
            ? 'عند التحويل لموظف استخدمي هذا النص: '.$settings->handoff_message
            : 'اعتذري بلطف وقولي إن الفريق سيتواصل معه.';

        $personality = filled($settings->personality)
            ? "\nشخصيتك ونبرتك:\n".$settings->personality."\n"
            : '';
        $instructions = filled($settings->business_instructions)
            ? "\nتعليمات البيزنيس الخاصة بالأتيليه (التزمي بها دائمًا):\n".$settings->business_instructions."\n"
            : '';
        $faqBlock = '';
        if (is_array($settings->faq) && $settings->faq !== []) {
            $faqBlock = "\nأسئلة شائعة وإجاباتها المعتمدة (استخدميها عند السؤال عنها):\n";
            foreach ($settings->faq as $f) {
                if (is_array($f) && filled($f['q'] ?? null)) {
                    $faqBlock .= 'س: '.$f['q']."\n".'ج: '.($f['a'] ?? '')."\n";
                }
            }
        }

        $toneLine = match ((string) $settings->tone) {
            'professional' => '- نبرتك مهنية ورسمية دون مبالغة.',
            'luxury' => '- نبرتك راقية وفاخرة، تليق بعملاء يبحثون عن التميز.',
            'warm' => '- نبرتك دافئة وقريبة من القلب.',
            'concise' => '- ردودك قصيرة جدًا ومباشرة.',
            default => '- نبرتك ودودة ومرحة باعتدال.',
        };
        $styleLine = match ((string) $settings->style) {
            'short_direct' => '- أسلوبك: قصير ومباشر، بلا إطالة.',
            'consultative' => '- أسلوبك: استشاري — تسألين لتفهمي الاحتياج قبل التوصية.',
            'detailed' => '- أسلوبك: تفصيلي عند الحاجة، مع بقاء الرد منظمًا.',
            default => '- أسلوبك: محادثة طبيعية وسلسة.',
        };
        $languageLine = match ((string) $settings->language) {
            'en' => '- ردّي بالإنجليزية دائمًا.',
            'ar_en' => '- اكتشفي لغة العميل تلقائيًا وردّي بنفس لغته (عربي ↔ إنجليزي).',
            default => '- ردّي بالعربية افتراضيًا، وإذا كتب العميل بالإنجليزية فردّي بالإنجليزية.',
        };

        $factsLine = '';
        $facts = $convo instanceof SmartAssistantWhatsAppConversation ? (array) ($convo->stateData()['known_facts'] ?? []) : [];
        if ($facts !== []) {
            $factsLine = "\nمعلومات معروفة عن العميل (لا تسألي عنها مجددًا إلا للتأكيد):\n";
            foreach ($facts as $k => $v) {
                $factsLine .= '- '.$k.': '.$v."\n";
            }
        }

        $identityLine = $identity->isPlatform
            ? $identity->introLineAr()."\n- هويتك دائمًا DressnMore: لا تذكر أبدًا اسم أي أتيليه ولا تدّعي أنك موظفة أتيليه، حتى لو سُئلت.\n- عند السؤال عن الأسعار أو الباقات: اشرحي باقات المنصة وخطط الاشتراك فقط، ولا تذكري فساتين أو أسعار إيجار."
            : $this->tenantAtelierIdentityBlock($identity);

        $shopKnowledge = $identity->isPlatform ? '' : $this->atelierKnowledge->promptBlock($settings);

        return <<<PROMPT
{$identityLine}

IDENTITY GUARDRAIL (English — mandatory):
{$identity->llmGuardrailEn()}

قواعد صارمة:
{$languageLine}
{$toneLine}
{$styleLine}
- لا تتجاوزي 5 أسطر غالبًا.
{$pricesLine}
{$invoiceLine}
- إذا طلب العميل موظفًا بشريًا أو كان الموضوع خارج نطاقك: {$handoff}
- {$customerLine}
{$personality}{$instructions}{$faqBlock}{$factsLine}{$shopKnowledge}
PROMPT;
    }

    private function tenantAtelierIdentityBlock(\DressnMore\SmartAssistantProduct\SalesIntelligence\Orchestrator\Domain\TenantIdentity $identity): string
    {
        $business = $identity->businessName;
        $greetLine = $identity->greetingLineAr();
        $greet = "عند الترحيب قولي حرفيًا: «{$greetLine}».";

        return $identity->introLineAr()."\n"
            .'- دورك: '.$identity->roleLabelAr().". تمثلين «{$business}» فقط — ليس DressnMore.\n"
            ."- {$greet}\n"
            .'- لا تقدّمي نفسك أبدًا باسم DressnMore. DressnMore منصة البرمجيات فقط.';
    }

    /**
     * Tenant FAQ: keyword match before calling the AI (free & deterministic).
     */
    private function matchFaq(SmartAssistantAgentSettings $settings, string $text): ?string
    {
        if (! is_array($settings->faq) || $settings->faq === [] || trim($text) === '') {
            return null;
        }

        $needle = $this->normalize($text);
        foreach ($settings->faq as $f) {
            if (! is_array($f)) {
                continue;
            }
            $q = $this->normalize((string) ($f['q'] ?? ''));
            if ($q !== '' && mb_strlen($q) >= 4 && (str_contains($needle, $q) || str_contains($q, $needle))) {
                $a = trim((string) ($f['a'] ?? ''));
                if ($a !== '') {
                    return $a;
                }
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function toolSchemas(SmartAssistantAgentSettings $settings): array
    {
        $fn = static fn (string $name, string $desc, array $props, array $required): array => [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $desc,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $props,
                    'required' => $required,
                ],
            ],
        ];

        $tools = [
            $fn('search_dresses', 'البحث في فساتين الأتيليه المتاحة (الاسم/اللون/الكود/الوصف) ومعرفة الأسعار', [
                'query' => ['type' => 'string', 'description' => 'كلمة البحث، أو اتركها فارغة لعرض المتاح'],
            ], []),
        ];

        if ($settings->can_register_customers) {
            $tools[] = $fn('register_customer', 'تسجيل العميل الحالي في النظام باسمه (رقمه هو رقم المرسل)', [
                'name' => ['type' => 'string', 'description' => 'اسم العميل كما ذكره'],
            ], ['name']);
        }

        $tools[] = $fn('get_customer', 'بيانات العميل الحالي وآخر فواتيره (لا تحتاج مدخلات)', ['_none' => ['type' => 'string', 'description' => 'اتركها فارغة']], []);

        $tools[] = $fn('search_customer', 'البحث عن عميل مسجل بالاسم أو رقم الهاتف', [
            'query' => ['type' => 'string', 'description' => 'اسم أو رقم هاتف العميل'],
        ], ['query']);

        if ($settings->can_register_customers) {
            $tools[] = $fn('update_customer', 'تحديث بيانات العميل الحالي (الاسم أو ملاحظات التفضيلات)', [
                'name' => ['type' => 'string'],
                'notes' => ['type' => 'string', 'description' => 'ملاحظة/تفضيل يذكره العميل'],
            ], []);
        }

        $tools[] = $fn('get_dress', 'تفاصيل فستان محدد بالمعرف أو الكود', [
            'id' => ['type' => 'integer', 'description' => 'معرف الفستان'],
            'code' => ['type' => 'string', 'description' => 'كود الفستان'],
        ], []);

        if ($settings->can_show_prices) {
            $tools[] = $fn('get_price', 'سعر فستان محدد (إيجار/بيع) من النظام مباشرة', [
                'dress_id' => ['type' => 'integer'],
                'code' => ['type' => 'string'],
            ], []);
        }

        $tools[] = $fn('check_availability', 'التحقق من توفر فستان في تاريخ محدد', [
            'dress_id' => ['type' => 'integer'],
            'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
        ], ['dress_id', 'date']);

        $tools[] = $fn('get_order_status', 'حالة طلب/فاتورة العميل (آخر فاتورة أو برقم محدد)', [
            'invoice_id' => ['type' => 'integer'],
        ], []);

        $tools[] = $fn('get_order', 'تفاصيل فاتورة محددة للعميل الحالي (أو أحدث فاتورة)', [
            'invoice_id' => ['type' => 'integer'],
        ], []);

        $tools[] = $fn('create_booking', 'إنشاء طلب حجز حقيقي للعميل بتاريخ محدد (يسجَّل في النظام)', [
            'preferred_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
            'dress_id' => ['type' => 'integer'],
            'notes' => ['type' => 'string'],
        ], ['preferred_date']);

        $tools[] = $fn('create_fitting', 'حجز موعد بروفة في فرع الأتيليه (تاريخ + ساعة + فرع + فستان اختياري)', [
            'preferred_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
            'preferred_time' => ['type' => 'string', 'description' => 'HH:MM'],
            'branch_id' => ['type' => 'integer', 'description' => 'معرف الفرع من get_branches'],
            'branch_name' => ['type' => 'string', 'description' => 'اسم الفرع إن لم يتوفر المعرف'],
            'dress_id' => ['type' => 'integer'],
            'notes' => ['type' => 'string'],
        ], ['preferred_date']);

        $tools[] = $fn('get_reservation', 'عرض حجوزات العميل الحالية (أو حجز محدد بالمعرف)', [
            'booking_id' => ['type' => 'integer'],
        ], []);

        $tools[] = $fn('cancel_reservation', 'إلغاء حجز قائم يخص العميل الحالي (يتطلب معرف الحجز)', [
            'booking_id' => ['type' => 'integer'],
        ], ['booking_id']);

        $tools[] = $fn('create_support_case', 'فتح حالة دعم عالية الأولوية لفريق الأتيليه عند مشكلة/شكوى العميل', [
            'issue' => ['type' => 'string', 'description' => 'وصف المشكلة كما ذكرها العميل'],
        ], ['issue']);

        $tools[] = $fn('create_followup', 'جدولة تذكير متابعة للفريق مع العميل الحالي', [
            'note' => ['type' => 'string', 'description' => 'سبب المتابعة'],
            'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD (اختياري)'],
        ], ['note']);

        $tools[] = $fn('handoff_to_human', 'تحويل المحادثة لموظف بشري مع ملخص — توقفي عن الرد بعدها', [
            'reason' => ['type' => 'string'],
            'summary' => ['type' => 'string', 'description' => 'ملخص المحادثة والمطلوب'],
        ], ['reason']);

        $tools[] = $fn('get_branches', 'عناوين وفروع الأتيليه من النظام (الاسم، العنوان، الهاتف). استخدميها عند سؤال العميل عن الموقع أو أقرب فرع.', [
            '_none' => ['type' => 'string', 'description' => 'اتركها فارغة'],
        ], []);

        $tools[] = $fn('get_branch_hours', 'ساعات عمل الأتيليه من إعدادات المساعد (من/إلى وهل هو مفتوح الآن)', [
            '_none' => ['type' => 'string', 'description' => 'اتركها فارغة'],
        ], []);

        $tools[] = $fn('get_price_policy', 'نطاق أسعار الإيجار والبيع الحالي من فساتين النظام (ليس سعر فستان واحد — للسعر المحدد استخدمي get_price)', [
            '_none' => ['type' => 'string', 'description' => 'اتركها فارغة'],
        ], []);

        if ($settings->can_create_invoices) {
            $tools[] = $fn('create_invoice', 'إنشاء فاتورة مسودة (بيع أو تأجير) لعميل مسجل — لا تعتمد نهائيًا إلا بكلمة تأكيد من العميل', [
                'type' => ['type' => 'string', 'enum' => ['rent', 'sale']],
                'dress_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'معرفات الفساتين من نتائج search_dresses'],
                'rent_start_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD للتأجير'],
                'rent_end_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD للتأجير'],
            ], ['type', 'dress_ids']);
        }

        return $tools;
    }

    /** @return array<string, mixed> */
    private function toolGetBranches(): array
    {
        $branches = $this->atelierKnowledge->branches();

        return [
            'ok' => true,
            'count' => count($branches),
            'branches' => $branches,
        ];
    }

    /**
     * @param array{settings?: ?SmartAssistantAgentSettings} $ctx
     * @return array<string, mixed>
     */
    private function toolGetBranchHours(array $ctx): array
    {
        $settings = $ctx['settings'] ?? null;
        if (! $settings instanceof SmartAssistantAgentSettings) {
            return ['ok' => false, 'error' => 'إعدادات المساعد غير متاحة'];
        }

        return ['ok' => true, 'hours' => $this->atelierKnowledge->hours($settings)];
    }

    /**
     * @param array{settings?: ?SmartAssistantAgentSettings} $ctx
     * @return array<string, mixed>
     */
    private function toolGetPricePolicy(array $ctx): array
    {
        $settings = $ctx['settings'] ?? null;
        $canShow = $settings instanceof SmartAssistantAgentSettings && $settings->can_show_prices;

        return ['ok' => true, 'policy' => $this->atelierKnowledge->pricePolicy($canShow)];
    }

    private function persist(SmartAssistantWhatsAppConversation $convo, string $in, string $out): void
    {
        if ($in !== '') {
            $convo->pushHistory('user', $in);
        }
        if ($out !== '') {
            $convo->pushHistory('assistant', $out);
        }
        $convo->tenant_id = (int) $convo->tenant_id;
        $convo->save();
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(trim($text));
    }

    /**
     * @param list<string> $words
     */
    private function matchesAny(string $text, array $words): bool
    {
        foreach ($words as $w) {
            if ($text !== '' && (str_contains($text, mb_strtolower($w)))) {
                return true;
            }
        }

        return false;
    }
}
