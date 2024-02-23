<?php

namespace ProgrammatorDev\Api\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ResponseEvent extends Event
{
    private mixed $contents;

    public function __construct($contents)
    {
        $this->contents = $contents;
    }

    public function getContents(): mixed
    {
        return $this->contents;
    }

    public function setContents($contents): void
    {
        $this->contents = $contents;
    }
}