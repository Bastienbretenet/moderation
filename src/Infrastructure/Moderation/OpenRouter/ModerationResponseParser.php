<?php

declare(strict_types=1);

namespace SudOuest\Comment\Infrastructure\Moderation\OpenRouter;

use JsonException;
use SudOuest\Comment\Domain\Comment\IllegalContentCategory;
use SudOuest\Comment\Domain\Moderation\Exception\ModerationFailedException;
use SudOuest\Comment\Domain\Moderation\ModerationDecision;

final readonly class ModerationResponseParser
{
    public function parse(string $completionBody): ModerationDecision
    {
        $completion = $this->decodeJsonObject($completionBody, 'completion');
        $decision = $this->decodeJsonObject($this->extractMessageContent($completion), 'message content');

        $explanation = $decision['explanation'] ?? null;
        if (!is_string($explanation) || trim($explanation) === '') {
            throw $this->invalidResponse('the explanation is missing.');
        }

        $verdict = $decision['verdict'] ?? null;
        $category = $decision['category'] ?? null;

        return match (is_string($verdict) ? ModerationVerdict::tryFrom($verdict) : null) {
            ModerationVerdict::Publish => $category === null
                ? ModerationDecision::approve($explanation)
                : throw $this->invalidResponse('a publish verdict must not carry a category.'),
            ModerationVerdict::Reject => ModerationDecision::reject(
                $this->parseCategory(is_string($category) ? $category : null),
                $explanation,
            ),
            null => throw $this->invalidResponse('the verdict is missing or unknown.'),
        };
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodeJsonObject(string $json, string $subject): array
    {
        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw $this->invalidResponse(sprintf('the %s is not valid JSON.', $subject), $exception);
        }

        if (!is_array($decoded)) {
            throw $this->invalidResponse(sprintf('the %s is not a JSON object.', $subject));
        }

        return $decoded;
    }

    /**
     * @param array<array-key, mixed> $completion
     */
    private function extractMessageContent(array $completion): string
    {
        $choices = $completion['choices'] ?? null;
        $firstChoice = is_array($choices) ? ($choices[0] ?? null) : null;
        $message = is_array($firstChoice) ? ($firstChoice['message'] ?? null) : null;
        $content = is_array($message) ? ($message['content'] ?? null) : null;

        if (!is_string($content)) {
            throw $this->invalidResponse('the completion has no message content.');
        }

        return $content;
    }

    private function parseCategory(?string $category): IllegalContentCategory
    {
        if ($category === null) {
            throw $this->invalidResponse('a reject verdict must carry a category code.');
        }

        return IllegalContentCategory::tryFrom($category)
            ?? throw $this->invalidResponse(sprintf('the category "%s" is unknown.', $category));
    }

    private function invalidResponse(string $reason, ?JsonException $previous = null): ModerationFailedException
    {
        return ModerationFailedException::because(sprintf('invalid LLM response, %s', $reason), $previous);
    }
}
