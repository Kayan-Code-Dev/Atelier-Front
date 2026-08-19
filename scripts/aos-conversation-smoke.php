<?php

declare(strict_types=1);

/**
 * Smoke test for AOS Conversation Engine (Sprint 2).
 * Run: php scripts/aos-conversation-smoke.php
 */

use DressnMore\Aos\Conversation\Application\ConversationManager;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationFactory;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationStatus;
use DressnMore\Aos\Conversation\Domain\Conversation\Exceptions\IllegalStateTransition;
use DressnMore\Aos\Conversation\Domain\Conversation\TenantScopeId;
use DressnMore\Aos\Conversation\Domain\Message\MessageAuthorKind;
use DressnMore\Aos\Conversation\Domain\Message\MessageContent;
use DressnMore\Aos\Conversation\Domain\Message\MessageDirection;
use DressnMore\Aos\Conversation\Domain\Timeline\TimelineEventType;
use DressnMore\Aos\Conversation\Infrastructure\Persistence\InMemoryConversationRepository;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;

$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    if ($cond) {
        echo "  OK  {$label}\n";
    } else {
        echo " FAIL {$label}\n";
        $failed++;
    }
};

echo "AOS Conversation Engine — domain smoke\n";

$factory = new ConversationFactory();
$repo = new InMemoryConversationRepository();

$c = $factory->createForTenant('smoke-tenant');
$assertTrue($c->status() === ConversationStatus::New, 'create starts as New');
$assertTrue($c->timeline()->hasType(TimelineEventType::ConversationStarted), 'timeline started');

$c->activate();
$assertTrue($c->status() === ConversationStatus::Active, 'activate → Active');
$assertTrue($c->openSession() !== null, 'session opened');

$c->addMessage(MessageDirection::Inbound, MessageAuthorKind::Customer, new MessageContent('Hi'));
$assertTrue(count($c->messages()) === 1, 'message added');
$assertTrue($c->timeline()->hasType(TimelineEventType::MessageAdded), 'timeline message');

$c->assignHuman();
$assertTrue($c->ownership() === ConversationOwnership::Human, 'assign human');

$c->returnToAi();
$assertTrue($c->ownership() === ConversationOwnership::AI, 'return to AI');

$c->pause();
$assertTrue($c->status() === ConversationStatus::Paused, 'pause');

$c->resume();
$assertTrue($c->status() === ConversationStatus::Active, 'resume');

$c->close();
$assertTrue($c->status() === ConversationStatus::Closed, 'close');

$c->archive();
$assertTrue($c->status() === ConversationStatus::Archived, 'archive');

$repo->save($c);
$assertTrue($repo->findById($c->id()) !== null, 'in-memory save/find');

$illegal = false;
try {
    $bad = $factory->createForTenant('x');
    $bad->archive();
} catch (IllegalStateTransition) {
    $illegal = true;
}
$assertTrue($illegal, 'illegal New→Archived rejected');

echo "AOS Conversation Engine — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $registry */
$registry = $app->make(ModuleRegistryInterface::class);
$assertTrue($registry->has('aos.conversation'), 'module aos.conversation registered');

/** @var ConversationManager $manager */
$manager = $app->make(ConversationManager::class);
$managed = $manager->startConversation(TenantScopeId::fromString('smoke-laravel'));
$assertTrue($managed->status() === ConversationStatus::Active, 'manager starts Active');

if ($failed === 0) {
    echo "PASSED\n";
    exit(0);
}

echo "FAILED ({$failed})\n";
exit(1);
