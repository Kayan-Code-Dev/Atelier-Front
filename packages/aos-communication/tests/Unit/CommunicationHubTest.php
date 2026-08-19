<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Tests\Unit;

use DressnMore\Aos\Communication\Domain\Attachment\Attachment;
use DressnMore\Aos\Communication\Domain\Attachment\AttachmentManager;
use DressnMore\Aos\Communication\Domain\Attachment\AttachmentType;
use DressnMore\Aos\Communication\Domain\Channel\ChannelAccount;
use DressnMore\Aos\Communication\Domain\Channel\ChannelRegistry;
use DressnMore\Aos\Communication\Domain\Channel\ChannelResolver;
use DressnMore\Aos\Communication\Domain\Channel\ChannelType;
use DressnMore\Aos\Communication\Domain\Comment\CommentFlow;
use DressnMore\Aos\Communication\Domain\Delivery\DeliveryManager;
use DressnMore\Aos\Communication\Domain\Delivery\DeliveryStatus;
use DressnMore\Aos\Communication\Domain\Message\MessageNormalizer;
use DressnMore\Aos\Communication\Domain\Message\MessageValidator;
use DressnMore\Aos\Communication\Domain\Pipeline\MessagePipeline;
use DressnMore\Aos\Communication\Domain\Pipeline\MessagePipelineBag;
use DressnMore\Aos\Communication\Domain\Policy\ChannelPolicies;
use DressnMore\Aos\Communication\Domain\Routing\ConversationRouter;
use DressnMore\Aos\Communication\Infrastructure\InMemory\StubChannelAdapter;
use PHPUnit\Framework\TestCase;

final class CommunicationHubTest extends TestCase
{
    private MessagePipeline $pipeline;
    private ChannelRegistry $registry;
    private DeliveryManager $delivery;

    protected function setUp(): void
    {
        $this->registry = new ChannelRegistry();
        $this->registry->register(new ChannelAccount(ChannelType::WebChat, 'webchat-default'), new StubChannelAdapter(ChannelType::WebChat));
        $this->delivery = new DeliveryManager();

        $this->pipeline = new MessagePipeline(
            new ChannelResolver($this->registry),
            $this->registry,
            new MessageNormalizer(),
            new MessageValidator(),
            new ChannelPolicies(),
            new AttachmentManager(),
            new ConversationRouter(),
            $this->delivery,
        );
    }

    public function test_message_normalization_and_pipeline_success(): void
    {
        $bag = $this->pipeline->process(new MessagePipelineBag([
            'channel' => 'web_chat',
            'conversation_id' => 'conv-1',
            'sender' => 'customer-1',
            'receiver' => 'agent-1',
            'text' => 'hello',
        ]));

        $this->assertSame([], $bag->errors());
        $this->assertNotNull($bag->message());
        $this->assertTrue($bag->outboundSent());
    }

    public function test_conversation_routing_and_channel_resolution(): void
    {
        $bag = $this->pipeline->process(new MessagePipelineBag([
            'channel' => 'web_chat',
            'conversation_id' => 'conv-route',
            'sender' => 'c',
            'receiver' => 'a',
            'text' => 'route',
        ]));

        $this->assertSame('conv-route', $bag->conversationId());
        $this->assertSame(ChannelType::WebChat, $bag->channel());
    }

    public function test_attachment_validation_and_delivery_states(): void
    {
        $invalid = new Attachment(AttachmentType::Image, '');
        $manager = new AttachmentManager();
        $this->assertFalse($manager->isValid($invalid));

        $bag = $this->pipeline->process(new MessagePipelineBag([
            'channel' => 'web_chat',
            'conversation_id' => 'conv-2',
            'sender' => 'customer-2',
            'receiver' => 'agent-2',
            'text' => 'ok',
        ]));
        $record = $this->delivery->get($bag->message()->id());
        $this->assertNotNull($record);
        $this->assertSame(DeliveryStatus::Sent, $record->status());
    }

    public function test_comment_classification_and_retry_policy_contract(): void
    {
        $flow = new CommentFlow();
        $intent = $flow->classify('please DM me');
        $this->assertTrue($flow->needsPublicReply($intent));
        $this->assertTrue($flow->needsPrivateConversation($intent));

        $this->assertSame(2, (new ChannelPolicies())->retryAttempts());
    }

    public function test_normalized_message_requires_content(): void
    {
        $bag = $this->pipeline->process(new MessagePipelineBag([
            'channel' => 'web_chat',
            'conversation_id' => 'conv-3',
            'sender' => 'customer-3',
            'receiver' => 'agent-3',
            'text' => '',
        ]));

        $this->assertContains('empty_content', $bag->errors());
    }
}
