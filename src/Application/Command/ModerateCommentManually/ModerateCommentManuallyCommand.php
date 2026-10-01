<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Command\ModerateCommentManually;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ModerateCommentManuallyCommand
{
    public const array MANUAL_MODERATION_STATUSES = ['published', 'rejected'];

    public function __construct(
        #[Assert\Uuid]
        public string $commentId,
        #[Assert\Choice(choices: self::MANUAL_MODERATION_STATUSES)]
        public string $status,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 1000)]
        public ?string $reason,
    ) {
    }
}
