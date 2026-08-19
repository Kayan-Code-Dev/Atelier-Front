<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Models\Central\AiSalesKnowledgeItem;
use App\Support\PlanFeatureCatalog;

/**
 * Seeds approved DressnMore knowledge from the feature catalog.
 * Live prices stay on DressnMoreSalesContext — never copied into knowledge as facts.
 */
final class AiSalesKnowledgeSync
{
    public function ensureCatalogKnowledge(): void
    {
        foreach ($this->items() as $item) {
            AiSalesKnowledgeItem::query()->updateOrCreate(
                [
                    'source' => 'catalog',
                    'title' => $item['title'],
                ],
                [
                    'content' => $item['content'],
                    'category' => $item['category'],
                    'status' => 'published',
                ],
            );
        }
    }

    /**
     * @return list<array{title: string, content: string, category: string}>
     */
    public function items(): array
    {
        $featureLines = [];
        foreach (PlanFeatureCatalog::definitions() as $def) {
            if (($def['type'] ?? '') !== 'boolean') {
                continue;
            }
            $featureLines[] = sprintf(
                '- %s (`%s`): %s — minimum plan: %s',
                $def['label'],
                $def['key'],
                $def['description'],
                $def['minimum_plan'],
            );
        }

        $planLines = [];
        foreach (PlanFeatureCatalog::publicPlanSlugs() as $slug) {
            $matrix = PlanFeatureCatalog::defaultMatrix()[$slug] ?? [];
            $planLines[] = sprintf(
                "- %s: branches.max=%s, users.max=%s, invoices.monthly.max=%s. Live price MUST be read from the subscription system, never from this article.",
                ucfirst($slug),
                $matrix['branches.max'] ?? '?',
                $matrix['users.max'] ?? '?',
                $matrix['invoices.monthly.max'] ?? '?',
            );
        }

        return [
            [
                'title' => 'What is DressnMore?',
                'category' => 'product',
                'content' => "DressnMore is a SaaS platform for atelier management. It helps fashion ateliers run rental, sales, tailoring, inventory, invoicing, deliveries, accounting, HR, website, and AI-assisted operations.\n\nWho it is for: fashion ateliers, dress rental houses, tailoring workshops, and multi-branch fashion businesses.\n\nProblems it solves: scattered Excel/WhatsApp operations, missed rentals, weak inventory control, no unified invoicing, and no structured growth path from a single atelier to multiple branches.",
            ],
            [
                'title' => 'DressnMore commercial plans',
                'category' => 'pricing',
                'content' => "Plans (authoritative entitlements from PlanFeatureCatalog; prices from live subscription plans):\n".implode("\n", $planLines)."\n\nNever quote a price that is not returned by DressnMoreSalesContext.plans(). Never invent discounts.",
            ],
            [
                'title' => 'DressnMore feature catalog',
                'category' => 'product',
                'content' => "Authoritative modules (PlanFeatureCatalog):\n".implode("\n", $featureLines),
            ],
            [
                'title' => 'Objection: the system is expensive',
                'category' => 'sales',
                'content' => "Do not discount. Acknowledge the concern, then discover: business size, users, branches, current workflow, current software cost, and pain points. Recommend the lowest fitting plan from DressnMorePlanAdvisor. Offer Free or Starter when the profile is small. Never invent a discount unless a configured policy exists (current policy: discount_requires_human).",
            ],
            [
                'title' => 'Objection: I already use another system',
                'category' => 'sales',
                'content' => "Ask which system, what they like, what is missing, and which problem they want solved. Explain relevant DressnMore advantages (atelier-specific rental + tailoring + inventory + invoicing). Do not attack competitors.",
            ],
            [
                'title' => 'Objection: I only have a small atelier',
                'category' => 'sales',
                'content' => "Recommend Free or Starter depending on website / HR / inventory needs. Free fits 1 branch and 1 user with core operations. Starter adds website, HR, workshop, and inventory movements.",
            ],
            [
                'title' => 'Objection: I have multiple branches',
                'category' => 'sales',
                'content' => "Ask for the branch count and employee count. Use DressnMorePlanAdvisor. Professional supports up to 3 branches / 10 users in the catalog defaults; Business is unlimited. Confirm live limits from the subscription system.",
            ],
        ];
    }
}
