<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Timeline;

/**
 * Ordered collection of timeline events for a Conversation.
 */
final class Timeline
{
    /** @var list<TimelineEvent> */
    private array $events = [];

    public function record(TimelineEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<TimelineEvent>
     */
    public function events(): array
    {
        return $this->events;
    }

    public function count(): int
    {
        return count($this->events);
    }

    public function hasType(TimelineEventType $type): bool
    {
        foreach ($this->events as $event) {
            if ($event->type() === $type) {
                return true;
            }
        }

        return false;
    }
}
