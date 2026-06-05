<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Resource;
use ProgrammatorDev\Api\Response;

class RawResource extends Resource
{
    public function fetch(): Response
    {
        return $this->get('/raw');
    }
}
