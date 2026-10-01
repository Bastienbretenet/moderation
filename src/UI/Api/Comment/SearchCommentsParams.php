<?php

declare(strict_types=1);

namespace SudOuest\Comment\UI\Api\Comment;

use OpenApi\Attributes as OA;
use SudOuest\Comment\Application\Query\SearchComments\SearchCommentsQuery;

final readonly class SearchCommentsParams
{
    public function __construct(
        #[OA\Property(description: 'Publisher slug.', example: 'sudouest')]
        public ?string $publisher = null,
        #[OA\Property(description: 'Moderation status.', enum: ['pending', 'published', 'rejected'])]
        public ?string $status = null,
        #[OA\Property(description: 'Commented article or post identifier.', example: 'article-123')]
        public ?string $source = null,
        #[OA\Property(description: 'Author user identifier.', example: 'user-42')]
        public ?string $authorId = null,
        #[OA\Property(minimum: 1)]
        public int $page = SearchCommentsQuery::DEFAULT_PAGE,
        #[OA\Property(maximum: SearchCommentsQuery::MAX_LIMIT, minimum: 1)]
        public int $limit = SearchCommentsQuery::DEFAULT_LIMIT,
    ) {
    }
}
