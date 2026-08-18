<?php

namespace App\Enums;

/**
 * Site areas where a poll can be shown. Add new cases here to extend the admin dropdown.
 *
 * Stored in `polls.page` as the backed string value.
 */
enum PollPage: string
{
    case Home = 'home';
    case Sports = 'sports';
    case FifaWorldCup = 'fifa-world-cup-2026';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Home',
            self::Sports => 'Sports',
            self::FifaWorldCup => 'FIFA World Cup',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function forSelect(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }
}
