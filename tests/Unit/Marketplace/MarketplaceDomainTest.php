<?php

declare(strict_types=1);

namespace Tests\Unit\Marketplace;

use App\Support\Marketplace\MarketplaceCategoryCatalog;
use App\Support\Marketplace\MarketplaceGeo;
use App\Support\Marketplace\MarketplaceModule;
use App\Support\PermissionLabels;
use App\Support\PlanFeatureCatalog;
use Tests\TestCase;

class MarketplaceDomainTest extends TestCase
{
    public function test_plan_feature_is_starter(): void
    {
        $this->assertSame('starter', PlanFeatureCatalog::minimumPlanFor(MarketplaceModule::PLAN_FEATURE));
        $this->assertTrue(PlanFeatureCatalog::isBooleanKey(MarketplaceModule::PLAN_FEATURE));
        $this->assertTrue((bool) PlanFeatureCatalog::defaultMatrix()[PlanFeatureCatalog::PLAN_STARTER][MarketplaceModule::PLAN_FEATURE]);
        $this->assertFalse((bool) PlanFeatureCatalog::defaultMatrix()[PlanFeatureCatalog::PLAN_FREE][MarketplaceModule::PLAN_FEATURE]);
    }

    public function test_permissions_are_labeled(): void
    {
        foreach ([
            'marketplace.view',
            'marketplace.products',
            'marketplace.orders',
            'marketplace.bookings',
            'marketplace.website',
            'marketplace.publish',
        ] as $key) {
            $this->assertNotSame($key, PermissionLabels::label($key));
        }
    }

    public function test_categories_match_dashboard(): void
    {
        $this->assertSame('فساتين زفاف', MarketplaceCategoryCatalog::label(MarketplaceCategoryCatalog::WEDDING));
        $this->assertTrue(MarketplaceCategoryCatalog::isValid('evening_dress'));
        $this->assertFalse(MarketplaceCategoryCatalog::isValid('unknown'));
        $this->assertSame('wedding_dress', MarketplaceCategoryCatalog::fromLabelOrKey('فساتين زفاف'));
        $this->assertSame('published', \App\Support\Marketplace\MarketplaceLabelMap::productStatus('منشور'));
        $this->assertSame('new', \App\Support\Marketplace\MarketplaceLabelMap::orderStatus('جديدة'));
        $this->assertTrue(MarketplaceModule::isPublicHost('market.dressnmore.it.com'));
    }

    public function test_geo_distance_cairo_to_giza_is_reasonable(): void
    {
        $km = MarketplaceGeo::kmBetween(30.031, 31.491, 30.051, 31.201);
        $this->assertGreaterThan(20, $km);
        $this->assertLessThan(50, $km);
        $this->assertEqualsWithDelta(0, MarketplaceGeo::kmBetween(30.03, 31.49, 30.03, 31.49), 0.01);
    }
}
