<?php

declare(strict_types=1);

namespace App\Contracts\AiSales;

/**
 * Shared sales-agent context consumed by the existing AI Sales Core.
 * DressnMore and tenant agents both implement this — no second engine.
 *
 * @phpstan-type PlanSnapshot array{
 *   slug: string,
 *   name: string,
 *   price: float|null,
 *   currency: string|null,
 *   billing_period: string|null,
 *   description: string|null,
 *   limits: array<string, mixed>,
 *   features: list<array{key: string, label: string, included: bool, value: mixed}>,
 *   upgrade_to: string|null
 * }
 */
interface SalesAgentContext
{
    public function businessType(): string;

    public function productIdentity(): string;

    public function productDescription(): string;

    /**
     * @return list<PlanSnapshot>
     */
    public function plans(): array;

    /**
     * @return array<string, mixed>
     */
    public function salesPolicies(): array;

    /**
     * @return array<string, mixed>
     */
    public function handoffRules(): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
