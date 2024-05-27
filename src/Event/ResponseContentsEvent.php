<?php

namespace ProgrammatorDev\Api\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ResponseContentsEvent extends Event
{
    public function __construct(
        private mixed $contents
    ) {}

    public function getContents(): mixed
    {
        return $this->contents;
    }

    public function setContents($contents): void
    {
        $this->contents = $contents;
    }
}