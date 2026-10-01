<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Moderation;

use SudOuest\Comment\Domain\Comment\IllegalContentCategory;

final readonly class ModerationDecision
{
    private function __construct(
        public ?IllegalContentCategory $illegalContentCategory,
        public string $explanation,
    ) {
    }

    public static function approve(string $explanation): self
    {
        return new self(null, $explanation);
    }

    public static function reject(IllegalContentCategory $illegalContentCategory, string $explanation): self
    {
        return new self($illegalContentCategory, $explanation);
    }
}
