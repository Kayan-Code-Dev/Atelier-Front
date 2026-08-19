<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\SmartAssistant\Contracts\Registry\ChannelRegistryInterface;
use DressnMore\SmartAssistant\Domain\Channel\Channel;
use DressnMore\SmartAssistant\Domain\Channel\ChannelTypeCatalog;
use DressnMore\SmartAssistant\Registry\DescriptorChannel;
use DressnMore\SmartAssistantProduct\Application\ChannelConnectionService;
use DressnMore\SmartAssistantProduct\Application\ChannelConnectorManager;
use DressnMore\SmartAssistantProduct\Application\SmartAssistantAccessGate;
use DressnMore\SmartAssistantProduct\Application\TenantAtelierKnowledge;
use DressnMore\SmartAssistantProduct\Application\AiQuotaService;
use DressnMore\SmartAssistantProduct\Application\MetaSocialAutoReplyService;
use DressnMore\SmartAssistantProduct\Application\WhatsAppAutoReplyService;
use DressnMore\SmartAssistantProduct\Application\WhatsAppEmbeddedSignupService;
use DressnMore\SmartAssistantProduct\Application\WhatsAppProfileSync;
use DressnMore\SmartAssistantProduct\Application\WhatsAppSalesAgentService;
use DressnMore\SmartAssistantProduct\Application\TenantWhatsAppOutbound;
use DressnMore\SmartAssistantProduct\Application\TenantInvoicePdf;
use DressnMore\SmartAssistantProduct\Application\TenantWhatsAppInvoiceNotifier;
use DressnMore\SmartAssistantProduct\Application\TenantWhatsAppReminderService;
use DressnMore\SmartAssistantProduct\Console\SendAtelierWhatsAppRemindersCommand;
use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use DressnMore\SmartAssistantProduct\Http\Middleware\EnsureSmartAssistantEnabled;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\EloquentChannelConnectionStore;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\FacebookChannelConnector;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\InstagramChannelConnector;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\WhatsAppChannelConnector;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaEmbeddedSignupClient;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaInstagramWebhookPayloadParser;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaMessengerGraphClient;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaPageWebhookPayloadParser;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaWebhookSignatureVerifier;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaWhatsAppCloudClient;
use DressnMore\SmartAssistantProduct\Infrastructure\WhatsAppWeb\WhatsAppGatewayClient;
use DressnMore\SmartAssistantProduct\Infrastructure\WhatsAppWeb\WhatsAppWebChannelConnector;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\WhatsAppWebhookPayloadParser;
use DressnMore\SmartAssistantProduct\Module\SmartAssistantProductModule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class SmartAssistantProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/smart-assistant-product.php',
            'smart-assistant-product'
        );

        $this->app->singleton(ChannelConnectionStoreInterface::class, EloquentChannelConnectionStore::class);
        $this->app->singleton(MetaWhatsAppCloudClient::class);
        $this->app->singleton(WhatsAppGatewayClient::class);
        $this->app->singleton(MetaMessengerGraphClient::class);
        $this->app->singleton(MetaEmbeddedSignupClient::class);
        $this->app->singleton(MetaWebhookSignatureVerifier::class);
        $this->app->singleton(WhatsAppWebhookPayloadParser::class);
        $this->app->singleton(MetaPageWebhookPayloadParser::class);
        $this->app->singleton(MetaInstagramWebhookPayloadParser::class);
        $this->app->singleton(WhatsAppChannelConnector::class);
        $this->app->singleton(WhatsAppWebChannelConnector::class);
        $this->app->singleton(FacebookChannelConnector::class);
        $this->app->singleton(InstagramChannelConnector::class);
        $this->app->singleton(WhatsAppProfileSync::class);
        $this->app->singleton(TenantAtelierKnowledge::class);
        $this->app->singleton(AiQuotaService::class);
        $this->app->singleton(WhatsAppSalesAgentService::class);
        $this->app->singleton(TenantInvoicePdf::class);
        $this->app->singleton(TenantWhatsAppOutbound::class);
        $this->app->singleton(TenantWhatsAppInvoiceNotifier::class);
        $this->app->singleton(TenantWhatsAppReminderService::class);
        $this->app->singleton(ChannelConnectorManager::class);
        $this->app->singleton(ChannelConnectionService::class);
        $this->app->singleton(WhatsAppAutoReplyService::class);
        $this->app->singleton(MetaSocialAutoReplyService::class);
        $this->app->singleton(WhatsAppEmbeddedSignupService::class);
        $this->app->singleton(SmartAssistantAccessGate::class);
        $this->app->singleton(SmartAssistantProductModule::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/smart-assistant-product.php' => config_path('smart-assistant-product.php'),
        ], 'smart-assistant-product-config');

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('smart-assistant.feature', EnsureSmartAssistantEnabled::class);

        $this->registerModule();
        $this->registerSocialChannels();
        $this->loadWebhookRoutes();

        $this->commands([
            SendAtelierWhatsAppRemindersCommand::class,
        ]);
    }

    private function registerModule(): void
    {
        if (! $this->app->bound(ModuleRegistryInterface::class)) {
            return;
        }

        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('platform.smart-assistant')) {
            $registry->add($this->app->make(SmartAssistantProductModule::class));
        }
    }

    private function registerSocialChannels(): void
    {
        if (! $this->app->bound(ChannelRegistryInterface::class)) {
            return;
        }

        /** @var ChannelRegistryInterface $channels */
        $channels = $this->app->make(ChannelRegistryInterface::class);

        $defs = [
            ['id' => 'sa.whatsapp', 'type' => ChannelTypeCatalog::WHATSAPP, 'name' => 'WhatsApp'],
            ['id' => 'sa.facebook', 'type' => ChannelTypeCatalog::FACEBOOK, 'name' => 'Facebook'],
            ['id' => 'sa.instagram', 'type' => ChannelTypeCatalog::INSTAGRAM, 'name' => 'Instagram'],
            ['id' => 'sa.messenger', 'type' => ChannelTypeCatalog::MESSENGER, 'name' => 'Messenger'],
        ];

        foreach ($defs as $def) {
            if ($channels->has($def['id'])) {
                continue;
            }
            $channels->register(new DescriptorChannel(
                new Channel($def['id'], $def['type'], $def['name'], true)
            ));
        }
    }

    private function loadWebhookRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        // Must be under /api/... to match documented Meta Callback URL and FE webhook_url.
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes/webhooks-smart-assistant.php');

        // Backward-compatible alias (pre-fix path without /api).
        Route::middleware('api')
            ->group(__DIR__.'/../routes/webhooks-smart-assistant.php');

        // Embedded Signup browser redirect (public).
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes/embedded-signup.php');

        // Baileys gateway callbacks (QR pairing inbound + state).
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes/webhooks-whatsapp-gateway.php');
    }
}
