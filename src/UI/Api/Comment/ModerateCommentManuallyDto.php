<?php

declare(strict_types=1);

namespace SudOuest\Comment\UI\Api\Comment;

use OpenApi\Attributes as OA;

final readonly class ModerateCommentManuallyDto
{
    public function __construct(
        #[OA\Property(description: 'Status chosen by the operator.', enum: ['published', 'rejected'])]
        public string $status,
        #[OA\Property(description: 'Optional reason, kept in the status history.', example: 'Critique légitime, rejet automatique abusif.')]
        public ?string $reason = null,
    ) {
    }
}
