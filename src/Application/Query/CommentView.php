<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query;

use DateTimeInterface;
use SudOuest\Comment\Domain\Comment\Comment;

final readonly class CommentView
{
    public function __construct(
        public string $id,
        public string $publisher,
        public string $source,
        public ?string $authorId,
        public string $content,
        public string $status,
        public ?string $rejectionReason,
        public ?string $category,
        public ?string $moderationExplanation,
        public string $submittedAt,
        public ?string $moderatedAt,
    ) {
    }

    public static function fromComment(Comment $comment): self
    {
        return new self(
            $comment->id()->toRfc4122(),
            $comment->publisher(),
            $comment->source(),
            $comment->author()?->externalId(),
            $comment->content(),
            $comment->status()->value,
            $comment->rejectionReason()?->value,
            $comment->category()?->value,
            $comment->moderationExplanation(),
            $comment->submittedAt()->format(DateTimeInterface::ATOM),
            $comment->moderatedAt()?->format(DateTimeInterface::ATOM),
        );
    }
}
