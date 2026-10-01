<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\Infrastructure\Moderation\OpenRouter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SudOuest\Comment\Domain\Comment\IllegalContentCategory;
use SudOuest\Comment\Domain\Moderation\Exception\ModerationFailedException;
use SudOuest\Comment\Infrastructure\Moderation\OpenRouter\ModerationResponseParser;

final class ModerationResponseParserTest extends TestCase
{
    public function testPublishVerdictIsApproved(): void
    {
        $decision = (new ModerationResponseParser())->parse(self::completionWithContent(
            '{"verdict": "publish", "category": null, "explanation": "Critique légitime."}',
        ));

        self::assertNull($decision->illegalContentCategory);
        self::assertSame('Critique légitime.', $decision->explanation);
    }

    public function testRejectVerdictCarriesItsCategory(): void
    {
        $decision = (new ModerationResponseParser())->parse(self::completionWithContent(
            '{"verdict": "reject", "category": "insult", "explanation": "Injure envers un tiers."}',
        ));

        self::assertSame(IllegalContentCategory::Insult, $decision->illegalContentCategory);
        self::assertSame('Injure envers un tiers.', $decision->explanation);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCompletionProvider(): iterable
    {
        yield 'completion is not JSON' => ['<html>Bad gateway</html>'];
        yield 'completion is a JSON scalar' => ['"ok"'];
        yield 'completion has no choices' => ['{"id": "gen-1"}'];
        yield 'choices is empty' => ['{"choices": []}'];
        yield 'message has no content' => ['{"choices": [{"message": {"role": "assistant"}}]}'];
        yield 'content is not a string' => ['{"choices": [{"message": {"content": 42}}]}'];
        yield 'content is not JSON' => [self::completionWithContent('Je publie ce commentaire.')];
        yield 'content is truncated JSON' => [self::completionWithContent('{"verdict": "reject", "categ')];
        yield 'content is a JSON scalar' => [self::completionWithContent('"publish"')];
        yield 'verdict is missing' => [self::completionWithContent('{"category": null, "explanation": "RAS."}')];
        yield 'verdict is unknown' => [self::completionWithContent('{"verdict": "maybe", "category": null, "explanation": "RAS."}')];
        yield 'verdict is not a string' => [self::completionWithContent('{"verdict": true, "category": null, "explanation": "RAS."}')];
        yield 'verdict has wrong case' => [self::completionWithContent('{"verdict": "PUBLISH", "category": null, "explanation": "RAS."}')];
        yield 'explanation is missing' => [self::completionWithContent('{"verdict": "publish", "category": null}')];
        yield 'explanation is blank' => [self::completionWithContent('{"verdict": "publish", "category": null, "explanation": "  "}')];
        yield 'explanation is not a string' => [self::completionWithContent('{"verdict": "publish", "category": null, "explanation": ["RAS"]}')];
        yield 'publish carries a category' => [self::completionWithContent('{"verdict": "publish", "category": "insult", "explanation": "RAS."}')];
        yield 'reject has no category' => [self::completionWithContent('{"verdict": "reject", "category": null, "explanation": "Injure."}')];
        yield 'reject has unknown category' => [self::completionWithContent('{"verdict": "reject", "category": "spam", "explanation": "Publicité."}')];
        yield 'reject category is not a string' => [self::completionWithContent('{"verdict": "reject", "category": 3, "explanation": "Injure."}')];
    }

    #[DataProvider('invalidCompletionProvider')]
    public function testInvalidCompletionFailsPermanently(string $completionBody): void
    {
        $this->expectException(ModerationFailedException::class);

        (new ModerationResponseParser())->parse($completionBody);
    }

    private static function completionWithContent(string $content): string
    {
        return json_encode(
            ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]],
            JSON_THROW_ON_ERROR,
        );
    }
}
