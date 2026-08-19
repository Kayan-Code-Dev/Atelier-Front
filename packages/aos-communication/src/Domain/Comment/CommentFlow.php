<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Comment;

enum CommentIntent: string { case NeedReply='need_reply'; case NoReply='no_reply'; case EscalatePrivate='escalate_private'; }

final class CommentFlow
{
    public function classify(string $comment): CommentIntent
    {
        $text = mb_strtolower($comment);
        if (str_contains($text, 'inbox') || str_contains($text, 'خاص') || str_contains($text, 'dm')) {
            return CommentIntent::EscalatePrivate;
        }
        if (trim($text) === '' || str_contains($text, 'thanks') || str_contains($text, 'شكرا')) {
            return CommentIntent::NoReply;
        }

        return CommentIntent::NeedReply;
    }

    public function needsPublicReply(CommentIntent $intent): bool
    {
        return $intent === CommentIntent::NeedReply || $intent === CommentIntent::EscalatePrivate;
    }

    public function needsPrivateConversation(CommentIntent $intent): bool
    {
        return $intent === CommentIntent::EscalatePrivate;
    }
}
