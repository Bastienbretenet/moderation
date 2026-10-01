<?php

declare(strict_types=1);

namespace SudOuest\Comment\UI\Api\Comment;

use OpenApi\Attributes as OA;

final readonly class SubmitCommentDto
{
    public function __construct(
        #[OA\Property(description: 'Publisher slug.', example: 'sudouest')]
        public string $publisher,
        #[OA\Property(description: 'Commented article or post identifier.', example: 'article-123')]
        public string $source,
        #[OA\Property(example: 'Très bon article.')]
        public string $content,
        #[OA\Property(description: 'Author user identifier.', example: 'user-42')]
        public ?string $authorId = null,
    ) {
    }
}
