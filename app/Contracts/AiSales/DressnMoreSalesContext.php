<?php

declare(strict_types=1);

namespace App\Contracts\AiSales;

/**
 * DressnMore-specific sales context. Pricing and features come from
 * PlanFeatureCatalog + the live subscription/plan system — never a parallel price book.
 */
interface DressnMoreSalesContext extends SalesAgentContext
{
    public function trialPolicy(): array;

    public function demoProcess(): array;

    public function paymentProcess(): array;

    public function contactRules(): array;

    /**
     * @return list<string>
     */
    public function markets(): array;

    /**
     * @return list<string>
     */
    public function languages(): array;

    /**
     * Feature key → minimum commercial plan, derived from PlanFeatureCatalog.
     *
     * @return array<string, string>
     */
    public function upgradeMapping(): array;

    /**
     * @return list<string>
     */
    public function knowledgePriority(): array;
}
