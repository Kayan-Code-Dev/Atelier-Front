<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Dress;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantAgentSettings;
use Throwable;

/**
 * Tenant-scoped atelier facts for the WhatsApp sales employee:
 * branches, hours, and live price ranges from THIS tenant DB only.
 */
final class TenantAtelierKnowledge
{
    /**
     * @return list<array<string, mixed>>
     */
    public function branches(): array
    {
        try {
            $rows = Branch::query()
                ->where('status', Branch::STATUS_ACTIVE)
                ->orderBy('id')
                ->get(['id', 'name', 'code', 'branch_code', 'address', 'street', 'building', 'phone', 'notes', 'vat_enabled', 'vat_value', 'currency']);
        } catch (Throwable) {
            return [];
        }

        $out = [];
        foreach ($rows as $b) {
            $parts = array_values(array_filter([
                trim((string) ($b->address ?? '')),
                trim((string) ($b->street ?? '')),
                trim((string) ($b->building ?? '')),
            ], static fn (string $p): bool => $p !== ''));

            $code = filled($b->code) ? (string) $b->code : (filled($b->branch_code) ? (string) $b->branch_code : null);

            $out[] = [
                'id' => (int) $b->id,
                'name' => (string) ($b->name ?: 'فرع'),
                'code' => $code,
                'address' => $parts !== [] ? implode('، ', $parts) : null,
                'phone' => filled($b->phone) ? (string) $b->phone : null,
                'notes' => filled($b->notes) ? (string) $b->notes : null,
                'vat_enabled' => (bool) $b->vat_enabled,
                'vat_value' => $b->vat_value !== null ? (float) $b->vat_value : null,
                'currency' => filled($b->currency) ? (string) $b->currency : null,
            ];
        }

        return $out;
    }

    /**
     * @return array{from:?string,to:?string,after_hours:?string,away_message:?string,open_now:bool,label:string}
     */
    public function hours(SmartAssistantAgentSettings $settings): array
    {
        $from = filled($settings->business_hours_from) ? (string) $settings->business_hours_from : null;
        $to = filled($settings->business_hours_to) ? (string) $settings->business_hours_to : null;
        $openNow = $settings->isWithinBusinessHours();

        if ($from === null || $to === null) {
            $label = 'ساعات العمل غير محددة في الإعدادات — أكّدي مع الفريق عند الحاجة.';
        } else {
            $label = 'ساعات العمل: من '.$from.' إلى '.$to.($openNow ? ' (مفتوح الآن)' : ' (خارج الدوام الآن)');
        }

        return [
            'from' => $from,
            'to' => $to,
            'after_hours' => filled($settings->after_hours_behavior) ? (string) $settings->after_hours_behavior : 'reply',
            'away_message' => filled($settings->away_message) ? (string) $settings->away_message : null,
            'open_now' => $openNow,
            'label' => $label,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pricePolicy(bool $canShowPrices): array
    {
        if (! $canShowPrices) {
            return [
                'can_show_prices' => false,
                'note' => 'عرض الأسعار معطّل من إعدادات المساعد — لا تذكري أرقامًا.',
            ];
        }

        try {
            $available = Dress::query()
                ->where('status', Dress::STATUS_AVAILABLE)
                ->whereNull('deleted_at');

            $rentMin = (clone $available)->where('rental_price', '>', 0)->min('rental_price');
            $rentMax = (clone $available)->where('rental_price', '>', 0)->max('rental_price');
            $saleMin = (clone $available)->where('sale_price', '>', 0)->min('sale_price');
            $saleMax = (clone $available)->where('sale_price', '>', 0)->max('sale_price');
            $count = (clone $available)->count();
        } catch (Throwable) {
            return ['can_show_prices' => true, 'note' => 'تعذر قراءة الأسعار حاليًا — استخدمي search_dresses.'];
        }

        $branches = $this->branches();
        $vat = null;
        foreach ($branches as $b) {
            if (! empty($b['vat_enabled']) && $b['vat_value'] !== null) {
                $vat = (float) $b['vat_value'];
                break;
            }
        }

        return [
            'can_show_prices' => true,
            'available_dresses' => $count,
            'rental' => [
                'min' => $rentMin !== null ? (float) $rentMin : null,
                'max' => $rentMax !== null ? (float) $rentMax : null,
            ],
            'sale' => [
                'min' => $saleMin !== null ? (float) $saleMin : null,
                'max' => $saleMax !== null ? (float) $saleMax : null,
            ],
            'vat_percent' => $vat,
            'note' => 'هذه نطاقات من النظام. السعر النهائي لفستان معيّن عبر get_price / search_dresses فقط.',
        ];
    }

    public function promptBlock(SmartAssistantAgentSettings $settings): string
    {
        $hours = $this->hours($settings);
        $branches = $this->branches();
        $prices = $this->pricePolicy((bool) $settings->can_show_prices);

        $lines = ["\nمعرفة الأتيليه (من النظام — لا تختلقي فروعًا أو أسعارًا خارجها):"];
        $lines[] = '- '.$hours['label'];

        if ($branches === []) {
            $lines[] = '- لا توجد فروع مسجّلة بعد. إذا سُئلت عن العنوان: قولي إن الفريق سيؤكّد أقرب فرع.';
        } else {
            $lines[] = '- الفروع:';
            foreach ($branches as $b) {
                $bit = '  • '.$b['name'];
                if (! empty($b['address'])) {
                    $bit .= ' — '.$b['address'];
                }
                if (! empty($b['phone'])) {
                    $bit .= ' — هاتف '.$b['phone'];
                }
                $lines[] = $bit;
            }
        }

        if (! ($prices['can_show_prices'] ?? false)) {
            $lines[] = '- '.$prices['note'];
        } else {
            $rent = $prices['rental'] ?? [];
            $sale = $prices['sale'] ?? [];
            if (($rent['min'] ?? null) !== null) {
                $lines[] = '- إيجار الفساتين المتاحة تقريبًا من '.number_format((float) $rent['min'], 0)
                    .' إلى '.number_format((float) ($rent['max'] ?? $rent['min']), 0)
                    .' (أكّدي السعر من search_dresses).';
            }
            if (($sale['min'] ?? null) !== null) {
                $lines[] = '- أسعار البيع المتاحة تقريبًا من '.number_format((float) $sale['min'], 0)
                    .' إلى '.number_format((float) ($sale['max'] ?? $sale['min']), 0).'.';
            }
            if (($prices['vat_percent'] ?? null) !== null) {
                $lines[] = '- ضريبة القيمة المضافة: '.rtrim(rtrim(number_format((float) $prices['vat_percent'], 2), '0'), '.').'٪.';
            }
        }

        $lines[] = '- للعنوان أو الفرع استخدمي get_branches. لساعات العمل get_branch_hours. لنطاق الأسعار get_price_policy.';

        return implode("\n", $lines)."\n";
    }
}
