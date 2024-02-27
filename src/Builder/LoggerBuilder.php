<?php

namespace ProgrammatorDev\Api\Builder;

use Http\Message\Formatter;
use Psr\Log\LoggerInterface;

class LoggerBuilder
{
    private Formatter $formatter;

    public function __construct(
        private LoggerInterface $logger,
        ?Formatter $formatter = null
    )
    {
        $this->formatter = $formatter ?: new Formatter\SimpleFormatter();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function getFormatter(): Formatter
    {
        return $this->formatter;
    }

    public function setFormatter(Formatter $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }
}