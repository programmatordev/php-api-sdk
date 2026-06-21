<?php

namespace ProgrammatorDev\Api\Builder;

use ProgrammatorDev\Api\Context\ErrorContext;

class ErrorBuilder
{
    /** @var array<int, class-string<\Throwable>|callable(ErrorContext): \Throwable> */
    private array $statusHandlers = [];

    /** @var array<int, callable(ErrorContext): ?\Throwable> */
    private array $handlers = [];

    /**
     * @param class-string<\Throwable>|callable(ErrorContext): \Throwable $handler
     */
    public function status(int $statusCode, string|callable $handler): self
    {
        if (is_string($handler) && ! is_a($handler, \Throwable::class, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Error handler for status %d must be a Throwable class or callable.',
                $statusCode
            ));
        }

        $this->statusHandlers[$statusCode] = $handler;

        return $this;
    }

    /**
     * @param array<int, class-string<\Throwable>|callable(ErrorContext): \Throwable> $handlers
     */
    public function statuses(array $handlers): self
    {
        foreach ($handlers as $statusCode => $handler) {
            $this->status($statusCode, $handler);
        }

        return $this;
    }

    /**
     * @param callable(ErrorContext): ?\Throwable $handler
     */
    public function when(callable $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function throwIfMatched(ErrorContext $context): void
    {
        $handler = $this->statusHandlers[$context->statusCode()] ?? null;

        if (is_string($handler)) {
            throw new $handler();
        }

        if ($handler !== null) {
            $throwable = $handler($context);

            if (! $throwable instanceof \Throwable) {
                throw new \UnexpectedValueException('Status error handler must return a Throwable.');
            }

            throw $throwable;
        }

        foreach ($this->handlers as $handler) {
            $throwable = $handler($context);

            if ($throwable instanceof \Throwable) {
                throw $throwable;
            }

            if ($throwable !== null) {
                throw new \UnexpectedValueException('Error handler must return a Throwable or null.');
            }
        }
    }
}
