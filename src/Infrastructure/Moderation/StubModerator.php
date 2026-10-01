<?php

declare(strict_types=1);

namespace SudOuest\Comment\Infrastructure\Moderation;

use LogicException;
use SudOuest\Comment\Domain\Comment\IllegalContentCategory;
use SudOuest\Comment\Domain\Moderation\Exception\ModerationUnavailableException;
use SudOuest\Comment\Domain\Moderation\ModerationDecision;
use SudOuest\Comment\Domain\Moderation\Moderator;

final readonly class StubModerator implements Moderator
{
    private const string MARKER_PATTERN = '/\[moderation:([a-z_]+)\]/';
    private const string UNAVAILABILITY_CODE = 'unavailable';

    public function moderate(string $content): ModerationDecision
    {
        if (preg_match(self::MARKER_PATTERN, $content, $matches) !== 1) {
            return ModerationDecision::approve('Stub moderator found no moderation marker.');
        }

        $code = $matches[1];

        if ($code === self::UNAVAILABILITY_CODE) {
            throw ModerationUnavailableException::because('stub moderator simulated an outage.');
        }

        $category = IllegalContentCategory::tryFrom($code)
            ?? throw new LogicException(sprintf('Unknown moderation marker "%s".', $code));

        return ModerationDecision::reject($category, sprintf('Stub moderator found the "%s" marker.', $code));
    }
}
