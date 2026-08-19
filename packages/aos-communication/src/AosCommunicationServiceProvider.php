<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication;

use DressnMore\Aos\Communication\Application\CommunicationHub;
use DressnMore\Aos\Communication\Contracts\CommunicationHubInterface;
use DressnMore\Aos\Communication\Domain\Attachment\AttachmentManager;
use DressnMore\Aos\Communication\Domain\Attachment\MediaManager;
use DressnMore\Aos\Communication\Domain\Channel\ChannelManager;
use DressnMore\Aos\Communication\Domain\Channel\ChannelRegistry;
use DressnMore\Aos\Communication\Domain\Channel\ChannelRegistryInterface;
use DressnMore\Aos\Communication\Domain\Channel\ChannelResolver;
use DressnMore\Aos\Communication\Domain\Comment\CommentFlow;
use DressnMore\Aos\Communication\Domain\Delivery\DeliveryManager;
use DressnMore\Aos\Communication\Domain\Delivery\ReadReceiptManager;
use DressnMore\Aos\Communication\Domain\Delivery\TypingIndicatorManager;
use DressnMore\Aos\Communication\Domain\Message\InboundDispatcher;
use DressnMore\Aos\Communication\Domain\Message\MessageNormalizer;
use DressnMore\Aos\Communication\Domain\Message\MessageValidator;
use DressnMore\Aos\Communication\Domain\Message\OutboundDispatcher;
use DressnMore\Aos\Communication\Domain\Message\WebhookGateway;
use DressnMore\Aos\Communication\Domain\Pipeline\MessagePipeline;
use DressnMore\Aos\Communication\Domain\Policy\ChannelPolicies;
use DressnMore\Aos\Communication\Domain\Routing\ConversationRouter;
use DressnMore\Aos\Communication\Domain\Session\ChannelSessionManager;
use DressnMore\Aos\Communication\Infrastructure\Bootstrap\BuiltinChannelCatalogBootstrap;
use DressnMore\Aos\Communication\Module\CommunicationModule;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use Illuminate\Support\ServiceProvider;

final class AosCommunicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChannelRegistry::class);
        $this->app->singleton(ChannelRegistryInterface::class, ChannelRegistry::class);
        $this->app->singleton(ChannelManager::class);
        $this->app->singleton(ChannelResolver::class);
        $this->app->singleton(MessageNormalizer::class);
        $this->app->singleton(MessageValidator::class);
        $this->app->singleton(ChannelPolicies::class);
        $this->app->singleton(AttachmentManager::class);
        $this->app->singleton(MediaManager::class);
        $this->app->singleton(ConversationRouter::class);
        $this->app->singleton(DeliveryManager::class);
        $this->app->singleton(ReadReceiptManager::class);
        $this->app->singleton(TypingIndicatorManager::class);
        $this->app->singleton(CommentFlow::class);
        $this->app->singleton(ChannelSessionManager::class);
        $this->app->singleton(WebhookGateway::class);
        $this->app->singleton(BuiltinChannelCatalogBootstrap::class);
        $this->app->singleton(MessagePipeline::class);
        $this->app->singleton(InboundDispatcher::class);
        $this->app->singleton(OutboundDispatcher::class);
        $this->app->singleton(CommunicationHub::class);
        $this->app->singleton(CommunicationHubInterface::class, CommunicationHub::class);
        $this->app->singleton(CommunicationModule::class);
    }

    public function boot(): void
    {
        $this->app->make(BuiltinChannelCatalogBootstrap::class)->seed();

        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('aos.communication')) {
            $registry->add($this->app->make(CommunicationModule::class));
        }
    }
}
