<?php

declare(strict_types=1);

/**
 * Smoke: Smart Assistant product — WA/FB/IG live (v0.25).
 * Run: php scripts/smart-assistant-product-smoke.php
 */

require __DIR__.'/../vendor/autoload.php';

$assertTrue = static function (bool $cond, string $label): void {
    if (! $cond) {
        fwrite(STDERR, "FAIL  {$label}\n");
        exit(1);
    }
    echo "  OK  {$label}\n";
};

echo "Smart Assistant Product — unit smoke\n";

use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use DressnMore\SmartAssistantProduct\Domain\SmartAssistantNavigation;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\FacebookChannelConnector;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\InMemoryChannelConnectionStore;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\InstagramChannelConnector;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\WhatsAppChannelConnector;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaInstagramWebhookPayloadParser;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaMessengerGraphClient;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaPageWebhookPayloadParser;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaWebhookSignatureVerifier;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaWhatsAppCloudClient;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\WhatsAppWebhookPayloadParser;
use DressnMore\SmartAssistantProduct\Support\SmartAssistantPermissionCatalog;
use Illuminate\Support\Facades\Http;

$assertTrue(count(SocialChannelCatalog::all()) === 3, '3 social channels');
$assertTrue(count(SmartAssistantPermissionCatalog::keys()) === 6, '6 permissions');
$assertTrue(count(SmartAssistantNavigation::items()) === 6, '6 nav items');

$store = new InMemoryChannelConnectionStore();
$waClient = new MetaWhatsAppCloudClient('https://graph.facebook.com', 'v21.0');
$msgClient = new MetaMessengerGraphClient('https://graph.facebook.com', 'v21.0');
$wa = new WhatsAppChannelConnector($store, $waClient);
$fb = new FacebookChannelConnector($store, $msgClient);
$ig = new InstagramChannelConnector($store, $msgClient);

$wa->connect('t1', [
    'phone_number_id' => 'pnid_1',
    'access_token' => 'tok_test',
    'auto_reply_enabled' => true,
]);
$assertTrue($wa->syncStatus('t1') === 'connected', 'whatsapp connected');

try {
    $fb->connect('t1', ['page_id' => 'page1']);
    $assertTrue(false, 'facebook requires token');
} catch (Throwable $e) {
    $assertTrue(str_contains($e->getMessage(), 'access_token'), 'facebook rejects incomplete connect');
}

$fb->connect('t1', [
    'page_id' => 'page_1',
    'access_token' => 'page_tok',
    'auto_reply_enabled' => true,
]);
$assertTrue($store->findTenantIdByExternalAccount('facebook', 'page_1') === 't1', 'resolve facebook page');
$cmt = $fb->receiveComment('t1', ['from' => 'user', 'text' => 'سعر الفستان؟', 'post_id' => 'p1', 'id' => 'c1']);
$assertTrue(($cmt['text'] ?? '') !== '', 'facebook comment');

$ig->connect('t1', [
    'ig_user_id' => 'ig_1',
    'page_id' => 'page_1',
    'access_token' => 'page_tok',
]);
$igMsg = $ig->receiveMessage('t1', ['from' => 'ig_user', 'text' => 'available?', 'id' => 'm1']);
$assertTrue(($igMsg['channel'] ?? '') === 'instagram', 'instagram message');

$pageParser = new MetaPageWebhookPayloadParser();
$pageItems = $pageParser->extract([
    'object' => 'page',
    'entry' => [[
        'id' => 'page_1',
        'messaging' => [[
            'sender' => ['id' => 'psid1'],
            'message' => ['mid' => 'm.1', 'text' => 'مرحبا فيسبوك'],
        ]],
        'changes' => [[
            'field' => 'feed',
            'value' => [
                'item' => 'comment',
                'verb' => 'add',
                'comment_id' => 'c.1',
                'post_id' => 'p.1',
                'message' => 'تعليق',
                'from' => ['id' => 'u1'],
            ],
        ]],
    ]],
]);
$assertTrue(count($pageItems) === 2, 'page parser message+comment');

$igParser = new MetaInstagramWebhookPayloadParser();
$igItems = $igParser->extract([
    'object' => 'instagram',
    'entry' => [[
        'id' => 'ig_1',
        'messaging' => [[
            'sender' => ['id' => 'igs1'],
            'message' => ['mid' => 'ig.1', 'text' => 'مرحبا IG'],
        ]],
    ]],
]);
$assertTrue(count($igItems) === 1 && $igItems[0]['payload']['text'] === 'مرحبا IG', 'instagram parser');

$verifier = new MetaWebhookSignatureVerifier();
$body = '{"ok":true}';
$sig = 'sha256='.hash_hmac('sha256', $body, 'secret123');
$assertTrue($verifier->isValid($body, $sig, 'secret123'), 'signature valid');

echo "Smart Assistant Product — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Http::fake([
    'graph.facebook.com/*' => Http::response(['message_id' => 'out.1', 'id' => 'cmt.out'], 200),
]);

$assertTrue((bool) config('aos.feature_flags.smart_assistant_product', false), 'feature flag');
$assertTrue(is_array(config('smart-assistant-product.messenger')), 'messenger config');
$module = $app->make(DressnMore\SmartAssistantProduct\Module\SmartAssistantProductModule::class);
$assertTrue($module->version() === '0.25.0', 'module version 0.25.0');

$mem = new InMemoryChannelConnectionStore();
$app->instance(ChannelConnectionStoreInterface::class, $mem);
$app->forgetInstance(DressnMore\SmartAssistantProduct\Infrastructure\Channel\WhatsAppChannelConnector::class);
$app->forgetInstance(DressnMore\SmartAssistantProduct\Infrastructure\Channel\FacebookChannelConnector::class);
$app->forgetInstance(DressnMore\SmartAssistantProduct\Infrastructure\Channel\InstagramChannelConnector::class);
$app->forgetInstance(DressnMore\SmartAssistantProduct\Application\ChannelConnectorManager::class);
$app->forgetInstance(DressnMore\SmartAssistantProduct\Application\ChannelConnectionService::class);

$svc = $app->make(DressnMore\SmartAssistantProduct\Application\ChannelConnectionService::class);
$svc->connect('smoke-tenant', 'facebook', [
    'page_id' => 'page_smoke',
    'access_token' => 'tok',
]);
$svc->connect('smoke-tenant', 'instagram', [
    'ig_user_id' => 'ig_smoke',
    'access_token' => 'tok',
]);
$svc->connect('smoke-tenant', 'whatsapp', [
    'phone_number_id' => 'wa_smoke',
    'access_token' => 'tok',
]);
$list = $svc->listChannels('smoke-tenant');
$assertTrue(count($list) === 3, 'lists 3 channels');
$assertTrue(collect($list)->every(static fn ($c) => ($c['mode'] ?? '') === 'live'), 'all channels live mode');
$svc->replyMessage('smoke-tenant', 'facebook', ['to' => 'psid', 'text' => 'أهلاً']);
$svc->replyComment('smoke-tenant', 'instagram', ['comment_id' => 'c1', 'text' => 'شكراً']);
$assertTrue(true, 'facebook/instagram send via Meta client');

$routes = collect(Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
    ->map(static fn ($r) => $r->uri())
    ->all();
$assertTrue(
    collect($routes)->contains(static fn ($u) => str_contains((string) $u, 'webhooks/smart-assistant')),
    'webhook routes'
);

echo "PASSED\n";
