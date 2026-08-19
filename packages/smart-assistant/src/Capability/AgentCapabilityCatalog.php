<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Capability;

use DressnMore\SmartAssistant\Domain\Agent\Capability;
use DressnMore\SmartAssistant\Registry\InMemoryCapabilityRegistry;

/**
 * Official capability definitions per agent type (architecture only).
 */
final class AgentCapabilityCatalog
{
    /**
     * @return list<Capability>
     */
    public function definitions(): array
    {
        return [
            // Sales
            new Capability('sales.lead_qualification', 'sales', 'Lead Qualification'),
            new Capability('sales.booking', 'sales', 'Booking'),
            new Capability('sales.reservation_assistance', 'sales', 'Reservation Assistance'),
            new Capability('sales.follow_up', 'sales', 'Follow-up'),
            new Capability('sales.upselling', 'sales', 'Upselling'),
            new Capability('sales.cross_selling', 'sales', 'Cross-selling'),
            // Support
            new Capability('support.faq', 'support', 'FAQ'),
            new Capability('support.complaint_handling', 'support', 'Complaint Handling'),
            new Capability('support.order_status', 'support', 'Order Status'),
            new Capability('support.customer_assistance', 'support', 'Customer Assistance'),
            // Marketing
            new Capability('marketing.campaign_planning', 'marketing', 'Campaign Planning'),
            new Capability('marketing.offer_recommendations', 'marketing', 'Offer Recommendations'),
            new Capability('marketing.audience_segmentation', 'marketing', 'Audience Segmentation'),
            new Capability('marketing.performance_analysis', 'marketing', 'Performance Analysis'),
            // Social
            new Capability('social.content_scheduling', 'social', 'Content Scheduling'),
            new Capability('social.comment_moderation', 'social', 'Comment Moderation'),
            new Capability('social.message_replies', 'social', 'Message Replies'),
            new Capability('social.post_publishing', 'social', 'Post Publishing'),
            // Analytics
            new Capability('analytics.kpi_monitoring', 'analytics', 'KPI Monitoring'),
            new Capability('analytics.revenue_analysis', 'analytics', 'Revenue Analysis'),
            new Capability('analytics.customer_insights', 'analytics', 'Customer Insights'),
            // Automation
            new Capability('automation.scheduled_jobs', 'automation', 'Scheduled Jobs'),
            new Capability('automation.trigger_management', 'automation', 'Trigger Management'),
            new Capability('automation.business_automation', 'automation', 'Business Automation'),
        ];
    }

    public function seed(InMemoryCapabilityRegistry $registry): void
    {
        foreach ($this->definitions() as $capability) {
            $registry->register($capability);
        }
    }
}
