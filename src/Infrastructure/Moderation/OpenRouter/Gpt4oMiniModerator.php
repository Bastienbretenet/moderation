<?php

declare(strict_types=1);

namespace SudOuest\Comment\Infrastructure\Moderation\OpenRouter;

use LogicException;
use SudOuest\Comment\Domain\Comment\IllegalContentCategory;
use SudOuest\Comment\Domain\Moderation\Exception\ModerationFailedException;
use SudOuest\Comment\Domain\Moderation\Exception\ModerationUnavailableException;
use SudOuest\Comment\Domain\Moderation\ModerationDecision;
use SudOuest\Comment\Domain\Moderation\Moderator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class Gpt4oMiniModerator implements Moderator
{
    private const string MODEL = 'openai/gpt-4o-mini';
    private const string SYSTEM_PROMPT_PATH = '/config/moderation/OpenRouter/gpt-4o-mini.md';
    private const int MAX_RESPONSE_TOKENS = 300;
    private const array TRANSIENT_STATUS_CODES = [
        Response::HTTP_REQUEST_TIMEOUT,
        Response::HTTP_TOO_MANY_REQUESTS,
    ];

    private string $systemPrompt;

    public function __construct(
        #[Autowire(service: 'openrouter.client')]
        private HttpClientInterface $openRouterClient,
        private ModerationResponseParser $responseParser,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $this->systemPrompt = $this->loadSystemPrompt($projectDir . self::SYSTEM_PROMPT_PATH);
    }

    public function moderate(string $content): ModerationDecision
    {
        try {
            $response = $this->openRouterClient->request('POST', 'chat/completions', [
                'json' => $this->buildPayload($content),
            ]);
            $statusCode = $response->getStatusCode();
            $completionBody = $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            throw ModerationUnavailableException::because('OpenRouter could not be reached.', $exception);
        }

        if (in_array($statusCode, self::TRANSIENT_STATUS_CODES, true) || $statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            throw ModerationUnavailableException::because(sprintf('OpenRouter answered HTTP %d.', $statusCode));
        }

        if ($statusCode !== Response::HTTP_OK) {
            throw ModerationFailedException::because(sprintf('OpenRouter refused the request with HTTP %d.', $statusCode));
        }

        return $this->responseParser->parse($completionBody);
    }

    private function loadSystemPrompt(string $systemPromptPath): string
    {
        $systemPrompt = is_readable($systemPromptPath) ? file_get_contents($systemPromptPath) : false;
        if ($systemPrompt === false) {
            throw new LogicException(sprintf('The system prompt "%s" cannot be read.', $systemPromptPath));
        }

        return $systemPrompt;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $content): array
    {
        return [
            'model' => self::MODEL,
            'temperature' => 0,
            'max_tokens' => self::MAX_RESPONSE_TOKENS,
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt],
                [
                    'role' => 'user',
                    'content' => json_encode(
                        ['comment' => $content],
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
                    ),
                ],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'moderation_decision',
                    'strict' => true,
                    'schema' => $this->responseSchema(),
                ],
            ],
            'provider' => ['require_parameters' => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        $categoryCodes = array_map(
            static fn (IllegalContentCategory $category): string => $category->value,
            IllegalContentCategory::cases(),
        );

        return [
            'type' => 'object',
            'properties' => [
                'verdict' => [
                    'type' => 'string',
                    'enum' => array_map(
                        static fn (ModerationVerdict $verdict): string => $verdict->value,
                        ModerationVerdict::cases(),
                    ),
                ],
                'category' => ['type' => ['string', 'null'], 'enum' => [...$categoryCodes, null]],
                'explanation' => ['type' => 'string'],
            ],
            'required' => ['verdict', 'category', 'explanation'],
            'additionalProperties' => false,
        ];
    }
}
