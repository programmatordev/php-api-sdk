<?php

namespace ProgrammatorDev\Api\Response;

enum ResponseFormat
{
    case Raw;
    case Json;
    case Xml;
    case Custom;
}
