<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Command\ModerateComment;

use Symfony\Component\Messenger\Attribute\AsMessage;
use Symfony\Component\Validator\Constraints as Assert;

#[AsMessage('async')]
final readonly class ModerateCommentCommand
{
    public function __construct(
        #[Assert\Uuid]
        public string $commentId,
    ) {
    }
}
