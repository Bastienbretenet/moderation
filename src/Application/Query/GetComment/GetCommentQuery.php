<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query\GetComment;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetCommentQuery
{
    public function __construct(
        #[Assert\Uuid]
        public string $commentId,
    ) {
    }
}
