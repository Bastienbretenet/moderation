<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Moderation;

use SudOuest\Comment\Domain\Moderation\Exception\ModerationUnavailableException;

interface Moderator
{
    /**
     * @throws ModerationUnavailableException when no decision could be obtained, which must never be read as a rejection
     */
    public function moderate(string $content): ModerationDecision;
}
