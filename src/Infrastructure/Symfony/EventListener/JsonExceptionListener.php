<?php

declare(strict_types=1);

namespace SudOuest\Comment\Infrastructure\Symfony\EventListener;

use SudOuest\Comment\Domain\Comment\Exception\CommentNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class JsonExceptionListener
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $this->unwrap($event->getThrowable());

        $event->setResponse(match (true) {
            $exception instanceof ValidationFailedException => $this->validationFailedResponse($exception),
            $exception instanceof CommentNotFoundException => $this->errorResponse($exception->getMessage(), Response::HTTP_NOT_FOUND),
            $exception instanceof HttpExceptionInterface => $this->errorResponse($exception->getMessage(), $exception->getStatusCode()),
            default => $this->errorResponse(
                $this->debug ? $exception->getMessage() : 'An internal error occurred.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ),
        });
    }

    private function unwrap(Throwable $exception): Throwable
    {
        if ($exception instanceof HandlerFailedException) {
            return $exception->getPrevious() ?? $exception;
        }

        return $exception;
    }

    private function validationFailedResponse(ValidationFailedException $exception): JsonResponse
    {
        $violations = array_map(
            static fn (ConstraintViolationInterface $violation): array => [
                'field' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ],
            iterator_to_array($exception->getViolations()),
        );

        return new JsonResponse(
            ['message' => 'Validation failed.', 'violations' => $violations],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function errorResponse(string $message, int $statusCode): JsonResponse
    {
        return new JsonResponse(['message' => $message], $statusCode);
    }
}
