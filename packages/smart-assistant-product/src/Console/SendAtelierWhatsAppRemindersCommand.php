<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Console;

use DressnMore\SmartAssistantProduct\Application\TenantWhatsAppReminderService;
use Illuminate\Console\Command;

final class SendAtelierWhatsAppRemindersCommand extends Command
{
    protected $signature = 'smart-assistant:atelier-whatsapp-reminders {--tenant= : Tenant slug only}';

    protected $description = 'Send tenant WhatsApp pickup/return reminders and post-return congratulations';

    public function handle(TenantWhatsAppReminderService $reminders): int
    {
        $slug = $this->option('tenant');
        $totals = $reminders->run(is_string($slug) ? $slug : null);

        $this->info(sprintf(
            'atelier WhatsApp reminders: tenants=%d pickup=%d return=%d congrats=%d skipped=%d',
            $totals['tenants'],
            $totals['pickup'],
            $totals['return'],
            $totals['congrats'],
            $totals['skipped'],
        ));

        return self::SUCCESS;
    }
}
