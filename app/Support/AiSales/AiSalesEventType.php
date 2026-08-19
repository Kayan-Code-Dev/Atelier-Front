<?php

declare(strict_types=1);

namespace App\Support\AiSales;

enum AiSalesEventType: string
{
    case LeadCreated = 'LeadCreated';
    case LeadQualified = 'LeadQualified';
    case LeadStageChanged = 'LeadStageChanged';
    case PlanRecommended = 'PlanRecommended';
    case PricingQuestionAsked = 'PricingQuestionAsked';
    case ObjectionDetected = 'ObjectionDetected';
    case ObjectionResolved = 'ObjectionResolved';
    case DemoRequested = 'DemoRequested';
    case TrialRequested = 'TrialRequested';
    case SalesIntentDetected = 'SalesIntentDetected';
    case PurchaseIntentDetected = 'PurchaseIntentDetected';
    case CheckoutRequested = 'CheckoutRequested';
    case HumanHandoffRequested = 'HumanHandoffRequested';
    case FollowUpScheduled = 'FollowUpScheduled';
    case LeadWon = 'LeadWon';
    case LeadLost = 'LeadLost';
    case MessageAdded = 'MessageAdded';
}
