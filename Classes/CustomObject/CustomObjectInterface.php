<?php

declare(strict_types=1);

namespace Ameos\Scim\CustomObject;

use Ameos\Scim\Enum\Context;

interface CustomObjectInterface
{
    /**
     * return payload for $data
     *
     * @param array $data
     * @param array $configuration
     * @param Context $context
     */
    public function read(array $data, array $configuration, Context $context);

    /**
     * return update $data array
     *
     * @param array $payload
     * @param array $data
     * @param array $configuration
     * @return array
     */
    public function write(array $payload, array $data, array $configuration): array;

    /**
     * return fields associate to properties
     *
     * @param array $configuration
     * @param string $filters
     * @return array|false
     */
    public function getAssociateFields(array $configuration, ?string $filters): array|false;
}
