<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\ConstraintViolationInterface;

final class ValidationExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $validationException = $this->extractValidationException($exception);
        if (!$validationException) {
            return;
        }

        $errors = [];
        foreach ($validationException->getViolations() as $violation) {
            $errors[$this->normalizePropertyPath($violation)][] = $violation->getMessage();
        }

        $event->setResponse(new JsonResponse([
            'error' => 'Validation failed.',
            'errors' => $errors,
        ], 422));
    }

    private function extractValidationException(\Throwable $exception): ?ValidationFailedException
    {
        if ($exception instanceof ValidationFailedException) {
            return $exception;
        }

        if ($exception instanceof HttpExceptionInterface && $exception->getPrevious() instanceof ValidationFailedException) {
            return $exception->getPrevious();
        }

        return null;
    }

    private function normalizePropertyPath(ConstraintViolationInterface $violation): string
    {
        $path = $violation->getPropertyPath();
        return $path !== '' ? $path : 'payload';
    }
}
