<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query\SearchComments;

use SudOuest\Comment\Domain\Comment\ModerationStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SearchCommentsQuery
{
    public const int DEFAULT_PAGE = 1;
    public const int DEFAULT_LIMIT = 20;
    public const int MAX_LIMIT = 100;

    public function __construct(
        #[Assert\Length(max: 100)]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'The publisher must be a lowercase slug.')]
        public ?string $publisher = null,
        #[Assert\Choice(callback: [self::class, 'statusValues'])]
        public ?string $status = null,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        public ?string $source = null,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        public ?string $authorId = null,
        #[Assert\Positive]
        public int $page = self::DEFAULT_PAGE,
        #[Assert\Range(min: 1, max: self::MAX_LIMIT)]
        public int $limit = self::DEFAULT_LIMIT,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function statusValues(): array
    {
        return array_map(
            static fn (ModerationStatus $status): string => $status->value,
            ModerationStatus::cases(),
        );
    }
}
