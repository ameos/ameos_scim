<?php

declare(strict_types=1);

namespace Ameos\Scim\Enum;

enum ResourceType: string
{
    case User = 'User';
    case Group = 'Group';
}