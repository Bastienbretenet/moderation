<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query\GetCommentStatusHistory;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetCommentStatusHistoryQuery
{
    public function __construct(
        #[Assert\Uuid]
        public string $commentId,
    ) {
    }
}
