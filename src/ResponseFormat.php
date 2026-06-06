<?php

namespace ProgrammatorDev\Api;

enum ResponseFormat
{
    case Raw;
    case Json;
    case Xml;
    case Custom;
}
