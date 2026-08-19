<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Tool;

/**
 * Built-in tool categories. Custom categories use ToolCategory::custom().
 */
enum ToolCategory: string
{
    case Customer = 'customer';
    case Reservation = 'reservation';
    case Invoice = 'invoice';
    case Payment = 'payment';
    case Order = 'order';
    case Inventory = 'inventory';
    case Knowledge = 'knowledge';
    case Notification = 'notification';
    case Automation = 'automation';
    case Analytics = 'analytics';
    case Communication = 'communication';
    case Administration = 'administration';

    /**
     * @return list<string>
     */
    public static function builtinValues(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
