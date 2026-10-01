<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Moderation;

use SudOuest\Comment\Domain\Moderation\Exception\ModerationFailedException;
use SudOuest\Comment\Domain\Moderation\Exception\ModerationUnavailableException;

interface Moderator
{
    /**
     * Neither exception must ever be read as a rejection.
     *
     * @throws ModerationUnavailableException when the failure is transient and worth retrying
     * @throws ModerationFailedException when the same request would fail again
     */
    public function moderate(string $content): ModerationDecision;
}
