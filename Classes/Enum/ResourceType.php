<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Enum;

enum ResourceType: string
{
    case User = 'User';
    case Group = 'Group';
}
