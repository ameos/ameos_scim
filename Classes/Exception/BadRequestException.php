<?php

declare(strict_types=1);

namespace Ameos\Scim\Exception;

use TYPO3\CMS\Core\Error\Http\BadRequestException as HttpBadRequestException;

class BadRequestException extends HttpBadRequestException
{
}
