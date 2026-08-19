<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Pipeline;

use DressnMore\Aos\Communication\Domain\Attachment\AttachmentManager;
use DressnMore\Aos\Communication\Domain\Channel\ChannelResolver;
use DressnMore\Aos\Communication\Domain\Channel\ChannelRegistryInterface;
use DressnMore\Aos\Communication\Domain\Delivery\DeliveryManager;
use DressnMore\Aos\Communication\Domain\Delivery\DeliveryStatus;
use DressnMore\Aos\Communication\Domain\Message\MessageNormalizer;
use DressnMore\Aos\Communication\Domain\Message\MessageValidator;
use DressnMore\Aos\Communication\Domain\Policy\ChannelPolicies;
use DressnMore\Aos\Communication\Domain\Routing\ConversationRouter;

final class MessagePipeline
{
    public function __construct(
        private readonly ChannelResolver $resolver,
        private readonly ChannelRegistryInterface $registry,
        private readonly MessageNormalizer $normalizer,
        private readonly MessageValidator $validator,
        private readonly ChannelPolicies $policies,
        private readonly AttachmentManager $attachments,
        private readonly ConversationRouter $router,
        private readonly DeliveryManager $delivery,
    ) {}

    public function process(MessagePipelineBag $bag): MessagePipelineBag
    {
        $bag->mark(MessagePipelineStage::Receive->value);

        $channel = $this->resolver->resolve($bag->payload(), $bag->tenantId());
        if ($channel === null) {
            $bag->error('channel_not_resolved');
            return $bag;
        }
        $bag->setChannel($channel);

        $adapter = $this->registry->adapter($channel, $bag->tenantId());
        if ($adapter === null || ! $adapter->validateWebhook($bag->payload())) {
            $bag->error('webhook_validation_failed');
            return $bag;
        }

        $message = $this->normalizer->normalize($bag->payload(), $adapter);
        $bag->setMessage($message);
        $bag->mark(MessagePipelineStage::Normalize->value);

        $errors = $this->validator->validate($message);
        foreach ($message->attachments() as $attachment) {
            if (! $this->attachments->isValid($attachment)) {
                $errors[] = 'invalid_attachment';
            }
        }
        if ($errors !== []) {
            foreach ($errors as $err) { $bag->error($err); }
            return $bag;
        }
        $bag->mark(MessagePipelineStage::Validate->value);

        if (! $this->policies->allows($message)) {
            $bag->error('policy_blocked_message');
            return $bag;
        }
        $bag->mark(MessagePipelineStage::PolicyCheck->value);

        $conversationId = $this->router->route($message);
        $bag->setConversationId($conversationId);
        $bag->mark(MessagePipelineStage::ConversationRoute->value);

        $bag->mark(MessagePipelineStage::AiProcessing->value);
        $bag->mark(MessagePipelineStage::ReplyGeneration->value);

        $sent = $adapter->sendOutbound($message);
        if (! $sent) {
            $this->delivery->track($message->id(), DeliveryStatus::Failed, 'adapter_send_failed');
            $bag->error('send_failed');
            return $bag;
        }
        $bag->markSent();
        $bag->mark(MessagePipelineStage::Send->value);

        $this->delivery->track($message->id(), DeliveryStatus::Sent);
        $bag->mark(MessagePipelineStage::TrackDelivery->value);

        return $bag;
    }
}
