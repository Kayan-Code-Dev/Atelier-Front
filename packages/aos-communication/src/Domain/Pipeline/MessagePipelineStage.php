<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Pipeline;

enum MessagePipelineStage: string
{
    case Receive = 'receive';
    case Normalize = 'normalize';
    case Validate = 'validate';
    case PolicyCheck = 'policy_check';
    case ConversationRoute = 'conversation_route';
    case AiProcessing = 'ai_processing';
    case ReplyGeneration = 'reply_generation';
    case Send = 'send';
    case TrackDelivery = 'track_delivery';
}
