<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query\GetAuthor;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetAuthorQuery
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $authorId,
    ) {
    }
}
