<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Command\SubmitComment;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SubmitCommentCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'The publisher must be a lowercase slug.')]
        public string $publisher,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $source,
        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public string $content,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        public ?string $authorId,
    ) {
    }
}
