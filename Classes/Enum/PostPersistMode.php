<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Enum;

enum PostPersistMode
{
    case Update;
    case Create;
    case Patch;
}
